<?php
require_once __DIR__ . '/../includes/init.php';

// Get the current page from URL
$page = isset($_GET['page']) ? sanitize($_GET['page']) : 'home';

// Define allowed pages
$allowedPages = [
    'home',
    'products',
    'product-detail',
    'cart',
    'checkout',
    'contact',
    'faq',
    'blog',
    'blog-detail',
    'about',
    'checkout-success'
];

// Check if page is allowed
if (!in_array($page, $allowedPages)) {
    $page = 'home';
}

// Include header
include __DIR__ . '/../views/partials/header.php';

// Include the requested page
$pageFile = __DIR__ . '/../views/pages/' . $page . '.php';
if (file_exists($pageFile)) {
    include $pageFile;
} else {
    echo '<div class="container"><h1>Page Not Found</h1></div>';
}

// Include footer
include __DIR__ . '/../views/partials/footer.php';
?>
