<?php
session_start();
session_unset(); // Xóa toàn bộ biến session
session_destroy(); // Hủy session
header("Location: ../../frontend/index.php"); // Trả về trang chủ
exit();
?>