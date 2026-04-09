<?php
session_name('STAFF_SESSION');
session_start();

// [FIX] Auth guard - Chỉ cho phép Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once "../../backend/config/database.php";

$currentUserId = $_SESSION['user_id'];

// Lấy thông tin Admin
$stmt = mysqli_prepare($conn, "SELECT name, email FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $currentUserId);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Xử lý tìm kiếm & Lọc dữ liệu nâng cao
$search = $_GET['q'] ?? '';
$filter_type = $_GET['type'] ?? 'all';
$search_results = [];

if ($search || $filter_type !== 'all') {
    $q = "%$search%";
    // Truy vấn kết hợp thông tin khách hàng, thiết bị và số lần sửa chữa
    $sql = "SELECT c.name as customer_name, c.phone, c.email as customer_email,
                   d.id as device_id, d.name as device_name, d.model, d.status as device_status,
                   (SELECT COUNT(*) FROM repair_tickets WHERE device_id = d.id) as total_repairs
            FROM devices d
            JOIN customers c ON d.customer_id = c.id
            WHERE (c.name LIKE ? OR c.phone LIKE ? OR d.name LIKE ? OR d.model LIKE ?)";
    
    if ($filter_type == 'pending') {
        $sql .= " AND d.status = 'pending_approval'";
    }

    $stmt_search = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt_search, "ssss", $q, $q, $q, $q);
    mysqli_stmt_execute($stmt_search);
    $search_results = mysqli_fetch_all(mysqli_stmt_get_result($stmt_search), MYSQLI_ASSOC);
}

// Thống kê nhanh cho Admin
$sql_count_pending = "SELECT COUNT(*) as total FROM devices WHERE status = 'pending_approval'";
$res_count = mysqli_query($conn, $sql_count_pending);
$count_pending = mysqli_fetch_assoc($res_count)['total'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Admin - Tra cứu hệ thống</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.default.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tracuu.css"> </head>
<body>
<div class="page">
    <header class="header">
        <nav class="navbar navbar-expand-lg ">
            <div class="container-fluid ">
                <div class="navbar-holder d-flex align-items-center justify-content-between">
                    <div class="navbar-header">
                        <a href="admin.php" class="navbar-brand">
                            <div class="brand-text brand-big hidden-lg-down">
                                <img src="img/logo.png" width="140" alt="Logo" class="img-fluid">
                            </div>
                            <div class="brand-text brand-small">
                                <img src="img/logo.png" alt="Logo" class="img-fluid">
                            </div>
                        </a>
                        <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center mb-0" style="margin-left: auto; gap: 20px;">
                            <li class="nav-item text-white">
                                Xin chào, <strong><?php echo isset($_SESSION['name']) ? $_SESSION['name'] : strtoupper($_SESSION['role']); ?></strong>
                            </li>
                            <li class="nav-item">
                                <a href="#" id="logoutBtn" class="nav-link text-danger font-weight-bold" style="padding: 0;">
                                    <i class="fa fa-sign-out"></i> Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                </div> 
            </div>
        </nav>
    </header>
    <script>
document.getElementById("logoutBtn").addEventListener("click", function(e) {
    e.preventDefault();

    fetch("../../backend/api/logout.php")
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message); 
                window.location.href = "../../frontend/admin/index.php"; 
            } else {
                alert("Logout thất bại!");
            }
        })
        .catch(err => console.error(err));
});
</script>

    <div class="page-content d-flex align-items-stretch">

        <nav class="side-navbar">
            <div class="sidebar-header d-flex align-items-center">
                <div class="avatar"><img src="img/avatar.jpg" alt="..." class="img-fluid rounded-circle"></div>
                <div class="title">
                    <h1 class="h4">Admin</h1>
                </div>
            </div>
            <hr>
                <ul class="list-unstyled" style="padding: 10px;">
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
                    <li class="mb-2">
                        <a href="profile.php" class="text-black d-block py-1">
                            <i class="fa fa-user fa-fw"></i> Hồ sơ
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="nhanvien.php" class="text-black d-block py-1">
                            <i class="fa fa-wrench fa-fw"></i> Nhân viên
                        </a>
                    </li>
                    <li class="mb-2">
                    <a href="tracuu_admin.php" class="text-black d-block py-1">
                        <i class="fa fa-search"></i> Tra cứu
                    </a>
                </li>
                </ul>
            </nav>

        <div class="content-inner w-100 p-4">
            <?php if ($count_pending > 0): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fa fa-warning"></i> Có <strong><?= $count_pending ?></strong> thiết bị đang chờ bạn phê duyệt vào hệ thống.
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-idt-main">
                        <div class="card-header-idt d-flex justify-content-between">
                            <h4 class="title-idt"><i class="fa fa-database"></i> Tra cứu cơ sở dữ liệu thiết bị</h4>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row mb-4">
                                <div class="col-md-6">
                                    <input type="text" name="q" class="form-control" placeholder="Tìm tên khách, SĐT, Tên máy, Số Model..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-3">
                                    <select name="type" class="form-control">
                                        <option value="all">Tất cả thiết bị</option>
                                        <option value="pending" <?= $filter_type == 'pending' ? 'selected' : '' ?>>Chỉ máy chờ duyệt</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-filter"></i> Truy xuất</button>
                                </div>
                            </form>

                            <div id="results">
                                <?php if ($search || $filter_type !== 'all'): ?>
                                    <p class="text-muted ml-2">Tìm thấy <?= count($search_results) ?> bản ghi tương ứng:</p>
                                    
                                    <?php foreach ($search_results as $row): ?>
                                        <div class="data-item border-bottom mb-3 pb-3">
                                            <div class="row">
                                                <div class="col-md-5">
                                                     Khách hàng: <strong><?= htmlspecialchars($row['customer_name']) ?></strong> 
                                                    <small class="text-muted ml-2">(<?= htmlspecialchars($row['phone']) ?>)</small>
                                                    <br><span class="ml-3 text-muted">- Email: <?= htmlspecialchars($row['customer_email']) ?></span>
                                                </div>
                                                <div class="col-md-4">
                                                     Thiết bị: <strong><?= htmlspecialchars($row['device_name']) ?></strong>
                                                    <br><span class="ml-3 text-muted">- Model: <?= htmlspecialchars($row['model']) ?></span>
                                                    <br><span class="ml-3 text-muted">- Trạng thái: 
                                                        <?php if($row['device_status'] == 'pending_approval'): ?>
                                                            <span class="text-warning font-weight-bold">Chờ duyệt</span>
                                                        <?php else: ?>
                                                            <span class="text-success"><?= $row['device_status'] ?></span>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                                <div class="col-md-3 text-right">
                                                    <div class="mb-2">
                                                        <span class="badge badge-info">Sửa chữa: <?= $row['total_repairs'] ?> lần</span>
                                                    </div>
                                                    <?php if($row['device_status'] == 'pending_approval'): ?>
                                                        <button class="btn btn-sm btn-success" onclick="approveDevice(<?= $row['device_id'] ?>)">
                                                            <i class="fa fa-check"></i> Duyệt ngay
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="admin.php?view_device=<?= $row['device_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fa fa-eye"></i> Chi tiết
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($search_results) == 0): ?>
                                        <div class="text-center py-4">
                                            <i class="fa fa-search-minus fa-3x text-light"></i>
                                            <p class="mt-2 text-muted"> Không tìm thấy dữ liệu nào khớp với yêu cầu.</p>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-light text-center">
                                        Vui lòng nhập từ khóa để tra cứu dữ liệu khách hàng & thiết bị.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script>
function approveDevice(deviceId) {
    if(confirm('Xác nhận đưa thiết bị này vào hệ thống chính thức?')) {
        fetch('../../backend/api/approve_device.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: deviceId })
        })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                alert('Đã phê duyệt thành công!');
                location.reload();
            } else {
                alert('Lỗi: ' + res.message);
            }
        });
    }
}
</script>
</body>
</html>