-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 14, 2026 at 06:17 PM
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
-- Database: `contractor_dept_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classification_results`
--

CREATE TABLE `classification_results` (
  `classification_id` int(11) NOT NULL,
  `grower_id` int(11) NOT NULL,
  `plant_position` varchar(5) DEFAULT NULL,
  `quality_code` int(11) DEFAULT NULL,
  `colour_code` varchar(5) DEFAULT NULL,
  `style_code` varchar(5) DEFAULT NULL,
  `extra_code` varchar(5) DEFAULT NULL,
  `generated_grade` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contractors`
--

CREATE TABLE `contractors` (
  `contractor_id` int(11) NOT NULL,
  `contractor_code` varchar(5) NOT NULL,
  `contractor_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contractors`
--

INSERT INTO `contractors` (`contractor_id`, `contractor_code`, `contractor_name`, `phone`, `email`, `address`, `status`, `created_at`) VALUES
(1, 'SMT', 'Smoke Merchant Tobacco', '0788567331', NULL, NULL, 'active', '2026-07-02 13:07:39');

-- --------------------------------------------------------

--
-- Table structure for table `contracts`
--

CREATE TABLE `contracts` (
  `contract_id` int(11) NOT NULL,
  `contract_number` varchar(30) NOT NULL,
  `grower_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `season_id` int(11) NOT NULL,
  `contract_date` date NOT NULL,
  `interest_rate` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contracts`
--

INSERT INTO `contracts` (`contract_id`, `contract_number`, `grower_id`, `contractor_id`, `season_id`, `contract_date`, `interest_rate`, `status`, `created_at`) VALUES
(1, 'SMT-2026-000001', 1, 1, 1, '2026-07-02', 0.00, 'active', '2026-07-02 13:09:38');

-- --------------------------------------------------------

--
-- Table structure for table `grade_prices`
--

CREATE TABLE `grade_prices` (
  `price_id` int(11) NOT NULL,
  `grade_code` varchar(20) NOT NULL,
  `average_price` decimal(10,2) NOT NULL,
  `price_date` date NOT NULL,
  `source` varchar(50) DEFAULT 'TIMB',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_prices`
--

INSERT INTO `grade_prices` (`price_id`, `grade_code`, `average_price`, `price_date`, `source`, `created_at`) VALUES
(1, 'L2O', 4.80, '2026-07-14', 'TIMB', '2026-07-14 15:17:42'),
(2, 'L2FA', 4.48, '2026-07-14', 'TIMB', '2026-07-14 15:17:42'),
(3, 'X4OK', 1.65, '2026-07-14', 'TIMB', '2026-07-14 15:17:42'),
(4, 'P4MD', 1.40, '2026-07-14', 'TIMB', '2026-07-14 15:17:42'),
(5, 'P5O', 1.00, '2026-07-14', 'TIMB', '2026-07-14 15:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `growers`
--

CREATE TABLE `growers` (
  `grower_id` int(11) NOT NULL,
  `grower_no` varchar(20) NOT NULL,
  `national_id` varchar(20) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `district` varchar(50) DEFAULT NULL,
  `ward` varchar(20) DEFAULT NULL,
  `village` varchar(80) DEFAULT NULL,
  `farm_name` varchar(100) DEFAULT NULL,
  `hectares` decimal(6,2) DEFAULT NULL,
  `portal_enabled` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_debt` decimal(10,2) DEFAULT 0.00,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `growers`
--

INSERT INTO `growers` (`grower_id`, `grower_no`, `national_id`, `first_name`, `last_name`, `gender`, `date_of_birth`, `phone`, `province`, `district`, `ward`, `village`, `farm_name`, `hectares`, `portal_enabled`, `created_at`, `total_debt`, `user_id`) VALUES
(1, 'V175259', NULL, 'Lennon', 'Jenifani', NULL, NULL, '0779876543', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-02 13:08:46', 1500.00, 3),
(2, 'V192046', 'T23-2435345 T56', 'Kelvin', 'Mundopa', NULL, NULL, '0777777777', 'Mvurwi', 'Makoni', '12', 'Katumbu', 'Prace farm', 1500.00, 0, '2026-07-03 09:59:54', 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `input_categories`
--

CREATE TABLE `input_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_type` enum('Input','Cash','Deduction','Adjustment') DEFAULT 'Input',
  `unit` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plant_positions`
--

CREATE TABLE `plant_positions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `code` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plant_positions`
--

INSERT INTO `plant_positions` (`id`, `name`, `code`) VALUES
(1, 'Primings', 'P'),
(2, 'Lugs', 'X'),
(3, 'Cutters', 'C'),
(4, 'Leaf', 'L'),
(5, 'Tips', 'T');

-- --------------------------------------------------------

--
-- Table structure for table `projection_prices`
--

CREATE TABLE `projection_prices` (
  `matrix_id` int(11) NOT NULL,
  `price_date` date NOT NULL,
  `plant_position` enum('P','X','C','L','T') NOT NULL,
  `quality` enum('Very Poor','Poor','Fair','Good','Very Good') NOT NULL,
  `estimated_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projection_prices`
--

INSERT INTO `projection_prices` (`matrix_id`, `price_date`, `plant_position`, `quality`, `estimated_price`) VALUES
(1, '2026-07-13', 'P', 'Very Poor', 0.80),
(2, '2026-07-13', 'P', 'Poor', 1.20),
(3, '2026-07-13', 'P', 'Fair', 1.60),
(4, '2026-07-13', 'P', 'Good', 2.00),
(5, '2026-07-13', 'P', 'Very Good', 2.40),
(6, '2026-07-13', 'X', 'Very Poor', 1.00),
(7, '2026-07-13', 'X', 'Poor', 1.50),
(8, '2026-07-13', 'X', 'Fair', 2.00),
(9, '2026-07-13', 'X', 'Good', 2.50),
(10, '2026-07-13', 'X', 'Very Good', 3.00),
(11, '2026-07-13', 'C', 'Very Poor', 1.50),
(12, '2026-07-13', 'C', 'Poor', 2.00),
(13, '2026-07-13', 'C', 'Fair', 2.50),
(14, '2026-07-13', 'C', 'Good', 3.00),
(15, '2026-07-13', 'C', 'Very Good', 3.50),
(16, '2026-07-13', 'L', 'Very Poor', 2.00),
(17, '2026-07-13', 'L', 'Poor', 2.50),
(18, '2026-07-13', 'L', 'Fair', 3.00),
(19, '2026-07-13', 'L', 'Good', 4.00),
(20, '2026-07-13', 'L', 'Very Good', 4.80),
(21, '2026-07-13', 'T', 'Very Poor', 2.20),
(22, '2026-07-13', 'T', 'Poor', 2.80),
(23, '2026-07-13', 'T', 'Fair', 3.40),
(24, '2026-07-13', 'T', 'Good', 4.20),
(25, '2026-07-13', 'T', 'Very Good', 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchased_bales`
--

CREATE TABLE `purchased_bales` (
  `bale_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `barcode` varchar(30) NOT NULL,
  `lot_no` int(11) DEFAULT NULL,
  `mass` decimal(10,2) DEFAULT NULL,
  `grade` varchar(20) DEFAULT NULL,
  `price_per_kg` decimal(10,2) DEFAULT NULL,
  `value` decimal(12,2) DEFAULT NULL,
  `rejected` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `sale_number` varchar(30) NOT NULL,
  `sale_date` date NOT NULL,
  `total_bales` int(11) DEFAULT 0,
  `total_mass` decimal(10,2) DEFAULT 0.00,
  `gross_value` decimal(12,2) DEFAULT 0.00,
  `average_price` decimal(10,2) DEFAULT 0.00,
  `bank_charges` decimal(12,2) DEFAULT 0.00,
  `net_payment` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_projections`
--

CREATE TABLE `sale_projections` (
  `projection_id` int(11) NOT NULL,
  `grower_id` int(11) NOT NULL,
  `plant_position` char(1) DEFAULT NULL,
  `quality` varchar(20) DEFAULT NULL,
  `estimated_kg` decimal(10,2) DEFAULT NULL,
  `estimated_price` decimal(10,2) DEFAULT NULL,
  `projected_revenue` decimal(10,2) DEFAULT NULL,
  `projected_payout` decimal(10,2) DEFAULT NULL,
  `recovery_risk` varchar(20) DEFAULT NULL,
  `zero_pay_status` varchar(10) DEFAULT NULL,
  `projection_date` datetime DEFAULT current_timestamp(),
  `generated_grade` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_projections`
--

INSERT INTO `sale_projections` (`projection_id`, `grower_id`, `plant_position`, `quality`, `estimated_kg`, `estimated_price`, `projected_revenue`, `projected_payout`, `recovery_risk`, `zero_pay_status`, `projection_date`, `generated_grade`) VALUES
(3, 1, 'X', 'Good', 500.00, 2.50, 1250.00, 0.00, 'MEDIUM', 'YES', '2026-07-14 17:28:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `seasons`
--

CREATE TABLE `seasons` (
  `season_id` int(11) NOT NULL,
  `season_name` varchar(10) NOT NULL,
  `is_active` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seasons`
--

INSERT INTO `seasons` (`season_id`, `season_name`, `is_active`) VALUES
(1, '2026', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tobacco_colours`
--

CREATE TABLE `tobacco_colours` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `code` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tobacco_colours`
--

INSERT INTO `tobacco_colours` (`id`, `name`, `code`) VALUES
(1, 'Pale Lemon', 'E'),
(2, 'Lemon', 'L'),
(3, 'Orange', 'O'),
(4, 'Light Mahogany', 'R'),
(5, 'Dark Mahogany', 'S');

-- --------------------------------------------------------

--
-- Table structure for table `tobacco_quality`
--

CREATE TABLE `tobacco_quality` (
  `id` int(11) NOT NULL,
  `description` varchar(50) DEFAULT NULL,
  `quality_code` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tobacco_quality`
--

INSERT INTO `tobacco_quality` (`id`, `description`, `quality_code`) VALUES
(1, 'Very Good', 1),
(2, 'Good', 2),
(3, 'Fair', 3),
(4, 'Poor', 4),
(5, 'Very Poor', 5);

-- --------------------------------------------------------

--
-- Table structure for table `tobacco_styles`
--

CREATE TABLE `tobacco_styles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `code` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tobacco_styles`
--

INSERT INTO `tobacco_styles` (`id`, `name`, `code`) VALUES
(1, 'Ripe / Soft', 'F'),
(2, 'Close Grained', 'K'),
(3, 'Slatey', 'U');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `transaction_type` enum('Debit','Credit') NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `uploaded_files`
--

CREATE TABLE `uploaded_files` (
  `file_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `rows_imported` int(11) DEFAULT 0,
  `upload_status` enum('Pending','Imported','Failed') DEFAULT 'Pending',
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('admin','contractor','grower') NOT NULL,
  `grower_id` int(11) DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `user_type`, `grower_id`, `contractor_id`, `status`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$nA4tne9S2VMWKI0ufi4pBuM1YP7gp0UtSKf3lN99nLiFB/E1e1a1q', 'admin', NULL, NULL, 'active', NULL, '2026-07-02 14:45:33'),
(2, 'SMT', '$2y$10$XtA90WTAhSBFXyOTwnemxOJFXSKa8YVJonWh3OOoeKfq9B6fv00km', 'contractor', NULL, NULL, 'active', NULL, '2026-07-04 15:12:34'),
(3, 'V175259', '$2y$10$XtA90WTAhSBFXyOTwnemxOJFXSKa8YVJonWh3OOoeKfq9B6fv00km', 'grower', NULL, NULL, 'active', NULL, '2026-07-04 15:14:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_audit_user` (`user_id`);

--
-- Indexes for table `classification_results`
--
ALTER TABLE `classification_results`
  ADD PRIMARY KEY (`classification_id`);

--
-- Indexes for table `contractors`
--
ALTER TABLE `contractors`
  ADD PRIMARY KEY (`contractor_id`),
  ADD UNIQUE KEY `contractor_code` (`contractor_code`);

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`contract_id`),
  ADD UNIQUE KEY `contract_number` (`contract_number`),
  ADD KEY `fk_contract_grower` (`grower_id`),
  ADD KEY `fk_contract_contractor` (`contractor_id`),
  ADD KEY `fk_contract_season` (`season_id`);

--
-- Indexes for table `grade_prices`
--
ALTER TABLE `grade_prices`
  ADD PRIMARY KEY (`price_id`);

--
-- Indexes for table `growers`
--
ALTER TABLE `growers`
  ADD PRIMARY KEY (`grower_id`),
  ADD UNIQUE KEY `grower_no` (`grower_no`),
  ADD UNIQUE KEY `national_id` (`national_id`);

--
-- Indexes for table `input_categories`
--
ALTER TABLE `input_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `fk_notification_user` (`user_id`);

--
-- Indexes for table `plant_positions`
--
ALTER TABLE `plant_positions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `projection_prices`
--
ALTER TABLE `projection_prices`
  ADD PRIMARY KEY (`matrix_id`);

--
-- Indexes for table `purchased_bales`
--
ALTER TABLE `purchased_bales`
  ADD PRIMARY KEY (`bale_id`),
  ADD KEY `fk_bale_sale` (`sale_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD UNIQUE KEY `sale_number` (`sale_number`),
  ADD KEY `fk_sales_contract` (`contract_id`);

--
-- Indexes for table `sale_projections`
--
ALTER TABLE `sale_projections`
  ADD PRIMARY KEY (`projection_id`);

--
-- Indexes for table `seasons`
--
ALTER TABLE `seasons`
  ADD PRIMARY KEY (`season_id`),
  ADD UNIQUE KEY `season_name` (`season_name`);

--
-- Indexes for table `tobacco_colours`
--
ALTER TABLE `tobacco_colours`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tobacco_quality`
--
ALTER TABLE `tobacco_quality`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tobacco_styles`
--
ALTER TABLE `tobacco_styles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `fk_transaction_contract` (`contract_id`),
  ADD KEY `fk_transaction_category` (`category_id`),
  ADD KEY `fk_transaction_user` (`created_by`);

--
-- Indexes for table `uploaded_files`
--
ALTER TABLE `uploaded_files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `fk_upload_contractor` (`contractor_id`),
  ADD KEY `fk_upload_user` (`uploaded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_user_grower` (`grower_id`),
  ADD KEY `fk_user_contractor` (`contractor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classification_results`
--
ALTER TABLE `classification_results`
  MODIFY `classification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contractors`
--
ALTER TABLE `contractors`
  MODIFY `contractor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `contract_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grade_prices`
--
ALTER TABLE `grade_prices`
  MODIFY `price_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `growers`
--
ALTER TABLE `growers`
  MODIFY `grower_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `input_categories`
--
ALTER TABLE `input_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plant_positions`
--
ALTER TABLE `plant_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `projection_prices`
--
ALTER TABLE `projection_prices`
  MODIFY `matrix_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `purchased_bales`
--
ALTER TABLE `purchased_bales`
  MODIFY `bale_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sale_projections`
--
ALTER TABLE `sale_projections`
  MODIFY `projection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `seasons`
--
ALTER TABLE `seasons`
  MODIFY `season_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tobacco_colours`
--
ALTER TABLE `tobacco_colours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tobacco_quality`
--
ALTER TABLE `tobacco_quality`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tobacco_styles`
--
ALTER TABLE `tobacco_styles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uploaded_files`
--
ALTER TABLE `uploaded_files`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `fk_contract_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`contractor_id`),
  ADD CONSTRAINT `fk_contract_grower` FOREIGN KEY (`grower_id`) REFERENCES `growers` (`grower_id`),
  ADD CONSTRAINT `fk_contract_season` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`season_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `purchased_bales`
--
ALTER TABLE `purchased_bales`
  ADD CONSTRAINT `fk_bale_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`contract_id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transaction_category` FOREIGN KEY (`category_id`) REFERENCES `input_categories` (`category_id`),
  ADD CONSTRAINT `fk_transaction_contract` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`contract_id`),
  ADD CONSTRAINT `fk_transaction_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `uploaded_files`
--
ALTER TABLE `uploaded_files`
  ADD CONSTRAINT `fk_upload_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`contractor_id`),
  ADD CONSTRAINT `fk_upload_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_contractor` FOREIGN KEY (`contractor_id`) REFERENCES `contractors` (`contractor_id`),
  ADD CONSTRAINT `fk_user_grower` FOREIGN KEY (`grower_id`) REFERENCES `growers` (`grower_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
