from __future__ import annotations

import json
import logging
import os
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

from dotenv import load_dotenv


@dataclass(frozen=True)
class Settings:
    backup_manager_url: str
    backup_manager_token: str
    sync_project_slug: str
    wc_url: str
    wc_consumer_key: str
    wc_consumer_secret: str
    wc_api_version: str
    phc_base_url: str
    phc_api_key: str
    phc_username: str
    phc_password: str
    phc_database: str
    phc_company: str
    batch_size: int
    default_currency: str
    log_dir: Path
    # Tabelas/campos/mapeamento configurados no painel (Sincronizadores > PHC).
    # Cada item: {"table": "ST", "fields": [...], "mapping": {"ref": "sku", ...}}
    sync_tables: list[dict[str, Any]] = field(default_factory=list)

    @property
    def log_file(self) -> Path:
        self.log_dir.mkdir(parents=True, exist_ok=True)
        return self.log_dir / "phc_woo_sync.log"

    @classmethod
    def from_env(cls) -> "Settings":
        load_dotenv()

        return cls(
            backup_manager_url=_env("BACKUP_MANAGER_URL"),
            backup_manager_token=_env("BACKUP_MANAGER_TOKEN"),
            sync_project_slug=_env("SYNC_PROJECT_SLUG"),
            wc_url=_env("WC_URL"),
            wc_consumer_key=_env("WC_CONSUMER_KEY"),
            wc_consumer_secret=_env("WC_CONSUMER_SECRET"),
            wc_api_version=os.getenv("WC_API_VERSION", "wc/v3"),
            phc_base_url=os.getenv("PHC_BASE_URL", ""),
            phc_api_key=os.getenv("PHC_API_KEY", ""),
            phc_username=os.getenv("PHC_USERNAME", ""),
            phc_password=os.getenv("PHC_PASSWORD", ""),
            phc_database=os.getenv("PHC_DATABASE", ""),
            phc_company=os.getenv("PHC_COMPANY", ""),
            batch_size=int(os.getenv("BATCH_SIZE", "50")),
            default_currency=os.getenv("DEFAULT_CURRENCY", "EUR"),
            log_dir=Path(os.getenv("LOG_DIR", "logs")),
            sync_tables=_load_sync_tables(),
        )


def _env(name: str) -> str:
    value = os.getenv(name, "").strip()
    if not value or value == "GERAR_TOKEN_NO_PAINEL":
        raise RuntimeError(f"Variavel obrigatoria em falta: {name}")
    return value


def _load_sync_tables() -> list[dict[str, Any]]:
    """Le sync_tables do config.generated.json gerado pelo painel (download do pacote),
    quando presente ao lado do script. Sem esse ficheiro, devolve [] e o script cai de
    volta na leitura via API REST do PHC (comportamento antigo)."""
    config_path = Path(os.getenv("PHC_CONFIG_JSON", "config.generated.json"))
    if not config_path.is_file():
        return []

    try:
        data = json.loads(config_path.read_text(encoding="utf-8"))
    except (OSError, ValueError) as exc:
        logging.warning("Nao foi possivel ler %s: %s", config_path, exc)
        return []

    tables = (data.get("phc") or {}).get("tables") or []
    return [t for t in tables if isinstance(t, dict) and t.get("table") and t.get("fields")]
