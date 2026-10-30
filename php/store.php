<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function store_list_placements(): array
{
    $rows = db()->query('SELECT * FROM placements ORDER BY id DESC')->fetchAll();
    $ids = array_column($rows, 'id');
    $photos = [];
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare("SELECT placement_id, filename FROM placement_photos WHERE placement_id IN ($in) ORDER BY id");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $row) {
            $photos[$row['placement_id']][] = $row['filename'];
        }
    }
    return array_map(function (array $p) use ($photos) {
        $p['pedestal'] = (bool) $p['pedestal'];
        $p['photos'] = $photos[$p['id']] ?? [];
        return $p;
    }, $rows);
}

function store_get_placement(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM placements WHERE id = ?');
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if (!$p) {
        return null;
    }
    $p['pedestal'] = (bool) $p['pedestal'];
    $stmt = db()->prepare('SELECT filename FROM placement_photos WHERE placement_id = ? ORDER BY id');
    $stmt->execute([$id]);
    $p['photos'] = array_column($stmt->fetchAll(), 'filename');
    return $p;
}

function store_create_placement(array $fields, ?string $agreement_photo, array $photos): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO placements
             (title, address, contact_name, contact_phone, contact_email, pedestal, agreement_photo)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $fields['title'],
            $fields['address'],
            $fields['contact_name'],
            $fields['contact_phone'],
            $fields['contact_email'],
            !empty($fields['pedestal']) ? 1 : 0,
            $agreement_photo,
        ]);
        $placement_id = (int) $pdo->lastInsertId();
        $photo_stmt = $pdo->prepare(
            'INSERT INTO placement_photos (placement_id, filename) VALUES (?, ?)'
        );
        foreach ($photos as $filename) {
            $photo_stmt->execute([$placement_id, $filename]);
        }
        $pdo->commit();
        return $placement_id;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
