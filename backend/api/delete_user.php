<?php
session_name('STAFF_SESSION');
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// 1. KIỂM TRA BẢO MẬT: Chỉ Admin mới được quyền xóa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Từ chối truy cập!"]);
    exit;
}

// 2. Lấy dữ liệu ID từ Fetch gửi lên
$input = json_decode(file_get_contents("php://input"), true);
$id = $input['id'] ?? '';

if (empty($id)) {
    echo json_encode(["success" => false, "error" => "Không tìm thấy ID người dùng!"]);
    exit;
}

// 3. KHÔNG CHO PHÉP ADMIN TỰ XÓA CHÍNH MÌNH (Bảo mật quan trọng)
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
    echo json_encode(["success" => false, "error" => "Bạn không thể tự xóa chính tài khoản đang đăng nhập!"]);
    exit;
}

// 4. THỰC THI LỆNH XÓA
$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true, "message" => "Đã xóa tài khoản thành công!"]);
} else {
    echo json_encode(["success" => false, "error" => "Lỗi Database: " . mysqli_error($conn)]);
}
?>