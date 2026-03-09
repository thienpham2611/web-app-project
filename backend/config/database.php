<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "device_management";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Kết nối thất bại");
}