<?php
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// 1. Kiểm tra xác thực (Chỉ khách hàng mới được tạo)
if (!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(["success" => false, "error" => "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại!"]);
    exit;
}

$customer_id = $_SESSION['customer_id'];

// 2. Nhận dữ liệu
$device_id = intval($_POST['device_id'] ?? 0);
$description = trim($_POST['description'] ?? '');

if ($device_id <= 0 || empty($description)) {
    echo json_encode(["success" => false, "error" => "Vui lòng cung cấp đầy đủ thông tin thiết bị và mô tả lỗi."]);
    exit;
}

// 3. Bảo mật: Kiểm tra xem thiết bị này có thuộc về khách hàng đang đăng nhập không
$checkStmt = mysqli_prepare($conn, "SELECT id FROM devices WHERE id = ? AND customer_id = ?");
mysqli_stmt_bind_param($checkStmt, "ii", $device_id, $customer_id);
mysqli_stmt_execute($checkStmt);
$checkRes = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($checkRes) === 0) {
    echo json_encode(["success" => false, "error" => "Thiết bị không tồn tại hoặc không thuộc quyền sở hữu của bạn."]);
    exit;
}

// 4. Thêm phiếu sửa chữa vào Database
$received_date = date('Y-m-d');
$status = 'pending'; // Trạng thái mặc định là Chờ xử lý
$progress = 0;       // Tiến độ 0%

$stmt = mysqli_prepare($conn, 
    "INSERT INTO repair_tickets (device_id, customer_id, received_date, description, status, progress) 
     VALUES (?, ?, ?, ?, ?, ?)"
);

mysqli_stmt_bind_param($stmt, "iisssi", $device_id, $customer_id, $received_date, $description, $status, $progress);

if (mysqli_stmt_execute($stmt)) {
    $ticket_id = mysqli_insert_id($conn);
    echo json_encode([
        "success" => true,
        "message" => "Gửi yêu cầu sửa chữa thành công!",
        "ticket_id" => $ticket_id
    ]);
} else {
    echo json_encode(["success" => false, "error" => "Lỗi hệ thống, không thể tạo phiếu lúc này."]);
}
?>