<?php
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");
session_start();

// 1. KIỂM TRA ĐĂNG NHẬP

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error" => "Unauthorized"
    ]);
    exit;
}

// 2. CHỈ ADMIN ĐƯỢC QUẢN LÝ USER

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "error" => "Forbidden"
    ]);
    exit;
}

// 3. ROUTER THEO HTTP METHOD

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet($conn);
        break;

    case 'POST':
        handlePost($conn);
        break;

    case 'PUT':
        handlePut($conn);
        break;

    case 'DELETE':
        handleDelete($conn);
        break;

    default:
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "error" => "Method not allowed"
        ]);
}

// FUNCTIONS

// ---------- GET: danh sách hoặc 1 user ----------
function handleGet($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        getUserById($conn, $id);
    } else {
        getAllUsers($conn);
    }
}

function getAllUsers($conn) {
    $sql = "SELECT id, username, role, created_at FROM users";
    $result = mysqli_query($conn, $sql);

    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $users
    ]);
}

function getUserById($conn, $id) {
    $sql = "SELECT id, username, role, created_at FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $user = mysqli_fetch_assoc($result);
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "error" => "User not found"
        ]);
        return;
    }

    echo json_encode([
        "success" => true,
        "data" => $user
    ]);
}

// ---------- POST: tạo user ----------
function handlePost($conn) {
    $data = json_decode(file_get_contents("php://input"), true);

    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');
    $role     = trim($data['role'] ?? 'staff');

    if ($username === '' || $password === '') {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Username and password required"
        ]);
        return;
    }

    if (!in_array($role, ['admin', 'manager', 'staff'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Invalid role"
        ]);
        return;
    }

    // check trùng username
    $checkSql = "SELECT id FROM users WHERE username = ? LIMIT 1";
    $checkStmt = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($checkStmt, "s", $username);
    mysqli_stmt_execute($checkStmt);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt))) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "error" => "Username already exists"
        ]);
        return;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $username, $hash, $role);

    if (mysqli_stmt_execute($stmt)) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "User created successfully"
        ]);
        return;
    }

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Create user failed"
    ]);
}

// ---------- PUT: cập nhật role ----------
function handlePut($conn) {
    $data = json_decode(file_get_contents("php://input"), true);

    $id   = intval($data['id'] ?? 0);
    $role = trim($data['role'] ?? '');

    if ($id <= 0 || !in_array($role, ['admin', 'manager', 'staff'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Invalid data"
        ]);
        return;
    }

    $sql = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $role, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            "success" => true,
            "message" => "User updated successfully"
        ]);
        return;
    }

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Update failed"
    ]);
}

// ---------- DELETE: xoá user ----------
function handleDelete($conn) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "User id required"
        ]);
        return;
    }

    $id = intval($_GET['id']);

    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            "success" => true,
            "message" => "User deleted successfully"
        ]);
        return;
    }

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Delete failed"
    ]);
}
