<?php
$pageTitle = "Blog";

// Pagination
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;

// Get total blog posts
$totalResult = $db->fetchOne("SELECT COUNT(*) as total FROM blog_posts WHERE is_published = 1");
$total = $totalResult['total'];

$pagination = paginate($total, 9, $currentPage);

// Get blog posts
$blogs = $db->fetchAll(
    "SELECT bp.*, au.full_name as author_name 
     FROM blog_posts bp
     LEFT JOIN admin_users au ON bp.author_id = au.id
     WHERE bp.is_published = 1
     ORDER BY bp.published_at DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}"
);
?>

<div class="blog-page">
    <div class="container">
        <div class="page-header">
            <h1>Our Blog</h1>
            <p>Latest news, tips, and updates from our team</p>
        </div>

        <?php if ($blogs): ?>
            <div class="blog-grid">
                <?php foreach ($blogs as $blog): ?>
                    <div class="blog-card">
                        <?php if ($blog['featured_image']): ?>
                            <div class="blog-image">
                                <a href="?page=blog-detail&slug=<?php echo $blog['slug']; ?>">
                                    <img src="/uploads/<?php echo htmlspecialchars($blog['featured_image']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>">
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="blog-content">
                            <div class="blog-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($blog['published_at'])); ?></span>
                                <?php if ($blog['author_name']): ?>
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($blog['author_name']); ?></span>
                                <?php endif; ?>
                            </div>
                            <h2><a href="?page=blog-detail&slug=<?php echo $blog['slug']; ?>"><?php echo htmlspecialchars($blog['title']); ?></a></h2>
                            <p><?php echo htmlspecialchars(substr($blog['excerpt'] ?? $blog['content'], 0, 200)); ?>...</p>
                            <a href="?page=blog-detail&slug=<?php echo $blog['slug']; ?>" class="read-more">
                                Read More <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination">
                    <?php if ($pagination['has_prev']): ?>
                        <a href="?page=blog&p=<?php echo $pagination['current_page'] - 1; ?>" class="page-link">&laquo; Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <a href="?page=blog&p=<?php echo $i; ?>" 
                           class="page-link <?php echo $i === $pagination['current_page'] ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($pagination['has_next']): ?>
                        <a href="?page=blog&p=<?php echo $pagination['current_page'] + 1; ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-content">
                <i class="fas fa-blog"></i>
                <h3>No blog posts yet</h3>
                <p>Check back soon for updates!</p>
            </div>
        <?php endif; ?>
    </div>
</div>
