from __future__ import annotations

import logging
from typing import Any

from woocommerce import API

from .config import Settings


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

    def sync_products(self, products: list[dict[str, Any]]) -> int:
        synced = 0
        for product in products:
            if not product.get("sku") or not product.get("name"):
                logging.warning("Produto ignorado por falta de SKU/nome: %s", product)
                continue

            payload = {
                "name": product["name"],
                "sku": product["sku"],
                "regular_price": str(product.get("price", "0")),
                "manage_stock": True,
                "stock_quantity": int(product.get("stock_quantity", 0)),
            }

            existing = self.find_by_sku(product["sku"])
            if existing:
                response = self.api.put(f"products/{existing['id']}", payload)
            else:
                response = self.api.post("products", payload)

            if response.status_code >= 400:
                raise RuntimeError(f"WooCommerce erro {response.status_code}: {response.text}")

            synced += 1
        return synced

    def find_by_sku(self, sku: str) -> dict[str, Any] | None:
        response = self.api.get("products", params={"sku": sku})
        if response.status_code >= 400:
            raise RuntimeError(f"WooCommerce erro {response.status_code}: {response.text}")

        data = response.json()
        return data[0] if data else None
