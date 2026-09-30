<?php 
include 'session_auth.php'; 
include 'db_connect.php'; 
include 'header.php'; 
include 'sidebar.php'; 

$role = $_SESSION['role']; 
?>

<div id="content">
   <?php include 'topbar.php'; ?>
   
   <div class="midde_cont">
      <div class="container-fluid">
         
         <div class="row column_title">
            <div class="col-md-12">
               <div class="page_title"><h2>Pengurusan Guru</h2></div>
            </div>
         </div>

         <div class="row">
            <div class="col-md-12">
               <div class="white_shd full margin_bottom_30">
                  
                  <div class="full graph_head" style="background: #f8f9fa;">
   <div class="d-flex justify-content-between align-items-center">
      <h2>Senarai Guru & Staf</h2>
      
      <?php if($role == 'admin' || $role == 'gpk') { ?>
      <button class="btn btn-success" data-toggle="modal" data-target="#modalTambahGuru">
         <i class="fa fa-plus"></i> Tambah Guru Baru
      </button>
      <?php } ?>
   </div>
</div>

                  <div class="full padding_infor_info">
                     <div class="table_section">
                        <div class="table-responsive-sm">
                           <table class="table table-bordered table-striped">
                              <thead class="table-dark"> <tr>
                                    <th>Bil</th>
                                    <th>Nama Penuh</th>
                                    
                                    <?php if($role == 'admin') { ?>
                                        <th>No. IC (Username)</th>
                                    <?php } ?>
                                    
                                    <th>Jawatan</th>
                                    <th>No. Tel</th>
                                    
                                    <?php if($role == 'admin') { ?>
                                        <th class="text-center">Aksi</th>
                                    <?php } ?>
                                 </tr>
                              </thead>
                              <tbody id="table_body_guru">
                                 <tr><td colspan="6" class="text-center">Sedang memuatkan data guru...</td></tr>
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
   
   <?php include 'modal_tambah_guru.php'; ?>
   <?php include 'modal_edit_guru.php'; ?>

   
<script src="js/jquery.min.js"></script> 
<script src="js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4"></script>

<script>
$(document).ready(function(){
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
        $('#content').toggleClass('active');
    });

    function load_guru_data() {
        $.ajax({
            url: 'ajax_guru_action.php',
            method: 'POST',
            data: { action: 'list' },
            success: function(data) {
                $('#table_body_guru').html(data);
            },
            error: function() {
                $('#table_body_guru').html('<tr><td colspan="6" class="text-danger text-center">Gagal memuat turun data guru.</td></tr>');
            }
        });
    }

    load_guru_data();

    $(document).on('click', '.btn_edit_guru', function(){
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var username = $(this).data('username');
        var email = $(this).data('email') || '';
        var tel = $(this).data('tel');
        var jawatan = $(this).data('jawatan');
        
        $('#edit_id_guru').val(id);
        $('#edit_nama_penuh').val(nama);
        $('#edit_username').val(username);
        $('#edit_email').val(email);
        $('#edit_no_tel').val(tel);
        $('#edit_jawatan').val(jawatan);
        
        var myModal = new bootstrap.Modal(document.getElementById('modalEditGuru'));
        myModal.show();
    });

    // AJAX SUBMIT TAMBAH GURU
    $('#formTambahGuru').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&action=add';
        $.ajax({
            url: 'ajax_guru_action.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#modalTambahGuru').modal('hide');
                    $('#formTambahGuru')[0].reset();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berjaya!',
                        text: res.msg,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    load_guru_data();
                } else {
                    Swal.fire('Ralat!', res.msg, 'error');
                }
            },
            error: function() {
                Swal.fire('Ralat!', 'Gagal menghubungi server.', 'error');
            }
        });
    });

    // AJAX SUBMIT KEMASKINI GURU
    $('#formEditGuru').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&action=update';
        $.ajax({
            url: 'ajax_guru_action.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#modalEditGuru').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berjaya!',
                        text: res.msg,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    load_guru_data();
                } else {
                    Swal.fire('Ralat!', res.msg, 'error');
                }
            },
            error: function() {
                Swal.fire('Ralat!', 'Gagal menghubungi server.', 'error');
            }
        });
    });

    // AJAX DELETE GURU VIA SWEETALERT2
    $(document).on('click', '.btn_delete_guru', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        var nama = $(this).data('nama') || 'Guru ini';
        
        Swal.fire({
            title: 'Adakah anda pasti?',
            text: "Anda akan memadam akaun guru: " + nama + " (tindakan tidak boleh dikembalikan!)",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa fa-trash"></i> Ya, Padam',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax_guru_action.php',
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
                            load_guru_data();
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
