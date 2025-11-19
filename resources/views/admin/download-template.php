<?php

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="product_template.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write header row
$header = [
    'name', 'sku', 'category_slug', 'subcategory_slug', 'description', 'short_description',
    'price', 'sale_price', 'cost_price', 'stock_quantity', 'low_stock_threshold',
    'length', 'width', 'height', 'actual_weight', 'is_active', 'is_featured'
];

fputcsv($output, $header);

// Write sample row
$sampleData = [
    'Premium Wireless Mouse',
    'MOUSE-001',
    'electronics',
    'computer-accessories',
    'High-quality wireless mouse with ergonomic design',
    'Comfortable wireless mouse',
    '29.99',
    '24.99',
    '15.00',
    '100',
    '10',
    '10.5',
    '6.2',
    '3.8',
    '0.15',
    '1',
    '0'
];

fputcsv($output, $sampleData);

fclose($output);
exit;
?>
