-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th4 08, 2026 lúc 03:02 PM
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
(2, 'Lê Đình Bảo Duy', '0934567890', 'bpoduy2@gmail.com', '$2y$10$KaLJ4rdsyiar9lbd42LnVeaioeQhBI51kmTDbIyoFU10abkBVZUla', '', '2026-03-07 03:16:39', '2026-04-04 03:36:05'),
(16, 'Test3', '', 'Test3@gmail.com', '$2y$10$6GlEswWgx7QMwlWyn47w9esPP5UyHwq3aMhl19l1hORK24LiydrMW', '', '2026-04-04 04:10:40', '2026-04-04 04:10:40');

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
(2, 'Laptop Dell XPS 15', 'IDT-2024-001', 1, 'hardware', '2024-01-01', '2026-07-30', 'active', '2026-03-29 02:20:23', '2026-04-04 11:35:49'),
(3, 'Phần mềm Quản lý Kho', 'SOFT-8899', 1, 'software', '2025-04-01', '2026-05-15', 'active', '2026-03-29 02:20:23', '2026-03-29 02:20:23'),
(4, 'Máy in HP Laser', 'HP-009922', 1, 'hardware', '2022-01-01', '2024-01-01', 'active', '2026-03-29 02:20:23', '2026-04-04 02:08:12'),
(5, 'MacBook Pro M3', 'MAC-IDT-001', 2, 'hardware', '2025-01-01', '2026-01-01', 'expired', '2026-03-29 04:21:48', '2026-03-29 04:25:35'),
(6, 'Máy in Canon LBP2900', 'CAN-IDT-002', 2, 'hardware', '2024-05-20', '2025-05-20', 'expired', '2026-03-29 04:21:48', '2026-03-29 04:25:09'),
(7, 'Màn hình Dell UltraSharp', 'DEL-IDT-003', 2, 'hardware', '2026-03-10', '2027-03-10', 'active', '2026-03-29 04:21:48', '2026-03-29 04:21:48'),
(8, 'Thanh RAM 16GB', 'RAM-01211025', 1, 'hardware', NULL, '2027-04-01', 'active', '2026-04-04 11:52:48', '2026-04-04 11:52:48'),
(9, 'Test123', '021155011', 1, 'hardware', '2026-04-08', '2026-04-08', 'expired', '2026-04-08 04:21:09', '2026-04-08 04:21:09'),
(10, 'Test1234', '123456', 1, 'hardware', '2026-04-08', '2026-04-08', 'expired', '2026-04-08 04:24:43', '2026-04-08 04:24:43');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `payment_status` enum('unpaid','paid') DEFAULT 'unpaid',
  `printed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Đang đổ dữ liệu cho bảng `notifications`
--

INSERT INTO `notifications` (`id`, `device_id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 4, '⚠️ Thiết bị \'Phần mềm Quản lý Kho\' (KH: customer1) sắp hết hạn bảo hành (còn 41 ngày) vào ngày 15/05/2026.', 0, '2026-04-04 02:07:01'),
(2, 3, 5, '⚠️ Thiết bị \'Phần mềm Quản lý Kho\' (KH: customer1) sắp hết hạn bảo hành (còn 41 ngày) vào ngày 15/05/2026.', 0, '2026-04-04 02:07:01'),
(3, 3, 8, '⚠️ Thiết bị \'Phần mềm Quản lý Kho\' (KH: customer1) sắp hết hạn bảo hành (còn 41 ngày) vào ngày 15/05/2026.', 0, '2026-04-04 02:07:01'),
(4, 2, 4, '⚠️ Thiết bị \'Laptop Dell XPS 15\' (KH: customer1) sắp hết hạn bảo hành (còn 87 ngày) vào ngày 30/06/2026.', 0, '2026-04-04 02:41:37'),
(5, 2, 5, '⚠️ Thiết bị \'Laptop Dell XPS 15\' (KH: customer1) sắp hết hạn bảo hành (còn 87 ngày) vào ngày 30/06/2026.', 0, '2026-04-04 02:41:37'),
(6, 2, 8, '⚠️ Thiết bị \'Laptop Dell XPS 15\' (KH: customer1) sắp hết hạn bảo hành (còn 87 ngày) vào ngày 30/06/2026.', 0, '2026-04-04 02:41:37'),
(7, 3, 4, '⚠️ Thiết bị \'Phần mềm Quản lý Kho\' (KH: customer1) sắp hết hạn bảo hành (còn 40 ngày) vào ngày 15/05/2026.', 0, '2026-04-05 01:10:38'),
(8, 3, 5, '⚠️ Thiết bị \'Phần mềm Quản lý Kho\' (KH: customer1) sắp hết hạn bảo hành (còn 40 ngày) vào ngày 15/05/2026.', 0, '2026-04-05 01:10:38'),
(9, 3, 8, '⚠️ Thiết bị \'Phần mềm Quản lý Kho\' (KH: customer1) sắp hết hạn bảo hành (còn 40 ngày) vào ngày 15/05/2026.', 0, '2026-04-05 01:10:38'),
(10, 3, 4, '⚠️ Thiết bị \'Phần mềm Quản lý Kho\' (KH: customer1) sắp hết hạn bảo hành (còn 37 ngày) vào ngày 15/05/2026.', 0, '2026-04-08 02:06:47'),
(11, 3, 5, '⚠️ Thiết bị \'Phần mềm Quản lý Kho\' (KH: customer1) sắp hết hạn bảo hành (còn 37 ngày) vào ngày 15/05/2026.', 0, '2026-04-08 02:06:47'),
(12, 3, 8, '⚠️ Thiết bị \'Phần mềm Quản lý Kho\' (KH: customer1) sắp hết hạn bảo hành (còn 37 ngày) vào ngày 15/05/2026.', 0, '2026-04-08 02:06:47'),
(13, NULL, 6, '🔧 Bạn được giao sửa phiếu #TICK-11. Thiết bị sẽ được kiểm tra ngay!', 0, '2026-04-08 12:51:57'),
(14, NULL, 6, '⏰ Phiếu #TICK-11 có deadline: 22/04/2026. Hãy hoàn thành đúng hạn!', 0, '2026-04-08 12:51:57');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `repair_ticket_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL,
  `quote_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','quoted','confirmed','paid','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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

--
-- Đang đổ dữ liệu cho bảng `repair_logs`
--

INSERT INTO `repair_logs` (`id`, `repair_ticket_id`, `user_id`, `action`, `note`, `created_at`) VALUES
(1, 7, 6, 'Cập nhật tiến độ: 75% (Trạng thái: repairing)', 'Màn hình đã khởi động lại được', '2026-04-08 02:52:18'),
(2, 5, 6, 'Cập nhật tiến độ: 30% (Trạng thái: repairing)', 'Đang nhận linh kiện', '2026-04-08 05:11:04'),
(3, 7, 6, 'Cập nhật tiến độ: 90% (Trạng thái: repairing)', 'Đang lau chùi và đóng gói', '2026-04-08 08:42:25'),
(4, 5, 6, 'Cập nhật tiến độ: 60% (Trạng thái: repairing)', 'Màn hình đã lên', '2026-04-08 10:19:09'),
(5, 6, 6, 'Cập nhật tiến độ: 20% (Trạng thái: repairing)', 'Test', '2026-04-08 12:43:36');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `repair_tickets`
--

CREATE TABLE `repair_tickets` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','repairing','completed','cancelled') DEFAULT 'pending',
  `progress` int(3) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `repair_tickets`
--

INSERT INTO `repair_tickets` (`id`, `device_id`, `customer_id`, `assigned_to`, `user_id`, `received_date`, `assigned_date`, `due_date`, `description`, `status`, `progress`, `created_at`, `updated_at`) VALUES
(4, 4, 1, 6, 6, '2026-03-15', NULL, NULL, 'Máy thỉnh thoảng bị xanh màn hình, cần kiểm tra RAM và cài lại Win.', 'completed', 100, '2026-03-29 02:55:04', '2026-04-08 05:31:05'),
(5, 2, 1, 6, 6, '2026-03-29', NULL, NULL, 'Bị đơ máy', 'repairing', 60, '2026-03-29 03:13:36', '2026-04-08 10:19:09'),
(6, 3, 1, NULL, 6, '2026-03-29', NULL, NULL, 'Hay lag', 'repairing', 20, '2026-03-29 03:18:06', '2026-04-08 12:43:35'),
(7, 7, 2, 6, 6, '2026-03-29', NULL, NULL, 'Màn hình bị sét đánh cháy khét lèn lẹt', 'repairing', 90, '2026-03-29 04:32:48', '2026-04-08 08:42:25'),
(10, 8, 1, 6, 6, '2026-04-05', NULL, NULL, 'RAM cháy', 'completed', 100, '2026-04-05 04:06:09', '2026-04-08 05:31:52'),
(11, 9, 1, NULL, 6, '2026-04-08', '2026-04-08', '2026-04-22', 'Test', 'repairing', 0, '2026-04-08 04:21:09', '2026-04-08 12:51:57'),
(12, 10, 1, NULL, NULL, '2026-04-08', NULL, NULL, 'test', 'pending', 0, '2026-04-08 04:24:43', '2026-04-08 04:24:43');

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
(8, 'Test', 'Test@gmail.com', '$2y$10$Q0fIXjdl3aVbza1yWmkwz.RksGeS54dMkX7v0qicrj/0QjQ322izS', 'manager', '2026-03-15 15:36:02', '2026-03-15 15:36:02'),
(11, 'Test5', 'abc@gmail.com', '$2y$10$U7DISlyB8HS7kD9AD47cZObwjib6kVYKCOV..MaBijxxiRC.aiJ7y', 'staff', '2026-04-05 02:08:09', '2026-04-05 02:08:16');

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
-- Chỉ mục cho bảng `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `fk_invoice_order` (`order_id`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notify_device` (`device_id`),
  ADD KEY `fk_notify_user` (`user_id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_repair` (`repair_ticket_id`),
  ADD KEY `fk_order_customer` (`customer_id`),
  ADD KEY `fk_order_device` (`device_id`);

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
  ADD KEY `fk_repair_user` (`user_id`),
  ADD KEY `fk_assigned_staff` (`assigned_to`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `repair_logs`
--
ALTER TABLE `repair_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `repair_tickets`
--
ALTER TABLE `repair_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
-- Các ràng buộc cho bảng `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoice_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notify_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notify_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_order_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_repair` FOREIGN KEY (`repair_ticket_id`) REFERENCES `repair_tickets` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `fk_assigned_staff` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
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
