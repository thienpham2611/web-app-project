<?php
session_name('STAFF_SESSION');
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

<div class="modal fade" id="updateTicketModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel"><i class="fa fa-edit"></i> Cập nhật tiến độ sửa chữa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal_ticket_id">
                
                <div class="form-group">
                    <label class="font-weight-bold">Trạng thái hiện tại:</label><br>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="st_repairing" name="modal_status" value="repairing" class="custom-control-input" onchange="toggleProgress()">
                        <label class="custom-control-label text-info" for="st_repairing">Đang sửa</label>
                    </div>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="st_completed" name="modal_status" value="completed" class="custom-control-input" onchange="toggleProgress()">
                        <label class="custom-control-label text-success" for="st_completed">Hoàn tất</label>
                    </div>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="st_cancelled" name="modal_status" value="cancelled" class="custom-control-input" onchange="toggleProgress()">
                        <label class="custom-control-label text-danger" for="st_cancelled">Hủy/Không sửa được</label>
                    </div>
                </div>

                <div class="form-group" id="progress_wrapper">
                    <label class="font-weight-bold">Tiến độ: <span id="progress_display" class="text-primary h5">50%</span></label>
                    <input type="range" class="form-control-range" id="modal_progress" min="0" max="100" value="50" oninput="document.getElementById('progress_display').innerText = this.value + '%'">
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Ghi chú (Tùy chọn):</label>
                    <textarea class="form-control" id="modal_note" rows="3" placeholder="VD: Đang chờ linh kiện màn hình, đã sấy khô main..."></textarea>
                    <small class="text-muted">Ghi chú này sẽ được lưu vào lịch sử sửa chữa để quản lý và khách hàng theo dõi.</small>
                </div>

                <hr>
                <h6><i class="fa fa-history"></i> Lịch sử xử lý:</h6>
                <div id="ticket_timeline" style="max-height: 250px; overflow-y: auto; padding: 10px; background: #f9f9f9; border-radius: 5px;">
                    <small class="text-muted">Đang tải lịch sử...</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="submitTicketUpdate()"><i class="fa fa-save"></i> Lưu cập nhật</button>
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
    <script src="js/staff_actions.js"></script>
    
    <!--Core Javascript -->
    <script src="js/mychart.js"></script>

</body>
</html>
