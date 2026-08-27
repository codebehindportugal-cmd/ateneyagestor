#!/usr/bin/env python3
"""
agent_sync.py — o ciclo do agente de backups.

    1. GET  /api/agent/config    → que máquinas e que sites, com que retenção
    2. junta os segredos locais  (secrets.yaml — as chaves SSH nunca saem daqui)
    3. escreve config.yaml       → corre backup.py
    4. POST /api/agent/runs      → devolve o resultado ao painel
    5. POST /api/agent/heartbeat → marca o agente como online

O agente liga SEMPRE para fora. O painel nunca precisa de alcançar esta máquina,
por isso o IP de casa pode mudar e o NAS nunca fica exposto à internet.

Os segredos são por MÁQUINA, não por site: uma chave SSH abre o servidor e
serve todos os domínios lá alojados.

Uso:
    python3 agent_sync.py [--dry-run] [--only nome] [--ignore-frequency] [--config-only]
"""

from __future__ import annotations

import argparse
import json
import logging
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path

try:
    import requests
    import yaml
except ImportError:  # pragma: no cover
    print("Faltam dependências. Corre: pip install requests pyyaml", file=sys.stderr)
    raise SystemExit(2)

BASE_DIR = Path(__file__).resolve().parent
AGENT_FILE = BASE_DIR / "agent.yaml"
SECRETS_FILE = BASE_DIR / "secrets.yaml"
CONFIG_FILE = BASE_DIR / "config.yaml"
RESULTS_FILE = BASE_DIR / "results.json"

log = logging.getLogger("agent_sync")


def load_yaml(path: Path, what: str) -> dict:
    if not path.is_file():
        raise SystemExit(f"Falta o {what}: {path}")
    return yaml.safe_load(path.read_text(encoding="utf-8")) or {}


def api(agent_cfg: dict, method: str, path: str, **kwargs):
    url = agent_cfg["api_url"].rstrip("/") + path
    headers = {
        "Authorization": f"Bearer {agent_cfg['token']}",
        "Accept": "application/json",
    }
    return requests.request(
        method, url, headers=headers,
        timeout=kwargs.pop("timeout", 60), **kwargs
    )


def build_config(remote: dict, secrets: dict, agent_cfg: dict) -> tuple[dict, list[str]]:
    """
    Junta os metadados que vieram do painel com os segredos locais.

    O painel nunca conhece chaves privadas: cada máquina traz um
    `agent_secret_ref` que tem de existir no secrets.yaml. Se não existir, a
    máquina é saltada com um aviso que segue para o painel — melhor do que
    falhar o lote inteiro em silêncio.
    """
    global_cfg = dict(remote.get("global") or {})
    merge_errors: list[str] = []

    # O agent.yaml local pode sobrepor-se ao painel: quem sabe onde o NAS está
    # montado é esta máquina, não o site.
    if agent_cfg.get("backup_root"):
        global_cfg["backup_root"] = agent_cfg["backup_root"]
    global_cfg.setdefault("backup_root", "/mnt/nas/backups")
    global_cfg["require_mountpoint"] = agent_cfg.get("require_mountpoint", True)

    secrets_servers = secrets.get("servers") or {}
    servers = []

    for server in remote.get("servers") or []:
        name = server.get("name")
        ref = server.get("agent_secret_ref") or name
        sites = server.get("sites") or []

        if not sites:
            continue

        if ref not in secrets_servers:
            merge_errors.append(
                f"{name}: sem entrada '{ref}' no secrets.yaml — "
                f"{len(sites)} site(s) saltados"
            )
            continue

        # `ref:` sem nada por baixo é None em YAML, não {} — e é uma entrada
        # legítima: significa "usa o default_key_path".
        entry = secrets_servers.get(ref) or {}

        merged = dict(server)
        merged["ssh_key"] = entry.get("key_path") or secrets.get("default_key_path")
        for field in ("user", "port", "known_hosts"):
            if entry.get(field):
                merged[field] = entry[field]

        if not merged.get("ssh_key"):
            merge_errors.append(f"{name}: '{ref}' não define key_path e não há default_key_path")
            continue

        servers.append(merged)

    return {"global": global_cfg, "servers": servers}, merge_errors


def main() -> int:
    parser = argparse.ArgumentParser(description="Ciclo do agente de backups.")
    parser.add_argument("--dry-run", action="store_true",
                        help="testa a ligação SSH a todas as máquinas, não transfere nada")
    parser.add_argument("--only", action="append", default=[],
                        help="limita a um site ou a uma máquina (pode repetir)")
    parser.add_argument("--ignore-frequency", action="store_true",
                        help="corre todos os sites, mesmo os mensais fora do dia 1 "
                             "(para testes; a corrida agendada nunca usa isto)")
    parser.add_argument("--config-only", action="store_true",
                        help="só gera o config.yaml e mostra-o")
    parser.add_argument("--verbose", "-v", action="store_true")
    args = parser.parse_args()

    logging.basicConfig(
        level=logging.DEBUG if args.verbose else logging.INFO,
        format="%(asctime)s %(levelname)-7s %(message)s",
        datefmt="%H:%M:%S",
    )

    agent_cfg = load_yaml(AGENT_FILE, "agent.yaml")
    for key in ("api_url", "token"):
        if not agent_cfg.get(key):
            raise SystemExit(f"agent.yaml sem '{key}'.")
    secrets = load_yaml(SECRETS_FILE, "secrets.yaml")

    # 1. Config do painel
    log.info("A pedir configuração a %s", agent_cfg["api_url"])
    config_fetch_ok = False
    try:
        resp = api(agent_cfg, "GET", "/api/agent/config")
        resp.raise_for_status()
        remote = resp.json()
        config_fetch_ok = True
    except Exception as exc:  # noqa: BLE001
        log.error("Não consegui obter a configuração: %s", exc)
        if CONFIG_FILE.is_file():
            log.warning("A usar o último config.yaml conhecido — o painel pode estar em baixo.")
            remote = None
        else:
            return 2

    if remote is not None:
        config, merge_errors = build_config(remote, secrets, agent_cfg)
        CONFIG_FILE.write_text(
            yaml.safe_dump(config, allow_unicode=True, sort_keys=False),
            encoding="utf-8",
        )
        CONFIG_FILE.chmod(0o600)
        for err in merge_errors:
            log.warning("config: %s", err)
        total_sites = sum(len(s.get("sites") or []) for s in config["servers"])
        log.info("Máquinas: %d   Sites: %d", len(config["servers"]), total_sites)
    else:
        merge_errors = ["config obtida do cache local (painel inacessível)"]

    if args.config_only:
        print(CONFIG_FILE.read_text(encoding="utf-8"))
        return 0

    # 2. Backups
    cmd = [
        sys.executable, str(BASE_DIR / "backup.py"),
        "--config", str(CONFIG_FILE),
        "--results-json", str(RESULTS_FILE),
    ]
    if args.dry_run:
        cmd.append("--dry-run")
    if args.ignore_frequency:
        cmd.append("--ignore-frequency")
    for only in args.only:
        cmd += ["--only", only]

    log.info("A correr backup.py…")
    backup_exit_code = subprocess.run(cmd).returncode

    # 3. Resultados para o painel
    results, dry_run = [], args.dry_run
    if RESULTS_FILE.is_file():
        payload = json.loads(RESULTS_FILE.read_text(encoding="utf-8"))
        results = payload.get("results", [])
        dry_run = payload.get("dry_run", args.dry_run)

    if results:
        try:
            resp = api(agent_cfg, "POST", "/api/agent/runs", json={
                "results": results,
                "merge_errors": merge_errors,
                "dry_run": dry_run,
            })
            resp.raise_for_status()
            body = resp.json()
            log.info("Painel: %s (guardados: %s, ignorados: %s)",
                     body.get("status"), body.get("stored"), body.get("skipped"))
        except Exception as exc:  # noqa: BLE001
            # Os backups estão feitos no NAS; falhar o report não é motivo para
            # deitar isso fora — fica em results.json para reenvio manual.
            log.error("Não consegui reportar ao painel: %s", exc)
            log.error("Os resultados ficaram em %s", RESULTS_FILE)

    # 4. Heartbeat
    try:
        api(agent_cfg, "POST", "/api/agent/heartbeat", json={
            "checked_in_at": datetime.now(timezone.utc).replace(microsecond=0).isoformat(),
            "config_fetch_ok": config_fetch_ok,
            "backup_exit_code": backup_exit_code,
        }, timeout=30)
    except Exception as exc:  # noqa: BLE001
        log.warning("Heartbeat falhou: %s", exc)

    return backup_exit_code


if __name__ == "__main__":
    sys.exit(main())
