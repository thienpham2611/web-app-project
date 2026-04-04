<?php
/**
 * check_warranty_expiry.php
 * Gọi API này mỗi ngày (hoặc khi admin/manager đăng nhập)
 * để tự động tạo thông báo cho thiết bị sắp hết hạn trong 90 ngày.
 *
 * Endpoint: GET /backend/api/check_warranty_expiry.php
 * Phân quyền: admin, manager (hoặc cron job nội bộ)
 */
session_name('STAFF_SESSION');
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// Cho phép cron job gọi bằng secret key, hoặc admin/manager đã đăng nhập
$secret = $_GET['secret'] ?? '';
$isInternal = ($secret === 'IDT_CRON_2026');
$isLoggedIn = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager']);

if (!$isInternal && !$isLoggedIn) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Không có quyền"]);
    exit;
}

// Tìm thiết bị hết hạn trong 90 ngày, chưa gửi thông báo hôm nay
$sql = "SELECT d.id AS device_id, d.name AS device_name, d.warranty_end_date,
               c.name AS customer_name, c.id AS customer_id,
               DATEDIFF(d.warranty_end_date, CURDATE()) AS days_left
        FROM devices d
        LEFT JOIN customers c ON c.id = d.customer_id
        WHERE d.warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
          AND d.status = 'active'
          AND d.id NOT IN (
              SELECT device_id FROM notifications
              WHERE DATE(created_at) = CURDATE()
                AND message LIKE '%sắp hết hạn bảo hành%'
          )";

$result = mysqli_query($conn, $sql);
$notified = 0;

while ($dev = mysqli_fetch_assoc($result)) {
    $days   = intval($dev['days_left']);
    $label  = $days <= 0 ? "HÔM NAY" : "còn {$days} ngày";
    $msg    = "⚠️ Thiết bị '{$dev['device_name']}' (KH: {$dev['customer_name']}) sắp hết hạn bảo hành ({$label}) vào ngày " . date('d/m/Y', strtotime($dev['warranty_end_date'])) . ".";

    // Gửi cho tất cả admin + manager
    $mgrs = mysqli_query($conn, "SELECT id FROM users WHERE role IN ('admin','manager')");
    while ($mgr = mysqli_fetch_assoc($mgrs)) {
        $ins = mysqli_prepare($conn,
            "INSERT INTO notifications (device_id, user_id, message) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($ins, "iis", $dev['device_id'], $mgr['id'], $msg);
        mysqli_stmt_execute($ins);
    }
    $notified++;
}

echo json_encode([
    "success"  => true,
    "notified" => $notified,
    "message"  => "Đã tạo {$notified} thông báo hết hạn bảo hành."
]);
