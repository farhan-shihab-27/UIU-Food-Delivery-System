-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 05, 2026 at 05:15 PM
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
-- Database: `uiu_food_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_action_logs`
--

CREATE TABLE `admin_action_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `action_type` varchar(60) NOT NULL,
  `target_type` varchar(40) DEFAULT NULL,
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_action_logs`
--

INSERT INTO `admin_action_logs` (`id`, `admin_id`, `action_type`, `target_type`, `target_id`, `description`, `created_at`) VALUES
(7, 4, 'approve_runner', 'runner', 4, 'Runner #4 approved', '2026-10-05 21:10:54'),
(8, 4, 'approve_shop', 'shop', 4, 'Shop #4 approved', '2026-10-05 21:10:57');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `menu_item_id` int(10) UNSIGNED NOT NULL,
  `quantity` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(10) UNSIGNED NOT NULL,
  `complaint_code` varchar(20) NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `shop_id` int(10) UNSIGNED DEFAULT NULL,
  `runner_id` int(10) UNSIGNED DEFAULT NULL,
  `category` enum('food_quality','late_delivery','wrong_order','missing_items','runner_behaviour','payment','other') NOT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `status` enum('open','in_review','resolved','closed') NOT NULL DEFAULT 'open',
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `screenshot_url` varchar(255) DEFAULT NULL,
  `resolution_note` text DEFAULT NULL,
  `handled_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `complaint_messages`
--

CREATE TABLE `complaint_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `complaint_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `shop_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `category` enum('burgers','pizza','rice','snacks','drinks','desserts','others') NOT NULL DEFAULT 'others',
  `price` decimal(8,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `prep_time_min` smallint(5) UNSIGNED DEFAULT NULL,
  `calories` smallint(5) UNSIGNED DEFAULT NULL,
  `dietary_tags` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `stock_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `shop_id`, `name`, `description`, `category`, `price`, `image_url`, `prep_time_min`, `calories`, `dietary_tags`, `is_available`, `stock_quantity`, `created_at`, `updated_at`) VALUES
(5, 4, 'Burger', 'Cheese bbq Burger', 'burgers', 5.00, 'uploads/menu/item_1791213108_4157.jpg', 10, 5, NULL, 1, 10, '2026-10-05 21:11:48', '2026-10-05 21:11:48');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `order_id`, `sender_id`, `receiver_id`, `content`, `is_read`, `created_at`) VALUES
(5, 6, 12, 11, 'hi', 1, '2026-10-05 21:13:28'),
(6, 6, 11, 12, 'hi\\', 0, '2026-10-05 21:13:41');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` varchar(500) DEFAULT NULL,
  `type` enum('order','promo','system','complaint') NOT NULL DEFAULT 'system',
  `link_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `link_url`, `is_read`, `created_at`) VALUES
(42, 12, 'Runner Account Approved', 'Your runner account has been approved. You can now accept deliveries!', 'system', 'runner-dashboard.php', 1, '2026-10-05 21:10:54'),
(43, 13, 'Shop Approved', '\"UIU Cafeteria Express\" has been approved. You can now start accepting orders!', 'system', 'shop-dashboard.php', 1, '2026-10-05 21:10:57'),
(44, 11, 'Wallet Topped Up', '$20.00 added via Bkash.', 'system', 'wallet.php', 1, '2026-10-05 21:12:56'),
(45, 13, 'New Order Received', 'Order #8502 placed by Shabab — $6.75', 'order', 'shop-orders.php', 1, '2026-10-05 21:13:06'),
(46, 11, 'Order Accepted', 'UIU Cafeteria Express accepted your order #8502', 'order', 'track-order.php?order_id=6', 1, '2026-10-05 21:13:16'),
(47, 11, 'Order Ready for Pickup', 'Your order #8502 is ready — a runner will pick it up soon.', 'order', 'track-order.php?order_id=6', 1, '2026-10-05 21:13:17'),
(48, 12, 'New Delivery Available', 'Order at UIU Cafeteria Express is ready for pickup (+$3.50)', 'order', 'runner-dashboard.php', 1, '2026-10-05 21:13:17'),
(49, 11, 'Runner On The Way', 'Istiaq picked up your order #8502 and is heading to you.', 'order', 'track-order.php?order_id=6', 1, '2026-10-05 21:13:21'),
(50, 11, 'Order Delivered', 'Your order #8502 has been delivered. Please rate the shop and runner!', 'order', 'rate-order.php?order_id=6', 1, '2026-10-05 21:14:18');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_code` varchar(20) NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `shop_id` int(10) UNSIGNED NOT NULL,
  `runner_id` int(10) UNSIGNED DEFAULT NULL,
  `delivery_address` varchar(255) NOT NULL,
  `delivery_note` varchar(255) DEFAULT NULL,
  `delivery_type` enum('asap','scheduled') NOT NULL DEFAULT 'asap',
  `scheduled_for` datetime DEFAULT NULL,
  `payment_method` enum('wallet','cash','card','bkash','nagad') NOT NULL DEFAULT 'wallet',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `promo_code_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('pending','accepted','rejected','preparing','ready','picked_up','on_the_way','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `placed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `accepted_at` datetime DEFAULT NULL,
  `ready_at` datetime DEFAULT NULL,
  `picked_up_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `student_id`, `shop_id`, `runner_id`, `delivery_address`, `delivery_note`, `delivery_type`, `scheduled_for`, `payment_method`, `payment_status`, `subtotal`, `delivery_fee`, `vat`, `discount`, `total`, `promo_code_id`, `status`, `placed_at`, `accepted_at`, `ready_at`, `picked_up_at`, `delivered_at`, `cancelled_at`, `cancel_reason`) VALUES
(6, '#8502', 4, 4, 4, 'mirpur-10', NULL, 'asap', NULL, 'wallet', 'paid', 5.00, 1.50, 0.25, 0.00, 6.75, NULL, 'delivered', '2026-10-05 21:13:06', NULL, NULL, '2026-10-05 21:13:21', '2026-10-05 21:14:18', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `menu_item_id` int(10) UNSIGNED DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `unit_price` decimal(8,2) NOT NULL,
  `quantity` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `item_name`, `unit_price`, `quantity`, `subtotal`) VALUES
(6, 6, 5, 'Burger', 5.00, 1, 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `status` varchar(30) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `changed_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `note`, `changed_by`, `created_at`) VALUES
(21, 6, 'pending', 'Order placed', 11, '2026-10-05 21:13:06'),
(22, 6, 'accepted', NULL, 13, '2026-10-05 21:13:16'),
(23, 6, 'ready', NULL, 13, '2026-10-05 21:13:17'),
(24, 6, 'picked_up', NULL, 12, '2026-10-05 21:13:21');

-- --------------------------------------------------------

--
-- Table structure for table `promo_codes`
--

CREATE TABLE `promo_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(40) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `discount_type` enum('fixed','percent') NOT NULL DEFAULT 'fixed',
  `discount_value` decimal(8,2) NOT NULL,
  `min_order` decimal(8,2) NOT NULL DEFAULT 0.00,
  `max_discount` decimal(8,2) DEFAULT NULL,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `usage_limit` int(10) UNSIGNED DEFAULT NULL,
  `used_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `shop_id` int(10) UNSIGNED NOT NULL,
  `runner_id` int(10) UNSIGNED DEFAULT NULL,
  `overall_rating` tinyint(3) UNSIGNED NOT NULL,
  `shop_rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `runner_rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `food_quality` tinyint(3) UNSIGNED DEFAULT NULL,
  `delivery_speed` tinyint(3) UNSIGNED DEFAULT NULL,
  `packaging` tinyint(3) UNSIGNED DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `order_id`, `student_id`, `shop_id`, `runner_id`, `overall_rating`, `shop_rating`, `runner_rating`, `food_quality`, `delivery_speed`, `packaging`, `comment`, `created_at`) VALUES
(5, 6, 4, 4, 4, 5, 5, 5, NULL, NULL, NULL, '', '2026-10-05 21:14:35');

-- --------------------------------------------------------

--
-- Table structure for table `runners`
--

CREATE TABLE `runners` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(30) NOT NULL,
  `vehicle_type` enum('bicycle','motorcycle','walking') NOT NULL DEFAULT 'bicycle',
  `document_url` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `rating_avg` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_deliveries` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_earnings` decimal(10,2) NOT NULL DEFAULT 0.00,
  `available_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pending_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `runners`
--

INSERT INTO `runners` (`id`, `user_id`, `student_id`, `vehicle_type`, `document_url`, `status`, `is_online`, `rating_avg`, `rating_count`, `total_deliveries`, `total_earnings`, `available_balance`, `pending_balance`, `approved_by`, `approved_at`, `created_at`) VALUES
(4, 12, '0112420555', 'motorcycle', 'uploads/runners/rdoc_1791212987_2505.jpg', 'approved', 0, 5.00, 1, 1, 3.50, 3.50, 0.00, 4, '2026-10-05 21:10:54', '2026-10-05 21:09:47');

-- --------------------------------------------------------

--
-- Table structure for table `runner_locations`
--

CREATE TABLE `runner_locations` (
  `id` int(10) UNSIGNED NOT NULL,
  `runner_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `runner_locations`
--

INSERT INTO `runner_locations` (`id`, `runner_id`, `order_id`, `latitude`, `longitude`, `updated_at`) VALUES
(4, 4, 6, 23.8000000, 90.3700000, '2026-10-05 21:13:25');

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `id` int(10) UNSIGNED NOT NULL,
  `owner_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `cuisine` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `document_url` varchar(255) DEFAULT NULL,
  `rating_avg` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
  `rejection_note` varchar(255) DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`id`, `owner_id`, `name`, `description`, `cuisine`, `address`, `image_url`, `document_url`, `rating_avg`, `rating_count`, `status`, `rejection_note`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(4, 13, 'UIU Cafeteria Express', NULL, NULL, NULL, 'uploads/shops/thumb_1791213043_8583.jpg', 'uploads/shops/doc_1791213043_3283.jpg', 5.00, 1, 'approved', NULL, 4, '2026-10-05 21:10:57', '2026-10-05 21:10:43', '2026-10-05 21:14:35');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(30) NOT NULL,
  `wallet_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `default_address` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `student_id`, `wallet_balance`, `default_address`, `created_at`) VALUES
(4, 11, '0112420269', 13.25, 'mirpur-10', '2026-10-05 21:09:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `role` enum('student','shop_owner','runner','admin') NOT NULL DEFAULT 'student',
  `profile_image` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `phone`, `role`, `profile_image`, `is_verified`, `is_active`, `created_at`, `updated_at`) VALUES
(4, 'Admin Principal', 'admin@uiu.edu', '$2y$10$HXFlnl0Mmm4x1yNKkjbwZOW3qWe/DiDDYvvm82UiZjI5kaAnFQMjO', '01700000000', 'admin', NULL, 1, 1, '2026-10-02 21:04:59', '2026-10-02 21:06:35'),
(11, 'Shabab', 'student@uiu.ac.bd', '$2y$10$B9rMGddEtXPOFraoiPEbqewKwSdGtaKXuhJVrlLAUPu5Sojzr58ES', '01805518252', 'student', 'uploads/profiles/u_1791212940_3768.png', 1, 1, '2026-10-05 21:09:00', '2026-10-05 21:09:00'),
(12, 'Istiaq', 'runner@uiu.ac.bd', '$2y$10$Gz1wKISuzzb/wM.Z7ufGM.1OoUQySM8ELjRVxNgd0EzjGRRHnyHpi', '01740135151', 'runner', 'uploads/profiles/u_1791212987_2201.png', 1, 1, '2026-10-05 21:09:47', '2026-10-05 21:09:47'),
(13, 'Chef Wazid', 'chef@uiu.ac.bd', '$2y$10$QjtCrfarAahgO2gCP29ZDuX.f80W30nMsb0tQGEnktbSjLBGq1WWO', 'chef@uiu.ac.bd', 'shop_owner', 'uploads/profiles/u_1791213043_3105.png', 1, 1, '2026-10-05 21:10:43', '2026-10-05 21:10:43');

-- --------------------------------------------------------

--
-- Table structure for table `user_payment_methods`
--

CREATE TABLE `user_payment_methods` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `bkash_number` varchar(30) DEFAULT NULL,
  `nagad_number` varchar(30) DEFAULT NULL,
  `card_holder` varchar(120) DEFAULT NULL,
  `card_number` varchar(30) DEFAULT NULL,
  `card_expiry` varchar(10) DEFAULT NULL,
  `bank_name` varchar(120) DEFAULT NULL,
  `bank_account_name` varchar(120) DEFAULT NULL,
  `bank_account_num` varchar(40) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_payment_methods`
--

INSERT INTO `user_payment_methods` (`id`, `user_id`, `bkash_number`, `nagad_number`, `card_holder`, `card_number`, `card_expiry`, `bank_name`, `bank_account_name`, `bank_account_num`, `updated_at`) VALUES
(2, 11, '01740135152', '01740135152', 'Shabab', '0000000000000000', '10/31', 'DBBL', 'Shabab', '0000000000000', '2026-10-05 21:12:46');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_order_details`
-- (See below for the actual view)
--
CREATE TABLE `v_order_details` (
`id` int(10) unsigned
,`order_code` varchar(20)
,`status` enum('pending','accepted','rejected','preparing','ready','picked_up','on_the_way','delivered','cancelled')
,`total` decimal(10,2)
,`placed_at` datetime
,`delivered_at` datetime
,`student_name` varchar(120)
,`student_code` varchar(30)
,`shop_name` varchar(150)
,`runner_name` varchar(120)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_shop_stats`
-- (See below for the actual view)
--
CREATE TABLE `v_shop_stats` (
`id` int(10) unsigned
,`name` varchar(150)
,`total_orders` bigint(21)
,`total_revenue` decimal(32,2)
,`avg_rating` decimal(6,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `type` enum('topup','order_payment','refund','transfer_in','transfer_out') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `direction` enum('credit','debit') NOT NULL,
  `method` enum('wallet','bkash','nagad','card','bank','cash') NOT NULL DEFAULT 'wallet',
  `reference` varchar(60) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('pending','success','failed') NOT NULL DEFAULT 'success',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `student_id`, `order_id`, `type`, `amount`, `direction`, `method`, `reference`, `description`, `status`, `created_at`) VALUES
(7, 4, NULL, 'topup', 20.00, 'credit', 'bkash', NULL, 'Wallet top-up', 'success', '2026-10-05 21:12:56'),
(8, 4, 6, 'order_payment', 6.75, 'debit', 'wallet', NULL, 'Order #8502', 'success', '2026-10-05 21:13:06');

-- --------------------------------------------------------

--
-- Structure for view `v_order_details`
--
DROP TABLE IF EXISTS `v_order_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_order_details`  AS SELECT `o`.`id` AS `id`, `o`.`order_code` AS `order_code`, `o`.`status` AS `status`, `o`.`total` AS `total`, `o`.`placed_at` AS `placed_at`, `o`.`delivered_at` AS `delivered_at`, `u_s`.`full_name` AS `student_name`, `s`.`student_id` AS `student_code`, `sh`.`name` AS `shop_name`, `u_r`.`full_name` AS `runner_name` FROM (((((`orders` `o` join `students` `s` on(`s`.`id` = `o`.`student_id`)) join `users` `u_s` on(`u_s`.`id` = `s`.`user_id`)) join `shops` `sh` on(`sh`.`id` = `o`.`shop_id`)) left join `runners` `r` on(`r`.`id` = `o`.`runner_id`)) left join `users` `u_r` on(`u_r`.`id` = `r`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_shop_stats`
--
DROP TABLE IF EXISTS `v_shop_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_shop_stats`  AS SELECT `sh`.`id` AS `id`, `sh`.`name` AS `name`, count(`o`.`id`) AS `total_orders`, coalesce(sum(`o`.`total`),0) AS `total_revenue`, round(avg(`r`.`overall_rating`),2) AS `avg_rating` FROM ((`shops` `sh` left join `orders` `o` on(`o`.`shop_id` = `sh`.`id` and `o`.`status` = 'delivered')) left join `reviews` `r` on(`r`.`shop_id` = `sh`.`id`)) GROUP BY `sh`.`id`, `sh`.`name` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_action_logs`
--
ALTER TABLE `admin_action_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_aal_admin` (`admin_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cart` (`student_id`,`menu_item_id`),
  ADD KEY `fk_cart_menu` (`menu_item_id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `complaint_code` (`complaint_code`),
  ADD KEY `fk_cmp_student` (`student_id`),
  ADD KEY `fk_cmp_order` (`order_id`),
  ADD KEY `fk_cmp_shop` (`shop_id`),
  ADD KEY `fk_cmp_runner` (`runner_id`),
  ADD KEY `fk_cmp_admin` (`handled_by`),
  ADD KEY `idx_cmp_status` (`status`);

--
-- Indexes for table `complaint_messages`
--
ALTER TABLE `complaint_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cm_complaint` (`complaint_id`),
  ADD KEY `fk_cm_sender` (`sender_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_menu_shop` (`shop_id`),
  ADD KEY `idx_menu_category` (`category`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_msg_sender` (`sender_id`),
  ADD KEY `fk_msg_receiver` (`receiver_id`),
  ADD KEY `idx_msg_order` (`order_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notif_user` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `fk_orders_promo` (`promo_code_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_student` (`student_id`),
  ADD KEY `idx_orders_shop` (`shop_id`),
  ADD KEY `idx_orders_runner` (`runner_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_oi_menu` (`menu_item_id`),
  ADD KEY `idx_oi_order` (`order_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_osh_user` (`changed_by`),
  ADD KEY `idx_osh_order` (`order_id`);

--
-- Indexes for table `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `fk_rev_student` (`student_id`),
  ADD KEY `fk_rev_shop` (`shop_id`),
  ADD KEY `fk_rev_runner` (`runner_id`);

--
-- Indexes for table `runners`
--
ALTER TABLE `runners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `fk_runners_admin` (`approved_by`),
  ADD KEY `idx_runners_status` (`status`);

--
-- Indexes for table `runner_locations`
--
ALTER TABLE `runner_locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_order` (`order_id`),
  ADD KEY `idx_runner` (`runner_id`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_shops_owner` (`owner_id`),
  ADD KEY `fk_shops_admin` (`approved_by`),
  ADD KEY `idx_shops_status` (`status`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_email` (`email`);

--
-- Indexes for table `user_payment_methods`
--
ALTER TABLE `user_payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_wt_order` (`order_id`),
  ADD KEY `idx_wt_student` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_action_logs`
--
ALTER TABLE `admin_action_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `complaint_messages`
--
ALTER TABLE `complaint_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `runners`
--
ALTER TABLE `runners`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `runner_locations`
--
ALTER TABLE `runner_locations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_payment_methods`
--
ALTER TABLE `user_payment_methods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_action_logs`
--
ALTER TABLE `admin_action_logs`
  ADD CONSTRAINT `fk_aal_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `fk_cmp_admin` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cmp_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cmp_runner` FOREIGN KEY (`runner_id`) REFERENCES `runners` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cmp_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cmp_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `complaint_messages`
--
ALTER TABLE `complaint_messages`
  ADD CONSTRAINT `fk_cm_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cm_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `fk_menu_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_msg_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_promo` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_runner` FOREIGN KEY (`runner_id`) REFERENCES `runners` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orders_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_oi_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `fk_osh_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_osh_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_rev_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rev_runner` FOREIGN KEY (`runner_id`) REFERENCES `runners` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rev_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rev_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `runners`
--
ALTER TABLE `runners`
  ADD CONSTRAINT `fk_runners_admin` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_runners_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `runner_locations`
--
ALTER TABLE `runner_locations`
  ADD CONSTRAINT `fk_rl_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rl_runner` FOREIGN KEY (`runner_id`) REFERENCES `runners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shops`
--
ALTER TABLE `shops`
  ADD CONSTRAINT `fk_shops_admin` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_shops_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_payment_methods`
--
ALTER TABLE `user_payment_methods`
  ADD CONSTRAINT `fk_upm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `fk_wt_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_wt_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
