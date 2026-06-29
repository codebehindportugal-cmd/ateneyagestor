import logging
import pathlib
from logging.handlers import RotatingFileHandler
from logging.handlers import SMTPHandler
import datetime



import logging
import pathlib
from logging.handlers import RotatingFileHandler
import datetime

def setup_logging(log_level: str = "INFO"):
    logs_dir = pathlib.Path(__file__).resolve().parent.parent.parent / "logs"
    logs_dir.mkdir(parents=True, exist_ok=True)
    log_file = logs_dir / "wintouch_sync.log"

    fmt = "%(asctime)s %(levelname)-8s %(name)s: %(message)s"

    handlers = [
        logging.StreamHandler(),
        RotatingFileHandler(
            log_file,
            maxBytes=5 * 1024 * 1024,
            backupCount=3,
            encoding="utf-8",
        ),
    ]

    logging.basicConfig(
        level=log_level.upper(),
        format=fmt,
        handlers=handlers,
        force=True,
    )
    logging.getLogger().info("Logging inicializado em %s", log_file)

    # apagar logs antigos (> 30 dias)
    cutoff_date = datetime.datetime.now() - datetime.timedelta(days=30)
    for file in logs_dir.glob("*.log"):
        if datetime.datetime.fromtimestamp(file.stat().st_mtime) < cutoff_date:
            file.unlink()
            logging.getLogger().info("🧹 Log antigo removido: %s", file)
