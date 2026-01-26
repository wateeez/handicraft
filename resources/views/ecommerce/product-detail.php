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
                <?php if ($images && count($images) > 0): ?>

                <div id="mainCarousel" class="main-carousel magnifier-area" tabindex="0">
                    <?php foreach ($images as $index => $image): ?>
                        <div class="slide" data-index="<?php echo $index; ?>">
                            <img class="slide-image" data-index="<?php echo $index; ?>" src="/<?php echo htmlspecialchars($image['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                    <?php endforeach; ?>
                        <!-- magnifier overlay (50% box, 100% zoom, not overlapping main image) -->
                        <div id="magnifier" class="magnifier" aria-hidden="true" style="display: none;"></div>
                </div>

                <!-- thumbnails below main carousel -->
                <div class="thumbnail-images" tabindex="0">
                    <?php foreach ($images as $index => $image): ?>
                        <img src="/<?php echo htmlspecialchars($image['image_path']); ?>"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             data-index="<?php echo $index; ?>"
                             onclick="scrollToSlide(<?php echo $index; ?>, this)"
                             class="<?php echo $index === 0 ? 'active' : ''; ?>">
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <div class="main-carousel">
                        <div class="slide"><div class="image-placeholder"><i class="fas fa-image"></i></div></div>
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

                <!-- Availability Status -->
                <div class="stock-status">
                    <?php if ($product['subject_to_availability'] ?? 0): ?>
                        <span class="out-of-stock" style="background: #ff9800; color: white;">
                            <i class="fas fa-info-circle"></i> Subject to Availability - Contact us for purchase
                        </span>
                    <?php else: ?>
                        <span class="in-stock">
                            <i class="fas fa-check-circle"></i> Available for Purchase
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Product Dimensions (actual, not shipping) -->
                <?php if ($product['length'] && $product['width'] && $product['height']): ?>
                    <div class="product-specs">
                        <h3>Specifications</h3>
                        <ul>
                            <li><strong>Dimensions:</strong> <?php echo floatval($product['length']); ?> x <?php echo floatval($product['width']); ?> x <?php echo floatval($product['height']); ?> cm</li>
                            <?php if ($product['actual_weight']): ?>
                                <li><strong>Weight:</strong> <?php echo $product['actual_weight']; ?> kg</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Shipping Information -->
                <?php if (!($product['subject_to_availability'] ?? 0)): ?>
                <div class="shipping-info">
                    <h3>Shipping Options</h3>
                    <div class="shipping-methods">
                        <?php
                        $selectedShipping = isset($_SESSION['cart_shipping_method']) ? $_SESSION['cart_shipping_method'] : ($shippingMethods[0]['id'] ?? null);
                        ?>
                        <select name="shipping_method" id="shippingMethodSelect" style="width:100%;padding:0.5rem 1rem;margin-bottom:1rem;">
                            <?php foreach ($shippingMethods as $method): ?>
                                <option value="<?php echo $method['id']; ?>" <?php echo $selectedShipping == $method['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($method['name']); ?> <?php echo formatPrice($shippingCosts[$method['id']]); ?><?php if ($method['estimated_days']): ?> (<?php echo htmlspecialchars($method['estimated_days']); ?>)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Add to Cart -->
                <?php if (!($product['subject_to_availability'] ?? 0)): ?>
                    <div class="product-actions">
                        <div class="quantity-selector">
                            <label>Quantity:</label>
                            <input type="number" id="productQuantity" value="1" min="1">
                        </div>
                        <button class="btn btn-primary btn-large" onclick="addToCartWithQuantityAndShipping(<?php echo $product['id']; ?>)">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </div>
                <?php else: ?>
                    <div class="product-actions">
                        <div style="padding: 1.5rem; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ff9800;">
                            <p style="color: #856404; margin-bottom: 1rem; font-size: 1rem;">
                                <i class="fas fa-info-circle"></i> <strong>This product is subject to availability.</strong><br>
                                Please contact us to inquire about purchasing this item.
                            </p>
                            <a href="/shop/contact" class="btn btn-warning btn-large" style="background: #ff9800; border-color: #ff9800;">
                                <i class="fas fa-phone"></i> Contact Us
                            </a>
                        </div>
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
                                        <img src="/<?php echo htmlspecialchars($relatedProduct['image']); ?>" alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>">
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
function changeMainImage(src, el) {
    const main = document.getElementById('mainProductImage');
    if (!main) return;
    main.src = src;
    const thumbnails = document.querySelectorAll('.thumbnail-images img');
    thumbnails.forEach(img => img.classList.remove('active'));
    if (el) el.classList.add('active');
    // no zoom window - thumbnails update main image only
}

function addToCartWithQuantityAndShipping(productId) {
    const quantity = document.getElementById('productQuantity').value;
    const shippingMethod = document.getElementById('shippingMethodSelect') ? document.getElementById('shippingMethodSelect').value : null;
    addToCart(productId, quantity, shippingMethod);
}
</script>

<script>
// Patch addToCart to accept shipping method
if (typeof addToCart === 'function') {
    const origAddToCart = addToCart;
    window.addToCart = function(productId, quantity = 1, shippingMethod = null) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
        fetch('/shop/api/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity,
                shipping_method: shippingMethod
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof updateCartCount === 'function') updateCartCount();
                if (typeof showNotification === 'function') showNotification('Product added to cart!', 'success');
            } else {
                if (typeof showNotification === 'function') showNotification(data.message || 'Failed to add product', 'error');
            }
        })
        .catch(error => {
            if (typeof showNotification === 'function') showNotification('An error occurred', 'error');
        });
    }
}
// Gallery: scrollable main carousel + full-area magnifier
(function(){
    const carousel = document.getElementById('mainCarousel');
    const thumbnails = document.querySelectorAll('.thumbnail-images img');
    const magnifier = document.getElementById('magnifier');
    if (!carousel) return;

    // Scroll snapping setup (CSS handles most). When clicking a thumbnail, scroll to the slide.
    window.scrollToSlide = function(index, el) {
        const slide = carousel.querySelector('.slide[data-index="' + index + '"]');
        if (slide) {
            carousel.scrollTo({ left: slide.offsetLeft, behavior: 'smooth' });
            thumbnails.forEach(t => t.classList.remove('active'));
            if (el) el.classList.add('active');
        }
    };

    // When carousel scrolls, update active thumbnail
    function updateActiveByScroll(){
        const slides = carousel.querySelectorAll('.slide');
        const scrollLeft = carousel.scrollLeft;
        let activeIndex = 0;
        let minDiff = Infinity;
        slides.forEach(s => {
            const diff = Math.abs(s.offsetLeft - scrollLeft);
            if (diff < minDiff) { minDiff = diff; activeIndex = parseInt(s.dataset.index); }
        });
        thumbnails.forEach(t => t.classList.toggle('active', parseInt(t.dataset.index) === activeIndex));
    }

    carousel.addEventListener('scroll', function(){
        if (this._raf) cancelAnimationFrame(this._raf);
        this._raf = requestAnimationFrame(updateActiveByScroll);
    });

    // Magnifier covers entire main image area on hover (desktop only)
        // Magnifier: 50% box, 100% zoom, not overlapping main image
        function enableMagnifierArea(){
            if (!magnifier) return;
            let activeImg = null;
            function setActiveImage(){
                const slides = carousel.querySelectorAll('.slide');
                const scrollLeft = carousel.scrollLeft;
                let sel = slides[0];
                let min = Infinity;
                slides.forEach(s => { const d = Math.abs(s.offsetLeft - scrollLeft); if (d < min) { min = d; sel = s; }});
                activeImg = sel.querySelector('.slide-image');
                if (activeImg) magnifier.style.backgroundImage = `url(${activeImg.src})`;
            }
            function showMagnifier(e){
                if (!activeImg) return;
                const rect = carousel.getBoundingClientRect();
                const naturalW = activeImg.naturalWidth;
                const naturalH = activeImg.naturalHeight;
                const boxW = Math.max(rect.width * 0.5, 120);
                const boxH = Math.max(rect.height * 0.5, 120);
                magnifier.style.display = 'block';
                magnifier.style.width = boxW + 'px';
                magnifier.style.height = boxH + 'px';
                // Position box to the right of the main image, or left if not enough space
                let left = rect.right + 16;
                let top = rect.top;
                if (window.innerWidth - rect.right < boxW + 32) {
                    left = rect.left - boxW - 16;
                }
                // If not enough space left, fallback to below
                if (left < 0) {
                    left = rect.left;
                    top = rect.bottom + 16;
                }
                magnifier.style.left = left + 'px';
                magnifier.style.top = top + 'px';
                // Show 100% zoom (1:1 pixel ratio)
                magnifier.style.backgroundSize = naturalW + 'px ' + naturalH + 'px';
                // Mouse position relative to carousel
                const x = (e.clientX - rect.left) / rect.width;
                const y = (e.clientY - rect.top) / rect.height;
                // Center the box on the cursor, but clamp so it doesn't go out of bounds
                let bgX = x * naturalW - boxW / 2;
                let bgY = y * naturalH - boxH / 2;
                bgX = Math.max(0, Math.min(bgX, naturalW - boxW));
                bgY = Math.max(0, Math.min(bgY, naturalH - boxH));
                magnifier.style.backgroundPosition = `-${Math.round(bgX)}px -${Math.round(bgY)}px`;
            }
            carousel.addEventListener('mouseenter', function(e){
                if (window.innerWidth <= 768) return;
                setActiveImage();
                showMagnifier(e);
            });
            carousel.addEventListener('mousemove', function(e){
                if (window.innerWidth <= 768) return;
                setActiveImage();
                showMagnifier(e);
            });
            carousel.addEventListener('mouseleave', function(){
                if (magnifier) magnifier.style.display = 'none';
            });
        }
        if (window.innerWidth > 768) enableMagnifierArea();
        window.addEventListener('resize', function(){ if (window.innerWidth > 768) enableMagnifierArea(); else if (magnifier) magnifier.style.display='none'; });

    // Keyboard navigation for carousel: left/right arrows
    document.addEventListener('keydown', function(e){
        const tag = document.activeElement && document.activeElement.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || (document.activeElement && document.activeElement.isContentEditable)) return;
        if (!carousel) return;
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            const slides = carousel.querySelectorAll('.slide');
            if (!slides.length) return;
            const scrollLeft = carousel.scrollLeft;
            let current = 0; let min = Infinity;
            slides.forEach(s => { const d = Math.abs(s.offsetLeft - scrollLeft); if (d < min) { min = d; current = parseInt(s.dataset.index); }});
            const nextIndex = Math.min(current + 1, slides.length - 1);
            const next = carousel.querySelector('.slide[data-index="' + nextIndex + '"]');
            if (next) carousel.scrollTo({ left: next.offsetLeft, behavior: 'smooth' });
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            const slides = carousel.querySelectorAll('.slide');
            if (!slides.length) return;
            const scrollLeft = carousel.scrollLeft;
            let current = 0; let min = Infinity;
            slides.forEach(s => { const d = Math.abs(s.offsetLeft - scrollLeft); if (d < min) { min = d; current = parseInt(s.dataset.index); }});
            const prevIndex = Math.max(current - 1, 0);
            const prev = carousel.querySelector('.slide[data-index="' + prevIndex + '"]');
            if (prev) carousel.scrollTo({ left: prev.offsetLeft, behavior: 'smooth' });
        }
    });
})();
</script>

<style>
/* Main carousel: horizontally scrollable, snap, no scrollbar */
.main-carousel {
    display: flex;
    gap: 0;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
    position: relative;
    min-height: 350px;
    background: #fff;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--color-border);
}
.main-carousel::-webkit-scrollbar { display: none; }
.main-carousel .slide {
    flex: 0 0 100%;
    scroll-snap-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 100%;
    min-height: 350px;
}
.main-carousel .slide img {
    width: 100%;
    height: auto;
    max-height: 420px;
    object-fit: contain;
    display: block;
    background: #f4f1de;
}
/* Magnifier lens covers main image area, with magnifying glass cursor */
.magnifier {
    position: fixed;
    pointer-events: none;
    background-repeat: no-repeat;
    background-position: center center;
    background-size: cover;
    border: 2px solid rgba(255,255,255,0.95);
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
    display: none;
    z-index: 9999;
    border-radius: 10px;
    cursor: zoom-in;
}
.main-carousel.magnifier-area:hover {
    cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><circle cx="14" cy="14" r="12" stroke="black" stroke-width="2" fill="none"/><line x1="22" y1="22" x2="30" y2="30" stroke="black" stroke-width="3"/></svg>') 16 16, zoom-in;
}
/* Thumbnail layout: centered row below image */
.thumbnail-images {
    display: flex;
    gap: 8px;
    justify-content: center;
    padding: 8px 0;
    flex-wrap: wrap;
}
.thumbnail-images img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    border: 2px solid transparent;
}
.thumbnail-images img.active {
    box-shadow: 0 0 0 3px rgba(52,152,219,0.15);
    transform: scale(1.03);
    border-color: var(--color-primary);
}
@media (max-width:600px) {
    .main-carousel .slide, .main-carousel { min-height: 220px; }
    .thumbnail-images img { width: 64px; height: 64px; }
}
</style>
