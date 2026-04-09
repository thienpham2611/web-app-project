<?php
session_name('STAFF_SESSION');
session_start();

// Auth guard - Chỉ cho phép Admin và Manager
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: index.php");
    exit();
}

require_once "../../backend/config/database.php";

$currentRole  = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

// Lấy thông tin Manager
$stmt = mysqli_prepare($conn, "SELECT name FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $currentUserId);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Xử lý tìm kiếm và Bộ lọc trạng thái
$search = $_GET['q'] ?? '';
$filter_status = $_GET['status'] ?? 'all';
$search_results = [];

if ($search || $filter_status !== 'all') {
    $q = "%$search%";
    $sql = "SELECT c.name as customer_name, c.phone, d.id as device_id, d.name as device_name, 
                   d.model, d.status as device_status, d.warranty_expiry,
                   (SELECT COUNT(*) FROM repair_tickets WHERE device_id = d.id) as repair_count
            FROM customers c
            JOIN devices d ON c.id = d.customer_id
            WHERE (c.name LIKE ? OR c.phone LIKE ? OR d.name LIKE ?)";
    
    if ($filter_status !== 'all') {
        $sql .= " AND d.status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
    }
    
    $stmt_search = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt_search, "sss", $q, $q, $q);
    mysqli_stmt_execute($stmt_search);
    $search_results = mysqli_fetch_all(mysqli_stmt_get_result($stmt_search), MYSQLI_ASSOC);
}

// Lấy danh sách thiết bị chờ phê duyệt
$sql_approve = "SELECT d.*, c.name as customer_name FROM devices d 
                JOIN customers c ON d.customer_id = c.id 
                WHERE d.status = 'pending_approval' ORDER BY d.created_at DESC";
$res_approve = mysqli_query($conn, $sql_approve);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Quản lý Tra cứu & Phê duyệt</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.default.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tracuu.css">
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
                    <h1 class="h4"><?= $roleLabel[$currentRole] ?? 'Quản lý' ?></h1>
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

        <div class="content-inner w-100 p-4">
            <div class="card card-idt-main mb-4 border-left-warning">
                <div class="card-header-idt d-flex justify-content-between">
                    <h4 class="title-idt"><i class="fa fa-bell-o"></i> Yêu cầu phê duyệt thiết bị mới</h4>
                    <span class="badge badge-warning"><?= mysqli_num_rows($res_approve) ?> yêu cầu</span>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($res_approve) > 0): ?>
                        <?php while($item = mysqli_fetch_assoc($res_approve)): ?>
                            <div class="data-item d-flex justify-content-between align-items-center mb-2 p-3 border rounded">
                                <div>
                                     <strong><?= htmlspecialchars($item['customer_name']) ?></strong> yêu cầu đăng ký:
                                    <span class="text-primary ml-1"><?= htmlspecialchars($item['name']) ?> (<?= htmlspecialchars($item['model']) ?>)</span>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-danger mr-2" onclick="rejectDevice(<?= $item['id'] ?>)">Từ chối</button>
                                    <button class="btn btn-sm btn-success" onclick="approveDevice(<?= $item['id'] ?>)">Phê duyệt</button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0"> Không có thiết bị nào đang chờ duyệt.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card card-idt-main">
                <div class="card-header-idt">
                    <h4 class="title-idt"><i class="fa fa-filter"></i> Hệ thống tra cứu & Theo dõi</h4>
                </div>
                <div class="card-body">
                    <form method="GET" class="row mb-4">
                        <div class="col-md-5">
                            <label class="small font-weight-bold">Từ khóa tìm kiếm</label>
                            <input type="text" name="q" class="form-control" placeholder="Tên khách, SĐT, Tên máy..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="small font-weight-bold">Trạng thái thiết bị</label>
                            <select name="status" class="form-control">
                                <option value="all">-- Tất cả trạng thái --</option>
                                <option value="active" <?= $filter_status == 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
                                <option value="repairing" <?= $filter_status == 'repairing' ? 'selected' : '' ?>>Đang sửa chữa</option>
                                <option value="broken" <?= $filter_status == 'broken' ? 'selected' : '' ?>>Hỏng/Cần thay thế</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> Lọc dữ liệu</button>
                        </div>
                    </form>

                    <div id="results">
                        <?php if ($search || $filter_status !== 'all'): ?>
                            <h6 class="mb-3 text-secondary">Tìm thấy <?= count($search_results) ?> kết quả phù hợp:</h6>
                            <?php foreach ($search_results as $row): ?>
                                <div class="data-item p-3 border-bottom mb-2">
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                             <strong><?= htmlspecialchars($row['customer_name']) ?></strong><br>
                                            <small class="text-muted"><i class="fa fa-phone"></i> <?= htmlspecialchars($row['phone']) ?></small>
                                        </div>
                                        <div class="col-md-4">
                                            - Thiết bị: <strong><?= htmlspecialchars($row['device_name']) ?></strong><br>
                                            - Model: <?= htmlspecialchars($row['model']) ?>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <span class="badge badge-pill badge-light border">Lần sửa: <?= $row['repair_count'] ?></span><br>
                                            <small class="text-danger"><?= (strtotime($row['warranty_expiry']) < time()) ? 'Hết BH' : 'Còn BH' ?></small>
                                        </div>
                                        <div class="col-md-2 text-right">
                                            <a href="quanly.php?device_id=<?= $row['device_id'] ?>" class="btn btn-sm btn-info">Chi tiết</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function approveDevice(deviceId) {
    if(confirm('Phê duyệt thiết bị này vào hệ thống quản lý?')) {
        sendApproval(deviceId, 'active');
    }
}

function rejectDevice(deviceId) {
    if(confirm('Từ chối thiết bị này? Dữ liệu sẽ bị xóa tạm thời.')) {
        // Tùy chọn xóa hoặc đổi status thành 'rejected'
        sendApproval(deviceId, 'rejected');
    }
}

function sendApproval(id, status) {
    fetch('../../backend/api/approve_device.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: id, status: status })
    })
    .then(r => r.json())
    .then(res => {
        if(res.success) location.reload();
        else alert('Lỗi: ' + res.message);
    });
}
</script>
</body>
</html>