<?php
require_once __DIR__ . '/security.php';
app_start_session();
include 'db_connect.php';

require_active_roles($conn, ['superadmin'], false);

include 'header.php';
?>
      </div>
   </div>

   <style>
   body {
       background: #f8fafc !important;
       font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
       margin: 0;
       padding: 0;
   }
   .superadmin-portal-wrapper {
       width: 100%;
       min-height: calc(100vh - 60px);
       display: flex;
       flex-direction: column;
       background: #f8fafc;
   }
   .superadmin-header-bar {
       background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
       color: #ffffff;
       padding: 30px 40px;
       border-bottom: 1px solid rgba(255, 255, 255, 0.1);
       box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
   }
   .sa-table-card {
       background: #ffffff;
       border-radius: 20px;
       box-shadow: 0 10px 30px rgba(0,0,0,0.04);
       border: 1px solid rgba(0,0,0,0.04);
       margin: -30px 40px 40px 40px; /* pull up over the header slightly */
       padding: 40px;
       position: relative;
       z-index: 10;
   }
   .sa-table-title {
       font-size: 20px;
       font-weight: 700;
       color: #1e293b;
       margin-bottom: 25px;
       display: flex;
       align-items: center;
       gap: 10px;
   }
   .form-label {
       font-size: 14px;
       font-weight: 600;
       color: #475569;
       margin-bottom: 8px;
   }
   .form-control, .form-select {
       border-radius: 10px;
       border: 1px solid #cbd5e1;
       padding: 12px 16px;
       font-size: 14px;
       transition: all 0.2s;
       box-shadow: none !important;
   }
   .form-control:focus, .form-select:focus {
       border-color: #6366f1;
       box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1) !important;
   }
   .input-group .btn {
       border-radius: 0 10px 10px 0;
       border: 1px solid #cbd5e1;
       border-left: none;
       background: #f8fafc;
       color: #64748b;
       padding: 0 20px;
   }
   .input-group .form-control {
       border-radius: 10px 0 0 10px;
   }
   .btn-primary {
       background: linear-gradient(135deg, #2563eb, #1d4ed8);
       border: none;
       border-radius: 10px;
       padding: 12px 28px;
       font-weight: 600;
       box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
       transition: all 0.3s;
   }
   .btn-primary:hover {
       transform: translateY(-2px);
       box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
   }
   /* Toast */
   .sa-toast-container { position: fixed; top: 28px; right: 28px; z-index: 99999; pointer-events: none; }
   .sa-toast { background: #ffffff; border-radius: 14px; padding: 16px 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-left: 5px solid #2563eb; display: flex; align-items: center; gap: 14px; transform: translateY(-20px); opacity: 0; visibility: hidden; transition: all 0.35s; pointer-events: none; }
   .sa-toast.show { transform: translateY(0); opacity: 1; visibility: visible; pointer-events: auto; }
   .sa-toast.success { border-left-color: #10b981; }
   .sa-toast.error { border-left-color: #ef4444; }
   .sa-toast-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
   .sa-toast.success .sa-toast-icon { background: #d1fae5; color: #059669; }
   .sa-toast.error .sa-toast-icon { background: #fee2e2; color: #dc2626; }
   </style>
   <link rel="stylesheet" href="css/superadmin-dashboard.css?v=<?php echo (int)@filemtime(__DIR__ . '/css/superadmin-dashboard.css'); ?>" />

   <div class="sa-toast-container" id="saToastContainer">
       <div class="sa-toast" id="saToast">
           <div class="sa-toast-icon" id="saToastIcon"><i class="fa fa-info-circle"></i></div>
           <div class="sa-toast-body">
               <div class="sa-toast-title" id="saToastTitle">Notifikasi</div>
               <div class="sa-toast-msg" id="saToastMsg">Mesej di sini</div>
           </div>
           <button class="sa-toast-close" onclick="document.getElementById('saToast').classList.remove('show');" style="background:none; border:none; font-size:20px; color:#94a3b8; cursor:pointer;">&times;</button>
       </div>
   </div>

   <div class="superadmin-portal-wrapper">
       <aside class="sa-sidebar">
           <a class="sa-brand" href="superadmin_dashboard.php" aria-label="Dashboard SuperAdmin"><span class="sa-brand-mark"><i class="fa fa-shield"></i></span><span><small>DRS</small> SuperAdmin</span></a>
           <nav class="sa-side-nav" aria-label="Navigasi SuperAdmin">
               <span class="sa-side-label">Workspace</span>
               <a href="superadmin_dashboard.php"><i class="fa fa-th-large"></i><span>Dashboard</span></a>
               <a href="superadmin_logs.php"><i class="fa fa-history"></i><span>Log Aktiviti</span></a>
               <a class="active" href="superadmin_tetapan.php"><i class="fa fa-sliders"></i><span>Tetapan Sistem</span></a>
           </nav>
           <div class="sa-side-status"><span class="sa-live-dot"></span><div><strong>Sistem beroperasi</strong><small>Perkhidmatan platform aktif</small></div></div>
           <form method="post" action="logout.php" class="sa-logout-form"><input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>"><button type="submit"><i class="fa fa-sign-out"></i><span>Log Keluar</span></button></form>
       </aside>

       <div class="sa-workspace">
           <header class="sa-workspace-bar">
               <div class="sa-workspace-context"><span>Platform</span><i class="fa fa-angle-right"></i><strong>Tetapan Sistem</strong></div>
               <div class="sa-workspace-actions">
                   <div class="dropdown">
                       <button type="button" class="sa-nav-icon sa-notification-button dropdown-toggle" data-toggle="dropdown" aria-label="Notifikasi sistem"><svg class="sa-button-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><span class="sa-nav-button-label">Notifikasi</span><span id="bellBadge" class="sa-notification-count">0</span></button>
                       <div class="dropdown-menu dropdown-menu-right sa-notification-menu">
                           <div class="dropdown-header"><div><strong>Notifikasi</strong><small id="notificationSummary">Memuatkan...</small></div><button type="button" id="markAllNotifications" onclick="markAllSettingsNotificationsRead(event)" disabled>Tanda semua dibaca</button></div>
                           <div class="sa-notification-list" id="notificationList" aria-live="polite"><div class="text-center text-muted py-3">Memuatkan...</div></div>
                       </div>
                   </div>
                   <span class="sa-admin-chip"><span>SA</span><strong>SuperAdmin</strong></span>
                   <a class="sa-btn sa-btn-secondary" href="superadmin_dashboard.php"><i class="fa fa-arrow-left"></i> Dashboard</a>
               </div>
           </header>

           <main class="superadmin-main-body sa-settings-main">
               <div class="sa-page-heading"><div><span class="sa-eyebrow">Konfigurasi platform</span><h1>Tetapan sistem</h1><p>Urus sambungan e-mel automatik untuk operasi SuperAdmin.</p></div></div>
               <div class="sa-settings-grid">
                   <section class="sa-panel sa-settings-panel">
                       <div class="sa-panel-header sa-settings-header"><div><span class="sa-section-kicker">Perkhidmatan e-mel</span><h2 class="sa-table-title">Konfigurasi SMTP</h2></div><span class="sa-config-badge"><i class="fa fa-lock"></i> Rahsia disulitkan</span></div>
                       <div class="sa-settings-body">
                           <div class="sa-settings-section">
                               <div class="sa-settings-section-title"><span>01</span><div><strong>Sambungan pelayan</strong><small>Alamat dan kaedah sambungan SMTP.</small></div></div>
                               <div class="sa-form-grid sa-form-grid-server">
                                   <div class="sa-field sa-field-host"><label for="smtp_host">SMTP host</label><input type="text" class="form-control" id="smtp_host" placeholder="smtp.gmail.com" /></div>
                                   <div class="sa-field"><label for="smtp_port">Port</label><input type="number" class="form-control" id="smtp_port" placeholder="587" min="1" max="65535" /></div>
                                   <div class="sa-field"><label for="smtp_encryption">Enkripsi</label><select class="form-control" id="smtp_encryption"><option value="tls">TLS (Disyorkan)</option><option value="ssl">SSL</option><option value="none">Tiada</option></select></div>
                               </div>
                           </div>
                           <div class="sa-settings-section">
                               <div class="sa-settings-section-title"><span>02</span><div><strong>Kelayakan akaun</strong><small>Akaun yang dibenarkan menghantar e-mel.</small></div></div>
                               <div class="sa-form-grid">
                                   <div class="sa-field"><label for="smtp_username">Username / e-mel SMTP</label><input type="text" class="form-control" id="smtp_username" placeholder="nama@gmail.com" autocomplete="username" /></div>
                                   <div class="sa-field"><label for="smtp_password">Password SMTP / App Password</label><div class="input-group"><input type="password" class="form-control" id="smtp_password" placeholder="Masukkan App Password" autocomplete="new-password" /><div class="input-group-append"><button class="btn btn-outline-secondary sa-password-toggle" type="button" onclick="toggleSmtpPass()" aria-label="Lihat kata laluan"><i class="fa fa-eye" id="smtpPassEyeIcon"></i><span id="smtpPassToggleLabel">Lihat</span></button></div></div></div>
                               </div>
                           </div>
                           <div class="sa-settings-section">
                               <div class="sa-settings-section-title"><span>03</span><div><strong>Identiti penghantar</strong><small>Nama dan alamat yang dilihat oleh penerima.</small></div></div>
                               <div class="sa-form-grid">
                                   <div class="sa-field"><label for="smtp_from_email">E-mel penghantar</label><input type="email" class="form-control" id="smtp_from_email" placeholder="admin@myrfid.edu.my" /></div>
                                   <div class="sa-field"><label for="smtp_from_name">Nama penghantar</label><input type="text" class="form-control" id="smtp_from_name" placeholder="SaaS MyRFID Admin" /></div>
                               </div>
                           </div>
                       </div>
                       <div class="sa-settings-footer"><span><i class="fa fa-info-circle"></i> Perubahan digunakan pada e-mel automatik seterusnya.</span><button class="btn-sa-register" type="button" onclick="saveSmtpSettings()" id="btnSaveSmtp"><i class="fa fa-save"></i> Simpan Tetapan</button></div>
                   </section>

                   <aside class="sa-settings-help">
                       <div class="sa-panel sa-help-card"><span class="sa-help-icon"><i class="fa fa-google"></i></span><h3>Menggunakan Gmail?</h3><p>Gunakan App Password 16 aksara. Kata laluan akaun Google biasa tidak akan diterima.</p><ol><li>Aktifkan pengesahan 2 langkah.</li><li>Jana App Password untuk Mail.</li><li>Gunakan port 587 dan TLS.</li></ol></div>
                       <div class="sa-panel sa-help-card sa-help-security"><span class="sa-help-icon"><i class="fa fa-shield"></i></span><h3>Keselamatan</h3><p>Password SMTP disimpan dalam bentuk disulitkan dan tidak dipaparkan semula selepas disimpan.</p></div>
                   </aside>
               </div>
           </main>
       </div>
   </div>

   <script src="js/jquery.min.js"></script>
   <script>
   function showToast(title, msg, type) {
       var toast = document.getElementById('saToast');
       var icon = document.getElementById('saToastIcon');
       document.getElementById('saToastTitle').innerText = title;
       document.getElementById('saToastMsg').innerText = msg;
       
       toast.className = 'sa-toast show ' + type;
       if(type === 'success') { icon.innerHTML = '<i class="fa fa-check-circle"></i>'; }
       else { icon.innerHTML = '<i class="fa fa-exclamation-circle"></i>'; }
       
       setTimeout(function() { toast.classList.remove('show'); }, 4500);
   }

   function escapeSettingsHtml(value) {
       var div = document.createElement('div');
       div.textContent = String(value == null ? '' : value);
       return div.innerHTML;
   }

   function loadSettingsNotifications() {
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
                   return '<button type="button" class="sa-notification-item ' + (item.is_read ? 'is-read' : 'is-unread') + '" onclick="openSettingsNotification(\'' + escapeSettingsHtml(item.key) + '\',\'' + escapeSettingsHtml(item.href) + '\')">' +
                       '<span class="sa-notification-icon ' + escapeSettingsHtml(item.type) + '"><i class="fa ' + icon + '"></i></span><span class="sa-notification-copy"><span class="sa-notification-title">' + escapeSettingsHtml(item.title) + '</span><span class="sa-notification-message">' + escapeSettingsHtml(item.message) + '</span><span class="sa-notification-time">' + escapeSettingsHtml(item.created_at) + '</span></span><span class="sa-read-indicator"></span></button>';
               }).join('');
           });
   }

   function openSettingsNotification(key, href) {
       var item = (window.saNotifications || []).find(function(notification) { return notification.key === key; });
       if (!item || item.is_read) {
           window.location.href = 'superadmin_dashboard.php' + href;
           return;
       }
       var fd = new FormData();
       fd.append('action', 'mark_notification_read');
       fd.append('notification_key', key);
       fetch('superadmin_ajax.php', { method: 'POST', body: fd }).finally(function() { window.location.href = 'superadmin_dashboard.php' + href; });
   }

   function markAllSettingsNotificationsRead(event) {
       event.preventDefault();
       event.stopPropagation();
       var fd = new FormData();
       fd.append('action', 'mark_all_notifications_read');
       fetch('superadmin_ajax.php', { method: 'POST', body: fd }).then(loadSettingsNotifications);
   }

   function toggleSmtpPass() {
       var input = document.getElementById('smtp_password');
       var icon = document.getElementById('smtpPassEyeIcon');
       var label = document.getElementById('smtpPassToggleLabel');
       if(input.type === 'password') {
           input.type = 'text';
           icon.className = 'fa fa-eye-slash';
           label.textContent = 'Sembunyi';
       } else {
           input.type = 'password';
           icon.className = 'fa fa-eye';
           label.textContent = 'Lihat';
       }
   }

   function loadSmtpSettings() {
       fetch('superadmin_ajax.php?action=get_smtp')
           .then(r => r.json())
           .then(data => {
               if (data.status === 'success' && data.smtp) {
                   document.getElementById('smtp_host').value = data.smtp.smtp_host || '';
                   document.getElementById('smtp_port').value = data.smtp.smtp_port || 587;
                   document.getElementById('smtp_encryption').value = data.smtp.smtp_encryption || 'tls';
                   document.getElementById('smtp_username').value = data.smtp.smtp_username || '';
                   document.getElementById('smtp_password').value = data.smtp.smtp_password || '';
                   document.getElementById('smtp_from_email').value = data.smtp.smtp_from_email || '';
                   document.getElementById('smtp_from_name').value = data.smtp.smtp_from_name || '';
               }
           });
   }

   function saveSmtpSettings() {
       var btn = document.getElementById('btnSaveSmtp');
       var origHtml = btn.innerHTML;
       btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i> Menyimpan...';
       btn.disabled = true;

       var fd = new FormData();
       fd.append('action', 'save_smtp');
       fd.append('smtp_host', document.getElementById('smtp_host').value);
       fd.append('smtp_port', document.getElementById('smtp_port').value);
       fd.append('smtp_encryption', document.getElementById('smtp_encryption').value);
       fd.append('smtp_username', document.getElementById('smtp_username').value);
       fd.append('smtp_password', document.getElementById('smtp_password').value);
       fd.append('smtp_from_email', document.getElementById('smtp_from_email').value);
       fd.append('smtp_from_name', document.getElementById('smtp_from_name').value);

       fetch('superadmin_ajax.php', { method: 'POST', body: fd })
           .then(r => r.json())
           .then(data => {
               btn.innerHTML = origHtml;
               btn.disabled = false;
               if(data.status === 'success') {
                   showToast('Berjaya', data.msg, 'success');
                   loadSmtpSettings(); // reload to get masked password
               } else {
                   showToast('Ralat', data.msg, 'error');
               }
           })
           .catch(function() {
               btn.innerHTML = origHtml;
               btn.disabled = false;
               showToast('Ralat', 'Tetapan tidak dapat disimpan. Cuba lagi.', 'error');
           });
   }

   document.addEventListener('DOMContentLoaded', function() {
       loadSmtpSettings();
       loadSettingsNotifications();
       window.setInterval(loadSettingsNotifications, 60000);
   });
   </script>
</body>
</html>
