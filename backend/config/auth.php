<?php
// config/auth.php
require_once __DIR__ . "/response.php";

function checkAuth() {
    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        response(false, "Thiếu token", null, 401);
    }

    $token = trim(str_replace("Bearer", "", $headers['Authorization']));

    // Demo: token đơn giản
    if ($token !== "valid_token") {
        response(false, "Token không hợp lệ", null, 401);
    }

    return true;
}
