<?php
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// 1. CHỈ CHO PHÉP POST

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed"
    ]);
    exit;
}

// 2. LẤY DỮ LIỆU

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$role     = trim($_POST['role'] ?? 'staff'); // mặc định staff

// 3. VALIDATE

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Username and password are required"
    ]);
    exit;
}

if (!in_array($role, ['admin', 'manager', 'staff'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Invalid role"
    ]);
    exit;
}

// 4. KIỂM TRA USERNAME ĐÃ TỒN TẠI

$checkSql = "SELECT id FROM users WHERE username = ? LIMIT 1";
$checkStmt = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($checkStmt, "s", $username);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_fetch_assoc($checkResult)) {
    http_response_code(409);
    echo json_encode([
        "success" => false,
        "error" => "Username already exists"
    ]);
    exit;
}

// 5. HASH PASSWORD

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 6. INSERT USER

$sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Database error"
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "sss", $username, $hashedPassword, $role);

if (mysqli_stmt_execute($stmt)) {

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "User registered successfully"
    ]);
    exit;
}

// 7. LỖI KHÔNG XÁC ĐỊNH

http_response_code(500);
echo json_encode([
    "success" => false,
    "error" => "Failed to register user"
]);
