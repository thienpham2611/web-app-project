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
    <link rel="stylesheet" href="css/apps/invoice.css"> 
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

            <!--***** CONTENT *****-->     
<div class="container-fluid">
    <div class="card p-4 shadow-sm">
        <div class="row align-items-center mb-4">
            <div class="col-sm-6">
                <img src="img/logo.png" alt="Logo" style="max-width: 200px;">
            </div>
            <div class="col-sm-6 text-sm-right mt-3 mt-sm-0">
                <h2 class="text-uppercase font-weight-bold">Hóa Đơn</h2>
                <p class="mb-0">Mã đơn hàng: <strong>#12345</strong></p>
                <p>Ngày đặt: 07/03/2026</p>
            </div>
        </div>

        <hr>

        <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <h5 class="text-primary font-weight-bold">Hóa đơn gửi đến:</h5>
                <address class="border-left pl-3">
                    <strong>Nguyễn Văn A</strong><br>
                    Số 123 Đường Lê Lợi, Quận 1<br>
                    TP. Hồ Chí Minh, Việt Nam<br>
                    Email: nguyenvana@email.com
                </address>
            </div>
            <div class="col-md-6 text-md-right">
                <h5 class="text-primary font-weight-bold">Địa chỉ giao hàng:</h5>
                <address class="border-right pr-3 d-inline-block text-left text-md-right">
                    <strong>Trần Thị B</strong><br>
                    Số 456 Đường Nguyễn Huệ, Quận 1<br>
                    TP. Hồ Chí Minh, Việt Nam<br>
                    Hạn thanh toán: Khi nhận hàng
                </address>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <p><strong>Phương thức thanh toán:</strong> Visa (**** 4242)</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Sản phẩm / Linh kiện</th>
                                <th class="text-center">Đơn giá</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-right">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Màn hình BS-200</td>
                                <td class="text-center">250.000đ</td>
                                <td class="text-center">1</td>
                                <td class="text-right">250.000đ</td>
                            </tr>
                            <tr>
                                <td>Pin BS-400</td>
                                <td class="text-center">150.000đ</td>
                                <td class="text-center">3</td>
                                <td class="text-right">450.000đ</td>
                            </tr>
                            <tr>
                                <td>Mainboard BS-1000</td>
                                <td class="text-center">2.000.000đ</td>
                                <td class="text-center">1</td>
                                <td class="text-right">2.000.000đ</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="border-0"></td>
                                <td class="text-center font-weight-bold">Tạm tính:</td>
                                <td class="text-right">2.700.000đ</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="border-0"></td>
                                <td class="text-center font-weight-bold">Phí vận chuyển:</td>
                                <td class="text-right">30.000đ</td>
                            </tr>
                            <tr class="h4">
                                <td colspan="2" class="border-0"></td>
                                <td class="text-center font-weight-bold text-danger">TỔNG CỘNG:</td>
                                <td class="text-right font-weight-bold text-danger">2.730.000đ</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12 text-right">
                <button onclick="window.print();" class="btn btn-outline-primary btn-lg mr-2">
                    <i class="fa fa-print"></i> In Hóa Đơn
                </button>
                <button class="btn btn-success btn-lg px-5">
                    Xác nhận Hóa Đơn
                </button>
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