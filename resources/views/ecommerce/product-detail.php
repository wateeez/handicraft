<?php
// Get product slug from route parameter
$productSlug = $slug ?? '';

if (!$productSlug) {
    header('Location: /shop/products');
    exit;
}

// Get product details
$product = $db->fetchOne(
    "SELECT p.*, c.name as category_name, sc.name as subcategory_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
     WHERE p.slug = ? AND p.is_active = 1",
    [$productSlug]
);

if (!$product) {
    header('Location: /shop/products');
    exit;
}

$pageTitle = $product['name'];

// Get product images
$images = $db->fetchAll(
    "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC",
    [$product['id']]
);

// Get related products
$relatedProducts = $db->fetchAll(
    "SELECT p.*, 
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
     FROM products p
     WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
     ORDER BY RAND()
     LIMIT 4",
    [$product['category_id'], $product['id']]
);

// Get available shipping methods with costs
$shippingMethods = $db->fetchAll("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY display_order ASC");
$shippingCosts = [];
foreach ($shippingMethods as $method) {
    $shippingCosts[$method['id']] = calculateShippingCost($product['id'], $method['id']);
}
?>

<div class="product-detail-page">
    <div class="container">
        <div class="breadcrumb">
            <a href="/shop">Home</a> / 
            <a href="/shop/products">Products</a> / 
            <a href="/shop/products?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a> / 
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <div class="product-detail-content">
            <!-- Product Images -->
            <div class="product-images">
                <div class="main-image">
                    <?php if ($images): ?>
                        <img id="mainProductImage" src="/<?php echo htmlspecialchars($images[0]['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <div class="image-placeholder">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (count($images) > 1): ?>
                    <div class="thumbnail-images">
                        <?php foreach ($images as $index => $image): ?>
                            <img src="/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 onclick="changeMainImage(this.src)"
                                 class="<?php echo $index === 0 ? 'active' : ''; ?>">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="product-info-detail">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="product-sku">SKU: <?php echo htmlspecialchars($product['sku']); ?></p>

                <div class="product-price-detail">
                    <?php if ($product['sale_price']): ?>
                        <span class="original-price"><?php echo formatPrice($product['price']); ?></span>
                        <span class="sale-price"><?php echo formatPrice($product['sale_price']); ?></span>
                        <span class="discount-percent">Save <?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%</span>
                    <?php else: ?>
                        <span class="price"><?php echo formatPrice($product['price']); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($product['short_description']): ?>
                    <p class="short-description"><?php echo nl2br(htmlspecialchars($product['short_description'])); ?></p>
                <?php endif; ?>

                <!-- Stock Status -->
                <div class="stock-status">
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <span class="in-stock">
                            <i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock_quantity']; ?> available)
                        </span>
                    <?php else: ?>
                        <span class="out-of-stock">
                            <i class="fas fa-times-circle"></i> Out of Stock
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Product Dimensions (for display only) -->
                <?php if ($product['length'] && $product['width'] && $product['height']): ?>
                    <div class="product-specs">
                        <h3>Specifications</h3>
                        <ul>
                            <li><strong>Dimensions:</strong> <?php echo $product['length']; ?> x <?php echo $product['width']; ?> x <?php echo $product['height']; ?> cm</li>
                            <?php if ($product['actual_weight']): ?>
                                <li><strong>Weight:</strong> <?php echo $product['actual_weight']; ?> kg</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Shipping Information -->
                <div class="shipping-info">
                    <h3>Shipping Options</h3>
                    <div class="shipping-methods">
                        <?php foreach ($shippingMethods as $method): ?>
                            <div class="shipping-method">
                                <strong><?php echo htmlspecialchars($method['name']); ?></strong>
                                <span><?php echo formatPrice($shippingCosts[$method['id']]); ?></span>
                                <?php if ($method['estimated_days']): ?>
                                    <span class="delivery-time">(<?php echo htmlspecialchars($method['estimated_days']); ?>)</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Add to Cart -->
                <?php if ($product['stock_quantity'] > 0): ?>
                    <div class="product-actions">
                        <div class="quantity-selector">
                            <label>Quantity:</label>
                            <input type="number" id="productQuantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                        </div>
                        <button class="btn btn-primary btn-large" onclick="addToCartWithQuantity(<?php echo $product['id']; ?>)">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Share -->
                <div class="product-share">
                    <span>Share:</span>
                    <a href="#" class="share-btn"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="share-btn"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="share-btn"><i class="fab fa-pinterest"></i></a>
                    <a href="#" class="share-btn"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>

        <!-- Product Description -->
        <div class="product-description-section">
            <h2>Product Description</h2>
            <div class="description-content">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>
        </div>

        <!-- Related Products -->
        <?php if ($relatedProducts): ?>
            <div class="related-products">
                <h2>Related Products</h2>
                <div class="products-grid">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <div class="product-card">
                            <a href="/shop/product/<?php echo $relatedProduct['slug']; ?>">
                                <div class="product-image">
                                    <?php if ($relatedProduct['image']): ?>
                                        <img src="/uploads/<?php echo htmlspecialchars($relatedProduct['image']); ?>" alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>">
                                    <?php else: ?>
                                        <div class="product-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($relatedProduct['name']); ?></h3>
                                    <div class="product-price">
                                        <?php if ($relatedProduct['sale_price']): ?>
                                            <span class="original-price"><?php echo formatPrice($relatedProduct['price']); ?></span>
                                            <span class="sale-price"><?php echo formatPrice($relatedProduct['sale_price']); ?></span>
                                        <?php else: ?>
                                            <span class="price"><?php echo formatPrice($relatedProduct['price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function changeMainImage(src) {
    document.getElementById('mainProductImage').src = src;
    const thumbnails = document.querySelectorAll('.thumbnail-images img');
    thumbnails.forEach(img => img.classList.remove('active'));
    event.target.classList.add('active');
}

function addToCartWithQuantity(productId) {
    const quantity = document.getElementById('productQuantity').value;
    addToCart(productId, quantity);
}
</script>
