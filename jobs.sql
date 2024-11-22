-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2024 at 05:52 AM
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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `fk_mable` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสงาน', AUTO_INCREMENT=2;

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
