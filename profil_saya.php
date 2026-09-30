<?php 
include 'session_auth.php'; 
include 'db_connect.php';
include 'header.php';
include 'sidebar.php'; 

$uid = (int)$_SESSION['user_id'];
$msg = "";

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf(false);
}

// --- PROSES 1: UPDATE MAKLUMAT DIRI (DARI MODAL) ---
if(isset($_POST['update_profile'])) {
    $nama = trim((string)($_POST['nama_penuh'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['no_tel'] ?? ''));
    if ($nama === '' || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $msg = '<div class="alert alert-warning">Nama atau alamat e-mel tidak sah.</div>';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $stmt->bind_param('si', $email, $uid);
        $stmt->execute();
        $emailUsed = $email !== '' && $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if ($emailUsed) {
            $msg = '<div class="alert alert-warning">Alamat e-mel telah digunakan oleh akaun lain.</div>';
        } else {
            $emailValue = $email !== '' ? $email : null;
            $stmt = $conn->prepare('UPDATE users SET nama_penuh = ?, email = ?, no_tel = ? WHERE id = ?');
            $stmt->bind_param('sssi', $nama, $emailValue, $phone, $uid);
            $stmt->execute();
            $stmt->close();
            $_SESSION['nama_penuh'] = $nama;
            $msg = '<div class="alert alert-success alert-dismissible fade show"><i class="fa fa-check-circle mr-2"></i> Maklumat berjaya dikemaskini!<button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div>';
        }
    }
}

// --- PROSES 2: TUKAR PASSWORD ---
if(isset($_POST['update_password'])) {
    $pass_lama = $_POST['pass_lama'];
    $pass_baru = $_POST['pass_baru'];
    $pass_confirm = $_POST['pass_confirm'];
    
    $stmt = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $curr_pass = (string)($stmt->get_result()->fetch_assoc()['password'] ?? '');
    $stmt->close();
    
    if(password_verify($pass_lama, $curr_pass)) {
        if(strlen($pass_baru) < 10) {
            $msg = '<div class="alert alert-warning">Kata laluan baharu mesti sekurang-kurangnya 10 aksara.</div>';
        } elseif($pass_baru == $pass_confirm) {
            $hashed = password_hash($pass_baru, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ?, auth_version = auth_version + 1 WHERE id = ?');
            $stmt->bind_param('si', $hashed, $uid);
            $stmt->execute();
            $stmt->close();
            $_SESSION['auth_version'] = (int)($_SESSION['auth_version'] ?? 1) + 1;
            session_regenerate_id(true);
            $msg = '<div class="alert alert-success alert-dismissible fade show">Kata laluan berjaya ditukar!</div>';
        } else {
            $msg = '<div class="alert alert-warning">Password baru tidak sama dengan pengesahan.</div>';
        }
    } else {
        $msg = '<div class="alert alert-danger">Password lama salah. Sila cuba lagi.</div>';
    }
}

// --- AMBIL DATA USER TERKINI ---
$stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $uid);
$stmt->execute();
$result = $stmt->get_result();
$u = mysqli_fetch_assoc($result);
$stmt->close();

$gambar = ($u['profile_pic'] != "") ? $u['profile_pic'] : "images/user_img.jpg";
?>

<div id="content">
   <?php include 'topbar.php'; ?>
   <div class="midde_cont">
      <div class="container-fluid">
         <div class="page_title"><h2>Tetapan Profil</h2></div>
         <?php echo $msg; ?>

         <div class="row">
            
            <div class="col-md-4">
               <div class="white_shd full margin_bottom_30 text-center p-4">
                  <img src="<?php echo escape_html($gambar); ?>" class="rounded-circle mb-3 shadow-sm" width="150" height="150" style="object-fit:cover; border: 4px solid #fff;">
                  <h4 class="mt-2"><?php echo htmlspecialchars($u['nama_penuh']); ?></h4>
                  <p class="text-muted mb-2"><?php echo htmlspecialchars(ucfirst($u['role'])); ?></p>
                  
                  <?php if(strpos($u['email'], '@gmail.com') !== false){ ?>
                      <span class="badge bg-danger"><i class="fa fa-google"></i> Google Linked</span>
                  <?php } ?>
               </div>
            </div>

            <div class="col-md-8">
               <div class="white_shd full margin_bottom_30">
                  <div class="full graph_head">
                     <div class="heading1 margin_0"><h2>Maklumat Akaun</h2></div>
                  </div>
                  <div class="full padding_infor_info">
                     
                     <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-primary m-0"><i class="fa fa-user-circle mr-2"></i> Info Peribadi</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" data-toggle="modal" data-target="#modalEditProfil">
                            <i class="fa fa-pencil mr-1"></i> Kemaskini
                        </button>
                     </div>

                     <div class="table-responsive">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%" class="text-muted">Nama Penuh</th>
                                <td class="font-weight-bold"><?php echo htmlspecialchars($u['nama_penuh']); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Username / IC</th>
                                <td><span class="font-weight-bold"><?php echo htmlspecialchars($u['username']); ?></span></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Emel</th>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">No. Telefon</th>
                                <td><?php echo ($u['no_tel']) ? htmlspecialchars($u['no_tel']) : '<span class="text-muted font-italic">- Tiada -</span>'; ?></td>
                            </tr>
                        </table>
                     </div>

                     <hr class="my-4">

                     <form method="POST">
                        <h5 class="mb-3 text-primary"><i class="fa fa-lock mr-2"></i> Tukar Kata Laluan</h5>
                        <div class="row">
                           <div class="col-md-12">
                              <label class="form-label text-muted small">Kata Laluan Lama</label>
                              <input type="password" name="pass_lama" placeholder="...." class="form-control" required>
                           </div>
                           <div class="col-md-6">
                              <label class="form-label text-muted small">Kata Laluan Baru</label>
                              <input type="password" name="pass_baru" placeholder="Minimum 10 aksara" class="form-control" minlength="10" autocomplete="new-password" required>
                           </div>
                           <div class="col-md-6">
                              <label class="form-label text-muted small">Ulang Kata Laluan Baru</label>
                              <input type="password" name="pass_confirm" placeholder="Ulang kata laluan baharu" class="form-control" minlength="10" autocomplete="new-password" required>
                           </div>
                           <div class="col-12 text-right mt-3">
                              <button type="submit" name="update_password" class="btn btn-warning text-white px-4">
                                  <i class="fa fa-save mr-1"></i> Simpan Password
                              </button>
                           </div>
                        </div>
                     </form>

                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<div class="modal fade" id="modalEditProfil" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
      
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title font-weight-bold"><i class="fa fa-pencil-square-o mr-2"></i> Kemaskini Profil</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
      </div>
      
      <form method="POST">
          <div class="modal-body p-4">
              <div class="mb-3">
                  <label class="form-label font-weight-bold">Nama Penuh</label>
                  <input type="text" name="nama_penuh" oninput="this.value = this.value.replace(/[^a-zA-Z\s\.\-\'\@]/g, '')" class="form-control" value="<?php echo htmlspecialchars($u['nama_penuh'], ENT_QUOTES); ?>" required>
              </div>

              <div class="mb-3">
                  <label class="form-label font-weight-bold">Username / IC <small class="text-danger">*Tidak boleh diubah</small></label>
                  <input type="text" class="form-control bg-light text-muted" value="<?php echo htmlspecialchars($u['username'], ENT_QUOTES); ?>" readonly>
              </div>

              <div class="mb-3">
                  <label class="form-label font-weight-bold">Alamat Emel</label>
                  <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>" required>
              </div>

              <div class="mb-3">
                  <label class="form-label font-weight-bold">No. Telefon</label>
                  <input type="text" name="no_tel" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" value="<?php echo htmlspecialchars($u['no_tel'] ?? '', ENT_QUOTES); ?>" placeholder="">
              </div>
          </div>
          
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-dismiss="modal">Batal</button>
            <button type="submit" name="update_profile" class="btn btn-primary rounded-pill px-4">
                <i class="fa fa-save mr-1"></i> Simpan Perubahan
            </button>
          </div>
      </form>

    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
