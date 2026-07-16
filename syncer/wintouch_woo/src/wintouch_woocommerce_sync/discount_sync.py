# Arquivo: src/wintouch_woocommerce_sync/discount_sync.py

import logging
import math
from datetime import datetime
from calendar import monthrange
import unicodedata
from src.wintouch_woocommerce_sync.apis.wintouch import WintouchClient

def normalize_name(name):
    return unicodedata.normalize("NFKD", name or "").encode("ASCII", "ignore").decode("ASCII").strip().lower()

def get_wintouch_period(discount_obj, campaigns):
    """Devolve as datas reais do Wintouch (para validar se a promoção está ativa)."""
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
        end = today.replace(day=last_day).strftime("%Y-%m-%d")

    return start[:10], end[:10]


def get_discount_period(mode: str = "monthly", wt_start: str = None, wt_end: str = None):
    """Devolve o período a definir no WooCommerce conforme o mode configurado.

    Modes disponíveis (configurar em config.yaml > discount > period_mode):
      monthly   → primeiro ao último dia do mês atual (padrão)
      weekly    → segunda-feira a domingo da semana atual
      quarterly → primeiro ao último dia do trimestre atual
      annual    → 1 janeiro a 31 dezembro do ano atual
      wintouch  → usar as datas reais vindas do Wintouch
    """
    today = datetime.now()

    if mode == "wintouch" and wt_start and wt_end:
        return wt_start, wt_end

    if mode == "weekly":
        monday = today - __import__("datetime").timedelta(days=today.weekday())
        sunday = monday + __import__("datetime").timedelta(days=6)
        return monday.strftime("%Y-%m-%d"), sunday.strftime("%Y-%m-%d")

    if mode == "quarterly":
        quarter_start_month = ((today.month - 1) // 3) * 3 + 1
        quarter_end_month = quarter_start_month + 2
        last_day = monthrange(today.year, quarter_end_month)[1]
        start = today.replace(month=quarter_start_month, day=1).strftime("%Y-%m-%d")
        end = today.replace(month=quarter_end_month, day=last_day).strftime("%Y-%m-%d")
        return start, end

    if mode == "annual":
        return f"{today.year}-01-01", f"{today.year}-12-31"

    # monthly (padrão)
    last_day = monthrange(today.year, today.month)[1]
    return today.replace(day=1).strftime("%Y-%m-%d"), today.replace(day=last_day).strftime("%Y-%m-%d")

def clear_stale_discounts(wc_client, updated_product_ids):
    """Remove sale_price de produtos que já não têm desconto activo no Wintouch."""
    logging.info("🧹 A verificar promoções obsoletas no WooCommerce...")
    cleared = 0
    page = 1
    while True:
        try:
            products = wc_client._get("products", {"per_page": 100, "page": page, "on_sale": True})
        except Exception as e:
            logging.warning("⚠️ Erro ao listar produtos em promoção: %s", e)
            break
        if not products:
            break
        for p in products:
            if p["id"] not in updated_product_ids:
                try:
                    wc_client._put(f"products/{p['id']}", {
                        "sale_price": "",
                        "date_on_sale_from": None,
                        "date_on_sale_to": None,
                    })
                    logging.info("🗑️ Promoção removida do produto: %s (ID %s)", p.get("name"), p["id"])
                    cleared += 1
                except Exception as e:
                    logging.warning("⚠️ Erro ao remover promoção do produto %s: %s", p.get("name"), e)
        if len(products) < 100:
            break
        page += 1
    logging.info("🧹 Promoções obsoletas removidas: %d", cleared)


def apply_discounts(wc_client, wintouch_cfg, discount_cfg=None):
    logging.info("🚀 Início do processo de aplicar descontos")
    period_mode = (discount_cfg.period_mode if discount_cfg else None) or "monthly"
    logging.info("📅 Modo de período de desconto: %s", period_mode)

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

    # Marcas (plugin WooCommerce Brands: products/brands) — opcional
    all_brands = []
    try:
        page = 1
        while True:
            page_data = wc_client._get("products/brands", {"per_page": 100, "page": page})
            if not page_data:
                break
            if isinstance(page_data, dict):
                page_data = page_data.get("brands", page_data.get("terms", []))
            all_brands.extend(page_data)
            if len(page_data) < 100:
                break
            page += 1
        logging.info("🏷️ Marcas WooCommerce carregadas: %d", len(all_brands))
    except Exception as e:
        logging.warning("⚠️ Endpoint 'products/brands' não disponível — matching por marca desactivado: %s", e)

    cat_map = {normalize_name(c["name"]): c["id"] for c in all_categories if isinstance(c, dict) and "name" in c}
    brand_map = {normalize_name(b["name"]): b["id"] for b in all_brands if isinstance(b, dict) and "name" in b and "id" in b}

    logging.info("📁 Categorias WooCommerce mapeadas: %d", len(cat_map))
    for name, bid in brand_map.items():
        logging.debug("🔧 Marca WooCommerce: %s → ID: %s", name, bid)

    updated_product_ids = set()

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

        # WinTouch pode usar camelCase ou underscore nos nomes das chaves
        discounts = data.get("LoyaltyPolicyDiscounts") or data.get("Loyalty_Policy_Discounts", [])
        campaigns = data.get("LoyaltyPolicyCampaigns") or data.get("Loyalty_Policy_Campaigns", [])
        logging.info("🎯 Total de descontos recebidos para política %s: %d", policy_id, len(discounts))
        if not discounts:
            logging.debug("🔍 Chaves disponíveis na política %s: %s", policy_id, list(data.keys()))

        for d in discounts:
            discount_limits = (
                d.get("LoyaltyPolicyDiscountLimits")
                or d.get("Loyalty_Policy_Discount_Limits", [])
            )
            if not discount_limits:
                logging.debug("⏭️ Desconto sem limites definidos, a ignorar: %s", d)
                continue

            discount_raw = discount_limits[0].get("Discount", 0)
            discount_percent = float(str(discount_raw).replace("%", ""))
            if discount_percent <= 0:
                continue

            # WinTouch pode usar diferentes nomes de campo para IDs de categoria/produto
            cat_id = d.get("Product1ndCategoryID") or d.get("Product1stCategoryID") or d.get("Product_1nd_Category_ID")
            brand_id = d.get("Product3ndCategoryID") or d.get("Product3rdCategoryID") or d.get("Product_3nd_Category_ID")
            product_info = d.get("Product")

            logging.info(
                "📋 A processar desconto %.2f%% | cat_id=%s | brand_id=%s | produto=%s",
                discount_percent, cat_id, brand_id,
                product_info.get("Name") if product_info else None,
            )

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
                        # Marcas via plugin WooCommerce Brands (campo "brands" no produto)
                        prod_brand_ids = [b["id"] for b in p.get("brands", []) if isinstance(b, dict)]

                        has_category = wc_cat_id and wc_cat_id in prod_cats
                        has_brand = wc_brand_id and wc_brand_id in prod_brand_ids
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
                logging.warning(
                    "⚠️ Nenhum produto encontrado para desconto %.2f%% "
                    "(cat_wc=%s [%s], brand='%s', produto='%s')",
                    discount_percent,
                    wc_cat_id, cat_name,
                    brand_name,
                    product_info.get("Name") if product_info else None,
                )
                continue

            wt_start, wt_end = get_wintouch_period(d, campaigns)
            today_str = datetime.now().strftime("%Y-%m-%d")
            if wt_end < today_str:
                logging.info(
                    "⏩ Desconto expirado em %s (cat=%s, marca=%s) — a ignorar e deixar clear_stale_discounts remover.",
                    wt_end, cat_name, brand_name,
                )
                continue

            # Datas no WooCommerce conforme o period_mode configurado
            start_date, end_date = get_discount_period(mode=period_mode, wt_start=wt_start, wt_end=wt_end)

            for p in unique_products:
                try:
                    regular_price = float(p.get("regular_price") or 0)
                    if regular_price <= 0:
                        logging.warning("⚠️ Produto %s ignorado pois preço regular é inválido: %s", p.get("name"), regular_price)
                        continue

                    # Arredondar para baixo (floor) garante que o cliente recebe
                    # pelo menos o desconto indicado e o badge WooCommerce mostra
                    # a percentagem correta mesmo em produtos de baixo valor.
                    sale_price = math.floor(regular_price * (1 - discount_percent / 100) * 100) / 100

                    wc_client._put(f"products/{p['id']}", {
                        "sale_price": f"{sale_price:.2f}",
                        "date_on_sale_from": start_date,
                        "date_on_sale_to": end_date,
                    })
                    updated_product_ids.add(p["id"])

                    logging.info("✅ Produto %s atualizado com %.2f%% de desconto (%s → %s)",
                                 p["name"], discount_percent,
                                 regular_price, f"{sale_price:.2f}")

                except Exception as e:
                    logging.warning("⚠️ Erro ao aplicar desconto no produto %s: %s", p.get("name"), e)

    clear_stale_discounts(wc_client, updated_product_ids)
    logging.info("🏁 Processo de descontos finalizado.")
