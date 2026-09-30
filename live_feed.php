<?php
require_once __DIR__ . '/security.php';
app_start_session();
include 'db_connect.php';
require_active_account($conn, false);

date_default_timezone_set("Asia/Kuala_Lumpur");
$tarikh_harini = date('Y-m-d');

$schoolId = current_school_id();
$stmt = $conn->prepare('SELECT t.nama_penuh, t.nama_kelas, COALESCE(p.darjah, \'-\') AS darjah, t.waktu AS masa
                        FROM transaksi_rmt t
                        LEFT JOIN pelajar p ON p.rfid_uid = t.rfid_uid AND p.school_id = t.school_id
                        WHERE t.tarikh = ? AND t.school_id = ?
                        ORDER BY t.waktu DESC');
$stmt->bind_param('si', $tarikh_harini, $schoolId);
$stmt->execute();
$result = $stmt->get_result();
$bil = 1;

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $masa_format = date('h:i:s A', strtotime($row['masa']));
        
        echo "<tr>";
        echo "<td>" . $bil++ . "</td>";
        echo "<td><strong>" . escape_html($row['nama_penuh']) . "</strong></td>";
        echo "<td>" . escape_html($row['nama_kelas']) . "</td>";
        echo "<td>Tahun " . escape_html($row['darjah']) . "</td>";
        echo "<td><span class='badge badge-success'>" . $masa_format . "</span></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' class='text-center'>Belum ada rekod kehadiran hari ini.</td></tr>";
}
$stmt->close();
?>
