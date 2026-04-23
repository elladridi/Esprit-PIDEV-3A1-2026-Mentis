-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 05, 2026 at 01:55 PM
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
-- Database: `mentis`
--

-- --------------------------------------------------------

--
-- Table structure for table `assessment`
--

CREATE TABLE `assessment` (
  `assessment_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` date DEFAULT curdate(),
  `image_path` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessment`
--

INSERT INTO `assessment` (`assessment_id`, `title`, `description`, `type`, `status`, `created_at`, `image_path`) VALUES
(11, 'DEPRESSION', 'Measures symptoms of depression including persistent sadness, loss of interest, and changes in sleep or appetite. Helps identify if professional help might be beneficial.', 'Depression', 'Active', '2026-03-01', 'assessment_images/assessment_1770405711552.jpg'),
(12, 'Anxiety', 'Evaluates feelings of anxiety, nervousness, and excessive worry that interfere with daily activities. Identifies anxiety patterns and severity.', 'Anxiety', 'Active', '2026-03-01', 'assessment_images/assessment_1770405805779.jpg'),
(13, 'Well-being Assessment', 'Measures overall life satisfaction, happiness, and positive functioning. Assesses emotional health and quality of life indicators.', 'Wellness', 'Active', '2026-03-01', 'assessment_images/assessment_1770405902229.jpg'),
(14, 'Stress', 'Evaluates stress levels and coping mechanisms. Identifies stress triggers and provides recommendations for stress management techniques.', 'Stress', 'Active', '2026-03-01', 'assessment_images/assessment_1770405929962.jpg'),
(15, 'General Mental Health Assessment', 'Comprehensive screening covering multiple aspects of mental health including mood, anxiety, sleep, and daily functioning.', 'General', 'Active', '2026-03-01', 'assessment_images/assessment_1770405958762.jpg'),
(16, 'Customized Assessment', 'Create your own assessment with personalized questions and categories. Ideal for tracking specific concerns or goals over time.', 'Custom', 'Inactive', '2026-03-01', 'assessment_images/assessment_1770405989351.jpg'),
(18, 'AZREAZR', 'AZERZER', 'Depression', 'Active', '2026-03-01', 'assessment_images/assessment_1770957026300.png');

-- --------------------------------------------------------

--
-- Table structure for table `assessmentresult`
--

CREATE TABLE `assessmentresult` (
  `result_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `assessment_id` int(11) DEFAULT NULL,
  `total_score` int(11) DEFAULT NULL,
  `risk_level` varchar(20) DEFAULT NULL,
  `interpretation` text DEFAULT NULL,
  `recommended_content` text DEFAULT NULL,
  `suggest_session` tinyint(1) DEFAULT NULL,
  `taken_at` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessmentresult`
--

INSERT INTO `assessmentresult` (`result_id`, `user_id`, `assessment_id`, `total_score`, `risk_level`, `interpretation`, `recommended_content`, `suggest_session`, `taken_at`) VALUES
(15, 5, 11, 10, 'Low', 'Your scores indicate minimal concerns in this area. Continue with healthy habits.', '• Continue with healthy habits\n• Mindfulness practices\n• Regular exercise routine', 0, '2026-02-06'),
(16, 7, 11, 6, 'Low', 'Your scores indicate minimal concerns in this area. Continue with healthy habits.', '• Continue with healthy habits\n• Mindfulness practices\n• Regular exercise routine', 0, '2026-02-06'),
(17, 1, 11, 10, 'Low', 'Your scores indicate minimal concerns in this area. Continue with healthy habits.', '• Continue with healthy habits\n• Mindfulness practices\n• Regular exercise routine', 0, '2026-02-06'),
(18, 3, 11, 7, 'Low', 'Your scores indicate minimal concerns in this area. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Continue with healthy habits\n• Mindfulness practices for maintenance\n• Regular exercise routine\n', 0, '2026-02-06'),
(19, 9, 11, 9, 'Moderate', 'Your scores suggest some areas that may need attention. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Stress management techniques\n• Self-help resources and books\n• Consider talking to a counselor\n• Sleep hygiene improvement strategies\n', 0, '2026-02-06'),
(20, 1, 14, 10, 'High', 'Your scores indicate significant concerns that should be addressed. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Professional consultation recommended\n• Support groups available\n• Crisis hotline: 1-800-273-8255\n• Consider stress management workshop\n', 1, '2026-02-06'),
(21, 7, 11, 6, 'Mild', 'Your scores suggest some areas that may need attention. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Stress management techniques\n• Self-help resources and books\n• Consider talking to a counselor\n• Sleep hygiene improvement strategies\n', 0, '2026-02-09'),
(22, 7, 11, 6, 'Mild', 'Your scores suggest some areas that may need attention. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Stress management techniques\n• Self-help resources and books\n• Consider talking to a counselor\n• Sleep hygiene improvement strategies\n', 0, '2026-02-09'),
(23, 7, 11, 6, 'Mild', 'Your scores suggest some areas that may need attention. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Stress management techniques\n• Self-help resources and books\n• Consider talking to a counselor\n• Sleep hygiene improvement strategies\n', 0, '2026-02-09'),
(24, 7, 11, 7, 'Moderate', 'Your scores suggest some areas that may need attention. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Stress management techniques\n• Self-help resources and books\n• Consider talking to a counselor\n• Sleep hygiene improvement strategies\n', 0, '2026-02-10'),
(25, 10, 11, 4, 'Mild', 'Your scores suggest some areas that may need attention. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Stress management techniques\n• Self-help resources and books\n• Consider talking to a counselor\n• Sleep hygiene improvement strategies\n', 0, '2026-02-12'),
(26, 16, 11, 3, 'Low', 'Your scores indicate minimal concerns in this area. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Continue with healthy habits\n• Mindfulness practices for maintenance\n• Regular exercise routine\n', 0, '2026-02-13');

-- --------------------------------------------------------

--
-- Table structure for table `content_node`
--

CREATE TABLE `content_node` (
  `node_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `pdf_path` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  `parent_node_id` int(11) DEFAULT NULL,
  `assigned_users` text DEFAULT '[]'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_node`
--

INSERT INTO `content_node` (`node_id`, `title`, `description`, `pdf_path`, `created_at`, `created_by`, `parent_node_id`, `assigned_users`) VALUES
(24, 'AERZAER', 'AKAAKA\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"', 'uploads\\content\\0_20260213_044633_Tutorial 1.pdf', '2026-02-13 04:46:33', 6, NULL, '[10]'),
(28, 'YOU ARE NOT YOUR PAST', 'HOW BRAIN PLASTISITY CAN SHAPE YOUR CARER', NULL, '2026-02-13 13:35:57', 9, NULL, '[10]'),
(29, 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFF', 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF', 'uploads\\content\\0_20260213_133739_Lab1- DDL.pdf', '2026-02-13 13:37:39', 9, NULL, '[10]'),
(30, 'TEST', 'TESEEEEEEEEEEEEEEEEEEEEEEEEEETETET', NULL, '2026-02-17 11:11:26', 6, NULL, '[7,10]');

-- --------------------------------------------------------

--
-- Table structure for table `content_path`
--

CREATE TABLE `content_path` (
  `path_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `node_id` int(11) NOT NULL,
  `accessed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_path`
--

INSERT INTO `content_path` (`path_id`, `user_id`, `node_id`, `accessed_at`) VALUES
(18, 10, 29, '2026-02-17 11:15:36');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date_time` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `max_participants` int(11) NOT NULL,
  `current_participants` int(11) DEFAULT 0,
  `event_type` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `image_url` varchar(500) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'UPCOMING',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `date_time`, `location`, `max_participants`, `current_participants`, `event_type`, `price`, `image_url`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Mindfulness Workshop', 'Learn mindfulness techniques for daily stress reduction and mental clarity. Covers breathing exercises, body scanning, and mindful meditation.', '2026-03-08 20:38:59', 'Online (Zoom)', 30, 5, 'WORKSHOP', 25.00, NULL, 'UPCOMING', 8, '2026-03-01 20:38:59', '2026-03-01 20:38:59'),
(2, 'Anxiety Support Group', 'Weekly support group for anxiety disorders. Share experiences and learn coping strategies in a safe environment.', '2026-03-03 20:38:59', 'Wellness Center Room 101', 15, 8, 'GROUP_THERAPY', 0.00, NULL, 'UPCOMING', 8, '2026-03-01 20:38:59', '2026-03-01 20:38:59'),
(3, 'Stress Management Seminar', 'Expert-led seminar on stress management including time management, relaxation techniques, and cognitive restructuring.', '2026-03-11 20:38:59', 'Community Hall A', 50, 12, 'SEMINAR', 15.00, NULL, 'UPCOMING', 8, '2026-03-01 20:38:59', '2026-03-01 20:38:59'),
(4, 'Art Therapy Social', 'Express yourself through art in a supportive group setting. No artistic experience required.', '2026-03-15 20:38:59', 'Art Studio B', 20, 3, 'SOCIAL', 10.00, NULL, 'UPCOMING', 8, '2026-03-01 20:38:59', '2026-03-01 20:38:59'),
(5, 'Meditation Basics', 'Introduction to meditation for complete beginners. Learn techniques you can practice at home every day.', '2026-03-06 20:38:59', 'Online (Zoom)', 40, 15, 'WORKSHOP', 0.00, NULL, 'UPCOMING', 8, '2026-03-01 20:38:59', '2026-03-01 20:38:59');

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `ticket_type` varchar(50) DEFAULT 'STANDARD',
  `number_of_tickets` int(11) DEFAULT 1,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'CONFIRMED',
  `payment_method` varchar(50) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_registrations`
--

INSERT INTO `event_registrations` (`id`, `event_id`, `user_id`, `user_name`, `email`, `phone`, `ticket_type`, `number_of_tickets`, `total_price`, `status`, `payment_method`, `special_requests`, `registration_date`) VALUES
(1, 1, 1, 'John Doe', 'john@email.com', '+1234567890', 'VIP', 1, 37.50, 'CONFIRMED', 'CREDIT_CARD', 'Need wheelchair access', '2026-03-01 20:38:59'),
(2, 1, 2, 'Jane Smith', 'jane@email.com', '+1234567891', 'STANDARD', 2, 50.00, 'CONFIRMED', 'PAYPAL', NULL, '2026-03-01 20:38:59'),
(3, 1, 3, 'Alice Brown', 'alice@email.com', '+1234567892', 'STANDARD', 1, 25.00, 'PENDING', 'CREDIT_CARD', NULL, '2026-03-01 20:38:59'),
(4, 2, 1, 'John Doe', 'john@email.com', '+1234567890', 'STANDARD', 1, 0.00, 'CONFIRMED', 'FREE', NULL, '2026-03-01 20:38:59'),
(5, 2, 5, 'Bob Wilson', 'bob@email.com', '+1234567893', 'STANDARD', 1, 0.00, 'CONFIRMED', 'FREE', 'First time attending', '2026-03-01 20:38:59'),
(6, 2, 4, 'Carol Davis', 'carol@email.com', '+1234567894', 'STANDARD', 1, 0.00, 'CANCELLED', 'FREE', NULL, '2026-03-01 20:38:59');

-- --------------------------------------------------------

--
-- Table structure for table `goal`
--

CREATE TABLE `goal` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `progress` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goal`
--

INSERT INTO `goal` (`id`, `user_id`, `description`, `deadline`, `progress`, `status`) VALUES
(49, 1, 'yesin', '2026-02-04', 49, 'En cours'),
(53, 2, 'hedi', '2026-01-08', 75, 'En attente');

-- --------------------------------------------------------

--
-- Table structure for table `mood`
--

CREATE TABLE `mood` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `feeling` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mood`
--

INSERT INTO `mood` (`id`, `user_id`, `feeling`, `note`, `date`) VALUES
(28, 1, ' 😐 Neutre 😊 Heureux', '12', '2026-02-12 19:27:44'),
(29, 1, ' 😔 Triste', 'aaaaaaaaa', '2026-02-12 19:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `pending_reminders`
--

CREATE TABLE `pending_reminders` (
  `reminder_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `weather_forecast` text DEFAULT NULL,
  `shown` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

CREATE TABLE `question` (
  `question_id` int(11) NOT NULL,
  `assessment_id` int(11) DEFAULT NULL,
  `text` text NOT NULL,
  `scale` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question`
--

INSERT INTO `question` (`question_id`, `assessment_id`, `text`, `scale`) VALUES
(7, 11, 'Over the past two weeks, how often have you felt down, depressed, or hopeless?', 'Never/Rarely/Sometimes/often/Always'),
(8, 11, 'How often have you had little interest or pleasure in doing things you usually enjoy?', 'Never/Rarely/Sometimes/often/Always'),
(10, 11, 'Have you experienced changes in your sleep patterns or appetite recently?', 'Never/Rarely/Sometimes/often/Always'),
(11, 12, 'How often do you feel nervous, anxious, or on edge?', 'Never/Rarely/Sometimes/often/Always'),
(12, 12, 'Do you find it difficult to stop or control worrying?', 'Never/Rarely/Sometimes/often/Always'),
(13, 12, 'On rate (1-5) how much do your worries interfere with your daily activities?', '1-5'),
(14, 13, 'Overall, how satisfied are you with your life nowadays?', 'Never/Rarely/Sometimes/often/Always'),
(15, 13, 'How often do you feel positive and optimistic about your future?', 'Never/Rarely/Sometimes/often/Always'),
(16, 13, 'To what extent do you feel the things you do in your life are worthwhile?', 'Never/Rarely/Sometimes/often/Always'),
(17, 14, 'How often do you feel stressed or overwhelmed by your daily responsibilities?', 'Never/Rarely/Sometimes/often/Always'),
(18, 14, 'On rate of 1 to 5 how well are you able to cope with stressful situations?', '1-5'),
(19, 14, 'Do you experience physical symptoms (headaches, fatigue, etc.) when stressed?', 'Never/Rarely/Sometimes/often/Always'),
(20, 15, 'How would you rate your overall mental health over the past month?', '1-5'),
(21, 15, 'How often do your emotional issues interfere with your social life or relationships?', 'Never/Rarely/Sometimes/often/Always'),
(22, 15, 'Have you felt the need for professional help or support for your mental health?', 'No/Yes');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `session_type` varchar(100) NOT NULL,
  `status` varchar(20) DEFAULT 'Scheduled',
  `reserved_by` int(11) DEFAULT NULL,
  `reserved_at` datetime DEFAULT NULL,
  `category` varchar(100) DEFAULT 'General',
  `popularity` int(11) DEFAULT 0,
  `average_rating` double DEFAULT 0,
  `meeting_link` varchar(500) DEFAULT NULL,
  `meeting_started` tinyint(1) DEFAULT 0,
  `meeting_ended` tinyint(1) DEFAULT 0,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `patient_confirmed` tinyint(1) DEFAULT 0,
  `confirmed_at` datetime DEFAULT NULL,
  `max_participants` int(11) DEFAULT 20,
  `current_participants` int(11) DEFAULT 0,
  `price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`session_id`, `title`, `session_date`, `start_time`, `end_time`, `location`, `session_type`, `status`, `reserved_by`, `reserved_at`, `category`, `popularity`, `average_rating`, `meeting_link`, `meeting_started`, `meeting_ended`, `reminder_sent`, `patient_confirmed`, `confirmed_at`, `max_participants`, `current_participants`, `price`) VALUES
(3, 'Completed Group Session', '2026-02-10', '10:00:00', '11:30:00', 'Room 205', 'Group', 'active', NULL, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 25.00),
(4, 'Past Individual Session', '2026-02-06', '15:00:00', '16:00:00', 'Online', 'Individual', 'active', NULL, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 1, 0, 50.00),
(5, 'Morning Therapy', '2026-02-14', '09:00:00', '10:00:00', 'Room 101', 'Individual', 'active', NULL, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 1, 0, 45.00),
(6, 'Group Session', '2026-02-15', '14:00:00', '15:30:00', 'Room 205', 'Group', 'active', NULL, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 30.00),
(7, 'Evening Relaxation', '2026-02-16', '18:00:00', '19:00:00', 'Online', 'Online', 'active', NULL, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 30, 0, 15.00),
(8, 'Family Therapy', '2026-02-17', '10:00:00', '11:30:00', 'Room 102', 'Family', 'active', NULL, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 8, 0, 60.00),
(9, 'Couple Counseling', '2026-02-18', '15:00:00', '16:00:00', 'Office 3', 'Couple', 'active', NULL, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 2, 0, 55.00),
(10, 'Mindfulness Session', '2026-02-19', '11:00:00', '12:00:00', 'Garden Room', 'Individual', 'active', 7, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 1, 0, 40.00),
(11, 'Past Group Session', '2026-02-08', '10:00:00', '11:30:00', 'Room 205', 'Group', 'active', 7, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 25.00),
(12, 'therapy', '2026-02-20', '09:00:00', '10:00:00', 'esprit', 'Group', 'scheduled', NULL, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 15, 0, 0.00),
(13, 'Morning Meditation', '2026-03-04', '09:00:00', '10:00:00', 'Online Room', 'Online', 'active', NULL, NULL, 'General', 15, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 0.00),
(14, 'Stress Relief', '2026-03-05', '14:00:00', '15:30:00', 'Room 205', 'Group', 'active', 20, '2026-03-02 01:22:12', 'General', 24, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 0.00),
(15, 'Individual Therapy', '2026-03-06', '11:00:00', '12:00:00', 'Room 101', 'Individual', 'active', 20, '2026-03-02 01:22:17', 'General', 9, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 0.00),
(16, 'Anxiety Support', '2026-03-07', '15:00:00', '16:30:00', 'Online Room', 'Online', 'active', NULL, NULL, 'General', 31, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 0.00),
(17, 'Past Therapy Session', '2026-02-25', '10:00:00', '11:00:00', 'Room 101', 'Individual', 'completed', 7, '2026-03-02 01:24:07', 'General', 35, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 0.00),
(18, 'Past Group Therapy', '2026-02-23', '14:00:00', '15:30:00', 'Room 205', 'Group', 'completed', 7, '2026-03-02 01:24:07', 'Anxiety', 22, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 0.00),
(19, 'Past Online Session', '2026-02-20', '09:00:00', '10:00:00', 'Online Room', 'Online', 'completed', 7, '2026-03-02 01:24:07', 'Wellness', 14, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 0.00),
(20, 'Past Couple Counseling', '2026-02-18', '16:00:00', '17:00:00', 'Room 102', 'Couple', 'completed', 7, '2026-03-02 01:24:07', 'Relationship', 8, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 0.00),
(21, 'Past Therapy Session', '2026-02-25', '10:00:00', '11:00:00', 'Room 101', 'Individual', 'completed', 20, '2026-03-02 01:27:27', 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 0.00),
(22, 'Past Group Therapy', '2026-02-23', '14:00:00', '15:30:00', 'Room 205', 'Group', 'completed', 20, '2026-03-02 01:27:27', 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 0.00),
(23, 'Past Online Session', '2026-02-20', '09:00:00', '10:00:00', 'Online Room', 'Online', 'completed', 20, '2026-03-02 01:27:27', 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 0.00),
(24, 'Past Couple Counseling', '2026-02-18', '16:00:00', '17:00:00', 'Room 102', 'Couple', 'completed', 20, '2026-03-02 01:27:27', 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `session_review`
--

CREATE TABLE `session_review` (
  `review_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `review_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `session_review`
--

INSERT INTO `session_review` (`review_id`, `session_id`, `patient_id`, `rating`, `comment`, `review_date`) VALUES
(1, 8, 7, 2, 'i liked the vibe', '2026-03-01 20:38:59'),
(2, 7, 7, 2, 'i waited for too long and the doctor seemed off', '2026-03-01 20:38:59'),
(4, 21, 20, 5, NULL, '2026-03-02 01:28:47'),
(5, 23, 20, 5, NULL, '2026-03-02 01:40:41');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `dateofbirth` varchar(100) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(1000) NOT NULL,
  `face_data` text DEFAULT NULL,
  `face_enabled` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `firstname`, `lastname`, `phone`, `dateofbirth`, `gender`, `type`, `email`, `password`, `face_data`, `face_enabled`, `created_at`) VALUES
(1, 'test', 'tet', '26667352', '2003-12-23', NULL, 'Admin', 'test@gmail.com', '9af15b336e6a9619928537df30b2e6a2376569fcf9d7e773eccede65606529a0', NULL, 0, '2026-03-01 20:38:58'),
(2, 'eya', 'mhadhbi', '99819349', '2003-06-23', NULL, 'Patient', 'eyamhadhbi@gmail.com', 'ee1ecd7240acd6f395028c21a8b6b82f00ef9841a3a0898893944ff95dcf0122', NULL, 0, '2026-03-01 20:38:58'),
(3, 'ahmed', 'rhouma', '96022691', '2000-10-16', NULL, 'Patient', 'ahmedrhouma24@gmail.com', 'fd138c8a2c2cd612e0564cd03db7eff474b49a3f0154b7e6c8e419d101db11fb', NULL, 0, '2026-03-01 20:38:58'),
(4, 'ela', 'dridi', '95126352', '2003-05-26', NULL, 'Patient', 'elladridi96@gmail.com', 'db3c234b8188d8df179b5ca969976d1e2f925bcf092f6e7a1f97e6750aa52a1a', NULL, 0, '2026-03-01 20:38:58'),
(5, 'skander', 'chamkhi', '99819349', '2000-12-23', NULL, 'Patient', 'skanderchamkhi@gmail.com', '7e071fd9b023ed8f18458a73613a0834f6220bd5cc50357ba3493c6040a9ea8c', NULL, 0, '2026-03-01 20:38:58'),
(6, 'test', 'tt', '24571231', '2000-06-23', NULL, 'psychologist', 'testtt@gmail.com', 'f348d5628621f3d8f59c8cabda0f8eb0aa7e0514a90be7571020b1336f26c113', NULL, 0, '2026-03-01 20:38:58'),
(7, 'sytej', 'bouhlila', '27033553', '1999-06-23', NULL, 'Patient', 'sytejbouhlila@gmail.com', '9af15b336e6a9619928537df30b2e6a2376569fcf9d7e773eccede65606529a0', NULL, 1, '2026-03-01 20:38:58'),
(8, 'aaa', 'bb', '26485352', '2003-12-20', NULL, 'Admin', 'admin@gmail.com', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', NULL, 0, '2026-03-01 20:38:58'),
(9, 'Arij', 'Bouhlila', '26667352', '2003-12-23', NULL, 'Psychologist', 'arijbouhlila9@gmail.com', '49efec89531d74840168da3cb78afc1fe2029351f3c86e891ec1312fc1342f63', NULL, 0, '2026-03-01 20:38:58'),
(10, 'ella', 'dridi', '55595801', '26-05-2003', NULL, 'Patient', 'ella.dridi@esprit.tn', '96cae35ce8a9b0244178bf28e4966c2ce1b8385723a96a6b838858cdd6ca0a1e', NULL, 0, '2026-03-01 20:38:58'),
(11, 'arij', 'bouhlila', '155878787', '28-12-2003', NULL, 'Admin', 'arij@esprit.tn', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', NULL, 0, '2026-03-01 20:38:58'),
(12, 'wee', 'wee', '545484854', '26-04-2003', NULL, 'Psychologist', 'we@esprit.tn', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', NULL, 0, '2026-03-01 20:38:58'),
(13, 'ahmed', 'zekri', '20666202', '23-01-2004', NULL, 'Patient', 'ahmedzekri20666202@gmail.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', NULL, 0, '2026-03-01 20:38:58'),
(14, 'zikou', '.', '20666202', '23-01-2004', NULL, 'Psychologist', 'ahmedzekri@gmail.com', '2ac9a6746aca543af8dff39894cfe8173afba21eb01c6fae33d52947222855ef', NULL, 0, '2026-03-01 20:38:58'),
(15, 'omar', 'omar', '232222222', '2003-11-11', NULL, 'Admin', 'TEST@TEST.COM', '984751f9767837e64aaa45b53bc1cfe950a7e494971600edd60c8cf0edb22796', NULL, 0, '2026-03-01 20:38:58'),
(16, 'AZER', 'AZER', '343242341', '234234234', NULL, 'Patient', 'AZER@AZER.COM', '98dc384177d6e033313c5f85fe1320e2e61e28d2ff555cef0bbee7ef65a82d05', NULL, 0, '2026-03-01 20:38:58'),
(17, 'AZER', 'AZER', '23412344', '2003-11-11', NULL, 'Patient', 'TEST@TEST.TEST', '984751f9767837e64aaa45b53bc1cfe950a7e494971600edd60c8cf0edb22796', NULL, 0, '2026-03-01 20:38:58'),
(18, 'AAA', 'AAA', '93222222', '2003-11-11', NULL, 'Psychologist', 'PSI@PSI.PSI', 'cb6c5ac68a624a693954824deee9b55280446889049daa16cfc5d7d6653f1da1', NULL, 0, '2026-03-01 20:38:58'),
(19, 'OMAR', 'KAMMOUN', '93322211', '2003-11-11', NULL, 'Patient', 'AAAA@AAAA.AAA', 'cb1ad2119d8fafb69566510ee712661f9f14b83385006ef92aec47f523a38358', NULL, 0, '2026-03-01 20:38:58'),
(20, 'maram', 'mmm', '50106936', '2007-02-01', NULL, 'Patient', 'maram@gmail.com', '483029d526219f816e8e8f6a9de07b422633dba180ffc26faac22862a017519f', NULL, 0, '2026-03-02 00:15:38'),
(22, 'Arij', 'bouhlila', '+21626667352', '2003-12-23', NULL, 'Psychologist', 'arijbouhlila@gmail.com', 'f32c03f58877dc841a65654a8285b933f5a85f93bd7d2e222971f0cf6b2d02b8', NULL, 0, '2026-04-05 11:46:40'),
(23, 'aa', 'bb', '25896325', '2003-12-23', 'female', 'Patient', 'arijbouh@gmail.com', '6357abacb770c6e429a157619a1a075a2ccbe1fc51d7b996d709e07a6f69b6ec', NULL, 0, '2026-04-05 11:46:40'),
(24, 'arij', 'bh', '26667352', '2003-12-23', NULL, 'Admin', 'arij.bouhlila@esprit.tn', '$2y$13$fmVrzJ/pzjBYQB3IK5mCHu.qOoEsZSqnBiOqShIsgyKn3ZHoTETGu', NULL, 0, '2026-04-05 11:46:40'),
(26, 'syt', 'bh', '26667458', '1999-06-23', NULL, 'Psychologist', 'sytejbh@gmail.com', '$2y$13$6cFM8SZOASGK3ehJ1ucSXuBaVVY9PwA1S/.vo3z2t8Fs38Aq4AOcu', NULL, 0, '2026-04-05 11:46:40'),
(28, 'iyed', 'bouhlila', '29344311', '2004-12-25', 'male', 'Admin', 'iyed@gmail.com', '$2y$13$2ZJZG9UhkldPo.I21v6BheJLGdnfxPCwQNk3Uf3dK9BHz5f3Lm4m.', NULL, 0, '2026-04-05 11:46:40'),
(29, 'toto', 'mbarki', '24578961', '1899-12-24', 'male', 'Patient', 'toto@gmail.com', '$2y$13$zo1oPhTENac/yGCj3OMePuTjPfSl3deGG0SGjmGHgN3T5s8Q.Yf7e', NULL, 0, '2026-04-05 11:46:40'),
(30, 'ahle', 'nahla', '99814785', '1980-04-12', 'female', 'Psychologist', 'ahla@gmail.com', '$2y$13$pZZ1v6dRpY170S3DYEv.LO80Umhx5oOA7DxAfKTmftvnO/5DznciK', NULL, 0, '2026-04-05 11:46:40'),
(31, 'nadine', 'bd', '96458796', '2003-11-25', 'female', 'Psychologist', 'nadinebs@gmail.com', '$2y$13$.pfc3QvEfvQ7pUl2zdWHBOYKjowSPdmNojOsSy1LigIrJz3248mtW', NULL, 0, '2026-04-05 11:46:40'),
(32, 'omar', 'kamoun', '41587963', '2002-10-16', 'male', 'Admin', 'omar@gmail.com', '$2y$13$pnZmBGFPrNfShTX0YhcePuSmYyDNW5aOXDgNg3xuG0WWg89jx1jy6', NULL, 0, '2026-04-05 11:46:40');

-- --------------------------------------------------------

--
-- Table structure for table `user_old`
--

CREATE TABLE `user_old` (
  `id` int(11) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `dateofbirth` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_old`
--

INSERT INTO `user_old` (`id`, `firstname`, `lastname`, `phone`, `dateofbirth`, `type`, `email`, `password`) VALUES
(7, 'ella', 'dridi', '55595801', '26-05-2003', 'Patient', 'ella.dridi@esprit.tn', '96cae35ce8a9b0244178bf28e4966c2ce1b8385723a96a6b838858cdd6ca0a1e'),
(8, 'arij', 'bouhlila', '155878787', '28-12-2003', 'Admin', 'arij@esprit.tn', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3'),
(9, 'wee', 'wee', '545484854', '26-04-2003', 'Psychologist', 'we@esprit.tn', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3'),
(10, 'ahmed', 'zekri', '20666202', '23-01-2004', 'Patient', 'ahmedzekri20666202@gmail.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3'),
(11, 'zikou', '.', '20666202', '23-01-2004', 'Psychologist', 'ahmedzekri@gmail.com', '2ac9a6746aca543af8dff39894cfe8173afba21eb01c6fae33d52947222855ef'),
(12, 'omar', 'omar', '232222222', '2003-11-11', 'Admin', 'TEST@TEST.COM', '984751f9767837e64aaa45b53bc1cfe950a7e494971600edd60c8cf0edb22796'),
(13, 'AZER', 'AZER', '343242341', '234234234', 'Patient', 'AZER@AZER.COM', '98dc384177d6e033313c5f85fe1320e2e61e28d2ff555cef0bbee7ef65a82d05'),
(14, 'AZER', 'AZER', '23412344', '2003-11-11', 'Patient', 'TEST@TEST.TEST', '984751f9767837e64aaa45b53bc1cfe950a7e494971600edd60c8cf0edb22796'),
(15, 'AAA', 'AAA', '93222222', '2003-11-11', 'Psychologist', 'PSI@PSI.PSI', 'cb6c5ac68a624a693954824deee9b55280446889049daa16cfc5d7d6653f1da1'),
(16, 'OMAR', 'KAMMOUN', '93322211', '2003-11-11', 'Patient', 'AAAA@AAAA.AAA', 'cb1ad2119d8fafb69566510ee712661f9f14b83385006ef92aec47f523a38358');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assessment`
--
ALTER TABLE `assessment`
  ADD PRIMARY KEY (`assessment_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `assessmentresult`
--
ALTER TABLE `assessmentresult`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_assessment` (`assessment_id`);

--
-- Indexes for table `content_node`
--
ALTER TABLE `content_node`
  ADD PRIMARY KEY (`node_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_parent` (`parent_node_id`);

--
-- Indexes for table `content_path`
--
ALTER TABLE `content_path`
  ADD PRIMARY KEY (`path_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_node` (`node_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_date_time` (`date_time`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email_event` (`email`,`event_id`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_registration_date` (`registration_date`);

--
-- Indexes for table `goal`
--
ALTER TABLE `goal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `mood`
--
ALTER TABLE `mood`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `pending_reminders`
--
ALTER TABLE `pending_reminders`
  ADD PRIMARY KEY (`reminder_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_assessment` (`assessment_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `reserved_by` (`reserved_by`),
  ADD KEY `idx_session_date` (`session_date`),
  ADD KEY `idx_session_type` (`session_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `session_review`
--
ALTER TABLE `session_review`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_patient` (`patient_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `user_old`
--
ALTER TABLE `user_old`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assessment`
--
ALTER TABLE `assessment`
  MODIFY `assessment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `assessmentresult`
--
ALTER TABLE `assessmentresult`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `content_node`
--
ALTER TABLE `content_node`
  MODIFY `node_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `content_path`
--
ALTER TABLE `content_path`
  MODIFY `path_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `goal`
--
ALTER TABLE `goal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `mood`
--
ALTER TABLE `mood`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `pending_reminders`
--
ALTER TABLE `pending_reminders`
  MODIFY `reminder_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `session_review`
--
ALTER TABLE `session_review`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `user_old`
--
ALTER TABLE `user_old`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assessmentresult`
--
ALTER TABLE `assessmentresult`
  ADD CONSTRAINT `assessmentresult_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessment` (`assessment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessmentresult_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `content_node`
--
ALTER TABLE `content_node`
  ADD CONSTRAINT `content_node_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_node_ibfk_2` FOREIGN KEY (`parent_node_id`) REFERENCES `content_node` (`node_id`) ON DELETE SET NULL;

--
-- Constraints for table `content_path`
--
ALTER TABLE `content_path`
  ADD CONSTRAINT `content_path_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_path_ibfk_2` FOREIGN KEY (`node_id`) REFERENCES `content_node` (`node_id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `goal`
--
ALTER TABLE `goal`
  ADD CONSTRAINT `goal_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mood`
--
ALTER TABLE `mood`
  ADD CONSTRAINT `mood_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pending_reminders`
--
ALTER TABLE `pending_reminders`
  ADD CONSTRAINT `fk_pending_reminders_patient` FOREIGN KEY (`patient_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pending_reminders_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE CASCADE;

--
-- Constraints for table `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `question_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessment` (`assessment_id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`reserved_by`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `session_review`
--
ALTER TABLE `session_review`
  ADD CONSTRAINT `session_review_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_review_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
