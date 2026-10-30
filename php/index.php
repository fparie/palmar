<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/store.php';
require_once __DIR__ . '/uploads.php';

init_db();

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function file_url(string $filename): string
{
    return 'uploads/' . rawurlencode($filename);
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET' && ($path === '/' || $path === '/index.php')) {
    $catalog = catalog_load();
    $options = '';
    foreach ($catalog as $item) {
        $price = $item['price'] ?? null;
        $options .= '<option value="' . e($item['title']) . '">'
            . e($item['title']) . ' — ' . e($price ?: 'price n/a') . '</option>';
    }
    $rows = '';
    foreach (store_list_placements() as $p) {
        $photos = '';
        foreach ($p['photos'] as $f) {
            $photos .= '<a href="' . e(file_url($f)) . '"><img src="' . e(file_url($f)) . '" height="80" alt="photo"></a> ';
        }
        $agreement = $p['agreement_photo']
            ? '<a href="' . e(file_url($p['agreement_photo'])) . '">signed agreement</a>'
            : 'no agreement on file';
        $pedestal = $p['pedestal'] ? '✓ pedestal' : '—';
        $rows .= '<tr>'
            . '<td>' . (int) $p['id'] . '</td>'
            . '<td><a href="/placements/' . (int) $p['id'] . '">' . e($p['title']) . '</a></td>'
            . '<td>' . e($p['address']) . '</td>'
            . '<td>' . e($p['contact_name']) . '<br>' . e($p['contact_phone']) . '<br>' . e($p['contact_email']) . '</td>'
            . '<td>' . $agreement . '</td>'
            . '<td>' . $pedestal . '</td>'
            . '<td>' . $photos . '</td>'
            . '</tr>';
    }
    echo '<!doctype html><html><head><title>Palmar</title></head>
      <body style="font-family: sans-serif; max-width: 960px; margin: 2rem auto;">
      <h1>Palmar</h1><p>Art placement tracker.</p>
      <h2>Add placement</h2>
      <form method="post" action="/placements" enctype="multipart/form-data">
        <select name="title" required>' . $options . '</select><br>
        <input name="address" placeholder="Address" required><br>
        <input name="contact_name" placeholder="Contact person" required><br>
        <input name="contact_phone" placeholder="Phone number" required><br>
        <input name="contact_email" type="email" placeholder="Email" required><br>
        <label><input type="checkbox" name="pedestal" value="1"> Pedestal included</label><br>
        <label>Signed agreement (photo/scan):</label>
        <input type="file" name="agreement_photo" accept="image/*,.pdf"><br>
        <label>Placement photos:</label>
        <input type="file" name="photos[]" multiple accept="image/*"><br>
        <button type="submit">Add</button>
      </form>
      <h2>Placements</h2>
      <table border="1" cellpadding="6" style="border-collapse: collapse;">
        <tr><th>ID</th><th>Piece</th><th>Address</th><th>Contact</th><th>Agreement</th><th>Pedestal</th><th>Photos</th></tr>
        ' . $rows . '
      </table></body></html>';
    exit;
}

if ($method === 'POST' && $path === '/placements') {
    $fields = [
        'title' => trim($_POST['title'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'contact_name' => trim($_POST['contact_name'] ?? ''),
        'contact_phone' => trim($_POST['contact_phone'] ?? ''),
        'contact_email' => trim($_POST['contact_email'] ?? ''),
        'pedestal' => $_POST['pedestal'] ?? null,
    ];
    if (in_array('', [$fields['title'], $fields['address'], $fields['contact_name'],
                      $fields['contact_phone'], $fields['contact_email']], true)) {
        http_response_code(400);
        exit('Missing required fields');
    }

    $agreement_photo = null;
    if (!empty($_FILES['agreement_photo']['name'])) {
        $agreement_photo = upload_save($_FILES['agreement_photo']);
    }

    $photos = [];
    foreach (($_FILES['photos'] ?? [])['name'] ?? [] as $i => $name) {
        if (!$name) {
            continue;
        }
        $file = [
            'name' => $name,
            'tmp_name' => $_FILES['photos']['tmp_name'][$i],
            'size' => $_FILES['photos']['size'][$i],
            'error' => $_FILES['photos']['error'][$i],
        ];
        $saved = upload_save($file);
        if ($saved) {
            $photos[] = $saved;
        }
    }

    $id = store_create_placement($fields, $agreement_photo, $photos);
    header('Location: /placements/' . $id);
    exit;
}

if ($method === 'GET' && preg_match('#^/placements/(\d+)$#', $path, $m)) {
    $p = store_get_placement((int) $m[1]);
    if (!$p) {
        http_response_code(404);
        exit('Placement not found');
    }
    $photos = '';
    foreach ($p['photos'] as $f) {
        $photos .= '<p><img src="' . e(file_url($f)) . '" height="300" alt="photo"></p>';
    }
    $agreement = $p['agreement_photo']
        ? '<a href="' . e(file_url($p['agreement_photo'])) . '">View signed agreement</a>'
        : 'No agreement on file';
    echo '<!doctype html><html><head><title>Palmar — ' . e($p['title']) . '</title></head>
      <body style="font-family: sans-serif; max-width: 640px; margin: 2rem auto;">
      <p><a href="/">&larr; All placements</a></p>
      <h1>' . e($p['title']) . '</h1>
      <p>Address: ' . e($p['address']) . '</p>
      <p>Contact: ' . e($p['contact_name']) . ' — ' . e($p['contact_phone']) . ' — ' . e($p['contact_email']) . '</p>
      <p>Pedestal: ' . ($p['pedestal'] ? '✓ included' : 'not included') . '</p>
      <p>Agreement: ' . $agreement . '</p>
      <h2>Placement photos</h2>
      ' . ($photos ?: '<p>No photos yet.</p>') . '
      </body></html>';
    exit;
}

if ($method === 'GET' && preg_match('#^/uploads/(.+)$#', $path, $m)) {
    $file = basename($m[1]);
    $path_on_disk = UPLOAD_DIR . '/' . $file;
    if (!is_file($path_on_disk)) {
        http_response_code(404);
        exit('Not found');
    }
    header('Content-Type: ' . (mime_content_type($path_on_disk) ?: 'application/octet-stream'));
    readfile($path_on_disk);
    exit;
}

if ($method === 'GET' && $path === '/api/placements') {
    header('Content-Type: application/json');
    $out = [];
    foreach (store_list_placements() as $p) {
        $p['agreement_photo_url'] = $p['agreement_photo'] ? file_url($p['agreement_photo']) : null;
        $p['photo_urls'] = array_map('file_url', $p['photos']);
        unset($p['agreement_photo'], $p['photos']);
        $out[] = $p;
    }
    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'GET' && preg_match('#^/api/placements/(\d+)$#', $path, $m)) {
    header('Content-Type: application/json');
    $p = store_get_placement((int) $m[1]);
    if (!$p) {
        http_response_code(404);
        echo json_encode(['error' => 'not found']);
        exit;
    }
    $p['agreement_photo_url'] = $p['agreement_photo'] ? file_url($p['agreement_photo']) : null;
    $p['photo_urls'] = array_map('file_url', $p['photos']);
    unset($p['agreement_photo'], $p['photos']);
    echo json_encode($p, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'GET' && $path === '/api/catalog') {
    header('Content-Type: application/json');
    echo json_encode(catalog_load(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(404);
exit('Not found');
