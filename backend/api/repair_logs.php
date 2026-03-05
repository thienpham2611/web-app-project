<?php
require_once "../config/database.php";

$requiredRoles = ['admin', 'manager', 'staff'];
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
// GET: lấy log theo ticket
// ──────────────────────────────────────────
function handleGet($conn) {
    if (empty($_GET['ticket_id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "ticket_id is required"]);
        return;
    }

    $ticket_id = intval($_GET['ticket_id']);
    $stmt = mysqli_prepare($conn,
        "SELECT rl.*, u.name AS user_name
         FROM repair_logs rl
         LEFT JOIN users u ON u.id = rl.user_id
         WHERE rl.repair_ticket_id = ?
         ORDER BY rl.id ASC");
    mysqli_stmt_bind_param($stmt, "i", $ticket_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
    echo json_encode(["success" => true, "data" => $data]);
}

// ──────────────────────────────────────────
// POST: thêm log hành động
// ──────────────────────────────────────────
function handlePost($conn) {
    $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;

    $ticket_id = intval($input['repair_ticket_id'] ?? 0);
    $action    = trim($input['action'] ?? '');
    $note      = trim($input['note'] ?? '');
    $user_id   = intval($_SESSION['user_id']);

    if ($ticket_id <= 0 || $action === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "repair_ticket_id and action are required"]);
        return;
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO repair_logs (repair_ticket_id, user_id, action, note)
         VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iiss", $ticket_id, $user_id, $action, $note);

    if (mysqli_stmt_execute($stmt)) {
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Log added", "id" => mysqli_insert_id($conn)]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to add log"]);
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
        echo json_encode(["success" => false, "error" => "Log id required"]);
        return;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM repair_logs WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Log deleted"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Delete failed"]);
    }
}
