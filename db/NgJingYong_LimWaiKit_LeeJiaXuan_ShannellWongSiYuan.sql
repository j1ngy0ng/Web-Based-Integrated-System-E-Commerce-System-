-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 20, 2025 at 02:08 AM
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
-- Database: `assignment`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cartID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cartID`, `userID`, `created_at`, `updated_at`) VALUES
(1, 2, '2025-11-20 14:14:52', '2025-12-06 10:25:42'),
(2, 5, '2025-11-20 14:51:22', '2025-11-29 22:01:45'),
(3, 10, '2025-11-23 16:43:34', '2025-11-23 16:43:34'),
(4, 8, '2025-11-23 16:55:30', '2025-11-23 16:55:30'),
(5, 1, '2025-11-29 10:12:38', '2025-11-29 10:12:38');

-- --------------------------------------------------------

--
-- Table structure for table `cart_item`
--

CREATE TABLE `cart_item` (
  `cart_item_id` int(11) NOT NULL,
  `cartID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_message`
--

CREATE TABLE `chat_message` (
  `msgID` int(11) NOT NULL,
  `roomID` int(11) NOT NULL,
  `senderID` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_message`
--

INSERT INTO `chat_message` (`msgID`, `roomID`, `senderID`, `message`, `created_at`) VALUES
(1, 1, 2, 'hello?', '2025-11-29 13:42:53'),
(2, 1, 1, 'moshi', '2025-11-29 13:44:05'),
(3, 1, 1, 'Is Ng Jing Yong beautiful', '2025-11-29 13:44:21'),
(4, 1, 2, 'Of course~ 100%', '2025-11-29 13:44:58'),
(5, 2, 1, '??', '2025-11-29 13:54:56'),
(6, 1, 1, '<3', '2025-11-29 14:02:33'),
(7, 1, 2, '<3', '2025-11-29 14:07:03'),
(8, 1, 1, 'bye', '2025-12-06 10:32:55'),
(9, 1, 2, 'moshi', '2025-12-06 11:48:58'),
(10, 1, 2, 'help', '2025-12-06 11:49:05'),
(11, 1, 2, 'helloo?', '2025-12-06 11:49:09'),
(12, 1, 2, 'got bug', '2025-12-06 11:51:26'),
(13, 1, 2, 'whyyy', '2025-12-06 11:51:29'),
(14, 1, 2, 'yayy', '2025-12-06 11:58:57'),
(15, 1, 2, '123456', '2025-12-06 11:59:02'),
(16, 3, 3, 'hello', '2025-12-06 11:59:21'),
(17, 1, 2, 'djnfoldf', '2025-12-11 21:41:41'),
(18, 1, 2, '????????/', '2025-12-13 11:40:53'),
(19, 1, 2, '...', '2025-12-13 12:21:36');

-- --------------------------------------------------------

--
-- Table structure for table `chat_room`
--

CREATE TABLE `chat_room` (
  `roomID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_read_at` datetime DEFAULT '1970-01-01 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_room`
--

INSERT INTO `chat_room` (`roomID`, `userID`, `created_at`, `last_read_at`) VALUES
(1, 2, '2025-11-29 13:42:45', '2025-12-13 11:45:54'),
(2, 1, '2025-11-29 13:44:31', '2025-12-06 11:52:42'),
(3, 3, '2025-12-06 11:59:18', '2025-12-06 11:59:30');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `userID`, `email`, `token`, `expires_at`, `is_used`, `created_at`) VALUES
(1, 11, '1234@gmail.com', '7cf67d0a6067d5406999a0f3424d80bd', '2025-12-01 15:25:45', 0, '2025-12-01 14:25:45'),
(12, 20, 'jyng-wm23@student.tarc.edu.my', '0b4c25d9899e103fca3670eddf215789', '2025-12-01 16:10:47', 0, '2025-12-01 15:10:47'),
(14, 1, '', '7749d0ee387883beb80ca9ddec91ffc2', '2025-12-01 16:21:01', 0, '2025-12-01 15:21:01'),
(15, 9, '', 'e821d3f9473de976c6e125ffeaa1ad6e', '2025-12-01 16:24:16', 0, '2025-12-01 15:24:16'),
(18, 11, '', '04209df3b8b95797f1abfe2e474e57e2', '2025-12-06 10:47:54', 0, '2025-12-06 09:47:54');

-- --------------------------------------------------------

--
-- Table structure for table `favorite`
--

CREATE TABLE `favorite` (
  `userID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorite`
--

INSERT INTO `favorite` (`userID`, `productID`, `created_at`) VALUES
(2, 3, '2025-12-02 09:20:54'),
(2, 5, '2025-12-06 11:24:43');

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `orderID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `status` enum('Pending','Paid','Shipped','Completed','Cancelled') DEFAULT 'Pending',
  `TotalAmount` decimal(10,2) NOT NULL,
  `voucher_code` varchar(20) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `redeemed_points` int(11) DEFAULT 0,
  `OrderDate` datetime DEFAULT current_timestamp(),
  `address_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`orderID`, `userID`, `status`, `TotalAmount`, `voucher_code`, `discount_amount`, `redeemed_points`, `OrderDate`, `address_id`) VALUES
(7, 3, 'Cancelled', 75.00, NULL, 0.00, 0, '2025-03-01 13:55:47', NULL),
(9, 2, 'Cancelled', 57.00, NULL, 0.00, 0, '2025-11-29 08:56:11', NULL),
(10, 2, 'Paid', 79.80, NULL, 0.00, 0, '2025-11-29 21:55:28', NULL),
(11, 5, 'Paid', 233.70, NULL, 0.00, 0, '2025-11-29 22:01:45', NULL),
(12, 2, 'Cancelled', 114.00, NULL, 0.00, 0, '2025-12-01 12:09:59', NULL),
(13, 2, 'Shipped', 57.00, NULL, 0.00, 0, '2025-12-01 17:31:44', NULL),
(14, 2, 'Completed', 119.70, NULL, 0.00, 0, '2025-12-02 09:22:27', NULL),
(15, 2, 'Completed', 108.60, NULL, 0.00, 0, '2025-12-04 16:05:41', 1),
(16, 2, 'Completed', 171.00, NULL, 0.00, 0, '2025-12-04 21:02:57', 1),
(17, 2, 'Completed', 71.82, 'SAVE10', 7.98, 0, '2025-12-05 17:52:43', NULL),
(18, 2, 'Completed', 194.70, NULL, 0.00, 0, '2025-12-06 09:56:02', 1),
(19, 2, 'Cancelled', 308.10, NULL, 0.00, 0, '2025-12-06 11:28:14', 1),
(20, 2, 'Cancelled', 114.00, NULL, 0.00, 0, '2025-12-11 21:42:18', 1),
(21, 2, 'Completed', 91.80, NULL, 0.00, 0, '2025-12-11 22:14:35', 1),
(22, 2, 'Completed', 124.25, 'SAVE10', 14.25, 0, '2025-12-11 22:49:07', 1),
(25, 2, 'Completed', 96.60, 'SAVE10', 11.40, 300, '2025-12-11 22:58:27', 1),
(26, 2, 'Cancelled', 96.60, 'SAVE10', 11.40, 300, '2025-12-11 23:04:00', 1),
(27, 2, 'Paid', 99.60, 'SAVE10', 11.40, 300, '2025-12-11 23:11:07', 1),
(28, 2, 'Completed', 260.00, NULL, 0.00, 0, '2025-12-20 09:04:05', 1),
(29, 2, 'Completed', 375.40, NULL, 0.00, 0, '2025-12-20 09:04:48', 1),
(30, 2, 'Completed', 1745.70, NULL, 0.00, 0, '2025-12-20 09:05:41', 1),
(31, 2, 'Completed', 641.16, 'SAVE10', 71.24, 0, '2025-12-20 09:06:25', 1);

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `order_item_id` int(11) NOT NULL,
  `orderID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`order_item_id`, `orderID`, `productID`, `quantity`, `price`) VALUES
(2, 10, 5, 2, 39.90),
(3, 11, 3, 4, 28.50),
(4, 11, 5, 3, 39.90),
(5, 12, 3, 4, 28.50),
(6, 13, 3, 2, 28.50),
(7, 14, 5, 3, 39.90),
(8, 15, 3, 2, 28.50),
(9, 15, 1, 4, 12.90),
(10, 16, 3, 6, 28.50),
(11, 17, 5, 2, 39.90),
(12, 18, 2, 3, 45.90),
(13, 18, 3, 2, 28.50),
(14, 19, 3, 9, 28.50),
(15, 19, 1, 4, 12.90),
(16, 20, 3, 4, 28.50),
(17, 21, 2, 2, 45.90),
(18, 22, 3, 5, 28.50),
(19, 25, 3, 4, 28.50),
(20, 26, 3, 4, 28.50),
(21, 27, 3, 4, 28.50),
(22, 28, 34, 4, 65.00),
(23, 29, 28, 2, 8.80),
(24, 29, 25, 6, 54.90),
(25, 29, 22, 2, 14.20),
(26, 30, 10, 4, 170.00),
(27, 30, 31, 4, 193.00),
(28, 30, 30, 3, 97.90),
(29, 31, 23, 4, 14.20),
(30, 31, 20, 8, 66.90),
(31, 31, 19, 4, 30.10);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `paymentID` int(11) NOT NULL,
  `orderID` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `stripe_client_secret` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Completed','Failed') DEFAULT 'Pending',
  `method` varchar(50) DEFAULT 'card',
  `currency` varchar(10) DEFAULT 'MYR',
  `payment_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`paymentID`, `orderID`, `amount`, `stripe_payment_intent_id`, `stripe_client_secret`, `status`, `method`, `currency`, `payment_date`) VALUES
(1, 9, 57.00, 'pi_3SYcRuJ2qvgPlT6g0aUok5iP', 'pi_3SYcRuJ2qvgPlT6g0aUok5iP_secret_XKwmMt2c7fxiBemxYZg2qNWN2', 'Completed', 'card', 'MYR', '2025-11-29 08:56:11'),
(2, 10, 79.80, 'pi_3SYocEJ2qvgPlT6g0274wsXl', 'pi_3SYocEJ2qvgPlT6g0274wsXl_secret_pvZ3B7FfLs8gwkU0MePQm5gxR', 'Completed', 'card', 'MYR', '2025-11-29 21:55:28'),
(3, 11, 233.70, 'pi_3SYoiOJ2qvgPlT6g1zsxW3HT', 'pi_3SYoiOJ2qvgPlT6g1zsxW3HT_secret_C7wLhrXsemaPdUDt2ScwZE0af', 'Completed', 'card', 'MYR', '2025-11-29 22:01:45'),
(4, 12, 114.00, 'pi_3SZOQaJ2qvgPlT6g15prbCpK', 'pi_3SZOQaJ2qvgPlT6g15prbCpK_secret_5tmUMIPOLYLDczXPJDeRQ3WMk', 'Completed', 'card', 'MYR', '2025-12-01 12:09:59'),
(5, 13, 57.00, 'pi_3SZTSDJ2qvgPlT6g1AumGkH1', 'pi_3SZTSDJ2qvgPlT6g1AumGkH1_secret_whp3R4hnkYMha4zm12HrcBzo8', 'Completed', 'card', 'MYR', '2025-12-01 17:31:44'),
(6, 14, 119.70, 'pi_3SZiHtJ2qvgPlT6g1yyhsF7X', 'pi_3SZiHtJ2qvgPlT6g1yyhsF7X_secret_d1uAb6hcOSXVz6pZMwS4KeNLL', 'Completed', 'card', 'MYR', '2025-12-02 09:22:27'),
(7, 15, 108.60, 'pi_3SaXXTJ2qvgPlT6g0agn0bEY', 'pi_3SaXXTJ2qvgPlT6g0agn0bEY_secret_pgZEtsa52VnEbs2LMqUibbN4i', 'Completed', 'card', 'MYR', '2025-12-04 16:05:41'),
(8, 16, 171.00, 'pi_3SacBCJ2qvgPlT6g0sAuQvAC', 'pi_3SacBCJ2qvgPlT6g0sAuQvAC_secret_1Ii4CincyjIgemu5T1fkqIdhS', 'Completed', 'card', 'MYR', '2025-12-04 21:02:57'),
(9, 17, 71.82, 'pi_3SavgfJ2qvgPlT6g1Z9awnbN', 'pi_3SavgfJ2qvgPlT6g1Z9awnbN_secret_dwwaKrg3Ht8HKwWW8QfGEpBB4', 'Completed', 'card', 'MYR', '2025-12-05 17:52:43'),
(10, 18, 194.70, 'pi_3SbAiYJ2qvgPlT6g1jBSkY5A', 'pi_3SbAiYJ2qvgPlT6g1jBSkY5A_secret_dxitxMbyfDzDIFGmfYIjvugl7', 'Completed', 'card', 'MYR', '2025-12-06 09:56:02'),
(11, 19, 308.10, 'pi_3SbC9wJ2qvgPlT6g0nW7PI3m', 'pi_3SbC9wJ2qvgPlT6g0nW7PI3m_secret_KFBV6CZVZNS7gqxUAkgTZsW1Z', 'Completed', 'card', 'MYR', '2025-12-06 11:28:14'),
(12, 20, 114.00, 'pi_3SdA88J2qvgPlT6g1XhDHIRm', 'pi_3SdA88J2qvgPlT6g1XhDHIRm_secret_b6KGNaNxqH3gsyFCpcJuoGnfa', 'Completed', 'card', 'MYR', '2025-12-11 21:42:18'),
(13, 21, 91.80, 'pi_3SdAd6J2qvgPlT6g0LDCPURY', 'pi_3SdAd6J2qvgPlT6g0LDCPURY_secret_Z71KSLjLJYkirDjHBlWkqafiY', 'Completed', 'card', 'MYR', '2025-12-11 22:14:35'),
(14, 22, 124.25, 'pi_3SdBAjJ2qvgPlT6g0lhF4x1F', 'pi_3SdBAjJ2qvgPlT6g0lhF4x1F_secret_1IjofnEQVbcQzUcoTDxm7iodG', 'Completed', 'card', 'MYR', '2025-12-11 22:49:07'),
(15, 25, 96.60, 'pi_3SdBJnJ2qvgPlT6g0umKuybx', 'pi_3SdBJnJ2qvgPlT6g0umKuybx_secret_oUsUSniBmMHXVek2Mfgt9pPqI', 'Completed', 'card', 'MYR', '2025-12-11 22:58:27'),
(16, 26, 96.60, 'pi_3SdBP8J2qvgPlT6g17vTk8zs', 'pi_3SdBP8J2qvgPlT6g17vTk8zs_secret_OTYfm2EkXB7RzFJ8aXhCdG3lD', 'Completed', 'card', 'MYR', '2025-12-11 23:04:00'),
(17, 27, 99.60, 'pi_3SdBW5J2qvgPlT6g0PEl6qHb', 'pi_3SdBW5J2qvgPlT6g0PEl6qHb_secret_Bs4I1rYOEiENU3l3FI0opA29Y', 'Completed', 'card', 'MYR', '2025-12-11 23:11:07'),
(18, 28, 260.00, 'pi_3SgEa5J2qvgPlT6g0BwZQZgf', 'pi_3SgEa5J2qvgPlT6g0BwZQZgf_secret_GW8jeON3xQUN2dSzdsCqTWEUY', 'Completed', 'card', 'MYR', '2025-12-20 09:04:05'),
(19, 29, 375.40, 'pi_3SgEapJ2qvgPlT6g0Ru2fZIJ', 'pi_3SgEapJ2qvgPlT6g0Ru2fZIJ_secret_5iKbi61pvpz9DkN3LFw5X5O5q', 'Completed', 'card', 'MYR', '2025-12-20 09:04:48'),
(20, 30, 1745.70, 'pi_3SgEbgJ2qvgPlT6g1bvwMh6d', 'pi_3SgEbgJ2qvgPlT6g1bvwMh6d_secret_VSc1ntlThaZU14Zdb8QprSK7P', 'Completed', 'card', 'MYR', '2025-12-20 09:05:41'),
(21, 31, 641.16, 'pi_3SgEcXJ2qvgPlT6g0VuIN64t', 'pi_3SgEcXJ2qvgPlT6g0VuIN64t_secret_NRXp37jFrfj3aitglJ8LVdATq', 'Completed', 'card', 'MYR', '2025-12-20 09:06:25');

-- --------------------------------------------------------

--
-- Table structure for table `phone_verifications`
--

CREATE TABLE `phone_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `token` char(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `phone_verifications`
--

INSERT INTO `phone_verifications` (`id`, `user_id`, `phone_number`, `token`, `expires_at`, `verified`, `created_at`) VALUES
(1, 2, '0186632127', '152414', '2025-11-20 15:14:32', 0, '2025-11-20 15:04:32'),
(2, 2, '0186632127', '921151', '2025-11-20 15:14:56', 0, '2025-11-20 15:04:56'),
(3, 2, '0186632127', '791098', '2025-11-20 15:18:11', 0, '2025-11-20 15:08:11'),
(4, 2, '0186632127', '133079', '2025-11-20 15:20:31', 0, '2025-11-20 15:10:31'),
(5, 2, '0186632127', '964202', '2025-11-20 15:22:16', 0, '2025-11-20 15:12:16'),
(6, 2, '0186632127', '595112', '2025-11-20 15:22:57', 0, '2025-11-20 15:12:57'),
(7, 2, '0186632127', '591393', '2025-11-20 15:25:13', 0, '2025-11-20 15:15:13'),
(8, 2, '0186632127', '459376', '2025-11-20 15:25:30', 0, '2025-11-20 15:15:30'),
(9, 2, '0186632127', '637694', '2025-11-20 15:25:52', 0, '2025-11-20 15:15:52'),
(10, 2, '0186632127', '249494', '2025-11-20 15:28:58', 1, '2025-11-20 15:18:58'),
(13, 10, '0186632127', '645152', '2025-11-23 16:53:21', 1, '2025-11-23 16:43:21'),
(15, 1, '0123456789', '598883', '2025-11-23 17:03:49', 1, '2025-11-23 16:53:49'),
(16, 8, '0186632127', '357650', '2025-11-23 17:05:13', 1, '2025-11-23 16:55:13'),
(22, 18, '0186632127', '462528', '2025-12-01 15:07:52', 1, '2025-12-01 14:57:52'),
(24, 20, '0186632127', '873640', '2025-12-01 15:18:11', 1, '2025-12-01 15:08:11'),
(25, 21, '0186632127', '629846', '2025-12-02 09:45:54', 1, '2025-12-02 09:35:54');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `productID` int(11) NOT NULL,
  `categoryID` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `video_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rating_avg` decimal(3,2) DEFAULT 0.00,
  `rating_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `model3d` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`productID`, `categoryID`, `name`, `description`, `price`, `stock`, `video_url`, `created_at`, `updated_at`, `rating_avg`, `rating_count`, `is_active`, `model3d`) VALUES
(1, 3, 'Vaseline Original Petroleum Jelly', 'Multi-purpose petroleum jelly for skin protection and moisturizing.', 12.90, 192, 'vaseline-original-petroleum-jelly_video_1766192449_3653.mp4', '2025-11-19 18:01:36', '2025-12-20 09:00:49', 0.00, 0, 1, NULL),
(2, 1, 'Cetaphil Gentle Skin Cleanser', 'Daily facial cleanser for sensitive skin. Non-irritating and hypoallergenic formula that cleanses without stripping natural oils.', 45.90, 145, NULL, '2025-11-20 14:14:25', '2025-12-11 22:20:20', 3.50, 0, 1, NULL),
(3, 2, 'L\'Oreal Paris Elvive Shampoo', 'Hydrating shampoo for dry and damaged hair. Enriched with Pro-Keratin and Ceramide to repair and strengthen hair fibers.', 28.50, 248, 'l-oreal-paris-elvive-shampoo_video_1766192429_9510.mp4', '2025-11-20 14:14:25', '2025-12-20 09:00:29', 4.50, 0, 1, NULL),
(5, 5, 'Digital Thermometer 2', 'Fast and accurate digital thermometer with LCD display. Suitable for oral, underarm, and rectal use with beep alert.', 39.90, 90, 'digital-thermometer-2_video_1766192374_7269.mp4', '2025-11-20 14:14:26', '2025-12-20 08:59:34', 0.00, 0, 1, 'digital-thermometer-2_3d_1766192374.glb'),
(10, 8, 'Rare Beauty True To Myself Tinted Pressed Finishing Powder • 8g', 'How To Use on bare skin for easy, no-makeup makeup; over tinted moisturiser or foundation for boosted coverage; on top of concealer or Positive Light Under Eye Brightener (sold separately) to set; or to touch up makeup wherever you go.Made for all skin types to love, even dry and combo skin. For best results, be sure to prep your skin with moisturiser, then apply your favourite Always an Optimist primer (sold separately).Lightly sweep and buff into skin using the Always an Optimist Powder Brush.', 170.00, 6, 'rare-beauty-true-to-myself-tinted-pressed-finishing-powder-8g_video_1764384650_7668.mp4', '2025-11-29 10:50:07', '2025-12-20 09:08:10', 5.00, 0, 1, NULL),
(14, 19, 'Reviving Rain', 'Ultimate Hydration Body Cream', 110.00, 5, '', '2025-12-14 12:50:27', '2025-12-14 12:50:27', 0.00, 0, 1, ''),
(15, 19, 'Glowing With Fruit Enzymes', 'Body Polish', 119.00, 10, '', '2025-12-14 12:52:09', '2025-12-14 12:52:09', 0.00, 0, 1, ''),
(16, 12, 'BABY WIPES UNSCENTED 20SX3', 'Baby Wipes with no scents', 6.50, 10, '', '2025-12-14 12:54:12', '2025-12-14 12:54:12', 0.00, 0, 1, ''),
(17, 12, 'JOHNSON\'S Baby Regular Lotion 500ml', 'Johnson Baby Lotion', 28.60, 10, '', '2025-12-14 13:05:40', '2025-12-14 13:05:40', 0.00, 0, 1, ''),
(18, 3, 'NIVEA Body CARE Creme Intensive Nourishment 50ml', 'Promote formulated products with palm oil coming from responsibly managed plantations.This product formulation contains 100% of certified sustainable palm oil/derivatives.Rich in moisturizing light crystals melting directly into the skin. Contains little hydro-waxes that melt immediately after application to the skin. Incredibly light feel on the skin. More than 24 hours intensive care. Intensely hydrates and care for your skin. Quick absorbing, non sticky finish, 0 greasiness.', 11.90, 15, '', '2025-12-14 13:36:13', '2025-12-14 13:36:13', 0.00, 0, 1, ''),
(19, 3, 'WATSONS Fun & Fresh Hair & Body Care Set', 'Watsons Fun & Fresh Hair & Body Care Set consist of body wash 800ml + Shampoo 500ml Promote a reduced usage of virgin materials by using recycled content instead The bottle is made with 20% recycled plastic. This safe and mild formula is infused with Aromaguard to instantly eliminate sweat odours and keep kids smelling super fresh even after play.', 30.10, 4, '', '2025-12-14 13:37:03', '2025-12-20 09:06:25', 0.00, 0, 1, ''),
(20, 15, 'First Aid Kit Medium 1\'s', 'First Aid Kit Medium Content List: Quality AS Plastic Container, 1 box; Antiseptic Cream 10gm, 1 tube; Liniment Methyl Salicylate 30ml, 1 bottle; Antiseptic Lotion 60ml, 1 bottle; Fabric Plaster Strips, 10 pcs; Absorbent Cotton Swabs 5cm x 5cm (15gm), 1 packet; Gauze Swabs 5cm x 5cm x 8ply x 8pcs, 1 packet; W.O.W Bandage 5cm x 5.5m, 1 roll; Safety Pins, 12’s; Adhesive Roll Plaster 1.25cm x 1m, 1 roll; Scissors Non-Foldable, Small, 1 pair; Medicated Oil 3ml, 1 bottle; Pre-Injection Swabs, 2 pcs;', 66.90, 4, '', '2025-12-14 13:38:27', '2025-12-20 09:06:25', 0.00, 0, 1, ''),
(21, 15, 'DR WOUND Bioheal Gel First Aid Gel 20ml', 'Bioheal Gel First Aid Gel 20ml contains chitosan biopolymer and is used to accelerates wound healing by promoting moisture, reduce scarring and reduce keloid formation.', 44.90, 4, '', '2025-12-14 13:39:10', '2025-12-14 13:39:10', 0.00, 0, 1, ''),
(22, 7, 'DASHING Tottenham Deodorant Perfume Body Spray 10 120ml', 'DASHING DEO+PERFUME Body Spray is the first deodorant + perfume product in the market here. It combines both benefits of a deodorant and perfume into one. Formulated with long-lasting masculine DASHING perfume that ensures you\'re feeling fresh and smelling great throughout the day.', 14.20, 3, '', '2025-12-14 13:40:26', '2025-12-20 09:08:00', 5.00, 0, 1, ''),
(23, 7, 'DASHING Tottenham Deodorant Perfume Body Spray Hattrick 120ml', 'DASHING DEO+PERFUME Body Spray is the first deodorant + perfume product in the market here. It combines both benefits of a deodorant and perfume into 1.\r\nFormulated with long-lasting, masculine DASHING fragrances it ensures you\'re feeling fresh and smelling great throughout the day.', 14.20, 41, '', '2025-12-14 13:41:23', '2025-12-20 09:06:25', 0.00, 0, 1, ''),
(24, 2, 'LUCIDO-L HAIR TREATMENT WATER 170ML', 'Contains highly compressed argan oil that repairs damaged hair such as split ends & breakage while leaving hair smooth. UV & Heat protection formula that protects hair agaisnt heat from ultraviolet rays & heat-styling tools. Contains gentle & glamorous floral fragrance. Product Features: - Water-based treatment lightly refreshes and gives hair a healthily moisturized, shiny & radiant finish.', 24.90, 30, '', '2025-12-14 13:42:32', '2025-12-14 13:42:32', 0.00, 0, 1, ''),
(25, 2, 'ELLIPS Hair Treatment Hair Oil 95ml', 'Hair Vitamin enriched with Moroccan / Argan Oil, Jojoba Oil, Vitamin A, C, E & Pro Vitamin B5 to intensively nourish and protect chemically damaged hair, making it easier to manage. Hair looks healthier and shinier.', 54.90, 28, '', '2025-12-14 13:43:06', '2025-12-20 09:07:55', 5.00, 0, 1, ''),
(26, 8, 'AMORTALS Portable Makeup Brush Set (5pcs)', 'Soft, high-quality bristles for flawless makeup application. Includes brushes for blending, contouring, and detailing, ensuring precision and versatility for all your beauty needs', 49.00, 343, '', '2025-12-14 13:44:10', '2025-12-14 13:44:10', 0.00, 0, 1, ''),
(27, 8, 'REVLON ColorStay Makeup Pump O/C 200 Nude', 'This oil-free foundation delivers a flawless matte finish for oily and combination skin, and wears for up to 24 hours, no matter what your day brings. That\'s why we call it \"life-tested\" foundation.The formula delivers a flawless matte finish, and is also oil-free and fortified with SPF 15, to help protect your skin. It provides medium-to-full buildable coverage that last.Shake well and can be apply with a small dab to one area at a time.', 59.90, 4, '', '2025-12-14 13:44:55', '2025-12-14 13:44:55', 0.00, 0, 1, ''),
(28, 13, 'BZU BZU Kids Oral Care Travel Kit Strawberry 1s', 'Keep your child prepared when leaving home with our BZU BZU Oral Care Travel Kit. It is designed for children ages 3 to 7 years old. The kit includes a toothbrush with a travel protective cap and toothpaste. Pre-packaged in a convenient box perfect for home or on the go.', 8.80, 1, '', '2025-12-14 13:45:40', '2025-12-20 09:07:51', 1.00, 0, 1, ''),
(29, 13, 'JORDAN Green Clean Toothbrush Kids', 'Promote paper packaging made with paper from responsibly managed forest, or with recycled content.The label is made of FSC paper, the paper used in this packaging is made from 100% recycled paper.Promote a reduced usage of virgin materials by using recycled content instead.The label is made of FSC paper, the paper used in this packaging is made from 100% recycled paper.Green Clean Kids toothbrush combines great functionality,', 9.90, 454, '', '2025-12-14 13:46:14', '2025-12-14 13:46:14', 0.00, 0, 1, ''),
(30, 1, 'L\'OREAL PARIS SKIN CARE Hyaluronic Acid Oil Cream 50ml', 'LOREAL PARIS REVITALIFT HYALURONIC ACID 8H OIL CONTROL GEL-CREAM, an oil-free formulation enriched with Hyaluronic acid and Salicylic acid, in an ultra-fresh and lightweight gel that absorbs quickly. Hydrates 10 layers deep whilst reducing excess oils and minimizing pores to reveal plumpy, no shine skin. HYALURONIC ACID Holds multiple times its weight in water and locks in moisture to hydrate and replump skin.', 97.90, 3, '', '2025-12-14 13:47:58', '2025-12-20 09:08:26', 5.00, 0, 1, ''),
(31, 1, 'L\'OREAL PARIS SKIN CARE Youth Code Pre Essence 30ml', 'With age, your skin recovers less quickly:facial features look tired, fine lines appear, skin tone is dull.A NEW ERA OF SKINCARE ISSUED FROM GENE SCIENCE - 10 YEARS OF RESEARCHAfter 10 years of research, L\'Oreal Laboratories discovered that recovery genes in youthful skin respond 5X faster to aggressions like fatigue and stress than in older skin.', 193.00, 30, '', '2025-12-14 13:48:41', '2025-12-20 09:08:18', 5.00, 0, 1, ''),
(32, 14, 'WATSONS Aqua UV Sun Protection Spray SPF50+ PA++++', 'Skin and hair use 1. High UV Protection: SPF50+, PA++++ against UVA & UVB 2. Ultra-light texture: weightless coverage with an invisible naked skin finish 3. Hyaluronate: Long lasting hydration effect Free from parabens, synthetic colour, fragrance, alcohol', 59.90, 6, '', '2025-12-14 13:49:28', '2025-12-14 13:49:28', 0.00, 0, 1, ''),
(33, 14, 'NIVEA Sun Protection for Body Set', 'Nivea Sun Protect and Light Feel, sun screen with no white cast and light on your face to be pair with our Nivea Soft Cream Jar to compliment your sun protect routine. NIVEA Sun Protect and Light Feel with SPF50 offers superior protection while keeping your skin light and fresh. Its lightweight texture is easily absorbed, leaving no residue.This formula is non comedogenic, making it ideal for all skin types, while being free from 14 irritating ingredients. It is also fragrance free.', 83.90, 40, '', '2025-12-14 13:50:07', '2025-12-14 13:50:07', 0.00, 0, 1, ''),
(34, 8, 'Lipstick Matte 90g', 'Hot Red Lips', 65.00, 411, '', '2025-12-20 09:02:08', '2025-12-20 09:07:44', 5.00, 0, 1, 'lipstick-matte-90g_3d_1766192528.glb');

-- --------------------------------------------------------

--
-- Table structure for table `productcategory`
--

CREATE TABLE `productcategory` (
  `categoryID` int(11) NOT NULL,
  `categoryName` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productcategory`
--

INSERT INTO `productcategory` (`categoryID`, `categoryName`, `description`) VALUES
(1, 'Skincare', 'Products for facial and body skincare such as cleansers, moisturizers, and serums'),
(2, 'Hair Care', 'Shampoos, conditioners, hair oils, and styling products'),
(3, 'Body Care', 'Lotions, body washes, scrubs, and deodorants'),
(4, 'Health Supplements', 'Vitamins, minerals, and dietary supplements to support overall health'),
(5, 'Medical Device', 'Home-use healthcare devices such as thermometers, BP monitors, and glucometers'),
(6, 'Personal Hygiene', 'Sanitary items, antibacterial products, and daily hygiene essentials'),
(7, 'Fragrances', 'Perfumes, body mists, and scented sprays'),
(8, 'Makeup', 'Cosmetics including foundation, lipsticks, mascara, and more'),
(9, 'Men’s Grooming', 'Shaving kits, beard oils, men’s skincare and hygiene products'),
(10, 'Wellness Essentials', 'Essential oils, massage tools, and stress relief products'),
(12, 'Baby Care', 'Gentle skincare, diapers, wipes, and hygiene products for babies and toddlers'),
(13, 'Oral Care', 'Toothpaste, toothbrushes, mouthwash, and dental hygiene products'),
(14, 'Sun Protection', 'Sunscreens, after-sun lotions, and UV protection skincare products'),
(15, 'First Aid', 'Basic medical supplies such as bandages, antiseptics, and wound care products'),
(16, 'Fitness & Recovery', 'Fitness accessories, muscle recovery tools, and sports support products'),
(17, 'Natural & Organic', 'Organic, chemical-free, and plant-based personal care products'),
(18, 'Women’s Health', 'Feminine hygiene, menstrual care, and women-focused health products'),
(19, 'Aromatherapy', 'Aromatic oils, diffusers, and relaxation-focused fragrance products'),
(20, 'Foot Care', 'Foot creams, sprays, insoles, and treatments for foot hygiene and comfort'),
(21, 'Senior Care', 'Healthcare and personal care products designed for elderly needs');

-- --------------------------------------------------------

--
-- Table structure for table `productphoto`
--

CREATE TABLE `productphoto` (
  `photoID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `photoURL` varchar(255) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productphoto`
--

INSERT INTO `productphoto` (`photoID`, `productID`, `photoURL`, `is_main`) VALUES
(1, 1, 'vaseline_main.jpg', 1),
(2, 1, 'vaseline_side.jpg', 0),
(3, 1, 'vaseline_open.jpg', 0),
(4, 2, 'cetaphil_cleanser_main.jpg', 1),
(5, 2, 'cetaphil_cleanser_back.jpg', 0),
(6, 2, 'cetaphil_cleanser_usage.jpg', 0),
(7, 3, 'loreal_shampoo_main.jpg', 1),
(8, 3, 'loreal_shampoo_back.jpg', 0),
(9, 3, 'loreal_shampoo_bottle.jpg', 0),
(13, 5, 'digital-thermometer_main_1763984044_5781.JPG', 1),
(21, 10, 'rare-beauty-true-to-myself-tinted-pressed-finishing-powder-8g_main_1765691212_9483.png', 1),
(22, 14, 'reviving-rain_main_1765687827_4188.jpg', 1),
(23, 15, 'glowing-with-fruit-enzymes_main_1765687929_4127.png', 1),
(24, 16, 'baby-wipes-unscented-20sx3_main_1765688052_5605.jpg', 1),
(25, 17, 'johnson-s-baby-regular-lotion-500ml_main_1765688740_4612.jpg', 1),
(26, 18, 'nivea-body-care-creme-intensive-nourishment-50ml_main_1765690573_1513.jpg', 1),
(27, 19, 'watsons-fun-fresh-hair-body-care-set_main_1765690623_8590.jpg', 1),
(28, 20, 'first-aid-kit-medium-1-s_main_1765690707_9296.jpg', 1),
(29, 21, 'dr-wound-bioheal-gel-first-aid-gel-20ml_main_1765690750_5622.png', 1),
(30, 22, 'dashing-tottenham-deodorant-perfume-body-spray-10-120ml_main_1765690826_6685.jpg', 1),
(31, 23, 'dashing-tottenham-deodorant-perfume-body-spray-hattrick-120ml_main_1765690883_8052.png', 1),
(32, 24, 'lucido-l-hair-treatment-water-170ml_main_1765690952_8169.png', 1),
(33, 25, 'ellips-hair-treatment-hair-oil-95ml_main_1765690986_1106.png', 1),
(34, 26, 'amortals-portable-makeup-brush-set-5pcs_main_1765691050_2579.png', 1),
(35, 27, 'revlon-colorstay-makeup-pump-o-c-200-nude_main_1765691095_4276.png', 1),
(36, 28, 'bzu-bzu-kids-oral-care-travel-kit-strawberry-1s_main_1765691140_3254.png', 1),
(37, 29, 'jordan-green-clean-toothbrush-kids_main_1765691174_2720.png', 1),
(38, 30, 'l-oreal-paris-skin-care-hyaluronic-acid-oil-cream-50ml_main_1765691278_1318.png', 1),
(39, 31, 'l-oreal-paris-skin-care-youth-code-pre-essence-30ml_main_1765691321_1179.png', 1),
(40, 32, 'watsons-aqua-uv-sun-protection-spray-spf50-pa_main_1765691368_3517.png', 1),
(41, 33, 'nivea-sun-protection-for-body-set_main_1765691407_2853.png', 1),
(42, 34, 'lipstick-matte-90g_main_1766192528_5904.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `productrating`
--

CREATE TABLE `productrating` (
  `ratingID` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `rating_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productrating`
--

INSERT INTO `productrating` (`ratingID`, `order_item_id`, `rating`, `comment`, `rating_date`) VALUES
(1, 7, 1, 'Beautiful boy', '2025-12-04 15:52:39'),
(2, 8, 5, 'good', '2025-12-04 16:06:50'),
(3, 9, 3, 'soso\r\n\r\nmaybe\r\n\r\ni oso dk\r\n\r\nhahaha\r\n\r\nbyebye', '2025-12-04 16:07:06'),
(4, 12, 3, 'dads', '2025-12-06 10:02:18'),
(5, 13, 4, 'fefr', '2025-12-06 10:02:23'),
(6, 17, 4, 'goat', '2025-12-11 22:20:20'),
(7, 22, 5, 'good', '2025-12-20 09:07:44'),
(8, 23, 1, 'bad', '2025-12-20 09:07:51'),
(9, 24, 5, 'ok', '2025-12-20 09:07:55'),
(10, 25, 5, 'smelly', '2025-12-20 09:08:00'),
(11, 26, 5, 'white', '2025-12-20 09:08:10'),
(12, 27, 5, 'very good', '2025-12-20 09:08:18'),
(13, 28, 5, 'best', '2025-12-20 09:08:26');

-- --------------------------------------------------------

--
-- Table structure for table `refund`
--

CREATE TABLE `refund` (
  `refundID` int(11) NOT NULL,
  `orderID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Completed','Failed') DEFAULT 'Pending',
  `refund_date` datetime DEFAULT current_timestamp(),
  `reason` text DEFAULT 'Order cancelled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `refund`
--

INSERT INTO `refund` (`refundID`, `orderID`, `userID`, `amount`, `status`, `refund_date`, `reason`) VALUES
(1, 9, 2, 57.00, 'Completed', '2025-11-29 09:30:30', 'Order cancelled'),
(2, 7, 3, 75.00, 'Failed', '2025-11-29 13:16:28', 'Order cancelled by Admin'),
(3, 12, 2, 114.00, 'Failed', '2025-12-01 12:13:23', 'Order cancelled'),
(4, 19, 2, 308.10, 'Pending', '2025-12-11 22:09:59', 'Order cancelled by Admin');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `selector` char(12) NOT NULL,
  `validator_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_addresses`
--

CREATE TABLE `shipping_addresses` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipping_addresses`
--

INSERT INTO `shipping_addresses` (`address_id`, `user_id`, `recipient_name`, `phone_number`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `latitude`, `longitude`, `is_default`, `created_at`) VALUES
(1, 2, 'jessica Ng 1345', '0185552164', '223 jalan tanjung rambutan 3', 'taman midah', 'Selangor', 'Selangor', '46513', 0.00000000, 0.00000000, 1, '2025-12-02 01:21:47');

-- --------------------------------------------------------

--
-- Table structure for table `token`
--

CREATE TABLE `token` (
  `id` varchar(100) NOT NULL,
  `expire` datetime NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `photo` varchar(100) NOT NULL,
  `role` varchar(100) NOT NULL,
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `lock_until` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `phone_number` varchar(20) DEFAULT NULL,
  `phone_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified` tinyint(1) DEFAULT 0,
  `reward_points` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `email`, `password`, `name`, `photo`, `role`, `failed_attempts`, `lock_until`, `is_active`, `phone_number`, `phone_verified`, `email_verified`, `reward_points`) VALUES
(1, '1@gmail.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'Lisa Manobal', '69338863b329b.jpg', 'Superadmin', 0, NULL, 1, '0123456789', 1, 1, NULL),
(2, '2@gmail.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'Kim Jisoo', '69183c6a47c0f.jpg', 'Member', 0, NULL, 1, '0186632127', 1, 1, 629),
(3, 'njy@gmail.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'Ng Jing Yong', '6917f5b722a8f.jpg', 'Member', 0, '2025-12-06 11:41:28', 1, '0186632127', 0, 0, 0),
(4, 'lwk@gmail.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'Lim Wai Kit', '691849633defe.jpg', 'Member', 0, NULL, 1, '0186632127', 0, 0, 0),
(5, 'ngjingyong168@gmail.com', '8cb2237d0679ca88db6464eac60da96345513964', 'Jessica Ng', '6918643cdab6f.jpg', 'Member', 0, NULL, 1, '0186632127', 1, 0, 0),
(6, 'njy1@gmail.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'hi', '691a84c1c1b33.jpg', 'Member', 0, NULL, 1, '0186632127', 0, 0, 0),
(8, '4@gmail.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'hi', '6922c364cc615.jpg', 'Member', 0, NULL, 1, '0186632127', 1, 0, 0),
(9, '3@gmail.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'Roseanne Park', '6922c6a9e8077.jpg', 'Member', 0, NULL, 1, '0186632127', 0, 0, 0),
(10, 'lisa@gmail.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'Lisa Manobal', '6922c928e5a53.jpg', 'Member', 0, NULL, 1, '0186632127', 1, 0, 0),
(11, '1234@gmail.com', '$2y$10$jDkZhc5ll4DdrvGXIfahbeuw3jluEzO.FTwh31c.4GxqXZrddCRtu', 'Bae Suzy XXX', '692d34e954c88.jpg', 'Member', 0, NULL, 1, '0186632127', 0, 0, 0),
(18, 'm-6662409@moe-dl.edu.my', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'hi', '692d3c70188de.jpg', 'Member', 0, NULL, 1, '0186632127', 1, 1, 0),
(20, 'jyng-wm23@student.tarc.edu.my', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'hi', '692d3edae2529.jpg', 'Member', 0, NULL, 1, '0186632127', 1, 1, 0),
(21, 'njywbisdemo@gmail.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'Ng Jing Yong', '692e427a1437b.jpg', 'Member', 0, NULL, 1, '0186632127', 1, 1, 0),
(23, '12345@gmail.com', '$2y$10$MFvayjuWgvLY9G9vocEOZu0p1V1JcwYT6QBzwp.caBI3fx/u44m8O', 'Ng Jing Yong', '69338b37031a7.png', 'Member', 0, '2025-12-06 09:54:08', 1, '0186632127', 1, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `voucher_code` varchar(20) NOT NULL,
  `discount_type` enum('fixed','percent') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_spend` decimal(10,2) DEFAULT 0.00,
  `expiry_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`voucher_code`, `discount_type`, `discount_value`, `min_spend`, `expiry_date`) VALUES
('MIN50', 'fixed', 50.00, 200.00, '2030-12-31'),
('NJY200', 'fixed', 20.00, 50.00, '2026-01-01'),
('SAVE10', 'percent', 10.00, 0.00, '2030-12-31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cartID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `cart_item`
--
ALTER TABLE `cart_item`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `cartID` (`cartID`),
  ADD KEY `productID` (`productID`);

--
-- Indexes for table `chat_message`
--
ALTER TABLE `chat_message`
  ADD PRIMARY KEY (`msgID`),
  ADD KEY `roomID` (`roomID`),
  ADD KEY `senderID` (`senderID`);

--
-- Indexes for table `chat_room`
--
ALTER TABLE `chat_room`
  ADD PRIMARY KEY (`roomID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `favorite`
--
ALTER TABLE `favorite`
  ADD PRIMARY KEY (`userID`,`productID`),
  ADD KEY `productID` (`productID`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`orderID`),
  ADD KEY `userID` (`userID`),
  ADD KEY `address_id` (`address_id`),
  ADD KEY `fk_order_voucher` (`voucher_code`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `orderID` (`orderID`),
  ADD KEY `productID` (`productID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`paymentID`),
  ADD KEY `orderID` (`orderID`);

--
-- Indexes for table `phone_verifications`
--
ALTER TABLE `phone_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`productID`),
  ADD KEY `categoryID` (`categoryID`);

--
-- Indexes for table `productcategory`
--
ALTER TABLE `productcategory`
  ADD PRIMARY KEY (`categoryID`);

--
-- Indexes for table `productphoto`
--
ALTER TABLE `productphoto`
  ADD PRIMARY KEY (`photoID`),
  ADD KEY `productID` (`productID`);

--
-- Indexes for table `productrating`
--
ALTER TABLE `productrating`
  ADD PRIMARY KEY (`ratingID`),
  ADD UNIQUE KEY `unique_rating_per_orderitem` (`order_item_id`);

--
-- Indexes for table `refund`
--
ALTER TABLE `refund`
  ADD PRIMARY KEY (`refundID`),
  ADD KEY `orderID` (`orderID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `shipping_addresses`
--
ALTER TABLE `shipping_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `token`
--
ALTER TABLE `token`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`voucher_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cartID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cart_item`
--
ALTER TABLE `cart_item`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `chat_message`
--
ALTER TABLE `chat_message`
  MODIFY `msgID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `chat_room`
--
ALTER TABLE `chat_room`
  MODIFY `roomID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `orderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `paymentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `phone_verifications`
--
ALTER TABLE `phone_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `productID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `productcategory`
--
ALTER TABLE `productcategory`
  MODIFY `categoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `productphoto`
--
ALTER TABLE `productphoto`
  MODIFY `photoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `productrating`
--
ALTER TABLE `productrating`
  MODIFY `ratingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `refund`
--
ALTER TABLE `refund`
  MODIFY `refundID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `shipping_addresses`
--
ALTER TABLE `shipping_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`id`);

--
-- Constraints for table `cart_item`
--
ALTER TABLE `cart_item`
  ADD CONSTRAINT `cart_item_ibfk_1` FOREIGN KEY (`cartID`) REFERENCES `cart` (`cartID`),
  ADD CONSTRAINT `cart_item_ibfk_2` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`);

--
-- Constraints for table `chat_message`
--
ALTER TABLE `chat_message`
  ADD CONSTRAINT `chat_message_ibfk_1` FOREIGN KEY (`roomID`) REFERENCES `chat_room` (`roomID`),
  ADD CONSTRAINT `chat_message_ibfk_2` FOREIGN KEY (`senderID`) REFERENCES `user` (`id`);

--
-- Constraints for table `chat_room`
--
ALTER TABLE `chat_room`
  ADD CONSTRAINT `chat_room_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`id`);

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorite`
--
ALTER TABLE `favorite`
  ADD CONSTRAINT `favorite_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `favorite_ibfk_2` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`);

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `fk_order_voucher` FOREIGN KEY (`voucher_code`) REFERENCES `vouchers` (`voucher_code`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `order_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `shipping_addresses` (`address_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`orderID`) REFERENCES `order` (`orderID`),
  ADD CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`orderID`) REFERENCES `order` (`orderID`);

--
-- Constraints for table `phone_verifications`
--
ALTER TABLE `phone_verifications`
  ADD CONSTRAINT `phone_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`categoryID`) REFERENCES `productcategory` (`categoryID`);

--
-- Constraints for table `productphoto`
--
ALTER TABLE `productphoto`
  ADD CONSTRAINT `productphoto_ibfk_1` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`) ON DELETE CASCADE;

--
-- Constraints for table `productrating`
--
ALTER TABLE `productrating`
  ADD CONSTRAINT `productrating_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_item` (`order_item_id`) ON DELETE CASCADE;

--
-- Constraints for table `refund`
--
ALTER TABLE `refund`
  ADD CONSTRAINT `refund_ibfk_1` FOREIGN KEY (`orderID`) REFERENCES `order` (`orderID`),
  ADD CONSTRAINT `refund_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `user` (`id`);

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipping_addresses`
--
ALTER TABLE `shipping_addresses`
  ADD CONSTRAINT `shipping_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `token`
--
ALTER TABLE `token`
  ADD CONSTRAINT `token_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
COMMIT;



-- Table structure for table `Events`
CREATE TABLE product_event (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_promo TINYINT(1) DEFAULT 0
);


CREATE TABLE product_event_item (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    product_id INT NOT NULL,
    FOREIGN KEY (event_id) REFERENCES product_event(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(productID) ON DELETE CASCADE
);



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
