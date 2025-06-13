-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 12, 2025 at 09:58 PM
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
-- Database: `ehr_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_tokens`
--

CREATE TABLE `access_tokens` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `allergies`
--

CREATE TABLE `allergies` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `allergen_name` varchar(255) NOT NULL,
  `reaction` text DEFAULT NULL,
  `severity` enum('mild','moderate','severe','life-threatening') DEFAULT 'mild',
  `diagnosis_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_date_time` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','accepted','declined','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reason_for_cancellation` text DEFAULT NULL,
  `doctor_notes` text DEFAULT NULL,
  `reason_for_decline` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `appointment_date_time`, `reason`, `status`, `notes`, `created_at`, `updated_at`, `reason_for_cancellation`, `doctor_notes`, `reason_for_decline`) VALUES
(1, 1, 1, '2025-07-01 02:39:00', 'I am ill', 'completed', NULL, '2025-06-11 00:39:53', '2025-06-12 15:35:45', NULL, NULL, NULL),
(2, 1, 2, '2025-06-12 11:36:00', 'Ajjskk', 'cancelled', NULL, '2025-06-11 09:36:49', '2025-06-12 17:48:13', 'fgweqrdstgvdsf', NULL, NULL),
(3, 2, 1, '2025-06-13 11:04:00', 'ঘুম বেশি', 'completed', NULL, '2025-06-12 09:05:05', '2025-06-12 15:35:56', NULL, NULL, NULL),
(4, 1, 1, '2025-06-13 19:07:00', 'ভাত খায়তে পারিনা', 'accepted', NULL, '2025-06-12 17:08:09', '2025-06-12 17:08:20', NULL, NULL, NULL),
(5, 1, 2, '2025-06-13 19:19:00', 'পেট ব্যাথা', '', NULL, '2025-06-12 17:20:00', '2025-06-12 17:41:58', NULL, 'তুমি সকাল ১০টায় দেখা করো', NULL),
(6, 1, 2, '2025-06-13 19:32:00', 'গুম', 'completed', NULL, '2025-06-12 17:32:29', '2025-06-12 17:34:42', NULL, NULL, '১৪৫৫'),
(7, 1, 2, '2025-06-21 19:42:00', 'বুকে ব্যাপার', 'accepted', NULL, '2025-06-12 17:42:35', '2025-06-12 17:42:40', NULL, '', NULL),
(8, 1, 2, '2025-06-26 19:43:00', 'জজরর', 'accepted', NULL, '2025-06-12 17:43:31', '2025-06-12 17:43:44', NULL, 'দহগসযডপ তকুইদতা', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chronic_conditions`
--

CREATE TABLE `chronic_conditions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `condition_name` varchar(255) NOT NULL,
  `diagnosis_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chronic_conditions`
--

INSERT INTO `chronic_conditions` (`id`, `patient_id`, `doctor_id`, `condition_name`, `diagnosis_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Col', '2025-06-05', 'laje3o', '2025-06-12 09:23:37', '2025-06-12 09:23:37');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `chamber_location` text DEFAULT NULL,
  `education_qualification` text DEFAULT NULL,
  `specialty` varchar(255) DEFAULT NULL,
  `license_number` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `designation`, `chamber_location`, `education_qualification`, `specialty`, `license_number`, `profile_picture`, `created_at`, `updated_at`) VALUES
(1, 2, 'Medical Officer.', 'CMC, Bangladesh', 'MBBS, FCPS, BCS (Health)', 'Cardiologist', '10001', NULL, '2025-06-11 00:26:02', '2025-06-11 07:30:19'),
(2, 4, 'Officer', 'Balul', 'MBBS, FCPS (Medicine), BCS(Health)', 'Neurologist', '10002', NULL, '2025-06-11 08:46:05', '2025-06-11 08:46:05');

-- --------------------------------------------------------

--
-- Table structure for table `health_conditions`
--

CREATE TABLE `health_conditions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `date_recorded` date DEFAULT current_timestamp(),
  `height_cm` decimal(5,2) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `blood_sugar_mgdl` decimal(6,2) DEFAULT NULL,
  `blood_pressure_systolic` int(11) DEFAULT NULL,
  `blood_pressure_diastolic` int(11) DEFAULT NULL,
  `bmi_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `health_conditions`
--

INSERT INTO `health_conditions` (`id`, `patient_id`, `date_recorded`, `height_cm`, `weight_kg`, `blood_sugar_mgdl`, `blood_pressure_systolic`, `blood_pressure_diastolic`, `bmi_message`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-06-12', 109.00, 43.00, 1.68, 83, 87, 'Obesity', '2025-06-12 09:38:11', '2025-06-12 09:38:11'),
(2, 1, '2025-06-12', 109.00, 43.00, 1.68, 83, 87, 'Obesity', '2025-06-12 09:40:32', '2025-06-12 09:40:32'),
(3, 1, '2025-06-12', 114.00, 46.00, 3.25, 86, 108, 'Obesity', '2025-06-12 09:41:56', '2025-06-12 09:41:56'),
(4, 1, '2025-06-12', 112.00, 46.00, 3.25, 86, 108, 'Obesity', '2025-06-12 09:42:38', '2025-06-12 09:42:38'),
(5, 1, '2025-06-12', 116.00, 46.00, 3.25, 86, 108, 'Obesity', '2025-06-12 09:44:27', '2025-06-12 09:44:27'),
(6, 1, '2025-06-12', 116.00, 66.00, 3.25, 86, 108, 'Obesity', '2025-06-12 09:44:42', '2025-06-12 09:44:42'),
(7, 2, '2025-06-12', 150.00, 50.00, 5.66, 75, 100, 'Normal weight', '2025-06-12 17:51:57', '2025-06-12 17:51:57');

-- --------------------------------------------------------

--
-- Table structure for table `lab_orders`
--

CREATE TABLE `lab_orders` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_orders`
--

INSERT INTO `lab_orders` (`id`, `patient_id`, `doctor_id`, `test_id`, `order_date`, `status`, `notes`) VALUES
(1, 1, 1, 1, '2025-06-11 08:12:48', 'pending', 'For thos'),
(2, 1, 1, 2, '2025-06-12 12:37:31', 'pending', ''),
(3, 1, 1, 3, '2025-06-12 12:37:31', 'pending', ''),
(4, 1, 1, 2, '2025-06-12 12:37:59', 'pending', '');

-- --------------------------------------------------------

--
-- Table structure for table `lab_results`
--

CREATE TABLE `lab_results` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `result_data` text DEFAULT NULL,
  `result_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `lab_person_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests`
--

CREATE TABLE `lab_tests` (
  `id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_tests`
--

INSERT INTO `lab_tests` (`id`, `test_name`, `description`, `created_at`) VALUES
(1, 'CBC', 'Your CBC is low', '2025-06-11 08:07:53'),
(2, 'Blood Glucose Test', '', '2025-06-12 12:31:48'),
(3, 'Lipid Profile', 'Total Cholesterol, HDL, LDL', '2025-06-12 12:32:59'),
(4, 'LFT - Liver Function Test', '', '2025-06-12 12:34:22'),
(5, 'KFT / RFT', 'Kidney Function Test / Renal Function Test', '2025-06-12 12:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `medical_reports`
--

CREATE TABLE `medical_reports` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_date` date DEFAULT NULL,
  `report_details` text DEFAULT NULL,
  `report_file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `user_id`, `date_of_birth`, `gender`, `blood_group`, `created_at`, `updated_at`) VALUES
(1, 3, '2007-12-05', 'Male', 'O+', '2025-06-11 00:28:08', '2025-06-11 00:28:08'),
(2, 5, '2005-06-16', 'Male', 'AB+', '2025-06-11 09:05:01', '2025-06-11 09:05:01');

-- --------------------------------------------------------

--
-- Table structure for table `patient_uploaded_lab_reports`
--

CREATE TABLE `patient_uploaded_lab_reports` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_uploaded_lab_reports`
--

INSERT INTO `patient_uploaded_lab_reports` (`id`, `patient_id`, `test_name`, `notes`, `image_path`, `uploaded_at`) VALUES
(2, 1, 'CBC', 'dsfas', NULL, '2025-06-12 14:24:31');

-- --------------------------------------------------------

--
-- Table structure for table `patient_uploaded_prescriptions`
--

CREATE TABLE `patient_uploaded_prescriptions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `medications_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`medications_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_uploaded_prescriptions`
--

INSERT INTO `patient_uploaded_prescriptions` (`id`, `patient_id`, `notes`, `image_path`, `uploaded_at`, `medications_json`) VALUES
(1, 1, '', NULL, '2025-06-12 06:58:42', '[{\"name\":\"Napa 500mg\",\"dosage\":\"3 tab\",\"frequency\":\"Daily\"},{\"name\":\"Provair\",\"dosage\":\"1 tab\",\"frequency\":\"Daily\"}]'),
(2, 1, '', 'uploads/prescriptions/pres_684a7defa0245_ChatGPT Image Jun 4, 2025, 01_25_36 AM.png', '2025-06-12 07:12:47', '[{\"name\":\"Mkast 10\",\"dosage\":\"1\",\"frequency\":\"Daily\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `diagnosis` text NOT NULL,
  `medications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`medications`)),
  `instructions` text NOT NULL,
  `prescription_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `medication` varchar(255) NOT NULL,
  `dosage` varchar(255) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `walkin_patient_name` varchar(255) DEFAULT NULL,
  `walkin_patient_gender` enum('Male','Female','Other') DEFAULT NULL,
  `walkin_patient_dob` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `doctor_id`, `patient_id`, `diagnosis`, `medications`, `instructions`, `prescription_date`, `medication`, `dosage`, `notes`, `walkin_patient_name`, `walkin_patient_gender`, `walkin_patient_dob`) VALUES
(1, 1, NULL, 'asdjfkjdsanf', '[{\"name\":\"sd\",\"dosage\":\"20\",\"frequency\":\"3\"}]', 'asdfe', '2025-06-11 01:24:44', '', '', NULL, 'Kurshed', 'Male', '2007-12-07'),
(4, 1, NULL, 'fdsgtewrygrtefg', '[{\"name\":\"fdgd\",\"dosage\":\"ff\",\"frequency\":\"sdsdg\"}]', 'sfdgs', '2025-06-12 16:28:26', '', '', NULL, 'trwert', 'Male', '2025-06-06'),
(5, 1, NULL, 'Normal Ingured', '[{\"name\":\"Napa\",\"dosage\":\"500 mg\",\"frequency\":\"1+1+1\"},{\"name\":\"xorel\",\"dosage\":\"20 mg\",\"frequency\":\"1+0+1\"}]', '2', '2025-06-12 16:34:53', '', '', NULL, 'Mokit', 'Male', '2025-06-04'),
(6, 1, NULL, 'adwtdfsgfd', '[{\"name\":\"sadf\",\"dosage\":\"ewrw\",\"frequency\":\"er\"}]', 'ewrwer', '2025-06-12 16:54:00', '', '', NULL, 'Habza', 'Female', '2025-06-02'),
(7, 1, NULL, 'adwtdfsgfd', '[{\"name\":\"sadf\",\"dosage\":\"ewrw\",\"frequency\":\"er\"}]', 'ewrwer', '2025-06-12 16:55:25', '', '', NULL, 'Habza', 'Female', '2025-06-02'),
(8, 1, NULL, 'adfewrqwe', '[{\"name\":\"sadf\",\"dosage\":\"ewrw\",\"frequency\":\"er\"},{\"name\":\"dfg\",\"dosage\":\"fgdf\",\"frequency\":\"ert\"}]', 're5rdge', '2025-06-12 17:00:36', '', '', NULL, 'Habzas', 'Female', '2025-06-02'),
(9, 1, NULL, 'etfrds', '[{\"name\":\"sadf\",\"dosage\":\"ewrw\",\"frequency\":\"er\"}]', 'wtert', '2025-06-12 17:03:47', '', '', NULL, 'Habzas', 'Female', '2025-06-02');

-- --------------------------------------------------------

--
-- Table structure for table `qr_access_tokens`
--

CREATE TABLE `qr_access_tokens` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_access_tokens`
--

INSERT INTO `qr_access_tokens` (`id`, `patient_id`, `token`, `expires_at`, `is_used`, `generated_at`) VALUES
(1, 1, 'd04126c32141d89653d4565858596450957d3a293f1b3506c3fc6f3a2cbf8cdb', '2025-06-12 12:42:54', 0, '2025-06-12 10:37:54'),
(2, 1, 'fc0e81a871d12d32b3da6032c886c641c04852df9d8f3fe6f7fa6e625e815f0e', '2025-06-12 12:50:14', 0, '2025-06-12 10:45:14'),
(3, 1, 'cfa8cb3135d707adad08aad8841fffb7a5012aa1068e51382c165d8d0317c6eb', '2025-06-12 13:03:27', 1, '2025-06-12 10:58:27'),
(4, 1, 'd371470dbcbcee1ecb29cf3ae25340718871f59b65c541065f3f458214261eed', '2025-06-12 13:20:24', 1, '2025-06-12 11:15:24'),
(5, 1, 'c186d4b2530374a532dad7b8809b119669bb3a877e6bac518361d11a392a8803', '2025-06-12 14:25:44', 1, '2025-06-12 12:20:44'),
(6, 1, '9daba45b5ba64702651b5bbe00c42ce0eeca62d70d3fedc482f5c946dc6dcee6', '2025-06-12 18:17:53', 1, '2025-06-12 16:12:53');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `user_type` enum('doctor','patient','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `username`, `password`, `mobile`, `user_type`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@admin.com', NULL, '$2y$10$lvRgpeXRw.qz8FO2Q9ZHqOooeZLrkrsPuCzkE2Q7ol/580dBHjVCi', '01234567891', 'admin', '2025-06-11 00:22:50', '2025-06-11 00:22:50'),
(2, 'Md Mostak', 'mostak@gmail.com', 'drmostak', '$2y$10$pAOUbp6uIsN.JLAZu0S8F.9AoJ0DoaMQ87HVTvrKHuc7Dkus8AHD6', '01896543577', 'doctor', '2025-06-11 00:26:02', '2025-06-11 07:29:57'),
(3, 'Habib', 'habib@gmail.com', 'mdhabib', '$2y$10$9MgfjM0lOxLg.bZHBKJDgeUBdgP4RrCWBVSnRciEYz3BDuCNCeNiy', '01254787854', 'patient', '2025-06-11 00:28:08', '2025-06-11 07:33:06'),
(4, 'Md. Sakib', 'sakib@gmail.com', 'drsakib', '$2y$10$ZAFpuOa61jnDqBSZH/0TOuh/jRt0QAxxkb3CL1ecepf.XXK2zXbmi', '01345858575', 'doctor', '2025-06-11 08:46:05', '2025-06-11 08:46:05'),
(5, 'Md Adib', 'adib@gmail.com', 'mdadib', '$2y$10$gnNpOp8jGzsFLnR8ri6vUO8kWyDderS0eTno7jVfOuHyAuGl4JS8W', '01425783625', 'patient', '2025-06-11 09:05:01', '2025-06-11 09:05:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_tokens`
--
ALTER TABLE `access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `allergies`
--
ALTER TABLE `allergies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `chronic_conditions`
--
ALTER TABLE `chronic_conditions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `health_conditions`
--
ALTER TABLE `health_conditions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `lab_orders`
--
ALTER TABLE `lab_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `test_id` (`test_id`);

--
-- Indexes for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`);

--
-- Indexes for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `test_name` (`test_name`);

--
-- Indexes for table `medical_reports`
--
ALTER TABLE `medical_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `patient_uploaded_lab_reports`
--
ALTER TABLE `patient_uploaded_lab_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `patient_uploaded_prescriptions`
--
ALTER TABLE `patient_uploaded_prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `qr_access_tokens`
--
ALTER TABLE `qr_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_tokens`
--
ALTER TABLE `access_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `allergies`
--
ALTER TABLE `allergies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chronic_conditions`
--
ALTER TABLE `chronic_conditions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `health_conditions`
--
ALTER TABLE `health_conditions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lab_orders`
--
ALTER TABLE `lab_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lab_results`
--
ALTER TABLE `lab_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lab_tests`
--
ALTER TABLE `lab_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `medical_reports`
--
ALTER TABLE `medical_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patient_uploaded_lab_reports`
--
ALTER TABLE `patient_uploaded_lab_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patient_uploaded_prescriptions`
--
ALTER TABLE `patient_uploaded_prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `qr_access_tokens`
--
ALTER TABLE `qr_access_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `access_tokens`
--
ALTER TABLE `access_tokens`
  ADD CONSTRAINT `access_tokens_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `allergies`
--
ALTER TABLE `allergies`
  ADD CONSTRAINT `allergies_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `allergies_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chronic_conditions`
--
ALTER TABLE `chronic_conditions`
  ADD CONSTRAINT `chronic_conditions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chronic_conditions_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `health_conditions`
--
ALTER TABLE `health_conditions`
  ADD CONSTRAINT `health_conditions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_orders`
--
ALTER TABLE `lab_orders`
  ADD CONSTRAINT `lab_orders_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lab_orders_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lab_orders_ibfk_3` FOREIGN KEY (`test_id`) REFERENCES `lab_tests` (`id`);

--
-- Constraints for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD CONSTRAINT `lab_results_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `lab_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_reports`
--
ALTER TABLE `medical_reports`
  ADD CONSTRAINT `medical_reports_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_uploaded_lab_reports`
--
ALTER TABLE `patient_uploaded_lab_reports`
  ADD CONSTRAINT `patient_uploaded_lab_reports_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_uploaded_prescriptions`
--
ALTER TABLE `patient_uploaded_prescriptions`
  ADD CONSTRAINT `patient_uploaded_prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `qr_access_tokens`
--
ALTER TABLE `qr_access_tokens`
  ADD CONSTRAINT `qr_access_tokens_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
