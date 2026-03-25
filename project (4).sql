-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2026 at 07:30 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `project`
--

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `donation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT 0,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `donation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_path_1` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`donation_id`, `user_id`, `event_id`, `item_id`, `quantity`, `donation_date`, `image_path_1`, `status`) VALUES
(207, 4, 51, 1, 160, '2026-03-23 20:18:49', 'uploads/donations/1774297129_00adc5b81305daac62e7db4f79a86918.jpg', 'Approved'),
(208, 4, 51, 2, 150, '2026-03-23 20:18:49', 'uploads/donations/1774297129_00adc5b81305daac62e7db4f79a86918.jpg', 'Approved'),
(209, 4, 51, 3, 120, '2026-03-23 20:18:49', 'uploads/donations/1774297129_00adc5b81305daac62e7db4f79a86918.jpg', 'Approved'),
(210, 4, 51, 4, 200, '2026-03-23 20:18:49', 'uploads/donations/1774297129_00adc5b81305daac62e7db4f79a86918.jpg', 'Approved'),
(211, 4, 51, 5, 150, '2026-03-23 20:18:49', 'uploads/donations/1774297129_00adc5b81305daac62e7db4f79a86918.jpg', 'Approved'),
(212, 4, 51, 6, 130, '2026-03-23 20:18:49', 'uploads/donations/1774297129_00adc5b81305daac62e7db4f79a86918.jpg', 'Approved'),
(213, 4, 51, 7, 120, '2026-03-23 20:18:49', 'uploads/donations/1774297129_00adc5b81305daac62e7db4f79a86918.jpg', 'Approved'),
(214, 4, 51, 8, 300, '2026-03-23 20:18:49', 'uploads/donations/1774297129_00adc5b81305daac62e7db4f79a86918.jpg', 'Approved'),
(215, 3, 50, 1, 120, '2026-03-23 20:19:50', 'uploads/donations/1774297190_1.1.jpg', 'Approved'),
(216, 3, 50, 2, 120, '2026-03-23 20:19:50', 'uploads/donations/1774297190_1.1.jpg', 'Approved'),
(217, 3, 50, 3, 60, '2026-03-23 20:19:50', 'uploads/donations/1774297190_1.1.jpg', 'Approved'),
(218, 3, 50, 4, 50, '2026-03-23 20:19:50', 'uploads/donations/1774297190_1.1.jpg', 'Approved'),
(219, 3, 50, 5, 10, '2026-03-23 20:19:50', 'uploads/donations/1774297190_1.1.jpg', 'Approved'),
(220, 3, 50, 6, 60, '2026-03-23 20:19:50', 'uploads/donations/1774297190_1.1.jpg', 'Approved'),
(221, 3, 50, 7, 80, '2026-03-23 20:19:50', 'uploads/donations/1774297190_1.1.jpg', 'Approved'),
(222, 3, 50, 8, 100, '2026-03-23 20:19:50', 'uploads/donations/1774297190_1.1.jpg', 'Approved'),
(223, 3, 62, 1, 50, '2026-03-23 20:20:28', 'uploads/donations/1774297228_3.5.jpg', 'Approved'),
(224, 3, 62, 2, 30, '2026-03-23 20:20:28', 'uploads/donations/1774297228_3.5.jpg', 'Approved'),
(225, 3, 62, 3, 70, '2026-03-23 20:20:28', 'uploads/donations/1774297228_3.5.jpg', 'Approved'),
(226, 3, 62, 4, 60, '2026-03-23 20:20:28', 'uploads/donations/1774297228_3.5.jpg', 'Approved'),
(227, 3, 62, 6, 60, '2026-03-23 20:20:28', 'uploads/donations/1774297228_3.5.jpg', 'Approved'),
(228, 3, 62, 7, 30, '2026-03-23 20:20:28', 'uploads/donations/1774297228_3.5.jpg', 'Approved'),
(229, 3, 62, 8, 60, '2026-03-23 20:20:28', 'uploads/donations/1774297228_3.5.jpg', 'Approved'),
(230, 7, 59, 1, 30, '2026-03-23 20:34:06', 'uploads/donations/1774298046_Untitled Project.jpg', 'Approved'),
(231, 7, 59, 2, 20, '2026-03-23 20:34:06', 'uploads/donations/1774298046_Untitled Project.jpg', 'Pending'),
(232, 7, 59, 3, 20, '2026-03-23 20:34:06', 'uploads/donations/1774298046_Untitled Project.jpg', 'Approved'),
(233, 7, 59, 4, 30, '2026-03-23 20:34:06', 'uploads/donations/1774298046_Untitled Project.jpg', 'Approved'),
(234, 7, 59, 5, 20, '2026-03-23 20:34:06', 'uploads/donations/1774298046_Untitled Project.jpg', 'Approved'),
(235, 7, 59, 6, 20, '2026-03-23 20:34:06', 'uploads/donations/1774298046_Untitled Project.jpg', 'Approved'),
(236, 7, 59, 7, 20, '2026-03-23 20:34:06', 'uploads/donations/1774298046_Untitled Project.jpg', 'Approved'),
(237, 4, 999, 2, 10, '2026-03-23 20:52:08', 'uploads/donations/1774299128_1.2.jpg', 'Pending'),
(238, 0, 59, 8, 10, '2026-03-24 16:17:28', NULL, 'Approved'),
(239, 3, 59, 2, 1, '2026-03-24 21:27:43', 'uploads/donations/1774387663_IMG_5460.jpeg', 'Approved'),
(240, 4, 999, 1, 10, '2026-03-25 07:29:14', 'uploads/donations/1774423754_1.2.png', 'Pending'),
(241, 4, 999, 2, 10, '2026-03-25 07:29:14', 'uploads/donations/1774423754_1.2.png', 'Approved'),
(242, 4, 999, 8, 30, '2026-03-25 07:29:14', 'uploads/donations/1774423754_1.2.png', 'Approved'),
(243, 4, 59, 2, 9, '2026-03-25 11:58:11', 'uploads/donations/1774439891_070cf036-e83f-4e73-830a-427878e617ed.jfif', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `donation_items`
--

CREATE TABLE `donation_items` (
  `item_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `sub_category` varchar(100) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `unit` varchar(50) DEFAULT 'ชิ้น',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donation_items`
--

INSERT INTO `donation_items` (`item_id`, `category`, `sub_category`, `item_name`, `unit`, `is_active`) VALUES
(1, 'อุปโภค', 'ยาสามัญประจำบ้าน', 'ยาสามัญประจำบ้าน', 'กก.', 1),
(2, 'อุปโภค', 'เสื้อผ้ามือสอง', 'เสื้อผ้ามือสอง', 'กก.', 1),
(3, 'อุปโภค', 'อุปกรณ์การเรียน', 'อุปกรณ์การเรียน', 'กก.', 1),
(4, 'อุปโภค', 'อุปกรณ์กีฬา', 'อุปกรณ์กีฬา', 'กก.', 1),
(5, 'อุปโภค', 'ของเล่นและตุ๊กตา', 'ของเล่นและตุ๊กตา', 'กก.', 1),
(6, 'อุปโภค', 'ผลิตภัณฑ์ทำความสะอาด', 'ผลิตภัณฑ์ทำความสะอาด', 'กก.', 1),
(7, 'อุปโภค', 'เครื่องใช้ภายในบ้าน', 'เครื่องใช้ภายในบ้าน', 'กก.', 1),
(8, 'บริโภค', 'ข้าวสารและอาหารแห้ง', 'ข้าวสารและอาหารแห้ง', 'กก.', 1);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` varchar(50) DEFAULT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `highlights` text DEFAULT NULL,
  `schedule_range` varchar(255) DEFAULT NULL,
  `event_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `closed_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `event_date`, `event_time`, `Location`, `highlights`, `schedule_range`, `event_image`, `is_active`, `status`, `closed_count`) VALUES
(50, 'วันพ่อแห่งชาติ', '2027-12-05', '', 'ศูนย์การเรียนรู้ชุมชนไทยภูเขาแม่ฟ้าหลวง บ้านมอโกรทะ อำเภออมก๋อย จังหวัดเชียงใหม่', 'ตั้งโรงทาน,มอบข้าวกล่อง,แจกน้ำดื่ม,รับข้าวสาร,จัดถุงยังชีพ,กระจายของแห้ง', '4-6 ธันวาคม 2569', '69aeb32dc91cd.jpg', 1, 'Active', 5),
(51, 'วันแม่แห่งชาติ', '2026-08-12', '', 'ศูนย์การเรียนรู้ชุมชนไทยภูเขาแม่ฟ้าหลวงบ้านแม่ลาบู อำเภอท่าสองยาง จังหวัดตาก', 'ตั้งโรงทาน,มอบข้าวกล่อง,แจกน้ำดื่ม,รับข้าวสาร,จัดถุงยังชีพ,กระจายของแห้ง', '11-14 สิงหาคม 2569', '69aeb4421bde3.jpg', 1, 'Closed', 5),
(59, 'วันครู', '2027-03-12', '', 'ศูนย์การเรียนรู้ชุมชนไทยภูเขาแม่ฟ้าหลวง บ้านมอโกรทะ อำเภออมก๋อย จังหวัดเชียงใหม่', 'ปปปป', '12 เมษายน', '69b7d0667d72b.jpg', 1, 'Active', 5),
(62, 'วันเด็ก', '2027-01-09', '', 'ศูนย์การเรียนรู้ชุมชนไทยภูเขาแม่ฟ้าหลวง บ้านมอโกรทะ อำเภออมก๋อย จังหวัดเชียงใหม่', 'เล่นเกม', '14 มกราคม 2569', '69bd448cdfc82.jpg', 1, 'Inactive', 0),
(1002, 'วันแม่แห่งชาติ', '2027-08-12', '', 'โรงเรียน/ศูนย์พัฒนาเด็กเล็ก', 'บอกรักแม่ด้วยการทำดี, นาทีทอง, กีฬาบนยอดดอย', '11-14 สิงหาคม 2570', '69c23c5e0e9e8.png', 1, 'Active', 0);

-- --------------------------------------------------------

--
-- Table structure for table `event_item_targets`
--

CREATE TABLE `event_item_targets` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `target_quantity` int(11) DEFAULT 0,
  `current_received` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `event_item_targets`
--

INSERT INTO `event_item_targets` (`id`, `event_id`, `item_id`, `target_quantity`, `current_received`) VALUES
(39, 50, 1, 20, 120),
(40, 50, 2, 100, 120),
(41, 50, 3, 50, 60),
(42, 50, 4, 40, 50),
(43, 50, 5, 30, 10),
(44, 50, 6, 60, 60),
(45, 50, 7, 80, 80),
(46, 50, 8, 200, 100),
(47, 51, 1, 60, 160),
(48, 51, 2, 50, 150),
(49, 51, 3, 20, 120),
(50, 51, 4, 100, 200),
(51, 51, 5, 50, 150),
(52, 51, 6, 30, 130),
(53, 51, 7, 20, 120),
(54, 51, 8, 200, 300),
(135, 60, 1, 30, 0),
(136, 60, 2, 40, 0),
(137, 60, 3, 50, 0),
(138, 60, 4, 20, 0),
(139, 60, 5, 40, 0),
(140, 60, 6, 50, 0),
(141, 60, 7, 30, 0),
(142, 60, 8, 20, 0),
(242, 59, 1, 30, 30),
(243, 59, 2, 20, 30),
(244, 59, 3, 20, 40),
(245, 59, 4, 20, 50),
(246, 59, 5, 20, 40),
(247, 59, 6, 20, 40),
(248, 59, 7, 20, 40),
(249, 59, 8, 20, 20),
(250, 62, 1, 50, 200),
(251, 62, 2, 30, 150),
(252, 62, 3, 50, 70),
(253, 62, 4, 40, 60),
(254, 62, 5, 20, 120),
(255, 62, 6, 40, 60),
(256, 62, 7, 60, 30),
(257, 62, 8, 50, 60),
(298, 1002, 1, 20, 0),
(299, 1002, 2, 50, 0),
(300, 1002, 3, 20, 0),
(301, 1002, 4, 10, 0),
(302, 1002, 5, 30, 0),
(303, 1002, 6, 50, 0),
(304, 1002, 7, 50, 0),
(305, 1002, 8, 100, 0);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_stock`
--

CREATE TABLE `inventory_stock` (
  `stock_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `source_event_id` int(11) DEFAULT NULL COMMENT 'กิจกรรมที่ของเกินมาจาก',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_stock`
--

INSERT INTO `inventory_stock` (`stock_id`, `item_id`, `quantity`, `source_event_id`, `created_at`, `updated_at`) VALUES
(75, 1, 100.00, NULL, '2026-03-23 20:20:57', '2026-03-23 20:34:57'),
(76, 8, 100.00, NULL, '2026-03-23 20:20:59', '2026-03-23 20:34:57'),
(77, 7, 100.00, NULL, '2026-03-23 20:21:02', '2026-03-23 20:34:57'),
(78, 6, 100.00, NULL, '2026-03-23 20:21:05', '2026-03-23 20:34:57'),
(79, 5, 100.00, NULL, '2026-03-23 20:21:07', '2026-03-23 20:34:57'),
(80, 4, 100.00, NULL, '2026-03-23 20:21:11', '2026-03-23 20:34:57'),
(81, 3, 100.00, NULL, '2026-03-23 20:21:13', '2026-03-23 20:34:57'),
(82, 2, 100.00, NULL, '2026-03-23 20:21:17', '2026-03-23 20:34:57'),
(83, 1, 60.00, NULL, '2026-03-23 20:21:20', '2026-03-23 20:34:57'),
(84, 2, 70.00, NULL, '2026-03-23 20:21:23', '2026-03-23 20:34:57'),
(85, 3, 40.00, NULL, '2026-03-23 20:21:26', '2026-03-23 20:34:57'),
(86, 4, 0.00, NULL, '2026-03-23 20:21:29', '2026-03-23 20:34:57'),
(87, 5, 0.00, NULL, '2026-03-23 20:21:32', '2026-03-23 20:34:57'),
(88, 6, 30.00, NULL, '2026-03-23 20:21:35', '2026-03-23 20:34:57'),
(89, 7, 60.00, NULL, '2026-03-23 20:21:38', '2026-03-23 20:34:57'),
(90, 8, 0.00, NULL, '2026-03-23 20:21:41', '2026-03-23 20:34:57'),
(91, 7, 10.00, NULL, '2026-03-23 20:21:44', '2026-03-23 20:34:57'),
(92, 6, 30.00, NULL, '2026-03-23 20:21:47', '2026-03-23 20:34:57'),
(93, 4, 0.00, NULL, '2026-03-23 20:21:50', '2026-03-23 20:34:57'),
(94, 3, 50.00, NULL, '2026-03-23 20:21:54', '2026-03-23 20:34:57'),
(95, 2, 0.00, NULL, '2026-03-23 20:21:57', '2026-03-23 20:34:57'),
(96, 1, 0.00, NULL, '2026-03-23 20:22:00', '2026-03-23 20:34:57'),
(97, 8, 0.00, NULL, '2026-03-23 20:22:03', '2026-03-23 20:34:57'),
(98, 7, 0.00, NULL, '2026-03-23 20:34:18', '2026-03-23 20:34:57'),
(99, 1, 0.00, NULL, '2026-03-23 20:34:20', '2026-03-23 20:34:57'),
(100, 3, 0.00, NULL, '2026-03-23 20:34:24', '2026-03-23 20:34:57'),
(101, 4, 0.00, NULL, '2026-03-23 20:34:28', '2026-03-23 20:34:57'),
(102, 5, 0.00, NULL, '2026-03-23 20:34:30', '2026-03-23 20:34:57'),
(103, 6, 0.00, NULL, '2026-03-23 20:34:33', '2026-03-23 20:34:57'),
(104, 2, 1.00, NULL, '2026-03-25 07:10:02', '2026-03-25 07:10:02'),
(105, 8, 30.00, NULL, '2026-03-25 07:29:39', '2026-03-25 07:29:39'),
(106, 2, 10.00, NULL, '2026-03-25 10:36:31', '2026-03-25 10:36:31'),
(107, 2, 9.00, NULL, '2026-03-25 11:58:42', '2026-03-25 11:58:42');

-- --------------------------------------------------------

--
-- Table structure for table `join_event`
--

CREATE TABLE `join_event` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `confirmed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `join_event`
--

INSERT INTO `join_event` (`id`, `user_id`, `event_id`, `status`, `confirmed_at`) VALUES
(45, 5, 51, 0, '2026-03-12 19:43:50'),
(47, 3, 51, 0, '2026-03-13 01:08:44'),
(49, 8, 51, 1, '2026-03-16 13:41:07'),
(51, 7, 50, 1, '2026-03-16 17:21:40'),
(52, 3, 50, 0, '2026-03-16 17:22:00'),
(53, 3, 59, 1, '2026-03-20 09:44:32');

-- --------------------------------------------------------

--
-- Table structure for table `news_images`
--

CREATE TABLE `news_images` (
  `image_id` int(11) NOT NULL,
  `news_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `news_images`
--

INSERT INTO `news_images` (`image_id`, `news_id`, `image_path`) VALUES
(31, 13, 'news_69afd71a0390a_0.jpg'),
(32, 13, 'news_69afd71a04a47_1.jpg'),
(33, 13, 'news_69afd71a05f16_2.jpg'),
(35, 13, 'news_69afd71a08169_4.jpg'),
(39, 14, 'news_69b073d60c238_0.jpg'),
(40, 14, 'news_69b073d60d09b_1.jpg'),
(41, 14, 'news_69b073d60d84d_2.jpg'),
(42, 14, 'news_69b073d60e8e1_3.jpg'),
(43, 14, 'news_69b073d60efa6_4.jpg'),
(44, 15, 'news_69b07482b2ad1_0.jpg'),
(45, 15, 'news_69b07482b3652_1.jpg'),
(46, 15, 'news_69b07482b3ee6_2.jpg'),
(47, 15, 'news_69b07482b49fb_3.jpg'),
(48, 15, 'news_69b07482b53a1_4.jpg'),
(49, 16, 'news_69b074ce4301f_0.jpg'),
(50, 16, 'news_69b074ce43d7e_1.jpg'),
(51, 16, 'news_69b074ce44b5c_2.jpg'),
(52, 16, 'news_69b074ce4528a_3.jpg'),
(53, 16, 'news_69b074ce45aa5_4.jpg'),
(54, 17, 'news_69b0750e0c66d_0.jpg'),
(55, 17, 'news_69b0750e0d033_1.jpg'),
(56, 17, 'news_69b0750e0d73d_2.jpg'),
(57, 17, 'news_69b0750e0e048_3.jpg'),
(58, 17, 'news_69b0750e0e726_4.jpg'),
(59, 18, 'news_69b0753f7a822_0.jpg'),
(60, 18, 'news_69b0753f7b231_1.jpg'),
(61, 18, 'news_69b0753f7b9f4_2.jpg'),
(62, 18, 'news_69b0753f7c23f_3.jpg'),
(63, 18, 'news_69b0753f7cb32_4.jpg'),
(67, 21, 'news_69c18e0da567d_0.jpg'),
(68, 21, 'news_69c18e0da613c_1.jpg'),
(69, 21, 'news_69c18e0da69ac_2.jpg'),
(70, 21, 'news_69c18e0da7079_3.jpg'),
(71, 21, 'news_69c18e0da7a6d_4.jpg'),
(73, 22, 'news_69c38d50c6447_0.png'),
(74, 22, 'news_69c38d50c7625_1.png'),
(76, 23, 'news_69c40c3ed1163_0.jpg'),
(77, 23, 'news_69c40c3ed23b2_1.jpg'),
(78, 23, 'news_69c40c3ed4736_2.jpg'),
(79, 23, 'news_69c40c3ed4f77_3.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `news_update`
--

CREATE TABLE `news_update` (
  `news_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `detail` text DEFAULT NULL,
  `publish_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `news_update`
--

INSERT INTO `news_update` (`news_id`, `user_id`, `event_id`, `title`, `detail`, `publish_date`, `status`) VALUES
(13, 4, 50, 'โรงทานปันรัก: มื้ออร่อยเติมพลังกาย', 'ปรุงอาหารสดใหม่มอบให้คนในชุมชน เพื่อเติมพลังและสร้างรอยยิ้มผ่านมื้ออาหารที่สะอาดและมีประโยชน์', '2026-03-10 08:32:26', 1),
(14, 4, 50, 'แสงเทียนส่องใจ: ร่วมรำลึกด้วยใจภักดิ์', 'พิธีจุดเทียนรวมใจคนในชุมชน เพื่อรำลึกถึงความสำคัญและแสดงความกตัญญูผ่านแสงเทียนที่สว่างไสวทั่วพื้นที่', '2026-03-10 19:41:10', 1),
(15, 4, 51, 'เกมมหาสนุก', 'รวมพลังน้องๆ อมก๋อย แข่งกีฬาสร้างมิตรภาพ ส่งเสริมความสามัคคี และรอยยิ้มผ่านเกมสุดมันส์ท่ามกลางขุนเขา', '2026-03-10 19:44:02', 1),
(16, 4, 50, 'ส่งต่อความอุ่น: เสื้อผ้ามือสองสภาพดี', 'แบ่งปันเสื้อผ้ามือสองสภาพดีและเครื่องกันหนาว ให้พี่น้องในพื้นที่ห่างไกลได้ใช้ประโยชน์ แทนความห่วงใยและส่งต่อไออุ่นให้กัน', '2026-03-10 19:45:18', 1),
(17, 4, 51, 'กีฬาสร้างมิตร', 'บรรยากาศความสนุกสนานในงานกีฬาสีชุมชน ที่รวมพลังน้องๆ เยาวชนร่วมแข่งขันวอลเลย์บอลและเซปักตะกร้อด้วยความมุ่งมั่น มุ่งเน้นการใช้กีฬาเป็นสื่อกลางในการสร้างมิตรภาพ', '2026-03-10 19:46:22', 1),
(18, 4, 50, 'แจกจ่ายสิ่งของบริจาค', 'ส่งต่อสิ่งของจากน้ำใจของผู้บริจาคทุกท่านให้แก่ชาวบ้านในชุมชนบนดอยทั้งสิ่งของอุปโภคและบริโภค', '2026-03-10 19:47:11', 1),
(21, 4, 62, 'เติมเต็มรอยยิ้มให้กับน้องๆ', 'ร่วมส่งต่อความสุขและเติมเต็มรอยยิ้มให้กับน้องๆ ในพื้นที่ห่างไกล เนื่องในวันเด็กแห่งชาติ ปีนี้เราเตรียมกิจกรรมสันทนาการ ซุ้มอาหารแสนอร่อย และของขวัญสุดพิเศษไปมอบให้ถึงบนดอย เพื่อสร้างแรงบันดาลใจและมอบโอกาสที่เท่าเทียมให้กับเด็กๆ...', '2026-03-18 08:23:29', 1),
(22, 4, 50, '99999999', '88888888888888888888888', '2026-03-25 07:22:56', 1),
(23, 4, 62, 'แมวววเด็ก', 'แมวววววววววววววววว', '2026-03-25 12:58:38', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `profile_image_path` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT 'inactive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `phone`, `province`, `address`, `created_at`, `role`, `profile_image_path`, `reset_token`, `token_expiry`, `status`) VALUES
(3, 'chompho', '$2y$10$84tAOWzRSC/0mrt212YY0O8Cj/iGB9zw1WSPnKaOTolDqz.giMzwe', 'minjinji5@gmail.com', 'นิตย์รดี', 'แก่นโงน', '0630351542', 'พิษณุโลก', 'เลขที่ 156 หมู่ 5 ถนนเลี่ยงเมืองพิษณุโลกด้านเหนือ ตำบลพลายชุมพล อำเภอเมือง จังหวัดพิษณุโลก 65000', '2025-10-16 18:00:56', 'volunteer', 'uploads/profiles/3_1774294274.jpg', 'ac08ea2e3470493a8f37885ecd3dec516f233061453d869410c9f4a629d1a9f1', '2026-02-10 08:33:18', 'active'),
(4, 'admin', '$2y$10$pBWkRK2YUW2kh29jpSUygunhJl6EAj4U9JVlQ.lALJ./p64bNSFRC', 'addmin@gmail.com', 'admin', 'server', '0930851543', 'กรุงเทพมหานคร', '', '2025-10-16 18:21:51', 'admin', 'uploads/profiles/4.jpg', NULL, NULL, 'inactive'),
(5, 'จันทร์เจ้า', '$2y$10$t/GaipDbQyYN6z8f4GBtbOVDuIIElix9TJxRJEhmE4G7DFmHp5ZHq', 'janjao@example.com', 'จันทร์จิรา', 'จิราวิวา', '0930851543', 'ตราด', 'เลขที่ 156 หมู่ 5 ', '2025-10-31 19:47:09', 'volunteer', 'uploads/profiles/5_1773231967.jpg', NULL, NULL, 'active'),
(6, 'chom', '$2y$10$RRPvQIqRbdt9ByQqBExwqOm4YFqhOcxafyq.kOpsT3OWtwFG2952W', '1234656@gmail.com', 'ชาชม', 'ชมชา', '0630351542', 'พิษณุโลก', '123  ต.บ้านคลอง  จ.พิษณุโลก ', '2026-08-10 21:52:17', 'user', 'uploads/profiles/6_1786399239.jpg', '5e528aef76cc298d5a083f919f2c96c31cc0597d052e9daef31fd18c2ed9c81a', '2026-01-12 06:02:25', 'inactive'),
(7, 'moo', '$2y$10$97.i/qZiSXYkwKiJlSbK5.pDfEDOolegn1wJuchS928nQ6uBicOme', 'moo7168@gmail.com', 'หมู', 'แก่นโงน', '0630351542', 'กรุงเทพมหานคร', NULL, '2026-01-12 05:09:00', 'volunteer', 'uploads/profiles/7_1774297986.jpg', 'd684bccb1ae93b52487544d53e2dd3ef23177d265d205c2bcf1804255205dd09', '2026-01-12 06:41:25', 'active'),
(8, 'ice', '$2y$10$d84bJR4xZJ6gCM1W/7vuQ.tC2U9XagEvfrSkX3S5vl5BnM7nxTOW6', 'thanwala.097@gmail.com', 'ธัญวรัตม์', 'สียะ', '0982408966', 'ตาก', '71/3 ม.3 ต.โป่งแดง อ.เมืองตาก จ.ตาก 63000', '2026-02-05 17:16:33', 'volunteer', 'uploads/profiles/8_1773643329.jpg', NULL, NULL, 'active'),
(9, 'อภิชาติ วัฒนธรรม', '$2y$10$VPz6xbesYLzFTVfhvsjp0eANjwmGQh9yGirRXm4INWsQ4MYnSgj7C', 'nitradee.kae67@psru.ac.th', 'อภิชาติ', 'แก่นโงน', '0630351542', 'พิษณุโลก', 'เลขที่ 156 หมู่ 5 ถนนเลี่ยงเมืองพิษณุโลกด้านเหนือ ตำบลพลายชุมพล อำเภอเมือง จังหวัดพิษณุโลก 65000', '2026-03-18 06:50:46', 'user', NULL, NULL, NULL, 'active'),
(11, 'กิตติพงษ์', '$2y$10$MzqqcD6ii9uh4LhcGqocjOHTX9mOaGLliRgFPDPAenXkTzfka8E4e', 'kittipong.test@example.com', 'นายกิตติพงษ์', ' รักดี', '', 'ชุมพร', '', '2026-03-25 15:20:09', 'volunteer', NULL, NULL, NULL, 'active'),
(12, 'moonee', '$2y$10$gz/5oQxTWI9DCWfxKz9YRO1CqWsEnKG4CL9zWkev2ltat2ApXBDHe', 'pong_stable@test.com', '123456', 'แก่นโงน', '', 'พิษณุโลก', '', '2026-03-25 15:50:49', 'volunteer', 'uploads/profiles/12_1774453959.jpg', NULL, NULL, 'inactive');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`donation_id`);

--
-- Indexes for table `donation_items`
--
ALTER TABLE `donation_items`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `event_item_targets`
--
ALTER TABLE `event_item_targets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_stock`
--
ALTER TABLE `inventory_stock`
  ADD PRIMARY KEY (`stock_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `join_event`
--
ALTER TABLE `join_event`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `news_images`
--
ALTER TABLE `news_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `fk_news_id` (`news_id`);

--
-- Indexes for table `news_update`
--
ALTER TABLE `news_update`
  ADD PRIMARY KEY (`news_id`),
  ADD KEY `fk_news_user` (`user_id`),
  ADD KEY `fk_news_event` (`event_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `donation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=244;

--
-- AUTO_INCREMENT for table `donation_items`
--
ALTER TABLE `donation_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1005;

--
-- AUTO_INCREMENT for table `event_item_targets`
--
ALTER TABLE `event_item_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=306;

--
-- AUTO_INCREMENT for table `inventory_stock`
--
ALTER TABLE `inventory_stock`
  MODIFY `stock_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `join_event`
--
ALTER TABLE `join_event`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `news_images`
--
ALTER TABLE `news_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `news_update`
--
ALTER TABLE `news_update`
  MODIFY `news_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_stock`
--
ALTER TABLE `inventory_stock`
  ADD CONSTRAINT `inventory_stock_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `donation_items` (`item_id`);

--
-- Constraints for table `join_event`
--
ALTER TABLE `join_event`
  ADD CONSTRAINT `join_event_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `join_event_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;

--
-- Constraints for table `news_images`
--
ALTER TABLE `news_images`
  ADD CONSTRAINT `fk_news_id` FOREIGN KEY (`news_id`) REFERENCES `news_update` (`news_id`) ON DELETE CASCADE;

--
-- Constraints for table `news_update`
--
ALTER TABLE `news_update`
  ADD CONSTRAINT `fk_news_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`),
  ADD CONSTRAINT `fk_news_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
