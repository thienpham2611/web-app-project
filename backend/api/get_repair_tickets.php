<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Chưa đăng nhập hoặc không có quyền Manager"]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT rt.id, rt.description, rt.status, c.name AS customer_name, d.name AS device_name 
        FROM repair_tickets rt
        LEFT JOIN customers c ON rt.customer_id = c.id
        LEFT JOIN devices d ON rt.device_id = d.id
        ORDER BY rt.created_at DESC LIMIT 50";

$result = mysqli_query($conn, $sql);
$tickets = [];

while ($row = mysqli_fetch_assoc($result)) {
    $tickets[] = $row;
}

echo json_encode($tickets);
mysqli_close($conn);
?>