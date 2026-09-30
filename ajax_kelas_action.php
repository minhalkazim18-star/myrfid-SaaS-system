<?php
require_once __DIR__ . '/security.php';
app_start_session();
header('Content-Type: application/json');

include 'db_connect.php';
include 'functions.php';

require_active_account($conn, true);
require_csrf(true);

$sch_id = current_school_id();
$action = $_POST['action'] ?? '';

// ==========================================
// 0. SENARAI KELAS (HTML ROWS)
// ==========================================
if ($action === 'list') {
    header('Content-Type: text/html');
    $stmt = $conn->prepare('SELECT id_kelas, nama_kelas FROM kelas WHERE school_id = ? ORDER BY nama_kelas ASC');
    $stmt->bind_param('i', $sch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bil = 1;

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $bil++ . "</td>";
            echo "<td>" . htmlspecialchars($row['nama_kelas']) . "</td>";
            echo "<td class='text-center'>
                    <button class='btn btn-danger btn-sm btn_delete_kelas' 
                       data-id='" . $row['id_kelas'] . "'
                       data-nama='" . htmlspecialchars($row['nama_kelas'], ENT_QUOTES) . "'>
                       <i class='fa fa-trash'></i>
                    </button>
                  </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='3' class='text-center text-muted'>Tiada kelas didaftarkan.</td></tr>";
    }
    $stmt->close();
    exit;
}

// ==========================================
// 1. TAMBAH KELAS
// ==========================================
if ($action === 'add') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['status' => 'error', 'msg' => 'Tiada kebenaran untuk menambah kelas.']);
        exit;
    }

    $nama_kelas_baru = trim((string)($_POST['nama_kelas_baru'] ?? ''));

    if (empty($nama_kelas_baru)) {
        echo json_encode(['status' => 'error', 'msg' => 'Sila masukkan nama kelas.']);
        exit;
    }

    if (!valid_class_name($nama_kelas_baru)) {
        json_response(['status' => 'error', 'msg' => 'Nama kelas mesti 1–50 aksara dan hanya menggunakan huruf, nombor, ruang atau tanda asas.'], 422);
    }

    $stmt = $conn->prepare('SELECT id_kelas FROM kelas WHERE nama_kelas = ? AND school_id = ? LIMIT 1');
    $stmt->bind_param('si', $nama_kelas_baru, $sch_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        echo json_encode(['status' => 'error', 'msg' => 'Kelas ini sudah wujud dalam sistem!']);
        exit;
    }
    $stmt->close();

    $stmt = $conn->prepare('INSERT INTO kelas (school_id, nama_kelas) VALUES (?, ?)');
    $stmt->bind_param('is', $sch_id, $nama_kelas_baru);
    if ($stmt->execute()) {
        log_aktiviti($conn, "Menambah kelas baru: $nama_kelas_baru");
        echo json_encode(['status' => 'success', 'msg' => 'Kelas berjaya ditambah!']);
    } else {
        error_log('Add class failed: ' . $stmt->error);
        echo json_encode(['status' => 'error', 'msg' => 'Kelas tidak dapat ditambah. Nama kelas mungkin telah digunakan.']);
    }
    exit;
}

// ==========================================
// 2. PADAM KELAS
// ==========================================
if ($action === 'delete') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['status' => 'error', 'msg' => 'Tiada kebenaran untuk memadam kelas.']);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'msg' => 'ID kelas tidak sah.']);
        exit;
    }

    $stmt = $conn->prepare('SELECT nama_kelas FROM kelas WHERE id_kelas = ? AND school_id = ? LIMIT 1');
    $stmt->bind_param('ii', $id, $sch_id);
    $stmt->execute();
    $kelas = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$kelas) {
        json_response(['status' => 'error', 'msg' => 'Kelas tidak dijumpai untuk sekolah ini.'], 404);
    }
    $nama_kelas = $kelas['nama_kelas'];

    // Semak sama ada ada pelajar dalam kelas ini
    $stmt = $conn->prepare('SELECT id FROM pelajar WHERE nama_kelas = ? AND school_id = ? AND status = 1 LIMIT 1');
    $stmt->bind_param('si', $nama_kelas, $sch_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        echo json_encode(['status' => 'error', 'msg' => "Tidak boleh memadam kelas '$nama_kelas' kerana masih terdapat pelajar aktif di dalam kelas ini."]);
        exit;
    }
    $stmt->close();

    $stmt = $conn->prepare('DELETE FROM kelas WHERE id_kelas = ? AND school_id = ?');
    $stmt->bind_param('ii', $id, $sch_id);
    if ($stmt->execute() && $stmt->affected_rows === 1) {
        log_aktiviti($conn, "Memadam kelas: $nama_kelas");
        echo json_encode(['status' => 'success', 'msg' => 'Kelas berjaya dipadam!']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Gagal memadam kelas.']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['status' => 'error', 'msg' => 'Tindakan tidak sah.']);
exit;
