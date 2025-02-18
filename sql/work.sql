-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 18, 2025 at 09:19 AM
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
  `assign_id` int(10) NOT NULL COMMENT 'รหัสการมอบหมาย',
  `job_id` int(10) DEFAULT NULL COMMENT 'รหัสงาน',
  `user_id` int(11) DEFAULT NULL COMMENT 'รหัสพนักงาน',
  `status` enum('ยังไม่อ่าน','อ่านแล้ว','กำลังดำเนินการ','ส่งแล้ว','ช้า') DEFAULT 'ยังไม่อ่าน' COMMENT 'สถานะ',
  `file_path` varchar(50) NOT NULL COMMENT 'ไฟล์ที่อัป'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`assign_id`, `job_id`, `user_id`, `status`, `file_path`) VALUES
(21, 14, 4, 'อ่านแล้ว', 'job14_user3.pdf'),
(28, 22, 8, 'อ่านแล้ว', 'job22_user3.pdf'),
(29, 22, 4, 'อ่านแล้ว', 'job22_user3.pdf'),
(33, 25, 4, '', 'job25_user3.pdf'),
(34, 25, 7, '', 'job25_user3.pdf'),
(35, 25, 8, '', 'job25_user3.pdf'),
(36, 27, 8, 'อ่านแล้ว', 'job27_user3.pdf'),
(37, 27, 7, 'อ่านแล้ว', 'job27_user3.pdf'),
(38, 27, 4, 'อ่านแล้ว', 'job27_user3.pdf'),
(39, 28, 7, 'ยังไม่อ่าน', 'job28_user3.pdf'),
(40, 29, 7, 'ยังไม่อ่าน', 'job29_user3.pdf'),
(41, 30, 7, 'ยังไม่อ่าน', 'job30_user3.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `supervisor_id` int(11) NOT NULL COMMENT 'รหัสผู้สั่งงาน',
  `job_id` int(11) NOT NULL COMMENT 'รหัสงาน',
  `job_title` varchar(255) NOT NULL COMMENT 'ชื่อเอกสาร',
  `job_level` enum('ปกติ','ด่วน','ด่วนมาก') NOT NULL DEFAULT 'ปกติ' COMMENT 'ระดับงาน',
  `job_description` text NOT NULL COMMENT 'รายละเอียดงาน',
  `due_datetime` datetime NOT NULL COMMENT 'วันและเวลาที่กำหนด',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้าง',
  `jobs_file` varchar(255) NOT NULL COMMENT 'ไฟล์ที่แนบ',
  `end_date` datetime DEFAULT NULL COMMENT 'วันที่ส่ง'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`supervisor_id`, `job_id`, `job_title`, `job_level`, `job_description`, `due_datetime`, `created_at`, `jobs_file`, `end_date`) VALUES
(3, 13, 'ertrg', 'ปกติ', 'กหหหด', '2025-01-05 23:59:00', '2025-01-05 17:56:52', '', NULL),
(3, 14, 'ertrg', 'ปกติ', 'esfd', '2025-01-06 01:04:00', '2025-01-05 18:04:23', 'job14_user3.pdf', NULL),
(3, 15, 'ๅ/-/ๅ-', 'ปกติ', 'กดเดกเ', '2025-01-06 01:18:00', '2025-01-05 18:18:14', 'job15_user3.pdf', NULL),
(3, 16, 'หกกเ', 'ปกติ', 'ดเกดเด', '2025-01-06 01:22:00', '2025-01-05 18:22:39', 'job16_user3.pdf', NULL),
(3, 17, 'ertrg', 'ปกติ', 'ดเิด', '2025-01-06 04:11:00', '2025-01-05 21:11:18', '', NULL),
(3, 18, 'ertrg', 'ปกติ', 'ดเิด', '2025-01-06 04:11:00', '2025-01-05 21:12:01', '', NULL),
(3, 19, 'ๅ/-/ๅ-', 'ปกติ', 'กหหแ', '2025-01-06 04:12:00', '2025-01-05 21:12:23', '', NULL),
(3, 21, 'สวัสดี', 'ปกติ', 'ทดสอบ', '2025-01-06 04:25:00', '2025-01-05 21:25:36', '', NULL),
(3, 22, 'สวัสดี', 'ปกติ', 'ทดสอบ', '2025-01-06 04:27:00', '2025-01-05 21:28:00', 'job22_user3.pdf', NULL),
(3, 25, 'mfg', 'ปกติ', 'asdfggh', '2025-01-28 11:53:00', '2025-01-28 04:54:13', 'job25_user3.pdf', NULL),
(3, 27, 'ertrg', 'ปกติ', 'dfghjkl', '2025-01-28 12:00:00', '2025-01-28 05:00:14', 'job27_user3.pdf', NULL),
(3, 28, '5555', 'ปกติ', 'กกกก', '2025-02-02 16:05:00', '2025-02-02 09:05:12', 'job28_user3.pdf', NULL),
(3, 29, '111111111111', 'ปกติ', '1111111111111111', '2025-02-02 16:09:00', '2025-02-02 09:09:43', 'job29_user3.pdf', NULL),
(3, 30, 'ดอกอ', 'ปกติ', 'กอกดอกด', '2025-02-02 16:12:00', '2025-02-02 09:13:00', 'job30_user3.pdf', NULL);

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
(3, 5555, '6074c6aa3488f3c2dddff2a7ca821aab', '', 'ดลยา', 'บุญครอบ', '2222222222', 'pai.got11@gmail.com', 'a', '', '2024-11-19 08:51:47'),
(4, 9999, 'fa246d0262c3925617b0c72bb20eeb1d', '', 'yyy', 'uuu', '0666666666', 'thfhty@tgfg.com', 'm', '', '2024-11-19 08:53:23'),
(7, 3333, 'b59c67bf196a4758191e42f76670ceba', 'นาย', 'กัลปพฤกษ์', 'มิ่งจันทึก', '0610793221', 'kru1@gmail.com', 'm', '', '2024-12-15 20:12:43'),
(8, 2222, '934b535800b1cba8f96a5d72f72f1611', 'นาง', 'ffff', 'ffff', '3434434444', 'tharadol@gmail.com', 'm', '', '2024-12-15 20:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `reply`
--

CREATE TABLE `reply` (
  `assign_id` int(50) DEFAULT NULL COMMENT 'รหัสการมอบหมาย',
  `reply_id` int(10) NOT NULL COMMENT 'รหัสตอบกลับ',
  `user_id` int(10) DEFAULT NULL COMMENT 'รหัสพนักงาน',
  `due_datetime` datetime(6) DEFAULT current_timestamp(6) COMMENT 'วันที่เวลากำหนด',
  `create_at` timestamp(6) NOT NULL DEFAULT current_timestamp(6) COMMENT 'วันที่สร้าง',
  `complete_at` datetime(6) DEFAULT current_timestamp(6) COMMENT 'วันที่เสร็จสิ้น',
  `file_reply` varchar(255) DEFAULT NULL COMMENT 'ไฟล์งานตอบกลับ',
  `reply_description` varchar(50) DEFAULT NULL COMMENT 'รายละเอียดงาน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `reply`
--

INSERT INTO `reply` (`assign_id`, `reply_id`, `user_id`, `due_datetime`, `create_at`, `complete_at`, `file_reply`, `reply_description`) VALUES
(NULL, 5, 4, '2025-02-18 12:40:38.000000', '2025-02-17 23:40:38.000000', '2025-02-18 06:40:38.000000', 'uploads/file_67b41d5663cdd8.17088468.pdf', 'ดเกเกด'),
(NULL, 6, 4, '2025-02-18 12:46:32.000000', '2025-02-17 23:46:32.000000', '2025-02-18 06:46:32.000000', 'uploads/file_67b41eb89768b2.82807903.pdf', 'ดเกเกด'),
(NULL, 7, 4, '2025-02-18 15:06:46.000000', '2025-02-18 02:06:46.000000', '2025-02-18 09:06:46.000000', 'uploads/file_67b43f969b37f7.21635216.pdf', 'dfgh'),
(0, 8, 4, '2025-02-18 15:13:23.000000', '2025-02-18 02:13:23.000000', '2025-02-18 09:13:23.000000', 'uploads/file_67b441230b2f90.43466589.pdf', 'dfgh'),
(NULL, 9, 4, '2025-02-18 15:16:54.000000', '2025-02-18 02:16:54.000000', '2025-02-18 09:16:54.000000', 'uploads/file_67b441f6301433.71437363.pdf', 'dfgh');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assign_id`),
  ADD KEY `fk_job_id` (`job_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`) USING BTREE;

--
-- Indexes for table `mable`
--
ALTER TABLE `mable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `reply`
--
ALTER TABLE `reply`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `fk_assgin_id` (`assign_id`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assign_id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'รหัสการมอบหมาย', AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสงาน', AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `mable`
--
ALTER TABLE `mable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ลำดับผู้ใช้', AUTO_INCREMENT=4446;

--
-- AUTO_INCREMENT for table `reply`
--
ALTER TABLE `reply`
  MODIFY `reply_id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'รหัสตอบกลับ', AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `fk_job_id` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
