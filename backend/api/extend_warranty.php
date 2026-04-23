<?php
session_name('STAFF_SESSION');
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// Chỉ admin và manager được phép
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

$customer_id  = intval($data['customer_id']  ?? 0);
$device_id    = intval($data['device_id']    ?? 0);
$new_end_date = trim($data['new_end_date']   ?? '');
$cost         = floatval($data['cost']       ?? 0);
$note         = trim($data['note']           ?? '');
$staff_id     = intval($_SESSION['user_id']);

// ── Validate ────────────────────────────────────────────────
if ($customer_id <= 0 || $device_id <= 0 || empty($new_end_date) || $cost < 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Thiếu hoặc sai dữ liệu đầu vào"]);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_end_date)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Định dạng ngày không hợp lệ (cần YYYY-MM-DD)"]);
    exit;
}

// ── Lấy thông tin thiết bị, kiểm tra quyền sở hữu ──────────
$stmtDev = mysqli_prepare($conn,
    "SELECT id, name, serial_number, customer_id, warranty_end_date
     FROM devices WHERE id = ? AND customer_id = ?");
mysqli_stmt_bind_param($stmtDev, "ii", $device_id, $customer_id);
mysqli_stmt_execute($stmtDev);
$device = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtDev));

if (!$device) {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Không tìm thấy thiết bị hoặc thiết bị không thuộc khách hàng này"]);
    exit;
}

// Ngày mới phải sau ngày cũ (nếu thiết bị đang có bảo hành)
if (!empty($device['warranty_end_date']) && $new_end_date <= $device['warranty_end_date']) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Ngày hết hạn mới phải sau ngày hết hạn hiện tại ({$device['warranty_end_date']})"]);
    exit;
}

$old_end_date = $device['warranty_end_date'] ?: null;
$device_name  = $device['name'];

// ── Transaction ──────────────────────────────────────────────
mysqli_begin_transaction($conn);
try {

    // 1. Cập nhật ngày hết hạn bảo hành trên bảng devices
    $stmtUpd = mysqli_prepare($conn,
        "UPDATE devices SET warranty_end_date = ?, status = 'active' WHERE id = ?");
    mysqli_stmt_bind_param($stmtUpd, "si", $new_end_date, $device_id);
    if (!mysqli_stmt_execute($stmtUpd)) throw new Exception("Lỗi cập nhật thiết bị");

    // 2. Ghi lịch sử gia hạn vào warranty_extensions
    $stmtExt = mysqli_prepare($conn,
        "INSERT INTO warranty_extensions
            (device_id, user_id, old_end_date, new_end_date, cost, note, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmtExt, "iissds",
        $device_id, $staff_id, $old_end_date, $new_end_date, $cost, $note);
    if (!mysqli_stmt_execute($stmtExt)) throw new Exception("Lỗi ghi lịch sử gia hạn");

    // 3. Tạo orders với order_type = 'warranty'
    $stmtOrd = mysqli_prepare($conn,
        "INSERT INTO orders
            (customer_id, device_id, order_type, quote_amount, total_amount, status, created_at, updated_at)
         VALUES (?, ?, 'warranty', ?, ?, 'paid', NOW(), NOW())");
    mysqli_stmt_bind_param($stmtOrd, "iidd", $customer_id, $device_id, $cost, $cost);
    if (!mysqli_stmt_execute($stmtOrd)) throw new Exception("Lỗi tạo đơn hàng");
    $order_id = mysqli_insert_id($conn);

    // 4. Tạo hóa đơn invoices
    $invoice_number = 'WEX-' . date('Ymd') . '-' . str_pad($order_id, 4, '0', STR_PAD_LEFT);
    $stmtInv = mysqli_prepare($conn,
        "INSERT INTO invoices (order_id, invoice_number, total, payment_status, created_at)
         VALUES (?, ?, ?, 'paid', NOW())");
    mysqli_stmt_bind_param($stmtInv, "isd", $order_id, $invoice_number, $cost);
    if (!mysqli_stmt_execute($stmtInv)) throw new Exception("Lỗi tạo hóa đơn");

    // 5. Tạo order_details — 1 dòng duy nhất cho gia hạn bảo hành
    $itemName = "Gia hạn bảo hành: $device_name đến " . date('d/m/Y', strtotime($new_end_date));
    $costInt  = (int) $cost;
    $stmtDet  = mysqli_prepare($conn,
        "INSERT INTO order_details (order_id, item_name, price, quantity) VALUES (?, ?, ?, 1)");
    mysqli_stmt_bind_param($stmtDet, "isi", $order_id, $itemName, $costInt);
    if (!mysqli_stmt_execute($stmtDet)) throw new Exception("Lỗi tạo chi tiết đơn hàng");

    // 6. Thông báo cho tất cả manager (để badge cập nhật)
    $newEndFmt  = date('d/m/Y', strtotime($new_end_date));
    $msgManager = "✅ Đã gia hạn bảo hành thiết bị \"$device_name\" đến $newEndFmt. Hóa đơn: $invoice_number.";
    $stmtNotif  = mysqli_prepare($conn,
        "INSERT INTO notifications (user_id, message, is_read)
         SELECT id, ?, 0 FROM users WHERE role IN ('admin','manager')");
    mysqli_stmt_bind_param($stmtNotif, "s", $msgManager);
    mysqli_stmt_execute($stmtNotif); // không throw nếu lỗi — thông báo là nice-to-have

    mysqli_commit($conn);
    echo json_encode([
        "success"        => true,
        "message"        => "Gia hạn bảo hành thành công!",
        "invoice_number" => $invoice_number,
        "order_id"       => $order_id,
        "new_end_date"   => $new_end_date
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

mysqli_close($conn);
?>
