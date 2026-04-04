<?php
session_name('STAFF_SESSION');
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    http_response_code(401);
    http_response_code(403); echo json_encode(["error" => "Không có quyền"]); exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

// Lấy đơn hàng mới nhất chưa in (hoặc theo order_id nếu truyền GET)
$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id > 0) {
    $sql = "SELECT o.*, c.name AS customer_name, c.phone, d.name AS device_name
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN devices d ON o.device_id = d.id
            WHERE o.id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
} else {
    // Lấy đơn hàng mới nhất để in demo
    $sql = "SELECT o.*, c.name AS customer_name, c.phone, d.name AS device_name
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN devices d ON o.device_id = d.id
            ORDER BY o.created_at DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
}
date_default_timezone_set("Asia/Ho_Chi_Minh");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    die("<h3>Không tìm thấy đơn hàng để in hóa đơn!</h3>");
}

// Tạo invoice_number nếu chưa có
$invoice_number = "INV-" . date("Ymd") . "-" . str_pad($order['id'], 4, '0', STR_PAD_LEFT);

// Cập nhật hoặc tạo invoice
mysqli_query($conn, "INSERT INTO invoices (order_id, invoice_number, total, payment_status, printed_at)
                     VALUES ({$order['id']}, '$invoice_number', {$order['total_amount']}, 'paid', NOW())
                     ON DUPLICATE KEY UPDATE printed_at = NOW()");

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>HÓA ĐƠN #<?= $invoice_number ?></title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
            .no-print { display: none; }
        }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <h2 style="text-align:center">HÓA ĐƠN THANH TOÁN</h2>
    <p><strong>Số hóa đơn:</strong> <?= $invoice_number ?> &nbsp;&nbsp; <strong>Ngày:</strong> <?= date('d/m/Y H:i') ?></p>
    
    <p><strong>Khách hàng:</strong> <?= htmlspecialchars($order['customer_name']) ?> 
       &nbsp;&nbsp; <strong>SĐT:</strong> <?= $order['phone'] ?></p>
    
    <p><strong>Thiết bị:</strong> <?= htmlspecialchars($order['device_name'] ?? 'Không xác định') ?></p>
    
    <table>
        <tr><th>Mô tả</th><th>Số tiền (VND)</th></tr>
        <tr><td>Báo giá sửa chữa / Dịch vụ</td><td><?= number_format($order['total_amount'], 0) ?></td></tr>
        <tr><td colspan="2" style="text-align:right"><strong>Tổng cộng: <?= number_format($order['total_amount'], 0) ?> ₫</strong></td></tr>
    </table>
    
    <p style="text-align:center">Cảm ơn quý khách đã sử dụng dịch vụ!<br>Chữ ký Manager</p>
    
    <div class="no-print" style="text-align:center;margin-top:30px">
        <button onclick="window.print()">🖨 In hóa đơn</button>
        <button onclick="window.close()">Đóng</button>
    </div>
</body>
</html>