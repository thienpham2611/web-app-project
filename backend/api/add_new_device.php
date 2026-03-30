<?php
ini_set('session.cookie_path', '/');
session_start();

header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = ['success' => false, 'error' => ''];

if (!isset($_SESSION['customer_id'])) {
    $response['error'] = 'Bạn chưa đăng nhập. Vui lòng đăng nhập lại.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Lấy dữ liệu từ form
$name                = trim($_POST['name'] ?? '');
$serial_number       = trim($_POST['serial_number'] ?? '');
$warranty_start_date = !empty($_POST['warranty_start_date']) ? $_POST['warranty_start_date'] : null;
$warranty_period     = intval($_POST['warranty_period'] ?? 0);

if (empty($name) || empty($serial_number)) {
    $response['error'] = 'Vui lòng nhập Tên thiết bị và Số serial (S/N).';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Tính hạn bảo hành
$warranty_end_date = null;
if ($warranty_start_date && $warranty_period > 0) {
    $date = new DateTime($warranty_start_date);
    $date->modify("+{$warranty_period} days");
    $warranty_end_date = $date->format('Y-m-d');
}

// SQL đã bỏ cột description
$sql = "INSERT INTO devices (customer_id, name, serial_number, warranty_start_date, warranty_end_date, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    $response['error'] = 'Lỗi chuẩn bị SQL: ' . mysqli_error($conn);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_stmt_bind_param($stmt, "issss", $customer_id, $name, $serial_number, $warranty_start_date, $warranty_end_date);

if (mysqli_stmt_execute($stmt)) {
    $response['success'] = true;
    $response['message'] = 'Thiết bị mới đã được thêm thành công!';
} else {
    $response['error'] = 'Lỗi khi thêm thiết bị: ' . mysqli_error($conn);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>