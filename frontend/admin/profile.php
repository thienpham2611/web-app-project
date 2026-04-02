<?php
session_start();

// Auth guard — chỉ cho nhân viên nội bộ
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

// Load thông tin nhân viên từ DB (luôn lấy mới nhất, không chỉ dựa session)
$stmt = mysqli_prepare($conn, "SELECT id, name, email, role, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $currentUserId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    // Tài khoản không tồn tại trong DB → ép logout
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$roleLabel = ['admin' => 'Quản trị viên', 'manager' => 'Quản lý', 'staff' => 'Nhân viên kỹ thuật'];

// Thống kê cá nhân: số ticket được giao
$stmt2 = mysqli_prepare($conn, "
    SELECT
        COUNT(*) AS total,
        SUM(status = 'completed') AS completed,
        SUM(status IN ('pending','repairing')) AS ongoing
    FROM repair_tickets WHERE user_id = ?
");
mysqli_stmt_bind_param($stmt2, "i", $currentUserId);
mysqli_stmt_execute($stmt2);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hồ sơ – <?= htmlspecialchars($user['name']) ?></title>
    <link rel="shortcut icon" href="img/favicon.png">
    <link href="https://fonts.googleapis.com/css?family=Roboto+Condensed" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/font-icon-style.css">
    <link rel="stylesheet" href="css/style.default.css" id="theme-stylesheet">
    <link rel="stylesheet" href="css/ui-elements/card.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
</head>

<body>
<div class="page">

    <!-- HEADER -->
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

        <!-- SIDEBAR -->
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
                    <a href="admin.php" class="text-white d-block py-1">
                        <i class="fa fa-dashboard fa-fw"></i> Dashboard
                    </a>
                </li>
                <li class="mb-2">
                    <a href="quanly.php" class="text-white d-block py-1">
                        <i class="fa fa-cogs fa-fw"></i> Quản lý
                    </a>
                </li>
                <?php elseif ($currentRole === 'manager'): ?>
                <li class="mb-2">
                    <a href="quanly.php" class="text-white d-block py-1">
                        <i class="fa fa-dashboard fa-fw"></i> Dashboard
                    </a>
                </li>
                <?php else: ?>
                <li class="mb-2">
                    <a href="nhanvien.php" class="text-white d-block py-1">
                        <i class="fa fa-wrench fa-fw"></i> Trang Nhân viên
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
                    <a href="profile.php" class="text-white d-block py-1" style="font-weight: bold;">
                        <i class="fa fa-user fa-fw"></i> Hồ sơ <small>(đang xem)</small>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- END SIDEBAR -->

        <div class="content-inner">

            <!-- Alert box dùng chung -->
            <div id="profile-alert" class="alert d-none mx-3 mt-3" role="alert"></div>

            <div class="row mt-3" id="card-prof">

                <!-- Cột trái: avatar + info tóm tắt -->
                <div class="col-md-3">
                    <div class="card hovercard">
                        <div class="cardheader"></div>
                        <div class="avatar">
                            <img alt="" src="img/avatar.jpg" class="img-fluid">
                        </div>
                        <div class="info">
                            <div class="title">
                                <strong id="display-name"><?= htmlspecialchars($user['name']) ?></strong>
                            </div>
                            <div class="desc text-muted"><?= htmlspecialchars($user['email']) ?></div>
                            <div class="desc">
                                <span class="badge badge-<?= $currentRole === 'admin' ? 'danger' : ($currentRole === 'manager' ? 'warning' : 'info') ?>">
                                    <?= $roleLabel[$currentRole] ?>
                                </span>
                            </div>
                            <hr>
                        </div>
                        <nav class="nav text-center prof-nav">
                            <ul class="list-unstyled">
                                <li><a href="../../backend/api/logout.php" class="text-danger"><i class="fa fa-power-off"></i> Đăng xuất</a></li>
                            </ul>
                        </nav>
                    </div>

                    <!-- Thống kê ticket -->
                    <div class="card mt-3 p-3 text-center">
                        <h6 class="font-weight-bold mb-3"><i class="fa fa-bar-chart"></i> Thống kê phiếu SC</h6>
                        <div class="row">
                            <div class="col-4">
                                <div class="h4 text-primary mb-0"><?= $stats['total'] ?? 0 ?></div>
                                <small class="text-muted">Tổng</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 text-success mb-0"><?= $stats['completed'] ?? 0 ?></div>
                                <small class="text-muted">Hoàn thành</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 text-warning mb-0"><?= $stats['ongoing'] ?? 0 ?></div>
                                <small class="text-muted">Đang xử lý</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cột phải: tabs -->
                <div class="col-md-9">
                    <div class="card">
                        <div class="tab" role="tabpanel">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" href="#tab-info" role="tab" data-toggle="tab">
                                        <i class="fa fa-user"></i> Thông tin
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#tab-edit" role="tab" data-toggle="tab">
                                        <i class="fa fa-edit"></i> Chỉnh sửa tên
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#tab-password" role="tab" data-toggle="tab">
                                        <i class="fa fa-lock"></i> Đổi mật khẩu
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content tabs p-3">

                                <!-- TAB 1: Thông tin -->
                                <div role="tabpanel" class="tab-pane fade show active" id="tab-info">
                                    <h5 class="border-bottom pb-2 mb-3"><i class="fa fa-id-card"></i> Thông tin tài khoản</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><i class="fa fa-user text-primary fa-fw"></i> <strong>Họ và tên:</strong>
                                                <span id="info-name"><?= htmlspecialchars($user['name']) ?></span>
                                            </p>
                                            <p><i class="fa fa-envelope text-primary fa-fw"></i> <strong>Email:</strong>
                                                <?= htmlspecialchars($user['email']) ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><i class="fa fa-shield text-primary fa-fw"></i> <strong>Chức vụ:</strong>
                                                <span class="badge badge-<?= $currentRole === 'admin' ? 'danger' : ($currentRole === 'manager' ? 'warning' : 'info') ?>">
                                                    <?= $roleLabel[$currentRole] ?>
                                                </span>
                                            </p>
                                            <p><i class="fa fa-calendar text-primary fa-fw"></i> <strong>Ngày tạo tài khoản:</strong>
                                                <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="alert alert-info mt-3 mb-0">
                                        <i class="fa fa-info-circle"></i>
                                        Email đăng nhập không thể thay đổi. Liên hệ Admin nếu cần hỗ trợ.
                                    </div>
                                </div>

                                <!-- TAB 2: Chỉnh sửa tên -->
                                <div role="tabpanel" class="tab-pane fade" id="tab-edit">
                                    <h5 class="border-bottom pb-2 mb-3"><i class="fa fa-edit"></i> Cập nhật họ tên</h5>
                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="form-group">
                                                <label>Họ và tên hiện tại</label>
                                                <input type="text" class="form-control" id="edit-name"
                                                    value="<?= htmlspecialchars($user['name']) ?>"
                                                    maxlength="100" placeholder="Nhập tên mới...">
                                                <small class="text-muted">Tối đa 100 ký tự</small>
                                            </div>
                                            <button class="btn btn-primary" id="btn-save-name" onclick="saveName()">
                                                <i class="fa fa-save"></i> Lưu thay đổi
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- TAB 3: Đổi mật khẩu -->
                                <div role="tabpanel" class="tab-pane fade" id="tab-password">
                                    <h5 class="border-bottom pb-2 mb-3"><i class="fa fa-lock"></i> Đổi mật khẩu</h5>
                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="form-group">
                                                <label>Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control" id="pw-current" placeholder="Nhập mật khẩu hiện tại...">
                                            </div>
                                            <div class="form-group">
                                                <label>Mật khẩu mới <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control" id="pw-new" placeholder="Ít nhất 6 ký tự...">
                                            </div>
                                            <div class="form-group">
                                                <label>Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control" id="pw-confirm" placeholder="Nhập lại mật khẩu mới...">
                                            </div>
                                            <button class="btn btn-danger" id="btn-change-pw" onclick="changePassword()">
                                                <i class="fa fa-key"></i> Đổi mật khẩu
                                            </button>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="alert alert-warning">
                                                <strong><i class="fa fa-exclamation-triangle"></i> Lưu ý:</strong>
                                                <ul class="mb-0 mt-1 pl-3">
                                                    <li>Mật khẩu mới phải ít nhất 6 ký tự</li>
                                                    <li>Sau khi đổi, bạn cần đăng nhập lại</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div><!-- end tab-content -->
                        </div>
                    </div>
                </div>

            </div><!-- end row -->
        </div><!-- end content-inner -->
    </div><!-- end page-content -->
</div><!-- end page -->

<script src="../js/jquery/jquery.min.js"></script>
<script src="../js/popper/popper.min.js"></script>
<script src="../js/bootstrap/bootstrap.min.js"></script>
<script src="../js/front.js"></script>

<script>
// Hiển thị alert
function showAlert(type, msg) {
    const box = document.getElementById('profile-alert');
    box.className = 'alert alert-' + type + ' mx-3 mt-3';
    box.textContent = msg;
    box.classList.remove('d-none');
    setTimeout(() => box.classList.add('d-none'), 4000);
}

// Lưu tên mới
function saveName() {
    const name = document.getElementById('edit-name').value.trim();
    if (!name) { showAlert('warning', 'Tên không được để trống!'); return; }

    const btn = document.getElementById('btn-save-name');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang lưu...';

    fetch('../../backend/api/update_profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action: 'update_name', name })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showAlert('success', res.message);
            // Cập nhật hiển thị trên trang ngay không cần reload
            document.getElementById('display-name').textContent = res.name;
            document.getElementById('info-name').textContent = res.name;
        } else {
            showAlert('danger', 'Lỗi: ' + (res.error || 'Không xác định'));
        }
    })
    .catch(() => showAlert('danger', 'Lỗi kết nối server!'))
    .finally(() => { btn.disabled = false; btn.innerHTML = orig; });
}

// Đổi mật khẩu
function changePassword() {
    const current  = document.getElementById('pw-current').value;
    const newPw    = document.getElementById('pw-new').value;
    const confirm  = document.getElementById('pw-confirm').value;

    if (!current || !newPw || !confirm) { showAlert('warning', 'Vui lòng điền đầy đủ các trường!'); return; }
    if (newPw !== confirm)              { showAlert('warning', 'Mật khẩu mới và xác nhận không khớp!'); return; }
    if (newPw.length < 6)              { showAlert('warning', 'Mật khẩu mới phải có ít nhất 6 ký tự!'); return; }

    const btn = document.getElementById('btn-change-pw');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang xử lý...';

    fetch('../../backend/api/update_profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            action: 'change_password',
            current_password: current,
            new_password: newPw,
            confirm_password: confirm
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showAlert('success', res.message + ' — Vui lòng đăng nhập lại.');
            document.getElementById('pw-current').value = '';
            document.getElementById('pw-new').value = '';
            document.getElementById('pw-confirm').value = '';
            // Tự động logout sau 2.5 giây
            setTimeout(() => { window.location.href = '../../backend/api/logout.php'; }, 2500);
        } else {
            showAlert('danger', 'Lỗi: ' + (res.error || 'Không xác định'));
        }
    })
    .catch(() => showAlert('danger', 'Lỗi kết nối server!'))
    .finally(() => { btn.disabled = false; btn.innerHTML = orig; });
}
</script>

</body>
</html>
