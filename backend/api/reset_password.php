<?php
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// Kiểm tra quyền Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Từ chối truy cập!"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$id = $input['id'] ?? '';
$new_password = $input['new_password'] ?? '';

if (empty($id) || empty($new_password)) {
    echo json_encode(["success" => false, "error" => "Dữ liệu không hợp lệ!"]);
    exit;
}

// Băm mật khẩu mới
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Cập nhật vào Database
$stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "si", $hashed_password, $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true, "message" => "Đã cập nhật mật khẩu mới!"]);
} else {
    echo json_encode(["success" => false, "error" => "Lỗi lưu Database."]);
}
?>