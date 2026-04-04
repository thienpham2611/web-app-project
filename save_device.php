<?php
session_name('STAFF_SESSION');
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Auth: admin và manager
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Không có quyền thực hiện"]);
    exit;
}

$id                  = intval($_POST['id'] ?? 0);
$name                = trim($_POST['name'] ?? '');
$serial_number       = trim($_POST['serial_number'] ?? '');
$customer_id         = intval($_POST['customer_id'] ?? 0) ?: null;
$warranty_start_date = !empty($_POST['warranty_start_date']) ? $_POST['warranty_start_date'] : null;
$warranty_end_date   = !empty($_POST['warranty_end_date'])   ? $_POST['warranty_end_date']   : null;
$type                = in_array($_POST['type'] ?? '', ['hardware', 'software']) ? $_POST['type'] : 'hardware';
$status              = in_array($_POST['status'] ?? '', ['active', 'expired', 'repairing']) ? $_POST['status'] : 'active';

if (empty($name) || empty($serial_number)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Tên thiết bị và Số serial (S/N) là bắt buộc"]);
    exit;
}

if ($id > 0) {
    // UPDATE
    $stmt = mysqli_prepare($conn,
        "UPDATE devices
         SET name=?, serial_number=?, customer_id=?, warranty_start_date=?,
             warranty_end_date=?, type=?, status=?, updated_at=NOW()
         WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssissssi",
        $name, $serial_number, $customer_id,
        $warranty_start_date, $warranty_end_date, $type, $status, $id);
    $msg = "Cập nhật thiết bị thành công!";
} else {
    // INSERT
    $stmt = mysqli_prepare($conn,
        "INSERT INTO devices (name, serial_number, customer_id, type, warranty_start_date, warranty_end_date, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    mysqli_stmt_bind_param($stmt, "ssissss",
        $name, $serial_number, $customer_id,
        $type, $warranty_start_date, $warranty_end_date, $status);
    $msg = "Thêm thiết bị thành công!";
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true, "message" => $msg]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Lỗi database: " . mysqli_error($conn)]);
}
mysqli_stmt_close($stmt);
