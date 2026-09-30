<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';
app_start_session();

$googleConfig = google_oauth_configuration();
if (!google_oauth_is_configured()) {
    error_log('Google OAuth environment variables are not configured.');
    $_SESSION['login_error'] = 'Log masuk Google belum tersedia. Sila gunakan nama pengguna dan kata laluan.';
    header('Location: index.php');
    exit;
}
$clientId = $googleConfig['client_id'];
$clientSecret = $googleConfig['client_secret'];
$redirectUri = $googleConfig['redirect_uri'];

$client = new Google_Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope('email');
$client->addScope('profile');
$client->setState(csrf_token());

if (!isset($_GET['code'])) {
    if (in_array(($_GET['next'] ?? ''), ['kiosk','renewal'], true)) {
        $_SESSION['oauth_next'] = $_GET['next'];
    } else {
        unset($_SESSION['oauth_next']);
    }
    header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
    exit;
}

$state = (string)($_GET['state'] ?? '');
if ($state === '' || !hash_equals(csrf_token(), $state)) {
    popup_and_redirect('Permintaan OAuth tidak sah atau telah tamat.', 'index.php', 'error');
}

$token = $client->fetchAccessTokenWithAuthCode((string)$_GET['code']);
if (isset($token['error']) || empty($token['access_token'])) {
    error_log('Google OAuth token exchange failed: ' . json_encode($token));
    popup_and_redirect('Log masuk Google gagal. Sila cuba lagi.', 'index.php', 'error');
}
$client->setAccessToken($token['access_token']);

$oauth = new Google_Service_Oauth2($client);
$account = $oauth->userinfo->get();
$email = strtolower(trim((string)$account->email));
$picture = trim((string)$account->picture);

$stmt = $conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 2');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    $stmt->close();
    popup_and_redirect('Akaun Google ini tidak dipautkan kepada satu akaun sistem yang sah.', 'index.php', 'info');
}
$user = $result->fetch_assoc();
$stmt->close();

$renewalOnly = false;
if ($user['role'] !== 'superadmin') {
    $schoolId = (int)$user['school_id'];
    $stmt = $conn->prepare('SELECT status, nama_sekolah, tarikh_luput FROM sekolah WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $school = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $expired = !empty($school['tarikh_luput']) && strtotime($school['tarikh_luput']) < strtotime(date('Y-m-d'));
    if (!$school || $schoolId < 1 || $school['status'] !== 'aktif' || ($expired && $user['role'] !== 'admin')) {
        popup_and_redirect('Akaun sekolah tidak aktif atau langganan telah tamat.', 'index.php', 'error');
    }
    $renewalOnly = $expired;
}

if ($picture !== '') {
    $stmt = $conn->prepare('UPDATE users SET profile_pic = ? WHERE id = ?');
    $userId = (int)$user['id'];
    $stmt->bind_param('si', $picture, $userId);
    $stmt->execute();
    $stmt->close();
}

session_regenerate_id(true);
unset($_SESSION['renewal_only']);
if ($renewalOnly) $_SESSION['renewal_only'] = true;
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['nama_penuh'] = $user['nama_penuh'];
$_SESSION['email'] = $user['email'] ?? '';
$_SESSION['school_id'] = (int)($user['school_id'] ?? 0);
$_SESSION['auth_version'] = (int)($user['auth_version'] ?? 1);
$_SESSION['last_activity'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
catat_log($conn, (int)$user['id'], $user['nama_penuh'], 'Log masuk melalui Google');

$oauthNext = (string)($_SESSION['oauth_next'] ?? '');
unset($_SESSION['oauth_next']);
if ($user['role'] === 'superadmin') {
    header('Location: superadmin_dashboard.php');
} elseif ($renewalOnly || $oauthNext === 'renewal') {
    header('Location: renewal.php');
} elseif ($oauthNext === 'kiosk') {
    header('Location: kiosk.php?terminal=1');
} else {
    header('Location: dashboard.php');
}
exit;