-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2024 at 11:11 AM
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
-- Database: `work`
--

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `job_type` varchar(50) NOT NULL,
  `job_subtype` varchar(50) NOT NULL,
  `job_description` text NOT NULL,
  `job_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `jobs_file` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `user_id`, `job_title`, `job_type`, `job_subtype`, `job_description`, `job_date`, `start_time`, `end_time`, `created_at`, `jobs_file`) VALUES
(4, '2', 'ทดสอบ', 'ไอทีซัพพอร์ต', 'ซ่อมคอมพิวเตอร์', 'ทดสอบ', '2024-06-13', '17:56:00', '15:54:00', '2024-06-19 07:52:15', '66728e2f63d46_DIP6601_6414631036_MINI_PROJ.pdf'),
(7, '2', 'test', 'เครือข่าย', 'งานสนับสนุน', 'test', '2024-06-19', '15:12:00', '21:12:00', '2024-06-19 08:13:04', 'วิธีการใช้งานเครื่องปริ้น.pdf'),
(8, '2', 'ทดสอบ', 'ไอทีซัพพอร์ต', 'ซ่อมปริ้นเตอร์', 'bds', '2024-06-12', '17:17:00', '20:18:00', '2024-06-19 08:14:00', 'DIP6601_6414631036_MINI_PROJ.pdf');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
