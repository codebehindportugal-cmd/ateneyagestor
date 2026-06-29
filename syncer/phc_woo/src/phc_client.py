from __future__ import annotations

import logging
from typing import Any

import requests

from .config import Settings


class PhcClient:
    def __init__(self, settings: Settings) -> None:
        self.settings = settings

    def fetch_products(self) -> list[dict[str, Any]]:
        if not self.settings.phc_base_url:
            logging.warning("PHC_BASE_URL vazio. Nenhum produto foi lido.")
            return []

        headers = {}
        if self.settings.phc_api_key:
            headers["Authorization"] = f"Bearer {self.settings.phc_api_key}"

        response = requests.get(
            self.settings.phc_base_url.rstrip("/") + "/products",
            headers=headers,
            timeout=60,
        )
        response.raise_for_status()

        data = response.json()
        if isinstance(data, dict):
            data = data.get("products", data.get("data", []))

        if not isinstance(data, list):
            raise RuntimeError("Resposta PHC inesperada: esperava uma lista de produtos.")

        return [self.normalize_product(item) for item in data]

    def normalize_product(self, item: dict[str, Any]) -> dict[str, Any]:
        return {
            "sku": str(item.get("sku") or item.get("ref") or item.get("reference") or "").strip(),
            "name": str(item.get("name") or item.get("descricao") or item.get("description") or "").strip(),
            "price": str(item.get("price") or item.get("preco") or item.get("regular_price") or "0"),
            "stock_quantity": int(float(item.get("stock") or item.get("stock_quantity") or 0)),
        }
