-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 07, 2025 at 04:12 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.3.14

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

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

DROP TABLE IF EXISTS `contacts`;
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flights`
--

DROP TABLE IF EXISTS `flights`;
CREATE TABLE IF NOT EXISTS `flights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `airline_name` varchar(100) NOT NULL,
  `flight_number` varchar(50) NOT NULL,
  `departure_city` varchar(100) NOT NULL,
  `arrival_city` varchar(100) NOT NULL,
  `departure_date` date NOT NULL,
  `departure_time` time NOT NULL,
  `flight_duration` varchar(10) DEFAULT NULL,
  `distance` varchar(50) DEFAULT NULL,
  `flight_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `cabin_class` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`cabin_class`)),
  `prices` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`prices`)),
  `seats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`seats`)),
  `stops` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stops`)),
  `return_flight_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`return_flight_data`)),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flights`
--

INSERT INTO `flights` (`id`, `airline_name`, `flight_number`, `departure_city`, `arrival_city`, `departure_date`, `departure_time`, `flight_duration`, `distance`, `flight_notes`, `created_at`, `cabin_class`, `prices`, `seats`, `stops`, `return_flight_data`) VALUES
(38, 'PIA', 'PK-309', 'Karachi', 'Jeddah', '2025-04-08', '15:41:00', '5', '3500', '', '2025-04-07 10:42:45', '[\"Economy\",\"Business\",\"First Class\"]', '{\"economy\":50,\"business\":100,\"first_class\":150}', '{\"economy\":{\"count\":15,\"seat_ids\":[\"E1\",\"E2\",\"E3\",\"E4\",\"E5\",\"E6\",\"E7\",\"E8\",\"E9\",\"E10\",\"E11\",\"E12\",\"E13\",\"E14\",\"E15\"]},\"business\":{\"count\":10,\"seat_ids\":[\"B1\",\"B2\",\"B3\",\"B4\",\"B5\",\"B6\",\"B7\",\"B8\",\"B9\",\"B10\"]},\"first_class\":{\"count\":5,\"seat_ids\":[\"F1\",\"F2\",\"F3\",\"F4\",\"F5\"]}}', '[{\"city\":\"Dubai\",\"duration\":\"2\"}]', '{\"has_return\":1,\"return_date\":\"2025-04-10\",\"return_time\":\"15:42\",\"return_flight_number\":\"PK-310\",\"return_flight_duration\":\"5\",\"return_airline\":\"PIA\",\"has_return_stops\":0,\"return_stops\":\"\\\"direct\\\"\"}'),
(39, 'Emirates', 'PK-333', 'Lahore', 'Jeddah', '2025-04-08', '17:43:00', '5', '222', '', '2025-04-07 10:44:03', '[\"Economy\",\"Business\",\"First Class\"]', '{\"economy\":50,\"business\":100,\"first_class\":150}', '{\"economy\":{\"count\":15,\"seat_ids\":[\"E1\",\"E2\",\"E3\",\"E4\",\"E5\",\"E6\",\"E7\",\"E8\",\"E9\",\"E10\",\"E11\",\"E12\",\"E13\",\"E14\",\"E15\"]},\"business\":{\"count\":10,\"seat_ids\":[\"B1\",\"B2\",\"B3\",\"B4\",\"B5\",\"B6\",\"B7\",\"B8\",\"B9\",\"B10\"]},\"first_class\":{\"count\":5,\"seat_ids\":[\"F1\",\"F2\",\"F3\",\"F4\",\"F5\"]}}', '[{\"city\":\"Dubai\",\"duration\":\"2\"}]', '{\"has_return\":1,\"return_date\":\"2025-04-09\",\"return_time\":\"15:44\",\"return_flight_number\":\"PK-344\",\"return_flight_duration\":\"5\",\"return_airline\":\"Qatar\",\"has_return_stops\":0,\"return_stops\":\"\\\"direct\\\"\"}'),
(40, 'Flynas', 'pk-404', 'Islamabad', 'Medina', '2025-04-11', '16:44:00', '5', '2222', '', '2025-04-07 10:44:48', '[\"Economy\",\"Business\",\"First Class\"]', '{\"economy\":12,\"business\":12,\"first_class\":12}', '{\"economy\":{\"count\":12,\"seat_ids\":[\"E1\",\"E2\",\"E3\",\"E4\",\"E5\",\"E6\",\"E7\",\"E8\",\"E9\",\"E10\",\"E11\",\"E12\"]},\"business\":{\"count\":12,\"seat_ids\":[\"B1\",\"B2\",\"B3\",\"B4\",\"B5\",\"B6\",\"B7\",\"B8\",\"B9\",\"B10\",\"B11\",\"B12\"]},\"first_class\":{\"count\":12,\"seat_ids\":[\"F1\",\"F2\",\"F3\",\"F4\",\"F5\",\"F6\",\"F7\",\"F8\",\"F9\",\"F10\",\"F11\",\"F12\"]}}', '\"direct\"', '{\"has_return\":1,\"return_date\":\"2025-04-12\",\"return_time\":\"17:44\",\"return_flight_number\":\"PK-310\",\"return_flight_duration\":\"5\",\"return_airline\":\"Emirates\",\"has_return_stops\":0,\"return_stops\":\"\\\"direct\\\"\"}'),
(41, 'Emirates', 'PK-309', 'Lahore', 'Jeddah', '2025-04-13', '15:47:00', '12', '12', '', '2025-04-07 10:45:10', '[\"Economy\",\"Business\",\"First Class\"]', '{\"economy\":12,\"business\":12,\"first_class\":12}', '{\"economy\":{\"count\":12,\"seat_ids\":[\"E1\",\"E2\",\"E3\",\"E4\",\"E5\",\"E6\",\"E7\",\"E8\",\"E9\",\"E10\",\"E11\",\"E12\"]},\"business\":{\"count\":12,\"seat_ids\":[\"B1\",\"B2\",\"B3\",\"B4\",\"B5\",\"B6\",\"B7\",\"B8\",\"B9\",\"B10\",\"B11\",\"B12\"]},\"first_class\":{\"count\":12,\"seat_ids\":[\"F1\",\"F2\",\"F3\",\"F4\",\"F5\",\"F6\",\"F7\",\"F8\",\"F9\",\"F10\",\"F11\",\"F12\"]}}', '\"direct\"', '{\"has_return\":0,\"return_date\":\"\",\"return_time\":\"\",\"return_flight_number\":\"\",\"return_flight_duration\":\"\",\"return_airline\":\"Emirates\",\"has_return_stops\":0,\"return_stops\":\"\\\"direct\\\"\"}');

-- --------------------------------------------------------

--
-- Table structure for table `flight_assign`
--

DROP TABLE IF EXISTS `flight_assign`;
CREATE TABLE IF NOT EXISTS `flight_assign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL COMMENT 'Reference to package_booking.id',
  `user_id` int(11) NOT NULL,
  `flight_id` int(11) NOT NULL,
  `seat_type` enum('economy','business','first_class') NOT NULL,
  `seat_number` varchar(10) NOT NULL,
  `admin_notes` text DEFAULT NULL,
  `status` enum('assigned','completed','cancelled') NOT NULL DEFAULT 'assigned',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `user_id` (`user_id`),
  KEY `flight_id` (`flight_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flight_assign`
--

INSERT INTO `flight_assign` (`id`, `booking_id`, `user_id`, `flight_id`, `seat_type`, `seat_number`, `admin_notes`, `status`, `created_at`, `updated_at`) VALUES
(5, 15, 13, 29, 'economy', 'E2', 'yes', 'cancelled', '2025-03-11 08:34:40', '2025-03-21 07:08:48'),
(6, 14, 12, 31, 'economy', 'E1', 'ADSFBG', 'assigned', '2025-03-21 07:10:10', '2025-03-21 07:10:10');

-- --------------------------------------------------------

--
-- Table structure for table `flight_book`
--

DROP TABLE IF EXISTS `flight_book`;
CREATE TABLE IF NOT EXISTS `flight_book` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `flight_id` int(11) DEFAULT NULL,
  `booking_time` datetime DEFAULT current_timestamp(),
  `flight_status` enum('upcoming','in-progress','completed') DEFAULT 'upcoming',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `flight_id` (`flight_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `flight_bookings`
--

DROP TABLE IF EXISTS `flight_bookings`;
CREATE TABLE IF NOT EXISTS `flight_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `flight_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `passenger_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `passenger_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `passenger_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cabin_class` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `adult_count` int(11) NOT NULL DEFAULT 1,
  `children_count` int(11) NOT NULL DEFAULT 0,
  `seats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seats`)),
  `return_flight_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`return_flight_data`)),
  `seat_id` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_date` datetime NOT NULL,
  `booking_status` varchar(50) NOT NULL DEFAULT 'pending',
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `flight_id` (`flight_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flight_bookings`
--

INSERT INTO `flight_bookings` (`id`, `flight_id`, `user_id`, `passenger_name`, `passenger_email`, `passenger_phone`, `cabin_class`, `adult_count`, `children_count`, `seats`, `return_flight_data`, `seat_id`, `booking_date`, `booking_status`, `price`) VALUES
(22, 38, 19, 'Usama', 'test@test.com', '03196977218', 'economy', 1, 0, '[\"E1\"]', '{\"has_return\":1,\"return_date\":\"2025-04-10\",\"return_time\":\"15:42\",\"return_flight_number\":\"PK-310\",\"return_flight_duration\":\"5\",\"return_airline\":\"PIA\",\"has_return_stops\":0,\"return_stops\":\"\\\"direct\\\"\"}', NULL, '2025-04-07 15:46:28', 'pending', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

DROP TABLE IF EXISTS `hotels`;
CREATE TABLE IF NOT EXISTS `hotels` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hotel_name` varchar(255) NOT NULL,
  `location` enum('makkah','madinah') NOT NULL,
  `room_count` int(10) UNSIGNED NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `amenities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`amenities`)),
  `room_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`room_ids`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotel_bookings`
--

DROP TABLE IF EXISTS `hotel_bookings`;
CREATE TABLE IF NOT EXISTS `hotel_bookings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `hotel_id` (`hotel_id`),
  KEY `room_id` (`room_id`),
  KEY `user_id` (`user_id`),
  KEY `check_dates` (`check_in_date`,`check_out_date`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotel_images`
--

DROP TABLE IF EXISTS `hotel_images`;
CREATE TABLE IF NOT EXISTS `hotel_images` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hotel_id` bigint(20) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `hotel_id` (`hotel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(38, 9, 'uploads/hotels/67c00f6fb7710.jpg', '2025-02-27 02:08:31');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
CREATE TABLE IF NOT EXISTS `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `package_image` varchar(255) DEFAULT NULL,
  `return_time` time DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_assign`
--

DROP TABLE IF EXISTS `package_assign`;
CREATE TABLE IF NOT EXISTS `package_assign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `transport_id` int(11) DEFAULT NULL,
  `flight_id` int(11) DEFAULT NULL,
  `seat_type` enum('economy','business','first_class') DEFAULT NULL,
  `seat_number` varchar(10) DEFAULT NULL,
  `transport_seat_number` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `user_id` (`user_id`),
  KEY `hotel_id` (`hotel_id`),
  KEY `transport_id` (`transport_id`),
  KEY `flight_id` (`flight_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_assign`
--

INSERT INTO `package_assign` (`id`, `booking_id`, `user_id`, `hotel_id`, `transport_id`, `flight_id`, `seat_type`, `seat_number`, `transport_seat_number`, `created_at`, `updated_at`) VALUES
(1, 13, 10, 8, 47, 29, 'economy', 'E1', '1', '2025-02-27 02:35:10', '2025-03-11 08:26:31'),
(2, 15, 13, 8, 8, 29, 'economy', 'E2', 'Reprehende', '2025-02-27 02:54:57', '2025-03-11 08:34:41'),
(3, 14, 12, 8, 9, 31, 'economy', 'E1', 'Mollitia s', '2025-02-27 03:43:09', '2025-03-21 07:10:11');

-- --------------------------------------------------------

--
-- Table structure for table `package_booking`
--

DROP TABLE IF EXISTS `package_booking`;
CREATE TABLE IF NOT EXISTS `package_booking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `booking_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','canceled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `total_price` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `package_id` (`package_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_booking`
--

INSERT INTO `package_booking` (`id`, `user_id`, `package_id`, `booking_date`, `status`, `payment_status`, `total_price`, `payment_method`) VALUES
(13, 10, 16, '2025-02-26 00:28:20', 'pending', 'pending', 263.00, NULL),
(14, 12, 16, '2025-02-27 01:00:05', 'pending', 'paid', 263.00, NULL),
(15, 13, 16, '2025-02-27 01:49:54', 'pending', 'paid', 263.00, NULL),
(16, 15, 16, '2025-03-21 10:01:34', 'pending', 'pending', 263.00, NULL),
(17, 19, 18, '2025-04-07 09:19:48', 'pending', 'pending', 1200.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `package_flights`
--

DROP TABLE IF EXISTS `package_flights`;
CREATE TABLE IF NOT EXISTS `package_flights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `flight_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  KEY `flight_id` (`flight_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rentacar_routes`
--

DROP TABLE IF EXISTS `rentacar_routes`;
CREATE TABLE IF NOT EXISTS `rentacar_routes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_title` varchar(255) NOT NULL,
  `year` int(4) NOT NULL,
  `route_number` int(11) NOT NULL,
  `route_name` varchar(255) NOT NULL,
  `gmc_16_19_price` decimal(10,2) DEFAULT NULL,
  `gmc_22_23_price` decimal(10,2) DEFAULT NULL,
  `coaster_price` decimal(10,2) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_service_year` (`service_title`,`year`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

DROP TABLE IF EXISTS `taxi_routes`;
CREATE TABLE IF NOT EXISTS `taxi_routes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_title` varchar(255) NOT NULL,
  `year` int(4) NOT NULL,
  `route_number` int(11) NOT NULL,
  `route_name` varchar(255) NOT NULL,
  `camry_sonata_price` decimal(10,2) DEFAULT NULL,
  `starex_staria_price` decimal(10,2) DEFAULT NULL,
  `hiace_price` decimal(10,2) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_service_year` (`service_title`,`year`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

DROP TABLE IF EXISTS `transportation`;
CREATE TABLE IF NOT EXISTS `transportation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `transport_id` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transportation_assign`
--

DROP TABLE IF EXISTS `transportation_assign`;
CREATE TABLE IF NOT EXISTS `transportation_assign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `user_id` (`user_id`),
  KEY `booking_reference` (`booking_reference`),
  KEY `service_type` (`service_type`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

DROP TABLE IF EXISTS `transportation_bookings`;
CREATE TABLE IF NOT EXISTS `transportation_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_reference` (`booking_reference`),
  KEY `user_id` (`user_id`),
  KEY `route_id` (`route_id`),
  KEY `booking_status` (`booking_status`),
  KEY `service_type` (`service_type`),
  KEY `booking_date` (`booking_date`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `date_of_birth` date NOT NULL,
  `profile_image` varchar(255) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone_number`, `date_of_birth`, `profile_image`, `gender`, `address`, `created_at`, `password`) VALUES
(19, 'Usama', 'test@test.com', '03196977218', '2002-12-15', '../user/uploads/67f370924c020.png', 'Male', 'HIGH STREET SAHIWAL', '2025-04-07 06:26:09', '$2y$10$s9MH/JU1by41/U.xOiwcZe.pFyMbw8L2D.Vg8iTKOTZaJMxb6lNqW');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `flight_bookings`
--
ALTER TABLE `flight_bookings`
  ADD CONSTRAINT `flight_bookings_ibfk_1` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`id`),
  ADD CONSTRAINT `flight_bookings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `package_flights`
--
ALTER TABLE `package_flights`
  ADD CONSTRAINT `package_flights_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`),
  ADD CONSTRAINT `package_flights_ibfk_2` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
