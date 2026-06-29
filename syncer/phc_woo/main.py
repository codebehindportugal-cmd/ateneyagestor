from __future__ import annotations

import logging
from datetime import datetime, timezone

from src.config import Settings
from src.phc_client import PhcClient
from src.reporter import BackupManagerReporter
from src.woocommerce_client import WooCommerceClient


def main() -> int:
    started_at = datetime.now(timezone.utc)
    settings = Settings.from_env()

    logging.basicConfig(
        level=logging.INFO,
        format="%(asctime)s %(levelname)s %(message)s",
        handlers=[
            logging.FileHandler(settings.log_file, encoding="utf-8"),
            logging.StreamHandler(),
        ],
    )

    reporter = BackupManagerReporter(settings)
    products_synced = 0
    orders_synced = 0
    errors_count = 0

    try:
        logging.info("A iniciar sincronizacao PHC -> WooCommerce")

        phc = PhcClient(settings)
        woo = WooCommerceClient(settings)

        products = phc.fetch_products()
        products_synced = woo.sync_products(products)

        logging.info("Sincronizacao concluida: %s produtos", products_synced)
        status = "success"
    except Exception as exc:
        errors_count = 1
        status = "failed"
        logging.exception("Erro na sincronizacao: %s", exc)

    finished_at = datetime.now(timezone.utc)
    reporter.send_run(
        status=status,
        products_synced=products_synced,
        orders_synced=orders_synced,
        errors_count=errors_count,
        started_at=started_at,
        finished_at=finished_at,
        log_path=settings.log_file,
    )

    return 0 if status == "success" else 1


if __name__ == "__main__":
    raise SystemExit(main())
