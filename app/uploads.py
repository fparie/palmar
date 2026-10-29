import re
import uuid
from pathlib import Path

ALLOWED_EXTENSIONS = {".jpg", ".jpeg", ".png", ".webp", ".gif", ".pdf"}
MAX_CONTENT_LENGTH = 25 * 1024 * 1024


def is_allowed(filename):
    return Path(filename).suffix.lower() in ALLOWED_EXTENSIONS


def safe_name(filename):
    suffix = Path(filename).suffix.lower()
    return f"{uuid.uuid4().hex}{suffix}"
