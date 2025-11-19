<?php

header('Content-Type: application/json');

if (!isset($_GET['category_id'])) {
    echo json_encode([]);
    exit;
}

$categoryId = intval($_GET['category_id']);

$subcategories = $db->fetchAll(
    "SELECT id, name, slug FROM subcategories WHERE category_id = ? AND is_active = 1 ORDER BY name ASC",
    [$categoryId]
);

echo json_encode($subcategories);
?>
