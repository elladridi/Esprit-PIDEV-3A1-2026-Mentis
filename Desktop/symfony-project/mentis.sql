-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 19 avr. 2026 à 18:17
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `mentis`
--

-- --------------------------------------------------------

--
-- Structure de la table `assessment`
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
-- Déchargement des données de la table `assessment`
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
-- Structure de la table `assessmentresult`
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
-- Déchargement des données de la table `assessmentresult`
--

INSERT INTO `assessmentresult` (`result_id`, `user_id`, `assessment_id`, `total_score`, `risk_level`, `interpretation`, `recommended_content`, `suggest_session`, `taken_at`) VALUES
(15, 5, 11, 10, 'Low', 'Your scores indicate minimal concerns in this area. Continue with healthy habits.', '• Continue with healthy habits\n• Mindfulness practices\n• Regular exercise routine', 0, '2026-02-06'),
(16, 7, 11, 6, 'Low', 'Your scores indicate minimal concerns in this area. Continue with healthy habits.', '• Continue with healthy habits\n• Mindfulness practices\n• Regular exercise routine', 0, '2026-02-06'),
(17, 1, 11, 10, 'Low', 'Your scores indicate minimal concerns in this area. Continue with healthy habits.', '• Continue with healthy habits\n• Mindfulness practices\n• Regular exercise routine', 0, '2026-02-06'),
(18, 3, 11, 7, 'Low', 'Your scores indicate minimal concerns in this area. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Continue with healthy habits\n• Mindfulness practices for maintenance\n• Regular exercise routine\n', 0, '2026-02-06'),
(20, 1, 14, 10, 'High', 'Your scores indicate significant concerns that should be addressed. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Professional consultation recommended\n• Support groups available\n• Crisis hotline: 1-800-273-8255\n• Consider stress management workshop\n', 1, '2026-02-06'),
(21, 7, 11, 6, 'Mild', 'Your scores suggest some areas that may need attention. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Stress management techniques\n• Self-help resources and books\n• Consider talking to a counselor\n• Sleep hygiene improvement strategies\n', 0, '2026-02-09'),
(22, 7, 11, 6, 'Mild', 'Your scores suggest some areas that may need attention. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Stress management techniques\n• Self-help resources and books\n• Consider talking to a counselor\n• Sleep hygiene improvement strategies\n', 0, '2026-02-09'),
(23, 7, 11, 6, 'Mild', 'Your scores suggest some areas that may need attention. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Stress management techniques\n• Self-help resources and books\n• Consider talking to a counselor\n• Sleep hygiene improvement strategies\n', 0, '2026-02-09'),
(24, 7, 11, 7, 'Moderate', 'Your scores suggest some areas that may need attention. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Stress management techniques\n• Self-help resources and books\n• Consider talking to a counselor\n• Sleep hygiene improvement strategies\n', 0, '2026-02-10'),
(25, 10, 11, 4, 'Mild', 'Your scores suggest some areas that may need attention. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Stress management techniques\n• Self-help resources and books\n• Consider talking to a counselor\n• Sleep hygiene improvement strategies\n', 0, '2026-02-12'),
(26, 16, 11, 3, 'Low', 'Your scores indicate minimal concerns in this area. The AI analysis provides personalized insights below.', 'Based on your assessment results:\n• Continue with healthy habits\n• Mindfulness practices for maintenance\n• Regular exercise routine\n', 0, '2026-02-13'),
(27, 29, 11, 9, 'Moderate', 'Your scores suggest some areas that may need attention. Please review your AI analysis for personalized insights.', 'Based on your assessment results:\n- Stress management techniques\n- Self-help resources and books\n- Consider talking to a counselor\n', 0, '2026-04-17');

-- --------------------------------------------------------

--
-- Structure de la table `content_node`
--

CREATE TABLE `content_node` (
  `node_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `pdf_path` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `parent_node_id` int(11) DEFAULT NULL,
  `assigned_users` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `content_node`
--

INSERT INTO `content_node` (`node_id`, `title`, `description`, `pdf_path`, `created_at`, `created_by`, `parent_node_id`, `assigned_users`) VALUES
(24, 'AERZAER', 'AKAAKA\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"\"', 'uploads\\content\\0_20260213_044633_Tutorial 1.pdf', '2026-02-13 04:46:33', 6, NULL, '[10]'),
(30, 'TEST', 'TESEEEEEEEEEEEEEEEEEEEEEEEEEETETET', NULL, '2026-02-17 11:11:26', 6, NULL, '[7,10]'),
(34, 'test title', 'test title desc', 'uploads\\content\\0_20260303_090002_lab_DQL.pdf', '2026-03-03 09:00:02', 1, NULL, '[2,19,23]'),
(35, 'test test', 'test test test', 'bubj', '2026-04-04 16:21:31', 24, NULL, '[29]'),
(36, 'fin', 'test arij', NULL, '2026-04-04 16:59:19', 26, NULL, '[29]');

-- --------------------------------------------------------

--
-- Structure de la table `content_path`
--

CREATE TABLE `content_path` (
  `path_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `node_id` int(11) NOT NULL,
  `accessed_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `content_path`
--

INSERT INTO `content_path` (`path_id`, `user_id`, `node_id`, `accessed_at`) VALUES
(20, 2, 34, '2026-03-03 09:02:20'),
(21, 24, 34, '2026-04-04 15:40:34'),
(23, 24, 35, '2026-04-04 16:21:37'),
(24, 29, 35, '2026-04-04 19:03:21'),
(26, 26, 36, '2026-04-04 20:02:57');

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `events`
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
-- Déchargement des données de la table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `date_time`, `location`, `max_participants`, `current_participants`, `event_type`, `price`, `image_url`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Mindfulness Workshop', 'Learn mindfulness techniques for daily stress reduction and mental clarity. Covers breathing exercises, body scanning, and mindful meditation.', '2026-03-08 20:38:59', 'Online (Zoom)', 30, 5, 'WORKSHOP', 25.00, NULL, 'UPCOMING', 8, '2026-03-01 20:38:59', '2026-03-01 20:38:59'),
(2, 'Anxiety Support Group', 'Weekly support group for anxiety disorders. Share experiences and learn coping strategies in a safe environment.', '2026-03-03 20:38:59', 'Wellness Center Room 101', 15, 8, 'GROUP_THERAPY', 0.00, NULL, 'UPCOMING', 8, '2026-03-01 20:38:59', '2026-03-01 20:38:59'),
(3, 'Stress Management Seminar', 'Expert-led seminar on stress management including time management, relaxation techniques, and cognitive restructuring.', '2026-03-11 20:38:59', 'Community Hall A', 50, 12, 'SEMINAR', 15.00, NULL, 'UPCOMING', 8, '2026-03-01 20:38:59', '2026-03-01 20:38:59'),
(4, 'Art Therapy Social', 'Express yourself through art in a supportive group setting. No artistic experience required.', '2026-03-15 20:38:59', 'Art Studio B', 20, 3, 'SOCIAL', 10.00, NULL, 'UPCOMING', 8, '2026-03-01 20:38:59', '2026-03-01 20:38:59'),
(5, 'Meditation Basics', 'Introduction to meditation for complete beginners. Learn techniques you can practice at home every day.', '2026-03-06 20:38:59', 'Online (Zoom)', 40, 15, 'WORKSHOP', 0.00, NULL, 'UPCOMING', 8, '2026-03-01 20:38:59', '2026-03-01 20:38:59');

-- --------------------------------------------------------

--
-- Structure de la table `event_registrations`
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
-- Déchargement des données de la table `event_registrations`
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
-- Structure de la table `goal`
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
-- Déchargement des données de la table `goal`
--

INSERT INTO `goal` (`id`, `user_id`, `description`, `deadline`, `progress`, `status`) VALUES
(49, 1, 'yesin', '2026-02-04', 49, 'En cours'),
(53, 2, 'hedi', '2026-01-08', 75, 'En attente');

-- --------------------------------------------------------

--
-- Structure de la table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` datetime NOT NULL,
  `was_successful` tinyint(1) NOT NULL DEFAULT 0,
  `user_agent` text DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `ip_address`, `attempted_at`, `was_successful`, `user_agent`, `country`, `city`) VALUES
(1, 'omar@gmail.com', '::1', '2026-04-18 01:30:59', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(2, 'omar@gmail.com', '::1', '2026-04-18 01:31:02', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(3, 'omar@gmail.com', '::1', '2026-04-18 01:31:05', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(4, 'jalel@gmail.com', '::1', '2026-04-18 02:34:26', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(5, 'toto@gmail.com', '::1', '2026-04-18 02:34:56', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(6, 'toto@gmail.com', '::1', '2026-04-18 02:34:58', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(7, 'omar@gmail.com', '::1', '2026-04-18 02:35:23', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(8, 'omar@gmail.com', '::1', '2026-04-18 02:35:25', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(9, 'omar@gmail.com', '::1', '2026-04-18 02:35:29', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(10, 'omar@gmail.com', '::1', '2026-04-18 02:49:51', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(11, 'jalel@gmail.com', '::1', '2026-04-18 03:06:36', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(12, 'jalel@gmail.com', '::1', '2026-04-18 03:06:39', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(13, 'jalel@gmail.com', '::1', '2026-04-18 03:07:09', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(14, 'jalel@gmail.com', '::1', '2026-04-18 03:07:11', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(15, 'omar@gmail.com', '::1', '2026-04-18 03:07:29', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(16, 'omar@gmail.com', '::1', '2026-04-18 03:07:32', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(17, 'omar@gmail.com', '::1', '2026-04-18 03:07:37', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(18, 'arijbouhlila9@gmail.com', '::1', '2026-04-18 03:45:51', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(19, 'arijbouhlila9@gmail.com', '::1', '2026-04-18 15:10:40', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(20, 'toto@gmail.com', '::1', '2026-04-18 15:12:36', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(21, 'omar@gmail.com', '::1', '2026-04-18 15:14:11', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(22, 'jalel@gmail.com', '::1', '2026-04-18 15:37:25', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(23, 'toto@gmail.com', '::1', '2026-04-18 15:37:51', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(24, 'omar@gmail.com', '::1', '2026-04-18 15:38:20', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(25, 'omar@gmail.com', '::1', '2026-04-18 15:42:27', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(26, 'doudouu@gmail.com', '::1', '2026-04-18 15:44:22', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(27, 'toto@gmail.com', '::1', '2026-04-18 15:44:47', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(28, 'jalel@gmail.com', '::1', '2026-04-18 15:53:47', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(29, 'jalel@gmail.com', '::1', '2026-04-18 15:56:36', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(30, 'omar@gmail.com', '::1', '2026-04-18 16:12:26', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(31, 'jalel@gmail.com', '::1', '2026-04-18 16:14:26', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(32, 'ahmedrhouma24@gmail.com', '::1', '2026-04-18 16:17:26', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(33, 'ahmedrhouma24@gmail.com', '::1', '2026-04-18 16:17:32', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(34, 'omar@gmail.com', '::1', '2026-04-18 16:17:43', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(35, 'arijbouhlila9@gmail.com', '::1', '2026-04-18 16:22:13', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(36, 'omar@gmail.com', '::1', '2026-04-18 16:22:49', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(37, 'arijbouhlila9@gmail.com', '::1', '2026-04-18 16:31:26', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(38, 'jalel@gmail.co+', '::1', '2026-04-19 15:02:04', 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(39, 'arijbouhlila9@gmail.com', '::1', '2026-04-19 16:05:55', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(40, 'arijbouhlila9@gmail.com', '::1', '2026-04-19 16:24:30', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(41, 'jall@gmail.com', '::1', '2026-04-19 16:54:06', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(42, 'arijbouhlila9@gmail.com', '::1', '2026-04-19 17:19:51', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL),
(43, 'jall@gmail.com', '::1', '2026-04-19 17:20:11', 1, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `mood`
--

CREATE TABLE `mood` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `feeling` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `mood`
--

INSERT INTO `mood` (`id`, `user_id`, `feeling`, `note`, `date`) VALUES
(28, 1, ' 😐 Neutre 😊 Heureux', '12', '2026-02-12 19:27:44'),
(29, 1, ' 😔 Triste', 'aaaaaaaaa', '2026-02-12 19:27:36');

-- --------------------------------------------------------

--
-- Structure de la table `pending_reminders`
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
-- Structure de la table `question`
--

CREATE TABLE `question` (
  `question_id` int(11) NOT NULL,
  `assessment_id` int(11) DEFAULT NULL,
  `text` text NOT NULL,
  `scale` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `question`
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
-- Structure de la table `sessions`
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
-- Déchargement des données de la table `sessions`
--

INSERT INTO `sessions` (`session_id`, `title`, `session_date`, `start_time`, `end_time`, `location`, `session_type`, `status`, `reserved_by`, `reserved_at`, `category`, `popularity`, `average_rating`, `meeting_link`, `meeting_started`, `meeting_ended`, `reminder_sent`, `patient_confirmed`, `confirmed_at`, `max_participants`, `current_participants`, `price`) VALUES
(3, 'Completed Group Session', '2026-02-10', '10:00:00', '11:30:00', 'Room 205', 'Group', 'active', NULL, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 25.00),
(4, 'Past Individual Session', '2026-02-06', '15:00:00', '16:00:00', 'Online', 'Individual', 'active', NULL, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 1, 0, 50.00),
(5, 'Morning Therapy', '2026-02-14', '09:00:00', '10:00:00', 'Room 101', 'Individual', 'active', NULL, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 1, 0, 45.00),
(6, 'Group Session', '2026-02-15', '14:00:00', '15:30:00', 'Room 205', 'Group', 'active', NULL, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 20, 0, 30.00),
(7, 'Evening Relaxation', '2026-02-16', '18:00:00', '19:00:00', 'Online', 'Online', 'active', NULL, NULL, 'General', 0, 0, NULL, 0, 0, 0, 0, NULL, 30, 0, 15.00),
(8, 'Family Therapy', '2026-02-17', '10:00:00', '11:30:00', 'Room 102', 'Family', 'active', 29, '2026-04-18 15:46:20', 'General', 1, 0, NULL, 0, 0, 0, 0, NULL, 8, 1, 60.00),
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
-- Structure de la table `session_review`
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
-- Déchargement des données de la table `session_review`
--

INSERT INTO `session_review` (`review_id`, `session_id`, `patient_id`, `rating`, `comment`, `review_date`) VALUES
(1, 8, 7, 2, 'i liked the vibe', '2026-03-01 20:38:59'),
(2, 7, 7, 2, 'i waited for too long and the doctor seemed off', '2026-03-01 20:38:59'),
(4, 21, 20, 5, NULL, '2026-03-02 01:28:47'),
(5, 23, 20, 5, NULL, '2026-03-02 01:40:41');

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `dateofbirth` date NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(1000) NOT NULL,
  `face_data` text DEFAULT NULL,
  `face_enabled` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `face_registered_at` datetime DEFAULT NULL,
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `banned_at` datetime DEFAULT NULL,
  `banned_until` datetime DEFAULT NULL,
  `ban_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `firstname`, `lastname`, `phone`, `dateofbirth`, `gender`, `type`, `email`, `password`, `face_data`, `face_enabled`, `created_at`, `face_registered_at`, `is_banned`, `banned_at`, `banned_until`, `ban_reason`) VALUES
(1, 'test', 'tet', '26667352', '2003-12-23', NULL, 'Admin', 'test@gmail.com', '9af15b336e6a9619928537df30b2e6a2376569fcf9d7e773eccede65606529a0', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(2, 'John', 'Doe', '99819349', '2003-06-23', NULL, 'Patient', 'eyamhadhbi@gmail.com', 'ee1ecd7240acd6f395028c21a8b6b82f00ef9841a3a0898893944ff95dcf0122', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(3, 'ahmed', 'rhouma', '96022691', '2000-10-16', NULL, 'Patient', 'ahmedrhouma24@gmail.com', 'fd138c8a2c2cd612e0564cd03db7eff474b49a3f0154b7e6c8e419d101db11fb', NULL, 0, '2026-03-01 20:38:58', NULL, 1, '2026-04-18 16:24:10', '2026-04-25 16:24:10', 'Suspicious activity detected - Multiple failed login attempts'),
(4, 'ela', 'dridi', '95126352', '2003-05-26', NULL, 'Patient', 'elladridi96@gmail.com', 'db3c234b8188d8df179b5ca969976d1e2f925bcf092f6e7a1f97e6750aa52a1a', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(5, 'skander', 'chamkhi', '99819349', '2000-12-23', NULL, 'Patient', 'skanderchamkhi@gmail.com', '7e071fd9b023ed8f18458a73613a0834f6220bd5cc50357ba3493c6040a9ea8c', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(6, 'test', 'tt', '24571231', '2000-06-23', 'female', 'psychologist', 'testtt@gmail.com', 'f348d5628621f3d8f59c8cabda0f8eb0aa7e0514a90be7571020b1336f26c113', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(7, 'aaa', 'bb', '26485352', '2003-12-20', NULL, 'Admin', 'admin@gmail.com', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(10, 'ella', 'dridi', '55595801', '0000-00-00', NULL, 'Patient', 'ella.dridi@esprit.tn', '96cae35ce8a9b0244178bf28e4966c2ce1b8385723a96a6b838858cdd6ca0a1e', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(11, 'arij', 'bouhlila', '155878787', '0000-00-00', NULL, 'Admin', 'arij@esprit.tn', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(12, 'wee', 'wee', '545484854', '0000-00-00', NULL, 'Psychologist', 'we@esprit.tn', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(13, 'ahmed', 'zekri', '20666202', '0000-00-00', NULL, 'Patient', 'ahmedzekri20666202@gmail.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(14, 'zikou', '.', '20666202', '0000-00-00', NULL, 'Psychologist', 'ahmedzekri@gmail.com', '2ac9a6746aca543af8dff39894cfe8173afba21eb01c6fae33d52947222855ef', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(15, 'omar', 'omar', '232222222', '2003-11-11', NULL, 'Admin', 'TEST@TEST.COM', '984751f9767837e64aaa45b53bc1cfe950a7e494971600edd60c8cf0edb22796', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(16, 'Test', 'User', '1234567890', '1990-01-01', 'male', 'Patient', 'test.user@mentis.com', '', NULL, 0, '2026-04-13 20:49:50', NULL, 0, NULL, NULL, NULL),
(17, 'AZER', 'AZER', '23412344', '2003-11-11', NULL, 'Patient', 'TEST@TEST.TEST', '984751f9767837e64aaa45b53bc1cfe950a7e494971600edd60c8cf0edb22796', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(18, 'AAA', 'AAA', '93222222', '2003-11-11', NULL, 'Psychologist', 'PSI@PSI.PSI', 'cb6c5ac68a624a693954824deee9b55280446889049daa16cfc5d7d6653f1da1', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(19, 'OMAR', 'KAMMOUN', '93322211', '2003-11-11', 'male', 'Patient', 'AAAA@AAAA.AAA', 'cb1ad2119d8fafb69566510ee712661f9f14b83385006ef92aec47f523a38358', NULL, 0, '2026-03-01 20:38:58', NULL, 0, NULL, NULL, NULL),
(20, 'maram', 'mmm', '50106936', '2007-02-01', 'female', 'Patient', 'maram@gmail.com', '483029d526219f816e8e8f6a9de07b422633dba180ffc26faac22862a017519f', NULL, 0, '2026-03-02 00:15:38', NULL, 0, NULL, NULL, NULL),
(22, 'Arij', 'bouhlila', '+21626667352', '2003-12-23', NULL, 'Psychologist', 'arijbouhlila9@gmail.com', '$2y$13$fPzY2eYou9MTI0Dc2olzeuzQ3SGW.aYizaVfzJHfRjLGJEE.NffNG', '[\"uploads\\/faces\\/22\\/sample_0.jpg\",\"uploads\\/faces\\/22\\/sample_1.jpg\",\"uploads\\/faces\\/22\\/sample_2.jpg\"]', 1, '2026-03-02 21:41:41', '2026-04-19 16:23:59', 0, NULL, NULL, NULL),
(23, 'aa', 'bb', '25896325', '2003-12-23', 'female', 'Patient', 'arijbouh@gmail.com', '6357abacb770c6e429a157619a1a075a2ccbe1fc51d7b996d709e07a6f69b6ec', NULL, 0, '2026-03-02 22:14:36', NULL, 0, NULL, NULL, NULL),
(24, 'arij', 'bh', '26667352', '2003-12-23', NULL, 'Admin', 'arij.bouhlila@esprit.tn', '$2y$13$fmVrzJ/pzjBYQB3IK5mCHu.qOoEsZSqnBiOqShIsgyKn3ZHoTETGu', NULL, 0, '2026-04-03 19:51:09', NULL, 0, NULL, NULL, NULL),
(26, 'syt', 'bh', '26667458', '1999-06-23', NULL, 'Psychologist', 'sytejbh@gmail.com', '$2y$13$6cFM8SZOASGK3ehJ1ucSXuBaVVY9PwA1S/.vo3z2t8Fs38Aq4AOcu', NULL, 0, '2026-04-03 19:52:56', NULL, 0, NULL, NULL, NULL),
(29, 'toto', 'mbarki', '24578961', '1899-12-24', 'male', 'Patient', 'toto@gmail.com', '$2y$13$zo1oPhTENac/yGCj3OMePuTjPfSl3deGG0SGjmGHgN3T5s8Q.Yf7e', NULL, 0, '2026-04-03 22:42:22', NULL, 0, NULL, NULL, NULL),
(30, 'ahle', 'nahla', '99814785', '1980-04-12', 'female', 'Psychologist', 'ahla@gmail.com', '$2y$13$pZZ1v6dRpY170S3DYEv.LO80Umhx5oOA7DxAfKTmftvnO/5DznciK', NULL, 0, '2026-04-03 23:12:27', NULL, 0, NULL, NULL, NULL),
(31, 'nadine', 'bd', '96458796', '2003-11-25', 'female', 'Psychologist', 'nadinebs@gmail.com', '$2y$13$.pfc3QvEfvQ7pUl2zdWHBOYKjowSPdmNojOsSy1LigIrJz3248mtW', NULL, 0, '2026-04-04 13:03:42', NULL, 0, NULL, NULL, NULL),
(32, 'omar', 'kamoun', '41587963', '2002-10-16', 'male', 'Admin', 'omar@gmail.com', '$2y$13$pnZmBGFPrNfShTX0YhcePuSmYyDNW5aOXDgNg3xuG0WWg89jx1jy6', NULL, 0, '2026-04-04 17:49:15', NULL, 0, NULL, NULL, NULL),
(33, 'Arij', 'Bouhlila', '96458796', '2002-10-16', 'male', 'Admin', 'arijbouhlila7@esprit.tn', '$2y$13$sDJYTBKT42OADnKEdSegx.n49GHJcU9Hg5xwupGAHNshzpk2mK.0y', NULL, 0, '2026-04-13 16:00:14', NULL, 0, NULL, NULL, NULL),
(34, 'pff', 'pffff', '41587963', '1999-06-23', 'male', 'Psychologist', 'morjena@gmail.com', '$2y$13$RamGiidguhtZnXLxS1K5dONqrFpxeQ8F04tGhqiYxiGnwSivr8N2C', NULL, 0, '2026-04-13 16:01:36', NULL, 0, NULL, NULL, NULL),
(35, 'jalel', 'bh', '29344311', '1980-09-19', 'male', 'Psychologist', 'jalelbh@gmail.com', '$2y$13$AP4xjk7HIL.w4JCps0w1YOKeLWcBaQKy5eCUiwZCJ.zH18rFRibSO', NULL, 0, '2026-04-13 20:57:11', NULL, 0, NULL, NULL, NULL),
(36, 'jalel', 'Bouhlila', '26667458', '1999-06-23', 'male', 'Patient', 'jalel@gmail.com', '$2y$13$R7lmTWCzmGHA5c2epeftYuOQDY7d41L3KCr8LmdhmUv89S1epj5Uu', NULL, 0, '2026-04-13 21:24:28', NULL, 0, '2026-04-18 15:38:46', NULL, 'Suspicious activity detected - Multiple failed login attempts'),
(37, 'ines', 'sisi', '26658741', '1990-09-19', 'male', 'Psychologist', 'ines@gmail.com', '$2y$13$qfMk/GliPSwiPZPDdmKGw.COcPJppw6Ca1qGBTS5mi8mkoabP/Jyy', NULL, 0, '2026-04-14 10:11:15', NULL, 0, NULL, NULL, NULL),
(38, 'iyed', 'bouh', '51428569', '2026-04-04', 'male', 'Patient', 'iyedbouh@gmail.com', '$2y$13$OizGt7ocr5l.KVdc5F8GJeqnkJvCQ6aOLNb6gJjhJ./PNbOjSD9Ni', NULL, 0, '2026-04-14 10:53:18', NULL, 0, NULL, NULL, NULL),
(40, 'touttt', 'ttt', '26667458', '2003-12-23', 'male', 'Patient', 'lafamille@gmail.com', '$2y$13$91gDxzMZ6wjTWElG.9IQUuYo79vRYLZDDT3ZpwnvXcYYUDuwThHT2', NULL, 0, '2026-04-14 11:20:28', NULL, 0, NULL, NULL, NULL),
(41, 'fadit', 'emchi', '74589652', '1999-06-23', 'male', 'Psychologist', 'fadit@gmail.com', '$2y$13$NM0WdVESxGXEuGxDfsx3K.pRBMIKOCSfKDogSackhbstk9YMa7ZI6', NULL, 0, '2026-04-14 11:26:50', NULL, 0, NULL, NULL, NULL),
(42, 'aicha', 'haaaa', '26667458', '1999-06-23', 'male', 'Patient', 'aicha@gmail.com', '$2y$13$DkE5v8jomER3oAyrVaGLe.T3hiSDdC4fe0ZJKVMEzL7Hx5F5cv7La', NULL, 0, '2026-04-14 11:34:29', NULL, 0, NULL, NULL, NULL),
(43, 'zizi', 'ouu', '58742123', '2002-10-16', 'male', 'Patient', 'zizi@gmail.com', '$2y$13$Js1SNpMOshiOyLvhHStUl.c/vff7wD19f34wi.nItjEEdTv/3DOFq', NULL, 0, '2026-04-17 08:33:14', NULL, 0, NULL, NULL, NULL),
(45, 'jad', 'chihi', '24589632', '2000-10-16', 'male', 'Psychologist', 'jadella@gmail.com', '$2y$13$sBAv66alOouRiGiHOTIZ0.ugx7IGU/A6eWjexbicrunfzl/h5Nk.W', NULL, 0, '2026-04-17 09:28:53', NULL, 0, NULL, NULL, NULL),
(46, 'jad', 'jad', '58963214', '1999-06-23', 'male', 'Patient', 'jad@gmail.com', '$2y$13$zWDiJCSoiIXJvMHIyzUZJut2u78diaKOb5ksJnqMUHozeC3sgmX9q', NULL, 0, '2026-04-17 09:34:28', NULL, 0, NULL, NULL, NULL),
(47, 'tet', 'tet', '50874563', '2003-12-23', 'male', 'Psychologist', '123@gmail.com', '$2y$13$OMoMzdTx.tR5.AkdLqPvmengfWn0le0fB1eJ7lsP/cGQ5g8rJg0C6', NULL, 0, '2026-04-17 21:10:44', NULL, 0, NULL, NULL, NULL),
(48, 'douaa', 'chou', '58963254', '1980-09-19', 'female', 'Admin', 'chou@gmail.com', '$2y$13$GpKUiwzp77diCwUH6m.K/.d.KbJ1hBMGnuTgCr9ChlB4YdJsGy2v2', NULL, 0, '2026-04-17 21:36:10', NULL, 0, NULL, NULL, NULL),
(49, 'doudou', 'ch', '54785236', '2002-10-16', 'male', 'Patient', 'doudouu@gmail.com', '$2y$13$a15VwGcL0eDXOEEu7.KQS.8iiDv8rML31h8qFPFeTJ.NTQZOsdgZy', NULL, 0, '2026-04-17 21:44:01', NULL, 0, NULL, NULL, NULL),
(52, 'Arij', 'Bouhlila', '96458796', '2008-04-16', 'female', 'Psychologist', 'nadia@gmail.com', '$2y$13$cCKyVDhkTBWrCcFKGZR4SeGC5Sp5RhICJlBew1gYfWcfCEvrJ7e6S', NULL, 0, '2026-04-18 15:05:35', NULL, 0, NULL, NULL, NULL),
(53, 'test', 'tettt', '58745236', '2005-05-18', 'female', 'Patient', 'test12345@gmail.com', '$2y$13$IWUf/M/Lyhs4bydQgoTeiuDNBItx3h0VBHFx2kCXZUxJIuNDNE3pq', NULL, 0, '2026-04-18 19:00:08', NULL, 0, NULL, NULL, NULL),
(54, 'manar', 'haddad', '54879632', '2006-06-19', 'female', 'Patient', 'manarhaddad@gmail.com', '$2y$13$z1U9jPeG9UXZ4keNntpvS.2MfSt81Db74Ljq7QFvmFcCWnTaCEJnO', NULL, 0, '2026-04-19 13:45:04', NULL, 0, NULL, NULL, NULL),
(55, 'sahsah', 'ahla wsahal', '56895124', '2005-06-10', 'female', 'Patient', 'jall@gmail.com', '$2y$13$xWbdJ.ZqX.UpK3oehYmW/OTbG.IflWLLcAo/.wQ4gmbdWLWJyAYTC', '[\"uploads\\/faces\\/55\\/sample_0.jpg\",\"uploads\\/faces\\/55\\/sample_1.jpg\",\"uploads\\/faces\\/55\\/sample_2.jpg\"]', 1, '2026-04-19 14:52:15', '2026-04-19 16:54:32', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user_old`
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
-- Déchargement des données de la table `user_old`
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
-- Index pour les tables déchargées
--

--
-- Index pour la table `assessment`
--
ALTER TABLE `assessment`
  ADD PRIMARY KEY (`assessment_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`);

--
-- Index pour la table `assessmentresult`
--
ALTER TABLE `assessmentresult`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_assessment` (`assessment_id`);

--
-- Index pour la table `content_node`
--
ALTER TABLE `content_node`
  ADD PRIMARY KEY (`node_id`),
  ADD KEY `IDX_481D0580DE12AB56` (`created_by`),
  ADD KEY `IDX_481D05803445EB91` (`parent_node_id`);

--
-- Index pour la table `content_path`
--
ALTER TABLE `content_path`
  ADD PRIMARY KEY (`path_id`),
  ADD KEY `IDX_C63666CAA76ED395` (`user_id`),
  ADD KEY `IDX_C63666CA460D9FD7` (`node_id`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_date_time` (`date_time`),
  ADD KEY `idx_status` (`status`);

--
-- Index pour la table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email_event` (`email`,`event_id`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_registration_date` (`registration_date`);

--
-- Index pour la table `goal`
--
ALTER TABLE `goal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Index pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_attempted_at` (`attempted_at`);

--
-- Index pour la table `mood`
--
ALTER TABLE `mood`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_date` (`date`);

--
-- Index pour la table `pending_reminders`
--
ALTER TABLE `pending_reminders`
  ADD PRIMARY KEY (`reminder_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Index pour la table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_assessment` (`assessment_id`);

--
-- Index pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `reserved_by` (`reserved_by`),
  ADD KEY `idx_session_date` (`session_date`),
  ADD KEY `idx_session_type` (`session_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`);

--
-- Index pour la table `session_review`
--
ALTER TABLE `session_review`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_patient` (`patient_id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_type` (`type`);

--
-- Index pour la table `user_old`
--
ALTER TABLE `user_old`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `assessment`
--
ALTER TABLE `assessment`
  MODIFY `assessment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `assessmentresult`
--
ALTER TABLE `assessmentresult`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT pour la table `content_node`
--
ALTER TABLE `content_node`
  MODIFY `node_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT pour la table `content_path`
--
ALTER TABLE `content_path`
  MODIFY `path_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `content_node`
--
ALTER TABLE `content_node`
  ADD CONSTRAINT `content_node_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_node_ibfk_2` FOREIGN KEY (`parent_node_id`) REFERENCES `content_node` (`node_id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `content_path`
--
ALTER TABLE `content_path`
  ADD CONSTRAINT `content_path_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `content_path_ibfk_2` FOREIGN KEY (`node_id`) REFERENCES `content_node` (`node_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
