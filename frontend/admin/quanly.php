<?php
session_name('STAFF_SESSION');
session_start();

// [FIX] Auth guard — cho phép cả 3 roles nội bộ, chặn customer và người chưa đăng nhập
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

// Chặn customer hoặc role lạ
$allowed_roles = ['admin', 'manager', 'staff'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../index.php");
    exit();
}

// Kết nối database
require_once "../../backend/config/database.php";

$currentRole = $_SESSION['role'];

// Lấy danh sách phiếu yêu cầu chờ phân công
$sql_pending = "SELECT rt.id, rt.received_date, rt.description, COALESCE(d.name, rt.device_name) as device_name, c.name as customer_name, c.phone 
                FROM repair_tickets rt 
                LEFT JOIN devices d ON rt.device_id = d.id 
                JOIN customers c ON rt.customer_id = c.id 
                WHERE rt.status = 'pending' AND rt.user_id IS NULL
                ORDER BY rt.created_at ASC";
$result_pending = mysqli_query($conn, $sql_pending);
$pending_tickets = mysqli_fetch_all($result_pending, MYSQLI_ASSOC);

// Lấy danh sách tất cả thiết bị
$sql_all_devices = "SELECT d.*, c.name as customer_name 
                    FROM devices d 
                    LEFT JOIN customers c ON d.customer_id = c.id 
                    ORDER BY d.id DESC";
$result_devices = mysqli_query($conn, $sql_all_devices);
$all_devices = mysqli_fetch_all($result_devices, MYSQLI_ASSOC);

// Lấy phiếu đang xử lý (pending đã gán + repairing)
$sql_ongoing = "SELECT rt.*, COALESCE(d.name, rt.device_name) as device_name, u.name as staff_name 
                FROM repair_tickets rt 
                LEFT JOIN devices d ON rt.device_id = d.id 
                LEFT JOIN users u ON rt.user_id = u.id 
                WHERE rt.status IN ('pending', 'repairing') AND rt.user_id IS NOT NULL
                ORDER BY rt.updated_at DESC";
$result_ongoing = mysqli_query($conn, $sql_ongoing);
$ongoing_tickets = mysqli_fetch_all($result_ongoing, MYSQLI_ASSOC);

// Lấy danh sách nhân viên kỹ thuật
$sql_staff = "SELECT id, name FROM users WHERE role = 'staff'";
$result_staff = mysqli_query($conn, $sql_staff);
$staff_list = mysqli_fetch_all($result_staff, MYSQLI_ASSOC);
$sql_invoices = "SELECT 
                    i.id AS invoice_id,
                    o.id AS order_id,
                    i.invoice_number,
                    i.total,
                    i.payment_status,
                    i.created_at,
                    c.name AS customer_name,
                    c.phone,
                    d.name AS device_name
                 FROM invoices i
                 RIGHT JOIN orders o ON i.order_id = o.id
                 JOIN customers c ON o.customer_id = c.id
                 LEFT JOIN devices d ON o.device_id = d.id
                 ORDER BY i.created_at DESC";

$result_invoices = mysqli_query($conn, $sql_invoices);
$invoices_list = mysqli_fetch_all($result_invoices, MYSQLI_ASSOC);

// Label hiển thị role
$roleLabel = ['admin' => 'Admin', 'manager' => 'Quản lý', 'staff' => 'Nhân viên'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý – Hệ thống sửa chữa & bảo hành</title>
    <link rel="shortcut icon" href="img/logo-small.png">
    <link href="https://fonts.googleapis.com/css?family=Roboto+Condensed" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/font-icon-style.css">
    <link rel="stylesheet" href="css/style.default.css" id="theme-stylesheet">
    <link rel="stylesheet" href="css/ui-elements/card.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css">
</head>

<body>
<div class="page">

    <!-- HEADER NAVBAR -->
    <header class="header">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <div class="navbar-holder d-flex align-items-center justify-content-between">
                    <div class="navbar-header d-flex align-items-center w-100">
                        <a href="quanly.php" class="navbar-brand">
                            <div class="brand-text brand-big hidden-lg-down">
                                <img src="img/logo.png" width="60" alt="Logo" class="img-fluid">
                            </div>
                            <div class="brand-text brand-small">
                                <img src="img/logo.png" alt="Logo" class="img-fluid">
                            </div>
                        </a>
                        <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center mb-0" style="margin-left: auto; gap: 20px;">
                            <li class="nav-item text-white">
                                Xin chào, <strong><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : strtoupper($currentRole); ?></strong>
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
                <li class="mb-2">
                    <?php
                    $tracuu_link = ($currentRole === 'admin') ? 'tracuu_admin.php' : 'tracuu_manager.php';
                    ?>
                    <a href="<?= $tracuu_link ?>" class="text-black d-block py-1">
                        <i class="fa fa-search fa-fw"></i> Tra cứu
                    </a>
                </li>
                <?php if ($currentRole === 'staff'): ?>
                <li class="mb-2">
                    <a href="nhanvien.php" class="text-black d-block py-1">
                        <i class="fa fa-wrench fa-fw"></i> Trang Nhân viên
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <div style="position:absolute;bottom:20px;left:0;right:0;padding:0 10px;">
                <a href="#" id="logoutBtn"
                   class="d-inline-flex align-items-center py-2 px-3 text-danger font-weight-bold"
                   style="width:fit-content;border-radius:6px;">
                    <i class="fa fa-sign-out mr-2"></i> Đăng xuất
                </a>
            </div>
        </nav>
        <!-- END SIDEBAR -->

        <div class="content-inner">

            <!-- BẢNG 1: Phiếu chờ phân công — chỉ admin/manager mới thấy nút phân công -->
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
                                            <?php if (in_array($currentRole, ['admin', 'manager'])): ?>
                                            <th class="text-center">Giao cho Kỹ thuật viên</th>
                                            <th class="text-center">Hành Động</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($pending_tickets)): ?>
                                            <tr><td colspan="6" class="text-center text-muted py-4">Tuyệt vời! Hiện không có yêu cầu nào đang tồn đọng.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($pending_tickets as $tick): ?>
                                            <tr>
                                                <td class="text-center"><strong>#TICK-<?= $tick['id'] ?></strong></td>
                                                <td class="text-center">
                                                    <?= htmlspecialchars($tick['customer_name']) ?><br>
                                                    <small class="text-muted"><i class="fa fa-phone"></i> <?= htmlspecialchars($tick['phone'] ?? 'Không có') ?></small>
                                                </td>
                                                <td class="text-center"><?= htmlspecialchars($tick['device_name']) ?></td>
                                                <td class="text-center"><?= htmlspecialchars($tick['description']) ?></td>
                                                <?php if (in_array($currentRole, ['admin', 'manager'])): ?>
                                                <td class="text-center">
                                                    <input type="date" class="form-control form-control-sm mb-1" id="due_date_<?= $tick['id'] ?>"
                                                           min="<?= date('Y-m-d') ?>" placeholder="Hạn hoàn thành">
                                                    <select class="form-control form-control-sm" id="staff_assign_<?= $tick['id'] ?>">
                                                        <option value="">-- Chọn thợ --</option>
                                                        <?php foreach ($staff_list as $staff): ?>
                                                            <option value="<?= $staff['id'] ?>"><?= htmlspecialchars($staff['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td class="text-center action-col">
                                                    <button class="btn btn-sm btn-success" onclick="assignTicket(<?= $tick['id'] ?>)">
                                                        <i class="fa fa-check"></i> Chốt
                                                    </button>
                                                </td>
                                                <?php endif; ?>
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

            <!-- BẢNG 2: Quản lý thiết bị & bảo hành -->
            <div class="row" id="report1">
                <div class="col-md-12">
                    <div class="card card-idt-main">
                        <div class="card-header-idt">
                            <h4 class="title-idt"><i class="fa fa-laptop"></i> HỆ THỐNG QUẢN LÝ THIẾT BỊ & BẢO HÀNH</h4>
                            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#modalDevice">
                                Thêm mới thiết bị
                            </button>
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
                                        <?php foreach ($all_devices as $dev):
                                            $end_date = strtotime($dev['warranty_end_date']);
                                            $days_left = ($end_date - time()) / 86400;
                                            $status_class = "text-status-good"; $status_text = "Đang bảo hành";
                                            if ($days_left < 0) { $status_class = "text-status-expired"; $status_text = "Đã hết hạn"; }
                                            elseif ($days_left <= 30) { $status_class = "text-status-warning"; $status_text = "Sắp hết hạn"; }
                                        ?>
                                        <tr>
                                            <td class="text-center"><strong><?= htmlspecialchars($dev['serial_number']) ?></strong></td>
                                            <td class="text-center"><?= htmlspecialchars($dev['name']) ?></td>
                                            <td class="text-center"><?= htmlspecialchars($dev['customer_name'] ?? 'Chưa gán') ?></td>
                                            <td class="text-center"><?= $end_date ? date('d/m/Y', $end_date) : '—' ?></td>
                                            <td class="text-center"><span class="<?= $status_class ?>"><?= $status_text ?></span></td>
                                            <td class="text-center action-col">
                                                <button class="btn-idt-fixed btn-blue" onclick="viewDeviceDetail(<?= $dev['id'] ?>)">
                                                    <i class="fa fa-search"></i> Xem chi tiết
                                                </button>
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
    

            <!-- BẢNG 3: Theo dõi tiến độ sửa chữa -->
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
                                        <?php if (empty($ongoing_tickets)): ?>
                                            <tr><td colspan="5" class="text-center text-muted py-4">Không có phiếu nào đang xử lý.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($ongoing_tickets as $tick): ?>
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
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bảng 4: tạo hóa đơn và lên đơn-->
            <div class="row" id="report4">
    <div class="col-md-12">
        <div class="card card-idt-main">
            <div class="card-header-idt d-flex justify-content-between align-items-center">
                <h4 class="title-idt mb-0">
                    <i class="fa fa-file-text"></i> QUẢN LÝ HÓA ĐƠN
                </h4>
                <div>
                    <button type="button" class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#modalBaoGia">
                        <i class="fas fa-file-invoice"></i> Lên báo giá sửa chữa
                    </button>
                    <!-- <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#modalTaoDonHang">
                        <i class="fas fa-cart-plus"></i> Tạo Đơn hàng (Order)
                    </button>  Chỗ này chưa có ý tưởng sẽ xài vào việc gì, ai có ý tưởng nhắn cho @Đình tú đẹp trai siêu cấp vũ trụ nhé-->
                </div>
            </div>
            <div class="card-body no-padding">
                <div class="table-responsive">
                    <table class="table idt-table-report table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">Số Hóa Đơn</th>
                                <th class="text-center">Khách hàng (SĐT)</th>
                                <th class="text-center">Thiết bị / Dịch vụ</th>
                                <th class="text-center">Tổng tiền</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Ngày tạo</th>
                                <th class="text-center">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices_list)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Chưa có đơn hàng / hóa đơn nào. Hãy tạo đơn hàng trước.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($invoices_list as $inv): 
                                    $status_class = ($inv['payment_status'] === 'paid') ? 'text-success' : 'text-warning';
                                    $status_text  = ($inv['payment_status'] === 'paid') ? 'Đã thanh toán' : 'Chưa thanh toán';
                                ?>
                                <tr>
                                    <td class="text-center"><strong><?= htmlspecialchars($inv['invoice_number'] ?? 'CHƯA CÓ HĐ') ?></strong></td>
                                    <td class="text-center">
                                        <?= htmlspecialchars($inv['customer_name']) ?><br>
                                        <small class="text-muted"><i class="fa fa-phone"></i> <?= htmlspecialchars($inv['phone'] ?? '—') ?></small>
                                    </td>
                                    <td class="text-center"><?= htmlspecialchars($inv['device_name'] ?? 'Sửa chữa') ?></td>
                                    <td class="text-center"><?= number_format($inv['total'] ?? 0, 0) ?> ₫</td>
                                    <td class="text-center"><span class="<?= $status_class ?>"><?= $status_text ?></span></td>
                                    <td class="text-center"><?= date('d/m/Y H:i', strtotime($inv['created_at'])) ?></td>
                                    <td class="text-center action-col">
                                        <!-- IN BẰNG ORDER ID -->
                                        <a href="../../backend/api/print_invoice.php?order_id=<?= $inv['order_id'] ?>" 
                                        target="_blank" 
                                        class="btn btn-success btn-sm">
                                            <i class="fa fa-print"></i> In
                                        </a>
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

        </div><!-- end content-inner -->
    </div><!-- end page-content -->
</div><!-- end page -->

<!-- MODAL XEM CHI TIẾT THIẾT BỊ -->
<div class="modal fade" id="deviceDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detail-modal-title">Chi tiết thiết bị</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body" id="detail-body">
                <div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalDevice" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalTitle">Thêm mới thiết bị</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formDevice">
                    <input type="hidden" id="device_id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Mã thiết bị (S/N) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="serial_number" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tên mặt hàng <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Khách hàng <span class="text-danger">*</span></label>
                            <select class="form-select" id="customer_id" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày hết hạn bảo hành</label>
                            <input type="date" class="form-control" id="warranty_end_date">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Loại thiết bị</label>
                            <select class="form-select" id="type">
                                <option value="hardware">Hardware</option>
                                <option value="software">Software</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tình trạng</label>
                            <select class="form-select" id="status">
                                <option value="active">Đang bảo hành</option>
                                <option value="expired">Đã hết hạn</option>
                                <option value="repairing">Đang sửa chữa</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="btnSaveDevice">💾 Lưu thiết bị</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts — đồng bộ path với admin.php -->
<script src="../js/jquery/jquery.min.js"></script>
<script src="../js/popper/popper.min.js"></script>
<script src="../js/bootstrap/bootstrap.min.js"></script>
<script src="../js/front.js"></script>
<script src="js/manager_actions.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- MODAL LÊN BÁO GIÁ SỬA CHỮA -->
<div class="modal fade" id="modalBaoGia" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">Lên báo giá sửa chữa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formBaoGia">
                    <div class="mb-3">
                        <label class="form-label">Phiếu sửa chữa</label>
                        <select class="form-select" id="repair_ticket_id" required></select>
                    </div>
                    <div class="row mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Khách hàng</label>
                        <input type="text" class="form-control" id="customer_name_display" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Thiết bị</label>
                        <input type="text" class="form-control" id="device_name_display" readonly>
                    </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Báo giá (VND) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quote_amount" step="1000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="note_quote" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-warning" id="btnLuuBaoGia">Lưu báo giá</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TẠO ĐƠN HÀNG -->
<div class="modal fade" id="modalTaoDonHang" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Tạo Đơn hàng mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formDonHang">
                    <input type="hidden" id="order_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Khách hàng</label>
                            <select class="form-select" id="customer_id_order" required></select>
                        </div>
                        <div class="col-md-6">
                            <label>Thiết bị / Phiếu sửa</label>
                            <select class="form-select" id="repair_ticket_id_order"></select>
                        </div>
                        <div class="col-12">
                            <label>Tổng tiền (VND)</label>
                            <input type="number" class="form-control" id="total_amount" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-info" id="btnTaoDonHang">Tạo đơn hàng</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
