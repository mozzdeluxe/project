-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2024 at 05:53 AM
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
-- Database: `work`
--

-- --------------------------------------------------------

--
-- Table structure for table `reply`
--

CREATE TABLE `reply` (
  `assign_id` int(50) NOT NULL COMMENT 'รหัสการมอบหมาย',
  `user_id` int(10) DEFAULT NULL COMMENT 'รหัสพนักงาน',
  `due_datetime` datetime(6) DEFAULT current_timestamp(6) COMMENT 'วันที่เวลากำหนด',
  `create_at` timestamp(6) NULL DEFAULT current_timestamp(6) COMMENT 'วันที่สร้าง',
  `complete_at` datetime(6) DEFAULT current_timestamp(6) COMMENT 'วันที่เสร็จสิ้น',
  `file_reply` varchar(50) DEFAULT NULL COMMENT 'ไฟล์งานตอบกลับ',
  `reply_description` varchar(50) DEFAULT NULL COMMENT 'รายละเอียดงาน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
