<?php 
include 'session_auth.php'; 
include 'db_connect.php';
include 'header.php';
include 'functions.php';
include 'sidebar.php'; 

// SECURITY CHECK
if($_SESSION['role'] != 'admin'){
    require_once 'functions.php';
        popup_and_redirect("Akses disekat!", "dashboard.php", "info");
    exit();
}

$sch_id = (int)($_SESSION['school_id'] ?? 1);


// FETCH DATA MENGIKUT SEKOLAH (TENANT)
$res_mula = mysqli_query($conn, "SELECT nilai FROM tetapan WHERE school_id='$sch_id' AND kunci='waktu_mula'");
$w_mula = (mysqli_num_rows($res_mula) > 0) ? mysqli_fetch_assoc($res_mula)['nilai'] : "08:30";

$res_tamat = mysqli_query($conn, "SELECT nilai FROM tetapan WHERE school_id='$sch_id' AND kunci='waktu_tamat'");
$w_tamat = (mysqli_num_rows($res_tamat) > 0) ? mysqli_fetch_assoc($res_tamat)['nilai'] : "12:00";

$res_kadar = mysqli_query($conn, "SELECT nilai FROM tetapan WHERE school_id='$sch_id' AND kunci='kadar_rmt'");
$kadar_rmt = (mysqli_num_rows($res_kadar) > 0) ? mysqli_fetch_assoc($res_kadar)['nilai'] : "3.50";

$ts_nama_sekolah = $_SESSION['nama_sekolah'] ?? "Sekolah Kebangsaan Latihan Harian";
$ts_logo_sekolah = $_SESSION['logo_sekolah'] ?? "images/default_logo.png";
$ts_alamat_sekolah = '';
$res_sch_ts = mysqli_query($conn, "SELECT nama_sekolah, logo, alamat FROM sekolah WHERE id = '$sch_id' LIMIT 1");
if ($res_sch_ts && mysqli_num_rows($res_sch_ts) > 0) {
    $r_ts = mysqli_fetch_assoc($res_sch_ts);
    if (!empty($r_ts['nama_sekolah'])) $ts_nama_sekolah = $r_ts['nama_sekolah'];
    if (!empty($r_ts['logo'])) $ts_logo_sekolah = $r_ts['logo'];
    $ts_alamat_sekolah = trim((string)($r_ts['alamat'] ?? ''));
}
$res_cfg_ts = mysqli_query($conn, "SELECT kunci, nilai FROM tetapan WHERE school_id='$sch_id' AND kunci IN ('nama_sekolah', 'logo_sekolah')");
if ($res_cfg_ts) {
    while ($c_ts = mysqli_fetch_assoc($res_cfg_ts)) {
        if ($c_ts['kunci'] === 'nama_sekolah' && !empty($c_ts['nilai'])) $ts_nama_sekolah = $c_ts['nilai'];
        if ($c_ts['kunci'] === 'logo_sekolah' && !empty($c_ts['nilai'])) $ts_logo_sekolah = $c_ts['nilai'];
    }
}
if ($ts_logo_sekolah === 'images/logosklh.png' || !file_exists($ts_logo_sekolah)) {
    $ts_logo_sekolah = 'images/default_logo.png';
}
?>

<div id="content">
   <?php include 'topbar.php'; ?>
   <div class="midde_cont">
      <div class="container-fluid">
         <div class="page_title"><h2>Tetapan Sistem</h2></div>

         <div class="row">
            
            <div class="col-md-12">
               <div class="white_shd full margin_bottom_30">
                  <div class="full graph_head" style="background-color: #2563eb; color: white;">
                     <div class="heading1 margin_0"><h2 style="color:white !important;"><i class="fa fa-university"></i> Profil & Logo Sekolah</h2></div>
                  </div>
                  <div class="full padding_infor_info">
                     <form id="formProfilSekolah" enctype="multipart/form-data">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center mb-3 mb-md-0">
                                <img id="current_logo_img" src="<?php echo htmlspecialchars($ts_logo_sekolah); ?>" alt="Logo Sekolah" style="width: 110px; height: 110px; object-fit: cover; border-radius: 50%; border: 4px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 3px; background: #fff;">
                                <div class="mt-2 text-muted" style="font-size: 12px;">Logo Semasa</div>
                            </div>
                            <div class="col-md-9">
                                <div class="form-group mb-3">
                                    <label style="font-weight: 600;">Nama Sekolah / Institusi</label>
                                    <input type="text" name="nama_sekolah" class="form-control" value="<?php echo htmlspecialchars($ts_nama_sekolah); ?>" required placeholder="Contoh: SK KUALA KUANG">
                                </div>
                                <div class="form-group mb-3">
                                    <label style="font-weight: 600;">Muat Naik Logo Baharu <small class="text-muted">(.png, .jpg, .jpeg, .webp)</small></label>
                                    <input type="file" name="logo_sekolah" class="form-control" accept="image/png, image/jpeg, image/webp">
                                    <small class="text-muted">Biarkan kosong jika tidak mahu menukar gambar logo.</small>
                                </div>
                                <div class="form-group mb-3">
                                    <label style="font-weight: 600;">Alamat Sekolah</label>
                                    <textarea name="alamat_sekolah" class="form-control" rows="3" maxlength="1000" placeholder="Alamat rasmi untuk dipaparkan pada laporan"><?php echo escape_html($ts_alamat_sekolah); ?></textarea>
                                </div>
                                <button type="submit" name="simpan_profil_sekolah" class="btn btn-primary mt-2 px-4 py-2" style="background: #2563eb; border: none; font-weight: 600;"><i class="fa fa-save mr-1"></i> Simpan Profil Sekolah</button>
                            </div>
                        </div>
                     </form>
                  </div>
               </div>
            </div>
            
            <div class="col-md-6">
               <div class="white_shd full margin_bottom_30">
                  <div class="full graph_head" style="background-color: #17a2b8; color: white;">
                     <div class="heading1 margin_0"><h2 style="color:white !important;"><i class="fa fa-clock-o"></i> Waktu Operasi RMT</h2></div>
                  </div>
                  <div class="full padding_infor_info">
                     <form id="formWaktuRMT">
                        <div class="row">
                            <div class="col-6">
                                <label>Waktu Mula</label>
                                <input type="time" name="waktu_mula" class="form-control" value="<?php echo $w_mula; ?>">
                            </div>
                            <div class="col-6">
                                <label>Waktu Tamat</label>
                                <input type="time" name="waktu_tamat" class="form-control" value="<?php echo $w_tamat; ?>">
                            </div>
                        </div>
                        <button type="submit" name="simpan_waktu" class="btn btn-success btn-block mt-3 w-100">Kemaskini Waktu</button>
                     </form>
                  </div>
               </div>
            </div>

            <div class="col-md-12">
               <div class="white_shd full margin_bottom_30">
                  <div class="full graph_head" style="background-color:#17324a;color:white;">
                     <div class="heading1 margin_0"><h2 style="color:white!important;"><i class="fa fa-credit-card"></i> Terminal RFID</h2></div>
                  </div>
                  <div class="full padding_infor_info">
                     <p class="text-muted mb-3">Daftar setiap peranti dengan ID dan kunci tersendiri. Kunci hanya dipaparkan sekali semasa dijana.</p>
                     <form id="formTerminal" class="form-row align-items-end mb-4">
                        <div class="col-md-5"><label class="form-label fw-semibold">Nama terminal</label><input class="form-control" name="label" maxlength="100" required placeholder="Contoh: Pembaca RFID Kantin"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">ID terminal</label><input class="form-control" name="device_id" maxlength="64" pattern="[A-Za-z0-9._-]{3,64}" placeholder="Dijana automatik jika kosong"></div>
                        <div class="col-md-3"><button class="btn btn-primary w-100" type="submit"><i class="fa fa-plus"></i> Daftar terminal</button></div>
                     </form>
                     <div id="terminalKey" class="alert alert-success d-none"><strong>Kunci API baharu</strong><div class="input-group mt-2"><input id="terminalKeyValue" class="form-control" style="font-family: monospace;" readonly><div class="input-group-append"><button id="copyTerminalKey" type="button" class="btn btn-outline-success">Salin</button></div></div><small>Simpan dengan selamat. Kunci ini tidak boleh dilihat semula.</small></div>
                     <div class="table-responsive"><table class="table"><thead><tr><th>Terminal</th><th>ID peranti</th><th>Status</th><th>Kali terakhir</th><th class="text-right">Tindakan</th></tr></thead><tbody id="terminalRows"><tr><td colspan="5" class="text-muted text-center py-4">Memuatkan terminal…</td></tr></tbody></table></div>
                     <div class="alert alert-light border mb-0"><strong>Header terminal:</strong> <code>X-RFID-Device-ID</code>, <code>X-RFID-API-Key</code> dan hantar <code>school_id</code> bersama permintaan POST.</div>
                  </div>
               </div>
            </div>


            <div class="col-md-6">
               <div class="white_shd full margin_bottom_30">
                  <div class="full graph_head" style="background-color: #6f42c1; color: white;">
                     <div class="heading1 margin_0"><h2 style="color:white !important;"><i class="fa fa-graduation-cap"></i> Anjakan Kelas / Tahun Baru</h2></div>
                  </div>
                  <div class="full padding_infor_info text-center">
                     <h5 class="text-danger">Warning!</h5>
                     <p>Fungsi ini akan menaikkan darjah pelajar dalam system.</p>
                     <button type="button" class="btn btn-outline-danger" data-toggle="modal" data-target="#modalNaikTahun">
                         <i class="fa fa-exclamation-triangle"></i> Buka Panel Anjakan Tahun
                     </button>
                  </div>
               </div>
            </div>

         </div>
      </div>
   </div>
</div>

<div class="modal fade" id="modalNaikTahun" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 15px; border: none;">
      
      <div class="modal-header" style="background-color: #6f42c1; color: white; padding: 20px;">
        <h5 class="modal-title" style="font-weight: 700;">
            <i class="fa fa-graduation-cap mr-2"></i> Sahkan Tahun Baru
        </h5>
        <button type="button" class="btn" data-dismiss="modal" aria-label="Tutup" 
                style="background: none; border: none; font-size: 1.5rem; color: white; padding: 0; line-height: 1;">
            <i class="fa fa-times"></i>
        </button>
      </div>
      <form id="formNaikTahun">
          <div class="modal-body" style="padding: 30px;">
              <p style="font-size: 1.1rem;">Adakah anda pasti mahu mulakan sesi tahun baru?</p>
              <div class="alert alert-warning mt-3">
                  <i class="fa fa-warning mr-1"></i> Pelajar Darjah 6 akan dinyahaktifkan dan pelajar aktif Darjah 1–5 akan dinaikkan setahun.
              </div>
          </div>
          <div class="modal-footer" style="background-color: #f8f9fa;">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <button type="button" id="btnNaikTahunConfirm" class="btn" style="background-color: #6f42c1; color: white;">
                <i class="fa fa-graduation-cap mr-1"></i> Ya, Mulakan Tahun Baru
            </button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="js/jquery.min.js"></script> 
<script src="js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4"></script>

<script>
$(document).ready(function(){
    function terminalPost(data) {
        return $.ajax({url:'ajax_tetapan_action.php',method:'POST',data:data,dataType:'json'});
    }
    function terminalText(value) { return $('<span>').text(value == null ? '' : String(value)).html(); }
    function loadTerminals() {
        terminalPost({action:'list_devices'}).done(function(res){
            var rows = $('#terminalRows').empty();
            if (!res.devices || !res.devices.length) return rows.append('<tr><td colspan="5" class="text-muted text-center py-4">Belum ada terminal didaftarkan.</td></tr>');
            res.devices.forEach(function(d){
                var active = Number(d.is_active) === 1;
                rows.append('<tr><td><strong>'+terminalText(d.label)+'</strong><br><small class="text-muted">Dicipta '+terminalText(d.created_at)+'</small></td><td><code>'+terminalText(d.device_id)+'</code></td><td><span class="badge '+(active?'badge-success':'badge-secondary')+'">'+(active?'Aktif':'Dibatalkan')+'</span></td><td>'+(d.last_used_at?terminalText(d.last_used_at):'<span class="text-muted">Belum digunakan</span>')+'</td><td class="text-right"><button class="btn btn-sm btn-outline-primary rotate-terminal" data-id="'+Number(d.id)+'">Tukar kunci</button> '+(active?'<button class="btn btn-sm btn-outline-danger revoke-terminal" data-id="'+Number(d.id)+'">Batal akses</button>':'')+'</td></tr>');
            });
        }).fail(function(){ $('#terminalRows').html('<tr><td colspan="5" class="text-danger text-center py-4">Senarai terminal tidak dapat dimuatkan. Jalankan migrasi pangkalan data terlebih dahulu.</td></tr>'); });
    }
    function showTerminalKey(key) { $('#terminalKeyValue').val(key); $('#terminalKey').removeClass('d-none'); }
    $('#formTerminal').on('submit', function(e){e.preventDefault();var data=$(this).serialize()+'&action=create_device';terminalPost(data).done(function(res){showTerminalKey(res.api_key);$('#formTerminal')[0].reset();loadTerminals();}).fail(function(xhr){Swal.fire('Ralat!',(xhr.responseJSON&&xhr.responseJSON.msg)||'Terminal tidak dapat didaftarkan.','error');});});
    $('#copyTerminalKey').on('click', function(){navigator.clipboard.writeText($('#terminalKeyValue').val()).then(function(){Swal.fire({icon:'success',title:'Kunci disalin',timer:1200,showConfirmButton:false});});});
    $(document).on('click','.rotate-terminal',function(){var id=$(this).data('id');Swal.fire({title:'Tukar kunci terminal?',text:'Kunci lama akan berhenti berfungsi serta-merta.',icon:'warning',showCancelButton:true,confirmButtonText:'Ya, tukar'}).then(function(r){if(r.isConfirmed)terminalPost({action:'rotate_device',id:id}).done(function(res){showTerminalKey(res.api_key);loadTerminals();});});});
    $(document).on('click','.revoke-terminal',function(){var id=$(this).data('id');Swal.fire({title:'Batalkan akses terminal?',icon:'warning',showCancelButton:true,confirmButtonText:'Ya, batalkan'}).then(function(r){if(r.isConfirmed)terminalPost({action:'revoke_device',id:id}).done(loadTerminals);});});
    loadTerminals();
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
        $('#content').toggleClass('active');
    });

    // 1. AJAX SUBMIT PROFIL & LOGO SEKOLAH (FormData)
    $('#formProfilSekolah').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'simpan_profil');

        Swal.fire({
            title: 'Menyimpan...',
            text: 'Sila tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: 'ajax_tetapan_action.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    if (res.logo_url) {
                        $('#current_logo_img').attr('src', res.logo_url + '?t=' + new Date().getTime());
                        $('img[alt="Logo Sekolah"]').attr('src', res.logo_url + '?t=' + new Date().getTime());
                    }
                    if (res.nama_sekolah) {
                        $('.school-name-text').text(res.nama_sekolah);
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Berjaya!',
                        text: res.msg,
                        timer: 2500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Ralat!', res.msg, 'error');
                }
            },
            error: function(xhr, status, err) {
                console.error("AJAX Error:", status, err, xhr.responseText);
                var errText = 'Gagal memproses permintaan.';
                if (xhr.responseText) {
                    try {
                        var j = JSON.parse(xhr.responseText);
                        if (j.msg) errText = j.msg;
                    } catch(e) {
                        errText = xhr.responseText.substring(0, 150);
                    }
                }
                Swal.fire('Ralat Server / Database!', errText, 'error');
            }
        });
    });

    // 2. AJAX SUBMIT WAKTU RMT
    $('#formWaktuRMT').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize() + '&action=simpan_waktu';

        $.ajax({
            url: 'ajax_tetapan_action.php',
            method: 'POST',
            data: formData,
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
                } else {
                    Swal.fire('Ralat!', res.msg, 'error');
                }
            },
            error: function(xhr, status, err) {
                console.error("AJAX Error (Waktu):", status, err, xhr.responseText);
                var errText = 'Gagal memproses permintaan.';
                if (xhr.responseText) {
                    try {
                        var j = JSON.parse(xhr.responseText);
                        if (j.msg) errText = j.msg;
                    } catch(e) {
                        errText = xhr.responseText.substring(0, 150);
                    }
                }
                Swal.fire('Ralat Server / Database!', errText, 'error');
            }
        });
    });

    // 3. NAIK TAHUN — Double-confirm dengan SweetAlert (pengguna mesti taip SAHKAN)
    $('#btnNaikTahunConfirm').on('click', function() {
        $('#modalNaikTahun').modal('hide');
        Swal.fire({
            title: '⚠️ Pengesahan Akhir',
            html: '<p style="color:#374151;">Proses ini akan <strong>menyahaktifkan pelajar Darjah 6</strong> dan menaikkan pelajar aktif Darjah 1–5.</p>'
                + '<p style="color:#dc2626; font-weight:bold;">Tindakan ini <u>TIDAK BOLEH DIKEMBALIKAN</u>.</p>'
                + '<p style="margin-top:16px;">Taip <code style="background:#fef2f2; color:#dc2626; padding:2px 8px; border-radius:4px; font-size:1rem;">SAHKAN</code> untuk teruskan:</p>',
            input: 'text',
            inputPlaceholder: 'Taip SAHKAN di sini',
            inputAttributes: { autocomplete: 'off', style: 'text-transform: uppercase; font-size: 1rem; font-weight: bold; text-align: center;' },
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-graduation-cap"></i> Proses Sekarang',
            confirmButtonColor: '#6f42c1',
            cancelButtonText: 'Batal',
            cancelButtonColor: '#6b7280',
            showLoaderOnConfirm: true,
            preConfirm: function(input) {
                if (input.trim().toUpperCase() !== 'SAHKAN') {
                    Swal.showValidationMessage('Sila taip <b>SAHKAN</b> dengan betul untuk teruskan.');
                    return false;
                }
                return $.ajax({
                    url: 'ajax_tetapan_action.php',
                    method: 'POST',
                    data: {
                        action: 'naik_tahun',
                        confirmation: input.trim().toUpperCase(),
                        year: <?php echo (int)date('Y'); ?>
                    },
                    dataType: 'json'
                }).then(function(res) {
                    return res;
                }).fail(function(xhr) {
                    var errText = 'Gagal memproses permintaan.';
                    try { var j = JSON.parse(xhr.responseText); if (j.msg) errText = j.msg; } catch(e) {}
                    Swal.showValidationMessage('Ralat: ' + errText);
                });
            },
            allowOutsideClick: function() { return !Swal.isLoading(); }
        }).then(function(result) {
            if (result.isConfirmed && result.value) {
                var res = result.value;
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berjaya!',
                        text: res.msg,
                        timer: 2500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Ralat!', res.msg, 'error');
                }
            }
        });
    });
});
</script>
</body>
</html>
