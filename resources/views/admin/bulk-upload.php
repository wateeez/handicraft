<?php
$pageTitle = "Bulk Upload Products";
include __DIR__ . '/includes/header.php';

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $errors = [];
    $successCount = 0;
    $errorRows = [];
    
    if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $filePath = $_FILES['csv_file']['tmp_name'];
        
        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            // Read header row
            $header = fgetcsv($handle);
            
            // Expected columns
            $expectedColumns = [
                'name', 'sku', 'category_slug', 'subcategory_slug', 'description', 'short_description',
                'price', 'sale_price', 'cost_price', 'stock_quantity', 'low_stock_threshold',
                'length', 'width', 'height', 'actual_weight', 'is_active', 'is_featured'
            ];
            
            $row = 1;
            while (($data = fgetcsv($handle)) !== FALSE) {
                $row++;
                
                try {
                    // Map data to columns
                    $productData = array_combine($header, $data);
                    
                    // Get category ID
                    $category = $db->fetchOne(
                        "SELECT id FROM categories WHERE slug = ?",
                        [$productData['category_slug']]
                    );
                    
                    if (!$category) {
                        throw new Exception("Category not found: " . $productData['category_slug']);
                    }
                    
                    $categoryId = $category['id'];
                    $subcategoryId = null;
                    
                    // Get subcategory ID if provided
                    if (!empty($productData['subcategory_slug'])) {
                        $subcategory = $db->fetchOne(
                            "SELECT id FROM subcategories WHERE slug = ? AND category_id = ?",
                            [$productData['subcategory_slug'], $categoryId]
                        );
                        
                        if ($subcategory) {
                            $subcategoryId = $subcategory['id'];
                        }
                    }
                    
                    // Generate slug
                    $slug = generateSlug($productData['name']);
                    
                    // Calculate shipping dimensions
                    $length = floatval($productData['length'] ?? 0);
                    $width = floatval($productData['width'] ?? 0);
                    $height = floatval($productData['height'] ?? 0);
                    
                    $shippingLength = $length + PACKAGING_BUFFER;
                    $shippingWidth = $width + PACKAGING_BUFFER;
                    $shippingHeight = $height + PACKAGING_BUFFER;
                    
                    // Check if product exists
                    $existing = $db->fetchOne(
                        "SELECT id FROM products WHERE sku = ?",
                        [$productData['sku']]
                    );
                    
                    if ($existing) {
                        // Update existing product
                        $db->execute(
                            "UPDATE products SET 
                                category_id = ?, subcategory_id = ?, name = ?, slug = ?,
                                description = ?, short_description = ?, price = ?, sale_price = ?,
                                cost_price = ?, stock_quantity = ?, low_stock_threshold = ?,
                                length = ?, width = ?, height = ?,
                                shipping_length = ?, shipping_width = ?, shipping_height = ?,
                                actual_weight = ?, is_active = ?, is_featured = ?
                             WHERE sku = ?",
                            [
                                $categoryId, $subcategoryId, $productData['name'], $slug,
                                $productData['description'], $productData['short_description'],
                                $productData['price'], $productData['sale_price'] ?: null,
                                $productData['cost_price'] ?: null,
                                $productData['stock_quantity'], $productData['low_stock_threshold'],
                                $length, $width, $height,
                                $shippingLength, $shippingWidth, $shippingHeight,
                                $productData['actual_weight'],
                                $productData['is_active'] == '1' ? 1 : 0,
                                $productData['is_featured'] == '1' ? 1 : 0,
                                $productData['sku']
                            ]
                        );
                    } else {
                        // Insert new product
                        $db->execute(
                            "INSERT INTO products (
                                category_id, subcategory_id, name, slug, sku,
                                description, short_description, price, sale_price, cost_price,
                                stock_quantity, low_stock_threshold,
                                length, width, height, shipping_length, shipping_width, shipping_height,
                                actual_weight, is_active, is_featured
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [
                                $categoryId, $subcategoryId, $productData['name'], $slug, $productData['sku'],
                                $productData['description'], $productData['short_description'],
                                $productData['price'], $productData['sale_price'] ?: null,
                                $productData['cost_price'] ?: null,
                                $productData['stock_quantity'], $productData['low_stock_threshold'],
                                $length, $width, $height,
                                $shippingLength, $shippingWidth, $shippingHeight,
                                $productData['actual_weight'],
                                $productData['is_active'] == '1' ? 1 : 0,
                                $productData['is_featured'] == '1' ? 1 : 0
                            ]
                        );
                    }
                    
                    $successCount++;
                    
                } catch (Exception $e) {
                    $errorRows[] = "Row $row: " . $e->getMessage();
                }
            }
            
            fclose($handle);
            
            if ($successCount > 0) {
                Session::setFlash('success', "$successCount products imported successfully");
            }
            
            if (!empty($errorRows)) {
                Session::setFlash('error', "Errors encountered:<br>" . implode('<br>', $errorRows));
            }
        }
    }
}
?>

<div class="bulk-upload">
    <div class="upload-instructions">
        <h2><i class="fas fa-info-circle"></i> Bulk Upload Instructions</h2>
        <ol>
            <li>Download the sample CSV template below</li>
            <li>Fill in your product data following the template format</li>
            <li>Upload the completed CSV file</li>
            <li>Products with existing SKUs will be updated, new SKUs will be inserted</li>
        </ol>
        
        <div class="template-download">
            <a href="/admin/download-template.php" class="btn btn-info">
                <i class="fas fa-download"></i> Download CSV Template
            </a>
        </div>
    </div>
    
    <div class="upload-form-section">
        <h2>Upload CSV File</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Select CSV File</label>
                <input type="file" name="csv_file" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-primary btn-large">
                <i class="fas fa-upload"></i> Upload and Import
            </button>
        </form>
    </div>
    
    <div class="csv-format-info">
        <h3>CSV Format Requirements</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Column Name</th>
                        <th>Required</th>
                        <th>Description</th>
                        <th>Example</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>name</td>
                        <td>Yes</td>
                        <td>Product name</td>
                        <td>Premium Wireless Mouse</td>
                    </tr>
                    <tr>
                        <td>sku</td>
                        <td>Yes</td>
                        <td>Unique product SKU</td>
                        <td>MOUSE-001</td>
                    </tr>
                    <tr>
                        <td>category_slug</td>
                        <td>Yes</td>
                        <td>Category slug (URL-friendly name)</td>
                        <td>electronics</td>
                    </tr>
                    <tr>
                        <td>subcategory_slug</td>
                        <td>No</td>
                        <td>Subcategory slug</td>
                        <td>computer-accessories</td>
                    </tr>
                    <tr>
                        <td>price</td>
                        <td>Yes</td>
                        <td>Regular price</td>
                        <td>29.99</td>
                    </tr>
                    <tr>
                        <td>sale_price</td>
                        <td>No</td>
                        <td>Sale price (if on sale)</td>
                        <td>24.99</td>
                    </tr>
                    <tr>
                        <td>stock_quantity</td>
                        <td>Yes</td>
                        <td>Available stock</td>
                        <td>100</td>
                    </tr>
                    <tr>
                        <td>length, width, height</td>
                        <td>No</td>
                        <td>Dimensions in cm</td>
                        <td>10.5</td>
                    </tr>
                    <tr>
                        <td>actual_weight</td>
                        <td>No</td>
                        <td>Weight in kg</td>
                        <td>0.5</td>
                    </tr>
                    <tr>
                        <td>is_active</td>
                        <td>No</td>
                        <td>1 for active, 0 for inactive</td>
                        <td>1</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
