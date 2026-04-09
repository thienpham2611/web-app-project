<?php
session_name('CUSTOMER_SESSION');
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// 1. Kiểm tra xác thực
if (!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(["success" => false, "error" => "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại!"]);
    exit;
}

$customer_id = $_SESSION['customer_id'];

// 2. Nhận dữ liệu JSON
$data = json_decode(file_get_contents('php://input'), true);
$device_name   = trim($data['device_name']   ?? '');
$serial_number = trim($data['serial_number'] ?? '');
$serial_number = $serial_number !== '' ? $serial_number : null;
$device_type   = in_array($data['device_type'] ?? '', ['hardware', 'software']) ? $data['device_type'] : 'hardware';
$description   = trim($data['description']   ?? '');

if (empty($device_name) || empty($description)) {
    echo json_encode(["success" => false, "error" => "Vui lòng nhập đầy đủ tên thiết bị và mô tả lỗi."]);
    exit;
}

// 3. Chỉ liên kết phiếu với thiết bị đã có (theo S/N). Không tạo bản ghi mới trong bảng devices.
$device_id = null;
$ticket_device_name = null;
$ticket_reported_serial = null;

if (!empty($serial_number)) {
    $stmtFind = mysqli_prepare($conn,
        "SELECT id FROM devices WHERE serial_number = ? AND customer_id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmtFind, "si", $serial_number, $customer_id);
    mysqli_stmt_execute($stmtFind);
    $rowFound = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtFind));
    if ($rowFound) {
        $device_id = (int) $rowFound['id'];
        $stmtSync = mysqli_prepare($conn,
            "UPDATE devices SET name = ?, type = ? WHERE id = ? AND customer_id = ?"
        );
        mysqli_stmt_bind_param($stmtSync, "ssii", $device_name, $device_type, $device_id, $customer_id);
        mysqli_stmt_execute($stmtSync);
    }
}

// 4. Không có thiết bị trùng S/N: chỉ lưu thông tin trên phiếu (không thêm thiết bị vào danh sách)
if (!$device_id) {
    $ticket_device_name = $device_name;
    $ticket_reported_serial = $serial_number !== null && $serial_number !== '' ? $serial_number : null;
}

// 5. Tạo phiếu sửa chữa
$received_date = date('Y-m-d');
$status = 'pending';
$progress = 0;

if ($device_id !== null) {
    $stmtTicket = mysqli_prepare($conn,
        "INSERT INTO repair_tickets (device_id, customer_id, received_date, description, status, progress, device_name, reported_serial)
         VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)"
    );
    mysqli_stmt_bind_param($stmtTicket, "iisssi",
        $device_id, $customer_id, $received_date, $description, $status, $progress
    );
} else {
    $stmtTicket = mysqli_prepare($conn,
        "INSERT INTO repair_tickets (device_id, customer_id, received_date, description, status, progress, device_name, reported_serial)
         VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmtTicket, "isssiss",
        $customer_id, $received_date, $description, $status, $progress, $ticket_device_name, $ticket_reported_serial
    );
}

if (mysqli_stmt_execute($stmtTicket)) {
    $ticket_id = mysqli_insert_id($conn);
    echo json_encode([
        "success"   => true,
        "message"   => "Tạo phiếu sửa chữa thành công!",
        "ticket_id" => $ticket_id
    ]);
} else {
    echo json_encode(["success" => false, "error" => "Lỗi hệ thống, không thể tạo phiếu: " . mysqli_error($conn)]);
}
?>
