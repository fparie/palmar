import sqlite3
from pathlib import Path

DB_PATH = Path(__file__).with_name("palmar.db")

SCHEMA = """
CREATE TABLE IF NOT EXISTS placements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    address TEXT NOT NULL,
    contact_name TEXT NOT NULL,
    contact_phone TEXT NOT NULL,
    contact_email TEXT NOT NULL,
    pedestal INTEGER NOT NULL DEFAULT 0,
    agreement_photo TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE TABLE IF NOT EXISTS placement_photos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    placement_id INTEGER NOT NULL REFERENCES placements(id) ON DELETE CASCADE,
    filename TEXT NOT NULL
);
"""


def get_db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA foreign_keys = ON")
    return conn


def init_db():
    with get_db() as conn:
        conn.executescript(SCHEMA)


def list_placements():
    with get_db() as conn:
        rows = conn.execute("SELECT * FROM placements ORDER BY id DESC").fetchall()
        placements = [dict(r) for r in rows]
        for p in placements:
            p["pedestal"] = bool(p["pedestal"])
            photos = conn.execute(
                "SELECT filename FROM placement_photos WHERE placement_id = ?",
                (p["id"],),
            ).fetchall()
            p["photos"] = [r["filename"] for r in photos]
        return placements


def create_placement(fields, agreement_photo, photos):
    with get_db() as conn:
        cur = conn.execute(
            """INSERT INTO placements
               (title, address, contact_name, contact_phone, contact_email,
                pedestal, agreement_photo)
               VALUES (?, ?, ?, ?, ?, ?, ?)""",
            (
                fields["title"],
                fields["address"],
                fields["contact_name"],
                fields["contact_phone"],
                fields["contact_email"],
                1 if fields.get("pedestal") else 0,
                agreement_photo,
            ),
        )
        placement_id = cur.lastrowid
        for filename in photos:
            conn.execute(
                "INSERT INTO placement_photos (placement_id, filename) VALUES (?, ?)",
                (placement_id, filename),
            )
        return placement_id


def get_placement(placement_id):
    with get_db() as conn:
        row = conn.execute(
            "SELECT * FROM placements WHERE id = ?", (placement_id,)
        ).fetchone()
        if row is None:
            return None
        p = dict(row)
        p["pedestal"] = bool(p["pedestal"])
        p["photos"] = [
            r["filename"]
            for r in conn.execute(
                "SELECT filename FROM placement_photos WHERE placement_id = ?",
                (placement_id,),
            )
        ]
        return p
