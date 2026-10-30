<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function catalog_clean(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = strip_tags($text);
    return trim(str_replace("\xc2\xa0", ' ', $text));
}

function catalog_parse(string $page): array
{
    $items = [];
    preg_match_all(
        '#<h2[^>]*>\s*<a[^>]*product/([a-z0-9-]+)/[^>]*>(.*?)</a>\s*</h2>#s',
        $page,
        $matches,
        PREG_SET_ORDER | PREG_OFFSET_CAPTURE
    );
    foreach ($matches as $i => $m) {
        $title = catalog_clean($m[2][0]);
        $price = null;
        $offset = (int) $m[0][1] + strlen($m[0][0]);
        if (preg_match(
            '#class="[^"]*wc-block-components-product-price[^"]*".*?<bdi>(.*?)</bdi>#s',
            $page,
            $pm,
            0,
            $offset
        )) {
            $price = catalog_clean($pm[1]);
        }
        $items[] = ['id' => $i + 1, 'slug' => $m[1][0], 'title' => $title, 'price' => $price];
    }
    return $items;
}

function catalog_fetch(): array
{
    $ctx = stream_context_create(['http' => [
        'timeout' => 15,
        'header' => "User-Agent: Palmar/1.0\r\n",
    ]]);
    $page = file_get_contents(SHOP_URL, false, $ctx);
    if ($page === false) {
        return [];
    }
    $items = catalog_parse($page);
    if ($items) {
        file_put_contents(__DIR__ . '/catalog_cache.json', json_encode($items));
    }
    return $items;
}

function catalog_load(): array
{
    $items = catalog_fetch();
    if (!$items) {
        $cache = __DIR__ . '/catalog_cache.json';
        if (is_file($cache)) {
            $items = json_decode(file_get_contents($cache), true) ?: [];
        }
    }
    return $items;
}
