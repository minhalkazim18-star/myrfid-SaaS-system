<?php 
include 'session_auth.php'; 
include 'db_connect.php';
include 'functions.php';



if($_SESSION['role'] != 'admin'){
    require_once 'functions.php';
        popup_and_redirect("Maaf, hanya Admin boleh akses halaman ini!", "dashboard.php", "error");
    exit();
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
               <div class="page_title"><h2>Pengurusan Kelas</h2></div>
            </div>
         </div>

         <div class="row">
            <div class="col-md-4">
               <div class="white_shd full margin_bottom_30">
                  <div class="full graph_head" style="background: #f8f9fa;">
                     <div class="heading1 margin_0"><h2>Tambah Kelas Baru</h2></div>
                  </div>
                  <div class="full padding_infor_info">
                     <form id="formTambahKelas" onsubmit="return false;">
                        <div class="form-group mb-3">
                           <label style="font-weight: 600;">Nama Kelas</label>
                           <input type="text" name="nama_kelas_baru" class="form-control" required 
                                  placeholder="nama kelas"
                                  maxlength="50"
                                  style="height: 45px;">
                           <small class="text-muted">Maksimum 50 aksara. Nombor dan tanda asas dibenarkan, contohnya “1 Amanah”.</small>
                        </div>
                        <button type="submit" name="btn_tambah_kelas" class="btn btn-success w-100" style="height: 45px;">
                            <i class="fa fa-plus mr-2"></i> Simpan Kelas
                        </button>
                     </form>
                  </div>
               </div>
            </div>

            <div class="col-md-8">
               <div class="white_shd full margin_bottom_30">
                  <div class="full graph_head" style="background: #f8f9fa;">
                     <div class="heading1 margin_0"><h2>Senarai Kelas</h2></div>
                  </div>
                  <div class="table_section padding_infor_info">
                     <div class="table-responsive-sm">
                        <table class="table table-bordered table-striped">
                           <thead class="table-dark">
                              <tr>
                                 <th>No</th>
                                 <th>Nama Kelas</th>
                                 <th class="text-center">Aksi</th>
                              </tr>
                           </thead>
                           <tbody id="table_body_kelas">
                              <tr><td colspan="3" class="text-center">Sedang memuatkan data kelas...</td></tr>
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
<script src="js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4"></script>
<script>
$(document).ready(function(){
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
        $('#content').toggleClass('active');
    });

    function load_kelas_data() {
        $.ajax({
            url: 'ajax_kelas_action.php',
            method: 'POST',
            data: { action: 'list' },
            success: function(data) {
                $('#table_body_kelas').html(data);
            },
            error: function() {
                $('#table_body_kelas').html('<tr><td colspan="3" class="text-danger text-center">Gagal memuat turun data kelas.</td></tr>');
            }
        });
    }

    load_kelas_data();

    // AJAX SUBMIT TAMBAH KELAS
    $('#formTambahKelas').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&action=add';
        $.ajax({
            url: 'ajax_kelas_action.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#formTambahKelas')[0].reset();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berjaya!',
                        text: res.msg,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    load_kelas_data();
                } else {
                    Swal.fire('Ralat!', res.msg, 'error');
                }
            },
            error: function() {
                Swal.fire('Ralat!', 'Gagal menghubungi server.', 'error');
            }
        });
    });

    // AJAX DELETE KELAS VIA SWEETALERT2
    $(document).on('click', '.btn_delete_kelas', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        var nama = $(this).data('nama') || 'Kelas ini';
        
        Swal.fire({
            title: 'Adakah anda pasti?',
            text: "Anda akan memadam kelas: " + nama,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa fa-trash"></i> Ya, Padam',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax_kelas_action.php',
                    method: 'POST',
                    data: { action: 'delete', id: id },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Dipadam!',
                                text: res.msg,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            load_kelas_data();
                        } else {
                            Swal.fire('Ralat!', res.msg, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Ralat!', 'Gagal menghubungi server.', 'error');
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>
