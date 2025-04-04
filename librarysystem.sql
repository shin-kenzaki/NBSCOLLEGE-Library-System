-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 03, 2025 at 12:02 PM
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
(89, 12, 'wsdawsadw', '', '', 'Corporate', '', '', '', NULL, NULL, '', '', '1', '', 1, 'pages', '', '', 'Text', 'Print', 'Book', 'TR M45.23 L56 2001 vol1 c1', '', 'English', 'TR', 1, '2025-04-03', 'Available', 1, '2025-04-03'),
(90, 13, 'wsdawsadw', '', '', 'Corporate', '', '', '', NULL, NULL, '', '', '2', '', 1, 'pages', '', '', 'Text', 'Print', 'Book', 'TR M45.23 L56 2001 vol2 c2', '', 'English', 'TR', 1, '2025-04-03', 'Available', 1, '2025-04-03'),
(91, 14, 'wsdawsadw', '', '', 'Corporate', '', '', '', NULL, NULL, '', '', '3', '', 1, 'pages', '', '', 'Text', 'Print', 'Book', 'TR M45.23 L56 2001 vol3 c3', '', 'English', 'TR', 1, '2025-04-03', 'Available', 1, '2025-04-03');

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
(2, 31, 35, 'Returned', '2025-03-31', 1, '2025-04-07', '2025-04-01', 1, NULL, NULL, 0),
(3, 31, 34, 'Returned', '2025-04-03', 1, '2025-04-10', '2025-04-03', 1, NULL, NULL, NULL),
(4, 31, 34, 'Returned', '2025-04-03', 1, '2025-04-10', '2025-04-03', 1, NULL, NULL, 0);

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
(687, 38, 11, 'Author'),
(690, 39, 11, 'Author'),
(693, 40, 11, 'Author'),
(696, 41, 11, 'Author'),
(699, 42, 11, 'Author'),
(714, 43, 11, 'Author'),
(717, 44, 11, 'Author'),
(720, 45, 11, 'Author'),
(723, 46, 11, 'Author'),
(726, 47, 11, 'Author'),
(729, 48, 11, 'Author'),
(732, 49, 11, 'Author'),
(735, 50, 11, 'Author'),
(738, 51, 11, 'Author'),
(741, 52, 11, 'Author'),
(744, 53, 11, 'Author'),
(795, 54, 11, 'Author'),
(798, 55, 11, 'Author'),
(801, 56, 11, 'Author'),
(804, 57, 11, 'Author'),
(807, 58, 11, 'Author'),
(813, 64, 11, 'Author'),
(816, 65, 11, 'Author'),
(819, 66, 11, 'Author'),
(822, 67, 11, 'Author'),
(825, 68, 11, 'Author'),
(831, 59, 11, 'Author'),
(834, 60, 11, 'Author'),
(837, 61, 11, 'Author'),
(840, 62, 11, 'Author'),
(843, 63, 11, 'Author'),
(879, 74, 11, 'Author'),
(882, 75, 11, 'Author'),
(885, 76, 11, 'Author'),
(888, 77, 11, 'Author'),
(891, 78, 11, 'Author'),
(906, 79, 11, 'Author'),
(909, 80, 11, 'Author'),
(912, 81, 11, 'Author'),
(939, 89, 10, 'Author'),
(945, 90, 10, 'Author'),
(946, 91, 10, 'Author');

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
  `gender` varchar(50) NOT NULL,
  `usertype` varchar(50) NOT NULL DEFAULT 'Student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `physical_login_users`
--

INSERT INTO `physical_login_users` (`id`, `student_number`, `course`, `year`, `firstname`, `middle_init`, `lastname`, `gender`) VALUES
(521, 230016, 'Accountancy', '1st', 'MARC ED', '', 'EBRADO', 'Male'),
(522, 230033, 'Accountancy', '1st', 'STEPHANIE', '', 'ESPEJON', 'Female'),
(523, 230030, 'Accountancy', '1st', 'GIRLIE GAIL', '', 'GALLARDO', 'Female'),
(524, 230019, 'Accountancy', '1st', 'JESSA', '', 'MADANLO', 'Female'),
(525, 230003, 'Accountancy', '1st', 'VINCELYN', '', 'MOZOL', 'Female'),
(526, 230037, 'Accountancy', '1st', 'REGINE', '', 'OCAMPO', 'Female'),
(527, 230044, 'Accountancy', '1st', 'MONIQUE', '', 'PACAMARRA', 'Female'),
(528, 220054, 'Accountancy', '1st', 'CHEEZER JANE', '', 'POWAO', 'Female'),
(529, 230027, 'Accountancy', '1st', 'JOHN RENZ', '', 'REJANO', 'Female'),
(530, 230009, 'Accountancy', '1st', 'RHOCA', '', 'TINAY', 'Female'),
(531, 230005, 'Accountancy', '1st', 'JOSE CARLOS', '', 'VILLANUEVA', 'Male'),
(532, 210027, 'Accountancy', '2nd', 'RANNIE', '', 'ASIS', 'Male'),
(533, 220055, 'Accountancy', '2nd', 'KAREN MAE', '', 'BALICTAR', 'Female'),
(534, 220021, 'Accountancy', '2nd', 'JAMES LHARS', '', 'BARRIENTOS', 'Male'),
(535, 220047, 'Accountancy', '2nd', 'NOEMI', '', 'LAURENTE', 'Female'),
(536, 210061, 'Accountancy', '2nd', 'ANGELA CLAIRE', '', 'PANAHON', 'Female'),
(537, 220007, 'Accountancy', '2nd', 'MA. KATRINA', '', 'SANTOS', 'Female'),
(538, 210037, 'Accountancy', '2nd', 'LEIRA', '', 'SINAGUINAN', 'Female'),
(539, 210014, 'Accountancy', '3rd', 'ANGELINE', '', 'ACEBUCHE', 'Female'),
(540, 210044, 'Accountancy', '3rd', 'MAY ANN', '', 'BAYOD', 'Female'),
(541, 210033, 'Accountancy', '3rd', 'PATRICK DOMINIC', '', 'CORRE', 'Male'),
(542, 220024, 'Accountancy', '3rd', 'JOHN AARON PAUL', '', 'GACHO', 'Male'),
(543, 220012, 'Accountancy', '3rd', 'ALLIANA MARIEL', '', 'GONZALES', 'Female'),
(544, 210050, 'Accountancy', '3rd', 'ANGELI', '', 'QUINTO', 'Female'),
(545, 210070, 'Accountancy', '3rd', 'MARIA SHEKINAH ELIZABETH', '', 'SAN JUAN', 'Female'),
(546, 210060, 'Accountancy', '3rd', 'LYNLEN', '', 'SINGCO', 'Female'),
(547, 210022, 'Accountancy', '3rd', 'ROSHIELA MAY', '', 'SOLDEVILLA', 'Female'),
(548, 210031, 'Accountancy', '3rd', 'JAY FOARD', '', 'TRAJE', 'Male'),
(549, 210058, 'Accountancy', '3rd', 'AIKA MARIE', '', 'YBAÑEZ', 'Female'),
(550, 200043, 'Accountancy', '4th', 'JHEDALYN', '', 'DACQUEL', 'Female'),
(551, 200039, 'Accountancy', '4th', 'JOYCE ANN', '', 'MERAS', 'Female'),
(552, 200019, 'Accountancy', '4th', 'ELVERT-ACE', '', 'NERI', 'Male'),
(553, 230032, 'Accounting Information System', '1st', 'ALEXZA', '', 'IGNACIO', 'Female'),
(554, 220018, 'Accounting Information System', '2nd', 'JERALD', '', 'BALAO', 'Male'),
(555, 210016, 'Accounting Information System', '2nd', 'CRIS ALFRED', '', 'PANLUBASAN', 'Male'),
(556, 190026, 'Accounting Information System', '2nd', 'FRANCES MARGARETT', '', 'PEDOCHE', 'Female'),
(557, 210049, 'Accounting Information System', '3rd', 'RICHELLE MAE', '', 'CADORNA', 'Female'),
(558, 210038, 'Accounting Information System', '3rd', 'MARIA ROSALINDA', '', 'GALLO', 'Female'),
(559, 210056, 'Accounting Information System', '3rd', 'JANE AUSTIN', '', 'LANSANG', 'Female'),
(560, 190034, 'Accounting Information System', '4th', 'JELLIANE', '', 'ALARCON', 'Female'),
(561, 200013, 'Accounting Information System', '4th', 'RIZA JEAN', '', 'ELLERAN', 'Female'),
(562, 190088, 'Accounting Information System', '4th', 'JEREMIE', '', 'GIGANTE', 'Male'),
(563, 200024, 'Accounting Information System', '4th', 'JOSEPH IVAN', '', 'GREGORIO', 'Male'),
(564, 200032, 'Accounting Information System', '4th', 'KINJI', '', 'LABAY', 'Male'),
(565, 200065, 'Accounting Information System', '4th', 'CHELSEA CHARMILANE', '', 'MULDONG', 'Female'),
(566, 190004, 'Accounting Information System', '4th', 'GWYNETH VEAH', '', 'OLIVER', 'Female'),
(567, 200042, 'Accounting Information System', '4th', 'DANIELA SHANE', '', 'PONTIMAR', 'Female'),
(568, 180004, 'Accounting Information System', '4th', 'IRICE DANIKKA', '', 'YALUNG', 'Female'),
(569, 230020, 'Computer Science', '1st', 'EMMANUEL', '', 'ABEJO', 'Male'),
(570, 230046, 'Computer Science', '1st', 'RHYZEN', '', 'BUMANLAG', 'Male'),
(571, 220056, 'Computer Science', '1st', 'MARY ANN', '', 'CEDILLO', 'Female'),
(572, 230007, 'Computer Science', '1st', 'RHOD LENARD', '', 'DELAS NIEVES', 'Male'),
(573, 230022, 'Computer Science', '1st', 'KATE ANDREI', '', 'DOSAL', 'Female'),
(574, 230021, 'Computer Science', '1st', 'JAVI AEREM', '', 'FAVIA', 'Male'),
(575, 230004, 'Computer Science', '1st', 'RENARD KEN', '', 'HAPIL', 'Male'),
(576, 230012, 'Computer Science', '1st', 'DANDIE', '', 'LAQUINTA', 'Male'),
(577, 230028, 'Computer Science', '1st', 'JHON KENNETH', '', 'LIMATOC', 'Male'),
(578, 230010, 'Computer Science', '1st', 'GRACE ANNE', '', 'LOGRONIO', 'Female'),
(579, 230045, 'Computer Science', '1st', 'PAOLO', '', 'MALICSE', 'Male'),
(580, 230039, 'Computer Science', '1st', 'FRANCIS ANGELO', '', 'ORILLANEDA', 'Male'),
(581, 230015, 'Computer Science', '1st', 'MICHAEL LORENZ', '', 'TAN', 'Male'),
(582, 220050, 'Computer Science', '2nd', 'ARMAN BERNARD', '', 'CABANG', 'Male'),
(583, 220057, 'Computer Science', '2nd', 'YZRAH', '', 'GASPADO', 'Female'),
(584, 220017, 'Computer Science', '2nd', 'BRIAN CHRISTIAN', '', 'HEBRON', 'Female'),
(585, 220048, 'Computer Science', '2nd', 'AJLEIN', '', 'MONTERO', 'Male'),
(586, 230014, 'Computer Science', '2nd', 'JAY FRANKLIN', '', 'ROLLO', 'Male'),
(587, 210079, 'Computer Science', '3rd', 'CARLOS MIGUEL', '', 'AUTOR', 'Male'),
(588, 210078, 'Computer Science', '3rd', 'KENNETH LAURENCE', '', 'BONAAGUA', 'Male'),
(589, 220001, 'Computer Science', '3rd', 'JOANNA CRIS', '', 'ESCANILLA', 'Female'),
(590, 210028, 'Computer Science', '3rd', 'CAYCE', '', 'EVANGELISTA', 'Male'),
(591, 210065, 'Computer Science', '3rd', 'JENEPIR', '', 'JABILLO', 'Female'),
(592, 220003, 'Computer Science', '3rd', 'JOSEPH', '', 'SIMANGCA', 'Male'),
(593, 180012, 'Computer Science', '4th', 'KARA COLUMBA', '', 'RABANAL', 'Female'),
(594, 200067, 'Computer Science', '4th', 'AZZEL IVAN', '', 'WEE', 'Male'),
(595, 200026, 'Computer Science', '4th', 'JERALD', '', 'YSAAC', 'Male'),
(596, 230034, 'Entrepreneurship', '1st', 'ALDRIN', '', 'BOJILADOR', 'Male'),
(597, 230043, 'Entrepreneurship', '1st', 'JOAN MAE', '', 'CAINGIN', 'Female'),
(598, 230023, 'Entrepreneurship', '1st', 'SEAN YVES', '', 'DE GUZMAN', 'Male'),
(599, 230011, 'Entrepreneurship', '1st', 'AARON CARL', '', 'DIÑO', 'Male'),
(600, 230013, 'Entrepreneurship', '1st', 'NORAMIE', '', 'USMAN', 'Female'),
(601, 220025, 'Entrepreneurship', '2nd', 'PATRICK JAMES', '', 'DE QUIROZ', 'Male'),
(602, 210024, 'Entrepreneurship', '3rd', 'APRIL NICOLE', '', 'CAMPOS', 'Female'),
(603, 210057, 'Entrepreneurship', '3rd', 'SYRA', '', 'LANSANG', 'Female'),
(604, 210012, 'Entrepreneurship', '3rd', 'LIEZELLE', '', 'SABLAWON', 'Female'),
(605, 210059, 'Entrepreneurship', '3rd', 'LARAMAE', '', 'SANTOS', 'Female'),
(606, 210008, 'Entrepreneurship', '3rd', 'CHARMAINE', '', 'VILLARMIA', 'Female'),
(607, 220036, 'Entrepreneurship', '3rd', 'JOHN MATTHEW', '', 'VILLARUBIA', 'Male'),
(608, 200028, 'Entrepreneurship', '4th', 'AUBREY', '', 'DULAG', 'Female'),
(609, 180036, 'Entrepreneurship', '4th', 'WILHELM ELIJAH', '', 'FERRER', 'Male'),
(610, 200048, 'Entrepreneurship', '4th', 'ADRIAN', '', 'MANDAWE', 'Male'),
(611, 210029, 'Entrepreneurship', '4th', 'JULIA CAITLIN', '', 'PIAMONTE', 'Female'),
(612, 220039, 'Entrepreneurship', '4th', 'JERAH MANUEL', '', 'SARABIA', 'Female'),
(613, 200029, 'Entrepreneurship', '4th', 'JIM LUIS', '', 'VILLACILLO', 'Male'),
(614, 220004, 'Tourism Management', '1st', 'MAXELL JAMES', '', 'ABUTIN', 'Male'),
(615, 230036, 'Tourism Management', '1st', 'CASSANDRA', '', 'AOANAN', 'Female'),
(616, 230031, 'Tourism Management', '1st', 'JUDE MARTIN', '', 'BALLADOS', 'Female'),
(617, 230026, 'Tourism Management', '1st', 'JENNY ROSE', '', 'COLIS', 'Female'),
(618, 230035, 'Tourism Management', '1st', 'JAKE', '', 'COMIA', 'Male'),
(619, 230029, 'Tourism Management', '1st', 'MATILDA ABIGAEL', '', 'DALISAY', 'Female'),
(620, 230018, 'Tourism Management', '1st', 'JOANNE MAY', '', 'DELA CRUZ', 'Female'),
(621, 230038, 'Tourism Management', '1st', 'RACHELLE MAE', '', 'EBIAS', 'Female'),
(622, 230041, 'Tourism Management', '1st', 'COLLINE FIONA', '', 'GUASCH', 'Female'),
(623, 230008, 'Tourism Management', '1st', 'PRINCESS JUVY', '', 'HIBAYA', 'Female'),
(624, 230040, 'Tourism Management', '1st', 'ARVIC JOHN', '', 'LIM', 'Male'),
(625, 230017, 'Tourism Management', '1st', 'GUIA', '', 'MAHOMITANO', 'Female'),
(626, 230002, 'Tourism Management', '1st', 'JUDITH', '', 'MANTILLA', 'Female'),
(627, 230024, 'Tourism Management', '1st', 'BRENT ALLEN', '', 'PIDUCA', 'Male'),
(628, 220045, 'Tourism Management', '1st', 'LARREI CHRUSZLE', '', 'PINEDA', 'Female'),
(629, 230006, 'Tourism Management', '1st', 'ROSEMARIE', '', 'PUENLEONA', 'Female'),
(630, 220035, 'Tourism Management', '1st', 'DIANA MAE', '', 'SALCEDO', 'Female'),
(631, 230042, 'Tourism Management', '1st', 'JASTINE CLARK', '', 'SAMILLANO', 'Male'),
(632, 230025, 'Tourism Management', '1st', 'JEAN WRANCES', '', 'TALBANOS', 'Female'),
(633, 210018, 'Tourism Management', '2nd', 'MICHA ANJELLA', '', 'ABUTIN', 'Female'),
(634, 200010, 'Tourism Management', '2nd', 'FRANCES JAZMIN', '', 'AMORA', 'Female'),
(635, 210062, 'Tourism Management', '2nd', 'HANNAH GRACE', '', 'TERANA', 'Female'),
(636, 220009, 'Tourism Management', '2nd', 'VERA FE FAYE', '', 'UY', 'Female'),
(637, 220030, 'Tourism Management', '3rd', 'CARLOS MIGUEL', '', 'CAMACHO', 'Male'),
(638, 220026, 'Tourism Management', '3rd', 'ARTURO MIGUEL', '', 'CRUZ', 'Male'),
(639, 190025, 'Tourism Management', '3rd', 'CARLA JOYCE', '', 'LEDESMA', 'Female'),
(640, 180011, 'Tourism Management', '3rd', 'JOHN JERRICHO', '', 'PORCIUNCULA', 'Male'),
(641, 220022, 'Tourism Management', '3rd', 'MARTINNE CHRISTIAN', '', 'ROSARIO', 'Male'),
(642, 220029, 'Tourism Management', '4th', 'MA. ELOISA', '', 'ACID', 'Female'),
(643, 200047, 'Tourism Management', '4th', 'ISAIAH DANIEL', '', 'DECEPIDA', 'Male'),
(644, 200052, 'Tourism Management', '4th', 'LARA MAE', '', 'DUCAY', 'Female'),
(645, 200049, 'Tourism Management', '4th', 'CRISTEA GHIEN', '', 'GALICIA', 'Female'),
(646, 200055, 'Tourism Management', '4th', 'MA. MELODY', '', 'MERCADEJAS', 'Female'),
(647, 200016, 'Tourism Management', '4th', 'LOVELY ROSE', '', 'MONTOYA', 'Female'),
(648, 180033, 'Tourism Management', '4th', 'CHLOIE', '', 'ONG', 'Female'),
(649, 200054, 'Tourism Management', '4th', 'SIGRID COLYNE NAOMI', '', 'PAZ', 'Female'),
(650, 200068, 'Tourism Management', '4th', 'IRISH MAE', '', 'RAPAL', 'Female');

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
(180, 81, 2, '2025'),
(188, 89, 8, '2001'),
(189, 90, 8, '2001'),
(190, 91, 8, '2001');

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
(8, 'Studio 5 Publishing', 'Manila, Ph');

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
(3, 31, 34, '2025-03-31 04:18:38', NULL, NULL, NULL, NULL, '2025-04-03 04:09:30', 1, 'Admin', NULL, 'Cancelled'),
(4, 31, 34, '2025-04-02 22:18:27', '2025-04-03 04:19:16', 1, '2025-04-03 04:19:24', 1, NULL, NULL, NULL, '2025-04-03 04:19:24', 'Received');

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
(14, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-02 04:28:43'),
(15, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-02 05:24:21'),
(16, 210078, 'Admin', 'Admin Logged In', 'Admin Kenneth Laurence Bonaagua Logged In as Active', '2025-04-03 01:22:34'),
(17, 210078, 'Student', 'User Logged In', 'Student Kenneth Laurence Bonaagua Logged In as Active', '2025-04-03 04:18:18'),
(18, 1, 'Admin', 'Admin Added New Book', 'Admin Kenneth Laurence Bonaagua added \"wsdawsadw\" with 3 copies', '2025-04-02 23:59:58'),
(19, 210078, 'Student', 'User Logged In', 'Student Kenneth Laurence Bonaagua Logged In as Active', '2025-04-03 10:02:12');

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
(31, 210078, 'Kenneth Laurence', NULL, 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$/Mp6RZAsEWMWNzTCNM.MLeF8e1dgWY2elORlDaJ3o57bB.zqrBJuu', '', '../Images/Profile/default-avatar.jpg', '', 'Student', '', '', '/upload/default-id.png', '2025-03-18', '1', '2025-04-03', '', '2025-04-03 17:44:46');

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
(22, 'E. Billy', '', 'Mondoñedo');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contributors`
--
ALTER TABLE `contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=947;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=651;

--
-- AUTO_INCREMENT for table `publications`
--
ALTER TABLE `publications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(225) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `writers`
--
ALTER TABLE `writers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
