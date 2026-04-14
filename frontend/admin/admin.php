<?php
session_name('STAFF_SESSION');
session_start();

// [FIX] Whitelist: chỉ cho phép role === 'admin', chặn tất cả role khác kể cả customer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    // Redirect đúng trang theo role thay vì để lọt qua
    if ($_SESSION['role'] === 'manager') {
        header("Location: quanly.php");
    } elseif ($_SESSION['role'] === 'staff') {
        header("Location: nhanvien.php");
    } else {
        // customer hoặc role lạ → về trang chủ
        header("Location: index.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="shortcut icon" href="img/logo-small.png">

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
    <header class="header">
        <nav class="navbar navbar-expand-lg ">
            <div class="container-fluid ">
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
                        <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center mb-0" style="margin-left: auto; gap: 20px;">
                            <li class="nav-item text-white">
                                Xin chào, <strong><?php echo isset($_SESSION['name']) ? $_SESSION['name'] : strtoupper($_SESSION['role']); ?></strong>
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

        <div class="content-inner">
            
            <div class="row" id="user-management">
                <div class="col-md-12">
                    <div class="card card-idt-main">
                        <div class="card-header-idt">
                            <h4 class="title-idt"><i class="fa fa-users"></i> QUẢN LÝ TÀI KHOẢN NỘI BỘ</h4>
                            <button class="btn btn-sm btn-success m-0" data-toggle="modal" data-target="#createEmployeeModal" style="background-color: #28a745; border: none; font-weight: bold;">
                                <i class="fa fa-user-plus"></i> Tạo tài khoản mới
                            </button>
                        </div>
                        <div class="card-body no-padding">
                            <div class="table-responsive">
                                <table class="table idt-table-report table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Họ và Tên</th>
                                            <th>Email đăng nhập</th>
                                            <th class="text-center">Chức vụ</th>
                                            <th class="text-center">Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody id="admin-user-list">
                                        <tr><td colspan="4" class="text-center text-muted">Chưa có dữ liệu nhân viên...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row" id="report1">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header-idt">
                            <h4 class="title-idt"><i class="fa fa-list"></i> HỆ THỐNG QUẢN LÝ BẢO HÀNH</h4>
                        </div>
                        <div class="card-body no-padding">
                            <div class="table-responsive">
                                <table class="table idt-table-report table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mã Thiết Bị</th>
                                            <th>Tên Thiết Bị</th>
                                            <th>Khách Hàng</th>
                                            <th>Mô Tả (S/N)</th>
                                            <th class="text-center">Tình Trạng</th>
                                            <th class="text-center">Xem Chi Tiết</th>
                                        </tr>
                                    </thead>
                                    <tbody id="admin-warranty-list">
                                        <tr><td colspan="6" class="text-center">Đang tải dữ liệu bảo hành...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row" id="report3">
                <div class="col-md-12">
                    <div class="card card-idt-main">
                        <div class="card-header-idt">
                            <h4 class="title-idt"><i class="fa fa-history"></i> THEO DÕI TIẾN ĐỘ SỬA CHỮA</h4>
                        </div>
                        <div class="card-body no-padding">
                            <div class="table-responsive">
                                <table class="table idt-table-report table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mã Phiếu</th>
                                            <th>Thiết Bị</th>
                                            <th>Kỹ Thuật Viên</th>
                                            <th>Mô Tả</th>
                                            <th class="text-center">Trạng Thái</th>
                                            <th class="text-center">Bổ Nhiệm</th>
                                        </tr>
                                    </thead>
                                    <tbody id="admin-repair-list">
                                        <tr><td colspan="5" class="text-center">Đang tải dữ liệu sửa chữa...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            </div> </div> </div> <div class="modal fade" id="createEmployeeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cấp tài khoản nhân sự mới</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="form-create-employee">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" id="emp_name" class="form-control" placeholder="Nhập tên nhân viên..." required>
                        </div>
                        <div class="form-group">
                            <label>Email đăng nhập <span class="text-danger">*</span></label>
                            <input type="email" id="emp_email" class="form-control" placeholder="nguyenvana@idtvietnam.vn" required>
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu khởi tạo <span class="text-danger">*</span></label>
                            <input type="text" id="emp_password" class="form-control" placeholder="Nhập mật khẩu..." required>
                            <small class="text-muted">Mật khẩu này sẽ được mã hóa an toàn vào Database.</small>
                        </div>
                        <div class="form-group">
                            <label>Chức vụ <span class="text-danger">*</span></label>
                            <select id="emp_role" class="form-control" required>
                                <option value="staff">Nhân viên kỹ thuật (Staff)</option>
                                <option value="manager">Quản lý (Manager)</option>
                                <option value="admin">Quản trị viên (Admin)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success">Tạo tài khoản</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cấp lại mật khẩu</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-reset-password">
                <div class="modal-body">
                    <input type="hidden" id="reset_emp_id">
                    <p>Đang thao tác trên tài khoản: <strong id="reset_emp_name" class="text-primary"></strong></p>
                    
                    <div class="form-group">
                        <label>Mật khẩu mới <span class="text-danger">*</span></label>
                        <input type="text" id="reset_new_password" class="form-control" placeholder="Nhập mật khẩu mới..." required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../js/jquery/jquery.min.js"></script>
    <script src="../js/popper/popper.min.js"></script>
    <script src="../js/bootstrap/bootstrap.min.js"></script>
    <script src="../js/front.js"></script>

    <script src="js/admin_actions.js"></script>
    <script src="js/manager_actions.js"></script>

<!-- MODAL XEM CHI TIẾT THIẾT BỊ -->
<div class="modal fade" id="deviceDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detail-modal-title">Chi tiết thiết bị</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
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

<!-- MODAL BỔ NHIỆM KỸ THUẬT VIÊN -->
<div class="modal fade" id="assignStaffModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-user-plus"></i> Bổ nhiệm kỹ thuật viên</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assign_ticket_id">
                <p>Phiếu: <strong id="assign_ticket_label" class="text-primary"></strong></p>
                <div class="form-group">
                    <label>Chọn kỹ thuật viên <span class="text-danger">*</span></label>
                    <select id="assign_staff_id" class="form-control">
                        <option value="">-- Đang tải... --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hạn hoàn thành (Deadline)</label>
                    <input type="date" id="assign_due_date" class="form-control" min="<?= date('Y-m-d') ?>">
                    <small class="text-muted">Không bắt buộc. Nhân viên sẽ được thông báo nếu gần hết hạn.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success" onclick="submitAssign()">
                    <i class="fa fa-check"></i> Xác nhận bổ nhiệm
                </button>
            </div>
        </div>
    </div>
</div>
</body>
</html>