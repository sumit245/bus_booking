-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 03, 2025 at 08:12 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `redbus`
--

-- --------------------------------------------------------

--
-- Table structure for table `booked_tickets`
--

CREATE TABLE `booked_tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bus_type` varchar(255) DEFAULT NULL,
  `travel_name` varchar(255) DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `arrival_time` time DEFAULT NULL,
  `operator_pnr` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `operator_id` bigint(20) UNSIGNED DEFAULT NULL,
  `operator_booking_id` bigint(20) UNSIGNED DEFAULT NULL,
  `agent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `booking_id` varchar(255) DEFAULT NULL,
  `ticket_no` varchar(255) DEFAULT NULL,
  `gender` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `trip_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `bus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `route_id` bigint(20) UNSIGNED DEFAULT NULL,
  `schedule_id` bigint(20) UNSIGNED DEFAULT NULL,
  `source_destination` varchar(40) DEFAULT NULL,
  `pickup_point` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `boarding_point` varchar(255) DEFAULT NULL,
  `boarding_point_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`boarding_point_details`)),
  `dropping_point` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `dropping_point_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dropping_point_details`)),
  `seats` varchar(255) DEFAULT NULL,
  `seat_numbers` varchar(255) DEFAULT NULL,
  `ticket_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `unit_price` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `sub_total` decimal(28,8) NOT NULL DEFAULT 0.00000000,
  `total_amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `paid_amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `date_of_journey` date DEFAULT NULL,
  `pnr_number` varchar(40) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `payment_status` varchar(255) DEFAULT NULL,
  `booking_type` varchar(255) DEFAULT NULL,
  `booking_source` enum('user','agent','operator') NOT NULL DEFAULT 'user',
  `booking_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cancellation_remarks` text DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `passenger_names` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`passenger_names`)),
  `passenger_phone` varchar(255) DEFAULT NULL,
  `passenger_phones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`passenger_phones`)),
  `passenger_email` varchar(255) DEFAULT NULL,
  `passenger_emails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`passenger_emails`)),
  `passenger_address` varchar(255) DEFAULT NULL,
  `api_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`api_response`)),
  `bus_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bus_details`)),
  `passenger_name` varchar(255) DEFAULT NULL,
  `passenger_age` int(11) DEFAULT NULL,
  `search_token_id` varchar(255) DEFAULT NULL,
  `api_invoice` varchar(255) DEFAULT NULL,
  `cancellation_policy` varchar(255) DEFAULT NULL,
  `api_invoice_amount` varchar(255) DEFAULT NULL,
  `api_invoice_date` varchar(255) DEFAULT NULL,
  `api_booking_id` varchar(255) DEFAULT NULL,
  `api_ticket_no` varchar(255) DEFAULT NULL,
  `agent_commission` varchar(255) DEFAULT NULL,
  `agent_commission_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_commission_charged` decimal(12,2) NOT NULL DEFAULT 0.00,
  `origin_city` varchar(255) DEFAULT NULL,
  `destination_city` varchar(255) DEFAULT NULL,
  `tds_from_api` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booked_tickets`
--

INSERT INTO `booked_tickets` (`id`, `bus_type`, `travel_name`, `departure_time`, `arrival_time`, `operator_pnr`, `user_id`, `operator_id`, `operator_booking_id`, `agent_id`, `booking_id`, `ticket_no`, `gender`, `trip_id`, `bus_id`, `route_id`, `schedule_id`, `source_destination`, `pickup_point`, `boarding_point`, `boarding_point_details`, `dropping_point`, `dropping_point_details`, `seats`, `seat_numbers`, `ticket_count`, `unit_price`, `sub_total`, `total_amount`, `paid_amount`, `date_of_journey`, `pnr_number`, `status`, `payment_status`, `booking_type`, `booking_source`, `booking_reason`, `notes`, `cancellation_remarks`, `cancelled_at`, `created_at`, `updated_at`, `passenger_names`, `passenger_phone`, `passenger_phones`, `passenger_email`, `passenger_emails`, `passenger_address`, `api_response`, `bus_details`, `passenger_name`, `passenger_age`, `search_token_id`, `api_invoice`, `cancellation_policy`, `api_invoice_amount`, `api_invoice_date`, `api_booking_id`, `api_ticket_no`, `agent_commission`, `agent_commission_amount`, `total_commission_charged`, `origin_city`, `destination_city`, `tds_from_api`) VALUES
(191, 'Non AC Seater (2+3)', 'Pankaj Translink (Sutra Seva)', NULL, NULL, 'TS251017025848437013SOJD/34692/RG REWA-SATNA 0730', 51, NULL, NULL, NULL, NULL, NULL, 1, 13, NULL, NULL, NULL, '[\"6664\",\"249\"]', 1, NULL, '{\"CityPointAddress\":\"New Bus Stand Near Samdariya Hotel\",\"CityPointContactNumber\":\"7415354240\",\"CityPointIndex\":1,\"CityPointLandmark\":\"New Bus Stand\",\"CityPointLocation\":\"New Bus Stand\",\"CityPointName\":\"New Bus Stand\",\"CityPointTime\":\"2025-10-31T07:30:00\"}', 1, '{\"CityPointIndex\":1,\"CityPointLocation\":\"Satna Railway Station\",\"CityPointName\":\"Satna Railway Station\",\"CityPointTime\":\"2025-10-31T08:45:00\"}', '\"1\"', NULL, 1, 90.00000000, 90.00000000, 0.00000000, 0.00000000, '2025-10-31', 'GQ149R79YP', 1, NULL, NULL, 'user', NULL, NULL, NULL, NULL, '2025-10-16 15:59:02', '2025-10-16 15:59:02', '\"[\\\"Sumit Ranjan\\\"]\"', '9649240944', NULL, 'sumitranjan245@gmail.com', NULL, 'A967/D\r\nMayur vihar phase 3', '{\"success\":true,\"Result\":{\"BookingStatus\":\"Confirmed\",\"InvoiceAmount\":75.43999999999999772626324556767940521240234375,\"InvoiceNumber\":\"TW\\/2526\\/511\",\"BookingID\":158,\"TicketNo\":\"BAZC6M73\",\"TravelOperatorPNR\":\"TS251017025848437013SOJD\\/34692\\/RG REWA-SATNA 0730\"},\"UserIp\":\"::1\",\"SearchTokenId\":\"276b7d9c50fe53e668ac4fb89660f60ad4d85739\",\"Error\":{\"ErrorCode\":0,\"ErrorMessage\":\"\"}}', '{\"departure_time\":\"10\\/31\\/2025 07:30:00\",\"arrival_time\":\"10\\/31\\/2025 08:45:00\",\"bus_type\":\"Non AC Seater (2+3)\",\"travel_name\":\"Pankaj Translink (Sutra Seva)\"}', 'Sumit Ranjan', 29, '276b7d9c50fe53e668ac4fb89660f60ad4d85739', 'TW/2526/511', '[\"Between 2:57 AM, 17 Oct 2025 to 7:30 PM, 29 Oct 2025 \\u2013 0% charge\",\"Between 7:30 PM, 29 Oct 2025 to 4:30 PM, 30 Oct 2025 \\u2013 0% charge\",\"Between 4:30 PM, 30 Oct 2025 to 8:45 AM, 31 Oct 2025 \\u2013 No refund\"]', '75.44', NULL, '158', 'BAZC6M73', NULL, 0.00, 0.00, NULL, NULL, NULL),
(197, 'Non AC Seater (2+3)', 'Pankaj Translink (Sutra Seva)', NULL, NULL, 'TS251017040020208780BGVM/34699/RG REWA-SATNA 0700', 51, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '\"[\\\"6664\\\",\\\"249\\\"]\"', 1, NULL, '{\"CityPointAddress\":\"New Bus Stand Near Samdariya Hotel\",\"CityPointContactNumber\":\"7415354240\",\"CityPointIndex\":1,\"CityPointLandmark\":\"New Bus Stand\",\"CityPointLocation\":\"New Bus Stand\",\"CityPointName\":\"New Bus Stand\",\"CityPointTime\":\"2025-10-31T07:00:00\"}', 1, '{\"CityPointIndex\":1,\"CityPointLocation\":\"Satna Railway Station\",\"CityPointName\":\"Satna Railway Station\",\"CityPointTime\":\"2025-10-31T08:15:00\"}', '[\"1\"]', NULL, 1, 75.20000000, 80.00000000, 0.00000000, 0.00000000, '2025-10-16', 'WH352AU21Z', 1, NULL, NULL, 'user', NULL, NULL, NULL, NULL, '2025-10-16 17:00:21', '2025-10-16 17:00:59', '[\"Sumit Ranjan\"]', '9649240944', NULL, 'sumitranjan245@gmail.com', NULL, 'A967/D\r\nMayur vihar phase 3', '{\"success\":true,\"Result\":{\"BookingStatus\":\"Confirmed\",\"InvoiceAmount\":75.43999999999999772626324556767940521240234375,\"InvoiceNumber\":\"TW\\/2526\\/515\",\"BookingID\":162,\"TicketNo\":\"BAH9UE9U\",\"TravelOperatorPNR\":\"TS251017040020208780BGVM\\/34699\\/RG REWA-SATNA 0700\"},\"UserIp\":\"::1\",\"SearchTokenId\":\"4b5e225435a2487802fcd7c1f790d4bcb5de85e6\",\"Error\":{\"ErrorCode\":0,\"ErrorMessage\":\"\"}}', NULL, 'Sumit Ranjan', 29, '4b5e225435a2487802fcd7c1f790d4bcb5de85e6', 'TW/2526/515', '[\"Between 3:59 AM, 17 Oct 2025 to 7:00 PM, 29 Oct 2025 \\u2013 0% charge\",\"Between 7:00 PM, 29 Oct 2025 to 4:00 PM, 30 Oct 2025 \\u2013 0% charge\",\"Between 4:00 PM, 30 Oct 2025 to 8:15 AM, 31 Oct 2025 \\u2013 No refund\"]', '75.44', '2025-10-17 04:00:50', '162', 'BAH9UE9U', '4.8', 0.00, 0.00, 'Rewa', 'Satna', '0.24'),
(227, 'Non Ac Seater (2+2)', 'Sutra Seva', '05:48:41', '13:48:41', 'OP_BOOK_1762148950_1', 56, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '\"[\\\"\\\",\\\"\\\"]\"', 1, NULL, '[{\"CityPointIndex\":1,\"CityPointLocation\":\"Kali Pahadi\",\"CityPointName\":\"Kali Pahadi\",\"CityPointTime\":\"2025-11-03T05:48:41\"}]', 1, '[{\"CityPointIndex\":1,\"CityPointLocation\":\"Anand Vihar\",\"CityPointName\":\"Anand Vihar\",\"CityPointTime\":\"2025-11-03T13:48:41\"}]', '[\"1\"]', NULL, 1, 2000.00000000, 2000.00000000, 0.00000000, 0.00000000, '2025-11-03', '6PMQGY5MW6', 1, NULL, NULL, 'user', NULL, NULL, NULL, NULL, '2025-11-03 00:18:41', '2025-11-03 00:19:10', '[\"Asdf surname\"]', '9649240944', NULL, 'guest@vindhyashrisolutions.com', NULL, 'Asdfg', '{\"success\":true,\"Result\":{\"BookingId\":\"OP_BOOK_1762148950_1\",\"TravelOperatorPNR\":\"OP_BOOK_1762148950_1\",\"BookingStatus\":\"Confirmed\",\"InvoiceNumber\":\"OP_INV_1762148950\",\"InvoiceAmount\":1000,\"InvoiceCreatedOn\":\"2025-11-03T05:49:10.344777Z\",\"TicketNo\":\"OP_TKT_1762148950\",\"Origin\":\"Origin City\",\"Destination\":\"Destination City\",\"Price\":{\"AgentCommission\":50,\"TDS\":10}}}', NULL, 'Asdf surname', 20, '4680668e4bad4dc40ec13c47a98e7f2b7d51b8bc', 'OP_INV_1762148950', '[]', '1000', '2025-11-03 05:49:10', 'OP_BOOK_1762148950_1', 'OP_TKT_1762148950', '50', 0.00, 0.00, 'Origin City', 'Destination City', '10'),
(228, 'Non Ac Seater (2+2)', 'Sutra Seva', '06:56:29', '14:56:29', 'OP_BOOK_1762153034_1', 56, 41, 0, NULL, NULL, NULL, 0, 0, 1, 1, NULL, '\"[9292,230]\"', 1, '1', '[{\"CityPointIndex\":1,\"CityPointLocation\":\"Kali Pahadi\",\"CityPointName\":\"Kali Pahadi\",\"CityPointTime\":\"2025-11-03T06:56:29\"}]', 1, '[{\"CityPointIndex\":1,\"CityPointLocation\":\"Anand Vihar\",\"CityPointName\":\"Anand Vihar\",\"CityPointTime\":\"2025-11-03T14:56:29\"}]', '[\"1\"]', NULL, 1, 2000.00000000, 2000.00000000, 2448.50000000, 0.00000000, '2025-11-03', 'ZHKJT65UWV', 1, NULL, NULL, 'user', NULL, NULL, NULL, NULL, '2025-11-03 01:26:29', '2025-11-03 01:27:14', '[\"Pwwo surname\"]', '9649240944', NULL, 'guest@vindhyashrisolutions.com', NULL, 'asdfghj', '{\"success\":true,\"Result\":{\"BookingId\":\"OP_BOOK_1762153034_1\",\"TravelOperatorPNR\":\"OP_BOOK_1762153034_1\",\"BookingStatus\":\"Confirmed\",\"InvoiceNumber\":\"OP_INV_1762153034\",\"InvoiceAmount\":\"2448.50000000\",\"InvoiceCreatedOn\":\"2025-11-03T06:57:14.205830Z\",\"TicketNo\":\"OP_TKT_1762153034\",\"Origin\":\"Patna\",\"Destination\":\"Delhi\",\"Price\":{\"AgentCommission\":\"0.00\",\"TDS\":0}}}', NULL, 'Pwwo surname', 29, '0c5f218de1f2c237b2377d4ff9f9942739ab3c3b', 'OP_INV_1762153034', '[]', '2448.50000000', '2025-11-03 06:57:14', 'OP_BOOK_1762153034_1', 'OP_TKT_1762153034', '0.00', 0.00, 0.00, 'Patna', 'Delhi', '0');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booked_tickets`
--
ALTER TABLE `booked_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booked_tickets_agent_id_index` (`agent_id`),
  ADD KEY `booked_tickets_booking_source_index` (`booking_source`),
  ADD KEY `booked_tickets_booking_source_created_at_index` (`booking_source`,`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booked_tickets`
--
ALTER TABLE `booked_tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=229;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booked_tickets`
--
ALTER TABLE `booked_tickets`
  ADD CONSTRAINT `booked_tickets_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
