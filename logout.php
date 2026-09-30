<?php
require_once __DIR__ . '/security.php';
app_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo 'Kaedah tidak dibenarkan.';
    exit;
}
require_csrf(false);

// Semak jika pengguna sedang impersonate (Super Admin menyamar sebagai Admin Sekolah)
if (isset($_SESSION['superadmin_impersonating']) && $_SESSION['superadmin_impersonating'] === true) {
    require_once __DIR__ . '/db_connect.php';
    record_superadmin_audit(
        $conn,
        'IMPERSONATE_END',
        'Menamatkan akses bantuan sebagai pentadbir sekolah ID ' . (int)($_SESSION['school_id'] ?? 0),
        (int)($_SESSION['original_user_id'] ?? 0),
        (string)($_SESSION['original_nama_penuh'] ?? 'Super Admin')
    );
    // Pulihkan peranan dan identiti asal Super Admin
    $_SESSION['role'] = $_SESSION['original_role'] ?? 'superadmin';
    $_SESSION['user_id'] = $_SESSION['original_user_id'] ?? 1;
    $_SESSION['username'] = $_SESSION['original_username'] ?? 'superadmin';
    $_SESSION['nama_penuh'] = $_SESSION['original_nama_penuh'] ?? 'Super Admin';
    $_SESSION['email'] = $_SESSION['original_email'] ?? '';
    $_SESSION['auth_version'] = (int)($_SESSION['original_auth_version'] ?? 1);
    $_SESSION['last_activity'] = time();
    
    unset($_SESSION['superadmin_impersonating']);
    unset($_SESSION['original_role']);
    unset($_SESSION['original_user_id']);
    unset($_SESSION['original_username']);
    unset($_SESSION['original_nama_penuh']);
    unset($_SESSION['original_email']);
    unset($_SESSION['original_auth_version']);
    
    // Reset maklumat sekolah ke default (boleh diabaikan atau diset ke 1)
    $_SESSION['school_id'] = (int)($_SESSION['original_school_id'] ?? 0);
    unset($_SESSION['original_school_id']);
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Kembali ke Dashboard Super Admin
    header("Location: superadmin_dashboard.php");
    exit();
}

// Kosongkan data sesi dan padam cookie dengan atribut yang sama seperti ketika
// ia dicipta. session_destroy() sahaja tidak membuang cookie daripada browser.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'] ?: '/',
        'domain' => $params['domain'] ?? '',
        'secure' => (bool)($params['secure'] ?? false),
        'httponly' => (bool)($params['httponly'] ?? true),
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}
session_destroy();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: index.php?logged_out=1', true, 303);
exit();
?>
