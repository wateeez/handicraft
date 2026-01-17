<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Nunito:wght@300;400;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-content">
                <div class="contact-info">
                    <span><i class="fas fa-phone"></i> +1 234 567 8900</span>
                    <span><i class="fas fa-envelope"></i> info@ecommerce.com</span>
                </div>
                <div class="top-links">
                    <a href="/shop/about">About</a>
                    <a href="/shop/contact">Contact</a>
                    <a href="/shop/faq">FAQ</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="/shop">
                        <h1><?php echo APP_NAME; ?></h1>
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="search-bar">
                    <form action="/shop/products" method="GET">
                        <input type="text" name="search" placeholder="Search products..."
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>

                <!-- Header Actions -->
                <div class="header-actions">
                    <a href="/shop/cart" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?php try {
                            echo getCartCount();
                        } catch (Exception $e) {
                            echo '0';
                        } ?></span>
                    </a>
                    <a href="/admin/login" class="admin-link">
                        <i class="fas fa-user"></i> Admin
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="main-nav">
        <div class="container">
            <ul class="nav-menu">
                <li><a href="/shop">Home</a></li>
                <li><a href="/shop/products">Products</a></li>

                <!-- Categories removed from top nav as requested -->

                <li><a href="/shop/blog">Blog</a></li>
                <li><a href="/shop/contact">Contact</a></li>
            </ul>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php
    $successMsg = session('success');
    $errorMsg = session('error');
    ?>
    <?php if ($successMsg): ?>
        <div class="alert alert-success">
            <div class="container"><?php echo htmlspecialchars($successMsg); ?></div>
        </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert-error">
            <div class="container"><?php echo htmlspecialchars($errorMsg); ?></div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main-content">