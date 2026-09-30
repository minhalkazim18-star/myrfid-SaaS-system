<?php
include 'session_auth.php';
include 'db_connect.php';


$tarikh_mula  = valid_iso_date($_GET['tarikh_mula'] ?? '', date('Y-m-d'));
$tarikh_akhir = valid_iso_date($_GET['tarikh_akhir'] ?? '', date('Y-m-d'));
$filter_kelas = trim((string)($_GET['kelas'] ?? 'Semua'));


$rpt_sch_id = (int)($_SESSION['school_id'] ?? 1);
$sql = 'SELECT * FROM transaksi_rmt WHERE school_id = ? AND tarikh BETWEEN ? AND ?';
$has_class_filter = $filter_kelas !== '' && $filter_kelas !== 'Semua';
if ($has_class_filter) $sql .= ' AND nama_kelas = ?';
$sql .= ' ORDER BY tarikh ASC, waktu ASC';
$stmt = $conn->prepare($sql);
if ($has_class_filter) $stmt->bind_param('isss', $rpt_sch_id, $tarikh_mula, $tarikh_akhir, $filter_kelas);
else $stmt->bind_param('iss', $rpt_sch_id, $tarikh_mula, $tarikh_akhir);
$stmt->execute();
$result = $stmt->get_result();
$jumlah_rekod = mysqli_num_rows($result);

$rpt_nama_sekolah = $_SESSION['nama_sekolah'] ?? 'Sekolah';
$rpt_logo_sekolah = $_SESSION['logo_sekolah'] ?? "images/default_logo.png";
$rpt_alamat_sekolah = '';
if (isset($conn) && $rpt_sch_id > 0) {
    $schoolStmt = $conn->prepare('SELECT nama_sekolah, logo, alamat FROM sekolah WHERE id = ? LIMIT 1');
    $schoolStmt->bind_param('i', $rpt_sch_id);
    $schoolStmt->execute();
    $row_rpt = $schoolStmt->get_result()->fetch_assoc();
    $schoolStmt->close();
    if ($row_rpt) {
        if (!empty($row_rpt['nama_sekolah'])) $rpt_nama_sekolah = $row_rpt['nama_sekolah'];
        if (!empty($row_rpt['logo'])) $rpt_logo_sekolah = $row_rpt['logo'];
        if (!empty($row_rpt['alamat'])) $rpt_alamat_sekolah = $row_rpt['alamat'];
    }
    $settingStmt = $conn->prepare("SELECT kunci, nilai FROM tetapan WHERE school_id = ? AND kunci IN ('nama_sekolah', 'logo_sekolah')");
    $settingStmt->bind_param('i', $rpt_sch_id);
    $settingStmt->execute();
    $res_cfg_rpt = $settingStmt->get_result();
    while ($cfg_rpt = $res_cfg_rpt->fetch_assoc()) {
            if ($cfg_rpt['kunci'] === 'nama_sekolah' && !empty($cfg_rpt['nilai'])) $rpt_nama_sekolah = $cfg_rpt['nilai'];
            if ($cfg_rpt['kunci'] === 'logo_sekolah' && !empty($cfg_rpt['nilai'])) $rpt_logo_sekolah = $cfg_rpt['nilai'];
    }
    $settingStmt->close();
}
if ($rpt_logo_sekolah === 'images/logosklh.png' || !file_exists($rpt_logo_sekolah)) {
    $rpt_logo_sekolah = 'images/default_logo.png';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Kehadiran RMT</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .container { width: 100%; max-width: 800px; margin: auto; }
        
        /* Header Surat */
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid black; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 2px; font-size: 11px; }
        
        /* Table Style */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; text-align: center; }
        .text-center { text-align: center; }
        
        /* Signature */
        .footer-sign { margin-top: 50px; display: flex; justify-content: space-between; }
        .sign-box { width: 40%; text-align: center; }
        .line { border-bottom: 1px solid black; margin-top: 40px; margin-bottom: 5px; }

        /* Butang Print (Hilang bila print) */
        .btn-print { 
            background: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer; 
            margin-bottom: 20px; font-size: 14px; border-radius: 5px;
        }
        @media print {
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

<center>
    <button onclick="window.print()" class="btn-print">🖨️ KLIK SINI UNTUK PRINT</button>
</center>

<div class="container">
    
    <div class="header">
        <img src="<?php echo htmlspecialchars($rpt_logo_sekolah); ?>" style="height: 60px;">
        <h2><?php echo htmlspecialchars(strtoupper($rpt_nama_sekolah)); ?></h2>
        <?php if ($rpt_alamat_sekolah !== ''): ?>
        <p><?php echo nl2br(escape_html($rpt_alamat_sekolah)); ?></p>
        <?php endif; ?>
        <p>Laporan Kehadiran Rancangan Makanan Tambahan (RMT)</p>
    </div>

    <table style="border: none; margin-bottom: 10px;">
        <tr style="border: none;">
            <td style="border: none; width: 15%;"><strong>Tarikh:</strong></td>
            <td style="border: none;"><?php echo date('d/m/Y', strtotime($tarikh_mula)) . " - " . date('d/m/Y', strtotime($tarikh_akhir)); ?></td>
            <td style="border: none; width: 15%;"><strong>Kelas:</strong></td>
            <td style="border: none;"><?php echo escape_html($filter_kelas); ?></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="5%">Bil</th>
                <th width="15%">Tarikh</th>
                <th width="15%">Masa</th>
                <th>Nama Pelajar</th>
                <th width="15%">Kelas</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if($jumlah_rekod > 0){
                $bil = 1;
                while($row = mysqli_fetch_assoc($result)){
                    echo "<tr>";
                    echo "<td class='text-center'>".$bil++."</td>";
                    echo "<td class='text-center'>".date('d/m/Y', strtotime($row['tarikh']))."</td>";
                    echo "<td class='text-center'>".date('h:i A', strtotime($row['waktu']))."</td>";
                    echo "<td>".escape_html(strtoupper($row['nama_penuh']))."</td>";
                    echo "<td class='text-center'>".escape_html($row['nama_kelas'])."</td>";
                    echo "<td class='text-center'>Hadir</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='text-center'>Tiada Rekod Dijumpai</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <p style="text-align: right; font-weight: bold; margin-top: 10px;">Jumlah Kehadiran: <?php echo $jumlah_rekod; ?> Orang</p>

    <div class="footer-sign">
        <div class="sign-box">
            <p>Disediakan Oleh:</p>
            <div class="line"></div>
            <p>( <?php echo escape_html(strtoupper($_SESSION['nama_penuh'] ?? '')); ?> )</p>
            <p>Guru Penyelaras RMT</p>
        </div>
        <div class="sign-box">
            <p>Disahkan Oleh:</p>
            <div class="line"></div>
            <p>( GURU BESAR )</p>
            <p><?php echo escape_html($rpt_nama_sekolah); ?></p>
        </div>
    </div>

</div>

</body>
</html>
