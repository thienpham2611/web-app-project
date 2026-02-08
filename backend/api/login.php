<?php
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// ============================
// 1. CHỈ CHO PHÉP POST
// ============================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed"
    ]);
    exit;
}

// ============================
// 2. LẤY DỮ LIỆU (JSON | FORM | QUERY)
// ============================
$email = '';
$password = '';

// Ưu tiên JSON
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (is_array($data)) {
    $email    = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');
}

// Fallback: form-data / x-www-form-urlencoded
if ($email === '' || $password === '') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
}

// Fallback cuối: query string (chỉ để debug)
if ($email === '' || $password === '') {
    $email    = trim($_GET['email'] ?? '');
    $password = trim($_GET['password'] ?? '');
}

// ============================
// 3. VALIDATE INPUT
// ============================
if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Email and password are required",
        "debug" => [
            "content_type" => $_SERVER['CONTENT_TYPE'] ?? null,
            "raw_body" => $raw
        ]
    ]);
    exit;
}

// ============================
// 4. QUERY USER
// ============================
$sql = "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Database prepare failed"
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// ============================
// 5. CHECK PASSWORD
// ============================
if ($user = mysqli_fetch_assoc($result)) {

    if (password_verify($password, $user['password'])) {

        session_start();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user" => [
                "id"    => $user['id'],
                "name"  => $user['name'],
                "email" => $user['email'],
                "role"  => $user['role']
            ]
        ]);
        exit;
    }
}

// ============================
// 6. LOGIN FAIL
// ============================
http_response_code(401);
echo json_encode([
    "success" => false,
    "error" => "Invalid email or password"
]);
