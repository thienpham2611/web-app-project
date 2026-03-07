<?php
// BẮT BUỘC phải gọi session_start() đầu tiên để đọc Session
session_start();

// KIỂM TRA BẢO MẬT: Nếu chưa có session customer_id hoặc role không phải customer
if (!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    // Đẩy ngược người dùng về trang chủ (hoặc trang có form đăng nhập)
    header("Location: index.html");
    exit(); // Dừng thực thi toàn bộ code bên dưới
}

// Lấy tên khách hàng từ Session để hiển thị
$customerName = $_SESSION['customer_name'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Bảng điều khiển - Khách hàng</title>
  
  <link href="css/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>

<body class="bg-light">

<header>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="khachhang.php">IDT Support</a>
      
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item">
            <span class="nav-link text-white">Xin chào, <strong><?php echo htmlspecialchars($customerName); ?></strong>!</span>
          </li>
          <li class="nav-item">
          <a class="nav-link text-danger" href="../backend/api/logout_customer.php">
              <i class="fa fa-sign-out"></i> Đăng xuất
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12 text-center mb-5">
            <h2>Chào mừng bạn đến với Cổng thông tin Khách hàng</h2>
            <p class="text-muted">Tại đây, bạn có thể theo dõi thiết bị, yêu cầu sửa chữa và xem lịch sử bảo hành.</p>
        </div>
    </div>

    <div class="row text-center">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <i class="fa fa-desktop fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Thiết bị của tôi</h5>
                    <p class="card-text">Xem danh sách thiết bị và trạng thái bảo hành hiện tại.</p>
                    <a href="#" class="btn btn-outline-primary btn-sm">Xem chi tiết</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <i class="fa fa-wrench fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Yêu cầu sửa chữa</h5>
                    <p class="card-text">Tạo phiếu yêu cầu sửa chữa mới hoặc theo dõi tiến độ.</p>
                    <a href="#" class="btn btn-outline-success btn-sm">Xem chi tiết</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <i class="fa fa-bell fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">Thông báo</h5>
                    <p class="card-text">Nhận các thông báo về gia hạn phần mềm hoặc nhắc nhở bảo trì.</p>
                    <a href="#" class="btn btn-outline-warning btn-sm">Xem chi tiết</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/jquery/jquery.min.js"></script>
<script src="js/bootstrap/bootstrap.min.js"></script>
</body>
</html>