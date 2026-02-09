<?php
// config/config.php

// Hiển thị lỗi (tắt khi deploy production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Header mặc định cho API
header("Content-Type: application/json; charset=UTF-8");

// Secret key (dùng sau nếu làm JWT)
define("SECRET_KEY", "web_project_secret_2026");

// Token hết hạn (giây)
define("TOKEN_EXPIRE", 3600);
