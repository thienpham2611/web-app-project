<?php
session_name('STAFF_SESSION');
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Chưa đăng nhập hoặc không có quyền"]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT id, name, phone FROM customers ORDER BY name ASC";
$result = mysqli_query($conn, $sql);

$customers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $customers[] = $row;
}

echo json_encode($customers);
mysqli_close($conn);
?>