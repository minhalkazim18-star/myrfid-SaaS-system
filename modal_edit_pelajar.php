<div class="modal fade" id="modalEditPelajar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 15px; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
      
      <div class="modal-header" style="background-color: #ffc107; color: #212529; padding: 20px;">
        <h5 class="modal-title" style="font-weight: 700;">
            <i class="fa fa-pencil mr-2"></i> Kemaskini / Ganti Kad
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup" style="opacity: 0.8;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form id="formEditPelajar" onsubmit="return false;">
          <div class="modal-body" style="padding: 30px; background-color: #fff;">
            
            <input type="hidden" name="rfid_lama" id="edit_rfid_lama">

            <div class="form-group mb-4">
              <label style="font-weight: 600; color: #555;">Nama Penuh</label>
              <input type="text" name="nama_penuh" id="edit_nama" class="form-control" required style="height: 50px; border-color: #ced4da;">
            </div>

            <div class="form-group mb-4">
              <label style="font-weight: 600; color: #555;">ID RFID (Klik ikon untuk scan kad baru)</label>
              <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text" id="btn_scan_edit_rfid" 
                            style="background-color: #fff3cd; border-color: #ffc107; cursor: pointer; padding: 0 15px;"
                            title="Klik untuk scan kad baru">
                          <img src="images/touch.png" alt="Scan" style="height: 30px; width: auto;">
                      </span>
                  </div>
                  <input type="text" name="rfid_uid" id="edit_rfid" class="form-control" required 
                         placeholder="Nombor ID akan keluar sini..."
                         style="height: 50px; border-color: #ced4da; background-color: #fff;">
              </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label style="font-weight: 600; color: #555;">Tahun</label>
                        <select name="darjah" id="edit_darjah" class="form-control" required style="height: 50px; border-color: #ced4da;">
                            <option value="1">Tahun 1</option>
                            <option value="2">Tahun 2</option>
                            <option value="3">Tahun 3</option>
                            <option value="4">Tahun 4</option>
                            <option value="5">Tahun 5</option>
                            <option value="6">Tahun 6</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label style="font-weight: 600; color: #555;">Kelas</label>
                        <select name="nama_kelas" id="edit_kelas" class="form-control" required style="height: 50px; border-color: #ced4da;">
                            <option value="">Pilih Kelas</option>
                            <?php
                            $sch_id = (int)($_SESSION['school_id'] ?? 1);
                            $sql_kelas = mysqli_query($conn, "SELECT * FROM kelas WHERE school_id = '$sch_id' ORDER BY nama_kelas ASC");
                            while($row_kelas = mysqli_fetch_assoc($sql_kelas)) {
                                echo "<option value='".$row_kelas['nama_kelas']."'>".$row_kelas['nama_kelas']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="form-group">
                        <label style="font-weight: 600; color: #555;">Status RMT</label>
                        <select name="status" id="edit_status" class="form-control" required style="height: 50px; border-color: #ced4da;">
                            <option value="1">LAYAK (Aktif)</option>
                            <option value="0">TIDAK LAYAK (Nyahaktif)</option>
                        </select>
                        <small class="text-muted">Pelajar yang tidak layak tidak boleh scan makanan.</small>
                    </div>
                </div>
            </div>

          </div>

          <div class="modal-footer" style="border-top: 1px solid #f1f3f5; padding: 20px 30px; background-color: #f8f9fa;">
            <button type="button" class="btn btn-secondary" data-dismiss="modal" style="padding: 10px 25px; font-weight: 600; border-radius: 50px;">Batal</button>
            <button type="submit" name="update_pelajar_btn" class="btn btn-warning" style="padding: 10px 35px; font-weight: 700; border-radius: 50px; color: #212529;">
                Simpan Perubahan
            </button>
          </div>
      </form>

    </div>
  </div>
</div>
