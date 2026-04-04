<?php
/**
 * Middleware xác thực NHÂN VIÊN NỘI BỘ
 * Session dùng key 'user_id' + role IN ('admin','manager','staff')
 * Hoàn toàn tách biệt với session khách hàng
 */
session_name('STAFF_SESSION');
session_start();
header("Content-Type: application/json; charset=UTF-8");

// Chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Chưa đăng nhập"]);
    exit;
}

// Không phải nhân viên nội bộ (tránh khách hàng gọi nhầm API)
if (!in_array($_SESSION['role'], ['admin','manager','staff'], true)) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Tài khoản không có quyền truy cập hệ thống nội bộ"]);
    exit;
}

// Kiểm tra role cụ thể nếu file gọi có khai báo $requiredRoles
if (isset($requiredRoles) && is_array($requiredRoles)) {
    if (!in_array($_SESSION['role'], $requiredRoles, true)) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "Không có quyền thực hiện"]);
        exit;
    }
}
