-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 20, 2025 at 06:30 PM
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
(4, 1, 1, '2025-06-13 19:07:00', 'ভাত খায়তে পারিনা', 'completed', NULL, '2025-06-12 17:08:09', '2025-11-18 05:49:57', NULL, NULL, NULL),
(5, 1, 2, '2025-06-13 19:19:00', 'পেট ব্যাথা', '', NULL, '2025-06-12 17:20:00', '2025-06-12 17:41:58', NULL, 'তুমি সকাল ১০টায় দেখা করো', NULL),
(6, 1, 2, '2025-06-13 19:32:00', 'গুম', 'completed', NULL, '2025-06-12 17:32:29', '2025-06-12 17:34:42', NULL, NULL, '১৪৫৫'),
(7, 1, 2, '2025-06-21 19:42:00', 'বুকে ব্যাপার', 'accepted', NULL, '2025-06-12 17:42:35', '2025-06-12 17:42:40', NULL, '', NULL),
(8, 1, 2, '2025-06-26 19:43:00', 'জজরর', 'accepted', NULL, '2025-06-12 17:43:31', '2025-06-12 17:43:44', NULL, 'দহগসযডপ তকুইদতা', NULL),
(9, 1, 2, '2025-06-17 08:29:00', 'fawsdffwgerqgerqgerq', 'accepted', NULL, '2025-06-16 06:29:39', '2025-06-16 06:31:15', NULL, 'ljqwiejtoejflk psdjf', NULL),
(10, 1, 2, '2025-06-19 09:45:00', 'asdf afadsf', 'accepted', NULL, '2025-06-18 07:46:12', '2025-09-02 04:41:49', NULL, '', NULL),
(11, 3, 4, '2025-11-11 17:52:00', 'পেট ব্যাথা', 'completed', NULL, '2025-11-11 04:52:58', '2025-11-11 04:56:50', NULL, 'তুমি সন্ধ্যা ৬টায় চেম্বারে দেখা করো', NULL),
(12, 3, 4, '2025-11-11 18:26:00', 'qwwawa', 'completed', NULL, '2025-11-11 05:27:08', '2025-11-11 05:32:31', NULL, 'fhghjjh', NULL),
(13, 2, 5, '2025-11-18 17:37:00', 'Head Pain', 'accepted', NULL, '2025-11-18 04:38:12', '2025-11-18 05:52:41', NULL, NULL, 'I am busy at this time. Please book appointment at tomorrow');

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
(2, 4, 'Officer', 'Balul', 'MBBS, FCPS (Medicine), BCS(Health)', 'Neurologist', '10002', NULL, '2025-06-11 08:46:05', '2025-06-11 08:46:05'),
(3, 8, 'Medical Officer', 'BGC', 'MBBS, BCS', 'Cardiology', 'MS-000399', NULL, '2025-11-11 04:50:54', '2025-11-11 04:50:54');

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
(7, 2, '2025-06-12', 150.00, 50.00, 5.66, 75, 100, 'Normal weight', '2025-06-12 17:51:57', '2025-06-12 17:51:57'),
(8, 2, '2025-06-18', 158.00, 80.00, 5.66, 75, 100, 'Obesity', '2025-06-18 08:02:52', '2025-06-18 08:02:52'),
(9, 2, '2025-06-18', 159.00, 70.00, 5.66, 75, 100, 'Overweight', '2025-06-18 08:03:28', '2025-06-18 08:03:28'),
(10, 5, '2025-11-18', 176.00, 60.00, 95.00, 125, 99, 'Normal weight', '2025-11-18 04:42:51', '2025-11-18 04:42:51');

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
(5, 'KFT / RFT', 'Kidney dess', '2025-06-12 12:34:24');

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
(2, 5, '2005-06-16', 'Male', 'AB+', '2025-06-11 09:05:01', '2025-06-11 09:05:01'),
(3, 6, '1997-01-05', 'Male', 'A+', '2025-11-04 14:36:24', '2025-11-04 14:36:24'),
(4, 7, '2002-01-01', 'Male', 'O-', '2025-11-11 04:46:07', '2025-11-11 04:46:07'),
(5, 9, '2002-06-01', 'Male', 'B+', '2025-11-18 04:36:34', '2025-11-18 04:36:34');

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
(2, 1, '', 'uploads/prescriptions/pres_684a7defa0245_ChatGPT Image Jun 4, 2025, 01_25_36 AM.png', '2025-06-12 07:12:47', '[{\"name\":\"Mkast 10\",\"dosage\":\"1\",\"frequency\":\"Daily\"}]'),
(3, 4, '', 'uploads/prescriptions/pres_6912bff9ebc6f_CamScanner 04-19-2025 15.53_2.jpg', '2025-11-11 04:47:53', '[{\"name\":\"Napa\",\"dosage\":\"500mg\",\"frequency\":\"BID\"}]'),
(4, 4, '', 'uploads/prescriptions/pres_6912c8f99123c_9.pdf', '2025-11-11 05:26:17', '[{\"name\":\"q\",\"dosage\":\"\",\"frequency\":\"q\"}]'),
(5, 5, 'This kdsfjdkferiughjgnsklgjnsfhgueofghoeurgjsdfvnseruhg', 'uploads/prescriptions/pres_691bf8bec47ed_সাস্থ্য কেন্দ্র.pdf', '2025-11-18 04:40:30', '[{\"name\":\"tupnil\",\"dosage\":\"3 tab\",\"frequency\":\"Daily\"}]');

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
(9, 1, NULL, 'etfrds', '[{\"name\":\"sadf\",\"dosage\":\"ewrw\",\"frequency\":\"er\"}]', 'wtert', '2025-06-12 17:03:47', '', '', NULL, 'Habzas', 'Female', '2025-06-02'),
(10, 2, NULL, 'Klban', '[{\"name\":\"Tab. Abc\",\"dosage\":\"20mg\",\"frequency\":\"1+1+0\"},{\"name\":\"Shy. Avc\",\"dosage\":\"30mg\",\"frequency\":\"1+1+2\"}]', 'Hxhvxn', '2025-06-13 09:29:16', '', '', NULL, 'Hafiz', 'Male', '2025-06-02'),
(11, 1, NULL, 'alsdkfjinglqeriojipfjasdpif', '[{\"name\":\"napa\",\"dosage\":\"500mg\",\"frequency\":\"1+1+1\"},{\"name\":\";laksjf\",\"dosage\":\"200mg\",\"frequency\":\"1+0+0\"}]', 'sdfjasldkjf', '2025-06-16 06:27:17', '', '', NULL, 'tahsin', 'Male', '2004-02-03'),
(12, 1, NULL, 'Fever 3day 102.4 F', '[{\"name\":\"Amoxicillin\",\"dosage\":\"250mg\",\"frequency\":\"BID\"},{\"name\":\"Napa Extend\",\"dosage\":\"625 mg\",\"frequency\":\"BID\"},{\"name\":\"Maxpro\",\"dosage\":\"20mg\",\"frequency\":\"BID\"},{\"name\":\"Provair\",\"dosage\":\"10mg\",\"frequency\":\"0+0+1\"}]', 'GO', '2025-06-18 07:29:08', '', '', NULL, 'Sadman', 'Male', '2003-02-22'),
(13, 3, NULL, 'ঋউইআঋউইআ', '[{\"name\":\"উঋইআ\",\"dosage\":\"\",\"frequency\":\"১\"},{\"name\":\"১\",\"dosage\":\"\",\"frequency\":\"১\"}]', 'উঋইআ', '2025-11-11 04:54:33', '', '', NULL, 'ঋউইআঋউইআ', 'Male', '2025-11-12');

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
(6, 1, '9daba45b5ba64702651b5bbe00c42ce0eeca62d70d3fedc482f5c946dc6dcee6', '2025-06-12 18:17:53', 1, '2025-06-12 16:12:53'),
(7, 1, 'e695add65bd0eeb47066967c00233d052c0d9d7fed32de39b9ffa18103dd8afe', '2025-06-14 05:23:55', 0, '2025-06-14 03:18:55'),
(8, 2, '3bf4675ae6b432b94755f3ec073bf448f4f63e11408a394c11e767d629f5ccfc', '2025-06-16 08:34:51', 1, '2025-06-16 06:29:51'),
(9, 2, '7d9898f88d3993bdae4d7fc443f3d72de0f22ab1ff3d6981774157d8a5501b82', '2025-06-18 10:08:58', 0, '2025-06-18 08:03:58'),
(10, 2, '1b01cac58243606df48ff9ea8ebdc0b5c04b6ac88e3fb86e49edb592e7ebf60b', '2025-06-18 10:08:59', 1, '2025-06-18 08:03:59'),
(11, 1, '6937697e39bd183fa388a19a3917cb9f14d2f8de1e435917514f4483c358c3ca', '2025-09-02 06:48:39', 1, '2025-09-02 04:43:39'),
(12, 3, '11ac523a6fb1d0bcafb6bf25542e6a4d25ac9039effab59710bf44e84d86fa71', '2025-11-04 17:00:11', 0, '2025-11-04 15:55:11'),
(13, 3, '7eb810e6e272a2fc15120c762759e9872f02d14cb098c7af7f90f545a3cf8621', '2025-11-04 17:04:04', 1, '2025-11-04 15:59:04'),
(14, 4, 'd7390de231635e47cca5418b91ccec3943fb17433cb8a806ed349e5a7b9d46f8', '2025-11-11 05:51:47', 0, '2025-11-11 04:46:47'),
(15, 4, 'e3a09a3b7c9f084b84ff363d319ee26181a3d53c86f57aa7d3ca69eaab5d32ff', '2025-11-11 05:56:21', 1, '2025-11-11 04:51:21');

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
(5, 'Md Adib', 'adib@gmail.com', 'mdadib', '$2y$10$gnNpOp8jGzsFLnR8ri6vUO8kWyDderS0eTno7jVfOuHyAuGl4JS8W', '01425783625', 'patient', '2025-06-11 09:05:01', '2025-06-11 09:05:01'),
(6, 'D.M Ashab Uddin', 'ashab@email.com', 'ashab', '$2y$10$0LzM.YURzx/ht9JIH2wD0eddCDAHQY0cLobKYgEfk124z6HbrT31e', '0181544747', 'patient', '2025-11-04 14:36:24', '2025-11-04 14:36:24'),
(7, 'Mohammad Younus', 'younos@gmail.com', 'younos', '$2y$10$8LE996/xx5juI8uzij5bc.jFJGqqQcgzcCzpvhKNCA1HUC8Oa55oi', '01897711222', 'patient', '2025-11-11 04:46:07', '2025-11-11 04:46:07'),
(8, 'Shachin', 'shachin@gmail.com', 'shachin123', '$2y$10$tCbvT6guNA2pUIZpWbPqFOlgiko4oKyzOWWnteScPPrUaFUy.uP4C', '01998822331', 'doctor', '2025-11-11 04:50:54', '2025-11-11 04:50:54'),
(9, 'Md Naim Uddin', 'naim5641@gmail.com', 'NaimUddin', '$2y$10$L6K48/CcPyvm2uE5/o7GYeGZgYCWYPa2fXQ4rCwHMayswxDBXT8Ca', '01843619759', 'patient', '2025-11-18 04:36:34', '2025-11-18 04:36:34');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `chronic_conditions`
--
ALTER TABLE `chronic_conditions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `health_conditions`
--
ALTER TABLE `health_conditions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `patient_uploaded_lab_reports`
--
ALTER TABLE `patient_uploaded_lab_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patient_uploaded_prescriptions`
--
ALTER TABLE `patient_uploaded_prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `qr_access_tokens`
--
ALTER TABLE `qr_access_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
