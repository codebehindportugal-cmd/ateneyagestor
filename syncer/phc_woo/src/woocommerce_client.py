from __future__ import annotations

import logging
from typing import Any

from woocommerce import API

from .config import Settings

# Campos do produto WooCommerce que aceitamos passar tal e qual, vindos do
# mapeamento configurado no painel (Sincronizadores > PHC > Tabelas e mapeamento).
ALLOWED_PRODUCT_FIELDS = {
    "name",
    "sku",
    "regular_price",
    "sale_price",
    "description",
    "short_description",
    "stock_quantity",
    "weight",
    "status",
}


class WooCommerceClient:
    def __init__(self, settings: Settings) -> None:
        self.settings = settings
        self.api = API(
            url=settings.wc_url,
            consumer_key=settings.wc_consumer_key,
            consumer_secret=settings.wc_consumer_secret,
            version=settings.wc_api_version,
            timeout=60,
        )

    def sync_products(self, products: list[dict[str, Any]]) -> tuple[int, list[dict[str, Any]]]:
        """Sincroniza produtos e devolve (quantidade, items) — 'items' e o detalhe
        por produto (sku/nome/accao/campos alterados) que o Backup Manager guarda
        para a vista de diff/comparacao (metadata do SyncRun)."""
        synced = 0
        items: list[dict[str, Any]] = []

        for product in products:
            sku = product.get("sku")
            name = product.get("name")
            if not sku or not name:
                logging.warning("Produto ignorado por falta de SKU/nome: %s", product)
                continue

            payload = {k: v for k, v in product.items() if k in ALLOWED_PRODUCT_FIELDS}
            payload.setdefault("regular_price", "0")
            payload["manage_stock"] = True
            payload.setdefault("stock_quantity", int(product.get("stock_quantity", 0)))

            existing = self.find_by_sku(sku)
            action = "updated" if existing else "created"

            if existing:
                response = self.api.put(f"products/{existing['id']}", payload)
            else:
                response = self.api.post("products", payload)

            if response.status_code >= 400:
                raise RuntimeError(f"WooCommerce erro {response.status_code}: {response.text}")

            synced += 1
            items.append({
                "sku": sku,
                "name": name,
                "action": action,
                "fields": sorted(k for k in payload if k in ALLOWED_PRODUCT_FIELDS),
            })

        return synced, items

    def find_by_sku(self, sku: str) -> dict[str, Any] | None:
        response = self.api.get("products", params={"sku": sku})
        if response.status_code >= 400:
            raise RuntimeError(f"WooCommerce erro {response.status_code}: {response.text}")

        data = response.json()
        return data[0] if data else None
