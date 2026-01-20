<?php
$pageTitle = "Categories & Subcategories";
include __DIR__ . '/includes/header.php';

// Get all categories from DB injected by controller
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY display_order ASC, name ASC");

// Get all subcategories grouped by category
$subcategories = [];
foreach ($categories as $category) {
    $subcategories[$category['id']] = $db->fetchAll(
        "SELECT * FROM subcategories WHERE category_id = ? ORDER BY display_order ASC, name ASC",
        [$category['id']]
    );
}
?>

<div class="admin-content">
    <div class="page-header">
        <h1><i class="fas fa-tags"></i> Categories Management</h1>
        <div class="page-actions">
            <button class="btn btn-primary" onclick="showModal('addCategoryModal')">
                <i class="fas fa-plus"></i> Add Category
            </button>
            <button class="btn btn-success" onclick="showModal('addSubcategoryModal')">
                <i class="fas fa-plus"></i> Add Subcategory
            </button>
        </div>
    </div>

    <!-- Categories List -->
    <div class="dashboard-section">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Image</th>
                        <th>Name & Slug</th>
                        <th>Status</th>
                        <th>Order</th>
                        <th>Subcategories</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No categories found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <?php if ($category['image']): ?>
                                        <div class="table-image"
                                            style="background-image: url('/<?php echo htmlspecialchars($category['image']); ?>');">
                                        </div>
                                    <?php else: ?>
                                        <div class="image-placeholder-small"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($category['name']); ?></div>
                                    <div style="font-size:0.8rem; color:var(--color-text-sub);">
                                        /<?php echo htmlspecialchars($category['slug']); ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $category['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo $category['display_order']; ?></span>
                                </td>
                                <td>
                                    <div style="display:flex; flex-wrap:wrap; gap:0.3rem;">
                                        <?php if (!empty($subcategories[$category['id']])): ?>
                                            <?php foreach ($subcategories[$category['id']] as $subcategory): ?>
                                                <div class="subcategory-tag"
                                                    style="display:flex; align-items:center; background:var(--color-bg-alt); padding:2px 8px; border-radius:12px; font-size:0.75rem; border:1px solid var(--color-border);">
                                                    <span><?php echo htmlspecialchars($subcategory['name']); ?></span>
                                                    <form method="POST" action="/admin/subcategories/delete"
                                                        style="display: inline; margin-left:5px;">
                                                        <?php echo csrf_field(); ?>
                                                        <input type="hidden" name="id" value="<?php echo $subcategory['id']; ?>">
                                                        <button type="submit"
                                                            style="background:none; border:none; color:var(--color-sale); padding:0; cursor:pointer;"
                                                            onclick="return confirm('Delete this subcategory?')">
                                                            <i class="fas fa-times-circle"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span
                                                style="font-style:italic; font-size:0.8rem; color:var(--color-text-sub);">None</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align:right;">
                                    <form method="POST" action="/admin/categories/delete" style="display: inline;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this category? This will affect products in this category.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addCategoryModal')">&times;</span>
        <h2>Add New Category</h2>
        <form method="POST" action="/admin/categories" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>Category Name *</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Slug (URL-friendly)</label>
                <input type="text" name="slug">
                <small>Leave empty to auto-generate</small>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Category Image</label>
                <input type="file" name="image" accept="image/*">
            </div>
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="0" min="0">
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" checked>
                    Active
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Add Category</button>
        </form>
    </div>
</div>

<!-- Add Subcategory Modal -->
<div id="addSubcategoryModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addSubcategoryModal')">&times;</span>
        <h2>Add New Subcategory</h2>
        <form method="POST" action="/admin/subcategories">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>Parent Category *</label>
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Subcategory Name *</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Slug</label>
                <input type="text" name="slug">
                <small>Leave empty to auto-generate</small>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="0" min="0">
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" checked>
                    Active
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Add Subcategory</button>
        </form>
    </div>
</div>

<script>
    function showModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    window.onclick = function (event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>