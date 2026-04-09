<?php
require_once 'functions.php';
// No login required for about page, but we need session for header
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link rel="stylesheet" href="../css/customer_dash.css">
    <style>
        .about-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .about-hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            font-family: 'Playfair Display', serif;
        }
        
        .about-hero p {
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.9;
        }
        
        .about-content {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
        }
        
        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .mission-card, .vision-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .mission-card:hover, .vision-card:hover {
            transform: translateY(-5px);
        }
        
        .mission-card h2, .vision-card h2 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 20px;
            font-family: 'Playfair Display', serif;
        }
        
        .mission-card p, .vision-card p {
            color: #666;
            line-height: 1.6;
            font-size: 16px;
        }
        
        .story-section {
            margin-bottom: 60px;
            background: #f9f9f9;
            padding: 60px;
            border-radius: 12px;
        }
        
        .story-section h2 {
            text-align: center;
            color: #333;
            font-size: 32px;
            margin-bottom: 30px;
            font-family: 'Playfair Display', serif;
        }
        
        .story-section p {
            color: #666;
            line-height: 1.8;
            font-size: 16px;
            margin-bottom: 20px;
            text-align: center;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }
        
        .value-card {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        
        .value-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .value-card h3 {
            color: #667eea;
            font-size: 22px;
            margin-bottom: 15px;
        }
        
        .value-card p {
            color: #666;
            line-height: 1.6;
        }
        
        .team-section {
            margin-bottom: 60px;
        }
        
        .team-section h2 {
            text-align: center;
            color: #333;
            font-size: 32px;
            margin-bottom: 40px;
            font-family: 'Playfair Display', serif;
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
        }
        
        .team-card {
            text-align: center;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        
        .team-card:hover {
            transform: translateY(-5px);
        }
        
        .team-img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .team-info {
            padding: 20px;
        }
        
        .team-info h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .team-info .role {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .team-info .bio {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .mission-vision {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .story-section {
                padding: 30px;
            }
            
            .about-hero h1 {
                font-size: 32px;
            }
            
            .values-grid {
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
                <a href="../3-customer/profile.php">
                    <div class="avatar">
                        <?php 
                        // Fetch profile image if needed
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

<!-- About Hero Section -->
<div class="about-hero">
    <h1>Our Story</h1>
    <p>Bringing beauty and confidence to every individual since 2020</p>
</div>

<div class="about-content">
    <!-- Mission & Vision -->
    <div class="mission-vision">
        <div class="mission-card">
            <h2>Our Mission</h2>
            <p>To provide high-quality beauty products that enhance natural beauty and boost confidence, while delivering exceptional customer service and value.</p>
        </div>
        <div class="vision-card">
            <h2>Our Vision</h2>
            <p>To become the most trusted and beloved beauty destination in the Philippines, inspiring self-expression and empowering individuals to feel beautiful in their own skin.</p>
        </div>
    </div>
    
    <!-- Our Story -->
    <div class="story-section">
        <h2>Our Journey</h2>
        <p>Beauty Mart was born from a simple idea: everyone deserves access to quality beauty products at affordable prices. What started as a small online shop in 2020 has grown into a trusted destination for beauty enthusiasts across the Philippines.</p>
        <p>We carefully curate our collection, partnering with reputable brands and local artisans to bring you the best in skincare, makeup, haircare, and fragrances. Every product in our store is chosen with our customers' needs and preferences in mind.</p>
        <p>Today, we're proud to serve thousands of satisfied customers, helping them discover products that make them feel confident and beautiful. Our commitment to quality, authenticity, and customer satisfaction remains at the heart of everything we do.</p>
    </div>
    
    <!-- Our Values -->
    <h2 style="text-align: center; color: #333; font-size: 32px; margin-bottom: 40px;">Our Core Values</h2>
    <div class="values-grid">
        <div class="value-card">
            <div class="value-icon">✨</div>
            <h3>Quality First</h3>
            <p>We never compromise on quality. Every product is thoroughly vetted to ensure it meets our high standards.</p>
        </div>
        <div class="value-card">
            <div class="value-icon">❤️</div>
            <h3>Customer-Centric</h3>
            <p>Your satisfaction is our priority. We're committed to providing exceptional service and support.</p>
        </div>
        <div class="value-card">
            <div class="value-icon">🌱</div>
            <h3>Sustainability</h3>
            <p>We promote eco-friendly products and practices, contributing to a healthier planet.</p>
        </div>
        <div class="value-card">
            <div class="value-icon">🤝</div>
            <h3>Authenticity</h3>
            <p>We guarantee 100% authentic products from trusted brands and suppliers.</p>
        </div>
    </div>
    
    <!-- Team Section -->
    <div class="team-section">
        <h2>Meet Our Team</h2>
        <div class="team-grid">
            <div class="team-card">
                <div class="team-img" style="background-image: url('images/team/ceo.jpg'); background-size: cover; background-position: center;"></div>
                <div class="team-info">
                    <h3>Maria Santos</h3>
                    <div class="role">Founder & CEO</div>
                    <p class="bio">With over 10 years in the beauty industry, Maria leads our vision of making beauty accessible to all.</p>
                </div>
            </div>
            <div class="team-card">
                <div class="team-img" style="background-image: url('images/team/marketing.jpg'); background-size: cover; background-position: center;"></div>
                <div class="team-info">
                    <h3>James Reyes</h3>
                    <div class="role">Marketing Director</div>
                    <p class="bio">James brings creativity and passion to our brand, connecting beauty with community.</p>
                </div>
            </div>
            <div class="team-card">
                <div class="team-img" style="background-image: url('images/team/customer.jpg'); background-size: cover; background-position: center;"></div>
                <div class="team-info">
                    <h3>Patricia Lim</h3>
                    <div class="role">Customer Experience Manager</div>
                    <p class="bio">Patricia ensures every customer feels heard, valued, and satisfied with their experience.</p>
                </div>
            </div>
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