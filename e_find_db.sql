-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 24, 2026 at 12:17 PM
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
-- Database: `e_find_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `is_read`, `created_at`) VALUES
(1, 'kuzanya johnbosco', 'kuzijohnbosco@gmail.com', 'i want a product', 'they are taking long', 0, '2026-05-15 12:20:34');

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `delivery_person_id` int(11) DEFAULT NULL,
  `status` enum('pending','assigned','picked_up','in_transit','delivered','failed') DEFAULT 'pending',
  `pickup_location` text DEFAULT NULL,
  `delivery_address` text NOT NULL,
  `estimated_time` timestamp NULL DEFAULT NULL,
  `actual_delivery_time` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `signature` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`id`, `order_id`, `delivery_person_id`, `status`, `pickup_location`, `delivery_address`, `estimated_time`, `actual_delivery_time`, `started_at`, `rating`, `delivery_notes`, `signature`, `created_at`, `updated_at`) VALUES
(1, 5, 2, 'in_transit', NULL, 'kito', NULL, NULL, NULL, NULL, 'client not the location', NULL, '2026-05-15 13:30:03', '2026-05-20 13:03:54'),
(2, 6, 2, 'delivered', NULL, 'kira plot 045', NULL, '2026-05-19 10:32:46', NULL, NULL, NULL, NULL, '2026-05-16 11:30:30', '2026-05-19 10:32:46'),
(3, 9, 2, 'picked_up', NULL, 'kampala', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-16 13:18:25', '2026-05-19 10:32:40'),
(4, 10, 2, 'in_transit', NULL, 'Namugogo', NULL, NULL, '2026-05-19 11:18:43', NULL, NULL, NULL, '2026-05-17 08:46:53', '2026-05-20 13:02:40'),
(5, 8, 2, 'in_transit', NULL, 'kireka', NULL, NULL, '2026-05-19 11:19:23', NULL, 'client not at the location\r\n', NULL, '2026-05-18 10:53:13', '2026-05-20 14:29:08'),
(7, 13, 2, 'in_transit', NULL, 'kasubi', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-19 13:21:29', '2026-05-19 13:50:42');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_address_updates`
--

CREATE TABLE `delivery_address_updates` (
  `id` int(11) NOT NULL,
  `delivery_id` int(11) NOT NULL,
  `old_address` text DEFAULT NULL,
  `new_address` text NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `updated_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_address_updates`
--

INSERT INTO `delivery_address_updates` (`id`, `delivery_id`, `old_address`, `new_address`, `reason`, `updated_by`, `created_at`) VALUES
(1, 1, 'kampala', 'gayaza', 'client changed location', 2, '2026-05-18 10:48:31'),
(2, 1, 'gayaza', 'kito', 'client is there', 2, '2026-05-19 11:10:49'),
(3, 5, 'kampala', 'kireka', 'client changed location', 2, '2026-05-20 12:45:03');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `order_id`, `type`, `title`, `message`, `is_read`, `data`, `created_at`) VALUES
(1, 3, 2, 'delivery_date', '', 'Estimated delivery date for order #EF-20260513-EDBD78 set to May 30, 2026.', 1, NULL, '2026-05-16 12:25:22'),
(2, 3, 9, 'delivery_date', '', 'Estimated delivery date for order #EF-20260516-F0FD62 set to May 23, 2026.', 0, NULL, '2026-05-16 13:18:12'),
(3, 3, 10, 'delivery_date', '', 'Estimated delivery date for order #EF-20260517-7B23DB set to May 20, 2026.', 1, NULL, '2026-05-17 08:47:11'),
(4, 3, 8, 'delivery_date', '', 'Estimated delivery date for order #EF-20260516-2EFCB3 set to May 19, 2026.', 0, NULL, '2026-05-18 10:54:05');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` enum('pending','confirmed','processing','ready','in_transit','delivered','completed','cancelled') DEFAULT 'pending',
  `customization_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`customization_details`)),
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_method` enum('door_delivery','pickup') DEFAULT 'pickup',
  `delivery_address` text DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `estimated_delivery` date DEFAULT NULL,
  `actual_delivery` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `service_id`, `order_number`, `status`, `customization_details`, `quantity`, `unit_price`, `total_amount`, `delivery_method`, `delivery_address`, `delivery_notes`, `estimated_delivery`, `actual_delivery`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'EF-20260513-C2A193', 'in_transit', '{\"details\":\"kuzi\"}', 2, 15000.00, 30000.00, 'door_delivery', 'kampala', 'ALRIGHT', NULL, NULL, '2026-05-13 09:21:16', '2026-05-15 12:31:17'),
(2, 3, 1, 'EF-20260513-EDBD78', 'processing', '{\"details\":\"blue color\"}', 1, 15000.00, 15000.00, 'door_delivery', 'kampala', 'hhkkllkh', '2026-05-30', NULL, '2026-05-13 09:31:58', '2026-05-16 12:25:22'),
(3, 3, 2, 'EF-20260513-54A75A', 'delivered', '{\"details\":\"color\"}', 4, 10000.00, 40000.00, 'door_delivery', 'kampala', 'hhhhh', '2026-05-24', NULL, '2026-05-13 09:35:33', '2026-05-16 12:06:54'),
(4, 3, 1, 'EF-20260513-812710', 'confirmed', '{\"details\":\"text \"}', 3, 15000.00, 45000.00, 'door_delivery', 'kampala', 'jjjjjj', '2026-05-28', NULL, '2026-05-13 09:36:40', '2026-05-16 12:22:06'),
(5, 3, 5, 'EF-20260513-7ADAEF', 'in_transit', '{\"details\":\"dggfgg\"}', 1, 25000.00, 25000.00, 'door_delivery', 'kito', '', '2026-05-21', NULL, '2026-05-13 10:00:23', '2026-05-19 11:30:53'),
(6, 3, 1, 'EF-20260516-C8B836', 'delivered', '{\"details\":\"I want a word on my shirt\"}', 1, 15000.00, 15000.00, 'door_delivery', 'kira plot 045', 'my product should be delivered in the evening when i am back from work', '2026-05-30', NULL, '2026-05-16 11:12:12', '2026-05-19 10:32:46'),
(7, 3, 2, 'EF-20260516-06B55F', 'pending', '{\"details\":\"jjjjj\"}', 1, 10000.00, 10000.00, 'door_delivery', 'wakiso', 'bring them now', NULL, NULL, '2026-05-16 12:45:04', '2026-05-16 12:45:04'),
(8, 3, 2, 'EF-20260516-2EFCB3', 'in_transit', '{\"details\":\"hhjhjhj\"}', 1, 10000.00, 10000.00, 'door_delivery', 'kireka', 'hjhjkkj', '2026-05-19', NULL, '2026-05-16 12:54:58', '2026-05-20 12:45:03'),
(9, 3, 2, 'EF-20260516-F0FD62', 'pending', '{\"details\":\"jjjj\"}', 1, 10000.00, 10000.00, 'door_delivery', 'kampala', 'kkjhh', '2026-05-23', NULL, '2026-05-16 13:16:31', '2026-05-16 13:18:12'),
(10, 3, 1, 'EF-20260517-7B23DB', 'in_transit', '{\"details\":\"I want to Engrave Orlins on these metals\"}', 10, 15000.00, 150000.00, 'door_delivery', 'Namugogo', 'the lady who will pick up is ABCD', '2026-05-20', NULL, '2026-05-17 08:39:51', '2026-05-17 11:57:47'),
(11, 3, 1, 'EF-20260519-E8D4C2', 'pending', '{\"details\":\"I want to brand my Tshirt\"}', 3, 15000.00, 45000.00, 'door_delivery', 'kira ', 'I will be a round in the evening hours', NULL, NULL, '2026-05-19 10:04:30', '2026-05-19 10:04:30'),
(12, 2, 1, 'EF-20260519-C41C6E', 'pending', '{\"details\":\"gddfd\"}', 1, 15000.00, 15000.00, 'door_delivery', 'kasese', 'dfddg', NULL, NULL, '2026-05-19 10:25:16', '2026-05-19 10:25:16'),
(13, 3, 1, 'EF-20260519-6A083E', 'in_transit', '{\"details\":\"brand my phone cover\"}', 2, 15000.00, 30000.00, 'door_delivery', 'kasubi', 'deliver in the evening', '2026-05-21', NULL, '2026-05-19 13:17:42', '2026-05-19 13:21:45'),
(14, 3, 2, 'EF-20260520-3BF1E4', 'pending', '{\"details\":\"\"}', 1, 10000.00, 10000.00, 'door_delivery', 'kampala', '', NULL, NULL, '2026-05-20 14:31:15', '2026-05-20 14:31:15');

-- --------------------------------------------------------

--
-- Table structure for table `order_files`
--

CREATE TABLE `order_files` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_files`
--

INSERT INTO `order_files` (`id`, `order_id`, `file_path`, `original_name`, `mime_type`, `size`, `created_at`) VALUES
(1, 1, 'uploads/orders/1/1778664076_Fixed Assets Register _ Electoral Commission Uganda Admin electrical.pdf', 'Fixed Assets Register _ Electoral Commission Uganda Admin electrical.pdf', 'application/pdf', 2616506, '2026-05-13 09:21:16'),
(2, 2, 'uploads/orders/2/1778664718_fddfds.png', 'fddfds.png', 'image/png', 139326, '2026-05-13 09:31:58'),
(3, 3, 'uploads/orders/3/1778664933_Screenshot 2026-04-22 065333.png', 'Screenshot 2026-04-22 065333.png', 'image/png', 175438, '2026-05-13 09:35:33'),
(4, 4, 'uploads/orders/4/1778665000_fddfds.png', 'fddfds.png', 'image/png', 139326, '2026-05-13 09:36:40'),
(5, 5, 'uploads/orders/5/1778666423_ERTDS Kit Return Management System.pdf', 'ERTDS Kit Return Management System.pdf', 'application/pdf', 134553, '2026-05-13 10:00:23'),
(6, 6, 'uploads/orders/6/1778929932_bulambuli.pdf', 'bulambuli.pdf', 'application/pdf', 156137, '2026-05-16 11:12:12'),
(7, 7, 'uploads/orders/7/1778935504_Screenshot 2026-05-15 132141.png', 'Screenshot 2026-05-15 132141.png', 'image/png', 74254, '2026-05-16 12:45:04'),
(8, 10, 'uploads/orders/10/1779007191_orlins.png', 'orlins.png', 'image/png', 5659, '2026-05-17 08:39:51'),
(9, 11, 'uploads/orders/11/1779185070_orlins.png', 'orlins.png', 'image/png', 5659, '2026-05-19 10:04:30'),
(10, 12, 'uploads/orders/12/1779186316_orlins.png', 'orlins.png', 'image/png', 5659, '2026-05-19 10:25:16'),
(11, 13, 'uploads/orders/13/1779196662_orlins.png', 'orlins.png', 'image/png', 5659, '2026-05-19 13:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 'pending', 'Order placed successfully', 3, '2026-05-13 09:21:16'),
(2, 2, 'pending', 'Order placed successfully', 3, '2026-05-13 09:31:58'),
(3, 3, 'pending', 'Order placed successfully', 3, '2026-05-13 09:35:33'),
(4, 4, 'pending', 'Order placed successfully', 3, '2026-05-13 09:36:40'),
(5, 5, 'pending', 'Order placed successfully', 3, '2026-05-13 10:00:23'),
(6, 5, 'confirmed', 'Status updated by admin', 4, '2026-05-15 10:44:23'),
(7, 4, 'confirmed', 'Status updated by admin', 4, '2026-05-15 10:44:31'),
(8, 3, 'confirmed', 'Status updated by admin', 4, '2026-05-15 10:44:35'),
(9, 2, 'processing', 'Status updated by admin', 4, '2026-05-15 10:44:39'),
(10, 1, 'ready', 'Status updated by admin', 4, '2026-05-15 10:44:47'),
(11, 1, 'in_transit', 'Status updated by admin', 4, '2026-05-15 12:31:17'),
(12, 3, 'delivered', 'Status updated by admin', 4, '2026-05-15 12:31:36'),
(13, 5, 'ready', 'Status updated by admin', 4, '2026-05-15 13:29:47'),
(14, 6, 'pending', 'Order placed successfully', 3, '2026-05-16 11:12:12'),
(15, 6, 'in_transit', 'Status updated by admin', 4, '2026-05-16 11:21:03'),
(16, 7, 'pending', 'Order placed successfully', 3, '2026-05-16 12:45:04'),
(17, 8, 'pending', 'Order placed successfully', 3, '2026-05-16 12:54:58'),
(18, 9, 'pending', 'Order placed successfully', 3, '2026-05-16 13:16:31'),
(19, 10, 'pending', 'Order placed successfully', 3, '2026-05-17 08:39:51'),
(20, 10, 'confirmed', 'Status updated by admin', 4, '2026-05-17 08:44:26'),
(21, 10, 'in_transit', 'Status updated by admin', 4, '2026-05-17 08:46:30'),
(22, 10, 'completed', 'Status updated by admin', 4, '2026-05-17 09:00:01'),
(23, 8, 'in_transit', 'Status updated by admin', 4, '2026-05-18 10:52:56'),
(24, 11, 'pending', 'Order placed successfully', 3, '2026-05-19 10:04:30'),
(25, 12, 'pending', 'Order placed successfully', 2, '2026-05-19 10:25:16'),
(26, 13, 'pending', 'Order placed successfully', 3, '2026-05-19 13:17:42'),
(27, 13, 'in_transit', 'Status updated by admin', 4, '2026-05-19 13:21:13'),
(28, 14, 'pending', 'Order placed successfully', 3, '2026-05-20 14:31:15');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('mtn_momo','airtel_money','cash_on_delivery','card') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_method`, `amount`, `transaction_id`, `status`, `payment_details`, `paid_at`, `created_at`) VALUES
(1, 9, 'cash_on_delivery', 10000.00, NULL, 'pending', NULL, NULL, '2026-05-16 13:16:31'),
(2, 10, 'cash_on_delivery', 150000.00, NULL, 'pending', NULL, NULL, '2026-05-17 08:39:51'),
(3, 11, 'cash_on_delivery', 45000.00, NULL, 'pending', NULL, NULL, '2026-05-19 10:04:30'),
(4, 12, 'cash_on_delivery', 15000.00, NULL, 'pending', NULL, NULL, '2026-05-19 10:25:16'),
(5, 13, 'cash_on_delivery', 30000.00, NULL, 'pending', NULL, NULL, '2026-05-19 13:17:42'),
(6, 14, 'cash_on_delivery', 10000.00, NULL, 'pending', NULL, NULL, '2026-05-20 14:31:15');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rider_locations`
--

CREATE TABLE `rider_locations` (
  `id` int(11) NOT NULL,
  `delivery_id` int(11) NOT NULL,
  `rider_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rider_locations`
--

INSERT INTO `rider_locations` (`id`, `delivery_id`, `rider_id`, `latitude`, `longitude`, `updated_at`) VALUES
(1, 4, 2, 0.34760000, 32.58250000, '2026-05-20 13:02:40'),
(2, 5, 2, 0.31741916, 32.57552708, '2026-05-20 14:29:08'),
(3, 1, 2, 0.34760000, 32.58250000, '2026-05-20 13:03:54'),
(4, 7, 2, 0.31474000, 32.59831000, '2026-06-15 11:03:07');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `long_description` longtext DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `slug`, `description`, `long_description`, `icon`, `image_path`, `image`, `category`, `base_price`, `features`, `is_active`, `is_featured`, `created_at`, `updated_at`) VALUES
(1, 'Premium Metal Engraving', 'metal-engraving', 'Professional metal engraving services for trophies, plaques, and personalized gifts with precision laser technology.', 'Our metal engraving service uses state-of-the-art laser technology to create precise, permanent markings on various metals. Perfect for corporate awards, personalized gifts, and commemorative items.', 'fa-trophy', NULL, NULL, 'engraving', 15000.00, '[\"Laser precision engraving\", \"Multiple metal types supported\", \"Custom designs accepted\", \"Quick turnaround time\", \"Bulk order discounts\"]', 1, 1, '2026-05-12 03:19:23', '2026-05-18 07:06:40'),
(2, 'Custom Embroidery', 'custom-embroidery', 'High-quality embroidery for uniforms, caps, corporate wear, and promotional items with vibrant thread colors.', 'Professional embroidery service using industrial-grade machines. We handle everything from single items to bulk corporate orders with consistent quality.', 'fa-tshirt', NULL, NULL, 'embroidery', 10000.00, '[\"Industrial quality stitching\", \"Wide color selection\", \"Logo digitizing included\", \"Fast production time\", \"Sample approval process\"]', 1, 1, '2026-05-12 03:19:23', '2026-05-18 07:06:40'),
(3, 'GPS Vehicle Tracking', 'vehicle-tracking', 'Real-time GPS tracking solutions for cars, motorcycles, and fleet management with mobile app access.', 'Advanced GPS tracking with real-time location updates, geofencing, speed alerts, and comprehensive reporting. Monitor your vehicles 24/7.', 'fa-map-mar', NULL, NULL, 'tracking', 50000.00, '[\"Real-time tracking\", \"Geofence alerts\", \"Speed monitoring\", \"Travel history\", \"Mobile app access\"]', 1, 1, '2026-05-12 03:19:23', '2026-05-18 07:06:40'),
(4, 'Professional Calligraphy', 'calligraphy-services', 'Elegant handcrafted calligraphy for wedding invitations, certificates, and decorative art pieces.', 'Our skilled calligraphers create beautiful hand-lettered pieces using traditional and modern techniques. Each piece is a unique work of art.', 'fa-paint-b', NULL, NULL, 'calligraphy', 8000.00, '[\"Hand-crafted designs\", \"Multiple script styles\", \"Custom ink colors\", \"Digital proofs provided\", \"Premium paper options\"]', 1, 1, '2026-05-12 03:19:23', '2026-05-18 07:06:40'),
(5, 'Corporate Branding Package', 'corporate-branding', 'Complete branding solutions including logo design, business cards, letterheads, and brand guidelines.', 'Comprehensive branding package that creates a cohesive visual identity for your business. Includes multiple design concepts and revisions.', 'fa-palette', NULL, NULL, 'branding', 25000.00, '[\"Logo design (3 concepts)\", \"Business card design\", \"Letterhead design\", \"Brand guidelines document\", \"Social media kit\", \"3 revision rounds\"]', 1, 1, '2026-05-12 03:19:23', '2026-05-18 07:06:40'),
(6, 'Custom T-Shirt Printing', 'tshirt-printing', 'High-quality DTG and screen printing for custom t-shirts with vibrant, long-lasting prints.', 'Professional t-shirt printing using both Direct-to-Garment and traditional screen printing methods. Eco-friendly inks and premium garments.', 'fa-print', NULL, NULL, 'printing', 12000.00, '[\"DTG & Screen printing\", \"Premium quality shirts\", \"Eco-friendly inks\", \"Bulk order pricing\", \"Multiple color options\"]', 1, 1, '2026-05-12 03:19:23', '2026-05-18 07:06:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','admin','delivery') DEFAULT 'customer',
  `avatar` varchar(500) DEFAULT 'default-avatar.png',
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `avatar`, `address`, `city`, `is_active`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES
(2, 'John Rider', 'rider@efind.com', '$2y$10$7o6bZW/h8.eX1aQ4I.jiP.HLnZWrFssjpN1Tf1A.l1GKhVEvBrFEa', '+256700000001', 'delivery', 'default-avatar.png', '456 Delivery Lane', 'Kampala', 1, NULL, NULL, '2026-05-12 03:19:23', '2026-05-15 12:28:45'),
(3, 'kuzanya johnbosco', 'kuzijohnbosco@gmail.com', '$2y$10$z9X2dJJ9TZe4kNLBhBHO1emP0hgoMZ2jKsLEy3Ot.Eq8QOvc3iF1q', '0757156578', 'customer', 'default-avatar.png', 'kampala', 'kampala', 1, NULL, NULL, '2026-05-13 07:09:00', '2026-05-13 07:09:00'),
(4, 'Administrator', 'admin@efind.com', '$2y$10$J/wmDNfjcuz41/HqFIe3Y.4XRa30iZA9uNmGFX.Qw1Q0./gZrShbO', '+256700000000', 'admin', 'default-avatar.png', NULL, NULL, 1, NULL, NULL, '2026-05-14 08:18:52', '2026-05-15 10:26:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `delivery_person_id` (`delivery_person_id`);

--
-- Indexes for table `delivery_address_updates`
--
ALTER TABLE `delivery_address_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_id` (`delivery_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `order_files`
--
ALTER TABLE `order_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `rider_locations`
--
ALTER TABLE `rider_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_id` (`delivery_id`),
  ADD KEY `rider_id` (`rider_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `delivery_address_updates`
--
ALTER TABLE `delivery_address_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `order_files`
--
ALTER TABLE `order_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rider_locations`
--
ALTER TABLE `rider_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deliveries_ibfk_2` FOREIGN KEY (`delivery_person_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `delivery_address_updates`
--
ALTER TABLE `delivery_address_updates`
  ADD CONSTRAINT `delivery_address_updates_ibfk_1` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `delivery_address_updates_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_files`
--
ALTER TABLE `order_files`
  ADD CONSTRAINT `order_files_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rider_locations`
--
ALTER TABLE `rider_locations`
  ADD CONSTRAINT `rider_locations_ibfk_1` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rider_locations_ibfk_2` FOREIGN KEY (`rider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
