<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../config/database.php";

if (!isset($_GET['id'])) {
    echo json_encode(["success" => false, "message" => "Thiếu ID"]);
    exit;
}

$id = intval($_GET['id']);

$sql = "SELECT 
            rt.id,
            c.name AS customer_name,
            d.name AS device_name
        FROM repair_tickets rt
        JOIN customers c ON rt.customer_id = c.id
        JOIN devices d ON rt.device_id = d.id
        WHERE rt.id = $id
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        "success" => true,
        "data" => $row
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Không tìm thấy"
    ]);
}