<?php
require_once __DIR__ . '/security.php';
app_start_session();
/**
 * AJAX KIOSK DATA (ajax_kiosk_data.php)
 * Menguruskan data masa nyata (statistik & senarai imbasan terkini)
 * untuk halaman Kiosk RMT Tanpa Login (Idea 1).
 */
include 'db_connect.php';
require_active_roles($conn, ['admin', 'gpk', 'guru'], true);
header('Content-Type: application/json');
date_default_timezone_set("Asia/Kuala_Lumpur");

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$sch_id = current_school_id();
$tarikh_harini = date('Y-m-d');

// 1. Dapatkan Statistik & Maklumat Sekolah
if ($action === 'stats') {
    // Cari nama & logo sekolah
    $nama_sekolah = "Sekolah RMT";
    $logo_sekolah = "images/default_logo.png";
    $kod_sekolah  = "";

    $sql_sch = mysqli_query($conn, "SELECT * FROM sekolah WHERE id = '$sch_id' LIMIT 1");
    if ($sql_sch && mysqli_num_rows($sql_sch) > 0) {
        $row_sch = mysqli_fetch_assoc($sql_sch);
        $nama_sekolah = $row_sch['nama_sekolah'];
        $kod_sekolah  = $row_sch['kod_sekolah'];
        if (!empty($row_sch['logo'])) $logo_sekolah = $row_sch['logo'];
    }

    // Semak tetapan logo/nama override
    $sql_cfg = mysqli_query($conn, "SELECT kunci, nilai FROM tetapan WHERE school_id = '$sch_id' AND kunci IN ('nama_sekolah', 'logo_sekolah', 'waktu_mula', 'waktu_tamat')");
    $waktu_mula = "07:00";
    $waktu_tamat = "14:00";
    if ($sql_cfg) {
        while ($cfg = mysqli_fetch_assoc($sql_cfg)) {
            if ($cfg['kunci'] === 'nama_sekolah' && !empty($cfg['nilai'])) $nama_sekolah = $cfg['nilai'];
            if ($cfg['kunci'] === 'logo_sekolah' && !empty($cfg['nilai'])) $logo_sekolah = $cfg['nilai'];
            if ($cfg['kunci'] === 'waktu_mula') $waktu_mula = $cfg['nilai'];
            if ($cfg['kunci'] === 'waktu_tamat') $waktu_tamat = $cfg['nilai'];
        }
    }
    if ($logo_sekolah === 'images/logosklh.png' || $logo_sekolah === 'images/default_logo.png' || !file_exists($logo_sekolah) || filesize($logo_sekolah) < 200) {
        $logo_sekolah = 'images/logo_drs.png';
    }

    // Kira Pelajar Layak
    $q_layak = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM pelajar WHERE school_id = '$sch_id' AND status = 1");
    $layak = ($q_layak && $r = mysqli_fetch_assoc($q_layak)) ? (int)$r['cnt'] : 0;

    // Kira Pelajar Hadir Hari Ini
    $q_hadir = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM transaksi_rmt WHERE school_id = '$sch_id' AND tarikh = '$tarikh_harini'");
    $hadir = ($q_hadir && $r = mysqli_fetch_assoc($q_hadir)) ? (int)$r['cnt'] : 0;

    $baki = max(0, $layak - $hadir);
    $peratus = ($layak > 0) ? round(($hadir / $layak) * 100) : 0;

    // Ambil 5 Imbasan Terkini Hari Ini
    $recent_scans = [];
    $q_recent = mysqli_query($conn, "SELECT nama_penuh, nama_kelas, waktu, status FROM transaksi_rmt WHERE school_id = '$sch_id' AND tarikh = '$tarikh_harini' ORDER BY id DESC LIMIT 5");
    if ($q_recent && mysqli_num_rows($q_recent) > 0) {
        while ($row_r = mysqli_fetch_assoc($q_recent)) {
            $recent_scans[] = [
                'nama' => htmlspecialchars($row_r['nama_penuh']),
                'kelas' => htmlspecialchars($row_r['nama_kelas']),
                'waktu' => date('h:i:s A', strtotime($row_r['waktu'])),
                'status' => $row_r['status']
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'sekolah' => [
            'id' => $sch_id,
            'nama' => $nama_sekolah,
            'kod' => $kod_sekolah,
            'logo' => $logo_sekolah,
            'waktu_operasi' => "$waktu_mula - $waktu_tamat"
        ],
        'statistik' => [
            'layak' => $layak,
            'hadir' => $hadir,
            'baki'  => $baki,
            'peratus' => $peratus
        ],
        'recent' => $recent_scans
    ]);
    exit;
}

// 2. Carian Senarai Sekolah Untuk Pemilihan Awalan
if ($action === 'list_schools') {
    $schools = [];
    $q = mysqli_query($conn, "SELECT id, kod_sekolah, nama_sekolah, logo FROM sekolah WHERE id = '$sch_id' AND status = 'aktif' LIMIT 1");
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $logo = (!empty($row['logo']) && file_exists($row['logo']) && $row['logo'] !== 'images/logosklh.png' && $row['logo'] !== 'images/default_logo.png' && filesize($row['logo']) >= 200) ? $row['logo'] : 'images/logo_drs.png';
            $schools[] = [
                'id' => (int)$row['id'],
                'kod' => htmlspecialchars($row['kod_sekolah']),
                'nama' => htmlspecialchars($row['nama_sekolah']),
                'logo' => $logo
            ];
        }
    }
    echo json_encode(['status' => 'success', 'schools' => $schools]);
    exit;
}

echo json_encode(['status' => 'error', 'msg' => 'Invalid action']);
?>
