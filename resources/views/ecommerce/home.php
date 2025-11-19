<?php
$pageTitle = "Home";

// Get featured products
$featuredProducts = $db->fetchAll(
    "SELECT p.*, 
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
     FROM products p
     WHERE p.is_active = 1 AND p.is_featured = 1
     ORDER BY p.created_at DESC
     LIMIT 8"
);

// Get categories
$categories = $db->fetchAll(
    "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC LIMIT 6"
);

// Get latest blog posts
$latestBlogs = $db->fetchAll(
    "SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY published_at DESC LIMIT 3"
);
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1>Welcome to <?php echo APP_NAME; ?></h1>
            <p>Discover amazing products at unbeatable prices</p>
            <a href="/shop/products" class="btn btn-primary">Shop Now</a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section">
    <div class="container">
        <h2 class="section-title">Shop by Category</h2>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <a href="/shop/products?category=<?php echo $category['slug']; ?>">
                        <?php if ($category['image']): ?>
                            <img src="/uploads/<?php echo htmlspecialchars($category['image']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                        <?php else: ?>
                            <div class="category-placeholder">
                                <i class="fas fa-box"></i>
                            </div>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="featured-products">
    <div class="container">
        <h2 class="section-title">Featured Products</h2>
        <div class="products-grid">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="product-card">
                    <a href="/shop/product/<?php echo $product['slug']; ?>">
                        <div class="product-image">
                            <?php if ($product['image']): ?>
                                <img src="/uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
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
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
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
        <div class="text-center">
            <a href="/shop/products" class="btn btn-secondary">View All Products</a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-shipping-fast"></i>
                <h3>Free Shipping</h3>
                <p>On orders over $50</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Secure Payment</h3>
                <p>100% secure transactions</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-undo"></i>
                <h3>Easy Returns</h3>
                <p>30-day return policy</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-headset"></i>
                <h3>24/7 Support</h3>
                <p>Dedicated customer service</p>
            </div>
        </div>
    </div>
</section>

<!-- Latest Blog Posts -->
<?php if ($latestBlogs): ?>
<section class="blog-section">
    <div class="container">
        <h2 class="section-title">Latest from Our Blog</h2>
        <div class="blog-grid">
            <?php foreach ($latestBlogs as $blog): ?>
                <div class="blog-card">
                    <?php if ($blog['featured_image']): ?>
                        <img src="/uploads/<?php echo htmlspecialchars($blog['featured_image']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>">
                    <?php endif; ?>
                    <div class="blog-content">
                        <h3><a href="/shop/blog/<?php echo $blog['slug']; ?>"><?php echo htmlspecialchars($blog['title']); ?></a></h3>
                        <p class="blog-meta">
                            <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($blog['published_at'])); ?>
                        </p>
                        <p><?php echo htmlspecialchars(substr($blog['excerpt'], 0, 150)); ?>...</p>
                        <a href="/shop/blog/<?php echo $blog['slug']; ?>" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
