from __future__ import annotations

from datetime import datetime
from pathlib import Path

import requests

from .config import Settings


class BackupManagerReporter:
    def __init__(self, settings: Settings) -> None:
        self.settings = settings

    def send_run(
        self,
        *,
        status: str,
        products_synced: int,
        orders_synced: int,
        errors_count: int,
        started_at: datetime,
        finished_at: datetime,
        log_path: Path,
        items: list[dict] | None = None,
    ) -> None:
        log = ""
        if log_path.exists():
            log = log_path.read_text(encoding="utf-8", errors="ignore")[-60000:]

        metadata: dict = {"slug": self.settings.sync_project_slug}
        if items:
            # Limite defensivo — o painel guarda isto num campo JSON.
            metadata["items"] = items[:2000]

        response = requests.post(
            self.settings.backup_manager_url.rstrip("/") + "/api/sync/runs",
            headers={
                "Authorization": f"Bearer {self.settings.backup_manager_token}",
                "Accept": "application/json",
            },
            json={
                "status": status,
                "products_synced": products_synced,
                "orders_synced": orders_synced,
                "errors_count": errors_count,
                "started_at": started_at.isoformat(),
                "finished_at": finished_at.isoformat(),
                "log": log,
                "metadata": metadata,
            },
            timeout=30,
        )
        response.raise_for_status()
