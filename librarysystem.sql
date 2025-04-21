-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2025 at 06:05 AM
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
(1, 210078, 'Kenneth Laurence', 'P.', 'Bonaagua', 'kbonaagua2021@student.nbscollge.edu.ph', '$2y$10$mpErS9zZW9HNndG2ivb8OOSIZg6gettXoePCuZ5xH5KMRxFTQnKGW', '../Images/Profile/default-avatar.jpg', 'Admin', '2025-04-14', NULL, NULL, NULL, NULL);

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
  `program` varchar(50) DEFAULT NULL,
  `subject_detail` varchar(9999) DEFAULT NULL,
  `summary` varchar(1000) DEFAULT NULL,
  `contents` varchar(1000) DEFAULT NULL,
  `front_image` varchar(100) DEFAULT NULL,
  `back_image` varchar(100) DEFAULT NULL,
  `dimension` varchar(100) DEFAULT NULL,
  `series` varchar(100) DEFAULT NULL,
  `volume` varchar(100) DEFAULT NULL,
  `part` varchar(20) DEFAULT NULL,
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

INSERT INTO `books` (`id`, `accession`, `title`, `preferred_title`, `parallel_title`, `subject_category`, `program`, `subject_detail`, `summary`, `contents`, `front_image`, `back_image`, `dimension`, `series`, `volume`, `part`, `edition`, `copy_number`, `total_pages`, `supplementary_contents`, `ISBN`, `content_type`, `media_type`, `carrier_type`, `call_number`, `URL`, `language`, `shelf_location`, `entered_by`, `date_added`, `status`, `updated_by`, `last_update`) VALUES
(1, 1245, 'Cebu: Pride of Place', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '1st', 1, '', '', '9789719396109', 'Text', 'Print', 'Book', 'FIL DG651 C38 2007 c.1', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(2, 1246, 'Cebu: Pride of Place', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '1st', 2, '', '', '9789719396109', 'Text', 'Print', 'Book', 'FIL DG651 C38 2007 c.2', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(3, 1247, 'Cebu: Pride of Place', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '1st', 3, '', '', '9789719396109', 'Text', 'Print', 'Book', 'FIL DG651 C38 2007 c.3', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(4, 1548, 'Colors of Lucban', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '', 1, '', '', '9789719472810', 'Text', 'Print', 'Book', 'FIL DS689.L4 K111 2010 c.1', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(5, 1549, 'Colors of Lucban', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '', 2, '', '', '9789719472810', 'Text', 'Print', 'Book', 'FIL DS689.L4 K111 2010 c.2', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(6, 1550, 'Colors of Lucban', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '', 3, '', '', '9789719472810', 'Text', 'Print', 'Book', 'FIL DS689.L4 K111 2010 c.3', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(7, 3214, 'Malacañang Palace: The Official Illustrated History', '', '', '', NULL, '', '', '', NULL, NULL, '', '', '', '', '', 1, '', '', '', 'Text', 'Print', 'Book', 'FIL DG651 C38 2005 c.1', '', 'English', 'FIL', 1, '2025-04-14', 'Available', NULL, '2025-04-14'),
(8, 3215, 'Malacañang Palace: The Official Illustrated History', '', '', '', NULL, '', '', '', NULL, NULL, '', '', '', '', '', 2, '', '', '', 'Text', 'Print', 'Book', 'FIL DG651 C38 2005 c.2', '', 'English', 'FIL', 1, '2025-04-14', 'Available', NULL, '2025-04-14'),
(9, 3216, 'Malacañang Palace: The Official Illustrated History', '', '', '', NULL, '', '', '', NULL, NULL, '', '', '', '', '', 3, '', '', '', 'Text', 'Print', 'Book', 'FIL DG651 C38 2005 c.3', '', 'English', 'FIL', 1, '2025-04-14', 'Available', NULL, '2025-04-14'),
(10, 4587, 'A Nation on Fire: The Unmaking of Joseph Ejercito Estrada and the Remaking of Democracy in the Phili', '', '', '', '', '', '', '', NULL, NULL, '', '', '', '', '', 1, '', '', '', 'Text', 'Print', 'Book', 'FIL DS686.616 E87 c2002 c.1', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(11, 4588, 'A Nation on Fire: The Unmaking of Joseph Ejercito Estrada and the Remaking of Democracy in the Phili', '', '', '', '', '', '', '', NULL, NULL, '', '', '', '', '', 2, '', '', '', 'Text', 'Print', 'Book', 'FIL DS686.616 E87 c2002 c.2', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(12, 4589, 'A Nation on Fire: The Unmaking of Joseph Ejercito Estrada and the Remaking of Democracy in the Phili', '', '', '', '', '', '', '', NULL, NULL, '', '', '', '', '', 3, '', '', '', 'Text', 'Print', 'Book', 'FIL DS686.616 E87 c2002 c.3', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(13, 5684, 'The Bohol We Love: An Anthology of Memoirs', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '', 1, '', '', '', 'Text', 'Print', 'Book', 'TR DS688 B64 2017 c.1', '', 'English', 'TR', 1, '2025-04-14', 'Borrowed', NULL, '2025-04-14'),
(14, 5685, 'The Bohol We Love: An Anthology of Memoirs', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '', 2, '', '', '', 'Text', 'Print', 'Book', 'TR DS688 B64 2017 c.2', '', 'English', 'TR', 1, '2025-04-14', 'Available', NULL, '2025-04-14'),
(15, 5686, 'The Bohol We Love: An Anthology of Memoirs', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '', 3, '', '', '', 'Text', 'Print', 'Book', 'TR DS688 B64 2017 c.3', '', 'English', 'TR', 1, '2025-04-14', 'Available', NULL, '2025-04-14'),
(69, 2654, '26584', '', '', 'Geographical', 'Tourism Management', 'wasd wasd wasd wasd', '', '', NULL, NULL, '', '', '1', '1', '', 1, 'xiii 265', 'includes Appendix', '5698487565', 'Text', 'Print', 'Book', 'TR M45.12 L23 c2000 v.1 pt.1 c.1', '', 'English', 'TR', 1, '2025-04-19', 'Available', 1, '2025-04-20'),
(70, 2655, '26584', '', '', 'Geographical', 'Tourism Management', 'wasd wasd wasd wasd', '', '', NULL, NULL, '', '', '1', '1', '', 2, 'xiii 265', 'includes Appendix', '5698487565', 'Text', 'Print', 'Book', 'TR M45.12 L23 c2000 v.1 pt.1 c.2', '', 'English', 'TR', 1, '2025-04-19', 'Available', 1, '2025-04-20');

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
(1, 1, 1, 'Returned', '2025-04-21', 1, '2025-04-28', '2025-04-20', 1, NULL, NULL, 0),
(2, 1, 4, 'Lost', '2025-04-21', 1, '2025-04-28', NULL, NULL, '2025-04-20', '2025-04-20', 0),
(3, 1, 69, 'Damaged', '2025-04-21', 1, '2025-04-28', NULL, NULL, '2025-04-20', '2025-04-20', 0),
(4, 1, 13, 'Active', '2025-04-21', 1, '2025-04-28', NULL, NULL, NULL, NULL, NULL);

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
(43, 4, 2, 'Author'),
(44, 5, 2, 'Author'),
(45, 6, 2, 'Author'),
(46, 1, 1, 'Author'),
(47, 2, 1, 'Author'),
(48, 3, 1, 'Author'),
(49, 7, 3, 'Author'),
(50, 7, 4, 'Author'),
(51, 7, 5, 'Author'),
(52, 8, 3, 'Author'),
(53, 8, 4, 'Author'),
(54, 8, 5, 'Author'),
(55, 9, 3, 'Author'),
(56, 9, 4, 'Author'),
(57, 9, 5, 'Author'),
(61, 13, 7, 'Author'),
(62, 14, 7, 'Author'),
(63, 15, 7, 'Author'),
(70, 10, 6, 'Author'),
(71, 11, 6, 'Author'),
(72, 12, 6, 'Author'),
(237, 69, 8, 'Author'),
(238, 69, 1, 'Co-Author'),
(239, 69, 2, 'Co-Author'),
(240, 69, 3, 'Editor'),
(241, 69, 5, 'Editor'),
(242, 69, 6, 'Editor'),
(243, 70, 8, 'Author'),
(244, 70, 1, 'Co-Author'),
(245, 70, 2, 'Co-Author'),
(246, 70, 3, 'Editor'),
(247, 70, 5, 'Editor'),
(248, 70, 6, 'Editor');

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
  `payment_date` date DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT NULL,
  `invoice_sale` int(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_visits`
--

CREATE TABLE `library_visits` (
  `id` int(11) NOT NULL,
  `student_number` int(11) DEFAULT NULL,
  `time` timestamp(6) NULL DEFAULT NULL,
  `status` binary(1) DEFAULT NULL,
  `purpose` varchar(50) DEFAULT NULL
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
(1, 1, 1, '2007'),
(2, 2, 1, '2007'),
(3, 3, 1, '2007'),
(4, 4, 2, '2010'),
(5, 5, 2, '2010'),
(6, 6, 2, '2010'),
(7, 7, 3, '2005'),
(8, 8, 3, '2005'),
(9, 9, 3, '2005'),
(10, 10, 4, '2002'),
(11, 11, 4, '2002'),
(12, 12, 4, '2002'),
(13, 13, 5, '2017'),
(14, 14, 5, '2017'),
(15, 15, 5, '2017'),
(69, 69, 5, '2000'),
(70, 70, 5, '2000');

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
(1, 'Arts Council of Cebu Foundation', 'Cebu, Philippines'),
(2, 'Business & Arts, Inc.', 'Iloilo, Philippines'),
(3, 'Studio 5 Publishing', 'Manila, Philippines'),
(4, 'Icon Press Inc.', 'Manila, Philippines'),
(5, 'Anvil Publishing Inc.', 'Mandaluyong, Philippines'),
(10, 'wasd', 'MNL, PH');

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
(1, 1, 13, '2025-04-20 12:54:53', '2025-04-20 18:55:06', 1, '2025-04-20 18:55:09', 1, NULL, NULL, NULL, '2025-04-20 18:55:09', 'Received');

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
(1, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered a User', 'Admin Kenneth Laurence Bonaagua Registered KENNETH LAURENCE P. BONAAGUA as Student', '2025-04-20 18:47:07'),
(2, 210078, 'Admin', 'Password Reset', 'Admin Kenneth Laurence Bonaagua generated a new password for user KENNETH LAURENCE BONAAGUA', '2025-04-20 18:52:50'),
(3, 210078, 'Admin', 'User Status Updated', 'Admin Kenneth Laurence Bonaagua changed the status of Student KENNETH LAURENCE BONAAGUA to Active', '2025-04-20 18:53:12'),
(4, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Active', '2025-04-20 18:53:22');

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
  `department` varchar(225) DEFAULT NULL,
  `usertype` varchar(100) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `id_type` varchar(100) DEFAULT NULL,
  `id_image` varchar(100) DEFAULT NULL,
  `date_added` date DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `last_update` date DEFAULT NULL,
  `reset_token` varchar(255) NOT NULL,
  `reset_expires` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `firstname`, `middle_init`, `lastname`, `email`, `password`, `contact_no`, `user_image`, `department`, `usertype`, `address`, `id_type`, `id_image`, `date_added`, `status`, `last_update`, `reset_token`, `reset_expires`) VALUES
(1, 210078, 'KENNETH LAURENCE', 'P.', 'BONAAGUA', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$k/uQYqUOzzSiXzJOK/9CROoxvbKhrG8ixu2b8tQNPT5vtSUtnXBCa', '09702582474', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', '2025-04-21', '1', '2025-04-21', '', '2025-04-21 02:53:38');

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
(1, 'E. Billy', '', 'Mondoñedo'),
(2, 'Corazon', 'P.', 'Kabayao'),
(3, 'Manuel', 'L.', 'Quezon III'),
(4, 'Paulo', '', 'Alcazaren'),
(5, 'Jeremy', '', 'Barns'),
(6, 'Francisco', 'S.', 'Tatad'),
(7, 'Marjorie', '', 'Evasco'),
(8, 'wasd', 'a', 'shin');

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
-- Indexes for table `library_visits`
--
ALTER TABLE `library_visits`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=249;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_visits`
--
ALTER TABLE `library_visits`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
