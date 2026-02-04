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

//2. LẤY & KIỂM TRA DỮ LIỆU

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Username and password are required"
    ]);
    exit;
}

// 3. TRUY VẤN USER (CHỐNG SQL INJECTION)

$sql = "SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Database error"
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// 4. KIỂM TRA USER & PASSWORD

if ($user = mysqli_fetch_assoc($result)) {

    // password trong DB PHẢI được hash bằng password_hash
    if (password_verify($password, $user['password'])) {

        // (Tuỳ chọn) khởi tạo session
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user" => [
                "id"       => $user['id'],
                "username" => $user['username'],
                "role"     => $user['role']
            ]
        ]);
        exit;
    }
}

// 5. SAI TÀI KHOẢN / MẬT KHẨU

http_response_code(401);
echo json_encode([
    "success" => false,
    "error" => "Invalid username or password"
]);
