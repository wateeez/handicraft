<?php
$pageTitle = "Products";
include __DIR__ . '/includes/header.php';

// Get search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;

// Build query
$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($categoryFilter) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryFilter;
}

$whereClause = implode(' AND ', $where);

// Get total count
$countSql = "SELECT COUNT(*) as total FROM products p WHERE $whereClause";
$totalResult = $db->fetchOne($countSql, $params);
$total = $totalResult['total'];

$pagination = paginate($total, ADMIN_ITEMS_PER_PAGE, $currentPage);

// Get products
$sql = "SELECT p.*, c.name as category_name,
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE $whereClause
        ORDER BY p.created_at DESC
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";

$products = $db->fetchAll($sql, $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name ASC");
?>

<div class="products-admin">
    <div class="page-actions">
        <div class="search-filter">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>
        <div class="action-buttons">
            <a href="/admin/products/add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Product
            </a>
            <a href="/admin/products/bulk-upload" class="btn btn-success">
                <i class="fas fa-upload"></i> Bulk Upload
            </a>
            <a href="/admin/products/export" class="btn btn-info">
                <i class="fas fa-download"></i> Export Products
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($products): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if ($product['image']): ?>
                                    <img src="/uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="" class="table-image">
                                <?php else: ?>
                                    <div class="image-placeholder-small">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['sku']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td>
                                <?php if ($product['sale_price']): ?>
                                    <del><?php echo formatPrice($product['price']); ?></del><br>
                                    <strong><?php echo formatPrice($product['sale_price']); ?></strong>
                                <?php else: ?>
                                    <?php echo formatPrice($product['price']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['stock_quantity'] <= 0): ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                <?php elseif ($product['stock_quantity'] <= $product['low_stock_threshold']): ?>
                                    <span class="badge badge-warning"><?php echo $product['stock_quantity']; ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?php echo $product['stock_quantity']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="/admin/products/edit/<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form method="POST" action="/admin/products/delete" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No products found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="pagination">
            <?php if ($pagination['has_prev']): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $pagination['current_page'] - 1])); ?>" class="page-link">&laquo; Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $i])); ?>" 
                   class="page-link <?php echo $i === $pagination['current_page'] ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($pagination['has_next']): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $pagination['current_page'] + 1])); ?>" class="page-link">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
