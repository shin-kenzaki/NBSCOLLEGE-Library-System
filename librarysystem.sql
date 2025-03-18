-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 18, 2025 at 02:45 AM
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
(1, 210078, 'Kenneth Laurence', 'P.', 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$qrUCNikI0TEGRX9jdPN7PeYAbclaTaLoc/2/7xGNMxcV5EwNvszCy', '../Images/Profile/default-avatar.jpg', 'Admin', '2025-02-15', '1', '2025-03-08', NULL, NULL);

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
(7, 27, 'Classification and Cataloging', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 2, '', '', '', 'Text', 'Print', 'Book', 'FIL TR  c1 2017 c2', '', 'English', 'FIL', 1, '2025-03-09', 'Borrowed', 1, '2025-03-09'),
(8, 28, 'Classification and Cataloging', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 3, '', '', '', 'Text', 'Print', 'Book', 'FIL TR  c1 2017 c3', '', 'English', 'FIL', 1, '2025-03-09', 'Available', 1, '2025-03-09'),
(9, 29, 'Classification and Cataloging', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 4, '', '', '', 'Text', 'Print', 'Book', 'FIL TR  c1 2017 c4', '', 'English', 'FIL', 1, '2025-03-09', 'Available', 1, '2025-03-09'),
(10, 30, 'Classification and Cataloging', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 5, '', '', '', 'Text', 'Print', 'Book', 'FIL TR  c1 2017 c5', '', 'English', 'FIL', 1, '2025-03-09', 'Available', 1, '2025-03-09'),
(11, 5986, 'The Colors of Lucban', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 1, '', '', '', 'Text', 'Print', 'Book', 'REF ASD WASD 2010 c1', '', 'English', 'REF', 1, '2025-03-09', 'Available', 1, '2025-03-11'),
(12, 5987, 'The Colors of Lucban', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 2, '', '', '', 'Text', 'Print', 'Book', 'REF ASD WASD 2010 c2', '', 'English', 'REF', 1, '2025-03-09', 'Available', 1, '2025-03-11'),
(13, 5988, 'The Colors of Lucban', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 3, '', '', '', 'Text', 'Print', 'Book', 'REF ASD WASD 2010 c3', '', 'English', 'REF', 1, '2025-03-09', 'Borrowed', 1, '2025-03-11'),
(14, 5989, 'The Colors of Lucban', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 4, '', '', '', 'Text', 'Print', 'Book', 'REF ASD WASD 2010 c4', '', 'English', 'REF', 1, '2025-03-09', 'Available', 1, '2025-03-11'),
(15, 5990, 'The Colors of Lucban', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 5, '', '', '', 'Text', 'Print', 'Book', 'REF ASD WASD 2010 c5', '', 'English', 'REF', 1, '2025-03-09', 'Available', 1, '2025-03-11');

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
(5, 31, 2, 'Active', '2025-03-18', 1, '2025-03-19', NULL, NULL, NULL, NULL, NULL),
(6, 31, 7, 'Active', '2025-03-18', 1, '2025-03-25', NULL, NULL, NULL, NULL, NULL),
(7, 31, 13, 'Active', '2025-03-18', 1, '2025-03-18', NULL, NULL, NULL, NULL, NULL);

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
(76, 6, 2, 'Author'),
(77, 7, 2, 'Author'),
(78, 8, 2, 'Author'),
(79, 9, 2, 'Author'),
(80, 10, 2, 'Author'),
(81, 11, 3, 'Author'),
(82, 12, 3, 'Author'),
(83, 13, 3, 'Author'),
(84, 14, 3, 'Author'),
(85, 15, 3, 'Author');

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

-- --------------------------------------------------------

--
-- Table structure for table `updates`
--

CREATE TABLE `updates` (
  `id` int(225) NOT NULL,
  `user_id` int(225) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `message` varchar(999) NOT NULL,
  `update` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `updates`
--

INSERT INTO `updates` (`id`, `user_id`, `role`, `title`, `message`, `update`) VALUES
(83, 210078, 'Admin', 'Kenneth Laurence Bonaagua Registered an Admin', 'Kenneth Laurence Bonaagua Registered asdasd asdasda as Encoder', '2025-03-17 10:16:52'),
(84, 210078, 'Admin', 'Kenneth Laurence Bonaagua Registered an Admin', 'Kenneth Laurence Bonaagua Registered sdasd asdasdas as Admin', '2025-03-17 10:18:50'),
(85, 210078, 'Admin', 'Kenneth Laurence Bonaagua Registered an Admin', 'Kenneth Laurence Bonaagua Registered sdasd asdasdas as Admin', '2025-03-17 10:18:58'),
(86, 210078, 'Admin', 'Kenneth Laurence Bonaagua Registered an Admin', 'Kenneth Laurence Bonaagua Registered asdasd asdasda as Admin', '2025-03-17 10:19:19'),
(87, 210078, 'Admin', 'Kenneth Laurence Bonaagua Registered an Admin', 'Kenneth Laurence Bonaagua Registered asdasd asdasdas as Admin', '2025-03-17 10:19:48'),
(88, 210078, 'Admin', 'Kenneth Laurence Bonaagua Registered an Admin', 'Kenneth Laurence Bonaagua Registered asdasd asdasd as Librarian', '2025-03-17 10:20:59'),
(89, 210078, 'Admin', 'Kenneth Laurence Bonaagua Registered an Admin', 'Kenneth Laurence Bonaagua Registered asdasda asdasd as Admin', '2025-03-17 10:21:34'),
(90, 210078, 'Admin', 'Kenneth Laurence Bonaagua Registered an Admin', 'Kenneth Laurence Bonaagua Registered asdasd adasdad as Admin', '2025-03-17 10:33:07'),
(91, 210078, 'Admin', 'Kenneth Laurence Bonaagua Registered an Admin', 'Kenneth Laurence Bonaagua Registered sadasd adsad as Admin', '2025-03-17 10:47:58'),
(92, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-03-17 15:07:23'),
(93, 1, 'Admin', 'Admin   Registered an Admin', 'Admin   Registered Cayce Evangelista as Assistant', '2025-03-17 15:08:08'),
(94, 1, 'Admin', 'Admin   Registered an Admin', 'Admin   Registered asd asd ads as Librarian', '2025-03-17 15:09:11'),
(95, 1, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered an Admin', 'Admin Kenneth Laurence Bonaagua Registered asdas asda as Librarian', '2025-03-17 15:10:54'),
(96, 1, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered asdas asd asda as Faculty', '2025-03-17 15:11:54'),
(97, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered an Admin', 'Admin Kenneth Laurence Bonaagua Registered sadasd asdasd as Librarian', '2025-03-17 15:34:09'),
(98, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Deactivated an Admin', 'Admin Kenneth Laurence Bonaagua Deactivated Librarian sadasd asdasd', '2025-03-17 15:34:18'),
(99, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Activated an Admin', 'Admin Kenneth Laurence Bonaagua Activated Librarian sadasd asdasd', '2025-03-17 15:34:42'),
(100, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Deleted an Admin', 'Admin Kenneth Laurence Bonaagua Deleted Librarian sadasd asdasd', '2025-03-17 15:41:37'),
(101, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered sadas adasd as Faculty', '2025-03-17 15:45:06'),
(102, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered asd asdasd as Faculty', '2025-03-17 15:45:20'),
(103, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered dasdasd asdas as Faculty', '2025-03-17 15:45:37'),
(104, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered asdad asdasd as Faculty', '2025-03-17 15:47:57'),
(105, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered sad asdasd as Faculty', '2025-03-17 15:53:48'),
(106, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered sadas asdads as Faculty', '2025-03-17 15:54:05'),
(107, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered asdas asdas as Faculty', '2025-03-17 15:54:25'),
(108, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Deleted a User', 'Admin Kenneth Laurence Bonaagua Deleted Faculty sad asdasd', '2025-03-17 15:54:30'),
(109, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Deleted a User', 'Admin Kenneth Laurence Bonaagua Deleted Faculty sadas asdads', '2025-03-17 15:54:30'),
(110, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Deleted a User', 'Admin Kenneth Laurence Bonaagua Deleted Faculty asdas asdas', '2025-03-17 15:54:30'),
(111, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered sad asd as Faculty', '2025-03-17 15:54:56'),
(112, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered sadasd asdas as Student', '2025-03-17 15:56:35'),
(113, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Deleted a User', 'Admin Kenneth Laurence Bonaagua Deleted Student sadasd asdas', '2025-03-17 15:56:48'),
(114, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered Kenneth Bonaagua as Faculty', '2025-03-17 15:58:34'),
(115, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Deleted a User', 'Admin Kenneth Laurence Bonaagua Deleted Faculty Kenneth Bonaagua', '2025-03-17 16:02:32'),
(116, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered asdsa sadasd as Faculty', '2025-03-17 16:12:59'),
(117, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Deleted a User', 'Admin Kenneth Laurence Bonaagua Deleted Faculty asdsa sadasd', '2025-03-17 16:13:21'),
(118, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-03-18 01:09:37'),
(119, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered Kenneth Laurence Bonaagua as Student', '2025-03-18 01:10:20'),
(120, 210078, 'Student', 'User Logged In', 'Student Kenneth Laurence Bonaagua Logged In as Active', '2025-03-18 01:11:13');

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
(31, 210078, 'Kenneth Laurence', NULL, 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$/Mp6RZAsEWMWNzTCNM.MLeF8e1dgWY2elORlDaJ3o57bB.zqrBJuu', '', 3, 0, 0, 0, '../Images/Profile/default-avatar.jpg', 'Student', '', '', '/upload/default-id.png', '2025-03-18', '1', '2025-03-18');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contributors`
--
ALTER TABLE `contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publications`
--
ALTER TABLE `publications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
