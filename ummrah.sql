-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 11, 2025 at 09:57 AM
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
-- Database: `ummrah`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `email`, `password`) VALUES
(6, 'admin@admin.com', '$2y$10$0Hbz84KSB4PdO6rOVNlTNOsGa6Ao0KH0TGJWhYpCZbZjfFXsEa2Ta'),
(7, 'admin@admins.com', '$2y$10$rdFIaL7oopxNOS1qnMc5Z.Xy/BG6XyycGwX9l/UUg1bAXRKM9w7t6');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flights`
--

CREATE TABLE `flights` (
  `id` int(11) NOT NULL,
  `airline_name` varchar(100) NOT NULL,
  `flight_number` varchar(50) NOT NULL,
  `departure_city` varchar(100) NOT NULL,
  `arrival_city` varchar(100) NOT NULL,
  `departure_date` date NOT NULL,
  `departure_time` time NOT NULL,
  `flight_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `cabin_class` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`cabin_class`)),
  `prices` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`prices`)),
  `seats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`seats`)),
  `stops` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stops`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flights`
--

INSERT INTO `flights` (`id`, `airline_name`, `flight_number`, `departure_city`, `arrival_city`, `departure_date`, `departure_time`, `flight_notes`, `created_at`, `cabin_class`, `prices`, `seats`, `stops`) VALUES
(25, 'PIA', 'PK-309', 'Karachi', 'Jeddah', '2025-03-03', '12:26:00', 'optional', '2025-03-03 02:26:26', '[\"Economy\", \"Business\", \"First Class\"]', '{\"economy\": 12, \"business\": 12, \"first_class\": 12}', '{\"economy\": {\"count\": 12, \"seat_ids\": [\"E1\", \"E2\", \"E3\", \"E4\", \"E5\", \"E6\", \"E7\", \"E8\", \"E9\", \"E10\", \"E11\", \"E12\"]}, \"business\": {\"count\": 12, \"seat_ids\": [\"B1\", \"B2\", \"B3\", \"B4\", \"B5\", \"B6\", \"B7\", \"B8\", \"B9\", \"B10\", \"B11\", \"B12\"]}, \"first_class\": {\"count\": 12, \"seat_ids\": [\"F1\", \"F2\", \"F3\", \"F4\", \"F5\", \"F6\", \"F7\", \"F8\", \"F9\", \"F10\", \"F11\", \"F12\"]}}', '[{\"city\": \"Laudantium praesent\", \"duration\": \"Ea est possimus ut\"}]'),
(26, 'Flynas', '642', 'Karachi', 'Jeddah', '2008-04-12', '07:50:00', 'Saepe ullam possimus', '2025-03-03 02:46:21', '[\"Economy\", \"Business\", \"First Class\"]', '{\"economy\": 316, \"business\": 572, \"first_class\": 76}', '{\"economy\": {\"count\": 28, \"seat_ids\": [\"E1\", \"E2\", \"E3\", \"E4\", \"E5\", \"E6\", \"E7\", \"E8\", \"E9\", \"E10\", \"E11\", \"E12\", \"E13\", \"E14\", \"E15\", \"E16\", \"E17\", \"E18\", \"E19\", \"E20\", \"E21\", \"E22\", \"E23\", \"E24\", \"E25\", \"E26\", \"E27\", \"E28\"]}, \"business\": {\"count\": 27, \"seat_ids\": [\"B1\", \"B2\", \"B3\", \"B4\", \"B5\", \"B6\", \"B7\", \"B8\", \"B9\", \"B10\", \"B11\", \"B12\", \"B13\", \"B14\", \"B15\", \"B16\", \"B17\", \"B18\", \"B19\", \"B20\", \"B21\", \"B22\", \"B23\", \"B24\", \"B25\", \"B26\", \"B27\"]}, \"first_class\": {\"count\": 23, \"seat_ids\": [\"F1\", \"F2\", \"F3\", \"F4\", \"F5\", \"F6\", \"F7\", \"F8\", \"F9\", \"F10\", \"F11\", \"F12\", \"F13\", \"F14\", \"F15\", \"F16\", \"F17\", \"F18\", \"F19\", \"F20\", \"F21\", \"F22\", \"F23\"]}}', '\"direct\"'),
(27, 'PIA', 'PK-309', 'Lahore', 'Jeddah', '2025-03-04', '13:10:00', 'NEW', '2025-03-04 02:10:37', '[\"Economy\", \"Business\", \"First Class\"]', '{\"economy\": 12, \"business\": 12, \"first_class\": 12}', '{\"economy\": {\"count\": 12, \"seat_ids\": [\"E1\", \"E2\", \"E3\", \"E4\", \"E5\", \"E6\", \"E7\", \"E8\", \"E9\", \"E10\", \"E11\", \"E12\"]}, \"business\": {\"count\": 12, \"seat_ids\": [\"B1\", \"B2\", \"B3\", \"B4\", \"B5\", \"B6\", \"B7\", \"B8\", \"B9\", \"B10\", \"B11\", \"B12\"]}, \"first_class\": {\"count\": 12, \"seat_ids\": [\"F1\", \"F2\", \"F3\", \"F4\", \"F5\", \"F6\", \"F7\", \"F8\", \"F9\", \"F10\", \"F11\", \"F12\"]}}', '\"direct\"'),
(28, 'Flynas', 'PK-309', 'Karachi', 'Jeddah', '2025-03-05', '13:08:00', 'sdf sadf sd  fsadf', '2025-03-05 02:08:59', '[\"Economy\", \"Business\", \"First Class\"]', '{\"economy\": 12, \"business\": 12, \"first_class\": 12}', '{\"economy\": {\"count\": 12, \"seat_ids\": [\"E1\", \"E2\", \"E3\", \"E4\", \"E5\", \"E6\", \"E7\", \"E8\", \"E9\", \"E10\", \"E11\", \"E12\"]}, \"business\": {\"count\": 12, \"seat_ids\": [\"B1\", \"B2\", \"B3\", \"B4\", \"B5\", \"B6\", \"B7\", \"B8\", \"B9\", \"B10\", \"B11\", \"B12\"]}, \"first_class\": {\"count\": 12, \"seat_ids\": [\"F1\", \"F2\", \"F3\", \"F4\", \"F5\", \"F6\", \"F7\", \"F8\", \"F9\", \"F10\", \"F11\", \"F12\"]}}', '\"direct\"'),
(29, 'PIA', 'PK-309', 'Karachi', 'Medina', '2025-03-12', '12:00:00', 'yes', '2025-03-11 08:24:04', '[\"Economy\",\"Business\",\"First Class\"]', '{\"economy\":200,\"business\":300,\"first_class\":400}', '{\"economy\":{\"count\":10,\"seat_ids\":[\"E1\",\"E2\",\"E3\",\"E4\",\"E5\",\"E6\",\"E7\",\"E8\",\"E9\",\"E10\"]},\"business\":{\"count\":20,\"seat_ids\":[\"B1\",\"B2\",\"B3\",\"B4\",\"B5\",\"B6\",\"B7\",\"B8\",\"B9\",\"B10\",\"B11\",\"B12\",\"B13\",\"B14\",\"B15\",\"B16\",\"B17\",\"B18\",\"B19\",\"B20\"]},\"first_class\":{\"count\":30,\"seat_ids\":[\"F1\",\"F2\",\"F3\",\"F4\",\"F5\",\"F6\",\"F7\",\"F8\",\"F9\",\"F10\",\"F11\",\"F12\",\"F13\",\"F14\",\"F15\",\"F16\",\"F17\",\"F18\",\"F19\",\"F20\",\"F21\",\"F22\",\"F23\",\"F24\",\"F25\",\"F26\",\"F27\",\"F28\",\"F29\",\"F30\"]}}', '\"direct\"');

-- --------------------------------------------------------

--
-- Table structure for table `flight_assign`
--

CREATE TABLE `flight_assign` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL COMMENT 'Reference to package_booking.id',
  `user_id` int(11) NOT NULL,
  `flight_id` int(11) NOT NULL,
  `seat_type` enum('economy','business','first_class') NOT NULL,
  `seat_number` varchar(10) NOT NULL,
  `admin_notes` text DEFAULT NULL,
  `status` enum('assigned','completed','cancelled') NOT NULL DEFAULT 'assigned',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flight_assign`
--

INSERT INTO `flight_assign` (`id`, `booking_id`, `user_id`, `flight_id`, `seat_type`, `seat_number`, `admin_notes`, `status`, `created_at`, `updated_at`) VALUES
(5, 15, 13, 29, 'economy', 'E2', 'yes', 'assigned', '2025-03-11 08:34:40', '2025-03-11 08:34:40');

-- --------------------------------------------------------

--
-- Table structure for table `flight_book`
--

CREATE TABLE `flight_book` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `flight_id` int(11) DEFAULT NULL,
  `booking_time` datetime DEFAULT current_timestamp(),
  `flight_status` enum('upcoming','in-progress','completed') DEFAULT 'upcoming'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flight_bookings`
--

CREATE TABLE `flight_bookings` (
  `id` int(11) NOT NULL,
  `flight_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `passenger_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `passenger_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `passenger_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cabin_class` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `adult_count` int(11) NOT NULL DEFAULT 1,
  `children_count` int(11) NOT NULL DEFAULT 0,
  `seats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seats`)),
  `seat_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flight_bookings`
--

INSERT INTO `flight_bookings` (`id`, `flight_id`, `user_id`, `passenger_name`, `passenger_email`, `passenger_phone`, `cabin_class`, `adult_count`, `children_count`, `seats`, `seat_id`, `booking_date`) VALUES
(10, 25, 10, 'Usama Bashir', 'braveonecody@gmail.com', '03196977218', 'economy', 2, 1, '[\"E3\", \"E4\", \"E1\"]', 'E1', '2025-03-11 11:14:40'),
(11, 27, 10, 'Usama Bashir', 'braveonecody@gmail.com', '03196977218', 'economy', 1, 0, '[\"E1\"]', NULL, '2025-03-05 08:40:15'),
(12, 25, 10, 'Usama Bashir', 'jubranyounas@gmail.com', '03196977218', 'first_class', 1, 1, '[\"E5\", \"E6\"]', NULL, '2025-03-05 12:01:49');

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `hotel_name` varchar(255) NOT NULL,
  `location` enum('makkah','madinah') NOT NULL,
  `room_count` int(10) UNSIGNED NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `amenities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`amenities`)),
  `room_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`room_ids`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotels`
--

INSERT INTO `hotels` (`id`, `hotel_name`, `location`, `room_count`, `price_per_night`, `rating`, `description`, `amenities`, `room_ids`, `created_at`, `updated_at`) VALUES
(10, 'The Lenox', 'madinah', 5, 200.00, 5, 'This is some dummy description.', '[\"wifi\", \"parking\", \"restaurant\", \"gym\"]', '[\"r1\", \"r2\", \"r3\", \"r4\", \"r5\"]', '2025-03-05 01:13:33', '2025-03-05 01:13:33'),
(11, 'Marriott Hotel', 'madinah', 5, 116.00, 5, 'sdaf dsfsad fsda fdsa fsad fsd f', '[\"wifi\", \"parking\"]', '[\"r1\", \"r2\", \"r3\", \"r4\", \"r5\"]', '2025-03-05 01:21:28', '2025-03-05 01:21:28'),
(12, 'The Lenox', 'makkah', 12, 12.00, 4, 'asd sdf sadf saadf asdf', '[\"gym\"]', '[\"r1\", \"r2\", \"r3\", \"r4\", \"r5\", \"r6\", \"r7\", \"r8\", \"r9\", \"r10\", \"r11\", \"r12\"]', '2025-03-05 02:27:08', '2025-03-05 02:27:08');

-- --------------------------------------------------------

--
-- Table structure for table `hotel_bookings`
--

CREATE TABLE `hotel_bookings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `hotel_id` bigint(20) UNSIGNED NOT NULL,
  `room_id` varchar(10) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `guest_name` varchar(255) NOT NULL,
  `guest_email` varchar(255) NOT NULL,
  `guest_phone` varchar(20) NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotel_bookings`
--

INSERT INTO `hotel_bookings` (`id`, `hotel_id`, `room_id`, `user_id`, `guest_name`, `guest_email`, `guest_phone`, `check_in_date`, `check_out_date`, `status`, `created_at`, `updated_at`) VALUES
(2, 10, 'r1', 10, 'test', 'jubranyounas@gmail.com', '03196977218', '2025-03-05', '2025-03-06', 'confirmed', '2025-03-05 01:48:01', '2025-03-05 01:48:01'),
(3, 12, 'r1', 10, 'Usama Bashir', 'braveonecody@gmail.com', '03196977218', '2025-03-31', '2025-03-06', 'confirmed', '2025-03-05 02:57:01', '2025-03-05 02:57:01');

-- --------------------------------------------------------

--
-- Table structure for table `hotel_images`
--

CREATE TABLE `hotel_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `hotel_id` bigint(20) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotel_images`
--

INSERT INTO `hotel_images` (`id`, `hotel_id`, `image_path`, `created_at`) VALUES
(31, 8, 'uploads/hotels/67be8784a1b7a.jpg', '2025-02-25 22:16:20'),
(32, 8, 'uploads/hotels/67be8784a28c9.jpg', '2025-02-25 22:16:20'),
(33, 8, 'uploads/hotels/67be8784a30aa.jpg', '2025-02-25 22:16:20'),
(34, 8, 'uploads/hotels/67be8784a35e1.jpg', '2025-02-25 22:16:20'),
(35, 8, 'uploads/hotels/67be8784a3a94.jpg', '2025-02-25 22:16:20'),
(36, 9, 'uploads/hotels/67c00f6fb2413.jpg', '2025-02-27 02:08:31'),
(37, 9, 'uploads/hotels/67c00f6fb689b.jpg', '2025-02-27 02:08:31'),
(38, 9, 'uploads/hotels/67c00f6fb7710.jpg', '2025-02-27 02:08:31'),
(39, 10, 'uploads/hotels/67c7eb8d92083.jpg', '2025-03-05 01:13:33'),
(40, 10, 'uploads/hotels/67c7eb8d95a96.jpg', '2025-03-05 01:13:33'),
(41, 10, 'uploads/hotels/67c7eb8d977ec.jpg', '2025-03-05 01:13:33'),
(42, 10, 'uploads/hotels/67c7eb8d97e5b.jpg', '2025-03-05 01:13:33'),
(43, 11, 'uploads/hotels/67c7ed6816ddf.jpg', '2025-03-05 01:21:28'),
(44, 11, 'uploads/hotels/67c7ed68173cb.jpg', '2025-03-05 01:21:28'),
(45, 11, 'uploads/hotels/67c7ed681b654.jpg', '2025-03-05 01:21:28'),
(46, 11, 'uploads/hotels/67c7ed681c361.jpg', '2025-03-05 01:21:28'),
(47, 11, 'uploads/hotels/67c7ed681d766.jpg', '2025-03-05 01:21:28'),
(48, 11, 'uploads/hotels/67c7ed681e179.jpg', '2025-03-05 01:21:28'),
(49, 12, 'uploads/hotels/67c7fccc938d9.jpg', '2025-03-05 02:27:08'),
(50, 12, 'uploads/hotels/67c7fccc96172.jpg', '2025-03-05 02:27:08'),
(51, 12, 'uploads/hotels/67c7fccc98129.jpg', '2025-03-05 02:27:08'),
(52, 12, 'uploads/hotels/67c7fccc98b91.jpg', '2025-03-05 02:27:08');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `package_type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `airline` varchar(100) NOT NULL,
  `flight_class` varchar(50) NOT NULL,
  `departure_city` varchar(255) DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `departure_date` date DEFAULT NULL,
  `arrival_city` varchar(255) DEFAULT NULL,
  `inclusions` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `package_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `package_type`, `title`, `description`, `airline`, `flight_class`, `departure_city`, `departure_time`, `departure_date`, `arrival_city`, `inclusions`, `price`, `package_image`) VALUES
(16, 'vip', 'Exercitation cumque ', 'Magni enim nostrud s', 'Aut id rerum numqua', 'business', 'Sequi ea consequatur', '00:19:00', '2020-08-23', 'Harum quisquam harum', 'hotel, transport, guide, vip_services', 263.00, 'uploads/packages/vip.webp');

-- --------------------------------------------------------

--
-- Table structure for table `package_assign`
--

CREATE TABLE `package_assign` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `transport_id` int(11) DEFAULT NULL,
  `flight_id` int(11) DEFAULT NULL,
  `seat_type` enum('economy','business','first_class') DEFAULT NULL,
  `seat_number` varchar(10) DEFAULT NULL,
  `transport_seat_number` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_assign`
--

INSERT INTO `package_assign` (`id`, `booking_id`, `user_id`, `hotel_id`, `transport_id`, `flight_id`, `seat_type`, `seat_number`, `transport_seat_number`, `created_at`, `updated_at`) VALUES
(1, 13, 10, 8, 47, 29, 'economy', 'E1', '1', '2025-02-27 02:35:10', '2025-03-11 08:26:31'),
(2, 15, 13, 8, 8, 29, 'economy', 'E2', 'Reprehende', '2025-02-27 02:54:57', '2025-03-11 08:34:41'),
(3, 14, 12, 8, 9, 29, 'economy', 'E1', 'Mollitia s', '2025-02-27 03:43:09', '2025-03-11 08:26:16');

-- --------------------------------------------------------

--
-- Table structure for table `package_booking`
--

CREATE TABLE `package_booking` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `booking_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','canceled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_booking`
--

INSERT INTO `package_booking` (`id`, `user_id`, `package_id`, `booking_date`, `status`, `payment_status`, `total_price`) VALUES
(13, 10, 16, '2025-02-26 00:28:20', 'pending', 'pending', 263.00),
(14, 12, 16, '2025-02-27 01:00:05', 'pending', 'paid', 263.00),
(15, 13, 16, '2025-02-27 01:49:54', 'pending', 'paid', 263.00);

-- --------------------------------------------------------

--
-- Table structure for table `rentacar_routes`
--

CREATE TABLE `rentacar_routes` (
  `id` int(11) NOT NULL,
  `service_title` varchar(255) NOT NULL,
  `year` int(4) NOT NULL,
  `route_number` int(11) NOT NULL,
  `route_name` varchar(255) NOT NULL,
  `gmc_16_19_price` decimal(10,2) DEFAULT NULL,
  `gmc_22_23_price` decimal(10,2) DEFAULT NULL,
  `coaster_price` decimal(10,2) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rentacar_routes`
--

INSERT INTO `rentacar_routes` (`id`, `service_title`, `year`, `route_number`, `route_name`, `gmc_16_19_price`, `gmc_22_23_price`, `coaster_price`, `created_at`, `updated_at`) VALUES
(1, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 1, 'Makkah to Jeddah', 299.00, 450.00, 550.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(2, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 2, 'Jeddah Airport to Madinah', 699.00, 850.00, 950.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(3, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 3, 'Madinah to Jeddah', 650.00, 799.00, 950.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(4, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 4, 'Makkah to Madinah', 650.00, 799.00, 950.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(5, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 5, 'Madinah to Makkah', 650.00, 799.00, 950.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(6, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 6, 'Taif Ziyarat', 650.00, 799.00, 950.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(7, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 7, 'Madinah Airport to Hottle', 300.00, 370.00, 399.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(8, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 8, 'Madinah to Madinah Airport', 200.00, 250.00, 350.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(9, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 9, 'Makkah Ziyarat', 330.00, 390.00, 450.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(10, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 10, 'Madinah Ziyarat', 300.00, 350.00, 400.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(11, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 11, 'Madinah Ziyarat + Wadi Jin', 400.00, 450.00, 499.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(12, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 12, 'Madinah Hottle to Train Station', 170.00, 250.00, 300.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(13, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 13, 'Madinah Train Station to Hottle', 170.00, 250.00, 300.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(14, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 14, 'Jeddah to Makkah Airport Arrival', 350.00, 399.00, 650.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32'),
(15, 'Best Umrah and Hajj Rent A Car in Makkah, Madinah and Jeddah', 2024, 15, 'Per Hour Rate', 100.00, 150.00, 200.00, '2025-03-10 12:06:32', '2025-03-10 07:06:32');

-- --------------------------------------------------------

--
-- Table structure for table `taxi_routes`
--

CREATE TABLE `taxi_routes` (
  `id` int(11) NOT NULL,
  `service_title` varchar(255) NOT NULL,
  `year` int(4) NOT NULL,
  `route_number` int(11) NOT NULL,
  `route_name` varchar(255) NOT NULL,
  `camry_sonata_price` decimal(10,2) DEFAULT NULL,
  `starex_staria_price` decimal(10,2) DEFAULT NULL,
  `hiace_price` decimal(10,2) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `taxi_routes`
--

INSERT INTO `taxi_routes` (`id`, `service_title`, `year`, `route_number`, `route_name`, `camry_sonata_price`, `starex_staria_price`, `hiace_price`, `created_at`, `updated_at`) VALUES
(1, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 1, 'Makkah to Jeddah', 180.00, 275.00, 300.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(2, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 2, 'Jeddah Airport to Madinah', 450.00, 550.00, 600.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(3, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 3, 'Madinah to Jeddah', 399.00, 499.00, 550.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(4, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 4, 'Makkah to Madinah', 399.00, 499.00, 550.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(5, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 5, 'Madinah to Makkah', 399.00, 499.00, 550.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(6, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 6, 'Taif Ziyarat', 399.00, 499.00, 550.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(7, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 7, 'Madinah Airport to Hottle', 150.00, 200.00, 299.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(8, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 8, 'Madinah to Madinah Airport', 99.00, 130.00, 250.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(9, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 9, 'Makkah Ziyarat', 199.00, 250.00, 299.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(10, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 10, 'Madinah Ziyarat', 199.00, 230.00, 299.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(11, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 11, 'Madinah Ziyarat + Wadi Jin', 275.00, 330.00, 400.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(12, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 12, 'Madinah Hottle to Train Station', 99.00, 130.00, 170.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(13, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 13, 'Madinah Train Station to Hottle', 99.00, 130.00, 170.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(14, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 14, 'Jeddah to Makkah Airport Arrival', 220.00, 300.00, 350.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10'),
(15, 'Best Taxi Service for Umrah and Hajj in Makkah, Madinah and Jeddah', 2024, 15, 'Per Hour Rate', 50.00, 75.00, 100.00, '2025-03-10 12:05:10', '2025-03-10 07:05:10');

-- --------------------------------------------------------

--
-- Table structure for table `transportation`
--

CREATE TABLE `transportation` (
  `id` int(11) NOT NULL,
  `category` enum('Luxury','VIP','Shared') NOT NULL,
  `transport_name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `details` text DEFAULT NULL,
  `seats` int(11) NOT NULL,
  `time_from` time NOT NULL,
  `time_to` time NOT NULL,
  `transport_image` varchar(255) DEFAULT NULL,
  `status` enum('available','booked') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `booking_limit` int(11) DEFAULT 0,
  `transport_id` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transportation_assign`
--

CREATE TABLE `transportation_assign` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `booking_reference` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_type` enum('taxi','rentacar') NOT NULL,
  `route_id` int(11) NOT NULL,
  `vehicle_id` varchar(20) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_contact` varchar(20) DEFAULT NULL,
  `pickup_time` datetime DEFAULT NULL,
  `status` enum('pending','assigned','completed','cancelled') NOT NULL DEFAULT 'pending',
  `booking_type` enum('transportation','package') DEFAULT 'transportation',
  `package_booking_id` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transportation_assign`
--

INSERT INTO `transportation_assign` (`id`, `booking_id`, `booking_reference`, `user_id`, `service_type`, `route_id`, `vehicle_id`, `driver_name`, `driver_contact`, `pickup_time`, `status`, `booking_type`, `package_booking_id`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 4, 'PKGE276D68F', 13, 'taxi', 0, '12', 'new', '03196977218', '2025-03-11 11:54:00', 'completed', 'package', 15, 'sfd d fdsf', '2025-03-11 06:00:39', '2025-03-11 06:01:22'),
(2, 5, 'PKGCB149EE5', 13, 'taxi', 0, '12', 'new', '03196977218', '2025-03-11 11:54:00', 'completed', 'package', 15, 'sfd d fdsf', '2025-03-11 06:01:24', '2025-03-11 06:01:51'),
(3, 6, 'PKGEED23807', 13, 'taxi', 0, '0', 'Lewis Kerr', 'Molestiae tempore s', '1982-09-25 15:05:00', 'completed', 'package', 15, 'Eu similique reicien', '2025-03-11 06:01:35', '2025-03-11 06:01:42'),
(4, 7, 'PKGDD20A1B1', 13, 'taxi', 0, '0', 'Lewis Kerr', 'Molestiae tempore s', '1982-09-25 15:05:00', 'completed', 'package', 15, 'Eu similique reicien', '2025-03-11 06:01:44', '2025-03-11 06:02:17'),
(5, 8, 'PKG2A6F1102', 13, 'taxi', 0, '0', 'Lewis Kerr', 'Molestiae tempore s', '1982-09-25 15:05:00', 'completed', 'package', 15, 'Eu similique reicien', '2025-03-11 06:01:53', '2025-03-11 06:02:24'),
(6, 9, 'PKG74C9ADA3', 12, 'taxi', 0, '0', 'Guy Giles', 'Magni et iure odio l', '2010-10-30 21:03:00', 'completed', 'package', 14, 'Laboris fugit conse', '2025-03-11 06:04:39', '2025-03-11 08:35:11');

-- --------------------------------------------------------

--
-- Table structure for table `transportation_bookings`
--

CREATE TABLE `transportation_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_reference` varchar(20) NOT NULL,
  `service_type` enum('taxi','rentacar') NOT NULL,
  `route_id` int(11) NOT NULL,
  `route_name` varchar(255) NOT NULL,
  `vehicle_type` varchar(50) NOT NULL,
  `vehicle_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `pickup_location` text NOT NULL,
  `dropoff_location` text NOT NULL,
  `passengers` int(11) NOT NULL,
  `special_requests` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `duration` enum('one_way','round_trip','full_day') DEFAULT 'one_way',
  `booking_status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transportation_bookings`
--

INSERT INTO `transportation_bookings` (`id`, `user_id`, `booking_reference`, `service_type`, `route_id`, `route_name`, `vehicle_type`, `vehicle_name`, `price`, `booking_date`, `booking_time`, `pickup_location`, `dropoff_location`, `passengers`, `special_requests`, `admin_notes`, `duration`, `booking_status`, `payment_status`, `created_at`, `updated_at`) VALUES
(1, 10, 'TR67CE924294', 'taxi', 1, '0', 'camry', 'Camry/Sonata', 180.00, '2025-03-10', '14:18:00', 'test', 'test', 1, 'ni', '', 'one_way', 'confirmed', 'unpaid', '2025-03-10 07:19:22', '2025-03-10 07:32:30'),
(2, 13, 'PKG701DFF53', 'taxi', 0, 'Makkah To Madennah', 'camry', 'Camry', 0.00, '2025-03-11', '11:54:00', 'Consectetur ab id es', 'Ipsa accusantium mo', 1, NULL, NULL, 'one_way', 'confirmed', 'paid', '2025-03-11 10:59:08', '2025-03-11 05:59:08'),
(3, 13, 'PKG26F2ECB9', 'taxi', 0, 'Makkah To Madennah', 'camry', 'Camry', 0.00, '2025-03-11', '11:54:00', 'Consectetur ab id es', 'Ipsa accusantium mo', 1, NULL, NULL, 'one_way', 'confirmed', 'paid', '2025-03-11 10:59:48', '2025-03-11 05:59:48'),
(4, 13, 'PKGE276D68F', 'taxi', 0, 'Makkah To Madennah', 'camry', 'Camry', 0.00, '2025-03-11', '11:54:00', 'Consectetur ab id es', 'Ipsa accusantium mo', 1, NULL, NULL, 'one_way', 'completed', 'paid', '2025-03-11 11:00:39', '2025-03-11 06:01:22'),
(5, 13, 'PKGCB149EE5', 'taxi', 0, 'Makkah To Madennah', 'camry', 'Camry', 0.00, '2025-03-11', '11:54:00', 'Consectetur ab id es', 'Ipsa accusantium mo', 1, NULL, NULL, 'one_way', 'completed', 'paid', '2025-03-11 11:01:24', '2025-03-11 06:01:51'),
(6, 13, 'PKGEED23807', 'taxi', 0, 'Tamara Farrell', 'starex', 'Starex', 0.00, '1982-09-25', '15:05:00', 'Sunt sit ut volupta', 'Occaecat enim suscip', 1, NULL, NULL, 'one_way', 'completed', 'paid', '2025-03-11 11:01:35', '2025-03-11 06:01:42'),
(7, 13, 'PKGDD20A1B1', 'taxi', 0, 'Tamara Farrell', 'starex', 'Starex', 0.00, '1982-09-25', '15:05:00', 'Sunt sit ut volupta', 'Occaecat enim suscip', 1, NULL, NULL, 'one_way', 'completed', 'paid', '2025-03-11 11:01:44', '2025-03-11 06:02:17'),
(8, 13, 'PKG2A6F1102', 'taxi', 0, 'Tamara Farrell', 'starex', 'Starex', 0.00, '1982-09-25', '15:05:00', 'Sunt sit ut volupta', 'Occaecat enim suscip', 1, NULL, NULL, 'one_way', 'completed', 'paid', '2025-03-11 11:01:53', '2025-03-11 06:02:24'),
(9, 12, 'PKG74C9ADA3', 'taxi', 0, 'Pascale Dotson', 'gmc_22_23', 'Gmc_22_23', 0.00, '2010-10-30', '21:03:00', 'Voluptate quo doloru', 'Veritatis esse volu', 1, NULL, NULL, 'one_way', 'completed', 'paid', '2025-03-11 11:04:38', '2025-03-11 08:35:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `date_of_birth` date NOT NULL,
  `profile_image` varchar(255) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone_number`, `date_of_birth`, `profile_image`, `gender`, `address`, `created_at`, `password`) VALUES
(10, 'test user', 'test@test.com', '03196977218', '2025-02-20', '../user/uploads/67b6f3ebd193e.jpg', 'Male', 'Pakistan Punjab, Sahiwal', '2025-02-20 03:58:50', '$2y$10$gEv0GrajHE5.1CKIwBvSpeRPYJDxabTjqAe7Qnp9ebgDHaY91NcGe'),
(11, 'ali', 'ali@gmail.com', '123456789', '1990-02-20', '../user/uploads/67b6f8a0ec075.jpg', 'Male', 'House 239', '2025-02-20 04:26:37', '$2y$10$Fk0Ni694.kj0x7WrFGPf/OIdWYrPNdkVbiJVynjbA0ZaBqeYL5EXC'),
(12, 'test2', 'test2@test.com', '03196977218', '2025-02-27', 'user/uploads/ilya-pavlov-OqtafYT5kTw-unsplash.jpg', 'Male', 'sdf sdfsadf', '2025-02-27 00:59:08', '$2y$10$o2C2yTNmAOi/1BOM3IOca.cL0sYb06gL4gTq2nOqXzaFyzPom1wIW'),
(13, 'test3', 'test3@test.com', '03196977218', '2025-02-27', 'user/uploads/ilya-pavlov-OqtafYT5kTw-unsplash.jpg', 'Male', 'test3@test.comtest3@test.comtest3@test.com', '2025-02-27 01:49:33', '$2y$10$H1Qf8j/u.6TS/leCVhk3yehJ5L1wgtjQHV6l.uChjLgwu7v7DP/uO');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `flights`
--
ALTER TABLE `flights`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `flight_assign`
--
ALTER TABLE `flight_assign`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `flight_id` (`flight_id`);

--
-- Indexes for table `flight_book`
--
ALTER TABLE `flight_book`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `flight_id` (`flight_id`);

--
-- Indexes for table `flight_bookings`
--
ALTER TABLE `flight_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flight_id` (`flight_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hotel_id` (`hotel_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `check_dates` (`check_in_date`,`check_out_date`);

--
-- Indexes for table `hotel_images`
--
ALTER TABLE `hotel_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_assign`
--
ALTER TABLE `package_assign`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `hotel_id` (`hotel_id`),
  ADD KEY `transport_id` (`transport_id`),
  ADD KEY `flight_id` (`flight_id`);

--
-- Indexes for table `package_booking`
--
ALTER TABLE `package_booking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `rentacar_routes`
--
ALTER TABLE `rentacar_routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service_year` (`service_title`,`year`);

--
-- Indexes for table `taxi_routes`
--
ALTER TABLE `taxi_routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service_year` (`service_title`,`year`);

--
-- Indexes for table `transportation`
--
ALTER TABLE `transportation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transportation_assign`
--
ALTER TABLE `transportation_assign`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_reference` (`booking_reference`),
  ADD KEY `service_type` (`service_type`);

--
-- Indexes for table `transportation_bookings`
--
ALTER TABLE `transportation_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_reference` (`booking_reference`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `booking_status` (`booking_status`),
  ADD KEY `service_type` (`service_type`),
  ADD KEY `booking_date` (`booking_date`);

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
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `flights`
--
ALTER TABLE `flights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `flight_assign`
--
ALTER TABLE `flight_assign`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `flight_book`
--
ALTER TABLE `flight_book`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `flight_bookings`
--
ALTER TABLE `flight_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hotel_images`
--
ALTER TABLE `hotel_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `package_assign`
--
ALTER TABLE `package_assign`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `package_booking`
--
ALTER TABLE `package_booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `rentacar_routes`
--
ALTER TABLE `rentacar_routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `taxi_routes`
--
ALTER TABLE `taxi_routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `transportation`
--
ALTER TABLE `transportation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `transportation_assign`
--
ALTER TABLE `transportation_assign`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `transportation_bookings`
--
ALTER TABLE `transportation_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `flight_bookings`
--
ALTER TABLE `flight_bookings`
  ADD CONSTRAINT `flight_bookings_ibfk_1` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`id`),
  ADD CONSTRAINT `flight_bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
