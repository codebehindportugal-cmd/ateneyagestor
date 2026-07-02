from __future__ import annotations

import logging
from typing import Any

import requests

from .config import Settings


class PhcClient:
    def __init__(self, settings: Settings) -> None:
        self.settings = settings

    def fetch_products(self) -> list[dict[str, Any]]:
        """Tabelas configuradas no painel (SQL direto) tem prioridade; sem
        configuracao, cai de volta na API REST generica (comportamento antigo)."""
        if self.settings.sync_tables:
            return self._fetch_from_sql()

        return self._fetch_from_rest()

    # ---------- SQL Server direto (tabelas configuradas no painel) ----------
    def _fetch_from_sql(self) -> list[dict[str, Any]]:
        try:
            import pyodbc
        except ImportError as exc:
            raise RuntimeError(
                "Tabelas PHC configuradas no painel mas o pacote 'pyodbc' nao esta instalado. "
                "Corre: pip install pyodbc (e instala o 'ODBC Driver 17/18 for SQL Server')."
            ) from exc

        conn_str = (
            "DRIVER={ODBC Driver 17 for SQL Server};"
            f"SERVER={self.settings.phc_base_url};"
            f"DATABASE={self.settings.phc_database};"
            f"UID={self.settings.phc_username};"
            f"PWD={self.settings.phc_password};"
        )

        rows: list[dict[str, Any]] = []
        with pyodbc.connect(conn_str, timeout=30) as conn:
            cursor = conn.cursor()
            for table_cfg in self.settings.sync_tables:
                table = table_cfg.get("table")
                fields = table_cfg.get("fields") or []
                mapping = table_cfg.get("mapping") or {}

                if not table or not fields:
                    logging.warning("Tabela PHC ignorada por falta de nome/campos: %s", table_cfg)
                    continue

                columns_sql = ", ".join(f"[{f}]" for f in fields)
                logging.info("A ler tabela PHC [%s] (%d campos)", table, len(fields))

                cursor.execute(f"SELECT {columns_sql} FROM [{table}]")
                column_names = [d[0] for d in cursor.description]

                for record in cursor.fetchall():
                    raw = dict(zip(column_names, record))
                    mapped = {mapping.get(k, k): v for k, v in raw.items()}
                    rows.append(self._finalize_row(mapped))

        return rows

    def _finalize_row(self, mapped: dict[str, Any]) -> dict[str, Any]:
        """Garante sku/name/regular_price/stock_quantity coerentes, mantendo
        quaisquer outros campos mapeados (description, short_description, ...)
        para o WooCommerceClient os poder usar diretamente."""
        result = {k: v for k, v in mapped.items() if v is not None}

        result["sku"] = str(result.get("sku") or result.get("ref") or result.get("reference") or "").strip()
        result["name"] = str(result.get("name") or result.get("design") or result.get("descricao") or "").strip()

        price = result.pop("price", None)
        if "regular_price" not in result and price is not None:
            result["regular_price"] = price
        if "regular_price" in result:
            result["regular_price"] = str(result["regular_price"])

        stock = result.get("stock_quantity", result.pop("stock", None))
        try:
            result["stock_quantity"] = int(float(stock or 0))
        except (TypeError, ValueError):
            result["stock_quantity"] = 0

        return result

    # ---------- API REST generica (sem tabelas configuradas) ----------
    def _fetch_from_rest(self) -> list[dict[str, Any]]:
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
            "regular_price": str(item.get("price") or item.get("preco") or item.get("regular_price") or "0"),
            "stock_quantity": int(float(item.get("stock") or item.get("stock_quantity") or 0)),
        }
