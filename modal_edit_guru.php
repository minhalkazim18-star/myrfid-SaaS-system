<div class="modal fade" id="modalEditGuru" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
      
     <div class="modal-header" style="background-color: #ffc107; color: #212529; padding: 20px;">
    <h5 class="modal-title" style="font-weight: 700;">
        <i class="fa fa-pencil mr-2"></i> Kemaskini Guru
    </h5>
    <button type="button" class="btn" data-dismiss="modal" aria-label="Tutup" 
            style="background: none; border: none; font-size: 1.5rem; color: #212529; padding: 0; line-height: 1;">
        <i class="fa fa-times"></i>
    </button>
</div>
      
      <form id="formEditGuru" onsubmit="return false;">
          <div class="modal-body" style="padding: 30px; background-color: #fff;">
            
            <input type="hidden" name="id_guru" id="edit_id_guru">
            
            <div class="mb-3">
              <label style="font-weight: 600;">Nama Penuh</label>
              <input type="text" name="nama_penuh" id="edit_nama_penuh" class="form-control" required style="height: 50px;">
            </div>
            
            <div class="mb-3">
              <label style="font-weight: 600;">No. IC (Username)</label>
              <input type="text" name="username" id="edit_username" class="form-control" required style="height: 50px;">
            </div>

            <div class="mb-3">
              <label style="font-weight: 600;">Alamat Email</label>
              <input type="email" name="email" id="edit_email" class="form-control" required style="height: 50px;">
            </div>
            
            <div class="mb-3">
              <label style="font-weight: 600;">No. Telefon</label>
              <input type="text" name="no_tel" id="edit_no_tel" class="form-control" required style="height: 50px;">
            </div>
            
            <div class="mb-3">
                <label style="font-weight: 600;">Jawatan</label>
                <select name="jawatan" id="edit_jawatan" class="form-control" style="height: 50px;">
                    <option value="Guru Biasa">Guru Biasa</option>
                    <option value="Guru Besar">Guru Besar</option>
                    <option value="Guru Penolong Kanan">Guru Penolong Kanan</option>
                </select>
            </div>
          </div>
          
          <div class="modal-footer" style="background-color: #f8f9fa;">
            <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 50px;">Batal</button>
            <button type="submit" name="update_guru_btn" class="btn btn-warning" style="border-radius: 50px; padding: 10px 30px;">Simpan Perubahan</button>
          </div>
      </form>
    </div>
  </div>
</div>
