<?php 
include 'session_auth.php'; 
include 'db_connect.php';
include 'header.php';
include 'sidebar.php'; 
?>

<div id="content">
   <?php include 'topbar.php'; ?>
   
   <div class="midde_cont">
      <div class="container-fluid">
         
         <div class="row column_title">
            <div class="col-md-12">
               <div class="page_title"><h2>Pengurusan Pelajar</h2></div>
            </div>
         </div>

         <div class="row">
            <div class="col-md-12">
               <div class="white_shd full margin_bottom_30">
                  
                  <div class="full graph_head" style="background-color: #e0e0e0; border-bottom: 1px solid #999;">
                     <div class="heading1 margin_0"><h2 style="color: #333;">Pelajar</h2></div>
                  </div>

                  <div class="full padding_infor_info">
                     
                     <div class="row mb-4"> 
                        <div class="col-md-6">
                           <form>
                              <div class="form-group row mb-2"> 
                                 <label class="col-sm-2 col-form-label" style="font-weight: 500;">Tahun</label>
                                 <div class="col-sm-5">
                                    <select id="filter_tahun" class="form-control" style="border: 1px solid #333; height: 35px;">
                                       <option value="">Semua Tahun</option>
                                       <option value="1">Tahun 1</option>
                                       <option value="2">Tahun 2</option>
                                       <option value="3">Tahun 3</option>
                                       <option value="4">Tahun 4</option>
                                       <option value="5">Tahun 5</option>
                                       <option value="6">Tahun 6</option>
                                    </select>
                                 </div>
                              </div>
                              <div class="form-group row">
                                 <label class="col-sm-2 col-form-label" style="font-weight: 500;">Kelas</label>
                                 <div class="col-sm-5">
                                    <select id="filter_kelas" class="form-control" style="border: 1px solid #333; height: 35px;">
                                       <option value="">Semua Kelas</option>
                                       <?php
                                       $sch_id = (int)($_SESSION['school_id'] ?? 1);
                                       $q_kelas = mysqli_query($conn, "SELECT * FROM kelas WHERE school_id = '$sch_id' ORDER BY nama_kelas ASC");
                                       if($q_kelas){
                                           while($rk = mysqli_fetch_assoc($q_kelas)){
                                               echo "<option value='".$rk['nama_kelas']."'>".$rk['nama_kelas']."</option>";
                                           }
                                       }
                                       ?>
                                    </select>
                                 </div>
                              </div>
                           </form>
                        </div>

                        <div class="col-md-6">
                           <div class="d-flex justify-content-end align-items-center" style="margin-top: 10px; gap: .5rem;">
                              <label style="font-weight: 500; margin-right: 5px;">Cari</label>
                              <input type="text" id="search_input" class="form-control" style="border: 1px solid #333; width: 200px; height: 35px;" placeholder="Nama / RFID">
                              
                              <button class="btn btn-primary ml-2" data-toggle="modal" data-target="#modalTambahPelajar" style="height: 35px;">
                                  <i class="fa fa-plus"></i> Tambah
                              </button>
                              <button class="btn btn-success ml-2" data-toggle="modal" data-target="#modalImportCSV" style="height: 35px;">
                                  <i class="fa fa-file-excel-o"></i> Import CSV
                              </button>
                           </div>
                        </div>
                     </div>

                     <div class="table_section">
                        <div class="table-responsive-sm">
                           <table class="table table-bordered table-striped">
                              <thead class="table-dark"> 
                                 <tr>
                                    <th style="width: 5%;">Bil</th>
                                    <th style="width: 35%;">Nama</th>
                                    <th style="width: 20%;">ID RFID</th>
                                    <th style="width: 15%;">Kelas</th>
                                    <th style="width: 10%;">Tahun</th>
                                    <th style="width: 10%; text-align: center;">Aksi</th>
                                 </tr>
                              </thead>
                              <tbody id="table_body_pelajar">
                                 <tr><td colspan="6" class="text-center" style="color: #999;">Sedang memuatkan data...</td></tr>
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
   
   <?php include 'modal_pelajar.php'; ?>
   <?php include 'modal_edit_pelajar.php'; ?>



   
   <div class="modal fade" id="modalImportCSV" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; border: none;">
          
          <div class="modal-header" style="background-color: #28a745; color: white; padding: 20px;">
            <h5 class="modal-title" style="font-weight: 700;">
                <i class="fa fa-upload mr-2"></i> Import Data Pelajar
            </h5>
            <button type="button" class="btn" data-dismiss="modal" aria-label="Close" 
                    style="background: none; border: none; font-size: 1.5rem; color: white; padding: 0; line-height: 1;">
                <i class="fa fa-times"></i>
            </button>
          </div>
          
          <form method="POST" action="process_import.php" enctype="multipart/form-data">
              <div class="modal-body" style="padding: 30px;">
                <div class="alert alert-info">
                    <small><b>Format Fail CSV:</b><br>
                    Col 1: RFID, Col 2: Nama, Col 3: Darjah, Col 4: Kelas</small>
                </div>
                <div class="mb-3"> 
                  <label class="form-label" style="font-weight: 600;">Pilih Fail CSV (.csv)</label>
                  <input type="file" name="file_pelajar" class="form-control" accept=".csv" required style="height: 45px;">
                </div>
              </div>
              <div class="modal-footer" style="background-color: #f8f9fa;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 50px; padding: 10px 25px;">Batal</button>
                <button type="submit" name="btn_import" class="btn btn-success" style="border-radius: 50px; padding: 10px 25px;">
                    <i class="fa fa-upload mr-1"></i> Upload & Import
                </button>
              </div>
          </form>
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

    var currentPage = 1;

    function filterData(page) {
        if (page === undefined || page === null) {
            page = 1;
        }
        currentPage = page;

        var tahun = $('#filter_tahun').val();
        var kelas = $('#filter_kelas').val();
        var keyword = $('#search_input').val();

        $('#table_body_pelajar').html('<tr><td colspan="6" class="text-center">Sedang menghubungi database...</td></tr>');

        $.ajax({
            url: "ajax_pelajar.php",
            method: "POST",
            data: {tahun: tahun, kelas: kelas, keyword: keyword, page: page},
            success: function(data){
                $('#table_body_pelajar').html(data);
            },
            error: function(xhr, status, error) {
                var msg = "Gagal Panggil ajax_pelajar.php!\nStatus: " + status + "\nError: " + error;
                $('#table_body_pelajar').html('<tr><td colspan="6" class="text-danger text-center">Tiada data dijumpai.</td></tr>');
            }
        });
    }

    filterData(1);

    $('#filter_tahun, #filter_kelas').change(function(){
        filterData(1);
    });
    
    $('#search_input').on('keyup', function(){
        filterData(1);
    });

    $(document).on('click', '.pagination_link', function(){
        var page = $(this).attr("id");
        filterData(page);
    });

    $('#btn_scan_rfid').click(function(){
        $('#rfid_input').val('');
        $('#rfid_input').focus();
        $('#rfid_input').css('border', '2px solid #28a745'); 
        $('#rfid_input').attr('placeholder', 'Sedia... Sila sentuh kad pada reader sekarang!');
    });

    $('#rfid_input').on('blur focusout', function(){
        $(this).css('border', '1px solid #ccc');
    });

    $('#rfid_input').on('keypress', function(e) {
        if(e.which == 13) { 
            e.preventDefault(); 
            $('select[name="darjah"]').focus(); 
        }
    });

    $(document).on('click', '.btn_edit_modal', function(){
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var darjah = $(this).data('darjah');
        var kelas = $(this).data('kelas');
        var status = $(this).data('status');

        $('#edit_rfid_lama').val(id);
        $('#edit_rfid').val(id);
        $('#edit_nama').val(nama);
        $('#edit_darjah').val(darjah);
        $('#edit_kelas').val(kelas);
        $('#edit_status').val(status);

        var myModal = new bootstrap.Modal(document.getElementById('modalEditPelajar'));
        myModal.show();
    });

    $('#btn_scan_edit_rfid').click(function(){
        $('#edit_rfid').val('');
        $('#edit_rfid').focus();
        $('#edit_rfid').css('border', '2px solid #ffc107'); 
        $('#edit_rfid').attr('placeholder', 'Sedia... Sentuh kad BARU sekarang!');
    });

    $('#edit_rfid').on('blur focusout', function(){
        $(this).css('border', '1px solid #ced4da');
    });

    $('#edit_rfid').on('keypress', function(e) {
        if(e.which == 13) { e.preventDefault(); }
    });

    // AJAX SUBMIT TAMBAH PELAJAR
    $('#formTambahPelajar').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&action=add';
        $.ajax({
            url: 'ajax_pelajar_action.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#modalTambahPelajar').modal('hide');
                    $('#formTambahPelajar')[0].reset();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berjaya!',
                        text: res.msg,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    filterData(1);
                } else {
                    Swal.fire('Ralat!', res.msg, 'error');
                }
            },
            error: function() {
                Swal.fire('Ralat!', 'Gagal menghubungi server.', 'error');
            }
        });
    });

    // AJAX SUBMIT KEMASKINI PELAJAR
    $('#formEditPelajar').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&action=update';
        $.ajax({
            url: 'ajax_pelajar_action.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#modalEditPelajar').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berjaya!',
                        text: res.msg,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    filterData(currentPage);
                } else {
                    Swal.fire('Ralat!', res.msg, 'error');
                }
            },
            error: function() {
                Swal.fire('Ralat!', 'Gagal menghubungi server.', 'error');
            }
        });
    });

    // Nyahaktif Pelajar via AJAX & SweetAlert2
    $(document).on('click', '.btn_delete_modal', function(e){
        e.preventDefault();
        var id = $(this).data('id');
        var nama = $(this).data('nama') || 'Pelajar ini';
        
        Swal.fire({
            title: 'Nyahaktifkan Pelajar?',
            text: nama + " (RFID: " + id + ") akan dinyahaktifkan (status Tidak Layak).",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff9800',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fa fa-ban"></i> Ya, Nyahaktifkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax_pelajar_action.php',
                    method: 'POST',
                    data: { action: 'delete', id: id },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berjaya!',
                                text: res.msg,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            filterData(currentPage);
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
