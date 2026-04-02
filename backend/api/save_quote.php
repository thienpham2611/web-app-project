<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

$repair_ticket_id = intval($data['repair_ticket_id'] ?? 0);
$quote_amount     = floatval($data['quote_amount'] ?? 0);
$note             = $data['note'] ?? '';

if (!$repair_ticket_id || !$quote_amount) {
    echo json_encode(["success" => false, "message" => "Thiếu dữ liệu"]);
    exit;
}

// 1. Lưu vào bảng quotes
$sql_quote = "INSERT INTO quotes (repair_ticket_id, quote_amount, note, created_at)
              VALUES ($repair_ticket_id, $quote_amount, '$note', NOW())";
mysqli_query($conn, $sql_quote);

// 2. Lấy thông tin ticket
$sql_ticket = "SELECT customer_id, device_id 
               FROM repair_tickets 
               WHERE id = $repair_ticket_id 
               LIMIT 1";
$res = mysqli_query($conn, $sql_ticket);
$ticket = mysqli_fetch_assoc($res);

if (!$ticket) {
    echo json_encode(["success" => false, "message" => "Không tìm thấy ticket"]);
    exit;
}

$customer_id = $ticket['customer_id'];
$device_id   = $ticket['device_id'];

// 3. Tạo ORDER
$sql_order = "INSERT INTO orders (customer_id, device_id, total_amount, created_at)
              VALUES ($customer_id, $device_id, $quote_amount, NOW())";
mysqli_query($conn, $sql_order);

$order_id = mysqli_insert_id($conn);

// 4. Tạo INVOICE
$invoice_number = 'INV-' . time();

$sql_invoice = "INSERT INTO invoices (order_id, invoice_number, total, payment_status, created_at)
                VALUES ($order_id, '$invoice_number', $quote_amount, 'paid', NOW())";
mysqli_query($conn, $sql_invoice);

echo json_encode([
    "success" => true,
    "message" => "Báo giá + tạo hóa đơn thành công"
]);