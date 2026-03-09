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

<!--API-->
<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // --- CẤU HÌNH ĐƯỜNG DẪN API (Bên Backend sẽ xử lý tệp này) ---
    // API này thường sẽ dựa vào Session (người đang đăng nhập) để trả về đúng ticket của họ
    const API_MY_TICKETS = 'api/get_my_tickets.php'; 

    fetch(API_MY_TICKETS)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('tech-repair-list');
            tbody.innerHTML = ''; // Xóa dòng thông báo "Đang tải"

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">Bạn hiện chưa có công việc nào được giao.</td></tr>';
                return;
            }

            data.forEach(item => {
                // Xác định màu sắc trạng thái (Cùng style với file CSS của bạn)
                let statusClass = 'btn-info-idt'; // Mặc định
                if(item.status === 'Hoàn thành') statusClass = 'btn-success-idt';
                if(item.status === 'Đang sửa') statusClass = 'btn-warning-idt';

                // Xác định màu thanh tiến độ
                let progressColor = 'bg-warning';
                if(item.progress >= 100) progressColor = 'bg-success';
                if(item.progress < 20) progressColor = 'bg-danger';

                tbody.innerHTML += `
                    <tr>
                        <td><strong>${item.id}</strong></td>
                        <td>${item.device_name}</td>
                        <td>${item.customer_name}</td>
                        <td class="align-middle">
                            <div class="progress idt-progress-bar">
                                <div class="progress-bar ${progressColor}" style="width: ${item.progress}%;"></div>
                            </div>
                            <small>${item.progress}%</small>
                        </td>
                        <td class="text-center">
                            <span class="status-btn ${statusClass}">${item.status}</span>
                        </td>
                        <td class="text-center">
                            <button class="btn-idt-fixed btn-blue" onclick="updateTicket('${item.id}')">
                                <i class="fa fa-edit"></i> Cập nhật
                            </button>
                        </td>
                    </tr>
                `;
            });
        })
        .catch(err => {
            console.error("Lỗi kết nối API Nhân viên:", err);
            document.getElementById('tech-repair-list').innerHTML = 
                '<tr><td colspan="6" class="text-center text-danger">Không thể kết nối máy chủ.</td></tr>';
        });
});

// Hàm mẫu xử lý khi nhấn nút Cập nhật
function updateTicket(ticketId) {
    // Backend sẽ xử lý việc mở Modal hoặc chuyển hướng trang tại đây
    console.log("Đang mở cập nhật cho Case: " + ticketId);
    alert("Chức năng cập nhật tiến độ cho Case: " + ticketId + " đang được xử lý.");
}
</script>


<script>
// JS Load dữ liệu riêng cho nhân viên
document.addEventListener("DOMContentLoaded", function() {
    // Gọi API lấy ticket theo ID nhân viên (lấy từ Session)
    fetch('api/get_my_tickets.php') 
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById('tech-repair-list');
        tbody.innerHTML = '';
        data.forEach(item => {
            tbody.innerHTML += `
                <tr>
                    <td><strong>${item.id}</strong></td>
                    <td>${item.device_name}</td>
                    <td>${item.customer_name}</td>
                    <td class="align-middle">
                        <div class="progress idt-progress-bar">
                            <div class="progress-bar bg-warning" style="width: ${item.progress}%;"></div>
                        </div>
                    </td>
                    <td class="action-col"><span class="status-btn btn-warning-idt">${item.status}</span></td>
                    <td class="action-col"><button class="btn-idt-fixed btn-blue">Cập nhật</button></td>
                </tr>
            `;
        });
    });
});
</script>

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
</body>

</html>
