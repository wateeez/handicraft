<?php
$pageTitle = "Categories & Subcategories";
include __DIR__ . '/includes/header.php';

// Handle category operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_category') {
        $name = sanitize($_POST['name']);
        $slug = generateSlug($_POST['slug'] ?: $name);
        $description = sanitize($_POST['description'] ?? '');
        $displayOrder = intval($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Handle image upload
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $upload = uploadImage($_FILES['image'], 'categories');
            if ($upload['success']) {
                $imagePath = $upload['filename'];
            }
        }
        
        $db->execute(
            "INSERT INTO categories (name, slug, description, image, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)",
            [$name, $slug, $description, $imagePath, $displayOrder, $isActive]
        );
        
        Session::setFlash('success', 'Category added successfully');
        redirect('/admin/categories.php');
    }
    
    elseif ($action === 'add_subcategory') {
        $categoryId = intval($_POST['category_id']);
        $name = sanitize($_POST['name']);
        $slug = generateSlug($_POST['slug'] ?: $name);
        $description = sanitize($_POST['description'] ?? '');
        $displayOrder = intval($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $db->execute(
            "INSERT INTO subcategories (category_id, name, slug, description, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)",
            [$categoryId, $name, $slug, $description, $displayOrder, $isActive]
        );
        
        Session::setFlash('success', 'Subcategory added successfully');
        redirect('/admin/categories.php');
    }
    
    elseif ($action === 'delete_category') {
        $id = intval($_POST['id']);
        $db->execute("DELETE FROM categories WHERE id = ?", [$id]);
        Session::setFlash('success', 'Category deleted');
        redirect('/admin/categories.php');
    }
    
    elseif ($action === 'delete_subcategory') {
        $id = intval($_POST['id']);
        $db->execute("DELETE FROM subcategories WHERE id = ?", [$id]);
        Session::setFlash('success', 'Subcategory deleted');
        redirect('/admin/categories.php');
    }
}

// Get all categories
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

<div class="categories-admin">
    <div class="page-actions">
        <button class="btn btn-primary" onclick="showModal('addCategoryModal')">
            <i class="fas fa-plus"></i> Add Category
        </button>
        <button class="btn btn-success" onclick="showModal('addSubcategoryModal')">
            <i class="fas fa-plus"></i> Add Subcategory
        </button>
    </div>

    <!-- Categories List -->
    <div class="categories-list">
        <?php foreach ($categories as $category): ?>
            <div class="category-item">
                <div class="category-header">
                    <div class="category-info">
                        <?php if ($category['image']): ?>
                            <img src="/uploads/<?php echo htmlspecialchars($category['image']); ?>" alt="" class="category-thumbnail">
                        <?php endif; ?>
                        <div>
                            <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                            <span class="slug">Slug: <?php echo htmlspecialchars($category['slug']); ?></span>
                        </div>
                    </div>
                    <div class="category-actions">
                        <span class="badge badge-<?php echo $category['is_active'] ? 'success' : 'secondary'; ?>">
                            <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                        <span class="order-badge">Order: <?php echo $category['display_order']; ?></span>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_category">
                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category? All subcategories and products will be affected.')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Subcategories -->
                <?php if (!empty($subcategories[$category['id']])): ?>
                    <div class="subcategories-list">
                        <h4>Subcategories:</h4>
                        <div class="subcategory-items">
                            <?php foreach ($subcategories[$category['id']] as $subcategory): ?>
                                <div class="subcategory-item">
                                    <span class="subcategory-name"><?php echo htmlspecialchars($subcategory['name']); ?></span>
                                    <span class="badge badge-<?php echo $subcategory['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $subcategory['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_subcategory">
                                        <input type="hidden" name="id" value="<?php echo $subcategory['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this subcategory?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
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

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
