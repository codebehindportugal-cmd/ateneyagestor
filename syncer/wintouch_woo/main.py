import sys
import pathlib
from datetime import datetime
from smtplib import SMTP
from email.message import EmailMessage
import os
from dotenv import load_dotenv
import smtplib
import json
import logging

BASE_DIR = pathlib.Path(__file__).resolve().parent
if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
if hasattr(sys.stderr, "reconfigure"):
    sys.stderr.reconfigure(encoding="utf-8", errors="replace")

load_dotenv(BASE_DIR / ".env")

sys.path.insert(0, str(BASE_DIR / "src"))

from src.wintouch_woocommerce_sync.config import get_settings
from src.wintouch_woocommerce_sync.utils import setup_logging
from src.wintouch_woocommerce_sync.apis.woocommerce import WooClient
from src.wintouch_woocommerce_sync.apis.wintouch import WintouchClient
from src.wintouch_woocommerce_sync.sync import run_sync
from src.wintouch_woocommerce_sync.discount_sync import apply_discounts
from reporter import SyncReporter

LOG_FILE = "logs/wintouch_sync.log"


def run_order_sync(woo: WooClient, wintouch: WintouchClient, reporter: SyncReporter):
    orders = woo.get_processing_orders_detailed()
    logging.info("Encomendas em processing encontradas: %d", len(orders))
    print(f"Encomendas em processing encontradas: {len(orders)}")

    if not orders:
        logging.info("Sem encomendas em processing para sincronizar.")
        print("Sem encomendas em processing para sincronizar.")
        return

    for order in orders:
        print(f"\n🧾 Encomenda #{order['id']} | Cliente: {order['billing']['email']} | Total: {order['total']} {order['currency']}")
        for item in order.get("line_items", []):
            print(f"  - {item['quantity']}x {item['name']} (SKU: {item.get('sku', 'N/A')})")
        try:
            if wintouch.create_order_on_wintouch(order, woo):
                reporter.order_synced()
                reporter.record_item(
                    str(order["id"]),
                    order.get("billing", {}).get("email", ""),
                    "synced",
                )
                logging.info("Encomenda %s sincronizada com sucesso.", order["id"])
            else:
                logging.info("Encomenda %s ignorada: ja estava sincronizada.", order["id"])
        except Exception as e:
            reporter.error()
            logging.exception("Erro na encomenda %s: %s", order["id"], e)
            print(f"❌ Erro na encomenda #{order['id']}: {e}")


def send_sync_report(log_file, cfg):
    smtp = getattr(cfg, "smtp", None)
    if not smtp or not smtp.host or not smtp.user or not smtp.password or not smtp.to:
        print("Relatorio por email ignorado: SMTP nao configurado.")
        return

    smtp_host = smtp.host
    smtp_port = int(smtp.port or 587)
    smtp_user = smtp.user
    smtp_pass = smtp.password
    to_email = smtp.to
    from_email = getattr(smtp, "from_", None) or smtp_user
    site_url = cfg.woocommerce.base_url

    msg = EmailMessage()
    msg["Subject"] = f"[Sync Log] {site_url} - {datetime.now().strftime('%Y-%m-%d %H:%M')}"
    msg["From"] = from_email
    msg["To"] = to_email
    msg.set_content(
        f"🛒 Sincronização finalizada para o site:\n{site_url}\n\n"
        f"📅 Data: {datetime.now().strftime('%Y-%m-%d %H:%M')}\n"
        "📎 Ficheiro de log em anexo.\n\n"
        "-- Este e-mail foi gerado automaticamente."
    )

    with open(log_file, "rb") as f:
        msg.add_attachment(f.read(), maintype="text", subtype="plain", filename=os.path.basename(log_file))

    with smtplib.SMTP(smtp_host, smtp_port, timeout=20) as server:
        server.set_debuglevel(1)
        server.starttls()
        server.login(smtp_user, smtp_pass)
        server.send_message(msg)
        print("✅ Log enviado com sucesso.")


if __name__ == "__main__":
    reporter = SyncReporter()

    config_path = sys.argv[1] if len(sys.argv) > 1 else "config.yaml"
    cfg = get_settings(config_path)
    log_level = getattr(cfg, "logging", {}).get("level", "INFO")
    setup_logging(log_level)

    woo = WooClient(cfg.woocommerce)
    win = WintouchClient(cfg.wintouch, cfg.sync.batch_size)

    try:
        # Sincronizar encomendas
        if cfg.sync.wants("orders"):
            run_order_sync(woo, win, reporter)
        else:
            logging.info("Sincronizacao de encomendas desativada nas definicoes do projeto.")
            print("Sincronizacao de encomendas desativada nas definicoes do projeto.")

        # Sincronizar produtos
        active_skus = set()
        products_enabled = cfg.sync.wants("products")
        for batch in (win.iter_products(woo) if products_enabled else []):
            for product in batch:
                try:
                    result = woo.sync_product(product)
                    if result:
                        sku = result["sku"]
                        action = result.get("action", "synced")
                        if result.get("active"):
                            active_skus.add(sku)
                        reporter.product_synced()
                        reporter.record_item(sku, product.name, action)
                except Exception as e:
                    reporter.error()
                    print(f"❌ Erro no produto: {e}")

        logging.info("Produtos ativos vindos do Wintouch nesta execucao: %d", len(active_skus))
        if active_skus:
            disabled_count = woo.disable_missing_wintouch_products(active_skus)
            print(f"Produtos desativados por ausencia no Wintouch: {disabled_count}")
        else:
            logging.warning("Desativacao de produtos ausentes ignorada: nenhum SKU ativo recolhido do Wintouch.")
            print("Desativacao de produtos ausentes ignorada: nenhum SKU ativo recolhido do Wintouch.")

        apply_discounts(woo, cfg.wintouch)

        status = "partial" if reporter._errors > 0 else "success"

    except Exception as e:
        print(f"🚨 Erro geral: {e}")
        reporter.error()
        status = "failed"

    try:
        send_sync_report(LOG_FILE, cfg)
    except Exception as e:
        print(f"❌ Erro ao enviar email: {e}")

    # Lê o log para enviar ao backup-manager
    try:
        with open(LOG_FILE, "r", encoding="utf-8") as f:
            log_content = f.read()
    except Exception:
        log_content = None

    reporter.report(status=status, log=log_content)

    print("SYNC_RESULT:" + json.dumps({
        "products_synced": reporter._products,
        "orders_synced": reporter._orders,
        "errors_count": reporter._errors,
    }))
