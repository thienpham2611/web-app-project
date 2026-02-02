<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "web_project";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Kết nối thất bại");
}