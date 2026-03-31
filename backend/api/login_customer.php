<?php
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Phương thức không được phép"]);
    exit;
}

// Nếu đang có session nhân viên nội bộ → không cho đăng nhập khách hàng
if (isset($_SESSION['user_id']) && in_array($_SESSION['role'] ?? '', ['admin','manager','staff'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Vui lòng đăng xuất tài khoản nội bộ trước"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Vui lòng nhập Email và Mật khẩu."]);
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, name, password FROM customers WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($customer && password_verify($password, $customer['password'])) {
    session_regenerate_id(true);

    // Session khách hàng — dùng customer_id, KHÔNG dùng user_id
    $_SESSION['customer_id']   = $customer['id'];
    $_SESSION['customer_name'] = $customer['name'];
    $_SESSION['role']          = 'customer';

    http_response_code(200);
    echo json_encode([
        "success"      => true,
        "message"      => "Đăng nhập thành công!",
        "data"         => ["id" => $customer['id'], "name" => $customer['name']],
        "redirect_url" => "khachhang.php"
    ]);
} else {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Email hoặc mật khẩu không chính xác."]);
}
