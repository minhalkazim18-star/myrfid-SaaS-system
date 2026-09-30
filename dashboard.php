<?php 
include 'session_auth.php'; 
include 'db_connect.php';
include 'header.php'; 
include 'sidebar.php'; 

$tarikh_harini = date('Y-m-d');
$sch_id = mysqli_real_escape_string($conn, $_SESSION['school_id'] ?? 1);

$sql_layak = mysqli_query($conn, "SELECT COUNT(*) as total FROM pelajar WHERE status = 1 AND school_id = '$sch_id'");
$data_layak = mysqli_fetch_assoc($sql_layak);
$total_layak = $data_layak['total'];

$sql_hadir = mysqli_query($conn, "SELECT COUNT(*) as total FROM transaksi_rmt WHERE tarikh = '$tarikh_harini' AND school_id = '$sch_id'");
$data_hadir = mysqli_fetch_assoc($sql_hadir);
$total_hadir = $data_hadir['total'];

if($total_layak > 0) {
    $peratus = round(($total_hadir / $total_layak) * 100);
} else {
    $peratus = 0;
}

$q_plan = mysqli_query($conn, "SELECT s.pelan, p.had_murid FROM sekolah s LEFT JOIN pelan_struktur p ON s.pelan = p.nama_pelan WHERE s.id = '$sch_id'");
$row_plan = mysqli_fetch_assoc($q_plan);
$current_plan = $row_plan['pelan'];
$had_murid = $row_plan['had_murid'];

$q_upg = mysqli_query($conn, "SELECT status FROM upgrade_requests WHERE school_id = '$sch_id' AND status = 'pending'");
$is_upgrade_pending = mysqli_num_rows($q_upg) > 0;
?>

<div id="content">
   <?php include 'topbar.php'; ?>
   
   <div class="midde_cont">
      <div class="container-fluid">
         
         <div class="row column_title">
            <div class="col-md-12">
               <div class="page_title d-flex align-items-center justify-content-between flex-wrap" style="gap: 12px;">
                  <div>
                     <h2 class="mb-1">Dashboard Kehadiran RMT</h2>
                     <small class="text-muted">Pantau rekod harian dan buka terminal untuk menerima imbasan murid.</small>
                  </div>
                  <a href="kiosk.php" target="_blank" rel="noopener" class="btn btn-primary" style="border-radius: 8px; font-weight: 600; padding: 10px 16px;">
                     <i class="fa fa-id-card-o mr-2" aria-hidden="true"></i>Buka Terminal Imbasan
                  </a>
               </div>
            </div>
         </div>

         <!-- PLAN & LIMIT INFO -->
         <div class="row mb-4">
             <div class="col-md-12">
                 <div class="card shadow-sm border-0" style="border-radius: 10px;">
                     <div class="card-body d-flex justify-content-between align-items-center flex-wrap" style="gap: 1rem;">
                         <div>
                             <h5 class="mb-1">Pelan Semasa: <strong><?php echo $current_plan; ?></strong></h5>
                             <?php if($current_plan === 'Basic' && $had_murid > 0): 
                                 $pct = ($total_layak / $had_murid) * 100;
                                 $bar_color = $pct >= 90 ? 'bg-danger' : 'bg-info';
                                 $text_color = $pct >= 90 ? 'text-danger font-weight-bold' : 'text-muted';
                             ?>
                                 <p class="mb-2 <?php echo $text_color; ?>"><i class="fa fa-info-circle"></i> <?php echo "$total_layak / $had_murid murid didaftarkan"; ?></p>
                                 <div class="progress" style="height: 10px; width: 300px; max-width: 100%;">
                                     <div class="progress-bar <?php echo $bar_color; ?>" role="progressbar" style="width: <?php echo $pct; ?>%;"></div>
                                 </div>
                             <?php else: ?>
                                 <p class="text-muted mb-0"><i class="fa fa-check-circle text-success"></i> Tiada had pendaftaran murid (Unlimited)</p>
                             <?php endif; ?>
                         </div>
                         
                         <?php if($current_plan === 'Basic'): ?>
                             <div>
                                 <?php if($is_upgrade_pending): ?>
                                     <button class="btn btn-secondary px-4 py-2" disabled><i class="fa fa-clock-o mr-2"></i>Permintaan Naik Taraf Sedang Diproses</button>
                                 <?php else: ?>
                                     <button class="btn btn-warning px-4 py-2 font-weight-bold" onclick="requestUpgrade()" style="color:#fff; background:#f59e0b; border:none; border-radius:8px;"><i class="fa fa-arrow-up mr-2"></i>Naik Taraf ke Pro</button>
                                 <?php endif; ?>
                             </div>
                         <?php endif; ?>
                     </div>
                 </div>
             </div>
         </div>

         <div class="row column1">
    
            <div class="col-md-6 col-lg-4">
                <div class="full counter_section margin_bottom_30" style="background: #17a2b8; color: white; padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <div class="couter_icon">
                        <div><i class="fa fa-users fa-3x"></i></div>
                    </div>
                    <div class="counter_no">
                        <div>
                            <p class="total_no" id="stat_layak" style="font-size: 35px; font-weight: bold; margin:0;">0</p>
                            <p class="head_couter" style="margin:0; color: white; font-size: 16px;">Pelajar Layak</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="full counter_section margin_bottom_30" style="background: #28a745; color: white; padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <div class="couter_icon">
                        <div><i class="fa fa-cutlery fa-3x"></i></div>
                    </div>
                    <div class="counter_no">
                        <div>
                            <p class="total_no" id="stat_hadir" style="font-size: 35px; font-weight: bold; margin:0;">0</p>
                            <p class="head_couter" style="margin:0; color: white; font-size:  16px;">Makan Hari Ini</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="full counter_section margin_bottom_30" style="background: #ffc107; color: #333; padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <div class="couter_icon">
                        <div><i class="fa fa-pie-chart fa-3x"></i></div>
                    </div>
                    <div class="counter_no">
                        <div>
                            <p class="total_no" style="font-size: 35px; font-weight: bold; margin:0;">
                                <span id="stat_peratus">0</span>%
                            </p>
                            <p class="head_couter" style="margin:0; color: white; font-size: 16px;">Kadar Kehadiran</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
         <div class="row column1">
             <div class="col-md-12">
                 <div class="white_shd full margin_bottom_30">
                     <div class="full graph_head">
                        <div class="heading1 margin_0"><h2>Senarai Pelajar Mengambil RMT Hari Ini</h2></div>
                        <div class="float-right text-muted">
                            <i class="fa fa-calendar"></i> <?php echo date('d M Y'); ?>
                        </div>
                     </div>
                     <div class="table_section padding_infor_info">
                        <div class="table-responsive-sm">
                           <table class="table table-striped table-hover">
                              <thead class="thead-dark">
                                 <tr>
                                    <th style="width: 20%;">Masa</th>
                                    <th style="width: 40%;">Nama Pelajar</th>
                                    <th style="width: 20%;">Kelas</th>
                                    <th style="width: 20%;">Status</th>
                                 </tr>
                              </thead>
                              <tbody id="live_rmt_feed">
                                 <tr><td colspan="4" class="text-center">Sedang menyambung ke server...</td></tr>
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

<script src="js/jquery.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4"></script>

<script>
function requestUpgrade() {
    Swal.fire({
        title: 'Pengesahan',
        text: 'Adakah anda pasti mahu memohon Naik Taraf ke Pelan Pro? Superadmin akan menghubungi anda dengan sebut harga baharu.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Teruskan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Kita simpan rujukan button jika dipanggil dengan onclick="requestUpgrade(this)" atau event global
            var btn = window.event ? window.event.currentTarget || window.event.srcElement : null;
            var originalHtml = '';
            if (btn) {
                originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i> Memproses...';
                btn.disabled = true;
            }
            
            var fd = new FormData();
            fd.append('action', 'request_upgrade');
            
            fetch('ajax_school_action.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire(
                        'Berjaya!',
                        'Permintaan Naik Taraf telah berjaya dihantar.',
                        'success'
                    ).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire(
                        'Ralat!',
                        data.msg,
                        'error'
                    );
                    if (btn) {
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                }
            }).catch(err => {
                Swal.fire('Ralat!', 'Ralat tidak dijangka.', 'error');
                if (btn) {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            });
        }
    });
}

$(document).ready(function(){

    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
        $('#content').toggleClass('active');
    });
    $('.dropdown-toggle').dropdown();

    function updateDashboard() {
        
        $.ajax({
            url: "ajax_monitor_rmt.php", 
            method: "GET",
            success: function(data){
                $('#live_rmt_feed').html(data);
            }
        });

        $.ajax({
            url: "ajax_stats.php", 
            method: "GET",
            dataType: "json", 
            success: function(stats){
                $('#stat_layak').text(stats.layak);
                $('#stat_hadir').text(stats.hadir);
                $('#stat_peratus').text(stats.peratus);
            },
            error: function(xhr, status, error) {
                console.log("Error Stats: " + error);
            }
        });
    }

    updateDashboard();
    setInterval(updateDashboard, 3000); 
});
</script>

</body>
</html>
