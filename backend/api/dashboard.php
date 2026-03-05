<?php
require_once "../config/database.php";

$requiredRoles = ['admin', 'manager'];
require_once "../middleware/check_auth.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Method not allowed"]);
    exit;
}

$stats = [];

// Tổng số khách hàng
$stats['total_customers'] = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM customers"))['cnt'];

// Tổng thiết bị theo trạng thái
$res = mysqli_query($conn, "SELECT status, COUNT(*) AS cnt FROM devices GROUP BY status");
$stats['devices'] = [];
while ($row = mysqli_fetch_assoc($res)) $stats['devices'][$row['status']] = intval($row['cnt']);

// Phiếu sửa chữa theo trạng thái
$res = mysqli_query($conn, "SELECT status, COUNT(*) AS cnt FROM repair_tickets GROUP BY status");
$stats['repair_tickets'] = [];
while ($row = mysqli_fetch_assoc($res)) $stats['repair_tickets'][$row['status']] = intval($row['cnt']);

// Doanh thu đơn hàng (đã thanh toán)
$stats['revenue'] = floatval(mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) AS total FROM orders WHERE status='paid'"))['total']);

// Đơn hàng tháng này
$stats['orders_this_month'] = intval(mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM orders
                         WHERE YEAR(order_date)=YEAR(CURDATE())
                         AND MONTH(order_date)=MONTH(CURDATE())"))['cnt']);

// Bảo hành sắp hết hạn (trong 30 ngày)
$stats['warranty_expiring_soon'] = intval(mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM devices
                         WHERE warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                         AND status='active'"))['cnt']);

// Thông báo chưa đọc của user hiện tại
$uid = intval($_SESSION['user_id']);
$stmtN = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id=? AND is_read=0");
mysqli_stmt_bind_param($stmtN, "i", $uid);
mysqli_stmt_execute($stmtN);
$stats['unread_notifications'] = intval(mysqli_fetch_assoc(mysqli_stmt_get_result($stmtN))['cnt']);

echo json_encode(["success" => true, "data" => $stats]);
