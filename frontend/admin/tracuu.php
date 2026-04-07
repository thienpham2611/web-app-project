<?php
session_name('STAFF_SESSION');
session_start();

// Auth guard - Đồng bộ với hệ thống
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

require_once "../../backend/config/database.php";

$currentRole  = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

// Lấy thông tin user để hiển thị Header/Sidebar
$stmt = mysqli_prepare($conn, "SELECT name, email FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $currentUserId);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$roleLabel = ['admin' => 'Quản trị viên', 'manager' => 'Quản lý', 'staff' => 'Nhân viên kỹ thuật'];

// Xử lý tìm kiếm
$search = $_GET['q'] ?? '';
$search_results = [];
if ($search) {
    $q = "%$search%";
    $sql = "SELECT c.name as customer_name, c.phone, d.id as device_id, d.name as device_name, 
                   d.model, d.status as device_status,
                   (SELECT COUNT(*) FROM repair_tickets WHERE device_id = d.id) as repair_count
            FROM customers c
            JOIN devices d ON c.id = d.customer_id
            WHERE c.name LIKE ? OR c.phone LIKE ? OR d.name LIKE ?";
    $stmt_search = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt_search, "sss", $q, $q, $q);
    mysqli_stmt_execute($stmt_search);
    $search_results = mysqli_fetch_all(mysqli_stmt_get_result($stmt_search), MYSQLI_ASSOC);
}

// Lấy danh sách thiết bị chờ phê duyệt
$sql_approve = "SELECT d.*, c.name as customer_name FROM devices d 
                JOIN customers c ON d.customer_id = c.id 
                WHERE d.status = 'pending_approval'";
$res_approve = mysqli_query($conn, $sql_approve);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Tra cứu Khách hàng & Thiết bị</title>
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
                        <a href="nhanvien.php" class="navbar-brand">
                            <div class="brand-text brand-big"><img src="img/logo.png" width="140" class="img-fluid"></div>
                        </a>
                        <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center mb-0" style="margin-left: auto; gap: 20px;">
                            <li class="nav-item text-white">Xin chào, <strong><?= htmlspecialchars($user['name']) ?></strong></li>
                            <li class="nav-item"><a href="../../backend/api/logout.php" class="nav-link text-danger"><i class="fa fa-sign-out"></i> Đăng xuất</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div class="page-content d-flex align-items-stretch">
        <nav class="side-navbar">
            <div class="sidebar-header d-flex align-items-center">
                <div class="avatar"><img src="img/avatar.jpg" class="img-fluid rounded-circle"></div>
                <div class="title">
                    <h1 class="h4"><?= $roleLabel[$currentRole] ?></h1>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($user['name']) ?></p>
                </div>
            </div>
            <hr>
            <ul class="list-unstyled" style="padding: 10px;">
                <li class="mb-2"><a href="nhanvien.php" class="text-black d-block py-1"><i class="fa fa-home fa-fw"></i> Trang chủ</a></li>
                <li class="mb-2"><a href="tracuu.php" class="text-black d-block py-1"><i class="fa fa-search fa-fw"></i> Tra cứu</a></li>
            </ul>
        </nav>

        <div class="content-inner w-100 p-4">
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card card-idt-main">
                        <div class="card-header-idt">
                            <h4 class="title-idt"><i class="fa fa-check-square-o"></i> Thiết bị khách hàng tự khai báo</h4>
                        </div>
                        <div class="card-body p-3">
                            <?php if (mysqli_num_rows($res_approve) > 0): ?>
                                <?php while($item = mysqli_fetch_assoc($res_approve)): ?>
                                    <div class="data-item d-flex justify-content-between align-items-center border-warning mb-2 p-2 border-left">
                                        <div>
                                            ● Khách hàng: <strong><?= htmlspecialchars($item['customer_name']) ?></strong>
                                            <br><span class="ml-3 text-muted">Thiết bị: <?= htmlspecialchars($item['name']) ?> (Model: <?= htmlspecialchars($item['model']) ?>)</span>
                                        </div>
                                        <button class="btn btn-sm btn-success" onclick="approveDevice(<?= $item['id'] ?>)">Phê duyệt</button>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted ml-3 mb-0">● Hiện tại không có thiết bị nào chờ phê duyệt.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-idt-main">
                        <div class="card-header-idt">
                            <h4 class="title-idt"><i class="fa fa-search"></i> Công cụ tra cứu thông tin</h4>
                        </div>
                        <div class="card-body">
                            <div class="search-box mb-4">
                                <form method="GET" class="row">
                                    <div class="col-md-9">
                                        <input type="text" name="q" class="form-control" placeholder="Tên khách, SĐT hoặc thiết bị..." value="<?= htmlspecialchars($search) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> Tìm ngay</button>
                                    </div>
                                </form>
                            </div>

                            <div id="results">
                                <?php if ($search): ?>
                                    <h6 class="ml-2 mb-3 text-secondary">Kết quả tìm kiếm cho: "<?= htmlspecialchars($search) ?>"</h6>
                                    <?php if ($search_results): ?>
                                        <?php foreach ($search_results as $row): ?>
                                            <div class="data-item border-bottom pb-3 mb-3">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        ● <strong><?= htmlspecialchars($row['customer_name']) ?></strong> - <?= htmlspecialchars($row['phone']) ?>
                                                        <div class="ml-3 mt-1 text-muted">
                                                            - Thiết bị: <?= htmlspecialchars($row['device_name']) ?> (<?= htmlspecialchars($row['model']) ?>)
                                                            <br>- Trạng thái hệ thống: <span class="text-info"><?= $row['device_status'] ?></span>
                                                        </div>
                                                    </div>
                                                    <span class="badge badge-light border p-2">Lịch sử: <?= $row['repair_count'] ?> lần sửa</span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-light text-center">● Không tìm thấy dữ liệu phù hợp.</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function approveDevice(deviceId) {
    if(confirm('Xác nhận đưa thiết bị này vào danh sách quản lý?')) {
        fetch('../../backend/api/approve_device.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: deviceId })
        })
        .then(r => r.json())
        .then(res => {
            if(res.success) { alert('Đã phê duyệt thành công!'); location.reload(); }
            else alert('Lỗi: ' + res.message);
        });
    }
}
</script>
</body>
</html>