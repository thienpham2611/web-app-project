<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();
session_unset();
session_destroy();
echo json_encode(["success" => true, "message" => "Đăng xuất thành công"]);
