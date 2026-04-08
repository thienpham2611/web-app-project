<?php
session_name('STAFF_SESSION');
session_start();

// Auth guard — đồng bộ với profile.php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}
$allowed_roles = ['admin', 'manager', 'staff'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../index.php");
    exit();
}

require_once "../../backend/config/database.php";

$currentRole  = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

// Load thông tin nhân viên từ DB
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

$roleLabel = ['admin' => 'Quản trị viên', 'manager' => 'Quản lý', 'staff' => 'Nhân viên kỹ thuật'];

// Thống kê tổng quan cho Dashboard
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM repair_tickets WHERE status != 'completed') as dang_xu_ly,
    (SELECT COUNT(*) FROM repair_tickets WHERE status = 'completed') as da_xong,
    (SELECT COUNT(*) FROM devices) as tong_may";
$res_stats = mysqli_query($conn, $sql_stats);
$stats = mysqli_fetch_assoc($res_stats);

// Lấy danh sách việc cần làm (Dạng danh sách - không dùng table)
$sql_tasks = "SELECT rt.*, d.name as device_name 
              FROM repair_tickets rt 
              JOIN devices d ON rt.device_id = d.id 
              WHERE rt.status != 'completed' " . ($currentRole === 'staff' ? "AND rt.user_id = $currentUserId " : "") . "
              ORDER BY rt.created_at ASC LIMIT 5";
$tasks = mysqli_query($conn, $sql_tasks);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bảng điều khiển – <?= htmlspecialchars($user['name']) ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.default.css" id="theme-stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
<div class="page">

    <header class="header">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <div class="navbar-holder d-flex align-items-center justify-content-between">
                    <div class="navbar-header">
                        <a href="<?= $currentRole === 'staff' ? 'nhanvien.php' : 'quanly.php' ?>" class="navbar-brand">
                            <div class="brand-text brand-big hidden-lg-down">
                                <img src="img/logo.png" width="140" alt="Logo" class="img-fluid">
                            </div>
                            <div class="brand-text brand-small">
                                <img src="img/logo.png" alt="Logo" class="img-fluid">
                            </div>
                        </a>
                        <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center mb-0" style="margin-left: auto; gap: 20px;">
                            <li class="nav-item text-white">
                                Xin chào, <strong><?= htmlspecialchars($user['name']) ?></strong>
                                <small class="text-muted ml-1">(<?= $roleLabel[$currentRole] ?? $currentRole ?>)</small>
                            </li>
                            <li class="nav-item">
                                <a href="../../backend/api/logout.php" class="nav-link text-danger font-weight-bold" style="padding: 0;">
                                    <i class="fa fa-sign-out"></i> Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div class="page-content d-flex align-items-stretch">

        <nav class="side-navbar">
            <div class="sidebar-header d-flex align-items-center">
                <div class="avatar">
                    <img src="img/avatar.jpg" alt="..." class="img-fluid rounded-circle">
                </div>
                <div class="title">
                    <h1 class="h4"><?= $roleLabel[$currentRole] ?? 'Nhân viên' ?></h1>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($user['name']) ?></p>
                </div>
            </div>
            <hr>
            <ul class="list-unstyled" style="padding: 10px;">
                <?php if ($currentRole === 'admin'): ?>
                <li class="mb-2">
                    <a href="admin.php" class="text-black d-block py-1">
                        <i class="fa fa-dashboard fa-fw"></i> Dashboard
                    </a>
                </li>
                <li class="mb-2">
                    <a href="quanly.php" class="text-black d-block py-1">
                        <i class="fa fa-cogs fa-fw"></i> Quản lý
                    </a>
                </li>
                <?php elseif ($currentRole === 'manager'): ?>
                <li class="mb-2">
                    <a href="quanly.php" class="text-black d-block py-1">
                        <i class="fa fa-home fa-fw"></i> Trang chủ
                    </a>
                </li>
                <?php else: ?>
                <li class="mb-2">
                    <a href="nhanvien.php" class="text-black d-block py-1">
                        <i class="fa fa-wrench fa-fw"></i> Trang Nhân viên
                    </a>
                </li>
                <?php endif; ?>
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
                    <a href="dashboard.php" class="text-black d-block py-1" style="font-weight: bold;">
                        <i class="fa fa-dashboard fa-fw"></i> Bảng điều khiển
                    </a>
                </li>
                <li class="mb-2">
                    <a href="tracuu_manager.php" class="text-black d-block py-1">
                        <i class="fa fa-search"></i> Tra cứu
                    </a>
                </li>
            </ul>
        </nav>

        <div class="content-inner w-100">
            <div class="p-4">
                <div class="row" style="text-align: center;">
                    <div class="col-md-4">
                        <div class="stat-card bg-primary">
                            <h5>Đang xử lý: <?= $stats['dang_xu_ly'] ?></h5>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card bg-success">
                            <h5>Hoàn thành: <?= $stats['da_xong'] ?></h5>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card bg-info">
                            <h5>Tổng thiết bị: <?= $stats['tong_may'] ?></h5>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header"><strong>CÔNG VIỆC CẦN ƯU TIÊN</strong></div>
                    <div class="card-body">
                        <?php while($row = mysqli_fetch_assoc($tasks)): 
                            $isOverdue = (strtotime($row['created_at']) < strtotime('-3 days'));
                        ?>
                        <div class="task-item p-3 d-flex justify-content-between align-items-center <?= $isOverdue ? 'task-overdue' : '' ?>">
                            <div>
                                <strong><?= htmlspecialchars($row['device_name']) ?></strong>
                                <?php if($isOverdue): ?><span class="text-danger small ml-2">[QUÁ HẠN]</span><?php endif; ?>
                                <p class="mb-0 text-muted small ml-3"><?= htmlspecialchars($row['description']) ?></p>
                            </div>
                            <small class="text-muted">Ngày nhận: <?= date('d/m/Y', strtotime($row['created_at'])) ?></small>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/jquery/jquery.min.js"></script>
<script src="../js/bootstrap/bootstrap.min.js"></script>
<script src="../js/front.js"></script>
</body>
</html>