<?php
require_once __DIR__ . '/security.php';
require_login(false);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
require_once __DIR__ . '/db_connect.php';
require_active_account($conn, false);
$currentScript = basename((string)($_SERVER['PHP_SELF'] ?? ''));
if (($_SESSION['role'] ?? '') === 'superadmin' && !in_array($currentScript, ['superadmin_dashboard.php','superadmin_ajax.php','superadmin_logs.php','superadmin_tetapan.php','superadmin_users.php','superadmin_renewals.php','renewal_download.php','impersonate.php','logout.php'],true)) {
    header('Location: superadmin_dashboard.php'); exit;
}
csrf_token();