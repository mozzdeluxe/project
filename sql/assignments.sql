-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 26, 2024 at 09:21 AM
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
  `job_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `job_description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `due_time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','completed','late') DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `file_path` varchar(50) NOT NULL,
  `file_reply` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`job_id`, `supervisor_id`, `user_id`, `job_title`, `job_description`, `due_date`, `due_time`, `created_at`, `status`, `completed_at`, `file_path`, `file_reply`) VALUES
(6, 4, 3, 'กดเ้่าส', 'หกปดแเ้ิ่ืา', '2024-07-05', '00:36:00', '2024-07-05 04:47:15', 'pending', NULL, '', ''),
(7, 4, 1, 'sedrfhgj', 'awstfggyt', '2024-07-04', '01:49:00', '2024-07-05 04:47:36', 'late', '2024-07-10 08:24:59', '', ''),
(10, 2, 1, 'test', 'dfghj123', '2024-07-25', '15:10:00', '2024-07-25 07:49:07', 'pending', NULL, '', ''),
(11, 2, 3, 'long', 'dfghj123', '2024-07-25', '16:00:00', '2024-07-25 07:49:45', 'pending', NULL, '', ''),
(12, 2, 1, 'test', 'dfghjkl123', '2024-07-26', '13:50:00', '2024-07-26 06:50:39', 'pending', NULL, '', ''),
(13, 2, 1, 'test', 'ertyui123', '2024-07-26', '13:53:00', '2024-07-26 06:53:55', 'pending', NULL, '', ''),
(14, 2, 1, 'fghejr32', 'sredtfghjbk', '2024-07-26', '15:01:00', '2024-07-26 07:00:22', 'pending', NULL, '', ''),
(15, 2, 3, 'ะัี้า่ิ', '้เ่้าีเรัดน้รส', '2024-07-26', '14:12:00', '2024-07-26 07:12:48', 'pending', NULL, 'uploads/5DO_5DON_T.pdf', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`job_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
