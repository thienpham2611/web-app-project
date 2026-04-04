<?php
session_name('STAFF_SESSION');
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Chưa đăng nhập hoặc không có quyền"]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Thiếu ID thiết bị"]);
    exit;
}

$stmt = mysqli_prepare($conn, 
    "SELECT id, name, serial_number, customer_id, type, 
            warranty_start_date, warranty_end_date, status 
     FROM devices 
     WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$device = mysqli_fetch_assoc($result);

if (!$device) {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Không tìm thấy thiết bị"]);
    exit;
}

echo json_encode(["success" => true, "device" => $device]);
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>