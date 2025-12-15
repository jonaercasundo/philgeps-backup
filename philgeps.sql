-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 11, 2025 at 04:41 AM
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
-- Database: `philgeps`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `billing_grouped`
--

CREATE TABLE `billing_grouped` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `dr_no` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `delivery_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `keystage_id` int(11) DEFAULT NULL,
  `lot_id` int(11) DEFAULT NULL,
  `package_type` enum('C1','C2','C3','C4','C5','C6') NOT NULL,
  `dr_no` varchar(100) NOT NULL,
  `delivery_date` date NOT NULL,
  `status` enum('pending','delivered','accepted','cancelled','warehouse') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivered_date` date DEFAULT NULL,
  `logistics_location_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_photo`
--

CREATE TABLE `delivery_photo` (
  `delivery_photo_id` int(11) NOT NULL,
  `package_status_id` int(11) NOT NULL,
  `status` enum('accepted','delivered','','') NOT NULL,
  `delivery_photo` varchar(255) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `doc_type` enum('BAC Resolution','Notice of Award','Notice to Proceed','Delivery Receipt','Inspection Report') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grouping`
--

CREATE TABLE `grouping` (
  `group_id` int(11) NOT NULL,
  `status` enum('for billing','billed','paid') NOT NULL DEFAULT 'for billing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `group_name` varchar(255) NOT NULL,
  `paid_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `item_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `inventory_status` enum('For Approval','Approved') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_history`
--

CREATE TABLE `inventory_history` (
  `history_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `old_qty` int(11) DEFAULT NULL,
  `new_qty` int(11) DEFAULT NULL,
  `changed_at` datetime DEFAULT current_timestamp(),
  `changed_by` varchar(100) DEFAULT NULL,
  `change_type` enum('insert','update','delete') DEFAULT 'update',
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `invoice_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `invoice_no` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `invoice_date` date NOT NULL,
  `status` enum('Pending','Paid','Cancelled') DEFAULT 'Pending',
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

CREATE TABLE `item` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `project_id` int(11) NOT NULL,
  `price` float NOT NULL,
  `supplier_price` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `keystage`
--

CREATE TABLE `keystage` (
  `keystage_id` int(11) NOT NULL,
  `keystage_num` int(11) NOT NULL,
  `lot_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logistics`
--

CREATE TABLE `logistics` (
  `logistic_id` int(11) NOT NULL,
  `logistic_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logistics_location`
--

CREATE TABLE `logistics_location` (
  `logistics_location_id` int(11) NOT NULL,
  `logistics_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `region` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logistic_activities`
--

CREATE TABLE `logistic_activities` (
  `logistic_activities_id` int(11) NOT NULL,
  `type` enum('outgoing','incoming') NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lot`
--

CREATE TABLE `lot` (
  `lot_id` int(11) NOT NULL,
  `lot_name` varchar(100) NOT NULL,
  `project_id` int(11) NOT NULL,
  `contract_no` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package`
--

CREATE TABLE `package` (
  `package_id` int(11) NOT NULL,
  `package_num` int(11) NOT NULL,
  `keystage_id` int(11) DEFAULT NULL,
  `lot_id` int(11) DEFAULT NULL,
  `height` float NOT NULL,
  `width` float NOT NULL,
  `length` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_content`
--

CREATE TABLE `package_content` (
  `package_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_status`
--

CREATE TABLE `package_status` (
  `package_status_id` int(11) NOT NULL,
  `delivery_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `status` enum('pending','accepted','delivered','warehouse') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL,
  `ref_no` int(11) DEFAULT NULL,
  `project_name` longtext NOT NULL,
  `agency` varchar(255) NOT NULL,
  `contract_amount` decimal(15,2) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Pending Evaluation','For Award','For Implementation','Ongoing','Delivered','Completed') DEFAULT 'Pending Evaluation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `keystage` enum('1','0') NOT NULL DEFAULT '0',
  `ABC` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school`
--

CREATE TABLE `school` (
  `school_id` int(11) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `contact` varchar(255) NOT NULL,
  `municipality` varchar(255) NOT NULL,
  `division` varchar(255) NOT NULL,
  `region` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schools_project`
--

CREATE TABLE `schools_project` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Super Admin','Warehouse Admin','Warehouse Coordinator','Office Admin','Office Coordinator','Viewer') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `warehouse_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouse`
--

CREATE TABLE `warehouse` (
  `warehouse_id` int(11) NOT NULL,
  `warehouse_name` varchar(255) NOT NULL,
  `warehouse_address` varchar(255) NOT NULL,
  `contact_info` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `billing_grouped`
--
ALTER TABLE `billing_grouped`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `keystage_id` (`keystage_id`),
  ADD KEY `lot_id` (`lot_id`),
  ADD KEY `logistics_location_id` (`logistics_location_id`);

--
-- Indexes for table `delivery_photo`
--
ALTER TABLE `delivery_photo`
  ADD PRIMARY KEY (`delivery_photo_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `grouping`
--
ALTER TABLE `grouping`
  ADD PRIMARY KEY (`group_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `inventory_ibfk_1` (`item_id`);

--
-- Indexes for table `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `inventory_history_ibfk_1` (`inventory_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `item`
--
ALTER TABLE `item`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `keystage`
--
ALTER TABLE `keystage`
  ADD PRIMARY KEY (`keystage_id`),
  ADD KEY `lot_id` (`lot_id`);

--
-- Indexes for table `logistics`
--
ALTER TABLE `logistics`
  ADD PRIMARY KEY (`logistic_id`);

--
-- Indexes for table `logistics_location`
--
ALTER TABLE `logistics_location`
  ADD PRIMARY KEY (`logistics_location_id`),
  ADD KEY `logistics_location_ibfk_1` (`logistics_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `logistic_activities`
--
ALTER TABLE `logistic_activities`
  ADD PRIMARY KEY (`logistic_activities_id`),
  ADD KEY `delivery_id` (`delivery_id`);

--
-- Indexes for table `lot`
--
ALTER TABLE `lot`
  ADD PRIMARY KEY (`lot_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `package`
--
ALTER TABLE `package`
  ADD PRIMARY KEY (`package_id`),
  ADD KEY `keystage_id` (`keystage_id`);

--
-- Indexes for table `package_content`
--
ALTER TABLE `package_content`
  ADD KEY `item_id` (`item_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `package_status`
--
ALTER TABLE `package_status`
  ADD PRIMARY KEY (`package_status_id`),
  ADD KEY `package_status_ibfk_1` (`delivery_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`),
  ADD UNIQUE KEY `ref_no` (`ref_no`);

--
-- Indexes for table `school`
--
ALTER TABLE `school`
  ADD PRIMARY KEY (`school_id`);

--
-- Indexes for table `schools_project`
--
ALTER TABLE `schools_project`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `school` (`school_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `warehouse`
--
ALTER TABLE `warehouse`
  ADD PRIMARY KEY (`warehouse_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `billing_grouped`
--
ALTER TABLE `billing_grouped`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_photo`
--
ALTER TABLE `delivery_photo`
  MODIFY `delivery_photo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grouping`
--
ALTER TABLE `grouping`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_history`
--
ALTER TABLE `inventory_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item`
--
ALTER TABLE `item`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `keystage`
--
ALTER TABLE `keystage`
  MODIFY `keystage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logistics`
--
ALTER TABLE `logistics`
  MODIFY `logistic_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logistics_location`
--
ALTER TABLE `logistics_location`
  MODIFY `logistics_location_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logistic_activities`
--
ALTER TABLE `logistic_activities`
  MODIFY `logistic_activities_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lot`
--
ALTER TABLE `lot`
  MODIFY `lot_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package`
--
ALTER TABLE `package`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_status`
--
ALTER TABLE `package_status`
  MODIFY `package_status_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school`
--
ALTER TABLE `school`
  MODIFY `school_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schools_project`
--
ALTER TABLE `schools_project`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warehouse`
--
ALTER TABLE `warehouse`
  MODIFY `warehouse_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `billing_grouped`
--
ALTER TABLE `billing_grouped`
  ADD CONSTRAINT `billing_grouped_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `grouping` (`group_id`);

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deliveries_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `school` (`school_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `deliveries_ibfk_4` FOREIGN KEY (`keystage_id`) REFERENCES `keystage` (`keystage_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `deliveries_ibfk_5` FOREIGN KEY (`lot_id`) REFERENCES `lot` (`lot_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `deliveries_ibfk_6` FOREIGN KEY (`logistics_location_id`) REFERENCES `logistics_location` (`logistics_location_id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`warehouse_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD CONSTRAINT `inventory_history_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`inventory_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `item`
--
ALTER TABLE `item`
  ADD CONSTRAINT `item_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `keystage`
--
ALTER TABLE `keystage`
  ADD CONSTRAINT `keystage_ibfk_1` FOREIGN KEY (`lot_id`) REFERENCES `lot` (`lot_id`);

--
-- Constraints for table `logistics_location`
--
ALTER TABLE `logistics_location`
  ADD CONSTRAINT `logistics_location_ibfk_1` FOREIGN KEY (`logistics_id`) REFERENCES `logistics` (`logistic_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `logistics_location_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`warehouse_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `logistic_activities`
--
ALTER TABLE `logistic_activities`
  ADD CONSTRAINT `logistic_activities_ibfk_1` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`delivery_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `lot`
--
ALTER TABLE `lot`
  ADD CONSTRAINT `lot_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`);

--
-- Constraints for table `package`
--
ALTER TABLE `package`
  ADD CONSTRAINT `package_ibfk_1` FOREIGN KEY (`keystage_id`) REFERENCES `keystage` (`keystage_id`);

--
-- Constraints for table `package_content`
--
ALTER TABLE `package_content`
  ADD CONSTRAINT `package_content_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `package_content_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `package` (`package_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `package_status`
--
ALTER TABLE `package_status`
  ADD CONSTRAINT `package_status_ibfk_1` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`delivery_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `package_status_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `package` (`package_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `schools_project`
--
ALTER TABLE `schools_project`
  ADD CONSTRAINT `project_id` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `school` FOREIGN KEY (`school_id`) REFERENCES `school` (`school_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`warehouse_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
