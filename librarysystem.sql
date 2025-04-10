-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2025 at 02:30 PM
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
(1, 210078, 'Kenneth', '', 'Bonaagua', 'kbonaagua2021@student.nbscollege,edu.ph', '$2y$10$aXsTy2iwm.jsgCLQ7TSU8.aEvWuevKdHutg2kHpt0CHcWhnVootHe', '../Images/Profile/default-avatar.jpg', 'Admin', '2025-04-10', NULL, NULL, NULL, NULL);

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
(1, 256, 'sakjjskahdhaskjhd', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 1, '', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 1, '2025-04-10', 'Available', NULL, '2025-04-10'),
(2, 257, 'sakjjskahdhaskjhd', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 2, '', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 1, '2025-04-10', 'Available', NULL, '2025-04-10'),
(3, 258, 'sakjjskahdhaskjhd', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 3, '', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 1, '2025-04-10', 'Available', NULL, '2025-04-10'),
(4, 3234, 'skjhakshdaksjhda', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 1, '', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 1, '2025-04-10', 'Available', NULL, '2025-04-10'),
(5, 3235, 'skjhakshdaksjhda', '', '', '', '', '', '', NULL, NULL, '', '', '', '', 2, '', '', '', 'Text', 'Print', 'Book', '', '', 'English', 'TR', 1, '2025-04-10', 'Available', NULL, '2025-04-10');

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
(1, 68, 1, 'Returned', '2025-04-10', 1, '2025-04-17', '2025-04-10', 1, NULL, NULL, 0),
(2, 68, 5, 'Returned', '2025-04-10', 1, '2025-04-17', '2025-04-10', 1, NULL, NULL, 0),
(3, 68, 1, 'Returned', '2025-04-10', 1, '2025-04-17', '2025-04-10', 1, NULL, NULL, 0),
(4, 68, 5, 'Returned', '2025-04-10', 1, '2025-04-17', '2025-04-10', 1, NULL, NULL, 0),
(5, 68, 1, 'Returned', '2025-04-10', 1, '2025-04-17', '2025-04-10', 1, NULL, NULL, 0),
(6, 68, 5, 'Returned', '2025-04-10', 1, '2025-04-17', '2025-04-10', 1, NULL, NULL, 0),
(7, 68, 1, 'Returned', '2025-04-10', 1, '2025-04-17', '2025-04-10', 1, NULL, NULL, 0),
(8, 68, 5, 'Returned', '2025-04-10', 1, '2025-04-17', '2025-04-10', 1, NULL, NULL, 0),
(9, 68, 2, 'Returned', '2025-04-10', 1, '2025-04-17', '2025-04-10', 1, NULL, NULL, 0),
(10, 68, 5, 'Returned', '2025-04-10', 1, '2025-04-17', '2025-04-10', 1, NULL, NULL, 0),
(11, 68, 3, 'Returned', '2025-04-10', 1, '2025-04-17', '2025-04-10', 1, NULL, NULL, 0),
(12, 68, 5, 'Returned', '2025-04-10', 1, '2025-04-17', '2025-04-10', 1, NULL, NULL, 0),
(13, 68, 3, 'Returned', '2025-03-03', 1, '2025-03-10', '2025-04-10', 1, NULL, NULL, 0),
(14, 68, 5, 'Returned', '2025-03-03', 1, '2025-03-10', '2025-04-10', 1, NULL, NULL, 0);

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
  `payment_date` date DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT NULL,
  `invoice_sale` decimal(20,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fines`
--

INSERT INTO `fines` (`id`, `borrowing_id`, `type`, `amount`, `status`, `date`, `payment_date`, `reminder_sent`, `invoice_sale`) VALUES
(1, 13, 'Overdue', 154.79, 'Unpaid', '2025-04-10', NULL, NULL, NULL),
(2, 14, 'Overdue', 154.79, 'Unpaid', '2025-04-10', NULL, NULL, NULL);

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
(1, 1, 0, '2025'),
(2, 2, 0, '2025'),
(3, 3, 0, '2025'),
(4, 4, 2, '2000'),
(5, 5, 2, '2000');

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
(1, 'asd', 'sda'),
(2, 'wasd', 'wasd');

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
(1, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Bonaagua added \"sakjjskahdhaskjhd\" with 3 copies', '2025-04-09 22:06:15'),
(2, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Bonaagua added \"skjhakshdaksjhda\" with 2 copies', '2025-04-09 22:09:05'),
(3, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Bonaagua Logged In as Active', '2025-04-10 10:23:44'),
(4, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Bonaagua Logged In as Active', '2025-04-10 10:40:13');

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
(1, 230016, 'MARC ED', '', 'EBRADO', 'mebrado2023@student.nbscollege.edu.ph', '$2y$10$bctd1e/Lbhmy2uvVWIQCGeL.1J7V4tTQRkBtN78AoHv6llCJE1P5.', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:18'),
(2, 230033, 'STEPHANIE', '', 'ESPEJON', 'sespejon2023@student.nbscollege.edu.ph', '$2y$10$Z89MlxJp.Wk0IUn8jmbmOe.qzQH84HcXEhUBrWEneobf0KE79g09.', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:18'),
(3, 230030, 'GIRLIE GAIL', '', 'GALLARDO', 'ggallardo2023@student.nbscollege.edu.ph', '$2y$10$eUSAwYdkVKalCn2PN2ukMOcdGuxb33XarKOqi65TyLBdck/TRsdi6', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:18'),
(4, 230019, 'JESSA', '', 'MADANLO', 'jmadanlo2023@student.nbscollege.edu.ph', '$2y$10$QU5OrkC2Bq6ib9G1ZjDNlORWFqdDcLkvzoiL2jxf4NFG7SFLrvZSK', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:19'),
(5, 230003, 'VINCELYN', '', 'MOZOL', 'vmozol2023@student.nbscollege.edu.ph', '$2y$10$NShZZtovHavbPk5teXae1OFWYO/QaR3Y.TtZ0cSVsKbNN6bxTkm..', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:19'),
(6, 230037, 'REGINE', '', 'OCAMPO', 'rocampo2023@student.nbscollege.edu.ph', '$2y$10$fU2mz9b7cFNXuymOIT1ZU.j95XuVBnhvpvJAimUY68nx1zl9j784u', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:19'),
(7, 230044, 'MONIQUE', '', 'PACAMARRA', 'mpacamarra2023@student.nbscollege.edu.ph', '$2y$10$WIJAz0l6V2ytSBv0fu74/uPoFIbdeHOnStsuXqjq61xuPvC1STxju', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:19'),
(8, 220054, 'CHEEZER JANE', '', 'POWAO', 'cpowao2022@student.nbscollege.edu.ph', '$2y$10$NWPVuwqsL/2kYIyod2YR.OwzAX7hd3Ftv.mrGQs2wt6HFWZQ448Fq', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:19'),
(9, 230027, 'JOHN RENZ', '', 'REJANO', 'jrejano2023@student.nbscollege.edu.ph', '$2y$10$HeU8bZFklZub9qFdgYHDYufaGHB/9Yxeq2dIytUqapACBAI/DZ1..', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:19'),
(10, 230009, 'RHOCA', '', 'TINAY', 'rtinay2023@student.nbscollege.edu.ph', '$2y$10$F958ID5FHv2EqUbpMhuriOPzLHdJmI85YtdqdmgMtExeSHks50Num', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:19'),
(11, 230005, 'JOSE CARLOS', '', 'VILLANUEVA', 'jvillanueva2023@student.nbscollege.edu.ph', '$2y$10$oL3Ens0/MBEBQq73GU5UzOaQHlos7QwY3NwFCxRBRIunosONHQU6m', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:19'),
(12, 210027, 'RANNIE', '', 'ASIS', 'rasis2021@student.nbscollege.edu.ph', '$2y$10$ibbBB6QPVItJ3A/umPYlGuDbzfjQkUq63maEg07nMji2ipHdesVDe', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:19'),
(13, 220055, 'KAREN MAE', '', 'BALICTAR', 'kbalictar2022@student.nbscollege.edu.ph', '$2y$10$GbyPwbuYJTe4VKE4TdJ9SefKXIMEdfVQAfh4eyIiMN6nxIjmUc9iO', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:20'),
(14, 220021, 'JAMES LHARS', '', 'BARRIENTOS', 'jbarrientos2022@student.nbscollege.edu.ph', '$2y$10$85wwnsyiLmADj6v3iZBWCe/RFVRNLfHsj.Jw8GauxUsS.lbm3..K6', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:20'),
(15, 220047, 'NOEMI', '', 'LAURENTE', 'nlaurente2022@student.nbscollege.edu.ph', '$2y$10$uwYfgU8Mmsu8cOY/D.2F0OjydeHPXPmCX2S40YTijod2QyNDpQo4q', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:20'),
(16, 210061, 'ANGELA CLAIRE', '', 'PANAHON', 'apanahon2021@student.nbscollege.edu.ph', '$2y$10$P7fukLoxAM/gpO7tv0wnFuVoQO4bkE1V4aTm7w9QUJnd52qoLlJ8W', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:20'),
(17, 220007, 'MA. KATRINA', '', 'SANTOS', 'msantos2022@student.nbscollege.edu.ph', '$2y$10$DPWjmhFK2Xx5oR8YLV9P6euyDgGXaQ1vQI8E8l/AAUFHsE5l1/K4u', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:20'),
(18, 210037, 'LEIRA', '', 'SINAGUINAN', 'lsinaguinan2021@student.nbscollege.edu.ph', '$2y$10$/BIDtygolUg6k4rrqkX03ugnQI.OG5rzyLIgPjw52hqb9NFcqDVGC', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:20'),
(19, 210014, 'ANGELINE', '', 'ACEBUCHE', 'aacebuche2021@student.nbscollege.edu.ph', '$2y$10$fLpOJBRp8RBQAzeaO4sFauxsGeDNJoL/cDK.CFvDqlWKNIevsn21K', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:20'),
(20, 210044, 'MAY ANN', '', 'BAYOD', 'mbayod2021@student.nbscollege.edu.ph', '$2y$10$taEfDsPG2UewzdERv9BhWOnFhJTUuL33zXrdrSj3UMghH3piOop..', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:20'),
(21, 210033, 'PATRICK DOMINIC', '', 'CORRE', 'pcorre2021@student.nbscollege.edu.ph', '$2y$10$TKCQwiL2vLqxp7GWO7/XGe8fIYhng60VarlofEMPSPRCuJnX9iLLu', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:20'),
(22, 220024, 'JOHN AARON PAUL', '', 'GACHO', 'jgacho2022@student.nbscollege.edu.ph', '$2y$10$YLUgK48HbIrWRVJPUG5zWuosvqno99t4a7oS1a1Dv1iArnkXPv3BK', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:21'),
(23, 220012, 'ALLIANA MARIEL', '', 'GONZALES', 'agonzales2022@student.nbscollege.edu.ph', '$2y$10$dSrUo0nWc1qSLlogIY1VsOIVSOTnkW0hs86XzdiQWFUKim8jQpnFS', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:21'),
(24, 210050, 'ANGELI', '', 'QUINTO', 'aquinto2021@student.nbscollege.edu.ph', '$2y$10$.kbuXR3Qrm/yAs4T/z8ff..QjFVhGOnj.dxjwDsrieqfw.FTP9Sd2', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:21'),
(25, 210070, 'MARIA SHEKINAH ELIZABETH', '', 'SAN JUAN', 'msanjuan2021@student.nbscollege.edu.ph', '$2y$10$7ehI6WJtSK/TcxqwaMnycOhdSljhdApcMaaHNQMkPXJZBtHl32WHC', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:21'),
(26, 210060, 'LYNLEN', '', 'SINGCO', 'lsingco2021@student.nbscollege.edu.ph', '$2y$10$XJQwDSawwHQxfX.uOLTer.BRirHBbZZogtfpC/BwpfLUumxrYM/O2', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:21'),
(27, 210022, 'ROSHIELA MAY', '', 'SOLDEVILLA', 'rsoldevilla2021@student.nbscollege.edu.ph', '$2y$10$MC55LlTv6bu5dRS76i0E.urcHe09rP8bqG29naHfKmOj0NZFm1K22', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:21'),
(28, 210031, 'JAY FOARD', '', 'TRAJE', 'jtraje2021@student.nbscollege.edu.ph', '$2y$10$x2U6YhAIaadzj7tmpbs4rOc0.nn/Ruj6FA/lqq4mvljoGaeQq14Na', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:21'),
(29, 210058, 'AIKA MARIE', '', 'YBAÑEZ', 'aybaÑez2021@student.nbscollege.edu.ph', '$2y$10$URrVxxyHuC12CtFmbsXuJOVTSzkxPlFhme0OgYR3voqlRaQ4Oq2e6', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:22'),
(30, 200043, 'JHEDALYN', '', 'DACQUEL', 'jdacquel2020@student.nbscollege.edu.ph', '$2y$10$cMIAhgtml7TIaGB1wTKqn..L0zS96aVtnaeRzk8c/UJVtO8kQGAPO', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:23'),
(31, 200039, 'JOYCE ANN', '', 'MERAS', 'jmeras2020@student.nbscollege.edu.ph', '$2y$10$pouVEdXisIYOu1I8vbTp5O8O7pPGhHaRHoxYLVvWdoGWLhUBRBhyW', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:23'),
(32, 200019, 'ELVERT-ACE', '', 'NERI', 'eneri2020@student.nbscollege.edu.ph', '$2y$10$NrynBDOFN2fC315cVDuU7eyoyqUXLOVHA7w9IgtlJ9hUyeAnoLZme', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:23'),
(33, 230032, 'ALEXZA', '', 'IGNACIO', 'aignacio2023@student.nbscollege.edu.ph', '$2y$10$gKgM4e0VN2N2xuk.XQwpZ.L.AZ/5akZOzKUFSHKrEL2Atg2bSxGqG', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:23'),
(34, 220018, 'JERALD', '', 'BALAO', 'jbalao2022@student.nbscollege.edu.ph', '$2y$10$dK8gKLjkoys/5Mcfx/hFUO8t8myTwswHb8uSLV.oOu1WW.EWtxd2.', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:24'),
(35, 210016, 'CRIS ALFRED', '', 'PANLUBASAN', 'cpanlubasan2021@student.nbscollege.edu.ph', '$2y$10$KjxLtQ2MHf0Mkkfc.CG10uudgwXmD/djUaSJNMNKJ6MY0uTWnzG7q', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:24'),
(36, 190026, 'FRANCES MARGARETT', '', 'PEDOCHE', 'fpedoche2019@student.nbscollege.edu.ph', '$2y$10$NetTP8VWqPw3ADtcH3oZge7aWznuL0CBEjqek9dU1CaN51oxSyYgK', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:24'),
(37, 210049, 'RICHELLE MAE', '', 'CADORNA', 'rcadorna2021@student.nbscollege.edu.ph', '$2y$10$OPCdLNDbzxK825/lPvWJke0Sa962q6bdbxZAD0h3v2mNbVX88s6TS', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:24'),
(38, 210038, 'MARIA ROSALINDA', '', 'GALLO', 'mgallo2021@student.nbscollege.edu.ph', '$2y$10$jsS5MtTrqnmjD5yCV4reaedlSH/mSZavFhWztwil/cKzjTHbLz1uq', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:24'),
(39, 210056, 'JANE AUSTIN', '', 'LANSANG', 'jlansang2021@student.nbscollege.edu.ph', '$2y$10$SwXqa/9y6mWyz7IStYL4EOO0.4OU/nzR/RmcZOodSHUSrmJnVzfKK', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:24'),
(40, 190034, 'JELLIANE', '', 'ALARCON', 'jalarcon2019@student.nbscollege.edu.ph', '$2y$10$6RanwLqkdztcQ7ixyiYq1eQn4zjlT4XkhyYDibOSPhbexi8MBnCHe', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:24'),
(41, 200013, 'RIZA JEAN', '', 'ELLERAN', 'relleran2020@student.nbscollege.edu.ph', '$2y$10$DrnSVFwOz3PGXX1x3W7DTu3..P6SvDb/hJ/xNh7pyB4Va8wQdFba.', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:24'),
(42, 190088, 'JEREMIE', '', 'GIGANTE', 'jgigante2019@student.nbscollege.edu.ph', '$2y$10$.XIsjZmzCko6hasxFnvTje7J2jR0ITpMqeh3i18CzamM6tR50qHle', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:25'),
(43, 200024, 'JOSEPH IVAN', '', 'GREGORIO', 'jgregorio2020@student.nbscollege.edu.ph', '$2y$10$qMoJqswSQW0.d7.16Gmrr.ytxsy309XrnfnkzQ2A.sY29tDspuk7u', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:25'),
(44, 200032, 'KINJI', '', 'LABAY', 'klabay2020@student.nbscollege.edu.ph', '$2y$10$3BGUUVFx7bdph7hkaZl/9eNdWeisOpCXqexhNq/q9nlyB5giBnwqa', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:25'),
(45, 200065, 'CHELSEA CHARMILANE', '', 'MULDONG', 'cmuldong2020@student.nbscollege.edu.ph', '$2y$10$SHQ4tr03NSYXldAMIgNnQOAuzSNqP4xkJHpAAFmUbOuTnMsIFtc0m', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:25'),
(46, 190004, 'GWYNETH VEAH', '', 'OLIVER', 'goliver2019@student.nbscollege.edu.ph', '$2y$10$Zeetuylr9UyxyY.qieN/W.p.gJvdFP2Lntep3yXqXra7.qPnsuyQm', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:25'),
(47, 200042, 'DANIELA SHANE', '', 'PONTIMAR', 'dpontimar2020@student.nbscollege.edu.ph', '$2y$10$1bsR1d79Gr1NGaL/87In3e7Wjf/GIpkljpPDscREXmdWShDwQqY2u', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:25'),
(48, 180004, 'IRICE DANIKKA', '', 'YALUNG', 'iyalung2018@student.nbscollege.edu.ph', '$2y$10$V36Y54vyq48r8r2Tv/4ON.ioPk3F8U.1jcgo4mL1GZJdDPFJquirW', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:25'),
(49, 230020, 'EMMANUEL', '', 'ABEJO', 'eabejo2023@student.nbscollege.edu.ph', '$2y$10$MEhU/cJ1FIff4ts/Dc80vOwh4PJaefC/q9FgggE.BOUxw0KBt2MEe', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:25'),
(50, 230046, 'RHYZEN', '', 'BUMANLAG', 'rbumanlag2023@student.nbscollege.edu.ph', '$2y$10$XWY7nY8Y5ZDFY3cSCnelg.SyRM7rvO2NMgRpEzCBqXmSj5AXpbrJa', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:25'),
(51, 220056, 'MARY ANN', '', 'CEDILLO', 'mcedillo2022@student.nbscollege.edu.ph', '$2y$10$liZA0v1fIOShmMEKWh1b4uRYDzxTTQFB1tG7pkxH/rYwGDmaD.fwm', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:25'),
(52, 230007, 'RHOD LENARD', '', 'DELAS NIEVES', 'rdelasnieves2023@student.nbscollege.edu.ph', '$2y$10$W0L98U/38Raid5NfvHetXu/UhwLvpic8tDtILLXqRRhc6c3TpAkLO', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:26'),
(53, 230022, 'KATE ANDREI', '', 'DOSAL', 'kdosal2023@student.nbscollege.edu.ph', '$2y$10$cxXb./Mx7GmETYyC3KhfT.03vCNTWdNp22nVd7s02UapaBa5.OWZW', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:26'),
(54, 230021, 'JAVI AEREM', '', 'FAVIA', 'jfavia2023@student.nbscollege.edu.ph', '$2y$10$EuQZ4eUY8GJDzKLoPoZYA..Fo9qsPXtXvwD6okO9hZxdKybBjI9Au', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:26'),
(55, 230004, 'RENARD KEN', '', 'HAPIL', 'rhapil2023@student.nbscollege.edu.ph', '$2y$10$C72CIpC1LNp5EfTygKZ16.xVUYYnuseMytWdcUbljqJitwOC1K8gG', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:26'),
(56, 230012, 'DANDIE', '', 'LAQUINTA', 'dlaquinta2023@student.nbscollege.edu.ph', '$2y$10$vHC3GhPYc939Z1O5fqq3DOGGg0GAxs8Xb2EMWQGKFfr8/jEcY/kTW', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:26'),
(57, 230028, 'JHON KENNETH', '', 'LIMATOC', 'jlimatoc2023@student.nbscollege.edu.ph', '$2y$10$mzCsD7kLgS3p4R1hCic2BeDq2CkzlkkZeKpI6w3SDjszsLjjbhHvC', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:26'),
(58, 230010, 'GRACE ANNE', '', 'LOGRONIO', 'glogronio2023@student.nbscollege.edu.ph', '$2y$10$T4b99lIxf1o1C1sTjfc/mOJZvXfY4ie9YT6y5hm9rQbcmDsGTsji2', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:27'),
(59, 230045, 'PAOLO', '', 'MALICSE', 'pmalicse2023@student.nbscollege.edu.ph', '$2y$10$1e8fb2nX14.fHvVTm8qRi.ZTeacJIt0O27YtrbrqPrqATU0/JodDC', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:28'),
(60, 230039, 'FRANCIS ANGELO', '', 'ORILLANEDA', 'forillaneda2023@student.nbscollege.edu.ph', '$2y$10$yVzbWh8GNJL0x7EQfTCwR.VYhygMFxAT.WGErN.hWXV.JBEON4Lsq', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:29'),
(61, 230015, 'MICHAEL LORENZ', '', 'TAN', 'mtan2023@student.nbscollege.edu.ph', '$2y$10$P7D.HSanxT.8HA9RXZa3TeJsshJ30airvIpYGoPoLKRLZhD0ky9oi', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:29'),
(62, 220050, 'ARMAN BERNARD', '', 'CABANG', 'acabang2022@student.nbscollege.edu.ph', '$2y$10$m2wdjcNsuNbchMAOWrkQEe9D5rPdUjFO0nRGBWyezEZrUdAnE/Rty', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:29'),
(63, 220057, 'YZRAH', '', 'GASPADO', 'ygaspado2022@student.nbscollege.edu.ph', '$2y$10$OrHvNh70PHhoZpX80hZ3A.M/irsKAR4PVHPUD2ktnQfsKR9dtKWSe', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:29'),
(64, 220017, 'BRIAN CHRISTIAN', '', 'HEBRON', 'bhebron2022@student.nbscollege.edu.ph', '$2y$10$kMJslPco5YReK84RDY2Xoelr1TH4CmkwwlLyXWddBekMkQZB.ADhG', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:30'),
(65, 220048, 'AJLEIN', '', 'MONTERO', 'amontero2022@student.nbscollege.edu.ph', '$2y$10$eY1nHTFBmPL5zXdn5xcHnug8.UugoH9ZwneaTZEScJm8fQxrdDsIO', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:30'),
(66, 230014, 'JAY FRANKLIN', '', 'ROLLO', 'jrollo2023@student.nbscollege.edu.ph', '$2y$10$1teMIaPr5QqQbWS/nneXv.Ug8xnA5Fpj75xVkglisErkO1KdSj.R2', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:30'),
(67, 210079, 'CARLOS MIGUEL', '', 'AUTOR', 'cautor2021@student.nbscollege.edu.ph', '$2y$10$Fxf9W5yDhRDlkVDOXQmUzeu9Ppwi6AwdcLHtH.GAMUbBE77nWLHfi', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:30'),
(68, 210078, 'KENNETH LAURENCE', '', 'BONAAGUA', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$bZuYicsg3RhxOZBvOh.zLelWzK8cRk.SSCHy0ZaBAhCOxok4ZZxMS', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', '2025-04-10', '', '2025-04-10 20:07:45'),
(69, 220001, 'JOANNA CRIS', '', 'ESCANILLA', 'jescanilla2022@student.nbscollege.edu.ph', '$2y$10$f0LGab6QhaC/2f0V3fN3CO3bwso9uuiE2LdLQ.kS3xK4yigT5sPrW', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:30'),
(70, 210028, 'CAYCE', '', 'EVANGELISTA', 'cevangelista2021@student.nbscollege.edu.ph', '$2y$10$f8WlbjOvIqkTwsAO16wTAeg96zrl..jxIZxDPuvKUckhTWamb8/eq', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:30'),
(71, 210065, 'JENEPIR', '', 'JABILLO', 'jjabillo2021@student.nbscollege.edu.ph', '$2y$10$uV4vQQFApJ4GFTPq6VC2iOs8Jzf7wsw0A8mt8RGji.GyNw62ZBti2', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:30'),
(72, 220003, 'JOSEPH', '', 'SIMANGCA', 'jsimangca2022@student.nbscollege.edu.ph', '$2y$10$wdCXyigx/WQcJUxi3HM0b.AjcU1kX8T9FfM86ktneWrlhgEgi4kQC', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:30'),
(73, 180012, 'KARA COLUMBA', '', 'RABANAL', 'krabanal2018@student.nbscollege.edu.ph', '$2y$10$X7QMQ2ZiXoaEXSukPOsPK.E.3IHiYpsjpeGZX8z5yiTXOxY114yg.', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:31'),
(74, 200067, 'AZZEL IVAN', '', 'WEE', 'awee2020@student.nbscollege.edu.ph', '$2y$10$BaydFArf9UOvarqs22I9ZucvYUHqBgAF9.SIAiRwynhwm/UZDc/zi', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:31'),
(75, 200026, 'JERALD', '', 'YSAAC', 'jysaac2020@student.nbscollege.edu.ph', '$2y$10$n/dvt23LuZ1gMERjyZrxlOhPPJT0e0n7ufosQKiCqmDaoRSRJSQQO', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:31'),
(76, 230034, 'ALDRIN', '', 'BOJILADOR', 'abojilador2023@student.nbscollege.edu.ph', '$2y$10$w3sPF3rYWXpR06Pbx1ZDxeRUg.n53d.71rjiJUnPwHS6NHhYriPQa', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:31'),
(77, 230043, 'JOAN MAE', '', 'CAINGIN', 'jcaingin2023@student.nbscollege.edu.ph', '$2y$10$CWMYzr4M8zpgCHPhFr1ugeVpa9M.Ak.8SqWssa9pdAH5aSqGNbcHG', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:31'),
(78, 230023, 'SEAN YVES', '', 'DE GUZMAN', 'sdeguzman2023@student.nbscollege.edu.ph', '$2y$10$I4.WJfM2aHV02ULilfL6rehKhwRsDjnP/QH.A6mE4SuyiPQf6m/66', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:31'),
(79, 230011, 'AARON CARL', '', 'DIÑO', 'adiÑo2023@student.nbscollege.edu.ph', '$2y$10$B.PXdEkeSplQuA9lPx7Fi.eVt8nPy95qbiVAvcSVepf716wEF38e2', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:31'),
(80, 230013, 'NORAMIE', '', 'USMAN', 'nusman2023@student.nbscollege.edu.ph', '$2y$10$s6S6eQGYlSWlSLkqYW5Vlu4mFDcgM0Obh3lvTYp8UljTdD/2IA2La', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:32'),
(81, 220025, 'PATRICK JAMES', '', 'DE QUIROZ', 'pdequiroz2022@student.nbscollege.edu.ph', '$2y$10$6StsHFulVJXksMFMV61Wku2ERpgPdIbjXlaM6mC4Pps..wE/xyD5G', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:33'),
(82, 210024, 'APRIL NICOLE', '', 'CAMPOS', 'acampos2021@student.nbscollege.edu.ph', '$2y$10$cso/W/OvH2gjOXi3qkTCQue2T2CPMSp/HM3fmJtoUsYYExDhPhwT2', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:33'),
(83, 210057, 'SYRA', '', 'LANSANG', 'slansang2021@student.nbscollege.edu.ph', '$2y$10$sNz.6eUGjQ./J4fEOeEFmunSxMycql9FH7Z7GbwwlBlua/3La9pLC', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:33'),
(84, 210012, 'LIEZELLE', '', 'SABLAWON', 'lsablawon2021@student.nbscollege.edu.ph', '$2y$10$TWyFU5N5y8TE/THTHzG8V.YQMjXdgGgm2CIguAm21YwAHO81UWTH.', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:34'),
(85, 210059, 'LARAMAE', '', 'SANTOS', 'lsantos2021@student.nbscollege.edu.ph', '$2y$10$SWPbtqfS33jJ9jCw4L4IeuH26IG9ntxS0/midqrj7/QI98zazGFbO', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:34'),
(86, 210008, 'CHARMAINE', '', 'VILLARMIA', 'cvillarmia2021@student.nbscollege.edu.ph', '$2y$10$KIitB80EV.buTrFwnv3cxulYo2nlZzS7MbU0hQS7LdPnem26eG4OS', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:34'),
(87, 220036, 'JOHN MATTHEW', '', 'VILLARUBIA', 'jvillarubia2022@student.nbscollege.edu.ph', '$2y$10$Beh6tVkdUAhQVTXbwM8nOO2iRDnfoy8S7e4VNTFSKiWEUz/2srcAq', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:34'),
(88, 200028, 'AUBREY', '', 'DULAG', 'adulag2020@student.nbscollege.edu.ph', '$2y$10$dVrwiVswPA6r/VB8qZmwB.5rzfSV5ev/5EI7L17L.EaLsXWj7xa1i', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:34'),
(89, 180036, 'WILHELM ELIJAH', '', 'FERRER', 'wferrer2018@student.nbscollege.edu.ph', '$2y$10$7/ur.kO2PtuqBLnHAwWMm.iAHE5q1GXhvqor8Yy2DnJuwFiDGi6ze', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:34'),
(90, 200048, 'ADRIAN', '', 'MANDAWE', 'amandawe2020@student.nbscollege.edu.ph', '$2y$10$69mbj2QlsV643qw3vTQddu1ChRQAs4fdX0jN6c/e9eM31B1G8dRki', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:34'),
(91, 210029, 'JULIA CAITLIN', '', 'PIAMONTE', 'jpiamonte2021@student.nbscollege.edu.ph', '$2y$10$B2O3DC2L/PvWs9L6U2O7wOYaZNVhJMrxRi32OV/iheulNE0q08PMC', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:34'),
(92, 220039, 'JERAH MANUEL', '', 'SARABIA', 'jsarabia2022@student.nbscollege.edu.ph', '$2y$10$P8GxqjNKWSbOdxwlWtpTHO/fZY6oVo.VCixAFZxP.zxO/ab4k42tS', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:34'),
(93, 200029, 'JIM LUIS', '', 'VILLACILLO', 'jvillacillo2020@student.nbscollege.edu.ph', '$2y$10$GIiPGWy6yWE2Qb8ihkKDle6o4VKjr0h2stWmIUXB8reloqmsNyAEW', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:35'),
(94, 220004, 'MAXELL JAMES', '', 'ABUTIN', 'mabutin2022@student.nbscollege.edu.ph', '$2y$10$WQRUpaVK38b02Q4VXajhL.jRNxrh784OuAFjnLN1UVAzEH/HhL2Hi', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:35'),
(95, 230036, 'CASSANDRA', '', 'AOANAN', 'caoanan2023@student.nbscollege.edu.ph', '$2y$10$0LUJdRmY1GG6JrQfH8iRz.FV4nqTZnkWUfv/Gw52Svlua7XXW.fp2', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:35'),
(96, 230031, 'JUDE MARTIN', '', 'BALLADOS', 'jballados2023@student.nbscollege.edu.ph', '$2y$10$6uyDytB7MYMuLxeQzEXnBuPjAbxVFycKPcbVX1KSZ.AIGsutNe0CC', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:35'),
(97, 230026, 'JENNY ROSE', '', 'COLIS', 'jcolis2023@student.nbscollege.edu.ph', '$2y$10$.NKddE40fywyDwrodSdUv.duBcST3BzrrnVT6FtuI2qjo0gghM3pi', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:35'),
(98, 230035, 'JAKE', '', 'COMIA', 'jcomia2023@student.nbscollege.edu.ph', '$2y$10$Iok0Ydss7hou6kDqqJ9vMuZRbapIt5h5cKW.tGjvFxrhdJVNhiYl6', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:35'),
(99, 230029, 'MATILDA ABIGAEL', '', 'DALISAY', 'mdalisay2023@student.nbscollege.edu.ph', '$2y$10$wFWTg3Z.jJoEpWW5tTwtt.sjyBwNY0ucHso9IJOJx76O4Rym4yT.m', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:35'),
(100, 230018, 'JOANNE MAY', '', 'DELA CRUZ', 'jdelacruz2023@student.nbscollege.edu.ph', '$2y$10$ymxzGDaUrg7LvbPROaLlfOwOnn8gVUDsQr2V/zk4W44t1fVdYYdwi', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:35'),
(101, 230038, 'RACHELLE MAE', '', 'EBIAS', 'rebias2023@student.nbscollege.edu.ph', '$2y$10$8fIYyGH79oP3XQxza67et.0puRN1hbRxKNwnenBP2wnXQ3LMyMUTq', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:36'),
(102, 230041, 'COLLINE FIONA', '', 'GUASCH', 'cguasch2023@student.nbscollege.edu.ph', '$2y$10$JgFWEZ14oeRWOiiM1HLB4OawjEGRrzL17TiAfccOz3YndsSdpp2o2', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:36'),
(103, 230008, 'PRINCESS JUVY', '', 'HIBAYA', 'phibaya2023@student.nbscollege.edu.ph', '$2y$10$4hdHfEoy8IVkgfRqkZcqgeo6aoa2WPHLjZkVVjnoeGRGUMCHqX/qu', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:36'),
(104, 230040, 'ARVIC JOHN', '', 'LIM', 'alim2023@student.nbscollege.edu.ph', '$2y$10$ebuZzot5hOuG6O7E4hrszuRJAeSSxBGO1qSxmBQj75nTg8DkoDYj2', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:36'),
(105, 230017, 'GUIA', '', 'MAHOMITANO', 'gmahomitano2023@student.nbscollege.edu.ph', '$2y$10$FaXRTF3VwO7DIr8lNP4Ht.Q4IWRfNTEyZqEqQl6GEEm.J.Q.f76YW', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:36'),
(106, 230002, 'JUDITH', '', 'MANTILLA', 'jmantilla2023@student.nbscollege.edu.ph', '$2y$10$gedHEM9re8y4eide55X/VOiDW7XJ.bAWUApe5QYxnTFrl6WRNZPTG', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:36'),
(107, 230024, 'BRENT ALLEN', '', 'PIDUCA', 'bpiduca2023@student.nbscollege.edu.ph', '$2y$10$EQWgGQDswM.MuvsI2JpvDOoLRd6ND95xHnT.eRu/kEwgnU3axjaAC', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:36'),
(108, 220045, 'LARREI CHRUSZLE', '', 'PINEDA', 'lpineda2022@student.nbscollege.edu.ph', '$2y$10$163SFE/nPEyBuLb/nfFeZ.duJWjMWA/WizWEnScdrY0U.z/eiqbKe', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:36'),
(109, 230006, 'ROSEMARIE', '', 'PUENLEONA', 'rpuenleona2023@student.nbscollege.edu.ph', '$2y$10$O4ZYxtyg2naFgwmdyxX2ue6S25ITAu3nK7RS0ThFbpILgDvOXDbRG', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:36'),
(110, 220035, 'DIANA MAE', '', 'SALCEDO', 'dsalcedo2022@student.nbscollege.edu.ph', '$2y$10$sXz3HnWveXtcj0TIzOaW1.DgiJzqn4SnDCX4G4BjDWLZhx4S.k/P6', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:37'),
(111, 230042, 'JASTINE CLARK', '', 'SAMILLANO', 'jsamillano2023@student.nbscollege.edu.ph', '$2y$10$7reajF52slVpwYymn1hg/umzId8gTWego9c/BpH4XVs/rPUtRl8X2', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:37'),
(112, 230025, 'JEAN WRANCES', '', 'TALBANOS', 'jtalbanos2023@student.nbscollege.edu.ph', '$2y$10$1yuEmOeWJ82ppn4GSj4uw.NOahQkei0pYFp/Sho1ksNWLSOyLzBTm', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:37'),
(113, 210018, 'MICHA ANJELLA', '', 'ABUTIN', 'mabutin2021@student.nbscollege.edu.ph', '$2y$10$sZPVB7HQ5TTWjerFl4.tJ.bxrQM9WWFecXj1Ji7xnIhy9Lyd3kOUG', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:37'),
(114, 200010, 'FRANCES JAZMIN', '', 'AMORA', 'famora2020@student.nbscollege.edu.ph', '$2y$10$sqH1NsQFadZ6kDBqejVDheywH/tQ0Fn9i9OpAgLPUPwEeqijugDt.', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:37'),
(115, 210062, 'HANNAH GRACE', '', 'TERANA', 'hterana2021@student.nbscollege.edu.ph', '$2y$10$l56qQQjkQPk3hSfQBbpBpem44OKPMxSRX9nRI/gzDk1ykxJdqB8US', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:38'),
(116, 220009, 'VERA FE FAYE', '', 'UY', 'vuy2022@student.nbscollege.edu.ph', '$2y$10$FRpmCiBYKQHyrWjfeXy.SOICKMSr9RbG.LDT9uHtuQlYzGBbhO2YC', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:39'),
(117, 220030, 'CARLOS MIGUEL', '', 'CAMACHO', 'ccamacho2022@student.nbscollege.edu.ph', '$2y$10$urbTxORHMCL8J.yunKMb5ujNKwUhE2DvxP61acNL4BNFJvC.S/2MK', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:39'),
(118, 220026, 'ARTURO MIGUEL', '', 'CRUZ', 'acruz2022@student.nbscollege.edu.ph', '$2y$10$Oi0r4EjsdxrzJ9/enJDnL.WUka/J.dqxGWc5htdqUj4VfXGOFPPwC', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:39'),
(119, 190025, 'CARLA JOYCE', '', 'LEDESMA', 'cledesma2019@student.nbscollege.edu.ph', '$2y$10$d5pdVz2j1cgvBUGEr4VuieZG8rvihhVqdOhk4.0t7JsMxLo1Hxg7.', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:39'),
(120, 180011, 'JOHN JERRICHO', '', 'PORCIUNCULA', 'jporciuncula2018@student.nbscollege.edu.ph', '$2y$10$cTNq1e3f2LMp1rTX1FSaWu2tJiaNk1l0tNAXND5HVVl2PgmW.11Wa', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:40'),
(121, 220022, 'MARTINNE CHRISTIAN', '', 'ROSARIO', 'mrosario2022@student.nbscollege.edu.ph', '$2y$10$Y3KeQa9SepZ.rk/V8kWiiOEE0Er6/O6UG1TcLpRlYzPkPGAi26aWq', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:40'),
(122, 220029, 'MA. ELOISA', '', 'ACID', 'macid2022@student.nbscollege.edu.ph', '$2y$10$4BLZNlrY00tzDDkjB4xeuOp7WxMo/A0ae9fxkJuRzcJi62w9/rcNO', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:40'),
(123, 200047, 'ISAIAH DANIEL', '', 'DECEPIDA', 'idecepida2020@student.nbscollege.edu.ph', '$2y$10$Zubl8wRAqjGTFqIvDioC/uIOCeVZmOspZCn0LlkHBZeXME0cNGySa', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:40'),
(124, 200052, 'LARA MAE', '', 'DUCAY', 'lducay2020@student.nbscollege.edu.ph', '$2y$10$my2qgjUijqVbQKHzHq0x9eF8Ccor5KMw5H7T05doZ0I9BdJb3g9fG', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:40'),
(125, 200049, 'CRISTEA GHIEN', '', 'GALICIA', 'cgalicia2020@student.nbscollege.edu.ph', '$2y$10$nDd2L1K8DNgqe1ex722C2.NXVM3EgJMamI3IiuCAIHZKHMvg/UROW', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:40'),
(126, 200055, 'MA. MELODY', '', 'MERCADEJAS', 'mmercadejas2020@student.nbscollege.edu.ph', '$2y$10$e6ThzI8WWUqWYYLmK2.OEujMcAT3GNf2Uiq2SoZ9nTfKPkaWG1WKG', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:40'),
(127, 200016, 'LOVELY ROSE', '', 'MONTOYA', 'lmontoya2020@student.nbscollege.edu.ph', '$2y$10$6IO2FJBN2TAQ7.wCHTfmM.ysgyczcAfiZ9SO07Z2ifgY6O3rUEa9W', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:40'),
(128, 180033, 'CHLOIE', '', 'ONG', 'cong2018@student.nbscollege.edu.ph', '$2y$10$Vs9U//0ci856Q/Egb7PqBeIIPvVtyvlzNU/iHmDLwzZviW08Bs7G.', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:40'),
(129, 200054, 'SIGRID COLYNE NAOMI', '', 'PAZ', 'spaz2020@student.nbscollege.edu.ph', '$2y$10$H9WnhW23QZ/97r5soPH/F.Kz0tIspYcOxp5ftdwWXwGTR0la3QHBa', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:41'),
(130, 200068, 'IRISH MAE', '', 'RAPAL', 'irapal2020@student.nbscollege.edu.ph', '$2y$10$ET5gr/kSqUqkYGkIcts5K.cPMcg3wfIlrau3Bm2G6u00N9nEyrJq.', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-10 20:05:41');

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
(3, 'wasadwasd', '', 'wasdwasdw'),
(4, 'wqwesdad', '', 'wqeda');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contributors`
--
ALTER TABLE `contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
