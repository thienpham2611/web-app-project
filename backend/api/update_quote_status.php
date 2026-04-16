<?php
// KIỂM TRA BẢO MẬT: Đảm bảo chỉ khách hàng mới được gọi API này
session_name('CUSTOMER_SESSION');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập hoặc phiên đã hết hạn.']);
    exit();
}

// Gọi file kết nối CSDL
require_once "../config/database.php"; 

$customerId = $_SESSION['customer_id'];
$ticketId = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
$approvalStatus = isset($_POST['approval_status']) ? $_POST['approval_status'] : '';

// 1. Validate dữ liệu đầu vào
if ($ticketId <= 0 || !in_array($approvalStatus, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit();
}

// 2. Kiểm tra xem Phiếu này có đúng là của Khách hàng này không, và có đang chờ duyệt không
$checkSql = "SELECT id, user_id AS assigned_to, device_name FROM repair_tickets WHERE id = ? AND customer_id = ? AND customer_approval = 'waiting'";
$stmtCheck = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($stmtCheck, "ii", $ticketId, $customerId);
mysqli_stmt_execute($stmtCheck);
$resultCheck = mysqli_stmt_get_result($stmtCheck);

if (mysqli_num_rows($resultCheck) === 0) {
    echo json_encode(['success' => false, 'message' => 'Phiếu yêu cầu không tồn tại, không thuộc quyền sở hữu của bạn, hoặc đã được xử lý.']);
    exit();
}

// Lấy thông tin phiếu để làm nội dung thông báo
$ticketInfo = mysqli_fetch_assoc($resultCheck);
$staffId = $ticketInfo['assigned_to'];
$deviceName = $ticketInfo['device_name'] ? $ticketInfo['device_name'] : 'Thiết bị';

// 3. Tiến hành cập nhật trạng thái
$updateSql = "UPDATE repair_tickets SET customer_approval = ? WHERE id = ?";
$stmtUpdate = mysqli_prepare($conn, $updateSql);
mysqli_stmt_bind_param($stmtUpdate, "si", $approvalStatus, $ticketId);

if (mysqli_stmt_execute($stmtUpdate)) {
    // Nếu khách hàng TỪ CHỐI, cập nhật status của phiếu thành 'cancelled' (Hủy)
    if ($approvalStatus === 'rejected') {
        $cancelSql = "UPDATE repair_tickets SET status = 'cancelled' WHERE id = ?";
        $stmtCancel = mysqli_prepare($conn, $cancelSql);
        mysqli_stmt_bind_param($stmtCancel, "i", $ticketId);
        mysqli_stmt_execute($stmtCancel);
    }

    // ==========================================
    // PHẦN THÊM MỚI: BẮN THÔNG BÁO CHO NỘI BỘ
    // ==========================================
    $statusText = ($approvalStatus === 'approved') ? 'ĐỒNG Ý' : 'TỪ CHỐI';
    $message = "Khách hàng đã $statusText báo giá sửa chữa cho phiếu #TICK-$ticketId ($deviceName).";

    // Bắn thông báo cho Nhân viên được phân công (nếu có)
    if (!empty($staffId)) {
        $notifyStaffSql = "INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)";
        $stmtNotifyStaff = mysqli_prepare($conn, $notifyStaffSql);
        mysqli_stmt_bind_param($stmtNotifyStaff, "is", $staffId, $message);
        mysqli_stmt_execute($stmtNotifyStaff);
    }

    // Bắn thông báo cho tất cả Quản lý (Manager)
    $notifyManagerSql = "INSERT INTO notifications (user_id, message, is_read) SELECT id, ?, 0 FROM users WHERE role = 'manager'";
    $stmtNotifyManager = mysqli_prepare($conn, $notifyManagerSql);
    mysqli_stmt_bind_param($stmtNotifyManager, "s", $message);
    mysqli_stmt_execute($stmtNotifyManager);
    // ==========================================

    echo json_encode(['success' => true, 'message' => 'Cập nhật thành công.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>