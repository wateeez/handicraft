<?php
$pageTitle = "Frequently Asked Questions";

// Get FAQs grouped by category
$faqs = $db->fetchAll("SELECT * FROM faqs WHERE is_active = 1 ORDER BY category, display_order ASC");

// Group by category
$faqsByCategory = [];
foreach ($faqs as $faq) {
    $category = $faq['category'] ?? 'General';
    $faqsByCategory[$category][] = $faq;
}
?>

<div class="faq-page">
    <div class="container">
        <div class="page-header">
            <h1>Frequently Asked Questions</h1>
            <p>Find answers to common questions about our products and services</p>
        </div>

        <div class="faq-content">
            <?php foreach ($faqsByCategory as $category => $categoryFaqs): ?>
                <div class="faq-category">
                    <h2><?php echo htmlspecialchars($category); ?></h2>
                    <div class="faq-list">
                        <?php foreach ($categoryFaqs as $index => $faq): ?>
                            <div class="faq-item">
                                <div class="faq-question" onclick="toggleFaq(this)">
                                    <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="faq-answer">
                                    <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="faq-contact">
            <h3>Still have questions?</h3>
            <p>Can't find the answer you're looking for? Please contact our customer support team.</p>
            <a href="?page=contact" class="btn btn-primary">Contact Us</a>
        </div>
    </div>
</div>

<script>
function toggleFaq(element) {
    const faqItem = element.parentElement;
    const answer = faqItem.querySelector('.faq-answer');
    const icon = element.querySelector('i');
    
    faqItem.classList.toggle('active');
    
    if (faqItem.classList.contains('active')) {
        answer.style.maxHeight = answer.scrollHeight + 'px';
        icon.style.transform = 'rotate(180deg)';
    } else {
        answer.style.maxHeight = '0';
        icon.style.transform = 'rotate(0deg)';
    }
}
</script>
