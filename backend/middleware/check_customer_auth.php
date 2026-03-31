<?php
/**
 * Middleware xác thực khách hàng
 * Session khách hàng dùng key 'customer_id' + role='customer'
 * Hoàn toàn tách biệt với session nhân viên nội bộ
 */
if (!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Vui lòng đăng nhập với tài khoản khách hàng"]);
    exit;
}
