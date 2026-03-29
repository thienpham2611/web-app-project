<?php
session_start();

// 1. Kiểm tra bảo mật và phân quyền
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    if ($_SESSION['role'] === 'admin') header("Location: admin.php");
    else if ($_SESSION['role'] === 'staff') header("Location: nhanvien.php");
    else header("Location: index.php");
    exit();
}

// 2. Kết nối database
require_once "../../backend/config/database.php"; 

$managerId = $_SESSION['user_id'] ?? 0;

// 3. Lấy danh sách phiếu yêu cầu
$sql_pending = "SELECT rt.id, rt.received_date, rt.description, d.name as device_name, c.name as customer_name, c.phone 
                FROM repair_tickets rt 
                JOIN devices d ON rt.device_id = d.id 
                JOIN customers c ON rt.customer_id = c.id 
                WHERE rt.status = 'pending' AND rt.user_id IS NULL
                ORDER BY rt.created_at ASC";
$result_pending = mysqli_query($conn, $sql_pending);
$pending_tickets = mysqli_fetch_all($result_pending, MYSQLI_ASSOC);

// 4. Lấy danh sách tất cả thiết bị
$sql_all_devices = "SELECT d.*, c.name as customer_name 
                    FROM devices d 
                    LEFT JOIN customers c ON d.customer_id = c.id 
                    ORDER BY d.id DESC";
$result_devices = mysqli_query($conn, $sql_all_devices);
$all_devices = mysqli_fetch_all($result_devices, MYSQLI_ASSOC);

// 5. Lấy phiếu đang xử lý
$sql_ongoing = "SELECT rt.*, d.name as device_name, u.name as staff_name 
                FROM repair_tickets rt 
                JOIN devices d ON rt.device_id = d.id 
                LEFT JOIN users u ON rt.user_id = u.id 
                WHERE rt.status IN ('pending', 'repairing') AND rt.user_id IS NOT NULL
                ORDER BY rt.updated_at DESC";
$result_ongoing = mysqli_query($conn, $sql_ongoing);
$ongoing_tickets = mysqli_fetch_all($result_ongoing, MYSQLI_ASSOC);

// 6. Lấy danh sách nhân viên
$sql_staff = "SELECT id, name FROM users WHERE role = 'staff'";
$result_staff = mysqli_query($conn, $sql_staff);
$staff_list = mysqli_fetch_all($result_staff, MYSQLI_ASSOC);
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
                    <a href="quanly.php" class="navbar-brand">
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

<!--PAGE CONTENT-->
    <div class="page-content d-flex align-items-stretch">

        <!--***** SIDE NAVBAR *****-->
        <nav class="side-navbar">
            <div class="sidebar-header d-flex align-items-center">
                <div class="avatar"><img src="img/avatar.jpg" alt="..." class="img-fluid rounded-circle"></div>
                <div class="title">
                    <h1 class="h4">Quản lý</h1>
                </div>
            </div>
            <hr>
            <!-- Sidebar Navidation Menus-->
            <ul class="list-unstyled">
                <li class="active"> <a href="quanly.php">Trang chủ</a></li>
                <li class="active"> <a href="email.php">Email</a></li>
                <li class="active"> <a href="tables.php">Bảng</a></li>
        </nav>
        <div class="content-inner">

<!--Danh sách nhân viên-->
<div class="row" id="report-pending">
    <div class="col-md-12">
        <div class="card card-idt-main">
            <div class="card-header-idt">
                <h4 class="title-idt"><i class="fa fa-bell text-warning"></i> YÊU CẦU SỬA CHỮA (CHỜ PHÂN CÔNG)</h4>
            </div>
            <div class="card-body no-padding">
                <div class="table-responsive">
                    <table class="table idt-table-report table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">Mã Phiếu</th>
                                <th class="text-center">Khách hàng (SĐT)</th>
                                <th class="text-center">Tên Thiết bị</th>
                                <th class="text-center">Mô tả lỗi</th>
                                <th class="text-center">Giao cho Kỹ thuật viên</th>
                                <th class="text-center">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pending_tickets)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Tuyệt vời! Hiện không có yêu cầu nào đang tồn đọng.</td></tr>
                            <?php else: ?>
                                <?php foreach($pending_tickets as $tick): ?>
                                <tr>
                                    <td class="text-center"><strong>#TICK-<?= $tick['id'] ?></strong></td>
                                    <td class="text-center">
                                        <?= htmlspecialchars($tick['customer_name']) ?><br>
                                        <small class="text-muted"><i class="fa fa-phone"></i> <?= htmlspecialchars($tick['phone'] ?? 'Không có') ?></small>
                                    </td>
                                    <td class="text-center"><?= htmlspecialchars($tick['device_name']) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($tick['description']) ?></td>
                                    <td class="text-center">
                                        <select class="form-control form-control-sm" id="staff_assign_<?= $tick['id'] ?>">
                                            <option value="">-- Chọn thợ --</option>
                                            <?php foreach($staff_list as $staff): ?>
                                                <option value="<?= $staff['id'] ?>"><?= htmlspecialchars($staff['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="text-center action-col">
                                        <button class="btn btn-sm btn-success" onclick="assignTicket(<?= $tick['id'] ?>)">
                                            <i class="fa fa-check"></i> Chốt
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!--REPORT-1-->
<div class="row" id="report1">
    <div class="col-md-12">
        <div class="card card-idt-main">
            <div class="card-header-idt">
                <h4 class="title-idt"><i class="fa fa-laptop"></i> HỆ THỐNG QUẢN LÝ THIẾT BỊ & BẢO HÀNH</h4>
            </div>
            <div class="card-body no-padding">
                <div class="table-responsive">
                    <table class="table idt-table-report table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">Mã Thiết Bị (S/N)</th>
                                <th class="text-center">Tên Mặt Hàng</th>
                                <th class="text-center">Khách Hàng</th>
                                <th class="text-center">Ngày Hết Hạn</th>
                                <th class="text-center">Tình Trạng</th>
                                <th class="text-center">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_devices as $dev): 
                                $end_date = strtotime($dev['warranty_end_date']);
                                $days_left = ($end_date - time()) / 86400;
                                $status_class = "text-status-good"; $status_text = "Đang bảo hành";
                                if($days_left < 0) { $status_class = "text-status-expired"; $status_text = "Đã hết hạn"; }
                                elseif($days_left <= 30) { $status_class = "text-status-warning"; $status_text = "Sắp hết hạn"; }
                            ?>
                            <tr>
                                <td class="text-center"><strong><?= htmlspecialchars($dev['serial_number']) ?></strong></td>
                                <td class="text-center"><?= htmlspecialchars($dev['name']) ?></td> <td class="text-center"><?= htmlspecialchars($dev['customer_name'] ?? 'Chưa gán') ?></td> <td class="text-center"><?= date('d/m/Y', $end_date) ?></td>
                                <td class="text-center"><span class="<?= $status_class ?>"><?= $status_text ?></span></td>
                                <td class="text-center action-col">
                                    <button class="btn-idt-fixed btn-blue">Xem chi tiết</button>
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

<!--REPORT-3-->
<div class="row" id="report3">
    <div class="col-md-12">
        <div class="card card-idt-main">
            <div class="card-header-idt">
                <h4 class="title-idt"><i class="fa fa-history"></i> THEO DÕI TIẾN ĐỘ SỬA CHỮA VÀ DỊCH VỤ</h4>
            </div>
            <div class="card-body no-padding">
                <div class="table-responsive">
                    <table class="table idt-table-report table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">Mã Case</th>
                                <th class="text-center">Thiết bị/Phần mềm</th>
                                <th class="text-center">Kỹ thuật viên</th>
                                <th class="text-center">Tiến độ xử lý</th>
                                <th class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ongoing_tickets as $tick): ?>
                            <tr>
                                <td class="text-center"><strong>#TICK-<?= $tick['id'] ?></strong></td>
                                <td class="text-center"><?= htmlspecialchars($tick['device_name']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($tick['staff_name'] ?? 'Chờ phân công') ?></td>
                                <td class="align-middle">
                                    <div class="progress idt-progress-bar" style="margin-bottom: 5px;">
                                        <div class="progress-bar bg-info" style="width: <?= $tick['progress'] ?>%;"></div>
                                    </div>
                                    <div class="text-center"><small class="font-weight-bold"><?= $tick['progress'] ?>%</small></div>
                                </td>
                                <td class="text-center action-col">
                                    <?php 
                                        $btn_class = ($tick['status'] == 'repairing') ? 'btn-warning-idt' : 'btn-info-idt';
                                        $status_txt = ($tick['status'] == 'repairing') ? 'Đang sửa chữa' : 'Chờ xử lý';
                                    ?>
                                    <span class="status-btn <?= $btn_class ?>"><?= $status_txt ?></span>
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

    <!--Global Javascript -->
    <script src="js/jquery.min.js"></script>
    <script src="js/popper/popper.min.js"></script>
    <script src="js/tether.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.cookie.js"></script>
    <script src="js/jquery.validate.min.js"></script> 
    <script src="js/chart.min.js"></script> 
    <script src="js/front.js"></script> 
    <script src="js/mychart.js"></script>
    <script src="js/manager_actions.js"></script> </body>
</body>

</html>
