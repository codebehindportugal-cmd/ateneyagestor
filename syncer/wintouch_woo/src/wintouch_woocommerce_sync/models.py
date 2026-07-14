from dataclasses import dataclass, field
from typing import List, Optional

@dataclass
class ProductImage:
    url: str
    alt: str = ""

@dataclass
class Product:
    name: str
    sku: str
    short_description: str
    description: str
    price: float
    currency: str
    categories: List[str]
    brand: str
    weight: Optional[float] = None
    images: List[ProductImage] = field(default_factory=list)
    vat_id: Optional[str] = None
    stock_quantity: int = 0
    wintouch_id: Optional[str] = None
    second_category_id: Optional[str] = None
    nao_web: bool = False