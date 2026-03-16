<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}
if ($_SESSION['role'] === 'manager') {
    header("Location: quanly.php");
    exit();
}
if ($_SESSION['role'] === 'staff') {
    header("Location: nhanvien.php");
    exit();
}
// Nếu lọt qua hết các lệnh trên, nghĩa là role === 'admin', cho phép tải trang
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>

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
                <div class="avatar"><img src="img/avatar.jpg" alt="..." class="img-fluid rounded-circle"></div>
                <div class="title">
                    <h1 class="h4">Admin</h1>
                </div>
            </div>
            <hr>
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
                                            <th class="text-center">Ngày Hết Hạn</th>
                                            <th class="text-center">Tình Trạng</th>
                                            <th class="text-center">Hành Động</th>
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
                                            <th>Mã Case</th>
                                            <th>Thiết bị</th>
                                            <th>Kỹ thuật viên</th>
                                            <th>Tiến độ</th>
                                            <th class="text-center">Trạng thái</th>
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // --- CẤU HÌNH ĐƯỜNG DẪN API (Bên Backend sẽ cung cấp các link này) ---
    const API_WARRANTY = "api/get_warranty.php"; // Placeholder cho Report 1
    const API_REPAIRS  = "api/get_repairs.php";  // Placeholder cho Report 3

    // 1. KẾT NỐI REPORT 1 (BẢO HÀNH)
    fetch(API_WARRANTY)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('admin-warranty-list');
            tbody.innerHTML = ''; // Xóa dòng "Đang tải"

            data.forEach(item => {
                let statusClass = item.status === 'active' ? 'text-status-good' : 'text-status-expired';
                let statusText  = item.status === 'active' ? 'Đang bảo hành' : 'Đã hết hạn';

                tbody.innerHTML += `
                    <tr>
                        <td><strong>${item.id}</strong></td>
                        <td>${item.device_name}</td>
                        <td>${item.customer_name}</td>
                        <td class="text-center">${item.warranty_end_date}</td>
                        <td class="text-center"><span class="${statusClass}">${statusText}</span></td>
                        <td class="action-col text-center">
                            <button class="btn-idt-fixed btn-red" onclick="extendWarranty('${item.id}')">Gia hạn</button>
                        </td>
                    </tr>
                `;
            });
        })
        .catch(err => console.error("Lỗi kết nối API Report 1:", err));


    // 2. KẾT NỐI REPORT 3 (SỬA CHỮA)
    fetch(API_REPAIRS)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('admin-repair-list');
            tbody.innerHTML = ''; // Xóa dòng "Đang tải"

            data.forEach(item => {
                let barColor = 'bg-info';
                if(item.progress >= 90) barColor = 'bg-success';
                if(item.progress < 30) barColor = 'bg-danger';

                tbody.innerHTML += `
                    <tr>
                        <td><strong>${item.id}</strong></td>
                        <td>${item.device_name}</td>
                        <td>${item.technician_name}</td>
                        <td class="align-middle">
                            <div class="progress idt-progress-bar">
                                <div class="progress-bar ${barColor}" style="width: ${item.progress}%;"></div>
                            </div>
                            <small>${item.progress}%</small>
                        </td>
                        <td class="action-col text-center">
                            <span class="status-btn btn-info-idt">${item.status}</span>
                        </td>
                    </tr>
                `;
            });
        })
        .catch(err => console.error("Lỗi kết nối API Report 3:", err));
});

function extendWarranty(id) {
    alert("Gửi yêu cầu gia hạn cho thiết bị: " + id);
}
</script>

<script>
// JS Load dữ liệu cho Admin
document.addEventListener("DOMContentLoaded", function() {
    // 1. Load Bảo hành
    fetch('api/get_all_devices.php')
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById('admin-warranty-list');
        tbody.innerHTML = '';
        data.forEach(item => {
            let statusClass = item.status === 'active' ? 'text-status-good' : 'text-status-expired';
            let statusText = item.status === 'active' ? 'Đang bảo hành' : 'Đã hết hạn';
            tbody.innerHTML += `
                <tr>
                    <td><strong>${item.id}</strong></td>
                    <td>${item.device_name}</td>
                    <td>${item.customer_name}</td>
                    <td class="text-center">${item.warranty_end_date}</td>
                    <td class="text-center"><span class="${statusClass}">${statusText}</span></td>
                    <td class="action-col"><button class="btn-idt-fixed btn-red">Gia hạn</button></td>
                </tr>
            `;
        });
    })
    .catch(err => console.log("Không tìm thấy API get_all_devices.php"));

    // 2. Load Tiến độ
    fetch('api/get_all_tickets.php')
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById('admin-repair-list');
        tbody.innerHTML = '';
        data.forEach(item => {
            tbody.innerHTML += `
                <tr>
                    <td><strong>${item.id}</strong></td>
                    <td>${item.device_name}</td>
                    <td>${item.technician_name}</td>
                    <td class="align-middle">
                        <div class="progress idt-progress-bar">
                            <div class="progress-bar bg-info" style="width: ${item.progress}%;"></div>
                        </div>
                    </td>
                    <td class="action-col"><span class="status-btn btn-info-idt">${item.status}</span></td>
                </tr>
            `;
        });
    })
    .catch(err => console.log("Không tìm thấy API get_all_tickets.php"));
    // 3. Load Danh sách nhân viên nội bộ
    fetch('../backend/api/get_users.php')
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            const tbody = document.getElementById('admin-user-list');
            tbody.innerHTML = ''; // Xóa dòng "Chưa có dữ liệu..." đi
            
            res.data.forEach(user => {
                // Chuyển đổi Role thành Tiếng Việt và Gắn Badge màu
                let roleBadge = '';
                if(user.role === 'admin') roleBadge = '<span class="badge badge-danger">Quản trị viên</span>';
                else if(user.role === 'manager') roleBadge = '<span class="badge badge-warning">Quản lý</span>';
                else roleBadge = '<span class="badge badge-info">Kỹ thuật viên</span>';

                // Vẽ từng dòng HTML tương ứng với từng nhân viên
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${user.name}</strong></td>
                        <td>${user.email}</td>
                        <td class="text-center">${roleBadge}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        }
    })
    .catch(err => console.log("Lỗi tải API get_users.php"));
});
</script>

<script src="../js/jquery/jquery.min.js"></script>
    <script src="../js/popper/popper.min.js"></script>
    <script src="../js/bootstrap/bootstrap.min.js"></script>
    <script src="../js/front.js"></script>

    <script src="js/admin_actions.js"></script>
</body>
</html>