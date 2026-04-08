<?php
session_name('STAFF_SESSION');
session_start();

// 1. Bảo mật: Chỉ cho phép nhân viên truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: index.php");
    exit();
}

require_once "../../backend/config/database.php";
$currentUserId = $_SESSION['user_id'];

// 2. Lấy thống kê cá nhân (Thứ mà nhân viên cần biết để theo dõi KPI)
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(status = 'repairing') as doing,
    SUM(status = 'completed') as done
    FROM repair_tickets WHERE assigned_to = $currentUserId";
$stats_res = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_res);

// 3. Xử lý tìm kiếm (Nâng cao: Tìm theo Case ID, SĐT, Serial)
$search = $_GET['q'] ?? '';
$search_results = [];
if ($search) {
    $q = "%$search%";
    // Truy vấn đa năng: tìm mọi thứ liên quan đến kỹ thuật
    $sql = "SELECT t.id as ticket_id, t.status as t_status, t.progress, 
                   d.name as device_name, d.serial_number, d.status as d_status,
                   c.name as customer_name, c.phone
            FROM repair_tickets t
            JOIN devices d ON t.device_id = d.id
            JOIN customers c ON t.customer_id = c.id
            WHERE t.id LIKE '$q' OR d.serial_number LIKE '$q' OR c.phone LIKE '$q' OR d.name LIKE '$q'
            ORDER BY t.created_at DESC LIMIT 20";
    $res = mysqli_query($conn, $sql);
    while($row = mysqli_fetch_assoc($res)) { $search_results[] = $row; }
}

// 4. Lấy danh sách công việc ĐANG LÀM của nhân viên này
$my_tickets = [];
$my_sql = "SELECT t.*, d.name as device_name, c.name as customer_name 
           FROM repair_tickets t
           JOIN devices d ON t.device_id = d.id
           JOIN customers c ON t.customer_id = c.id
           WHERE t.assigned_to = $currentUserId AND t.status != 'completed'
           ORDER BY t.created_at DESC";
$my_res = mysqli_query($conn, $my_sql);
while($row = mysqli_fetch_assoc($my_res)) { $my_tickets[] = $row; }
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">

    <title>Trung tâm Tra cứu Kỹ thuật</title>
    <link rel="shortcut icon" href="img/favicon.png">
    
    <!-- global stylesheets -->
    <link href="https://fonts.googleapis.com/css?family=Roboto+Condensed" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/font-icon-style.css">
    <link rel="stylesheet" href="css/style.default.css" id="theme-stylesheet">

    <!-- Core stylesheets -->
    <link rel="stylesheet" href="css/ui-elements/card.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<!--MAIN NAVBAR-->
<header class="header">
    <nav class="navbar navbar-expand-lg ">
        <div class="container-fluid ">
            <div class="navbar-holder d-flex align-items-center justify-content-between">
                <div class="navbar-header">
                    <a href="nhanvien.php" class="navbar-brand">
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
                            <a href="#" onclick="logoutStaff(); return false;" class="nav-link text-danger font-weight-bold" style="padding: 0;">
                                <i class="fa fa-sign-out"></i> Đăng xuất
                            </a>
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
        <div class="avatar"><img src="img/avatar.jpg" alt="..." class="img-fluid rounded-circle"></div>
        <div class="title">
            <h1 class="h4">Nhân viên</h1>
        </div>
    </div>
    <ul class="list-unstyled" style="padding: 10px;">
        <li class="mb-2">
            <a href="nhanvien.php" class="text-black d-block py-1">
                <i class="fa fa-home fa-fw"></i> Trang chủ
            </a>
        </li>
        <li class="mb-2">
            <a href="tracuu_staff.php" class="text-black d-block py-1">
                <i class="fa fa-search"></i> Tra cứu
            </a>
        </li>
    </ul>
</nav>
<div class="content-inner">

    <div class="content-inner" style="width: 100%; padding: 20px;">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card bg-primary text-white p-3">
                    <small>Tổng công việc đã nhận</small>
                    <h3><?= $stats['total'] ?? 0 ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-info text-white p-3">
                    <small>Đang xử lý</small>
                    <h3><?= $stats['doing'] ?? 0 ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-success text-white p-3">
                    <small>Đã hoàn thành</small>
                    <h3><?= $stats['done'] ?? 0 ?></h3>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form action="" method="GET" class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Tìm theo Mã Case (#RT-123), Số điện thoại, hoặc Số Serial máy..." value="<?= htmlspecialchars($search) ?>">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Tìm kiếm</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <?php if($search): ?>
                    <h4>Kết quả tra cứu cho: "<?= htmlspecialchars($search) ?>"</h4>
                    <div class="list-group">
                        <?php foreach($search_results as $r): ?>
                            <div class="list-group-item ticket-item ticket-<?= $r['t_status'] ?>">
                                <div class="d-flex justify-content-between">
                                    <h5>#RT-<?= $r['ticket_id'] ?>: <?= $r['device_name'] ?></h5>
                                    <span class="badge badge-pill badge-info"><?= $r['t_status'] ?></span>
                                </div>
                                <p class="mb-1 text-muted">Khách hàng: <strong><?= $r['customer_name'] ?></strong> (<?= $r['phone'] ?>)</p>
                                <small>Số Serial: <?= $r['serial_number'] ?> | Tiến độ: <?= $r['progress'] ?>%</small>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewTimeline(<?= $r['ticket_id'] ?>)">Xem lịch sử sửa chữa</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($search_results)) echo "<p class='text-muted'>Không tìm thấy dữ liệu.</p>"; ?>
                    </div>
                <?php else: ?>
                    <h4>Danh sách công việc đang thực hiện</h4>
                    <div class="table-responsive">
                        <table class="table table-hover bg-white border">
                            <thead>
                                <tr>
                                    <th>Mã Case</th>
                                    <th>Thiết bị</th>
                                    <th>Khách hàng</th>
                                    <th>Tiến độ</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($my_tickets as $mt): ?>
                                <tr>
                                    <td>#RT-<?= $mt['id'] ?></td>
                                    <td><?= $mt['device_name'] ?></td>
                                    <td><?= $mt['customer_name'] ?></td>
                                    <td><?= $mt['progress'] ?>%</td>
                                    <td>
                                        <a href="nhanvien.php" class="btn btn-sm btn-primary">Cập nhật</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="js/staff_actions.js"></script>
</body>
</html>