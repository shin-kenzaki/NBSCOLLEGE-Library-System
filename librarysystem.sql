-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 19, 2025 at 08:59 AM
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
  `last_update` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `employee_id`, `firstname`, `middle_init`, `lastname`, `email`, `password`, `image`, `role`, `date_added`, `status`, `last_update`) VALUES
(1, 210078, 'Kenneth Laurence', 'P.', 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$NKjOxPvbSdsey678OD.ekOhtr8phWH5mL5RemGUekdNxUV9GVf0Ay', 'inc/upload/default-avatar.jpg', 'Admin', '2025-02-15', NULL, NULL),
(2, 210069, 'Cayce', '', 'Eangelista', 'cevangelista@student.nbscollege.edu.ph', '$2y$10$f6HIGgK8THWN/E4VtrMFB.NjE58QGXPNks/V15ZCiboLZZZ/xpXcW', 'inc/upload/default-avatar.jpg', 'Librarian', '2025-02-15', '0', '2025-02-16');

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
  `subject_specification` varchar(100) DEFAULT NULL,
  `subject_detail` varchar(9999) DEFAULT NULL,
  `summary` varchar(1000) DEFAULT NULL,
  `contents` varchar(1000) DEFAULT NULL,
  `front_image` varchar(100) DEFAULT NULL,
  `back_image` varchar(100) DEFAULT NULL,
  `height` varchar(100) DEFAULT NULL,
  `width` varchar(100) DEFAULT NULL,
  `series` varchar(100) DEFAULT NULL,
  `volume` varchar(100) DEFAULT NULL,
  `edition` varchar(100) DEFAULT NULL,
  `copy_number` int(225) DEFAULT NULL,
  `total_pages` varchar(100) DEFAULT NULL,
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
  `last_update` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `accession`, `title`, `preferred_title`, `parallel_title`, `subject_category`, `subject_specification`, `subject_detail`, `summary`, `contents`, `front_image`, `back_image`, `height`, `width`, `series`, `volume`, `edition`, `copy_number`, `total_pages`, `ISBN`, `content_type`, `media_type`, `carrier_type`, `call_number`, `URL`, `language`, `shelf_location`, `entered_by`, `date_added`, `status`, `last_update`) VALUES
(1, 2867, 'Cataloging Library Resources', '', '', 'Topical', 'Social Sciences', '', '', '', '', '', '', '', '', '', '', 1, '', '1658947', 'Text', 'Print', 'Book', '', 'https://www.amazon.com/Cataloging-Library-Resources-Introduction-Handbooks/dp/1538186772', 'English', 'REF', 210078, '2025-02-17', 'Available', '2025-02-17'),
(2, 2868, 'Cataloging Library Resources', '', '', 'Topical', 'Social Sciences', '', '', '', '', '', '', '', '', '', '', 2, '', '9781442274853', 'Text', 'Print', 'Book', '', 'https://www.amazon.com/Cataloging-Library-Resources-Introduction-Handbooks/dp/1538186772', 'English', 'REF', 210078, '2025-02-17', 'Available', '2025-02-17'),
(3, 2870, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH, and MARC 21 Standards', '', '', 'Topical', 'Library Sciences', '', '', '', '', '', '', '', '', '', '', 1, '', '165984', 'Text', 'Print', 'Book', '', '', 'English', 'REF', 210078, '2025-02-17', 'Available', '2025-02-17');

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `id` int(225) NOT NULL,
  `user_id` int(225) DEFAULT NULL,
  `book_id` int(225) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `borrow_date` timestamp NULL DEFAULT NULL,
  `allowed_days` int(225) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `replacement_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowings`
--

INSERT INTO `borrowings` (`id`, `user_id`, `book_id`, `status`, `borrow_date`, `allowed_days`, `due_date`, `return_date`, `report_date`, `replacement_date`) VALUES
(1, 1, 1, 'Returned', '2025-02-19 07:18:00', 7, '2025-02-26', '2025-02-19', NULL, NULL),
(2, 1, 3, 'Returned', '2025-02-19 07:18:02', 7, '2025-02-26', '2025-02-19', NULL, NULL),
(3, 1, 1, 'Returned', '2025-02-19 07:20:45', 7, '2025-02-26', '2025-02-19', NULL, NULL),
(4, 1, 3, 'Returned', '2025-02-19 07:20:49', 7, '2025-02-26', '2025-02-19', NULL, NULL);

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
(1, 3, 1, 'Author'),
(2, 2, 2, 'Author'),
(3, 1, 2, 'Author');

-- --------------------------------------------------------

--
-- Table structure for table `fines`
--

CREATE TABLE `fines` (
  `id` int(11) NOT NULL,
  `borrowing_id` int(225) NOT NULL,
  `type` varchar(100) NOT NULL,
  `amount` decimal(65,2) NOT NULL,
  `status` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `payment_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 3, 1, '2015'),
(2, 2, 2, '2017'),
(3, 1, 2, '2017');

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
(2, 'Rowman & Littlefield', 'USA');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(225) NOT NULL,
  `user_id` int(225) DEFAULT NULL,
  `book_id` int(225) DEFAULT NULL,
  `reserve_date` timestamp NULL DEFAULT NULL,
  `cancel_date` timestamp NULL DEFAULT NULL,
  `recieved_date` timestamp NULL DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `book_id`, `reserve_date`, `cancel_date`, `recieved_date`, `status`) VALUES
(1, 1, 1, '2025-02-19 00:17:31', NULL, '2025-02-19 07:18:00', 'Received'),
(2, 1, 3, '2025-02-19 00:17:33', NULL, '2025-02-19 07:18:02', 'Received'),
(3, 1, 1, '2025-02-19 00:20:14', NULL, '2025-02-19 07:20:45', 'Received'),
(4, 1, 3, '2025-02-19 00:20:20', NULL, '2025-02-19 07:20:49', 'Received');

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
(1, 210069, 'Encoder', 'Inactive login', '2025-02-15 11:32:28'),
(2, 210069, 'Encoder', 'Inactive login', '2025-02-15 11:32:51'),
(3, 210069, 'Encoder', 'Active login', '2025-02-15 11:34:15'),
(4, 210078, 'Admin', 'Active login', '2025-02-15 11:38:43'),
(5, 210078, 'student', 'Inactive Login', '2025-02-15 11:41:39'),
(6, 210078, 'student', 'Inactive Login', '2025-02-15 15:40:37'),
(7, 210078, 'Admin', 'Active login', '2025-02-15 15:48:12'),
(8, 210078, 'Admin', 'Active login', '2025-02-15 15:49:00'),
(9, 210078, 'Admin', 'Active login', '2025-02-15 15:52:51'),
(10, 210069, 'Librarian', 'Active login', '2025-02-15 15:53:15'),
(11, 210078, 'Admin', 'Active login', '2025-02-15 15:54:57'),
(12, 210078, 'Admin', 'Active login', '2025-02-15 15:55:43'),
(13, 210069, 'Encoder', 'Active login', '2025-02-15 16:05:13'),
(14, 210078, 'Admin', 'Active login', '2025-02-15 16:05:27'),
(15, 210078, 'Admin', 'Active login', '2025-02-15 16:14:18'),
(16, 210069, 'Encoder', 'Active login', '2025-02-15 16:14:52'),
(17, 210069, 'Encoder', 'Active login', '2025-02-15 16:18:51'),
(18, 210078, 'Admin', 'Active login', '2025-02-15 16:35:12'),
(19, 210069, 'Librarian', 'Active login', '2025-02-15 16:37:18'),
(20, 210078, 'Admin', 'Active login', '2025-02-15 16:37:41'),
(21, 210069, 'Librarian', 'Active login', '2025-02-15 16:39:02'),
(22, 210078, 'Admin', 'Active login', '2025-02-15 16:39:48'),
(23, 210069, 'Librarian', 'Active login', '2025-02-15 16:41:54'),
(24, 210078, 'Admin', 'Active login', '2025-02-15 16:42:16'),
(25, 210069, 'Librarian', 'Active login', '2025-02-15 16:44:41'),
(26, 210078, 'Admin', 'Active login', '2025-02-15 16:44:53'),
(27, 210069, 'Librarian', 'Active login', '2025-02-15 16:47:54'),
(28, 210078, 'Admin', 'Active login', '2025-02-15 16:48:01'),
(29, 210078, 'Admin', 'Active login', '2025-02-16 04:03:02'),
(30, 210069, 'Librarian', 'Active login', '2025-02-16 04:03:11'),
(31, 210069, 'Librarian', 'Active login', '2025-02-16 04:03:29'),
(32, 210078, 'Admin', 'Active login', '2025-02-16 04:03:48'),
(33, 210069, 'Encoder', 'Active login', '2025-02-16 04:04:18'),
(34, 210069, 'Encoder', 'Active login', '2025-02-16 04:04:34'),
(35, 210069, 'Encoder', 'Active login', '2025-02-16 04:04:41'),
(36, 210078, 'Admin', 'Active login', '2025-02-16 04:04:52'),
(37, 210069, 'Encoder', 'Active login', '2025-02-16 04:05:31'),
(38, 210069, 'Encoder', 'Active login', '2025-02-16 04:05:57'),
(39, 210078, 'Admin', 'Active login', '2025-02-16 04:07:54'),
(40, 210069, 'Librarian', 'Active login', '2025-02-16 04:08:15'),
(41, 210078, 'student', 'Inactive Login', '2025-02-16 04:08:41'),
(42, 210078, 'student', 'Inactive Login', '2025-02-16 04:12:53'),
(43, 210078, 'Admin', 'Active login', '2025-02-16 04:13:06'),
(44, 210069, 'Librarian', 'Active login', '2025-02-16 04:14:24'),
(45, 210078, 'Admin', 'Active login', '2025-02-16 11:08:20'),
(46, 210078, 'Admin', 'Active login', '2025-02-16 11:19:01'),
(47, 210078, 'Admin', 'Active login', '2025-02-16 11:21:50'),
(48, 210078, 'Admin', 'Active login', '2025-02-17 00:57:00'),
(49, 210078, 'Admin', 'Active login', '2025-02-17 03:46:23'),
(50, 210078, 'Admin', 'Active login', '2025-02-17 05:01:10'),
(51, 210078, 'Admin', 'Active login', '2025-02-17 05:49:47'),
(52, 210078, 'Admin', 'Active login', '2025-02-17 06:26:20'),
(53, 210078, 'student', 'Inactive Login', '2025-02-17 07:40:45'),
(54, 210078, 'student', 'Inactive Login', '2025-02-17 07:49:03'),
(55, 210078, 'student', 'Inactive login', '2025-02-17 07:53:17'),
(56, 210078, 'student', 'Inactive Login', '2025-02-17 08:02:17'),
(57, 210078, 'student', 'Inactive Login', '2025-02-17 08:04:47'),
(58, 210078, 'student', 'Inactive Login', '2025-02-17 08:06:18'),
(59, 210078, 'Admin', 'Active login', '2025-02-17 08:06:55'),
(60, 210078, 'Admin', 'Active login', '2025-02-17 08:46:02'),
(61, 210078, 'Admin', 'Active login', '2025-02-17 08:48:57'),
(62, 210078, 'Admin', 'Active login', '2025-02-17 08:52:57'),
(63, 210078, 'student', 'Inactive Login', '2025-02-17 08:55:51'),
(64, 210078, 'student', 'Inactive Login', '2025-02-17 08:58:01'),
(65, 210078, 'student', 'Inactive Login', '2025-02-17 08:58:52'),
(66, 210078, 'student', 'Inactive Login', '2025-02-17 09:04:46'),
(67, 210078, 'Admin', 'Active login', '2025-02-17 09:30:16'),
(68, 210078, 'student', 'Inactive Login', '2025-02-17 09:42:18'),
(69, 210078, 'student', 'Inactive Login', '2025-02-17 09:43:04'),
(70, 210078, 'Admin', 'Active login', '2025-02-17 09:47:09'),
(71, 210078, 'student', 'Inactive Login', '2025-02-17 11:01:29'),
(72, 210078, 'Admin', 'Active login', '2025-02-17 11:01:46'),
(73, 210078, 'Admin', 'Active login', '2025-02-17 17:30:09'),
(74, 210078, 'student', 'Inactive Login', '2025-02-17 17:30:15'),
(75, 210078, 'Admin', 'Active login', '2025-02-18 03:32:20'),
(76, 210078, 'student', 'Inactive Login', '2025-02-18 03:32:38'),
(77, 210078, 'student', 'Inactive Login', '2025-02-18 04:32:44'),
(78, 210078, 'Admin', 'Active login', '2025-02-18 07:30:13'),
(79, 210078, 'Admin', 'Active login', '2025-02-18 07:36:39'),
(80, 210078, 'student', 'Inactive Login', '2025-02-18 07:36:52'),
(81, 210078, 'Admin', 'Active login', '2025-02-18 08:56:29'),
(82, 210078, 'Admin', 'Active login', '2025-02-18 09:52:15'),
(83, 210078, 'student', 'Inactive Login', '2025-02-18 10:45:01'),
(84, 210078, 'student', 'Inactive Login', '2025-02-18 10:48:40'),
(85, 210078, 'Admin', 'Active login', '2025-02-18 10:49:10'),
(86, 210078, 'student', 'Inactive Login', '2025-02-18 14:17:56'),
(87, 210078, 'Admin', 'Active login', '2025-02-18 14:18:04'),
(88, 210078, 'Admin', 'Active login', '2025-02-18 15:08:20'),
(89, 210078, 'student', 'Inactive Login', '2025-02-18 15:11:12'),
(90, 210078, 'Admin', 'Active login', '2025-02-18 15:13:30'),
(91, 210078, 'student', 'Inactive Login', '2025-02-18 15:20:25'),
(92, 210078, 'Admin', 'Active login', '2025-02-18 15:23:06'),
(93, 210078, 'student', 'Inactive Login', '2025-02-18 15:23:40'),
(94, 210078, 'Admin', 'Active login', '2025-02-18 15:26:40'),
(95, 210069, 'student', 'Inactive Login', '2025-02-19 01:13:21'),
(96, 210069, 'student', 'Inactive Login', '2025-02-19 01:19:26'),
(97, 210068, 'student', 'Inactive Login', '2025-02-19 01:19:44'),
(98, 210078, 'Admin', 'Active login', '2025-02-19 03:01:23'),
(99, 210078, 'student', 'Inactive Login', '2025-02-19 03:01:57'),
(100, 210078, 'Admin', 'Active login', '2025-02-19 03:02:32'),
(101, 210078, 'student', 'Inactive Login', '2025-02-19 03:03:06'),
(102, 210068, 'student', 'Inactive Login', '2025-02-19 03:13:01'),
(103, 210078, 'Admin', 'Active login', '2025-02-19 06:29:07'),
(104, 210078, 'student', 'Inactive Login', '2025-02-19 06:29:17'),
(105, 210078, 'Admin', 'Active login', '2025-02-19 06:30:33'),
(106, 210078, 'student', 'Inactive Login', '2025-02-19 06:31:03'),
(107, 210078, 'Admin', 'Active login', '2025-02-19 06:31:10');

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
(1, 210078, 'Kenneth Laurence', 'P', 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$6.WegXnf4TMXe5R9VvVem.QH/2mARd5bhEyab.ONCqnQ.7Pls9fEu', '', 17, 9, 3, 5, '../Admin/inc/upload/default-avatar.jpg', 'student', NULL, NULL, NULL, '2025-02-15', '0', '2025-02-17'),
(2, 210069, 'Cayce', '', 'Evangelista', 'cevangelista@student.nbscollege.edu.ph', '$2y$10$ynJw.hoXDLCxmKebyCahV.QYKNQhWsoXttgia7.2u7.ovXaZ0lH9e', NULL, 0, 0, 0, 0, '../Admin/inc/upload/default-avatar.jpg', 'student', NULL, NULL, NULL, '2025-02-19', NULL, NULL),
(3, 210068, 'Shin', '', 'Kenzaki', 'skenzaki@student.nbscollege.edu.ph', '$2y$10$8NT275sba9GTn2RsDxYhGuAgKKprjlYjLrjY2Pc.a7lgqmtPkKdHS', NULL, 0, 0, 0, 0, '../Admin/inc/upload/default-avatar.jpg', 'student', NULL, NULL, NULL, '2025-02-19', NULL, NULL);

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
(2, 'Marie Keen', '', 'Shaw');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contributors`
--
ALTER TABLE `contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publications`
--
ALTER TABLE `publications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
