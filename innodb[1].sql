-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 27, 2025 at 03:27 AM
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
-- Database: `innodb`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_roll_number` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent') DEFAULT 'Absent',
  `marked_by` int(11) DEFAULT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `academic_year` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_ids`
--

CREATE TABLE `blocked_ids` (
  `block_id` int(11) NOT NULL,
  `student_roll_number` varchar(50) NOT NULL,
  `reason` text NOT NULL,
  `blocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unblocked_at` datetime DEFAULT NULL,
  `attendance_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_grievances`
--

CREATE TABLE `feedback_grievances` (
  `grievance_id` int(11) NOT NULL,
  `student_roll_number` varchar(50) NOT NULL,
  `type` enum('Feedback','Grievance') NOT NULL,
  `message` text NOT NULL,
  `status` enum('Open','Resolved','Pending') DEFAULT 'Open',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hostels`
--

CREATE TABLE `hostels` (
  `hostel_id` int(11) NOT NULL,
  `hostel_code` varchar(20) NOT NULL,
  `hostel_name` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Mixed') NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `id_movements`
--

CREATE TABLE `id_movements` (
  `movement_id` int(11) NOT NULL,
  `block_id` int(11) NOT NULL,
  `movement_type` enum('In','Out') NOT NULL,
  `movement_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `leave_id` int(11) NOT NULL,
  `student_roll_number` varchar(50) NOT NULL,
  `leave_type` enum('General','Leave','Emergency','OD') NOT NULL,
  `from_date` datetime NOT NULL,
  `to_date` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `parent_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `faculty_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `final_status` enum('Pending','Approved','Rejected','Cancelled','Closed','Out') DEFAULT 'Pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `outpass_mode` enum('Digital','Manual') DEFAULT 'Digital',
  `proof_path` varchar(255) DEFAULT NULL,
  `parent_email_scheduled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_approval_portals`
--

CREATE TABLE `leave_approval_portals` (
  `portal_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `leave_id` int(11) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `notified_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mess_menu`
--

CREATE TABLE `mess_menu` (
  `menu_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `meal_type` enum('Breakfast','Lunch','Snacks','Dinner') NOT NULL,
  `items` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `fee` decimal(8,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mess_supervisors`
--

CREATE TABLE `mess_supervisors` (
  `supervisor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mess_tokens`
--

CREATE TABLE `mess_tokens` (
  `token_id` int(11) NOT NULL,
  `student_roll_number` varchar(50) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `token_type` enum('Paid','Unpaid','Special') NOT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `special_fee` decimal(8,2) DEFAULT 0.00,
  `supervisor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mess_token_bills`
--

CREATE TABLE `mess_token_bills` (
  `bill_id` int(11) NOT NULL,
  `student_roll_number` varchar(50) NOT NULL,
  `month` year(4) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `outings`
--

CREATE TABLE `outings` (
  `outing_id` int(11) NOT NULL,
  `student_roll_number` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Planned','Completed','Cancelled') DEFAULT 'Planned',
  `allowed` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `parent_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `email_alt` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `phone_alt` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `hostel_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 3,
  `occupied` int(11) NOT NULL DEFAULT 0,
  `room_type` enum('AC','Non-AC') NOT NULL DEFAULT 'Non-AC',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `roll_number` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `block` varchar(10) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `student_phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `father_phone` varchar(15) DEFAULT NULL,
  `mother_phone` varchar(15) DEFAULT NULL,
  `guardian_phone` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_parents`
--

CREATE TABLE `student_parents` (
  `student_roll_number` varchar(50) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `relation_enum` enum('Father','Mother','Guardian','Other') DEFAULT 'Other',
  `is_primary_contact` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temp_stays`
--

CREATE TABLE `temp_stays` (
  `stay_id` int(11) NOT NULL,
  `student_roll_number` varchar(50) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `daily_charge` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `token_actions`
--

CREATE TABLE `token_actions` (
  `action_id` int(11) NOT NULL,
  `token_id` int(11) NOT NULL,
  `supervisor_id` int(11) NOT NULL,
  `action_type` enum('Issued','Approved','Revoked') NOT NULL,
  `action_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin','faculty','mess_supervisor') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `uq_attendance_student_date` (`student_roll_number`,`date`),
  ADD KEY `marked_by` (`marked_by`),
  ADD KEY `idx_attendance_date` (`date`);

--
-- Indexes for table `blocked_ids`
--
ALTER TABLE `blocked_ids`
  ADD PRIMARY KEY (`block_id`),
  ADD KEY `attendance_id` (`attendance_id`),
  ADD KEY `idx_block_student` (`student_roll_number`);

--
-- Indexes for table `feedback_grievances`
--
ALTER TABLE `feedback_grievances`
  ADD PRIMARY KEY (`grievance_id`),
  ADD KEY `idx_grievance_student` (`student_roll_number`);

--
-- Indexes for table `hostels`
--
ALTER TABLE `hostels`
  ADD PRIMARY KEY (`hostel_id`),
  ADD UNIQUE KEY `hostel_code` (`hostel_code`);

--
-- Indexes for table `id_movements`
--
ALTER TABLE `id_movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `idx_movements_block` (`block_id`);

--
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `idx_leave_student` (`student_roll_number`),
  ADD KEY `idx_leave_final_status` (`final_status`),
  ADD KEY `idx_leave_applied_at` (`applied_at`);

--
-- Indexes for table `leave_approval_portals`
--
ALTER TABLE `leave_approval_portals`
  ADD PRIMARY KEY (`portal_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `idx_portal_leave` (`leave_id`);

--
-- Indexes for table `mess_menu`
--
ALTER TABLE `mess_menu`
  ADD PRIMARY KEY (`menu_id`),
  ADD UNIQUE KEY `uq_mess_menu_date_meal` (`date`,`meal_type`);

--
-- Indexes for table `mess_supervisors`
--
ALTER TABLE `mess_supervisors`
  ADD PRIMARY KEY (`supervisor_id`),
  ADD KEY `idx_supervisor_user` (`user_id`);

--
-- Indexes for table `mess_tokens`
--
ALTER TABLE `mess_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `supervisor_id` (`supervisor_id`),
  ADD KEY `idx_tokens_student` (`student_roll_number`),
  ADD KEY `idx_tokens_menu` (`menu_id`);

--
-- Indexes for table `mess_token_bills`
--
ALTER TABLE `mess_token_bills`
  ADD PRIMARY KEY (`bill_id`),
  ADD UNIQUE KEY `uq_bill_student_month` (`student_roll_number`,`month`),
  ADD KEY `idx_bills_student` (`student_roll_number`);

--
-- Indexes for table `outings`
--
ALTER TABLE `outings`
  ADD PRIMARY KEY (`outing_id`),
  ADD KEY `idx_outings_student` (`student_roll_number`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`parent_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `uq_hostel_room` (`hostel_id`,`room_number`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `roll_number` (`roll_number`),
  ADD KEY `idx_students_user` (`user_id`),
  ADD KEY `idx_students_room` (`room_id`);

--
-- Indexes for table `student_parents`
--
ALTER TABLE `student_parents`
  ADD PRIMARY KEY (`student_roll_number`,`parent_id`),
  ADD KEY `idx_sp_parent` (`parent_id`);

--
-- Indexes for table `temp_stays`
--
ALTER TABLE `temp_stays`
  ADD PRIMARY KEY (`stay_id`),
  ADD KEY `student_roll_number` (`student_roll_number`);

--
-- Indexes for table `token_actions`
--
ALTER TABLE `token_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `supervisor_id` (`supervisor_id`),
  ADD KEY `idx_token_actions_token` (`token_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blocked_ids`
--
ALTER TABLE `blocked_ids`
  MODIFY `block_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_grievances`
--
ALTER TABLE `feedback_grievances`
  MODIFY `grievance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hostels`
--
ALTER TABLE `hostels`
  MODIFY `hostel_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `id_movements`
--
ALTER TABLE `id_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_approval_portals`
--
ALTER TABLE `leave_approval_portals`
  MODIFY `portal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mess_menu`
--
ALTER TABLE `mess_menu`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mess_supervisors`
--
ALTER TABLE `mess_supervisors`
  MODIFY `supervisor_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mess_tokens`
--
ALTER TABLE `mess_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mess_token_bills`
--
ALTER TABLE `mess_token_bills`
  MODIFY `bill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `outings`
--
ALTER TABLE `outings`
  MODIFY `outing_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `parent_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `temp_stays`
--
ALTER TABLE `temp_stays`
  MODIFY `stay_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `token_actions`
--
ALTER TABLE `token_actions`
  MODIFY `action_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_roll_number`) REFERENCES `students` (`roll_number`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`marked_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `blocked_ids`
--
ALTER TABLE `blocked_ids`
  ADD CONSTRAINT `blocked_ids_ibfk_1` FOREIGN KEY (`student_roll_number`) REFERENCES `students` (`roll_number`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `blocked_ids_ibfk_2` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`attendance_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `feedback_grievances`
--
ALTER TABLE `feedback_grievances`
  ADD CONSTRAINT `feedback_grievances_ibfk_1` FOREIGN KEY (`student_roll_number`) REFERENCES `students` (`roll_number`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `id_movements`
--
ALTER TABLE `id_movements`
  ADD CONSTRAINT `id_movements_ibfk_1` FOREIGN KEY (`block_id`) REFERENCES `blocked_ids` (`block_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD CONSTRAINT `leave_applications_ibfk_1` FOREIGN KEY (`student_roll_number`) REFERENCES `students` (`roll_number`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `leave_approval_portals`
--
ALTER TABLE `leave_approval_portals`
  ADD CONSTRAINT `leave_approval_portals_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`parent_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `leave_approval_portals_ibfk_2` FOREIGN KEY (`leave_id`) REFERENCES `leave_applications` (`leave_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mess_supervisors`
--
ALTER TABLE `mess_supervisors`
  ADD CONSTRAINT `mess_supervisors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mess_tokens`
--
ALTER TABLE `mess_tokens`
  ADD CONSTRAINT `mess_tokens_ibfk_1` FOREIGN KEY (`student_roll_number`) REFERENCES `students` (`roll_number`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `mess_tokens_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `mess_menu` (`menu_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `mess_tokens_ibfk_3` FOREIGN KEY (`supervisor_id`) REFERENCES `mess_supervisors` (`supervisor_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `mess_token_bills`
--
ALTER TABLE `mess_token_bills`
  ADD CONSTRAINT `mess_token_bills_ibfk_1` FOREIGN KEY (`student_roll_number`) REFERENCES `students` (`roll_number`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `outings`
--
ALTER TABLE `outings`
  ADD CONSTRAINT `outings_ibfk_1` FOREIGN KEY (`student_roll_number`) REFERENCES `students` (`roll_number`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`hostel_id`) REFERENCES `hostels` (`hostel_id`) ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `student_parents`
--
ALTER TABLE `student_parents`
  ADD CONSTRAINT `student_parents_ibfk_1` FOREIGN KEY (`student_roll_number`) REFERENCES `students` (`roll_number`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_parents_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`parent_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `temp_stays`
--
ALTER TABLE `temp_stays`
  ADD CONSTRAINT `temp_stays_ibfk_1` FOREIGN KEY (`student_roll_number`) REFERENCES `students` (`roll_number`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `token_actions`
--
ALTER TABLE `token_actions`
  ADD CONSTRAINT `token_actions_ibfk_1` FOREIGN KEY (`token_id`) REFERENCES `mess_tokens` (`token_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `token_actions_ibfk_2` FOREIGN KEY (`supervisor_id`) REFERENCES `mess_supervisors` (`supervisor_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
