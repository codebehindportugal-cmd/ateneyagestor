from __future__ import annotations

import argparse
import ctypes
import json
import os
import shutil
import socket
import sqlite3
import sys
import tempfile
import threading
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid
from dataclasses import dataclass
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Any

try:
    import tkinter as tk
except Exception:
    tk = None


APP_NAME = "Gestao Ateneya - Monitor de Produtividade"


@dataclass
class CurrentApp:
    process_name: str
    app_name: str


class WindowsActivity:
    PROCESS_QUERY_LIMITED_INFORMATION = 0x1000

    def __init__(self) -> None:
        self.user32 = ctypes.windll.user32
        self.kernel32 = ctypes.windll.kernel32

    def idle_seconds(self) -> int:
        class LASTINPUTINFO(ctypes.Structure):
            _fields_ = [("cbSize", ctypes.c_uint), ("dwTime", ctypes.c_uint)]

        last_input = LASTINPUTINFO()
        last_input.cbSize = ctypes.sizeof(LASTINPUTINFO)

        if not self.user32.GetLastInputInfo(ctypes.byref(last_input)):
            return 0

        elapsed_ms = self.kernel32.GetTickCount() - last_input.dwTime
        return max(0, int(elapsed_ms / 1000))

    def foreground_app(self) -> CurrentApp:
        hwnd = self.user32.GetForegroundWindow()
        if not hwnd:
            return CurrentApp(process_name="unknown", app_name="Unknown")

        pid = ctypes.c_ulong()
        self.user32.GetWindowThreadProcessId(hwnd, ctypes.byref(pid))

        process_name = self.process_name(pid.value)
        return CurrentApp(process_name=process_name, app_name=self.pretty_app_name(process_name))

    def process_name(self, pid: int) -> str:
        handle = self.kernel32.OpenProcess(self.PROCESS_QUERY_LIMITED_INFORMATION, False, pid)
        if not handle:
            return "unknown"

        try:
            buffer = ctypes.create_unicode_buffer(1024)
            size = ctypes.c_ulong(len(buffer))
            ok = self.kernel32.QueryFullProcessImageNameW(handle, 0, buffer, ctypes.byref(size))
            if not ok:
                return "unknown"
            return os.path.basename(buffer.value)
        finally:
            self.kernel32.CloseHandle(handle)

    def pretty_app_name(self, process_name: str) -> str:
        name = process_name.lower()
        known = {
            "chrome.exe": "Google Chrome",
            "msedge.exe": "Microsoft Edge",
            "firefox.exe": "Mozilla Firefox",
            "excel.exe": "Microsoft Excel",
            "winword.exe": "Microsoft Word",
            "outlook.exe": "Microsoft Outlook",
            "teams.exe": "Microsoft Teams",
            "code.exe": "Visual Studio Code",
        }
        return known.get(name, process_name)


class BrowserHistory:
    CHROME_EPOCH_OFFSET_SECONDS = 11644473600

    def __init__(self) -> None:
        self.local_app_data = Path(os.environ.get("LOCALAPPDATA", ""))
        self.app_data = Path(os.environ.get("APPDATA", ""))

    def recent_domains(self, since: datetime) -> list[dict[str, Any]]:
        domains: list[dict[str, Any]] = []
        domains.extend(self.chromium_domains("Google Chrome", self.local_app_data / "Google/Chrome/User Data", since))
        domains.extend(self.chromium_domains("Microsoft Edge", self.local_app_data / "Microsoft/Edge/User Data", since))
        domains.extend(self.firefox_domains(since))

        seen: set[tuple[str, str, str]] = set()
        unique: list[dict[str, Any]] = []
        for item in sorted(domains, key=lambda row: row["visited_at"]):
            key = (item["browser"], item["domain"], item["visited_at"].isoformat())
            if key in seen:
                continue
            seen.add(key)
            unique.append(item)

        return unique[:100]

    def chromium_domains(self, browser: str, user_data: Path, since: datetime) -> list[dict[str, Any]]:
        if not user_data.exists():
            return []

        since_value = int((since.timestamp() + self.CHROME_EPOCH_OFFSET_SECONDS) * 1_000_000)
        rows: list[dict[str, Any]] = []

        for history_path in user_data.glob("*/History"):
            rows.extend(self.query_sqlite_copy(
                history_path,
                "SELECT url, last_visit_time FROM urls WHERE last_visit_time > ? ORDER BY last_visit_time ASC LIMIT 100",
                (since_value,),
                lambda row: self.chromium_row(browser, row),
            ))

        return rows

    def chromium_row(self, browser: str, row: tuple[Any, ...]) -> dict[str, Any] | None:
        domain = self.domain_from_url(str(row[0]))
        if not domain:
            return None

        visited_at = datetime.fromtimestamp(
            (int(row[1]) / 1_000_000) - self.CHROME_EPOCH_OFFSET_SECONDS,
            tz=timezone.utc,
        )

        return {"browser": browser, "domain": domain, "visited_at": visited_at}

    def firefox_domains(self, since: datetime) -> list[dict[str, Any]]:
        profiles = self.app_data / "Mozilla/Firefox/Profiles"
        if not profiles.exists():
            return []

        since_value = int(since.timestamp() * 1_000_000)
        rows: list[dict[str, Any]] = []

        for places_path in profiles.glob("*/places.sqlite"):
            rows.extend(self.query_sqlite_copy(
                places_path,
                """
                SELECT moz_places.url, moz_historyvisits.visit_date
                FROM moz_historyvisits
                JOIN moz_places ON moz_places.id = moz_historyvisits.place_id
                WHERE moz_historyvisits.visit_date > ?
                ORDER BY moz_historyvisits.visit_date ASC
                LIMIT 100
                """,
                (since_value,),
                lambda row: self.firefox_row(row),
            ))

        return rows

    def firefox_row(self, row: tuple[Any, ...]) -> dict[str, Any] | None:
        domain = self.domain_from_url(str(row[0]))
        if not domain:
            return None

        visited_at = datetime.fromtimestamp(int(row[1]) / 1_000_000, tz=timezone.utc)

        return {"browser": "Mozilla Firefox", "domain": domain, "visited_at": visited_at}

    def query_sqlite_copy(self, path: Path, query: str, params: tuple[Any, ...], mapper) -> list[dict[str, Any]]:
        if not path.exists():
            return []

        tmp_name = ""
        try:
            with tempfile.NamedTemporaryFile(delete=False, suffix=".sqlite") as tmp:
                tmp_name = tmp.name
            shutil.copy2(path, tmp_name)

            with sqlite3.connect(tmp_name) as connection:
                cursor = connection.execute(query, params)
                return [mapped for row in cursor.fetchall() if (mapped := mapper(row)) is not None]
        except Exception:
            return []
        finally:
            if tmp_name:
                try:
                    os.unlink(tmp_name)
                except OSError:
                    pass

    def domain_from_url(self, url: str) -> str:
        parsed = urllib.parse.urlparse(url)
        if parsed.scheme not in {"http", "https"}:
            return ""

        hostname = (parsed.hostname or "").lower()
        if hostname.startswith("www."):
            hostname = hostname[4:]

        return hostname


class ProductivityAgent:
    def __init__(self, config_path: Path) -> None:
        self.config_path = config_path
        self.config = self.load_config()
        self.activity = WindowsActivity()
        self.browser_history = BrowserHistory()
        self.hostname = socket.gethostname()
        self.device_uid = self.config.get("device_uid") or self.ensure_device_uid()
        self.buffer: list[dict[str, Any]] = []
        self.last_key: tuple[str, str, str] | None = None
        self.last_started_at = self.now()
        self.last_domain_check = self.now() - timedelta(minutes=2)
        self.last_sent = "Nunca"
        self.last_status = "A iniciar"
        self.last_app = "-"
        self.running = True

    def load_config(self) -> dict[str, Any]:
        if not self.config_path.exists():
            raise RuntimeError(f"Config nao encontrada: {self.config_path}")

        return json.loads(self.config_path.read_text(encoding="utf-8"))

    def ensure_device_uid(self) -> str:
        uid_path = self.config_path.with_name("device_uid.txt")
        if uid_path.exists():
            return uid_path.read_text(encoding="utf-8").strip()

        value = str(uuid.uuid4())
        uid_path.write_text(value, encoding="utf-8")
        return value

    def now(self) -> datetime:
        return datetime.now(timezone.utc)

    def in_work_hours(self) -> bool:
        work = self.config.get("work_hours", {})
        if not work.get("enabled", True):
            return True

        local = datetime.now()
        if local.isoweekday() not in work.get("weekdays", [1, 2, 3, 4, 5]):
            return False

        start = work.get("start", "09:00")
        end = work.get("end", "18:00")
        current = local.strftime("%H:%M")
        return start <= current <= end

    def sample(self) -> None:
        if not self.in_work_hours():
            self.last_status = "Fora do horario laboral"
            return

        idle_threshold = int(self.config.get("idle_threshold_seconds", 300))
        idle = self.activity.idle_seconds()
        app = self.activity.foreground_app()
        state = "idle" if idle >= idle_threshold else "active"
        key = ("app", state, app.process_name)
        self.last_app = f"{app.app_name} ({state})"
        self.last_status = "A monitorizar"

        if self.last_key is None:
            self.last_key = key
            self.last_started_at = self.now()
            return

        if key != self.last_key:
            self.flush_current_event()
            self.last_key = key
            self.last_started_at = self.now()

    def flush_current_event(self) -> None:
        if self.last_key is None:
            return

        ended_at = self.now()
        duration = int((ended_at - self.last_started_at).total_seconds())
        if duration <= 0:
            return

        _, state, process_name = self.last_key
        app_name = self.activity.pretty_app_name(process_name)

        self.buffer.append({
            "event_type": "app",
            "app_name": app_name,
            "process_name": process_name,
            "activity_state": state,
            "started_at": self.last_started_at.isoformat(),
            "ended_at": ended_at.isoformat(),
            "duration_seconds": duration,
            "metadata": {
                "privacy": {
                    "window_titles": False,
                    "screenshots": False,
                    "keystrokes": False,
                }
            },
        })

    def collect_domain_events(self) -> None:
        privacy = self.config.get("privacy", {})
        if not privacy.get("collect_domains", False):
            return

        now = self.now()
        recent = self.browser_history.recent_domains(self.last_domain_check)
        self.last_domain_check = now

        for item in recent:
            self.buffer.append({
                "event_type": "site",
                "app_name": item["browser"],
                "process_name": "",
                "domain": item["domain"],
                "activity_state": "active",
                "started_at": item["visited_at"].isoformat(),
                "ended_at": item["visited_at"].isoformat(),
                "duration_seconds": 0,
                "metadata": {
                    "privacy": {
                        "full_url": False,
                        "page_title": False,
                        "content": False,
                        "screenshots": False,
                        "keystrokes": False,
                    }
                },
            })

    def send(self) -> None:
        if not self.buffer:
            now = self.now()
            self.buffer.append({
                "event_type": "heartbeat",
                "activity_state": "active",
                "started_at": now.isoformat(),
                "ended_at": now.isoformat(),
                "duration_seconds": 0,
                "metadata": {
                    "agent": self.config.get("agent", {}),
                    "privacy": {
                        "window_titles": False,
                        "screenshots": False,
                        "keystrokes": False,
                    },
                },
            })

        if not self.buffer:
            return

        api_url = self.config.get("api_url", "").rstrip("/")
        token = self.config.get("token", "")
        if not api_url or not token:
            self.last_status = "Config incompleta"
            return

        payload = {
            "device_uid": self.device_uid,
            "hostname": self.hostname,
            "events": self.buffer[:],
        }

        request = urllib.request.Request(
            api_url + "/api/productivity/events",
            data=json.dumps(payload).encode("utf-8"),
            headers={
                "Authorization": f"Bearer {token}",
                "Accept": "application/json",
                "Content-Type": "application/json",
            },
            method="POST",
        )

        try:
            with urllib.request.urlopen(request, timeout=20) as response:
                if response.status >= 400:
                    raise RuntimeError(f"HTTP {response.status}")
            self.buffer.clear()
            self.last_sent = datetime.now().strftime("%H:%M:%S")
            self.last_status = "Dados enviados"
        except (urllib.error.URLError, TimeoutError, RuntimeError) as exc:
            self.last_status = f"Falha ao enviar: {exc}"

    def loop(self) -> None:
        sample_interval = int(self.config.get("sample_interval_seconds", 5))
        send_interval = int(self.config.get("send_interval_seconds", 60))
        next_send = time.time() + send_interval

        while self.running:
            self.sample()

            if time.time() >= next_send:
                self.flush_current_event()
                self.collect_domain_events()
                self.last_started_at = self.now()
                self.send()
                next_send = time.time() + send_interval

            time.sleep(sample_interval)


class StatusWindow:
    def __init__(self, agent: ProductivityAgent, minimized: bool = False) -> None:
        self.agent = agent
        self.minimized = minimized

    def run(self) -> None:
        if tk is None:
            self.agent.loop()
            return

        worker = threading.Thread(target=self.agent.loop, daemon=True)
        worker.start()

        root = tk.Tk()
        root.title(APP_NAME)
        root.geometry("420x210")
        root.resizable(False, False)

        if self.minimized:
            root.iconify()

        title = tk.Label(root, text=APP_NAME, font=("Segoe UI", 12, "bold"))
        title.pack(pady=(14, 4))

        notice = tk.Label(
            root,
            text="Monitorizacao transparente: apps, dominios e atividade/inatividade. Fechar minimiza. Sem teclas, screenshots ou conteudo privado.",
            wraplength=370,
            justify="center",
            fg="#475569",
        )
        notice.pack(pady=(0, 12))

        status = tk.Label(root, text="", font=("Segoe UI", 10))
        status.pack(pady=2)

        app = tk.Label(root, text="", font=("Segoe UI", 10))
        app.pack(pady=2)

        sent = tk.Label(root, text="", font=("Segoe UI", 10))
        sent.pack(pady=2)

        def refresh() -> None:
            status.config(text=f"Estado: {self.agent.last_status}")
            app.config(text=f"Aplicacao: {self.agent.last_app}")
            sent.config(text=f"Ultimo envio: {self.agent.last_sent}")
            root.after(1000, refresh)

        def on_close() -> None:
            self.agent.last_status = "A monitorizar em segundo plano"
            root.iconify()

        root.protocol("WM_DELETE_WINDOW", on_close)
        refresh()
        if self.minimized:
            root.after(100, root.iconify)
        root.mainloop()


def main() -> int:
    if sys.platform != "win32":
        print("Este agent foi criado para Windows.")
        return 1

    parser = argparse.ArgumentParser()
    parser.add_argument("--config", default="config.json")
    parser.add_argument("--minimized", action="store_true")
    args = parser.parse_args()

    agent = ProductivityAgent(Path(args.config))
    StatusWindow(agent, minimized=args.minimized).run()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
