"""
Reporta o resultado de cada execução ao backup-manager.

Configuração no .env do script:
    BACKUP_MANAGER_URL=https://gestao.example.com   # URL do backup-manager
    BACKUP_MANAGER_TOKEN=1|xxxxx...                  # Token gerado no admin > Sincronizadores

Se estas variáveis não estiverem definidas o reporter fica silencioso
e nunca bloqueia a execução do sync.
"""

import os
import time
from datetime import datetime
from typing import Optional

try:
    import requests as _requests
    _REQUESTS_OK = True
except ImportError:
    _REQUESTS_OK = False


class SyncReporter:
    def __init__(self):
        self._url = os.getenv("BACKUP_MANAGER_URL", "").rstrip("/")
        self._token = os.getenv("BACKUP_MANAGER_TOKEN", "")
        self._started_at = datetime.now()
        self._products = 0
        self._orders = 0
        self._errors = 0
        self._log_lines: list[str] = []

    @property
    def _enabled(self) -> bool:
        return bool(self._url and self._token and _REQUESTS_OK)

    # --- contadores (chamar durante a execução) ---

    def product_synced(self, count: int = 1) -> None:
        self._products += count

    def order_synced(self, count: int = 1) -> None:
        self._orders += count

    def error(self, count: int = 1) -> None:
        self._errors += count

    def append_log(self, line: str) -> None:
        self._log_lines.append(line)

    # --- envio final ---

    def report(
        self,
        status: str = "success",
        log: Optional[str] = None,
        metadata: Optional[dict] = None,
    ) -> None:
        """
        Envia o relatório ao backup-manager.
        status: "success" | "partial" | "failed"
        log:    conteúdo do ficheiro de log (opcional, sobrepõe-se a append_log)
        """
        if not self._enabled:
            return

        log_content = log
        if log_content is None:
            log_content = "\n".join(self._log_lines) if self._log_lines else None

        payload = {
            "status": status,
            "products_synced": self._products,
            "orders_synced": self._orders,
            "errors_count": self._errors,
            "started_at": self._started_at.isoformat(),
            "finished_at": datetime.now().isoformat(),
            "log": log_content,
            "metadata": metadata or {},
        }

        try:
            resp = _requests.post(
                f"{self._url}/api/sync/runs",
                headers={
                    "Authorization": f"Bearer {self._token}",
                    "Accept": "application/json",
                },
                json=payload,
                timeout=15,
            )
            resp.raise_for_status()
        except Exception as exc:
            # Nunca bloqueia — o sync já correu, o relatório é best-effort
            print(f"[reporter] Aviso: falha ao reportar ao backup-manager: {exc}")
