<?php
$pageTitle = "Edit Product";
include __DIR__ . '/includes/header.php';

// Get product ID from URL
$product_id = $_GET['id'] ?? null;

if (!$product_id) {
    header('Location: /admin/products');
    exit;
}

// Get product details
$product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$product_id]);

if (!$product) {
    echo "Product not found";
    exit;
}

// Get categories for dropdown
$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$subcategories = $db->fetchAll("SELECT * FROM subcategories WHERE category_id = ? AND is_active = 1 ORDER BY name", [$product['category_id']]);
?>

<div class="admin-content">
    <div class="page-header">
        <h1><?php echo $pageTitle; ?></h1>
        <a href="/admin/products" class="btn btn-secondary">Back to Products</a>
    </div>

    <div class="form-container">
        <form method="POST" action="/admin/products/edit" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="slug">Slug *</label>
                    <input type="text" id="slug" name="slug" class="form-control" 
                           value="<?php echo htmlspecialchars($product['slug']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="sku">SKU *</label>
                    <input type="text" id="sku" name="sku" class="form-control" 
                           value="<?php echo htmlspecialchars($product['sku']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" class="form-control" required onchange="loadSubcategories(this.value)">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subcategory_id">Subcategory</label>
                    <select id="subcategory_id" name="subcategory_id" class="form-control">
                        <option value="">Select Subcategory</option>
                        <?php foreach ($subcategories as $subcategory): ?>
                            <option value="<?php echo $subcategory['id']; ?>"
                                    <?php echo $product['subcategory_id'] == $subcategory['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subcategory['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="price">Price *</label>
                    <input type="number" id="price" name="price" class="form-control" 
                           value="<?php echo $product['price']; ?>" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="compare_price">Compare Price</label>
                    <input type="number" id="compare_price" name="compare_price" class="form-control" 
                           value="<?php echo $product['sale_price'] ?? ''; ?>" step="0.01">
                </div>

                <div class="form-group">
                    <label for="cost_price">Cost Price</label>
                    <input type="number" id="cost_price" name="cost_price" class="form-control" 
                           value="<?php echo $product['cost_price']; ?>" step="0.01">
                </div>

                <div class="form-group">
                    <label for="stock">Stock Quantity *</label>
                    <input type="number" id="stock" name="stock" class="form-control" 
                           value="<?php echo $product['stock_quantity']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="weight">Weight (kg)</label>
                    <input type="number" id="weight" name="weight" class="form-control" 
                           value="<?php echo $product['actual_weight'] ?? ''; ?>" step="0.01">
                </div>

                <div class="form-group">
                    <label for="length">Length (cm)</label>
                    <input type="number" id="length" name="length" class="form-control" 
                           value="<?php echo $product['length']; ?>" step="0.01">
                </div>

                <div class="form-group">
                    <label for="width">Width (cm)</label>
                    <input type="number" id="width" name="width" class="form-control" 
                           value="<?php echo $product['width']; ?>" step="0.01">
                </div>

                <div class="form-group">
                    <label for="height">Height (cm)</label>
                    <input type="number" id="height" name="height" class="form-control" 
                           value="<?php echo $product['height']; ?>" step="0.01">
                </div>

                <div class="form-group full-width">
                    <label for="short_description">Short Description</label>
                    <textarea id="short_description" name="short_description" class="form-control" rows="3"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="6"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label>Current Product Images</label>
                    <?php 
                    $productImages = $db->fetchAll("SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order", [$product['id']]);
                    if ($productImages): 
                    ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-bottom: 15px;">
                            <?php foreach ($productImages as $img): ?>
                                <div style="position: relative;">
                                    <img src="/<?php echo htmlspecialchars($img['image_path']); ?>" alt="" 
                                         style="width: 100%; height: 150px; object-fit: cover; border-radius: 5px;">
                                    <?php if ($img['is_primary']): ?>
                                        <span style="position: absolute; top: 5px; left: 5px; background: #3498db; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px;">Primary</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <label for="images">Add More Images</label>
                    <input type="file" id="images" name="images[]" class="form-control" accept="image/*" multiple>
                    <small>Select multiple images to add. First new image will be set as primary if no images exist.</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_featured" value="1" 
                               <?php echo $product['is_featured'] ? 'checked' : ''; ?>>
                        Featured Product
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" 
                               <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                        Active
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Product</button>
                <a href="/admin/products" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function loadSubcategories(categoryId) {
    const subcategorySelect = document.getElementById('subcategory_id');
    subcategorySelect.innerHTML = '<option value="">Loading...</option>';
    
    if (!categoryId) {
        subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
        return;
    }
    
    fetch(`/admin/get-subcategories?category_id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            data.forEach(subcat => {
                const option = document.createElement('option');
                option.value = subcat.id;
                option.textContent = subcat.name;
                subcategorySelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading subcategories:', error);
            subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
        });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
