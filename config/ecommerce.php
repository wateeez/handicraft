<?php

return [
    // Application Configuration
    'app_name' => env('ECOMMERCE_APP_NAME', 'E-Commerce Store'),
    'app_url' => env('APP_URL', 'http://localhost'),

    // File Upload Configuration
    'upload_dir' => public_path('uploads/'),
    'max_file_size' => 5242880, // 5MB in bytes

    // Pagination
    'products_per_page' => 12,
    'admin_items_per_page' => 20,

    // Shipping Configuration
    'dimensional_factor' => 5000, // Standard dimensional weight divisor (L x W x H / 5000)
    'packaging_buffer' => 8, // 8 cm added to each dimension

    // Tax Configuration
    'default_tax_rate' => 15, // 15%

    // Currency
    'currency_symbol' => '$',
    'currency_code' => 'USD',
];
