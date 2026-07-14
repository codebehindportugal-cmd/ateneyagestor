import logging
from .config import get_settings
from .utils import setup_logging
from .apis.wintouch import WintouchClient
from .apis.woocommerce import WooClient

def run_sync():
    setup_logging()
    cfg = get_settings()

    win = WintouchClient(cfg.wintouch, cfg.sync.batch_size)
    woo = WooClient(cfg.woocommerce)

    for batch in win.iter_products():
        for p in batch:
            logging.info(
                "SKU=%s | Nome=%s | Cat=%s | Marca=%s | Imagens=%d | Preço=%.2f | Stock=%.1f | Peso=%.2fkg | IVA=%s",
                p.sku,
                p.name,
                p.categories[0] if p.categories else "",
                p.brand,
                len(p.images),
                p.price,
                p.stock_quantity,
                p.weight or 0.0,
                p.vat_id or "N/A",
            )
            try:
                woo.sync_product(p)
            except Exception as e:
                logging.exception("❌ Erro ao sincronizar SKU=%s: %s", p.sku, e)