<?php
// Product catalog — loaded from database.
// NOTE: This file is included by lib.php. Do NOT require lib.php here.
// The get_db_connection() function is already available when this file loads.

$db = get_db_connection();
$stmt = $db->query('SELECT * FROM products ORDER BY created_at ASC');
$rows = $stmt->fetchAll();

$PRODUCTS = [];
foreach ($rows as $row) {
    $PRODUCTS[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'price' => (float)$row['price'],
        'category' => $row['category'],
        'shortDescription' => $row['short_description'],
        'longDescription' => $row['long_description'],
        'imageUrl' => $row['image_url'],
        'stock' => (int)$row['stock'],
        'inStock' => (int)$row['stock'] > 0,
    ];
}
