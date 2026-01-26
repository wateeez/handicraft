<?php

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="product_template.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write header row
$header = [
    'name', 'sku', 'category_slug', 'subcategory_slug', 'description', 'short_description',
    'price', 'sale_price', 'cost_price', 'subject_to_availability',
    'length', 'width', 'height', 'actual_weight', 'is_active', 'is_featured'
];

fputcsv($output, $header);

// Write sample row
$sampleData = [
    'Premium Handmade Vase',
    'VASE-001',
    'home-decor',
    'vases',
    'Handcrafted ceramic vase with unique glaze',
    'Beautiful ceramic vase',
    '49.99',
    '',
    '15.00',
    '0', // subject_to_availability (0 = Available, 1 = Subject to Availability)
    '15.5',
    '8.2',
    '8.2',
    '1.2',
    '1',
    '0'
];

fputcsv($output, $sampleData);

fclose($output);
exit;
?>
