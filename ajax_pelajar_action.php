<?php
require_once __DIR__ . '/security.php';
app_start_session();
header('Content-Type: application/json');

include 'db_connect.php';
include 'functions.php';

require_active_roles($conn, ['admin', 'gpk'], true);
require_csrf(true);

$sch_id = current_school_id();
$action = $_POST['action'] ?? '';

// ==========================================
// 1. TAMBAH PELAJAR BARU
// ==========================================
if ($action === 'add') {
    $nama_penuh = trim($_POST['nama_penuh'] ?? '');
    $rfid_uid   = trim($_POST['rfid_uid'] ?? '');
    $darjah     = trim($_POST['darjah'] ?? '');
    $nama_kelas = trim($_POST['nama_kelas'] ?? '');

    if (empty($nama_penuh) || empty($rfid_uid) || empty($darjah) || empty($nama_kelas)) {
        echo json_encode(['status' => 'error', 'msg' => 'Sila isi semua ruangan bertanda (*) dengan lengkap.']);
        exit;
    }
    if (!preg_match('/^[A-Za-z0-9:_-]{1,50}$/', $rfid_uid) || !in_array($darjah, ['1', '2', '3', '4', '5', '6'], true)) {
        json_response(['status' => 'error', 'msg' => 'Format RFID atau darjah tidak sah.'], 422);
    }

    // Semak duplikasi RFID
    $stmt = $conn->prepare('SELECT id FROM pelajar WHERE school_id = ? AND rfid_uid = ? LIMIT 1');
    $stmt->bind_param('is', $sch_id, $rfid_uid);
    $stmt->execute();
    $result_check = $stmt->get_result();

    if (mysqli_num_rows($result_check) > 0) {
        echo json_encode(['status' => 'error', 'msg' => "MAAF! Nombor RFID/ID '$rfid_uid' sudah didaftarkan untuk pelajar lain."]);
        exit;
    }
    $stmt->close();

    $stmt = $conn->prepare('SELECT id_kelas FROM kelas WHERE school_id = ? AND nama_kelas = ? LIMIT 1');
    $stmt->bind_param('is', $sch_id, $nama_kelas);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        json_response(['status' => 'error', 'msg' => 'Kelas yang dipilih tidak sah.'], 422);
    }
    $stmt->close();

    mysqli_begin_transaction($conn);
    try {
        // Kunci baris sekolah supaya dua import/tambah serentak tidak melepasi kuota.
        $planStmt = $conn->prepare('SELECT s.pelan, p.had_murid FROM sekolah s LEFT JOIN pelan_struktur p ON s.pelan = p.nama_pelan WHERE s.id = ? FOR UPDATE');
        $planStmt->bind_param('i', $sch_id);
        $planStmt->execute();
        $row_sch = $planStmt->get_result()->fetch_assoc();
        $planStmt->close();
        if ($row_sch && $row_sch['had_murid'] !== null && (int)$row_sch['had_murid'] > 0) {
            $countStmt = $conn->prepare('SELECT COUNT(*) AS tot FROM pelajar WHERE school_id = ?');
            $countStmt->bind_param('i', $sch_id);
            $countStmt->execute();
            $tot = (int)($countStmt->get_result()->fetch_assoc()['tot'] ?? 0);
            $countStmt->close();
            if ($tot >= (int)$row_sch['had_murid']) {
                $had = (int)$row_sch['had_murid'];
                mysqli_rollback($conn);
                json_response(['status' => 'error', 'msg' => "Pelan sekolah anda dihadkan kepada $had murid RMT. Sila naik taraf pelan untuk menambah murid."], 409);
            }
        }

        $status = 1;
        $stmt = $conn->prepare('INSERT INTO pelajar (school_id, rfid_uid, nama_penuh, darjah, nama_kelas, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssi', $sch_id, $rfid_uid, $nama_penuh, $darjah, $nama_kelas, $status);
        $stmt->execute();
        $stmt->close();
        catat_log($conn, (int)$_SESSION['user_id'], (string)$_SESSION['nama_penuh'], "Menambah pelajar baru: $nama_penuh (RFID: $rfid_uid, Tahun $darjah $nama_kelas)");
        mysqli_commit($conn);
        echo json_encode(['status' => 'success', 'msg' => 'Berjaya menambah pelajar baharu!']);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        error_log('Add student failed: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'msg' => 'Pelajar tidak dapat ditambah. No. kad mungkin telah digunakan.']);
    }
    exit;
}

// ==========================================
// 2. KEMASKINI / EDIT PELAJAR
// ==========================================
if ($action === 'update') {
    $rfid_lama  = trim($_POST['rfid_lama'] ?? '');
    $rfid_baru  = trim($_POST['rfid_uid'] ?? '');
    $nama       = trim($_POST['nama_penuh'] ?? '');
    $darjah     = trim($_POST['darjah'] ?? '');
    $kelas      = trim($_POST['nama_kelas'] ?? '');
    $status     = (string)($_POST['status'] ?? '1') === '0' ? 0 : 1;

    if (empty($rfid_lama) || empty($rfid_baru) || empty($nama)) {
        echo json_encode(['status' => 'error', 'msg' => 'Maklumat tidak lengkap.']);
        exit;
    }

    $targetStmt = $conn->prepare('SELECT id FROM pelajar WHERE school_id = ? AND rfid_uid = ? LIMIT 1');
    $targetStmt->bind_param('is', $sch_id, $rfid_lama);
    $targetStmt->execute();
    $targetExists = $targetStmt->get_result()->num_rows === 1;
    $targetStmt->close();
    if (!$targetExists) {
        json_response(['status' => 'error', 'msg' => 'Pelajar tidak dijumpai untuk sekolah ini.'], 404);
    }
    if (!preg_match('/^[A-Za-z0-9:_-]{1,50}$/', $rfid_baru) || !in_array($darjah, ['1', '2', '3', '4', '5', '6'], true)) {
        json_response(['status' => 'error', 'msg' => 'Format RFID atau darjah tidak sah.'], 422);
    }

    if ($rfid_lama !== $rfid_baru) {
        $stmt = $conn->prepare('SELECT id FROM pelajar WHERE school_id = ? AND rfid_uid = ? LIMIT 1');
        $stmt->bind_param('is', $sch_id, $rfid_baru);
        $stmt->execute();
        $check = $stmt->get_result();
        if (mysqli_num_rows($check) > 0) {
            echo json_encode(['status' => 'error', 'msg' => "Gagal! ID '$rfid_baru' sudah digunakan oleh pelajar lain."]);
            exit;
        }
        $stmt->close();
    }

    $stmt = $conn->prepare('SELECT id_kelas FROM kelas WHERE school_id = ? AND nama_kelas = ? LIMIT 1');
    $stmt->bind_param('is', $sch_id, $kelas);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        json_response(['status' => 'error', 'msg' => 'Kelas yang dipilih tidak sah.'], 422);
    }
    $stmt->close();

    $stmt = $conn->prepare('UPDATE pelajar SET rfid_uid = ?, nama_penuh = ?, darjah = ?, nama_kelas = ?, status = ? WHERE rfid_uid = ? AND school_id = ?');
    $stmt->bind_param('ssssisi', $rfid_baru, $nama, $darjah, $kelas, $status, $rfid_lama, $sch_id);
    if ($stmt->execute()) {
        if (isset($_SESSION['user_id'])) {
            catat_log($conn, $_SESSION['user_id'], $_SESSION['nama_penuh'], "Mengemaskini maklumat pelajar: $nama");
        }
        echo json_encode(['status' => 'success', 'msg' => 'Maklumat pelajar berjaya dikemaskini!']);
    } else {
        error_log('Update student failed: ' . $stmt->error);
        echo json_encode(['status' => 'error', 'msg' => 'Maklumat pelajar tidak dapat dikemaskini.']);
    }
    $stmt->close();
    exit;
}

// ==========================================
// 3. NYAHAKTIF / PADAM PELAJAR
// ==========================================
if ($action === 'delete') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['status' => 'error', 'msg' => 'Maaf, hanya Admin dibenarkan menyahaktifkan pelajar.']);
        exit;
    }

    $id = trim($_POST['id'] ?? '');
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'msg' => 'ID pelajar tidak sah.']);
        exit;
    }

    $stmt = $conn->prepare('SELECT nama_penuh, status FROM pelajar WHERE rfid_uid = ? AND school_id = ? LIMIT 1');
    $stmt->bind_param('si', $id, $sch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pelajar = mysqli_fetch_assoc($result);
    $stmt->close();
    if (!$pelajar) {
        json_response(['status' => 'error', 'msg' => 'Pelajar tidak dijumpai untuk sekolah ini.'], 404);
    }
    if ((int)$pelajar['status'] === 0) {
        json_response(['status' => 'error', 'msg' => 'Pelajar ini telah pun dinyahaktifkan.'], 409);
    }
    $nama_pelajar = $pelajar['nama_penuh'];

    $stmt = $conn->prepare('UPDATE pelajar SET status = 0 WHERE rfid_uid = ? AND school_id = ? AND status = 1');
    $stmt->bind_param('si', $id, $sch_id);
    if ($stmt->execute() && $stmt->affected_rows === 1) {
        catat_log($conn, $_SESSION['user_id'], $_SESSION['nama_penuh'], "Menyahaktifkan pelajar: $nama_pelajar (RFID: $id)");
        echo json_encode(['status' => 'success', 'msg' => 'Pelajar berjaya dinyahaktifkan!']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Gagal menyahaktifkan pelajar.']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['status' => 'error', 'msg' => 'Tindakan tidak sah.']);
exit;
