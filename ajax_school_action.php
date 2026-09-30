<?php
require_once __DIR__ . '/security.php';
app_start_session();
header('Content-Type: application/json');

include 'db_connect.php';
include 'functions.php';

require_active_roles($conn, ['admin'], true);
require_csrf(true);

$action = $_POST['action'] ?? '';
$sch_id = mysqli_real_escape_string($conn, $_SESSION['school_id'] ?? 1);

if ($action === 'request_upgrade') {
    // Check if already pending
    $chk = mysqli_query($conn, "SELECT id FROM upgrade_requests WHERE school_id = '$sch_id' AND status = 'pending'");
    if(mysqli_num_rows($chk) > 0) {
        echo json_encode(['status' => 'error', 'msg' => 'Anda telah membuat permohonan naik taraf sebelum ini. Sila tunggu maklum balas.']);
        exit;
    }

    $q = "INSERT INTO upgrade_requests (school_id, status) VALUES ('$sch_id', 'pending')";
    if(mysqli_query($conn, $q)) {
        if(isset($_SESSION['user_id'])) {
            catat_log($conn, $_SESSION['user_id'], $_SESSION['nama_penuh'], "Memohon Naik Taraf ke Pelan Pro");
        }
        echo json_encode(['status' => 'success', 'msg' => 'Berjaya dihantar']);
    } else {
        error_log('School request failed: ' . mysqli_error($conn));
        echo json_encode(['status' => 'error', 'msg' => 'Permohonan tidak dapat dihantar.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'msg' => 'Aksi tidak sah.']);
?>
