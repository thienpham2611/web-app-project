<?php
require_once "../config/database.php";
$requiredRoles = ['admin', 'manager', 'staff'];
require_once "../middleware/check_auth.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Phương thức không được phép"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';
$user_id = $_SESSION['user_id'];

// ── ACTION 1: Cập nhật tên ────────────────────────────────────────────────
if ($action === 'update_name') {
    $name = trim($data['name'] ?? '');
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Tên không được để trống"]);
        exit;
    }
    if (mb_strlen($name) > 100) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Tên không được quá 100 ký tự"]);
        exit;
    }

    $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $name, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['name'] = $name; // cập nhật session ngay
        echo json_encode(["success" => true, "message" => "Cập nhật tên thành công", "name" => $name]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Lỗi cập nhật CSDL"]);
    }
    exit;
}

// ── ACTION 2: Đổi mật khẩu ───────────────────────────────────────────────
if ($action === 'change_password') {
    $current_password  = $data['current_password']  ?? '';
    $new_password      = $data['new_password']      ?? '';
    $confirm_password  = $data['confirm_password']  ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Vui lòng điền đầy đủ các trường mật khẩu"]);
        exit;
    }
    if ($new_password !== $confirm_password) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Mật khẩu mới và xác nhận không khớp"]);
        exit;
    }
    if (mb_strlen($new_password) < 6) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Mật khẩu mới phải có ít nhất 6 ký tự"]);
        exit;
    }

    // Lấy hash hiện tại
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if (!$user || !password_verify($current_password, $user['password'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "error" => "Mật khẩu hiện tại không đúng"]);
        exit;
    }

    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt2 = mysqli_prepare($conn, "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, "si", $new_hash, $user_id);
    if (mysqli_stmt_execute($stmt2)) {
        echo json_encode(["success" => true, "message" => "Đổi mật khẩu thành công"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Lỗi cập nhật CSDL"]);
    }
    exit;
}

// Action không hợp lệ
http_response_code(400);
echo json_encode(["success" => false, "error" => "Action không hợp lệ"]);
