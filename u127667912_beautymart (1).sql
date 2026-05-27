-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 27, 2026 at 02:17 PM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u127667912_beautymart`
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
(2, 1, 'Home', 'purok2', 'paiton', 'tangub city', 'mis. occ', '7214', '2026-04-12 02:51:44', '2026-05-26 03:38:52'),
(3, 2, 'Home', 'Test Address Details', 'Test Barangay', 'Test Municipality', 'Test Province', '0000', '2026-04-12 03:03:02', '2026-04-12 03:03:02'),
(4, 7, 'Home', 'purok4', 'labuyo', 'tangub', 'mis.occ', '7214', '2026-04-16 01:19:41', '2026-04-16 01:19:41'),
(5, 55, 'Home', 'purok2', 'gata dako', 'clarin', 'mis occ', '7214', '2026-05-22 02:27:55', '2026-05-22 02:27:55'),
(6, 56, 'Home', 'purok1', 'aquino', 'tangub city', 'mis occ', '2332', '2026-05-22 06:46:03', '2026-05-23 01:25:04'),
(7, 60, 'Home', 'purok2', 'labuyo', 'tangub', 'mis occ', '7214', '2026-05-26 07:17:42', '2026-05-26 07:17:42');

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
(1, 2, '2026-04-09 02:39:35'),
(5, 117, '2026-05-26 07:27:37'),
(6, 116, '2026-05-26 07:29:05');

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
(9, 2, 15, 1, '2026-04-12 03:25:05'),
(30, 58, 50, 1, '2026-05-23 04:12:33'),
(31, 58, 18, 1, '2026-05-23 04:13:13');

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
(2, 5, '09123654789', 3, '2026-04-12 02:57:56'),
(4, 10, NULL, NULL, '2026-04-13 04:25:32'),
(5, 11, NULL, NULL, '2026-04-13 04:33:11'),
(6, 12, NULL, NULL, '2026-04-15 07:03:17'),
(7, 4, '0942568912', 4, '2026-04-16 01:16:22'),
(9, 14, NULL, NULL, '2026-04-30 10:34:05'),
(11, 17, NULL, NULL, '2026-04-30 10:41:04'),
(12, 19, NULL, NULL, '2026-04-30 10:49:53'),
(13, 20, NULL, NULL, '2026-04-30 11:00:47'),
(14, 23, NULL, NULL, '2026-04-30 13:20:12'),
(15, 24, NULL, NULL, '2026-04-30 13:52:22'),
(16, 25, NULL, NULL, '2026-04-30 14:04:24'),
(17, 26, NULL, NULL, '2026-04-30 14:06:45'),
(19, 28, NULL, NULL, '2026-04-30 14:08:15'),
(20, 29, NULL, NULL, '2026-04-30 14:09:25'),
(21, 30, NULL, NULL, '2026-04-30 14:10:30'),
(22, 31, NULL, NULL, '2026-04-30 14:11:25'),
(23, 32, NULL, NULL, '2026-04-30 14:12:14'),
(25, 34, NULL, NULL, '2026-04-30 14:14:16'),
(26, 35, NULL, NULL, '2026-04-30 14:15:20'),
(27, 36, NULL, NULL, '2026-04-30 14:16:05'),
(28, 37, NULL, NULL, '2026-04-30 14:16:50'),
(29, 38, NULL, NULL, '2026-04-30 14:17:34'),
(30, 39, NULL, NULL, '2026-04-30 14:18:19'),
(31, 40, NULL, NULL, '2026-04-30 14:18:59'),
(33, 42, NULL, NULL, '2026-04-30 14:22:01'),
(34, 43, NULL, NULL, '2026-04-30 14:22:44'),
(35, 44, NULL, NULL, '2026-04-30 14:24:36'),
(36, 45, NULL, NULL, '2026-05-01 03:02:09'),
(37, 46, NULL, NULL, '2026-05-01 03:03:21'),
(38, 47, NULL, NULL, '2026-05-01 03:05:59'),
(39, 48, NULL, NULL, '2026-05-01 03:06:44'),
(40, 49, NULL, NULL, '2026-05-01 03:07:35'),
(41, 50, NULL, NULL, '2026-05-01 03:08:26'),
(42, 51, NULL, NULL, '2026-05-01 03:10:35'),
(43, 52, NULL, NULL, '2026-05-01 03:11:28'),
(44, 53, NULL, NULL, '2026-05-01 03:12:59'),
(45, 54, NULL, NULL, '2026-05-01 03:13:35'),
(46, 55, NULL, NULL, '2026-05-01 03:14:14'),
(47, 56, NULL, NULL, '2026-05-01 03:14:56'),
(48, 57, NULL, NULL, '2026-05-01 03:19:53'),
(49, 58, NULL, NULL, '2026-05-01 03:20:46'),
(50, 59, NULL, NULL, '2026-05-01 03:21:31'),
(51, 60, NULL, NULL, '2026-05-01 03:22:14'),
(52, 61, NULL, NULL, '2026-05-01 03:23:06'),
(55, 110, '0927465657', 5, '2026-05-22 01:44:47'),
(56, 111, '09786441123', 6, '2026-05-22 06:41:33'),
(58, 114, NULL, NULL, '2026-05-23 04:08:07'),
(60, 116, '0977777', 7, '2026-05-26 07:12:56');

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
(1, 2, 'User TestCustomer (testcustomer@gmail.com) has requested account deletion. Reason: No longer need the account', 3, 1, '2026-04-12 03:14:37'),
(23, 2, 'New customer registered: Ace (xavierazcona0422@gmail.com) awaiting email verification from admin.', 1, 1, '2026-04-12 06:32:11'),
(24, 2, 'Verification email resent to Ace (xavierazcona0422@gmail.com) by admin', 5, 1, '2026-04-12 06:33:14'),
(25, 2, 'New seller registration: Clark (gamingxac@gmail.com) - Business: None. Awaiting email verification from admin and seller application review.', 4, 1, '2026-04-12 06:36:13'),
(26, 2, 'Application #2 approved for Clark.', 2, 1, '2026-04-12 06:36:41'),
(27, 2, 'Application #1 declined for Test.', 2, 1, '2026-04-12 06:36:52'),
(28, 6, 'Your payout request for PHP 100.00 could not be completed. Please contact admin for verification.', 6, 0, '2026-04-12 06:37:01'),
(29, 2, 'Payout request #3 updated to failed.', 6, 1, '2026-04-12 06:37:01'),
(30, 2, 'Verification email resent to Clark (gamingxac@gmail.com) by admin', 5, 1, '2026-04-12 06:37:45'),
(32, 2, 'New customer registered: acer (lordjemsaavedra@gmail.com) awaiting email verification from admin.', 1, 1, '2026-04-13 04:25:32'),
(33, 2, 'Verification email resent to acer (lordjemsaavedra@gmail.com) by admin', 5, 1, '2026-04-13 04:27:36'),
(34, 10, 'Welcome to Beauty Mart! Your email has been verified.', 5, 1, '2026-04-13 04:28:20'),
(35, 2, 'New customer registered: Haha (xavierazcona0422@gmail.com) awaiting email verification from admin.', 1, 1, '2026-04-13 04:33:11'),
(36, 2, 'Verification email resent to Haha (xavierazcona0422@gmail.com) by admin', 5, 1, '2026-04-13 04:33:39'),
(37, 11, 'Welcome to Beauty Mart! Your email has been verified.', 5, 1, '2026-04-13 04:33:58'),
(38, 2, 'New customer registered: Frelyn_ (talanefrelyn@gmail.com) awaiting email verification from admin.', 1, 1, '2026-04-15 07:03:17'),
(39, 2, 'Verification email resent to Frelyn_ (talanefrelyn@gmail.com) by admin', 5, 1, '2026-04-15 07:03:36'),
(40, 2, 'Verification email resent to Frelyn_ (talanefrelyn@gmail.com) by admin', 5, 1, '2026-04-15 07:03:40'),
(41, 2, 'Verification email resent to Frelyn_ (talanefrelyn@gmail.com) by admin', 5, 1, '2026-04-15 07:04:29'),
(42, 2, 'New customer registered: ed (edelberto.iyog@nmsc.edu.ph) awaiting email verification from admin.', 1, 1, '2026-05-21 02:51:01'),
(43, 2, 'Verification email resent to ed (edelberto.iyog@nmsc.edu.ph) by admin', 5, 1, '2026-05-21 02:55:37'),
(44, 2, 'New customer registered: jas (calunsagrodelyn939@gmail.com) awaiting email verification from admin.', 1, 1, '2026-05-22 01:44:47'),
(45, 2, 'Verification email resent to jas (calunsagrodelyn939@gmail.com) by admin', 5, 1, '2026-05-22 02:10:45'),
(46, 2, 'Verification email resent to jas (calunsagrodelyn939@gmail.com) by admin', 5, 1, '2026-05-22 02:19:51'),
(47, 2, 'Verification email resent to jas (calunsagrodelyn939@gmail.com) by admin', 5, 1, '2026-05-22 02:20:15'),
(48, 2, 'Verification email resent to jas (calunsagrodelyn939@gmail.com) by admin', 5, 1, '2026-05-22 02:20:20'),
(49, 2, 'Verification email resent to jas (calunsagrodelyn939@gmail.com) by admin', 5, 1, '2026-05-22 02:20:58'),
(50, 2, 'Verification email resent to jas (calunsagrodelyn939@gmail.com) by admin', 5, 1, '2026-05-22 02:24:44'),
(51, 110, 'Welcome to Beauty Mart! Your email has been verified.', 5, 1, '2026-05-22 02:25:04'),
(52, 2, 'New customer registered: jayl (jayle.talan@nmsc.edu.ph) awaiting email verification from admin.', 1, 0, '2026-05-22 06:41:33'),
(53, 2, 'Verification email resent to jayl (jayle.talan@nmsc.edu.ph) by admin', 5, 0, '2026-05-22 06:42:21'),
(54, 111, 'Welcome to Beauty Mart! Your email has been verified.', 5, 1, '2026-05-22 06:45:06'),
(55, 2, 'New customer registered: Ahh (internshipapplicationportal@gmail.com) awaiting email verification.', 1, 0, '2026-05-23 02:41:52'),
(56, 2, 'New customer registered: HA (internshipapplicationportal@gmail.com) awaiting email verification.', 1, 0, '2026-05-23 04:08:07'),
(57, 114, 'Welcome to Beauty Mart! Your email has been verified.', 5, 1, '2026-05-23 04:09:52'),
(58, 2, 'New customer registered: edward (jayle.talan@nmsc.edu.ph) awaiting email verification.', 1, 0, '2026-05-26 07:12:56'),
(59, 116, 'Welcome to Beauty Mart! Your email has been verified.', 5, 1, '2026-05-26 07:15:21');

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
(2, 2, 25.00, 3, 'cancelled', 3, NULL, 0, '2026-04-12 03:03:53', '2026-04-16 01:22:02'),
(4, 2, 101.00, 3, 'cancelled', 3, NULL, 1, '2026-04-12 03:08:22', '2026-04-12 03:09:37'),
(5, 2, 101.00, 3, 'cancelled', 3, NULL, 0, '2026-04-12 03:09:53', '2026-04-12 03:10:03'),
(6, 2, 101.00, 3, 'completed', 3, '000123456', 0, '2026-04-12 03:11:08', '2026-04-12 03:12:06'),
(7, 1, 1250.00, 2, 'completed', 3, '0005678', 0, '2026-04-12 03:18:10', '2026-04-12 03:19:58'),
(8, 7, 1085.00, 4, 'completed', 3, NULL, 0, '2026-04-16 01:20:13', '2026-04-16 01:21:41'),
(9, 1, 957.00, 2, 'completed', 3, NULL, 0, '2026-04-20 05:56:01', '2026-04-20 05:57:21'),
(10, 1, 3599.00, 2, 'pending', 3, NULL, 0, '2026-05-17 23:20:24', '2026-05-17 23:20:24'),
(11, 1, 852.00, 2, 'completed', 3, '100000', 0, '2026-05-22 01:02:15', '2026-05-26 08:34:34'),
(12, 55, 326.00, 5, 'cancelled', 3, NULL, 0, '2026-05-22 02:34:21', '2026-05-22 02:39:39'),
(13, 55, 326.00, 5, 'processing', 3, NULL, 0, '2026-05-22 02:35:02', '2026-05-22 02:45:10'),
(14, 56, 2450.00, 6, 'pending', 3, NULL, 0, '2026-05-22 06:51:41', '2026-05-22 06:51:41'),
(15, 56, 754.00, 6, 'pending', 3, NULL, 0, '2026-05-22 06:55:43', '2026-05-22 06:55:43'),
(16, 56, 999.00, 6, 'cancelled', 3, NULL, 0, '2026-05-23 01:25:19', '2026-05-23 01:27:14'),
(17, 56, 199.00, 6, 'pending', 3, NULL, 0, '2026-05-23 01:29:59', '2026-05-23 01:29:59'),
(18, 1, 1629.00, 2, 'pending', 3, NULL, 0, '2026-05-23 02:53:47', '2026-05-23 02:53:47'),
(19, 1, 2317.00, 2, 'pending', 3, NULL, 0, '2026-05-26 03:39:07', '2026-05-26 03:39:07'),
(20, 60, 2849.00, 7, 'completed', 3, '100000', 0, '2026-05-26 07:17:56', '2026-05-26 08:34:01'),
(21, 60, 1169.00, 7, 'processing', 3, NULL, 0, '2026-05-26 07:19:02', '2026-05-26 07:24:19'),
(22, 1, 5301.00, 2, 'pending', 3, NULL, 0, '2026-05-26 08:31:47', '2026-05-26 08:31:47'),
(23, 1, 1169.00, 2, 'pending', 3, NULL, 0, '2026-05-26 10:56:19', '2026-05-26 10:56:19'),
(24, 1, 798.00, 2, 'pending', 3, NULL, 0, '2026-05-26 10:59:14', '2026-05-26 10:59:14'),
(25, 1, 1100.00, 2, 'cancelled', 3, NULL, 0, '2026-05-26 11:01:00', '2026-05-26 11:01:13'),
(26, 1, 1997.00, 2, 'pending', 3, NULL, 0, '2026-05-26 11:27:52', '2026-05-26 11:27:52'),
(27, 1, 669.00, 2, 'shipped', 3, NULL, 0, '2026-05-27 11:45:45', '2026-05-27 11:58:27');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL COMMENT 'Snapshot of price at time of order',
  `status` enum('pending','processing','shipped','delivered','completed','cancelled') NOT NULL DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL
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
(8, 7, 20, 1, 500.00),
(9, 8, 18, 1, 852.00),
(10, 8, 21, 1, 233.00),
(11, 9, 12, 1, 724.00),
(12, 9, 21, 1, 233.00),
(13, 10, 36, 1, 2600.00),
(14, 10, 58, 1, 999.00),
(15, 11, 18, 1, 852.00),
(16, 12, 8, 1, 326.00),
(17, 13, 8, 1, 326.00),
(18, 14, 3, 2, 850.00),
(19, 14, 10, 3, 250.00),
(20, 15, 43, 1, 455.00),
(21, 15, 50, 1, 299.00),
(22, 16, 58, 1, 999.00),
(23, 17, 59, 1, 199.00),
(24, 18, 26, 1, 1129.00),
(25, 18, 52, 1, 500.00),
(26, 19, 33, 1, 1599.00),
(27, 19, 57, 2, 359.00),
(28, 20, 18, 1, 852.00),
(29, 20, 53, 2, 499.00),
(30, 20, 58, 1, 999.00),
(31, 21, 51, 1, 170.00),
(32, 21, 58, 1, 999.00),
(33, 22, 25, 1, 689.00),
(34, 22, 53, 1, 499.00),
(35, 22, 57, 1, 359.00),
(36, 22, 58, 1, 999.00),
(37, 22, 43, 1, 455.00),
(38, 22, 28, 1, 2300.00),
(39, 23, 58, 1, 999.00),
(40, 23, 51, 1, 170.00),
(41, 24, 53, 1, 499.00),
(42, 24, 50, 1, 299.00),
(43, 25, 2, 1, 500.00),
(44, 25, 40, 1, 600.00),
(45, 26, 15, 1, 500.00),
(46, 26, 24, 1, 499.00),
(47, 26, 23, 1, 599.00),
(48, 26, 45, 1, 399.00),
(49, 27, 59, 1, 199.00),
(50, 27, 49, 1, 470.00);

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
(1, 5, 'cf969259d6336a00084efcfa12ebeb8fa2d540186428d46c3312bc6c79fd90ac', '2026-04-12 04:15:17', '2026-04-12 03:15:17'),
(8, 110, '7253ea0d98ff0824096379cf06eb3b97fa3c72f583cd9a86cc3defc2ef743184', '2026-05-22 10:45:57', '2026-05-22 01:45:57');

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
(3, 1, 1, 'SKIN1004', 'A large bottle of hydrating toner that preps the skin. Like the serum we talked about, it uses salmon-derived DNA (PDRN) to repair the skin barrier, essentially the concentrated version of the serum. It’s used to target dullness and loss of elasticity.', 850.00, 3, '1775891886_663725763_1293320436077027_1107736041531920453_n.jpg', '2026-04-11 07:18:06', '2026-05-22 06:51:41'),
(4, 1, 7, 'Nuvé Shampoo Conditioner set', 'products are Sulphate-Free, Vegan, and use 100% Natural Fragrance. Sulphate-free formulas are great because they cleanse without stripping the natural oils from your hair, making them ideal for color-treated or dry hair.', 357.00, 7, '1775892532_662353419_1237481921488400_5024791025081148446_n.jpg', '2026-04-11 07:28:52', '2026-04-11 07:28:52'),
(5, 1, 7, 'Nutra Harmony', 'Work together to reinforce the hair shaft and promote thickness, helps with hair elasticity (less snapping when you brush!)', 489.00, 17, '1775892659_667625014_897880443277103_5359000086498027090_n.jpg', '2026-04-11 07:30:59', '2026-04-11 07:30:59'),
(6, 1, 7, 'Kérastase Paris', 'A high-end, professional-grade hair care routine focused on achieving high shine (the \"gloss\" effect) and smoothness.', 888.00, 20, '1775892789_667598573_1311586910880097_3320470117391099289_n.jpg', '2026-04-11 07:33:09', '2026-04-11 07:33:09'),
(7, 1, 5, 'Giorgio Armani \"My Way\" Eau de Parfum', 'Shimmering, pearlescent liquid inside, while the scent profile remains the same as the classic My Way, it includes fine silk-like pearls that leave a subtle glow on your skin when sprayed.', 999.00, 12, '1775893017_663679916_2821258378252206_1662661140127997948_n.jpg', '2026-04-11 07:36:57', '2026-04-11 07:36:57'),
(8, 1, 5, 'Miss Dior Blooming Bouquet', 'Softer, more delicate cousin. It is often described as a \"sparkling\" floral fragrance that feels like a fresh bouquet of spring flowers.', 326.00, 13, '1775893130_599873079_2285141815327113_420567308875245626_n.jpg', '2026-04-11 07:38:50', '2026-05-22 02:35:02'),
(9, 1, 5, 'Chanel Coco Mademoiselle', 'Ultimate \"boss girl\" fragrance. It’s a sophisticated Amber-Floral that feels much more grounded and classic, captures the \"Old Money\" or \"Classic Elegance\" aesthetic perfectly. Surrounding it with pearls, gold silk, and a Chanel lipstick highlights that this isn\'t just a perfume; it’s a status symbol of timeless French style.', 400.00, 23, '1775893346_650014201_2073181036592663_7980717092869646644_n.jpg', '2026-04-11 07:42:26', '2026-04-11 07:42:26'),
(10, 1, 6, 'ClarinsMen', 'A high-performance shaving gel that softens facial hair and protects the skin from razor burn, splash-on liquid that provides an \"intense freshness\" (as noted by the snowflake icon). It helps tighten pores and disinfect any micro-cuts.', 250.00, 0, '1775893587_664833603_920591150873668_6053465436097034809_n.jpg', '2026-04-11 07:46:27', '2026-05-22 06:51:41'),
(11, 1, 6, 'Mr. Jones Body Moisturiser', 'Marketed with three main goals: Nourish, Hydrate, and Repair. * Texture: As you can see from the swatch on the arm, it has a thick, creamy consistency. Despite looking rich, luxury men\'s body lotions are usually formulated to be \"fast-absorbing\" so they don\'t feel greasy under clothes.', 299.00, 19, '1775893661_655279246_1855396401836163_7898389392906156820_n.jpg', '2026-04-11 07:47:41', '2026-04-11 07:47:41'),
(12, 1, 6, 'Jovees Herbal Men Essential Advanced 4-in-1 Moisturising Face Wash', 'A big plus for those looking to avoid synthetic preservatives, usually incorporates ingredients like Neem, Aloe Vera, or Charcoal in their men\'s line to balance oil and prevent breakouts.', 724.00, 10, '1775893762_663767328_952552890971856_1607170772744202876_n.jpg', '2026-04-11 07:49:22', '2026-04-20 05:56:01'),
(13, 1, 3, 'Neogen Dermalogy Coconut Milk Pure Mild Cleanser', 'Coconut Milk Cleanser first to wash away impurities, as a creamy, milky liquid and transforms into a soft foam when mixed with water.', 399.00, 0, '1775893905_663914925_1297487738928762_9170207716882003591_n.jpg', '2026-04-11 07:51:45', '2026-04-11 07:51:45'),
(14, 1, 3, 'Elorea Grapefruit Body Scrub', 'Provides the \"grit\" needed to buff away dead skin cells and smooth out rough patches (like elbows and knees), naturally high in antioxidants and Vitamin C, which can help brighten the skin’s appearance.', 694.00, 18, '1775894064_650273863_1972096183695375_8754328323479048120_n.jpg', '2026-04-11 07:54:24', '2026-04-11 07:54:24'),
(15, 1, 3, 'Sephora Collection All-over Solid Cleanser', 'Head-to-toe\" product. It is formulated to be gentle enough for the face, effective for the body, and can even be used on the hair in a pinch, known for its nourishing and comforting properties. It’s designed to soothe the skin while cleansing, rather than leaving it feeling \"squeaky\" and dry.', 500.00, 16, '1775894254_662315623_1512553426970061_3557366965381366101_n.jpg', '2026-04-11 07:57:34', '2026-04-11 07:57:34'),
(16, 1, 2, 'Dior Backstage Glow Face Palette', 'frosty white for intense highlighting on the high points of the face, warm gold that works beautifully on the cheekbones or as an eyeshadow.', 2000.00, 20, '1775894406_663774174_972663479032942_6804486464091375151_n.jpg', '2026-04-11 08:00:06', '2026-04-11 08:00:06'),
(17, 1, 2, 'Dior Forever Skin Glow duo', 'A long-wear foundation that offers medium-to-full coverage with a hydrated, luminous finish. It’s famous for its \"86% floral skincare base,\" meaning it’s packed with ingredients like iris, wild pansy, and hibiscus to keep your skin hydrated while you wear it.', 750.00, 16, '1775894474_663709931_930175443230818_6025311504035769647_n.jpg', '2026-04-11 08:01:14', '2026-04-12 03:18:10'),
(18, 1, 2, 'Dior Backstage Face & Body Foundations', 't has an ultra-fluid, watery texture that feels like nothing on the skin. It provides a natural, luminous matte finish that is extremely difficult to detect, even in person, suggests, it’s designed to be used everywhere. Because it’s waterproof and sweat-resistant, makeup artists use it to even out skin on the neck, shoulders, and legs without it rubbing off on clothes.', 852.00, 9, '1775894546_667071935_945793788297716_1435160138817010985_n.jpg', '2026-04-11 08:02:26', '2026-05-26 08:32:51'),
(19, 1, 4, 'Dior Star Hair Clips', 'Feature the Christian Dior logo alongside the signature Dior Star. Monsieur Dior was very superstitious and considered the star his \"lucky charm\" after finding a metal star on the ground right before he opened his couture house.', 25.00, 99, '1775894659_667071935_1766992101373774_7892585715104817566_n.jpg', '2026-04-11 08:04:19', '2026-04-12 03:20:25'),
(20, 1, 4, 'Dior x Dyson', 'Custom-skinned version of what looks like a Dyson Supersonic. It’s designed for fast drying without extreme heat, protecting the \"Glass Hair\" shine you’d get from your Kérastase products', 500.00, 49, '1775894805_667607058_2014823725994062_9045540055757760503_n.jpg', '2026-04-11 08:06:45', '2026-04-12 03:18:10'),
(21, 1, 4, 'Hair Styling & Maintenance Kit', 'A sleek, brushless motor dryer designed for ultra-fast drying while minimizing heat damage, flat iron with floating plates, perfect for that \"Glass Hair\" look.', 233.00, 98, '1775894903_668909277_1467740201665003_1169938576115543829_n.jpg', '2026-04-11 08:08:23', '2026-04-20 05:56:01'),
(22, 2, 3, 'Test Product', 'Test Description', 101.00, 0, '1775963142_Screenshot 2026-03-27 085944.png', '2026-04-12 03:05:20', '2026-04-12 03:18:49'),
(23, 5, 2, 'M·A·C Cosmetics', 'The lip shades you love – reimagined in our award-winning, moisture-matte formula. Meet Powder Kiss Lipstick', 599.00, 482, '1777554606_M·A·C Cosmetics on Instagram_ “The lip shades you love – reimagined in our award-winning, moisture-matte formula_ Meet Powder Kiss Lipstick in_ 💄Marrakesh-mere (inspired…”.jpg', '2026-04-30 13:10:06', '2026-04-30 13:10:06'),
(24, 5, 2, 'LIMITED-EDITION - Day Dazzle Collection SHADE: Veronica', 'meant liquid lipstick day dazzle night muse matte finish slay together bold soft', 499.00, 343, '1777555231_A little lipstick, a lot of confidence 💋🌟__LIMITED-EDITION - Day Dazzle Collection_SHADE_ Veronica__Shop Now🛍️ _ Website link in bio!!__[ meant liquid lipstick day dazzle night muse matte finish slay togethe.jpg', '2026-04-30 13:20:31', '2026-04-30 13:20:31'),
(25, 5, 2, 'Maybelline Lipstick', 'A bold and modern lipstick poster concept inspired by Maybelline. . Featuring striking colors, clean layout, and a high-fashion vibe — ideal for makeup branding inspiration.', 689.00, 232, '1777556024_Maybelline Lipstick Poster Design Inspiration.jpg', '2026-04-30 13:33:44', '2026-04-30 13:33:44'),
(26, 5, 2, 'SwissBeauty Matte Lipstick', 'Get bold, beautiful lips with this Swiss Beauty Matte Lipstick. Designed with rich pigmentation, it delivers intense color in just one swipe. The smooth matte finish gives a classy, non-shiny look while staying comfortable on your lips all day. Perfect for daily wear, parties, and special occasions.', 1129.00, 53, '1777556373_Swiss Beauty Matte Lipstick _ Rich Pigment Long Lasting Red Lipstick.jpg', '2026-04-30 13:39:33', '2026-04-30 13:39:33'),
(27, 17, 5, 'Victoria\'s Secret Love Spell Fragrance', 'Victoria’s Secret Love Spell body mist is a luxurious and alluring soft perfume that will leave you feeling confident and irresistible.', 399.00, 235, '1777808147_Victoria\'s Secret Love Spell Fragrance.jpg', '2026-05-03 11:35:47', '2026-05-03 11:35:47'),
(28, 17, 5, 'Prada', 'This high-end beauty product photography concept gives premium, feminine, and glamorous vibes.', 2300.00, 336, '1777808325_Luxury Perfume Aesthetic 💖✨.jpg', '2026-05-03 11:38:45', '2026-05-03 11:38:45'),
(29, 17, 5, 'DESIRE – DIOR', 'Draped in rich velvet fabric, the scene captures the essence of temptation — bold, sensual, and irresistibly elegant.', 1500.00, 377, '1777808458_DESIRE – DIOR,  A fragrance born from passion, wrapped in luxury_.jpg', '2026-05-03 11:40:58', '2026-05-03 11:40:58'),
(30, 17, 5, 'Bella Vita Perfume', 'A soft, elegant fragrance that captures femininity and charm', 1659.00, 437, '1777808620_Bella Vita Perfume for Women 💖 Luxury Fragrance Vibes.jpg', '2026-05-03 11:43:40', '2026-05-03 11:45:29'),
(33, 21, 6, 'PRAVADA', 'Packaging inspiration for barbershop brands and masculine skincare lines.', 1599.00, 295, '1777809632_Men’s Grooming Product Packaging Ideas.jpg', '2026-05-03 12:00:32', '2026-05-03 12:00:32'),
(34, 21, 6, 'Ruixing', 'A collection of RUIXING skincare products is displayed. The elegant packaging features sleek black bottles and jars with gold accents.', 3799.00, 177, '1777809890_Midjourney_  Elegant RUIXING skincare collection with sleek black and gold packaging_.jpg', '2026-05-03 12:04:50', '2026-05-03 12:04:50'),
(35, 21, 6, 'Felicity & Yorker\'s Men\'s Beauty Kit', 'With our selected collection of face scrubs, cleansers, shower gels, and facial care essentials, you are able to delight in the ultimate skincare pleasure. Turn your everyday routine into a relaxing experience that will leave you feeling confident and renewed', 3999.00, 388, '1777810028_Enhance your grooming routine with Felicity & Yorker\'s Men\'s Beauty Kit! 🚿🌟.jpg', '2026-05-03 12:07:08', '2026-05-03 12:07:08'),
(36, 21, 6, 'HUNTER', 'The best Australian men\'s clothing brands and designers have brought our continent a long way in little time.', 2600.00, 298, '1777810240_28 Best Australian Men\'s Clothing Brands & Designers _ Man of Many.jpg', '2026-05-03 12:10:40', '2026-05-17 23:20:24'),
(37, 21, 6, 'MANSCAPED', 'fresh start with our Platinum Package grooming kit ✓ The Lawn Mower® 4.0 electric trimmer ✓ Weed Whacker® nose & ear hair trimmer ✓ Crop Preserver® ball deodorant ✓ Crop Reviver® ball refreshing spray ✓ 2-in-1 Shampoo + Conditioner sea kelp infused ✓ Body Wash with aloe infused hydration ✓ Deodorant aluminum', 4700.00, 244, '1777810733_A Fresh Start to the New Year!.jpg', '2026-05-03 12:18:53', '2026-05-03 12:18:53'),
(38, 45, 1, 'Collagen', 'deeply moisturize, boost skin elasticity, and give a plump, healthy glow', 399.00, 560, '1777811084_Pink Collagen Cream Aesthetic Skincare ✨💗.jpg', '2026-05-03 12:24:44', '2026-05-03 12:24:44'),
(39, 45, 1, 'Arencia', 'Dive deep into hydration with ARENCIA Deep Water Surge Serum (30ml) — a lightweight, marine-infused formula that instantly refreshes and revitalizes your skin.', 899.00, 490, '1777811408_Arencia Deep Water Surge Serum 30 ml.jpg', '2026-05-03 12:30:08', '2026-05-03 12:30:08'),
(40, 45, 1, 'Anua, Facial Wash', 'Anua Heartleaf Quercetinol Pore Deep Cleansing Foam is a gentle daily face wash designed to deeply cleanse pores without stripping moisture.', 600.00, 409, '1777811637_Anua Heartleaf Quercetinol Pore Deep Cleansing Foam, Face wash.jpg', '2026-05-03 12:33:57', '2026-05-03 12:33:57'),
(41, 45, 1, 'Rice Toner', 'Super nourishing and moisturizing toner. Great for dry skin types.', 650.00, 332, '1777811770_I\'m From Rice Toner!.jpg', '2026-05-03 12:36:10', '2026-05-03 12:36:10'),
(42, 45, NULL, 'Yam Root Milk Tone Up Sun Cream SPF 50', 'Enriched with Andong Yam Root Extract, this sunscreen provides superior defense against UV rays while imparting a radiant, tinted finish. With its lightweight texture, this moisturizing tinted sunscreen effortlessly blends into the skin, offering light coverage and a natural glow.', 799.00, 277, '1777811994_Yam Root Milk Tone Up Sun Cream SPF 50.jpg', '2026-05-03 12:39:54', '2026-05-03 12:39:54'),
(43, 12, 2, 'Laura Mercier\'s Tinted Moisturizer Blush', 'This creamy formula comes in 14 customizable shades that can be layered to your desired coverage level, provides 12 hours of hydration and can be applied almost anywhere (from cheeks to lips to eyelids).', 455.00, 691, '1777812275_Ombre Blush Is the TikTok Beauty Trend Taking Over the Summer.jpg', '2026-05-03 12:44:35', '2026-05-22 06:55:43'),
(44, 12, NULL, 'Hushed Cherry', 'Clear and transparent soft veil texture that permeates smoothly.\r\nSoft tone-on-tone color combinations that can be easily completed.', 500.00, 533, '1777812402_espoir Real Eye Palette All New.jpg', '2026-05-03 12:46:42', '2026-05-03 12:46:42'),
(45, 12, 2, 'Laura Mercier REAL FLAWLESS WEIGHTLESS PERFECTING CONCEALER', 'Laura Mercier REAL FLAWLESS WEIGHTLESS PERFECTING CONCEALER - Correcteur - 0W1', 399.00, 448, '1777812517_Laura Mercier REAL FLAWLESS WEIGHTLESS PERFECTING CONCEALER - Correcteur - 0W1.jpg', '2026-05-03 12:48:37', '2026-05-03 12:48:37'),
(46, 12, 2, 'Athena Lash Co', 'natural slender long fishtail false eyelashes transparent stem cross cartoon false eyelashes.', 259.00, 372, '1777812781_Athena Lash Co_ Pink and minimal product photography styling for a cosmetics brand - SalisStudio.jpg', '2026-05-03 12:53:01', '2026-05-03 12:53:01'),
(47, 12, 2, 'It Cosmetics Brow Power Universal Eyebrow Pencil - Universal Taupe', 'Developed with plastic surgeons, this best-selling, award-winning eyebrow pencil creates your most natural-looking brows. The blendable formula easily adjusts to match all hair colors based on how much pressure you apply; while the built-in spooley finishes off your look to create naturally beautiful brows.', 399.00, 446, '1777812903_It Cosmetics Brow Power Universal Eyebrow Pencil - Universal Taupe.jpg', '2026-05-03 12:55:03', '2026-05-03 12:55:03'),
(48, 11, 3, 'nectar', 'Indulge in luxurious, whipped soap that moisturizes and refreshes with every use. Explore scents that captivate and hydrate your skin for gentle care.', 450.00, 436, '1777813058_Whipped Soap.jpg', '2026-05-03 12:57:38', '2026-05-03 12:57:38'),
(49, 11, 3, 'Conseda', 'Conseda cosmetics soft shower gel', 470.00, 431, '1777813445_Conseda cosmetics soft shower gel _ENIGMA_ packaging by Parvin.jpg', '2026-05-03 13:04:05', '2026-05-27 11:58:15'),
(50, 11, 3, 'Fiksa', 'Gently cleanses hair and removes dirt.\r\nLeaves your hair soft, smooth, and shiny.', 299.00, 597, '1777813533_Fresh Roots Shampoo.jpg', '2026-05-03 13:05:33', '2026-05-22 06:55:43'),
(51, 11, 3, 'Luma', 'This serene composition features a creamy soap bar topped with soft foam and a natural sponge, accompanied by a bamboo brush. Every detail reflects softness, care, and organic luxury.', 170.00, 743, '1777813876_AI Concept Mockup with Natural Sponge & Brush.jpg', '2026-05-03 13:11:16', '2026-05-03 13:11:16'),
(52, 11, 3, 'Tula - Skincare', 'This cult-favorite purifying cleanser is your daily go-to for clear, nourished, glowing skin 🌊. It gently removes dirt, oil, and makeup without stripping moisture, leaving skin soft, hydrated, and refreshed. Perfect for every skin type.', 500.00, 443, '1777814046_Hydrating Face Wash for Clear, Glowing Skin Daily.jpg', '2026-05-03 13:14:06', '2026-05-03 13:14:20'),
(53, 7, 4, 'Bowlder Facial Massage', '1/2Pcs Bowlder Facial Massage Tool Pink Massage Tool Suitable For Facial Massage Women Facial Massage Tool Portable And Compact Massage Tool Women Beauty Roller Pink ABS Massager Set Beauty Tools, size features are:Bust: ,Length: ,Sleeve Length', 499.00, 421, '1777814383_1_2Pcs Bowlder Facial Massage Tool Pink Massage Tool Suitable For Facial Massage Women Facial Massage Tool Portable And Compact Massage Tool Women Beauty Roller.jpg', '2026-05-03 13:19:43', '2026-05-03 13:19:43'),
(57, 7, NULL, 'Luxe Silk Sleep', 'Elevate your self-care routine with this elegant rose gold silk beauty set featuring scrunchies, sleep masks, makeup bags, and beauty accessories. Perfect for skincare enthusiasts and luxury lovers.', 359.00, 332, '1777815033_Luxe Silk Sleep & Beauty Collection - Rose Gold Edition_.jpg', '2026-05-03 13:30:33', '2026-05-03 13:30:33'),
(58, 7, 4, 'Sleepwear Pajama set', 'SilkySpell SilkySpell Plunging Neck Camisole Top, Shorts, And Robe Sleepwear Set Satin Pajama Set Pink Satin Pajamas Set Satin Sleepwear Set Silk Pajama Set Pink Silk Pajamas Set  Cozy And Elegant Details, Fall ClothesI discovered amazing products on SHEIN.com, come check them out!', 999.00, 331, '1777815347_download.jpg', '2026-05-03 13:32:36', '2026-05-17 23:20:24'),
(59, 7, NULL, 'Hair-clips', '', 199.00, 499, '1777815279_hair clips.jpg', '2026-05-03 13:34:39', '2026-05-27 11:58:15');

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
(2, 6, 'Test Business', 'Test Business Address', '01234567891', '000333999', NULL, '2026-04-12 02:59:22', '2026-04-12 02:59:22', '2026-04-12 02:59:22'),
(4, 16, 'jayproducts', 'Lorenzo Tan', '09729193827', '52617283945', 1, '2026-04-30 10:38:21', '2026-04-30 10:38:21', '2026-04-30 10:38:21'),
(5, 18, 'bonlipsticks', 'Sto. Nino Banale, Pagadian City', '098266115243', '0987654567', 1, '2026-04-30 10:46:07', '2026-04-30 10:46:07', '2026-04-30 14:00:39'),
(6, 62, 'jaysonpoducts', 'Cebu', '09876787313', '000111222', 1, '2026-05-01 03:55:06', '2026-05-01 03:55:06', '2026-05-01 03:55:06'),
(7, 63, 'angelaproducts', 'Pagadian', '09729193827', '000555333', 1, '2026-05-01 03:56:32', '2026-05-01 03:56:32', '2026-05-01 03:56:32'),
(8, 64, 'kevproducts', 'Davao', '09876787313', '666444999', 1, '2026-05-01 03:57:47', '2026-05-01 03:57:47', '2026-05-01 03:57:47'),
(9, 65, 'marproducts', 'Cebu', '09729193827', '888555333', 1, '2026-05-01 03:58:57', '2026-05-01 03:58:57', '2026-05-01 03:58:57'),
(10, 66, 'marproducts', 'Ilo-Ilo', '09729193827', '555333000', 1, '2026-05-01 04:00:22', '2026-05-01 04:00:22', '2026-05-01 04:00:22'),
(11, 67, 'japroducts', 'Davao', '09876787313', '888555111', 1, '2026-05-01 04:01:46', '2026-05-01 04:01:46', '2026-05-01 04:01:46'),
(12, 68, 'drewproducts', 'Palawan', '09876787313', '1234567890123', 1, '2026-05-01 04:10:19', '2026-05-01 04:10:19', '2026-05-01 04:10:19'),
(13, 69, 'arsproducts', 'Bacolod', '098266115243', '999555777', 1, '2026-05-01 04:13:34', '2026-05-01 04:13:34', '2026-05-01 04:13:34'),
(14, 70, 'thanproducts', 'Pasig', '09267118776', '888666777', 1, '2026-05-01 04:15:41', '2026-05-01 04:15:41', '2026-05-01 04:15:41'),
(15, 71, 'krisproducts', 'Makati', '09335376337', '222111555', 1, '2026-05-01 04:19:07', '2026-05-01 04:19:07', '2026-05-01 04:19:07'),
(16, 72, 'bryproducts', 'Manila', '09729193827', '000666444', 1, '2026-05-01 04:24:17', '2026-05-01 04:24:17', '2026-05-01 04:24:17'),
(17, 73, 'alonzo', 'Aklan', '09876787313', '888333666', 1, '2026-05-01 04:31:30', '2026-05-01 04:31:30', '2026-05-01 04:31:30'),
(18, 74, 'jerproducts', 'Cagayan De Oro', '09876787313', '999888777', 1, '2026-05-01 04:33:23', '2026-05-01 04:33:23', '2026-05-01 04:33:23'),
(19, 75, 'michael', 'Aurora', '09729193827', '111555333', 1, '2026-05-01 04:35:38', '2026-05-01 04:35:38', '2026-05-01 04:35:38'),
(20, 76, 'chloeproducts', 'Davao', '098266115243', '888555666', 1, '2026-05-01 04:37:51', '2026-05-01 04:37:51', '2026-05-01 04:37:51'),
(21, 77, 'vicsproducts', 'Molave', '098266115243', '111222333', 1, '2026-05-01 04:39:54', '2026-05-01 04:39:54', '2026-05-01 04:39:54'),
(22, 78, 'alproducts', 'Rizal', '09335376337', '555222777', 1, '2026-05-01 04:44:39', '2026-05-01 04:44:39', '2026-05-01 04:44:39'),
(23, 79, 'emsproducts', 'SND', '098266115243', '999555777', 1, '2026-05-01 04:48:46', '2026-05-01 04:48:46', '2026-05-01 04:48:46'),
(24, 80, 'lilyproducts', 'Lanao', '09335376337', '000555333', 1, '2026-05-01 04:53:51', '2026-05-01 04:53:51', '2026-05-01 04:53:51'),
(25, 81, 'joproducts', 'Albay', '09876787313', '666444999', 1, '2026-05-01 04:55:13', '2026-05-01 04:55:13', '2026-05-01 04:55:13'),
(26, 82, 'adproducts', 'Maguindanao', '098266115243', '000555333', 1, '2026-05-01 04:56:35', '2026-05-01 04:56:35', '2026-05-01 04:56:35'),
(27, 83, 'carlproducts', 'Cavite', '09876787313', '000666444', 1, '2026-05-01 04:57:46', '2026-05-01 04:57:46', '2026-05-01 04:57:46'),
(28, 84, 'lizaproducts', 'Tangub', '09718253766', '000666444', 1, '2026-05-01 04:58:58', '2026-05-01 04:58:58', '2026-05-01 04:58:58'),
(29, 85, 'dianaproducts', 'Jimenez', '09335376337', '999555777', 1, '2026-05-02 00:02:52', '2026-05-02 00:02:52', '2026-05-02 00:02:52'),
(30, 86, 'naldproducts', 'Bulacan', '09729193827', '000555333', 1, '2026-05-02 00:04:22', '2026-05-02 00:04:22', '2026-05-02 00:04:22'),
(31, 87, 'ellasproducts', 'Manila', '09335376337', '000111222', 1, '2026-05-02 00:05:46', '2026-05-02 00:05:46', '2026-05-02 00:05:46'),
(32, 88, 'sarahproducts', 'Cavite', '09876787313', '000666444', 1, '2026-05-02 00:07:05', '2026-05-02 00:07:05', '2026-05-02 00:07:05'),
(33, 89, 'kefproducts', 'Pampangga', '09335376337', '999444555', 1, '2026-05-02 00:08:23', '2026-05-02 00:08:23', '2026-05-02 00:08:23'),
(34, 90, 'gabproducts', 'Zambuanga', '09718253766', '000555333', 1, '2026-05-02 00:10:01', '2026-05-02 00:10:01', '2026-05-02 00:10:01'),
(35, 91, 'graceproducts', 'Cotabato', '09335376337', '000111222', 1, '2026-05-02 00:11:39', '2026-05-02 00:11:39', '2026-05-02 00:11:39'),
(36, 92, 'sofproducts', 'Davao', '09876787313', '666444999', 1, '2026-05-02 00:13:13', '2026-05-02 00:13:13', '2026-05-02 00:13:13'),
(37, 93, 'avsproduct', 'Lanao', '09876787313', '000666444', 1, '2026-05-02 00:14:19', '2026-05-02 00:14:19', '2026-05-02 00:14:19'),
(38, 94, 'paoproduct', 'Laguna', '09729193827', '000111222', 1, '2026-05-02 00:18:05', '2026-05-02 00:18:05', '2026-05-02 00:18:05'),
(39, 95, 'tinproducts', 'Tarlac', '09718253766', '999444555', 1, '2026-05-02 00:19:24', '2026-05-02 00:19:24', '2026-05-02 00:19:24'),
(40, 96, 'miaproducts', 'Agusan', '09876787313', '000111222', 1, '2026-05-02 00:20:43', '2026-05-02 00:20:43', '2026-05-02 00:20:43'),
(41, 97, 'rinproducts', 'Iligan City', '09335376337', '999444555', 1, '2026-05-02 00:36:15', '2026-05-02 00:36:15', '2026-05-02 00:36:15'),
(42, 98, 'ninproduct', 'Sta. Cruz', '09335376337', '999555777', 1, '2026-05-02 00:37:21', '2026-05-02 00:37:21', '2026-05-02 00:37:21'),
(43, 99, 'nielproducts', 'Quezon City', '09718253766', '666444999', 1, '2026-05-02 00:38:30', '2026-05-02 00:38:30', '2026-05-02 00:38:30'),
(44, 100, 'kenproducts', 'Dipolog', '09718253766', '999555777', 1, '2026-05-02 00:40:00', '2026-05-02 00:40:00', '2026-05-02 00:40:00'),
(45, 101, 'nahproducts', 'Tangub City', '09729193827', '000111222', 1, '2026-05-02 01:51:58', '2026-05-02 01:51:58', '2026-05-02 01:51:58'),
(46, 102, 'beaproducts', 'Surigao', '09267118776', '999555777', 1, '2026-05-02 02:02:00', '2026-05-02 02:02:00', '2026-05-02 02:02:00'),
(47, 103, 'marproducts', 'Batangas', '09267118776', '000666444', 1, '2026-05-02 02:04:44', '2026-05-02 02:04:44', '2026-05-02 02:04:44'),
(48, 104, 'denproducts', 'Sursogon', '09729193827', '000111222', 1, '2026-05-02 02:09:34', '2026-05-02 02:09:34', '2026-05-02 02:09:34'),
(49, 106, 'steveproducts', 'Masbate', '09718253766', '999444555', 1, '2026-05-02 02:12:20', '2026-05-02 02:12:20', '2026-05-02 02:12:20'),
(50, 107, 'fransproducts', 'Basilan', '09718253766', '000666444', 1, '2026-05-02 02:14:22', '2026-05-02 02:14:22', '2026-05-02 02:14:22'),
(51, 108, 'hensproducts', 'Bohol', '09267118776', '999555777', 1, '2026-05-02 02:20:56', '2026-05-02 02:20:56', '2026-05-02 02:20:56');

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
(1, 7, 'Test', 'Test', '01234567891', '222444888', 'declined', '', 1, '2026-04-12 06:36:49', '2026-04-12 03:27:05', '2026-04-12 06:36:49');

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
(3, 1, 7, 1250.00, 'paid', '2026-04-12 03:19:58'),
(4, 1, 8, 1085.00, 'paid', '2026-04-16 01:21:41'),
(5, 1, 9, 957.00, 'paid', '2026-04-20 05:57:21'),
(6, 1, 20, 852.00, 'paid', '2026-05-26 08:34:01'),
(7, 1, 11, 852.00, 'paid', '2026-05-26 08:34:34');

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
(3, 2, 100.00, 1, 'failed', NULL, '2026-04-12 03:16:13', '2026-04-12 06:37:01'),
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
(36, 2, 'seller_payout_updated', 'seller_payouts', 8, 'Admin set payout #8 for seller Beauty Mart to completed.', '::1', '2026-04-12 04:52:43'),
(37, 2, 'seller_application_approved', 'seller_applications', 2, 'Admin approved seller application #2 for user Clark and created seller #3.', '124.217.29.220', '2026-04-12 06:36:37'),
(38, 2, 'seller_application_declined', 'seller_applications', 1, 'Admin declined seller application #1 for user Test. Notes: No notes provided.', '124.217.29.220', '2026-04-12 06:36:49'),
(39, 2, 'seller_payout_updated', 'seller_payouts', 3, 'Admin set payout #3 for seller Test Business to failed.', '124.217.29.220', '2026-04-12 06:37:01'),
(40, NULL, 'customer_logged_in', 'users', 8, 'Customer Ace logged in successfully.', '124.217.29.220', '2026-04-12 06:38:57'),
(41, NULL, 'customer_logged_out', 'users', 8, 'Customer Ace logged out.', '124.217.29.220', '2026-04-12 06:39:02'),
(42, NULL, 'customer_logged_in', 'users', 8, 'Customer Ace logged in successfully.', '124.217.29.220', '2026-04-12 06:40:01'),
(43, NULL, 'customer_logged_out', 'users', 8, 'Customer Ace logged out.', '124.217.29.220', '2026-04-12 06:40:07'),
(44, 1, 'superadmin_user_deleted', 'users', 9, 'Superadmin deleted account Clark.', '124.217.29.220', '2026-04-12 06:40:43'),
(45, 1, 'superadmin_user_deleted', 'users', 8, 'Superadmin deleted account Ace.', '124.217.29.220', '2026-04-12 06:40:49'),
(46, 10, 'customer_logged_in', 'users', 10, 'Customer acer logged in successfully.', '180.193.195.230', '2026-04-13 04:28:41'),
(47, 10, 'customer_logged_out', 'users', 10, 'Customer acer logged out.', '180.193.195.230', '2026-04-13 04:28:58'),
(48, 11, 'customer_logged_in', 'users', 11, 'Customer Haha logged in successfully.', '180.193.212.130', '2026-04-13 04:34:11'),
(49, 11, 'customer_logged_out', 'users', 11, 'Customer Haha logged out.', '180.193.212.130', '2026-04-13 04:34:17'),
(50, 10, 'customer_logged_in', 'users', 10, 'Customer acer logged in successfully.', '180.193.195.230', '2026-04-13 04:38:41'),
(51, 10, 'customer_logged_out', 'users', 10, 'Customer acer logged out.', '180.193.195.230', '2026-04-13 04:38:58'),
(52, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-04-13 04:43:26'),
(53, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-04-13 04:44:51'),
(54, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-04-15 05:42:35'),
(55, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-04-15 05:44:06'),
(56, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-04-15 06:59:03'),
(57, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-04-15 07:03:01'),
(58, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-04-16 01:13:54'),
(59, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-04-16 01:15:41'),
(60, 4, 'customer_checkout', 'orders', 8, 'Customer Naniw placed order #8 with 2 item(s), total PHP 1,085.00.', '180.193.195.230', '2026-04-16 01:20:13'),
(61, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-04-20 02:25:32'),
(62, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-04-20 02:25:56'),
(63, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.212.130', '2026-04-20 04:10:22'),
(64, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.212.130', '2026-04-20 04:13:32'),
(65, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-04-20 05:31:21'),
(66, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-04-20 05:32:36'),
(67, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-04-20 05:39:29'),
(68, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-04-20 05:39:37'),
(69, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-04-20 05:46:54'),
(70, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-04-20 05:47:44'),
(71, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-04-20 05:55:19'),
(72, 3, 'customer_checkout', 'orders', 9, 'Customer Jayle placed order #9 with 2 item(s), total PHP 957.00.', '180.193.195.230', '2026-04-20 05:56:01'),
(73, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-04-20 05:56:49'),
(74, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-04-20 05:57:40'),
(75, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-04-20 06:10:07'),
(76, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '49.148.156.133', '2026-04-20 15:56:57'),
(77, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '49.148.156.133', '2026-04-20 22:44:17'),
(78, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-04-21 00:57:14'),
(79, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '2001:fd8:2723:6178:4968:9f61:af74:3432', '2026-04-21 12:50:21'),
(80, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '112.208.73.133', '2026-04-22 05:17:53'),
(81, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '112.208.73.133', '2026-04-22 05:18:29'),
(82, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '49.148.147.33', '2026-04-22 06:02:46'),
(83, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '112.208.73.133', '2026-04-22 16:13:09'),
(84, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '112.208.73.133', '2026-04-22 16:13:29'),
(85, 11, 'customer_logged_in', 'users', 11, 'Customer Haha logged in successfully.', '112.208.73.133', '2026-04-22 16:19:24'),
(86, 11, 'customer_logged_out', 'users', 11, 'Customer Haha logged out.', '112.208.73.133', '2026-04-22 16:19:31'),
(87, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '175.176.85.97', '2026-04-29 03:49:29'),
(88, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '175.176.85.97', '2026-04-29 03:49:39'),
(89, 2, 'admin_user_created', 'users', 13, 'Administrator created customer account for jerox (aljer@beautymart.com).', '49.145.247.6', '2026-04-30 10:32:06'),
(90, 2, 'admin_user_created', 'users', 14, 'Administrator created customer account for dan (dan@beautymart.com).', '49.145.247.6', '2026-04-30 10:34:05'),
(91, 2, 'admin_user_created', 'users', 15, 'Administrator created customer account for van (van@beautymart.com).', '49.145.247.6', '2026-04-30 10:34:59'),
(92, 2, 'admin_user_created', 'users', 16, 'Administrator created seller account for jaja (jay@beautymart.com).', '49.145.247.6', '2026-04-30 10:38:21'),
(93, 2, 'admin_user_created', 'users', 17, 'Administrator created customer account for cha (cha@beautymart.com).', '49.145.247.6', '2026-04-30 10:41:04'),
(94, 2, 'admin_user_created', 'users', 18, 'Administrator created seller account for bon (bon@beautymart.com).', '49.145.247.6', '2026-04-30 10:46:07'),
(95, 2, 'admin_user_created', 'users', 19, 'Administrator created customer account for jea (jea@beautymart.com).', '49.145.247.6', '2026-04-30 10:49:53'),
(96, 2, 'admin_user_created', 'users', 20, 'Administrator created customer account for ben (ben@gmail.com).', '49.145.247.6', '2026-04-30 11:00:47'),
(97, 2, 'admin_user_created', 'users', 21, 'Administrator created admin account for tud (tud@gmail.com).', '49.145.247.6', '2026-04-30 11:02:17'),
(98, 2, 'admin_user_created', 'users', 22, 'Administrator created admin account for vian (vian@gmail.com).', '49.145.247.6', '2026-04-30 11:03:17'),
(99, 2, 'admin_user_updated', 'users', 18, 'Administrator updated account #18 (bon).', '49.145.247.6', '2026-04-30 13:05:28'),
(100, 18, 'seller_product_created', 'products', 23, 'Seller bonlipsticks added product \'M·A·C Cosmetics\'.', '49.145.247.6', '2026-04-30 13:10:06'),
(101, 2, 'admin_user_created', 'users', 23, 'Administrator created customer account for clarky (clarky@gmail.com).', '124.217.16.7', '2026-04-30 13:20:12'),
(102, 18, 'seller_product_created', 'products', 24, 'Seller bonlipsticks added product \'LIMITED-EDITION - Day Dazzle Collection SHADE: Veronica\'.', '49.145.247.6', '2026-04-30 13:20:31'),
(103, 18, 'seller_product_created', 'products', 25, 'Seller bonlipsticks added product \'Maybelline Lipstick\'.', '49.145.247.6', '2026-04-30 13:33:44'),
(104, 18, 'seller_product_created', 'products', 26, 'Seller bonlipsticks added product \'SwissBeauty Matte Lipstick\'.', '49.145.247.6', '2026-04-30 13:39:33'),
(105, 1, 'admin_user_deleted', 'users', 22, 'Superadmin deleted account vian.', '49.145.247.6', '2026-04-30 13:49:58'),
(106, 1, 'admin_user_deleted', 'users', 21, 'Superadmin deleted account tud.', '49.145.247.6', '2026-04-30 13:50:09'),
(107, 1, 'admin_user_created', 'users', 24, 'Superadmin created customer account for jamin (jamin@gmail.com).', '49.145.247.6', '2026-04-30 13:52:22'),
(108, 1, 'admin_user_updated', 'users', 18, 'Superadmin updated account #18 (bon).', '49.145.247.6', '2026-04-30 13:58:43'),
(109, 1, 'admin_user_updated', 'users', 18, 'Superadmin updated account #18 (bon).', '49.145.247.6', '2026-04-30 14:00:39'),
(110, 1, 'admin_user_created', 'users', 25, 'Superadmin created customer account for maxi (maxi@gmaiil.com).', '49.145.247.6', '2026-04-30 14:04:24'),
(111, 1, 'admin_user_created', 'users', 26, 'Superadmin created customer account for zedy (zedy@gmail.com).', '49.145.247.6', '2026-04-30 14:06:45'),
(112, 1, 'admin_user_created', 'users', 27, 'Superadmin created customer account for zamy (zamy@gmail.com).', '49.145.247.6', '2026-04-30 14:07:29'),
(113, 1, 'admin_user_created', 'users', 28, 'Superadmin created customer account for mico (mico@gmail.com).', '49.145.247.6', '2026-04-30 14:08:15'),
(114, 1, 'admin_user_created', 'users', 29, 'Superadmin created customer account for jenny (jenny@gmail.com).', '49.145.247.6', '2026-04-30 14:09:25'),
(115, 1, 'admin_user_created', 'users', 30, 'Superadmin created customer account for ava (ava@gmail.com).', '49.145.247.6', '2026-04-30 14:10:30'),
(116, 1, 'admin_user_created', 'users', 31, 'Superadmin created customer account for eza (eza@gmail.com).', '49.145.247.6', '2026-04-30 14:11:25'),
(117, 1, 'admin_user_created', 'users', 32, 'Superadmin created customer account for rava (rava@gmail.com).', '49.145.247.6', '2026-04-30 14:12:14'),
(118, 1, 'admin_user_created', 'users', 33, 'Superadmin created customer account for ryle (ryle@gmail.com).', '49.145.247.6', '2026-04-30 14:13:34'),
(119, 1, 'admin_user_created', 'users', 34, 'Superadmin created customer account for kyle (kyle@gmail.com).', '49.145.247.6', '2026-04-30 14:14:16'),
(120, 1, 'admin_user_created', 'users', 35, 'Superadmin created customer account for jax (jax@gmail.com).', '49.145.247.6', '2026-04-30 14:15:20'),
(121, 1, 'admin_user_created', 'users', 36, 'Superadmin created customer account for mik (mika@gmail.com).', '49.145.247.6', '2026-04-30 14:16:05'),
(122, 1, 'admin_user_created', 'users', 37, 'Superadmin created customer account for zion (zion@gmail.com).', '49.145.247.6', '2026-04-30 14:16:50'),
(123, 1, 'admin_user_created', 'users', 38, 'Superadmin created customer account for yuri (yuri@gmail.com).', '49.145.247.6', '2026-04-30 14:17:34'),
(124, 1, 'admin_user_created', 'users', 39, 'Superadmin created customer account for nial (nial@gmail.com).', '49.145.247.6', '2026-04-30 14:18:19'),
(125, 1, 'admin_user_created', 'users', 40, 'Superadmin created customer account for ryns (ryns@gmail.com).', '49.145.247.6', '2026-04-30 14:18:59'),
(126, 1, 'admin_user_created', 'users', 41, 'Superadmin created customer account for ivy (ivy@gmail.com).', '49.145.247.6', '2026-04-30 14:19:45'),
(127, 1, 'admin_user_created', 'users', 42, 'Superadmin created customer account for ron (ron@gmail.com).', '49.145.247.6', '2026-04-30 14:22:01'),
(128, 1, 'admin_user_created', 'users', 43, 'Superadmin created customer account for aira (aira@gmail.com).', '49.145.247.6', '2026-04-30 14:22:44'),
(129, 1, 'admin_user_created', 'users', 44, 'Superadmin created customer account for naya (naya@gmail.com).', '49.145.247.6', '2026-04-30 14:24:36'),
(130, 2, 'admin_user_created', 'users', 45, 'Administrator created customer account for ino (ino@gmail.com).', '49.145.247.6', '2026-05-01 03:02:09'),
(131, 2, 'admin_user_created', 'users', 46, 'Administrator created customer account for iri (iri@gmail.com).', '49.145.247.6', '2026-05-01 03:03:21'),
(132, 2, 'admin_user_created', 'users', 47, 'Administrator created customer account for alo (alo@gmail.com).', '49.145.247.6', '2026-05-01 03:05:59'),
(133, 2, 'admin_user_created', 'users', 48, 'Administrator created customer account for izy (izy@gmail.com).', '49.145.247.6', '2026-05-01 03:06:44'),
(134, 2, 'admin_user_created', 'users', 49, 'Administrator created customer account for rio (rio@gmail.com).', '49.145.247.6', '2026-05-01 03:07:35'),
(135, 2, 'admin_user_created', 'users', 50, 'Administrator created customer account for navy (navy@gmail.com).', '49.145.247.6', '2026-05-01 03:08:26'),
(136, 2, 'admin_user_created', 'users', 51, 'Administrator created customer account for krix (krix@gmail.com).', '49.145.247.6', '2026-05-01 03:10:35'),
(137, 2, 'admin_user_created', 'users', 52, 'Administrator created customer account for kaye (kaye@gmail.com).', '49.145.247.6', '2026-05-01 03:11:28'),
(138, 2, 'admin_user_created', 'users', 53, 'Administrator created customer account for kian (kian@gmail.com).', '49.145.247.6', '2026-05-01 03:12:59'),
(139, 2, 'admin_user_created', 'users', 54, 'Administrator created customer account for luna (luna@gmail.com).', '49.145.247.6', '2026-05-01 03:13:35'),
(140, 2, 'admin_user_created', 'users', 55, 'Administrator created customer account for zara (zara@gmail.com).', '49.145.247.6', '2026-05-01 03:14:14'),
(141, 2, 'admin_user_created', 'users', 56, 'Administrator created customer account for hana (hana@gmail.com).', '49.145.247.6', '2026-05-01 03:14:56'),
(142, 2, 'admin_user_created', 'users', 57, 'Administrator created customer account for zeke (zeke@gmail.com).', '49.145.247.6', '2026-05-01 03:19:53'),
(143, 2, 'admin_user_created', 'users', 58, 'Administrator created customer account for lex (lex@gmail.com).', '49.145.247.6', '2026-05-01 03:20:46'),
(144, 2, 'admin_user_created', 'users', 59, 'Administrator created customer account for zeno (zeno@gmail.com).', '49.145.247.6', '2026-05-01 03:21:31'),
(145, 2, 'admin_user_created', 'users', 60, 'Administrator created customer account for lara (lara@gmail.com).', '49.145.247.6', '2026-05-01 03:22:14'),
(146, 2, 'admin_user_created', 'users', 61, 'Administrator created customer account for kai (kai@gmail.com).', '49.145.247.6', '2026-05-01 03:23:06'),
(147, 2, 'admin_user_created', 'users', 62, 'Administrator created seller account for jayson (jayson@gmail.com).', '49.145.247.6', '2026-05-01 03:55:06'),
(148, 2, 'admin_user_created', 'users', 63, 'Administrator created seller account for angela (angela@gmail.com).', '49.145.247.6', '2026-05-01 03:56:32'),
(149, 2, 'admin_user_created', 'users', 64, 'Administrator created seller account for kevin (kevin@gmail.com).', '49.145.247.6', '2026-05-01 03:57:47'),
(150, 2, 'admin_user_created', 'users', 65, 'Administrator created seller account for maria (maria@gmail.com).', '49.145.247.6', '2026-05-01 03:58:57'),
(151, 2, 'admin_user_created', 'users', 66, 'Administrator created seller account for mar (mar@gmail.com).', '49.145.247.6', '2026-05-01 04:00:22'),
(152, 2, 'admin_user_created', 'users', 67, 'Administrator created seller account for jane (jane@gmail.com).', '49.145.247.6', '2026-05-01 04:01:46'),
(153, 2, 'admin_user_created', 'users', 68, 'Administrator created seller account for andrew (andrew@gmail.com).', '49.145.247.6', '2026-05-01 04:10:19'),
(154, 2, 'admin_user_created', 'users', 69, 'Administrator created seller account for arlyn (arlyn@gmail.com).', '49.145.247.6', '2026-05-01 04:13:34'),
(155, 2, 'admin_user_created', 'users', 70, 'Administrator created seller account for nathan (nathan@gmail.com).', '49.145.247.6', '2026-05-01 04:15:41'),
(156, 2, 'admin_user_created', 'users', 71, 'Administrator created seller account for kris (kris@gmail.com).', '49.145.247.6', '2026-05-01 04:19:07'),
(157, 2, 'admin_user_created', 'users', 72, 'Administrator created seller account for bry (bry@gmail.com).', '49.145.247.6', '2026-05-01 04:24:17'),
(158, 2, 'admin_user_created', 'users', 73, 'Administrator created seller account for alonzo (alonzo@gmail.com).', '49.145.247.6', '2026-05-01 04:31:30'),
(159, 2, 'admin_user_created', 'users', 74, 'Administrator created seller account for jeremy (jeremy@gmail.com).', '49.145.247.6', '2026-05-01 04:33:23'),
(160, 2, 'admin_user_created', 'users', 75, 'Administrator created seller account for michael (michael@gmail.com).', '49.145.247.6', '2026-05-01 04:35:38'),
(161, 2, 'admin_user_created', 'users', 76, 'Administrator created seller account for chloe (chloe@gmail.com).', '49.145.247.6', '2026-05-01 04:37:51'),
(162, 2, 'admin_user_created', 'users', 77, 'Administrator created seller account for victor (victor@gmail.com).', '49.145.247.6', '2026-05-01 04:39:54'),
(163, 2, 'admin_user_created', 'users', 78, 'Administrator created seller account for albert (al@gmail.com).', '49.145.247.6', '2026-05-01 04:44:39'),
(164, 2, 'admin_user_created', 'users', 79, 'Administrator created seller account for emma (emma@gmail.com).', '49.145.247.6', '2026-05-01 04:48:46'),
(165, 2, 'admin_user_created', 'users', 80, 'Administrator created seller account for lily (lily@gmail.com).', '49.145.247.6', '2026-05-01 04:53:51'),
(166, 2, 'admin_user_created', 'users', 81, 'Administrator created seller account for joseph (joseph@gmail.com).', '49.145.247.6', '2026-05-01 04:55:13'),
(167, 2, 'admin_user_created', 'users', 82, 'Administrator created seller account for adrian (adrian@gmail.com).', '49.145.247.6', '2026-05-01 04:56:35'),
(168, 2, 'admin_user_created', 'users', 83, 'Administrator created seller account for carlos (carlos@gmail.com).', '49.145.247.6', '2026-05-01 04:57:46'),
(169, 2, 'admin_user_created', 'users', 84, 'Administrator created seller account for liza (liza@gmail.com).', '49.145.247.6', '2026-05-01 04:58:58'),
(170, 2, 'admin_user_created', 'users', 85, 'Administrator created seller account for diana (diana@gmail.com).', '49.148.147.33', '2026-05-02 00:02:52'),
(171, 2, 'admin_user_created', 'users', 86, 'Administrator created seller account for ronald (ronald@gmail.com).', '49.148.147.33', '2026-05-02 00:04:22'),
(172, 2, 'admin_user_created', 'users', 87, 'Administrator created seller account for ella (ella@gmail.com).', '49.148.147.33', '2026-05-02 00:05:46'),
(173, 2, 'admin_user_created', 'users', 88, 'Administrator created seller account for sarah (sarah@gmail.com).', '49.148.147.33', '2026-05-02 00:07:05'),
(174, 2, 'admin_user_created', 'users', 89, 'Administrator created seller account for kiefer (kef@gmail.com).', '49.148.147.33', '2026-05-02 00:08:23'),
(175, 2, 'admin_user_created', 'users', 90, 'Administrator created seller account for gabriel (gabriel@gmail.com).', '49.148.147.33', '2026-05-02 00:10:01'),
(176, 2, 'admin_user_created', 'users', 91, 'Administrator created seller account for grace (grace@gmail.com).', '49.148.147.33', '2026-05-02 00:11:39'),
(177, 2, 'admin_user_created', 'users', 92, 'Administrator created seller account for sofia (sofia@gmail.com).', '49.148.147.33', '2026-05-02 00:13:13'),
(178, 2, 'admin_user_created', 'users', 93, 'Administrator created seller account for avy (avy@gmail.com).', '49.148.147.33', '2026-05-02 00:14:19'),
(179, 2, 'admin_user_created', 'users', 94, 'Administrator created seller account for pao (pao@gmail.com).', '49.148.147.33', '2026-05-02 00:18:05'),
(180, 2, 'admin_user_created', 'users', 95, 'Administrator created seller account for justin (justin@gmail.com).', '49.148.147.33', '2026-05-02 00:19:24'),
(181, 2, 'admin_user_created', 'users', 96, 'Administrator created seller account for mia (mia@gmail.com).', '49.148.147.33', '2026-05-02 00:20:43'),
(182, 2, 'admin_user_created', 'users', 97, 'Administrator created seller account for rina (rina@gmail.com).', '49.148.147.33', '2026-05-02 00:36:15'),
(183, 2, 'admin_user_created', 'users', 98, 'Administrator created seller account for nina (nina@gmail.com).', '49.148.147.33', '2026-05-02 00:37:21'),
(184, 2, 'admin_user_created', 'users', 99, 'Administrator created seller account for niel (niel@gmail.com).', '49.148.147.33', '2026-05-02 00:38:30'),
(185, 2, 'admin_user_created', 'users', 100, 'Administrator created seller account for ken (ken@gmail.com).', '49.148.147.33', '2026-05-02 00:40:00'),
(186, 2, 'admin_user_created', 'users', 101, 'Administrator created seller account for hannah (hannah@gmail.com).', '49.148.147.33', '2026-05-02 01:51:58'),
(187, 2, 'admin_user_created', 'users', 102, 'Administrator created seller account for bea (bea@gmail.com).', '49.148.147.33', '2026-05-02 02:02:00'),
(188, 2, 'admin_user_created', 'users', 103, 'Administrator created seller account for marvin (marvin@gmail.com).', '49.148.147.33', '2026-05-02 02:04:44'),
(189, 2, 'admin_user_created', 'users', 104, 'Administrator created seller account for den (den@gmail.com).', '49.148.147.33', '2026-05-02 02:09:34'),
(190, 2, 'admin_user_created', 'users', 105, 'Administrator created customer account for sam (samuel@gmail.com).', '49.148.147.33', '2026-05-02 02:11:02'),
(191, 2, 'admin_user_created', 'users', 106, 'Administrator created seller account for steve (steve@gmail.com).', '49.148.147.33', '2026-05-02 02:12:20'),
(192, 2, 'admin_user_created', 'users', 107, 'Administrator created seller account for frans (frans@gmail.com).', '49.148.147.33', '2026-05-02 02:14:22'),
(193, 2, 'admin_user_deleted', 'users', 105, 'Administrator deleted account sam.', '49.148.147.33', '2026-05-02 02:17:22'),
(194, 2, 'admin_user_deleted', 'users', 27, 'Administrator deleted account zamy.', '49.148.147.33', '2026-05-02 02:18:45'),
(195, 2, 'admin_user_created', 'users', 108, 'Administrator created seller account for henry (henry@gmail.com).', '49.148.147.33', '2026-05-02 02:20:56'),
(196, 73, 'seller_product_created', 'products', 27, 'Seller alonzo added product \'Victoria\'s Secret Love Spell Fragrance\'.', '49.148.147.33', '2026-05-03 11:35:47'),
(197, 73, 'seller_product_created', 'products', 28, 'Seller alonzo added product \'Prada\'.', '49.148.147.33', '2026-05-03 11:38:45'),
(198, 73, 'seller_product_created', 'products', 29, 'Seller alonzo added product \'DESIRE – DIOR\'.', '49.148.147.33', '2026-05-03 11:40:58'),
(199, 73, 'seller_product_created', 'products', 30, 'Seller alonzo added product \'Bella Vita Perfume\'.', '49.148.147.33', '2026-05-03 11:43:40'),
(200, 73, 'seller_product_created', 'products', 31, 'Seller alonzo added product \'Flowerbomb viktor and rolf\'.', '49.148.147.33', '2026-05-03 11:45:06'),
(201, 73, 'seller_product_updated', 'products', 30, 'Seller alonzo updated product \'Bella Vita Perfume\'.', '49.148.147.33', '2026-05-03 11:45:29'),
(202, 73, 'seller_product_updated', 'products', 31, 'Seller alonzo updated product \'Flowerbomb viktor and rolf\'.', '49.148.147.33', '2026-05-03 11:45:50'),
(203, 73, 'seller_product_updated', 'products', 31, 'Seller alonzo updated product \'Flowerbomb viktor and rolf\'.', '49.148.147.33', '2026-05-03 11:48:09'),
(204, 77, 'seller_product_created', 'products', 32, 'Seller vicsproducts added product \'SADOER\'.', '49.148.147.33', '2026-05-03 11:51:37'),
(205, 77, 'seller_product_created', 'products', 33, 'Seller vicsproducts added product \'PRAVADA\'.', '49.148.147.33', '2026-05-03 12:00:32'),
(206, 77, 'seller_product_created', 'products', 34, 'Seller vicsproducts added product \'Ruixing\'.', '49.148.147.33', '2026-05-03 12:04:50'),
(207, 77, 'seller_product_created', 'products', 35, 'Seller vicsproducts added product \'Felicity & Yorker\'s Men\'s Beauty Kit\'.', '49.148.147.33', '2026-05-03 12:07:08'),
(208, 77, 'seller_product_created', 'products', 36, 'Seller vicsproducts added product \'HUNTER\'.', '49.148.147.33', '2026-05-03 12:10:40'),
(209, 77, 'seller_product_updated', 'products', 32, 'Seller vicsproducts updated product \'SADOER\'.', '49.148.147.33', '2026-05-03 12:12:04'),
(210, 77, 'seller_product_created', 'products', 37, 'Seller vicsproducts added product \'MANSCAPED\'.', '49.148.147.33', '2026-05-03 12:18:53'),
(211, 77, 'seller_product_deleted', 'products', 32, 'Seller vicsproducts deleted product \'SADOER\'.', '49.148.147.33', '2026-05-03 12:19:03'),
(212, 73, 'seller_product_deleted', 'products', 31, 'Seller alonzo deleted product \'Flowerbomb viktor and rolf\'.', '49.148.147.33', '2026-05-03 12:20:19'),
(213, 101, 'seller_product_created', 'products', 38, 'Seller nahproducts added product \'Collagen\'.', '49.148.147.33', '2026-05-03 12:24:44'),
(214, 101, 'seller_product_created', 'products', 39, 'Seller nahproducts added product \'Arencia\'.', '49.148.147.33', '2026-05-03 12:30:08'),
(215, 101, 'seller_product_created', 'products', 40, 'Seller nahproducts added product \'Anua, Facial Wash\'.', '49.148.147.33', '2026-05-03 12:33:57'),
(216, 101, 'seller_product_created', 'products', 41, 'Seller nahproducts added product \'Rice Toner\'.', '49.148.147.33', '2026-05-03 12:36:10'),
(217, 101, 'seller_product_created', 'products', 42, 'Seller nahproducts added product \'Yam Root Milk Tone Up Sun Cream SPF 50\'.', '49.148.147.33', '2026-05-03 12:39:54'),
(218, 68, 'seller_product_created', 'products', 43, 'Seller drewproducts added product \'Laura Mercier\'s Tinted Moisturizer Blush\'.', '49.148.147.33', '2026-05-03 12:44:35'),
(219, 68, 'seller_product_created', 'products', 44, 'Seller drewproducts added product \'Hushed Cherry\'.', '49.148.147.33', '2026-05-03 12:46:42'),
(220, 68, 'seller_product_created', 'products', 45, 'Seller drewproducts added product \'Laura Mercier REAL FLAWLESS WEIGHTLESS PERFECTING CONCEALER\'.', '49.148.147.33', '2026-05-03 12:48:37'),
(221, 68, 'seller_product_created', 'products', 46, 'Seller drewproducts added product \'Athena Lash Co\'.', '49.148.147.33', '2026-05-03 12:53:01'),
(222, 68, 'seller_product_created', 'products', 47, 'Seller drewproducts added product \'It Cosmetics Brow Power Universal Eyebrow Pencil - Universal Taupe\'.', '49.148.147.33', '2026-05-03 12:55:03'),
(223, 67, 'seller_product_created', 'products', 48, 'Seller japroducts added product \'nectar\'.', '49.148.147.33', '2026-05-03 12:57:38'),
(224, 67, 'seller_product_created', 'products', 49, 'Seller japroducts added product \'Conseda\'.', '49.148.147.33', '2026-05-03 13:04:05'),
(225, 67, 'seller_product_created', 'products', 50, 'Seller japroducts added product \'Fiksa\'.', '49.148.147.33', '2026-05-03 13:05:33'),
(226, 67, 'seller_product_created', 'products', 51, 'Seller japroducts added product \'Luma\'.', '49.148.147.33', '2026-05-03 13:11:16'),
(227, 67, 'seller_product_created', 'products', 52, 'Seller japroducts added product \'Tula - Skincare\'.', '49.148.147.33', '2026-05-03 13:14:06'),
(228, 67, 'seller_product_updated', 'products', 52, 'Seller japroducts updated product \'Tula - Skincare\'.', '49.148.147.33', '2026-05-03 13:14:20'),
(229, 67, 'seller_product_updated', 'products', 49, 'Seller japroducts updated product \'Conseda\'.', '49.148.147.33', '2026-05-03 13:14:33'),
(230, 63, 'seller_product_created', 'products', 53, 'Seller angelaproducts added product \'Bowlder Facial Massage\'.', '49.148.147.33', '2026-05-03 13:19:43'),
(231, 63, 'seller_product_created', 'products', 54, 'Seller angelaproducts added product \'Bowlder Facial Massage\'.', '49.148.147.33', '2026-05-03 13:24:40'),
(232, 63, 'seller_product_deleted', 'products', 54, 'Seller angelaproducts deleted product \'Bowlder Facial Massage\'.', '49.148.147.33', '2026-05-03 13:24:48'),
(233, 63, 'seller_product_created', 'products', 55, 'Seller angelaproducts added product \'hair tie\'.', '49.148.147.33', '2026-05-03 13:26:09'),
(234, 63, 'seller_product_updated', 'products', 55, 'Seller angelaproducts updated product \'hair tie\'.', '49.148.147.33', '2026-05-03 13:28:00'),
(235, 63, 'seller_product_created', 'products', 56, 'Seller angelaproducts added product \'Hair-tie\'.', '49.148.147.33', '2026-05-03 13:28:39'),
(236, 63, 'seller_product_deleted', 'products', 55, 'Seller angelaproducts deleted product \'hair tie\'.', '49.148.147.33', '2026-05-03 13:28:47'),
(237, 63, 'seller_product_deleted', 'products', 56, 'Seller angelaproducts deleted product \'Hair-tie\'.', '49.148.147.33', '2026-05-03 13:28:53'),
(238, 63, 'seller_product_created', 'products', 57, 'Seller angelaproducts added product \'Luxe Silk Sleep\'.', '49.148.147.33', '2026-05-03 13:30:33'),
(239, 63, 'seller_product_created', 'products', 58, 'Seller angelaproducts added product \'Sleepwear Pajama set\'.', '49.148.147.33', '2026-05-03 13:32:36'),
(240, 63, 'seller_product_created', 'products', 59, 'Seller angelaproducts added product \'Hair-clips\'.', '49.148.147.33', '2026-05-03 13:34:39'),
(241, 63, 'seller_product_updated', 'products', 58, 'Seller angelaproducts updated product \'Sleepwear Pajama set\'.', '49.148.147.33', '2026-05-03 13:35:47'),
(242, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-04 05:28:53'),
(243, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-05-04 05:33:34'),
(244, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-06 05:15:52'),
(245, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-06 05:16:07'),
(246, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-06 05:16:34'),
(247, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-06 05:54:09'),
(248, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-06 05:54:57'),
(249, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-06 05:55:19'),
(250, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '49.145.255.92', '2026-05-17 23:18:47'),
(251, 3, 'customer_checkout', 'orders', 10, 'Customer Jayle placed order #10 with 2 item(s), total PHP 3,599.00.', '49.145.255.92', '2026-05-17 23:20:24'),
(252, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '122.54.46.66', '2026-05-21 02:58:36'),
(253, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '122.54.46.66', '2026-05-22 00:54:26'),
(254, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '122.54.46.66', '2026-05-22 00:56:34'),
(255, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '122.54.46.66', '2026-05-22 01:01:08'),
(256, 3, 'customer_checkout', 'orders', 11, 'Customer Jayle placed order #11 with 1 item(s), total PHP 852.00.', '122.54.46.66', '2026-05-22 01:02:15'),
(257, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '122.54.46.66', '2026-05-22 01:05:06'),
(258, 110, 'customer_logged_in', 'users', 110, 'Customer jas logged in successfully.', '122.54.46.66', '2026-05-22 02:25:28'),
(259, 110, 'customer_checkout', 'orders', 12, 'Customer jas placed order #12 with 1 item(s), total PHP 326.00.', '122.54.46.66', '2026-05-22 02:34:21'),
(260, 110, 'customer_checkout', 'orders', 13, 'Customer jas placed order #13 with 1 item(s), total PHP 326.00.', '122.54.46.66', '2026-05-22 02:35:02'),
(261, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '122.54.46.66', '2026-05-22 04:09:46'),
(262, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '122.54.46.66', '2026-05-22 04:10:48'),
(263, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-22 06:17:50'),
(264, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-05-22 06:17:58'),
(265, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-22 06:23:21'),
(266, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-05-22 06:23:41'),
(267, 111, 'customer_logged_in', 'users', 111, 'Customer jayl logged in successfully.', '180.193.195.230', '2026-05-22 06:45:21'),
(268, 111, 'customer_logged_out', 'users', 111, 'Customer jayl logged out.', '180.193.195.230', '2026-05-22 06:46:13'),
(269, 111, 'customer_logged_in', 'users', 111, 'Customer jayl logged in successfully.', '180.193.195.230', '2026-05-22 06:48:11'),
(270, 111, 'customer_checkout', 'orders', 14, 'Customer jayl placed order #14 with 2 item(s), total PHP 2,450.00.', '180.193.195.230', '2026-05-22 06:51:41'),
(271, 111, 'customer_checkout', 'orders', 15, 'Customer jayl placed order #15 with 2 item(s), total PHP 754.00.', '180.193.195.230', '2026-05-22 06:55:43'),
(272, 111, 'customer_logged_out', 'users', 111, 'Customer jayl logged out.', '180.193.195.230', '2026-05-22 06:57:17'),
(273, 2, 'admin_user_created', 'users', 112, 'Administrator created admin account for edwardo (edwardo@gmail.com).', '180.193.195.230', '2026-05-22 07:00:48'),
(274, 111, 'customer_logged_in', 'users', 111, 'Customer jayl logged in successfully.', '49.145.253.216', '2026-05-23 01:22:04'),
(275, 111, 'customer_checkout', 'orders', 16, 'Customer jayl placed order #16 with 1 item(s), total PHP 999.00.', '49.145.253.216', '2026-05-23 01:25:19'),
(276, 111, 'customer_order_cancelled', 'orders', 16, 'Customer jayl cancelled order #16.', '49.145.253.216', '2026-05-23 01:27:14'),
(277, 111, 'customer_checkout', 'orders', 17, 'Customer jayl placed order #17 with 1 item(s), total PHP 199.00.', '49.145.253.216', '2026-05-23 01:29:59'),
(278, 111, 'customer_logged_out', 'users', 111, 'Customer jayl logged out.', '49.145.253.216', '2026-05-23 01:31:51'),
(279, 2, 'admin_user_deleted', 'users', 112, 'Administrator deleted account edwardo.', '49.145.253.216', '2026-05-23 01:35:49'),
(280, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '112.208.73.29', '2026-05-23 01:36:41'),
(281, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '112.208.73.29', '2026-05-23 01:38:59'),
(282, 2, 'admin_user_deleted', 'users', 109, 'Administrator deleted account ed.', '49.145.253.216', '2026-05-23 01:39:34'),
(283, 2, 'admin_user_deleted', 'users', 41, 'Administrator deleted account ivy.', '49.145.253.216', '2026-05-23 01:40:17'),
(284, 2, 'admin_user_deleted', 'users', 15, 'Administrator deleted account van.', '112.208.73.29', '2026-05-23 01:45:01'),
(285, 2, 'admin_user_deleted', 'users', 13, 'Administrator deleted account jerox.', '112.208.73.29', '2026-05-23 01:45:26'),
(286, 2, 'admin_user_deleted', 'users', 33, 'Administrator deleted account ryle.', '112.208.73.29', '2026-05-23 02:36:35'),
(287, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '112.208.73.29', '2026-05-23 02:51:45'),
(288, 3, 'customer_checkout', 'orders', 18, 'Customer Jayle placed order #18 with 2 item(s), total PHP 1,629.00.', '112.208.73.29', '2026-05-23 02:53:47'),
(289, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '112.208.73.29', '2026-05-23 03:02:19'),
(290, NULL, 'customer_logged_in', 'users', 113, 'Customer Ahh logged in successfully.', '112.208.73.29', '2026-05-23 03:06:29'),
(291, NULL, 'customer_logged_out', 'users', 113, 'Customer Ahh logged out.', '112.208.73.29', '2026-05-23 03:06:35'),
(292, 2, 'admin_user_deleted', 'users', 113, 'Administrator deleted account Ahh.', '112.208.73.29', '2026-05-23 03:06:53'),
(293, 2, 'admin_password_reset', 'users', 11, 'Administrator reset the password for Haha.', '112.208.73.29', '2026-05-23 03:09:36'),
(294, 11, 'customer_logged_in', 'users', 11, 'Customer Haha logged in successfully.', '112.208.73.29', '2026-05-23 03:10:10'),
(295, 11, 'customer_logged_out', 'users', 11, 'Customer Haha logged out.', '112.208.73.29', '2026-05-23 03:10:17'),
(296, 11, 'customer_logged_in', 'users', 11, 'Customer Haha logged in successfully.', '112.208.73.29', '2026-05-23 03:10:36'),
(297, 114, 'customer_logged_in', 'users', 114, 'Customer HA logged in successfully.', '112.208.73.29', '2026-05-23 04:10:13'),
(298, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-25 02:20:59'),
(299, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-05-25 02:22:23'),
(300, 2, 'admin_user_created', 'users', 115, 'Administrator created customer account for lj (lj@gmail.com).', '180.193.195.230', '2026-05-25 02:25:53'),
(301, NULL, 'customer_logged_in', 'users', 115, 'Customer lj logged in successfully.', '180.193.195.230', '2026-05-25 02:26:59'),
(302, NULL, 'customer_logged_out', 'users', 115, 'Customer lj logged out.', '180.193.195.230', '2026-05-25 02:27:18'),
(303, 2, 'admin_user_deleted', 'users', 115, 'Administrator deleted account lj.', '180.193.195.230', '2026-05-25 02:27:41'),
(304, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-26 03:37:13'),
(305, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-05-26 03:37:54'),
(306, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-26 03:38:09'),
(307, 3, 'customer_checkout', 'orders', 19, 'Customer Jayle placed order #19 with 2 item(s), total PHP 2,317.00.', '180.193.195.230', '2026-05-26 03:39:07'),
(308, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-05-26 04:37:45'),
(309, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-26 05:38:20'),
(310, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-05-26 05:39:53'),
(311, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-26 06:50:46'),
(312, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-05-26 07:12:07'),
(313, 116, 'customer_logged_in', 'users', 116, 'Customer edward logged in successfully.', '180.193.195.230', '2026-05-26 07:15:35'),
(314, 116, 'customer_checkout', 'orders', 20, 'Customer edward placed order #20 with 3 item(s), total PHP 2,849.00.', '180.193.195.230', '2026-05-26 07:17:56'),
(315, 116, 'customer_checkout', 'orders', 21, 'Customer edward placed order #21 with 2 item(s), total PHP 1,169.00.', '180.193.195.230', '2026-05-26 07:19:02'),
(316, 116, 'customer_logged_out', 'users', 116, 'Customer edward logged out.', '180.193.195.230', '2026-05-26 07:19:40'),
(317, 116, 'customer_logged_in', 'users', 116, 'Customer edward logged in successfully.', '180.193.195.230', '2026-05-26 07:25:27'),
(318, 116, 'customer_logged_out', 'users', 116, 'Customer edward logged out.', '180.193.195.230', '2026-05-26 07:26:02'),
(319, 2, 'admin_user_created', 'users', 117, 'Administrator created admin account for les (les@gmail.com).', '180.193.195.230', '2026-05-26 07:27:37'),
(320, 117, 'admin_password_reset', 'users', 116, 'Administrator reset the password for edward.', '180.193.195.230', '2026-05-26 07:28:43'),
(321, 117, 'admin_user_updated', 'users', 116, 'Administrator updated account #116 (edward) from Customer to Admin.', '180.193.195.230', '2026-05-26 07:29:05'),
(322, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-26 07:31:10'),
(323, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.195.230', '2026-05-26 07:34:45'),
(324, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.212.130', '2026-05-26 07:55:33'),
(325, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.212.130', '2026-05-26 08:01:25'),
(326, 3, 'customer_checkout', 'orders', 22, 'Customer Jayle placed order #22 with 6 item(s), total PHP 5,301.00.', '180.193.212.130', '2026-05-26 08:31:48'),
(327, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '180.193.212.130', '2026-05-26 08:36:44'),
(328, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-26 10:49:48'),
(329, 3, 'customer_checkout', 'orders', 23, 'Customer Jayle placed order #23 with 2 item(s), total PHP 1,169.00.', '180.193.195.230', '2026-05-26 10:56:19'),
(330, 3, 'customer_checkout', 'orders', 24, 'Customer Jayle placed order #24 with 2 item(s), total PHP 798.00.', '180.193.195.230', '2026-05-26 10:59:14'),
(331, 3, 'customer_checkout', 'orders', 25, 'Customer Jayle placed order #25 with 2 item(s), total PHP 1,100.00.', '180.193.195.230', '2026-05-26 11:01:00'),
(332, 3, 'customer_order_cancelled', 'orders', 25, 'Customer Jayle cancelled order #25.', '180.193.195.230', '2026-05-26 11:01:13'),
(333, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '180.193.195.230', '2026-05-26 11:25:25'),
(334, 3, 'customer_checkout', 'orders', 26, 'Customer Jayle placed order #26 with 4 item(s), total PHP 1,997.00.', '180.193.195.230', '2026-05-26 11:27:52'),
(335, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '49.145.248.99', '2026-05-27 11:45:04'),
(336, 3, 'customer_checkout', 'orders', 27, 'Customer Jayle placed order #27 with 2 item(s), total PHP 669.00.', '49.145.248.99', '2026-05-27 11:45:45'),
(337, 3, 'customer_logged_out', 'users', 3, 'Customer Jayle logged out.', '49.145.248.99', '2026-05-27 11:49:57'),
(338, 3, 'customer_logged_in', 'users', 3, 'Customer Jayle logged in successfully.', '49.145.248.99', '2026-05-27 11:58:41');

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
(7, 'Test', 'Test', 'Test', 'test@gmail.com', '$2y$10$4aqTtmQ7oluX8t8SPWHzNOGSsk4ADZH5xuLErNiQ9NzpYqIKdLHDG', NULL, 0, 0, NULL, '8329bef2d762cc06c5d079ab50471e7a3fe9af17efdc7d251057c19ae4649e57', 'active', NULL, NULL, NULL, '2026-04-12 03:27:05', '2026-04-12 03:27:05'),
(10, 'ace', 'cona', 'acer', 'lordjemsaavedra@gmail.com', '$2y$10$l/mUOPoMHnKBxxt0rtpOm.1PUw512Ls2U8BcLo0hZTjpdXFD.Guie', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-13 04:25:32', '2026-04-13 04:37:39'),
(11, 'Ace', 'Azcona', 'Haha', 'xavierazcona0422@gmail.com', '$2y$10$sU.mB8F57dcuoDvF.MSkeu61GDgzAnAWAVTaPXqKgpVduZjOYevSK', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-13 04:33:11', '2026-05-23 03:09:36'),
(12, 'Efrelyn', 'Talan', 'Frelyn_', 'talanefrelyn@gmail.com', '$2y$10$ZAuAii9h/3nObDhHDdmI/OgyplhL6jKfmfIqyW6ABnMUPc3z/Bgwm', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-15 07:03:17', '2026-04-30 10:29:50'),
(14, 'daniel', 'arcilla', 'dan', 'dan@beautymart.com', '$2y$10$WROyqyoTIeYtmEwqNIvqq.aGTrNfyZ0dd2U1K.M2h7KUlb7mxulEO', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 10:34:05', '2026-04-30 10:34:05'),
(16, 'jay', 'reyes', 'jaja', 'jay@beautymart.com', '$2y$10$w/DNjgH574RwXK.8J4vC5u33.A1nlCXe4aCQ8JBH4P9BN/5lLrKEi', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 10:38:21', '2026-04-30 10:38:21'),
(17, 'charish', 'doncillo', 'cha', 'cha@beautymart.com', '$2y$10$Neq5CUTG1KVPqHjr2Lm4quWhBDZSOfivx9ypBglA7VRhBINNASCK.', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 10:41:04', '2026-04-30 10:41:04'),
(18, 'Chane', 'Victorama', 'bon', 'bon@beautymart.com', '$2y$10$K7/qThBgiiXeomO7bbDyReAfYJZR8gNvd/O0Ak8QthApMNbXXGpJC', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 10:46:07', '2026-04-30 13:58:43'),
(19, 'Jecil', 'Dilla', 'jea', 'jea@beautymart.com', '$2y$10$iVGW/NaExN65QBdpqpKJ6OFS0HIsDL8loLmw5NIpNjHgvCapgMNfW', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 10:49:53', '2026-04-30 10:49:53'),
(20, 'Benjie', 'Copingco', 'ben', 'ben@gmail.com', '$2y$10$P3R4r79VjXnSrtqAozFpS.m1EDbBWImm/.u4ClkgcxEQsRsGT5ZY6', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 11:00:47', '2026-04-30 13:01:40'),
(23, 'Clark', 'Azcona', 'clarky', 'clarky@gmail.com', '$2y$10$u2yn5oMb2at9A3QvrUX78.3KfKNyOSOxnfC46lNav1NGg83clVC1O', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 13:20:12', '2026-04-30 13:20:12'),
(24, 'ja', 'min', 'jamin', 'jamin@gmail.com', '$2y$10$BMGJJy3ha3hjEPS0BRtru.7VR0PQ4.IjvX88ESYbPyCt/bBvNMj3i', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 13:52:22', '2026-04-30 13:52:22'),
(25, 'ma', 'xi', 'maxi', 'maxi@gmaiil.com', '$2y$10$YTFO7jI3lmbJ7nspUUxoaeERYgMtoa2j4B3t7uNwV8wTyM3z45OwO', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:04:24', '2026-04-30 14:04:24'),
(26, 'ze', 'dy', 'zedy', 'zedy@gmail.com', '$2y$10$jjFzvNOdejocZZReBNQ4QOb3yJn1sW4tr7TIs7jYYm0E3ZsY0n/D2', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:06:45', '2026-04-30 14:06:45'),
(28, 'mi', 'co', 'mico', 'mico@gmail.com', '$2y$10$dcVFzdLSfKUSpL68P6aAC.r7sd7WpgfdvNgE0gYEPyvFhdXip5qJ2', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:08:15', '2026-04-30 14:08:15'),
(29, 'jen', 'ny', 'jenny', 'jenny@gmail.com', '$2y$10$eQLgiy.LPS4dpUDxWCYvSeSdEoeU0HpP0JKJ7ifAAtCYd6uTyn6PK', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:09:25', '2026-04-30 14:09:25'),
(30, 'Av', 'A', 'ava', 'ava@gmail.com', '$2y$10$yISejdiOg8xHEg7KFXWtUuL5hCLP.HHXSjeXjnvkQTqO5fbFFM8Ly', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:10:30', '2026-04-30 14:10:30'),
(31, 'eza', 'len', 'eza', 'eza@gmail.com', '$2y$10$f3Yv4jeyZJTRcoOnUUsf9Ong.XexqwH/4HIPc.OzbayTJqSA4YyIe', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:11:25', '2026-04-30 14:11:25'),
(32, 'Ra', 'Va', 'rava', 'rava@gmail.com', '$2y$10$es7zapx26iBS5BBhE7MokO2HPkEQlWZaAKb6cp62die/.qE/Fx1wG', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:12:14', '2026-04-30 14:12:14'),
(34, 'Ky', 'Le', 'kyle', 'kyle@gmail.com', '$2y$10$1kkoW5IP52jKqcEcCuBsiOHCijfSbKW.WjqkDO41LJuJzOJvxwWjG', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:14:16', '2026-04-30 14:14:16'),
(35, 'Ja', 'Xy', 'jax', 'jax@gmail.com', '$2y$10$LK7djnhYEsbSVB1pz5wlbur/7uwwyDPjLjti8xscb9rGh5JguI6cW', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:15:20', '2026-04-30 14:15:20'),
(36, 'Mi', 'Ka', 'mik', 'mika@gmail.com', '$2y$10$MQJF7qL9pmk10EKElIkiPeHpovn.64Di8iJK0WpCxEZDv67KPK8JG', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:16:05', '2026-04-30 14:16:05'),
(37, 'Zi', 'On', 'zion', 'zion@gmail.com', '$2y$10$.K1SiEdjH77kVOutHp1hBOUMRDq0ymv.uyQoqrNA5F/JK1ttR1qRe', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:16:50', '2026-04-30 14:16:50'),
(38, 'Yu', 'Ri', 'yuri', 'yuri@gmail.com', '$2y$10$eSknLDKBJnMu94c79OY6V.2vgj2ajmDwRxgCEHsfaSgRaHqqF7z06', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:17:34', '2026-04-30 14:17:34'),
(39, 'Ni', 'Al', 'nial', 'nial@gmail.com', '$2y$10$jtopINPifyLXFE0/s0IPl.tJyak9TK0Pn1/S6B244JgVykie5kh.2', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:18:19', '2026-04-30 14:18:19'),
(40, 'Ry', 'Ns', 'ryns', 'ryns@gmail.com', '$2y$10$0XNkGZ3Z.Y22aDeolR0e0emPPXS5Jf3PLmkSGfqyqfFDw8.CufHeW', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:18:59', '2026-04-30 14:18:59'),
(42, 'Aa', 'Ron', 'ron', 'ron@gmail.com', '$2y$10$JJ3.2O.NQq8P3I9Zm7Zpgu1nDmuNjb2vTuwQ0z9Bak26Z0TEEXnB.', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:22:01', '2026-04-30 14:22:01'),
(43, 'Ai', 'Ra', 'aira', 'aira@gmail.com', '$2y$10$xJfOLMgIQoHkLuqMmvxaBOsuLRlwWrnii2T09UoIymMrgTJ9u4IPa', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:22:44', '2026-04-30 14:22:44'),
(44, 'Na', 'Ya', 'naya', 'naya@gmail.com', '$2y$10$P4dDnTEYzQAz9rYJwLrejOEs8oVXevqLqxSF0vjsP0skISgMrzcFG', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-04-30 14:24:36', '2026-04-30 14:24:36'),
(45, 'In', 'No', 'ino', 'ino@gmail.com', '$2y$10$i66LxfZXlOJpeIiiNmWanerYLBM.RDFnHE6r.pS/4PnxAjGrCjBQm', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:02:09', '2026-05-01 03:02:09'),
(46, 'Ir', 'I', 'iri', 'iri@gmail.com', '$2y$10$GhM7Xm1Co/zmSWqIgA7ZQeU6H3ezigwrkB4OGGlZLE9kTuy4Puaom', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:03:21', '2026-05-01 03:03:21'),
(47, 'A', 'lo', 'alo', 'alo@gmail.com', '$2y$10$6c4ePNDrbZOnA6RAxwY0i.XpYVwt6CN92zSgntZjBP580UQqwa/Wu', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:05:59', '2026-05-01 03:05:59'),
(48, 'Iz', 'Zy', 'izy', 'izy@gmail.com', '$2y$10$dMY4ONY6KdyxnCDFcfLqsOhf2uCP3csDTKgqLMofraHnMstZe6vWu', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:06:44', '2026-05-01 03:06:44'),
(49, 'Ri', 'O', 'rio', 'rio@gmail.com', '$2y$10$aMZLNda6DHbuqwwT5EWmbOnMTwCBNezCtQYqMUDA4srvnyk28LGhK', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:07:35', '2026-05-01 03:07:35'),
(50, 'Na', 'Vy', 'navy', 'navy@gmail.com', '$2y$10$SS1GCyadsEcFhrEzCcbV0u1kgn/8UeoymFSemX71KY1mUbZqz2Ns6', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:08:26', '2026-05-01 03:08:26'),
(51, 'Kr', 'Ix', 'krix', 'krix@gmail.com', '$2y$10$hjrw.HZXasFVCZbrP4EcXOndQBaEWyQHdfxFafY0BsmugPs6IESWq', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:10:35', '2026-05-01 03:10:35'),
(52, 'Ka', 'Ye', 'kaye', 'kaye@gmail.com', '$2y$10$.YouTscpG0SKKMwziA3ueeHSQLACiAtlbcVqEgHBd1kGMR1s2vk3K', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:11:28', '2026-05-01 03:11:28'),
(53, 'Ki', 'An', 'kian', 'kian@gmail.com', '$2y$10$s851/bNx7JSo.5Y45COAqOCkNUVtFfpLV9r2kC6/db1wUoUPX2Rme', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:12:59', '2026-05-01 03:12:59'),
(54, 'Lu', 'Na', 'luna', 'luna@gmail.com', '$2y$10$8c22ReCL7MS.oGzDr2vGE.Eeuzrc/A.qqlkJp/A6wo6KkEakomYzy', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:13:35', '2026-05-01 03:13:35'),
(55, 'Za', 'Ra', 'zara', 'zara@gmail.com', '$2y$10$yhWGw5fB8uNtPTs38qrGYu.2bFRcNF9k.g4vlthhILpSlFf1GDmIK', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:14:14', '2026-05-01 03:14:14'),
(56, 'Ha', 'Na', 'hana', 'hana@gmail.com', '$2y$10$waGWWmTeGfVH9RC8hC3YwOrdcBReazTu/vBoBz1iEmLJNqUTXfK86', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:14:56', '2026-05-01 03:14:56'),
(57, 'Ze', 'Ke', 'zeke', 'zeke@gmail.com', '$2y$10$OvHXFehM58LxDrXyptYaL.pLeNNotWnjBbKMUtiykB8aVY/xAg5xO', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:19:53', '2026-05-01 03:19:53'),
(58, 'Le', 'Xe', 'lex', 'lex@gmail.com', '$2y$10$enFU99Qz1V9sjaK4.4.JgOFgf0tXTKxTRhe2p5R7fwpwMYvUauvLy', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:20:46', '2026-05-01 03:20:46'),
(59, 'Ze', 'No', 'zeno', 'zeno@gmail.com', '$2y$10$N.LsruVPwCNuWQH7MZt56OqTnQz.b9mzlSvFrCTu.3AWVZs3Nt47G', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:21:31', '2026-05-01 03:21:31'),
(60, 'La', 'Ra', 'lara', 'lara@gmail.com', '$2y$10$GJ0DlvP07jjGwL5/ZXfjROplK2/8VNqNvmDnKxqPbaWVQuz8YL50i', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:22:14', '2026-05-01 03:22:14'),
(61, 'Kai', 'Lie', 'kai', 'kai@gmail.com', '$2y$10$6zbd0fVR7fMNfQ55WNLN2elLdwR1zLL6T2KyaLSU1.v7r/OCVNQjG', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:23:06', '2026-05-01 03:23:06'),
(62, 'Jay', 'Son', 'jayson', 'jayson@gmail.com', '$2y$10$uC4O.Eqw5sV2.ChjXaHLpODsHkDSj0GYgcpB/PfsYnrIUUX6CGq8C', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:55:06', '2026-05-01 03:55:06'),
(63, 'An', 'Gela', 'angela', 'angela@gmail.com', '$2y$10$q.AudrQejF6Li06jMDXzuu/ZD5R1gaVzeATxFQirwjYRSFahuHkAe', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:56:32', '2026-05-01 03:56:32'),
(64, 'Ke', 'Vin', 'kevin', 'kevin@gmail.com', '$2y$10$BtciWHX00n8XNgSef/MNgOe.45uQN.C2YjtgPNZ8lkZ3yZRA3BRAi', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:57:47', '2026-05-01 03:57:47'),
(65, 'Ma', 'Ria', 'maria', 'maria@gmail.com', '$2y$10$RA4tWmIykVv4WmHzT5q7pulIGM.3A3/kilIw01QudGxzaubCXHY.u', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 03:58:57', '2026-05-01 03:58:57'),
(66, 'Mar', 'Kus', 'mar', 'mar@gmail.com', '$2y$10$LD4lty97Oz4nm0Kz.ZardunAFGDqOT1UzccbrW8RXRW7.GgeV3a9K', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:00:22', '2026-05-01 04:00:22'),
(67, 'Ja', 'Ne', 'jane', 'jane@gmail.com', '$2y$10$LTbeK4jYljf.ZZT2wgCE9etjSZ/MDJ2jR6PFv2yB3vzDu.AhqlVb6', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:01:46', '2026-05-01 04:01:46'),
(68, 'An', 'Drew', 'andrew', 'andrew@gmail.com', '$2y$10$iYdDKoa5WPSgBeX1kAvjIe7v3s2DIGqXyFRSMuNn940nNtTFiPE4u', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:10:19', '2026-05-01 04:10:19'),
(69, 'Ar', 'Lyn', 'arlyn', 'arlyn@gmail.com', '$2y$10$jUX5qxAv4UKdgZ/fQxYR5.k.0ZKzBjnJqe9M.tlbG4TvzkwiM9Xd2', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:13:34', '2026-05-01 04:13:34'),
(70, 'Na', 'Than', 'nathan', 'nathan@gmail.com', '$2y$10$TZ41aVjqbZ.Yt/0J5shaLO.knz3UxXop9dukNI.A.ppTSeQY5E3Ku', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:15:41', '2026-05-01 04:15:41'),
(71, 'Kris', 'Ty', 'kris', 'kris@gmail.com', '$2y$10$/XQSsM0VIfjaKwDzradO6ub3uK3cEQtGIDrDrcvUMklr7O0SZtBIy', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:19:07', '2026-05-01 04:19:07'),
(72, 'Bri', 'An', 'bry', 'bry@gmail.com', '$2y$10$ZIOetMHx8ipvoAfu/ZJszeKHOrenhdElmaKbGx7J7fjk7KpEb72QS', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:24:17', '2026-05-01 04:24:17'),
(73, 'Alon', 'Zo', 'alonzo', 'alonzo@gmail.com', '$2y$10$CN8DF5eN4MWgNcWjrNYDWOVIRKmPr4l9gtDxBIo3CcTqR2q.xiV0a', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:31:30', '2026-05-01 04:31:30'),
(74, 'Jere', 'My', 'jeremy', 'jeremy@gmail.com', '$2y$10$6o6kA8kJA2SblcQQkrrQ6Oae6yE1B/Bjyy.DFWPklqcnE4vqXgl/a', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:33:23', '2026-05-01 04:33:23'),
(75, 'Mi', 'Chael', 'michael', 'michael@gmail.com', '$2y$10$cxzn5XTTytbHQLGlq5XNn.xj0iayKD.RJhOw6F8R67K4URAw/st22', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:35:38', '2026-05-01 04:35:38'),
(76, 'Chloe', 'Reyes', 'chloe', 'chloe@gmail.com', '$2y$10$THtLTX5tm6TSXDVO4.s/wuLyvIfuI6TaXLNt.gCiAPTAxIvK0KRDe', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:37:51', '2026-05-01 04:37:51'),
(77, 'Vic', 'Tor', 'victor', 'victor@gmail.com', '$2y$10$D6Y//CH0th3OVAvCF0gs7OqyQDPrFOfSEe2qGbAfi5pUul8Kg0dUq', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:39:54', '2026-05-01 04:39:54'),
(78, 'Al', 'Bert', 'albert', 'al@gmail.com', '$2y$10$ATtpGMGspPIU3rHUCKH6he4BHRwf83HGBU0gq6bEEh3K9A63l9/um', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:44:39', '2026-05-01 04:44:39'),
(79, 'Em', 'Ma', 'emma', 'emma@gmail.com', '$2y$10$XDzTIArsKCkG7WKXXvboPO6KX6JwAi9Kb9zp1f4a.wBydtDMlMpse', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:48:46', '2026-05-01 04:48:46'),
(80, 'Li', 'Ly', 'lily', 'lily@gmail.com', '$2y$10$dG67NOIT7Ur99pm4krt7Ruxltxy/cnW7THfaM.qG5A5SeVNui6uUq', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:53:51', '2026-05-01 04:53:51'),
(81, 'Jo', 'Seph', 'joseph', 'joseph@gmail.com', '$2y$10$PVGlik64TWdqcrNmG8Vd9OBBVL5Ejmwn1lD.QTcQKzk7VM1vQZIMe', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:55:13', '2026-05-01 04:55:13'),
(82, 'Ad', 'Rian', 'adrian', 'adrian@gmail.com', '$2y$10$a84ZP8lJE/tJwO6VS7FNx.u1uoUyEM3FyG2wg8gd81HC6IUiTmxv.', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:56:35', '2026-05-01 04:56:35'),
(83, 'Car', 'Los', 'carlos', 'carlos@gmail.com', '$2y$10$fJOb.ItsHQ61uWlLbVNt4.8njKLvLZcxN8sXwsDnxc5qX5WmKoBmy', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:57:46', '2026-05-01 04:57:46'),
(84, 'Li', 'Za', 'liza', 'liza@gmail.com', '$2y$10$GvSN8pQ79tXz39ZOdTe4juueYzJGHmP/Q/4XIeqcQ/H2GNhBkCRPm', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01 04:58:58', '2026-05-01 04:58:58'),
(85, 'Dia', 'Na', 'diana', 'diana@gmail.com', '$2y$10$QyRiYQv4gYTufMNFsrTJh.cZSVi8dt/lWmQvbJcN8/pdDMXQlFNs.', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:02:52', '2026-05-02 00:02:52'),
(86, 'Ro', 'Nald', 'ronald', 'ronald@gmail.com', '$2y$10$cfr0/67r3FBIChnAePQqQuHGu6Q66q7PcU32QvK7bZj7eb3I2PUMK', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:04:22', '2026-05-02 00:04:22'),
(87, 'El', 'La', 'ella', 'ella@gmail.com', '$2y$10$.aKOPe7c8XMrQyA1MT6pqOAscOgpKUxKcKpLeKj0d09mG0orjCIra', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:05:46', '2026-05-02 00:05:46'),
(88, 'Sa', 'Rah', 'sarah', 'sarah@gmail.com', '$2y$10$01uzgp9frnEC8NNwecn6yurDwNHSMYl45zX8Xvc1/NOqva8FMnJdO', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:07:05', '2026-05-02 00:07:05'),
(89, 'Kie', 'Fer', 'kiefer', 'kef@gmail.com', '$2y$10$.nLfJsJ8sFX0S8qxM.tmGeMfJy/e1cz2RXFKXBuFpVdDzLAY5wflm', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:08:23', '2026-05-02 00:08:23'),
(90, 'Gab', 'Riel', 'gabriel', 'gabriel@gmail.com', '$2y$10$Gf9QWQugfLKA1Ue1QFtiaOi.q7uFw6TsEfAxIRMwdpPkGzUqwX3nu', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:10:01', '2026-05-02 00:10:01'),
(91, 'Gra', 'Ce', 'grace', 'grace@gmail.com', '$2y$10$lpTzqnEHItCrALsulBYKx.xbdkWfjuqL6yE.yQHZJhqC6OoIMcDWO', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:11:39', '2026-05-02 00:11:39'),
(92, 'So', 'Fia', 'sofia', 'sofia@gmail.com', '$2y$10$GwHbV1wNewFe0A1gxt./MOc0zdHgzm5PtmVNQiQhZIW8VcFS.Yy6a', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:13:13', '2026-05-02 00:13:13'),
(93, 'A', 'Vy', 'avy', 'avy@gmail.com', '$2y$10$84BK.WjZHW5bqRMHrtXs6.FlDqoBP9XnpsrrkuBe3QhbfH1xjCOHW', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:14:19', '2026-05-02 00:14:19'),
(94, 'Pao', 'Lo', 'pao', 'pao@gmail.com', '$2y$10$VfY7H8xoXZbzKBElL33DVunL.cEp0pNIu0DnA.ATDI9HBswGJ9UKS', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:18:05', '2026-05-02 00:18:05'),
(95, 'Jus', 'Tin', 'justin', 'justin@gmail.com', '$2y$10$n.dUc5T3ojyKqD6mKYbb8OR8r1RDOUWNu3oRK/8zkCuwjRx2kJXGq', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:19:24', '2026-05-02 00:19:24'),
(96, 'Mi', 'Ann', 'mia', 'mia@gmail.com', '$2y$10$/Gi/RxT0ffGvABetRqJECeZht7oFq4P8lWIG8HvSqL2Idn/QWlgAC', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:20:43', '2026-05-02 00:20:43'),
(97, 'Ri', 'Na', 'rina', 'rina@gmail.com', '$2y$10$hrZDVrOn4dapt4dYaPng4urnQ1LoBco221Jkxwvq3/jafLR0lN9ty', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:36:15', '2026-05-02 00:36:15'),
(98, 'Ni', 'Na', 'nina', 'nina@gmail.com', '$2y$10$YQ2XKy7utv/e32/F4OB2bubg94ls98YoVWg1jwDUQ1sJ8v29bXLSu', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:37:21', '2026-05-02 00:37:21'),
(99, 'Ni', 'El', 'niel', 'niel@gmail.com', '$2y$10$UU85ckMy20emaFSP5knnZeqPVd89BQQztKWU5Q3Dom0qwQTrFHLWO', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:38:30', '2026-05-02 00:38:30'),
(100, 'ken', 'ken', 'ken', 'ken@gmail.com', '$2y$10$RE.fwY.nH/m5jzlxs204L.Cq/r3Np3jGxSzDBbmq/CbhC8mgVUjBC', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 00:40:00', '2026-05-02 00:40:00'),
(101, 'Han', 'Nah', 'hannah', 'hannah@gmail.com', '$2y$10$4DTvpg70vjzitKfbxwWERelA7TCxCphwaNx1qIjzUYp4v3NcdTeS2', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 01:51:58', '2026-05-02 01:51:58'),
(102, 'Bea', 'Trice', 'bea', 'bea@gmail.com', '$2y$10$MPZCZrIb9sLaJcf51Z.cWumWQ.BHbPAfxyQIVUxttpWTH/PMJfnR6', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 02:02:00', '2026-05-02 02:02:00'),
(103, 'Mar', 'Vin', 'marvin', 'marvin@gmail.com', '$2y$10$hSKCRpIVAU7fEwDPYaEyJeh/s3bx7YqkGNE.mFU92Q56dHEL15iBS', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 02:04:44', '2026-05-02 02:04:44'),
(104, 'Jor', 'Den', 'den', 'den@gmail.com', '$2y$10$AAhAbMMsVOIz3MxF1jAYTOhSieJDcUMx6dwiKbH97wiyuZL/SjPDC', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 02:09:34', '2026-05-02 02:09:34'),
(106, 'Ste', 'Ven', 'steve', 'steve@gmail.com', '$2y$10$Yf6HxR3HSqsXoQOULigFmOAYpao58chyiidxWWknFDed12VAtYYFW', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 02:12:20', '2026-05-02 02:12:20'),
(107, 'Fran', 'Ces', 'frans', 'frans@gmail.com', '$2y$10$h996oAbwvUMlhXkJJS7RHuwdBhas7HmD6GTkJ9SahLLKK9NJx4nq6', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 02:14:22', '2026-05-02 02:14:22'),
(108, 'Hen', 'Ry', 'henry', 'henry@gmail.com', '$2y$10$3zcSSj9xtPvN5fqH0.NJNuj6j84louHqh6VlMi8rh2qBr90xmroCq', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-02 02:20:56', '2026-05-02 02:20:56'),
(114, 'Ace', 'Azcona', 'HA', 'internshipapplicationportal@gmail.com', '$2y$10$7fHhD6kpwAnjI1yDlHmL.eY/ZJXK6Q7pboiFN9gpXLTBRsdPVowy6', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-23 04:08:07', '2026-05-23 04:09:52'),
(116, 'edward', 'espanyo', 'edward', 'jayle.talan@nmsc.edu.ph', '$2y$10$Gx4nL./MmppHucbnw5eXJeKyXAxOiBVY2mD3fnhZO1w29AgWCki66', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-26 07:12:56', '2026-05-26 07:28:43'),
(117, 'lesly', 'chan', 'les', 'les@gmail.com', '$2y$10$JwbAzrsjAtoB/rMFHb5re.6b7G/A037V78IHBRArdKRHaNKYFeFmS', NULL, 1, 0, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-26 07:27:37', '2026-05-26 07:27:37');

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
(1, 1, 16, '2026-04-12 02:55:14'),
(2, 55, 8, '2026-05-22 02:26:10');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `deletion_requests`
--
ALTER TABLE `deletion_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `notification_types`
--
ALTER TABLE `notification_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `sellers`
--
ALTER TABLE `sellers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `seller_applications`
--
ALTER TABLE `seller_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `seller_earnings`
--
ALTER TABLE `seller_earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=339;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
