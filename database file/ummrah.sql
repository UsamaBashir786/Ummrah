-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 24, 2025 at 02:00 AM
-- Server version: 9.1.0
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
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `email`, `password`) VALUES
(6, 'admin@admin.com', '$2y$10$0Hbz84KSB4PdO6rOVNlTNOsGa6Ao0KH0TGJWhYpCZbZjfFXsEa2Ta');

-- --------------------------------------------------------

--
-- Table structure for table `flights`
--

DROP TABLE IF EXISTS `flights`;
CREATE TABLE IF NOT EXISTS `flights` (
  `id` int NOT NULL AUTO_INCREMENT,
  `airline_name` varchar(100) NOT NULL,
  `flight_number` varchar(50) NOT NULL,
  `departure_city` varchar(100) NOT NULL,
  `arrival_city` varchar(100) NOT NULL,
  `departure_date` date NOT NULL,
  `departure_time` time NOT NULL,
  `economy_price` decimal(10,2) NOT NULL,
  `business_price` decimal(10,2) NOT NULL,
  `first_class_price` decimal(10,2) NOT NULL,
  `economy_seats` int NOT NULL,
  `business_seats` int NOT NULL,
  `first_class_seats` int NOT NULL,
  `flight_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `flights`
--

INSERT INTO `flights` (`id`, `airline_name`, `flight_number`, `departure_city`, `arrival_city`, `departure_date`, `departure_time`, `economy_price`, `business_price`, `first_class_price`, `economy_seats`, `business_seats`, `first_class_seats`, `flight_notes`, `created_at`) VALUES
(7, 'Saudi', '455', 'Islamabad', 'Jeddah', '2024-10-17', '10:31:00', 678.00, 358.00, 859.00, 96, 13, 46, 'Officiis ad modi exe', '2025-02-21 06:53:40');

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

DROP TABLE IF EXISTS `hotels`;
CREATE TABLE IF NOT EXISTS `hotels` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `hotel_name` varchar(255) NOT NULL,
  `location` enum('makkah','madinah') NOT NULL,
  `room_count` int UNSIGNED NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `rating` tinyint UNSIGNED NOT NULL,
  `description` text,
  `amenities` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ;

--
-- Dumping data for table `hotels`
--

INSERT INTO `hotels` (`id`, `hotel_name`, `location`, `room_count`, `price_per_night`, `rating`, `description`, `amenities`, `created_at`, `updated_at`) VALUES
(6, 'Example', 'madinah', 2, 653.00, 5, 'This is the for example description just for testing purpose', '[\"wifi\"]', '2025-02-20 10:42:40', '2025-02-20 10:42:40'),
(7, 'Wilma Pate', 'makkah', 19, 552.00, 4, 'Aut non autem blandi', '[\"wifi\", \"restaurant\", \"gym\"]', '2025-02-20 10:53:00', '2025-02-20 10:53:00');

-- --------------------------------------------------------

--
-- Table structure for table `hotel_images`
--

DROP TABLE IF EXISTS `hotel_images`;
CREATE TABLE IF NOT EXISTS `hotel_images` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `hotel_id` bigint UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `hotel_id` (`hotel_id`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `hotel_images`
--

INSERT INTO `hotel_images` (`id`, `hotel_id`, `image_path`, `created_at`) VALUES
(29, 6, 'uploads/hotels/67b70720947cf.jpg', '2025-02-20 10:42:40'),
(28, 6, 'uploads/hotels/67b7072094371.jpg', '2025-02-20 10:42:40'),
(27, 6, 'uploads/hotels/67b7072093e83.jpg', '2025-02-20 10:42:40'),
(26, 6, 'uploads/hotels/67b7072093812.jpg', '2025-02-20 10:42:40'),
(30, 7, 'uploads/hotels/67b7098cb74a2.jpg', '2025-02-20 10:53:00');

-- --------------------------------------------------------

--
-- Table structure for table `transportation`
--

DROP TABLE IF EXISTS `transportation`;
CREATE TABLE IF NOT EXISTS `transportation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category` enum('luxury','standard','economy') NOT NULL,
  `transport_name` varchar(255) NOT NULL,
  `transport_id` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `details` text,
  `seats` int NOT NULL,
  `time_from` time NOT NULL,
  `time_to` time NOT NULL,
  `transport_image` varchar(255) DEFAULT NULL,
  `status` enum('available','booked') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transport_id` (`transport_id`)
) ;

--
-- Dumping data for table `transportation`
--

INSERT INTO `transportation` (`id`, `category`, `transport_name`, `transport_id`, `location`, `latitude`, `longitude`, `details`, `seats`, `time_from`, `time_to`, `transport_image`, `status`, `created_at`) VALUES
(4, 'luxury', 'Athena Kent', 'Labore consectetur d', 'sss', 0.00000000, 0.00000000, 'Omnis quidem sint de', 80, '17:11:00', '21:48:00', 'uploads/vehicles/1740123956_car.jpg', 'available', '2025-02-21 07:45:56'),
(5, 'luxury', 'Noelle Waters', 'Iure dolor vitae asp', 'Est id aspernatur qu', 0.00000000, 0.00000000, 'Harum dolor voluptat', 77, '05:33:00', '01:38:00', 'uploads/vehicles/1740123979_car.jpg', 'available', '2025-02-21 07:46:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `date_of_birth` date NOT NULL,
  `profile_image` varchar(255) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone_number`, `date_of_birth`, `profile_image`, `gender`, `address`, `created_at`, `password`) VALUES
(10, 'test user', 'test@test.com', '03196977218', '2025-02-20', '../user/uploads/67b6f3ebd193e.jpg', 'Male', 'Pakistan Punjab, Sahiwal', '2025-02-20 08:58:50', '$2y$10$gEv0GrajHE5.1CKIwBvSpeRPYJDxabTjqAe7Qnp9ebgDHaY91NcGe'),
(11, 'ali', 'ali@gmail.com', '123456789', '1990-02-20', '../user/uploads/67b6f8a0ec075.jpg', 'Male', 'House 239', '2025-02-20 09:26:37', '$2y$10$Fk0Ni694.kj0x7WrFGPf/OIdWYrPNdkVbiJVynjbA0ZaBqeYL5EXC');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
