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
-- Table structure for table `mable`
--

CREATE TABLE `mable` (
  `id` int(11) NOT NULL COMMENT 'ลำดับผู้ใช้',
  `user_id` int(11) NOT NULL COMMENT 'รหัสพนักงาน',
  `password` varchar(255) NOT NULL,
  `nametitle` varchar(10) NOT NULL COMMENT 'คำนำหน้า',
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `userlevel` enum('a','m','u') NOT NULL COMMENT 'ระดับผู้ใช้',
  `img_path` varchar(255) DEFAULT NULL COMMENT 'รูปผู้ใช้',
  `datesave` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่บันทึก'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mable`
--

INSERT INTO `mable` (`id`, `user_id`, `password`, `nametitle`, `firstname`, `lastname`, `phone`, `email`, `userlevel`, `img_path`, `datesave`) VALUES
(1, 1111, 'hashed_password1', 'นาย', 'John', 'Doe', '0812345678', 'john@example.com', 'm', 'profile1.jpg', '2024-11-19 08:38:44'),
(2, 2222, 'hashed_password2', 'นาง', 'Jane', 'Smith', '0812345679', 'jane@example.com', 'a', 'profile2.jpg', '2024-11-19 08:38:44'),
(3, 5555, '6074c6aa3488f3c2dddff2a7ca821aab', '', 'ดลยา', 'บุญครอบ', '2222222222', 'pai.got11@gmail.com', 'm', '', '2024-11-19 08:51:47'),
(4, 9999, 'fa246d0262c3925617b0c72bb20eeb1d', '', 'yyy', 'uuu', '0666666666', 'thfhty@tgfg.com', 'm', '', '2024-11-19 08:53:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mable`
--
ALTER TABLE `mable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mable`
--
ALTER TABLE `mable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ลำดับผู้ใช้', AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
