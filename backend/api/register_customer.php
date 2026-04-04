<?php
session_name('CUSTOMER_SESSION');
session_start();
require_once "../config/database.php";

// Thiết lập Header
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "error" => "Phương thức không được phép"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
$name     = trim($input['name'] ?? '');
$phone    = trim($input['phone'] ?? '');
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$address  = trim($input['address'] ?? '');


// KIỂM TRA DỮ LIỆU (VALIDATION)
// 1. Kiểm tra rỗng
if ($name === '' || $email === '' || $password === '') {
    echo json_encode(["success" => false, "error" => "Vui lòng nhập đầy đủ Họ tên, Email và Mật khẩu."]);
    exit;
}

// 2. Kiểm tra định dạng Regex cơ bản
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "error" => "Định dạng email không hợp lệ."]);
    exit;
}

// 3. Kiểm tra định dạng đuôi gmail
$domain = strtolower(substr(strrchr($email, "@"), 1));

if ($domain !== 'gmail.com') {
    echo json_encode(["success" => false, "error" => "Nhập sai định dạng gmail. Vui lòng nhập lại."]);
    exit;
}

// 4. Kiểm tra định dạng Số điện thoại (Nếu có nhập)
if (!empty($phone) && !preg_match('/^(0[3|5|7|8|9])+([0-9]{8})$/', $phone)) {
    echo json_encode(["success" => false, "error" => "Số điện thoại không hợp lệ (Phải là 10 số chuẩn Việt Nam)."]);
    exit;
}

// 5. Kiểm tra độ dài mật khẩu
if (strlen($password) < 6) {
    echo json_encode(["success" => false, "error" => "Mật khẩu quá ngắn, vui lòng nhập ít nhất 6 ký tự."]);
    exit;
}

// 6. Kiểm tra email đã tồn tại trong DB chưa
$checkStmt = mysqli_prepare($conn, "SELECT id FROM customers WHERE email = ?");
mysqli_stmt_bind_param($checkStmt, "s", $email);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);

if (mysqli_stmt_num_rows($checkStmt) > 0) {
    echo json_encode(["success" => false, "error" => "Email này đã được đăng ký. Vui lòng sử dụng email khác."]);
    mysqli_stmt_close($checkStmt);
    exit;
}
mysqli_stmt_close($checkStmt);

// LƯU VÀO DATABASE
// 7. Mã hóa mật khẩu an toàn
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 8. Thực thi lưu khách hàng
$insertStmt = mysqli_prepare($conn, "INSERT INTO customers (name, phone, email, password, address) VALUES (?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($insertStmt, "sssss", $name, $phone, $email, $hashedPassword, $address);

if (mysqli_stmt_execute($insertStmt)) {
    echo json_encode([
        "success" => true, 
        "message" => "Đăng ký tài khoản thành công!", 
        "customer_id" => mysqli_insert_id($conn)
    ]);
} else {
    echo json_encode(["success" => false, "error" => "Lỗi hệ thống, không thể đăng ký tài khoản lúc này."]);
}

mysqli_stmt_close($insertStmt);
?>