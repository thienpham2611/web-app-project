<?php
require_once "../config/database.php";

$requiredRoles = ['admin', 'manager', 'staff'];
require_once "../middleware/check_auth.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':    handleGet($conn);    break;
    case 'POST':   handlePost($conn);   break;
    case 'PUT':    handlePut($conn);    break;   // Đánh dấu đã đọc
    case 'DELETE': handleDelete($conn); break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Phương thức không được phép"]);
}

// ──────────────────────────────────────────
// GET: thông báo của user hiện tại
// ──────────────────────────────────────────
function handleGet($conn) {
    $user_id  = intval($_SESSION['user_id']);
    $unread   = isset($_GET['unread']) ? (bool)$_GET['unread'] : false;

    $sql = "SELECT n.*, d.name AS device_name
            FROM notifications n
            LEFT JOIN devices d ON d.id = n.device_id
            WHERE n.user_id = ?";
    if ($unread) $sql .= " AND n.is_read = 0";
    $sql .= " ORDER BY n.id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
    echo json_encode(["success" => true, "data" => $data]);
}

// ──────────────────────────────────────────
// POST: tạo thông báo (admin/manager gửi)
// ──────────────────────────────────────────
function handlePost($conn) {
    if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Không có quyền thực hiện"]);
        return;
    }

    $input     = json_decode(file_get_contents("php://input"), true) ?? [];
    $device_id = intval($input['device_id'] ?? 0) ?: null;
    $user_id   = intval($input['user_id'] ?? 0);
    $message   = trim($input['message'] ?? '');

    if ($user_id <= 0 || $message === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Thiếu người nhận hoặc nội dung thông báo"]);
        return;
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO notifications (device_id, user_id, message) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iis", $device_id, $user_id, $message);

    if (mysqli_stmt_execute($stmt)) {
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Gửi thông báo thành công", "id" => mysqli_insert_id($conn)]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Gửi thông báo thất bại"]);
    }
}

// ──────────────────────────────────────────
// PUT: đánh dấu đã đọc (một hoặc tất cả)
// ──────────────────────────────────────────
function handlePut($conn) {
    $input   = json_decode(file_get_contents("php://input"), true) ?? [];
    $user_id = intval($_SESSION['user_id']);

    // Đánh dấu tất cả
    if (isset($input['mark_all']) && $input['mark_all']) {
        $stmt = mysqli_prepare($conn,
            "UPDATE notifications SET is_read=1 WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        echo json_encode(["success" => true, "message" => "Đã đánh dấu tất cả thông báo là đã đọc"]);
        return;
    }

    // Đánh dấu một
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Thiếu mã thông báo"]);
        return;
    }

    $stmt = mysqli_prepare($conn,
        "UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Đã đánh dấu đã đọc"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Cập nhật thất bại"]);
    }
}

// ──────────────────────────────────────────
// DELETE
// ──────────────────────────────────────────
function handleDelete($conn) {
    $id      = intval($_GET['id'] ?? 0);
    $user_id = intval($_SESSION['user_id']);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Thiếu mã thông báo"]);
        return;
    }

    // User chỉ xóa được thông báo của chính mình, admin xóa được tất cả
    $sql = $_SESSION['role'] === 'admin'
        ? "DELETE FROM notifications WHERE id=?"
        : "DELETE FROM notifications WHERE id=? AND user_id=$user_id";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Xóa thông báo thành công"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Xóa thất bại"]);
    }
}
