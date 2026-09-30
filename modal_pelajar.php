<div class="modal fade" id="modalTambahPelajar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 15px; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
      
      <div class="modal-header" style="background-color: #343a40; color: white; padding: 20px;">
        <h5 class="modal-title" style="font-weight: 600; letter-spacing: 1px;">
            <i class="fa fa-user-plus mr-2"></i> Tambah Pelajar Baru </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup" style="color: white; opacity: 0.8;"><span aria-hidden="true">&times;</span></button>
      </div>

      <form id="formTambahPelajar" onsubmit="return false;">
          <div class="modal-body" style="padding: 30px; background-color: #fff;">
            
            <div class="form-group mb-4">
              <label style="font-weight: 600; color: #555;">Nama Penuh Pelajar</label>
              <div class="input-group">
                  <div class="input-group-prepend"><span class="input-group-text" style="background-color: #f1f3f5; border-color: #ced4da;">
                      <i class="fa fa-user"></i>
                  </span></div>
                  <input type="text" name="nama_penuh" class="form-control" placeholder="nama dalam ic" required 
                         style="height: 50px; border-color: #ced4da;"
                         oninput="this.value = this.value.replace(/[^a-zA-Z\s\.\-\'\@]/g, '')">
              </div>
            </div>

            <div class="form-group mb-4">
              <label style="font-weight: 600; color: #555;">ID RFID</label>
              <div class="input-group">
                  <div class="input-group-prepend"><span class="input-group-text" id="btn_scan_rfid" 
                        style="background-color: #f1f3f5; border-color: #ced4da; cursor: pointer; padding: 0 15px;"
                        title="Klik sini untuk scan kad">
                      <img src="images/touch.png" alt="Scan" style="height: 30px; width: auto;">
                  </span></div>
                  <input type="text" name="rfid_uid" id="rfid_input" class="form-control" 
                         placeholder="Klik 'Touch' & sentuh kad..." required
                         style="height: 50px; border-color: #ced4da; background-color: #fff;">
              </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label style="font-weight: 600; color: #555;">Tahun / Darjah</label>
                    <select name="darjah" class="form-control" required style="height: 50px; border-color: #ced4da;">
                        <option value="">Pilih Tahun...</option>
                        <option value="1">Tahun 1</option>
                        <option value="2">Tahun 2</option>
                        <option value="3">Tahun 3</option>
                        <option value="4">Tahun 4</option>
                        <option value="5">Tahun 5</option>
                        <option value="6">Tahun 6</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label style="font-weight: 600; color: #555;">Nama Kelas</label>
                    <select name="nama_kelas" class="form-control" required style="height: 50px; border-color: #ced4da;">
                        <option value="">Pilih Kelas...</option>
                        <?php
                        $sch_id = (int)($_SESSION['school_id'] ?? 1);
                        $sql_kelas = "SELECT * FROM kelas WHERE school_id = '$sch_id' ORDER BY nama_kelas ASC";
                        $result_kelas = mysqli_query($conn, $sql_kelas);
                        if($result_kelas){
                            while($row_k = mysqli_fetch_assoc($result_kelas)){
                                echo "<option value='".$row_k['nama_kelas']."'>".$row_k['nama_kelas']."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

          </div>

          <div class="modal-footer" style="border-top: 1px solid #f1f3f5; padding: 20px 30px; background-color: #f8f9fa;">
            <button type="button" class="btn btn-secondary" data-dismiss="modal" style="padding: 10px 25px; font-weight: 600; border-radius: 50px;">
                Batal
            </button>
            <button type="submit" name="tambah_pelajar_btn" class="btn btn-success" style="padding: 10px 35px; font-weight: 600; border-radius: 50px; background-color: #28a745; border: none;">
                <i class="fa fa-check-circle mr-1"></i> Simpan </button>
          </div>
      </form>

    </div>
  </div>
</div>
