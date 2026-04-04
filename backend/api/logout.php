<?php
session_name('STAFF_SESSION');
session_start();
session_unset();
session_destroy();

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
          (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($isAjax) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(["success" => true, "message" => "Đăng xuất thành công"], JSON_UNESCAPED_UNICODE);
} else {
    header("Location: /web-app-project-main/frontend/admin/index.php");
    exit();
}
