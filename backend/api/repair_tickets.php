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
            "SELECT rt.*,
                    d.name AS device_name, d.serial_number,
                    c.name AS customer_name,
                    u.name AS staff_name
             FROM repair_tickets rt
             LEFT JOIN devices d ON d.id = rt.device_id
             LEFT JOIN customers c ON c.id = rt.customer_id
             LEFT JOIN users u ON u.id = rt.user_id
             WHERE rt.id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) {
            http_response_code(404);
            echo json_encode(["success" => false, "error" => "Ticket not found"]);
            return;
        }
        echo json_encode(["success" => true, "data" => $row]);
        return;
    }

    $where  = [];
    $params = [];
    $types  = '';

    if (!empty($_GET['status'])) {
        $where[]  = "rt.status = ?";
        $params[] = $_GET['status'];
        $types   .= 's';
    }
    if (!empty($_GET['customer_id'])) {
        $where[]  = "rt.customer_id = ?";
        $params[] = intval($_GET['customer_id']);
        $types   .= 'i';
    }
    if (!empty($_GET['user_id'])) {
        $where[]  = "rt.user_id = ?";
        $params[] = intval($_GET['user_id']);
        $types   .= 'i';
    }

    // Staff chỉ xem ticket của mình
    if ($_SESSION['role'] === 'staff') {
        $where[]  = "rt.user_id = ?";
        $params[] = intval($_SESSION['user_id']);
        $types   .= 'i';
    }

    $sql = "SELECT rt.*,
                   d.name AS device_name, d.serial_number,
                   c.name AS customer_name,
                   u.name AS staff_name
            FROM repair_tickets rt
            LEFT JOIN devices d ON d.id = rt.device_id
            LEFT JOIN customers c ON c.id = rt.customer_id
            LEFT JOIN users u ON u.id = rt.user_id";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY rt.id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
    echo json_encode(["success" => true, "data" => $data]);
}

// ──────────────────────────────────────────
// POST: tạo ticket sửa chữa
// ──────────────────────────────────────────
function handlePost($conn) {
    $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;

    $device_id     = intval($input['device_id'] ?? 0);
    $customer_id   = intval($input['customer_id'] ?? 0);
    $user_id       = intval($input['user_id'] ?? 0) ?: null;
    $received_date = $input['received_date'] ?? date('Y-m-d');
    $description   = trim($input['description'] ?? '');
    $status        = $input['status'] ?? 'pending';

    if ($device_id <= 0 || $customer_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "device_id and customer_id are required"]);
        return;
    }

    if (!in_array($status, ['pending', 'repairing', 'completed', 'cancelled'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid status"]);
        return;
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO repair_tickets (device_id, customer_id, user_id, received_date, description, status)
         VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iiisss",
        $device_id, $customer_id, $user_id, $received_date, $description, $status);

    if (mysqli_stmt_execute($stmt)) {
        $ticketId = mysqli_insert_id($conn);

        // Cập nhật trạng thái thiết bị → repairing
        $upd = mysqli_prepare($conn, "UPDATE devices SET status='repairing' WHERE id=?");
        mysqli_stmt_bind_param($upd, "i", $device_id);
        mysqli_stmt_execute($upd);

        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Repair ticket created", "id" => $ticketId]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to create ticket"]);
    }
}

// ──────────────────────────────────────────
// PUT: cập nhật trạng thái / assign nhân viên
// ──────────────────────────────────────────
function handlePut($conn) {
    $input = json_decode(file_get_contents("php://input"), true) ?? [];

    $id          = intval($input['id'] ?? 0);
    $status      = $input['status'] ?? null;
    $user_id     = array_key_exists('user_id', $input) ? (intval($input['user_id']) ?: null) : 'UNCHANGED';
    $description = array_key_exists('description', $input) ? trim($input['description']) : null;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Ticket id required"]);
        return;
    }

    // Build dynamic update
    $sets   = [];
    $params = [];
    $types  = '';

    if ($status !== null) {
        if (!in_array($status, ['pending', 'repairing', 'completed', 'cancelled'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Invalid status"]);
            return;
        }
        $sets[]   = "status = ?";
        $params[] = $status;
        $types   .= 's';
    }
    if ($user_id !== 'UNCHANGED') {
        $sets[]   = "user_id = ?";
        $params[] = $user_id;
        $types   .= 'i';
    }
    if ($description !== null) {
        $sets[]   = "description = ?";
        $params[] = $description;
        $types   .= 's';
    }

    if (!$sets) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Nothing to update"]);
        return;
    }

    $params[] = $id;
    $types   .= 'i';

    $stmt = mysqli_prepare($conn, "UPDATE repair_tickets SET " . implode(", ", $sets) . " WHERE id=?");
    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (mysqli_stmt_execute($stmt)) {
        // Nếu completed → cập nhật device active; nếu cancelled → active
        if (in_array($status, ['completed', 'cancelled'])) {
            $row = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT device_id FROM repair_tickets WHERE id=$id"));
            if ($row) {
                $devUpd = mysqli_prepare($conn, "UPDATE devices SET status='active' WHERE id=?");
                mysqli_stmt_bind_param($devUpd, "i", $row['device_id']);
                mysqli_stmt_execute($devUpd);
            }
        }
        echo json_encode(["success" => true, "message" => "Ticket updated"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Update failed"]);
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
        echo json_encode(["success" => false, "error" => "Ticket id required"]);
        return;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM repair_tickets WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Ticket deleted"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Delete failed"]);
    }
}
