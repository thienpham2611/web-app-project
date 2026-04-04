<?php
session_name('STAFF_SESSION');
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Không có quyền thực hiện"]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$repair_ticket_id = intval($_POST['repair_ticket_id'] ?? 0);
$customer_id      = intval($_POST['customer_id'] ?? 0);
$total_amount     = floatval($_POST['total_amount'] ?? 0);

if ($customer_id <= 0 || $total_amount <= 0) {
    echo json_encode(["success" => false, "message" => "Khách hàng và tổng tiền là bắt buộc"]);
    exit;
}

$stmt = mysqli_prepare($conn, 
    "INSERT INTO orders 
     (repair_ticket_id, customer_id, device_id, quote_amount, total_amount, status, created_at, updated_at)
     VALUES (?, ?, 
             (SELECT device_id FROM repair_tickets WHERE id = ?),
             ?, ?, 'confirmed', NOW(), NOW())");

mysqli_stmt_bind_param($stmt, "iiidd", 
    $repair_ticket_id, $customer_id, $repair_ticket_id, 
    $total_amount, $total_amount);

if (mysqli_stmt_execute($stmt)) {
    $order_id = mysqli_insert_id($conn);
    echo json_encode([
        "success" => true, 
        "message" => "Tạo đơn hàng #" . $order_id . " thành công!",
        "order_id" => $order_id
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Lỗi database: " . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>