<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Chưa đăng nhập hoặc không có quyền Manager"]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$id                = intval($_POST['id'] ?? 0);
$name              = trim($_POST['name'] ?? '');
$serial_number     = trim($_POST['serial_number'] ?? '');
$customer_id       = intval($_POST['customer_id'] ?? 0);
$warranty_end_date = $_POST['warranty_end_date'] ?? null;
$type              = $_POST['type'] ?? 'hardware';
$status            = $_POST['status'] ?? 'active';

if (empty($name) || empty($serial_number)) {
    echo json_encode(["success" => false, "message" => "Tên mặt hàng và Mã thiết bị (S/N) là bắt buộc"]);
    exit;
}

if ($id > 0) {
    // === CHỈNH SỬA ===
    $stmt = mysqli_prepare($conn, 
        "UPDATE devices 
         SET name = ?, serial_number = ?, customer_id = ?, 
             warranty_end_date = ?, type = ?, status = ?, updated_at = NOW()
         WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ssisssi", 
        $name, $serial_number, $customer_id, 
        $warranty_end_date, $type, $status, $id);
} else {
    // === THÊM MỚI ===
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO devices 
         (name, serial_number, customer_id, type, warranty_end_date, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    mysqli_stmt_bind_param($stmt, "ssisss", 
        $name, $serial_number, $customer_id, 
        $type, $warranty_end_date, $status);
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "success" => true, 
        "message" => $id > 0 ? "Cập nhật thiết bị thành công!" : "Thêm mới thiết bị thành công!"
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Lỗi database: " . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>