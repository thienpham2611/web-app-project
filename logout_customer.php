<?php
session_name('CUSTOMER_SESSION');
session_start();

// Xóa toàn bộ session khách hàng
session_unset();
session_destroy();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");

echo json_encode([
    "success" => true,
    "message" => "Đăng xuất thành công"
]);
