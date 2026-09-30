<?php
require_once __DIR__ . '/security.php';
error_reporting(0);
ini_set('display_errors', 0);
app_start_session();
header('Content-Type: application/json');

include 'db_connect.php';

require_active_roles($conn, ['superadmin'], true);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf(true);
}

$action = $_REQUEST['action'] ?? '';

function get_superadmin_notifications(mysqli $conn, int $userId): array
{
    $notifications = [];

    $pending = $conn->prepare("SELECT s.id, s.nama_sekolah, s.kod_sekolah, s.tarikh_daftar,
        nr.read_at
        FROM sekolah s
        LEFT JOIN superadmin_notification_reads nr
          ON nr.user_id = ?
         AND nr.notification_key = CONCAT('pending_school:', s.id)
        WHERE s.status = 'pending'
        ORDER BY s.tarikh_daftar DESC, s.id DESC");
    $pending->bind_param('i', $userId);
    $pending->execute();
    $pendingRows = $pending->get_result();
    while ($row = $pendingRows->fetch_assoc()) {
        $notifications[] = [
            'key' => 'pending_school:' . (int)$row['id'],
            'type' => 'approval',
            'title' => 'Permohonan sekolah baharu',
            'message' => $row['nama_sekolah'] . ' (' . $row['kod_sekolah'] . ') menunggu kelulusan.',
            'href' => '#approvalPanel',
            'created_at' => date('d/m/Y H:i', strtotime((string)$row['tarikh_daftar'])),
            'is_read' => $row['read_at'] !== null,
        ];
    }
    $pending->close();

    $expiring = $conn->prepare("SELECT s.id, s.nama_sekolah, s.kod_sekolah, s.tarikh_luput,
        nr.read_at
        FROM sekolah s
        LEFT JOIN superadmin_notification_reads nr
          ON nr.user_id = ?
         AND nr.notification_key = CONCAT('expiring_school:', s.id, ':', DATE(s.tarikh_luput))
        WHERE s.status = 'aktif'
          AND s.tarikh_luput IS NOT NULL
          AND DATE(s.tarikh_luput) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY s.tarikh_luput ASC, s.id ASC");
    $expiring->bind_param('i', $userId);
    $expiring->execute();
    $expiringRows = $expiring->get_result();
    while ($row = $expiringRows->fetch_assoc()) {
        $expiryDate = date('Y-m-d', strtotime((string)$row['tarikh_luput']));
        $notifications[] = [
            'key' => 'expiring_school:' . (int)$row['id'] . ':' . $expiryDate,
            'type' => 'expiry',
            'title' => 'Langganan hampir tamat',
            'message' => $row['nama_sekolah'] . ' tamat pada ' . date('d/m/Y', strtotime($expiryDate)) . '.',
            'href' => '#expiringSoonList',
            'created_at' => date('d/m/Y', strtotime($expiryDate)),
            'is_read' => $row['read_at'] !== null,
        ];
    }
    $expiring->close();

    usort($notifications, static function (array $left, array $right): int {
        if ($left['is_read'] !== $right['is_read']) return $left['is_read'] ? 1 : -1;
        return strcmp((string)$right['created_at'], (string)$left['created_at']);
    });

    return $notifications;
}

// 1. GET STATS REAL-TIME
if ($action === 'get_stats') {
    $total_schools = 0;
    $active_schools = 0;
    $suspended_schools = 0;
    $total_students = 0;
    $total_admins = 0;

    $res_sch = mysqli_query($conn, "SELECT status, COUNT(*) as cnt FROM sekolah GROUP BY status");
    while ($row = mysqli_fetch_assoc($res_sch)) {
        $cnt = (int)$row['cnt'];
        $total_schools += $cnt;
        if ($row['status'] === 'aktif') {
            $active_schools = $cnt;
        } elseif ($row['status'] === 'digantung') {
            $suspended_schools = $cnt;
        }
    }

    $res_stu = mysqli_query($conn, "SELECT COUNT(*) as total FROM pelajar");
    if ($res_stu && $r = mysqli_fetch_assoc($res_stu)) {
        $total_students = (int)$r['total'];
    }

    $res_adm = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role != 'superadmin'");
    if ($res_adm && $r = mysqli_fetch_assoc($res_adm)) {
        $total_admins = (int)$r['total'];
    }

    $pending_schools = 0;
    $res_pen = mysqli_query($conn, "SELECT COUNT(*) as total FROM sekolah WHERE status = 'pending'");
    if ($res_pen && $r = mysqli_fetch_assoc($res_pen)) {
        $pending_schools = (int)$r['total'];
    }

    $expiring_schools = 0;
    $res_exp = mysqli_query($conn, "SELECT COUNT(*) as total FROM sekolah WHERE status = 'aktif' AND tarikh_luput IS NOT NULL AND DATE(tarikh_luput) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    if ($res_exp && $r = mysqli_fetch_assoc($res_exp)) {
        $expiring_schools = (int)$r['total'];
    }

    $notifications = get_superadmin_notifications($conn, (int)$_SESSION['user_id']);
    $unread_notifications = count(array_filter($notifications, static fn(array $notification): bool => !$notification['is_read']));

    echo json_encode([
        'status' => 'success',
        'stats' => [
            'total_schools' => $total_schools,
            'active_schools' => $active_schools,
            'suspended_schools' => $suspended_schools,
            'pending_schools' => $pending_schools,
            'expiring_schools' => $expiring_schools,
            'total_students' => $total_students,
            'total_admins' => $total_admins,
            'unread_notifications' => $unread_notifications
        ],
        'notifications' => $notifications
    ]);
    exit;
}

if ($action === 'mark_notification_read') {
    $notificationKey = trim((string)($_POST['notification_key'] ?? ''));
    if (!preg_match('/^(pending_school:\d+|expiring_school:\d+:\d{4}-\d{2}-\d{2})$/', $notificationKey)) {
        json_response(['status' => 'error', 'msg' => 'Notifikasi tidak sah.'], 422);
    }

    $userId = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare('INSERT INTO superadmin_notification_reads (user_id, notification_key, read_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)');
    $stmt->bind_param('is', $userId, $notificationKey);
    $stmt->execute();
    $stmt->close();
    json_response(['status' => 'success']);
}

if ($action === 'mark_all_notifications_read') {
    $userId = (int)$_SESSION['user_id'];
    $notifications = get_superadmin_notifications($conn, $userId);
    $stmt = $conn->prepare('INSERT INTO superadmin_notification_reads (user_id, notification_key, read_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)');
    foreach ($notifications as $notification) {
        $key = (string)$notification['key'];
        $stmt->bind_param('is', $userId, $key);
        $stmt->execute();
    }
    $stmt->close();
    json_response(['status' => 'success', 'count' => count($notifications)]);
}

// 2. GET SENARAI SEKOLAH (TENANT LIST)
if ($action === 'get_schools') {
    $sql = "SELECT s.*, 
            (SELECT COUNT(*) FROM pelajar p WHERE p.school_id = s.id) as jum_pelajar,
            (SELECT COUNT(*) FROM users u WHERE u.school_id = s.id AND u.role != 'superadmin') as jum_staf,
            ps.had_murid
            FROM sekolah s 
            LEFT JOIN pelan_struktur ps ON s.pelan = ps.nama_pelan
            WHERE s.status IN ('aktif', 'digantung')
            ORDER BY s.id ASC";
    $result = mysqli_query($conn, $sql);
    $schools = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $is_expired = !empty($row['tarikh_luput']) && strtotime($row['tarikh_luput']) < strtotime('today');
        $schools[] = [
            'id' => (int)$row['id'],
            'kod_sekolah' => $row['kod_sekolah'],
            'nama_sekolah' => $row['nama_sekolah'],
            'email_sekolah' => $row['email_sekolah'] ?? '-',
            'no_tel' => $row['no_tel'] ?? '-',
            'alamat' => $row['alamat'] ?? '',
            'status' => $row['status'],
            'pelan' => $row['pelan'] ?? 'Trial',
            'tarikh_luput' => $row['tarikh_luput'] ? date('d/m/Y', strtotime($row['tarikh_luput'])) : '-',
            'tarikh_luput_iso' => $row['tarikh_luput'] ? date('Y-m-d', strtotime($row['tarikh_luput'])) : '',
            'is_expired' => $is_expired,
            'status_bayaran' => $row['status_bayaran'] ?? 'Percuma',
            'jum_pelajar' => (int)$row['jum_pelajar'],
            'had_murid' => $row['had_murid'] !== null ? (int)$row['had_murid'] : 'unlimited',
            'jum_staf' => (int)$row['jum_staf'],
            'tarikh_daftar' => date('d/m/Y', strtotime($row['tarikh_daftar']))
        ];
    }

    echo json_encode([
        'status' => 'success',
        'schools' => $schools
    ]);
    exit;
}

// 2.5 GET CHART DATA (ANALYTICS)
if ($action === 'get_charts') {
    $monthly_trend = [];
    $res_trend = mysqli_query($conn, "
        SELECT 
            DATE_FORMAT(tarikh_daftar, '%Y-%m') as ym, 
            DATE_FORMAT(MAX(tarikh_daftar), '%b %Y') as bulan, 
            COUNT(*) as jumlah 
        FROM sekolah 
        WHERE tarikh_daftar >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY ym
        ORDER BY ym ASC
    ");
    while ($row = mysqli_fetch_assoc($res_trend)) {
        $monthly_trend[] = $row;
    }

    $plan_breakdown = [];
    $res_plan = mysqli_query($conn, "SELECT pelan, COUNT(*) as jumlah FROM sekolah GROUP BY pelan");
    while ($row = mysqli_fetch_assoc($res_plan)) {
        $plan_breakdown[] = $row;
    }
    
    $expiring_soon = [];
    $res_exp = mysqli_query($conn, "
        SELECT id, nama_sekolah, kod_sekolah, pelan, tarikh_luput 
        FROM sekolah 
        WHERE status = 'aktif'
        AND tarikh_luput IS NOT NULL 
        AND DATE(tarikh_luput) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY tarikh_luput ASC
    ");
    if($res_exp) {
        while ($row = mysqli_fetch_assoc($res_exp)) {
            $row['tarikh_luput_my'] = date('d/m/Y', strtotime($row['tarikh_luput']));
            $expiring_soon[] = $row;
        }
    }

    $expired_schools = [];
    $expired_result = $conn->query("SELECT id,nama_sekolah,kod_sekolah,pelan,tarikh_luput FROM sekolah WHERE status='aktif' AND tarikh_luput < CURDATE() ORDER BY tarikh_luput ASC");
    while ($expired_row = $expired_result->fetch_assoc()) {
        $expired_row['tarikh_luput_my'] = date('d/m/Y',strtotime($expired_row['tarikh_luput']));
        $expired_schools[] = $expired_row;
    }
    echo json_encode([
        'status' => 'success',
        'monthly_trend' => $monthly_trend,
        'plan_breakdown' => $plan_breakdown,
        'expired_schools' => $expired_schools,
        'expiring_soon' => $expiring_soon
    ]);
    exit;
}

// 3. TAMBAH SEKOLAH BAHARU SERENTAK ADMIN SEKOLAH
if ($action === 'add_school') {
    $kod_sekolah   = trim($_POST['kod_sekolah'] ?? '');
    $nama_sekolah  = trim($_POST['nama_sekolah'] ?? '');
    $email_sekolah = trim($_POST['email_sekolah'] ?? '');
    $no_tel        = trim($_POST['no_tel'] ?? '');

    $admin_nama     = trim($_POST['admin_nama'] ?? '');
    $admin_email    = trim($_POST['admin_email'] ?? '');
    $admin_username = trim($_POST['admin_username'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';

    if (empty($kod_sekolah) || empty($nama_sekolah) || empty($admin_nama) || empty($admin_email) || empty($admin_username) || empty($admin_password)) {
        echo json_encode(['status' => 'error', 'msg' => 'Sila isi semua ruangan bertanda (*) dengan lengkap, termasuk E-mel Pentadbir.']);
        exit;
    }
    if (strlen($admin_password) < 10) {
        json_response(['status' => 'error', 'msg' => 'Kata laluan pentadbir mesti sekurang-kurangnya 10 aksara.'], 422);
    }
    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        json_response(['status' => 'error', 'msg' => 'Alamat e-mel pentadbir tidak sah.'], 422);
    }

    $chk_kod = mysqli_query($conn, "SELECT id FROM sekolah WHERE kod_sekolah = '" . mysqli_real_escape_string($conn, $kod_sekolah) . "'");
    if (mysqli_num_rows($chk_kod) > 0) {
        echo json_encode(['status' => 'error', 'msg' => 'Kod Sekolah ini telah wujud dalam sistem!']);
        exit;
    }

    $chk_uname = mysqli_query($conn, "SELECT id FROM users WHERE username = '" . mysqli_real_escape_string($conn, $admin_username) . "'");
    if (mysqli_num_rows($chk_uname) > 0) {
        echo json_encode(['status' => 'error', 'msg' => 'Username Admin ini telah digunakan! Sila pilih username lain.']);
        exit;
    }

    $chk_m = mysqli_query($conn, "SELECT id FROM users WHERE email = '" . mysqli_real_escape_string($conn, $admin_email) . "' AND email != ''");
    if (mysqli_num_rows($chk_m) > 0) {
        echo json_encode(['status' => 'error', 'msg' => 'E-mel Pentadbir ini telah didaftarkan untuk akaun lain! Sila gunakan e-mel lain.']);
        exit;
    }

    mysqli_begin_transaction($conn);

    try {
        $pelan = in_array($_POST['pelan'] ?? '', ['Trial', 'Basic', 'Pro', 'Premium'], true) ? $_POST['pelan'] : 'Trial';
        $tempoh_bulan = max(1, min(120, (int)($_POST['tempoh_bulan'] ?? 1)));
        $tarikh_luput = date('Y-m-d', strtotime("+$tempoh_bulan months"));

        $stmt_sch = $conn->prepare("INSERT INTO sekolah (kod_sekolah, nama_sekolah, email_sekolah, no_tel, pelan, tarikh_luput, status, tarikh_daftar) VALUES (?, ?, ?, ?, ?, NULL, 'pending', NOW())");
        $stmt_sch->bind_param("sssss", $kod_sekolah, $nama_sekolah, $email_sekolah, $no_tel, $pelan);
        $stmt_sch->execute();
        $new_sch_id = $conn->insert_id;

        $hashed = password_hash($admin_password, PASSWORD_DEFAULT);
        $role = 'admin';
        $jawatan = 'Guru Besar';
        $stmt_usr = $conn->prepare("INSERT INTO users (school_id, nama_penuh, username, email, password, role, jawatan, no_tel) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_usr->bind_param("isssssss", $new_sch_id, $admin_nama, $admin_username, $admin_email, $hashed, $role, $jawatan, $no_tel);
        $stmt_usr->execute();

        mysqli_commit($conn);

        echo json_encode(['status' => 'success', 'msg' => 'Sekolah baharu dan Akaun Admin telah berjaya didaftarkan!']);
    } catch(Throwable $e) {
        mysqli_rollback($conn);
        error_log('Superadmin add_school failed: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'msg' => 'Sekolah tidak dapat didaftarkan. Sila semak maklumat dan cuba lagi.']);
    }
    exit;
}

// 4. TOGGLE STATUS SEKOLAH (AKTIF / DIGANTUNG)
if ($action === 'toggle_status') {
    $sch_id = (int)($_POST['school_id'] ?? 0);
    $stmt = $conn->prepare('SELECT status, nama_sekolah FROM sekolah WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $sch_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $new_status = ($row['status'] === 'aktif') ? 'digantung' : 'aktif';
        $stmt = $conn->prepare('UPDATE sekolah SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $new_status, $sch_id);
        $stmt->execute();
        $changed = $stmt->affected_rows;
        $stmt->close();
        if ($changed !== 1) {
            json_response(['status' => 'error', 'msg' => 'Status sekolah tidak dapat dikemaskini.'], 409);
        }
        
        $butiran = "Menukar status sekolah {$row['nama_sekolah']} kepada $new_status";
        record_superadmin_audit($conn, 'TOGGLE_STATUS', $butiran);
        
        echo json_encode(['status' => 'success', 'msg' => "Status sekolah {$row['nama_sekolah']} telah ditukar kepada $new_status."]);
    } else {
        json_response(['status' => 'error', 'msg' => 'Sekolah tidak dijumpai.'], 404);
    }
    exit;
}

// 5. EDIT SEKOLAH
if ($action === 'edit_school') {
    $sch_id = (int)($_POST['school_id'] ?? 0);
    $kod = trim((string)($_POST['kod_sekolah'] ?? ''));
    $nama = trim((string)($_POST['nama_sekolah'] ?? ''));
    $email = trim((string)($_POST['email_sekolah'] ?? ''));
    $notel = trim((string)($_POST['no_tel'] ?? ''));
    $alamat = trim((string)($_POST['alamat'] ?? ''));
    $pelanInput = trim($_POST['pelan'] ?? 'Basic');
    $pelan = in_array($pelanInput, ['Trial', 'Basic', 'Pro', 'Premium'], true) ? $pelanInput : 'Basic';
    $tarikh_luput = trim($_POST['tarikh_luput'] ?? '');

    if ($sch_id <= 0 || empty($kod) || empty($nama)) {
        echo json_encode(['status' => 'error', 'msg' => 'Sila isi maklumat wajib sekolah.']);
        exit;
    }
    if (!preg_match('/^[A-Za-z0-9._-]{2,20}$/', $kod) || mb_strlen($nama) > 150 || mb_strlen($notel) > 20 || mb_strlen($alamat) > 1000 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        json_response(['status' => 'error', 'msg' => 'Format atau panjang maklumat sekolah tidak sah.'], 422);
    }
    if ($tarikh_luput !== '' && valid_iso_date($tarikh_luput, '') !== $tarikh_luput) {
        json_response(['status' => 'error', 'msg' => 'Tarikh luput tidak sah.'], 422);
    }

    $targetStmt = $conn->prepare('SELECT id FROM sekolah WHERE id = ? LIMIT 1');
    $targetStmt->bind_param('i', $sch_id);
    $targetStmt->execute();
    $targetExists = $targetStmt->get_result()->num_rows === 1;
    $targetStmt->close();
    if (!$targetExists) {
        json_response(['status' => 'error', 'msg' => 'Sekolah tidak dijumpai.'], 404);
    }

    $expiryValue = $tarikh_luput !== '' ? $tarikh_luput : null;
    $emailValue = $email !== '' ? $email : null;
    $stmt = $conn->prepare('UPDATE sekolah SET kod_sekolah = ?, nama_sekolah = ?, email_sekolah = ?, no_tel = ?, alamat = ?, pelan = ?, tarikh_luput = ? WHERE id = ?');
    $stmt->bind_param('sssssssi', $kod, $nama, $emailValue, $notel, $alamat, $pelan, $expiryValue, $sch_id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'msg' => 'Maklumat sekolah berjaya dikemaskini.']);
    } else {
        error_log('Superadmin edit_school failed: ' . $stmt->error);
        echo json_encode(['status' => 'error', 'msg' => 'Maklumat sekolah tidak dapat dikemaskini.']);
    }
    $stmt->close();
    exit;
}

// 6. ADD ADMIN
if ($action === 'add_admin') {
    $sch_id = (int)($_POST['school_id'] ?? 0);
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama'] ?? ''));
    $username = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($sch_id <= 0 || empty($nama) || empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'msg' => 'Sila isi semua medan bertanda *.']);
        exit;
    }
    if (strlen($password) < 10) {
        json_response(['status' => 'error', 'msg' => 'Kata laluan pentadbir mesti sekurang-kurangnya 10 aksara.'], 422);
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['status' => 'error', 'msg' => 'Alamat e-mel tidak sah.'], 422);
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $emailValue = $email !== '' ? $email : null;
    $role = 'admin';
    $jawatan = 'Guru Besar';
    $stmt = $conn->prepare('INSERT INTO users (school_id, nama_penuh, username, email, password, role, jawatan) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssss', $sch_id, $nama, $username, $emailValue, $hashed, $role, $jawatan);
    try {
        $stmt->execute();
        echo json_encode(['status' => 'success', 'msg' => 'Akaun Admin baharu berjaya dicipta!']);
    } catch (Throwable $e) {
        error_log('Superadmin add_admin failed: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'msg' => 'Akaun pentadbir tidak dapat dicipta. Username atau e-mel mungkin telah digunakan.']);
    }
    $stmt->close();
    exit;
}

// 10. GET PENDING APPROVAL
if ($action === 'get_pending_approval') {
    $sql = "SELECT id, kod_sekolah, nama_sekolah, email_sekolah, no_tel, pelan, tarikh_daftar 
            FROM sekolah 
            WHERE status = 'pending' 
            ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    $schools = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['tarikh_daftar'] = date('d/m/Y', strtotime($row['tarikh_daftar']));
        $schools[] = $row;
    }
    echo json_encode(['status' => 'success', 'schools' => $schools]);
    exit;
}

// 11. GET PENDING PAYMENT
if ($action === 'get_pending_payment') {
    $sql = "SELECT s.id as school_id, s.kod_sekolah, s.nama_sekolah, s.pelan,
            q.id as quotation_id, q.no_rujukan, q.tarikh_terbit, q.tarikh_luput, q.jenis
            FROM sekolah s 
            JOIN sebutharga q ON s.id = q.school_id
            WHERE q.status = 'menunggu bayaran'
            ORDER BY q.id DESC";
    $result = mysqli_query($conn, $sql);
    $quotations = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['tarikh_terbit_fmt'] = date('d/m/Y', strtotime($row['tarikh_terbit']));
        $row['tarikh_luput_fmt'] = date('d/m/Y', strtotime($row['tarikh_luput']));
        $row['is_overdue'] = (strtotime($row['tarikh_luput']) < time());
        $quotations[] = $row;
    }
    echo json_encode(['status' => 'success', 'quotations' => $quotations]);
    exit;
}

// 12. APPROVE REGISTRATION (Generate Quotation & Send Email)
if ($action === 'approve_registration') {
    $sch_id = (int)($_POST['school_id'] ?? 0);
    $tarikh_terbit = date('Y-m-d H:i:s');
    $tarikh_luput = date('Y-m-d H:i:s', strtotime('+14 days'));
    $pdf_path = null;
    mysqli_begin_transaction($conn);
    try {
        $stmt = $conn->prepare("SELECT * FROM sekolah WHERE id = ? AND status = 'pending' FOR UPDATE");
        $stmt->bind_param('i', $sch_id);
        $stmt->execute();
        $sch = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$sch) throw new RuntimeException('Sekolah tidak dijumpai atau telah diproses.');

        $temporaryRef = 'pending-' . bin2hex(random_bytes(16));
        $stmt = $conn->prepare("INSERT INTO sebutharga (school_id, no_rujukan, tarikh_terbit, tarikh_luput, status) VALUES (?, ?, ?, ?, 'menunggu bayaran')");
        $stmt->bind_param("isss", $sch_id, $temporaryRef, $tarikh_terbit, $tarikh_luput);
        $stmt->execute();
        $quote_id = $conn->insert_id;
        $stmt->close();
        $ref_no = 'DRS-QT-' . date('Ym') . '-' . str_pad((string)$quote_id, 6, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare('UPDATE sebutharga SET no_rujukan = ? WHERE id = ?');
        $stmt->bind_param('si', $ref_no, $quote_id);
        $stmt->execute();
        $stmt->close();

        require_once __DIR__ . '/generate_quotation.php';
        $pdf_path = generateQuotationPDF($sch, $ref_no, $tarikh_terbit, $tarikh_luput);
        $stmt = $conn->prepare('UPDATE sebutharga SET pdf_path = ? WHERE id = ?');
        $stmt->bind_param('si', $pdf_path, $quote_id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("UPDATE sekolah SET status = 'menunggu bayaran' WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('i', $sch_id);
        $stmt->execute();
        $stmt->close();
        $butiran = "Meluluskan pendaftaran {$sch['nama_sekolah']} dan menjana sebut harga $ref_no";
        record_superadmin_audit($conn, 'LULUS', $butiran);
        mysqli_commit($conn);

        require_once __DIR__ . '/email_helper.php';
        $sent = false;
        try {
            $sent = !empty($sch['email_sekolah']) && sendQuotationEmail($sch['email_sekolah'], $sch['nama_sekolah'], $pdf_path, $ref_no);
        } catch (Throwable $mailError) {
            error_log('Quotation email failed after approval: ' . $mailError->getMessage());
        }
        echo json_encode(['status' => 'success', 'email_sent' => $sent, 'msg' => $sent ? 'Sebut harga berjaya dihantar ke ' . $sch['nama_sekolah'] : 'Pendaftaran diluluskan dan sebut harga dijana. E-mel belum berjaya dihantar.']);
    } catch(Throwable $e) {
        mysqli_rollback($conn);
        if ($pdf_path && is_file($pdf_path)) @unlink($pdf_path);
        error_log('Approve registration failed: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'msg' => $e instanceof RuntimeException ? $e->getMessage() : 'Pendaftaran tidak dapat diluluskan atau sebut harga gagal dijana.']);
    }
    exit;
}

// 13. REJECT REGISTRATION
if ($action === 'reject_registration') {
    $sch_id = (int)($_POST['school_id'] ?? 0);
    $reason = mysqli_real_escape_string($conn, trim($_POST['reason'] ?? ''));
    
    $res = mysqli_query($conn, "SELECT nama_sekolah, email_sekolah FROM sekolah WHERE id = '$sch_id' AND status = 'pending'");
    if(mysqli_num_rows($res) === 0) {
        echo json_encode(['status' => 'error', 'msg' => 'Sekolah tidak dijumpai atau status bukan pending.']);
        exit;
    }
    $sch = mysqli_fetch_assoc($res);
    
    $update = mysqli_query($conn, "UPDATE sekolah SET status = 'ditolak' WHERE id = '$sch_id'");
    if($update) {
        $butiran = "Menolak pendaftaran {$sch['nama_sekolah']}. Sebab: $reason";
        record_superadmin_audit($conn, 'TOLAK', $butiran);
        
        require_once __DIR__ . '/email_helper.php';
        $sent = false;
        try {
            $sent = !empty($sch['email_sekolah']) && sendRejectionEmail($sch['email_sekolah'], $sch['nama_sekolah'], $reason);
        } catch (Throwable $mailError) {
            error_log('Rejection email failed after status update: ' . $mailError->getMessage());
        }
        echo json_encode([
            'status' => 'success',
            'email_sent' => $sent,
            'msg' => $sent
                ? 'Pendaftaran ditolak dan e-mel pemberitahuan berjaya dihantar.'
                : 'Pendaftaran ditolak, tetapi e-mel pemberitahuan belum berjaya dihantar.',
        ]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Gagal menolak pendaftaran.']);
    }
    exit;
}

// 14. CONFIRM PAYMENT
if ($action === 'confirm_payment') {
    $quote_id = (int)($_POST['quotation_id'] ?? 0);
    $requestedExpiry = trim((string)($_POST['tarikh_luput'] ?? ''));
    if ($requestedExpiry !== '' && valid_iso_date($requestedExpiry, '') !== $requestedExpiry) {
        json_response(['status' => 'error', 'msg' => 'Tarikh luput tidak sah.'], 422);
    }
    $tarikh_luput_baru = $requestedExpiry !== '' ? $requestedExpiry : date('Y-m-d', strtotime('+1 year'));
    mysqli_begin_transaction($conn);
    try {
        $stmt = $conn->prepare("SELECT q.school_id, q.jenis, s.nama_sekolah, s.email_sekolah FROM sebutharga q JOIN sekolah s ON s.id = q.school_id WHERE q.id = ? AND q.status = 'menunggu bayaran' FOR UPDATE");
        $stmt->bind_param('i', $quote_id);
        $stmt->execute();
        $sch = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$sch) throw new RuntimeException('Sebut harga tidak dijumpai atau bayaran telah diproses.');
        $sch_id = (int)$sch['school_id'];

        if($sch['jenis'] === 'Naik Taraf') {
            $stmt = $conn->prepare("UPDATE sekolah SET pelan = 'Pro', status_bayaran = 'Lunas', tarikh_luput = ? WHERE id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE sekolah SET status = 'aktif', tarikh_luput = ?, status_bayaran = 'Lunas' WHERE id = ?");
        }
        $stmt->bind_param('si', $tarikh_luput_baru, $sch_id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("UPDATE sebutharga SET status = 'selesai' WHERE id = ? AND status = 'menunggu bayaran'");
        $stmt->bind_param('i', $quote_id);
        $stmt->execute();
        $stmt->close();
        $butiran = "Mengesahkan bayaran dan mengaktifkan sekolah {$sch['nama_sekolah']} sehingga $tarikh_luput_baru";
        record_superadmin_audit($conn, 'SAH_BAYAR', $butiran);
        mysqli_commit($conn);

        require_once __DIR__ . '/email_helper.php';
        $sent = false;
        try {
            $sent = !empty($sch['email_sekolah']) && sendActivationEmail($sch['email_sekolah'], $sch['nama_sekolah'], $tarikh_luput_baru);
        } catch (Throwable $mailError) {
            error_log('Activation email failed after payment: ' . $mailError->getMessage());
        }
        echo json_encode([
            'status' => 'success',
            'email_sent' => $sent,
            'msg' => $sent
                ? 'Bayaran disahkan, sekolah diaktifkan dan e-mel pemberitahuan berjaya dihantar.'
                : 'Bayaran disahkan dan sekolah diaktifkan, tetapi e-mel pemberitahuan belum berjaya dihantar.',
        ]);
    } catch(Throwable $e) {
        mysqli_rollback($conn);
        error_log('Confirm payment failed: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'msg' => $e instanceof RuntimeException ? $e->getMessage() : 'Bayaran tidak dapat disahkan. Tiada perubahan disimpan.']);
    }
    exit;
}

// 15. GET PENDING UPGRADE
if ($action === 'get_pending_upgrade') {
    $q = "SELECT u.id, u.created_at, s.kod_sekolah, s.nama_sekolah 
          FROM upgrade_requests u 
          JOIN sekolah s ON u.school_id = s.id 
          WHERE u.status = 'pending'
          ORDER BY u.created_at ASC";
    $res = mysqli_query($conn, $q);
    $data = [];
    while($row = mysqli_fetch_assoc($res)) {
        $row['created_at'] = date('d/m/Y H:i', strtotime($row['created_at']));
        $data[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

// 16. APPROVE UPGRADE
if ($action === 'approve_upgrade') {
    $req_id = (int)($_POST['req_id'] ?? 0);
    $tarikh_terbit = date('Y-m-d');
    $tarikh_luput = date('Y-m-d', strtotime('+14 days'));
    $pdf_path = null;
    mysqli_begin_transaction($conn);
    try {
        $stmt = $conn->prepare("SELECT u.school_id, s.nama_sekolah, s.kod_sekolah, s.email_sekolah, s.no_tel, s.tarikh_luput FROM upgrade_requests u JOIN sekolah s ON s.id = u.school_id WHERE u.id = ? AND u.status = 'pending' FOR UPDATE");
        $stmt->bind_param('i', $req_id);
        $stmt->execute();
        $sch = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$sch) throw new RuntimeException('Permintaan tidak wujud atau telah diproses.');
        $sch_id = (int)$sch['school_id'];

        $temporaryRef = 'pending-' . bin2hex(random_bytes(16));
        $jenis = 'Naik Taraf';
        $stmt = $conn->prepare("INSERT INTO sebutharga (school_id, jenis, no_rujukan, tarikh_terbit, tarikh_luput, status) VALUES (?, ?, ?, ?, ?, 'menunggu bayaran')");
        $stmt->bind_param('issss', $sch_id, $jenis, $temporaryRef, $tarikh_terbit, $tarikh_luput);
        $stmt->execute();
        $quote_id = $conn->insert_id;
        $stmt->close();
        $no_rujukan = 'QT-UPG-' . date('Ym') . '-' . str_pad((string)$quote_id, 6, '0', STR_PAD_LEFT);

        require_once __DIR__ . '/generate_quotation.php';
        $pdf_path = generateUpgradeQuotationPDF($sch, $no_rujukan, $tarikh_terbit, $tarikh_luput);
        $stmt = $conn->prepare('UPDATE sebutharga SET no_rujukan = ?, pdf_path = ? WHERE id = ?');
        $stmt->bind_param('ssi', $no_rujukan, $pdf_path, $quote_id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("UPDATE upgrade_requests SET status = 'approved' WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('i', $req_id);
        $stmt->execute();
        $stmt->close();
        $butiran = "Meluluskan permohonan naik taraf sekolah {$sch['nama_sekolah']} dan menjana sebut harga $no_rujukan";
        record_superadmin_audit($conn, 'LULUS_UPG', $butiran);
        mysqli_commit($conn);

        require_once __DIR__ . '/email_helper.php';
        $sent = false;
        try {
            $sent = !empty($sch['email_sekolah']) && sendQuotationEmail($sch['email_sekolah'], $sch['nama_sekolah'], $pdf_path, $no_rujukan);
        } catch (Throwable $mailError) {
            error_log('Upgrade quotation email failed after approval: ' . $mailError->getMessage());
        }
        echo json_encode(['status' => 'success', 'msg' => $sent ? 'Sebut harga naik taraf telah dijana dan dihantar.' : 'Sebut harga naik taraf dijana. E-mel belum berjaya dihantar.']);
    } catch(Throwable $e) {
        mysqli_rollback($conn);
        if ($pdf_path && is_file($pdf_path)) @unlink($pdf_path);
        error_log('Approve upgrade failed: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'msg' => $e instanceof RuntimeException ? $e->getMessage() : 'Permohonan naik taraf tidak dapat diproses.']);
    }
    exit;
}

// 17. GET SMTP SETTINGS
if ($action === 'get_smtp') {
    $res = mysqli_query($conn, "SELECT * FROM sa_smtp_settings WHERE is_active = 1 LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $smtp = mysqli_fetch_assoc($res);
        $smtp['smtp_password'] = !empty($smtp['smtp_password']) ? str_repeat('*', min(strlen($smtp['smtp_password']), 12)) : '';
        echo json_encode(['status' => 'success', 'smtp' => $smtp]);
    } else {
        echo json_encode(['status' => 'success', 'smtp' => [
            'smtp_host' => '', 'smtp_port' => 587, 'smtp_username' => '', 'smtp_password' => '',
            'smtp_from_email' => 'admin@myrfid.edu.my', 'smtp_from_name' => 'SaaS MyRFID Admin', 'smtp_encryption' => 'tls'
        ]]);
    }
    exit;
}

// 18. SAVE SMTP SETTINGS
if ($action === 'save_smtp') {
    $host       = mysqli_real_escape_string($conn, trim($_POST['smtp_host'] ?? ''));
    $port       = (int)($_POST['smtp_port'] ?? 587);
    $username   = mysqli_real_escape_string($conn, trim($_POST['smtp_username'] ?? ''));
    $password   = trim($_POST['smtp_password'] ?? '');
    $from_email = mysqli_real_escape_string($conn, trim($_POST['smtp_from_email'] ?? ''));
    $from_name  = mysqli_real_escape_string($conn, trim($_POST['smtp_from_name'] ?? ''));
    $encryption = in_array($_POST['smtp_encryption'] ?? 'tls', ['tls','ssl','none']) ? $_POST['smtp_encryption'] : 'tls';

    if ($host === '' || strlen($host) > 255 || !preg_match('/^[A-Za-z0-9.-]+$/', $host) || $port < 1 || $port > 65535
        || !filter_var($from_email, FILTER_VALIDATE_EMAIL) || strlen($from_name) > 255 || strlen($username) > 255) {
        json_response(['status' => 'error', 'msg' => 'Tetapan host, port atau alamat pengirim SMTP tidak sah.'], 422);
    }

    $check = mysqli_query($conn, "SELECT id FROM sa_smtp_settings WHERE is_active = 1 LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $id  = (int)$row['id'];
        if (!empty($password) && strpos($password, '*') === false) {
            try {
                $esc_pass = mysqli_real_escape_string($conn, encrypt_secret($password));
            } catch (RuntimeException $e) {
                json_response(['status' => 'error', 'msg' => $e->getMessage()], 500);
            }
            $q = "UPDATE sa_smtp_settings SET smtp_host='$host', smtp_port=$port, smtp_username='$username', smtp_password='$esc_pass', smtp_from_email='$from_email', smtp_from_name='$from_name', smtp_encryption='$encryption' WHERE id=$id";
        } else {
            $q = "UPDATE sa_smtp_settings SET smtp_host='$host', smtp_port=$port, smtp_username='$username', smtp_from_email='$from_email', smtp_from_name='$from_name', smtp_encryption='$encryption' WHERE id=$id";
        }
        mysqli_query($conn, $q);
    } else {
        try {
            $esc_pass = mysqli_real_escape_string($conn, encrypt_secret($password));
        } catch (RuntimeException $e) {
            json_response(['status' => 'error', 'msg' => $e->getMessage()], 500);
        }
        mysqli_query($conn, "INSERT INTO sa_smtp_settings (smtp_host, smtp_port, smtp_username, smtp_password, smtp_from_email, smtp_from_name, smtp_encryption) VALUES ('$host', $port, '$username', '$esc_pass', '$from_email', '$from_name', '$encryption')");
    }
    echo json_encode(['status' => 'success', 'msg' => 'Tetapan SMTP berjaya disimpan!']);
    exit;
}

// 19. TEST SMTP
if ($action === 'test_smtp') {
    $host       = trim($_POST['smtp_host'] ?? '');
    $port       = (int)($_POST['smtp_port'] ?? 587);
    $username   = trim($_POST['smtp_username'] ?? '');
    $password   = trim($_POST['smtp_password'] ?? '');
    $from_email = trim($_POST['smtp_from_email'] ?? '');
    $from_name  = trim($_POST['smtp_from_name'] ?? '');
    $encryption = trim($_POST['smtp_encryption'] ?? 'tls');
    $test_to    = trim($_POST['test_to'] ?? '');
    if (!preg_match('/^[A-Za-z0-9.-]+$/', $host) || $port < 1 || $port > 65535
        || !in_array($encryption, ['tls', 'ssl', 'none'], true)
        || ($from_email !== '' && !filter_var($from_email, FILTER_VALIDATE_EMAIL))
        || !filter_var($test_to, FILTER_VALIDATE_EMAIL)) {
        json_response(['status' => 'error', 'msg' => 'Tetapan ujian SMTP atau alamat penerima tidak sah.'], 422);
    }
    
    if (!empty($password) && strpos($password, '*') !== false) {
        $res = mysqli_query($conn, "SELECT smtp_password FROM sa_smtp_settings WHERE is_active = 1 LIMIT 1");
        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            $password = decrypt_secret((string)$row['smtp_password']);
        }
    }
    
    if (empty($host) || empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'msg' => 'Sila isi semua medan SMTP (Host, Username, Password) dahulu.']);
        exit;
    }
    if (empty($test_to)) {
        echo json_encode(['status' => 'error', 'msg' => 'Sila masukkan e-mel ujian.']);
        exit;
    }

    require_once 'email_helper.php';
    $result = testSmtpConnection($host, $port, $username, $password, $encryption, $from_email ?: 'admin@myrfid.edu.my', $from_name ?: 'SaaS MyRFID', $test_to);
    if ($result['success']) {
        echo json_encode(['status' => 'success', 'msg' => $result['msg']]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => $result['msg']]);
    }
    exit;
}

// 20. RESEND QUOTATION EMAIL
if ($action === 'resend_quotation') {
    $quote_id = (int)($_POST['quote_id'] ?? 0);
    $res = mysqli_query($conn, "SELECT q.no_rujukan, q.pdf_path, q.tarikh_terbit, q.tarikh_luput as q_tarikh_luput, q.jenis as q_jenis, s.* 
                                FROM sebutharga q 
                                JOIN sekolah s ON q.school_id = s.id 
                                WHERE q.id = $quote_id");
    
    if (mysqli_num_rows($res) > 0) {
        $data = mysqli_fetch_assoc($res);
        if (empty($data['email_sekolah'])) {
            echo json_encode(['status' => 'error', 'msg' => 'Sekolah ini tiada e-mel yang sah.']);
            exit;
        }
        
        require_once 'email_helper.php';
        require_once 'generate_quotation.php';
        
        if (!isSmtpConfigured()) {
            echo json_encode(['status' => 'error', 'msg' => 'Sila tetapkan tetapan SMTP terlebih dahulu di bahagian Tetapan SMTP E-mel.']);
            exit;
        }

        if (isset($data['q_jenis']) && $data['q_jenis'] == 'Naik Taraf') {
            $pdf_path = generateUpgradeQuotationPDF($data, $data['no_rujukan'], $data['tarikh_terbit'], $data['q_tarikh_luput']);
        } else {
            $pdf_path = generateQuotationPDF($data, $data['no_rujukan'], $data['tarikh_terbit'], $data['q_tarikh_luput']);
        }
        mysqli_query($conn, "UPDATE sebutharga SET pdf_path = '$pdf_path' WHERE id = '$quote_id'");

        $sent = sendQuotationEmail($data['email_sekolah'], $data['nama_sekolah'], $pdf_path, $data['no_rujukan']);
        if ($sent) {
            echo json_encode(['status' => 'success', 'msg' => 'E-mel sebut harga berjaya dijana dan dihantar semula.']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Gagal menghantar e-mel. Sila semak tetapan SMTP.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Sebut harga tidak dijumpai.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'msg' => 'Tindakan AJAX tidak dikenali.']);
?>