<?php
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Chỉ khách hàng đã đăng nhập
if (!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Vui lòng đăng nhập lại"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Phương thức không được phép"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
$device_id = intval($input['device_id'] ?? 0);
$note      = trim($input['note'] ?? '');
$customer_id = intval($_SESSION['customer_id']);

if ($device_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Thiếu mã thiết bị"]);
    exit;
}

// Kiểm tra thiết bị thuộc khách hàng này
$chk = mysqli_prepare($conn, "SELECT id, name, warranty_end_date FROM devices WHERE id = ? AND customer_id = ?");
mysqli_stmt_bind_param($chk, "ii", $device_id, $customer_id);
mysqli_stmt_execute($chk);
$device = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));

if (!$device) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Thiết bị không hợp lệ"]);
    exit;
}

// Gửi thông báo đến tất cả manager
$msg = "📋 Khách hàng yêu cầu gia hạn bảo hành thiết bị: {$device['name']} (Hết hạn: {$device['warranty_end_date']}). Ghi chú: " . ($note ?: "Không có");

$managers = mysqli_query($conn, "SELECT id FROM users WHERE role IN ('admin','manager')");
while ($mgr = mysqli_fetch_assoc($managers)) {
    $ins = mysqli_prepare($conn,
        "INSERT INTO notifications (device_id, user_id, message) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($ins, "iis", $device_id, $mgr['id'], $msg);
    mysqli_stmt_execute($ins);
}

echo json_encode([
    "success" => true,
    "message" => "Đã gửi yêu cầu gia hạn thành công! Chúng tôi sẽ liên hệ với bạn sớm."
]);
