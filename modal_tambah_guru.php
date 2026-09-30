<div class="modal fade" id="modalTambahGuru" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 10px; border: none;">
      
      <div class="modal-header" style="background-color: #343a40; color: white;">
        <h5 class="modal-title">
            <i class="fa fa-briefcase mr-2"></i> TAMBAH GURU BARU
        </h5>
        <button type="button" class="btn" data-dismiss="modal" aria-label="Tutup" 
                style="background: none; border: none; font-size: 1.5rem; color: white; padding: 0; line-height: 1;">
            <i class="fa fa-times"></i>
        </button>
      </div>
      
      <form id="formTambahGuru" onsubmit="return false;">
          <div class="modal-body" style="padding: 25px;">
            
            <div class="form-group mb-3">
              <label style="font-weight: 600;">Nama Penuh</label>
              <input type="text" name="nama_penuh" class="form-control" placeholder="" required 
                     style="height: 45px;"
                     oninput="this.value = this.value.replace(/[^a-zA-Z\s\.\@]/g, '')">
            </div>
            
            <div class="form-group mb-3">
              <label style="font-weight: 600;">No. Kad Pengenalan</label>
              <input type="text" name="username" class="form-control" placeholder="" required 
                     maxlength="12" 
                     style="height: 45px;"
                     oninput="this.value = this.value.replace(/[^0-9]/g, '')">
              <small class="text-muted">Mesti 12 digit nombor sahaja.</small>
            </div>

            <div class="form-group mb-3">
              <label style="font-weight: 600;">Alamat Email</label>
              <input type="email" name="email" class="form-control" placeholder="" required 
                     style="height: 45px;">
            </div>
            
            <div class="form-group mb-3">
              <label style="font-weight: 600;">Kata Laluan Sementara</label>
              <input type="password" name="password" class="form-control" required minlength="10"
                     autocomplete="new-password" placeholder="Minimum 10 aksara" style="height: 45px;">
              <small class="text-muted">Berikan kata laluan ini kepada guru melalui saluran yang selamat.</small>
            </div>
            
            <div class="form-group mb-3">
              <label style="font-weight: 600;">No. Telefon</label>
              <input type="text" name="no_tel" class="form-control" placeholder="012-3456789" required 
                     maxlength="13"
                     style="height: 45px;"
                     oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            
            <div class="form-group mb-3">
                <label style="font-weight: 600;">Jawatan</label>
                <select name="jawatan" class="form-control" style="height: 45px;">
                    <option value="Guru Biasa">Guru Biasa</option>
                    <option value="Guru Besar">Guru Besar</option>
                    <option value="Guru Penolong Kanan">Guru Penolong Kanan</option>
                </select>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            <button type="submit" name="tambah_guru_btn" class="btn btn-success" style="background-color: #28a745; border: none;">Simpan</button>
          </div>
      </form>
    </div>
  </div>
</div>
