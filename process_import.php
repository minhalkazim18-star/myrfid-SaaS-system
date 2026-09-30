<?php
require_once __DIR__ . '/security.php';
app_start_session();
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

require_active_roles($conn, ['admin', 'gpk'], false);
require_csrf(false);

if (!isset($_POST['btn_import'])) {
    header('Location: pelajar.php');
    exit;
}

$file = $_FILES['file_pelajar'] ?? null;
if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    popup_and_redirect('Fail CSV tidak berjaya dimuat naik.', 'pelajar.php', 'error');
}
if ((int)($file['size'] ?? 0) <= 0 || (int)$file['size'] > 5 * 1024 * 1024) {
    popup_and_redirect('Fail CSV mesti tidak melebihi 5MB.', 'pelajar.php', 'error');
}
if (strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'csv') {
    popup_and_redirect('Sila muat naik fail berformat CSV sahaja.', 'pelajar.php', 'error');
}

$handle = fopen((string)$file['tmp_name'], 'rb');
if ($handle === false) {
    popup_and_redirect('Fail CSV tidak dapat dibaca.', 'pelajar.php', 'error');
}

$schoolId = current_school_id();
$rows = [];
$seen = [];
$duplicates = 0;
$invalid = 0;
$line = 0;
fgetcsv($handle);

$validClasses = [];
$classStmt = $conn->prepare('SELECT nama_kelas FROM kelas WHERE school_id = ?');
$classStmt->bind_param('i', $schoolId);
$classStmt->execute();
$classResult = $classStmt->get_result();
while ($classRow = $classResult->fetch_assoc()) {
    $validClasses[(string)$classRow['nama_kelas']] = true;
}
$classStmt->close();

$duplicateStmt = $conn->prepare('SELECT id FROM pelajar WHERE school_id = ? AND rfid_uid = ? LIMIT 1');
while (($data = fgetcsv($handle)) !== false) {
    $line++;
    if ($line > 10000) {
        fclose($handle);
        popup_and_redirect('Fail CSV melebihi had 10,000 baris.', 'pelajar.php', 'error');
    }

    $rfid = trim((string)($data[0] ?? ''));
    $name = trim((string)($data[1] ?? ''));
    $year = trim((string)($data[2] ?? ''));
    $class = trim((string)($data[3] ?? ''));
    if ($rfid === '' && $name === '') continue;
    if (!preg_match('/^[A-Za-z0-9:_-]{1,50}$/', $rfid) || $name === '' || !in_array($year, ['1', '2', '3', '4', '5', '6'], true) || !isset($validClasses[$class])) {
        $invalid++;
        continue;
    }
    if (isset($seen[$rfid])) {
        $duplicates++;
        continue;
    }

    $duplicateStmt->bind_param('is', $schoolId, $rfid);
    $duplicateStmt->execute();
    if ($duplicateStmt->get_result()->num_rows > 0) {
        $duplicates++;
        continue;
    }
    $seen[$rfid] = true;
    $rows[] = [$rfid, $name, $year, $class];
}
fclose($handle);
$duplicateStmt->close();

$inserted = 0;
mysqli_begin_transaction($conn);
$limitStmt = $conn->prepare('SELECT p.had_murid, (SELECT COUNT(*) FROM pelajar WHERE school_id = ?) AS current_count FROM sekolah s LEFT JOIN pelan_struktur p ON s.pelan = p.nama_pelan WHERE s.id = ? FOR UPDATE');
$limitStmt->bind_param('ii', $schoolId, $schoolId);
$limitStmt->execute();
$limit = $limitStmt->get_result()->fetch_assoc() ?: [];
$limitStmt->close();
$studentLimit = isset($limit['had_murid']) ? (int)$limit['had_murid'] : null;
$currentCount = (int)($limit['current_count'] ?? 0);
if ($studentLimit !== null && $studentLimit > 0 && $currentCount + count($rows) > $studentLimit) {
    mysqli_rollback($conn);
    popup_and_redirect("Import melebihi kuota pelan ($studentLimit murid). Kurangkan data atau naik taraf pelan.", 'pelajar.php', 'warning');
}

try {
    $status = 1;
    $insertStmt = $conn->prepare('INSERT INTO pelajar (school_id, rfid_uid, nama_penuh, darjah, nama_kelas, status) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($rows as [$rfid, $name, $year, $class]) {
        $insertStmt->bind_param('issssi', $schoolId, $rfid, $name, $year, $class, $status);
        $insertStmt->execute();
        $inserted++;
    }
    $insertStmt->close();
    catat_log($conn, (int)$_SESSION['user_id'], (string)$_SESSION['nama_penuh'], "Import CSV pelajar: $inserted berjaya, $duplicates duplikat, $invalid tidak sah.");
    mysqli_commit($conn);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log('CSV student import failed: ' . $e->getMessage());
    popup_and_redirect('Import gagal dan tiada rekod baharu disimpan.', 'pelajar.php', 'error');
}

popup_and_redirect("Import selesai. Berjaya: $inserted, duplikat: $duplicates, tidak sah: $invalid.", 'pelajar.php', 'success');
