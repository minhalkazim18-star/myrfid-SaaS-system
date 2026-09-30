<?php
require_once __DIR__ . '/security.php';
app_start_session();
header('Content-Type: application/json');

include 'db_connect.php';
require_active_account($conn, true);

$tarikh_harini = date('Y-m-d');
$sch_id = current_school_id();

$sql_layak = mysqli_query($conn, "SELECT COUNT(*) as total FROM pelajar WHERE status = '1' AND school_id = '$sch_id'");

if (!$sql_layak) {
    error_log('Stats eligible query failed: ' . mysqli_error($conn));
    json_response(['status' => 'error', 'msg' => 'Statistik tidak dapat dimuatkan.'], 500);
    exit;
}

$data_layak = mysqli_fetch_assoc($sql_layak);
$total_layak = (int)$data_layak['total'];


$sql_hadir = "SELECT COUNT(*) as total 
              FROM transaksi_rmt t 
              JOIN pelajar p ON t.rfid_uid = p.rfid_uid AND p.school_id = t.school_id
              WHERE t.tarikh = '$tarikh_harini' 
              AND p.status = '1' AND p.school_id = '$sch_id' AND t.school_id = '$sch_id'";

$result_hadir = mysqli_query($conn, $sql_hadir);


if (!$result_hadir) {
    $sql_hadir = "SELECT COUNT(*) as total FROM transaksi_rmt WHERE tarikh = '$tarikh_harini' AND school_id = '$sch_id'";
    $result_hadir = mysqli_query($conn, $sql_hadir);
}

$data_hadir = mysqli_fetch_assoc($result_hadir);
$total_hadir = (int)$data_hadir['total'];



if($total_layak > 0) {
    $peratus = round(($total_hadir / $total_layak) * 100);
} else {
    $peratus = 0;
}


echo json_encode([
    'layak'   => $total_layak,
    'hadir'   => $total_hadir,
    'peratus' => $peratus
]);
?>
