<?php
require_once 'functions.php';
// No login required for contact page, but we need session for header
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$success = '';
$error = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate
    $errors = [];
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($subject)) $errors[] = "Subject is required.";
    if (empty($message)) $errors[] = "Message is required.";
    
    if (empty($errors)) {
        // Here you can send email to admin or save to database
        // For now, we'll just show a success message
        $to = "admin@beautymart.com";
        $headers = "From: " . $email . "\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $full_message = "Name: $name\nEmail: $email\n\nMessage:\n$message";
        
        // Uncomment to actually send email
        // if (mail($to, $subject, $full_message, $headers)) {
        //     $success = "Thank you for contacting us! We'll get back to you within 24 hours.";
        // } else {
        //     $error = "Failed to send message. Please try again later.";
        // }
        
        // For demo purposes, just show success
        $success = "Thank you for contacting us! We'll get back to you within 24 hours.";
        
        // Optionally save to database
        // $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        // $stmt->execute([$name, $email, $subject, $message]);
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="../css/customer_dash.css">
    <style>
        .contact-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        
        .contact-hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            font-family: 'Playfair Display', serif;
        }
        
        .contact-hero p {
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.9;
        }
        
        .contact-content {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 50px;
        }
        
        .contact-info {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            height: fit-content;
        }
        
        .contact-info h3 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 20px;
            font-family: 'Playfair Display', serif;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        
        .info-icon {
            width: 40px;
            font-size: 20px;
            margin-right: 15px;
        }
        
        .info-text h4 {
            color: #333;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .info-text p {
            color: #666;
            line-height: 1.5;
        }
        
        .business-hours {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .business-hours h4 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .business-hours p {
            color: #666;
            margin-bottom: 5px;
        }
        
        .contact-form {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .contact-form h3 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
        }
        
        .form-subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-submit {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-submit:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .map-section {
            max-width: 1200px;
            margin: 0 auto 60px;
            padding: 0 20px;
        }
        
        .map-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .map-container iframe {
            width: 100%;
            height: 400px;
            border: none;
        }
        
        .faq-section {
            max-width: 1200px;
            margin: 0 auto 60px;
            padding: 0 20px;
        }
        
        .faq-section h2 {
            text-align: center;
            color: #333;
            font-size: 32px;
            margin-bottom: 40px;
            font-family: 'Playfair Display', serif;
        }
        
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .faq-item {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .faq-item h4 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 12px;
        }
        
        .faq-item p {
            color: #666;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .contact-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .contact-hero h1 {
                font-size: 32px;
            }
            
            .contact-form {
                padding: 25px;
            }
            
            .faq-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="inner">
        <div class="left">
            <a href="about.php">About Us</a>
            <span class="sep">|</span>
            <a href="contact.php">Contact Us</a>
        </div>
        <div class="right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="../includes/3-customer/profile.php">
                    <div class="avatar">
                        <?php 
                        if (isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image']) && file_exists("../uploads/profile_images/" . $_SESSION['profile_image'])): 
                        ?>
                            <img src="../uploads/profile_images/<?= htmlspecialchars($_SESSION['profile_image']) ?>" 
                                 alt="Profile" 
                                 style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                </a>
                Welcome, <?= htmlspecialchars($_SESSION['username']) ?>
            <?php else: ?>
                <a href="login.php">Login</a>
                <span class="sep">|</span>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Navbar -->
<nav class="navbar">
    <div class="inner">
        <a href="<?= isset($_SESSION['user_id']) ? '../3-customer/dashboard.php' : '../index.php' ?>" class="logo">
            <div class="logo-icon">
                <img src="../images/logo.png" alt="Logo">
            </div>
            <div class="logo-text"><span>Beauty</span><span>Mart</span></div>
        </a>

        <div class="search-wrap">
            <!--<input type="text" placeholder="Search...">
            <button type="button">Search</button>-->
        </div>

        <!--<div class="nav-icons">
            <a href="<?= isset($_SESSION['user_id']) ? 'customer/dashboard.php' : 'index.php' ?>" title="Home">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/>
                    <polyline points="9 21 9 12 15 12 15 21"/>
                </svg>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="customer/orders.php" title="Orders">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                        <path d="M8 21h8M12 17v4"/>
                    </svg>
                </a>
                <a href="customer/wishlist.php" title="Wishlist">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </a>
                <a href="customer/cart.php" title="Cart">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                </a>
            <?php endif; ?>
        </div>-->
    </div>
</nav>

<!-- Contact Hero Section -->
<div class="contact-hero">
    <h1>Get In Touch</h1>
    <p>We'd love to hear from you! Send us a message and we'll respond as soon as possible.</p>
</div>

<div class="contact-content">
    <!-- Contact Information -->
    <div class="contact-info">
        <h3>Contact Information</h3>
        
        <div class="info-item">
            <div class="info-icon">📍</div>
            <div class="info-text">
                <h4>Visit Us</h4>
                <p>123 Beauty Avenue,<br>Makati City, Metro Manila,<br>Philippines 1200</p>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-icon">📞</div>
            <div class="info-text">
                <h4>Call Us</h4>
                <p>+63 2 8123 4567<br>+63 912 345 6789</p>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-icon">✉️</div>
            <div class="info-text">
                <h4>Email Us</h4>
                <p>hello@beautymart.com<br>support@beautymart.com</p>
            </div>
        </div>
        
        <div class="business-hours">
            <h4>Business Hours</h4>
            <p><strong>Monday - Friday:</strong> 9:00 AM - 8:00 PM</p>
            <p><strong>Saturday:</strong> 10:00 AM - 6:00 PM</p>
            <p><strong>Sunday:</strong> Closed</p>
        </div>
    </div>
    
    <!-- Contact Form -->
    <div class="contact-form">
        <h3>Send Us a Message</h3>
        <p class="form-subtitle">We'll get back to you within 24 hours</p>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" required 
                       value="<?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="subject">Subject *</label>
                <select id="subject" name="subject" required>
                    <option value="">Select a subject</option>
                    <option value="General Inquiry">General Inquiry</option>
                    <option value="Product Question">Product Question</option>
                    <option value="Order Issue">Order Issue</option>
                    <option value="Returns & Refunds">Returns & Refunds</option>
                    <option value="Partnership">Partnership Opportunity</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="message">Message *</label>
                <textarea id="message" name="message" rows="6" required></textarea>
            </div>
            
            <button type="submit" name="send_message" class="btn-submit">Send Message</button>
        </form>
    </div>
</div>

<!-- FAQ Section -->
<div class="faq-section">
    <h2>Frequently Asked Questions</h2>
    <div class="faq-grid">
        <div class="faq-item">
            <h4>How long does shipping take?</h4>
            <p>Metro Manila orders typically arrive within 2-3 business days. Provincial orders take 5-7 business days.</p>
        </div>
        <div class="faq-item">
            <h4>What is your return policy?</h4>
            <p>We accept returns within 30 days of purchase. Items must be unused and in original packaging.</p>
        </div>
        <div class="faq-item">
            <h4>Do you offer international shipping?</h4>
            <p>Currently, we only ship within the Philippines. We're working on expanding internationally soon!</p>
        </div>
        <div class="faq-item">
            <h4>How can I track my order?</h4>
            <p>Once your order ships, you'll receive a tracking number via email. You can also track it in your account dashboard.</p>
        </div>
    </div>
</div>

<!-- Footer -->
<footer>
    <div class="inner">
        <p class="copy">Copyright &copy; 2025 All Rights Reserved by <span>Beauty Mart.</span></p>
        <div class="socials">
            <a href="#" title="Facebook">f</a>
            <a href="#" title="Twitter">t</a>
            <a href="#" title="Website">🌐</a>
            <a href="#" title="LinkedIn">in</a>
        </div>
    </div>
</footer>

</body>
</html>