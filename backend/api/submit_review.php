<?php
// Bật session với tên giống bên khachhang.php
session_name('CUSTOMER_SESSION');
session_start();

// Đặt header trả về dạng JSON
header('Content-Type: application/json');

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'error' => 'Vui lòng đăng nhập lại để thực hiện thao tác.']);
    exit();
}

// 2. Kết nối Database
require_once "../config/database.php"; 

// 3. Lấy dữ liệu JSON từ JS gửi lên
$data = json_decode(file_get_contents('php://input'), true);

$ticket_id = isset($data['ticket_id']) ? intval($data['ticket_id']) : 0;
$rating = isset($data['rating']) ? intval($data['rating']) : 0;
$comment = isset($data['comment']) ? trim($data['comment']) : '';
$customer_id = $_SESSION['customer_id'];

// Kiểm tra tính hợp lệ của dữ liệu
if ($ticket_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Dữ liệu đánh giá không hợp lệ.']);
    exit();
}

// 4. Kiểm tra quyền sở hữu và trạng thái phiếu
// Khách chỉ được đánh giá phiếu của chính mình và phiếu đó phải ở trạng thái "completed"
$stmt_check = mysqli_prepare($conn, "SELECT rt.status, rt.assigned_to, c.name as customer_name FROM repair_tickets rt JOIN customers c ON rt.customer_id = c.id WHERE rt.id = ? AND rt.customer_id = ?");
mysqli_stmt_bind_param($stmt_check, "ii", $ticket_id, $customer_id);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);

if (mysqli_num_rows($result_check) === 0) {
    echo json_encode(['success' => false, 'error' => 'Không tìm thấy phiếu sửa chữa hoặc bạn không có quyền truy cập.']);
    exit();
}

$ticket = mysqli_fetch_assoc($result_check);
if ($ticket['status'] !== 'completed') {
    echo json_encode(['success' => false, 'error' => 'Bạn chỉ có thể đánh giá khi phiếu sửa chữa đã hoàn thành.']);
    exit();
}

// 5. Kiểm tra xem phiếu này đã được đánh giá trước đó chưa (Chống spam)
$stmt_rev_check = mysqli_prepare($conn, "SELECT id FROM repair_reviews WHERE repair_ticket_id = ?");
mysqli_stmt_bind_param($stmt_rev_check, "i", $ticket_id);
mysqli_stmt_execute($stmt_rev_check);
if (mysqli_num_rows(mysqli_stmt_get_result($stmt_rev_check)) > 0) {
    echo json_encode(['success' => false, 'error' => 'Phiếu sửa chữa này đã được bạn đánh giá rồi.']);
    exit();
}

// 6. Thực hiện lưu đánh giá vào database
$stmt_insert = mysqli_prepare($conn, "INSERT INTO repair_reviews (repair_ticket_id, customer_id, rating, comment) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt_insert, "iiis", $ticket_id, $customer_id, $rating, $comment);

if (mysqli_stmt_execute($stmt_insert)) {
    // 1. Chuẩn bị nội dung thông báo
    $customerName = $ticket['customer_name'];
    $notif_message = "⭐ Khách hàng {$customerName} vừa đánh giá {$rating} sao cho phiếu #TICK-{$ticket_id}.";

    // 2. Gửi thông báo cho Nhân viên kỹ thuật phụ trách (nếu có)
    if (!empty($ticket['assigned_to'])) {
        $stmt_notify_staff = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt_notify_staff, "is", $ticket['assigned_to'], $notif_message);
        mysqli_stmt_execute($stmt_notify_staff);
    }

    // 3. Gửi thông báo cho tất cả Quản lý / Admin
    $sql_managers = "SELECT id FROM users WHERE role IN ('admin', 'manager')";
    $result_managers = mysqli_query($conn, $sql_managers);
    
    while ($mgr = mysqli_fetch_assoc($result_managers)) {
        // Tránh gửi trùng 2 lần nếu Manager đó cũng chính là người phụ trách (assigned_to)
        if ($mgr['id'] != $ticket['assigned_to']) {
            $stmt_notify_mgr = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt_notify_mgr, "is", $mgr['id'], $notif_message);
            mysqli_stmt_execute($stmt_notify_mgr);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Gửi đánh giá thành công! Cảm ơn bạn.']);
} else {
    // Ghi log lỗi nếu cần thiết
    echo json_encode(['success' => false, 'error' => 'Đã xảy ra lỗi hệ thống khi lưu đánh giá. Vui lòng thử lại sau.']);
}

mysqli_close($conn);
?>