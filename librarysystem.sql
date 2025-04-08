-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 07, 2025 at 01:54 PM
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
(1, 210078, 'Kenneth', '', 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$7xCbmZnyGjq.FiJT5XHyIuaPDtalM1hZjOUPJ7Cf9uTvLhyDAdf.O', '../Images/Profile/default-avatar.jpg', 'Admin', '2025-04-04', '1', '2025-04-04', NULL, NULL);

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
(1, 4324, 'ssdwasdeefsdasda', '', '', '', '', '', '', NULL, NULL, '', '', '1', '', 1, 'pages  ', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'CIR', 1, '2025-04-07', 'Borrowed', NULL, '2025-04-07'),
(2, 4325, 'ssdwasdeefsdasda', '', '', '', '', '', '', NULL, NULL, '', '', '1', '', 2, 'pages  ', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'CIR', 1, '2025-04-07', 'Available', NULL, '2025-04-07'),
(3, 4326, 'ssdwasdeefsdasda', '', '', '', '', '', '', NULL, NULL, '', '', '1', '', 3, 'pages  ', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'CIR', 1, '2025-04-07', 'Available', NULL, '2025-04-07'),
(4, 4327, 'ssdwasdeefsdasda', '', '', '', '', '', '', NULL, NULL, '', '', '1', '', 4, 'pages  ', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'REF', 1, '2025-04-07', 'Available', NULL, '2025-04-07'),
(5, 4328, 'ssdwasdeefsdasda', '', '', '', '', '', '', NULL, NULL, '', '', '1', '', 5, 'pages  ', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'REF', 1, '2025-04-07', 'Available', NULL, '2025-04-07'),
(6, 5689, 'ssdwasdeefsdasda', '', '', '', '', '', '', NULL, NULL, '', '', '2', '', 6, 'pages  ', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 1, '2025-04-07', 'Available', NULL, '2025-04-07'),
(7, 5690, 'ssdwasdeefsdasda', '', '', '', '', '', '', NULL, NULL, '', '', '2', '', 7, 'pages  ', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 1, '2025-04-07', 'Available', NULL, '2025-04-07'),
(8, 5691, 'ssdwasdeefsdasda', '', '', '', '', '', '', NULL, NULL, '', '', '2', '', 8, 'pages  ', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 1, '2025-04-07', 'Available', NULL, '2025-04-07'),
(9, 5692, 'ssdwasdeefsdasda', '', '', '', '', '', '', NULL, NULL, '', '', '2', '', 9, 'pages  ', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'RES', 1, '2025-04-07', 'Available', NULL, '2025-04-07'),
(10, 5693, 'ssdwasdeefsdasda', '', '', '', '', '', '', NULL, NULL, '', '', '2', '', 10, 'pages  ', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'RES', 1, '2025-04-07', 'Available', NULL, '2025-04-07');

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
(1, 68, 5, 'Returned', '2025-03-07', 1, '2025-03-07', '2025-04-07', 1, NULL, NULL, 0),
(2, 68, 10, 'Returned', '2025-03-07', 1, '2025-03-08', '2025-04-07', 1, NULL, NULL, 0),
(3, 68, 4, 'Returned', '2025-03-07', 1, '2025-03-07', '2025-04-07', 1, NULL, NULL, 0),
(4, 68, 9, 'Returned', '2025-03-07', 1, '2025-03-08', '2025-04-07', 1, NULL, NULL, 0),
(5, 68, 1, 'Returned', '2025-03-07', 1, '2025-03-14', '2025-04-07', 1, NULL, NULL, 0),
(6, 68, 8, 'Returned', '2025-03-07', 1, '2025-03-14', '2025-04-07', 1, NULL, NULL, 0),
(7, 68, 1, 'Active', '2025-04-07', 1, '2025-04-14', NULL, NULL, NULL, NULL, NULL);

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
(1, 1, 1, 'Author'),
(2, 2, 1, 'Author'),
(3, 3, 1, 'Author'),
(4, 4, 1, 'Author'),
(5, 5, 1, 'Author'),
(6, 6, 1, 'Author'),
(7, 7, 1, 'Author'),
(8, 8, 1, 'Author'),
(9, 9, 1, 'Author'),
(10, 10, 1, 'Author');

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
  `reminder_sent` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fines`
--

INSERT INTO `fines` (`id`, `borrowing_id`, `type`, `amount`, `status`, `date`, `payment_date`, `reminder_sent`) VALUES
(1, 1, 'Overdue', 928.75, 'Unpaid', '2025-04-07', NULL, NULL),
(2, 2, 'Overdue', 898.75, 'Unpaid', '2025-04-07', NULL, NULL),
(3, 3, 'Overdue', 928.75, 'Unpaid', '2025-04-07', NULL, NULL),
(4, 4, 'Overdue', 898.75, 'Unpaid', '2025-04-07', NULL, NULL),
(5, 5, 'Overdue', 119.79, 'Unpaid', '2025-04-07', NULL, NULL),
(6, 6, 'Overdue', 119.79, 'Unpaid', '2025-04-07', NULL, NULL);

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
(1, 'Arts Council of Cebu Foundation, Inc.', 'Cebu, Philippines');

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
(1, 68, 1, '2025-04-07 05:43:32', '2025-04-07 11:45:38', 1, '2025-04-07 11:45:46', 1, NULL, NULL, NULL, '2025-04-07 11:45:46', 'Received');

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
(1, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Bonaagua added \"ssdwasdeefsdasda\" with 10 copies', '2025-04-07 03:46:09'),
(2, 210078, 'Student', 'User Logged In', 'Student KENNETH LAURENCE BONAAGUA Logged In as Active', '2025-04-07 11:43:03');

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
(1, 230016, 'MARC ED', '', 'EBRADO', 'mebrado2023@student.nbscollege.edu.ph', '$2y$10$/3b2WFrOXs924tjDFS4pKOA34bQVczQBN53kESKtRbr3kUC8HGHEO', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:26'),
(2, 230033, 'STEPHANIE', '', 'ESPEJON', 'sespejon2023@student.nbscollege.edu.ph', '$2y$10$iYevRObnDUbagJkwGDtefeRsLWVLffcPCcLiF5/j7kjGDw9dVWPKu', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:29'),
(3, 230030, 'GIRLIE GAIL', '', 'GALLARDO', 'ggallardo2023@student.nbscollege.edu.ph', '$2y$10$aFBTzedw1iUZAPCKAS8P1eWENHHLo8KUsOO2URrDUdDe4.7JMOIMm', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:29'),
(4, 230019, 'JESSA', '', 'MADANLO', 'jmadanlo2023@student.nbscollege.edu.ph', '$2y$10$xqXItbzcyUmdqP3iGUxY6.hdnpcW9CoeK6HvJeCulfEmZu2Z6JsTC', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:29'),
(5, 230003, 'VINCELYN', '', 'MOZOL', 'vmozol2023@student.nbscollege.edu.ph', '$2y$10$THRHq7/hGwGzLSI82d7ydu4MM3blhMG88xKAXftQ8jPseLH3hFVHO', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:29'),
(6, 230037, 'REGINE', '', 'OCAMPO', 'rocampo2023@student.nbscollege.edu.ph', '$2y$10$BhE3SA7kL2omCJ3AV5/mFOl6G9UqAKBWNvGmMO/J6xu1eZZes70s.', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:29'),
(7, 230044, 'MONIQUE', '', 'PACAMARRA', 'mpacamarra2023@student.nbscollege.edu.ph', '$2y$10$v44dusIx4WlVP0MU4/SJ1OlzX5Df/DF7/Tq5PI9GVThXdOUjqQK4u', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:29'),
(8, 220054, 'CHEEZER JANE', '', 'POWAO', 'cpowao2022@student.nbscollege.edu.ph', '$2y$10$9SE1/XrziwR97msmX8n29..OKJThV.ZIns9CFYi95NCcsxIkCgEce', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:29'),
(9, 230027, 'JOHN RENZ', '', 'REJANO', 'jrejano2023@student.nbscollege.edu.ph', '$2y$10$QckeEVdlXSbZnp1yovK9RuDDFWTB353HkJF3YK6b79gLZOECG4o1i', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:29'),
(10, 230009, 'RHOCA', '', 'TINAY', 'rtinay2023@student.nbscollege.edu.ph', '$2y$10$EkiWREWqpoKbZTqp9IUHr.Sb.QrpbkL0SefDTLnEIJqswyjR9zekC', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:29'),
(11, 230005, 'JOSE CARLOS', '', 'VILLANUEVA', 'jvillanueva2023@student.nbscollege.edu.ph', '$2y$10$QAtHCOvZdM0Xoe5ODjLRU.SXKnRcbuyl4DuSBkw/l4H9PEIc24CsK', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:30'),
(12, 210027, 'RANNIE', '', 'ASIS', 'rasis2021@student.nbscollege.edu.ph', '$2y$10$7ch3y1gkPKMbZ64PUaf3Mu055fb2lWX9.R84uGRxsuLFp0fbUTCVi', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:30'),
(13, 220055, 'KAREN MAE', '', 'BALICTAR', 'kbalictar2022@student.nbscollege.edu.ph', '$2y$10$cjVwBT5ems01w9PGaRKelOWaNjtOAj8D1hsTvx.blTfYg3toC3Gyi', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:30'),
(14, 220021, 'JAMES LHARS', '', 'BARRIENTOS', 'jbarrientos2022@student.nbscollege.edu.ph', '$2y$10$T8pEDI2dOU.Pdh4xnNoIr.r3IcNojNi0AMP82qxXZ.dKqv7O7kxhi', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:30'),
(15, 220047, 'NOEMI', '', 'LAURENTE', 'nlaurente2022@student.nbscollege.edu.ph', '$2y$10$YYRe4Ci0.610SeIcQZqEDubU8XGepD5LiFVmX3JzM5kbi6OVTkof.', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:30'),
(16, 210061, 'ANGELA CLAIRE', '', 'PANAHON', 'apanahon2021@student.nbscollege.edu.ph', '$2y$10$UYp32KSPHEwXaTU.cnM2puEbJe41ArmTSOM2JMqja2eiyTcChEOfW', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:30'),
(17, 220007, 'MA. KATRINA', '', 'SANTOS', 'msantos2022@student.nbscollege.edu.ph', '$2y$10$nmrH/3G9qWhAr14P0xHd4ezewW8wZ02jRSB.BoK4yZSOi54.6Xd0.', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:30'),
(18, 210037, 'LEIRA', '', 'SINAGUINAN', 'lsinaguinan2021@student.nbscollege.edu.ph', '$2y$10$95ba.4ROMYqxxulOQ4dbsOJX/ehuoHMpTvWdPQ4lx5ZXGruM8WMb2', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:30'),
(19, 210014, 'ANGELINE', '', 'ACEBUCHE', 'aacebuche2021@student.nbscollege.edu.ph', '$2y$10$kv3tpaA3s2IyStWE3uO00e3L4HftAglN9PlFgxu0wtJbjrR1YUAsi', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:30'),
(20, 210044, 'MAY ANN', '', 'BAYOD', 'mbayod2021@student.nbscollege.edu.ph', '$2y$10$yxP7He.hcLImt4tUF1xPm.DRzK/lC.n7m0BqVUUEC5OMcyhRiXdeC', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:31'),
(21, 210033, 'PATRICK DOMINIC', '', 'CORRE', 'pcorre2021@student.nbscollege.edu.ph', '$2y$10$1J7QZQXPjW0SDpJX/3zGLOd7HdYY5cAn0dDPEAIhwlrtsspyFtsu.', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:32'),
(22, 220024, 'JOHN AARON PAUL', '', 'GACHO', 'jgacho2022@student.nbscollege.edu.ph', '$2y$10$aLequMQyydtvozdYnqvkS./hUI4etstHqa3Xkq02HxbZHP4oZViMm', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:32'),
(23, 220012, 'ALLIANA MARIEL', '', 'GONZALES', 'agonzales2022@student.nbscollege.edu.ph', '$2y$10$RjTnZJxEdjmGD1zLA9GQZuJO1vib0xM2KTzPrBINFaMYpEYP811dq', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:32'),
(24, 210050, 'ANGELI', '', 'QUINTO', 'aquinto2021@student.nbscollege.edu.ph', '$2y$10$FNs.dIAVbeKKt3E9OecpieKI4w1YuYPumGhwGW2CC2OlqtJO8qTmy', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:32'),
(25, 210070, 'MARIA SHEKINAH ELIZABETH', '', 'SAN JUAN', 'msanjuan2021@student.nbscollege.edu.ph', '$2y$10$n/yRANBe5kjY9Q129MAOpewjysAdVdqOoAb.ixRONAqIuaCG78l6a', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:32'),
(26, 210060, 'LYNLEN', '', 'SINGCO', 'lsingco2021@student.nbscollege.edu.ph', '$2y$10$aLg.quT7D1UyFtYG7myQ.ewixTWB.pJ/MW8SHVuTOsZl0W4Qn3NG.', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:32'),
(27, 210022, 'ROSHIELA MAY', '', 'SOLDEVILLA', 'rsoldevilla2021@student.nbscollege.edu.ph', '$2y$10$rr1LekiO1Mm6/IjZyQLp.eVU8t5HB0jqN2EsiluqaPN8mUqzujtYa', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:32'),
(28, 210031, 'JAY FOARD', '', 'TRAJE', 'jtraje2021@student.nbscollege.edu.ph', '$2y$10$S02Q0OUZw9ccdYPPHcL4Jej8B0comNUpzGB/Ha9dd/xwABdewuOn2', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:33'),
(29, 210058, 'AIKA MARIE', '', 'YBAÑEZ', 'aybaÑez2021@student.nbscollege.edu.ph', '$2y$10$q97Z4burbWZFpPbZUV5HbODM7JcOr9t0DK6YHdz6sLIfuSzKQlOOe', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:33'),
(30, 200043, 'JHEDALYN', '', 'DACQUEL', 'jdacquel2020@student.nbscollege.edu.ph', '$2y$10$2Tne6Bzr/kFTSuijKaPoFevA/YNNry9MGZgv2c4zgIRsi8DVV0M.K', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:33'),
(31, 200039, 'JOYCE ANN', '', 'MERAS', 'jmeras2020@student.nbscollege.edu.ph', '$2y$10$hP6rJJEyjNgGzr5kDvmzpupEWbukb8eW3okhQHSMLP4sRnB62zQLa', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:33'),
(32, 200019, 'ELVERT-ACE', '', 'NERI', 'eneri2020@student.nbscollege.edu.ph', '$2y$10$Em8UHtJUuO.kdc0J.WCApORZIDrXl.ccbgz9nXJ2c.oUWxbs2AG8e', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:33'),
(33, 230032, 'ALEXZA', '', 'IGNACIO', 'aignacio2023@student.nbscollege.edu.ph', '$2y$10$5SCVY5VElzbqaAoho2D.bek.jdYbemePQTfXputVuKQjL1LHpD4EG', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:33'),
(34, 220018, 'JERALD', '', 'BALAO', 'jbalao2022@student.nbscollege.edu.ph', '$2y$10$ci6rTt8IPXpa6P14Pw8qPOMVe5y0R.WO.fQWjUySxgIw8N/NoRZJa', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:33'),
(35, 210016, 'CRIS ALFRED', '', 'PANLUBASAN', 'cpanlubasan2021@student.nbscollege.edu.ph', '$2y$10$.uqw6AgBPB8Pl4VZ0jyC1.rF3QU1b.Zh1I0exw5ScZfzdPjcZmvm.', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:33'),
(36, 190026, 'FRANCES MARGARETT', '', 'PEDOCHE', 'fpedoche2019@student.nbscollege.edu.ph', '$2y$10$SQLDcws2DxS3jI5/753oBuWQjntdTh/n.QfDzLrQxbErW1JDNc3NC', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:33'),
(37, 210049, 'RICHELLE MAE', '', 'CADORNA', 'rcadorna2021@student.nbscollege.edu.ph', '$2y$10$gbzgRqBtQUaGid0oENDMx.etYQeINrzJOgkVqZ.C//7g0ni1FaWGS', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:33'),
(38, 210038, 'MARIA ROSALINDA', '', 'GALLO', 'mgallo2021@student.nbscollege.edu.ph', '$2y$10$TZTH2IhRZ0lE9JdSHV8S3OJSB5d5fogh1mPYIg2J2b0T19XcRa5Yy', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:34'),
(39, 210056, 'JANE AUSTIN', '', 'LANSANG', 'jlansang2021@student.nbscollege.edu.ph', '$2y$10$1djL1pPQbO6h0PjbZUTZNuuEy4P8sB528XITTwkjDZt/AFlOJlSJG', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:34'),
(40, 190034, 'JELLIANE', '', 'ALARCON', 'jalarcon2019@student.nbscollege.edu.ph', '$2y$10$blKLJUfTJuRDBOfTzhwoI.iTTOOlpmv66ghS8Pp6T7RYihFRvw8TC', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:34'),
(41, 200013, 'RIZA JEAN', '', 'ELLERAN', 'relleran2020@student.nbscollege.edu.ph', '$2y$10$WyFiaY27.gzuh3Cvxhka0e/yHxPOTveCp0nQHYqkXTiz5abRjLIb.', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:34'),
(42, 190088, 'JEREMIE', '', 'GIGANTE', 'jgigante2019@student.nbscollege.edu.ph', '$2y$10$5OybqLzWDuVB0RnctRLizO6VSy7WModHkCoFnsy6ZaIEJAGJsG2Ga', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:34'),
(43, 200024, 'JOSEPH IVAN', '', 'GREGORIO', 'jgregorio2020@student.nbscollege.edu.ph', '$2y$10$xpxVvBwZm5aHFFhDZ8iTy.XMywMCdLcqq5a6iF5pyGn/H8kCRZc1C', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:34'),
(44, 200032, 'KINJI', '', 'LABAY', 'klabay2020@student.nbscollege.edu.ph', '$2y$10$Ga4f/FX.NS04aQZmu.vEV.fBfRruVixGUw4q7h1losZLyC62BGnRi', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:34'),
(45, 200065, 'CHELSEA CHARMILANE', '', 'MULDONG', 'cmuldong2020@student.nbscollege.edu.ph', '$2y$10$ZhgDCmuCoWWBr42P9CDOiONrgcesQ5AATmFFxbvpvHUnpLSM3jQXu', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:34'),
(46, 190004, 'GWYNETH VEAH', '', 'OLIVER', 'goliver2019@student.nbscollege.edu.ph', '$2y$10$i609PiMkHcdex3aqbjC2yOuw7AWipCfTjTrhciVIekMFVztRYd8S2', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:34'),
(47, 200042, 'DANIELA SHANE', '', 'PONTIMAR', 'dpontimar2020@student.nbscollege.edu.ph', '$2y$10$OfZzknZJEYjL6MetlSWU7uC2gIbRVyrCCRhZtjXLQLpH9zo/cqEl.', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:35'),
(48, 180004, 'IRICE DANIKKA', '', 'YALUNG', 'iyalung2018@student.nbscollege.edu.ph', '$2y$10$ZgYroha2KzUKAiQtM8f5WuEgGXvEvasazuyxTU11FHkq6SbDzE7rm', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:35'),
(49, 230020, 'EMMANUEL', '', 'ABEJO', 'eabejo2023@student.nbscollege.edu.ph', '$2y$10$KNLufRHSLvBTYmsU3rlyguU9yn1LGKxc/AfS1TtIe3aTXo86vuoTC', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:35'),
(50, 230046, 'RHYZEN', '', 'BUMANLAG', 'rbumanlag2023@student.nbscollege.edu.ph', '$2y$10$kYp4/WxFEP3aQPFfJGSqauaSaHfjUWcjX8EBSzS4puB.3y5ZuYfUG', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:35'),
(51, 220056, 'MARY ANN', '', 'CEDILLO', 'mcedillo2022@student.nbscollege.edu.ph', '$2y$10$SlBXRKyyobzjhqXZ4BoGL.h22NMQoZI8T4qTqLJTwWldZeGyzQTNS', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:35'),
(52, 230007, 'RHOD LENARD', '', 'DELAS NIEVES', 'rdelasnieves2023@student.nbscollege.edu.ph', '$2y$10$XvkdDLUaDffl142i1QkabeZMZliKSI5aRfJ/4lKCEJ/jhRDVukWDy', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:35'),
(53, 230022, 'KATE ANDREI', '', 'DOSAL', 'kdosal2023@student.nbscollege.edu.ph', '$2y$10$xgcoL4lyhZtFy2vXTwCy..R4pZsRTcXgFivq/yFUiDU6zV4EMJOlC', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:35'),
(54, 230021, 'JAVI AEREM', '', 'FAVIA', 'jfavia2023@student.nbscollege.edu.ph', '$2y$10$gbB0lr0NsiwuCXKPS.gO4.EE1ktKgpKluM3ayRJqe2BRXR0aJDs4K', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:35'),
(55, 230004, 'RENARD KEN', '', 'HAPIL', 'rhapil2023@student.nbscollege.edu.ph', '$2y$10$9bGt7lApVkWX4PAH.A859.K/.qhNPCiiCik62RQ5wSciBZvonvb2e', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:35'),
(56, 230012, 'DANDIE', '', 'LAQUINTA', 'dlaquinta2023@student.nbscollege.edu.ph', '$2y$10$0yfsZ/fsHknw28FeFr5K5e3956FRE9RE63I7Zo9Y8XLohMmdo8k3a', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:35'),
(57, 230028, 'JHON KENNETH', '', 'LIMATOC', 'jlimatoc2023@student.nbscollege.edu.ph', '$2y$10$OJTnxKW4IzRHsg8p1Co26Or9mGUjFIrmihddEVdv8qMTfKyC4oH3S', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:36'),
(58, 230010, 'GRACE ANNE', '', 'LOGRONIO', 'glogronio2023@student.nbscollege.edu.ph', '$2y$10$xckcWpG9ITG3wTOvnyqw2.SOtXDtBdB5OsuVFDZcnyQLFpzqbBt3W', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:36'),
(59, 230045, 'PAOLO', '', 'MALICSE', 'pmalicse2023@student.nbscollege.edu.ph', '$2y$10$eMUtxYhslX/rmTZcSLPjdO0RcdDfDhQ/ZGvqCjxQ0bAQkqNnXXGZy', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:36'),
(60, 230039, 'FRANCIS ANGELO', '', 'ORILLANEDA', 'forillaneda2023@student.nbscollege.edu.ph', '$2y$10$LYebRa0Ke/2rVrxDd7rFuufCNaSlrp84srMbXGwBW/7xR6.7UejAS', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:36'),
(61, 230015, 'MICHAEL LORENZ', '', 'TAN', 'mtan2023@student.nbscollege.edu.ph', '$2y$10$N6GI.FyP2YpA.AAIu6I8KuEiZj1SEmbtujxfyQQQhdIoMu3o0Ri96', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:36'),
(62, 220050, 'ARMAN BERNARD', '', 'CABANG', 'acabang2022@student.nbscollege.edu.ph', '$2y$10$h1HaIe8hdl64XZtIPHt0uuv7owdNdFLv9oq0ec6MiSyJjEUEWUkYq', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:36'),
(63, 220057, 'YZRAH', '', 'GASPADO', 'ygaspado2022@student.nbscollege.edu.ph', '$2y$10$fLQEmxvfjYzobGV3U4.Ykeaj4ao5VZcC2xzJeOmYqMBBF1CdKIOlC', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:37'),
(64, 220017, 'BRIAN CHRISTIAN', '', 'HEBRON', 'bhebron2022@student.nbscollege.edu.ph', '$2y$10$Chme2Vxd21ec6EBrXg.HGuF2gLdB.48VXqqw.arxyqQ8RmMQmaUGy', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:38'),
(65, 220048, 'AJLEIN', '', 'MONTERO', 'amontero2022@student.nbscollege.edu.ph', '$2y$10$wfee7CzaAXd1VsSiHPyCaeUbs1pnPQjhqnt28n3F994iL8kKxJ8HC', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:38'),
(66, 230014, 'JAY FRANKLIN', '', 'ROLLO', 'jrollo2023@student.nbscollege.edu.ph', '$2y$10$S.5lGXPNkQU1RKQdEroG2eGbmVl1P5EYmUDG5NGTqu5zK7eolyIVy', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:38'),
(67, 210079, 'CARLOS MIGUEL', '', 'AUTOR', 'cautor2021@student.nbscollege.edu.ph', '$2y$10$pRZjNwCqJX0gxfK0IJQVo.rHkzInYYcKzgCN0n209if8ev7F5aCyC', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:38'),
(68, 210078, 'KENNETH LAURENCE', '', 'BONAAGUA', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$d13GLLaNjzFsEIlFEnRrq.khhr80wqxVoHBzpLaMtU9h7bF8c.4ku', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', '2025-04-07', '', '2025-04-07 17:13:05'),
(69, 220001, 'JOANNA CRIS', '', 'ESCANILLA', 'jescanilla2022@student.nbscollege.edu.ph', '$2y$10$wu4vxFlHoOdL316JVFX/YehSZ.HcTJ0I1J0GQYWPrHR8.wUTd/A46', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:38'),
(70, 210028, 'CAYCE', '', 'EVANGELISTA', 'cevangelista2021@student.nbscollege.edu.ph', '$2y$10$xXGbUpWMqJ2.GdKoJmIkg./2HjwMur4SfVoVAiOkcZ0mNTs7ysz16', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:38'),
(71, 210065, 'JENEPIR', '', 'JABILLO', 'jjabillo2021@student.nbscollege.edu.ph', '$2y$10$gj2FcY42kYbTy575cR6NVuceb4qmdcuk/DQa5pczSS.aflVvgCw2e', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:39'),
(72, 220003, 'JOSEPH', '', 'SIMANGCA', 'jsimangca2022@student.nbscollege.edu.ph', '$2y$10$wvueNeFprN32JskzHahlZ.mHv03//MMB6eE5q33Pu3ehyStdrTtWm', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:39'),
(73, 180012, 'KARA COLUMBA', '', 'RABANAL', 'krabanal2018@student.nbscollege.edu.ph', '$2y$10$3z69plwChxnULcpOP4YsEOQFzEdddCZ8am37maMihX3w/D0RNzbZq', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:39'),
(74, 200067, 'AZZEL IVAN', '', 'WEE', 'awee2020@student.nbscollege.edu.ph', '$2y$10$jNZ.I7C9x6PqdAr8sz15GejS8cXoPwDi6NBTk0peq4cjLuJ8KOnTC', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:39'),
(75, 200026, 'JERALD', '', 'YSAAC', 'jysaac2020@student.nbscollege.edu.ph', '$2y$10$OtdmaAxNhltP4hXECMbAv.tOjK18pnkKNYCgGJpCWkORu8lsiv5t6', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:39'),
(76, 230034, 'ALDRIN', '', 'BOJILADOR', 'abojilador2023@student.nbscollege.edu.ph', '$2y$10$wTts03cd5KWduLTrilqeLOaFF45.6NDJq3xmXx714g8MqrbwPyHFS', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:39'),
(77, 230043, 'JOAN MAE', '', 'CAINGIN', 'jcaingin2023@student.nbscollege.edu.ph', '$2y$10$wf/kz.oVd4jpmm42KExsN.93Ix2SX7eYnHlS5wm7CSBCbPS/Ydyje', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:39'),
(78, 230023, 'SEAN YVES', '', 'DE GUZMAN', 'sdeguzman2023@student.nbscollege.edu.ph', '$2y$10$rocfnqZxwK/1c.I8wFzje.GYGa8rhRVQnvL5BEuYgUwijbvzwT3TS', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:39'),
(79, 230011, 'AARON CARL', '', 'DIÑO', 'adiÑo2023@student.nbscollege.edu.ph', '$2y$10$D2zTL3XbYc2FIatuS8EGhesuFCEyq39.C8mp1sons46ZC8vZoLjaa', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:39'),
(80, 230013, 'NORAMIE', '', 'USMAN', 'nusman2023@student.nbscollege.edu.ph', '$2y$10$38wqA2j1U5SeBzfX862.ye8tziPqYIa0ZV8HVJUA4C0Pxqfc.84Wi', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:40'),
(81, 220025, 'PATRICK JAMES', '', 'DE QUIROZ', 'pdequiroz2022@student.nbscollege.edu.ph', '$2y$10$YYlEwujlZWtSThgb43LBiejFbGuGHzEzak.Uz7upM5YmUVToPVj8i', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:40'),
(82, 210024, 'APRIL NICOLE', '', 'CAMPOS', 'acampos2021@student.nbscollege.edu.ph', '$2y$10$uXyJ7FrThzxM5uknV6oj8eyZVck0wc.dOlVda87gTv/uC6NVsNHUC', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:40'),
(83, 210057, 'SYRA', '', 'LANSANG', 'slansang2021@student.nbscollege.edu.ph', '$2y$10$Rw6gqqPp3Uz5xhlX.XhozOxZPuom.n2whPcigKrn5QYh7yUDYV36G', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:40'),
(84, 210012, 'LIEZELLE', '', 'SABLAWON', 'lsablawon2021@student.nbscollege.edu.ph', '$2y$10$UeDW.aCujUnAudBbkyy36.WwEGJpQ..dLFkXl9cV4.RWMCDzpRbHq', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:40'),
(85, 210059, 'LARAMAE', '', 'SANTOS', 'lsantos2021@student.nbscollege.edu.ph', '$2y$10$Hy9sFYcnh5.SH5yGPReVZ.KNwDwR3EEWRxBnEOdCU3BdRJwfgO4ka', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:40'),
(86, 210008, 'CHARMAINE', '', 'VILLARMIA', 'cvillarmia2021@student.nbscollege.edu.ph', '$2y$10$bgFs33.UxdDpDuYi1GYgeeeJzQm63Sg/MJ/axy.ehyosfGMg0BdPu', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:40'),
(87, 220036, 'JOHN MATTHEW', '', 'VILLARUBIA', 'jvillarubia2022@student.nbscollege.edu.ph', '$2y$10$9MN7z7hP4BqCndtS.XxaMew31oKtm2t/rav9n6wc/xGu5o3LWQdu6', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:40'),
(88, 200028, 'AUBREY', '', 'DULAG', 'adulag2020@student.nbscollege.edu.ph', '$2y$10$mRr.rcSlK0zyqi1NvMEnquoK4DOrvT.x21Xeu7jXOYAp3Im3Kcd1i', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:40'),
(89, 180036, 'WILHELM ELIJAH', '', 'FERRER', 'wferrer2018@student.nbscollege.edu.ph', '$2y$10$y37bF4IFxYkao4qnmVgp6.l25dAND9nu4AIHP8g1hG4ES72nhzvQK', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:40'),
(90, 200048, 'ADRIAN', '', 'MANDAWE', 'amandawe2020@student.nbscollege.edu.ph', '$2y$10$2P5E3.ageCljZszsO1oZ9O2yCW/OqGZQMdqX8emTb1rlXp6N4WP8O', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:41'),
(91, 210029, 'JULIA CAITLIN', '', 'PIAMONTE', 'jpiamonte2021@student.nbscollege.edu.ph', '$2y$10$.7TwaMA/6U5g4J2PfieD5uIfmT2rys4aShzCjU6zaY3EI9CZN5trG', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:41'),
(92, 220039, 'JERAH MANUEL', '', 'SARABIA', 'jsarabia2022@student.nbscollege.edu.ph', '$2y$10$Q8R8JNeTXzTIEVGM1d7VluemI4QcPeguxNdr5.bOKJn.Pj8oJj16a', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:41'),
(93, 200029, 'JIM LUIS', '', 'VILLACILLO', 'jvillacillo2020@student.nbscollege.edu.ph', '$2y$10$itLUMHcIjekOW8quYo9oSuLdgC2a1sQAuU/PLCeXB1.3YTqGMUCIu', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:41'),
(94, 220004, 'MAXELL JAMES', '', 'ABUTIN', 'mabutin2022@student.nbscollege.edu.ph', '$2y$10$xLN//5Ujq12DtLk/GhilBO.WR56911wUY64KxRTHxwS8Tg1RgjYoS', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:41'),
(95, 230036, 'CASSANDRA', '', 'AOANAN', 'caoanan2023@student.nbscollege.edu.ph', '$2y$10$PVApZvLc.da2Gw9ZjFJIOe8nO3AfUZbB5iZGMbu0oo9u9hYyQGWIe', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:41'),
(96, 230031, 'JUDE MARTIN', '', 'BALLADOS', 'jballados2023@student.nbscollege.edu.ph', '$2y$10$QMOcNu.vtPi70dHxiMw0n.NBAH/ghznylSa.NLliNQZG8cK5AH7OG', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:41'),
(97, 230026, 'JENNY ROSE', '', 'COLIS', 'jcolis2023@student.nbscollege.edu.ph', '$2y$10$0/PKLCtGkJGkxysl2A8bCumy6RuaMOxnFEjpd0shBib6WFjSpld96', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:41'),
(98, 230035, 'JAKE', '', 'COMIA', 'jcomia2023@student.nbscollege.edu.ph', '$2y$10$p0.kpkWC3gGAWRgkg6vd9OTV1WA1l1wjEfNw3ZSIwsVoAL1Bg.cKi', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:41'),
(99, 230029, 'MATILDA ABIGAEL', '', 'DALISAY', 'mdalisay2023@student.nbscollege.edu.ph', '$2y$10$JHv09Ob3MTgYkH2cIY8kT.1OLgI8U.y3tAd8Ug3FqOTSwoFsHId7q', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:42'),
(100, 230018, 'JOANNE MAY', '', 'DELA CRUZ', 'jdelacruz2023@student.nbscollege.edu.ph', '$2y$10$mRhPM6xqkkbuw//xukZCyuZ.zFobVedfcHaBidEWpWEgrF992zdLG', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:42'),
(101, 230038, 'RACHELLE MAE', '', 'EBIAS', 'rebias2023@student.nbscollege.edu.ph', '$2y$10$3OFQp4F3iHKyEl8IKjoTruOXJqwujd7sBIt0Otrm7kxra59eDswn.', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:42'),
(102, 230041, 'COLLINE FIONA', '', 'GUASCH', 'cguasch2023@student.nbscollege.edu.ph', '$2y$10$D/CXwl0Zj3GqwLG9tP75suDXOCW7yfIaHrNxkNVZ8WstnQEWJRxO.', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:42'),
(103, 230008, 'PRINCESS JUVY', '', 'HIBAYA', 'phibaya2023@student.nbscollege.edu.ph', '$2y$10$yKmtJQ2n1/.7PGCWHnPYauiUO8xEy8FgIeGtnwJyYiABoejs6xck.', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:43'),
(104, 230040, 'ARVIC JOHN', '', 'LIM', 'alim2023@student.nbscollege.edu.ph', '$2y$10$WyI0be5Mw8yzlS1Ap3cLY.Zju81zUKAckalFEkHFw7aiBxFy99Y/i', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:45'),
(105, 230017, 'GUIA', '', 'MAHOMITANO', 'gmahomitano2023@student.nbscollege.edu.ph', '$2y$10$qoNRylH34hw.At2JaCiiMenJkis083V2m9xLsd4yGYUUcQOO3E/2S', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:45'),
(106, 230002, 'JUDITH', '', 'MANTILLA', 'jmantilla2023@student.nbscollege.edu.ph', '$2y$10$b5i5KrWcf6bLAYbKdGLrb.tROvJdT8mMj2ZbyMAyprYiP4SM6heQ2', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:45'),
(107, 230024, 'BRENT ALLEN', '', 'PIDUCA', 'bpiduca2023@student.nbscollege.edu.ph', '$2y$10$1S5jzUlgSkKu5C2Lrz0XferhNwtMr67qVj3v6hMS7GTcF2SpzdR72', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:45'),
(108, 220045, 'LARREI CHRUSZLE', '', 'PINEDA', 'lpineda2022@student.nbscollege.edu.ph', '$2y$10$V/5IBBoBCGtyIkBUCOttXeEWMJLGEmAe2pr3eLJD4/04tjkoXi3oW', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:45'),
(109, 230006, 'ROSEMARIE', '', 'PUENLEONA', 'rpuenleona2023@student.nbscollege.edu.ph', '$2y$10$.cRvev3ornzbs2VBJB5fVet1gDNmLjmetta0KVZfs.x.xLeRFmgiq', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:46'),
(110, 220035, 'DIANA MAE', '', 'SALCEDO', 'dsalcedo2022@student.nbscollege.edu.ph', '$2y$10$MiSHaVTCE26dZi6EBoUjr.FHdPfj4qcxPFn6a18yJwkASn36Pi0EO', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:46'),
(111, 230042, 'JASTINE CLARK', '', 'SAMILLANO', 'jsamillano2023@student.nbscollege.edu.ph', '$2y$10$ZbP9rVX.4s5hN5.SRUr0v.3pIHkSriawDoMEGyJVUPt.CWmxG19pW', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:46'),
(112, 230025, 'JEAN WRANCES', '', 'TALBANOS', 'jtalbanos2023@student.nbscollege.edu.ph', '$2y$10$G2hrxql0ESvXWcS5D0jfa./HRIdg76LQ1kA1fBNE5C16ywiu/cqYW', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:46'),
(113, 210018, 'MICHA ANJELLA', '', 'ABUTIN', 'mabutin2021@student.nbscollege.edu.ph', '$2y$10$FHSSxmEj9rB0NoCBmrghaujkhIkcoYCFb2M7c/2buu3SGgTCfE4X6', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:46'),
(114, 200010, 'FRANCES JAZMIN', '', 'AMORA', 'famora2020@student.nbscollege.edu.ph', '$2y$10$c94OrZCGK0sl6VgMp/xrwempDgRMwF/TqAneVZ4.mqr8ObKJXwCXS', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:46'),
(115, 210062, 'HANNAH GRACE', '', 'TERANA', 'hterana2021@student.nbscollege.edu.ph', '$2y$10$jQ67fEVd5hZEE5pU1Avxye3fjEPA92hmpZ.iefKm2OlAKsux9Vp2G', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:46'),
(116, 220009, 'VERA FE FAYE', '', 'UY', 'vuy2022@student.nbscollege.edu.ph', '$2y$10$8W5IbyQ2h.xvliHntvI7gulfj3nhMk8w7XeKjjGYwHwHaQg6MPCny', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:46'),
(117, 220030, 'CARLOS MIGUEL', '', 'CAMACHO', 'ccamacho2022@student.nbscollege.edu.ph', '$2y$10$K.uxR0TcTURzDQqRuF6ajeaMQbi66hwk5G1uTdyqOJAruvFclaBYm', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:46'),
(118, 220026, 'ARTURO MIGUEL', '', 'CRUZ', 'acruz2022@student.nbscollege.edu.ph', '$2y$10$WEyQNBiU0UZo3m3ZD9iW0u3BM4lDsELJ/obOoa2FvBEZ5oqzJfxE6', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:46'),
(119, 190025, 'CARLA JOYCE', '', 'LEDESMA', 'cledesma2019@student.nbscollege.edu.ph', '$2y$10$3U1c.N4SQ2l25Kpy0jd0S.ievpq3v653n6.zn0KrxDNmxkIJj7FjC', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:47'),
(120, 180011, 'JOHN JERRICHO', '', 'PORCIUNCULA', 'jporciuncula2018@student.nbscollege.edu.ph', '$2y$10$J9m9OnrCC01pjmUiffia3.fy5awA0SUJa49X3k6ASyRmCQfOp.ZE2', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:47'),
(121, 220022, 'MARTINNE CHRISTIAN', '', 'ROSARIO', 'mrosario2022@student.nbscollege.edu.ph', '$2y$10$vW50Td5cBatGZU8Oyc47lezPKMDrKGwjoSyiuzFzDV907Fz6/xCtO', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:47'),
(122, 220029, 'MA. ELOISA', '', 'ACID', 'macid2022@student.nbscollege.edu.ph', '$2y$10$YSjqGgbQuAFqBXAaAO7RnehShAz/nwf1iJpT7g5q4Gt06wRmnaRri', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:47'),
(123, 200047, 'ISAIAH DANIEL', '', 'DECEPIDA', 'idecepida2020@student.nbscollege.edu.ph', '$2y$10$dyIFSpcNoQHitHe28SF5o.LxvNLOX0P32kVII97dgOLovQHQHl0EW', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:48'),
(124, 200052, 'LARA MAE', '', 'DUCAY', 'lducay2020@student.nbscollege.edu.ph', '$2y$10$9EjESNZ8RWSbIxSUj47fSOY.3yO.lvlY9xSikW76HvP58nOie8avu', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:48'),
(125, 200049, 'CRISTEA GHIEN', '', 'GALICIA', 'cgalicia2020@student.nbscollege.edu.ph', '$2y$10$2qMxv.6t8kpMllQsSt9d9.db6GRnqwPYhvOeMEH1UV7qMt2uaVXBO', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:48'),
(126, 200055, 'MA. MELODY', '', 'MERCADEJAS', 'mmercadejas2020@student.nbscollege.edu.ph', '$2y$10$wk6fZF7L7IU4MK6ufRl59uz2BrtP.qpoRIjeOnXu6JzF1rhzaRbpe', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:49'),
(127, 200016, 'LOVELY ROSE', '', 'MONTOYA', 'lmontoya2020@student.nbscollege.edu.ph', '$2y$10$78mOaPhjezod2uxDdeuB2OduaIcDJKoIgX3304BObideXPlbJ2uPG', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:49'),
(128, 180033, 'CHLOIE', '', 'ONG', 'cong2018@student.nbscollege.edu.ph', '$2y$10$mxHTG3Q71FPN9E/yNUPS5eugeiovlO.fMmK6ulWv8L3aLlVp5tvLu', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:49'),
(129, 200054, 'SIGRID COLYNE NAOMI', '', 'PAZ', 'spaz2020@student.nbscollege.edu.ph', '$2y$10$DolwTB1.J26YiELpFsdCZumaYhRTTQUrrwdo./6BbjwOPLKCk3Z3m', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:49'),
(130, 200068, 'IRISH MAE', '', 'RAPAL', 'irapal2020@student.nbscollege.edu.ph', '$2y$10$5hLZerfRKC2rEEqpnUkJ6O/TeogkHqjTr4wDwPuwHQxL5GZDT18Ly', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-07 13:08:49');

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
(2, 'Alya', 'B.', 'Honasan');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
