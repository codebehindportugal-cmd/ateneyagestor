import logging, requests, os, time
from typing import Dict, Any, List, Iterator, Optional
from ..models import Product, ProductImage
from .woocommerce import WooClient
import uuid
from datetime import datetime, timedelta, timezone
from email.utils import parsedate_to_datetime
import base64
from dateutil.parser import parse as parse_date  # (mantido se usares noutros sítios)
import json
from urllib.parse import urljoin

log = logging.getLogger(__name__)


class WintouchClient:
    def __init__(self, cfg, batch_size: int = 50):
        self.cfg = cfg
        self.base = cfg.base_url.rstrip("/") + "/api/v1"
        self.batch = batch_size
        self.session = requests.Session()
        self.session.headers.update({
            "Authorization": f"ApiKey {cfg.api_key}",
            "Accept": "application/json",
            "Content-Type": "application/json"
        })
        self.sku_to_id: Dict[str, str] = {}

    # ---------- AUTH JWT (fallback) ----------
    def _authenticate_with_jwt(self) -> str:
        email = self.cfg.login_email
        password = self.cfg.login_password
        tenant_id = base64.b64decode(self.cfg.api_key).decode().split(",")[1]

        url = "https://api.wintouchcloud.com/api/v1/security/authenticate"
        payload = {
            "email": email,
            "password": password,
            "tenantUniqueId": tenant_id
        }

        r = requests.post(url, json=payload, timeout=120)
        r.raise_for_status()
        return r.json().get("token")

    # ---------- HTTP helpers ----------
    def _retry_after_seconds(self, value: Optional[str]) -> float:
        if not value:
            return 2.0

        try:
            return max(0.0, float(value))
        except ValueError:
            pass

        try:
            retry_at = parsedate_to_datetime(value)
            if retry_at.tzinfo is None:
                retry_at = retry_at.replace(tzinfo=timezone.utc)
            return max(0.0, (retry_at - datetime.now(timezone.utc)).total_seconds())
        except Exception:
            return 2.0

    def _request(self, method: str, path: str, timeout: int, **kwargs):
        url = f"{self.base}/{path.lstrip('/')}"
        attempts = 8

        for attempt in range(1, attempts + 1):
            r = self.session.request(method, url, timeout=timeout, **kwargs)

            if r.status_code == 429:
                wait = self._retry_after_seconds(r.headers.get("Retry-After")) + 2
                log.warning(
                    "Wintouch API rate limit (429) em %s %s. A aguardar %.1fs antes de repetir (%d/%d).",
                    method.upper(),
                    path,
                    wait,
                    attempt,
                    attempts,
                )
                time.sleep(wait)
                continue

            if r.status_code in (500, 502, 503, 504) and attempt < attempts:
                wait = min(30.0, 0.5 * (2 ** (attempt - 1)))
                log.warning(
                    "Wintouch API respondeu %s em %s %s. Retry em %.1fs (%d/%d).",
                    r.status_code,
                    method.upper(),
                    path,
                    wait,
                    attempt,
                    attempts,
                )
                time.sleep(wait)
                continue

            r.raise_for_status()
            return r.json()

        r.raise_for_status()
        return r.json()

    def _get(self, path: str, **params):
        return self._request("get", path, timeout=120, params=params or None)

    def _post(self, path: str, json):
        return self._request("post", path, timeout=40, json=json)

    def _post_with_jwt_if_needed(self, path: str, payload: dict):
        try:
            return self._post(path, payload)
        except requests.HTTPError as e:
            if e.response.status_code in (401, 403):
                log.warning("🔐 API Key não autorizada — tentando JWT...")
                token = self._authenticate_with_jwt()
                url = f"{self.base}/{path.lstrip('/')}"
                headers = {
                    "Authorization": f"Bearer {token}",
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                    "x-current-enterprise": "22743f14-3638-4e3a-bd3c-14fc983b3656",
                    "x-current-workstation": "BASE",
                    "x-current-app": "commercial",
                    "x-request-origin": "frontend-commercial"
                }
                r = requests.post(url, headers=headers, json=payload, timeout=40)
                r.raise_for_status()
                return r.json()
            raise

    # ---------- Woo helpers ----------
    def mark_order_as_synced(self, woo_client: WooClient, order_id: int):
        woo_client._put(f"orders/{order_id}", {
            "meta_data": [
                {"key": "wintouch_synced", "value": "true"}
            ]
        })

    def is_order_already_synced(self, order: dict) -> bool:
        for meta in order.get("meta_data", []):
            if meta.get("key") == "wintouch_synced" and meta.get("value") == "true":
                return True
        return False

    # ---------- Datas ----------
    def get_latest_wintouch_update(self, product_id: str) -> Optional[datetime]:
        try:
            product_details = self._get(
                f"products/{product_id}?embed=Product_Prices,Product_Languages"
            )
            product_stock_list = self._post("products/stocks", [product_id])

            product_stock = product_stock_list[0] if product_stock_list else {}

            product_date_str = product_details.get("LastUpdate")
            price_date_str = None
            if product_details.get("ProductPrices"):
                price_date_str = product_details["ProductPrices"][0].get("LastUpdate")

            stock_date_str = product_stock.get("LastUpdate")

            fmt = "%Y-%m-%dT%H:%M:%S.%f"
            dates = []
            for d in [product_date_str, price_date_str, stock_date_str]:
                if d:
                    try:
                        dt = datetime.strptime(d, fmt)
                    except ValueError:
                        try:
                            dt = datetime.strptime(d.split(".")[0], "%Y-%m-%dT%H:%M:%S")
                        except Exception:
                            continue
                    dates.append(dt.replace(tzinfo=timezone.utc))  # timezone-aware

            if not dates:
                log.warning(
                    "⚠ Produto %s sem data de atualização válida. Ignorado.",
                    product_id,
                )
                return None

            return max(dates)

        except Exception as e:
            log.error(
                "❌ Erro ao obter data de atualização do produto Wintouch %s: %s",
                product_id,
                e,
            )
            return None

    # ---------- Produtos ----------
    def iter_products(self, woo_client: Optional[WooClient] = None) -> Iterator[List[Product]]:
        skip = 0
        while True:
            rows = self._get("products", limit=self.batch, skip=skip)
            if not rows:
                break
            ids = [row["ID"] for row in rows]
            stock_map = {s["ProductID"]: s for s in self._post("products/stocks", ids)}

            products: List[Product] = []
            for pid in ids:
                try:
                    p = self._get(f"products/{pid}", embed="VATSales")

                    # 🛑 Ignorar produtos com Product2ndCategoryID proibido
                    if p.get("Product2ndCategoryID") == "45893e11-637c-4c73-9b1f-f5ae40acac80":
                        log.info(
                            "⏩ Produto %s ignorado por ter categoria 2nd proibida.",
                            pid,
                        )
                        continue

                    if p.get("IsService") or not p.get("Enabled", True):
                        continue

                    _ = self.get_latest_wintouch_update(pid)  # calculado para consistência

                    if woo_client:
                        # Mantido para compatibilidade (não é usado diretamente aqui)
                        _woo_product = woo_client._get(
                            "products", {"sku": p["Code"]}
                        )

                    # ---------- IMAGENS ----------
                    try:
                        img_resp = self._get(f"products/{pid}/images")
                        imgs_raw = img_resp.get("Images", []) if isinstance(img_resp, dict) else img_resp
                    except Exception as e:
                        log.warning("Erro ao obter imagens do produto %s: %s", pid, e)
                        imgs_raw = []

                    # Base do .env: IMAGES_BASE_URL=https://neoapp.blob.core.windows.net/.../images/
                    base_images = os.getenv("IMAGES_BASE_URL", "").strip()
                    if base_images and not base_images.endswith("/"):
                        base_images += "/"

                    if not base_images:
                        log.warning(
                            "⚠ IMAGES_BASE_URL não definido no ambiente. "
                            "URLs relativos (ImageUrl) podem não funcionar."
                        )

                    imgs: List[ProductImage] = []
                    for img in imgs_raw or []:
                        filename = img.get("Filename", p["Name"])
                        image_data = img.get("ImageData")
                        image_url = img.get("ImageUrl")

                        final_url: Optional[str] = None

                        if image_data:
                            # caso antigo: base64
                            if not str(image_data).startswith("data:image"):
                                image_data = "data:image/png;base64," + image_data
                            final_url = image_data
                            log.debug(
                                "🧩 [WT:%s] imagem base64 (filename=%r)",
                                pid,
                                filename,
                            )
                        elif image_url:
                            # caso novo: só vem 'images/xxx.png'
                            original_image_url = image_url
                            image_url = image_url.lstrip("/")
                            if base_images:
                                base_check = base_images[:-1] if base_images.endswith("/") else base_images
                                # evitar /images/images/...
                                if base_check.endswith("/images") and image_url.startswith("images/"):
                                    image_url = image_url[len("images/"):]
                                final_url = urljoin(
                                    base_images if base_images.endswith("/") else base_images + "/",
                                    image_url,
                                )
                            else:
                                # fallback sem IMAGES_BASE_URL
                                final_url = image_url

                            log.debug(
                                "🌐 [WT:%s] ImageUrl=%r → final_url=%r (base=%r)",
                                pid,
                                original_image_url,
                                final_url,
                                base_images,
                            )

                        if not final_url:
                            log.warning(
                                "⚠ [WT:%s] imagem ignorada (sem ImageData nem ImageUrl utilizável) filename=%r",
                                pid,
                                filename,
                            )
                            continue

                        imgs.append(
                            ProductImage(
                                url=final_url,
                                alt=filename,
                            )
                        )

                    if not imgs:
                        log.warning(
                            "⚠ PRODUTO SEM IMAGEM DE ORIGEM (Wintouch) | SKU=%s | WintouchID=%s | Nome=%s",
                            p.get("Code"),
                            pid,
                            p.get("Name"),
                        )

                    # ---------- Categorias / Marca ----------
                    cat_name = ""
                    cat_id = p.get("Product1ndCategoryID")
                    if cat_id:
                        try:
                            cat_obj = self._get(f"product_1st_categories/{cat_id}")
                            cat_name = cat_obj.get("Name", "")
                        except Exception as e:
                            log.warning("⚠️ Falha ao obter categoria %s: %s", cat_id, e)

                    brand_name = ""
                    brand_id = p.get("Product3ndCategoryID")
                    if brand_id:
                        try:
                            brand_obj = self._get(f"product_3rd_categories/{brand_id}")
                            brand_name = brand_obj.get("Name", "").split(" - ")[0]
                        except Exception as e:
                            log.warning("⚠️ Falha ao obter marca %s: %s", brand_id, e)

                    # ---------- Preço ----------
                    price = 0.0
                    try:
                        price_resp = self._get(f"products/{pid}/prices")
                        if isinstance(price_resp, list):
                            for row in price_resp:
                                val = row.get("PriceWithoutVAT")
                                try:
                                    val = float(val)
                                    if val > 0:
                                        price = val
                                        break
                                except (TypeError, ValueError):
                                    continue
                    except Exception as e:
                        log.warning(
                            "⚠️ Produto %s sem preço definido! Erro: %s", pid, e
                        )

                    if price <= 0:
                        log.warning(
                            "⛔ PRODUTO SEM PREÇO (Wintouch) | SKU=%s | WintouchID=%s | Nome=%s",
                            p.get("Code"),
                            pid,
                            p.get("Name"),
                        )

                    # ---------- Peso / IVA ----------
                    weight = p.get("Weight")
                    weight = float(weight) if isinstance(weight, (int, float)) else 0.0
                    tax_rate = p.get("VATSales", {}).get("TaxRate")

                    self.sku_to_id[p["Code"]] = pid

                    products.append(
                        Product(
                            name=p["Name"],
                            sku=p["Code"],
                            price=price,
                            stock_quantity=stock_map.get(pid, {}).get(
                                "StockQuantity", 0
                            ),
                            categories=[cat_name] if cat_name else [],
                            brand=brand_name or "",
                            images=imgs,
                            short_description=p.get("Notes", ""),
                            description=p.get("Description", ""),
                            currency="EUR",
                            weight=weight,
                            vat_id=tax_rate,
                            wintouch_id=pid,
                            second_category_id=p.get("Product2ndCategoryID"),
                        )
                    )

                except Exception as e:
                    log.exception("Erro ao processar produto %s: %s", pid, e)

            yield products
            skip += self.batch

    # ---------- Encomendas ----------
    def create_order_on_wintouch(self, order: dict, woo_client: WooClient):
        try:
            for meta in order.get("meta_data", []):
                if meta.get("key") == "wintouch_synced" and meta.get("value") == "true":
                    log.info(
                        "⏩ Encomenda %s já sincronizada no WooCommerce.", order["id"]
                    )
                    return False

            now = datetime.utcnow().replace(microsecond=0).isoformat() + "Z"
            doc_id = str(uuid.uuid4())

            shipping = order.get("shipping", {})
            shipping_lines = order.get("shipping_lines", [])

            shipping_cost = 0
            shipping_method = ""
            if shipping_lines:
                shipping_cost = float(shipping_lines[0].get("total", 0)) + float(
                    shipping_lines[0].get("total_tax", 0)
                )
                shipping_method = shipping_lines[0].get("method_title", "")

            payload = {
                "PrimaryKey": doc_id,
                "ID": doc_id,
                "EntryDate": now,
                "Date": now,
                "DueDate": (datetime.utcnow() + timedelta(days=15))
                .replace(microsecond=0)
                .isoformat()
                + "Z",
                "ClientEntryDate": now,
                "DocumentTypeID": "8dc48894-c5fb-450d-b2ba-01b2023bafbd",
                "DocumentSerieID": "9f15fd6e-6023-4bb8-9a49-7abad3a08ac9",
                "CurrencyID": "b2fb32f7-5f51-4226-83c7-d2f7790a98d9",
                "CountryID": "3443694d-29c6-473e-9087-ca258c515d09",
                "EntityID": "90d663a2-365f-4dd6-96bf-56b6dba288c4",
                "EntityName": order["billing"].get("company")
                or order["billing"].get("first_name")
                or "Cliente Provisório",
                "Address1": order["billing"].get("address_1", ""),
                "Address2": order["billing"].get("address_2", ""),
                "City": order["billing"].get("city", ""),
                "PostalCode": order["billing"].get("postcode", ""),
                "VATNumber": order["billing"].get("vat_number", "999999990"),
                "ExternalDocument": str(order["id"]),
                "SectorID": "667e3be5-65a5-48af-b1bd-ea68410b2e28",
                "PrintLayoutID": "aa748f51-d126-46c5-8b0f-8ef166ea86e1",
                "CreatedWorkStationID": "e4c7f9bd-64e8-4877-8647-f1c51166ba43",
                "EditedWorkstationID": "e4c7f9bd-64e8-4877-8647-f1c51166ba43",
                "PriceLevel": 1,
                "PaymentTermID": "e65fee67-6d77-473a-93e2-a67796479a3b",
                "ProductDocumentDetails": [],
                "Payments": [],
                "Notes": order.get("customer_note", ""),
                "GrossValue1": 0,
                "GrossValueWithVAT": 0,
                "VATTax1": 23,
                "VATValue1": 0,
                "ShippingAddress1": shipping.get("address_1", ""),
                "ShippingAddress2": shipping.get("address_2", ""),
                "ShippingPostalCode": shipping.get("postcode", ""),
                "ShippingCity": shipping.get("city", ""),
                "ShippingMethod": shipping_method,
                "ShippingValue": shipping_cost,
            }

            VAT_RATE_TO_ID = {
                23: "90916cea-cd15-4242-8b00-f1949486df26",
                13: "49c18807-ae94-46a8-bc04-270501a268c7",
                6: "14568254-1f7b-4c12-8be3-dc4c021223eb",
            }

            def is_uuid(val):
                return isinstance(val, str) and len(val) == 36 and "-" in val

            total_base = 0
            total_iva = 0

            for i, item in enumerate(order.get("line_items", [])):
                log.debug("📦 Item bruto do pedido: %s", item)

                product_id = item.get("wintouch_id")
                if not product_id or len(product_id) < 10:
                    log.warning(
                        "❌ Produto com ID Wintouch inválido ou ausente: %s", item
                    )
                    continue

                try:
                    quantity = int(item["quantity"])
                    total = float(item["total"] or 0)
                    total_tax = float(item.get("total_tax") or 0)
                    price_vat = round(
                        (total + total_tax) / quantity, 2
                    ) if quantity else 0.0

                    raw_vat = item.get("vat_id", "23")
                    if is_uuid(raw_vat):
                        vat_id = raw_vat
                        vat_tax = round(
                            (total_tax / total * 100), 0
                        ) if total else 0
                    else:
                        vat_tax = int(raw_vat) if raw_vat else 23
                        vat_id = VAT_RATE_TO_ID.get(vat_tax, VAT_RATE_TO_ID[23])

                    price = round(price_vat / (1 + vat_tax / 100), 2)
                    vat_value = round((price_vat - price) * quantity, 2)
                    gross_value = round(price * quantity, 2)
                    gross_with_vat = round(price_vat * quantity, 2)

                    detail_id = str(uuid.uuid4())

                    detail = {
                        "PrimaryKey": detail_id,
                        "ID": detail_id,
                        "ProductID": product_id,
                        "Row": i,
                        "RowUnitPrice": price,
                        "UnitID": "aee8e4c1-9899-4c52-b0e1-8d3c54e3e576",
                        "Quantity": quantity,
                        "VATID": vat_id,
                        "VATTax": vat_tax,
                        "UnitPriceWithoutVAT": price,
                        "UnitPriceWithVAT": price_vat,
                        "WharehouseID": "d99cd8ce-b143-43fb-b3c2-d95e6131ab88",
                        "EntryDate": now,
                        "DeliveryDate": now,
                        "ProductDocumentDetailsDimensions": [
                            {
                                "PrimaryKey": str(uuid.uuid4()),
                                "ID": str(uuid.uuid4()),
                                "ProductDocumentDetailID": detail_id,
                                "Quantity": quantity,
                                "StockQuantity": quantity,
                                "GrossValueWithoutVAT": gross_value,
                                "GrossValueWithVAT": gross_with_vat,
                                "VATValue": vat_value,
                                "EntryDate": now,
                                "StockDate": now,
                                "CanceledQuantity": 0,
                                "SatisfiedQuantity": 0,
                                "Sources": [],
                            }
                        ],
                    }

                    payload["ProductDocumentDetails"].append(detail)
                    total_base += gross_value
                    total_iva += vat_value

                except Exception as e:
                    log.warning("⚠️ Erro ao processar item %s: %s", item.get("name"), e)

            if shipping_cost > 0:
                detail_id = str(uuid.uuid4())
                vat_tax = 23
                vat_id = VAT_RATE_TO_ID[vat_tax]
                price_vat = round(shipping_cost, 2)
                price = round(price_vat / (1 + vat_tax / 100), 2)
                vat_value = round(price_vat - price, 2)

                detail = {
                    "PrimaryKey": detail_id,
                    "ID": detail_id,
                    "ProductID": "1ae4bdc6-7f1d-4883-bd55-7b459ccfde2f",
                    "Row": len(payload["ProductDocumentDetails"]),
                    "RowUnitPrice": price,
                    "UnitID": "aee8e4c1-9899-4c52-b0e1-8d3c54e3e576",
                    "Quantity": 1,
                    "VATID": vat_id,
                    "VATTax": vat_tax,
                    "UnitPriceWithoutVAT": price,
                    "UnitPriceWithVAT": price_vat,
                    "WharehouseID": "d99cd8ce-b143-43fb-b3c2-d95e6131ab88",
                    "EntryDate": now,
                    "DeliveryDate": now,
                    "ProductDocumentDetailsDimensions": [
                        {
                            "PrimaryKey": str(uuid.uuid4()),
                            "ID": str(uuid.uuid4()),
                            "ProductDocumentDetailID": detail_id,
                            "Quantity": 1,
                            "StockQuantity": 1,
                            "GrossValueWithoutVAT": price,
                            "GrossValueWithVAT": price_vat,
                            "VATValue": vat_value,
                            "EntryDate": now,
                            "StockDate": now,
                            "CanceledQuantity": 0,
                            "SatisfiedQuantity": 0,
                            "Sources": [],
                        }
                    ],
                }

                payload["ProductDocumentDetails"].append(detail)
                total_base += price
                total_iva += vat_value

            if not payload["ProductDocumentDetails"]:
                raise ValueError(
                    "Encomenda sem linhas válidas para enviar ao Wintouch."
                )

            payload["GrossValue1"] = round(total_base, 2)
            payload["GrossValueWithVAT"] = round(total_base + total_iva, 2)
            payload["VATValue1"] = round(total_iva, 2)

            log.debug(
                "📤 Payload enviado para Wintouch:\n%s",
                json.dumps(payload, indent=2, ensure_ascii=False),
            )

            self._post_with_jwt_if_needed("product_documents", payload)

            woo_client._put(
                f"orders/{order['id']}",
                {
                    "meta_data": [
                        {
                            "key": "wintouch_synced",
                            "value": "true",
                        }
                    ]
                },
            )
            log.info(
                "✅ Pedido %s marcado como sincronizado no WooCommerce.", order["id"]
            )

            return True

        except Exception as e:
            log.exception(
                "❌ Erro ao enviar encomenda %s: %s", order.get("id", "?"), e
            )
            raise
