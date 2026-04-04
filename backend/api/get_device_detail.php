<?php
session_name('STAFF_SESSION');
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','manager','staff'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Chưa đăng nhập"]);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Thiếu mã thiết bị"]);
    exit;
}

// Thông tin thiết bị
$stmt = mysqli_prepare($conn,
    "SELECT d.*, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email
     FROM devices d
     LEFT JOIN customers c ON c.id = d.customer_id
     WHERE d.id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$device = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$device) {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Không tìm thấy thiết bị"]);
    exit;
}

// Lịch sử phiếu sửa chữa
$stmtT = mysqli_prepare($conn,
    "SELECT rt.id, rt.status, rt.description, rt.progress, rt.received_date, u.name AS staff_name
     FROM repair_tickets rt
     LEFT JOIN users u ON u.id = rt.user_id
     WHERE rt.device_id = ?
     ORDER BY rt.id DESC LIMIT 5");
mysqli_stmt_bind_param($stmtT, "i", $id);
mysqli_stmt_execute($stmtT);
$tickets = mysqli_fetch_all(mysqli_stmt_get_result($stmtT), MYSQLI_ASSOC);

// Lịch sử gia hạn
$stmtW = mysqli_prepare($conn,
    "SELECT we.old_end_date, we.new_end_date, we.cost, we.note, we.created_at, u.name AS user_name
     FROM warranty_extensions we
     LEFT JOIN users u ON u.id = we.user_id
     WHERE we.device_id = ?
     ORDER BY we.id DESC LIMIT 5");
mysqli_stmt_bind_param($stmtW, "i", $id);
mysqli_stmt_execute($stmtW);
$extensions = mysqli_fetch_all(mysqli_stmt_get_result($stmtW), MYSQLI_ASSOC);

echo json_encode([
    "success"    => true,
    "device"     => $device,
    "tickets"    => $tickets,
    "extensions" => $extensions
]);
