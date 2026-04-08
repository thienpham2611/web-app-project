<?php
session_name('STAFF_SESSION');
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
$due_date  = trim($input['due_date'] ?? '');

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

// Cập nhật ticket: gán nhân viên + chuyển trạng thái sang repairing + lưu deadline
$assigned_date = date('Y-m-d');
if (!empty($due_date)) {
    $stmt = mysqli_prepare($conn,
        "UPDATE repair_tickets SET user_id = ?, status = 'repairing', assigned_date = ?, due_date = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "issi", $staff_id, $assigned_date, $due_date, $ticket_id);
} else {
    $stmt = mysqli_prepare($conn,
        "UPDATE repair_tickets SET user_id = ?, status = 'repairing', assigned_date = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "isi", $staff_id, $assigned_date, $ticket_id);
}

if (mysqli_stmt_execute($stmt)) {
    // Tạo thông báo "được giao việc" cho nhân viên
    $msg_assign = "🔧 Bạn được giao sửa phiếu #TICK-{$ticket_id}. Thiết bị sẽ được kiểm tra ngay!";
    $stmtN = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmtN, "is", $staff_id, $msg_assign);
    mysqli_stmt_execute($stmtN);

    // Nếu có deadline, tạo thêm thông báo nhắc hạn
    if (!empty($due_date)) {
        $due_fmt = date('d/m/Y', strtotime($due_date));
        $msg_due = "⏰ Phiếu #TICK-{$ticket_id} có deadline: {$due_fmt}. Hãy hoàn thành đúng hạn!";
        $stmtD = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmtD, "is", $staff_id, $msg_due);
        mysqli_stmt_execute($stmtD);
    }

    echo json_encode([
        "success" => true,
        "message" => "Đã giao phiếu #TICK-{$ticket_id} cho {$staff['name']}!"
    ]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Lỗi cập nhật database"]);
}
