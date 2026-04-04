<?php
session_name('STAFF_SESSION');
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// Auth: tất cả nhân viên nội bộ
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'staff'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Chưa đăng nhập"]);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Thiếu ID phiếu"]);
    exit;
}

$stmt = mysqli_prepare($conn,
    "SELECT rt.id, c.name AS customer_name, d.name AS device_name
     FROM repair_tickets rt
     JOIN customers c ON rt.customer_id = c.id
     JOIN devices d ON rt.device_id = d.id
     WHERE rt.id = ?
     LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($row) {
    echo json_encode(["success" => true, "data" => $row]);
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Không tìm thấy phiếu"]);
}
