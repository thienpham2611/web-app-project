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

// 3. Tìm thiết bị theo serial (nếu có) trong danh sách của khách hàng
$device_id = null;

if (!empty($serial_number)) {
    $stmtFind = mysqli_prepare($conn,
        "SELECT id FROM devices WHERE serial_number = ? AND customer_id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmtFind, "si", $serial_number, $customer_id);
    mysqli_stmt_execute($stmtFind);
    $rowFound = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtFind));
    if ($rowFound) {
        $device_id = $rowFound['id'];
    }
}

// 4. Nếu không tìm thấy thiết bị → thêm mới vào bảng devices
if (!$device_id) {
    $today = date('Y-m-d');
    $stmtInsertDev = mysqli_prepare($conn,
        "INSERT INTO devices (name, serial_number, customer_id, type, warranty_start_date, warranty_end_date, status)
         VALUES (?, ?, ?, ?, ?, ?, 'expired')"
    );
    // warranty_end_date = ngày hôm nay (coi như không có bảo hành)
    mysqli_stmt_bind_param($stmtInsertDev, "ssisss",
        $device_name, $serial_number, $customer_id, $device_type, $today, $today
    );
    if (!mysqli_stmt_execute($stmtInsertDev)) {
        echo json_encode(["success" => false, "error" => "Không thể thêm thiết bị: " . mysqli_error($conn)]);
        exit;
    }
    $device_id = mysqli_insert_id($conn);
}

// 5. Tạo phiếu sửa chữa
$received_date = date('Y-m-d');
$status = 'pending';
$progress = 0;

$stmtTicket = mysqli_prepare($conn,
    "INSERT INTO repair_tickets (device_id, customer_id, received_date, description, status, progress)
     VALUES (?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmtTicket, "iisssi",
    $device_id, $customer_id, $received_date, $description, $status, $progress
);

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
