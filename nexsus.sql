-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 07, 2025 at 09:22 PM
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
-- Database: `nexsus`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` char(36) NOT NULL,
  `application_number` varchar(32) NOT NULL,
  `client_id` char(36) NOT NULL,
  `counsellor_id` char(36) DEFAULT NULL,
  `university_id` char(36) NOT NULL,
  `program_id` char(36) NOT NULL,
  `status` enum('Draft','Submitted','In Review','Accepted','Rejected','Withdrawn') DEFAULT 'Draft',
  `applied_at` datetime DEFAULT NULL,
  `decision_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `application_number`, `client_id`, `counsellor_id`, `university_id`, `program_id`, `status`, `applied_at`, `decision_at`, `notes`, `created_at`, `updated_at`) VALUES
('5b90a361-43d3-11f0-bb1a-842afd92708e', 'APP20250001', '45c40eb9-43ca-11f0-bb1a-842afd92708e', '6061373cc9bf171056b32fbc085364ca', '3a941d57-43d3-11f0-bb1a-842afd92708e', '425de8e4-43d3-11f0-bb1a-842afd92708e', 'Draft', '2025-06-08 00:00:00', NULL, 'sdasdasd', '2025-06-08 00:57:30', '2025-06-08 00:57:30');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `dob` date DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `education_from` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT 'other',
  `marital_status` enum('single','married','other') DEFAULT 'single',
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `passport_expiry_date` date DEFAULT NULL,
  `accountId` char(36) NOT NULL,
  `invited_by` char(36) DEFAULT NULL,
  `handled_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `first_name`, `middle_name`, `last_name`, `dob`, `nationality`, `education_from`, `email`, `phone`, `alternate_phone`, `gender`, `marital_status`, `address`, `city`, `state`, `country`, `zip_code`, `profile_picture`, `passport_number`, `passport_expiry_date`, `accountId`, `invited_by`, `handled_by`, `created_at`, `updated_at`) VALUES
(4, 'Admin', '', '', NULL, NULL, NULL, 'admin@nexsus.com', NULL, NULL, 'other', 'single', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '185d09e2-43ca-11f0-bb1a-842afd92708e', NULL, NULL, '2025-06-07 23:51:11', '2025-06-07 23:51:11'),
(5, 'Mohan', '', 'Basnet', NULL, NULL, NULL, 'counsellor@nexsus.com', NULL, NULL, 'other', 'single', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '37092c8b-43ca-11f0-bb1a-842afd92708e', NULL, NULL, '2025-06-07 23:52:03', '2025-06-07 23:52:03'),
(6, 'Vicky', '', 'Prajapati', NULL, NULL, NULL, 'client@nexsus.com', '9844001101', NULL, 'male', 'single', 'Kathmandu', 'Kathmandu', '', 'Nepal', '44600', '', NULL, NULL, '45c40eb9-43ca-11f0-bb1a-842afd92708e', NULL, NULL, '2025-06-07 23:52:28', '2025-06-08 00:27:45');

-- --------------------------------------------------------

--
-- Table structure for table `counsellor`
--

CREATE TABLE `counsellor` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `dob` date DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT 'other',
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `accountId` char(36) NOT NULL,
  `invited_by` char(36) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `counsellor`
--

INSERT INTO `counsellor` (`id`, `first_name`, `middle_name`, `last_name`, `dob`, `nationality`, `email`, `phone`, `gender`, `address`, `city`, `state`, `country`, `zip_code`, `profile_picture`, `accountId`, `invited_by`, `created_at`, `updated_at`) VALUES
(2, 'Mohan', '', 'Basnet', '2002-01-13', 'Nepali', 'basnet.anish2030@gmail.com', '9844001101', 'male', 'Kathmandu', 'Kathmandu', 'Bagmati', 'Nepal', '44600', 'uploads/counsellor_68448e43f07cf.jpg', '6061373cc9bf171056b32fbc085364ca', NULL, '2025-06-08 00:53:52', '2025-06-08 00:53:52');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `status` enum('new','contacted','in_progress','converted','closed','lost') DEFAULT 'new',
  `assigned_to` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` char(36) NOT NULL,
  `university_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `level` enum('Bachelors','Masters','PhD','Diploma','Certificate','Other') DEFAULT 'Bachelors',
  `duration` varchar(100) DEFAULT NULL,
  `tuition_fee` decimal(12,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `university_id`, `name`, `level`, `duration`, `tuition_fee`, `description`, `created_at`, `updated_at`) VALUES
('425de8e4-43d3-11f0-bb1a-842afd92708e', '3a941d57-43d3-11f0-bb1a-842afd92708e', 'asdasdasd', 'PhD', '', NULL, '', '2025-06-08 00:56:48', '2025-06-08 00:56:48');

-- --------------------------------------------------------

--
-- Table structure for table `student_academics`
--

CREATE TABLE `student_academics` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `degree` varchar(100) NOT NULL,
  `board` varchar(100) NOT NULL,
  `year_of_passing` int(11) NOT NULL,
  `grade` varchar(20) NOT NULL,
  `grading_system` varchar(50) NOT NULL,
  `school_name` varchar(100) NOT NULL,
  `school_address` varchar(255) NOT NULL,
  `school_city` varchar(50) NOT NULL,
  `school_state` varchar(50) NOT NULL,
  `school_country` varchar(50) NOT NULL,
  `school_zip_code` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_academics`
--

INSERT INTO `student_academics` (`id`, `client_id`, `degree`, `board`, `year_of_passing`, `grade`, `grading_system`, `school_name`, `school_address`, `school_city`, `school_state`, `school_country`, `school_zip_code`, `created_at`, `updated_at`) VALUES
(3, 6, 'Bachelor', 'Distinctio Corrupti', 2012, 'Sed dolores et eos', 'GPA', 'Molly Fulton', 'Et consequat Minus', 'Sint aut cillum even', 'Consequat Quia dese', 'Duis at ipsum aut i', '88466', '2025-06-08 00:37:44', '2025-06-08 00:37:44'),
(4, 6, 'Master', 'Sit numquam aut moll', 2018, '3.75', 'GPA', 'Alexis Shaffer', 'Laborum tempore qui', 'Blanditiis optio re', 'Ut qui est duis ut', 'Velit voluptat', '29044', '2025-06-08 00:38:24', '2025-06-08 00:52:06'),
(5, 6, 'SEE', 'Consequatur ex dolo', 2013, '90', 'Percentage', 'Harper Decker', 'Do assumenda volupta', 'Exercitationem repel', 'Soluta quasi sed quo', 'Incididunt in fuga', '87969', '2025-06-08 01:03:35', '2025-06-08 01:03:35');

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

CREATE TABLE `student_documents` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `document_url` varchar(255) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_documents`
--

INSERT INTO `student_documents` (`id`, `client_id`, `document_type`, `document_url`, `document_name`, `created_at`, `updated_at`) VALUES
(2, 6, 'Academic Certificate', 'uploads/documents/68448df19c5ab_Nexsus ERD.png', 'Passport', '2025-06-08 00:52:29', '2025-06-08 00:52:29'),
(3, 6, 'Transcript', 'uploads/documents/684490b06dd45_Nexsus ERD.png', 'Transcript of +2', '2025-06-08 01:04:12', '2025-06-08 01:04:12');

-- --------------------------------------------------------

--
-- Table structure for table `student_preferences`
--

CREATE TABLE `student_preferences` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `visa_type` varchar(50) NOT NULL,
  `country` varchar(50) NOT NULL,
  `course` varchar(100) NOT NULL,
  `level` varchar(50) NOT NULL,
  `intake` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_preferences`
--

INSERT INTO `student_preferences` (`id`, `client_id`, `visa_type`, `country`, `course`, `level`, `intake`, `created_at`, `updated_at`) VALUES
(2, 6, 'Student', 'USA', 'BIT', 'Undergraduate', '2025-06', '2025-06-08 01:04:41', '2025-06-08 01:04:41');

-- --------------------------------------------------------

--
-- Table structure for table `student_test_scores`
--

CREATE TABLE `student_test_scores` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `exam_type` varchar(50) NOT NULL,
  `date_of_exam` date NOT NULL,
  `overall_score` int(11) NOT NULL,
  `listening` varchar(4) NOT NULL,
  `reading` varchar(4) NOT NULL,
  `writing` varchar(4) NOT NULL,
  `speaking` varchar(4) NOT NULL,
  `gre_score` int(11) DEFAULT NULL,
  `gmat_score` int(11) DEFAULT NULL,
  `sat_score` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_test_scores`
--

INSERT INTO `student_test_scores` (`id`, `client_id`, `exam_type`, `date_of_exam`, `overall_score`, `listening`, `reading`, `writing`, `speaking`, `gre_score`, `gmat_score`, `sat_score`, `created_at`, `updated_at`) VALUES
(2, 6, 'IELTS', '2025-06-09', 8, '', '', '', '', NULL, NULL, NULL, '2025-06-08 01:05:13', '2025-06-08 01:05:13');

-- --------------------------------------------------------

--
-- Table structure for table `universities`
--

CREATE TABLE `universities` (
  `id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `universities`
--

INSERT INTO `universities` (`id`, `name`, `country`, `city`, `website`, `description`, `logo`, `created_at`, `updated_at`) VALUES
('3a941d57-43d3-11f0-bb1a-842afd92708e', 'Mohan Basnet', 'Nepal', 'Kathmandu', '', '', '', '2025-06-08 00:56:35', '2025-06-08 00:56:35');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `role` enum('admin','client','counsellor') NOT NULL DEFAULT 'client',
  `banned` tinyint(1) DEFAULT 0,
  `ban_reason` varchar(255) DEFAULT NULL,
  `ban_expires` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `email`, `password`, `email_verified`, `role`, `banned`, `ban_reason`, `ban_expires`, `created_at`, `updated_at`) VALUES
('185d09e2-43ca-11f0-bb1a-842afd92708e', 'Admin', 'admin@nexsus.com', '$2y$10$K93Lj6y0ew9r/SA1nZ/Ec.G7DZ9YV1Jo4t1B3r4/CsbYH6cQRQhQO', 0, 'admin', 0, NULL, NULL, '2025-06-07 23:51:11', '2025-06-07 23:52:57'),
('37092c8b-43ca-11f0-bb1a-842afd92708e', 'Mohan Basnet', 'counsellor@nexsus.com', '$2y$10$5.uq1s7hAiVlDt8FkNWHc.fJgxYYBjhgQZGFt1TsgOdbgk9G4L0km', 0, 'counsellor', 0, NULL, NULL, '2025-06-07 23:52:03', '2025-06-07 23:53:02'),
('45c40eb9-43ca-11f0-bb1a-842afd92708e', 'Vicky Prajapati', 'client@nexsus.com', '$2y$10$uiUnYxFx71DyrEY3F4Se1ujN4ECzYdO7gIiiBIXHyE4flJ56PD.yu', 0, 'client', 0, NULL, NULL, '2025-06-07 23:52:28', '2025-06-07 23:52:28'),
('6061373cc9bf171056b32fbc085364ca', 'Mohan  Basnet', 'basnet.anish2030@gmail.com', '$2y$10$z0lnzyz0ddXB9xoLNcQ6k.Cy2rRfJuYpWGpMkTy/b4DvTWgiazHMe', 0, 'counsellor', 0, NULL, NULL, '2025-06-08 00:53:52', '2025-06-08 00:53:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_number` (`application_number`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `counsellor_id` (`counsellor_id`),
  ADD KEY `university_id` (`university_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `accountId` (`accountId`);

--
-- Indexes for table `counsellor`
--
ALTER TABLE `counsellor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `accountId` (`accountId`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `student_academics`
--
ALTER TABLE `student_academics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `student_preferences`
--
ALTER TABLE `student_preferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `student_test_scores`
--
ALTER TABLE `student_test_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `universities`
--
ALTER TABLE `universities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `counsellor`
--
ALTER TABLE `counsellor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_academics`
--
ALTER TABLE `student_academics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_preferences`
--
ALTER TABLE `student_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_test_scores`
--
ALTER TABLE `student_test_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`accountId`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`counsellor_id`) REFERENCES `counsellor` (`accountId`) ON DELETE SET NULL,
  ADD CONSTRAINT `applications_ibfk_3` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_4` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`accountId`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `counsellor`
--
ALTER TABLE `counsellor`
  ADD CONSTRAINT `counsellor_ibfk_1` FOREIGN KEY (`accountId`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_academics`
--
ALTER TABLE `student_academics`
  ADD CONSTRAINT `student_academics_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD CONSTRAINT `student_documents_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_preferences`
--
ALTER TABLE `student_preferences`
  ADD CONSTRAINT `student_preferences_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_test_scores`
--
ALTER TABLE `student_test_scores`
  ADD CONSTRAINT `student_test_scores_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
