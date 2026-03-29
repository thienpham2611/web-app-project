/**
 * manager_actions.js - Quản lý tập trung toàn bộ logic trang Manager (Quản lý)
 */

function assignTicket(ticketId) {
    // Lấy ID của nhân viên được chọn trong dropdown
    var staffId = $('#staff_assign_' + ticketId).val();

    if (!staffId) {
        alert("Vui lòng chọn một kỹ thuật viên để giao việc!");
        return;
    }

    if (confirm("Xác nhận giao phiếu #TICK-" + ticketId + " cho kỹ thuật viên này?")) {
        $.ajax({
            url: '../../backend/api/assign_ticket.php',
            type: 'POST',
            data: {
                ticket_id: ticketId,
                staff_id: staffId
            },
            success: function(response) {
                try {
                    var res = (typeof response === 'string') ? JSON.parse(response) : response;
                    if (res.success) {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert("Lỗi: " + (res.error || "Không xác định"));
                    }
                } catch(e) {
                    alert("Lỗi xử lý phản hồi từ máy chủ.");
                }
            },
            error: function() {
                alert("Không kết nối được với máy chủ.");
            }
        });
    }
}