<?php
session_start();

// 1. Xóa sạch mọi biến được lưu trong Session (role, id, name...)
session_unset();

// 2. Phá hủy hoàn toàn Session hiện tại
session_destroy();

// 3. Đá người dùng về thẳng trang đăng nhập của nội bộ
header("Location: ../../frontend/admin/index.php");
exit(); // Bắt buộc phải có exit() sau lệnh header
?>