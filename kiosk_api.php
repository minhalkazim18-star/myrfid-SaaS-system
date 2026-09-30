<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';
app_start_session();
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');

function kiosk_new_secret(): string
{
    return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
}

if ($action === 'activate') {
    require_active_roles($conn, ['admin', 'gpk', 'guru'], true);
    require_csrf(true);

    $schoolId = current_school_id();
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $deviceId = 'pwa-' . strtolower(bin2hex(random_bytes(6)));
    $secret = kiosk_new_secret();
    $secretHash = hash('sha256', $secret);
    $label = trim((string)($_POST['label'] ?? 'Terminal PWA'));
    if ($label === '') $label = 'Terminal PWA';
    $label = mb_substr($label, 0, 100);

    $stmt = $conn->prepare('INSERT INTO rfid_devices (school_id, device_id, label, api_key_hash, created_by) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('isssi', $schoolId, $deviceId, $label, $secretHash, $userId);
    $stmt->execute();
    $stmt->close();

    json_response([
        'status' => 'success',
        'school_id' => $schoolId,
        'device_id' => $deviceId,
        'api_key' => $secret,
    ], 201);
}

if ($action !== 'stats') {
    json_response(['status' => 'error', 'msg' => 'Tindakan terminal tidak sah.'], 422);
}

$schoolId = (int)($_POST['school_id'] ?? $_GET['school_id'] ?? 0);
$deviceId = trim((string)($_SERVER['HTTP_X_RFID_DEVICE_ID'] ?? ''));
$apiKey = trim((string)($_SERVER['HTTP_X_RFID_API_KEY'] ?? ''));
if ($schoolId <= 0 || $apiKey === '' || !preg_match('/^[A-Za-z0-9._-]{3,64}$/', $deviceId)) {
    json_response(['status' => 'error', 'msg' => 'Terminal belum diaktifkan.'], 401);
}

$apiKeyHash = hash('sha256', $apiKey);
$stmt = $conn->prepare("SELECT d.id, s.nama_sekolah, s.kod_sekolah, s.logo FROM rfid_devices d JOIN sekolah s ON s.id = d.school_id WHERE d.school_id = ? AND d.device_id = ? AND d.api_key_hash = ? AND d.is_active = 1 AND s.status = 'aktif' AND (s.tarikh_luput IS NULL OR DATE(s.tarikh_luput) >= CURDATE()) LIMIT 1");
$stmt->bind_param('iss', $schoolId, $deviceId, $apiKeyHash);
$stmt->execute();
$terminal = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$terminal) {
    json_response(['status' => 'error', 'msg' => 'Akses terminal telah tamat atau dibatalkan.'], 401);
}

$schoolName = (string)$terminal['nama_sekolah'];
$schoolCode = (string)$terminal['kod_sekolah'];
$schoolLogo = (string)($terminal['logo'] ?? '');
$settings = ['waktu_mula' => '07:00', 'waktu_tamat' => '14:00'];
$stmt = $conn->prepare("SELECT kunci, nilai FROM tetapan WHERE school_id = ? AND kunci IN ('nama_sekolah', 'logo_sekolah', 'waktu_mula', 'waktu_tamat')");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['kunci'] === 'nama_sekolah' && $row['nilai'] !== '') $schoolName = (string)$row['nilai'];
    if ($row['kunci'] === 'logo_sekolah' && $row['nilai'] !== '') $schoolLogo = (string)$row['nilai'];
    if ($row['kunci'] === 'waktu_mula') $settings['waktu_mula'] = (string)$row['nilai'];
    if ($row['kunci'] === 'waktu_tamat') $settings['waktu_tamat'] = (string)$row['nilai'];
}
$stmt->close();

if ($schoolLogo === '' || !is_file(__DIR__ . '/' . ltrim($schoolLogo, '/'))) {
    $schoolLogo = 'images/logo_drs.png';
}

$today = date('Y-m-d');
$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM pelajar WHERE school_id = ? AND status = 1');
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$eligible = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM transaksi_rmt WHERE school_id = ? AND tarikh = ?');
$stmt->bind_param('is', $schoolId, $today);
$stmt->execute();
$attended = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

json_response([
    'status' => 'success',
    'sekolah' => [
        'id' => $schoolId,
        'nama' => $schoolName,
        'kod' => $schoolCode,
        'logo' => $schoolLogo,
        'waktu_operasi' => $settings['waktu_mula'] . ' - ' . $settings['waktu_tamat'],
    ],
    'statistik' => [
        'layak' => $eligible,
        'hadir' => $attended,
        'baki' => max(0, $eligible - $attended),
    ],
]);

