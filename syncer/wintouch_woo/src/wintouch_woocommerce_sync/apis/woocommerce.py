import logging, requests, os
from typing import Dict, Any, Optional, List
import base64
from woocommerce import API
from ..models import Product, ProductImage
import json
from PIL import Image
from io import BytesIO
import unicodedata
import re
import time
import socket
from urllib.parse import urlparse, urljoin
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry
import urllib3
from requests.auth import HTTPBasicAuth

DEFAULT_UA = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
    "AppleWebKit/537.36 (KHTML, like Gecko) Chrome Safari"
)


class WooClient:
    def __init__(self, cfg):
        # --- Normalizar base_url ---
        base_url = (cfg.base_url or "").strip().rstrip("/")
        if not base_url.lower().startswith(("http://", "https://")):
            base_url = "https://" + base_url
        self.base_url = base_url

        # --- Base para URLs de imagem vindos do Wintouch ---
        # cfg.images_base_url deve vir do IMAGES_BASE_URL do .env
        self.images_base_url = (getattr(cfg, "images_base_url", "") or "").strip()
        if self.images_base_url and not self.images_base_url.endswith("/"):
            self.images_base_url += "/"

        # --- Preflight DNS ---
        parsed = urlparse(base_url)
        host = parsed.hostname
        port = parsed.port or (443 if parsed.scheme == "https" else 80)
        ok = False
        for i in range(3):
            try:
                socket.getaddrinfo(host, port, 0, socket.SOCK_STREAM)
                ok = True
                break
            except socket.gaierror:
                time.sleep(0.5 * (2 ** i))
        if not ok:
            raise RuntimeError(
                f"Não consigo resolver DNS para {host}. "
                "Verifica a ligação, DNS e o .env (WC_URL com http(s)://)."
            )

        # --- Session com retries ---
        session = requests.Session()
        retries = Retry(
            total=5,
            connect=5,
            read=5,
            status=5,
            backoff_factor=0.5,
            status_forcelist=[429, 500, 502, 503, 504],
            allowed_methods=frozenset(["GET", "POST", "PUT", "DELETE", "PATCH"]),
            raise_on_status=False,
        )
        adapter = HTTPAdapter(max_retries=retries, pool_connections=50, pool_maxsize=50)
        session.mount("https://", adapter)
        session.mount("http://", adapter)
        session.headers.update({"Connection": "keep-alive"})

        self.wcapi = API(
            url=base_url,
            consumer_key=cfg.consumer_key,
            consumer_secret=cfg.consumer_secret,
            version=cfg.version,
            timeout=120,
            wp_api=True,
            session=session,
        )
        try:
            self.wcapi.session = session
        except Exception:
            try:
                self.wcapi._session = session
            except Exception:
                pass

        self.admin_username = cfg.admin_username
        self.admin_password = cfg.admin_app_password
        self.consumer_key = cfg.consumer_key
        self.consumer_secret = cfg.consumer_secret
        self.api_version = cfg.version.strip("/")
        self.session = session
        self.image_cache: Dict[str, str] = {}

    # ----------------- Helpers de request -----------------
    def _wc_url(self, endpoint: str) -> str:
        return f"{self.base_url}/wp-json/{self.api_version}/{endpoint.lstrip('/')}"

    def _request_with_retries(self, verb: str, endpoint: str, **kwargs):
        attempts = 5
        params = kwargs.pop("params", None)
        data = kwargs.pop("data", None)
        url = self._wc_url(endpoint)
        headers = {
            "Accept": "application/json",
            "User-Agent": DEFAULT_UA,
        }
        if data is not None:
            headers["Content-Type"] = "application/json;charset=utf-8"

        for i in range(1, attempts + 1):
            try:
                resp = self.session.request(
                    verb.upper(),
                    url,
                    params=params,
                    json=data,
                    auth=HTTPBasicAuth(self.consumer_key, self.consumer_secret),
                    headers=headers,
                    timeout=120,
                    **kwargs,
                )
                if resp.status_code >= 400:
                    raise requests.HTTPError(
                        f"WooCommerce {verb.upper()} {endpoint} failed with HTTP {resp.status_code}: {resp.text[:500]}",
                        response=resp,
                    )
                return resp.json()
            except (
                requests.exceptions.ConnectionError,
                requests.exceptions.ChunkedEncodingError,
                requests.exceptions.ReadTimeout,
                urllib3.exceptions.ProtocolError,
                urllib3.exceptions.NameResolutionError,
                requests.HTTPError,
            ) as e:
                if i == attempts:
                    raise
                if i >= 3:
                    self.session.headers.update({"Connection": "close"})
                sleep = min(30, 0.5 * (2 ** (i - 1)))
                logging.warning(
                    "%s %s falhou (%s). Tentativa %d/%d — retry em %.1fs...",
                    verb.upper(),
                    endpoint,
                    e.__class__.__name__,
                    i,
                    attempts,
                    sleep,
                )
                time.sleep(sleep)

    def _get(self, endpoint: str, params: Optional[Dict[str, Any]] = None, **kwargs):
        kwargs = kwargs or {}
        if params is not None:
            kwargs["params"] = params
        return self._request_with_retries("get", endpoint, **kwargs)

    def _post(self, endpoint: str, data: Dict[str, Any]):
        return self._request_with_retries("post", endpoint, data=data)

    def _put(self, endpoint: str, data: Dict[str, Any]):
        return self._request_with_retries("put", endpoint, data=data)

    # ----------------- WordPress REST (/wp/v2) -----------------
    def _wp_get(self, path: str, params: Optional[Dict[str, Any]] = None) -> Any:
        url = f"{self.base_url}/wp-json/wp/v2/{path.lstrip('/')}"
        r = self.session.get(url, params=params, timeout=40)
        r.raise_for_status()
        return r.json()

    def _wp_post_media(
        self,
        filename: str,
        content: bytes,
        mime: str,
        title: str,
        alt_text: str,
    ) -> Dict[str, Any]:
        url = f"{self.base_url}/wp-json/wp/v2/media"
        r = self.session.post(
            url,
            auth=(self.admin_username, self.admin_password),
            files={"file": (filename, content, mime)},
            data={"title": title, "alt_text": alt_text},
            timeout=60,
        )
        try:
            r.raise_for_status()
        except requests.HTTPError:
            logging.error("❌ Upload media falhou %s — %s", r.status_code, r.text)
            raise
        return r.json()

    # ----------------- Utilidades de string -----------------
    def _normalize_str(self, s: str) -> str:
        return (
            unicodedata.normalize("NFKD", s)
            .encode("ASCII", "ignore")
            .decode("ASCII")
            .lower()
            .replace(" ", "")
        )

    def _simplify_alt(self, s: str) -> str:
        base = (
            unicodedata.normalize("NFKD", s)
            .encode("ASCII", "ignore")
            .decode("ASCII")
            .lower()
        )
        base = re.sub(r"\s+", "", base)
        base = re.sub(r"[\-_\.]?\d*(\.\w+)?$", "", base)
        return base

    # ----------------- URLs de imagem -----------------
    def _abs_image_url(self, url: str) -> str:
        """
        Converte 'images/xyz.png' em absoluto usando images_base_url (ou base_url).
        Evita ficar com '/images/images/...'.
        """
        if not isinstance(url, str) or not url:
            return ""
        u = url.strip()

        # já absoluto ou base64
        if u.startswith(("http://", "https://", "data:image")):
            return u

        base = self.images_base_url or self.base_url
        # Se a base já termina em /images/ e o caminho começa em images/, remove o prefixo duplicado
        base_check = base[:-1] if base.endswith("/") else base
        if base_check.endswith("/images") and u.startswith("images/"):
            u = u[len("images/"):]

        return urljoin(base if base.endswith("/") else base + "/", u.lstrip("/"))

    # ----------------- Media -----------------
    def _find_media_by_exact_title(self, title: str) -> Optional[Dict[str, Any]]:
        """
        Procura por media no WP (/wp/v2/media), não na API WooCommerce.

        IMPORTANTE: alguns sites devolvem 400 quando pedimos page > número de páginas.
        Nesse caso, paramos a pesquisa e devolvemos None em vez de rebentar.
        """
        if not title:
            return None

        normalized_title = self._simplify_alt(title)
        page = 1
        while True:
            try:
                media = self._wp_get("media", {"search": title, "per_page": 100, "page": page})
            except requests.HTTPError as e:
                status = getattr(e.response, "status_code", None)
                if status == 400:
                    logging.warning(
                        "⚠ WP media search devolveu 400 para title=%r page=%s — a terminar procura.",
                        title,
                        page,
                    )
                    break
                logging.warning(
                    "⚠ Erro ao pesquisar media no WP para title=%r page=%s: %s",
                    title,
                    page,
                    e,
                )
                break

            if not isinstance(media, list) or not media:
                break

            for item in media:
                item_title = (item.get("title") or {}).get("rendered") or ""
                if item_title:
                    simplified_title = self._simplify_alt(item_title)
                    if normalized_title in simplified_title or simplified_title in normalized_title:
                        return {"id": item.get("id"), "alt": item_title}
                file_path = (item.get("media_details") or {}).get("file") or ""
                if file_path:
                    filename_base = file_path.split("/")[-1].rsplit(".", 1)[0]
                    simplified_filename = self._simplify_alt(filename_base)
                    if normalized_title in simplified_filename or simplified_filename in normalized_title:
                        return {"id": item.get("id"), "alt": item_title or filename_base}
            page += 1

        return None

    def _resize_and_convert_webp(self, img_bytes: bytes) -> bytes:
        with Image.open(BytesIO(img_bytes)) as img:
            img.thumbnail((1200, 1200))
            output = BytesIO()
            img.save(output, format="WEBP", quality=85)
            return output.getvalue()

    def _upload_image_base64(self, img: ProductImage) -> Optional[int]:
        try:
            header, b64data = img.url.split(",", 1)
            mime_type = header.split(":")[1].split(";")[0]
            base_name = (img.alt or "image").replace(" ", "_")[:50]
            if not b64data.strip():
                logging.warning("⚠ Dados base64 vazios para imagem '%s'.", img.alt)
                return None
            img_bytes = base64.b64decode(b64data)
            # converter para webp
            try:
                pil = Image.open(BytesIO(img_bytes)).convert("RGB")
                pil.thumbnail((1200, 1200))
                out = BytesIO()
                pil.save(out, format="WEBP", quality=85)
                out.seek(0)
                img_bytes = out.read()
                mime_type = "image/webp"
                filename = f"{base_name}.webp"
            except Exception:
                ext = mime_type.split("/")[-1]
                filename = f"{base_name}.{ext}"
            resp = self._wp_post_media(
                filename,
                img_bytes,
                mime_type,
                base_name,
                img.alt or base_name,
            )
            return int(resp.get("id"))
        except Exception as e:
            logging.warning("⚠ Upload base64 falhou para '%s': %s", getattr(img, "alt", None), e)
            return None

    def _ensure_media_id(self, img: ProductImage, *, sku: str = "") -> Optional[int]:
        # 1) tenta reutilizar por título
        if img.alt:
            try:
                existing = self._find_media_by_exact_title(img.alt)
            except Exception as e:
                logging.warning("⚠ [%s] Erro ao procurar media existente para '%s': %s", sku, img.alt, e)
                existing = None
            if existing and existing.get("id"):
                return int(existing["id"])

        # 2) base64 => upload direto
        if isinstance(img.url, str) and img.url.startswith("data:image"):
            return self._upload_image_base64(img)

        # 3) URL (relativo ou absoluto) => download + upload
        abs_url = self._abs_image_url(getattr(img, "url", ""))
        if isinstance(abs_url, str) and abs_url.startswith(("http://", "https://")):
            headers = {
                "User-Agent": DEFAULT_UA,
                "Accept": "image/avif,image/webp,image/apng,image/*,*/*;q=0.8",
                "Referer": self.base_url,
            }
            tries = 3
            last_err = None
            for i in range(tries):
                try:
                    r = self.session.get(
                        abs_url,
                        timeout=45,
                        headers=headers,
                        allow_redirects=True,
                    )
                    r.raise_for_status()
                    content = r.content
                    # normalizar para webp se possível
                    try:
                        pil = Image.open(BytesIO(content)).convert("RGB")
                        pil.thumbnail((1200, 1200))
                        out = BytesIO()
                        pil.save(out, format="WEBP", quality=85)
                        out.seek(0)
                        content = out.read()
                        mime = "image/webp"
                        filename_base = (img.alt or "image").strip().replace(" ", "_")[:50]
                        filename = f"{filename_base}.webp"
                    except Exception:
                        mime = r.headers.get("Content-Type", "image/jpeg")
                        if not (isinstance(mime, str) and mime.startswith("image/")):
                            mime = "image/jpeg"
                        ext = (mime.split("/")[-1] or "jpg").lower()
                        if ext not in {"jpg", "jpeg", "png", "webp"}:
                            ext = "jpg"
                        filename_base = (img.alt or "image").strip().replace(" ", "_")[:50]
                        filename = f"{filename_base}.{ext}"
                    media = self._wp_post_media(
                        filename,
                        content,
                        mime,
                        (img.alt or "image")[:50],
                        img.alt or "",
                    )
                    return int(media.get("id"))
                except Exception as e:
                    last_err = e
                    time.sleep(1.5 * (i + 1))
            logging.warning(
                "⚠ [%s] Falha ao descarregar/carregar imagem de %r: %s",
                sku,
                abs_url,
                last_err,
            )
            return None

        if getattr(img, "url", None):
            logging.warning(
                "⚠ [%s] URL de imagem inválida ou não suportada: %r",
                sku,
                img.url,
            )
        return None

    # ----------------- Taxonomias -----------------
    def ensure_category(self, name: str) -> int:
        existing = self._get("products/categories", {"search": name})
        if isinstance(existing, list) and existing:
            return existing[0]["id"]
        res = self._post("products/categories", {"name": name})
        return res["id"]

    def ensure_brand(self, name: str) -> int:
        existing = self._get("products/brands", {"search": name})
        if isinstance(existing, list) and existing:
            return existing[0]["id"]
        res = self._post("products/brands", {"name": name})
        return res["id"]

    # ----------------- Produto -----------------
    def sync_product(self, p: Product):
        if p.price <= 0:
            logging.warning(
                "⛔ PRODUTO SEM PREÇO | SKU=%s | WintouchID=%s | Nome=%s",
                p.sku,
                p.wintouch_id,
                p.name,
            )
            return None

        TAX_RATE_TO_CLASS = {
            23: "Taxas Padrão",
            13: "Taxas Taxa Intermédia",
            6: "Taxas Taxa reduzida",
            0: "Taxas Taxa zero",
        }

        cat_ids = [self.ensure_category(c) for c in (p.categories or []) if c]
        brand_term_id = self.ensure_brand(p.brand) if p.brand else None

        existentes = self._get("products", {"sku": p.sku})
        existing_imgs = []
        if isinstance(existentes, list) and existentes:
            prod_id = existentes[0]["id"]
            existing_imgs = existentes[0].get("images", []) or []
        else:
            prod_id = None
            existing_imgs = []

        # Aviso se já vem sem imagens do Wintouch
        if not (p.images or []):
            logging.warning(
                "⚠ PRODUTO SEM IMAGEM DE ORIGEM | SKU=%s | WintouchID=%s | Nome=%s",
                p.sku,
                p.wintouch_id,
                p.name,
            )

        # títulos já associados
        existing_titles = set()
        for e in existing_imgs:
            base = e.get("alt", "") or e.get("name", "")
            if base:
                existing_titles.add(self._simplify_alt(base))

        # base: manter as atuais (por id)
        final_images: List[Dict[str, Any]] = []
        seen_ids: set[int] = set()
        for e in existing_imgs:
            if e.get("id"):
                iid = int(e["id"])
                if iid not in seen_ids:
                    seen_ids.add(iid)
                    final_images.append({"id": iid, "position": len(final_images)})

        # acrescentar novas (logs detalhados em debug apenas)
        for img in (p.images or []):
            norm_alt = self._simplify_alt(img.alt or "")
            if norm_alt and norm_alt in existing_titles:
                logging.debug(
                    "⏭️ [%s] IMG ignorada por já existir via alt=%r",
                    p.sku,
                    img.alt,
                )
                continue

            media_id = self._ensure_media_id(img, sku=p.sku)
            if media_id:
                if media_id in seen_ids:
                    logging.debug(
                        "⏭️ [%s] IMG duplicada por id=%s", p.sku, media_id
                    )
                    continue
                seen_ids.add(media_id)
                final_images.append(
                    {"id": media_id, "position": len(final_images)}
                )
                logging.debug(
                    "✅ [%s] IMG anexada por id=%s (total agora=%s)",
                    p.sku,
                    media_id,
                    len(final_images),
                )
            else:
                # fallback: deixar o Woo tentar puxar (alguns hosts aceitam)
                abs_url = self._abs_image_url(getattr(img, "url", ""))
                if isinstance(abs_url, str) and abs_url.startswith(
                    ("http://", "https://")
                ):
                    final_images.append(
                        {"src": abs_url, "position": len(final_images)}
                    )
                    logging.debug(
                        "🪙 [%s] Fallback por src=%r (total agora=%s)",
                        p.sku,
                        abs_url,
                        len(final_images),
                    )
                else:
                    logging.warning(
                        "⚠ [%s] Sem media_id nem URL válido para '%s'",
                        p.sku,
                        img.alt,
                    )

        # Plano B + avisos para o cliente
        if not final_images:
            if not (p.images or []):
                # já logámos acima, este é redundante mas inofensivo
                logging.warning(
                    "⚠ PRODUTO SEM IMAGEM NO SITE | SKU=%s | WintouchID=%s | Nome=%s | origem=0",
                    p.sku,
                    p.wintouch_id,
                    p.name,
                )
            else:
                logging.warning(
                    "⚠ PRODUTO SEM IMAGEM NO SITE | SKU=%s | WintouchID=%s | Nome=%s | imagens_origem=%d",
                    p.sku,
                    p.wintouch_id,
                    p.name,
                    len(p.images or []),
                )

        # meta e dados comuns
        meta_data = [{"key": "_wintouch_product_id", "value": p.wintouch_id}]
        if p.vat_id:
            meta_data.append({"key": "_wintouch_vat_id", "value": p.vat_id})

        tax_class = ""
        try:
            if isinstance(p.vat_id, (int, float)):
                tax_class = TAX_RATE_TO_CLASS.get(int(p.vat_id), "")
            elif isinstance(p.vat_id, str) and p.vat_id.isdigit():
                tax_class = TAX_RATE_TO_CLASS.get(int(p.vat_id), "")
        except Exception as e:
            logging.warning(
                "⚠ Erro ao mapear classe de imposto para VAT ID %s: %s",
                p.vat_id,
                e,
            )

        data_common = {
            "name": p.name,
            "sku": p.sku,
            "type": "simple",
            "status": "publish",
            "short_description": p.short_description,
            "description": p.description,
            "regular_price": f"{p.price:.2f}",
            "categories": [{"id": cid} for cid in cat_ids],
            "brands": [{"id": brand_term_id}] if brand_term_id else [],
            "weight": f"{p.weight:.3f}" if p.weight else "",
            "manage_stock": True,
            "stock_quantity": int(p.stock_quantity or 0),
            "meta_data": meta_data,
            "tax_class": tax_class,
            "featured": p.second_category_id
            == "beeb282d-d672-4d13-9b78-4ffa40806f51",
        }

        logging.debug(
            "🧾 Dados (sem imagens) SKU %s:\n%s",
            p.sku,
            json.dumps(data_common, indent=2, ensure_ascii=False),
        )
        logging.debug(
            "🖼️ Images SKU %s:\n%s",
            p.sku,
            json.dumps(final_images, indent=2, ensure_ascii=False),
        )

        # PUT em duas fases + verificação
        def _images_applied_ok(pid: int) -> bool:
            try:
                check = self._get(f"products/{pid}")
                imgs = check.get("images", []) or []
                if final_images:
                    return len(imgs) >= len(
                        [x for x in final_images if "id" in x or "src" in x]
                    )
                return len(imgs) == 0
            except Exception:
                return False

        if prod_id:
            self._put(f"products/{prod_id}", data_common)
            if final_images:
                self._put(f"products/{prod_id}", {"images": final_images})
                if not _images_applied_ok(prod_id):
                    only_ids = [{"id": x["id"]} for x in final_images if "id" in x]
                    if only_ids:
                        logging.warning(
                            "🔁 Reenvio apenas IDs de imagens (sem position) para SKU %s",
                            p.sku,
                        )
                        self._put(
                            f"products/{prod_id}",
                            {"images": only_ids},
                        )
            logging.info(
                "✅ Atualizado produto %s (ID %s) — imagens: %d",
                p.sku,
                prod_id,
                len(final_images),
            )
        else:
            created = self._post("products", {**data_common, "images": final_images})
            created_id = created.get("id")
            if created_id and final_images and not _images_applied_ok(created_id):
                only_ids = [{"id": x["id"]} for x in final_images if "id" in x]
                if only_ids:
                    logging.warning(
                        "🔁 Reenvio apenas IDs (sem position) produto novo SKU %s",
                        p.sku,
                    )
                    self._put(
                        f"products/{created_id}",
                        {"images": only_ids},
                    )
            logging.info(
                "✅ Criado produto %s (ID %s) — imagens: %d",
                p.sku,
                created_id,
                len(final_images),
            )

        return p.sku

    def disable_missing_wintouch_products(self, active_skus: set[str]) -> int:
        disabled = 0
        page = 1

        while True:
            products = self._get(
                "products",
                {"per_page": 100, "page": page, "status": "publish"},
            )
            if not products:
                break

            for product in products:
                meta = product.get("meta_data", []) or []
                has_wintouch_id = any(
                    item.get("key") in ("_wintouch_product_id", "global_unique_id")
                    and item.get("value")
                    for item in meta
                )
                sku = product.get("sku")

                if has_wintouch_id and sku and sku not in active_skus:
                    self._put(f"products/{product['id']}", {"status": "draft"})
                    disabled += 1
                    logging.info(
                        "Produto WooCommerce desativado por nao existir no Wintouch: SKU=%s ID=%s",
                        sku,
                        product["id"],
                    )

            if len(products) < 100:
                break
            page += 1

        logging.info("Produtos desativados por ausencia no Wintouch: %d", disabled)
        return disabled

    # ----------------- Encomendas -----------------
    def _extract_billing_with_nif(self, order: Dict[str, Any]) -> Dict[str, Any]:
        billing = order.get("billing", {}).copy()
        nif_keys = {"nif", "vat_number", "_billing_nif", "_billing_vat", "billing_nif"}
        meta_data = order.get("meta_data", [])
        for meta in meta_data:
            key = meta.get("key", "").lower()
            if key in nif_keys:
                billing["vat_number"] = meta.get("value")
                break
        return billing

    def get_processing_orders_detailed(self) -> List[Dict[str, Any]]:
        try:
            orders = []
            page = 1
            while True:
                batch = self._get(
                    "orders",
                    {"status": "processing", "per_page": 50, "page": page},
                )
                if not batch or not isinstance(batch, list):
                    break
                orders.extend(batch)
                page += 1

            extracted = []
            product_meta_cache = {}

            for order in orders:
                try:
                    order_full = self._get(f"orders/{order['id']}")
                    order["meta_data"] = order_full.get("meta_data", [])
                except Exception as e:
                    logging.warning(
                        "⚠️ Falha ao carregar meta_data para encomenda %s: %s",
                        order["id"],
                        e,
                    )

                line_items = []
                for item in order.get("line_items", []):
                    meta_data = item.get("meta_data", [])
                    wintouch_id = next(
                        (
                            m.get("value")
                            for m in meta_data
                            if m.get("key")
                            in ("_wintouch_product_id", "global_unique_id")
                        ),
                        None,
                    )
                    vat_id = next(
                        (
                            m.get("value")
                            for m in meta_data
                            if m.get("key") in ("_wintouch_vat_id", "vat_id")
                        ),
                        None,
                    )
                    prod_id = item.get("product_id")

                    if (not wintouch_id or not vat_id) and prod_id:
                        if prod_id not in product_meta_cache:
                            try:
                                prod_data = self._get(f"products/{prod_id}")
                                prod_meta = prod_data.get("meta_data", [])
                                product_meta_cache[prod_id] = {
                                    "wintouch_id": next(
                                        (
                                            m.get("value")
                                            for m in prod_meta
                                            if m.get("key")
                                            in (
                                                "_wintouch_product_id",
                                                "global_unique_id",
                                            )
                                        ),
                                        None,
                                    ),
                                    "vat_id": next(
                                        (
                                            m.get("value")
                                            for m in prod_meta
                                            if m.get("key")
                                            in ("_wintouch_vat_id", "vat_id")
                                        ),
                                        None,
                                    ),
                                }
                            except Exception as e:
                                logging.warning(
                                    "❌ Erro ao buscar metadados do produto %s: %s",
                                    prod_id,
                                    e,
                                )
                        meta = product_meta_cache.get(prod_id, {})
                        if not wintouch_id:
                            wintouch_id = meta.get("wintouch_id")
                        if not vat_id:
                            vat_id = meta.get("vat_id")

                    line_items.append(
                        {
                            "product_id": prod_id,
                            "name": item.get("name"),
                            "quantity": item.get("quantity"),
                            "sku": item.get("sku"),
                            "total": item.get("total"),
                            "total_tax": item.get("total_tax"),
                            "wintouch_id": wintouch_id,
                            "vat_id": vat_id,
                        }
                    )

                extracted.append(
                    {
                        "id": order["id"],
                        "date_created": order.get("date_created"),
                        "status": order.get("status"),
                        "currency": order.get("currency"),
                        "total": order.get("total"),
                        "payment_method": order.get("payment_method"),
                        "payment_method_title": order.get(
                            "payment_method_title"
                        ),
                        "customer_id": order.get("customer_id"),
                        "customer_note": order.get("customer_note"),
                        "billing": self._extract_billing_with_nif(order),
                        "shipping": order.get("shipping", {}),
                        "line_items": line_items,
                        "meta_data": order.get("meta_data", []),
                        "shipping_lines": order.get("shipping_lines", []),
                    }
                )

            return extracted

        except Exception as e:
            logging.exception("❌ Erro ao obter encomendas: %s", e)
            raise
