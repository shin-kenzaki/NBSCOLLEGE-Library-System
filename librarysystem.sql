-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 03, 2025 at 05:37 AM
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
-- Database: `librarysystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middle_init` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `date_added` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `last_update` date DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `employee_id`, `firstname`, `middle_init`, `lastname`, `email`, `password`, `image`, `role`, `date_added`, `status`, `last_update`, `reset_token`, `reset_expires`) VALUES
(1, 210078, 'Kenneth Laurence', 'P.', 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$pcuTpA26VUZhdIqe3QBkUu5oFHVbMig6nSa/Xr7QoaSLPn2sXVjbq', 'inc/upload/default-avatar.jpg', 'Admin', '2025-02-15', '1', '2025-02-25', '60f7756dbb4033ffa7a6e3cc82a86f11d3b8d7ae70f0905e3900a0fbd4a00871', '2025-02-25 05:06:21'),
(2, 210069, 'Cayce', '', 'Eangelista', 'cevangelista@student.nbscollege.edu.ph', '$2y$10$f6HIGgK8THWN/E4VtrMFB.NjE58QGXPNks/V15ZCiboLZZZ/xpXcW', 'inc/upload/default-avatar.jpg', 'Librarian', '2025-02-15', '1', '2025-02-25', NULL, NULL),
(3, 210045, 'Shin Haruno', '', 'Kenzaki', 'shkenzaki@student.nbscollege.edu.ph', '$2y$10$E3XrZ.GUm/fT3Kl5LWZjWO5B5t43duU.8gZpT8G0ybJrIC11FkAbe', 'inc/upload/default-avatar.jpg', 'Admin', '2025-02-25', '1', '2025-02-25', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `accession` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `preferred_title` varchar(100) DEFAULT NULL,
  `parallel_title` varchar(100) DEFAULT NULL,
  `subject_category` varchar(100) DEFAULT NULL,
  `subject_detail` varchar(9999) DEFAULT NULL,
  `summary` varchar(1000) DEFAULT NULL,
  `contents` varchar(1000) DEFAULT NULL,
  `front_image` varchar(100) DEFAULT NULL,
  `back_image` varchar(100) DEFAULT NULL,
  `dimension` varchar(100) DEFAULT NULL,
  `series` varchar(100) DEFAULT NULL,
  `volume` varchar(100) DEFAULT NULL,
  `edition` varchar(100) DEFAULT NULL,
  `copy_number` int(225) DEFAULT NULL,
  `total_pages` varchar(100) DEFAULT NULL,
  `supplementary_contents` varchar(100) DEFAULT NULL,
  `ISBN` varchar(100) DEFAULT NULL,
  `content_type` varchar(100) DEFAULT NULL,
  `media_type` varchar(100) DEFAULT NULL,
  `carrier_type` varchar(100) DEFAULT NULL,
  `call_number` varchar(100) DEFAULT NULL,
  `URL` varchar(225) DEFAULT NULL,
  `language` varchar(100) DEFAULT NULL,
  `shelf_location` varchar(100) DEFAULT NULL,
  `entered_by` int(225) DEFAULT NULL,
  `date_added` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `last_update` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `accession`, `title`, `preferred_title`, `parallel_title`, `subject_category`, `subject_detail`, `summary`, `contents`, `front_image`, `back_image`, `dimension`, `series`, `volume`, `edition`, `copy_number`, `total_pages`, `supplementary_contents`, `ISBN`, `content_type`, `media_type`, `carrier_type`, `call_number`, `URL`, `language`, `shelf_location`, `entered_by`, `date_added`, `status`, `updated_by`, `last_update`) VALUES
(108, 2870, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC 21 Standards', '', '', 'Topical', '', '', '', NULL, NULL, '', '', '', '', 1, '', '', NULL, 'Text', 'Print', 'Book', 'RES RES  c1 2015 c1', '', 'English', 'RES', 1, '2025-03-02', 'Available', 1, '2025-03-02'),
(109, 2871, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC 21 Standards', '', '', 'Topical', '', '', '', NULL, NULL, '', '', '', '', 2, '', '', NULL, 'Text', 'Print', 'Book', 'RES RES  c1 2015 c2', '', 'English', 'RES', 1, '2025-03-02', 'Available', 1, '2025-03-02'),
(110, 2872, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC 21 Standards', '', '', 'Topical', '', '', '', NULL, NULL, '', '', '', '', 3, '', '', NULL, 'Text', 'Print', 'Book', 'RES RES  c1 2015 c3', '', 'English', 'RES', 1, '2025-03-02', 'Lost', 1, '2025-03-02'),
(111, 2873, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC 21 Standards', '', '', 'Topical', '', '', '', NULL, NULL, '', '', '', '', 4, '', '', NULL, 'Text', 'Print', 'Book', 'RES RES  c1 2015 c4', '', 'English', 'RES', 1, '2025-03-02', 'Damaged', 1, '2025-03-02'),
(112, 2874, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC 21 Standards', '', '', 'Topical', '', '', '', NULL, NULL, '', '', '', '', 5, '', '', NULL, 'Text', 'Print', 'Book', 'RES RES  c1 2015 c5', '', 'English', 'RES', 1, '2025-03-02', 'Available', 1, '2025-03-02');

-- --------------------------------------------------------

--
-- Table structure for table `book_copies`
--

CREATE TABLE `book_copies` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `accession` int(11) NOT NULL,
  `copy_number` int(11) NOT NULL,
  `call_number` varchar(100) DEFAULT NULL,
  `shelf_location` varchar(100) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `date_added` date DEFAULT NULL,
  `last_update` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `id` int(225) NOT NULL,
  `user_id` int(225) DEFAULT NULL,
  `book_id` int(225) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `issue_date` timestamp NULL DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `allowed_days` int(225) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `recieved_by` int(11) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `replacement_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowings`
--

INSERT INTO `borrowings` (`id`, `user_id`, `book_id`, `status`, `issue_date`, `issued_by`, `allowed_days`, `due_date`, `return_date`, `recieved_by`, `report_date`, `replacement_date`) VALUES
(8, 4, 98, 'Returned', '2025-02-27 11:36:42', 2, 7, '2025-03-06', '2025-02-27', 2, NULL, NULL),
(9, 4, 88, 'Returned', '2025-02-27 11:36:42', 2, 7, '2025-03-06', '2025-02-27', 2, NULL, NULL),
(10, 1, 98, 'Returned', '2025-02-28 15:32:24', 1, 7, '2025-03-07', '2025-02-28', 1, NULL, NULL),
(11, 1, 88, 'Returned', '2025-02-28 15:32:24', 1, 7, '2025-03-07', '2025-02-28', 1, NULL, NULL),
(12, 1, 98, 'Returned', '2025-02-28 15:38:25', 1, 7, '2025-03-07', '2025-03-01', 1, NULL, NULL),
(13, 1, 88, 'Returned', '2025-02-28 15:38:25', 1, 7, '2025-03-07', '2025-03-01', 1, NULL, NULL),
(14, 1, 101, 'Lost', '2025-03-01 05:11:38', 1, NULL, '2025-03-08', NULL, NULL, '2025-03-01', '2025-04-01'),
(15, 1, 95, 'Damaged', '2025-03-01 05:11:47', 1, NULL, '2025-03-08', NULL, 1, '2025-03-01', '2025-04-01'),
(16, 1, 98, 'Returned', '2025-03-01 10:03:30', 1, 7, '2025-03-08', '2025-04-01', 1, NULL, NULL),
(17, 1, 88, 'Returned', '2025-03-01 10:03:30', 1, 7, '2025-03-08', '2025-04-01', 1, NULL, NULL),
(18, 1, 98, 'Lost', '2025-04-01 10:17:51', 1, 7, '2025-04-08', NULL, 1, '2025-03-01', '2025-04-01'),
(19, 1, 88, 'Lost', '2025-04-01 10:17:53', 1, 7, '2025-04-08', NULL, 1, '2025-03-01', '2025-04-01'),
(20, 1, 98, 'Returned', '2025-04-01 11:21:27', 1, 7, '2025-04-08', '2025-04-01', 1, NULL, NULL),
(21, 1, 89, 'Damaged', '2025-04-01 11:21:27', 1, 7, '2025-04-08', NULL, 1, '2025-04-01', '2025-04-01'),
(22, 1, 98, 'Lost', '2025-03-01 11:34:06', 1, 7, '2025-03-08', NULL, NULL, '2025-03-01', NULL),
(23, 1, 88, 'Lost', '2025-03-01 11:34:06', 1, 7, '2025-03-08', NULL, 1, '2025-03-01', NULL),
(24, 1, 99, 'Damaged', '2025-03-01 12:26:13', 1, 7, '2025-03-08', '2025-03-01', 1, '2025-03-01', NULL),
(25, 1, 88, 'Lost', '2025-03-01 12:26:13', 1, 7, '2025-03-08', NULL, NULL, '2025-03-01', NULL),
(26, 1, 108, 'Returned', '2025-03-02 04:07:50', 1, 7, '2025-03-09', '2025-03-02', 1, NULL, NULL),
(27, 1, 108, 'Returned', '2025-03-02 06:05:15', 1, 7, '2025-03-09', '2025-03-14', 1, NULL, NULL),
(28, 1, 110, 'Lost', '2025-03-14 06:35:01', 1, NULL, '2025-03-21', NULL, NULL, '2025-03-14', NULL),
(29, 1, 111, 'Damaged', '2025-03-14 06:40:51', 1, NULL, '2025-03-21', '2025-03-14', 1, '2025-03-14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(100) NOT NULL,
  `book_id` int(100) DEFAULT NULL,
  `user_id` int(100) DEFAULT NULL,
  `date` timestamp NULL DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `book_id`, `user_id`, `date`, `status`) VALUES
(1, 68, 1, '2025-02-27 06:34:24', 1),
(2, 98, 1, '2025-03-01 17:15:59', 0),
(3, 88, 1, '2025-03-01 17:16:01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `contributors`
--

CREATE TABLE `contributors` (
  `id` int(11) NOT NULL,
  `book_id` int(225) NOT NULL,
  `writer_id` int(225) NOT NULL,
  `role` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contributors`
--

INSERT INTO `contributors` (`id`, `book_id`, `writer_id`, `role`) VALUES
(31, 11, 3, 'Author'),
(78, 20, 3, 'Author'),
(1200, 68, 1, 'Author'),
(1201, 69, 1, 'Author'),
(1202, 70, 1, 'Author'),
(1203, 71, 1, 'Author'),
(1204, 72, 1, 'Author'),
(1345, 108, 1, 'Author'),
(1346, 109, 1, 'Author'),
(1347, 110, 1, 'Author'),
(1348, 111, 1, 'Author'),
(1349, 112, 1, 'Author');

-- --------------------------------------------------------

--
-- Table structure for table `fines`
--

CREATE TABLE `fines` (
  `id` int(11) NOT NULL,
  `borrowing_id` int(225) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `amount` decimal(65,2) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `payment_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fines`
--

INSERT INTO `fines` (`id`, `borrowing_id`, `type`, `amount`, `status`, `date`, `payment_date`) VALUES
(3, 16, 'Overdue', 120.00, 'Paid', '2025-04-01', '2025-04-01'),
(4, 17, 'Overdue', 120.00, 'Paid', '2025-04-01', '2025-04-01'),
(5, 27, 'Overdue', 25.00, 'Unpaid', '2025-03-14', '0000-00-00');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `sender_role` varchar(100) NOT NULL,
  `receiver_role` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `sender_role`, `receiver_role`, `message`, `timestamp`, `is_read`) VALUES
(52, 1, 1, 'Student', 'Admin', 'hii admin', '2025-02-26 19:27:25', 1),
(53, 1, 1, 'Admin', 'Student', 'oww hiii', '2025-02-26 19:28:02', 1),
(54, 1, 1, 'Student', 'Admin', 'can I borrow a book?', '2025-02-27 03:05:24', 1),
(55, 1, 1, 'Admin', 'Student', 'suree, what book do you want?', '2025-02-27 03:05:41', 1),
(56, 1, 1, 'Student', 'Admin', 'I would like to borrow Rizal\'s Life and Works if there is any edition there?', '2025-02-27 03:06:23', 1),
(57, 1, 1, 'Admin', 'Student', 'wait ill get back to you', '2025-02-27 03:07:24', 1);

-- --------------------------------------------------------

--
-- Table structure for table `publications`
--

CREATE TABLE `publications` (
  `id` int(11) NOT NULL,
  `book_id` int(225) NOT NULL,
  `publisher_id` int(225) NOT NULL,
  `publish_date` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `publications`
--

INSERT INTO `publications` (`id`, `book_id`, `publisher_id`, `publish_date`) VALUES
(68, 68, 1, '2015'),
(69, 69, 1, '2015'),
(70, 70, 1, '2015'),
(71, 71, 1, '2015'),
(72, 72, 1, '2015'),
(108, 108, 1, '2015'),
(109, 109, 1, '2015'),
(110, 110, 1, '2015'),
(111, 111, 1, '2015'),
(112, 112, 1, '2015');

-- --------------------------------------------------------

--
-- Table structure for table `publishers`
--

CREATE TABLE `publishers` (
  `id` int(11) NOT NULL,
  `publisher` varchar(100) NOT NULL,
  `place` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `publishers`
--

INSERT INTO `publishers` (`id`, `publisher`, `place`) VALUES
(1, 'Chandos Publishing', 'USA'),
(2, 'Elsevier', 'USA'),
(3, 'Richard D. Irwin Inc.', 'USA');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(225) NOT NULL,
  `user_id` int(225) DEFAULT NULL,
  `book_id` int(225) DEFAULT NULL,
  `reserve_date` timestamp NULL DEFAULT NULL,
  `ready_date` timestamp NULL DEFAULT NULL,
  `ready_by` int(11) DEFAULT NULL,
  `issue_date` timestamp NULL DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `cancel_date` timestamp NULL DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_by_role` varchar(50) DEFAULT NULL,
  `recieved_date` timestamp NULL DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `book_id`, `reserve_date`, `ready_date`, `ready_by`, `issue_date`, `issued_by`, `cancel_date`, `cancelled_by`, `cancelled_by_role`, `recieved_date`, `status`) VALUES
(1, 1, 68, '2025-04-27 02:02:34', '2025-04-27 08:02:42', 1, NULL, NULL, '2025-04-27 02:03:18', 1, 'User', NULL, 'Cancelled'),
(2, 1, 68, '2025-04-27 02:03:41', NULL, NULL, NULL, NULL, '2025-04-27 08:03:53', 1, 'Admin', NULL, 'Cancelled'),
(3, 1, 68, '2025-04-27 02:04:10', NULL, NULL, NULL, NULL, '2025-04-27 08:05:04', 2, 'Admin', NULL, 'Cancelled'),
(4, 4, 98, '2025-02-27 03:31:05', '2025-02-27 10:31:58', 1, '2025-02-27 11:15:00', 1, NULL, NULL, NULL, '2025-02-27 11:15:00', 'Received'),
(5, 4, 88, '2025-02-27 03:31:08', '2025-02-27 10:32:37', 2, '2025-02-27 11:15:00', 1, NULL, NULL, NULL, '2025-02-27 11:15:00', 'Received'),
(6, 4, 98, '2025-02-27 04:36:26', '2025-02-27 11:36:38', 2, '2025-02-27 11:36:42', 2, NULL, NULL, NULL, '2025-02-27 11:36:42', 'Received'),
(7, 4, 88, '2025-02-27 04:36:28', '2025-02-27 11:36:38', 2, '2025-02-27 11:36:42', 2, NULL, NULL, NULL, '2025-02-27 11:36:42', 'Received'),
(8, 1, 98, '2025-02-28 08:05:38', '2025-02-28 15:32:19', 1, '2025-02-28 15:32:24', 1, NULL, NULL, NULL, '2025-02-28 15:32:24', 'Received'),
(9, 1, 88, '2025-02-28 08:05:39', '2025-02-28 15:32:19', 1, '2025-02-28 15:32:24', 1, NULL, NULL, NULL, '2025-02-28 15:32:24', 'Received'),
(10, 1, 98, '2025-02-28 08:38:04', '2025-02-28 15:38:21', 1, '2025-02-28 15:38:25', 1, NULL, NULL, NULL, '2025-02-28 15:38:25', 'Received'),
(11, 1, 88, '2025-02-28 08:38:07', '2025-02-28 15:38:21', 1, '2025-02-28 15:38:25', 1, NULL, NULL, NULL, '2025-02-28 15:38:25', 'Received'),
(12, 1, 98, '2025-03-01 03:02:36', '2025-03-01 10:03:27', 1, '2025-03-01 10:03:30', 1, NULL, NULL, NULL, '2025-03-01 10:03:30', 'Received'),
(13, 1, 88, '2025-03-01 03:02:38', '2025-03-01 10:03:27', 1, '2025-03-01 10:03:30', 1, NULL, NULL, NULL, '2025-03-01 10:03:30', 'Received'),
(14, 1, 98, '2025-04-01 04:17:32', '2025-04-01 10:17:48', 1, '2025-04-01 10:17:51', 1, NULL, NULL, NULL, '2025-04-01 10:17:51', 'Received'),
(15, 1, 88, '2025-04-01 04:17:36', '2025-04-01 10:17:45', 1, '2025-04-01 10:17:53', 1, NULL, NULL, NULL, '2025-04-01 10:17:53', 'Received'),
(16, 1, 98, '2025-04-01 05:21:11', '2025-04-01 11:21:23', 1, '2025-04-01 11:21:27', 1, NULL, NULL, NULL, '2025-04-01 11:21:27', 'Received'),
(17, 1, 89, '2025-04-01 05:21:13', '2025-04-01 11:21:23', 1, '2025-04-01 11:21:27', 1, NULL, NULL, NULL, '2025-04-01 11:21:27', 'Received'),
(18, 1, 98, '2025-03-01 04:33:48', '2025-03-01 11:34:03', 1, '2025-03-01 11:34:06', 1, NULL, NULL, NULL, '2025-03-01 11:34:06', 'Received'),
(19, 1, 88, '2025-03-01 04:33:50', '2025-03-01 11:34:03', 1, '2025-03-01 11:34:06', 1, NULL, NULL, NULL, '2025-03-01 11:34:06', 'Received'),
(20, 1, 99, '2025-03-01 05:26:01', '2025-03-01 12:26:10', 1, '2025-03-01 12:26:13', 1, NULL, NULL, NULL, '2025-03-01 12:26:13', 'Received'),
(21, 1, 88, '2025-03-01 05:26:03', '2025-03-01 12:26:10', 1, '2025-03-01 12:26:13', 1, NULL, NULL, NULL, '2025-03-01 12:26:13', 'Received'),
(22, 1, 98, '2025-03-01 10:16:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending'),
(23, 1, 108, '2025-03-01 21:07:40', '2025-03-02 04:07:48', 1, '2025-03-02 04:07:50', 1, NULL, NULL, NULL, '2025-03-02 04:07:50', 'Received'),
(24, 1, 108, '2025-03-01 22:39:35', '2025-03-02 06:05:10', 1, '2025-03-02 06:05:15', 1, NULL, NULL, NULL, '2025-03-02 06:05:15', 'Received');

-- --------------------------------------------------------

--
-- Table structure for table `updates`
--

CREATE TABLE `updates` (
  `id` int(225) NOT NULL,
  `user_id` int(225) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `update` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `updates`
--

INSERT INTO `updates` (`id`, `user_id`, `role`, `status`, `update`) VALUES
(1, 210078, 'Admin', 'Active login', '2025-02-21 17:21:41'),
(2, 210078, 'Admin', 'Active login', '2025-02-22 01:31:39'),
(3, 210078, 'Admin', 'Active login', '2025-02-22 02:51:38'),
(4, 210078, 'Admin', 'Active login', '2025-02-22 04:43:27'),
(5, 210078, 'student', 'Inactive Login', '2025-02-22 04:43:40'),
(6, 210078, 'Admin', 'Active login', '2025-02-22 05:29:21'),
(7, 210078, 'Admin', 'Active login', '2025-02-22 06:47:44'),
(8, 210078, 'Admin', 'Active login', '2025-02-22 07:44:14'),
(9, 210078, 'Admin', 'Active login', '2025-02-22 10:47:26'),
(10, 210078, 'Admin', 'Active login', '2025-02-22 11:44:44'),
(11, 210078, 'Admin', 'Active login', '2025-02-22 13:00:41'),
(12, 210078, 'Admin', 'Active login', '2025-02-23 03:34:17'),
(13, 210078, 'Admin', 'Active login', '2025-02-23 14:14:19'),
(14, 210078, 'Admin', 'Active login', '2025-02-23 14:36:04'),
(15, 210078, 'student', 'Inactive Login', '2025-02-23 14:36:13'),
(16, 210078, 'Admin', 'Active login', '2025-02-24 02:08:42'),
(17, 210078, 'student', 'Inactive Login', '2025-02-24 02:08:49'),
(18, 210069, 'student', 'Inactive Login', '2025-02-24 02:13:45'),
(19, 210068, 'student', 'Inactive Login', '2025-02-24 02:14:26'),
(20, 210075, 'student', 'Inactive Login', '2025-02-24 02:16:46'),
(21, 210078, 'Admin', 'Active login', '2025-02-24 04:32:07'),
(22, 210078, 'student', 'Inactive Login', '2025-02-24 04:34:05'),
(23, 265948, 'student', 'Active Login', '2025-02-24 04:45:52'),
(24, 210078, 'student', 'Inactive Login', '2025-02-24 04:46:07'),
(25, 210078, 'Admin', 'Active login', '2025-02-24 04:49:21'),
(26, 265984, 'student', 'Active Login', '2025-02-24 04:51:18'),
(27, 210078, 'Admin', 'Active login', '2025-02-24 04:52:28'),
(28, 210078, 'student', 'Inactive Login', '2025-02-24 04:52:38'),
(29, 210078, 'Admin', 'Active login', '2025-02-24 04:54:08'),
(30, 210078, 'student', 'Inactive Login', '2025-02-24 04:54:19'),
(31, 210078, 'Admin', 'Active login', '2025-02-24 04:57:10'),
(32, 210078, 'student', 'Inactive Login', '2025-02-24 04:57:18'),
(33, 210078, 'student', 'Inactive Login', '2025-02-24 04:57:50'),
(34, 210069, 'student', 'Inactive Login', '2025-02-24 05:58:17'),
(35, 210069, 'student', 'Inactive Login', '2025-02-24 05:58:37'),
(36, 210068, 'student', 'Inactive Login', '2025-02-24 05:58:50'),
(37, 210075, 'student', 'Inactive Login', '2025-02-24 05:59:11'),
(38, 265984, 'student', 'Active Login', '2025-02-24 05:59:39'),
(39, 210078, 'student', 'Inactive Login', '2025-02-24 06:00:32'),
(40, 210069, 'student', 'Inactive Login', '2025-02-24 06:20:17'),
(41, 210068, 'student', 'Inactive Login', '2025-02-24 07:39:51'),
(42, 210075, 'student', 'Inactive Login', '2025-02-24 07:40:24'),
(43, 210078, 'student', 'Inactive Login', '2025-02-24 07:40:43'),
(44, 210069, 'student', 'Active Login', '2025-02-24 07:56:43'),
(45, 210068, 'student', 'Active Login', '2025-02-24 07:57:00'),
(46, 210075, 'student', 'Active Login', '2025-02-24 07:57:20'),
(47, 210048, 'student', 'Active Login', '2025-02-24 07:58:12'),
(48, 210078, 'student', 'Active Login', '2025-02-24 08:25:37'),
(49, 210078, 'Admin', 'Active login', '2025-02-24 08:50:24'),
(50, 210078, 'student', 'Active Login', '2025-02-24 08:51:20'),
(51, 210069, 'student', 'Active Login', '2025-02-24 08:58:43'),
(52, 210068, 'student', 'Active Login', '2025-02-24 08:59:00'),
(53, 210078, 'student', 'Active Login', '2025-02-24 09:10:10'),
(54, 210069, 'student', 'Active Login', '2025-02-24 09:10:37'),
(55, 210068, 'student', 'Active Login', '2025-02-24 09:10:54'),
(56, 210078, 'Admin', 'Active login', '2025-02-24 09:20:12'),
(57, 210078, 'student', 'Active Login', '2025-02-24 09:21:44'),
(58, 210078, 'Admin', 'Active login', '2025-02-24 14:30:45'),
(59, 210078, 'student', 'Active Login', '2025-02-24 14:31:02'),
(60, 210078, 'student', 'Active Login', '2025-02-25 02:23:43'),
(61, 210078, 'Admin', 'Active login', '2025-02-25 02:23:57'),
(62, 210045, 'Admin', 'Active login', '2025-02-25 02:37:47'),
(63, 210078, 'student', 'Active Login', '2025-02-25 02:50:15'),
(64, 210048, 'student', 'Active Login', '2025-02-25 02:50:28'),
(65, 210069, 'student', 'Active Login', '2025-02-25 02:51:39'),
(66, 210078, 'student', 'Active Login', '2025-02-25 02:51:57'),
(67, 210048, 'student', 'Active Login', '2025-02-25 02:52:26'),
(68, 210078, 'student', 'Active Login', '2025-02-25 02:54:16'),
(69, 210078, 'Admin', 'Active login', '2025-02-25 03:15:27'),
(70, 210078, 'Admin', 'Active login', '2025-02-25 03:16:30'),
(71, 210078, 'Admin', 'Active login', '2025-02-25 03:16:44'),
(72, 210078, 'student', 'Active Login', '2025-02-25 03:22:21'),
(73, 210078, 'student', 'Active Login', '2025-02-25 03:24:16'),
(74, 210078, 'Admin', 'Active login', '2025-02-25 03:25:54'),
(75, 210078, 'Admin', 'Active login', '2025-02-25 03:27:48'),
(76, 210078, 'student', 'Active Login', '2025-02-25 03:29:10'),
(77, 210078, 'Admin', 'Active login', '2025-02-25 03:29:36'),
(78, 210078, 'Admin', 'Active login', '2025-02-25 03:55:10'),
(79, 210078, 'Admin', 'Active login', '2025-02-25 03:59:05'),
(80, 210078, 'Admin', 'Active login', '2025-02-25 04:19:48'),
(81, 210078, 'student', 'Active Login', '2025-02-25 04:30:12'),
(82, 210078, 'Admin', 'Active login', '2025-02-25 04:56:02'),
(83, 210078, 'student', 'Active Login', '2025-02-25 05:10:45'),
(84, 210078, 'Admin', 'Active login', '2025-02-25 05:39:31'),
(85, 210078, 'Admin', 'Active login', '2025-02-25 05:46:10'),
(86, 210078, 'Admin', 'Active login', '2025-02-25 06:43:52'),
(87, 210078, 'Admin', 'Active login', '2025-02-25 06:45:38'),
(88, 210078, 'student', 'Active Login', '2025-02-25 06:55:52'),
(89, 210078, 'student', 'Active Login', '2025-02-25 07:23:10'),
(90, 210078, 'Admin', 'Active login', '2025-02-25 07:47:35'),
(91, 210078, 'student', 'Active Login', '2025-02-25 07:53:52'),
(92, 210078, 'Admin', 'Active login', '2025-02-25 08:24:24'),
(93, 210078, 'student', 'Active Login', '2025-02-25 08:24:52'),
(94, 210078, 'Admin', 'Added Book ID:0', '2025-02-25 08:40:56'),
(95, 210078, 'Admin', 'Active login', '2025-02-25 13:49:13'),
(96, 210078, 'student', 'Active Login', '2025-02-25 13:49:21'),
(97, 210078, 'Admin', 'Active login', '2025-02-26 00:52:20'),
(98, 210078, 'student', 'Active Login', '2025-02-26 00:55:53'),
(99, 210078, 'Admin', 'Active login', '2025-02-26 01:50:27'),
(100, 210078, 'student', 'Active Login', '2025-02-26 01:53:21'),
(101, 210078, 'Student', 'Active Login', '2025-02-26 03:12:50'),
(102, 210078, 'Student', 'Active Login', '2025-02-26 03:15:41'),
(103, 210078, 'Student', 'Active Login', '2025-02-26 03:16:00'),
(104, 210069, 'Student', 'Active Login', '2025-02-26 03:16:08'),
(105, 210078, 'Student', 'Active Login', '2025-02-26 03:16:31'),
(106, 210078, 'Student', 'Active Login', '2025-02-26 03:32:29'),
(107, 210078, 'Student', 'Active Login', '2025-02-26 03:41:16'),
(108, 210078, 'Student', 'Active Login', '2025-02-26 03:44:31'),
(109, 210078, 'Student', 'Active Login', '2025-02-26 03:52:10'),
(110, 210078, 'Admin', 'Active login', '2025-02-26 04:02:40'),
(111, 210078, 'Student', 'Active Login', '2025-02-26 04:02:52'),
(112, 210078, 'Admin', 'Active login', '2025-02-26 04:32:35'),
(113, 210078, 'Student', 'Active Login', '2025-02-26 04:32:46'),
(114, 210045, 'Admin', 'Active login', '2025-02-26 04:33:14'),
(115, 210078, 'Admin', 'Active login', '2025-02-26 04:33:34'),
(116, 210078, 'Student', 'Active Login', '2025-02-26 04:35:27'),
(117, 210078, 'Student', 'Active Login', '2025-02-26 05:29:46'),
(118, 210078, 'Admin', 'Active login', '2025-02-26 05:48:53'),
(119, 210078, 'Student', 'Active Login', '2025-02-26 05:49:20'),
(120, 210078, 'Student', 'Active Login', '2025-02-26 06:06:34'),
(121, 210078, 'Admin', 'Active login', '2025-02-26 10:34:16'),
(122, 210078, 'Student', 'Active Login', '2025-02-26 10:40:52'),
(123, 210078, 'Admin', 'Active login', '2025-02-26 11:49:50'),
(124, 210078, 'Admin', 'Active login', '2025-02-26 12:10:07'),
(125, 210078, 'Admin', 'Active login', '2025-02-26 12:53:05'),
(126, 210078, 'Admin', 'Active login', '2025-02-26 13:26:32'),
(127, 210078, 'Admin', 'Active login', '2025-02-26 13:55:19'),
(128, 210078, 'Admin', 'Active login', '2025-02-26 14:25:32'),
(129, 210078, 'Student', 'Active Login', '2025-02-26 14:44:07'),
(130, 210075, 'Student', 'Active Login', '2025-02-26 14:47:54'),
(131, 210068, 'Student', 'Active Login', '2025-02-26 15:09:00'),
(132, 210075, 'Student', 'Active Login', '2025-02-26 15:09:25'),
(133, 210078, 'Admin', 'Active login', '2025-02-26 15:11:21'),
(134, 210048, 'Student', 'Active Login', '2025-02-26 15:38:42'),
(135, 210069, 'Librarian', 'Active login', '2025-02-26 15:41:17'),
(136, 210069, 'Librarian', 'Active login', '2025-02-26 15:41:27'),
(137, 210069, 'Librarian', 'Active login', '2025-02-26 15:42:30'),
(138, 210069, 'Librarian', 'Active login', '2025-02-26 15:44:37'),
(139, 210069, 'Librarian', 'Active login', '2025-02-26 15:44:49'),
(140, 210078, 'Admin', 'Active login', '2025-02-26 15:45:14'),
(141, 210069, 'Librarian', 'Active login', '2025-02-26 15:47:22'),
(142, 210069, 'Librarian', 'Active login', '2025-02-26 15:49:35'),
(143, 210069, 'Librarian', 'Active login', '2025-02-26 15:49:45'),
(144, 210069, 'Librarian', 'Active login', '2025-02-26 15:50:22'),
(145, 210069, 'Librarian', 'Active login', '2025-02-26 15:50:37'),
(146, 210069, 'Librarian', 'Active login', '2025-02-26 15:51:31'),
(147, 210069, 'Librarian', 'Active login', '2025-02-26 15:51:42'),
(148, 210069, 'Librarian', 'Active login', '2025-02-26 15:52:04'),
(149, 210045, 'Admin', 'Active login', '2025-02-26 15:52:25'),
(150, 210069, 'Librarian', 'Active login', '2025-02-26 15:54:48'),
(151, 210078, 'Admin', 'Active login', '2025-02-26 15:55:08'),
(152, 210069, 'Librarian', 'Active login', '2025-02-26 15:58:54'),
(153, 210078, 'Admin', 'Active login', '2025-02-26 15:59:32'),
(154, 210069, 'Librarian', 'Active login', '2025-02-26 15:59:38'),
(155, 210069, 'Librarian', 'Active login', '2025-02-26 16:00:10'),
(156, 210078, 'Admin', 'Active login', '2025-02-26 16:03:19'),
(157, 210069, 'Librarian', 'Active login', '2025-02-26 16:03:34'),
(158, 210078, 'Student', 'Active Login', '2025-02-26 16:17:05'),
(159, 210078, 'Student', 'Active Login', '2025-02-26 16:36:18'),
(160, 210078, 'Admin', 'Active login', '2025-02-26 16:43:19'),
(161, 210078, 'Admin', 'Active login', '2025-02-26 16:51:09'),
(162, 210078, 'Admin', 'Active login', '2025-02-26 16:53:32'),
(163, 210078, 'Admin', 'Active login', '2025-02-26 17:18:57'),
(164, 210078, 'Student', 'Active Login', '2025-02-26 17:22:38'),
(165, 210078, 'Admin', 'Active login', '2025-02-26 17:35:20'),
(166, 210078, 'Student', 'Active Login', '2025-02-26 18:40:25'),
(167, 210068, 'Student', 'Active Login', '2025-02-26 18:41:03'),
(168, 210075, 'Student', 'Active Login', '2025-02-26 18:41:48'),
(169, 210078, 'Student', 'Active Login', '2025-02-26 18:46:47'),
(170, 210078, 'Student', 'Active Login', '2025-02-26 19:03:22'),
(171, 210078, 'Admin', 'Active login', '2025-02-26 19:06:27'),
(172, 210078, 'Admin', 'Active login', '2025-02-27 03:04:58'),
(173, 210078, 'Student', 'Active Login', '2025-02-27 03:05:04'),
(174, 210078, 'Admin', 'Active login', '2025-02-27 03:20:11'),
(175, 210078, 'Student', 'Active Login', '2025-02-27 03:20:17'),
(176, 210078, 'Admin', 'Active login', '2025-02-27 03:22:22'),
(177, 210078, 'Student', 'Active Login', '2025-02-27 03:22:33'),
(178, 210078, 'Admin', 'Active login', '2025-02-27 04:33:14'),
(179, 210078, 'Student', 'Active Login', '2025-02-27 04:39:59'),
(180, 210078, 'Admin', 'Active login', '2025-02-27 05:29:03'),
(181, 210078, 'Student', 'Active Login', '2025-02-27 05:51:53'),
(182, 210078, 'Admin', 'Active login', '2025-02-27 05:52:43'),
(183, 210078, 'Student', 'Active Login', '2025-02-27 05:55:53'),
(184, 210078, 'Admin', 'Active login', '2025-02-27 05:56:12'),
(185, 210078, 'Student', 'Active Login', '2025-02-27 06:33:46'),
(186, 210078, 'Admin', 'Active login', '2025-02-27 06:38:35'),
(187, 210078, 'Admin', 'Active login', '2025-02-27 07:09:55'),
(188, 210069, 'Librarian', 'Active login', '2025-04-27 07:54:49'),
(189, 210078, 'Admin', 'Active login', '2025-04-27 08:36:06'),
(190, 210078, 'Admin', 'Active login', '2025-04-27 08:38:57'),
(191, 210078, 'Admin', 'Active login', '2025-04-27 08:39:46'),
(192, 210069, 'Librarian', 'Active login', '2025-04-27 08:40:34'),
(193, 210075, 'Student', 'Inactive Login', '2025-02-27 10:25:57'),
(194, 210078, 'Admin', 'Active login', '2025-02-27 10:43:54'),
(195, 210078, 'Admin', 'Active login', '2025-02-27 14:21:59'),
(196, 210078, 'Student', 'Active Login', '2025-02-27 14:22:05'),
(197, 210078, 'Admin', 'Active login', '2025-02-27 14:41:51'),
(198, 210078, 'Student', 'Active Login', '2025-02-27 14:42:01'),
(199, 210078, 'Student', 'Active Login', '2025-02-27 14:47:17'),
(200, 210078, 'Admin', 'Active login', '2025-02-28 11:56:32'),
(201, 210078, 'Student', 'Active Login', '2025-02-28 11:56:48'),
(202, 210078, 'Student', 'Active Login', '2025-02-28 13:56:42'),
(203, 210078, 'Student', 'Active Login', '2025-02-28 14:14:16'),
(204, 210078, 'Admin', 'Active login', '2025-02-28 14:29:12'),
(205, 210078, 'Admin', 'Active login', '2025-02-28 15:06:02'),
(206, 210078, 'Student', 'Active Login', '2025-02-28 15:06:27'),
(207, 210078, 'Admin', 'Active login', '2025-03-01 05:05:17'),
(208, 210078, 'Student', 'Active Login', '2025-03-01 05:05:20'),
(209, 210078, 'Admin', 'Active login', '2025-03-01 05:21:21'),
(210, 210078, 'Admin', 'Active login', '2025-03-01 09:44:39'),
(211, 210078, 'Admin', 'Active login', '2025-03-01 10:02:20'),
(212, 210078, 'Student', 'Active Login', '2025-03-01 10:02:28'),
(213, 210078, 'Admin', 'Active login', '2025-04-01 10:25:04'),
(214, 210078, 'Student', 'Active Login', '2025-04-01 11:21:05'),
(215, 210078, 'Admin', 'Active login', '2025-03-01 14:43:56'),
(216, 210078, 'Admin', 'Active Login', '2025-03-01 17:40:56'),
(217, 210078, 'Student', 'Active Login', '2025-03-01 17:44:05'),
(218, 210078, 'Student', 'Active Login', '2025-03-01 17:50:26'),
(219, 210078, 'Admin', 'Active Login', '2025-03-01 17:51:50'),
(220, 210078, 'Student', 'Active Login', '2025-03-01 17:52:24'),
(221, 210078, 'Admin', 'Active Login', '2025-03-01 18:02:21'),
(222, 210078, 'Student', 'Active Login', '2025-03-01 18:06:01'),
(223, 210078, 'Admin', 'Active Login', '2025-03-02 03:29:51'),
(224, 210078, 'Student', 'Active Login', '2025-03-02 03:29:58'),
(225, 210078, 'Admin', 'Active Login', '2025-03-02 04:57:28'),
(226, 210078, 'Student', 'Active Login', '2025-03-02 05:39:12'),
(227, 210078, 'Admin', 'Active Login', '2025-03-03 03:46:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `school_id` int(11) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middle_init` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `contact_no` varchar(100) DEFAULT NULL,
  `borrowed_books` int(225) NOT NULL DEFAULT 0,
  `returned_books` int(225) NOT NULL DEFAULT 0,
  `damaged_books` int(225) NOT NULL DEFAULT 0,
  `lost_books` int(225) NOT NULL DEFAULT 0,
  `user_image` varchar(100) DEFAULT NULL,
  `usertype` varchar(100) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `id_type` varchar(100) DEFAULT NULL,
  `id_image` varchar(100) DEFAULT NULL,
  `date_added` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `last_update` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `firstname`, `middle_init`, `lastname`, `email`, `password`, `contact_no`, `borrowed_books`, `returned_books`, `damaged_books`, `lost_books`, `user_image`, `usertype`, `address`, `id_type`, `id_image`, `date_added`, `status`, `last_update`) VALUES
(1, 210078, 'Kenneth Laurence', 'P', 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$dWw.Ny63FItTeSPJCbzLdetO/ojmR1cjWalq6d.O/uIWaXJyT9esG', '', 20, 9, 6, 5, '../Images/Profile/bg-login.JPG', 'Student', NULL, NULL, NULL, '2025-02-15', '1', '2025-03-02'),
(2, 210069, 'Cayce', '', 'Evangelista', 'cevangelista@student.nbscollege.edu.ph', '$2y$10$ynJw.hoXDLCxmKebyCahV.QYKNQhWsoXttgia7.2u7.ovXaZ0lH9e', NULL, 0, 0, 0, 0, '../Images/Profile/default-avatar.jpg', 'Student', NULL, NULL, NULL, '2025-02-19', '0', '2025-04-27'),
(3, 210068, 'Shin', '', 'Kenzaki', 'skenzaki@student.nbscollege.edu.ph', '$2y$10$8NT275sba9GTn2RsDxYhGuAgKKprjlYjLrjY2Pc.a7lgqmtPkKdHS', NULL, 0, 0, 0, 0, '../Images/Profile/default-avatar.jpg', 'Student', NULL, NULL, NULL, '2025-02-19', '0', '2025-04-27'),
(4, 210075, 'Shin Haruno', '', 'Kenzaki', 'shin@gmail.com', '$2y$10$SzIEexrbM78XknWbgOevr.YvvU6eHC8G4q8RPXUpOP1suQCrHSphW', NULL, 6, 6, 0, 0, '../Images/Profile/default-avatar.jpg', 'Student', NULL, NULL, NULL, '2025-02-24', '1', '2025-02-27'),
(13, 210048, 'Jenepir', NULL, 'Jabillo', 'jjabillo2021@student.nbscollege.edu.ph', '$2y$10$oD4N/8mSgK5.oeIoc7laY.ZGZAXnG8WA1eJGA3/gRLEUejLt5/YZ.', '', 0, 0, 0, 0, '../Images/Profile/default-avatar.jpg', 'Student', '', '', '/upload/default-id.png', '2025-02-25', '0', '2025-04-27');

-- --------------------------------------------------------

--
-- Table structure for table `writers`
--

CREATE TABLE `writers` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middle_init` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `writers`
--

INSERT INTO `writers` (`id`, `firstname`, `middle_init`, `lastname`) VALUES
(1, 'Fotiz', '', 'Lazarinis'),
(2, 'Marie Keen', '', 'Shaw'),
(3, 'Shin Haruno', '', 'Kenzaki'),
(4, 'M. K.', '', 'Shaw'),
(9, 'William', 'J.', 'Stevenson');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `accession` (`accession`);

--
-- Indexes for table `book_copies`
--
ALTER TABLE `book_copies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `accession` (`accession`);

--
-- Indexes for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contributors`
--
ALTER TABLE `contributors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fines`
--
ALTER TABLE `fines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `publications`
--
ALTER TABLE `publications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `publishers`
--
ALTER TABLE `publishers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `updates`
--
ALTER TABLE `updates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_id` (`school_id`);

--
-- Indexes for table `writers`
--
ALTER TABLE `writers`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `book_copies`
--
ALTER TABLE `book_copies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contributors`
--
ALTER TABLE `contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1350;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `publications`
--
ALTER TABLE `publications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=228;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
