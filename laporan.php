<?php 
include 'session_auth.php'; 
include 'db_connect.php';
include 'header.php';
include 'sidebar.php'; 


$today = date('Y-m-d');
$msg = "";

$tarikh_mula = valid_iso_date($_GET['tarikh_mula'] ?? '', date('Y-m-d'));
$tarikh_akhir = valid_iso_date($_GET['tarikh_akhir'] ?? '', date('Y-m-d'));
$filter_kelas = trim((string)($_GET['kelas'] ?? ''));


if($tarikh_mula > $today) $tarikh_mula = $today;
if($tarikh_akhir > $today) $tarikh_akhir = $today;


$sch_id = current_school_id();
$has_class_filter = $filter_kelas !== '' && $filter_kelas !== 'Semua';
$where = 'tarikh BETWEEN ? AND ? AND school_id = ?';
if ($has_class_filter) $where .= ' AND nama_kelas = ?';

$stmt = $conn->prepare("SELECT * FROM transaksi_rmt WHERE $where ORDER BY tarikh DESC, waktu DESC LIMIT 10");
if ($has_class_filter) $stmt->bind_param('ssis', $tarikh_mula, $tarikh_akhir, $sch_id, $filter_kelas);
else $stmt->bind_param('ssi', $tarikh_mula, $tarikh_akhir, $sch_id);
$stmt->execute();
$result_table = $stmt->get_result();

$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM transaksi_rmt WHERE $where");
if ($has_class_filter) $count_stmt->bind_param('ssis', $tarikh_mula, $tarikh_akhir, $sch_id, $filter_kelas);
else $count_stmt->bind_param('ssi', $tarikh_mula, $tarikh_akhir, $sch_id);
$count_stmt->execute();
$jumlah_rekod = (int)($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);


$chart_stmt = $conn->prepare('SELECT nama_kelas, COUNT(*) AS total FROM transaksi_rmt WHERE tarikh BETWEEN ? AND ? AND school_id = ? GROUP BY nama_kelas');
$chart_stmt->bind_param('ssi', $tarikh_mula, $tarikh_akhir, $sch_id);
$chart_stmt->execute();
$result_chart = $chart_stmt->get_result();

$chart_labels = []; 
$chart_data   = []; 

while($row_c = mysqli_fetch_assoc($result_chart)) {
    $chart_labels[] = $row_c['nama_kelas'];
    $chart_data[]   = $row_c['total'];
}
?>

<div id="content">
   <?php include 'topbar.php'; ?>
   
   <div class="midde_cont">
      <div class="container-fluid">
         
         <div class="print-only">
             </div>

         <div class="row column_title no-print">
            <div class="col-md-12">
               <div class="page_title"><h2>Laporan & Statistik RMT</h2></div>
               <?php if($msg != "") echo $msg; ?>
            </div>
         </div>

         <div class="row no-print">
            <div class="col-md-12">
               <div class="white_shd full margin_bottom_30">
                  <div class="full graph_head">
                     <div class="heading1 margin_0"><h2>Tapis Laporan</h2></div>
                  </div>
                  <div class="full padding_infor_info">
                     <form method="GET" action="laporan.php">
                        <div class="form-row">
                           <div class="col-md-3">
                              <label>Tarikh Mula</label>
                              <input type="date" name="tarikh_mula" class="form-control" 
                                     value="<?php echo $tarikh_mula; ?>" 
                                     max="<?php echo $today; ?>">
                           </div>
                           <div class="col-md-3">
                              <label>Tarikh Akhir</label>
                              <input type="date" name="tarikh_akhir" class="form-control" 
                                     value="<?php echo $tarikh_akhir; ?>" 
                                     max="<?php echo $today; ?>">
                           </div>
                           <div class="col-md-3">
                              <label>Kelas</label>
                              <select name="kelas" class="form-control">
    <option value="Semua">Semua Kelas</option>
    
    <?php
    $sch_id = (int)($_SESSION['school_id'] ?? 1);
    $q_kelas = mysqli_query($conn, "SELECT * FROM kelas WHERE school_id = '$sch_id' ORDER BY nama_kelas ASC");

    while($row_k = mysqli_fetch_assoc($q_kelas)){
        $selected = ($filter_kelas == $row_k['nama_kelas']) ? 'selected' : '';
        echo "<option value='" . escape_html($row_k['nama_kelas']) . "' $selected>" . escape_html($row_k['nama_kelas']) . "</option>";
    }
    ?>
</select>
                           </div>
                           <div class="col-md-3">
                              <label>&nbsp;</label><br>
                              <button type="submit" class="btn btn-primary">
                                  <i class="fa fa-search"></i> Cari
                              </button>
                              
                              <a href="export_pdf.php?<?php echo escape_html(http_build_query(['tarikh_mula' => $tarikh_mula, 'tarikh_akhir' => $tarikh_akhir, 'kelas' => $filter_kelas])); ?>" 
                                 target="_blank" 
                                 class="btn btn-danger">
                                 <i class="fa fa-file-pdf-o"></i> Download PDF
                              </a>
                           </div>
                        </div>
                     </form>
                  </div>
               </div>
            </div>
         </div>

         <div class="row no-print">
             <div class="col-md-6">
                 <div class="white_shd full margin_bottom_30">
                     <div class="full graph_head">
                        <div class="heading1 margin_0"><h2>Analisis Semua Kelas</h2></div>
                     </div>
                     <div class="full padding_infor_info">
                         <div style="height: 300px; display: flex; justify-content: center;">
                             <?php if(count($chart_data) > 0) { ?>
                                <canvas id="rmtChart"></canvas>
                             <?php } else { ?>
                                <p class="text-center mt-5 text-muted">Tiada data untuk dipaparkan dalam graf.</p>
                             <?php } ?>
                         </div>
                     </div>
                 </div>
             </div>

             <div class="col-md-6">
                 <div class="white_shd full margin_bottom_30">
                     <div class="full graph_head">
                        <div class="heading1 margin_0"><h2>Ringkasan Eksekutif</h2></div>
                     </div>
                     <div class="full padding_infor_info">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Jumlah Transaksi Keseluruhan
                                <span class="badge badge-primary badge-pill" style="font-size: 16px;"><?php echo $jumlah_rekod; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Tempoh Laporan
                                <span class="badge badge-secondary badge-pill"><?php echo date('d/m', strtotime($tarikh_mula)) . " - " . date('d/m', strtotime($tarikh_akhir)); ?></span>
                            </li>
                        </ul>
                     </div>
                 </div>
             </div>
         </div>

         <div class="row">
            <div class="col-md-12">
               <div class="white_shd full margin_bottom_30" style="box-shadow: none;"> 
                  <div class="full graph_head no-print">
                     <div class="heading1 margin_0">
                        <h2>Perincian Data Transaksi (10 Terkini)</h2>
                     </div>
                  </div>
                  <div class="full padding_infor_info">
                     <div class="table_section">
                        <div class="table-responsive-sm">
                           <table class="table table-bordered table-striped">
                              <thead class="thead-dark">
                                 <tr>
                                    <th>Bil</th>
                                    <th>Tarikh</th>
                                    <th>Masa</th>
                                    <th>Nama Pelajar</th>
                                    <th>Kelas</th>
                                    <th>Status</th>
                                 </tr>
                              </thead>
                              <tbody>
                                 <?php
                                 
                                 if(mysqli_num_rows($result_table) > 0) {
                                    $bil = 1;
                                    while($row = mysqli_fetch_assoc($result_table)) {
                                       $t_date = date('d-m-Y', strtotime($row['tarikh']));
                                       $t_time = date('h:i A', strtotime($row['waktu']));
                                       echo "<tr>";
                                       echo "<td>" . $bil++ . "</td>";
                                       echo "<td>" . $t_date . "</td>";
                                       echo "<td>" . $t_time . "</td>";
                                       echo "<td>" . htmlspecialchars($row['nama_penuh']) . "</td>";
                                       echo "<td>" . htmlspecialchars($row['nama_kelas']) . "</td>";
                                       echo "<td>BERJAYA</td>";
                                       echo "</tr>";
                                    }
                                 } else {
                                    echo "<tr><td colspan='6' class='text-center'>Tiada rekod.</td></tr>";
                                 }
                                 ?>
                              </tbody>
                           </table>
                           <?php if($jumlah_rekod > 10) { ?>
                               <div class="text-right mt-2">
                                   <small class="text-muted">* Paparan dihadkan kepada 10 rekod terkini. Sila muat turun PDF untuk senarai penuh.</small>
                               </div>
                           <?php } ?>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         
      </div>
   </div>
</div>

<script src="js/jquery.min.js"></script> 
<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/Chart.min.js"></script>

<script>
$(document).ready(function(){
    $('#sidebarCollapse').on('click', function(){ $('#sidebar').toggleClass('active'); $('#content').toggleClass('active'); });
    $('.dropdown-toggle').dropdown();
});
</script>

<script>

    var ctx = document.getElementById('rmtChart');
    if(ctx) {
        var myChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Jumlah Pelajar',
                    data: <?php echo json_encode($chart_data); ?>,
                    backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#dc3545', '#6610f2'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'bottom' }
            }
        });
    }
</script>
</body>
</html>
