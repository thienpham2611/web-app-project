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
// Thêm thiết bị ################ thật sự là chỗ này còn quá lỗi
$sql_available = "SELECT * FROM devices 
                  WHERE customer_id IS NULL OR customer_id != ? 
                  ORDER BY name ASC";
$stmt_available = mysqli_prepare($conn, $sql_available);
mysqli_stmt_bind_param($stmt_available, "i", $customerId);
mysqli_stmt_execute($stmt_available);
// $available_devices = mysqli_fetch_all(mysqli_stmt_get_result($stmt_available), MYSQLI_ASSOC);
mysqli_stmt_close($stmt_available);

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

// laod từ db
$sql_all_devices = "SELECT id, name FROM devices ORDER BY name ASC";
$stmt_all = mysqli_prepare($conn, $sql_all_devices);
mysqli_stmt_execute($stmt_all);
$all_devices = mysqli_fetch_all(mysqli_stmt_get_result($stmt_all), MYSQLI_ASSOC);

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
                   <button class="btn btn-success mb-3" data-toggle="modal" data-target="#addNewDeviceModal">
                        <i class="fa fa-plus-circle"></i> Thêm thiết bị mới
                    </button>
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
                                        <button class="btn btn-sm btn-outline-primary mr-1" onclick="openRepairModal(<?= $dev['id'] ?>, '<?= htmlspecialchars($dev['name']) ?>')">
                                            <i class="fa fa-wrench"></i> Yêu cầu sửa
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="openWarrantyModal(<?= $dev['id'] ?>, '<?= htmlspecialchars($dev['name']) ?>')">
                                            <i class="fa fa-refresh"></i> Gia hạn BH
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
                    <!-- <h3>Tiến độ sửa chữa thiết bị</h3>
<div class="text-right mb-3">
<button class="btn btn-success" data-toggle="modal" data-target="#createRepairModal">
<i class="fa fa-plus"></i> Tạo yêu cầu sửa chữa
</button>
</div> -->
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


<!-- thêm thiết bị mới -->
<div class="modal fade" id="addNewDeviceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Thêm thiết bị mới</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="addDeviceForm">
                    <div class="form-group">
                        <label>Tên thiết bị / Phần mềm <span class="text-danger">*</span></label>
                        <input type="text" id="device_name" class="form-control" placeholder="Ví dụ: MacBook Pro M3" required>
                    </div>

                    <div class="form-group">
                        <label>Số serial (S/N) <span class="text-danger">*</span></label>
                        <input type="text" id="serial_number" class="form-control" placeholder="SN123456789 hoặc MAC-IDT-001" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ngày mua</label>
                                <input type="date" id="warranty_start_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hạn bảo hành (số ngày)</label>
                                <input type="number" id="warranty_period" class="form-control" placeholder="365" min="1">
                            </div>
                        </div>
                        </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" id="btnAddDevice" onclick="submitNewDevice()" class="btn btn-success">
                    <i class="fa fa-save"></i> Thêm thiết bị
                </button>
            </div>
        </div>
    </div>
</div>
<!-- tạo yêu cầu sữa chữa-->
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
                    <div class="form-group">
                        <label>Thiết bị cần sửa <span class="text-danger">*</span></label>
                        <select name="device_id" id="modal_device_id" class="form-control" required>
                            <option value="">-- Chọn thiết bị --</option>
                            <?php foreach ($devices as $dev): ?>
                                <option value="<?= htmlspecialchars($dev['id']) ?>">
                                    <?= htmlspecialchars($dev['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Mô tả chi tiết lỗi <span class="text-danger">*</span></label>
                        <textarea name="description" id="modal_description" class="form-control" rows="4" required></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="button" id="btnSubmitRepair" onclick="submitRepairTicket()" class="btn btn-success">
                        <i class="fa fa-paper-plane"></i> Gửi yêu cầu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- MODAL YÊU CẦU GIA HẠN BẢO HÀNH -->
<div class="modal fade" id="warrantyRequestModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-refresh text-success"></i> Yêu cầu gia hạn bảo hành</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="warranty_device_id">
                <div class="form-group">
                    <label>Thiết bị</label>
                    <input type="text" id="warranty_device_name" class="form-control" readonly style="background:#e9ecef;font-weight:bold;">
                </div>
                <div class="form-group">
                    <label>Ghi chú (tuỳ chọn)</label>
                    <textarea id="warranty_note" class="form-control" rows="3"
                        placeholder="Ví dụ: Muốn gia hạn thêm 1 năm, liên hệ qua SĐT..."></textarea>
                </div>
                <div class="alert alert-info py-2">
                    <i class="fa fa-info-circle"></i> Yêu cầu sẽ được gửi đến bộ phận kỹ thuật, họ sẽ liên hệ lại với bạn sớm.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success" id="btnGuiGiaHan">
                    <i class="fa fa-paper-plane"></i> Gửi yêu cầu
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openWarrantyModal(deviceId, deviceName) {
    document.getElementById('warranty_device_id').value = deviceId;
    document.getElementById('warranty_device_name').value = deviceName;
    document.getElementById('warranty_note').value = '';
    $('#warrantyRequestModal').modal('show');
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btnGuiGiaHan').addEventListener('click', function() {
        const device_id = document.getElementById('warranty_device_id').value;
        const note = document.getElementById('warranty_note').value.trim();

        fetch('../backend/api/warranty_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ device_id: parseInt(device_id), note })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('✅ ' + res.message);
                $('#warrantyRequestModal').modal('hide');
            } else {
                alert('❌ ' + res.error);
            }
        })
        .catch(() => alert('Lỗi kết nối máy chủ!'));
    });
});
</script>
<script>
function claimDevice(deviceId) {
    if (!confirm('Bạn có chắc muốn thêm thiết bị này vào danh sách của mình không?')) return;

    fetch('../backend/api/claim_my_device.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'device_id=' + deviceId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Thiết bị đã được thêm thành công!');
            $('#addMyDeviceModal').modal('hide');
            location.reload(); // Tải lại trang để cập nhật danh sách thiết bị
        } else {
            alert('Lỗi: ' + (data.error || 'Không thể thêm thiết bị'));
        }
    })
    .catch(() => alert('Lỗi kết nối với server'));
}
</script>
<script>
function submitNewDevice() {
    const btn = document.getElementById('btnAddDevice');
    const originalText = btn.innerHTML;

    const data = {
        name: document.getElementById('device_name').value.trim(),
        serial_number: document.getElementById('serial_number').value.trim(),
        warranty_start_date: document.getElementById('warranty_start_date').value || null,
        warranty_period: parseInt(document.getElementById('warranty_period').value) || 0,
    };

    if (!data.name || !data.serial_number) {
        alert('Vui lòng nhập Tên thiết bị và Số serial (S/N)!');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang thêm...';

    // ĐƯỜNG DẪN ĐÚNG NHẤT (từ frontend/ lên backend/)
    fetch('../backend/api/add_new_device.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams(data),
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Server lỗi: ' + response.status + ' - Kiểm tra đường dẫn');
        }
        return response.text();   // Dùng .text() trước để debug dễ hơn
    })
    .then(text => {
        console.log("Raw response:", text);   // ← Xem trong Console F12
        return JSON.parse(text);
    })
    .then(result => {
        if (result.success) {
            alert(result.message || 'Thiết bị đã được thêm thành công!');
            $('#addNewDeviceModal').modal('hide');
            location.reload();
        } else {
            alert('Lỗi: ' + (result.error || 'Không xác định'));
        }
    })
    .catch(err => {
        console.error("Fetch Error:", err);
        alert('Lỗi kết nối');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>
<script>
let isRepairSubmitting = false;

function submitRepairTicket() {
    if (isRepairSubmitting) return;
    isRepairSubmitting = true;

    const btn = document.getElementById('btnSubmitRepair');
    const originalHTML = btn.innerHTML;

    const data = {
        device_id: document.getElementById('modal_device_id').value,
        description: document.getElementById('modal_description').value.trim()
    };

    if (!data.device_id || !data.description) {
        alert("Vui lòng chọn thiết bị và nhập mô tả lỗi!");
        isRepairSubmitting = false;
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang gửi...';

    fetch('../backend/api/create_repair_ticket.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data),
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert(`✅ Gửi yêu cầu sửa chữa thành công!\n\nMã phiếu: #TICK-${result.ticket_id}`);
            $('#createRepairModal').modal('hide');
            location.reload();
        } else {
            alert("Lỗi: " + (result.error || "Không xác định"));
        }
    })
    .catch(() => alert("Lỗi kết nối. Vui lòng thử lại."))
    .finally(() => {
        isRepairSubmitting = false;
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    });
}

// Reset trạng thái khi đóng modal
$('#createRepairModal').on('hidden.bs.modal', function () {
    isRepairSubmitting = false;
});
</script>
</body>
</html>
