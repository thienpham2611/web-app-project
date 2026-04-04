<?php
session_name('STAFF_SESSION');
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff','manager','admin'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Chưa đăng nhập"]);
    exit;
}

$user_id = intval($_SESSION['user_id']);

$stmt = mysqli_prepare($conn,
    "SELECT rt.id, rt.status, rt.description, rt.progress, rt.received_date,
            d.name AS device_name, d.serial_number,
            c.name AS customer_name, c.phone AS customer_phone
     FROM repair_tickets rt
     JOIN devices d ON d.id = rt.device_id
     JOIN customers c ON c.id = rt.customer_id
     WHERE rt.user_id = ?
       AND rt.status IN ('pending','repairing')
     ORDER BY rt.updated_at DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

echo json_encode(["success" => true, "data" => $data]);
