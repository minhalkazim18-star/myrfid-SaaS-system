<?php
/**
 * UNIVERSAL API LISTENER (api_listener.php)
 * --------------------------------------------------------------------------
 * Satu endpoint universal untuk menyambut data imbasan kad RFID daripada
 * pelbagai jenis peranti dan model pengimbas komersial & IoT mandiri.
 * Semua transaksi imbasan mesti menggunakan POST dan pengesahan terminal:
 *
 * 1. USB RFID Reader / Web Scan (POST rfid_uid)
 * 2. WiFi HTTP POST Reader (POST uid / rfid / card_no / id_kad)
 * 3. Modul IoT ESP32 / ESP8266 / M5Stack (JSON Payload: {"card_id": "...", ...})
 * 4. Payload ATTLOG melalui POST daripada gateway yang membekalkan header API.
 * --------------------------------------------------------------------------
 */

require_once __DIR__ . '/security.php';
app_start_session();
include 'db_connect.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

$target_sch_id = (int)($_POST['sch_id'] ?? $_POST['school_id'] ?? $_GET['sch_id'] ?? $_GET['school_id'] ?? $_SESSION['school_id'] ?? 0);
$providedCsrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '');
$sessionAllowed = isset($_SESSION['user_id'])
    && $target_sch_id > 0
    && $target_sch_id === (int)($_SESSION['school_id'] ?? 0)
    && $providedCsrf !== ''
    && hash_equals((string)($_SESSION['csrf_token'] ?? ''), $providedCsrf);

if ($sessionAllowed) {
    $sessionUserId = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT u.id FROM users u JOIN sekolah s ON s.id = u.school_id WHERE u.id = ? AND u.school_id = ? AND u.role IN ('admin', 'gpk', 'guru') AND s.status = 'aktif' AND (s.tarikh_luput IS NULL OR DATE(s.tarikh_luput) >= CURDATE()) LIMIT 1");
    $stmt->bind_param('ii', $sessionUserId, $target_sch_id);
    $stmt->execute();
    $sessionAllowed = $stmt->get_result()->num_rows === 1;
    $stmt->close();
}

$providedApiKey = trim((string)($_SERVER['HTTP_X_RFID_API_KEY'] ?? ''));
$providedDeviceId = trim((string)($_SERVER['HTTP_X_RFID_DEVICE_ID'] ?? $_POST['device_id'] ?? $_GET['device_id'] ?? $_GET['SN'] ?? ''));
$deviceAllowed = false;
$authenticatedDeviceRowId = 0;

if ($target_sch_id > 0 && $providedApiKey !== '' && preg_match('/^[A-Za-z0-9._-]{3,64}$/', $providedDeviceId)) {
    try {
        $apiKeyHash = hash('sha256', $providedApiKey);
        $stmt = $conn->prepare("SELECT d.id FROM rfid_devices d JOIN sekolah s ON s.id = d.school_id WHERE d.school_id = ? AND d.device_id = ? AND d.api_key_hash = ? AND d.is_active = 1 AND s.status = 'aktif' AND (s.tarikh_luput IS NULL OR DATE(s.tarikh_luput) >= CURDATE()) LIMIT 1");
        $stmt->bind_param('iss', $target_sch_id, $providedDeviceId, $apiKeyHash);
        $stmt->execute();
        $device = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($device) {
            $deviceAllowed = true;
            $authenticatedDeviceRowId = (int)$device['id'];
        }
    } catch (Throwable $e) {
        error_log('RFID device authentication failed: ' . $e->getMessage());
    }
}

// Temporary migration path. It is disabled by default and, when enabled,
// remains bound to exactly one school instead of granting cross-tenant access.
if (!$deviceAllowed && getenv('RFID_ALLOW_LEGACY_GLOBAL_KEY') === '1') {
    $legacyKey = trim((string)getenv('RFID_API_KEY'));
    $legacySchoolId = (int)getenv('RFID_LEGACY_SCHOOL_ID');
    $deviceAllowed = $legacyKey !== '' && $legacySchoolId > 0 && $target_sch_id === $legacySchoolId
        && $providedApiKey !== '' && hash_equals($legacyKey, $providedApiKey);
    if ($deviceAllowed) $providedDeviceId = 'legacy-' . $legacySchoolId;
}

if (!$sessionAllowed && !$deviceAllowed) {
    json_response(['status' => 'error', 'msg' => 'Pengesahan terminal RFID gagal.'], 401);
}
if ($target_sch_id <= 0) {
    json_response(['status' => 'error', 'msg' => 'school_id wajib dibekalkan oleh terminal.'], 422);
}

if ($deviceAllowed) {
    $deviceRateKey = 'rfid:' . $target_sch_id . ':' . $providedDeviceId;
    if (login_rate_limited($conn, $deviceRateKey, 300, 1)) {
        json_response(['status' => 'error', 'msg' => 'Had permintaan terminal telah dicapai.'], 429);
    }
    record_login_failure($conn, $deviceRateKey, 1);
    if ($authenticatedDeviceRowId > 0) {
        $stmt = $conn->prepare('UPDATE rfid_devices SET last_used_at = NOW(), last_ip_hash = ? WHERE id = ?');
        $ipHash = client_ip_hash();
        $stmt->bind_param('si', $ipHash, $authenticatedDeviceRowId);
        $stmt->execute();
        $stmt->close();
    }
}

// --------------------------------------------------------------------------
// 1. HANDSHAKE GATEWAY ATTLOG YANG TELAH DISAHKAN
// --------------------------------------------------------------------------
// Gateway boleh membuat request awal options/SN, tetapi masih wajib menghantar
// school_id dan kunci API melalui header seperti permintaan terminal lain.
if (isset($_GET['options']) || (isset($_GET['SN']) && !isset($_GET['table']) && empty($_POST))) {
    header('Content-Type: text/plain');
    echo "GET OPTION FROM: " . htmlspecialchars($_GET['SN'] ?? 'UNKNOWN') . "\r\n";
    echo "Stamp=85238234\r\n";
    echo "OpStamp=85238234\r\n";
    echo "ErrorDelay=60\r\n";
    echo "Delay=30\r\n";
    echo "ResLogDay=18250\r\n";
    echo "TransTimes=00:00;14:05\r\n";
    echo "TransInterval=1\r\n";
    echo "TransFlag=1111000000\r\n";
    echo "OK";
    exit;
}


if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    json_response(['status' => 'error', 'msg' => 'Imbasan RFID mesti dihantar menggunakan POST.'], 405);
}

// --------------------------------------------------------------------------
// 2. EXTRACTION: CARI NOMBOR KAD (rfid_uid) DARI PELBAGAI FORMAT PAYLOAD
// --------------------------------------------------------------------------
$rfid_uid = "";
$is_zkteco = false;

// A. Semak POST biasa
if (!empty($_POST['rfid_uid'])) {
    $rfid_uid = trim($_POST['rfid_uid']);
} elseif (!empty($_POST['uid'])) {
    $rfid_uid = trim($_POST['uid']);
} elseif (!empty($_POST['rfid'])) {
    $rfid_uid = trim($_POST['rfid']);
} elseif (!empty($_POST['card_no'])) {
    $rfid_uid = trim($_POST['card_no']);
} elseif (!empty($_POST['id_kad'])) {
    $rfid_uid = trim($_POST['id_kad']);
}

// B. Semak Raw Input (JSON atau payload ATTLOG)
if (empty($rfid_uid)) {
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        // Cuba parse sebagai JSON (Modul IoT ESP32 / JSON POST)
        $json = @json_decode($raw_input, true);
        if (is_array($json)) {
            if (!empty($json['card_id'])) $rfid_uid = trim($json['card_id']);
            elseif (!empty($json['uid'])) $rfid_uid = trim($json['uid']);
            elseif (!empty($json['rfid'])) $rfid_uid = trim($json['rfid']);
            elseif (!empty($json['rfid_uid'])) $rfid_uid = trim($json['rfid_uid']);
            elseif (!empty($json['id_kad'])) $rfid_uid = trim($json['id_kad']);
        } else {
            // Jika bukan JSON, semak jika ia format ZKTeco ATTLOG
            // Format log ZKTeco: <PIN/CardNo>\t<Timestamp>\t<Status>\t<VerifyType>
            if ((isset($_GET['table']) && $_GET['table'] === 'ATTLOG') || strpos($raw_input, "\t") !== false) {
                $is_zkteco = true;
                $lines = explode("\n", trim($raw_input));
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    $parts = preg_split('/\s+/', $line);
                    if (!empty($parts[0])) {
                        $rfid_uid = trim($parts[0]);
                        break; // Ambil kad pertama untuk diproses
                    }
                }
            }
        }
    }
}

if (strlen($rfid_uid) > 50 || !preg_match('/^[A-Za-z0-9:_-]+$/', $rfid_uid)) {
    json_response(['status' => 'error', 'msg' => 'Format nombor kad tidak sah.'], 422);
}

// Jika masih tiada nombor kad dikesan
if (empty($rfid_uid)) {
    if ($is_zkteco || isset($_GET['table'])) {
        header('Content-Type: text/plain');
        echo "OK";
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'msg' => 'Tiada data nombor kad (rfid_uid/uid/rfid) dikesan dalam request.']);
    exit;
}

// --------------------------------------------------------------------------
// 3. PROSES KAD TERHADAP PANGKALAN DATA (MULTI-TENANT & WAKTU OPERASI)
// --------------------------------------------------------------------------
$rfid_no_zero = ltrim($rfid_uid, '0');
$rfid_no_zero = $rfid_no_zero !== '' ? $rfid_no_zero : '0';

// Cari pelajar (Sokong carian padanan tepat atau tanpa leading zero)
$stmt = $conn->prepare('SELECT * FROM pelajar WHERE school_id = ? AND (rfid_uid = ? OR rfid_uid = ?) LIMIT 1');
$stmt->bind_param('iss', $target_sch_id, $rfid_uid, $rfid_no_zero);
$stmt->execute();
$query_pelajar = $stmt->get_result();

if (!$query_pelajar || mysqli_num_rows($query_pelajar) == 0) {
    if ($is_zkteco) { header('Content-Type: text/plain'); echo "OK"; exit; }
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'msg' => 'Kad Tidak Dikenali (' . htmlspecialchars($rfid_uid) . ')',
        'info_tambahan' => 'Sila daftarkan kad ini dalam pengurusan pelajar.'
    ]);
    $stmt->close();
    exit;
}

$student = mysqli_fetch_assoc($query_pelajar);
$stmt->close();
$stu_school_id = (int)($student['school_id'] ?? 1);
$nama = $student['nama_penuh'];
$kelas = $student['nama_kelas'];
$status_pelajar = $student['status'];
$tarikh_harini = date('Y-m-d');
$waktu_sekarang = date('H:i');
$waktu_insert = date('H:i:s');

// STRICT TENANT ISOLATION CHECK: Jika terminal berada pada sekolah tertentu ($target_sch_id > 0), kad mestilah milik sekolah tersebut!
if ($target_sch_id > 0 && $stu_school_id !== $target_sch_id) {
    if ($is_zkteco) { header('Content-Type: text/plain'); echo "OK"; exit; }
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'msg' => 'KAD BUKAN MILIK SEKOLAH INI!',
        'info_tambahan' => 'Pelajar ini didaftarkan di bawah sekolah lain. Terminal ini hanya boleh merekod kehadiran pelajar sekolah ini sahaja.',
        'nama' => $nama
    ]);
    exit;
}

// Semak status aktif sekolah pelajar (Multi-Tenant Check)
$stmt = $conn->prepare('SELECT status, nama_sekolah, tarikh_luput FROM sekolah WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $stu_school_id);
$stmt->execute();
$chk_sch = $stmt->get_result();
if ($chk_sch && mysqli_num_rows($chk_sch) > 0) {
    $sch_row = mysqli_fetch_assoc($chk_sch);
    $schoolExpired = !empty($sch_row['tarikh_luput']) && strtotime((string)$sch_row['tarikh_luput']) < strtotime(date('Y-m-d'));
    if ($sch_row['status'] !== 'aktif' || $schoolExpired) {
        if ($is_zkteco) { header('Content-Type: text/plain'); echo "OK"; exit; }
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'msg' => 'Akses sekolah (' . htmlspecialchars($sch_row['nama_sekolah']) . ') telah digantung!',
            'nama' => $nama
        ]);
        exit;
    }
}
$stmt->close();

// Semak tetapan waktu operasi sekolah
$stmt = $conn->prepare("SELECT kunci, nilai FROM tetapan WHERE school_id = ? AND kunci IN ('waktu_mula', 'waktu_tamat')");
$stmt->bind_param('i', $stu_school_id);
$stmt->execute();
$sql_setting = $stmt->get_result();
$settings = ['waktu_mula' => '07:00', 'waktu_tamat' => '14:00'];
while ($s = mysqli_fetch_assoc($sql_setting)) {
    $settings[$s['kunci']] = $s['nilai'];
}
$stmt->close();

$mula = $settings['waktu_mula'];
$tamat = $settings['waktu_tamat'];

if ($waktu_sekarang < $mula || $waktu_sekarang > $tamat) {
    if ($is_zkteco) { header('Content-Type: text/plain'); echo "OK"; exit; }
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'msg' => 'Maaf! Waktu RMT Sekolah Tutup.',
        'info_tambahan' => "Waktu Operasi Sekolah: $mula - $tamat",
        'nama' => $nama
    ]);
    exit;
}

// Semak kelayakan status RMT pelajar
if ($status_pelajar != '1') {
    if ($is_zkteco) { header('Content-Type: text/plain'); echo "OK"; exit; }
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'msg' => 'GAGAL! Pelajar Tidak Layak RMT.',
        'nama' => $nama,
        'kelas' => $kelas
    ]);
    exit;
}

// Semak imbuhan berganda (duplicate scan) hari ini
$stmt = $conn->prepare('SELECT id FROM transaksi_rmt WHERE school_id = ? AND (rfid_uid = ? OR rfid_uid = ?) AND tarikh = ? LIMIT 1');
$stmt->bind_param('isss', $stu_school_id, $rfid_uid, $rfid_no_zero, $tarikh_harini);
$stmt->execute();
$check_double = $stmt->get_result();

if ($check_double && mysqli_num_rows($check_double) > 0) {
    if ($is_zkteco) {
        // ZKTeco mengharapkan respons OK walaupun duplicate supaya ia tidak hantar berulang kali
        header('Content-Type: text/plain');
        echo "OK";
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'duplicate',
        'msg' => 'MAAF! Anda sudah merekod kehadiran hari ini.',
        'nama' => $nama,
        'kelas' => $kelas
    ]);
    $stmt->close();
    exit;
}
$stmt->close();

// --------------------------------------------------------------------------
// 4. SIMPAN REKOD KEHADIRAN KE DALAM JADUAL TRANSAKSI
// --------------------------------------------------------------------------
$status_rekod = 'BERJAYA';
$stmt = $conn->prepare('INSERT INTO transaksi_rmt (school_id, rfid_uid, nama_penuh, nama_kelas, tarikh, waktu, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('issssss', $stu_school_id, $rfid_uid, $nama, $kelas, $tarikh_harini, $waktu_insert, $status_rekod);

try {
    $inserted = $stmt->execute();
} catch (mysqli_sql_exception $e) {
    if ((int)$e->getCode() === 1062) {
        if ($is_zkteco) { header('Content-Type: text/plain'); echo 'OK'; exit; }
        json_response(['status' => 'duplicate', 'msg' => 'MAAF! Anda sudah merekod kehadiran hari ini.', 'nama' => $nama, 'kelas' => $kelas], 409);
    }
    throw $e;
}

if ($inserted) {
    if ($is_zkteco) {
        header('Content-Type: text/plain');
        echo "OK";
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'msg' => 'LAYAK! Selamat Menjamu Selera.',
        'nama' => $nama,
        'kelas' => $kelas,
        'waktu' => date('h:i A', strtotime($waktu_insert))
    ]);
} else {
    if ($is_zkteco) { header('Content-Type: text/plain'); echo "ERROR"; exit; }
    if ($stmt->errno === 1062) {
        json_response(['status' => 'duplicate', 'msg' => 'MAAF! Anda sudah merekod kehadiran hari ini.', 'nama' => $nama, 'kelas' => $kelas], 409);
    }
    error_log('RFID attendance insert failed: ' . $stmt->error);
    json_response(['status' => 'error', 'msg' => 'Rekod kehadiran tidak dapat disimpan.'], 500);
}
$stmt->close();
?>
