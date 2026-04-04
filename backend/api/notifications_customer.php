<?php
session_name('CUSTOMER_SESSION');
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");

// Xác thực khách hàng
if (!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Vui lòng đăng nhập lại"]);
    exit;
}

$customer_id = intval($_SESSION['customer_id']);
$notifications = [];

// ──────────────────────────────────────────
// 1. PHIẾU SỬA CHỮA vừa hoàn thành hoặc bị hủy (trong 7 ngày gần nhất)
// ──────────────────────────────────────────
$stmt = mysqli_prepare($conn,
    "SELECT rt.id, rt.status, rt.updated_at, d.name AS device_name
     FROM repair_tickets rt
     JOIN devices d ON d.id = rt.device_id
     WHERE rt.customer_id = ?
       AND rt.status IN ('completed', 'cancelled')
       AND rt.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY rt.updated_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$tickets = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

foreach ($tickets as $t) {
    $icon = $t['status'] === 'completed' ? '✅' : '❌';
    $text = $t['status'] === 'completed' ? 'đã hoàn thành sửa chữa' : 'đã bị hủy';
    $notifications[] = [
        'type'    => 'repair',
        'icon'    => $icon,
        'message' => $icon . ' Thiết bị <strong>' . htmlspecialchars($t['device_name']) . '</strong> ' . $text,
        'time'    => $t['updated_at'],
        'link'    => '#pills-repairs'
    ];
}

// ──────────────────────────────────────────
// 2. THIẾT BỊ SẮP HẾT BẢO HÀNH (≤ 90 ngày)
// ──────────────────────────────────────────
$stmt2 = mysqli_prepare($conn,
    "SELECT id, name, warranty_end_date,
            DATEDIFF(warranty_end_date, CURDATE()) AS days_left
     FROM devices
     WHERE customer_id = ?
       AND warranty_end_date >= CURDATE()
       AND DATEDIFF(warranty_end_date, CURDATE()) <= 90
     ORDER BY days_left ASC"
);
mysqli_stmt_bind_param($stmt2, "i", $customer_id);
mysqli_stmt_execute($stmt2);
$expiring = mysqli_fetch_all(mysqli_stmt_get_result($stmt2), MYSQLI_ASSOC);

foreach ($expiring as $d) {
    $days = intval($d['days_left']);
    $icon = $days <= 30 ? '🔴' : '⚠️';
    $notifications[] = [
        'type'    => 'warranty',
        'icon'    => $icon,
        'message' => $icon . ' Thiết bị <strong>' . htmlspecialchars($d['name']) . '</strong> còn <strong>' . $days . ' ngày</strong> bảo hành',
        'time'    => null,
        'link'    => '#pills-devices'
    ];
}

// ──────────────────────────────────────────
// 3. THIẾT BỊ ĐÃ HẾT BẢO HÀNH (trong 30 ngày gần nhất)
// ──────────────────────────────────────────
$stmt3 = mysqli_prepare($conn,
    "SELECT id, name, warranty_end_date,
            ABS(DATEDIFF(warranty_end_date, CURDATE())) AS days_ago
     FROM devices
     WHERE customer_id = ?
       AND warranty_end_date < CURDATE()
       AND DATEDIFF(CURDATE(), warranty_end_date) <= 30
     ORDER BY warranty_end_date DESC"
);
mysqli_stmt_bind_param($stmt3, "i", $customer_id);
mysqli_stmt_execute($stmt3);
$expired = mysqli_fetch_all(mysqli_stmt_get_result($stmt3), MYSQLI_ASSOC);

foreach ($expired as $d) {
    $notifications[] = [
        'type'    => 'expired',
        'icon'    => '🔴',
        'message' => '🔴 Bảo hành thiết bị <strong>' . htmlspecialchars($d['name']) . '</strong> đã hết hạn ' . $d['days_ago'] . ' ngày trước',
        'time'    => null,
        'link'    => '#pills-devices'
    ];
}

echo json_encode([
    "success" => true,
    "count"   => count($notifications),
    "data"    => $notifications
]);
