-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 06, 2024 at 05:39 AM
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
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `assign_id` int(10) NOT NULL,
  `job_id` int(10) DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('pending','completed','late') DEFAULT 'pending',
  `file_path` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`assign_id`, `job_id`, `supervisor_id`, `user_id`, `status`, `file_path`) VALUES
(1, NULL, 4, 3, 'pending', ''),
(2, NULL, 4, 1, 'late', ''),
(3, NULL, 2, 1, 'pending', ''),
(4, NULL, 2, 3, 'pending', ''),
(5, NULL, 2, 1, 'pending', ''),
(6, NULL, 2, 1, 'pending', ''),
(7, NULL, 2, 1, 'pending', ''),
(8, NULL, 2, 3, 'pending', 'uploads/5DO_5DON_T.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `job_id` int(11) NOT NULL COMMENT 'รหัสงาน',
  `user_id` int(11) NOT NULL COMMENT 'รหัสพนักงาน',
  `supervisor_id` int(11) NOT NULL COMMENT 'รหัสผู้สั่งงาน',
  `job_title` varchar(255) NOT NULL COMMENT 'ชื่อเอกสาร',
  `job_level` enum('low','medium','high') NOT NULL DEFAULT 'low' COMMENT 'ระดับงาน',
  `job_description` text NOT NULL COMMENT 'รายละเอียดงาน',
  `due_datetime` datetime NOT NULL COMMENT 'วันและเวลาที่กำหนด',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้าง',
  `jobs_file` varchar(255) NOT NULL COMMENT 'ไฟล์ที่แนบ',
  `end_date` datetime DEFAULT NULL COMMENT 'วันที่ส่ง'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`job_id`, `user_id`, `supervisor_id`, `job_title`, `job_level`, `job_description`, `due_datetime`, `created_at`, `jobs_file`, `end_date`) VALUES
(1, 1, 2, 'Report Submission', 'medium', 'Submit monthly reports', '2024-11-25 17:00:00', '2024-11-19 08:38:44', 'report.pdf', '2024-11-26 17:00:00');

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
(3, 5555, '6074c6aa3488f3c2dddff2a7ca821aab', '', 'ดลยา', 'บุญครอบ', '2222222222', 'pai.got11@gmail.com', 'a', '', '2024-11-19 08:51:47'),
(4, 9999, 'fa246d0262c3925617b0c72bb20eeb1d', '', 'yyy', 'uuu', '0666666666', 'thfhty@tgfg.com', 'm', '', '2024-11-19 08:53:23');

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assign_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `fk_mable` (`user_id`);

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
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assign_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสงาน', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mable`
--
ALTER TABLE `mable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ลำดับผู้ใช้', AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `fk_mable` FOREIGN KEY (`user_id`) REFERENCES `mable` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
