<?php
require_once __DIR__ . '/security.php';
app_start_session();
error_reporting(E_ALL);
ini_set('display_errors', '0');

include 'db_connect.php';
require_active_account($conn, false);

date_default_timezone_set("Asia/Kuala_Lumpur");
$tarikh_harini = date('Y-m-d');
$sch_id = current_school_id();

$sql = "SELECT * FROM transaksi_rmt WHERE tarikh = '$tarikh_harini' AND school_id = '$sch_id' ORDER BY id DESC LIMIT 20";
$result = mysqli_query($conn, $sql);

if (!$result) {
    error_log('RMT monitor query failed: ' . mysqli_error($conn));
    echo "<tr><td colspan='4' class='text-danger text-center'>Data tidak dapat dimuatkan.</td></tr>";
    exit;
}

if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        // Format masa
        $masa = date('h:i:s A', strtotime($row['waktu']));
        
        
        $nama_pelajar = htmlspecialchars($row['nama_penuh']);
        $nama_kelas = htmlspecialchars($row['nama_kelas']);
        $status = htmlspecialchars($row['status']);

        
        $warna_status = ($row['status'] == 'BERJAYA') ? 'badge-success' : 'badge-danger';
        
        echo "<tr>";
        echo "<td><span class='badge badge-info' style='font-size:14px;'>$masa</span></td>";
        echo "<td style='font-weight:bold; text-transform:uppercase;'>$nama_pelajar</td>";
        echo "<td>$nama_kelas</td>";
        echo "<td><span class='badge $warna_status'>$status</span></td>";
        echo "</tr>";
    }
} else {
    
    echo "<tr><td colspan='4' class='text-center text-muted' style='padding: 20px;'>
            <i class='fa fa-info-circle' style='font-size: 20px;'></i><br>
            Belum ada pelajar scan untuk tarikh: <b>$tarikh_harini</b>
          </td></tr>";
}
?>
