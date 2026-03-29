<?php
// KIỂM TRA BẢO MẬT: Ngăn truy cập lậu hoặc bất thường
session_start();
if (!isset($_SESSION['customer_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: index.php");
    exit();
}

// Gọi file cấu hình database để lấy thông tin của khách hàng
require_once "../backend/config/database.php"; 
$customerId = $_SESSION['customer_id'];

$stmt = mysqli_prepare($conn, "SELECT name, email, phone, address FROM customers WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $customerId);
mysqli_stmt_execute($stmt);
$customerData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Lấy tên khách hàng từ Database và lưu vào Session để hiển thị trên Navbar
$customerName = $customerData['name'];
$_SESSION['customer_name'] = $customerName;

// Lấy danh sách thiết bị và phần mềm
$sql_dev = "SELECT * FROM devices WHERE customer_id = ? ORDER BY id DESC";
$stmt_dev = mysqli_prepare($conn, $sql_dev);
mysqli_stmt_bind_param($stmt_dev, "i", $customerId);
mysqli_stmt_execute($stmt_dev);
$devices = mysqli_fetch_all(mysqli_stmt_get_result($stmt_dev), MYSQLI_ASSOC);

// Lấy danh sách phiếu sửa chữa
$sql_tick = "SELECT rt.id, rt.description, rt.status, rt.progress, d.name as device_name 
             FROM repair_tickets rt 
             JOIN devices d ON rt.device_id = d.id 
             WHERE d.customer_id = ? ORDER BY rt.created_at DESC";
$stmt_tick = mysqli_prepare($conn, $sql_tick);
mysqli_stmt_bind_param($stmt_tick, "i", $customerId);
mysqli_stmt_execute($stmt_tick);
$tickets = mysqli_fetch_all(mysqli_stmt_get_result($stmt_tick), MYSQLI_ASSOC);

// Lấy lịch sử gia hạn
$sql_ext = "SELECT we.created_at, we.old_end_date, we.new_end_date, we.cost, we.note, d.name as device_name 
            FROM warranty_extensions we 
            JOIN devices d ON we.device_id = d.id 
            WHERE d.customer_id = ? ORDER BY we.created_at DESC";
$stmt_ext = mysqli_prepare($conn, $sql_ext);
mysqli_stmt_bind_param($stmt_ext, "i", $customerId);
mysqli_stmt_execute($stmt_ext);
$extensions = mysqli_fetch_all(mysqli_stmt_get_result($stmt_ext), MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Hệ thống quản lý sửa chữa & bảo hành thiết bị – phần mềm</title>
    <link rel="shortcut icon" href="img/logo.png">
  
  <link href="https://fonts.googleapis.com/css?family=Roboto+Condensed:300,400,700" rel="stylesheet">
  <link href="css/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="css/animate/animate.min.css">
  <link rel="stylesheet" href="css/owl-carousel/owl.carousel.min.css">
  <link rel="stylesheet" href="css/owl-carousel/owl.theme.default.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>

<body class="bg-light">

<header>
      <nav class="navbar navbar-expand-lg navbar-light" id="mainNav">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.php">
        <img src="img/logo.png" alt="logo" width="140">
      </a>

      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="index.php">Trang chủ</a></li>
            <li class="nav-item"><a class="nav-link" href="about.php">Giới thiệu</a></li>
            <li class="nav-item"><a class="nav-link" href="services.php">Dịch vụ</a></li>

            <?php if(isset($_SESSION['customer_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'customer'): ?>
                <li class="nav-item">
                    <a class="nav-link text-success" href="khachhang.php">
                        <i class="fa fa-user-circle"></i> <strong><?php echo htmlspecialchars($_SESSION['customer_name']); ?></strong>
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" style="color: #ff9800;">
                        <i class="fa fa-bell"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow" style="width:250px;">
                        <a class="dropdown-item" href="#">Thiết bị của bạn đã sửa xong</a>
                        <a class="dropdown-item text-muted small" href="#">Xem tất cả thông báo...</a>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-danger" href="../backend/api/logout_customer.php">
                        <i class="fa fa-sign-out"></i> Đăng xuất
                    </a>
                </li>

            <?php else: ?>
                <li class="nav-item">
                    <?php if(basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
                        <a href="#" class="nav-link smooth-scroll" data-toggle="modal" data-target="#login-modal">Đăng nhập</a>
                    <?php else: ?>
                        <a class="nav-link smooth-scroll" href="index.php?show_login=true">Đăng nhập</a>
                    <?php endif; ?>
                </li>
            <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
</header>

<div id="home-p" class="home-p pages-head2 text-center">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1 class="wow fadeInUp" data-wow-delay="0.1s">XIN CHÀO, <span class="text-blue"><strong><?php echo mb_strtoupper(htmlspecialchars($customerName), 'UTF-8'); ?></strong></span></h1>
                <div class="heading-border-light"></div> <p class="wow fadeInUp" data-wow-delay="0.3s">Chào mừng bạn quay trở lại. Hãy quản lý các thiết bị và yêu cầu của bạn bên dưới.</p>
            </div>
        </div>
    </div>
</div>
<div class="py-5 container mt-5">
    <div class="row">
        <div class="col-md-12 text-center mb-5">
            <h2>CHÀO MỪNG BẠN ĐẾN VỚI CỔNG THÔNG TIN KHÁCH HÀNG</h2>
            <p class="text-muted">Tại đây, bạn có thể theo dõi thiết bị, yêu cầu sửa chữa và xem lịch sử bảo hành.</p>
        </div>
    </div>
</div>
<section id="customer-dashboard" class="bg-light">
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="customer-stats d-flex align-items-center justify-content-between wow fadeIn">
                    <div>
                        <h5><i class="fa fa-user-circle text-success"></i> Thông tin cá nhân</h5>
                        
                        <p class="mb-0">Họ và tên: <strong><?php echo htmlspecialchars($customerData['name']); ?></strong></p>
                        
                        <p class="mb-0">Email: <?php echo htmlspecialchars($customerData['email']); ?></p>
                        <p class="mb-0">SĐT: <?php echo htmlspecialchars($customerData['phone'] ?: 'Chưa cập nhật'); ?></p>
                        <p class="mb-0">Địa chỉ: <?php echo htmlspecialchars($customerData['address'] ?: 'Chưa cập nhật'); ?></p>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-general btn-green" data-toggle="modal" data-target="#profileModal">Cập nhật hồ sơ</button>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-pills nav-customer mb-4 justify-content-center" id="pills-tab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="pills-devices-tab" data-toggle="pill" href="#pills-devices" role="tab">Thiết bị & Phần mềm</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pills-repairs-tab" data-toggle="pill" href="#pills-repairs" role="tab">Yêu cầu sửa chữa</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pills-history-tab" data-toggle="pill" href="#pills-history" role="tab">Lịch sử gia hạn</a>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            
            <div class="tab-pane fade show active" id="pills-devices" role="tabpanel">
                <div class="card card-dashboard p-4 bg-white">
                    <table class="table table-hover mt-2">
                        <thead class="bg-light">
                            <tr>
                                <th>Thiết bị / Phần mềm</th>
                                <th>Ngày mua</th>
                                <th>Hạn bảo hành</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($devices)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Bạn chưa có thiết bị hay phần mềm nào.</td></tr>
                            <?php else: ?>
                                <?php foreach($devices as $dev): 
                                    $end_date = strtotime($dev['warranty_end_date']);
                                    $days_left = ($end_date - time()) / 86400;
                                    
                                    $date_class = 'text-success'; $status_text = 'Đang bảo hành'; $badge_class = 'badge-success';
                                    if ($days_left < 0) {
                                        $date_class = 'text-danger font-weight-bold'; $status_text = 'Đã hết hạn'; $badge_class = 'badge-danger';
                                    } elseif ($days_left <= 30) {
                                        $date_class = 'text-warning font-weight-bold'; $status_text = 'Sắp hết hạn'; $badge_class = 'badge-warning';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($dev['name']) ?></strong><br>
                                        <small class="text-muted">S/N: <?= htmlspecialchars($dev['serial_number']) ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($dev['warranty_start_date'])) ?></td>
                                    <td class="<?= $date_class ?>"><?= date('d/m/Y', $end_date) ?></td>
                                    <td><span class="badge <?= $badge_class ?> p-2"><?= $status_text ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="openRepairModal(<?= $dev['id'] ?>, '<?= htmlspecialchars($dev['name']) ?>')">
                                            <i class="fa fa-wrench"></i> Yêu cầu sửa
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="pills-repairs" role="tabpanel">
                <div class="card card-dashboard p-4 bg-white">
                    <table class="table table-hover mt-2">
                        <thead class="bg-light">
                            <tr>
                                <th>Mã phiếu</th>
                                <th>Tên thiết bị</th>
                                <th>Ghi chú lỗi</th>
                                <th>Tiến độ</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($tickets)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Bạn chưa có phiếu sửa chữa nào.</td></tr>
                            <?php else: ?>
                                <?php foreach($tickets as $tick): ?>
                                <tr>
                                    <td><strong>#TICK-<?= $tick['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($tick['device_name']) ?></td>
                                    <td><?= htmlspecialchars($tick['description'] ?? 'Không có mô tả') ?></td>
                                    <td class="align-middle">
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-info" style="width: <?= $tick['progress'] ?>%;"></div>
                                        </div>
                                        <small class="font-weight-bold"><?= $tick['progress'] ?>%</small>
                                    </td>
                                    <td>
                                        <?php 
                                            $status_badge = 'badge-secondary'; $status_vi = 'Chờ xử lý';
                                            if($tick['status'] == 'repairing') { $status_badge = 'badge-warning'; $status_vi = 'Đang sửa chữa'; }
                                            if($tick['status'] == 'completed') { $status_badge = 'badge-success'; $status_vi = 'Đã hoàn thành'; }
                                            if($tick['status'] == 'cancelled') { $status_badge = 'badge-danger'; $status_vi = 'Đã hủy'; }
                                        ?>
                                        <span class="badge <?= $status_badge ?> p-2"><?= $status_vi ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="pills-history" role="tabpanel">
                <div class="card card-dashboard p-4 bg-white">
                    <table class="table table-hover mt-2">
                        <thead class="bg-light">
                            <tr>
                                <th>Ngày giao dịch</th>
                                <th>Tên thiết bị</th>
                                <th>Thay đổi thời hạn</th>
                                <th>Chi phí</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($extensions)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Bạn chưa có lịch sử gia hạn nào.</td></tr>
                            <?php else: ?>
                                <?php foreach($extensions as $ext): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($ext['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($ext['device_name']) ?></td>
                                    <td>
                                        <del class="text-muted"><?= date('d/m/Y', strtotime($ext['old_end_date'])) ?></del>
                                        <i class="fa fa-arrow-right mx-2 text-secondary"></i>
                                        <strong class="text-success"><?= date('d/m/Y', strtotime($ext['new_end_date'])) ?></strong>
                                    </td>
                                    <td><?= number_format($ext['cost'], 0, ',', '.') ?> đ</td>
                                    <td><?= htmlspecialchars($ext['note']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cập nhật thông tin cá nhân</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-update-profile">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Họ và tên</label>
                        <input type="text" id="prof_name" class="form-control" value="<?php echo htmlspecialchars($customerData['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="text" id="prof_phone" class="form-control" value="<?php echo htmlspecialchars($customerData['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <textarea id="prof_address" class="form-control" rows="2"><?php echo htmlspecialchars($customerData['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-green">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<footer> 
        <div id="footer-s1" class="footer-s1">
          <div class="footer">
            <div class="container-fluid" style="padding-right:80px;">
              <div class="row" style="margin:0; justify-content:flex-end;">
                <div class="col-md-3 col-sm-6" style="margin-right:80px;">
                  <div><img src="img/logo.png" alt="" class="img-fluid"></div>
                  <ul class="list-unstyled comp-desc-f">
                     <li><p>Chúng tôi cung cấp dịch vụ bảo hành, sửa chữa và bảo trì
                    chuyên nghiệp, nhanh chóng và uy tín cho khách hàng.</p></li> 
                  </ul><br> 
                </div>
                <div class="col-md-3 col-sm-6" style="margin-right:80px;">
                  <div class="heading-footer"><h2>Số 3/36 Trần Điền - Phường Phương Liệt - Hà Nội</h2></div>
                  <ul class="list-unstyled link-list">
                    <li><a class="fa fa-envelope" href="index.php"> contact@idtvietnam.vn</a><i class="fa fa-angle-right"></i></li> 
                    <li><a class="fa fa-phone" href="index.php"> 0243.2222.720</a><i class="fa fa-angle-right"></i></li> 
                    <li><a class="fa fa-phone" href="index.php"> Hotline: 0904.288.822</a><i class="fa fa-angle-right"></i></li> 
                    <li><a class="fa fa-phone" href="index.php"> VPHN: 0246.291.1401/0246.326.1898</a><i class="fa fa-angle-right"></i></li> 
                    <li><a class="fa fa-phone" href="index.php"> VPMN: 0282.229.5501/0938.651.659</a><i class="fa fa-angle-right"></i></li> 
                  </ul>
                </div>
                <div class="col-md-3 col-sm-6" style="margin-right:80px;">
                  <div class="heading-footer"><h2>Hỗ trợ kỹ thuật</h2></div>
                  <address class="address-details-f">
                    tech.support@idtvietnam.vn<br>
                    Miền Bắc - Miền Trung: 024.62.911.224<br>
                    Miền Nam: 0938.651.659<br>
                  </address>  
                  <ul class="list-inline social-icon-f top-data">
                    <li><a href="#" target="_empty"><i class="fa top-social fa-facebook"></i></a></li>
                    <li><a href="#" target="_empty"><i class="fa top-social fa-twitter"></i></a></li>
                    <li><a href="#" target="_empty"><i class="fa top-social fa-google-plus"></i></a></li> 
                  </ul>
                </div>
                </div>
            </div></div> 
        </div>

        <div id="footer-bottom">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div id="footer-copyrights">
                            <p>&copy; 2026 IDT Design. All rights reserved. <a href="#">Chính sách bảo mật</a> <a href="#">Điều khoản bảo mật</a></p>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
        <a href="#" id="back-to-top" class="btn btn-sm btn-green btn-back-to-top smooth-scrolls hidden-sm hidden-xs" title="home" role="button">
            <i class="fa fa-angle-up"></i>
        </a>
    </footer>

<script src="js/jquery/jquery.min.js"></script>
<script src="js/popper/popper.min.js"></script>
<script src="js/bootstrap/bootstrap.min.js"></script>
<script src="js/wow/wow.min.js"></script>
<script src="js/custom.js"></script>
<script src="js/auth.js"></script>

<div class="modal fade" id="createRepairModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo yêu cầu sửa chữa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <form id="repairForm">
                <div class="modal-body">
                    <input type="hidden" id="modal_device_id" name="device_id">

                    <div class="form-group">
                        <label>Thiết bị cần sửa</label>
                        <input type="text" id="modal_device_name" class="form-control" readonly style="background-color: #e9ecef; font-weight: bold;">
                    </div>

                    <div class="form-group">
                        <label>Mô tả chi tiết lỗi <span class="text-danger">*</span></label>
                        <textarea name="description" id="modal_description" class="form-control" 
                                  rows="4" placeholder="Ví dụ: Máy dạo này hay bị màn hình xanh, quạt kêu to..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="button" id="btnGuiYeuCau" class="btn btn-success">
                        <i class="fa fa-paper-plane"></i> Gửi yêu cầu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
