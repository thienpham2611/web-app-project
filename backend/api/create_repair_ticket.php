<?php
// =============================================
// DỰ ÁN DEMO TẠO TICKET REPAIR
// File: backend/api/create_repair_ticket.php
// =============================================

session_start();

// Debug: Bật hiển thị lỗi tạm thời để thấy rõ lỗi 500
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../config/database.php";   // ← Đường dẫn này rất hay sai!

// Kiểm tra kết nối DB
if (!isset($conn) || $conn === false) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Không kết nối được database. Kiểm tra file database.php"]);
    exit;
}

// Kiểm tra đăng nhập khách hàng
if (!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Bạn chưa đăng nhập hoặc session hết hạn. Vui lòng login lại."]);
    exit;
}

header("Content-Type: application/json; charset=UTF-8");

// Nhận dữ liệu từ form
$device_id   = intval($_POST['device_id'] ?? 0);
$description = trim($_POST['description'] ?? '');

if ($device_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Vui lòng chọn thiết bị"]);
    exit;
}
if (empty($description)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Vui lòng nhập mô tả lỗi"]);
    exit;
}

$customer_id = $_SESSION['customer_id'];
$received_date = date('Y-m-d');
$status = 'pending';

try {
    $device_id   = intval($_POST['device_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($device_id <= 0 || empty($description)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Vui lòng chọn thiết bị và nhập mô tả lỗi"]);
        exit;
    }

    $customer_id = $_SESSION['customer_id'];
    $received_date = date('Y-m-d');
    $status = 'pending';

    // === FIX FOREIGN KEY: Tự động tạo device nếu chưa tồn tại ===
    $check = mysqli_prepare($conn, "SELECT id FROM devices WHERE id = ?");
    mysqli_stmt_bind_param($check, "i", $device_id);
    mysqli_stmt_execute($check);
    $result = mysqli_stmt_get_result($check);

    if (mysqli_num_rows($result) === 0) {
        // Tạo device mới (demo)
        $device_name = ($device_id == 1) ? "Dell XPS 15" : "Máy in HP Laser";
        $insert_dev = mysqli_prepare($conn, "INSERT INTO devices (id, name, status) VALUES (?, ?, 'repairing')");
        mysqli_stmt_bind_param($insert_dev, "is", $device_id, $device_name);
        mysqli_stmt_execute($insert_dev);
    }

    // Bây giờ insert ticket an toàn
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO repair_tickets (device_id, customer_id, user_id, received_date, description, status) 
         VALUES (?, ?, NULL, ?, ?, ?)");

    mysqli_stmt_bind_param($stmt, "iisss", $device_id, $customer_id, $received_date, $description, $status);

    if (mysqli_stmt_execute($stmt)) {
        $ticket_id = mysqli_insert_id($conn);

        echo json_encode([
            "success" => true,
            "message" => "✅ Yêu cầu sửa chữa đã được tạo thành công!",
            "ticket_id" => "#RT-" . str_pad($ticket_id, 3, '0', STR_PAD_LEFT),
            "id" => $ticket_id
        ]);
    } else {
        throw new Exception("Insert ticket thất bại: " . mysqli_error($conn));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

exit;
?>