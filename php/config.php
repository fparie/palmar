<?php

declare(strict_types=1);

const DB_PATH = __DIR__ . '/palmar.db';

// Development default: SQLite (auto-created). For MySQL/MariaDB apply schema.sql
// and set e.g.: const DB_DSN = 'mysql:host=localhost;dbname=palmar';
const DB_DSN = 'sqlite:' . DB_PATH;

const DB_USER = null;

const DB_PASS = null;

const UPLOAD_DIR = __DIR__ . '/uploads';

const SHOP_URL = 'https://imperionism.com/shop';

const MAX_UPLOAD_BYTES = 25 * 1024 * 1024;

const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'];

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        if (str_starts_with(DB_DSN, 'sqlite:')) {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
    }
    return $pdo;
}

function init_db(): void
{
    if (!str_starts_with(DB_DSN, 'sqlite:')) {
        return;
    }
    db()->exec(<<<'SQL'
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
SQL);
}
