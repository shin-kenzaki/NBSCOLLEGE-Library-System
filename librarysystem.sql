-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 02, 2025 at 07:10 AM
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
(1, 210078, 'Kenneth Laurence', 'P.', 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$Nsh4wEnGVUrGzCAoXGWZ7OEwgf.TRIr5o8evwXpBoqDWd48GYurbO', '../Images/Profile/default-avatar.jpg', 'Admin', '2025-02-15', '1', '2025-03-26', NULL, NULL),
(56, 210028, 'Cayce', '', 'Evangelista', 'cevangelista2021@student.nbscollege.edu.ph', '$2y$10$rvWq3J4KVVfKArg2keeA5OHEjwg9EQQ5U9w2.irMsPDSiKoo7H1w.', '/upload/nbs-login.jpg', 'Admin', '2025-03-21', '1', NULL, NULL, NULL);

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
(34, 23, 'sadwasdwasdw', '', '', '', '', '', '', NULL, NULL, '', '', '1', '', 1, '', '', '', 'Text', 'Print', 'Book', 'TR M45.23 L66 2025 vol1 c1', '', 'English', 'TR', 1, '2025-03-31', 'Available', 1, '2025-04-01'),
(35, 45, 'sadwasdwasdw', '', '', '', '', '', '', NULL, NULL, '', '', '2', '', 1, '', '', '', 'Text', 'Print', 'Book', 'TR M45.23 L66 2025 vol2 c1', '', 'English', 'TR', 1, '2025-03-31', 'Available', 1, '2025-04-01'),
(36, 241, 'sadwasdwasdw', '', '', 'Geographical', 'sadwasdwasdwasdwasdawsadwaswasdwasadawdasdwasdwasdasdw', '', '', NULL, NULL, '', '', '3', '', 1, '', '', '', 'Text', 'Print', 'Book', 'TR M45.23 L66 2025 vol3 c1', '', 'English', 'TR', 1, '2025-03-31', 'Available', 1, '2025-04-01');

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
(1, 31, 34, 'Returned', '2025-03-31', 1, '2025-04-07', '2025-04-01', 1, NULL, NULL, 0),
(2, 31, 35, 'Returned', '2025-03-31', 1, '2025-04-07', '2025-04-01', 1, NULL, NULL, 0);

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
(1, 34, 31, '2025-03-31 07:55:44', 0),
(2, 34, 31, '2025-03-31 09:35:29', 0),
(3, 34, 31, '2025-03-31 09:38:42', 0),
(4, 34, 31, '2025-03-31 09:39:02', 0);

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
(684, 37, 11, 'Author'),
(685, 37, 29, 'Co-Author'),
(686, 37, 31, 'Co-Author'),
(687, 38, 11, 'Author'),
(688, 38, 29, 'Co-Author'),
(689, 38, 31, 'Co-Author'),
(690, 39, 11, 'Author'),
(691, 39, 29, 'Co-Author'),
(692, 39, 31, 'Co-Author'),
(693, 40, 11, 'Author'),
(694, 40, 29, 'Co-Author'),
(695, 40, 31, 'Co-Author'),
(696, 41, 11, 'Author'),
(697, 41, 29, 'Co-Author'),
(698, 41, 31, 'Co-Author'),
(699, 42, 11, 'Author'),
(700, 42, 29, 'Co-Author'),
(701, 42, 31, 'Co-Author'),
(714, 43, 11, 'Author'),
(715, 43, 29, 'Co-Author'),
(716, 43, 31, 'Co-Author'),
(717, 44, 11, 'Author'),
(718, 44, 29, 'Co-Author'),
(719, 44, 31, 'Co-Author'),
(720, 45, 11, 'Author'),
(721, 45, 29, 'Co-Author'),
(722, 45, 31, 'Co-Author'),
(723, 46, 11, 'Author'),
(724, 46, 29, 'Co-Author'),
(725, 46, 31, 'Co-Author'),
(726, 47, 11, 'Author'),
(727, 47, 29, 'Co-Author'),
(728, 47, 31, 'Co-Author'),
(729, 48, 11, 'Author'),
(730, 48, 29, 'Co-Author'),
(731, 48, 31, 'Co-Author'),
(732, 49, 11, 'Author'),
(733, 49, 29, 'Co-Author'),
(734, 49, 31, 'Co-Author'),
(735, 50, 11, 'Author'),
(736, 50, 29, 'Co-Author'),
(737, 50, 31, 'Co-Author'),
(738, 51, 11, 'Author'),
(739, 51, 29, 'Co-Author'),
(740, 51, 31, 'Co-Author'),
(741, 52, 11, 'Author'),
(742, 52, 29, 'Co-Author'),
(743, 52, 31, 'Co-Author'),
(744, 53, 11, 'Author'),
(745, 53, 29, 'Co-Author'),
(746, 53, 31, 'Co-Author'),
(792, 34, 11, 'Author'),
(793, 34, 29, 'Co-Author'),
(794, 34, 31, 'Co-Author'),
(795, 54, 11, 'Author'),
(796, 54, 29, 'Co-Author'),
(797, 54, 31, 'Co-Author'),
(798, 55, 11, 'Author'),
(799, 55, 29, 'Co-Author'),
(800, 55, 31, 'Co-Author'),
(801, 56, 11, 'Author'),
(802, 56, 29, 'Co-Author'),
(803, 56, 31, 'Co-Author'),
(804, 57, 11, 'Author'),
(805, 57, 29, 'Co-Author'),
(806, 57, 31, 'Co-Author'),
(807, 58, 11, 'Author'),
(808, 58, 29, 'Co-Author'),
(809, 58, 31, 'Co-Author'),
(813, 64, 11, 'Author'),
(814, 64, 29, 'Co-Author'),
(815, 64, 31, 'Co-Author'),
(816, 65, 11, 'Author'),
(817, 65, 29, 'Co-Author'),
(818, 65, 31, 'Co-Author'),
(819, 66, 11, 'Author'),
(820, 66, 29, 'Co-Author'),
(821, 66, 31, 'Co-Author'),
(822, 67, 11, 'Author'),
(823, 67, 29, 'Co-Author'),
(824, 67, 31, 'Co-Author'),
(825, 68, 11, 'Author'),
(826, 68, 29, 'Co-Author'),
(827, 68, 31, 'Co-Author'),
(828, 36, 11, 'Author'),
(829, 36, 29, 'Co-Author'),
(830, 36, 31, 'Co-Author'),
(831, 59, 11, 'Author'),
(832, 59, 29, 'Co-Author'),
(833, 59, 31, 'Co-Author'),
(834, 60, 11, 'Author'),
(835, 60, 29, 'Co-Author'),
(836, 60, 31, 'Co-Author'),
(837, 61, 11, 'Author'),
(838, 61, 29, 'Co-Author'),
(839, 61, 31, 'Co-Author'),
(840, 62, 11, 'Author'),
(841, 62, 29, 'Co-Author'),
(842, 62, 31, 'Co-Author'),
(843, 63, 11, 'Author'),
(844, 63, 29, 'Co-Author'),
(845, 63, 31, 'Co-Author'),
(879, 74, 11, 'Author'),
(880, 74, 29, 'Co-Author'),
(881, 74, 31, 'Co-Author'),
(882, 75, 11, 'Author'),
(883, 75, 29, 'Co-Author'),
(884, 75, 31, 'Co-Author'),
(885, 76, 11, 'Author'),
(886, 76, 29, 'Co-Author'),
(887, 76, 31, 'Co-Author'),
(888, 77, 11, 'Author'),
(889, 77, 29, 'Co-Author'),
(890, 77, 31, 'Co-Author'),
(891, 78, 11, 'Author'),
(892, 78, 29, 'Co-Author'),
(893, 78, 31, 'Co-Author'),
(903, 35, 11, 'Author'),
(904, 35, 29, 'Co-Author'),
(905, 35, 31, 'Co-Author'),
(906, 79, 11, 'Author'),
(907, 79, 29, 'Co-Author'),
(908, 79, 31, 'Co-Author'),
(909, 80, 11, 'Author'),
(910, 80, 29, 'Co-Author'),
(911, 80, 31, 'Co-Author'),
(912, 81, 11, 'Author'),
(913, 81, 29, 'Co-Author'),
(914, 81, 31, 'Co-Author');

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
-- Table structure for table `library_visits`
--

CREATE TABLE `library_visits` (
  `id` int(11) NOT NULL,
  `student_number` int(11) DEFAULT NULL,
  `time` timestamp(6) NULL DEFAULT NULL,
  `status` TINYINT(1) NOT NULL,
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
-- Table structure for table `physical_login_users`
--

CREATE TABLE `physical_login_users` (
  `id` int(11) NOT NULL,
  `student_number` int(11) NOT NULL,
  `course` varchar(100) NOT NULL,
  `year` varchar(50) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middle_init` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `gender` varchar(50) NOT NULL
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
(104, 34, 2, '2025'),
(105, 35, 2, '2025'),
(106, 36, 2, '2025'),
(113, 37, 2, '2025'),
(114, 38, 2, '2025'),
(115, 39, 2, '2025'),
(116, 40, 2, '2025'),
(117, 41, 2, '2025'),
(118, 42, 2, '2025'),
(119, 43, 2, '2025'),
(120, 44, 2, '2025'),
(121, 45, 2, '2025'),
(122, 46, 2, '2025'),
(123, 47, 2, '2025'),
(124, 48, 2, '2025'),
(125, 49, 2, '2025'),
(126, 50, 2, '2025'),
(127, 51, 2, '2025'),
(128, 52, 2, '2025'),
(129, 53, 2, '2025'),
(145, 54, 2, '2025'),
(146, 55, 2, '2025'),
(147, 56, 2, '2025'),
(148, 57, 2, '2025'),
(149, 58, 2, '2025'),
(150, 64, 2, '2025'),
(151, 65, 2, '2025'),
(152, 66, 2, '2025'),
(153, 67, 2, '2025'),
(154, 68, 2, '2025'),
(155, 59, 2, '2025'),
(156, 60, 2, '2025'),
(157, 61, 2, '2025'),
(158, 62, 2, '2025'),
(159, 63, 2, '2025'),
(170, 74, 2, '2025'),
(171, 75, 2, '2025'),
(172, 76, 2, '2025'),
(173, 77, 2, '2025'),
(174, 78, 2, '2025'),
(178, 79, 2, '2025'),
(179, 80, 2, '2025'),
(180, 81, 2, '2025');

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
(2, 'Arts Council of Cebu Foundation Inc.', 'Cebu, Ph'),
(3, 'Business & Arts Inc.', 'Manila, Ph'),
(6, 'Anvil Publishing Inc.', 'Bohol, Ph'),
(7, 'Icon Press Inc', 'Manila, Ph'),
(8, 'Studio 5 Publishing', 'Manila, Ph'),
(16, 'asd', 'asd'),
(17, 'wasd', 'wasd'),
(18, 'xasd', 'xasd'),
(19, 'jyfg', 'jygh');

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
(1, 31, 34, '2025-03-31 01:56:20', NULL, NULL, NULL, NULL, '2025-03-31 07:56:30', 31, 'User', NULL, 'Cancelled'),
(2, 31, 34, '2025-03-31 01:57:24', NULL, NULL, NULL, NULL, '2025-03-31 09:35:23', 31, 'User', NULL, 'Cancelled'),
(3, 31, 34, '2025-03-31 04:18:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending');

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
(1, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"The Colors of Lucban\" with 9 copies', '2025-03-30 22:07:33'),
(2, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"asdwasd\" with 3 copies', '2025-03-30 22:48:37'),
(3, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"sadwas\" with 3 copies', '2025-03-30 22:49:42'),
(4, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wsdaw\" with 3 copies', '2025-03-30 22:51:27'),
(5, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"Colors of Lucban\" with 3 copies', '2025-03-30 22:53:25'),
(6, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"gasddawrssdw\" with 3 copies', '2025-03-30 23:07:05'),
(7, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"sadwasd\" with 3 copies', '2025-03-30 23:19:53'),
(8, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"daswasd\" with 3 copies', '2025-03-30 23:24:12'),
(9, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-03-31 06:40:15'),
(10, 210078, 'Student', 'User Logged In', 'Student Kenneth Laurence Bonaagua Logged In as Active', '2025-03-31 07:55:00'),
(11, 210078, 'Student', 'User Logged In', 'Student Kenneth Laurence Bonaagua Logged In as Active', '2025-03-31 10:18:27'),
(12, 210078, 'Student', 'User Logged In', 'Student Kenneth Laurence Bonaagua Logged In as Active', '2025-03-31 10:53:48'),
(13, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-01 06:09:14'),
(14, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-02 04:28:43');

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
  `last_update` date DEFAULT NULL,
  `reset_token` varchar(255) NOT NULL,
  `reset_expires` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `firstname`, `middle_init`, `lastname`, `email`, `password`, `contact_no`, `borrowed_books`, `returned_books`, `damaged_books`, `lost_books`, `user_image`, `usertype`, `address`, `id_type`, `id_image`, `date_added`, `status`, `last_update`, `reset_token`, `reset_expires`) VALUES
(31, 210078, 'Kenneth Laurence', NULL, 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$/Mp6RZAsEWMWNzTCNM.MLeF8e1dgWY2elORlDaJ3o57bB.zqrBJuu', '', 30, 24, 2, 1, '../Images/Profile/default-avatar.jpg', 'Student', '', '', '/upload/default-id.png', '2025-03-18', '1', '2025-03-31', '', '2025-04-01 12:46:12');

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
(8, 'Paulo', '', 'Alcazaren'),
(9, 'Jeremy', '', 'Barns'),
(10, 'Marjorie', '', 'Evasco'),
(11, 'Alya', 'B', 'Honasan'),
(12, 'Corazon', 'Pineda', 'Kabayao'),
(13, 'Manuel', 'L', 'Quezon III'),
(14, 'Francisco', 'S', 'Tatad'),
(22, 'E. Billy', '', 'Mondoñedo'),
(29, 'asd', 'a', 'asd'),
(30, 'asd', 'w', 'wasd'),
(31, 'asd', 'v', 'vasd'),
(32, 'hdf', 's', 'dfesd');

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
-- Indexes for table `physical_login_users`
--
ALTER TABLE `physical_login_users`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contributors`
--
ALTER TABLE `contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=936;

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
-- AUTO_INCREMENT for table `physical_login_users`
--
ALTER TABLE `physical_login_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publications`
--
ALTER TABLE `publications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
