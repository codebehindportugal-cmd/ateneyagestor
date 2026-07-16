import os
import yaml
import json
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any, Dict, Union, Optional   # ← acrescentar Union

@dataclass
class WintouchConfig:
    base_url: str
    api_key: str
    login_email: str
    login_password: str

@dataclass
class WooConfig:
    base_url: str
    consumer_key: str
    consumer_secret: str
    version: str = "wc/v3"
    admin_username: Optional[str] = None
    admin_app_password: Optional[str] = None
    images_base_url: Optional[str] = None

@dataclass
class DiscountConfig:
    # Como definir o período de promoção no WooCommerce:
    #   monthly   → primeiro ao último dia do mês atual (padrão)
    #   weekly    → segunda-feira a domingo da semana atual
    #   quarterly → primeiro ao último dia do trimestre atual
    #   annual    → 1 janeiro a 31 dezembro do ano atual
    #   wintouch  → usar as datas reais vindas do Wintouch
    period_mode: str = "monthly"

@dataclass
class SyncConfig:
    batch_size: int = 50
    default_currency: str = "EUR"
    download_images: bool = True
    scope: Dict[str, bool] = field(default_factory=dict)

    def wants(self, key: str) -> bool:
        """Devolve True se o painel não desativou explicitamente esta parte do sync."""
        return bool(self.scope.get(key, True))

@dataclass
class SmtpConfig:
    user: str       # login no servidor
    from_: str      # cabeçalho do e-mail
    password: str
    to: str
    host: str
    port: int

@dataclass
class Settings:
    wintouch: WintouchConfig
    woocommerce: WooConfig
    sync: SyncConfig
    discount: DiscountConfig = field(default_factory=DiscountConfig)
    smtp: Optional[SmtpConfig] = None

def load_yaml(path: Union[str, Path]) -> Dict[str, Any]:
    path = Path(path)
    with open(path, "r", encoding="utf-8") as fp:
        if path.suffix.lower() == ".json":
            return json.load(fp)
        return yaml.safe_load(fp)

def get_settings(path: Union[str, Path] = "config.yaml") -> Settings:
    data = load_yaml(path)
    smtp_data = data.get("smtp", {})
    if "from" in smtp_data:
        smtp_data["from_"] = smtp_data.pop("from")
    smtp = SmtpConfig(**smtp_data) if any(smtp_data.values()) else None
    discount_data = data.get("discount", {})
    return Settings(
        wintouch=WintouchConfig(**data["wintouch"]),
        woocommerce=WooConfig(**data["woocommerce"]),
        sync=SyncConfig(**data.get("sync", {})),
        discount=DiscountConfig(**discount_data) if discount_data else DiscountConfig(),
        smtp=smtp,
    )
