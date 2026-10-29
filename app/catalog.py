import html as html_lib
import json
import re
import urllib.request
from pathlib import Path

SHOP_URL = "https://imperionism.com/shop"
CACHE_PATH = Path(__file__).with_name("catalog_cache.json")

TITLE_RE = re.compile(r'<h2[^>]*>\s*<a[^>]*product/([^"]*"+/[^>]*>(.*?)</a>\s*</h2>', re.S)
PRICE_RE = re.compile(r'class="[^"]*wc-block-components-product-price[^"]*".*?<bdi>(.*?)</bdi>', re.S)
TAG_RE = re.compile(r"<[^>]+>")


def _clean(text):
    text = html_lib.unescape(text)
    return TAG_RE.sub("", text).strip().replace("\xa0", " ")


def parse_shop_html(page):
    items = []
    for i, m in enumerate(TITLE_RE.finditer(page)):
        slug = m.group(1)
        title = _clean(m.group(2))
        price = None
        pm = PRICE_RE.search(page, m.end())
        if pm:
            price = _clean(pm.group(1))
        items.append({"id": i + 1, "slug": slug, "title": title, "price": price})
    return items


def fetch_catalog():
    req = urllib.request.Request(SHOP_URL, headers={"User-Agent": "Palmar/1.0"})
    with urllib.request.urlopen(req, timeout=15) as resp:
        items = parse_shop_html(resp.read().decode("utf-8", "replace"))
    if items:
        CACHE_PATH.write_text(json.dumps(items))
    return items


def load_catalog():
    try:
        return fetch_catalog()
    except OSError:
        if CACHE_PATH.exists():
            return json.loads(CACHE_PATH.read_text())
        return []
