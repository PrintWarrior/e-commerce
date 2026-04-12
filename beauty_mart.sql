-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 12, 2026 at 07:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `beauty_mart`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT NULL COMMENT 'e.g. Home, Office',
  `address_details` text DEFAULT NULL COMMENT 'Unit/house no., street, landmark',
  `barangay` varchar(100) DEFAULT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `customer_id`, `label`, `address_details`, `barangay`, `municipality`, `province`, `zip_code`, `created_at`, `updated_at`) VALUES
(2, 1, 'Home', 'Purok 4', 'Paiton', 'Tangub City', 'Misamis Occidental', '7214', '2026-04-12 02:51:44', '2026-04-12 02:51:44'),
(3, 2, 'Home', 'Test Address Details', 'Test Barangay', 'Test Municipality', 'Test Province', '0000', '2026-04-12 03:03:02', '2026-04-12 03:03:02');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `user_id`, `created_at`) VALUES
(1, 2, '2026-04-09 02:39:35');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `customer_id`, `product_id`, `quantity`, `created_at`) VALUES
(9, 2, 15, 1, '2026-04-12 03:25:05');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Skincare', 'Products that care for and treat the skin'),
(2, 'Makeup', 'Cosmetics and colour products'),
(3, 'Bath & Body', 'Products specifically for body wash'),
(4, 'Tools & Accessories', 'Tools & Accessories for beauty'),
(5, 'Fragrances', 'Products for body musk'),
(6, 'Men\'s Grooming', 'Products for men grooming'),
(7, 'Haircare', 'Products for healthy hair');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `default_address_id` int(11) DEFAULT NULL COMMENT 'FK to addresses.id — preferred shipping address',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `phone`, `default_address_id`, `created_at`) VALUES
(1, 3, '01234567890', 2, '2026-04-11 04:04:18'),
(2, 5, '09123654789', 3, '2026-04-12 02:57:56');

-- --------------------------------------------------------

--
-- Table structure for table `deletion_requests`
--

CREATE TABLE `deletion_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','declined','cancelled') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deletion_requests`
--

INSERT INTO `deletion_requests` (`id`, `user_id`, `reason`, `status`, `admin_notes`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 5, 'No longer need the account', 'pending', NULL, NULL, NULL, '2026-04-12 03:14:37', '2026-04-12 03:14:37');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `notification_type_id` int(11) NOT NULL COMMENT 'FK to notification_types.id',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `notification_type_id`, `is_read`, `created_at`) VALUES
(1, 2, 'User TestCustomer (testcustomer@gmail.com) has requested account deletion. Reason: No longer need the account', 3, 1, '2026-04-12 03:14:37');

-- --------------------------------------------------------

--
-- Table structure for table `notification_types`
--

CREATE TABLE `notification_types` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL COMMENT 'Machine-readable key, e.g. new_user',
  `label` varchar(100) NOT NULL COMMENT 'Human-readable label',
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_types`
--

INSERT INTO `notification_types` (`id`, `code`, `label`, `description`) VALUES
(1, 'new_user', 'New user registered', NULL),
(2, 'seller_application', 'Seller application submitted', NULL),
(3, 'deletion_request', 'Account deletion requested', NULL),
(4, 'new_seller', 'New seller approved', NULL),
(5, 'system', 'System', NULL),
(6, 'payout', 'Payout', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address_id` int(11) NOT NULL COMMENT 'FK to addresses.id — snapshot row created at checkout',
  `status` enum('pending','processing','shipped','delivered','completed','cancelled','paid') DEFAULT 'pending',
  `payment_method_id` int(11) DEFAULT NULL COMMENT 'FK to payment_methods.id',
  `tracking_number` varchar(100) DEFAULT NULL,
  `hidden_from_customer` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `total_amount`, `shipping_address_id`, `status`, `payment_method_id`, `tracking_number`, `hidden_from_customer`, `created_at`, `updated_at`) VALUES
(2, 2, 25.00, 3, 'pending', 3, NULL, 0, '2026-04-12 03:03:53', '2026-04-12 03:03:53'),
(4, 2, 101.00, 3, 'cancelled', 3, NULL, 1, '2026-04-12 03:08:22', '2026-04-12 03:09:37'),
(5, 2, 101.00, 3, 'cancelled', 3, NULL, 0, '2026-04-12 03:09:53', '2026-04-12 03:10:03'),
(6, 2, 101.00, 3, 'completed', 3, '000123456', 0, '2026-04-12 03:11:08', '2026-04-12 03:12:06'),
(7, 1, 1250.00, 2, 'completed', 3, '0005678', 0, '2026-04-12 03:18:10', '2026-04-12 03:19:58');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL COMMENT 'Snapshot of price at time of order'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(2, 2, 19, 1, 25.00),
(4, 4, 22, 1, 101.00),
(5, 5, 22, 1, 101.00),
(6, 6, 22, 1, 101.00),
(7, 7, 17, 1, 750.00),
(8, 7, 20, 1, 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Replaces email FK for stability',
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 5, 'cf969259d6336a00084efcfa12ebeb8fa2d540186428d46c3312bc6c79fd90ac', '2026-04-12 04:15:17', '2026-04-12 03:15:17');

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'e.g. GCash, Maya, COD, Bank Transfer',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `description`, `is_active`) VALUES
(1, 'GCash', 'GCash mobile wallet', 1),
(2, 'Maya', 'Maya (PayMaya) mobile wallet', 1),
(3, 'COD', 'Cash on delivery', 1),
(4, 'Bank Transfer', 'Direct bank transfer', 1),
(5, 'Credit Card', 'Visa / Mastercard credit card', 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT 'default.jpg' COMMENT 'Primary thumbnail; additional images in product_images',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `category_id`, `name`, `description`, `price`, `stock`, `image`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Elixbloom Bio-Collagen Real Deep Mask', 'It is a specialized facial sheet mask designed for intensive skincare. Unlike standard thin cotton masks, these \"bio-collagen\" masks are often made of a solidified essence that starts out opaque and typically becomes transparent as the skin absorbs the nutrients.', 450.00, 10, '1775891352_669023643_2777147012640341_8314684180057227294_n.jpg', '2026-04-11 07:09:12', '2026-04-11 07:09:12'),
(2, 1, 1, 'Medicube PDRN Pink Peptide Serum', 'Excellent for healing the skin barrier or fading post-acne marks, works on elasticity and \"plumping\" the skin from the inside out, has a slightly viscous, silky texture that leaves a dewy finish without being overly greasy.', 500.00, 15, '1775891456_648895728_2040302510255551_361545360364398000_n.jpg', '2026-04-11 07:10:56', '2026-04-11 07:10:56'),
(3, 1, 1, 'SKIN1004', 'A large bottle of hydrating toner that preps the skin. Like the serum we talked about, it uses salmon-derived DNA (PDRN) to repair the skin barrier, essentially the concentrated version of the serum. It’s used to target dullness and loss of elasticity.', 850.00, 5, '1775891886_663725763_1293320436077027_1107736041531920453_n.jpg', '2026-04-11 07:18:06', '2026-04-11 07:26:30'),
(4, 1, 7, 'Nuvé Shampoo Conditioner set', 'products are Sulphate-Free, Vegan, and use 100% Natural Fragrance. Sulphate-free formulas are great because they cleanse without stripping the natural oils from your hair, making them ideal for color-treated or dry hair.', 357.00, 7, '1775892532_662353419_1237481921488400_5024791025081148446_n.jpg', '2026-04-11 07:28:52', '2026-04-11 07:28:52'),
(5, 1, 7, 'Nutra Harmony', 'Work together to reinforce the hair shaft and promote thickness, helps with hair elasticity (less snapping when you brush!)', 489.00, 17, '1775892659_667625014_897880443277103_5359000086498027090_n.jpg', '2026-04-11 07:30:59', '2026-04-11 07:30:59'),
(6, 1, 7, 'Kérastase Paris', 'A high-end, professional-grade hair care routine focused on achieving high shine (the \"gloss\" effect) and smoothness.', 888.00, 20, '1775892789_667598573_1311586910880097_3320470117391099289_n.jpg', '2026-04-11 07:33:09', '2026-04-11 07:33:09'),
(7, 1, 5, 'Giorgio Armani \"My Way\" Eau de Parfum', 'Shimmering, pearlescent liquid inside, while the scent profile remains the same as the classic My Way, it includes fine silk-like pearls that leave a subtle glow on your skin when sprayed.', 999.00, 12, '1775893017_663679916_2821258378252206_1662661140127997948_n.jpg', '2026-04-11 07:36:57', '2026-04-11 07:36:57'),
(8, 1, 5, 'Miss Dior Blooming Bouquet', 'Softer, more delicate cousin. It is often described as a \"sparkling\" floral fragrance that feels like a fresh bouquet of spring flowers.', 326.00, 15, '1775893130_599873079_2285141815327113_420567308875245626_n.jpg', '2026-04-11 07:38:50', '2026-04-11 07:38:50'),
(9, 1, 5, 'Chanel Coco Mademoiselle', 'Ultimate \"boss girl\" fragrance. It’s a sophisticated Amber-Floral that feels much more grounded and classic, captures the \"Old Money\" or \"Classic Elegance\" aesthetic perfectly. Surrounding it with pearls, gold silk, and a Chanel lipstick highlights that this isn\'t just a perfume; it’s a status symbol of timeless French style.', 400.00, 23, '1775893346_650014201_2073181036592663_7980717092869646644_n.jpg', '2026-04-11 07:42:26', '2026-04-11 07:42:26'),
(10, 1, 6, 'ClarinsMen', 'A high-performance shaving gel that softens facial hair and protects the skin from razor burn, splash-on liquid that provides an \"intense freshness\" (as noted by the snowflake icon). It helps tighten pores and disinfect any micro-cuts.', 250.00, 3, '1775893587_664833603_920591150873668_6053465436097034809_n.jpg', '2026-04-11 07:46:27', '2026-04-11 07:46:27'),
(11, 1, 6, 'Mr. Jones Body Moisturiser', 'Marketed with three main goals: Nourish, Hydrate, and Repair. * Texture: As you can see from the swatch on the arm, it has a thick, creamy consistency. Despite looking rich, luxury men\'s body lotions are usually formulated to be \"fast-absorbing\" so they don\'t feel greasy under clothes.', 299.00, 19, '1775893661_655279246_1855396401836163_7898389392906156820_n.jpg', '2026-04-11 07:47:41', '2026-04-11 07:47:41'),
(12, 1, 6, 'Jovees Herbal Men Essential Advanced 4-in-1 Moisturising Face Wash', 'A big plus for those looking to avoid synthetic preservatives, usually incorporates ingredients like Neem, Aloe Vera, or Charcoal in their men\'s line to balance oil and prevent breakouts.', 724.00, 11, '1775893762_663767328_952552890971856_1607170772744202876_n.jpg', '2026-04-11 07:49:22', '2026-04-11 08:13:38'),
(13, 1, 3, 'Neogen Dermalogy Coconut Milk Pure Mild Cleanser', 'Coconut Milk Cleanser first to wash away impurities, as a creamy, milky liquid and transforms into a soft foam when mixed with water.', 399.00, 0, '1775893905_663914925_1297487738928762_9170207716882003591_n.jpg', '2026-04-11 07:51:45', '2026-04-11 07:51:45'),
(14, 1, 3, 'Elorea Grapefruit Body Scrub', 'Provides the \"grit\" needed to buff away dead skin cells and smooth out rough patches (like elbows and knees), naturally high in antioxidants and Vitamin C, which can help brighten the skin’s appearance.', 694.00, 18, '1775894064_650273863_1972096183695375_8754328323479048120_n.jpg', '2026-04-11 07:54:24', '2026-04-11 07:54:24'),
(15, 1, 3, 'Sephora Collection All-over Solid Cleanser', 'Head-to-toe\" product. It is formulated to be gentle enough for the face, effective for the body, and can even be used on the hair in a pinch, known for its nourishing and comforting properties. It’s designed to soothe the skin while cleansing, rather than leaving it feeling \"squeaky\" and dry.', 500.00, 16, '1775894254_662315623_1512553426970061_3557366965381366101_n.jpg', '2026-04-11 07:57:34', '2026-04-11 07:57:34'),
(16, 1, 2, 'Dior Backstage Glow Face Palette', 'frosty white for intense highlighting on the high points of the face, warm gold that works beautifully on the cheekbones or as an eyeshadow.', 2000.00, 20, '1775894406_663774174_972663479032942_6804486464091375151_n.jpg', '2026-04-11 08:00:06', '2026-04-11 08:00:06'),
(17, 1, 2, 'Dior Forever Skin Glow duo', 'A long-wear foundation that offers medium-to-full coverage with a hydrated, luminous finish. It’s famous for its \"86% floral skincare base,\" meaning it’s packed with ingredients like iris, wild pansy, and hibiscus to keep your skin hydrated while you wear it.', 750.00, 16, '1775894474_663709931_930175443230818_6025311504035769647_n.jpg', '2026-04-11 08:01:14', '2026-04-12 03:18:10'),
(18, 1, 2, 'Dior Backstage Face & Body Foundations', 't has an ultra-fluid, watery texture that feels like nothing on the skin. It provides a natural, luminous matte finish that is extremely difficult to detect, even in person, suggests, it’s designed to be used everywhere. Because it’s waterproof and sweat-resistant, makeup artists use it to even out skin on the neck, shoulders, and legs without it rubbing off on clothes.', 852.00, 12, '1775894546_667071935_945793788297716_1435160138817010985_n.jpg', '2026-04-11 08:02:26', '2026-04-11 08:02:26'),
(19, 1, 4, 'Dior Star Hair Clips', 'Feature the Christian Dior logo alongside the signature Dior Star. Monsieur Dior was very superstitious and considered the star his \"lucky charm\" after finding a metal star on the ground right before he opened his couture house.', 25.00, 99, '1775894659_667071935_1766992101373774_7892585715104817566_n.jpg', '2026-04-11 08:04:19', '2026-04-12 03:20:25'),
(20, 1, 4, 'Dior x Dyson', 'Custom-skinned version of what looks like a Dyson Supersonic. It’s designed for fast drying without extreme heat, protecting the \"Glass Hair\" shine you’d get from your Kérastase products', 500.00, 49, '1775894805_667607058_2014823725994062_9045540055757760503_n.jpg', '2026-04-11 08:06:45', '2026-04-12 03:18:10'),
(21, 1, 4, 'Hair Styling & Maintenance Kit', 'A sleek, brushless motor dryer designed for ultra-fast drying while minimizing heat damage, flat iron with floating plates, perfect for that \"Glass Hair\" look.', 233.00, 100, '1775894903_668909277_1467740201665003_1169938576115543829_n.jpg', '2026-04-11 08:08:23', '2026-04-11 08:08:23'),
(22, 2, 3, 'Test Product', 'Test Description', 101.00, 0, '1775963142_Screenshot 2026-03-27 085944.png', '2026-04-12 03:05:20', '2026-04-12 03:18:49');

-- --------------------------------------------------------

--
-- Table structure for table `sellers`
--

CREATE TABLE `sellers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(100) NOT NULL,
  `business_address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sellers`
--

INSERT INTO `sellers` (`id`, `user_id`, `business_name`, `business_address`, `phone`, `tax_id`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 4, 'Beauty Mart', 'Paiton, Purok 4', '09874563211', '000333666', 1, '2026-04-11 04:23:33', '2026-04-11 04:23:33', '2026-04-11 06:15:12'),
(2, 6, 'Test Business', 'Test Business Address', '01234567891', '000333999', NULL, '2026-04-12 02:59:22', '2026-04-12 02:59:22', '2026-04-12 02:59:22');

-- --------------------------------------------------------

--
-- Table structure for table `seller_applications`
--

CREATE TABLE `seller_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(100) NOT NULL COMMENT 'Intentional snapshot at application time',
  `business_address` text NOT NULL COMMENT 'Intentional snapshot at application time',
  `phone` varchar(20) NOT NULL COMMENT 'Intentional snapshot at application time',
  `tax_id` varchar(50) DEFAULT NULL COMMENT 'Intentional snapshot at application time',
  `status` enum('pending','approved','declined') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_applications`
--

INSERT INTO `seller_applications` (`id`, `user_id`, `business_name`, `business_address`, `phone`, `tax_id`, `status`, `admin_notes`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 7, 'Test', 'Test', '01234567891', '222444888', 'pending', NULL, NULL, NULL, '2026-04-12 03:27:05', '2026-04-12 03:27:05');

-- --------------------------------------------------------

--
-- Table structure for table `seller_earnings`
--

CREATE TABLE `seller_earnings` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_earnings`
--

INSERT INTO `seller_earnings` (`id`, `seller_id`, `order_id`, `amount`, `status`, `created_at`) VALUES
(2, 2, 6, 101.00, 'paid', '2026-04-12 03:12:06'),
(3, 1, 7, 1250.00, 'paid', '2026-04-12 03:19:58');

-- --------------------------------------------------------

--
-- Table structure for table `seller_payouts`
--

CREATE TABLE `seller_payouts` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method_id` int(11) DEFAULT NULL COMMENT 'FK to payment_methods.id',
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_payouts`
--

INSERT INTO `seller_payouts` (`id`, `seller_id`, `amount`, `payment_method_id`, `status`, `transaction_id`, `requested_at`, `processed_at`) VALUES
(3, 2, 100.00, 1, 'processing', NULL, '2026-04-12 03:16:13', NULL),
(4, 1, 250.00, 1, 'completed', '956', '2026-04-12 03:21:13', '2026-04-12 03:22:22'),
(5, 1, 100.00, 1, 'completed', '256', '2026-04-12 04:15:28', '2026-04-12 04:21:01'),
(6, 1, 100.00, 1, 'completed', '589', '2026-04-12 04:20:07', '2026-04-12 04:20:46'),
(7, 1, 100.00, 1, 'completed', '885', '2026-04-12 04:48:36', '2026-04-12 04:48:55'),
(8, 1, 100.00, 1, 'completed', '123', '2026-04-12 04:51:54', '2026-04-12 04:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `superadmins`
--

CREATE TABLE `superadmins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `superadmins`
--

INSERT INTO `superadmins` (`id`, `user_id`, `created_at`) VALUES
(1, 1, '2026-04-10 01:37:48');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`, `created_at`) VALUES
(1, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '::1', '2026-04-12 02:51:02'),
(26, 2, 'seller_payout_updated', 'seller_payouts', 4, 'Admin set payout #4 for seller Beauty Mart to completed.', '::1', '2026-04-12 03:22:22'),
(27, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '::1', '2026-04-12 03:23:34'),
(28, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '::1', '2026-04-12 03:24:15'),
(29, 5, 'customer_logged_in', 'users', 5, 'Customer TestCustomer logged in successfully.', '::1', '2026-04-12 03:24:54'),
(30, 5, 'customer_logged_out', 'users', 5, 'Customer TestCustomer logged out.', '::1', '2026-04-12 03:25:41'),
(31, 2, 'seller_payout_updated', 'seller_payouts', 6, 'Admin set payout #6 for seller Beauty Mart to completed.', '::1', '2026-04-12 04:20:46'),
(32, 2, 'seller_payout_updated', 'seller_payouts', 5, 'Admin set payout #5 for seller Beauty Mart to completed.', '::1', '2026-04-12 04:21:01'),
(33, 2, 'seller_payout_updated', 'seller_payouts', 3, 'Admin set payout #3 for seller Test Business to processing.', '::1', '2026-04-12 04:21:06'),
(34, 2, 'seller_payout_updated', 'seller_payouts', 7, 'Admin set payout #7 for seller Beauty Mart to completed.', '::1', '2026-04-12 04:48:55'),
(35, 2, 'seller_payout_updated', 'seller_payouts', 8, 'Admin set payout #8 for seller Beauty Mart to processing.', '::1', '2026-04-12 04:52:25'),
(36, 2, 'seller_payout_updated', 'seller_payouts', 8, 'Admin set payout #8 for seller Beauty Mart to completed.', '::1', '2026-04-12 04:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `marked_for_deletion` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `status` enum('active','suspended','banned') DEFAULT 'active',
  `suspended_until` datetime DEFAULT NULL,
  `banned_reason` text DEFAULT NULL,
  `action_by` int(11) DEFAULT NULL COMMENT 'FK to admins.id — admin who last changed this user status',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `username`, `email`, `password`, `profile_pic`, `email_verified`, `marked_for_deletion`, `deleted_at`, `verification_token`, `status`, `suspended_until`, `banned_reason`, `action_by`, `created_at`, `updated_at`) VALUES
(1, 'super', 'admin', 'superadmin', 'superadmin@beautymart.com', '$2y$10$N2BPGDSrNh8YB1ncx5IsJu2SKsfIxBdMzzuhpQac.PWGmUVkEBiCa', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-10 01:37:27', '2026-04-11 15:15:25'),
(2, 'ad', 'min', 'admin', 'admin@beautymart.com', '$2y$10$pG61p6jttuuKRjK28y7Paud1YdZc9CFMZIMIgeqmBtHKdudjgggK6', '1775879952_9ce3f6bb8bceaf380976f30b74e55c538f7a35ca.png', 1, 0, NULL, NULL, 'active', NULL, NULL, 1, '2026-04-08 16:57:51', '2026-04-11 15:15:48'),
(3, 'Jayle Luza', 'Talan', 'Jayle', 'luza@beautymart.com', '$2y$10$UOlbrLprJ3lFE.OEzRankOmhKUb/W66qmRSF/y7/O/3b5oFBihADO', '1775880579_9ce3f6bb8bceaf380976f30b74e55c538f7a35ca.png', 1, 0, NULL, NULL, 'active', NULL, NULL, 1, '2026-04-11 04:04:17', '2026-04-11 15:16:51'),
(4, 'Jayle Luza', 'Talan', 'Naniw', 'jayle@beautymart.com', '$2y$10$bbRvjZ58A0w1VtGWnNjV2O0U/hs8LiqawWmQK1haM7yLbkFw5Czw2', '1775888025_9ce3f6bb8bceaf380976f30b74e55c538f7a35ca.png', 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-11 04:23:33', '2026-04-11 06:13:45'),
(5, 'Test', 'Customer', 'TestCustomer', 'testcustomer@gmail.com', '$2y$10$NhBWLlSw8HshDSafqIAUaeu4E34DJ3WBf2EIXKJTARhx/JC7co2ce', '1775963013_Screenshot 2026-03-27 085944.png', 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-12 02:57:56', '2026-04-12 03:03:33'),
(6, 'Test', 'Seller', 'TestSeller', 'testseller@gmail.com', '$2y$10$QjpEOwTb5NKpjvr6WRO2luikBcSww6DmhZDa5WUr1FnJyFyfG0YP.', '1775962889_Screenshot 2026-03-27 085944.png', 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-12 02:59:22', '2026-04-12 03:01:29'),
(7, 'Test', 'Test', 'Test', 'test@gmail.com', '$2y$10$4aqTtmQ7oluX8t8SPWHzNOGSsk4ADZH5xuLErNiQ9NzpYqIKdLHDG', NULL, 0, 0, NULL, '8329bef2d762cc06c5d079ab50471e7a3fe9af17efdc7d251057c19ae4649e57', 'active', NULL, NULL, NULL, '2026-04-12 03:27:05', '2026-04-12 03:27:05');

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlists`
--

INSERT INTO `wishlists` (`id`, `customer_id`, `product_id`, `created_at`) VALUES
(1, 1, 16, '2026-04-12 02:55:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_addresses_customer` (`customer_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admins_user` (`user_id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart_item` (`customer_id`,`product_id`),
  ADD KEY `fk_carts_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_categories_name` (`name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_customers_user` (`user_id`),
  ADD KEY `fk_customers_default_address` (`default_address_id`);

--
-- Indexes for table `deletion_requests`
--
ALTER TABLE `deletion_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_del_req_user` (`user_id`),
  ADD KEY `fk_del_req_reviewed_by` (`reviewed_by`),
  ADD KEY `idx_del_req_status` (`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_user` (`user_id`),
  ADD KEY `fk_notifications_type` (`notification_type_id`);

--
-- Indexes for table `notification_types`
--
ALTER TABLE `notification_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_notification_types_code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_customer` (`customer_id`),
  ADD KEY `fk_orders_address` (`shipping_address_id`),
  ADD KEY `fk_orders_payment_method` (`payment_method_id`),
  ADD KEY `idx_orders_status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_product` (`product_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pr_token` (`token`),
  ADD KEY `fk_pr_user` (`user_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payment_methods_name` (`name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_seller` (`seller_id`),
  ADD KEY `fk_products_category` (`category_id`);

--
-- Indexes for table `sellers`
--
ALTER TABLE `sellers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sellers_user` (`user_id`),
  ADD KEY `fk_sellers_approved_by` (`approved_by`);

--
-- Indexes for table `seller_applications`
--
ALTER TABLE `seller_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_seller_app_user` (`user_id`),
  ADD KEY `fk_seller_app_reviewed_by` (`reviewed_by`),
  ADD KEY `idx_seller_app_status` (`status`);

--
-- Indexes for table `seller_earnings`
--
ALTER TABLE `seller_earnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_seller_earnings_seller` (`seller_id`),
  ADD KEY `fk_seller_earnings_order` (`order_id`);

--
-- Indexes for table `seller_payouts`
--
ALTER TABLE `seller_payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_seller_payouts_seller` (`seller_id`),
  ADD KEY `fk_seller_payouts_payment_method` (`payment_method_id`);

--
-- Indexes for table `superadmins`
--
ALTER TABLE `superadmins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_superadmins_user` (`user_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `fk_users_action_by` (`action_by`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`customer_id`,`product_id`),
  ADD KEY `fk_wishlists_product` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `deletion_requests`
--
ALTER TABLE `deletion_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `notification_types`
--
ALTER TABLE `notification_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `sellers`
--
ALTER TABLE `sellers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `seller_applications`
--
ALTER TABLE `seller_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `seller_earnings`
--
ALTER TABLE `seller_earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `seller_payouts`
--
ALTER TABLE `seller_payouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `superadmins`
--
ALTER TABLE `superadmins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carts_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customers_ibfk_2` FOREIGN KEY (`default_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `deletion_requests`
--
ALTER TABLE `deletion_requests`
  ADD CONSTRAINT `deletion_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deletion_requests_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`notification_type_id`) REFERENCES `notification_types` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`shipping_address_id`) REFERENCES `addresses` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sellers`
--
ALTER TABLE `sellers`
  ADD CONSTRAINT `sellers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sellers_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `seller_applications`
--
ALTER TABLE `seller_applications`
  ADD CONSTRAINT `seller_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seller_applications_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `seller_earnings`
--
ALTER TABLE `seller_earnings`
  ADD CONSTRAINT `seller_earnings_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seller_earnings_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_payouts`
--
ALTER TABLE `seller_payouts`
  ADD CONSTRAINT `seller_payouts_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seller_payouts_ibfk_2` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `superadmins`
--
ALTER TABLE `superadmins`
  ADD CONSTRAINT `superadmins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `fk_system_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_action_by` FOREIGN KEY (`action_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
