<?php
// Bắt buộc phải gọi session_start() ở đầu file để lưu trạng thái đăng nhập
session_start();
require_once "../config/database.php";

// Thiết lập Header
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Phương thức không được phép"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

// 1. Kiểm tra dữ liệu đầu vào
if ($email === '' || $password === '') {
    http_response_code(400); // 400 Bad Request
    echo json_encode(["success" => false, "error" => "Vui lòng nhập Email và Mật khẩu."]);
    exit;
}

// 2. Truy vấn tìm khách hàng theo email [cite: 13, 14, 25-45]
$stmt = mysqli_prepare($conn, "SELECT id, name, password FROM customers WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$customer = mysqli_fetch_assoc($result);

// 3. Xác thực mật khẩu
// Hàm password_verify sẽ tự động so sánh mật khẩu nhập vào với mã băm (hash) trong database
if ($customer && password_verify($password, $customer['password'])) {
    
    // 4. Lưu thông tin vào Session
    $_SESSION['customer_id'] = $customer['id'];
    $_SESSION['customer_name'] = $customer['name'];
    $_SESSION['role'] = 'customer'; // Gắn role riêng biệt để không nhầm với admin/manager/staff

    http_response_code(200); // 200 OK
    echo json_encode([
        "success" => true, 
        "message" => "Đăng nhập thành công!",
        "data" => [
            "id" => $customer['id'],
            "name" => $customer['name']
        ],
        "redirect_url" => "khachhang.php" // Gợi ý URL để frontend tự chuyển trang
    ]);

} else {
    // Sai email hoặc mật khẩu
    http_response_code(401); // 401 Unauthorized
    echo json_encode(["success" => false, "error" => "Email hoặc mật khẩu không chính xác."]);
}

mysqli_stmt_close($stmt);
?>