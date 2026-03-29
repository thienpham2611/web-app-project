-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th3 29, 2026 lúc 06:52 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `device_management`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `password`, `address`, `created_at`, `updated_at`) VALUES
(1, 'customer1', '0934567890', 'customer1@gmail.com', '$2y$10$J5lyUr5D3qzG/0OZvSnByuh6B4BWtew1WdCiuIyfuEinZ.z8QtZpa', 'Phan Boi Chau, Thong Nhat, Dong Nai', '2026-03-07 02:42:55', '2026-03-16 03:40:38'),
(2, 'Lê Đình Bảo Duy', '', 'bpoduy2@gmail.com', '$2y$10$KaLJ4rdsyiar9lbd42LnVeaioeQhBI51kmTDbIyoFU10abkBVZUla', '', '2026-03-07 03:16:39', '2026-03-07 03:16:39');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `type` enum('hardware','software') NOT NULL,
  `warranty_start_date` date DEFAULT NULL,
  `warranty_end_date` date DEFAULT NULL,
  `status` enum('active','expired','repairing') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `devices`
--

INSERT INTO `devices` (`id`, `name`, `serial_number`, `customer_id`, `type`, `warranty_start_date`, `warranty_end_date`, `status`, `created_at`, `updated_at`) VALUES
(2, 'Laptop Dell XPS 15', 'IDT-2024-001', 1, 'hardware', '2024-01-01', '2026-12-31', 'active', '2026-03-29 02:20:23', '2026-03-29 02:20:23'),
(3, 'Phần mềm Quản lý Kho', 'SOFT-8899', 1, 'software', '2025-04-01', '2026-05-15', 'active', '2026-03-29 02:20:23', '2026-03-29 02:20:23'),
(4, 'Máy in HP Laser', 'HP-009922', 1, 'hardware', '2022-01-01', '2024-01-01', 'expired', '2026-03-29 02:20:23', '2026-03-29 02:20:23'),
(5, 'MacBook Pro M3', 'MAC-IDT-001', 2, 'hardware', '2025-01-01', '2026-01-01', 'expired', '2026-03-29 04:21:48', '2026-03-29 04:25:35'),
(6, 'Máy in Canon LBP2900', 'CAN-IDT-002', 2, 'hardware', '2024-05-20', '2025-05-20', 'expired', '2026-03-29 04:21:48', '2026-03-29 04:25:09'),
(7, 'Màn hình Dell UltraSharp', 'DEL-IDT-003', 2, 'hardware', '2026-03-10', '2027-03-10', 'active', '2026-03-29 04:21:48', '2026-03-29 04:21:48');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `repair_logs`
--

CREATE TABLE `repair_logs` (
  `id` int(11) NOT NULL,
  `repair_ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `repair_tickets`
--

CREATE TABLE `repair_tickets` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','repairing','completed','cancelled') DEFAULT 'pending',
  `progress` int(3) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `repair_tickets`
--

INSERT INTO `repair_tickets` (`id`, `device_id`, `customer_id`, `user_id`, `received_date`, `description`, `status`, `progress`, `created_at`, `updated_at`) VALUES
(3, 4, 1, 6, '2026-03-15', 'Máy thỉnh thoảng bị xanh màn hình, cần kiểm tra RAM và cài lại Win.', 'repairing', 65, '2026-03-29 02:54:07', '2026-03-29 02:54:07'),
(4, 4, 1, 6, '2026-03-15', 'Máy thỉnh thoảng bị xanh màn hình, cần kiểm tra RAM và cài lại Win.', 'repairing', 65, '2026-03-29 02:55:04', '2026-03-29 02:55:04'),
(5, 2, 1, NULL, '2026-03-29', 'Bị đơ máy', 'pending', 0, '2026-03-29 03:13:36', '2026-03-29 03:13:36'),
(6, 3, 1, NULL, '2026-03-29', 'Hay lag', 'pending', 0, '2026-03-29 03:18:06', '2026-03-29 03:18:06'),
(7, 7, 2, NULL, '2026-03-29', 'Màn hình bị sét đánh cháy khét lèn lẹt', 'pending', 0, '2026-03-29 04:32:48', '2026-03-29 04:32:48');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(4, 'Admin', 'admin@gmail.com', '$2y$10$3vVTcxrKExF622yp9HJ.9O0rKlAJSTX2Kgfy1eUr10gsjtqg74LUK', 'admin', '2026-02-05 14:08:52', '2026-03-16 03:41:09'),
(5, 'Manager Test', 'manager@gmail.com', '$2y$10$3dixkaUpFZeQk5ICAjqbEebnf6Mc8zi9hT8NzRSIVEqq4cSblViHi', 'manager', '2026-02-05 18:53:04', '2026-03-16 03:41:44'),
(6, 'Staff Test', 'staff@gmail.com', '$2y$10$aNKcSalp7qdb/OZYuhhaCOOwfWPpCnhoF/ub9Zq8F9M5mLyz8pEw2', 'staff', '2026-02-05 18:53:05', '2026-03-16 03:42:10'),
(8, 'Test', 'Test@gmail.com', '$2y$10$Q0fIXjdl3aVbza1yWmkwz.RksGeS54dMkX7v0qicrj/0QjQ322izS', 'manager', '2026-03-15 15:36:02', '2026-03-15 15:36:02');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `warranty_extensions`
--

CREATE TABLE `warranty_extensions` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `old_end_date` date DEFAULT NULL,
  `new_end_date` date DEFAULT NULL,
  `cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `warranty_extensions`
--

INSERT INTO `warranty_extensions` (`id`, `device_id`, `user_id`, `old_end_date`, `new_end_date`, `cost`, `note`, `created_at`) VALUES
(2, 4, 6, '2025-12-31', '2026-12-31', 500000.00, 'Khách hàng mua thêm gói bảo hành mở rộng 1 năm.', '2026-03-29 02:55:04');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_devices_customer` (`customer_id`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notify_device` (`device_id`),
  ADD KEY `fk_notify_user` (`user_id`);

--
-- Chỉ mục cho bảng `repair_logs`
--
ALTER TABLE `repair_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_ticket` (`repair_ticket_id`),
  ADD KEY `fk_log_user` (`user_id`);

--
-- Chỉ mục cho bảng `repair_tickets`
--
ALTER TABLE `repair_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_repair_device` (`device_id`),
  ADD KEY `fk_repair_customer` (`customer_id`),
  ADD KEY `fk_repair_user` (`user_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `warranty_extensions`
--
ALTER TABLE `warranty_extensions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_warranty_device` (`device_id`),
  ADD KEY `fk_warranty_user` (`user_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `repair_logs`
--
ALTER TABLE `repair_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `repair_tickets`
--
ALTER TABLE `repair_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `warranty_extensions`
--
ALTER TABLE `warranty_extensions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `devices`
--
ALTER TABLE `devices`
  ADD CONSTRAINT `fk_devices_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notify_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notify_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `repair_logs`
--
ALTER TABLE `repair_logs`
  ADD CONSTRAINT `fk_log_ticket` FOREIGN KEY (`repair_ticket_id`) REFERENCES `repair_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `repair_tickets`
--
ALTER TABLE `repair_tickets`
  ADD CONSTRAINT `fk_repair_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_repair_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`),
  ADD CONSTRAINT `fk_repair_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `warranty_extensions`
--
ALTER TABLE `warranty_extensions`
  ADD CONSTRAINT `fk_warranty_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`),
  ADD CONSTRAINT `fk_warranty_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
