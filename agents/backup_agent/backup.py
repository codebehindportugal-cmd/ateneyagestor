#!/usr/bin/env python3
"""
backup.py — puxa os backups dos sites para o disco local (NAS) e escreve o resultado em JSON.

Corre no agente (LXC no Proxmox). Nunca é chamado de fora: o agente liga sempre para fora.
Todos os segredos vêm do config.yaml gerado pelo agent_sync.py a partir do secrets.yaml local.

O config tem máquinas, e cada máquina tem os seus sites — uma ligação SSH por
máquina em vez de uma por domínio.

Uso:
    python3 backup.py --config config.yaml --results-json results.json [--only nome] [--dry-run]

Layout no disco:
    <backup_root>/<servidor>/<site>/<AAAA-MM-DD_HHMM>/{ficheiros...}
    <backup_root>/<servidor>/<site>/latest -> <AAAA-MM-DD_HHMM>
"""

from __future__ import annotations

import argparse
import hashlib
import json
import logging
import os
import re
import shlex
import shutil
import subprocess
import sys
import tempfile
import threading
import time
from datetime import datetime, timezone
from pathlib import Path

try:
    import yaml
except ImportError:  # pragma: no cover
    print("Falta o pyyaml. Corre: pip install pyyaml requests", file=sys.stderr)
    raise SystemExit(2)

log = logging.getLogger("backup")

SSH_BASE_OPTS = [
    "-o", "BatchMode=yes",
    "-o", "StrictHostKeyChecking=accept-new",
    "-o", "ConnectTimeout=20",
    "-o", "ServerAliveInterval=30",
    "-o", "ServerAliveCountMax=10",
]

STAMP_RE = re.compile(r"^\d{4}-\d{2}-\d{2}_\d{4}(_\d+)?$")

# Sem isto, um mysqldump que falha a meio de `mysqldump | gzip` fica escondido
# atrás do código de saída do gzip, e guarda-se um ficheiro vazio a dizer que
# correu bem. Foi assim que um backup de 376 bytes passou por bom.
PIPEFAIL = "set -o pipefail 2>/dev/null || true; "

# O tar distingue aviso de erro pelo codigo de saida: 1 = "alguns ficheiros
# diferem" (mudaram ou desapareceram durante a leitura), 2 = erro fatal, o
# arquivo pode estar truncado. So o 1 e tolerado.
TAR_WARN_EXIT_CODES = (1,)


class BackupError(RuntimeError):
    """Falha num site — apanhada e reportada, nunca aborta o lote todo."""


# --------------------------------------------------------------------------
# Utilitários
# --------------------------------------------------------------------------

def utc_now_iso() -> str:
    return datetime.now(timezone.utc).replace(microsecond=0).isoformat()


def human_size(num: float) -> str:
    for unit in ("B", "KB", "MB", "GB", "TB"):
        if num < 1024:
            return f"{num:.1f} {unit}"
        num /= 1024.0
    return f"{num:.1f} PB"


# --------------------------------------------------------------------------
# SSH — a ligação pertence à máquina, não ao site
# --------------------------------------------------------------------------

def ssh_command(server: dict) -> list[str]:
    key = server.get("ssh_key")
    if not key:
        raise BackupError(
            f"sem chave SSH: nenhuma entrada em secrets.yaml para "
            f"'{server.get('agent_secret_ref') or server['name']}'"
        )
    if not Path(key).is_file():
        raise BackupError(f"chave SSH não encontrada em {key}")

    cmd = ["ssh", "-i", key, *SSH_BASE_OPTS]
    if server.get("port"):
        cmd += ["-p", str(server["port"])]
    if server.get("known_hosts"):
        cmd += ["-o", f"UserKnownHostsFile={server['known_hosts']}"]

    cmd.append(f"{server.get('user') or 'root'}@{server['host']}")
    return cmd


def ssh_check(server: dict, timeout: int = 30) -> None:
    """Verifica que o SSH funciona antes de tentar puxar gigabytes."""
    proc = subprocess.run(
        ssh_command(server) + ["--", "true"],
        capture_output=True, text=True, timeout=timeout,
    )
    if proc.returncode != 0:
        raise BackupError(f"SSH falhou: {(proc.stderr or '').strip()[:300]}")


def ssh_stream_to_file(server: dict, remote_cmd: str, dest: Path,
                       timeout: int | None = None,
                       warn_exit_codes: tuple[int, ...] = ()) -> dict:
    """
    Corre um comando remoto que escreve para stdout e guarda esse stream em
    `dest`, calculando o sha256 pelo caminho. Nada toca no disco de origem —
    é isso que faz o backup funcionar em servidores com o disco cheio.
    """
    cmd = ssh_command(server) + ["--", remote_cmd]
    digest = hashlib.sha256()
    total = 0

    dest.parent.mkdir(parents=True, exist_ok=True)
    stderr_file = tempfile.TemporaryFile()

    log.debug("stream: %s", remote_cmd)
    started = time.monotonic()

    with subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=stderr_file) as proc, \
            open(dest, "wb") as out:
        assert proc.stdout is not None
        while True:
            chunk = proc.stdout.read(1024 * 512)
            if not chunk:
                break
            out.write(chunk)
            digest.update(chunk)
            total += len(chunk)
            if timeout and (time.monotonic() - started) > timeout:
                proc.kill()
                raise BackupError(f"timeout ao transferir {dest.name}")
        rc = proc.wait()

    stderr_file.seek(0)
    err = stderr_file.read().decode("utf-8", "replace").strip()
    stderr_file.close()

    if rc != 0 and rc not in warn_exit_codes:
        dest.unlink(missing_ok=True)
        raise BackupError(f"falha ao gerar {dest.name}: {err[:300] or f'exit {rc}'}")

    if total == 0:
        dest.unlink(missing_ok=True)
        raise BackupError(f"{dest.name} veio vazio — {err[:200] or 'sem erro reportado'}")

    if rc != 0:
        # O tar devolve 1 quando um ficheiro mudou ou desapareceu enquanto era
        # lido — num site vivo isso acontece sempre (caches, sessoes, logs). O
        # arquivo esta completo e legivel; so aquele ficheiro pode estar
        # inconsistente. Apagar o backup por causa disto era deitar fora horas
        # de transferencia por um aviso. Exit 2 continua a ser fatal.
        log.warning("    %s — %s (aviso do tar, exit %d: ficheiros mudaram durante a leitura)",
                    dest.name, human_size(total), rc)
    else:
        log.info("    %s — %s", dest.name, human_size(total))
    return {"file": dest.name, "bytes": total, "sha256": digest.hexdigest()}


def assert_real_sql_dump(path: Path) -> None:
    """
    Um dump sem uma única tabela não é um backup, por muito que o comando tenha
    devolvido 0. Acontece quando o utilizador da base de dados perdeu permissões
    e o mysqldump escreve só o cabeçalho.
    """
    import gzip

    try:
        with gzip.open(path, "rb") as handle:
            head = handle.read(2 * 1024 * 1024)
    except OSError as exc:
        raise BackupError(f"{path.name} não abre como gzip: {exc}")

    if b"CREATE TABLE" not in head.upper().replace(b"`", b""):
        raise BackupError(
            f"{path.name} não contém nenhuma tabela — o utilizador da base de "
            f"dados tem permissões para a ler?"
        )


# --------------------------------------------------------------------------
# Handlers por tipo de site
# --------------------------------------------------------------------------

def backup_plesk(server: dict, site: dict, dest_dir: Path) -> list[dict]:
    """Backup de um domínio Plesk, transmitido para stdout."""
    domain = site.get("domain")
    if not domain:
        raise BackupError("site plesk sem domínio definido")

    extra = " ".join(site.get("plesk_backup_args") or [])
    # O PATH de uma sessão SSH não-interativa não inclui /usr/sbin, que é onde
    # o binário `plesk` vive.
    remote = (
        f"{PIPEFAIL}export PATH=/usr/local/psa/bin:/usr/sbin:/usr/local/sbin:$PATH; "
        f"plesk bin pleskbackup --domains-name {shlex.quote(domain)} "
        f"{extra} --output-file=-"
    ).strip()

    return [ssh_stream_to_file(server, remote, dest_dir / f"{domain.replace('/', '_')}.tar")]


def backup_wordpress(server: dict, site: dict, dest_dir: Path) -> list[dict]:
    """Base de dados via wp-cli (ou mysqldump lido do wp-config) + tar dos ficheiros."""
    wp_root = site.get("wp_root")
    if not wp_root:
        raise BackupError("site wordpress sem wp_root definido")

    root = shlex.quote(wp_root)
    artifacts = []

    db_cmd = (
        f"{PIPEFAIL}cd {root} && "
        f"if command -v wp >/dev/null 2>&1; then "
        f"  wp db export - --single-transaction --quick --allow-root | gzip -c; "
        f"else "
        f"  DB_NAME=$(php -r \"include '{wp_root}/wp-config.php'; echo DB_NAME;\"); "
        f"  DB_USER=$(php -r \"include '{wp_root}/wp-config.php'; echo DB_USER;\"); "
        f"  DB_PASS=$(php -r \"include '{wp_root}/wp-config.php'; echo DB_PASSWORD;\"); "
        f"  DB_HOST=$(php -r \"include '{wp_root}/wp-config.php'; echo DB_HOST;\"); "
        f"  MYSQL_PWD=\"$DB_PASS\" mysqldump -h \"${{DB_HOST%%:*}}\" -u \"$DB_USER\" "
        f"     --single-transaction --quick --default-character-set=utf8mb4 \"$DB_NAME\" | gzip -c; "
        f"fi"
    )
    artifacts.append(ssh_stream_to_file(server, db_cmd, dest_dir / "database.sql.gz"))
    assert_real_sql_dump(dest_dir / "database.sql.gz")

    files_cmd = (
        f"{PIPEFAIL}tar czf - -C {root} "
        f"--exclude=./wp-content/cache "
        f"--exclude=./wp-content/uploads/backup* "
        f"--exclude=./wp-content/ai1wm-backups "
        f"--warning=no-file-changed --warning=no-file-removed "
        f"--ignore-failed-read ."
    )
    artifacts.append(ssh_stream_to_file(server, files_cmd, dest_dir / "files.tar.gz",
                                        warn_exit_codes=TAR_WARN_EXIT_CODES))
    return artifacts


def backup_vps_laravel(server: dict, site: dict, dest_dir: Path) -> list[dict]:
    """Base de dados lida do .env da app (ou do db_override) + tar da app."""
    app_path = site.get("app_path")
    if not app_path:
        raise BackupError("site vps_laravel sem app_path definido")

    app = shlex.quote(app_path)
    artifacts = []
    override = site.get("db_override") or {}

    if override.get("database"):
        db_cmd = (
            f"{PIPEFAIL}MYSQL_PWD={shlex.quote(str(override.get('password', '')))} "
            f"mysqldump -h {shlex.quote(str(override.get('host', '127.0.0.1')))} "
            f"-u {shlex.quote(str(override.get('username', 'root')))} "
            f"--single-transaction --quick --default-character-set=utf8mb4 "
            f"{shlex.quote(str(override['database']))} | gzip -c"
        )
    else:
        db_cmd = (
            f"{PIPEFAIL}set -a && . {app}/.env && set +a && "
            f"MYSQL_PWD=\"$DB_PASSWORD\" mysqldump -h \"${{DB_HOST:-127.0.0.1}}\" "
            f"-P \"${{DB_PORT:-3306}}\" -u \"$DB_USERNAME\" "
            f"--single-transaction --quick --default-character-set=utf8mb4 "
            f"\"$DB_DATABASE\" | gzip -c"
        )
    artifacts.append(ssh_stream_to_file(server, db_cmd, dest_dir / "database.sql.gz"))
    assert_real_sql_dump(dest_dir / "database.sql.gz")

    files_cmd = (
        f"{PIPEFAIL}tar czf - -C {app} "
        f"--exclude=./vendor --exclude=./node_modules "
        f"--exclude=./storage/framework/cache --exclude=./storage/framework/sessions "
        f"--exclude=./storage/framework/views --exclude=./storage/logs "
        f"--warning=no-file-changed --warning=no-file-removed "
        f"--ignore-failed-read ."
    )
    artifacts.append(ssh_stream_to_file(server, files_cmd, dest_dir / "app.tar.gz",
                                        warn_exit_codes=TAR_WARN_EXIT_CODES))

    for idx, extra in enumerate(site.get("storage_paths") or [], start=1):
        safe_name = Path(extra.rstrip("/")).name or f"storage{idx}"
        cmd = (f"{PIPEFAIL}tar czf - -C {shlex.quote(extra)} "
               f"--warning=no-file-changed --warning=no-file-removed "
               f"--ignore-failed-read .")
        artifacts.append(
            ssh_stream_to_file(server, cmd, dest_dir / f"storage-{safe_name}.tar.gz",
                               warn_exit_codes=TAR_WARN_EXIT_CODES)
        )

    return artifacts


def backup_cpanel(server: dict, site: dict, dest_dir: Path) -> list[dict]:
    raise BackupError(
        "tipo 'cpanel' ainda não implementado neste agente — usa SSH/rsync ou pede para ser adicionado"
    )


HANDLERS = {
    "plesk": backup_plesk,
    "wordpress": backup_wordpress,
    "vps_laravel": backup_vps_laravel,
    "cpanel": backup_cpanel,
}


# --------------------------------------------------------------------------
# Retenção
# --------------------------------------------------------------------------

def sweep_stale_partials(site_dir: Path, older_than_hours: int = 24) -> None:
    """Limpa restos de execuções interrompidas, sem tocar numa que esteja a correr."""
    cutoff = time.time() - older_than_hours * 3600
    for leftover in site_dir.glob(".*.partial"):
        if leftover.is_dir() and leftover.stat().st_mtime < cutoff:
            log.info("    a limpar resto de execução interrompida: %s", leftover.name)
            shutil.rmtree(leftover, ignore_errors=True)


def prune(site_dir: Path, keep_days: int | None, keep_min_copies: int | None,
          max_copies: int | None = None) -> None:
    """
    Apaga snapshots antigos. Três controlos, aplicados por esta ordem:

      keep_min_copies  mínimo intocável — nunca se desce abaixo disto
      max_copies       máximo — acima deste número apaga-se por contagem,
                       independentemente da idade ("guarda só as últimas 2")
      keep_days        idade — apaga o que for mais velho do que isto

    Sem max_copies nem keep_days não se apaga nada: era o comportamento
    anterior, em que keep_min_copies (um mínimo) era o único controlo
    existente e por isso os snapshots acumulavam indefinidamente.
    """
    if not site_dir.is_dir():
        return

    snapshots = sorted(
        (d for d in site_dir.iterdir() if d.is_dir() and STAMP_RE.match(d.name)),
        key=lambda d: d.name,
        reverse=True,
    )

    keep_min_copies = max(int(keep_min_copies or 1), 1)

    if max_copies is not None:
        max_copies = max(int(max_copies), 1)
        # Um mínimo maior que o máximo é contraditório; o máximo manda, senão
        # um valor herdado do servidor impedia o limite de alguma vez actuar.
        keep_min_copies = min(keep_min_copies, max_copies)

    cutoff = time.time() - (int(keep_days or 0) * 86400)

    for idx, snap in enumerate(snapshots):
        if idx < keep_min_copies:
            continue

        if max_copies is not None and idx >= max_copies:
            log.info("    retenção: a apagar %s (acima do máximo de %d)", snap.name, max_copies)
            shutil.rmtree(snap, ignore_errors=True)
            continue

        if not keep_days:
            continue

        if snap.stat().st_mtime < cutoff:
            log.info("    retenção: a apagar %s (mais de %d dias)", snap.name, int(keep_days))
            shutil.rmtree(snap, ignore_errors=True)


# --------------------------------------------------------------------------
# Execução
# --------------------------------------------------------------------------

def run_site(server: dict, site: dict, global_cfg: dict, dry_run: bool) -> dict:
    name = site["name"]
    stype = site.get("type", "")
    started = utc_now_iso()

    result = {
        "name": name,
        "type": stype,
        "success": False,
        "error": None,
        "started_at": started,
        "finished_at": None,
        "size_bytes": None,
        "file_count": 0,
        "nas_path": None,
    }

    log.info("  ▸ %s (%s)", name, stype)

    try:
        handler = HANDLERS.get(stype)
        if handler is None:
            raise BackupError(f"tipo de site desconhecido: '{stype}'")

        if dry_run:
            result["success"] = True
            result["finished_at"] = utc_now_iso()
            return result

        root = Path(global_cfg["backup_root"])
        assert_disk_alive(root)

        site_dir = root / server["name"] / name
        site_dir.mkdir(parents=True, exist_ok=True)
        sweep_stale_partials(site_dir)

        stamp = datetime.now().strftime("%Y-%m-%d_%H%M")
        dest_dir = site_dir / stamp
        attempt = 1
        while dest_dir.exists():
            attempt += 1
            dest_dir = site_dir / f"{stamp}_{attempt}"

        partial = site_dir / f".{dest_dir.name}.partial"
        shutil.rmtree(partial, ignore_errors=True)
        partial.mkdir(parents=True, exist_ok=True)

        try:
            artifacts = handler(server, site, partial)

            total = sum(a["bytes"] for a in artifacts)
            manifest = {
                "site": name,
                "server": server["name"],
                "type": stype,
                "host": server.get("host"),
                "started_at": started,
                "finished_at": utc_now_iso(),
                "total_bytes": total,
                "artifacts": artifacts,
            }
            (partial / "manifest.json").write_text(
                json.dumps(manifest, indent=2, ensure_ascii=False), encoding="utf-8"
            )

            # Só agora o snapshot ganha o nome final: uma execução interrompida
            # nunca fica a parecer completa.
            partial.rename(dest_dir)
        except Exception:
            shutil.rmtree(partial, ignore_errors=True)
            try:
                site_dir.rmdir()
            except OSError:
                pass
            raise

        latest = site_dir / "latest"
        latest.unlink(missing_ok=True)
        try:
            latest.symlink_to(dest_dir.name, target_is_directory=True)
        except OSError:
            pass  # sistemas de ficheiros sem symlinks não são fatais

        retention = site.get("retention") or {}
        global_retention = global_cfg.get("retention", {})
        prune(
            site_dir,
            retention.get("keep_days", global_retention.get("keep_days")),
            retention.get("keep_min_copies", global_retention.get("keep_min_copies")),
            retention.get("max_copies", global_retention.get("max_copies")),
        )

        log.info("    ✓ OK — %s em %s", human_size(total), dest_dir.name)
        result.update({
            "success": True,
            "size_bytes": total,
            "file_count": len(artifacts),
            "nas_path": str(dest_dir),
        })

    except BackupError as exc:
        log.error("    ✗ %s", exc)
        result["error"] = str(exc)
    except subprocess.TimeoutExpired:
        log.error("    ✗ timeout")
        result["error"] = "timeout na ligação SSH"
    except Exception as exc:  # noqa: BLE001 - um site nunca derruba o lote
        log.exception("    ✗ erro inesperado")
        result["error"] = f"{type(exc).__name__}: {exc}"[:500]

    result["finished_at"] = utc_now_iso()
    return result


def site_due_today(site: dict, day_of_month: int) -> bool:
    """
    Um site mensal só corre no dia 1. O cron do agente é diário — é aqui que
    se decide o que fica para trás, e não no crontab, para a periodicidade
    viver no painel e não espalhada pela máquina.
    """
    freq = str(site.get("frequency") or "daily").lower()
    if freq == "monthly":
        return day_of_month == 1
    return True


def run_server(server: dict, global_cfg: dict, dry_run: bool, only: list[str]) -> list[dict]:
    sites = server.get("sites") or []
    if only:
        sites = [s for s in sites if s["name"] in only or server["name"] in only]

    if not sites:
        return []

    label = server.get("label") or server["name"]
    log.info("▶ %s — %s (%d site%s)",
             label, server.get("host"), len(sites), "s" if len(sites) != 1 else "")

    # Uma verificação de SSH por máquina: se a ligação está em baixo, não vale
    # a pena tentar cada site e repetir o mesmo erro N vezes.
    try:
        ssh_check(server)
    except BackupError as exc:
        log.error("  ✗ %s", exc)
        now = utc_now_iso()
        return [{
            "name": site["name"], "type": site.get("type"), "success": False,
            "error": str(exc), "started_at": now, "finished_at": now,
            "size_bytes": None, "file_count": 0, "nas_path": None,
        } for site in sites]

    if dry_run:
        log.info("  dry-run: SSH ok, nada foi transferido")

    return [run_site(server, site, global_cfg, dry_run) for site in sites]


# --------------------------------------------------------------------------
# Manter o disco acordado
#
# O NAS é uma caixa externa (WD My Book) com um temporizador de inatividade no
# firmware: adormece o disco sozinha, e não obedece ao hdparm. Quando volta a
# ser preciso, o spin-up demora mais do que o timeout do kernel, o SCSI é
# marcado offline e tudo o que se escreve a seguir dá Errno 5 — depois de o
# backup já ter dito que estava a correr bem.
#
# Duas defesas: acordar e esperar antes de começar, e nunca deixar o disco
# parado tempo suficiente para voltar a adormecer.
# --------------------------------------------------------------------------

KEEPALIVE_FILE = ".backup-agent-keepalive"


def _touch_disk(root: Path) -> None:
    """
    Escrita minúscula com fsync. Tem de ser escrita: uma leitura pode ser
    servida pela cache e não chega a acordar os pratos.
    """
    os.statvfs(root)
    marker = root / KEEPALIVE_FILE
    with open(marker, "w") as handle:
        handle.write(utc_now_iso())
        handle.flush()
        os.fsync(handle.fileno())


def wake_disk(root: Path, timeout: int = 240) -> None:
    """
    Acorda o disco e espera que responda. Um WD Green a arrancar do standby
    demora 15-30s; damos-lhe folga a sério antes de desistir.
    """
    deadline = time.monotonic() + timeout
    attempt = 0
    last_error: Exception | None = None

    while time.monotonic() < deadline:
        attempt += 1
        try:
            _touch_disk(root)
            if attempt > 1:
                log.info("NAS acordado à %da tentativa", attempt)
            return
        except OSError as exc:
            last_error = exc
            log.warning("NAS ainda não responde (%s) — a aguardar…", exc.strerror or exc)
            time.sleep(5)

    raise SystemExit(
        f"O NAS não respondeu em {timeout}s ({last_error}). "
        f"Nada foi copiado — melhor não ter backup do que ter um backup falso."
    )


class DiskKeepalive:
    """
    Mantém o disco acordado enquanto o backup corre. Os intervalos perigosos
    não são entre sites: são dentro de um site, enquanto o mysqldump está a
    ser gerado do outro lado e ainda não chegou um único byte cá.
    """

    def __init__(self, root: Path, interval: int = 120):
        self.root = root
        self.interval = interval
        self._stop = threading.Event()
        self._thread = threading.Thread(target=self._run, daemon=True, name="keepalive")
        self.failures = 0

    def _run(self) -> None:
        while not self._stop.wait(self.interval):
            try:
                _touch_disk(self.root)
                self.failures = 0
            except OSError as exc:
                self.failures += 1
                log.warning("keepalive do NAS falhou (%dx): %s", self.failures, exc)

    def __enter__(self) -> "DiskKeepalive":
        self._thread.start()
        return self

    def __exit__(self, *exc_info) -> None:
        self._stop.set()


def assert_disk_alive(root: Path) -> None:
    """Confirma entre sites que o disco continua a responder."""
    try:
        _touch_disk(root)
    except OSError as exc:
        raise BackupError(f"o NAS deixou de responder ({exc.strerror or exc})")


# --------------------------------------------------------------------------
# Guardas do disco de destino
# --------------------------------------------------------------------------

def assert_backup_root(global_cfg: dict) -> None:
    root = Path(global_cfg.get("backup_root", ""))
    if not root or str(root) in ("", "/"):
        raise SystemExit("backup_root não definido no config.")

    if global_cfg.get("require_mountpoint", True):
        if not root.exists():
            raise SystemExit(
                f"{root} não existe. O NAS está montado? "
                f"(require_mountpoint: false no agent.yaml desliga esta verificação)"
            )
        if not os.path.ismount(root):
            # Sem esta guarda, um NAS desmontado enche o disco do container em
            # silêncio e os backups ficam a ser escritos no sítio errado.
            raise SystemExit(
                f"{root} não é um ponto de montagem — o NAS parece estar desmontado. "
                f"Nada foi feito."
            )

    root.mkdir(parents=True, exist_ok=True)
    if not os.access(root, os.W_OK):
        raise SystemExit(f"sem permissão de escrita em {root}")


# --------------------------------------------------------------------------
# Notificações
# --------------------------------------------------------------------------

def notify(global_cfg: dict, results: list[dict]) -> None:
    failed = [r for r in results if not r["success"]]
    cfg = global_cfg.get("notify") or {}

    if cfg.get("on_failure_only", True) and not failed:
        return

    body = "\n".join(
        f"{'✓' if r['success'] else '✗'} {r['name']}"
        + (f" — {r['error']}" if r["error"] else "")
        for r in results
    )
    subject = f"Backups: {len(results) - len(failed)} ok, {len(failed)} falhados"

    webhook = cfg.get("webhook") or {}
    if webhook.get("enabled") and webhook.get("url"):
        try:
            import requests
            requests.post(webhook["url"], json={"text": f"*{subject}*\n```{body}```"}, timeout=15)
        except Exception as exc:  # noqa: BLE001
            log.warning("webhook falhou: %s", exc)

    sendmail = cfg.get("sendmail") or {}
    if sendmail.get("enabled") and sendmail.get("to"):
        try:
            subprocess.run(["mail", "-s", subject, sendmail["to"]],
                           input=body, text=True, timeout=30, check=False)
        except FileNotFoundError:
            log.warning("sendmail pedido mas o comando 'mail' não existe neste container")


# --------------------------------------------------------------------------
# Main
# --------------------------------------------------------------------------

def main() -> int:
    parser = argparse.ArgumentParser(description="Puxa os backups dos sites para o NAS.")
    parser.add_argument("--config", required=True, help="config.yaml gerado pelo agent_sync.py")
    parser.add_argument("--results-json", help="ficheiro onde escrever os resultados")
    parser.add_argument("--only", action="append", default=[],
                        help="limita a um site ou a uma máquina (pode repetir)")
    parser.add_argument("--dry-run", action="store_true",
                        help="testa só o SSH, não transfere nada")
    parser.add_argument("--ignore-frequency", action="store_true",
                        help="corre também os mensais, mesmo não sendo dia 1")
    args = parser.parse_args()

    cfg = yaml.safe_load(Path(args.config).read_text(encoding="utf-8")) or {}
    global_cfg = cfg.get("global") or {}
    servers = cfg.get("servers") or []

    logging.basicConfig(
        level=getattr(logging, str(global_cfg.get("logging", {}).get("level", "INFO")).upper(), logging.INFO),
        format="%(asctime)s %(levelname)-7s %(message)s",
        datefmt="%H:%M:%S",
    )

    if args.only:
        servers = [
            s for s in servers
            if s["name"] in args.only
            or any(site["name"] in args.only for site in (s.get("sites") or []))
        ]

    # Um --only explícito é uma ordem directa: corre o que foi pedido,
    # seja qual for a periodicidade.
    if not args.only and not args.ignore_frequency:
        hoje = datetime.now().day
        saltados = 0
        for server in servers:
            todos = server.get("sites") or []
            devidos = [site for site in todos if site_due_today(site, hoje)]
            saltados += len(todos) - len(devidos)
            server["sites"] = devidos
        servers = [s for s in servers if s.get("sites")]
        if saltados:
            log.info("Frequência: %d site(s) mensais saltados (só correm no dia 1).", saltados)

    total_sites = sum(len(s.get("sites") or []) for s in servers)
    if not servers or not total_sites:
        log.warning("Nenhum site a processar.")
        if args.results_json:
            Path(args.results_json).write_text(
                json.dumps({"results": [], "dry_run": args.dry_run}, ensure_ascii=False),
                encoding="utf-8")
        return 0

    results: list[dict] = []

    if args.dry_run:
        log.info("Máquinas: %d   Sites: %d", len(servers), total_sites)
        for server in servers:
            results.extend(run_server(server, global_cfg, args.dry_run, args.only))
    else:
        assert_backup_root(global_cfg)
        root = Path(global_cfg["backup_root"])

        log.info("A acordar o NAS…")
        wake_disk(root)

        log.info("Máquinas: %d   Sites: %d", len(servers), total_sites)

        with DiskKeepalive(root):
            for server in servers:
                results.extend(run_server(server, global_cfg, args.dry_run, args.only))

    ok = sum(1 for r in results if r["success"])
    log.info("Resumo: ✓ %d   ✗ %d", ok, len(results) - ok)

    if args.results_json:
        Path(args.results_json).write_text(
            json.dumps({"results": results, "dry_run": args.dry_run},
                       indent=2, ensure_ascii=False),
            encoding="utf-8",
        )

    if not args.dry_run:
        notify(global_cfg, results)

    return 0 if ok == len(results) else 1


if __name__ == "__main__":
    sys.exit(main())
