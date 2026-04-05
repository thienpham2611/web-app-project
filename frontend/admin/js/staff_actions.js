/**
 * staff_actions.js - Quản lý logic dành riêng cho nhân viên kỹ thuật
 */

document.addEventListener("DOMContentLoaded", function() {
    loadMyTickets();
});

// 1. Tải danh sách công việc được giao
function loadMyTickets() {
    fetch('../../backend/api/get_my_tickets.php', {credentials:'include'})
    .then(r => { 
        if(r.status===401){ window.location.href='index.php'; return null; } 
        return r.json(); 
    })
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
            if(item.status==='repairing'){sc='badge-info'; st='Đang sửa';}
            if(item.status==='completed'){sc='badge-success'; st='Hoàn tất';}
            if(item.status==='cancelled'){sc='badge-danger'; st='Đã hủy';}

            const bar = parseInt(item.progress)||0;
            const barColor = bar>=90?'bg-success':bar<30?'bg-danger':'bg-info';

            tbody.innerHTML += `<tr>
                <td><strong>#RT-${item.id}</strong></td>
                <td>${item.device_name??'—'}<br><small class="text-muted">S/N: ${item.serial_number??'—'}</small></td>
                <td>${item.customer_name??'—'}<br><small class="text-muted">${item.customer_phone??''}</small></td>
                <td class="align-middle">
                    <div class="progress idt-progress-bar" style="margin-bottom:3px; height: 8px;">
                        <div class="progress-bar ${barColor}" style="width:${bar}%;"></div>
                    </div>
                    <small class="font-weight-bold">${bar}%</small>
                </td>
                <td class="text-center"><span class="badge ${sc} p-2">${st}</span></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" onclick="updateTicketStatus(${item.id})">
                        <i class="fa fa-edit"></i> Cập nhật
                    </button>
                </td>
            </tr>`;
        });
    }).catch(err => {
        console.error(err);
        document.getElementById('tech-repair-list').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Lỗi tải dữ liệu.</td></tr>';
    });
}

// 2. Logic cập nhật mới: Trạng thái + Tiến độ %
function updateTicketStatus(ticketId) {
    const newStatus = prompt('Nhập trạng thái mới:\n- repairing (đang sửa)\n- completed (hoàn tất)\n- cancelled (hủy)');
    if (!newStatus) return;
    
    const allowed = ['repairing','completed','cancelled'];
    if (!allowed.includes(newStatus)) { alert('Trạng thái không hợp lệ!'); return; }

    let progress = 0;
    
    // Logic: Nếu xong thì auto 100%, nếu đang sửa thì hỏi %
    if (newStatus === 'completed') {
        progress = 100;
    } else if (newStatus === 'cancelled') {
        progress = 0;
    } else {
        let inputProgress = prompt('Nhập tiến độ % hiện tại (0-99):', '50');
        if (inputProgress === null) return;
        progress = parseInt(inputProgress);
        
        if (isNaN(progress) || progress < 0 || progress > 100) {
            alert('Tiến độ phải là số từ 0 đến 100!');
            return;
        }
    }

    fetch('../../backend/api/repair_tickets.php', {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        credentials: 'include', 
        body: JSON.stringify({id: ticketId, status: newStatus, progress: progress})
    })
    .then(async r => {
        const text = await r.text(); // Lấy văn bản thô để "soi" lỗi PHP nếu có
        if (!r.ok) {
            console.error("HTTP Error:", text);
            throw new Error("Lỗi HTTP: " + r.status);
        }
        try {
            return JSON.parse(text); // Thử ép sang JSON
        } catch (e) {
            console.error("⚠️ PHÁT HIỆN LỖI PHP NGẦM:", text);
            throw new Error("Dữ liệu trả về không phải JSON. Hãy xem Console.");
        }
    })
    .then(res => {
        if (res.success) { 
            alert('✅ Đã cập nhật lên ' + progress + '%'); 
            loadMyTickets(); 
        } else {
            alert('❌ ' + res.error);
        }
    })
    .catch(err => {
        console.error("Chi tiết lỗi:", err);
        alert('Lỗi hệ thống! Vui lòng ấn F12 và xem tab Console để biết dòng PHP nào đang lỗi.');
    });
}

function logoutStaff() {
    if(!confirm("Đăng xuất khỏi hệ thống?")) return;
    fetch("../../backend/api/logout.php", { credentials: "include" })
    .then(() => { window.location.href = "index.php"; });
}