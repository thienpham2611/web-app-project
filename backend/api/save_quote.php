<?php
session_name('STAFF_SESSION');
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// Auth: chỉ admin và manager
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Không có quyền thực hiện"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Phương thức không được phép"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$repair_ticket_id = intval($data['repair_ticket_id'] ?? 0);
$quote_amount     = floatval($data['quote_amount'] ?? 0);
$note             = trim($data['note'] ?? '');

if ($repair_ticket_id <= 0 || $quote_amount <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Thiếu mã phiếu hoặc số tiền báo giá"]);
    exit;
}

// Lấy thông tin ticket bằng prepared statement
$stmt = mysqli_prepare($conn,
    "SELECT customer_id, device_id FROM repair_tickets WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $repair_ticket_id);
mysqli_stmt_execute($stmt);
$ticket = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$ticket) {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Không tìm thấy phiếu sửa chữa"]);
    exit;
}

$customer_id = $ticket['customer_id'];
$device_id   = isset($ticket['device_id']) && $ticket['device_id'] !== null && $ticket['device_id'] !== ''
    ? (int) $ticket['device_id'] : null;

mysqli_begin_transaction($conn);
try {
    // Tạo ORDER (device_id có thể NULL nếu phiếu không gắn thiết bị đăng ký)
    if ($device_id !== null) {
        $stmt2 = mysqli_prepare($conn,
            "INSERT INTO orders (repair_ticket_id, customer_id, device_id, quote_amount, total_amount, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 'quoted', NOW(), NOW())");
        mysqli_stmt_bind_param($stmt2, "iiidd", $repair_ticket_id, $customer_id, $device_id, $quote_amount, $quote_amount);
    } else {
        $stmt2 = mysqli_prepare($conn,
            "INSERT INTO orders (repair_ticket_id, customer_id, device_id, quote_amount, total_amount, status, created_at, updated_at)
             VALUES (?, ?, NULL, ?, ?, 'quoted', NOW(), NOW())");
        mysqli_stmt_bind_param($stmt2, "iidd", $repair_ticket_id, $customer_id, $quote_amount, $quote_amount);
    }
    if (!mysqli_stmt_execute($stmt2)) throw new Exception("Lỗi tạo đơn hàng");
    $order_id = mysqli_insert_id($conn);

    // Tạo INVOICE
    $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($order_id, 4, '0', STR_PAD_LEFT);
    $stmt3 = mysqli_prepare($conn,
        "INSERT INTO invoices (order_id, invoice_number, total, payment_status, created_at)
         VALUES (?, ?, ?, 'unpaid', NOW())");
    mysqli_stmt_bind_param($stmt3, "isd", $order_id, $invoice_number, $quote_amount);
    if (!mysqli_stmt_execute($stmt3)) throw new Exception("Lỗi tạo hóa đơn");

    mysqli_commit($conn);
    echo json_encode([
        "success"        => true,
        "message"        => "Báo giá và tạo hóa đơn thành công!",
        "order_id"       => $order_id,
        "invoice_number" => $invoice_number
    ]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}