-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 11, 2025 at 02:38 AM
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
(1, 210078, 'Kenneth Laurence', 'P.', 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$pcuTpA26VUZhdIqe3QBkUu5oFHVbMig6nSa/Xr7QoaSLPn2sXVjbq', '../Images/Profile/default-avatar.jpg', 'Admin', '2025-02-15', '1', '2025-03-08', '6b8ff05dad38e8049b464e83e79f337697a83bd620fd30df5916bcf6fa1f2344', '2025-03-09 16:08:24');

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
(1, 6190, 'Entrepreneurship', '', '', 'Topical', '', '', '', NULL, NULL, '', '', '', '', 1, '', '', '9789395080545', 'Text', 'Print', 'Book', 'RES HB615 2024 c1', '', 'English', 'RES', 1, '2025-03-06', 'Available', 1, '2025-03-09'),
(2, 6191, 'Entrepreneurship', '', '', 'Topical', '', '', '', NULL, NULL, '', '', '', '', 2, '', '', '9789395080545', 'Text', 'Print', 'Book', 'RES HB615 2024 c2', '', 'English', 'RES', 1, '2025-03-06', 'Borrowed', 1, '2025-03-09'),
(3, 6192, 'Entrepreneurship', '', '', 'Topical', '', '', '', NULL, NULL, '', '', '', '', 3, '', '', '9789395080545', 'Text', 'Print', 'Book', 'RES HB615 2024 c3', '', 'English', 'RES', 1, '2025-03-06', 'Available', 1, '2025-03-09'),
(4, 6193, 'Entrepreneurship', '', '', 'Topical', '', '', '', NULL, NULL, '', '', '', '', 4, '', '', '9789395080545', 'Text', 'Print', 'Book', 'RES HB615 2024 c4', '', 'English', 'RES', 1, '2025-03-06', 'Available', 1, '2025-03-09'),
(5, 6194, 'Entrepreneurship', '', '', 'Topical', '', '', '', NULL, NULL, '', '', '', '', 5, '', '', '9789395080545', 'Text', 'Print', 'Book', 'RES HB615 2024 c5', '', 'English', 'RES', 1, '2025-03-06', 'Available', 1, '2025-03-09'),
(6, 26, 'Classification and Cataloging', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 1, '', '', '', 'Text', 'Print', 'Book', 'FIL TR  c1 2017 c1', '', 'English', 'FIL', 1, '2025-03-09', 'Available', 1, '2025-03-09'),
(7, 27, 'Classification and Cataloging', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 2, '', '', '', 'Text', 'Print', 'Book', 'FIL TR  c1 2017 c2', '', 'English', 'FIL', 1, '2025-03-09', 'Available', 1, '2025-03-09'),
(8, 28, 'Classification and Cataloging', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 3, '', '', '', 'Text', 'Print', 'Book', 'FIL TR  c1 2017 c3', '', 'English', 'FIL', 1, '2025-03-09', 'Borrowed', 1, '2025-03-09'),
(9, 29, 'Classification and Cataloging', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 4, '', '', '', 'Text', 'Print', 'Book', 'FIL TR  c1 2017 c4', '', 'English', 'FIL', 1, '2025-03-09', 'Available', 1, '2025-03-09'),
(10, 30, 'Classification and Cataloging', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 5, '', '', '', 'Text', 'Print', 'Book', 'FIL TR  c1 2017 c5', '', 'English', 'FIL', 1, '2025-03-09', 'Available', 1, '2025-03-09'),
(11, 5986, 'The Colors of Lucban', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 1, '', '', '', 'Text', 'Print', 'Book', 'REF FIL  c1 2010 c1', '', 'English', 'REF', 1, '2025-03-09', 'Available', 1, '2025-03-09'),
(12, 5987, 'The Colors of Lucban', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 2, '', '', '', 'Text', 'Print', 'Book', 'REF FIL  c1 2010 c2', '', 'English', 'REF', 1, '2025-03-09', 'Available', 1, '2025-03-09'),
(13, 5988, 'The Colors of Lucban', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 3, '', '', '', 'Text', 'Print', 'Book', 'REF FIL  c1 2010 c3', '', 'English', 'REF', 1, '2025-03-09', 'Borrowed', 1, '2025-03-09'),
(14, 5989, 'The Colors of Lucban', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 4, '', '', '', 'Text', 'Print', 'Book', 'REF FIL  c1 2010 c4', '', 'English', 'REF', 1, '2025-03-09', 'Available', 1, '2025-03-09'),
(15, 5990, 'The Colors of Lucban', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 5, '', '', '', 'Text', 'Print', 'Book', 'REF FIL  c1 2010 c5', '', 'English', 'REF', 1, '2025-03-09', 'Available', 1, '2025-03-09');

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `id` int(225) NOT NULL,
  `user_id` int(225) DEFAULT NULL,
  `book_id` int(225) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `recieved_by` int(11) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `replacement_date` date DEFAULT NULL,
  `reminder_sent` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowings`
--

INSERT INTO `borrowings` (`id`, `user_id`, `book_id`, `status`, `issue_date`, `issued_by`, `due_date`, `return_date`, `recieved_by`, `report_date`, `replacement_date`, `reminder_sent`) VALUES
(13, 1, 3, 'Returned', '2025-03-06', 1, '2025-03-13', '2025-03-06', 1, NULL, NULL, NULL),
(14, 2, 5, 'Returned', '2025-03-06', 1, '2025-03-13', '2025-03-06', 1, NULL, NULL, NULL),
(15, 1, 5, 'Returned', '2025-03-06', 1, '2025-03-07', '2025-03-06', 1, NULL, NULL, NULL),
(16, 2, 4, 'Returned', '2025-03-06', 1, '2025-03-06', '2025-03-06', 1, NULL, NULL, NULL),
(17, 1, 5, 'Returned', '2025-03-06', 1, '2025-03-07', '2025-03-06', 1, NULL, NULL, NULL),
(18, 2, 3, 'Returned', '2025-03-06', 1, '2025-03-06', '2025-03-06', 1, NULL, NULL, NULL),
(19, 1, 1, 'Returned', '2025-03-06', 1, '2025-03-13', '2025-03-06', 1, NULL, NULL, NULL),
(20, 1, 4, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(21, 1, 4, 'Returned', '2025-03-09', 1, '2025-03-09', '2025-03-09', 1, NULL, NULL, NULL),
(22, 1, 5, 'Returned', '2025-03-09', 1, '2025-03-10', '2025-03-09', 1, NULL, NULL, NULL),
(23, 1, 1, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(25, 1, 1, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(26, 1, 9, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(27, 1, 13, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(28, 1, 1, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(29, 1, 7, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(30, 1, 14, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(31, 1, 1, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(32, 1, 6, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(33, 1, 12, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(34, 1, 1, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(35, 1, 11, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(36, 1, 1, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(37, 1, 6, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(38, 1, 11, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(39, 1, 1, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(40, 1, 6, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(41, 1, 11, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(42, 1, 1, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(43, 1, 6, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(44, 1, 11, 'Returned', '2025-03-09', 1, '2025-03-16', '2025-03-09', 1, NULL, NULL, NULL),
(45, 1, 1, 'Returned', '2025-03-09', 1, '2025-03-10', '2025-03-09', 1, NULL, NULL, NULL),
(46, 1, 6, 'Returned', '2025-03-09', 1, '2025-03-10', '2025-03-09', 1, NULL, NULL, NULL),
(47, 1, 11, 'Returned', '2025-03-09', 1, '2025-03-09', '2025-03-09', 1, NULL, NULL, NULL),
(48, 1, 6, 'Returned', '2025-03-09', 1, '2025-03-10', '2025-03-09', 1, NULL, NULL, NULL),
(49, 1, 1, 'Returned', '2025-03-09', 1, '2025-03-10', '2025-03-09', 1, NULL, NULL, NULL),
(50, 1, 11, 'Returned', '2025-03-09', 1, '2025-03-09', '2025-03-09', 1, NULL, NULL, NULL),
(51, 1, 1, 'Returned', '2025-03-09', 1, '2025-03-30', '2025-03-09', 1, NULL, NULL, 0),
(52, 1, 6, 'Returned', '2025-03-09', 1, '2025-03-10', '2025-03-09', 1, NULL, NULL, NULL),
(53, 1, 13, 'Returned', '2025-03-09', 1, '2025-03-09', '2025-03-09', 1, NULL, NULL, NULL),
(54, 1, 8, 'Active', '2025-03-09', 1, '2025-03-17', NULL, NULL, NULL, NULL, 0),
(55, 1, 2, 'Active', '2025-03-09', 1, '2025-09-11', NULL, NULL, NULL, NULL, 0),
(56, 1, 13, 'Active', '2025-03-09', 1, '2025-03-09', NULL, NULL, NULL, NULL, NULL);

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
(66, 1, 1, 'Author'),
(67, 2, 1, 'Author'),
(68, 3, 1, 'Author'),
(69, 4, 1, 'Author'),
(70, 5, 1, 'Author'),
(71, 11, 3, 'Author'),
(72, 12, 3, 'Author'),
(73, 13, 3, 'Author'),
(74, 14, 3, 'Author'),
(75, 15, 3, 'Author'),
(76, 6, 2, 'Author'),
(77, 7, 2, 'Author'),
(78, 8, 2, 'Author'),
(79, 9, 2, 'Author'),
(80, 10, 2, 'Author');

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
(1, 1, 'Overdue', 10.00, 'Paid', '2025-03-15', '2025-03-15');

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

-- --------------------------------------------------------

--
-- Table structure for table `publications`
--

CREATE TABLE `publications` (
  `id` int(11) NOT NULL,
  `book_id` int(225) DEFAULT NULL,
  `publisher_id` int(225) DEFAULT NULL,
  `publish_date` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `publications`
--

INSERT INTO `publications` (`id`, `book_id`, `publisher_id`, `publish_date`) VALUES
(1, 1, 2, '2024'),
(2, 2, 2, '2024'),
(3, 3, 2, '2024'),
(4, 4, 2, '2024'),
(5, 5, 2, '2024'),
(6, 6, 3, '2017'),
(7, 7, 3, '2017'),
(8, 8, 3, '2017'),
(9, 9, 3, '2017'),
(10, 10, 3, '2017'),
(11, 11, 4, '2010'),
(12, 12, 4, '2010'),
(13, 13, 4, '2010'),
(14, 14, 4, '2010'),
(15, 15, 4, '2010');

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
(2, 'Keya International Press LLP', 'Jaipur, India'),
(3, 'Chandos Publishing', 'USA'),
(4, 'Business & Arts, Inc.', 'Iloilo, Philippines');

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
(1, 1, 4, '2025-03-08 20:27:01', '2025-03-09 03:30:56', 1, '2025-03-09 03:35:07', 1, NULL, NULL, NULL, '2025-03-09 03:35:07', 'Received'),
(2, 1, 4, '2025-03-08 20:45:25', '2025-03-09 03:45:34', 1, '2025-03-09 03:45:38', 1, NULL, NULL, NULL, '2025-03-09 03:45:38', 'Received'),
(3, 1, 5, '2025-03-08 20:47:03', '2025-03-09 03:47:15', 1, '2025-03-09 03:47:19', 1, NULL, NULL, NULL, '2025-03-09 03:47:19', 'Received'),
(4, 1, 1, '2025-03-08 22:55:59', '2025-03-09 05:56:13', 1, '2025-03-09 05:56:19', 1, NULL, NULL, NULL, '2025-03-09 05:56:19', 'Received'),
(5, 1, 11, '2025-03-08 22:56:03', '2025-03-09 05:56:13', 1, '2025-03-09 05:56:19', 1, NULL, NULL, NULL, '2025-03-09 05:56:19', 'Received'),
(6, 1, 6, '2025-03-08 23:43:28', '2025-03-09 06:43:47', 1, '2025-03-09 06:43:51', 1, NULL, NULL, NULL, '2025-03-09 06:43:51', 'Received'),
(7, 1, 1, '2025-03-08 23:43:31', '2025-03-09 06:43:47', 1, '2025-03-09 06:43:51', 1, NULL, NULL, NULL, '2025-03-09 06:43:51', 'Received'),
(8, 1, 11, '2025-03-08 23:43:33', '2025-03-09 06:43:47', 1, '2025-03-09 06:43:51', 1, NULL, NULL, NULL, '2025-03-09 06:43:51', 'Received');

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
(1, 210078, 'Admin', 'Active Login', '2025-03-06 01:50:42'),
(2, 210078, 'student', 'Active Login', '2025-03-06 02:15:27'),
(3, 210078, 'student', 'Active Login', '2025-03-06 02:21:35'),
(4, 210078, 'student', 'Active Login', '2025-03-06 02:24:46'),
(5, 210078, 'student', 'Active Login', '2025-03-06 02:25:34'),
(6, 210078, 'student', 'Active Login', '2025-03-06 02:26:37'),
(7, 210078, 'student', 'Active Login', '2025-03-06 02:27:48'),
(8, 210078, 'student', 'Active Login', '2025-03-06 02:29:21'),
(9, 210078, 'student', 'Active Login', '2025-03-06 02:30:47'),
(10, 210078, 'Admin', 'Active Login', '2025-03-06 02:31:26'),
(11, 210078, 'student', 'Active Login', '2025-03-06 02:34:38'),
(12, 210078, 'Admin', 'Active Login', '2025-03-06 02:36:14'),
(13, 210078, 'student', 'Active Login', '2025-03-06 02:36:21'),
(14, 210078, 'student', 'Active Login', '2025-03-06 02:39:55'),
(15, 210078, 'Admin', 'Active Login', '2025-03-06 02:41:40'),
(16, 210078, 'Student', 'Active Login', '2025-03-06 02:45:01'),
(17, 210078, 'Admin', 'Active Login', '2025-03-06 02:45:39'),
(18, 210069, 'Student', 'Active Login', '2025-03-06 02:49:58'),
(19, 210078, 'Student', 'Active Login', '2025-03-15 03:12:14'),
(20, 210078, 'Admin', 'Active Login', '2025-03-07 13:13:14'),
(21, 210078, 'Admin', 'Active Login', '2025-03-08 09:42:59'),
(22, 210078, 'Admin', 'Active Login', '2025-03-08 11:13:08'),
(23, 210078, 'Admin', 'Active Login', '2025-03-08 14:31:55'),
(24, 210078, 'Admin', 'Active Login', '2025-03-09 03:26:26'),
(25, 210078, 'Student', 'Active Login', '2025-03-09 03:26:45'),
(26, 210078, 'Admin', 'Active Login', '2025-03-09 03:28:21'),
(27, 210078, 'Student', 'Active Login', '2025-03-09 03:30:23'),
(28, 210078, 'Admin', 'Active Login', '2025-03-09 03:54:49'),
(29, 210078, 'Admin', 'Active Login', '2025-03-09 05:27:28'),
(30, 210078, 'Student', 'Active Login', '2025-03-09 05:55:39'),
(31, 210078, 'Admin', 'Active Login', '2025-03-09 06:17:07'),
(32, 210078, 'Admin', 'Active Login', '2025-03-09 06:36:26'),
(33, 210078, 'Student', 'Active Login', '2025-03-09 06:43:07'),
(34, 210078, 'Admin', 'Active Login', '2025-03-09 13:46:32'),
(35, 210078, 'Admin', 'Active Login', '2025-03-11 01:31:44'),
(36, 210078, 'Student', 'Active Login', '2025-03-11 01:31:59');

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
(1, 210078, 'Kenneth', 'P', 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$NEG2OTMfLQSGcoKbGvj1/u/NUagvasGN0C1WWK1/ErlZ41qI.4mt2', '09702582474', 44, 41, 0, 0, '../Images/Profile/default-avatar.jpg', 'Student', '', '', '/upload/default-id.png', '2025-03-06', '1', '2025-03-09'),
(2, 210069, 'Cayce', NULL, 'Evangelista', 'cevans@student.nbscollege.edu.ph', '$2y$10$ULOcdeaIOY8zf9H7N9pzq.LGg.6VwxSVxK8DInBCqsW1HrmRapwnO', '', 4, 4, 0, 0, '../Images/Profile/default-avatar.jpg', 'Student', '', '', '/upload/default-id.png', '2025-03-06', '1', '2025-03-06');

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
(1, 'Ravi', '', 'Sharma'),
(2, 'Marie Keen', '', 'Shaw'),
(3, 'Corazon', 'Pineda', 'Kabayao');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contributors`
--
ALTER TABLE `contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publications`
--
ALTER TABLE `publications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
