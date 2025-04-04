-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2025 at 09:01 AM
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
(1, 210078, 'Kenneth', '', 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$VXeCCITlHnIEA5e98NZQdu8D.gUlAQWk8yF21IK8lxYn12zC/grq6', '../Images/Profile/default-avatar.jpg', 'Admin', '2025-04-04', '1', '2025-04-04', NULL, NULL);

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
  `reminder_sent` tinyint(1) DEFAULT NULL
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

--
-- Dumping data for table `library_visits`
--

INSERT INTO `library_visits` (`id`, `student_number`, `time`, `status`, `purpose`) VALUES
(1, 210078, '2025-04-04 06:35:52.000000', 0x31, 'Study'),
(2, 210078, '2025-04-04 06:36:11.000000', 0x30, 'Exit'),
(3, 210078, '2025-04-04 06:47:08.000000', 0x31, 'Study'),
(4, 210078, '2025-04-04 06:49:12.000000', 0x30, 'Exit'),
(5, 210078, '2025-04-04 06:49:39.000000', 0x31, 'Study'),
(6, 210078, '2025-04-04 06:50:04.000000', 0x30, 'Exit');

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
(1, 210078, 'Admin', 'Admin Kenneth Laurence Bonaagua Registered an Admin', 'Admin Kenneth Laurence Bonaagua Registered Kenneth Bonaagua as Admin', '2025-04-04 05:33:41'),
(2, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Bonaagua Logged In as Active', '2025-04-04 05:34:19'),
(3, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Bonaagua Logged In as Active', '2025-04-04 06:21:34');

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
(1, 230016, 'MARC ED', '', 'EBRADO', 'mebrado2023@student.nbscollege.edu.ph', '$2y$10$WvCl/dB3v6kOaEzRwHDAHusaJktt1RdwyvLQepVyc6u.YuzblidOy', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:44'),
(2, 230033, 'STEPHANIE', '', 'ESPEJON', 'sespejon2023@student.nbscollege.edu.ph', '$2y$10$mC/H7DNrY/4LafqYrX6hye8U87zsSnj2t8NtutiQ8E44zcRiPHTXe', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:44'),
(3, 230030, 'GIRLIE GAIL', '', 'GALLARDO', 'ggallardo2023@student.nbscollege.edu.ph', '$2y$10$6weHb3LlNHrsXLfIzwdkB.XlME.1JUBQsOCpTTC1ePFnvslK0R5WK', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:44'),
(4, 230019, 'JESSA', '', 'MADANLO', 'jmadanlo2023@student.nbscollege.edu.ph', '$2y$10$nBJBxUANxSkrZgsOjUqktOIqlWAW3A.s.a8./RJzpDZYm.3aELlEW', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:44'),
(5, 230003, 'VINCELYN', '', 'MOZOL', 'vmozol2023@student.nbscollege.edu.ph', '$2y$10$SfM8orqCHWQJa2fGg..6IeEds.L1vuqNFo1AWq1NP3x4L7SNyeNHC', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:44'),
(6, 230037, 'REGINE', '', 'OCAMPO', 'rocampo2023@student.nbscollege.edu.ph', '$2y$10$X.5tjGnuINrmjznvaWN4fOTYFTJSHE9gJ3H87bs7NbGUnoOG9DgAK', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:44'),
(7, 230044, 'MONIQUE', '', 'PACAMARRA', 'mpacamarra2023@student.nbscollege.edu.ph', '$2y$10$iLdICyEACwNgmEAUeoHSVe5JnAGFmlOldDEsAZM72LIbMhqJ1bcbG', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:44'),
(8, 220054, 'CHEEZER JANE', '', 'POWAO', 'cpowao2022@student.nbscollege.edu.ph', '$2y$10$6T36sgNWFpFvHUTDd0oQwOLohRpu38f6jCLBHX6j9fq3GJtYzCMze', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:45'),
(9, 230027, 'JOHN RENZ', '', 'REJANO', 'jrejano2023@student.nbscollege.edu.ph', '$2y$10$KHLqalqAMHmzVDDJ.EhcZ.QTyFM3I.mj2rcujsC6Ju7NiaWba2NwS', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:45'),
(10, 230009, 'RHOCA', '', 'TINAY', 'rtinay2023@student.nbscollege.edu.ph', '$2y$10$nUztQXPDu2zQyGvLCH2OeeuLI1AvZQpUCDsx6mNQjzGd3FoL22uvS', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:45'),
(11, 230005, 'JOSE CARLOS', '', 'VILLANUEVA', 'jvillanueva2023@student.nbscollege.edu.ph', '$2y$10$XohRKpuoAdecOz5aB/wgUOT8NXNWf1E1EZ2ae3t6DxDw45.u38HnG', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:45'),
(12, 210027, 'RANNIE', '', 'ASIS', 'rasis2021@student.nbscollege.edu.ph', '$2y$10$ILn3roHTG1hJM5QPw9p/ye6rcnc5ahEeshZ/sNcYM6Zscwmu7Q19i', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:45'),
(13, 220055, 'KAREN MAE', '', 'BALICTAR', 'kbalictar2022@student.nbscollege.edu.ph', '$2y$10$Qo1y6EelIYijBZraTgUDJ.lckmdpmVawldlnCatsFmcU/8aTHHBb6', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:45'),
(14, 220021, 'JAMES LHARS', '', 'BARRIENTOS', 'jbarrientos2022@student.nbscollege.edu.ph', '$2y$10$MJ/kH4xEhM/p0tHJelAKN.0rBMvpQAwyXVEu0ikAw9.TIzqN7sUde', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:45'),
(15, 220047, 'NOEMI', '', 'LAURENTE', 'nlaurente2022@student.nbscollege.edu.ph', '$2y$10$Csfmje6S2cfWETwbbiScPez9jIHu3U8IXA1R7P.oXdqC.PZMxkyGW', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:45'),
(16, 210061, 'ANGELA CLAIRE', '', 'PANAHON', 'apanahon2021@student.nbscollege.edu.ph', '$2y$10$hWRfZwTqCHgHC3FrVgHbsu7TUoi749EkXVS0pZOvhOrn6f6EMhBUO', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:45'),
(17, 220007, 'MA. KATRINA', '', 'SANTOS', 'msantos2022@student.nbscollege.edu.ph', '$2y$10$uoDz8E/lU4BAfO.zyvSpSuwcnHMU8D8tW5pCZm1z7Scvv/l1pCihi', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:46'),
(18, 210037, 'LEIRA', '', 'SINAGUINAN', 'lsinaguinan2021@student.nbscollege.edu.ph', '$2y$10$cPNPBdVf8Q/G7X/4ogKsZOsFVAjSQIDhetwDWM/6lovxqjlAM6gma', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:46'),
(19, 210014, 'ANGELINE', '', 'ACEBUCHE', 'aacebuche2021@student.nbscollege.edu.ph', '$2y$10$zxCy36/kF0uayYvrstUCZed/9wDIryaUut4qeUe9AK.IbAJRF5ezG', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:46'),
(20, 210044, 'MAY ANN', '', 'BAYOD', 'mbayod2021@student.nbscollege.edu.ph', '$2y$10$p/90NzRGTFh47Hettcrfc.9.vG0IPG/PwK68YgKZMYUjzDgJd/Rwu', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:46'),
(21, 210033, 'PATRICK DOMINIC', '', 'CORRE', 'pcorre2021@student.nbscollege.edu.ph', '$2y$10$mJcp7bnfuuC1FIRcEI6Z4O1E3A52dW9fyFjo7I7L9jVwU0aZumuEy', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:46'),
(22, 220024, 'JOHN AARON PAUL', '', 'GACHO', 'jgacho2022@student.nbscollege.edu.ph', '$2y$10$O80Fp.Gn2Z8VLXY4/7VxY.d8/KnJUTT6n2K7Xh6eiO4qXB2RkuPse', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:46'),
(23, 220012, 'ALLIANA MARIEL', '', 'GONZALES', 'agonzales2022@student.nbscollege.edu.ph', '$2y$10$w1IvTbltCVBAlmx/QxfKwuE9q.AzfVf.eqiBRUaIauo9lm2w3jNZm', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:46'),
(24, 210050, 'ANGELI', '', 'QUINTO', 'aquinto2021@student.nbscollege.edu.ph', '$2y$10$CxBh52bNd7XRAf8lFhxRQuYFEV0qe2eHZMzBi8MsCsfZshQk3Rky2', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:46'),
(25, 210070, 'MARIA SHEKINAH ELIZABETH', '', 'SAN JUAN', 'msanjuan2021@student.nbscollege.edu.ph', '$2y$10$hkpegBdk3eNzovPoPGHdLe/nMCgLKKQbYxWavL.IcY4D8Kl5BZLCC', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:46'),
(26, 210060, 'LYNLEN', '', 'SINGCO', 'lsingco2021@student.nbscollege.edu.ph', '$2y$10$/65jS78V2rKAoituZH2dw.WpkDTXmokL6o0WQoWOQGN4neetWSNya', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:46'),
(27, 210022, 'ROSHIELA MAY', '', 'SOLDEVILLA', 'rsoldevilla2021@student.nbscollege.edu.ph', '$2y$10$P7m2zY1T3nHGtiqSqgVlh./.jdghyvDPpHkNDUflfrQDmgOsrw7L.', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:47'),
(28, 210031, 'JAY FOARD', '', 'TRAJE', 'jtraje2021@student.nbscollege.edu.ph', '$2y$10$JKFVOCwTRQW6e2/FLVSGcOUgEtXxX2Z3H7FO0/T90iSs1y2Svz6Gu', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:47'),
(29, 210058, 'AIKA MARIE', '', 'YBAÑEZ', 'aybaÑez2021@student.nbscollege.edu.ph', '$2y$10$D/lyVejL07laglsKWrR42ub0dcL6fIaYY7DRAE5M0OL9j2Htrc9fi', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:47'),
(30, 200043, 'JHEDALYN', '', 'DACQUEL', 'jdacquel2020@student.nbscollege.edu.ph', '$2y$10$MktbSUaZEnwwsBjdoZVzYe1yClyRFfSPmnlEmgqonEmyRGZpsCn9K', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:47'),
(31, 200039, 'JOYCE ANN', '', 'MERAS', 'jmeras2020@student.nbscollege.edu.ph', '$2y$10$Uy1zZMJdkIeTfPNXxWGlR.PqYC/9U388/v4MV0ErPwgUTrA.XwVai', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:47'),
(32, 200019, 'ELVERT-ACE', '', 'NERI', 'eneri2020@student.nbscollege.edu.ph', '$2y$10$OF9G/CVybeL1Y.BqU5LO2.bvRT/hHXkgmdrTGrJqIow99/p5shSeu', '', '../Images/Profile/default-avatar.jpg', 'Accountancy', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:47'),
(33, 230032, 'ALEXZA', '', 'IGNACIO', 'aignacio2023@student.nbscollege.edu.ph', '$2y$10$HCtA6/5vhVZSpmAPdzweJ.2JobXgD4nV/72TeisC7n/P6Ri5tTfRa', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:47'),
(34, 220018, 'JERALD', '', 'BALAO', 'jbalao2022@student.nbscollege.edu.ph', '$2y$10$pkwQnbx5SQsK1xbKaSnJUeWbpbGvN0ouE3EdL4dCvjvVmy1hZg8K.', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:47'),
(35, 210016, 'CRIS ALFRED', '', 'PANLUBASAN', 'cpanlubasan2021@student.nbscollege.edu.ph', '$2y$10$FfyP99OL2UPlOH4cBZDI1.eSLfw/ebwspKG3ObVv.B/V2DqHPZMH2', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:47'),
(36, 190026, 'FRANCES MARGARETT', '', 'PEDOCHE', 'fpedoche2019@student.nbscollege.edu.ph', '$2y$10$ngTa/FNnF11vfoLrmQbLA.r213XBy7UrhZDmmcv23LYJvxkEhWAAu', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:48'),
(37, 210049, 'RICHELLE MAE', '', 'CADORNA', 'rcadorna2021@student.nbscollege.edu.ph', '$2y$10$uMJesDhf1Mh5pJ4vzo/meuvGi1fORS6hkcz6xt94UJwkDHqw.zaG2', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:48'),
(38, 210038, 'MARIA ROSALINDA', '', 'GALLO', 'mgallo2021@student.nbscollege.edu.ph', '$2y$10$d1Fb5Y0KHsY2UIbo2K0ZB.qp9G6mePO.pM3vhVTmUc0LsfHOX5pXG', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:48'),
(39, 210056, 'JANE AUSTIN', '', 'LANSANG', 'jlansang2021@student.nbscollege.edu.ph', '$2y$10$dLtAZrxuMC1y8mBXgjj1h./9yiqXosOQbgesVId.cR..lD/Ri9O2y', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:48'),
(40, 190034, 'JELLIANE', '', 'ALARCON', 'jalarcon2019@student.nbscollege.edu.ph', '$2y$10$3r2wBNBPxGNK4OgJprh8VOcRMUTcsNhL9k7k4uihjFK3SGXH8VVdG', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:48'),
(41, 200013, 'RIZA JEAN', '', 'ELLERAN', 'relleran2020@student.nbscollege.edu.ph', '$2y$10$ehEEasE8DsWWfNLidgkNG.SNGGuaW.scxqPzlCZIf/6YnT0iwJUP6', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:48'),
(42, 190088, 'JEREMIE', '', 'GIGANTE', 'jgigante2019@student.nbscollege.edu.ph', '$2y$10$vMpZKHmOuorA9UDRTKTDyu4Fn7QtY7XMNrJZ2.rVemXN4wPbGAZoq', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:48'),
(43, 200024, 'JOSEPH IVAN', '', 'GREGORIO', 'jgregorio2020@student.nbscollege.edu.ph', '$2y$10$fuRO594O09VTbaK5n/JU9ujtXwwqVg1DdegU5/W/uOVaLIFzRby6C', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:48'),
(44, 200032, 'KINJI', '', 'LABAY', 'klabay2020@student.nbscollege.edu.ph', '$2y$10$MKA2uBXHcDpPuZJYGsvGH.BBVWq2V/UiHTyApfw/JfaedTyHUjkCy', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:48'),
(45, 200065, 'CHELSEA CHARMILANE', '', 'MULDONG', 'cmuldong2020@student.nbscollege.edu.ph', '$2y$10$5agIZCNqHKaJdPtlag41ref2DtD1Uk3wFm2d2NfzKKYNhW3CDd6fu', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:49'),
(46, 190004, 'GWYNETH VEAH', '', 'OLIVER', 'goliver2019@student.nbscollege.edu.ph', '$2y$10$EBxwVRn.h.uYvNUet3WFV./7PHJS6cMtMzV23Gc3h5EJzijsZ5BXe', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:49'),
(47, 200042, 'DANIELA SHANE', '', 'PONTIMAR', 'dpontimar2020@student.nbscollege.edu.ph', '$2y$10$kyIpKlGmtujkGQVvrQQ.7u8eOUUTwtZrX0l4l75JClcUmSqIyXmV6', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:49'),
(48, 180004, 'IRICE DANIKKA', '', 'YALUNG', 'iyalung2018@student.nbscollege.edu.ph', '$2y$10$X0z/vhGCqfbMEEHE1l8p/uBbGWV0uhXZXcWcpMB0JfWX2qoJ4JVyu', '', '../Images/Profile/default-avatar.jpg', 'Accounting Information System', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:49'),
(49, 230020, 'EMMANUEL', '', 'ABEJO', 'eabejo2023@student.nbscollege.edu.ph', '$2y$10$Rbqr.tDjBbhh0FYmv6bypux78.aeGwsfh6pM6LqIt83Ws2o70C046', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:49'),
(50, 230046, 'RHYZEN', '', 'BUMANLAG', 'rbumanlag2023@student.nbscollege.edu.ph', '$2y$10$OHqgTd/.tFDPrQ4/K4GEU.wUcU3QxM5/upfeF3kFx9J8pkiDrJsC.', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:49'),
(51, 220056, 'MARY ANN', '', 'CEDILLO', 'mcedillo2022@student.nbscollege.edu.ph', '$2y$10$i0RdDYDdUmQ63AG2S32fqOwszfNQMpeZUIBktW/9rF7H.jCc3Z2s6', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:49'),
(52, 230007, 'RHOD LENARD', '', 'DELAS NIEVES', 'rdelasnieves2023@student.nbscollege.edu.ph', '$2y$10$iS7e6HmsLon4kh.WZhUua.MuzbYSqIsKtnI1RpyPIe9QhjnbSBerS', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:49'),
(53, 230022, 'KATE ANDREI', '', 'DOSAL', 'kdosal2023@student.nbscollege.edu.ph', '$2y$10$8Rv/rBOU5f16ofKAgKGSjucRIFzEOzdKY3wOQ47D8Wvl9qH54GmRC', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:49'),
(54, 230021, 'JAVI AEREM', '', 'FAVIA', 'jfavia2023@student.nbscollege.edu.ph', '$2y$10$iz00reFImopfFEKsimBDJ.DCRwikeCLsNNprj8Ile9DqI32lfalsS', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:50'),
(55, 230004, 'RENARD KEN', '', 'HAPIL', 'rhapil2023@student.nbscollege.edu.ph', '$2y$10$xVpgzGeYOul06mu3m8Wzd.vVhphxY688s8eIW0ZiMzndFWag63Ucy', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:50'),
(56, 230012, 'DANDIE', '', 'LAQUINTA', 'dlaquinta2023@student.nbscollege.edu.ph', '$2y$10$bSt2spqzPYWJjmuZ9GKwOuK6bbdW7GheX..JwNLlFr5hD3XvuCbc2', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:50'),
(57, 230028, 'JHON KENNETH', '', 'LIMATOC', 'jlimatoc2023@student.nbscollege.edu.ph', '$2y$10$0IBoOVFXsst8X6HWzS5H5eBMIpStJ2u/irJdCxSNcyUFTtwDhsBOq', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:50'),
(58, 230010, 'GRACE ANNE', '', 'LOGRONIO', 'glogronio2023@student.nbscollege.edu.ph', '$2y$10$Di5pCxfsPvFFqqIZ3mquq.Qfa0i04BzkUG90NRl/aIFK5d236LjKS', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:50'),
(59, 230045, 'PAOLO', '', 'MALICSE', 'pmalicse2023@student.nbscollege.edu.ph', '$2y$10$26k5Ys7Kels9EXBYbFwo6ehGju/UldxUlCjXyhPv7.dQZZq78e/Gi', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:50'),
(60, 230039, 'FRANCIS ANGELO', '', 'ORILLANEDA', 'forillaneda2023@student.nbscollege.edu.ph', '$2y$10$1HS3SBCct.BKdh9GDT.m8eEllWkoNMQdQFcM1lT20EvalFkNVkQYy', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:50'),
(61, 230015, 'MICHAEL LORENZ', '', 'TAN', 'mtan2023@student.nbscollege.edu.ph', '$2y$10$qZO.ihD0lVbUYAxdgTBjQe6ANVTzNtfJJFMGpmmbY7P4yeCv3z2CK', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:50'),
(62, 220050, 'ARMAN BERNARD', '', 'CABANG', 'acabang2022@student.nbscollege.edu.ph', '$2y$10$oNfjCf/RLbaPbaXMk5W.wemWBgs2DNLDN5KZxd7gY1OK4uHqz.0QW', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:50'),
(63, 220057, 'YZRAH', '', 'GASPADO', 'ygaspado2022@student.nbscollege.edu.ph', '$2y$10$esqgCkJeCk787KJWPHshQuNOeZ0mZz8VO.nYvwKv0k6PxkHaEn1La', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:51'),
(64, 220017, 'BRIAN CHRISTIAN', '', 'HEBRON', 'bhebron2022@student.nbscollege.edu.ph', '$2y$10$yAfzaVgTjw7ni0Yw50XRlOWM9hI5CCE8SYIpYwddWUDjWW3AX7Iau', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:51'),
(65, 220048, 'AJLEIN', '', 'MONTERO', 'amontero2022@student.nbscollege.edu.ph', '$2y$10$ucowy2lUMRpS1GmLuWMiUu/Ey2QdnGN1s8kpdqTjoMxXN.JMITv96', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:51'),
(66, 230014, 'JAY FRANKLIN', '', 'ROLLO', 'jrollo2023@student.nbscollege.edu.ph', '$2y$10$SLm2Y25pKlKOrpnnZbqiiuF88xOWiH5QUrH8utIPsvmZd/ObirY4K', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:51'),
(67, 210079, 'CARLOS MIGUEL', '', 'AUTOR', 'cautor2021@student.nbscollege.edu.ph', '$2y$10$iDbSxvZ7kyqCVUNzc9M4SO4yom5Em8zEBYicYj0Ivva29Rx5n.c5K', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:51'),
(68, 210078, 'KENNETH LAURENCE', '', 'BONAAGUA', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$auhNHnL/Nn3uviEd7ITod.zRphqnwuX/0TgCwkh3Ue9iPSG5K3XY2', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:51'),
(69, 220001, 'JOANNA CRIS', '', 'ESCANILLA', 'jescanilla2022@student.nbscollege.edu.ph', '$2y$10$t5VvqxxAOSSl/LR83804U.9WeRbh4D6G4R99JvmNJ9qRmYOhGHUPa', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:51'),
(70, 210028, 'CAYCE', '', 'EVANGELISTA', 'cevangelista2021@student.nbscollege.edu.ph', '$2y$10$yLh1Rn41DdLj9j.WNlITEOLRR8oAth4iMP7u3/JGInjTD74rh7iCe', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:51'),
(71, 210065, 'JENEPIR', '', 'JABILLO', 'jjabillo2021@student.nbscollege.edu.ph', '$2y$10$n8vWVkUxCJ2Jnc3rEnel8eSfOL6Sg/2X2wHFHFxwnBYTAmqVoTiN.', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:51'),
(72, 220003, 'JOSEPH', '', 'SIMANGCA', 'jsimangca2022@student.nbscollege.edu.ph', '$2y$10$QTaVcBrH8TjVtnAF3nhGauLGVy1kZmyzL9yt7rvuPIKrQX9h0u6LO', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:52'),
(73, 180012, 'KARA COLUMBA', '', 'RABANAL', 'krabanal2018@student.nbscollege.edu.ph', '$2y$10$xE/eTGMKYJaIukzhsrBtYe59AkuXNei75wUkyzbL1IZPmljbirBl2', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:52'),
(74, 200067, 'AZZEL IVAN', '', 'WEE', 'awee2020@student.nbscollege.edu.ph', '$2y$10$5OtlyiFMp1fjNYs633m3tuz99OSbsdfuPxeQoNQNHKP8s/1u.pYNC', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:52'),
(75, 200026, 'JERALD', '', 'YSAAC', 'jysaac2020@student.nbscollege.edu.ph', '$2y$10$.mGWuoOGiOnNDdz7eOBTjeuaStpdUzvhxkhqqdhCUtcPWclP2Uy6O', '', '../Images/Profile/default-avatar.jpg', 'Computer Science', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:52'),
(76, 230034, 'ALDRIN', '', 'BOJILADOR', 'abojilador2023@student.nbscollege.edu.ph', '$2y$10$T5LEFJfEaQVmdoMmNle.GeBfkc3DuOSlt8oTNhde9LiJSxe5dveSG', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:52'),
(77, 230043, 'JOAN MAE', '', 'CAINGIN', 'jcaingin2023@student.nbscollege.edu.ph', '$2y$10$Aye627co6Hes0l.SzHrd/.wwlfICe12.QPm4Mhoc6IsrQq9.uXPOm', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:52'),
(78, 230023, 'SEAN YVES', '', 'DE GUZMAN', 'sdeguzman2023@student.nbscollege.edu.ph', '$2y$10$DwieYH2QU3h9ZEkJDw0uYeUfexOWS9SEtMQLg/M9YUmA1Cie07ewq', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:52'),
(79, 230011, 'AARON CARL', '', 'DIÑO', 'adiÑo2023@student.nbscollege.edu.ph', '$2y$10$zszwZ4SYE0vdMRXavSWoI.AmAuGKny1SqE3uNbYggTa.A2IXtmQpy', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:52'),
(80, 230013, 'NORAMIE', '', 'USMAN', 'nusman2023@student.nbscollege.edu.ph', '$2y$10$9lAmAQ/yc9mfPmMVEsgAQu9g1Tobcoran68.iQSYgpvruxjdZZx/6', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:52'),
(81, 220025, 'PATRICK JAMES', '', 'DE QUIROZ', 'pdequiroz2022@student.nbscollege.edu.ph', '$2y$10$QRxA3UWAIK7nsxwMy1TOPu3qUgvmSxOf.Sp7JPCbDom4v7gDHtZ2i', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:52'),
(82, 210024, 'APRIL NICOLE', '', 'CAMPOS', 'acampos2021@student.nbscollege.edu.ph', '$2y$10$JbXTl43VAPT35mAD3wuhde2Uo/q5qLEmzx7OWM.lF/ragFuOIaIi6', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:53'),
(83, 210057, 'SYRA', '', 'LANSANG', 'slansang2021@student.nbscollege.edu.ph', '$2y$10$HUGV/1TgDhNRnq0k2b9KsOHad/qErzaqfGbrwlwyaxN4f3zMGwmJ6', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:53'),
(84, 210012, 'LIEZELLE', '', 'SABLAWON', 'lsablawon2021@student.nbscollege.edu.ph', '$2y$10$cHVdeylE5l9nq.2ecEwT6uGhfaVM9nVBqlaTXoibSjZ/4Cyjvzb/C', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:53'),
(85, 210059, 'LARAMAE', '', 'SANTOS', 'lsantos2021@student.nbscollege.edu.ph', '$2y$10$zaMUc70SYwYSjauPdsOKO.SVGA2z4.ifiss3dh2wMLGgel5TcVHHG', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:53'),
(86, 210008, 'CHARMAINE', '', 'VILLARMIA', 'cvillarmia2021@student.nbscollege.edu.ph', '$2y$10$tcFG7FSTma24KazUJoDp0OjEwQ3qgcYyzvAH3/sxvxKesTPiu1HjO', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:53'),
(87, 220036, 'JOHN MATTHEW', '', 'VILLARUBIA', 'jvillarubia2022@student.nbscollege.edu.ph', '$2y$10$NCcvI366SttoBdjpTd1AR.KIaIkMjmj8KVuMpTVsHbLL114wAlCfe', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:53'),
(88, 200028, 'AUBREY', '', 'DULAG', 'adulag2020@student.nbscollege.edu.ph', '$2y$10$vVHvQ13adz9aDb26N0/gpuHEOL.zpI9bykgV5580BnNPnWmUxr2Te', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:53'),
(89, 180036, 'WILHELM ELIJAH', '', 'FERRER', 'wferrer2018@student.nbscollege.edu.ph', '$2y$10$XZ0ovL4soMqOyvWxOVNsKOBhZ07vpWR/Yc85giPMMFdbB2quSuXQS', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:53'),
(90, 200048, 'ADRIAN', '', 'MANDAWE', 'amandawe2020@student.nbscollege.edu.ph', '$2y$10$RgKZvHtrPURy9Pw03e98ieqXidCNgEyjTkx1BGRhxXTTQP22mfGzq', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:54'),
(91, 210029, 'JULIA CAITLIN', '', 'PIAMONTE', 'jpiamonte2021@student.nbscollege.edu.ph', '$2y$10$qjnFBW1XA8bvAkf/hEkeJ.9nS8vYz1UhjkF6FANgNoR6mZn4k.X7u', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:54'),
(92, 220039, 'JERAH MANUEL', '', 'SARABIA', 'jsarabia2022@student.nbscollege.edu.ph', '$2y$10$CJYKPdDcpEDq4jJpzwmu1.1j.wiglx6Y7IqAvB5/nqiR/a0zCEoXC', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:54'),
(93, 200029, 'JIM LUIS', '', 'VILLACILLO', 'jvillacillo2020@student.nbscollege.edu.ph', '$2y$10$QJdOg9Qf.7j8k1ENrdYPNeec8i2vcCU23MwdW4g4dyR2kN7tix8YK', '', '../Images/Profile/default-avatar.jpg', 'Entrepreneurship', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:54'),
(94, 220004, 'MAXELL JAMES', '', 'ABUTIN', 'mabutin2022@student.nbscollege.edu.ph', '$2y$10$b3Fw.3vfEcL9laAt8Vx.8uxYzPiAUgoNvRG99fsq7BgFOHtjadejq', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:54'),
(95, 230036, 'CASSANDRA', '', 'AOANAN', 'caoanan2023@student.nbscollege.edu.ph', '$2y$10$l7TX5FSQUedVy3y5TBzHveudFGYxhzfpCuHu1Z3ucqVxTLnQ2CoO.', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:54'),
(96, 230031, 'JUDE MARTIN', '', 'BALLADOS', 'jballados2023@student.nbscollege.edu.ph', '$2y$10$nh9aSxK7waxST/uNtyj26ev/.AzCPcMtP.zoo32XyMqHHiaw8RWs.', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:54'),
(97, 230026, 'JENNY ROSE', '', 'COLIS', 'jcolis2023@student.nbscollege.edu.ph', '$2y$10$HklZmhbHfqWKsGByYizi1Oyi28O18M4gma3Eju6XLC4xAFC5ks6HO', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:54'),
(98, 230035, 'JAKE', '', 'COMIA', 'jcomia2023@student.nbscollege.edu.ph', '$2y$10$oHcRpAhr3shjeObCK3NzhOI80eKjT2LT.l.ziuVnjJREEBidcmRtm', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:54'),
(99, 230029, 'MATILDA ABIGAEL', '', 'DALISAY', 'mdalisay2023@student.nbscollege.edu.ph', '$2y$10$MtOSBPGnlHoS0ZxU8SWQc.1/BHIdU0qEAJvEvK8OypwXC5rEyX9WW', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:55'),
(100, 230018, 'JOANNE MAY', '', 'DELA CRUZ', 'jdelacruz2023@student.nbscollege.edu.ph', '$2y$10$uMxmOlK1yH94zU9QnQaN.OCTbwjKnbX8iiQxpPmtuUo8vK5alZ5ZG', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:55'),
(101, 230038, 'RACHELLE MAE', '', 'EBIAS', 'rebias2023@student.nbscollege.edu.ph', '$2y$10$9iu8PYIYfr0t7bpPaHjll.LYHjhQLfevA/dae7ecIfP13ZUpWkJm6', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:55'),
(102, 230041, 'COLLINE FIONA', '', 'GUASCH', 'cguasch2023@student.nbscollege.edu.ph', '$2y$10$cB/YWNKwBOtLgNsbxjzlUOPcZVgtW/IdyjQmSvLJiW5RL3QoyPEjC', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:55'),
(103, 230008, 'PRINCESS JUVY', '', 'HIBAYA', 'phibaya2023@student.nbscollege.edu.ph', '$2y$10$QngX9Az5CxqDtglGTv.D/eKd6aeIrIkHoMB9lQnUWnC1dzWQbpzB2', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:55'),
(104, 230040, 'ARVIC JOHN', '', 'LIM', 'alim2023@student.nbscollege.edu.ph', '$2y$10$w/kk5eG9dreFsFHtYPift.5Rsavgrw2XxfQEKruYWxIimPWgKHw.O', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:55'),
(105, 230017, 'GUIA', '', 'MAHOMITANO', 'gmahomitano2023@student.nbscollege.edu.ph', '$2y$10$C/iwp1XObByTXhqNKSvrTe9m3uZ/IDViCXyHe9yNnDd.1xcRmyqIK', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:55'),
(106, 230002, 'JUDITH', '', 'MANTILLA', 'jmantilla2023@student.nbscollege.edu.ph', '$2y$10$z7CHzzkTP1ZuD7WML.x6y.LMW3ZNGIjcgGsYcqJJtj5uf4AM1hCbm', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:55'),
(107, 230024, 'BRENT ALLEN', '', 'PIDUCA', 'bpiduca2023@student.nbscollege.edu.ph', '$2y$10$3g.qqU53VPuY44XDa9NZjuiIr6SeBSD1Zl4KgqMv.VsD86dSec3wa', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:55'),
(108, 220045, 'LARREI CHRUSZLE', '', 'PINEDA', 'lpineda2022@student.nbscollege.edu.ph', '$2y$10$o2sl5x/n9Gp4mVM3wY4T/eQ403HNSZyPtfGgKAAaQKYi5m4y8o5yy', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:56'),
(109, 230006, 'ROSEMARIE', '', 'PUENLEONA', 'rpuenleona2023@student.nbscollege.edu.ph', '$2y$10$xDOQC0zo2CsJSsjCftNzdu.O6TG1LCS3F0A6cGVeso9Sv7rdIjb9u', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:56'),
(110, 220035, 'DIANA MAE', '', 'SALCEDO', 'dsalcedo2022@student.nbscollege.edu.ph', '$2y$10$eM.K0xQbEvktWd.akdBsmeGgE.RNTiB6919OOPdhWRgXuy.0LujNG', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:56'),
(111, 230042, 'JASTINE CLARK', '', 'SAMILLANO', 'jsamillano2023@student.nbscollege.edu.ph', '$2y$10$8VSf95EOD7bC.FlStoPIJeRG5acu8yAc2tWKc4GWeuFTF.JRhVt8e', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:56'),
(112, 230025, 'JEAN WRANCES', '', 'TALBANOS', 'jtalbanos2023@student.nbscollege.edu.ph', '$2y$10$gEtp95HfAJnz/jC/S2GJxOzREMV68SPCo/VuL78BrF/OvFsH9nxUW', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:56'),
(113, 210018, 'MICHA ANJELLA', '', 'ABUTIN', 'mabutin2021@student.nbscollege.edu.ph', '$2y$10$.oKhS33eYQuT3sUOM2sjG.A/G7u6KAP2B.v1PGWU20GLNmL.BYd92', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:56'),
(114, 200010, 'FRANCES JAZMIN', '', 'AMORA', 'famora2020@student.nbscollege.edu.ph', '$2y$10$dCnHubngRv9rtwCk4lWLr.ccya5KAOoFnklHrgdUW0CKWWrbd.52e', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:56'),
(115, 210062, 'HANNAH GRACE', '', 'TERANA', 'hterana2021@student.nbscollege.edu.ph', '$2y$10$lh7lx4/.9sJ/iKof7RutbunMFVLwrxMMlKDViOZ8M4pZc4Xj4rlRW', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:56'),
(116, 220009, 'VERA FE FAYE', '', 'UY', 'vuy2022@student.nbscollege.edu.ph', '$2y$10$ejpiV5S4Uz5wMLcuMl2U1OVFhqUEhhq7lm85Q2fMF8GDMdhzruVAS', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:56'),
(117, 220030, 'CARLOS MIGUEL', '', 'CAMACHO', 'ccamacho2022@student.nbscollege.edu.ph', '$2y$10$GZyj5zifQQlQvCu2wZKx6uaSGJNjH.uVsPFoUnIoz1aVuwKpYVyJ.', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:57'),
(118, 220026, 'ARTURO MIGUEL', '', 'CRUZ', 'acruz2022@student.nbscollege.edu.ph', '$2y$10$4EqJZ.vkqISvkIMbC2GvsuYvhn//svAORQ1CYtsS5KYkJ.EY00cVu', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:57'),
(119, 190025, 'CARLA JOYCE', '', 'LEDESMA', 'cledesma2019@student.nbscollege.edu.ph', '$2y$10$tQh8BfjeoSK27IfsTN2DueFsf1vXDUE/z0jM35ZF5uAei5B68UDuu', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:57'),
(120, 180011, 'JOHN JERRICHO', '', 'PORCIUNCULA', 'jporciuncula2018@student.nbscollege.edu.ph', '$2y$10$D0/ZMgyP.bBEhMFJb0n39.WfONAMw326gK8O8mAqR4b2G6vY0f472', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:57'),
(121, 220022, 'MARTINNE CHRISTIAN', '', 'ROSARIO', 'mrosario2022@student.nbscollege.edu.ph', '$2y$10$jqGk5/lqXveTwnXcZxWiPu2F7s2wG2Pgf4mUzoDH1DucH6UAM.AVS', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:57'),
(122, 220029, 'MA. ELOISA', '', 'ACID', 'macid2022@student.nbscollege.edu.ph', '$2y$10$hqlzwVe9dCVyhCK1F/1u0uv.sWHnWSdCHozqjt91aPCg8tmm0Sp6.', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:57'),
(123, 200047, 'ISAIAH DANIEL', '', 'DECEPIDA', 'idecepida2020@student.nbscollege.edu.ph', '$2y$10$VFygK6QiNKHszegKeuX9J.WB7wqJko1Kt8OnVooZlQ35rOmvahfO6', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:57'),
(124, 200052, 'LARA MAE', '', 'DUCAY', 'lducay2020@student.nbscollege.edu.ph', '$2y$10$.DTfEFiwzUgtCxIe3J8chuVtqjuNtTD4B/Cz42e13Z8kEsJvUcJja', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:57'),
(125, 200049, 'CRISTEA GHIEN', '', 'GALICIA', 'cgalicia2020@student.nbscollege.edu.ph', '$2y$10$J7x1eKGkPUIASB/d2ui2u.VZHUWm0g5rKZdhDPQmRAh46Sm3bzmOq', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:58'),
(126, 200055, 'MA. MELODY', '', 'MERCADEJAS', 'mmercadejas2020@student.nbscollege.edu.ph', '$2y$10$f7BxTqV6AzdWFf4le57tp.Po59Vy4ckv4IodwZORz3xLRQTbXaUN.', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:58'),
(127, 200016, 'LOVELY ROSE', '', 'MONTOYA', 'lmontoya2020@student.nbscollege.edu.ph', '$2y$10$EM9Pr/QqRHQRvQ6BwAHWYeQ8QnZUzDjoDmAwDzuGtb8bf0Fe/7CoW', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:58'),
(128, 180033, 'CHLOIE', '', 'ONG', 'cong2018@student.nbscollege.edu.ph', '$2y$10$PMHhYPU2f3OyBKykqxvJ1O2V2UQ2IMLAHiBXLLMx60W1WlNmowqQ2', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:58'),
(129, 200054, 'SIGRID COLYNE NAOMI', '', 'PAZ', 'spaz2020@student.nbscollege.edu.ph', '$2y$10$Ts0dulJu9tFKX7zhMpjVNuZpA5um0IsGPDJMAzectEq6X46C5tH0u', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:58'),
(130, 200068, 'IRISH MAE', '', 'RAPAL', 'irapal2020@student.nbscollege.edu.ph', '$2y$10$6X5/UsE0lnA53KsRMA.Jru4IAYsvO1pp/zVumDSblqfIlCkfSrhva', '', '../Images/Profile/default-avatar.jpg', 'Tourism Management', 'Student', '', '', '/upload/default-id.png', NULL, '1', NULL, '', '2025-04-04 14:21:58');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `library_visits`
--
ALTER TABLE `library_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
