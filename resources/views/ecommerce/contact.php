<?php
$pageTitle = "Contact Us";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    $errors = [];
    
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email) || !isEmail($email)) $errors[] = "Valid email is required";
    if (empty($message)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        $result = $db->execute(
            "INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)",
            [$name, $email, $phone, $subject, $message]
        );
        
        if ($result) {
            Session::setFlash('success', 'Thank you for your message! We will get back to you soon.');
            redirect('?page=contact');
        } else {
            Session::setFlash('error', 'Failed to send message. Please try again.');
        }
    } else {
        Session::setFlash('error', implode('<br>', $errors));
    }
}
?>

<div class="contact-page">
    <div class="container">
        <div class="page-header">
            <h1>Contact Us</h1>
            <p>Get in touch with us. We'd love to hear from you!</p>
        </div>

        <div class="contact-content">
            <!-- Contact Information -->
            <div class="contact-info-section">
                <div class="contact-info-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Visit Us</h3>
                    <p>123 Shopping Street<br>City, State 12345<br>Country</p>
                </div>
                <div class="contact-info-card">
                    <i class="fas fa-phone"></i>
                    <h3>Call Us</h3>
                    <p>+1 234 567 8900<br>+1 234 567 8901</p>
                </div>
                <div class="contact-info-card">
                    <i class="fas fa-envelope"></i>
                    <h3>Email Us</h3>
                    <p>info@ecommerce.com<br>support@ecommerce.com</p>
                </div>
                <div class="contact-info-card">
                    <i class="fas fa-clock"></i>
                    <h3>Working Hours</h3>
                    <p>Mon - Fri: 9:00 AM - 6:00 PM<br>Sat - Sun: 10:00 AM - 4:00 PM</p>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-section">
                <h2>Send Us a Message</h2>
                <form method="POST" class="contact-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Your Name *</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Your Email *</label>
                            <input type="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone">
                        </div>
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Message *</label>
                        <textarea name="message" rows="6" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-large">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
