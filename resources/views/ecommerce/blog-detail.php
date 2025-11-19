<?php
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (!$slug) {
    redirect('?page=blog');
}

// Get blog post
$blog = $db->fetchOne(
    "SELECT bp.*, au.full_name as author_name 
     FROM blog_posts bp
     LEFT JOIN admin_users au ON bp.author_id = au.id
     WHERE bp.slug = ? AND bp.is_published = 1",
    [$slug]
);

if (!$blog) {
    redirect('?page=blog');
}

$pageTitle = $blog['title'];

// Get related blog posts
$relatedBlogs = $db->fetchAll(
    "SELECT * FROM blog_posts 
     WHERE is_published = 1 AND id != ?
     ORDER BY published_at DESC
     LIMIT 3",
    [$blog['id']]
);
?>

<div class="blog-detail-page">
    <div class="container">
        <div class="breadcrumb">
            <a href="?page=home">Home</a> / 
            <a href="?page=blog">Blog</a> / 
            <span><?php echo htmlspecialchars($blog['title']); ?></span>
        </div>

        <article class="blog-post">
            <?php if ($blog['featured_image']): ?>
                <div class="blog-featured-image">
                    <img src="/uploads/<?php echo htmlspecialchars($blog['featured_image']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>">
                </div>
            <?php endif; ?>

            <div class="blog-header">
                <h1><?php echo htmlspecialchars($blog['title']); ?></h1>
                <div class="blog-meta">
                    <span><i class="fas fa-calendar"></i> <?php echo date('F d, Y', strtotime($blog['published_at'])); ?></span>
                    <?php if ($blog['author_name']): ?>
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($blog['author_name']); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="blog-content">
                <?php echo nl2br(htmlspecialchars($blog['content'])); ?>
            </div>

            <div class="blog-share">
                <h3>Share this post:</h3>
                <div class="share-buttons">
                    <a href="#" class="share-btn facebook"><i class="fab fa-facebook"></i> Facebook</a>
                    <a href="#" class="share-btn twitter"><i class="fab fa-twitter"></i> Twitter</a>
                    <a href="#" class="share-btn linkedin"><i class="fab fa-linkedin"></i> LinkedIn</a>
                    <a href="#" class="share-btn pinterest"><i class="fab fa-pinterest"></i> Pinterest</a>
                </div>
            </div>
        </article>

        <?php if ($relatedBlogs): ?>
            <div class="related-posts">
                <h2>Related Posts</h2>
                <div class="blog-grid">
                    <?php foreach ($relatedBlogs as $relatedBlog): ?>
                        <div class="blog-card">
                            <?php if ($relatedBlog['featured_image']): ?>
                                <div class="blog-image">
                                    <a href="?page=blog-detail&slug=<?php echo $relatedBlog['slug']; ?>">
                                        <img src="/uploads/<?php echo htmlspecialchars($relatedBlog['featured_image']); ?>" alt="<?php echo htmlspecialchars($relatedBlog['title']); ?>">
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="blog-content">
                                <h3><a href="?page=blog-detail&slug=<?php echo $relatedBlog['slug']; ?>"><?php echo htmlspecialchars($relatedBlog['title']); ?></a></h3>
                                <p><?php echo htmlspecialchars(substr($relatedBlog['excerpt'] ?? $relatedBlog['content'], 0, 150)); ?>...</p>
                                <a href="?page=blog-detail&slug=<?php echo $relatedBlog['slug']; ?>" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
