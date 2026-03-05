<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

/*
|--------------------------------------------------------------------------
| 1. CHƯA ĐĂNG NHẬP → 401
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error" => "Unauthorized"
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 2. KIỂM TRA ROLE (NẾU FILE GỌI CÓ KHAI BÁO)
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
if (isset($requiredRoles) && is_array($requiredRoles)) {
    if (!in_array($_SESSION['role'], $requiredRoles, true)) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "error" => "Forbidden"
        ]);
        exit;
    }
}
