<?php
require_once "../config/database.php";

$username = $_POST['username'];
$password = md5($_POST['password']);

$sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
$result = mysqli_query($conn, $sql);

echo json_encode([
    "success" => mysqli_num_rows($result) > 0
]);
