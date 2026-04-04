<?php
session_name('CUSTOMER_SESSION');
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// Chỉ cho phép khách hàng đã đăng nhập gọi API này
if (!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(["success" => false, "error" => "Vui lòng đăng nhập lại"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$name    = trim($input['name'] ?? '');
$phone   = trim($input['phone'] ?? '');
$address = trim($input['address'] ?? '');

if (empty($name)) {
    echo json_encode(["success" => false, "error" => "Họ tên không được để trống"]);
    exit;
}

// Cập nhật vào Database (Bỏ check affected_rows để tránh báo lỗi khi khách lưu mà không sửa gì)
$stmt = mysqli_prepare($conn, "UPDATE customers SET name=?, phone=?, address=? WHERE id=?");
mysqli_stmt_bind_param($stmt, "sssi", $name, $phone, $address, $_SESSION['customer_id']);

if (mysqli_stmt_execute($stmt)) {
    // Cập nhật lại Session tên để Navbar đổi tên theo ngay lập tức
    $_SESSION['customer_name'] = $name;
    echo json_encode(["success" => true, "message" => "Cập nhật hồ sơ thành công!"]);
} else {
    echo json_encode(["success" => false, "error" => "Có lỗi xảy ra, vui lòng thử lại."]);
}
?>