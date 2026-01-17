<?php
$pageTitle = "Home"; // Will be rendered as "Home - [AppName]"

// Fetch Data (Keep existing logic)
$featuredProducts = $db->fetchAll(
    "SELECT p.*, 
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
     FROM products p
     WHERE p.is_active = 1 AND p.is_featured = 1
     ORDER BY p.created_at DESC
     LIMIT 8"
);

$categories = $db->fetchAll(
    "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC LIMIT 4"
);

$latestBlogs = $db->fetchAll(
    "SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY published_at DESC LIMIT 3"
);
?>

<!-- Hero Section: Storytelling -->
<div class="hero-wrapper">
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <span class="hero-subtitle">Handcrafted with Love</span>
                <h1>Authentic Artisan Treasures</h1>
                <p>Discover unique, handmade items that tell a story. Connected directly with master craftsmen.</p>
                <div class="hero-buttons">
                    <a href="/shop/products" class="btn btn-primary btn-lg">Explore Collection</a>
                    <a href="/shop/about" class="btn btn-white btn-lg">Our Story</a>
                </div>
            </div>
        </div>
    </section>

    <!-- USP: Artisan Values -->
    <section class="usp-strip">
        <div class="container">
            <div class="usp-grid">
                <div class="usp-item">
                    <i class="fas fa-hand-holding-heart"></i>
                    <div class="usp-text">
                        <strong>100% Handmade</strong>
                        <span>Crafted with care</span>
                    </div>
                </div>
                <div class="usp-item">
                    <i class="fas fa-leaf"></i>
                    <div class="usp-text">
                        <strong>Eco-Friendly</strong>
                        <span>Sustainable materials</span>
                    </div>
                </div>
                <div class="usp-item">
                    <i class="fas fa-gem"></i>
                    <div class="usp-text">
                        <strong>Unique Designs</strong>
                        <span>One of a kind</span>
                    </div>
                </div>
                <div class="usp-item">
                    <i class="fas fa-globe-americas"></i>
                    <div class="usp-text">
                        <strong>Global Shipping</strong>
                        <span>From our workshop to you</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Shop by Category: "Our Collections" -->
<section class="section categories-section">
    <div class="container">
        <div class="section-header text-center">
            <h2 class="section-title">Curated Collections</h2>
            <p class="section-subtitle">Find the perfect piece for your home</p>
        </div>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <a href="/shop/products?category=<?php echo $category['slug']; ?>">
                        <div class="category-image-wrapper">
                            <?php if ($category['image']): ?>
                                <img src="/<?php echo htmlspecialchars($category['image']); ?>"
                                    alt="<?php echo htmlspecialchars($category['name']); ?>">
                            <?php else: ?>
                                <div class="category-placeholder">
                                    <i class="fas fa-paint-brush"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5">
            <a href="/shop/products" class="btn btn-secondary">View All Categories</a>
        </div>
    </div>
</section>

<!-- Featured Products: "Fresh from the Workshop" -->
<section class="section featured-products">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Fresh from the Workshop</h2>
        </div>
        <div class="products-grid">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <a href="/shop/product/<?php echo $product['slug']; ?>">
                            <?php if ($product['image']): ?>
                                <img src="/<?php echo htmlspecialchars($product['image']); ?>"
                                    alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <div class="category-placeholder"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                        </a>

                        <?php if ($product['sale_price']): ?>
                            <span class="badge badge-sale">Sale</span>
                        <?php endif; ?>

                        <!-- Quick View Overlay -->
                        <div class="product-overlay">
                            <button class="btn-quick-view" title="Quick View"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>

                    <div class="product-info">
                        <span class="product-category">Handmade</span>
                        <h3 class="product-name">
                            <a
                                href="/shop/product/<?php echo $product['slug']; ?>"><?php echo htmlspecialchars($product['name']); ?></a>
                        </h3>
                        <div class="product-price">
                            <?php if ($product['sale_price']): ?>
                                <span class="original-price"><?php echo formatPrice($product['price']); ?></span>
                                <span class="sale-price"><?php echo formatPrice($product['sale_price']); ?></span>
                            <?php else: ?>
                                <span class="price"><?php echo formatPrice($product['price']); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($product['stock_quantity'] > 0): ?>
                            <div class="product-action-btn">
                                <button class="btn btn-primary btn-add-to-cart"
                                    onclick="addToCart(<?php echo $product['id']; ?>)">
                                    Add to Basket
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Promotional Section -->
<section class="promo-banner">
    <div class="container">
        <div class="promo-text">
            <h2>Support Local Artisans</h2>
            <p>Every purchase directly supports our community of master crafters.</p>
            <a href="/shop/about" class="btn btn-white btn-lg">Read Our Mission</a>
        </div>
    </div>
</section>

<!-- Blog Section: "Artisan Journals" -->
<?php if ($latestBlogs): ?>
    <section class="section blog-section">
        <div class="container">
            <div class="section-header text-center">
                <h2 class="section-title">Artisan Journals</h2>
                <p class="section-subtitle">Stories behind the craft</p>
            </div>
            <div class="blog-grid"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <?php foreach ($latestBlogs as $blog): ?>
                    <div class="blog-card"
                        style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                        <?php if ($blog['featured_image']): ?>
                            <div style="height: 200px; overflow: hidden;">
                                <img src="/<?php echo htmlspecialchars($blog['featured_image']); ?>"
                                    style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                        <div class="blog-content" style="padding: 1.5rem;">
                            <span
                                style="color: #666; font-size: 0.85rem; display: block; margin-bottom: 0.5rem;"><?php echo date('F d, Y', strtotime($blog['published_at'])); ?></span>
                            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;"><a
                                    href="/shop/blog/<?php echo $blog['slug']; ?>"><?php echo htmlspecialchars($blog['title']); ?></a>
                            </h3>
                            <p style="color: #666; font-size: 0.95rem; line-height: 1.6;">
                                <?php echo htmlspecialchars(substr($blog['excerpt'], 0, 100)); ?>...</p>
                            <a href="/shop/blog/<?php echo $blog['slug']; ?>"
                                style="color: #5c7457; font-weight: 700; margin-top: 1rem; display: inline-block;">Read
                                Story</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Newsletter -->
<section class="newsletter-section">
    <div class="container">
        <div class="newsletter-content">
            <div class="newsletter-text">
                <h3>Join Our Craft Community</h3>
                <p>Receive exclusive offers, artisan stories, and new arrival alerts.</p>
            </div>
            <form class="newsletter-form">
                <input type="email" placeholder="Your email address" required>
                <button type="submit" class="btn btn-primary">Subscribe</button>
            </form>
        </div>
    </div>
</section>