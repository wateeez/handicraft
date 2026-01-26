<?php
$pageTitle = "Bulk Upload Products";
include __DIR__ . '/includes/header.php';
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
            <a href="/admin/download-template" class="btn btn-info">
                <i class="fas fa-download"></i> Download CSV Template
            </a>
        </div>
    </div>
    
    <div class="upload-form-section">
        <h2>Upload CSV File</h2>
        <form method="POST" action="/admin/products/bulk" enctype="multipart/form-data">
            <?php if(function_exists('csrf_field')) { echo csrf_field(); } else { ?>
            <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
            <?php } ?>
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
                        <td>Handmade Vase</td>
                    </tr>
                    <tr>
                        <td>sku</td>
                        <td>Yes</td>
                        <td>Unique product SKU</td>
                        <td>VASE-001</td>
                    </tr>
                    <tr>
                        <td>category_slug</td>
                        <td>Yes</td>
                        <td>Category slug (URL-friendly name)</td>
                        <td>home-decor</td>
                    </tr>
                    <tr>
                        <td>subcategory_slug</td>
                        <td>No</td>
                        <td>Subcategory slug</td>
                        <td>vases</td>
                    </tr>
                    <tr>
                        <td>price</td>
                        <td>Yes</td>
                        <td>Regular price</td>
                        <td>49.99</td>
                    </tr>
                    <tr>
                        <td>sale_price</td>
                        <td>No</td>
                        <td>Sale price (if on sale)</td>
                        <td>39.99</td>
                    </tr>
                    <tr>
                        <td>subject_to_availability</td>
                        <td>No</td>
                        <td>0 = Available, 1 = Subject to Availability</td>
                        <td>0</td>
                    </tr>
                    <tr>
                        <td>length, width, height</td>
                        <td>No</td>
                        <td>Dimensions in cm</td>
                        <td>15.5</td>
                    </tr>
                    <tr>
                        <td>actual_weight</td>
                        <td>No</td>
                        <td>Weight in kg</td>
                        <td>1.2</td>
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
