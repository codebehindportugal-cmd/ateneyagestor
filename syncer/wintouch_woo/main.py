"""
Wintouch → WooCommerce synchronizer.
Entry point called by the backup-manager via `sync:run wintouch-woo`.

Prints a SYNC_RESULT line at the end so the Artisan command can parse stats:
  SYNC_RESULT:{"products_synced": 10, "orders_synced": 5, "errors_count": 0}
"""

import sys
import json
import pathlib
import os
from datetime import datetime

from dotenv import load_dotenv

BASE_DIR = pathlib.Path(__file__).resolve().parent
sys.path.insert(0, str(BASE_DIR / "src"))

load_dotenv(BASE_DIR / ".env")

from src.wintouch_woocommerce_sync.config import get_settings
from src.wintouch_woocommerce_sync.utils import setup_logging
from src.wintouch_woocommerce_sync.apis.woocommerce import WooClient
from src.wintouch_woocommerce_sync.apis.wintouch import WintouchClient
from src.wintouch_woocommerce_sync.sync import run_sync
from src.wintouch_woocommerce_sync.discount_sync import apply_discounts


def run_order_sync(woo: WooClient, wintouch: WintouchClient) -> int:
    orders = woo.get_processing_orders_detailed()
    count = 0
    for order in orders:
        print(
            f"🧾 Encomenda #{order['id']} | {order['billing']['email']} | {order['total']} {order['currency']}"
        )
        wintouch.create_order_on_wintouch(order, woo)
        count += 1
    return count


def main() -> dict:
    cfg = get_settings()
    log_level = getattr(cfg, "logging", {}).get("level", "INFO")
    try:
        setup_logging(log_level)
    except PermissionError as e:
        # logs/ dir exists but is owned by root (created by a previous root-cron run).
        # Fall back to stderr so the sync still runs. Fix: chown the logs dir on the server.
        import logging
        logging.basicConfig(
            level=getattr(logging, log_level, logging.INFO),
            stream=sys.stderr,
            format="%(asctime)s %(levelname)s %(message)s",
        )
        logging.warning(f"Não foi possível abrir ficheiro de log ({e}). A usar stderr.")

    woo = WooClient(cfg.woocommerce)
    win = WintouchClient(cfg.wintouch, cfg.sync.batch_size)

    products_synced = 0
    orders_synced = 0
    errors_count = 0

    try:
        orders_synced = run_order_sync(woo, win)
    except Exception as e:
        print(f"ERRO na sync de encomendas: {e}", file=sys.stderr)
        errors_count += 1

    try:
        for batch in win.iter_products(woo):
            for product in batch:
                woo.sync_product(product)
                products_synced += 1
    except Exception as e:
        print(f"ERRO na sync de produtos: {e}", file=sys.stderr)
        errors_count += 1

    try:
        apply_discounts(woo, cfg.wintouch)
    except Exception as e:
        print(f"ERRO nos descontos: {e}", file=sys.stderr)
        errors_count += 1

    return {
        "products_synced": products_synced,
        "orders_synced": orders_synced,
        "errors_count": errors_count,
    }


if __name__ == "__main__":
    result = main()
    # This line is parsed by RunSyncProject.php — keep format intact.
    print(f"SYNC_RESULT:{json.dumps(result)}")
    sys.exit(1 if result["errors_count"] > 0 else 0)
