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
// GET: danh sách hoặc 1 khách hàng
// ──────────────────────────────────────────
function handleGet($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = mysqli_prepare($conn, "SELECT * FROM customers WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) {
            http_response_code(404);
            echo json_encode(["success" => false, "error" => "Customer not found"]);
            return;
        }
        echo json_encode(["success" => true, "data" => $row]);
        return;
    }

    // Tìm kiếm theo tên / phone
    $search = trim($_GET['search'] ?? '');
    if ($search !== '') {
        $like = "%$search%";
        $stmt = mysqli_prepare($conn,
            "SELECT * FROM customers WHERE name LIKE ? OR phone LIKE ? OR email LIKE ? ORDER BY id DESC");
        mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM customers ORDER BY id DESC");
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
    echo json_encode(["success" => true, "data" => $data]);
}

// ──────────────────────────────────────────
// POST: tạo mới
// ──────────────────────────────────────────
function handlePost($conn) {
    $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    $name    = trim($input['name'] ?? '');
    $phone   = trim($input['phone'] ?? '');
    $email   = trim($input['email'] ?? '');
    $address = trim($input['address'] ?? '');

    if ($name === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Name is required"]);
        return;
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $name, $phone, $email, $address);

    if (mysqli_stmt_execute($stmt)) {
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Customer created", "id" => mysqli_insert_id($conn)]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to create customer"]);
    }
}

// ──────────────────────────────────────────
// PUT: cập nhật
// ──────────────────────────────────────────
function handlePut($conn) {
    $input = json_decode(file_get_contents("php://input"), true) ?? [];
    $id      = intval($input['id'] ?? 0);
    $name    = trim($input['name'] ?? '');
    $phone   = trim($input['phone'] ?? '');
    $email   = trim($input['email'] ?? '');
    $address = trim($input['address'] ?? '');

    if ($id <= 0 || $name === '') {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid data"]);
        return;
    }

    $stmt = mysqli_prepare($conn,
        "UPDATE customers SET name=?, phone=?, email=?, address=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssssi", $name, $phone, $email, $address, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Customer updated"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Update failed"]);
    }
}

// ──────────────────────────────────────────
// DELETE: chỉ admin/manager
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
        echo json_encode(["success" => false, "error" => "Customer id required"]);
        return;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM customers WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(["success" => true, "message" => "Customer deleted"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Delete failed"]);
    }
}
