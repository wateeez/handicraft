<?php
$adminName = session('admin_name', 'Admin');
$adminRole = session('admin_role', 'admin');
$currentPage = request()->segment(2) ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Admin Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body class="admin-panel">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h2><?php echo APP_NAME; ?></h2>
            <p>Admin Panel</p>
        </div>
        <nav class="sidebar-nav">
            <a href="/admin/dashboard" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="/admin/products" class="<?php echo $currentPage === 'products' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="/admin/categories" class="<?php echo $currentPage === 'categories' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="/admin/shipping" class="<?php echo $currentPage === 'shipping' ? 'active' : ''; ?>">
                <i class="fas fa-truck"></i> Shipping
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <!-- Top Bar -->
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h1>
            </div>
            <div class="header-right">
                <a href="/shop" target="_blank" class="btn btn-sm">
                    <i class="fas fa-external-link-alt"></i> View Website
                </a>
                <div class="admin-profile">
                    <span><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="dropdown">
                        <a href="/admin/logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php
        $successMsg = session('success');
        $errorMsg = session('error');
        ?>
        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>

        <!-- Page Content -->
        <main class="admin-content">