-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 21, 2025 at 03:04 PM
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

INSERT INTO `books` (`id`, `accession`, `title`, `preferred_title`, `parallel_title`, `subject_category`, `subject_detail`, `summary`, `contents`, `front_image`, `back_image`, `dimension`, `series`, `volume`, `edition`, `copy_number`, `total_pages`, `ISBN`, `content_type`, `media_type`, `carrier_type`, `call_number`, `URL`, `language`, `shelf_location`, `entered_by`, `date_added`, `status`, `last_update`) VALUES
(1, 2870, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 1, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(2, 2871, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 2, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(3, 2872, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 3, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(4, 2873, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 4, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(5, 2874, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 5, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(6, 4879, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 6, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(7, 4880, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 7, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(8, 4881, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 8, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(9, 5689, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 9, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(10, 5690, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 10, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(11, 5691, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 11, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(12, 5692, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 12, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(13, 5693, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 13, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(14, 5694, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 14, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21'),
(15, 5695, 'Cataloguing and Classification: An introduction to AACR2, RDA, DDC, LCC, LCSH and MARC21 Standards', '', '', 'Topical', '', '', '', '', '', '', '', '', '', 15, '241', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 210078, '2025-02-21', 'Available', '2025-02-21');

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
-- Table structure for table `publications`
--

CREATE TABLE `publications` (
  `id` int(11) NOT NULL,
  `book_id` int(225) NOT NULL,
  `publisher_id` int(225) NOT NULL,
  `publish_date` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publishers`
--

CREATE TABLE `publishers` (
  `id` int(11) NOT NULL,
  `publisher` varchar(100) NOT NULL,
  `place` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 210078, 'Kenneth Laurence', 'P', 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$6.WegXnf4TMXe5R9VvVem.QH/2mARd5bhEyab.ONCqnQ.7Pls9fEu', '', 26, 16, 3, 6, '../Admin/inc/upload/default-avatar.jpg', 'student', NULL, NULL, NULL, '2025-02-15', '0', '2025-02-17'),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contributors`
--
ALTER TABLE `contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publications`
--
ALTER TABLE `publications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
