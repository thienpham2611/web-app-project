<?php
require_once "../config/database.php";

// Thiết lập Header để Frontend (React/Vue/JS thuần) có thể gọi API mà không bị lỗi CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Xử lý request OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit; 
}

// Chỉ cho phép phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Phương thức không được phép"]);
    exit;
}

// Nhận dữ liệu từ Frontend (hỗ trợ cả JSON body và Form Data)
$input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
$name     = trim($input['name'] ?? '');
$phone    = trim($input['phone'] ?? '');
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$address  = trim($input['address'] ?? '');

// 1. Kiểm tra các trường dữ liệu bắt buộc
if ($name === '' || $email === '' || $password === '') {
    http_response_code(400); // 400 Bad Request
    echo json_encode(["success" => false, "error" => "Vui lòng nhập đầy đủ Tên, Email và Mật khẩu."]);
    exit;
}

// 2. Kiểm tra định dạng Email hợp lệ
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Định dạng email không hợp lệ."]);
    exit;
}

// 3. Kiểm tra Email đã tồn tại trong database chưa
// (Dù database đã có UNIQUE KEY, nhưng kiểm tra trước giúp trả về thông báo lỗi thân thiện hơn)
$checkStmt = mysqli_prepare($conn, "SELECT id FROM customers WHERE email = ?");
mysqli_stmt_bind_param($checkStmt, "s", $email);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);

if (mysqli_stmt_num_rows($checkStmt) > 0) {
    http_response_code(409); // 409 Conflict
    echo json_encode(["success" => false, "error" => "Email này đã được đăng ký. Vui lòng sử dụng email khác."]);
    mysqli_stmt_close($checkStmt);
    exit;
}
mysqli_stmt_close($checkStmt);

// 4. Mã hóa mật khẩu (Security Best Practice)
// Tuyệt đối không lưu mật khẩu dạng Text thường vào database
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 5. Thêm khách hàng mới vào Database
$insertStmt = mysqli_prepare($conn, "INSERT INTO customers (name, phone, email, password, address) VALUES (?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($insertStmt, "sssss", $name, $phone, $email, $hashedPassword, $address);

if (mysqli_stmt_execute($insertStmt)) {
    http_response_code(201); // 201 Created
    echo json_encode([
        "success" => true, 
        "message" => "Đăng ký tài khoản thành công!", 
        "customer_id" => mysqli_insert_id($conn) // Trả về ID của khách hàng vừa tạo
    ]);
} else {
    http_response_code(500); // 500 Internal Server Error
    echo json_encode(["success" => false, "error" => "Lỗi hệ thống, không thể đăng ký tài khoản lúc này."]);
}

mysqli_stmt_close($insertStmt);
?>