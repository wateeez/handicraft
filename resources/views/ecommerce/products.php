<?php
$pageTitle = "Products";

// Get filter parameters
$categorySlug = isset($_GET['category']) ? sanitize($_GET['category']) : null;
$subcategorySlug = isset($_GET['subcategory']) ? sanitize($_GET['subcategory']) : null;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : null;
$sortBy = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest';
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;

// Build query
$where = ["p.is_active = 1"];
$params = [];

if ($categorySlug) {
    $where[] = "c.slug = ?";
    $params[] = $categorySlug;
}

if ($subcategorySlug) {
    $where[] = "sc.slug = ?";
    $params[] = $subcategorySlug;
}

if ($search) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = implode(' AND ', $where);

// Get total count
$countSql = "SELECT COUNT(DISTINCT p.id) as total 
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
             WHERE $whereClause";
$totalResult = $db->fetchOne($countSql, $params);
$total = $totalResult['total'];

// Pagination
$pagination = paginate($total, PRODUCTS_PER_PAGE, $currentPage);

// Sorting
$orderBy = match($sortBy) {
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name' => 'p.name ASC',
    default => 'p.created_at DESC'
};

// Get products
$sql = "SELECT p.*, c.name as category_name, sc.name as subcategory_name,
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";

$products = $db->fetchAll($sql, $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
?>

<div class="products-page">
    <div class="container">
        <div class="page-header">
            <h1>
                <?php
                if ($search) {
                    echo 'Search Results for "' . htmlspecialchars($search) . '"';
                } elseif ($categorySlug) {
                    $cat = $db->fetchOne("SELECT name FROM categories WHERE slug = ?", [$categorySlug]);
                    echo htmlspecialchars($cat['name'] ?? 'Products');
                } else {
                    echo 'All Products';
                }
                ?>
            </h1>
            <p class="results-count">Showing <?php echo count($products); ?> of <?php echo $total; ?> products</p>
        </div>

        <div class="products-layout">
            <!-- Sidebar Filters -->
            <aside class="products-sidebar">
                <div class="filter-section">
                    <h3>Categories</h3>
                    <ul class="category-filter">
                        <li>
                            <a href="/shop/products" <?php echo !$categorySlug ? 'class="active"' : ''; ?>>
                                All Categories
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="/shop/products?category=<?php echo $cat['slug']; ?>" 
                                   <?php echo ($categorySlug === $cat['slug']) ? 'class="active"' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                                <?php
                                // Get subcategories
                                $subcats = $db->fetchAll(
                                    "SELECT * FROM subcategories WHERE category_id = ? AND is_active = 1 ORDER BY name ASC",
                                    [$cat['id']]
                                );
                                if ($subcats && $categorySlug === $cat['slug']):
                                ?>
                                    <ul class="subcategory-filter">
                                        <?php foreach ($subcats as $subcat): ?>
                                            <li>
                                                <a href="/shop/products?category=<?php echo $cat['slug']; ?>&subcategory=<?php echo $subcat['slug']; ?>"
                                                   <?php echo ($subcategorySlug === $subcat['slug']) ? 'class="active"' : ''; ?>>
                                                    <?php echo htmlspecialchars($subcat['name']); ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

            <!-- Products Grid -->
            <div class="products-content">
                <!-- Sorting -->
                <div class="products-toolbar">
                    <div class="sort-options">
                        <label>Sort by:</label>
                        <select id="sortSelect" onchange="window.location.href=this.value">
                            <option value="/shop/products?<?php echo $categorySlug ? 'category=' . $categorySlug . '&' : ''; ?><?php echo $subcategorySlug ? 'subcategory=' . $subcategorySlug . '&' : ''; ?><?php echo $search ? 'search=' . urlencode($search) . '&' : ''; ?>sort=newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="/shop/products?<?php echo $categorySlug ? 'category=' . $categorySlug . '&' : ''; ?><?php echo $subcategorySlug ? 'subcategory=' . $subcategorySlug . '&' : ''; ?><?php echo $search ? 'search=' . urlencode($search) . '&' : ''; ?>sort=price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="/shop/products?<?php echo $categorySlug ? 'category=' . $categorySlug . '&' : ''; ?><?php echo $subcategorySlug ? 'subcategory=' . $subcategorySlug . '&' : ''; ?><?php echo $search ? 'search=' . urlencode($search) . '&' : ''; ?>sort=price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="/shop/products?<?php echo $categorySlug ? 'category=' . $categorySlug . '&' : ''; ?><?php echo $subcategorySlug ? 'subcategory=' . $subcategorySlug . '&' : ''; ?><?php echo $search ? 'search=' . urlencode($search) . '&' : ''; ?>sort=name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name</option>
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                <?php if ($products): ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <a href="/shop/product/<?php echo $product['slug']; ?>">
                                    <div class="product-image">
                                        <?php if ($product['image']): ?>
                                            <img src="/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <div class="product-placeholder">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($product['sale_price']): ?>
                                            <span class="sale-badge">Sale</span>
                                        <?php endif; ?>
                                        <?php if ($product['stock_quantity'] <= 0): ?>
                                            <span class="out-of-stock-badge">Out of Stock</span>
                                        <?php elseif ($product['stock_quantity'] <= $product['low_stock_threshold']): ?>
                                            <span class="low-stock-badge">Low Stock</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-info">
                                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                        <div class="product-price">
                                            <?php if ($product['sale_price']): ?>
                                                <span class="original-price"><?php echo formatPrice($product['price']); ?></span>
                                                <span class="sale-price"><?php echo formatPrice($product['sale_price']); ?></span>
                                            <?php else: ?>
                                                <span class="price"><?php echo formatPrice($product['price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <button class="btn btn-add-to-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <div class="pagination">
                            <?php if ($pagination['has_prev']): ?>
                                <a href="/shop/products?<?php echo $categorySlug ? 'category=' . $categorySlug . '&' : ''; ?><?php echo $subcategorySlug ? 'subcategory=' . $subcategorySlug . '&' : ''; ?><?php echo $search ? 'search=' . urlencode($search) . '&' : ''; ?>sort=<?php echo $sortBy; ?>&p=<?php echo $pagination['current_page'] - 1; ?>" class="page-link">&laquo; Previous</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                <a href="/shop/products?<?php echo $categorySlug ? 'category=' . $categorySlug . '&' : ''; ?><?php echo $subcategorySlug ? 'subcategory=' . $subcategorySlug . '&' : ''; ?><?php echo $search ? 'search=' . urlencode($search) . '&' : ''; ?>sort=<?php echo $sortBy; ?>&p=<?php echo $i; ?>" 
                                   class="page-link <?php echo $i === $pagination['current_page'] ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($pagination['has_next']): ?>
                                <a href="/shop/products?<?php echo $categorySlug ? 'category=' . $categorySlug . '&' : ''; ?><?php echo $subcategorySlug ? 'subcategory=' . $subcategorySlug . '&' : ''; ?><?php echo $search ? 'search=' . urlencode($search) . '&' : ''; ?>sort=<?php echo $sortBy; ?>&p=<?php echo $pagination['current_page'] + 1; ?>" class="page-link">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fas fa-box-open"></i>
                        <h3>No products found</h3>
                        <p>Try adjusting your filters or search terms</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
