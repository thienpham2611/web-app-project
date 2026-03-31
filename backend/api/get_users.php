<?php
session_start();
require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

// Chỉ Admin mới có quyền xem danh sách này
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Từ chối truy cập!"]);
    exit;
}

// Lấy danh sách nhân sự (Sắp xếp người mới tạo lên đầu)
$sql = "SELECT id, name, email, role FROM users ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

$users = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}

echo json_encode(["success" => true, "data" => $users]);
?>