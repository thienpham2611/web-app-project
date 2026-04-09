<?php
session_name('STAFF_SESSION');
session_start();

$internal_roles = ['admin', 'manager', 'staff'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $internal_roles)) {
    header("Location: index.php"); exit();
}

require_once "../../backend/config/database.php";
$currentRole   = $_SESSION['role'];
$currentUserId = intval($_SESSION['user_id'] ?? 0);
$roleLabel     = ['admin'=>'Quản trị viên','manager'=>'Quản lý','staff'=>'Nhân viên kỹ thuật'];

$stmt = mysqli_prepare($conn, "SELECT name FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $currentUserId);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$userName = $user['name'] ?? strtoupper($currentRole);
?>
<!DOCTYPE html>
<html>

<head>
    
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">

    <title>Hệ thống quản lý sửa chữa & bảo hành thiết bị – phần mềm</title>
    <link rel="shortcut icon" href="img/favicon.png">
    
    <!-- global stylesheets -->
    <link href="https://fonts.googleapis.com/css?family=Roboto+Condensed" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/font-icon-style.css">
    <link rel="stylesheet" href="css/style.default.css" id="theme-stylesheet">

    <!-- Core stylesheets -->
</head>

<body> 

<!--MAIN NAVBAR-->
    <header class="header">
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <div class="navbar-holder d-flex align-items-center justify-content-between">
                <div class="navbar-header d-flex align-items-center w-100">
                    <a href="quanly.php" class="navbar-brand">
                        <img src="img/logo.png" width="140" class="img-fluid">
                    </a>
                    <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center mb-0" style="margin-left:auto;gap:20px;">
                        <li class="nav-item text-white">
                            Xin chào, <strong><?= htmlspecialchars($userName) ?></strong>
                            <small class="text-muted ml-1">(<?= $roleLabel[$currentRole] ?? $currentRole ?>)</small>
                        </li>
                        <li class="nav-item dropdown" id="staff-notif-bell" style="list-style:none;">
                            <a href="#" class="dropdown-toggle position-relative nav-link" data-toggle="dropdown"
                               style="color:#ff9800;padding:0 5px;">
                                <i class="fa fa-bell fa-lg"></i>
                                <span id="staff-notif-badge" class="badge badge-danger"
                                      style="position:absolute;top:-4px;right:-2px;font-size:9px;padding:2px 4px;display:none;">0</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow p-0"
                                 style="width:300px;max-height:360px;overflow-y:auto;">
                                <div class="px-3 py-2 border-bottom bg-light d-flex justify-content-between align-items-center">
                                    <strong><i class="fa fa-bell text-warning"></i> Thông báo</strong>
                                    <a href="#" onclick="markAllStaffNotifRead(); return false;" class="small text-muted">Đánh dấu tất cả</a>
                                </div>
                                <div id="staff-notif-list">
                                    <div class="text-center text-muted py-3 small"><i class="fa fa-spinner fa-spin"></i> Đang tải...</div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
</header>

<!--PAGE CONTENT-->
    <div class="page-content d-flex align-items-stretch">

        <!--***** SIDE NAVBAR *****-->
                <nav class="side-navbar">
            <div class="sidebar-header d-flex align-items-center">
                <div class="avatar"><img src="img/avatar.jpg" class="img-fluid rounded-circle"></div>
                <div class="title">
                    <h1 class="h4"><?= $roleLabel[$currentRole] ?? '' ?></h1>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($userName) ?></p>
                </div>
            </div>
            <hr>
            <ul class="list-unstyled" style="padding:10px;">
                <?php if ($currentRole === 'admin'): ?>
                <li class="mb-2"><a href="admin.php" class="text-black d-block py-1"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a></li>
                <?php endif; ?>
                <li class="mb-2"><a href="quanly.php" class="text-black d-block py-1"><i class="fa fa-home fa-fw"></i> Trang chủ</a></li>
                <li class="mb-2"><a href="tables.php" class="text-black d-block py-1"><i class="fa fa-table fa-fw"></i> Bảng dữ liệu</a></li>
                <li class="mb-2"><a href="invoice.php" class="text-black d-block py-1"><i class="fa fa-file-text fa-fw"></i> Hóa đơn</a></li>
                <li class="mb-2"><a href="email.php" class="text-black d-block py-1"><i class="fa fa-envelope fa-fw"></i> Email</a></li>
                <li class="mb-2"><a href="profile.php" class="text-black d-block py-1"><i class="fa fa-user fa-fw"></i> Hồ sơ</a></li>
                <li class="mb-2"><a href="baocao.php" class="text-black d-block py-1"><i class="fa fa-bar-chart fa-fw"></i> Báo cáo</a></li>
                <li class="mb-2"><a href="dashboard.php" class="text-black d-block py-1"><i class="fa fa-dashboard fa-fw"></i> Bảng điều khiển</a></li>
                <?php if ($currentRole === 'staff'): ?>
                <li class="mb-2"><a href="nhanvien.php" class="text-black d-block py-1"><i class="fa fa-wrench fa-fw"></i> Trang Nhân viên</a></li>
                <?php endif; ?>
                <li class="mb-2">
                    <?php $tracuu = ($currentRole==='admin') ? 'tracuu_admin.php' : (($currentRole==='staff') ? 'tracuu_staff.php' : 'tracuu_manager.php'); ?>
                    <a href="<?= $tracuu ?>" class="text-black d-block py-1"><i class="fa fa-search fa-fw"></i> Tra cứu</a>
                </li>
            </ul>
            <div style="position:absolute;bottom:20px;left:0;right:0;padding:0 10px;">
                <a href="../../backend/api/logout.php"
                   class="d-block py-2 px-3 text-danger font-weight-bold"
                   style="border-top:1px solid #eee;">
                    <i class="fa fa-sign-out"></i> Đăng xuất
                </a>
            </div>
        </nav>
        <div class="content-inner">

        <div class="content-inner chart-cont">

            <!--***** CONTENT *****-->     
<div class="row">
    <div class="col-md-12">
        <div class="card p-3 shadow-sm">
            <h3 class="mb-4 text-primary"><i class="fa fa-list"></i> Danh sách Thiết bị & Phần mềm Đang Quản lý</h3>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th>Mã Thiết bị</th>
                            <th>Tên Mặt hàng</th>
                            <th>Khách hàng</th>
                            <th>Ngày Hết hạn</th>
                            <th class="text-center">Tình trạng</th>
                            <th class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-danger">
                            <td><strong>DE-001</strong></td>
                            <td>Máy chủ Dell PowerEdge</td>
                            <td>Ngân hàng ACB</td>
                            <td>15/02/2024</td>
                            <td class="text-center">
                                <span class="badge badge-pill badge-danger">ĐÃ HẾT HẠN</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-danger"><i class="fa fa-refresh"></i> Gia hạn ngay</button>
                            </td>
                        </tr>

                        <tr class="table-warning">
                            <td><strong>SW-042</strong></td>
                            <td>Phần mềm kế toán IDT</td>
                            <td>Công ty TNHH Hải Nam</td>
                            <td>25/11/2024</td>
                            <td class="text-center">
                                <span class="badge badge-pill badge-warning text-dark">SẮP HẾT HẠN</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-warning"><i class="fa fa-bell"></i> Gửi nhắc nhở</button>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>DE-105</strong></td>
                            <td>Switch Cisco 24 Port</td>
                            <td>Trường ĐH Bách Khoa</td>
                            <td>10/10/2026</td>
                            <td class="text-center">
                                <span class="badge badge-pill badge-info">ĐANG BẢO HÀNH</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary"><i class="fa fa-eye"></i> Xem chi tiết</button>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>LP-200</strong></td>
                            <td>Laptop ThinkPad X1</td>
                            <td>FPT Software</td>
                            <td>12/12/2025</td>
                            <td class="text-center">
                                <span class="badge badge-pill badge-success">HOẠT ĐỘNG</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary"><i class="fa fa-edit"></i> Cập nhật</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 p-3 bg-light border-left border-primary">
                <small class="text-muted">
                    <i class="fa fa-info-circle"></i> <strong>Lưu ý dành cho Trưởng ban:</strong> 
                    Hệ thống tự động đánh dấu màu đỏ cho các thiết bị đã quá hạn. Nhân viên phụ trách sẽ nhận được thông báo qua Email để tiến hành làm dịch vụ gia hạn bảo hành cho khách hàng.
                </small>
            </div>
        </div>
    </div>
</div>

    <!--Global Javascript -->
    <script src="js/jquery.min.js"></script>
    <script src="js/popper/popper.min.js"></script>
    <script src="js/tether.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.cookie.js"></script>
    <script src="js/jquery.validate.min.js"></script> 
    <script src="js/chart.min.js"></script> 
    <script src="js/front.js"></script> 
    
    <!--Core Javascript -->
    <script>
        new Chart(document.getElementById("myChart-nav").getContext('2d'), {
          type: 'doughnut',
          data: {
            labels: ["M", "T", "W", "T", "F", "S", "S"],
            datasets: [{
              backgroundColor: [
                "#2ecc71",
                "#3498db",
                "#95a5a6",
                "#9b59b6",
                "#f1c40f",
                "#e74c3c",
                "#34495e"
              ],
              data: [12, 19, 3, 17, 28, 24, 7]
            }]
          },
          options: {
              legend: { display: false },
              title: {
                display: true,
                text: ''
               } 
            }
        });
    </script> 
    <script src="js/manager_actions.js"></script>
</body>

</html>
