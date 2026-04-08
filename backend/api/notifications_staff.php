<?php
session_name('STAFF_SESSION');
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager','staff'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Chưa đăng nhập"]);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$method  = $_SERVER['REQUEST_METHOD'];

// GET: lấy danh sách thông báo
if ($method === 'GET') {
    $stmt = mysqli_prepare($conn,
        "SELECT id, message, is_read, created_at FROM notifications
         WHERE user_id = ? ORDER BY id DESC LIMIT 30");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    echo json_encode(["success" => true, "data" => $data]);
    exit;
}

// POST: tạo thông báo cho chính mình (deadline reminder)
if ($method === 'POST') {
    $input   = json_decode(file_get_contents("php://input"), true) ?? [];
    $message = trim($input['message'] ?? '');
    if (empty($message)) {
        echo json_encode(["success" => false, "error" => "Thiếu nội dung"]);
        exit;
    }
    $stmt = mysqli_prepare($conn,
        "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "is", $user_id, $message);
    mysqli_stmt_execute($stmt);
    echo json_encode(["success" => true]);
    exit;
}

// PUT: đánh dấu đã đọc
if ($method === 'PUT') {
    $input = json_decode(file_get_contents("php://input"), true) ?? [];
    if (!empty($input['mark_all'])) {
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read=1 WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
    } else {
        $id = intval($input['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    }
    mysqli_stmt_execute($stmt);
    echo json_encode(["success" => true]);
    exit;
}

http_response_code(405);
echo json_encode(["success" => false, "error" => "Phương thức không hợp lệ"]);
