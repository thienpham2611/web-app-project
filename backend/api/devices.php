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
    case 'PUT':    handlePut($conn);    break;
    case 'DELETE': handleDelete($conn); break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Method not allowed"]);
}

// ──────────────────────────────────────────
// GET
// ──────────────────────────────────────────
function handleGet($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = mysqli_prepare($conn,
            "SELECT d.*, c.name AS customer_name
             FROM devices d
             LEFT JOIN customers c ON c.id = d.customer_id
             WHERE d.id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) {
            http_response_code(404);
            echo json_encode(["success" => false, "error" => "Device not found"]);
            return;
        }
        echo json_encode(["success" => true, "data" => $row]);
        return;
    }

    // Lọc theo customer_id, status
    $where = [];
    $params = [];
    $types  = '';

    if (!empty($_GET['customer_id'])) {
        $where[]  = "d.customer_id = ?";
        $params[] = intval($_GET['customer_id']);
        $types   .= 'i';
    }
    if (!empty($_GET['status'])) {
        $where[]  = "d.status = ?";
        $params[] = $_GET['status'];
        $types   .= 's';
    }
    if (!empty($_GET['search'])) {
        $like     = "%" . $_GET['search'] . "%";
        $where[]  = "(d.name LIKE ? OR d.serial_number LIKE ?)";
        $params[] = $like;
        $params[] = $like;
        $types   .= 'ss';
    }

    $sql = "SELECT d.*, c.name AS customer_name
            FROM devices d
            LEFT JOIN customers c ON c.id = d.customer_id";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY d.id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
    echo json_encode(["success" => true, "data" => $data]);
}

// ──────────────────────────────────────────
// POST: tạo thiết bị
// ──────────────────────────────────────────
function handlePost($conn) {
    $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;

    $name              = trim($input['name'] ?? '');
    $serial_number     = trim($input['serial_number'] ?? '');
    $customer_id       = intval($input['customer_id'] ?? 0) ?: null;
    $type              = $input['type'] ?? 'hardware';
    $warranty_start    = $input['warranty_start_date'] ?? null;
    $warranty_end      = $input['warranty_end_date'] ?? null;
    $status            = $input['status'] ?? 'active';

    if ($name === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Device name is required"]);
        return;
    }

    if (!in_array($type, ['hardware', 'software'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid type"]);
        return;
    }

    if (!in_array($status, ['active', 'expired', 'repairing'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid status"]);
        return;
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO devices (name, serial_number, customer_id, type, warranty_start_date, warranty_end_date, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssissss",
        $name, $serial_number, $customer_id, $type, $warranty_start, $warranty_end, $status);

    if (mysqli_stmt_execute($stmt)) {
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Device created", "id" => mysqli_insert_id($conn)]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to create device"]);
    }
}

// ──────────────────────────────────────────
// PUT: cập nhật
// ──────────────────────────────────────────
function handlePut($conn) {
    $input = json_decode(file_get_contents("php://input"), true) ?? [];

    $id             = intval($input['id'] ?? 0);
    $name           = trim($input['name'] ?? '');
    $serial_number  = trim($input['serial_number'] ?? '');
    $customer_id    = intval($input['customer_id'] ?? 0) ?: null;
    $type           = $input['type'] ?? 'hardware';
    $warranty_start = $input['warranty_start_date'] ?? null;
    $warranty_end   = $input['warranty_end_date'] ?? null;
    $status         = $input['status'] ?? 'active';

    if ($id <= 0 || $name === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid data"]);
        return;
    }

    $stmt = mysqli_prepare($conn,
        "UPDATE devices SET name=?, serial_number=?, customer_id=?, type=?,
         warranty_start_date=?, warranty_end_date=?, status=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssissssi",
        $name, $serial_number, $customer_id, $type, $warranty_start, $warranty_end, $status, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Device updated"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Update failed"]);
    }
}

// ──────────────────────────────────────────
// DELETE
// ──────────────────────────────────────────
function handleDelete($conn) {
    if (!in_array($_SESSION['role'], ['admin', 'manager'])) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Forbidden"]);
        return;
    }

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Device id required"]);
        return;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM devices WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Device deleted"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Delete failed"]);
    }
}
