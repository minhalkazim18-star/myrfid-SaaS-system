<?php 
include 'session_auth.php'; 
include 'db_connect.php';

if($_SESSION['role'] != 'admin') {
    require_once 'functions.php';
        popup_and_redirect("Akses Ditolak! Halaman ini hanya untuk Admin.", "dashboard.php", "info");
    exit();
}


if(isset($_POST['btn_reset_log'])){
    require_csrf(false);
    
    $sch_id = current_school_id();
    mysqli_begin_transaction($conn);
    try {
        $stmt = $conn->prepare('DELETE FROM activity_logs WHERE school_id = ?');
        $stmt->bind_param('i', $sch_id);
        $stmt->execute();
        $stmt->close();
        catat_log($conn, (int)$_SESSION['user_id'], (string)$_SESSION['nama_penuh'], 'Telah mengosongkan (RESET) semua rekod audit log.');
        mysqli_commit($conn);
        require_once 'functions.php';
        popup_and_redirect("Berjaya! Semua rekod lama telah dipadam.", "log_aktiviti.php", "success");
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        error_log('Activity log reset failed: ' . $e->getMessage());
        require_once 'functions.php';
        popup_and_redirect('Rekod log tidak dapat direset; tiada perubahan disimpan.', 'log_aktiviti.php', 'error');
    }
}

include 'header.php';
include 'sidebar.php'; 
?>

<div id="content">
   <?php include 'topbar.php'; ?>
   <div class="midde_cont">
      <div class="container-fluid">
         
         <div class="row column_title">
            <div class="col-md-12">
               <div class="page_title"><h2><i class="fa fa-history"></i> Audit Trail (Log Aktiviti)</h2></div>
            </div>
         </div>

         <div class="row">
            <div class="col-md-12">
                <div class="alert alert-warning" role="alert" style="border-left: 5px solid #ffc107;">
                    <i class="fa fa-info-circle mr-2"></i> 
                    Rekod aktiviti disimpan sehingga Admin memilih tindakan reset secara manual.
                </div>
            </div>
         </div>

         <div class="row">
            <div class="col-md-12">
               <div class="white_shd full margin_bottom_30">
                  
                  <div class="full graph_head" style="background: #f8f9fa;">
                     <div class="d-flex justify-content-between align-items-center">
                        <h2>Sejarah Penggunaan Sistem</h2>
                        
                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalResetLog">
                            <i class="fa fa-trash mr-1"></i> Reset Database Log
                        </button>
                     </div>
                  </div>

                  <div class="full padding_infor_info">
                     <div class="table_section">
                        <div class="table-responsive-sm">
                           <table class="table table-bordered table-striped">
                              <thead class="table-dark">
                                 <tr>
                                    <th style="width: 5%">No</th>
                                    <th style="width: 20%">Masa</th>
                                    <th style="width: 25%">Nama Pengguna</th>
                                    <th>Aktiviti</th>
                                 </tr>
                              </thead>
                              <tbody>
                                 <?php
                                 
                                 $sch_id = mysqli_real_escape_string($conn, $_SESSION['school_id'] ?? 1);
                                 $sql = "SELECT * FROM activity_logs WHERE school_id = '$sch_id' ORDER BY masa DESC";
                                 $res = mysqli_query($conn, $sql);
                                 
                                 if(mysqli_num_rows($res) > 0){
                                     $bil = 1;
                                     while($row = mysqli_fetch_assoc($res)) {
                                        $tarikh = date('d/m/Y h:i A', strtotime($row['masa']));
                                        
                                        echo "<tr>";
                                        echo "<td>".$bil++."</td>";
                                        echo "<td><span class='badge bg-secondary text-white' style='font-size: 12px;'>$tarikh</span></td>";
                                        
                                        
                                        $style_nama = ($row['nama_user'] == 'Developer' || $row['nama_user'] == 'Admin') ? "font-weight:bold; color:#007bff;" : "";
                                        
                                        echo "<td style='$style_nama'>".htmlspecialchars($row['nama_user'])."</td>";
                                        echo "<td>".htmlspecialchars($row['aktiviti'])."</td>";
                                        echo "</tr>";
                                     }
                                 } else {
                                     echo "<tr><td colspan='4' class='text-center p-4 text-muted'>Tiada rekod aktiviti dijumpai.</td></tr>";
                                 }
                                 ?>
                              </tbody>
                           </table>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>

      </div>
   </div>
</div>

<!-- Modal Reset Log -->
<div class="modal fade" id="modalResetLog" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 15px; border: none;">
      
      <div class="modal-header" style="background-color: #dc3545; color: white; padding: 20px;">
        <h5 class="modal-title" style="font-weight: 700;">
            <i class="fa fa-exclamation-triangle mr-2"></i> AMARAN KERAS!
        </h5>
        <button type="button" class="btn" data-dismiss="modal" aria-label="Close" 
                style="background: none; border: none; font-size: 1.5rem; color: white; padding: 0; line-height: 1;">
            <i class="fa fa-times"></i>
        </button>
      </div>
      
      <form method="POST">
          <div class="modal-body text-center" style="padding: 40px;">
            <i class="fa fa-database" style="font-size: 4rem; color: #dc3545; margin-bottom: 20px;"></i>
            <h5 class="mb-3" style="font-weight: 600;">Padam SEMUA Rekod Log?</h5>
            <p class="text-muted">Anda akan memadam kesemua rekod sejarah penggunaan sistem.</p>
            <p class="text-danger" style="font-size: 0.9rem;">
                <i class="fa fa-warning mr-1"></i> <b>Tindakan ini tidak boleh dikembalikan!</b>
            </p>
          </div>
          
          <div class="modal-footer" style="background-color: #f8f9fa; padding: 15px 20px;">
            <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 50px; padding: 10px 25px;">
                <i class="fa fa-times mr-1"></i> Batal
            </button>
            <button type="submit" name="btn_reset_log" class="btn btn-danger" style="border-radius: 50px; padding: 10px 25px;">
                <i class="fa fa-trash mr-1"></i> Ya, Reset Semua
            </button>
          </div>
      </form>
      
    </div>
  </div>
</div>

<script src="js/jquery.min.js"></script> 
<script src="js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    $('#sidebarCollapse').on('click', function(){ 
        $('#sidebar').toggleClass('active'); 
        $('#content').toggleClass('active'); 
    });
});
</script>
</body>
</html>
