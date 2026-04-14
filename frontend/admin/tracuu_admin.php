<?php
session_name('STAFF_SESSION');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); exit();
}

require_once "../../backend/config/database.php";
$currentRole   = 'admin';
$currentUserId = intval($_SESSION['user_id']);
$today         = date('Y-m-d');

// Thống kê tổng hợp
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COUNT(*) as total,
        SUM(status='pending') as pending,
        SUM(status='repairing') as repairing,
        SUM(status='completed') as completed,
        SUM(due_date < '$today' AND status NOT IN ('completed','cancelled')) as overdue
     FROM repair_tickets"));

$total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM customers"))['n'];
$total_devices   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM devices"))['n'];
$pending_devices = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM devices WHERE status='pending_approval'"))['n'];

// Danh sách nhân viên để lọc
$staff_list = mysqli_fetch_all(mysqli_query($conn,
    "SELECT id, name, role FROM users WHERE role IN ('staff','manager') ORDER BY name"), MYSQLI_ASSOC);

// Tab active
$tab = $_GET['tab'] ?? 'tickets'; // tickets | customers | devices

// --- Tab Phiếu sửa chữa ---
$search       = trim($_GET['q'] ?? '');
$filter_status= $_GET['status'] ?? 'all';
$filter_staff = intval($_GET['staff_id'] ?? 0);
$ticket_results = [];

if ($tab === 'tickets') {
    $sql = "SELECT rt.id, rt.status, rt.progress, rt.due_date,
                   COALESCE(d.name, rt.device_name) AS device_name, COALESCE(d.serial_number, rt.reported_serial) AS serial_number,
                   c.name AS customer_name, c.phone,
                   u.name AS staff_name
            FROM repair_tickets rt
            LEFT JOIN devices d ON d.id = rt.device_id
            JOIN customers c ON c.id = rt.customer_id
            LEFT JOIN users u ON u.id = rt.user_id
            WHERE 1=1";
    $params=[]; $types="";

    if (!empty($search)) {
        $q="%$search%";
        $sql.=" AND (rt.id LIKE ? OR COALESCE(d.serial_number, rt.reported_serial) LIKE ? OR COALESCE(d.name, rt.device_name) LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
        $params=array_merge($params,[$q,$q,$q,$q,$q]); $types.="sssss";
    }
    if ($filter_status==='overdue') {
        $sql.=" AND rt.due_date < ? AND rt.status NOT IN ('completed','cancelled')";
        $params[]=$today; $types.="s";
    } elseif (in_array($filter_status,['pending','repairing','completed','cancelled'])) {
        $sql.=" AND rt.status = ?"; $params[]=$filter_status; $types.="s";
    }
    if ($filter_staff>0) { $sql.=" AND rt.user_id = ?"; $params[]=$filter_staff; $types.="i"; }

    $sql.=" ORDER BY
        CASE WHEN rt.due_date IS NOT NULL AND rt.due_date < '$today' AND rt.status NOT IN ('completed','cancelled') THEN 0 ELSE 1 END,
        rt.due_date ASC, rt.id DESC";

    $stmt=mysqli_prepare($conn,$sql);
    if (!empty($params)) mysqli_stmt_bind_param($stmt,$types,...$params);
    mysqli_stmt_execute($stmt);
    $ticket_results=mysqli_fetch_all(mysqli_stmt_get_result($stmt),MYSQLI_ASSOC);
}

// --- Tab Khách hàng & Thiết bị ---
$cust_results = [];
if ($tab === 'customers') {
    $q = "%".trim($_GET['q']??'')."%";
    $stmt2 = mysqli_prepare($conn,
        "SELECT c.id, c.name, c.phone, c.email,
                COUNT(d.id) as device_count,
                COUNT(rt.id) as ticket_count
         FROM customers c
         LEFT JOIN devices d ON d.customer_id = c.id
         LEFT JOIN repair_tickets rt ON rt.customer_id = c.id
         WHERE c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?
         GROUP BY c.id ORDER BY c.name");
    mysqli_stmt_bind_param($stmt2,"sss",$q,$q,$q);
    mysqli_stmt_execute($stmt2);
    $cust_results=mysqli_fetch_all(mysqli_stmt_get_result($stmt2),MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tra cứu - Admin</title>
    <link rel="shortcut icon" href="img/logo-small.png">
    <link href="https://fonts.googleapis.com/css?family=Roboto+Condensed" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.default.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="header">
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <div class="navbar-holder d-flex align-items-center justify-content-between">
                <div class="navbar-header d-flex align-items-center w-100">
                    <a href="admin.php" class="navbar-brand">
                        <div class="brand-text brand-big hidden-lg-down">
                            <img src="img/logo.png" width="60" alt="Logo" class="img-fluid">
                        </div>
                        <div class="brand-text brand-small">
                            <img src="img/logo.png" alt="Logo" class="img-fluid">
                        </div>
                    </a>
                    <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center mb-0" style="margin-left:auto;gap:20px;">
                        <li class="nav-item text-white">
                            Xin chào, <strong><?= htmlspecialchars($_SESSION['name']??'') ?></strong>
                            <small class="text-muted ml-1">(Quản trị viên)</small>
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
            <div class="avatar"><img src="img/avatar.jpg" class="img-fluid rounded-circle"></div>
            <div class="title">
                <h1 class="h4">Quản trị viên</h1>
                <p class="text-muted small mb-0"><?= htmlspecialchars($_SESSION['name']??'') ?></p>
            </div>
        </div>
        <hr>
        <ul class="list-unstyled" style="padding:10px;">
            <li class="mb-2"><a href="admin.php" class="text-black d-block py-1"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a></li>
            <li class="mb-2"><a href="quanly.php" class="text-black d-block py-1"><i class="fa fa-cogs fa-fw"></i> Quản lý</a></li>
            <li class="mb-2"><a href="tables.php" class="text-black d-block py-1"><i class="fa fa-table fa-fw"></i> Bảng dữ liệu</a></li>
            <li class="mb-2"><a href="invoice.php" class="text-black d-block py-1"><i class="fa fa-file-text fa-fw"></i> Hóa đơn</a></li>
            <li class="mb-2"><a href="email.php" class="text-black d-block py-1"><i class="fa fa-envelope fa-fw"></i> Email</a></li>
            <li class="mb-2"><a href="profile.php" class="text-black d-block py-1"><i class="fa fa-user fa-fw"></i> Hồ sơ</a></li>
            <li class="mb-2"><a href="nhanvien.php" class="text-black d-block py-1"><i class="fa fa-wrench fa-fw"></i> Nhân viên</a></li>
            <li class="mb-2">
                <a href="tracuu_admin.php" class="text-black d-block py-1 font-weight-bold">
                    <i class="fa fa-search fa-fw"></i> Tra cứu
                </a>
            </li>
        </ul>
        <div style="position:absolute;bottom:20px;left:0;right:0;padding:0 10px;">
            <a href="#" id="logoutBtn"
               class="d-inline-flex align-items-center py-2 px-3 text-danger font-weight-bold"
               style="width:fit-content;border-radius:6px;">
                <i class="fa fa-sign-out mr-2"></i> Đăng xuất
            </a>
        </div>
    </nav>

    <div class="content-inner w-100 p-4">

        <!-- Thống kê -->
        <div class="row mb-4">
            <div class="col-6 col-md mb-2">
                <div class="card p-3 text-center" style="border-left:4px solid #6c757d;">
                    <small class="text-muted">Tổng phiếu</small>
                    <h3 class="mb-0"><?= intval($stats['total']) ?></h3>
                </div>
            </div>
            <div class="col-6 col-md mb-2">
                <div class="card p-3 text-center" style="border-left:4px solid #dc3545;">
                    <small class="text-muted">Quá deadline</small>
                    <h3 class="mb-0 text-danger"><?= intval($stats['overdue']) ?></h3>
                </div>
            </div>
            <div class="col-6 col-md mb-2">
                <div class="card p-3 text-center" style="border-left:4px solid #17a2b8;">
                    <small class="text-muted">Khách hàng</small>
                    <h3 class="mb-0"><?= intval($total_customers) ?></h3>
                </div>
            </div>
            <div class="col-6 col-md mb-2">
                <div class="card p-3 text-center" style="border-left:4px solid #28a745;">
                    <small class="text-muted">Thiết bị</small>
                    <h3 class="mb-0"><?= intval($total_devices) ?></h3>
                </div>
            </div>
            <?php if($pending_devices > 0): ?>
            <div class="col-6 col-md mb-2">
                <div class="card p-3 text-center" style="border-left:4px solid #ffc107;">
                    <small class="text-muted">Chờ duyệt</small>
                    <h3 class="mb-0 text-warning"><?= $pending_devices ?></h3>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $tab==='tickets'?'active':'' ?>" href="?tab=tickets">
                    <i class="fa fa-wrench"></i> Phiếu sửa chữa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab==='customers'?'active':'' ?>" href="?tab=customers">
                    <i class="fa fa-users"></i> Khách hàng
                </a>
            </li>
        </ul>

        <?php if ($tab === 'tickets'): ?>
        <!-- Lọc phiếu -->
        <div class="card card-idt-main mb-4">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <input type="hidden" name="tab" value="tickets">
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">Tìm kiếm</label>
                        <input type="text" name="q" class="form-control" placeholder="Mã phiếu, Serial, Tên thiết bị, KH..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">Trạng thái</label>
                        <select name="status" class="form-control">
                            <option value="all"       <?= $filter_status==='all'?'selected':''?>>Tất cả</option>
                            <option value="pending"   <?= $filter_status==='pending'?'selected':''?>>Chờ xử lý</option>
                            <option value="repairing" <?= $filter_status==='repairing'?'selected':''?>>Đang sửa</option>
                            <option value="completed" <?= $filter_status==='completed'?'selected':''?>>Hoàn thành</option>
                            <option value="overdue"   <?= $filter_status==='overdue'?'selected':''?>>⚠ Quá deadline</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">Nhân viên</label>
                        <select name="staff_id" class="form-control">
                            <option value="0">-- Tất cả --</option>
                            <?php foreach($staff_list as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $filter_staff==$s['id']?'selected':'' ?>>
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> Lọc</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-idt-main">
            <div class="card-header-idt">
                <h4 class="title-idt"><i class="fa fa-list"></i> Phiếu sửa chữa <small class="text-muted ml-2">(<?= count($ticket_results) ?> phiếu)</small></h4>
            </div>
            <div class="card-body no-padding">
                <div class="table-responsive">
                    <table class="table idt-table-report table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Mã phiếu</th><th>Thiết bị</th><th>Khách hàng</th>
                                <th>Nhân viên</th><th>Tiến độ</th>
                                <th class="text-center">Deadline</th><th class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($ticket_results)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Không có phiếu nào.</td></tr>
                        <?php else: foreach($ticket_results as $r):
                            $badge='badge-secondary';$label='Chờ xử lý';
                            if($r['status']==='repairing'){$badge='badge-info';$label='Đang sửa';}
                            if($r['status']==='completed'){$badge='badge-success';$label='Hoàn thành';}
                            if($r['status']==='cancelled'){$badge='badge-danger';$label='Đã hủy';}
                            $dh='<span class="text-muted">—</span>';
                            if($r['due_date']){
                                $diff=(int)(new DateTime($today))->diff(new DateTime($r['due_date']))->format('%r%a');
                                $fmt=(new DateTime($r['due_date']))->format('d/m/Y');
                                if($diff<0&&!in_array($r['status'],['completed','cancelled']))
                                    $dh="<span class='badge badge-danger p-1'>⚠ Quá ".abs($diff)." ngày<br>$fmt</span>";
                                elseif($diff<=2) $dh="<span class='badge badge-warning p-1'>🔔 Còn $diff ngày<br>$fmt</span>";
                                else $dh="<small class='text-success'>$fmt<br>(còn $diff ngày)</small>";
                            }
                            $bar=intval($r['progress']);
                            $bc=$bar>=90?'bg-success':($bar<30?'bg-danger':'bg-info');
                        ?>
                            <tr>
                                <td><strong>#RT-<?= $r['id'] ?></strong></td>
                                <td><?= htmlspecialchars($r['device_name']) ?><br><small class="text-muted">S/N: <?= htmlspecialchars($r['serial_number']??'—') ?></small></td>
                                <td><?= htmlspecialchars($r['customer_name']) ?><br><small class="text-muted"><?= htmlspecialchars($r['phone']) ?></small></td>
                                <td><?= $r['staff_name']?htmlspecialchars($r['staff_name']):'<span class="text-muted">Chưa giao</span>' ?></td>
                                <td class="align-middle">
                                    <div class="progress" style="height:8px;margin-bottom:3px;"><div class="progress-bar <?= $bc ?>" style="width:<?= $bar ?>%"></div></div>
                                    <small class="font-weight-bold"><?= $bar ?>%</small>
                                </td>
                                <td class="text-center"><?= $dh ?></td>
                                <td class="text-center"><span class="badge <?= $badge ?> p-2"><?= $label ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif($tab === 'customers'): ?>
        <!-- Tìm kiếm khách hàng -->
        <div class="card card-idt-main mb-4">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <input type="hidden" name="tab" value="customers">
                    <div class="col-md-8 mb-2">
                        <input type="text" name="q" class="form-control" placeholder="Tên khách hàng, SĐT, Email..." value="<?= htmlspecialchars(trim($_GET['q']??'')) ?>">
                    </div>
                    <div class="col-md-4 mb-2">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> Tìm</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-idt-main">
            <div class="card-header-idt">
                <h4 class="title-idt"><i class="fa fa-users"></i> Danh sách khách hàng <small class="text-muted ml-2">(<?= count($cust_results) ?> khách)</small></h4>
            </div>
            <div class="card-body no-padding">
                <div class="table-responsive">
                    <table class="table idt-table-report table-hover mb-0">
                        <thead>
                            <tr><th>Tên</th><th>SĐT</th><th>Email</th><th class="text-center">Thiết bị</th><th class="text-center">Phiếu sửa</th></tr>
                        </thead>
                        <tbody>
                        <?php if(empty($cust_results)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Không tìm thấy khách hàng.</td></tr>
                        <?php else: foreach($cust_results as $c): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                                <td><?= htmlspecialchars($c['phone']??'—') ?></td>
                                <td><?= htmlspecialchars($c['email']??'—') ?></td>
                                <td class="text-center"><?= $c['device_count'] ?></td>
                                <td class="text-center"><?= $c['ticket_count'] ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/admin_actions.js"></script>
</body>
</html>
