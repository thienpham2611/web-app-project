
// ==========================================
// THÔNG BÁO NHÂN VIÊN / QUẢN LÝ
// ==========================================
function loadStaffNotifications() {
    fetch('../../backend/api/notifications_staff.php', { credentials: 'include' })
    .then(r => r.json())
    .then(res => {
        const list  = document.getElementById('staff-notif-list');
        const badge = document.getElementById('staff-notif-badge');
        if (!list || !badge) return;

        if (!res.success || res.data.length === 0) {
            list.innerHTML = '<div class="text-center text-muted py-3 small">Không có thông báo nào</div>';
            badge.style.display = 'none';
            return;
        }

        const unread = res.data.filter(n => !parseInt(n.is_read)).length;
        badge.textContent = unread > 9 ? '9+' : unread;
        badge.style.display = unread > 0 ? 'inline-block' : 'none';

        list.innerHTML = res.data.map(n => {
            const bg   = parseInt(n.is_read) ? '' : 'background:#fff8e1;';
            const d    = new Date(n.created_at);
            const time = d.toLocaleString('vi-VN', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'});
            return `<div class="dropdown-item py-2 border-bottom" style="white-space:normal;font-size:13px;${bg}cursor:pointer;"
                        onclick="markStaffNotifRead(${n.id}, this)">
                        <div>${n.message}</div>
                        <small class="text-muted">${time}</small>
                    </div>`;
        }).join('');
    })
    .catch(() => {
        const list = document.getElementById('staff-notif-list');
        if (list) list.innerHTML = '<div class="text-center text-danger py-2 small">Lỗi tải thông báo</div>';
    });
}

function markStaffNotifRead(id, el) {
    fetch('../../backend/api/notifications_staff.php', {
        method: 'PUT', headers: {'Content-Type':'application/json'},
        credentials: 'include',
        body: JSON.stringify({ id })
    }).then(() => {
        if (el) el.style.background = '';
        loadStaffNotifications();
    });
}

function markAllStaffNotifRead() {
    fetch('../../backend/api/notifications_staff.php', {
        method: 'PUT', headers: {'Content-Type':'application/json'},
        credentials: 'include',
        body: JSON.stringify({ mark_all: true })
    }).then(() => loadStaffNotifications());
}

document.addEventListener('DOMContentLoaded', function() {
    loadStaffNotifications();
    const bell = document.getElementById('staff-notif-bell');
    if (bell) {
        bell.addEventListener('show.bs.dropdown', function() {
            fetch('../../backend/api/notifications_staff.php', {
                method: 'PUT', headers: {'Content-Type':'application/json'},
                credentials: 'include',
                body: JSON.stringify({ mark_all: true })
            }).then(() => loadStaffNotifications());
        });
    }
});

/**
 * manager_actions.js - Logic trang Manager
 * [FIX] Đổi $.ajax (gửi form-data) sang fetch JSON để đồng bộ với assign_ticket.php
 */


