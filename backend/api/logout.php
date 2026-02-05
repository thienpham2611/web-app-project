<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();

// Nếu chưa đăng nhập thì cũng coi như logout thành công
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => true,
        "message" => "Already logged out"
    ]);
    exit;
}

// Xoá toàn bộ session
$_SESSION = [];

// Huỷ session
session_destroy();

echo json_encode([
    "success" => true,
    "message" => "Logout successful"
]);
