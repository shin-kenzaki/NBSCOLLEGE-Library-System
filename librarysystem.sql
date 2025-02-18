-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2025 at 04:38 AM
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

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `id` int(225) NOT NULL,
  `user_id` int(225) NOT NULL,
  `book_id` int(225) NOT NULL,
  `status` varchar(100) NOT NULL,
  `borrow_date` date NOT NULL,
  `allowed_days` int(225) NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date NOT NULL
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
(1, 3, 2, 'Author'),
(2, 2, 2, 'Author');

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
(1, 3, 1, '2017'),
(2, 2, 1, '2017');

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
(1, 'Rowman & Littlefield', 'USA'),
(2, 'Chandos Publishing', 'USA');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(225) NOT NULL,
  `user_id` int(225) NOT NULL,
  `book_id` int(225) NOT NULL,
  `reserve_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cancel_date` timestamp NULL DEFAULT NULL,
  `recieved_date` timestamp NULL DEFAULT NULL,
  `status` boolean NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(48, 210078, 'Admin', 'Active login', '2025-02-17 00:57:00');

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
  `user_image` varchar(100) DEFAULT NULL,
  `usertype` varchar(100) DEFAULT NULL,
  `borrowed_books` int(225) DEFAULT NULL,
  `returned_books` int(225) DEFAULT NULL,
  `damaged_books` int(11) DEFAULT NULL,
  `lost_books` int(225) DEFAULT NULL,
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

INSERT INTO `users` (`id`, `school_id`, `firstname`, `middle_init`, `lastname`, `email`, `password`, `contact_no`, `user_image`, `usertype`, `borrowed_books`, `returned_books`, `damaged_books`, `lost_books`, `address`, `id_type`, `id_image`, `date_added`, `status`, `last_update`) VALUES
(1, 210078, 'Kenneth Laurence', 'P', 'Bonaagua', 'kbonssgus2021@student.nbscollege.edu.ph', '$2y$10$6.WegXnf4TMXe5R9VvVem.QH/2mARd5bhEyab.ONCqnQ.7Pls9fEu', '', '../Admin/inc/upload/default-avatar.jpg', 'student', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-02-15', '0', '2025-02-16');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contributors`
--
ALTER TABLE `contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
