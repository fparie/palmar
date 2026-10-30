<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function upload_is_allowed(string $filename): bool
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_EXTENSIONS, true);
}

function upload_safe_name(string $filename): string
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

function upload_save(array $file): ?string
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return null;
    }
    if (!upload_is_allowed($file['name'])) {
        return null;
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $name = upload_safe_name($file['name']);
    if (is_uploaded_file($file['tmp_name'])) {
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
            return null;
        }
    } else {
        if (!copy($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
            return null;
        }
    }
    return $name;
}
