<?php
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// Chỉ manager và admin mới được phân công
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Không có quyền thực hiện"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
$ticket_id = intval($input['ticket_id'] ?? 0);
$staff_id  = intval($input['staff_id'] ?? 0);

if ($ticket_id <= 0 || $staff_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Thiếu mã phiếu hoặc mã nhân viên"]);
    exit;
}

// Kiểm tra nhân viên tồn tại
$chk = mysqli_prepare($conn, "SELECT id, name FROM users WHERE id = ? AND role IN ('staff','manager')");
mysqli_stmt_bind_param($chk, "i", $staff_id);
mysqli_stmt_execute($chk);
$staff = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));

if (!$staff) {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Không tìm thấy kỹ thuật viên"]);
    exit;
}

// Cập nhật ticket: gán nhân viên + chuyển trạng thái sang repairing
$stmt = mysqli_prepare($conn,
    "UPDATE repair_tickets SET user_id = ?, status = 'repairing' WHERE id = ?");
mysqli_stmt_bind_param($stmt, "ii", $staff_id, $ticket_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "success" => true,
        "message" => "Đã giao phiếu #TICK-{$ticket_id} cho {$staff['name']}!"
    ]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Lỗi cập nhật database"]);
}
