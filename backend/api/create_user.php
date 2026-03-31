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

// 1. Kiểm tra định dạng Regex cơ bản (cú pháp hợp lệ)
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "error" => "Định dạng email không hợp lệ."]);
    exit;
}

// 2. DANH SÁCH TRẮNG: Ép buộc đuôi email nội bộ
$domain = strtolower(substr(strrchr($email, "@"), 1));
$allowed_domains = ['gmail.com', 'idtvietnam.vn']; // CHỈ CHO PHÉP 2 ĐUÔI NÀY

if (!in_array($domain, $allowed_domains)) {
    echo json_encode(["success" => false, "error" => "Tài khoản nội bộ chỉ được dùng email @gmail.com hoặc @idtvietnam.vn!"]);
    exit;
}

// 3. Kiểm tra độ dài mật khẩu
if (strlen($password) < 6) {
    echo json_encode(["success" => false, "error" => "Mật khẩu phải có ít nhất 6 ký tự!"]);
    exit;
}

// 4. KIỂM TRA TRÙNG LẶP: Xem email này đã có ai dùng chưa
$stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt_check, "s", $email);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);
if (mysqli_stmt_num_rows($stmt_check) > 0) {
    echo json_encode(["success" => false, "error" => "Email này đã được sử dụng trong hệ thống! Vui lòng chọn email khác."]);
    exit;
}

// 5. BẢO MẬT: Băm (Hash) mật khẩu trước khi lưu vào Database
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 6. THÊM VÀO DATABASE
$stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashed_password, $role);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true, "message" => "Đã tạo tài khoản thành công cho [$name]!"]);
} else {
    echo json_encode(["success" => false, "error" => "Lỗi lưu Database: " . mysqli_error($conn)]);
}
?>