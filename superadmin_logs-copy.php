<?php
require_once __DIR__ . '/security.php';
app_start_session();
include 'db_connect.php';

require_active_roles($conn, ['superadmin'], false);

include 'header.php';
?>
      </div> <!-- close inner_container from header.php -->
   </div> <!-- close full_container from header.php -->

<style>
body {
    background: #f1f5f9 !important;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    overflow-y: scroll !important;
}
.superadmin-portal-wrapper {
    width: 100%;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f8fafc;
}
.sa-topbar {
    background: #0a2540;
    padding: 16px 0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    position: sticky;
    top: 0;
    z-index: 1050;
}
.sa-topbar-inner {
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
    padding: 0 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.sa-topbar .sa-brand {
    color: #ffffff;
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 0.5px;
}
.sa-topbar .sa-nav-links a {
    color: #cbd5e1;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.2s ease;
    padding: 8px 16px;
    border-radius: 8px;
}
.sa-topbar .sa-nav-links a:hover, .sa-topbar .sa-nav-links a.active {
    color: #ffffff;
    background: rgba(255,255,255,0.1);
}
.sa-topbar .dropdown-toggle::after {
    display: none !important;
}
.dropdown-menu {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}
.dropdown-item {
    padding: 12px 16px;
    transition: background 0.2s;
}
.dropdown-item:hover {
    background: #f8fafc;
}
.notification-item {
    border-bottom: 1px solid #f1f5f9;
}
.notification-item:last-child {
    border-bottom: none;
}
.sa-content-container {
    padding: 40px;
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
}
.sa-table-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.02);
}
</style>
<link rel="stylesheet" href="css/superadmin-dashboard.css?v=<?php echo (int)@filemtime(__DIR__ . '/css/superadmin-dashboard.css'); ?>" />

<div class="superadmin-portal-wrapper">
    <aside class="sa-sidebar">
        <a class="sa-brand" href="superadmin_dashboard.php" aria-label="Dashboard SuperAdmin">
            <span class="sa-brand-mark"><i class="fa fa-shield"></i></span>
            <span><small>DRS</small> SuperAdmin</span>
        </a>
        <nav class="sa-side-nav" aria-label="Navigasi SuperAdmin">
            <span class="sa-side-label">Workspace</span>
            <a href="superadmin_dashboard.php"><i class="fa fa-th-large"></i><span>Dashboard</span></a>
            <a class="active" href="superadmin_logs.php"><i class="fa fa-history"></i><span>Log Aktiviti</span></a>
            <a href="superadmin_tetapan.php"><i class="fa fa-sliders"></i><span>Tetapan Sistem</span></a>
        </nav>
        <div class="sa-side-status"><span class="sa-live-dot"></span><div><strong>Sistem beroperasi</strong><small>Perkhidmatan platform aktif</small></div></div>
        <form method="post" action="logout.php" class="sa-logout-form">
            <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
            <button type="submit"><i class="fa fa-sign-out"></i><span>Log Keluar</span></button>
        </form>
    </aside>

    <div class="sa-workspace">
        <header class="sa-workspace-bar">
            <div class="sa-workspace-context"><span>Platform</span><i class="fa fa-angle-right"></i><strong>Log Aktiviti</strong></div>
            <div class="sa-workspace-actions">
                <div class="dropdown">
                    <button type="button" class="sa-nav-icon sa-notification-button dropdown-toggle" data-toggle="dropdown" aria-label="Notifikasi sistem">
                        <svg class="sa-button-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><span class="sa-nav-button-label">Notifikasi</span><span id="bellBadge" class="sa-notification-count">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right sa-notification-menu">
                        <div class="dropdown-header">
                            <div><strong>Notifikasi</strong><small id="notificationSummary">Memuatkan...</small></div>
                            <button type="button" id="markAllNotifications" onclick="markAllNotificationsRead(event)" disabled>Tanda semua dibaca</button>
                        </div>
                        <div class="sa-notification-list" id="notificationList" aria-live="polite"><div class="text-center text-muted py-3">Memuatkan...</div></div>
                    </div>
                </div>
                <span class="sa-admin-chip"><span>SA</span><strong>SuperAdmin</strong></span>
                <a class="sa-btn sa-btn-secondary" href="superadmin_dashboard.php"><i class="fa fa-arrow-left"></i> Dashboard</a>
            </div>
        </header>

        <main class="superadmin-main-body sa-log-main">
            <div class="sa-page-heading">
                <div><span class="sa-eyebrow">Audit platform</span><h1>Log aktiviti sistem</h1><p>Jejak aktiviti pengguna dan perubahan penting dalam satu paparan.</p></div>
            </div>
            <section class="sa-panel sa-log-panel">
                <div class="sa-panel-header sa-log-header">
                    <div><span class="sa-section-kicker">1000 rekod terkini</span><h2 class="sa-table-title">Audit trail</h2></div>
                    <div class="sa-log-tools">
                        <label class="sa-search" for="logSearch"><i class="fa fa-search"></i><input id="logSearch" type="search" placeholder="Cari pengguna, sekolah atau aktiviti"></label>
                        <button type="button" class="sa-btn sa-btn-secondary" onclick="exportLogsCsv()"><i class="fa fa-download"></i> CSV</button>
                    </div>
                </div>
            <div class="table-responsive">
                <table class="table-tenant table table-hover table-borderless" id="logsTable" width="100%">
                    <thead>
                        <tr>
                            <th>Tarikh & Masa</th>
                            <th>Sekolah</th>
                            <th>Pengguna</th>
                            <th>Peranan</th>
                            <th>Aktiviti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = "SELECT l.*, s.nama_sekolah, u.role FROM activity_logs l 
                              LEFT JOIN sekolah s ON l.school_id = s.id 
                              LEFT JOIN users u ON l.user_id = u.id 
                              ORDER BY l.id DESC LIMIT 1000";
                        $res = mysqli_query($conn, $q);
                        if($res) {
                            while($row = mysqli_fetch_assoc($res)) {
                                echo "<tr data-log-search='" . escape_html(strtolower(implode(' ', [$row['nama_sekolah'] ?? 'Sistem Pusat', $row['nama_user'] ?? 'Sistem', $row['role'] ?? '-', $row['aktiviti'] ?? '']))) . "'>";
                                echo "<td><strong>" . date('d/m/Y h:i A', strtotime($row['masa'])) . "</strong></td>";
                                echo "<td><strong>" . escape_html($row['nama_sekolah'] ?? 'Sistem Pusat') . "</strong></td>";
                                echo "<td>" . escape_html($row['nama_user'] ?? 'Sistem') . "</td>";
                                echo "<td><span class='sa-role-badge'>" . escape_html($row['role'] ?? '-') . "</span></td>";
                                echo "<td>" . escape_html($row['aktiviti'] ?? '') . "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            </section>
        </main>
    </div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
function fetchNotifications() {
    fetch('superadmin_ajax.php?action=get_stats')
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            let totalNotif = data.stats.pending_schools + data.stats.expiring_schools;
            let badge = document.getElementById('bellBadge');
            if(totalNotif > 0) {
                badge.innerText = totalNotif;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }

            let notifHtml = '';
            if(totalNotif === 0) {
                notifHtml = '<div class="text-center text-muted py-3" style="font-size: 13px;">Tiada notifikasi baharu.</div>';
            } else {
                if(data.stats.pending_schools > 0) {
                    notifHtml += `<a class="dropdown-item" href="superadmin_dashboard.php" style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: start; gap: 12px; white-space: normal;">
                                    <div style="background: #fffbeb; color: #d97706; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fa fa-clock-o"></i></div>
                                    <div><div style="font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 2px;">Permohonan Baharu</div><div style="font-size: 12px; color: #64748b;">${data.stats.pending_schools} sekolah sedang menunggu kelulusan anda.</div></div>
                                  </a>`;
                }
                if(data.stats.expiring_schools > 0) {
                    notifHtml += `<a class="dropdown-item" href="superadmin_dashboard.php#expiringSoonList" style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: start; gap: 12px; white-space: normal;">
                                    <div style="background: #fef2f2; color: #dc2626; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fa fa-exclamation-circle"></i></div>
                                    <div><div style="font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 2px;">Langganan Hampir Tamat</div><div style="font-size: 12px; color: #64748b;">${data.stats.expiring_schools} sekolah akan tamat tempoh dalam masa 1 hari.</div></div>
                                  </a>`;
                }
            }
            document.getElementById('notificationList').innerHTML = notifHtml;
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    fetchNotifications();
    setInterval(fetchNotifications, 60000); // 1 minit
});
</script>

<script>
function escapeLogHtml(value) {
    var div = document.createElement('div');
    div.textContent = String(value == null ? '' : value);
    return div.innerHTML;
}

function fetchNotifications() {
    fetch('superadmin_ajax.php?action=get_stats')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.status !== 'success') return;
            window.saNotifications = data.notifications || [];
            var unread = window.saNotifications.filter(function(item) { return !item.is_read; }).length;
            var badge = document.getElementById('bellBadge');
            var bell = document.querySelector('.sa-nav-icon');
            badge.textContent = unread > 99 ? '99+' : String(unread);
            badge.style.display = unread ? 'inline-flex' : 'none';
            bell.classList.toggle('has-unread', unread > 0);
            document.getElementById('notificationSummary').textContent = unread ? unread + ' belum dibaca' : 'Semua sudah dibaca';
            document.getElementById('markAllNotifications').disabled = unread === 0;

            if (!window.saNotifications.length) {
                document.getElementById('notificationList').innerHTML = '<div class="sa-notification-empty"><i class="fa fa-check-circle"></i><strong>Tiada notifikasi</strong><span>Semua urusan platform terkawal.</span></div>';
                return;
            }
            document.getElementById('notificationList').innerHTML = window.saNotifications.slice(0, 10).map(function(item) {
                var icon = item.type === 'expiry' ? 'fa-clock-o' : 'fa-file-text-o';
                return '<button type="button" class="sa-notification-item ' + (item.is_read ? 'is-read' : 'is-unread') + '" onclick="openLogNotification(\'' + escapeLogHtml(item.key) + '\',\'' + escapeLogHtml(item.href) + '\')">' +
                    '<span class="sa-notification-icon ' + escapeLogHtml(item.type) + '"><i class="fa ' + icon + '"></i></span>' +
                    '<span class="sa-notification-copy"><span class="sa-notification-title">' + escapeLogHtml(item.title) + '</span><span class="sa-notification-message">' + escapeLogHtml(item.message) + '</span><span class="sa-notification-time">' + escapeLogHtml(item.created_at) + '</span></span><span class="sa-read-indicator"></span></button>';
            }).join('');
        });
}

function openLogNotification(key, href) {
    var item = (window.saNotifications || []).find(function(notification) { return notification.key === key; });
    if (!item || item.is_read) {
        window.location.href = 'superadmin_dashboard.php' + href;
        return;
    }
    var formData = new FormData();
    formData.append('action', 'mark_notification_read');
    formData.append('notification_key', key);
    fetch('superadmin_ajax.php', { method: 'POST', body: formData }).finally(function() {
        window.location.href = 'superadmin_dashboard.php' + href;
    });
}

function markAllNotificationsRead(event) {
    event.preventDefault();
    event.stopPropagation();
    var formData = new FormData();
    formData.append('action', 'mark_all_notifications_read');
    fetch('superadmin_ajax.php', { method: 'POST', body: formData }).then(fetchNotifications);
}

function exportLogsCsv() {
    var rows = Array.from(document.querySelectorAll('#logsTable tr')).filter(function(row) { return !row.hidden; });
    var csv = rows.map(function(row) {
        return Array.from(row.querySelectorAll('th,td')).map(function(cell) {
            var value = cell.innerText.trim();
            if (/^[=+\-@]/.test(value)) value = "'" + value;
            return '"' + value.replace(/"/g, '""') + '"';
        }).join(',');
    }).join('\r\n');
    var link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' }));
    link.download = 'log-aktiviti-' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
    URL.revokeObjectURL(link.href);
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('logSearch').addEventListener('input', function() {
        var term = this.value.trim().toLowerCase();
        document.querySelectorAll('#logsTable tbody tr').forEach(function(row) {
            row.hidden = term !== '' && (row.dataset.logSearch || '').indexOf(term) === -1;
        });
    });
});
</script>
</body>
</html>
