<?php
session_name('STAFF_SESSION');
session_start();
require_once "../../backend/config/database.php";

// Auth guard
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'staff'])) {
    header("Location: index.php");
    exit();
}

// Lấy toàn bộ danh sách thiết bị
$sql = "SELECT d.*, c.name as customer_name FROM devices d LEFT JOIN customers c ON d.customer_id = c.id ORDER BY d.id DESC";
$res = mysqli_query($conn, $sql);
$all_devices = mysqli_fetch_all($res, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Toàn bộ thiết bị & Bảo hành</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">
    <div class="p-4">
        <div class="mb-3">
            <a href="quanly.php" class="btn btn-secondary btn-sm"><i class="fa fa-chevron-left"></i> Quay lại Bảng điều khiển</a>
        </div>

        <div class="card card-idt-main">
            <div class="card-header-idt">
                <h4 class="title-idt"><i class="fa fa-laptop"></i> DANH SÁCH TOÀN BỘ THIẾT BỊ & BẢO HÀNH</h4>
            </div>
            <div class="card-body no-padding">
                <div class="table-responsive">
                    <table class="table idt-table-report table-hover">
                        <thead>
                            <tr class="bg-success text-white">
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
                                <td class="text-center">
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
</body>
<div class="modal fade" id="modalViewDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fa fa-info-circle"></i> Chi tiết thiết bị</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="deviceDetailContent">
                <div class="text-center p-5">
                    <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                    <p>Đang tải dữ liệu...</p>
                </div>
            </div>
        </div>
    </div>
</div>
</html>