-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 16, 2026 at 11:36 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.4.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `awdsite_result`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `cat_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title`, `image`, `link`, `cat_id`, `sort_order`) VALUES
(1, 'iPhone ', 'images/banners/1777884645_banner.png', 'https://updatesall.site/store/product_detail.php?id=3', 1, 0),
(3, 'Camera', 'images/banners/1777883951_banner.png', 'https://updatesall.site/store/product_detail.php?id=11', 1, 0),
(5, 'EssayGrowth', 'images/banners/1777884835_banner.jpg', 'https://essaygrowth.netlify.app', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `image`) VALUES
(1, 'Electronics', 'images/categories/1777868625.jpg'),
(2, 'Fashion', 'images/categories/1777874030.jpg'),
(3, 'Home', 'images/categories/1777874095.jpg'),
(4, 'Beauty', 'images/categories/1777874163.jpg'),
(5, 'Essentials', 'images/categories/1777874225.jpg'),
(8, 'Lifestyle', 'images/categories/1777874288.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_boys`
--

CREATE TABLE `delivery_boys` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_boys`
--

INSERT INTO `delivery_boys` (`id`, `name`, `phone`, `address`, `email`, `password`, `status`, `created_at`) VALUES
(1, 'aaa', '1234567890', 'VILLAGE-GARGARI,POST OFFICE-BURHINAGAR,POLICE STATION-MANGALDAI', 'aaa111@gmail.com', '$2y$10$cwy9Xmei224qA9eKK2.1je4R5DrG6XtIqmWL5RqQaNMscliNNtvsa', 1, '2026-05-03 07:07:21'),
(2, 'sss', '09764310852', 'Assam', 'sss123@gmail.com', '$2y$10$o7AymK5aimJozqkXV9fsle2N/K3GPCoTQ6dEb.pQ6jRQrZytxYTIi', 1, '2026-05-03 07:49:25');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('COD','QR') DEFAULT 'COD',
  `screenshot` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('Placed','Dispatched','Delivered','Cancelled') DEFAULT 'Placed',
  `delivery_boy_id` int(11) DEFAULT NULL,
  `verification_code` varchar(6) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `payment_method`, `screenshot`, `address`, `status`, `delivery_boy_id`, `verification_code`, `created_at`) VALUES
(1, 1, 999.00, 'COD', NULL, NULL, 'Cancelled', NULL, NULL, '2026-05-01 13:27:32'),
(2, 1, 123.00, 'COD', NULL, NULL, 'Placed', NULL, NULL, '2026-05-01 15:20:11'),
(3, 1, 369.00, 'COD', NULL, 'VILLAGE-GARGARI,POST OFFICE-BURHINAGAR,POLICE STATION-MANGALDAI', 'Placed', NULL, NULL, '2026-05-01 15:45:25'),
(4, 1, 999.00, 'COD', NULL, '', 'Cancelled', NULL, NULL, '2026-05-01 15:46:43'),
(5, 1, 123.00, 'COD', NULL, 'assam', 'Delivered', NULL, NULL, '2026-05-01 15:48:13'),
(6, 1, 1222.00, 'COD', NULL, 'assam', 'Delivered', 1, NULL, '2026-05-01 16:51:13'),
(7, 2, 1222.00, 'QR', 'images/payments/1777730908_payment.jpeg', '', 'Cancelled', NULL, NULL, '2026-05-02 14:08:28'),
(8, 2, 23.00, 'COD', '', '', 'Delivered', 2, NULL, '2026-05-02 14:09:27'),
(9, 2, 1268.00, 'QR', 'images/payments/1777732496_payment.png', 'VILLAGE-GARGARI,POST OFFICE-BURHINAGAR,POLICE STATION-MANGALDAI', 'Delivered', 2, NULL, '2026-05-02 14:34:56'),
(10, 2, 999.00, 'COD', '', 'VILLAGE-GARGARI,POST OFFICE-BURHINAGAR,POLICE STATION-MANGALDAI', 'Delivered', 2, NULL, '2026-05-02 14:37:42'),
(11, 2, 23.00, 'COD', '', 'Darrng\r\ndarrang', 'Delivered', 1, NULL, '2026-05-02 15:05:19'),
(12, 2, 180.00, 'QR', 'images/payments/1777807798_payment.png', 'Darrng\r\ndarrang', 'Delivered', 1, NULL, '2026-05-03 11:29:58'),
(13, 1, 99.00, 'QR', 'images/payments/1777880399_payment.jpg', 'assam', 'Delivered', 1, NULL, '2026-05-04 07:39:59'),
(14, 2, 597.00, 'QR', 'images/payments/1777886112_payment.jpg', 'Assam Darrang 784147', 'Delivered', 1, NULL, '2026-05-04 09:15:12'),
(15, 1, 199.00, 'COD', '', 'assam', 'Delivered', 1, NULL, '2026-05-05 07:50:52'),
(16, 4, 52999.00, 'QR', 'images/payments/1777975140_payment.jpg', 'Mangaldai', 'Delivered', 2, NULL, '2026-05-05 09:59:00'),
(17, 1, 15000.00, 'COD', '', 'assam', 'Placed', NULL, NULL, '2026-05-05 14:36:58'),
(18, 1, 199.00, 'COD', '', 'assam', 'Placed', NULL, NULL, '2026-05-05 14:38:34'),
(19, 2, 15000.00, 'COD', '', 'Assam Darrang 784147', 'Placed', NULL, NULL, '2026-05-06 06:57:14'),
(20, 1, 15000.00, 'COD', '', 'assam', 'Placed', NULL, NULL, '2026-05-06 08:17:47'),
(21, 1, 799.00, 'COD', '', 'assam', 'Placed', NULL, NULL, '2026-05-06 08:31:33'),
(22, 6, 15000.00, 'COD', '', 'Mahaliapara, Darrang - 784147', 'Delivered', 1, NULL, '2026-05-06 13:27:19'),
(23, 10, 249.00, 'COD', '', 'Mahaliapara, Darrang - 784124', 'Delivered', 1, NULL, '2026-05-09 06:26:14'),
(24, 11, 52999.00, 'COD', '', 'Gshsh, Hshs - Hshsh', 'Delivered', 1, NULL, '2026-05-16 11:10:49');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(6, 6, 3, 1, 1222.00),
(7, 7, 3, 1, 1222.00),
(8, 8, 4, 1, 23.00),
(9, 9, 3, 1, 1222.00),
(10, 9, 4, 2, 23.00),
(12, 11, 4, 1, 23.00),
(13, 12, 7, 1, 180.00),
(14, 13, 9, 1, 99.00),
(15, 14, 10, 3, 199.00),
(16, 15, 10, 1, 199.00),
(17, 16, 11, 1, 52999.00),
(18, 17, 7, 1, 15000.00),
(19, 18, 10, 1, 199.00),
(20, 19, 7, 1, 15000.00),
(21, 20, 7, 1, 15000.00),
(22, 21, 6, 1, 799.00),
(23, 22, 7, 1, 15000.00),
(24, 23, 9, 1, 249.00),
(25, 24, 11, 1, 52999.00);

-- --------------------------------------------------------

--
-- Table structure for table `productratingreport`
--

CREATE TABLE `productratingreport` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `star` tinyint(4) NOT NULL,
  `message` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productratingreport`
--

INSERT INTO `productratingreport` (`id`, `user_id`, `product_id`, `star`, `message`, `created_at`) VALUES
(1, 2, 10, 2, 'Good', '2026-05-05 07:48:37'),
(2, 1, 10, 4, 'Woow', '2026-05-05 07:53:09'),
(3, 2, 7, 4, 'good', '2026-05-05 09:51:35'),
(4, 2, 4, 5, 'good', '2026-05-05 09:51:53'),
(5, 2, 3, 2, 'woow', '2026-05-05 09:52:08'),
(6, 4, 11, 5, 'Good', '2026-05-05 10:02:25'),
(7, 6, 7, 3, 'Good', '2026-05-06 13:30:43'),
(8, 10, 9, 4, 'Excellent', '2026-05-09 06:30:05');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `subcat_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `mrp` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `offer_percent` decimal(5,2) DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `cat_id`, `subcat_id`, `name`, `description`, `price`, `mrp`, `unit`, `offer_percent`, `stock`, `image`, `created_at`) VALUES
(3, 1, 1, 'Apple iPhone (Black)', 'Upgrade your smartphone experience with this Apple iPhone (Black) featuring a sleek design and powerful performance.\r\n📱 Key Features:\r\nDual rear camera for sharp photos 📸\r\nSmooth performance & reliable iOS experience\r\nPremium matte black finish 🖤\r\nPerfect for daily use, social media & photography\r\n💯 Condition: Well maintained, fully working\r\n🔋 Good battery backup\r\n📦 Ready to use', 34999.00, 49999.00, 'Piece', 30.00, 1, 'images/1777882871.jpg', '2026-05-01 16:47:17'),
(4, 2, 3, 'Leriya Women’s', 'Leriya Fashion Women’s A-Line Midi Dress\r\n👗 Elegant Fit | 🌸 Stylish Design | 💃 Comfortable Wear\r\nPerfect for casual outings & special occasions\r\nStep out in style with this elegant A-Line Midi Dress featuring a modern wavy print design.\r\n👗 Key Features:\r\nStylish A-line fit for a flattering look\r\nElegant beige & black pattern\r\nSoft, comfortable fabric for all-day wear\r\nFull sleeves with waist tie for perfect fitting\r\n💃 Perfect for casual outings, office wear & special occasions\r\n✨ Trendy, comfortable & eye-catching design', 899.00, 1499.00, 'Piece', 40.00, 7, 'images/1777877610.webp', '2026-05-01 17:19:03'),
(6, 2, 2, 'Boldfit Men’s', 'Boldfit Men’s Hooded Hoodie\r\n🧥 Warm & Comfortable | 🔥 Trendy Fit | 💪 Durable Fabric\r\nPerfect for winter wear & casual style\r\nUpgrade your casual style with this Men’s Black Hoodie, designed for comfort and a modern look.\r\n🧥 Key Features:\r\nClassic solid black design (always in trend)\r\nSoft & comfortable fabric for daily wear\r\nAdjustable hood with drawstrings\r\nFull sleeves with relaxed fit\r\n🔥 Perfect for winter wear, gym, travel & casual outings\r\n💪 Stylish, durable & easy to pair with any outfit', 799.00, 1499.00, 'Piece', 47.00, 2, 'images/1777877207.jpg', '2026-05-03 06:23:13'),
(7, 1, 1, 'REDMI A7 Pro 5G', 'REDMI A7 Pro 5G (Mist Blue, 4GB/128GB)\r\n⚡ Fast Processor | 🔋 Big Battery | 📺 6.9\" 120Hz Display\r\n🚀 Smooth performance with ultra-fast 5G connectivity', 15000.00, 20000.00, 'Q', 25.00, 3, 'images/1777876488.jpg', '2026-05-03 09:46:01'),
(8, 1, 1, 'Samsung Galaxy M17 5G', 'Samsung Galaxy M17 5G\r\n⚡ Smooth Performance | 🔋 Long Battery | 📶 Fast 5G\r\n📱 Big display with reliable Samsung quality', 12000.00, 18000.00, 'Q', 33.00, 5, 'images/1777876826.jpg', '2026-05-04 06:40:26'),
(9, 3, 5, 'Stainless Steel', 'Stainless Steel Kitchen Tool Set (3 Pcs)\r\n🧀 Flat Grater | 🥕 Vegetable Grater | ✋ Easy Handheld Use\r\nDurable, rust-resistant & perfect for everyday cooking\r\nMake your cooking easier with this 3-piece stainless steel kitchen tool set, designed for everyday use.\r\n🍴 Includes:\r\nWhisk (for mixing & beating)\r\nFlat grater\r\nHandheld grater\r\n✨ Key Features:\r\nHigh-quality stainless steel (rust-resistant)\r\nStrong, durable & long-lasting\r\nEasy to clean & maintain\r\nComfortable grip for smooth use\r\n🍳 Perfect for kitchen use, cooking, baking & food preparation', 249.00, 499.00, 'Set', 50.00, 6, 'images/1777878432.jpg', '2026-05-04 07:07:12'),
(10, 5, 6, 'Camlin Scholar Plus', 'Camlin Scholar Plus Geometry Box\r\n📐 Complete Set | 🎒 Student Friendly | ✏️ Durable Quality\r\nPerfect for school & exam use\r\nGet all essential math tools in one box with the Camlin Scholar+ Geometry Box, perfect for students.\r\n📐 Includes:\r\nCompass & divider\r\nScale (ruler)\r\nProtractor & set squares\r\nPencil, eraser & sharpener\r\n✨ Key Features:\r\nHigh-precision instruments for accurate drawing\r\nDurable & long-lasting quality\r\nCompact metal box for easy storage\r\n🎒 Ideal for school, exams & daily study use', 199.00, 299.00, 'Piece', 33.00, 5, 'images/1777878647.jpg', '2026-05-04 07:10:47'),
(11, 1, 4, 'Canon M50 Mark II', 'Capture stunning photos and cinematic videos with the Canon M50 Mark II.\r\nPerfect for photography, YouTube, and content creation.\r\n📸 Includes:\r\n50mm f/1.8 lens (great for portraits & blur effect)\r\n16–45mm kit lens (wide & everyday shots)\r\nViltrox adapter (lens compatibility)\r\n🔥 Ideal for beginners & professionals\r\n🎥 Excellent for vlogging & video editing\r\n💡 Lightweight, powerful & easy to use', 52999.00, 59999.00, 'Q', 12.00, 1, 'images/1777882370.jpg', '2026-05-04 08:12:50');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image`, `sort_order`) VALUES
(11, 7, 'images/1777876488_2.jpg', 2),
(12, 7, 'images/1777876488_3.jpg', 3),
(13, 8, 'images/1777876826_2.jpg', 2),
(14, 8, 'images/1777876826_3.jpg', 3),
(15, 6, 'images/1777877207_2.jpg', 2),
(16, 6, 'images/1777877207_3.jpg', 3),
(17, 4, 'images/1777877610_2.webp', 2),
(18, 4, 'images/1777877610_3.webp', 3),
(19, 9, 'images/1777878432_2.jpg', 2),
(20, 9, 'images/1777878432_3.jpg', 3),
(21, 10, 'images/1777878647_2.jpg', 2),
(22, 10, 'images/1777878648_3.jpg', 3),
(23, 11, 'images/1777882370_2.jpg', 2),
(24, 11, 'images/1777882370_3.jpg', 3),
(25, 3, 'images/1777882871_2.jpg', 2),
(26, 3, 'images/1777882871_3.jpg', 3);

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_codes`
--

INSERT INTO `qr_codes` (`id`, `image`, `company_name`, `status`) VALUES
(1, 'images/qr/1777961297_qr.png', 'admin', 0),
(2, 'images/qr/1777961322_qr.png', 'admin', 0),
(3, 'images/qr/1777983400_qr.png', 'UpdatesAll', 1);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` enum('Pending','Responded') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `name`, `phone`, `email`, `message`, `status`, `created_at`) VALUES
(1, 1, 'sss', '1234567789', 'sss111@gmail.com', 'good', 'Responded', '2026-05-01 17:10:15'),
(2, 1, 'sss', '1234567789', 'sss111@gmail.com', 'good', 'Pending', '2026-05-01 17:51:35');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `designation` enum('inserter','ordermanager') NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `designation`, `address`, `phone`, `email`, `password`) VALUES
(1, 'sss', 'ordermanager', 'Assam', '09101620936', 'sss111@gmail.com', 'ssssss'),
(2, 'ddd', 'inserter', 'Darrng\r\ndarrang', '09101620936', 'ddd111@gmail.com', 'dddddd');

-- --------------------------------------------------------

--
-- Table structure for table `subcategories`
--

CREATE TABLE `subcategories` (
  `id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subcategories`
--

INSERT INTO `subcategories` (`id`, `cat_id`, `name`, `image`) VALUES
(1, 1, 'mobile', 'images/subcategories/1777876124_subcat.png'),
(2, 2, 'Man', 'images/subcategories/1777875755_subcat.png'),
(3, 2, 'Woman', 'images/subcategories/1777875780_subcat.png'),
(4, 1, 'Camera', 'images/subcategories/1777876167_subcat.png'),
(5, 3, 'Kitchen', 'images/subcategories/1777878152_subcat.png'),
(6, 5, 'Student', 'images/subcategories/1777878207_subcat.png');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `pin` varchar(10) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `firebase_uid` varchar(128) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `phone`, `address`, `district`, `pin`, `email`, `password`, `firebase_uid`, `profile_pic`, `created_at`, `status`) VALUES
(1, 'sss', '1234567789', 'assam', NULL, NULL, 'sss111@gmail.com', '$2y$10$IKA6VihRVYPyNcteqjOHAO4mc0YGgT3kuOqpcWW6OIK2dsp9Z2Tee', NULL, NULL, '2026-05-01 13:26:08', 1),
(2, 'sss', '09764310852', 'Assam Darrang 784147', NULL, NULL, 'sss123@gmail.com', '$2y$10$swzSx2y6O6APSMFB3vmMpeCSTVJdnarr0jlYGuWPbKGGrpY/JEIoG', NULL, NULL, '2026-05-02 13:37:28', 1),
(3, 'aaa', '102938486', NULL, NULL, NULL, 'aaa111@gmail.com', '$2y$10$pks3zfoNdHWhrebvElATaOBOCB2gmQHqKcMr2SUkC14h.h2xGNWD2', NULL, NULL, '2026-05-05 07:50:00', 1),
(4, 'Moon', '9101620936', 'Mangaldai', NULL, NULL, 'moon1@gmail.com', '$2y$10$70.iEKheH3P0G9KHJTPaNupOD3R2C90MuaNZYdnlmLWVqI8HzUW8e', NULL, NULL, '2026-05-05 09:55:55', 1),
(5, 'Barasha Saikia', '', NULL, NULL, NULL, 'saikiabarasha12@gmail.com', '', 'wipQyAzSPtQLGZsEP6RC0AOzLF62', 'https://lh3.googleusercontent.com/a/ACg8ocLr-y90f9spWHsC8siQthGQxMc3n0ZYBS9xhAmope_3_nJ6Sg=s96-c', '2026-05-06 12:37:08', 1),
(6, 'Bitu Saikia', '8134003519', 'Mahaliapara', 'Darrang', '784147', 'saikiabitu464@gmail.com', '', 'NTO0owLU7kbcwNWRjdm1pHAuRh63', 'https://lh3.googleusercontent.com/a/ACg8ocJge7qaEP1t9B5L6vLuXHg1gc7wzVnGnN3efVcLbEW86JVL6z1x=s96-c', '2026-05-06 13:25:32', 1),
(7, 'Deep jyoti Deka', '', NULL, NULL, NULL, 'cnmoydeka@gmail.com', '', 'ttZd5NwJtEQsnwxbXkL92eZKae03', 'https://lh3.googleusercontent.com/a-/ALV-UjWVujMd9fJ-gxgg3zBZawKLDTlpDtby4Xj-Jg79CcFK0LqYMbNTDV5rDkhx22HMsEJ2gfNdLW6B1lldbW5ki2Q3arfN0si2a-jK_CjahoGLJceIO5_n5kleF26-RmK9q8h3JGeBaE8rRRNNgBEL7iQCbSxK7cSKh5FMaAnx8_E1AT6WBQcu7P171fiODV4UBIL7TYCg7gXyeQ58Xlpqo9', '2026-05-07 02:49:12', 1),
(8, 'Essay Growth', '', NULL, NULL, NULL, 'essaygrowth@gmail.com', '', 'RcMyButUdNU0gTWaONfuba75zB02', 'https://lh3.googleusercontent.com/a/ACg8ocJgnuajCVNyhNryLPFJfLdAQBrOtXOBgFCrFA5VToL-Mz4lyms=s96-c', '2026-05-07 11:32:26', 1),
(9, 'Subham Bhattacharyya', '', NULL, NULL, NULL, 'bpritam839@gmail.com', '', 'B0Uj6cysbfhbMvjF29T5nN0yifq1', 'https://lh3.googleusercontent.com/a/ACg8ocJDIkQoicJltuu0C3I5iGjpYSgQ7qhW8RfaWaPn5lfymZu4XvV4=s96-c', '2026-05-08 03:08:35', 1),
(10, 'Tarini Charan Bhattacharyya', '8134003519', 'Mahaliapara', 'Darrang', '784124', 'subhambhatta818@gmail.com', '', 'M4KiAJZkYaVgV8i4l01atPGLAlt2', 'https://lh3.googleusercontent.com/a/ACg8ocJ8sdhVokJ4Fq8JNP6xIygTG7vqtWPQ7CMvZO02apkj5aXvDRQL=s96-c', '2026-05-08 10:36:44', 1),
(11, 'Deep Jyoti', '9707603043', 'Gshsh', 'Hshs', 'Hshsh', 'finddeepjyoti@gmail.com', '', 'zHWT2KOr3dRFqXms5cdDK0sWlcn1', 'https://lh3.googleusercontent.com/a/ACg8ocI3gzjCFk3nP3HCatmjhiX-_mrTktgXB47LGp5AkXIs_RmdFl4=s96-c', '2026-05-16 11:09:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(2, 2, 6, '2026-05-05 08:44:12'),
(3, 1, 8, '2026-05-05 11:31:16'),
(4, 6, 7, '2026-05-06 13:26:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cat_id` (`cat_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_boys`
--
ALTER TABLE `delivery_boys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `delivery_boy_id` (`delivery_boy_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `productratingreport`
--
ALTER TABLE `productratingreport`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cat_id` (`cat_id`),
  ADD KEY `subcat_id` (`subcat_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cat_id` (`cat_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_product` (`user_id`,`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `delivery_boys`
--
ALTER TABLE `delivery_boys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `productratingreport`
--
ALTER TABLE `productratingreport`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `banners`
--
ALTER TABLE `banners`
  ADD CONSTRAINT `banners_ibfk_1` FOREIGN KEY (`cat_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`delivery_boy_id`) REFERENCES `delivery_boys` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `productratingreport`
--
ALTER TABLE `productratingreport`
  ADD CONSTRAINT `productratingreport_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `productratingreport_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`cat_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`subcat_id`) REFERENCES `subcategories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`cat_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
