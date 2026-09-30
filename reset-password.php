<?php
require_once __DIR__ . '/security.php';
app_start_session();
require 'db_connect.php';

$msg = "";
$validToken = false;
$email = "";

// 1. Check kalau token ada di URL
if (isset($_GET['token'])) {
    $token = (string)$_GET['token'];
    $tokenHash = hash('sha256', $token);
    $now = date("Y-m-d H:i:s");

    // Check token sah dan belum expired
    $stmt = $conn->prepare('SELECT email FROM password_resets WHERE token = ? AND expires_at > ? LIMIT 1');
    $stmt->bind_param('ss', $tokenHash, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $validToken = true;
        // Ambil email siap-siap dari token ni untuk kegunaan update nanti
        $row = $result->fetch_assoc();
        $email = $row['email'];
    } else {
        $msg = "Pautan tidak sah atau telah tamat tempoh.";
    }
    $stmt->close();
} else {
    header("Location: index.php"); // Kalau user masuk tanpa token, tendang balik
    exit();
}

// 2. Proses form bila user submit password baru
if (isset($_POST['reset_pass'])) {
    require_csrf(false);
    $new_pass = (string)($_POST['password'] ?? '');
    $confirm_pass = (string)($_POST['confirm_password'] ?? '');

    if (strlen($new_pass) < 10) {
        $msg = 'Kata laluan mesti sekurang-kurangnya 10 aksara.';
        $validToken = true;
    } elseif ($new_pass === $confirm_pass) {
        
        if (!empty($email)) {
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
            mysqli_begin_transaction($conn);
            try {
                // Tuntut token secara atomik supaya dua permintaan serentak tidak boleh menggunakannya.
                $stmt = $conn->prepare('DELETE FROM password_resets WHERE token = ? AND expires_at > NOW()');
                $stmt->bind_param('s', $tokenHash);
                $stmt->execute();
                $claimed = $stmt->affected_rows === 1;
                $stmt->close();
                if (!$claimed) {
                    throw new RuntimeException('Reset token was already used or expired');
                }

                $stmt = $conn->prepare('UPDATE users SET password = ?, auth_version = auth_version + 1 WHERE email = ? LIMIT 1');
                $stmt->bind_param('ss', $hashed_password, $email);
                $stmt->execute();
                if ($stmt->affected_rows !== 1) {
                    throw new RuntimeException('Reset account was not found');
                }
                $stmt->close();

                $stmt = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->close();
                mysqli_commit($conn);
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                error_log('Password reset failed: ' . $e->getMessage());
                $msg = 'Pautan tidak sah, telah digunakan atau telah tamat tempoh.';
                $validToken = false;
                goto reset_done;
            }

            require_once 'functions.php';
        popup_and_redirect("Password berjaya ditukar! Sila login menggunakan password baru.", "index.php", "success");
        } else {
            $msg = "Ralat sistem: Email tidak dijumpai.";
        }

    } else {
        $msg = "Password tidak sama. Sila cuba lagi.";
        $validToken = true; // Kekalkan form
    }
}
reset_done:
?>

<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
      
      <title>Reset Password - Sistem RMT</title>
      
      <link rel="icon" href="images/logosklh.png" type="image/png" />
      <link rel="stylesheet" href="css/bootstrap.min.css" />
      <link rel="stylesheet" href="style.css" />
      <link rel="stylesheet" href="css/responsive.css" />
      <link rel="stylesheet" href="css/bootstrap-select.css" />
      <link rel="stylesheet" href="css/perfect-scrollbar.css" />
      <link rel="stylesheet" href="css/custom.css" />
      
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

      <style>
          .login_form {
              padding: 40px !important; /* Tambah ruang dalam kotak putih */
              border-radius: 10px;
          }
          .input-group-text {
              background-color: #f8f9fa;
              border-right: none;
          }
          .form-control {
              border-left: none; /* Hilangkan garisan supaya nampak bercantum dgn ikon */
              height: 45px;
          }
          .input-group-text i {
              color: #666;
          }
          /* Focus state supaya nampak cantik bila klik */
          .form-control:focus {
              box-shadow: none;
              border-color: #ced4da;
          }
          .input-group:focus-within .input-group-text {
              border-color: #80bdff; /* Warna biru bila klik */
          }
          .input-group:focus-within .form-control {
              border-color: #80bdff;
          }
      </style>
   </head>

   <body class="inner_page login">
      <div class="full_container">
         <div class="container">
            <div class="center verticle_center full_height">
               
               <div class="login_section">
                  
                  <div class="logo_login">
                     <div class="center">
                        <img width="280" src="images/DRS_Logo_White.png" alt="Logo DRS" style="max-height: 115px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.3));" />
                     </div>
                  </div>

                  <div class="login_form">
                     
                     <h4 class="text-center mb-4" style="font-weight: 700; color: #333;">CIPTA KATA LALUAN BARU</h4>

                     <?php if($msg != "") { ?>
                        <div class="alert alert-danger text-center p-2" role="alert" style="font-size: 14px;">
                           <i class="fa fa-exclamation-circle"></i> <?php echo $msg; ?>
                        </div>
                     <?php } ?>

                     <?php if ($validToken): ?>
                     <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
                        <fieldset>
                           
                           <div class="form-group mb-3">
                              <label style="font-weight: 600; font-size: 14px; color: #555;">Password Baru</label>
                              <div class="input-group">
                                  <div class="input-group-prepend"><span class="input-group-text border-right-0"><i class="fa fa-lock"></i></span></div>
                                  <input type="password" name="password" id="pass1" class="form-control border-start-0" placeholder="Minima 10 aksara" minlength="10" autocomplete="new-password" required />
                              </div>
                           </div>

                           <div class="form-group mb-3">
                              <label style="font-weight: 600; font-size: 14px; color: #555;">Ulang Password</label>
                              <div class="input-group">
                                  <div class="input-group-prepend"><span class="input-group-text border-right-0"><i class="fa fa-lock"></i></span></div>
                                  <input type="password" name="confirm_password" id="pass2" class="form-control border-start-0" placeholder="Taip semula password" minlength="10" autocomplete="new-password" required />
                              </div>
                           </div>

                           <div class="form-group mb-4">
                              <div class="form-check">
                                 <input class="form-check-input" type="checkbox" onclick="showPassword()" id="showPassCheck">
                                 <label class="form-check-label" for="showPassCheck" style="font-size: 13px; color: #666; cursor: pointer;">
                                    Lihat Password
                                 </label>
                              </div>
                           </div>

                           <div class="form-group">
                              <button type="submit" name="reset_pass" class="main_bt w-100" style="border-radius: 5px;">Simpan Password Baru</button>
                           </div>

                        </fieldset>
                     </form>
                     <?php else: ?>
                        <div class="text-center mt-4">
                            <p class="text-muted">Pautan ini telah tamat tempoh.</p>
                            <a href="forgot-password.php" class="btn btn-warning text-white w-100 mb-2">Request Link Baru</a>
                            <a href="index.php" style="text-decoration: none; color: #666; font-size: 14px;">Kembali ke Login</a>
                        </div>
                     <?php endif; ?>
                     
                  </div> 
               
               </div> 

            </div>
         </div>
      </div>

      <script src="js/jquery.min.js"></script>
      <script src="js/popper.min.js"></script>
      <script src="js/bootstrap.min.js"></script>
      
      <script>
        function showPassword() {
          var x = document.getElementById("pass1");
          var y = document.getElementById("pass2");
          if (x.type === "password") {
            x.type = "text";
            y.type = "text";
          } else {
            x.type = "password";
            y.type = "password";
          }
        }
      </script>
   </body>
</html>
