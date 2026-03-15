<?php
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// 1. KIỂM TRA BẢO MẬT: Chỉ có Admin mới được quyền gọi API này
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Từ chối truy cập! Chỉ Quản trị viên (Admin) mới có quyền tạo tài khoản."]);
    exit;
}

// 2. Lấy dữ liệu từ Frontend (JS) gửi lên
$input = json_decode(file_get_contents("php://input"), true);
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');
$role = trim($input['role'] ?? '');

// Validate dữ liệu rỗng
if (empty($name) || empty($email) || empty($password) || empty($role)) {
    echo json_encode(["success" => false, "error" => "Vui lòng điền đầy đủ thông tin!"]);
    exit;
}

// 3. KIỂM TRA TRÙNG LẶP: Xem email này đã có ai dùng chưa
$stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt_check, "s", $email);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);
if (mysqli_stmt_num_rows($stmt_check) > 0) {
    echo json_encode(["success" => false, "error" => "Email này đã được sử dụng trong hệ thống! Vui lòng chọn email khác."]);
    exit;
}

// 4. BẢO MẬT: Băm (Hash) mật khẩu trước khi lưu vào Database
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 5. THÊM VÀO DATABASE
$stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashed_password, $role);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true, "message" => "Đã tạo tài khoản thành công cho [$name]!"]);
} else {
    echo json_encode(["success" => false, "error" => "Lỗi lưu Database: " . mysqli_error($conn)]);
}
?>