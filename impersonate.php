<?php
require_once __DIR__ . '/security.php';
app_start_session();
require_once __DIR__ . '/db_connect.php';
require_active_roles($conn, ['superadmin'], true);
require_csrf(true);

$schoolId = (int)($_POST['school_id'] ?? 0);
if ($schoolId <= 0) {
    json_response(['status' => 'error', 'msg' => 'ID sekolah tidak sah.'], 422);
}

$stmt = $conn->prepare("SELECT s.id, s.nama_sekolah, s.logo, u.id AS admin_id, u.username, u.nama_penuh, u.email, u.auth_version
                        FROM sekolah s
                        JOIN users u ON u.school_id = s.id AND u.role = 'admin'
                        WHERE s.id = ? AND s.status = 'aktif'
                        ORDER BY u.id ASC LIMIT 1");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$target = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$target) {
    json_response(['status' => 'error', 'msg' => 'Sekolah aktif atau akaun admin tidak dijumpai.'], 404);
}

$_SESSION['superadmin_impersonating'] = true;
$_SESSION['original_role'] = $_SESSION['role'];
$_SESSION['original_user_id'] = $_SESSION['user_id'];
$_SESSION['original_username'] = $_SESSION['username'];
$_SESSION['original_nama_penuh'] = $_SESSION['nama_penuh'];
$_SESSION['original_email'] = $_SESSION['email'] ?? '';
$_SESSION['original_school_id'] = $_SESSION['school_id'] ?? 0;
$_SESSION['original_auth_version'] = $_SESSION['auth_version'] ?? 1;

record_superadmin_audit($conn, 'IMPERSONATE_START', "Memulakan akses bantuan sebagai pentadbir sekolah {$target['nama_sekolah']}");

session_regenerate_id(true);
$_SESSION['school_id'] = (int)$target['id'];
$_SESSION['nama_sekolah'] = $target['nama_sekolah'];
$_SESSION['logo_sekolah'] = $target['logo'];
$_SESSION['role'] = 'admin';
$_SESSION['user_id'] = (int)$target['admin_id'];
$_SESSION['username'] = $target['username'];
$_SESSION['nama_penuh'] = $target['nama_penuh'];
$_SESSION['email'] = $target['email'] ?? '';
$_SESSION['auth_version'] = (int)$target['auth_version'];
$_SESSION['last_activity'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

json_response(['status' => 'success', 'redirect' => 'dashboard.php']);
