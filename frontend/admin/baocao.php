<?php
session_name('STAFF_SESSION');
session_start();

// 1. Auth guard - Kiểm tra quyền truy cập (Admin hoặc Manager)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$allowed_roles = ['admin', 'manager'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    // Nếu là staff thường thì không cho xem báo cáo tổng quát
    header("Location: nhanvien.php");
    exit();
}

require_once "../../backend/config/database.php";

$currentRole  = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

// 2. Load thông tin người dùng từ DB (Để fix lỗi thiếu 'name' trong session)
$stmt = mysqli_prepare($conn, "SELECT id, name, email, role, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $currentUserId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$roleLabel = ['admin' => 'Quản trị viên', 'manager' => 'Quản lý', 'staff' => 'Kỹ thuật viên'];

// 3. Lấy dữ liệu thống kê tổng quát
$sql_summary = "SELECT 
    (SELECT SUM(total) FROM invoices WHERE payment_status = 'paid') as total_revenue,
    (SELECT COUNT(*) FROM repair_tickets) as total_tickets,
    (SELECT COUNT(*) FROM devices WHERE warranty_end_date < NOW()) as expired_devices";
$res_summary = mysqli_query($conn, $sql_summary);
$summary = mysqli_fetch_assoc($res_summary);

// 4. Thống kê hiệu suất nhân viên
$sql_staff_perf = "SELECT u.name, 
    COUNT(rt.id) as assigned,
    SUM(rt.status = 'completed') as completed,
    IFNULL(ROUND((SUM(rt.status = 'completed') / COUNT(rt.id)) * 100, 1), 0) as rate
    FROM users u
    LEFT JOIN repair_tickets rt ON u.id = rt.user_id
    WHERE u.role = 'staff'
    GROUP BY u.id";
$res_staff = mysqli_query($conn, $sql_staff_perf);
$staff_stats = mysqli_fetch_all($res_staff, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Báo cáo & Thống kê – <?= htmlspecialchars($user['name']) ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.default.css" id="theme-stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/baocao.css">
</head>

<body>
<div class="page">
    <header class="header">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <div class="navbar-holder d-flex align-items-center justify-content-between">
                    <div class="navbar-header">
                        <a href="quanly.php" class="navbar-brand">
                            <div class="brand-text brand-big"><img src="img/logo.png" width="140" alt="Logo"></div>
                        </a>
                        <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center mb-0" style="margin-left: auto; gap: 20px;">
                            <li class="nav-item text-white">
                                Xin chào, <strong><?= htmlspecialchars($user['name']) ?></strong>
                            </li>
                            <li class="nav-item">
                                <a href="../../backend/api/logout.php" class="nav-link text-danger font-weight-bold"><i class="fa fa-sign-out"></i> Đăng xuất</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div class="page-content d-flex align-items-stretch">
         <!-- SIDEBAR -->
        <nav class="side-navbar">
            <div class="sidebar-header d-flex align-items-center">
                <div class="avatar">
                    <img src="img/avatar.jpg" alt="..." class="img-fluid rounded-circle">
                </div>
                <div class="title">
                    <h1 class="h4"><?= $roleLabel[$currentRole] ?? 'Nhân viên' ?></h1>
                    <p class="text-muted small mb-0"><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''; ?></p>
                </div>
            </div>
            <hr>
            <ul class="list-unstyled" style="padding: 10px;">
                <li class="mb-2">
                    <a href="quanly.php" class="text-black d-block py-1">
                        <i class="fa fa-home fa-fw"></i> Trang chủ
                    </a>
                </li>
                <?php if ($currentRole === 'admin'): ?>
                <li class="mb-2">
                    <a href="admin.php" class="text-black d-block py-1">
                        <i class="fa fa-shield fa-fw"></i> Dashbroad
                    </a>
                </li>
                <?php endif; ?>
                <li class="mb-2">
                    <a href="tables.php" class="text-black d-block py-1">
                        <i class="fa fa-table fa-fw"></i> Bảng dữ liệu
                    </a>
                </li>
                <li class="mb-2">
                    <a href="invoice.php" class="text-black d-block py-1">
                        <i class="fa fa-file-text fa-fw"></i> Hóa đơn
                    </a>
                </li>
                <li class="mb-2">
                    <a href="email.php" class="text-black d-block py-1">
                        <i class="fa fa-envelope fa-fw"></i> Email
                    </a>
                </li>
                <li class="mb-2">
                    <a href="profile.php" class="text-black d-block py-1">
                        <i class="fa fa-user fa-fw"></i> Hồ sơ
                    </a>
                </li>
                <li class="mb-2">
                    <a href="baocao.php" class="text-black d-block py-1">
                        <i class="fa fa-bar-chart fa-fw"></i> Báo cáo thống kê
                    </a>
                </li>
                <li class="mb-2">
                    <a href="dashboard.php" class="text-black d-block py-1">
                        <i class="fa fa-dashboard fa-fw"></i> Bảng điều khiển
                    </a>
                </li>
            </ul>
        </nav>
        <!-- END SIDEBAR -->

        <div class="content-inner w-100">
            <div class="container-fluid mt-4">
                <h4 class="mb-4 text-gray-800"><i class="fa fa-line-chart"></i> Thống kê dữ liệu quản trị</h4>
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card stat-card bg-gradient-success p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-white-50 small text-uppercase font-weight-bold">Doanh thu đã thu</div>
                                    <div class="h3 font-weight-bold mb-0"><?= number_format($summary['total_revenue'] ?? 0) ?> ₫</div>
                                </div>
                                <i class="fa fa-money fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card stat-card bg-gradient-primary p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-white-50 small text-uppercase font-weight-bold">Tổng số phiếu yêu cầu</div>
                                    <div class="h3 font-weight-bold mb-0"><?= $summary['total_tickets'] ?> phiếu</div>
                                </div>
                                <i class="fa fa-ticket fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card stat-card bg-gradient-danger p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-white-50 small text-uppercase font-weight-bold">Thiết bị hết hạn BH</div>
                                    <div class="h3 font-weight-bold mb-0"><?= $summary['expired_devices'] ?> thiết bị</div>
                                </div>
                                <i class="fa fa-warning fa-2x text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                                <h6 class="m-0 font-weight-bold text-primary"><i class="fa fa-users"></i> Đánh giá hiệu suất nhân viên kỹ thuật</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover border">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Họ tên nhân viên</th>
                                                <th class="text-center">Số phiếu nhận</th>
                                                <th class="text-center">Đã hoàn thành</th>
                                                <th>Tiến độ công việc</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($staff_stats as $row): ?>
                                            <tr>
                                                <td class="font-weight-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                                <td class="text-center"><span class="badge badge-secondary"><?= $row['assigned'] ?></span></td>
                                                <td class="text-center"><span class="badge badge-success"><?= $row['completed'] ?></span></td>
                                                <td style="min-width: 200px;">
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 mr-2" style="height: 12px;">
                                                            <div class="progress-bar bg-info" role="progressbar" style="width: <?= $row['rate'] ?>%"></div>
                                                        </div>
                                                        <span class="small font-weight-bold"><?= $row['rate'] ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="../js/jquery/jquery.min.js"></script>
<script src="../js/bootstrap/bootstrap.min.js"></script>
</body>
</html>