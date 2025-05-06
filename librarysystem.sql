-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2025 at 04:42 AM
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
  `accession` varchar(25) NOT NULL,
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
(1, '1245', 'Cebu: Pride of Place', '', '', 'Geographical', 'Tourism Management', 'wasd wwad', '', '', NULL, NULL, '', 'a', '', '', '', 1, 'xiii 564', 'includes Appendix, Bibliography, and Index', '9789719396109', 'Text', 'Print', 'Book', 'FIL DG651 C38 c2007 c.1', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-21'),
(2, '1246', 'Cebu: Pride of Place', '', '', 'Geographical', 'Tourism Management', 'wasd wwad', '', '', NULL, NULL, '', 'a', '', '', '', 2, 'xiii 564', 'includes Appendix, Bibliography, and Index', '9789719396109', 'Text', 'Print', 'Book', 'FIL DG651 C38 c2007 c.2', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-21'),
(3, '1247', 'Cebu: Pride of Place', '', '', 'Geographical', 'Tourism Management', 'wasd wwad', '', '', NULL, NULL, '', 'a', '', '', '', 3, 'xiii 564', 'includes Appendix, Bibliography, and Index', '9789719396109', 'Text', 'Print', 'Book', 'FIL DG651 C38 c2007 c.3', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-21'),
(4, '1548', 'Colors of Lucban', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '', 1, '', '', '9789719472810', 'Text', 'Print', 'Book', 'FIL DS689.L4 K111 2010 c.1', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(5, '1549', 'Colors of Lucban', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '', 2, '', '', '9789719472810', 'Text', 'Print', 'Book', 'FIL DS689.L4 K111 2010 c.2', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(6, '1550', 'Colors of Lucban', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '', 3, '', '', '9789719472810', 'Text', 'Print', 'Book', 'FIL DS689.L4 K111 2010 c.3', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(7, '3214', 'Malacañang Palace: The Official Illustrated History', '', '', '', NULL, '', '', '', NULL, NULL, '', '', '', '', '', 1, '', '', '', 'Text', 'Print', 'Book', 'FIL DG651 C38 2005 c.1', '', 'English', 'FIL', 1, '2025-04-14', 'Available', NULL, '2025-04-14'),
(8, '3215', 'Malacañang Palace: The Official Illustrated History', '', '', '', NULL, '', '', '', NULL, NULL, '', '', '', '', '', 2, '', '', '', 'Text', 'Print', 'Book', 'FIL DG651 C38 2005 c.2', '', 'English', 'FIL', 1, '2025-04-14', 'Available', NULL, '2025-04-14'),
(9, '3216', 'Malacañang Palace: The Official Illustrated History', '', '', '', NULL, '', '', '', NULL, NULL, '', '', '', '', '', 3, '', '', '', 'Text', 'Print', 'Book', 'FIL DG651 C38 2005 c.3', '', 'English', 'FIL', 1, '2025-04-14', 'Available', NULL, '2025-04-14'),
(10, '4587', 'A Nation on Fire: The Unmaking of Joseph Ejercito Estrada and the Remaking of Democracy in the Phili', '', '', '', '', '', '', '', NULL, NULL, '', '', '', '', '', 1, '', '', '', 'Text', 'Print', 'Book', 'FIL DS686.616 E87 c2002 c.1', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(11, '4588', 'A Nation on Fire: The Unmaking of Joseph Ejercito Estrada and the Remaking of Democracy in the Phili', '', '', '', '', '', '', '', NULL, NULL, '', '', '', '', '', 2, '', '', '', 'Text', 'Print', 'Book', 'FIL DS686.616 E87 c2002 c.2', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(12, '4589', 'A Nation on Fire: The Unmaking of Joseph Ejercito Estrada and the Remaking of Democracy in the Phili', '', '', '', '', '', '', '', NULL, NULL, '', '', '', '', '', 3, '', '', '', 'Text', 'Print', 'Book', 'FIL DS686.616 E87 c2002 c.3', '', 'English', 'FIL', 1, '2025-04-14', 'Available', 1, '2025-04-14'),
(13, '5684', 'The Bohol We Love: An Anthology of Memoirs', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '', 1, '', '', '', 'Text', 'Print', 'Book', 'TR DS688 B64 2017 c.1', '', 'English', 'TR', 1, '2025-04-14', 'Borrowed', NULL, '2025-04-14'),
(14, '5685', 'The Bohol We Love: An Anthology of Memoirs', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '', 2, '', '', '', 'Text', 'Print', 'Book', 'TR DS688 B64 2017 c.2', '', 'English', 'TR', 1, '2025-04-14', 'Available', NULL, '2025-04-14'),
(15, '5686', 'The Bohol We Love: An Anthology of Memoirs', '', '', 'Geographical', 'Tourism Management', '', '', '', NULL, NULL, '', '', '', '', '', 3, '', '', '', 'Text', 'Print', 'Book', 'TR DS688 B64 2017 c.3', '', 'English', 'TR', 1, '2025-04-14', 'Available', NULL, '2025-04-14');

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
(252, 1, 1, 'Author'),
(253, 2, 1, 'Author'),
(254, 3, 1, 'Author'),
(315, 69, 1, 'Author'),
(316, 69, 1, 'Co-Author'),
(317, 69, 3, 'Editor'),
(318, 69, 5, 'Editor'),
(319, 69, 6, 'Editor'),
(325, 71, 2, 'Author'),
(326, 71, 1, 'Co-Author'),
(327, 71, 3, 'Co-Author'),
(328, 71, 4, 'Editor'),
(329, 72, 2, 'Author'),
(330, 72, 1, 'Co-Author'),
(331, 72, 3, 'Co-Author'),
(332, 72, 4, 'Editor');

-- --------------------------------------------------------

--
-- Table structure for table `corporates`
--

CREATE TABLE `corporates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `corporates`
--

INSERT INTO `corporates` (`id`, `name`, `type`, `location`, `description`) VALUES
(1, 'National Library of the Philippines', 'Government Institution', 'Manila, Philippines', 'Official national library of the Philippines'),
(2, 'University of the Philippines Press', 'University Press', 'Quezon City, Philippines', 'Academic publishing house of the University of the Philippines'),
(3, 'Philippine Institute of Volcanology and Seismology', 'Research Institute', 'Quezon City, Philippines', 'Government institute focused on volcano and earthquake monitoring');

-- --------------------------------------------------------

--
-- Table structure for table `corporate_contributors`
--

CREATE TABLE `corporate_contributors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `corporate_id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `book_id` (`book_id`),
  KEY `corporate_id` (`corporate_id`),
  CONSTRAINT `corporate_contributors_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `corporate_contributors_ibfk_2` FOREIGN KEY (`corporate_id`) REFERENCES `corporates` (`id`) ON DELETE CASCADE
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
(71, 71, 3, '2000'),
(72, 72, 3, '2000');

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
(12, 'wasd', 'Manila, Philippines');

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
(4, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Active', '2025-04-20 18:53:22'),
(5, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Deleted a User', 'Admin Kenneth Laurence Bonaagua Deleted Student KENNETH LAURENCE P. BONAAGUA', '2025-04-21 09:09:11'),
(6, 210078, 'Student', 'User Registered', 'Shin H.. Kenzako Registered as Student', '2025-04-21 09:11:23'),
(7, 210078, 'Student', 'User Logged In', 'Student Shin Kenzako Logged In as Inactive', '2025-04-21 09:11:30'),
(8, 210078, 'Student', 'User Registered', 'Kenneth Bonaagua Registered as Student', '2025-04-21 09:17:43'),
(9, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Deleted a User', 'Admin Kenneth Laurence Bonaagua Deleted Student Kenneth Bonaagua', '2025-04-21 09:18:10'),
(10, 210078, 'Student', 'User Registered', 'Kenneth Bonaagua Registered as Student', '2025-04-21 09:19:29'),
(11, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Deleted a User', 'Admin Kenneth Laurence Bonaagua Deleted Student Kenneth Bonaagua', '2025-04-21 09:20:44'),
(12, 210078, 'Student', 'User Registered', 'Kenneth laurence Bonaagua Registered as Student', '2025-04-21 09:22:06'),
(13, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-21 09:22:53'),
(14, 210078, 'Student', 'User Logged In', 'Student Kenneth laurence Bonaagua Logged In as Inactive', '2025-04-21 09:23:02'),
(15, 210078, 'Student', 'User Registered', 'Kenneth laurence P... Bonaagua Registered as Student', '2025-04-21 09:30:55'),
(16, 210078, 'Student', 'User Registered', 'Kenneth laurence P. Bonaagua Registered as Student', '2025-04-21 09:40:34'),
(17, 210078, 'Student', 'User Registered', 'KENNETH LAURENCE P. BONAAGUA Registered as Student', '2025-04-21 09:43:19'),
(18, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Inactive', '2025-04-21 09:43:46'),
(19, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Inactive', '2025-04-21 09:48:32'),
(20, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Inactive', '2025-04-21 10:38:14'),
(21, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Inactive', '2025-04-22 01:28:30'),
(22, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"Psychology\" with 2 copies', '2025-04-21 19:47:37'),
(23, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-22 02:07:36'),
(24, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"7634\" with 4 copies', '2025-04-21 20:08:04'),
(25, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"56235\" with 4 copies', '2025-04-21 20:12:57'),
(26, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"87687234\" with 2 copies', '2025-04-21 20:17:23'),
(27, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 8 copies', '2025-04-21 20:29:43'),
(28, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Inactive', '2025-04-22 02:50:52'),
(29, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"128763\" with 3 copies', '2025-04-21 21:01:13'),
(30, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asjgdwjhgashdg\" with 4 copies', '2025-04-21 21:32:54'),
(31, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"sajdghjhjgasdgjhg\" with 1 copies', '2025-04-21 21:42:21'),
(32, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"jsdjasjdh\" with 1 copies', '2025-04-21 21:43:28'),
(33, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasdhg\" with 1 copies', '2025-04-21 21:51:19'),
(34, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"dsajdhjh\" with 3 copies', '2025-04-21 21:56:39'),
(35, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 6 copies', '2025-04-22 09:02:41'),
(36, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 10 copies', '2025-04-22 09:04:47'),
(37, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasdwas\" with 5 copies', '2025-04-22 10:09:55'),
(38, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 5 copies', '2025-04-22 10:25:49'),
(39, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 2 copies', '2025-04-22 10:27:46'),
(40, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 5 copies', '2025-04-22 10:43:57'),
(41, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-23 03:06:28'),
(42, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Inactive', '2025-04-23 03:51:28'),
(43, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 6 copies', '2025-04-23 11:27:47'),
(44, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 6 copies', '2025-04-23 11:40:13'),
(45, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"add book\" with 6 copies', '2025-04-23 20:15:00'),
(46, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"add book\" with 6 copies', '2025-04-23 20:22:20'),
(47, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"add book\" with 6 copies', '2025-04-23 20:25:54'),
(48, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"add book\" with 3 copies', '2025-04-23 20:26:48'),
(49, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"add book\" with 5 copies', '2025-04-23 20:28:37'),
(50, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 3 copies', '2025-04-23 22:08:38'),
(51, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 5 copies', '2025-04-23 22:12:52'),
(52, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 10 copies', '2025-04-23 22:24:53'),
(53, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-24 00:16:44'),
(54, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asdwasd\" with 15 copies', '2025-04-24 01:04:10'),
(55, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 15 copies', '2025-04-24 01:12:25'),
(56, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-24 11:33:59'),
(57, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 15 copies', '2025-04-24 05:41:44'),
(58, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Inactive', '2025-04-24 11:59:51'),
(59, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 10 copies', '2025-04-24 07:40:51'),
(60, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-25 13:28:11'),
(61, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-26 01:08:34'),
(62, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 5 copies', '2025-04-25 20:09:32'),
(63, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 6 copies', '2025-04-25 21:26:32'),
(64, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 8 copies', '2025-04-25 21:32:41'),
(65, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 5 copies', '2025-04-25 23:48:58'),
(66, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-26 00:13:11'),
(67, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-26 00:15:01'),
(68, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-26 07:23:27'),
(69, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-26 07:48:15'),
(70, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-26 01:50:22'),
(71, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 10 copies', '2025-04-26 02:25:33'),
(72, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-26 09:35:55'),
(73, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wasd\" with 5 copies', '2025-04-26 03:43:13'),
(74, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Inactive', '2025-04-26 09:43:39'),
(75, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-27 01:00:09'),
(76, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Inactive', '2025-04-27 01:00:17'),
(77, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-26 21:02:33'),
(78, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Inactive', '2025-04-27 03:03:17'),
(79, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Inactive', '2025-04-27 04:27:55'),
(80, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-27 07:04:52'),
(81, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-27 12:55:59'),
(82, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-27 08:18:53'),
(83, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-27 08:24:53'),
(84, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-27 16:55:59'),
(85, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-27 16:58:34'),
(86, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-27 17:02:07'),
(87, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-27 17:04:49'),
(88, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-27 17:14:33'),
(89, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-27 17:21:43'),
(90, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-27 17:56:21'),
(91, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-28 00:01:19'),
(92, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Inactive', '2025-04-28 00:01:32'),
(93, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asd\" with 10 copies', '2025-04-27 18:04:24'),
(94, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-05-06 02:23:06');

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
(3, 210078, 'KENNETH LAURENCE', 'P.', 'BONAAGUA', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$YmYBf/qjyQo5Mz7IMc6WM.MReMUQv6Fkp1wZ.ZkDvyGBo8RVcmbpC', '09702582474', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', 'B1 L8 Ph-F1 Balagtas St. Francisco Homes - Narra, SJDM, Bulacan', '', NULL, '2025-04-21', NULL, '2025-04-21', '', '2025-04-21 18:37:51');

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
(7, 'Marjorie', '', 'Evasco');

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
-- Indexes for table `corporates`
--
ALTER TABLE `corporates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `corporate_contributors`
--
ALTER TABLE `corporate_contributors`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=554;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2889;

--
-- AUTO_INCREMENT for table `corporates`
--
ALTER TABLE `corporates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `corporate_contributors`
--
ALTER TABLE `corporate_contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=549;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
