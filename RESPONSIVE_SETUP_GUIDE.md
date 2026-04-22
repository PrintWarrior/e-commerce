# RESPONSIVE DESIGN SETUP GUIDE - Lume & Co

## Overview
This guide explains how to implement the responsive CSS and hamburger menu for your Beauty Mart project.

### Breakpoints
- **Mobile**: < 768px (phones)
- **Tablet**: 768px - 1024px 
- **Desktop**: > 1024px (laptops/PCs)
- **Extra Small**: < 480px (small phones)
- **Large Screens**: > 1440px

---

## STEP 1: Link the Responsive CSS File

Add this line in the <head> section of ALL your PHP/HTML files that need responsive design.

**Location**: In the <head> tag, AFTER your existing CSS links.

\\\html
<link rel=\"stylesheet\" href=\"css/responsive.css\">
\\\

**Example for index.php:**
\\\html
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Beauty Mart</title>
    <link href=\"https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Nunito:wght@400;600;700&display=swap\" rel=\"stylesheet\">
    <link rel=\"stylesheet\" href=\"css/index.css\">
    <link rel=\"stylesheet\" href=\"css/responsive.css\">  <!-- ADD THIS LINE -->
    <link rel=\"icon\" type=\"image/png\" href=\"images/logo.png\">
</head>
\\\

---

## STEP 2: Update Navbar HTML Structure

Replace your navbar \<nav class=\"navbar\">\ section with this structure to include the hamburger menu:

\\\html
<!-- CHECKBOX FOR MENU TOGGLE (hidden) -->
<input type=\"checkbox\" id=\"menu-toggle\" style=\"display: none;\">

<!-- ── Navbar ───────────────────────────────────────────────────── -->
<nav class=\"navbar\">
    <div class=\"inner\">
        <!-- Logo -->
        <a href=\"index.php\" class=\"logo\">
            <div class=\"logo-icon\">
                <img src=\"images/logo.png\" alt=\"Beauty Mart\">
            </div>
            <div class=\"logo-text\">
                <span>Beauty</span>
                <span>Mart</span>
            </div>
        </a>

        <!-- Search (hidden on mobile via CSS) -->
        <div class=\"search-wrap\">
            <input type=\"text\" placeholder=\"Search...\">
            <button type=\"button\">Search</button>
        </div>

        <!-- Nav Icons (hidden on mobile via CSS) -->
        <div class=\"nav-icons\">
            <a href=\"index.php\" title=\"Home\">
                <svg width=\"18\" height=\"18\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\">
                    <path d=\"M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z\"/>
                    <polyline points=\"9 21 9 12 15 12 15 21\"/>
                </svg>
            </a>
        </div>

        <!-- HAMBURGER MENU BUTTON (visible only on mobile) -->
        <label for=\"menu-toggle\" class=\"hamburger\" style=\"cursor: pointer;\">
            <span></span>
            <span></span>
            <span></span>
        </label>
    </div>

    <!-- MOBILE NAVIGATION MENU (hidden by default, shown when hamburger is clicked) -->
    <div class=\"nav-menu\">
        <a href=\"index.php\">Home</a>
        <a href=\"browse-products.php\">Browse Products</a>
        <a href=\"includes/about.php\">About Us</a>
        <a href=\"includes/contact.php\">Contact Us</a>
        <a href=\"includes/sell.php\">Start Selling</a>
        <a href=\"includes/register.php\">Sign Up</a>
        <a href=\"includes/login.php\">Login</a>
    </div>
</nav>
\\\

---

## STEP 3: Apply to Your Files

You need to update these files to include the responsive CSS link:

### Public Pages:
1. **index.php** - Homepage
2. **browse-products.php** - Product browsing
3. All files in **includes/** folder:
   - includes/about.php
   - includes/contact.php
   - includes/login.php
   - includes/register.php
   - includes/sell.php
   - includes/forgot_password.php
   - includes/reset_password.php

### Customer Pages (in 3-customer/):
1. 3-customer/dashboard.php
2. 3-customer/products.php
3. 3-customer/products_details.php
4. 3-customer/cart.php
5. 3-customer/checkout.php
6. 3-customer/wishlist.php
7. 3-customer/orders.php
8. 3-customer/profile.php
9. All other customer pages

### Seller Pages (in 2-seller/):
1. 2-seller/dashboard.php
2. 2-seller/add_product.php
3. 2-seller/account.php
4. 2-seller/earnings.php
5. 2-seller/notifications.php
6. All other seller pages

### Admin Pages (in 1-admin/ and 0-superadmin/):
1. All admin dashboard pages
2. All management pages

---

## STEP 4: Breakpoint Reference

### Mobile (< 768px)
- Top bar: **HIDDEN**
- Search bar: **HIDDEN**
- Nav icons: **HIDDEN**
- Hamburger menu: **VISIBLE** ✓
- Categories grid: **2 columns**
- Products grid: **2 columns**

### Tablet (768px - 1024px)
- Top bar: **VISIBLE** (smaller)
- Search bar: **VISIBLE** (smaller)
- Nav icons: **VISIBLE** (smaller)
- Hamburger menu: **HIDDEN**
- Categories grid: **4 columns**
- Products grid: **3 columns**

### Desktop (> 1024px)
- Top bar: **VISIBLE** (normal)
- Search bar: **VISIBLE** (full width)
- Nav icons: **VISIBLE** (normal)
- Hamburger menu: **HIDDEN**
- Categories grid: **5 columns**
- Products grid: **4 columns**

---

## STEP 5: CSS Features Included

✅ **CSS-Only Hamburger Menu** - No JavaScript required
✅ **Smooth Animations** - Menu slides smoothly
✅ **Responsive Navbar** - Adjusts for all screen sizes
✅ **Responsive Grid** - Products and categories adapt
✅ **Mobile-First** - Optimized for phones first
✅ **Touch-Friendly** - Larger buttons on mobile
✅ **Font Scaling** - Text sizes adjust per viewport
✅ **Landscape Support** - Works in landscape mode

---

## QUICK TEST

To test your responsive design:

1. **Open any page** in your browser
2. **Press F12** to open Developer Tools
3. **Click the device icon** (Responsive Design Mode)
4. **Test at different widths:**
   - 375px (iPhone SE)
   - 768px (iPad)
   - 1440px (Desktop)

---

## FILES CREATED/MODIFIED

- ✅ **css/responsive.css** - NEW (Main responsive stylesheet)
- ✅ Update all **PHP files** - Add responsive CSS link + update navbar HTML

---

## NOTES

- All styling is **purely CSS** (no JavaScript)
- The hamburger menu uses CSS checkbox trick
- Hamburger icon animates smoothly when clicked
- Mobile menu closes automatically when you click a link (you may add JS for this later)
- All breakpoints are mobile-first approach
- Fully compatible with existing CSS files

---
