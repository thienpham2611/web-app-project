<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Chưa đăng nhập hoặc không có quyền Manager"]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$repair_ticket_id = intval($_POST['repair_ticket_id'] ?? 0);
$quote_amount     = floatval($_POST['quote_amount'] ?? 0);
$note             = trim($_POST['note'] ?? '');

if ($repair_ticket_id <= 0 || $quote_amount <= 0) {
    echo json_encode(["success" => false, "message" => "Phiếu sửa chữa và báo giá là bắt buộc"]);
    exit;
}

// Tạo hoặc cập nhật đơn hàng với báo giá
$stmt = mysqli_prepare($conn, 
    "INSERT INTO orders 
     (repair_ticket_id, customer_id, device_id, quote_amount, total_amount, status, created_at, updated_at)
     SELECT rt.id, rt.customer_id, rt.device_id, ?, ?, 'quoted', NOW(), NOW()
     FROM repair_tickets rt
     WHERE rt.id = ?
     ON DUPLICATE KEY UPDATE 
         quote_amount = VALUES(quote_amount),
         total_amount = VALUES(quote_amount),
         status = 'quoted',
         updated_at = NOW()");

mysqli_stmt_bind_param($stmt, "ddi", $quote_amount, $quote_amount, $repair_ticket_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true, "message" => "Lưu báo giá thành công!"]);
} else {
    echo json_encode(["success" => false, "message" => "Lỗi database: " . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>