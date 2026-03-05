<?php
require_once "../config/database.php";

$requiredRoles = ['admin', 'manager'];
require_once "../middleware/check_auth.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':    handleGet($conn);    break;
    case 'POST':   handlePost($conn);   break;
    case 'DELETE': handleDelete($conn); break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Method not allowed"]);
}

// ──────────────────────────────────────────
// GET: lịch sử gia hạn theo thiết bị
// ──────────────────────────────────────────
function handleGet($conn) {
    $device_id = intval($_GET['device_id'] ?? 0);

    if ($device_id <= 0) {
        // Lấy tất cả
        $stmt = mysqli_prepare($conn,
            "SELECT we.*, d.name AS device_name, u.name AS user_name
             FROM warranty_extensions we
             LEFT JOIN devices d ON d.id = we.device_id
             LEFT JOIN users u ON u.id = we.user_id
             ORDER BY we.id DESC");
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT we.*, d.name AS device_name, u.name AS user_name
             FROM warranty_extensions we
             LEFT JOIN devices d ON d.id = we.device_id
             LEFT JOIN users u ON u.id = we.user_id
             WHERE we.device_id = ?
             ORDER BY we.id DESC");
        mysqli_stmt_bind_param($stmt, "i", $device_id);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
    echo json_encode(["success" => true, "data" => $data]);
}

// ──────────────────────────────────────────
// POST: gia hạn bảo hành
// ──────────────────────────────────────────
function handlePost($conn) {
    $input = json_decode(file_get_contents("php://input"), true) ?? [];

    $device_id    = intval($input['device_id'] ?? 0);
    $new_end_date = trim($input['new_end_date'] ?? '');
    $user_id      = intval($_SESSION['user_id']);

    if ($device_id <= 0 || $new_end_date === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "device_id and new_end_date are required"]);
        return;
    }

    // Lấy ngày kết thúc bảo hành hiện tại
    $stmt = mysqli_prepare($conn, "SELECT warranty_end_date FROM devices WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $device_id);
    mysqli_stmt_execute($stmt);
    $device = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$device) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Device not found"]);
        return;
    }

    $old_end_date = $device['warranty_end_date'];

    mysqli_begin_transaction($conn);
    try {
        // Lưu lịch sử
        $ins = mysqli_prepare($conn,
            "INSERT INTO warranty_extensions (device_id, user_id, old_end_date, new_end_date)
             VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($ins, "iiss", $device_id, $user_id, $old_end_date, $new_end_date);
        if (!mysqli_stmt_execute($ins)) throw new Exception("Failed to log extension");

        // Cập nhật thiết bị
        $upd = mysqli_prepare($conn,
            "UPDATE devices SET warranty_end_date=?, status='active' WHERE id=?");
        mysqli_stmt_bind_param($upd, "si", $new_end_date, $device_id);
        if (!mysqli_stmt_execute($upd)) throw new Exception("Failed to update device");

        mysqli_commit($conn);
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Warranty extended",
            "old_end_date" => $old_end_date,
            "new_end_date" => $new_end_date
        ]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        http_response_code(500);
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}

// ──────────────────────────────────────────
// DELETE: chỉ admin
// ──────────────────────────────────────────
function handleDelete($conn) {
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Forbidden"]);
        return;
    }

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Record id required"]);
        return;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM warranty_extensions WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Record deleted"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Delete failed"]);
    }
}
