<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

$response = ['success' => false];

if (!isset($_SESSION['customer_id'])) {
    $response['error'] = 'Bạn chưa đăng nhập!';
    echo json_encode($response);
    exit;
}

$customer_id = $_SESSION['customer_id'];
$device_id   = intval($_POST['device_id'] ?? 0);

if ($device_id <= 0) {
    $response['error'] = 'Thiết bị không hợp lệ';
    echo json_encode($response);
    exit;
}

// Chỉ cho phép thêm nếu thiết bị chưa thuộc ai hoặc không phải của bạn
$sql = "UPDATE devices 
        SET customer_id = ? 
        WHERE id = ? 
          AND (customer_id IS NULL OR customer_id != ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $customer_id, $device_id, $customer_id);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    $response['success'] = true;
    $response['message'] = 'Thiết bị đã được thêm vào danh sách của bạn!';
} else {
    $response['error'] = 'Không thể thêm thiết bị (có thể đã thuộc về người khác hoặc không tồn tại).';
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
echo json_encode($response);
?>