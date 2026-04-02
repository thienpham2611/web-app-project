<?php
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
$sql_pending = "SELECT rt.id, rt.received_date, rt.description, d.name as device_name, c.name as customer_name, c.phone 
                FROM repair_tickets rt 
                JOIN devices d ON rt.device_id = d.id 
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
$sql_ongoing = "SELECT rt.*, d.name as device_name, u.name as staff_name 
                FROM repair_tickets rt 
                JOIN devices d ON rt.device_id = d.id 
                LEFT JOIN users u ON rt.user_id = u.id 
                WHERE rt.status IN ('pending', 'repairing') AND rt.user_id IS NOT NULL
                ORDER BY rt.updated_at DESC";
$result_ongoing = mysqli_query($conn, $sql_ongoing);
$ongoing_tickets = mysqli_fetch_all($result_ongoing, MYSQLI_ASSOC);

// Lấy danh sách nhân viên kỹ thuật
$sql_staff = "SELECT id, name FROM users WHERE role = 'staff'";
$result_staff = mysqli_query($conn, $sql_staff);
$staff_list = mysqli_fetch_all($result_staff, MYSQLI_ASSOC);

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
    <link rel="shortcut icon" href="img/favicon.png">
    <link href="https://fonts.googleapis.com/css?family=Roboto+Condensed" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/font-icon-style.css">
    <link rel="stylesheet" href="css/style.default.css" id="theme-stylesheet">
    <link rel="stylesheet" href="css/ui-elements/card.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
<div class="page">

    <!-- HEADER NAVBAR -->
    <header class="header">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
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
                                Xin chào, <strong><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : strtoupper($currentRole); ?></strong>
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
                    <a href="quanly.php" class="text-white d-block py-1">
                        <i class="fa fa-dashboard fa-fw"></i> Dashboard
                    </a>
                </li>
                <?php if ($currentRole === 'admin'): ?>
                <li class="mb-2">
                    <a href="admin.php" class="text-white d-block py-1">
                        <i class="fa fa-shield fa-fw"></i> Trang Admin
                    </a>
                </li>
                <?php endif; ?>
                <li class="mb-2">
                    <a href="tables.php" class="text-white d-block py-1">
                        <i class="fa fa-table fa-fw"></i> Bảng dữ liệu
                    </a>
                </li>
                <li class="mb-2">
                    <a href="invoice.php" class="text-white d-block py-1">
                        <i class="fa fa-file-text fa-fw"></i> Hóa đơn
                    </a>
                </li>
                <li class="mb-2">
                    <a href="email.php" class="text-white d-block py-1">
                        <i class="fa fa-envelope fa-fw"></i> Email
                    </a>
                </li>
                <li class="mb-2">
                    <a href="profile.php" class="text-white d-block py-1">
                        <i class="fa fa-user fa-fw"></i> Hồ sơ
                    </a>
                </li>
                <?php if ($currentRole === 'staff'): ?>
                <li class="mb-2">
                    <a href="nhanvien.php" class="text-white d-block py-1">
                        <i class="fa fa-wrench fa-fw"></i> Trang Nhân viên
                    </a>
                </li>
                <?php endif; ?>
            </ul>
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

        </div><!-- end content-inner -->
    </div><!-- end page-content -->
</div><!-- end page -->

<!-- MODAL XEM CHI TIẾT THIẾT BỊ -->
<div class="modal fade" id="deviceDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document" style="margin-top: 60px; max-height: calc(100vh - 100px);">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detail-modal-title">Chi tiết thiết bị</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="detail-body">
                <div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
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

<script>
function viewDeviceDetail(id) {
    const modal = $('#deviceDetailModal');
    document.getElementById('detail-body').innerHTML = '<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x"></i></div>';
    modal.modal('show');

    fetch('../../backend/api/get_device_detail.php?id=' + id, { credentials: 'include' })
    .then(r => r.json())
    .then(res => {
        if (!res.success) { alert('Lỗi: ' + res.error); return; }
        const d = res.device, ts = res.tickets, ws = res.extensions;
        const daysLeft = Math.ceil((new Date(d.warranty_end_date) - new Date()) / 86400000);
        const wBadge = daysLeft < 0
            ? '<span class="badge badge-danger">Đã hết hạn</span>'
            : daysLeft <= 90
                ? '<span class="badge badge-warning">Sắp hết hạn (' + daysLeft + ' ngày)</span>'
                : '<span class="badge badge-success">Còn hạn</span>';

        let tr = '';
        ts.forEach(t => {
            const stMap = { pending: 'Chờ xử lý', repairing: 'Đang sửa', completed: 'Hoàn tất', cancelled: 'Đã hủy' };
            tr += `<tr>
                <td>#TICK-${t.id}</td>
                <td>${t.description ?? '—'}</td>
                <td>${t.staff_name ?? 'Chưa gán'}</td>
                <td><span class="badge badge-info">${stMap[t.status] ?? t.status}</span></td>
            </tr>`;
        });
        if (!tr) tr = '<tr><td colspan="4" class="text-center text-muted">Chưa có</td></tr>';

        let wr = '';
        ws.forEach(w => {
            wr += `<tr>
                <td>${new Date(w.created_at).toLocaleDateString('vi-VN')}</td>
                <td><del>${w.old_end_date}</del> → <strong class="text-success">${w.new_end_date}</strong></td>
                <td>${Number(w.cost).toLocaleString('vi-VN')} đ</td>
                <td>${w.user_name}</td>
            </tr>`;
        });
        if (!wr) wr = '<tr><td colspan="4" class="text-center text-muted">Chưa có</td></tr>';

        document.getElementById('detail-body').innerHTML = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>Tên:</strong> ${d.name}</p>
                    <p><strong>Serial:</strong> ${d.serial_number ?? '—'}</p>
                    <p><strong>Loại:</strong> ${d.type === 'hardware' ? 'Phần cứng' : 'Phần mềm'}</p>
                    <p><strong>Khách hàng:</strong> ${d.customer_name ?? '—'} ${d.customer_phone ? '(' + d.customer_phone + ')' : ''}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Bắt đầu BH:</strong> ${d.warranty_start_date ?? '—'}</p>
                    <p><strong>Hết hạn BH:</strong> ${d.warranty_end_date ?? '—'} ${wBadge}</p>
                    <p><strong>Trạng thái:</strong> ${d.status}</p>
                </div>
            </div>
            <h6 class="font-weight-bold border-bottom pb-1">Lịch sử phiếu sửa chữa</h6>
            <table class="table table-sm table-bordered mb-3">
                <thead class="thead-light"><tr><th>Mã</th><th>Mô tả</th><th>KTV</th><th>Trạng thái</th></tr></thead>
                <tbody>${tr}</tbody>
            </table>
            <h6 class="font-weight-bold border-bottom pb-1">Lịch sử gia hạn bảo hành</h6>
            <table class="table table-sm table-bordered">
                <thead class="thead-light"><tr><th>Ngày</th><th>Thay đổi</th><th>Chi phí</th><th>Người thực hiện</th></tr></thead>
                <tbody>${wr}</tbody>
            </table>`;
        document.getElementById('detail-modal-title').innerText = 'Chi tiết: ' + d.name;
    }).catch(() => alert('Lỗi kết nối server!'));
}

<?php if (in_array($currentRole, ['admin', 'manager'])): ?>
function assignTicket(ticketId) {
    const staffId = document.getElementById('staff_assign_' + ticketId).value;
    if (!staffId) { alert('Vui lòng chọn kỹ thuật viên!'); return; }

    const btn = event.currentTarget;
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

    fetch('../../backend/api/assign_ticket.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ ticket_id: ticketId, staff_id: staffId })
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert('Phân công thành công!');
            location.reload();
        } else {
            alert('Lỗi: ' + (result.error || 'Không xác định'));
        }
    })
    .catch(() => alert('Lỗi kết nối server!'))
    .finally(() => { btn.disabled = false; btn.innerHTML = originalHTML; });
}
<?php endif; ?>
</script>

</body>
</html>
