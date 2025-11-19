<?php
$pageTitle = "Add Product";
include __DIR__ . '/includes/header.php';

// Get categories and subcategories
$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Get form data
    $categoryId = intval($_POST['category_id']);
    $subcategoryId = !empty($_POST['subcategory_id']) ? intval($_POST['subcategory_id']) : null;
    $name = sanitize($_POST['name']);
    $slug = generateSlug($_POST['slug'] ?: $name);
    $sku = sanitize($_POST['sku']);
    $description = sanitize($_POST['description']);
    $shortDescription = sanitize($_POST['short_description']);
    $price = floatval($_POST['price']);
    $salePrice = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
    $costPrice = !empty($_POST['cost_price']) ? floatval($_POST['cost_price']) : null;
    $stockQuantity = intval($_POST['stock_quantity']);
    $lowStockThreshold = intval($_POST['low_stock_threshold']);
    
    // Dimensions (for display)
    $length = floatval($_POST['length']);
    $width = floatval($_POST['width']);
    $height = floatval($_POST['height']);
    
    // Calculate shipping dimensions (add 8cm to each)
    $shippingLength = $length + PACKAGING_BUFFER;
    $shippingWidth = $width + PACKAGING_BUFFER;
    $shippingHeight = $height + PACKAGING_BUFFER;
    
    $actualWeight = floatval($_POST['actual_weight']);
    
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($sku)) $errors[] = "SKU is required";
    if ($price <= 0) $errors[] = "Valid price is required";
    if ($categoryId <= 0) $errors[] = "Category is required";
    
    // Check if SKU or slug already exists
    $existing = $db->fetchOne("SELECT id FROM products WHERE sku = ? OR slug = ?", [$sku, $slug]);
    if ($existing) {
        $errors[] = "SKU or Slug already exists";
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Insert product
            $db->execute(
                "INSERT INTO products (
                    category_id, subcategory_id, name, slug, sku, description, short_description,
                    price, sale_price, cost_price, stock_quantity, low_stock_threshold,
                    length, width, height, shipping_length, shipping_width, shipping_height,
                    actual_weight, is_active, is_featured
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $categoryId, $subcategoryId, $name, $slug, $sku, $description, $shortDescription,
                    $price, $salePrice, $costPrice, $stockQuantity, $lowStockThreshold,
                    $length, $width, $height, $shippingLength, $shippingWidth, $shippingHeight,
                    $actualWeight, $isActive, $isFeatured
                ]
            );
            
            $productId = $db->lastInsertId();
            
            // Handle image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $uploadedImages = [];
                foreach ($_FILES['images']['name'] as $key => $filename) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['images']['name'][$key],
                            'type' => $_FILES['images']['type'][$key],
                            'tmp_name' => $_FILES['images']['tmp_name'][$key],
                            'error' => $_FILES['images']['error'][$key],
                            'size' => $_FILES['images']['size'][$key]
                        ];
                        
                        $upload = uploadImage($file, 'products');
                        if ($upload['success']) {
                            $uploadedImages[] = $upload['filename'];
                        }
                    }
                }
                
                // Insert product images
                foreach ($uploadedImages as $index => $imagePath) {
                    $isPrimary = ($index === 0) ? 1 : 0;
                    $db->execute(
                        "INSERT INTO product_images (product_id, image_path, is_primary, display_order) VALUES (?, ?, ?, ?)",
                        [$productId, $imagePath, $isPrimary, $index]
                    );
                }
            }
            
            $db->commit();
            Session::setFlash('success', 'Product added successfully');
            redirect('/admin/products.php');
            
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = "Failed to add product: " . $e->getMessage();
        }
    }
    
    if ($errors) {
        Session::setFlash('error', implode('<br>', $errors));
    }
}
?>

<div class="product-form">
    <form method="POST" action="/admin/products/add" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="form-grid">
            <!-- Basic Information -->
            <div class="form-section">
                <h2>Basic Information</h2>
                
                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" required value="<?php echo $_POST['name'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Slug (URL-friendly name)</label>
                    <input type="text" name="slug" value="<?php echo $_POST['slug'] ?? ''; ?>">
                    <small>Leave empty to auto-generate from name</small>
                </div>
                
                <div class="form-group">
                    <label>SKU (Stock Keeping Unit) *</label>
                    <input type="text" name="sku" required value="<?php echo $_POST['sku'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" id="categorySelect" required onchange="loadSubcategories(this.value)">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Subcategory</label>
                    <select name="subcategory_id" id="subcategorySelect">
                        <option value="">Select Subcategory</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Short Description</label>
                    <textarea name="short_description" rows="3"><?php echo $_POST['short_description'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Full Description</label>
                    <textarea name="description" rows="6"><?php echo $_POST['description'] ?? ''; ?></textarea>
                </div>
            </div>
            
            <!-- Pricing & Stock -->
            <div class="form-section">
                <h2>Pricing & Stock</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Regular Price *</label>
                        <input type="number" name="price" step="0.01" min="0" required value="<?php echo $_POST['price'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Sale Price</label>
                        <input type="number" name="sale_price" step="0.01" min="0" value="<?php echo $_POST['sale_price'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Cost Price</label>
                    <input type="number" name="cost_price" step="0.01" min="0" value="<?php echo $_POST['cost_price'] ?? ''; ?>">
                    <small>For internal use only</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock_quantity" min="0" required value="<?php echo $_POST['stock_quantity'] ?? 0; ?>">
                    </div>
                    <div class="form-group">
                        <label>Low Stock Threshold</label>
                        <input type="number" name="low_stock_threshold" min="0" value="<?php echo $_POST['low_stock_threshold'] ?? 10; ?>">
                    </div>
                </div>
            </div>
            
            <!-- Shipping Dimensions & Weight -->
            <div class="form-section">
                <h2>Dimensions & Weight</h2>
                <p class="info-note"><i class="fas fa-info-circle"></i> Product dimensions for customer display. <?php echo PACKAGING_BUFFER; ?>cm will be automatically added to each dimension for shipping calculations.</p>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Length (cm)</label>
                        <input type="number" name="length" step="0.01" min="0" value="<?php echo $_POST['length'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Width (cm)</label>
                        <input type="number" name="width" step="0.01" min="0" value="<?php echo $_POST['width'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Height (cm)</label>
                        <input type="number" name="height" step="0.01" min="0" value="<?php echo $_POST['height'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Actual Weight (kg)</label>
                    <input type="number" name="actual_weight" step="0.01" min="0" value="<?php echo $_POST['actual_weight'] ?? ''; ?>">
                </div>
                
                <div class="shipping-calc-preview" id="shippingPreview">
                    <!-- JavaScript will update this -->
                </div>
            </div>
            
            <!-- Product Images -->
            <div class="form-section">
                <h2>Product Images *</h2>
                <div class="form-group">
                    <label>Upload Images (Required - Select at least 1 image)</label>
                    <input type="file" name="images[]" multiple accept="image/*" required onchange="previewImages(this)">
                    <small>You can upload multiple images. First image will be set as primary. Accepted formats: JPG, PNG, GIF, WEBP</small>
                </div>
                <div id="imagePreview" class="image-preview-grid"></div>
            </div>
            
            <!-- Options -->
            <div class="form-section">
                <h2>Options</h2>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Active (visible to customers)
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_featured" value="1">
                        Featured Product
                    </label>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-large">
                <i class="fas fa-save"></i> Add Product
            </button>
            <a href="/admin/products.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
function loadSubcategories(categoryId) {
    const subcategorySelect = document.getElementById('subcategorySelect');
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

function previewImages(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'image-preview-item';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    ${index === 0 ? '<span class="primary-badge">Primary</span>' : ''}
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
