<?php
session_start();

// [FIX] Whitelist: chỉ cho phép nhân viên nội bộ (staff), chặn customer và role lạ
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit();
}
if ($_SESSION['role'] === 'manager') {
    header("Location: quanly.php");
    exit();
}

// [FIX] Chặn customer và bất kỳ role nào không phải staff
if ($_SESSION['role'] !== 'staff') {
    header("Location: index.php");
    exit();
}
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
                    <h1 class="h4">Nhân viên</h1>
                </div>
            </div>
        </nav>
        <div class="content-inner">

<!--REPORT-3-->
<div class="row" id="report3">
    <div class="col-md-12">
        <div class="card card-idt-main">
            <div class="card-header-idt">
                <h4 class="title-idt"><i class="fa fa-wrench"></i> Danh sách sửa chữa được giao</h4>
            </div>
            <div class="card-body no-padding">
                <div class="table-responsive">
                    <table class="table idt-table-report table-hover">
                        <thead>
                            <tr>
                                <th>Mã Case</th>
                                <th>Thiết bị</th>
                                <th>Khách hàng</th>
                                <th>Tiến độ</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody id="tech-repair-list">
                            <tr><td colspan="6" class="text-center">Đang tải danh sách công việc...</td></tr>
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
    
    <!--Core Javascript -->
    <script src="js/mychart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    fetch('../../backend/api/get_my_tickets.php', {credentials:'include'})
    .then(r => { if(r.status===401){window.location.href='index.php';return null;} return r.json(); })
    .then(res => {
        if (!res) return;
        const tbody = document.getElementById('tech-repair-list');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!res.success || !res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Bạn hiện chưa có công việc nào được giao.</td></tr>';
            return;
        }
        res.data.forEach(item => {
            let sc='badge-secondary', st='Chờ xử lý';
            if(item.status==='repairing'){sc='badge-warning'; st='Đang sửa chữa';}
            if(item.status==='completed'){sc='badge-success'; st='Đã hoàn tất';}

            const bar = parseInt(item.progress)||0;
            const barColor = bar>=90?'bg-success':bar<30?'bg-danger':'bg-info';

            tbody.innerHTML += `<tr>
                <td><strong>#TICK-${item.id}</strong></td>
                <td>${item.device_name??'—'}<br><small class="text-muted">S/N: ${item.serial_number??'—'}</small></td>
                <td>${item.customer_name??'—'}<br><small class="text-muted">${item.customer_phone??''}</small></td>
                <td class="align-middle">
                    <div class="progress idt-progress-bar" style="margin-bottom:3px;">
                        <div class="progress-bar ${barColor}" style="width:${bar}%;"></div>
                    </div>
                    <small class="font-weight-bold">${bar}%</small>
                </td>
                <td class="text-center"><span class="badge ${sc} p-2">${st}</span></td>
                <td class="text-center">
                    <button class="btn-idt-fixed btn-blue" onclick="updateTicketStatus(${item.id})">
                        <i class="fa fa-edit"></i> Cập nhật
                    </button>
                </td>
            </tr>`;
        });
    }).catch(err => {
        console.error(err);
        document.getElementById('tech-repair-list').innerHTML =
            '<tr><td colspan="6" class="text-center text-danger">Không thể kết nối máy chủ.</td></tr>';
    });
});

function updateTicketStatus(ticketId) {
    const newStatus = prompt('Nhập trạng thái mới:\n- repairing (đang sửa)\n- completed (hoàn tất)\n- cancelled (hủy)');
    if (!newStatus) return;
    const allowed = ['repairing','completed','cancelled'];
    if (!allowed.includes(newStatus)) { alert('Trạng thái không hợp lệ!'); return; }

    fetch('../../backend/api/repair_tickets.php', {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: ticketId, status: newStatus})
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) { alert('✅ Cập nhật thành công!'); location.reload(); }
        else alert('❌ ' + res.error);
    }).catch(() => alert('Lỗi kết nối!'));
}
</script>
</body>
</html>
