# Arquivo: src/wintouch_woocommerce_sync/discount_sync.py

import logging
from datetime import datetime
from calendar import monthrange
import unicodedata
from src.wintouch_woocommerce_sync.apis.wintouch import WintouchClient

def normalize_name(name):
    return unicodedata.normalize("NFKD", name or "").encode("ASCII", "ignore").decode("ASCII").strip().lower()

def get_discount_period(discount_obj, campaigns):
    today = datetime.now()
    start = discount_obj.get("StartDate")
    end = discount_obj.get("EndDate")

    if campaigns:
        start = campaigns[0].get("StartDate") or start
        end = campaigns[0].get("EndDate") or end

    if not start:
        start = today.replace(day=1).strftime("%Y-%m-%d")

    if not end:
        last_day = monthrange(today.year, today.month)[1]
        end = today.replace(day=last_day, hour=23, minute=59, second=59).strftime("%Y-%m-%d")

    return start[:10], end[:10]

def apply_discounts(wc_client, wintouch_cfg):
    logging.info("🚀 Início do processo de aplicar descontos")

    wintouch = WintouchClient(cfg=wintouch_cfg)

    try:
        policies = wintouch._get("datalists/COMMERCIAL_TABLES_PRODUCTTABLES_LOYALTYPOLICIES/results")
    except Exception as e:
        logging.error("❌ Falha ao obter lista de políticas de fidelização: %s", e)
        return

    try:
        all_categories = []
        page = 1
        while True:
            page_data = wc_client._get("products/categories", {"per_page": 100, "page": page})
            if not page_data:
                break
            all_categories.extend(page_data)
            if len(page_data) < 100:
                break
            page += 1
    except Exception as e:
        logging.error("❌ Erro ao carregar categorias do WooCommerce: %s", e)
        return

    # Marcas (atributo pa_marca) — opcional: se não existir no WooCommerce, continua sem marcas
    all_brands = []
    try:
        all_brands_resp = wc_client._get("products/attributes/pa_marca/terms")
        all_brands = all_brands_resp.get("terms", all_brands_resp) if isinstance(all_brands_resp, dict) else all_brands_resp
        logging.info("🏷️ Marcas WooCommerce carregadas: %d", len(all_brands))
    except Exception as e:
        logging.warning("⚠️ Atributo 'pa_marca' não encontrado no WooCommerce — matching por marca desactivado: %s", e)

    cat_map = {normalize_name(c["name"]): c["id"] for c in all_categories if isinstance(c, dict) and "name" in c}
    brand_map = {normalize_name(b["name"]): b["id"] for b in all_brands if isinstance(b, dict) and "name" in b and "id" in b}

    logging.info("📁 Categorias WooCommerce mapeadas: %d", len(cat_map))
    for name, bid in brand_map.items():
        logging.debug("🔧 Marca WooCommerce: %s → ID: %s", name, bid)

    for policy in policies:
        policy_id = policy["ID"]
        url = f"loyalty_policies/{policy_id}?embed=" + ",".join([
            "Loyalty_Policy_Campaigns.CEntity",
            "Loyalty_Policy_Promotions.Product",
            "Loyalty_Policy_Promotions.OfferProduct",
            "Loyalty_Policy_Prices.Loyalty_Policy_Price_Limits",
            "Loyalty_Policy_Prices.Product",
            "Loyalty_Policy_Discounts.Loyalty_Policy_Discount_Limits",
            "Loyalty_Policy_Discounts.Product",
        ])

        try:
            data = wintouch._get(url)
        except Exception as e:
            logging.warning("⚠️ Erro ao obter dados da política %s: %s", policy_id, e)
            continue

        discounts = data.get("LoyaltyPolicyDiscounts", [])
        campaigns = data.get("LoyaltyPolicyCampaigns", [])
        logging.info("🎯 Total de descontos recebidos para política %s: %d", policy_id, len(discounts))

        for d in discounts:
            discount_limits = d.get("LoyaltyPolicyDiscountLimits", [])
            if not discount_limits:
                continue

            discount_raw = discount_limits[0].get("Discount", 0)
            discount_percent = float(str(discount_raw).replace("%", ""))
            if discount_percent <= 0:
                continue

            cat_id = d.get("Product1ndCategoryID")
            brand_id = d.get("Product3ndCategoryID")
            product_info = d.get("Product")

            cat_name = ""
            if cat_id:
                try:
                    cat_obj = wintouch._get(f"product_1st_categories/{cat_id}")
                    cat_name = normalize_name(cat_obj.get("Name", ""))
                    logging.info("🔍 Categoria Wintouch: %s → WooCommerce ID: %s", cat_name, cat_map.get(cat_name))
                except Exception as e:
                    logging.warning("⚠️ Erro ao obter nome da categoria %s: %s", cat_id, e)

            brand_name = ""
            if brand_id:
                try:
                    brand_obj = wintouch._get(f"product_3rd_categories/{brand_id}")
                    brand_name = normalize_name(brand_obj.get("Name", "").split(" - ")[0])
                    logging.info("🔍 Marca Wintouch: %s → WooCommerce ID: %s", brand_name, brand_map.get(brand_name))
                except Exception as e:
                    logging.warning("⚠️ Erro ao obter nome da marca %s: %s", brand_id, e)

            wc_cat_id = cat_map.get(cat_name) if cat_name else None
            wc_brand_id = brand_map.get(brand_name) if brand_name else None

            all_products = []

            try:
                page = 1
                while True:
                    page_products = wc_client._get("products", {"per_page": 100, "page": page})
                    if not page_products:
                        break
                    for p in page_products:
                        prod_cats = [c["id"] for c in p.get("categories", [])]
                        prod_attrs = p.get("attributes", [])
                        prod_brand_names = [normalize_name(v) for a in prod_attrs if normalize_name(a.get("name")) == "pa_marca" for v in a.get("options", [])]

                        has_category = wc_cat_id and wc_cat_id in prod_cats
                        has_brand = brand_name and brand_name in prod_brand_names
                        is_specific_product = product_info and normalize_name(product_info.get("Name")) == normalize_name(p.get("name"))

                        if has_category or has_brand or is_specific_product:
                            all_products.append(p)

                    if len(page_products) < 100:
                        break
                    page += 1
            except Exception as e:
                logging.warning("⚠️ Erro ao buscar produtos filtrados: %s", e)

            seen_ids = set()
            unique_products = []
            for p in all_products:
                if p["id"] not in seen_ids:
                    unique_products.append(p)
                    seen_ids.add(p["id"])

            logging.info("📦 Total de produtos únicos com desconto: %d", len(unique_products))
            for p in unique_products:
                logging.debug("🔍 Produto: %s | ID: %s | Preço: %s | Categorias: %s | Atributos: %s",
                              p.get("name"), p.get("id"), p.get("regular_price"),
                              p.get("categories"), p.get("attributes"))

            if not unique_products:
                continue

            start_date, end_date = get_discount_period(d, campaigns)

            for p in unique_products:
                try:
                    regular_price = float(p.get("regular_price") or 0)
                    if regular_price <= 0:
                        logging.warning("⚠️ Produto %s ignorado pois preço regular é inválido: %s", p.get("name"), regular_price)
                        continue

                    sale_price = regular_price * (1 - discount_percent / 100)

                    wc_client._put(f"products/{p['id']}", {
                        "sale_price": f"{sale_price:.2f}",
                        "date_on_sale_from": start_date,
                        "date_on_sale_to": end_date,
                    })

                    logging.info("✅ Produto %s atualizado com %.2f%% de desconto (%s → %s)",
                                 p["name"], discount_percent,
                                 regular_price, f"{sale_price:.2f}")

                except Exception as e:
                    logging.warning("⚠️ Erro ao aplicar desconto no produto %s: %s", p.get("name"), e)

    logging.info("🏁 Processo de descontos finalizado.")
