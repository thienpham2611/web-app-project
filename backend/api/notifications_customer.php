<?php
session_name('CUSTOMER_SESSION');
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");

if (!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Vui lòng đăng nhập lại"]);
    exit;
}

$customer_id = intval($_SESSION['customer_id']);
$notifications = [];

// 1. Phiếu hoàn thành / hủy (7 ngày)
$stmt = mysqli_prepare($conn,
    "SELECT rt.id, rt.status, rt.updated_at, COALESCE(d.name, rt.device_name) AS device_name
     FROM repair_tickets rt LEFT JOIN devices d ON d.id = rt.device_id
     WHERE rt.customer_id = ? AND rt.status IN ('completed','cancelled')
       AND rt.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY rt.updated_at DESC");
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
foreach (mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC) as $t) {
    $icon = $t['status'] === 'completed' ? '✅' : '❌';
    $text = $t['status'] === 'completed' ? 'đã hoàn thành sửa chữa' : 'đã bị hủy';
    $notifications[] = ['type'=>'repair','icon'=>$icon,
        'message'=>$icon.' Thiết bị <strong>'.htmlspecialchars($t['device_name']).'</strong> '.$text,
        'time'=>$t['updated_at'],'link'=>'#pills-repairs'];
}

// 2. Sắp hết bảo hành (≤ 90 ngày)
$stmt2 = mysqli_prepare($conn,
    "SELECT name, DATEDIFF(warranty_end_date, CURDATE()) AS days_left
     FROM devices WHERE customer_id = ? AND warranty_end_date >= CURDATE()
       AND DATEDIFF(warranty_end_date, CURDATE()) <= 90 ORDER BY days_left ASC");
mysqli_stmt_bind_param($stmt2, "i", $customer_id);
mysqli_stmt_execute($stmt2);
foreach (mysqli_fetch_all(mysqli_stmt_get_result($stmt2), MYSQLI_ASSOC) as $d) {
    $icon = $d['days_left'] <= 30 ? '🔴' : '⚠️';
    $notifications[] = ['type'=>'warranty','icon'=>$icon,
        'message'=>$icon.' Thiết bị <strong>'.htmlspecialchars($d['name']).'</strong> còn <strong>'.$d['days_left'].' ngày</strong> bảo hành',
        'time'=>null,'link'=>'#pills-devices'];
}

// 3. Đã hết bảo hành (30 ngày gần nhất)
$stmt3 = mysqli_prepare($conn,
    "SELECT name, ABS(DATEDIFF(warranty_end_date, CURDATE())) AS days_ago
     FROM devices WHERE customer_id = ? AND warranty_end_date < CURDATE()
       AND DATEDIFF(CURDATE(), warranty_end_date) <= 30 ORDER BY warranty_end_date DESC");
mysqli_stmt_bind_param($stmt3, "i", $customer_id);
mysqli_stmt_execute($stmt3);
foreach (mysqli_fetch_all(mysqli_stmt_get_result($stmt3), MYSQLI_ASSOC) as $d) {
    $notifications[] = ['type'=>'expired','icon'=>'🔴',
        'message'=>'🔴 Bảo hành <strong>'.htmlspecialchars($d['name']).'</strong> đã hết hạn '.$d['days_ago'].' ngày trước',
        'time'=>null,'link'=>'#pills-devices'];
}

// 4. Cập nhật tiến độ từ nhân viên (repair_logs, 7 ngày)
$stmt4 = mysqli_prepare($conn,
    "SELECT rl.note, rl.created_at, rt.id AS ticket_id, rt.progress,
            COALESCE(d.name, rt.device_name) AS device_name, u.name AS staff_name
     FROM repair_logs rl
     JOIN repair_tickets rt ON rt.id = rl.repair_ticket_id
     LEFT JOIN devices d ON d.id = rt.device_id
     LEFT JOIN users u ON u.id = rl.user_id
     WHERE rt.customer_id = ? AND rl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY rl.created_at DESC LIMIT 10");
mysqli_stmt_bind_param($stmt4, "i", $customer_id);
mysqli_stmt_execute($stmt4);
foreach (mysqli_fetch_all(mysqli_stmt_get_result($stmt4), MYSQLI_ASSOC) as $log) {
    $history_progress = 0;
    if (isset($log['action']) && preg_match('/(\d+)%/', $log['action'], $matches)) {
        $history_progress = intval($matches[1]);
    } else {
        // Fallback: Nếu vì lý do nào đó không có % trong text, mới dùng % hiện hành
        $history_progress = intval($log['progress']); 
    }

    $staff    = htmlspecialchars($log['staff_name'] ?? 'Nhân viên');
    $device   = htmlspecialchars($log['device_name']);
    $note_html = $log['note'] ? '<br><span style="font-style:italic;color:#17a2b8;">Ghi chú: '.htmlspecialchars($log['note']).'</span>' : '';
    
    $notifications[] = [
        'type'      => 'repair_log',
        'icon'      => '🔧',
        'message'   => '🔧 <strong>'.$device.'</strong>: '.$staff.' cập nhật tiến độ <strong>'.$history_progress.'%</strong>'.$note_html,
        'time'      => $log['created_at'],
        'link'      => '#pills-repairs',
        'progress'  => $history_progress, // Cập nhật lại key này để FE hiển thị đúng
        'note'      => $log['note'] ?? '',
        'ticket_id' => $log['ticket_id']
    ];
}

// Sắp xếp: có time thì mới nhất lên đầu, không có time xuống cuối
usort($notifications, function($a, $b) {
    if ($a['time'] && $b['time']) return strtotime($b['time']) - strtotime($a['time']);
    if ($a['time']) return -1;
    if ($b['time']) return 1;
    return 0;
});

echo json_encode(["success"=>true,"count"=>count($notifications),"data"=>$notifications]);
