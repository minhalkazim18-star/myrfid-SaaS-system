<?php
require_once __DIR__ . '/security.php';
ob_start(); // Buffer output supaya tiada amaran PHP mengacau respons JSON
app_start_session();
header('Content-Type: application/json');

include 'db_connect.php';
include 'functions.php';

require_active_roles($conn, ['admin'], true);
require_csrf(true);

$sch_id = (int)($_SESSION['school_id'] ?? 1);
$action = $_POST['action'] ?? '';

function new_device_secret(): string {
    return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
}

if ($action === 'list_devices') {
    $stmt = $conn->prepare('SELECT id, device_id, label, is_active, last_used_at, created_at FROM rfid_devices WHERE school_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $sch_id);
    $stmt->execute();
    $devices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    json_response(['status' => 'success', 'devices' => $devices]);
}

if ($action === 'create_device') {
    $label = trim((string)($_POST['label'] ?? ''));
    $deviceId = trim((string)($_POST['device_id'] ?? ''));
    if ($label === '' || mb_strlen($label) > 100) {
        json_response(['status' => 'error', 'msg' => 'Nama terminal wajib diisi dan maksimum 100 aksara.'], 422);
    }
    if ($deviceId === '') $deviceId = 'terminal-' . strtolower(bin2hex(random_bytes(4)));
    if (!preg_match('/^[A-Za-z0-9._-]{3,64}$/', $deviceId)) {
        json_response(['status' => 'error', 'msg' => 'ID terminal mesti 3–64 aksara (huruf, nombor, titik, sengkang atau garis bawah).'], 422);
    }
    $secret = new_device_secret();
    $hash = hash('sha256', $secret);
    $createdBy = (int)$_SESSION['user_id'];
    try {
        $stmt = $conn->prepare('INSERT INTO rfid_devices (school_id, device_id, label, api_key_hash, created_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('isssi', $sch_id, $deviceId, $label, $hash, $createdBy);
        $stmt->execute();
        $stmt->close();
        log_aktiviti($conn, "Mendaftarkan terminal RFID $deviceId");
        json_response(['status' => 'success', 'msg' => 'Terminal berjaya didaftarkan. Salin kunci sekarang; ia tidak akan dipaparkan lagi.', 'device_id' => $deviceId, 'api_key' => $secret], 201);
    } catch (Throwable $e) {
        error_log('Create RFID device failed: ' . $e->getMessage());
        json_response(['status' => 'error', 'msg' => 'ID terminal telah digunakan atau terminal tidak dapat didaftarkan.'], 409);
    }
}

if ($action === 'rotate_device') {
    $deviceRowId = (int)($_POST['id'] ?? 0);
    $secret = new_device_secret();
    $hash = hash('sha256', $secret);
    $stmt = $conn->prepare('UPDATE rfid_devices SET api_key_hash = ?, is_active = 1 WHERE id = ? AND school_id = ?');
    $stmt->bind_param('sii', $hash, $deviceRowId, $sch_id);
    $stmt->execute();
    $changed = $stmt->affected_rows;
    $stmt->close();
    if (!$changed) json_response(['status' => 'error', 'msg' => 'Terminal tidak dijumpai.'], 404);
    log_aktiviti($conn, "Menukar kunci terminal RFID #$deviceRowId");
    json_response(['status' => 'success', 'msg' => 'Kunci baharu dijana. Salin sekarang; kunci lama telah dibatalkan.', 'api_key' => $secret]);
}

if ($action === 'revoke_device') {
    $deviceRowId = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare('UPDATE rfid_devices SET is_active = 0 WHERE id = ? AND school_id = ? AND is_active = 1');
    $stmt->bind_param('ii', $deviceRowId, $sch_id);
    $stmt->execute();
    $changed = $stmt->affected_rows;
    $stmt->close();
    if (!$changed) json_response(['status' => 'error', 'msg' => 'Terminal aktif tidak dijumpai.'], 404);
    log_aktiviti($conn, "Membatalkan terminal RFID #$deviceRowId");
    json_response(['status' => 'success', 'msg' => 'Akses terminal telah dibatalkan.']);
}

// Fungsi helper selamat untuk jadual tetapan (mengelakkan ralat Primary Key id=0 jika tiada Auto Increment)
function kemaskini_tetapan($conn, $sch_id, $kunci, $nilai) {
    try {
        $sch_id = (int)$sch_id;
        $stmt = $conn->prepare('INSERT INTO tetapan (school_id, kunci, nilai) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)');
        if (!$stmt) {
            throw new RuntimeException('Tetapan statement could not be prepared');
        }
        $stmt->bind_param('iss', $sch_id, $kunci, $nilai);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $t) {
        error_log('Update setting failed: ' . $t->getMessage());
        throw $t;
    }
}

// ==========================================
// 1. SIMPAN PROFIL & LOGO SEKOLAH
// ==========================================
if ($action === 'simpan_profil') {
    $nama_sek = trim($_POST['nama_sekolah'] ?? '');
    $alamat_sek = trim((string)($_POST['alamat_sekolah'] ?? ''));
    $logo_url = $_SESSION['logo_sekolah'] ?? 'images/default_logo.png';
    if ($logo_url === 'images/logosklh.png') {
        $logo_url = 'images/default_logo.png';
    }

    if ($nama_sek === '' || mb_strlen($nama_sek) > 150 || mb_strlen($alamat_sek) > 1000) {
        json_response(['status' => 'error', 'msg' => 'Nama sekolah wajib diisi (maksimum 150 aksara) dan alamat maksimum 1000 aksara.'], 422);
    }
    $stmt = $conn->prepare('UPDATE sekolah SET nama_sekolah = ?, alamat = ? WHERE id = ?');
    $stmt->bind_param('ssi', $nama_sek, $alamat_sek, $sch_id);
    $stmt->execute();
    $stmt->close();
    kemaskini_tetapan($conn, $sch_id, 'nama_sekolah', $nama_sek);
    $_SESSION['nama_sekolah'] = $nama_sek;

    if (isset($_FILES['logo_sekolah']) && !empty($_FILES['logo_sekolah']['name'])) {
        if ($_FILES['logo_sekolah']['error'] === 0) {
            $maxBytes = 2 * 1024 * 1024;
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['logo_sekolah']['tmp_name']);
            $mimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            if ($_FILES['logo_sekolah']['size'] <= $maxBytes && isset($mimeMap[$mime]) && @getimagesize($_FILES['logo_sekolah']['tmp_name']) !== false) {
                $dir = 'uploads/logos';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $filename = 'logo_school_' . $sch_id . '_' . bin2hex(random_bytes(8)) . '.' . $mimeMap[$mime];
                $target_path = $dir . "/" . $filename;

                if (move_uploaded_file($_FILES['logo_sekolah']['tmp_name'], $target_path)) {
                    $logoStmt = $conn->prepare('UPDATE sekolah SET logo = ? WHERE id = ?');
                    $logoStmt->bind_param('si', $target_path, $sch_id);
                    $logoStmt->execute();
                    $logoStmt->close();
                    kemaskini_tetapan($conn, $sch_id, 'logo_sekolah', $target_path);
                    $_SESSION['logo_sekolah'] = $target_path;
                    $logo_url = $target_path;
                    log_aktiviti($conn, "Mengemaskini Logo dan Profil Sekolah kepada $nama_sek");
                } else {
                    ob_clean();
                    echo json_encode(['status' => 'error', 'msg' => 'Ralat memuat naik gambar logo ke folder server (Permission / Path Error).']);
                    exit;
                }
            } else {
                ob_clean();
                echo json_encode(['status' => 'error', 'msg' => 'Fail mesti imej JPG, PNG atau WebP yang sah dan tidak melebihi 2MB.']);
                exit;
            }
        } else {
            ob_clean();
            echo json_encode(['status' => 'error', 'msg' => 'Ralat muat naik gambar (Kod Ralat: ' . $_FILES['logo_sekolah']['error'] . '). Saiz fail mungkin terlalu besar.']);
            exit;
        }
    } else {
        log_aktiviti($conn, "Mengemaskini profil sekolah kepada $nama_sek");
    }

    session_write_close(); // Lepaskan lock sesi secepat mungkin
    ob_clean();
    echo json_encode([
        'status' => 'success',
        'msg' => 'Profil dan Logo Sekolah berjaya dikemaskini!',
        'nama_sekolah' => $nama_sek,
        'logo_url' => $logo_url,
        'alamat_sekolah' => $alamat_sek
    ]);
    exit;
}

// ==========================================
// 2. SIMPAN WAKTU OPERASI RMT
// ==========================================
if ($action === 'simpan_waktu') {
    $mula = trim($_POST['waktu_mula'] ?? '08:30');
    $tamat = trim($_POST['waktu_tamat'] ?? '12:00');
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $mula) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $tamat) || $mula >= $tamat) {
        json_response(['status' => 'error', 'msg' => 'Julat waktu operasi tidak sah.'], 422);
    }

    kemaskini_tetapan($conn, $sch_id, 'waktu_mula', $mula);
    kemaskini_tetapan($conn, $sch_id, 'waktu_tamat', $tamat);
    log_aktiviti($conn, "Mengemaskini Waktu RMT ($mula - $tamat)");

    session_write_close();
    ob_clean();
    echo json_encode(['status' => 'success', 'msg' => 'Waktu operasi RMT berjaya dikemaskini!']);
    exit;
}

// ==========================================
// 3. ANJAKAN KELAS / TAHUN BARU
// ==========================================
if ($action === 'naik_tahun') {
    $confirmation = strtoupper(trim((string)($_POST['confirmation'] ?? '')));
    $rolloverYear = (int)date('Y');
    $requestedYear = (int)($_POST['year'] ?? 0);
    if ($confirmation !== 'SAHKAN' || $requestedYear !== $rolloverYear) {
        json_response(['status' => 'error', 'msg' => 'Pengesahan tahun akademik tidak sah. Muat semula halaman dan cuba lagi.'], 422);
    }

    mysqli_begin_transaction($conn);
    try {
        // Kunci penanda ini di dalam transaksi supaya proses tidak boleh dimainkan
        // semula atau dihantar serentak untuk sekolah dan tahun yang sama.
        $rolloverKey = 'rollover_last_year';
        $stmt = $conn->prepare('SELECT nilai FROM tetapan WHERE school_id = ? AND kunci = ? FOR UPDATE');
        $stmt->bind_param('is', $sch_id, $rolloverKey);
        $stmt->execute();
        $rolloverSetting = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ((int)($rolloverSetting['nilai'] ?? 0) === $rolloverYear) {
            mysqli_rollback($conn);
            json_response(['status' => 'error', 'msg' => "Anjakan kelas bagi tahun $rolloverYear telah pun selesai dan tidak boleh dijalankan semula."], 409);
        }

        if ($rolloverSetting) {
            $yearValue = (string)$rolloverYear;
            $stmt = $conn->prepare('UPDATE tetapan SET nilai = ? WHERE school_id = ? AND kunci = ?');
            $stmt->bind_param('sis', $yearValue, $sch_id, $rolloverKey);
        } else {
            $yearValue = (string)$rolloverYear;
            $stmt = $conn->prepare('INSERT INTO tetapan (school_id, kunci, nilai) VALUES (?, ?, ?)');
            $stmt->bind_param('iss', $sch_id, $rolloverKey, $yearValue);
        }
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE pelajar SET status = 0 WHERE darjah = '6' AND school_id = ?");
        $stmt->bind_param('i', $sch_id);
        $stmt->execute();
        $graduatedCount = $stmt->affected_rows;
        $stmt->close();
        $stmt = $conn->prepare("UPDATE pelajar SET darjah = CAST(darjah AS UNSIGNED) + 1 WHERE school_id = ? AND status = 1 AND darjah REGEXP '^[1-5]$'");
        $stmt->bind_param('i', $sch_id);
        $stmt->execute();
        $promotedCount = $stmt->affected_rows;
        $stmt->close();
        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        error_log('Year rollover failed: ' . $e->getMessage());
        json_response(['status' => 'error', 'msg' => 'Proses anjakan gagal dan semua perubahan telah dibatalkan.'], 500);
    }

    log_aktiviti($conn, "Anjakan kelas tahun $rolloverYear selesai: $promotedCount pelajar dinaikkan, $graduatedCount pelajar Tahun 6 dinyahaktifkan");

    session_write_close();
    ob_clean();
    echo json_encode([
        'status' => 'success',
        'msg' => "Anjakan tahun $rolloverYear selesai: $promotedCount pelajar dinaikkan dan $graduatedCount pelajar Tahun 6 dinyahaktifkan.",
        'promoted' => $promotedCount,
        'graduated' => $graduatedCount,
        'year' => $rolloverYear,
    ]);
    exit;
}

ob_clean();
echo json_encode(['status' => 'error', 'msg' => 'Permintaan tidak sah atau tindakan tidak dijumpai.']);
exit;
?>
