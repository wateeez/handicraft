<?php

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="products_export_' . date('Y-m-d') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write header row
$header = [
    'id', 'name', 'sku', 'category_slug', 'subcategory_slug', 'description', 'short_description',
    'price', 'sale_price', 'cost_price', 'stock_quantity', 'low_stock_threshold',
    'length', 'width', 'height', 'actual_weight', 'is_active', 'is_featured', 'created_at'
];

fputcsv($output, $header);

// Get all products with category and subcategory info
$products = $db->fetchAll(
    "SELECT p.*, c.slug as category_slug, sc.slug as subcategory_slug
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
     ORDER BY p.id ASC"
);

// Write product data
foreach ($products as $product) {
    $row = [
        $product['id'],
        $product['name'],
        $product['sku'],
        $product['category_slug'],
        $product['subcategory_slug'] ?? '',
        $product['description'],
        $product['short_description'],
        $product['price'],
        $product['sale_price'] ?? '',
        $product['cost_price'] ?? '',
        $product['stock_quantity'],
        $product['low_stock_threshold'],
        $product['length'] ?? '',
        $product['width'] ?? '',
        $product['height'] ?? '',
        $product['actual_weight'] ?? '',
        $product['is_active'],
        $product['is_featured'],
        $product['created_at']
    ];
    
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
