<?php
// PHP's built-in server prioritizes index.php over index.html and ignores
// Apache's DirectoryIndex setting. Keep the site root public during local
// development while retaining this file (and /portal) as the staff login.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = trim(is_string($requestPath) ? rawurldecode($requestPath) : '', '/');
if (($requestPath === '' || $requestPath === 'index.html') && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    readfile(__DIR__ . '/index.html');
    exit();
}

require_once __DIR__ . '/security.php';
app_start_session();
include "db_connect.php";

$loginNext = (string)($_POST['next'] ?? $_GET['next'] ?? '');
$loginNext = in_array($loginNext, ['kiosk','renewal'], true) ? $loginNext : '';

if (isset($_SESSION['role'])) {
    if (!empty($_SESSION['renewal_only'])) { header('Location: renewal.php'); exit; }
    if ($_SESSION['role'] === 'superadmin') {
        header("Location: superadmin_dashboard.php");
    } elseif ($loginNext === 'renewal') {
        header('Location: renewal.php');
    } elseif ($loginNext === 'kiosk') {
        header('Location: kiosk.php?terminal=1');
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error_msg = (string)($_SESSION['login_error'] ?? '');
unset($_SESSION['login_error']);
if (isset($_GET['account']) && $_GET['account'] === 'inactive') {
    $error_msg = 'Akaun sekolah tidak aktif atau langganan telah tamat. Sila hubungi pentadbir.';
}
csrf_token();
$googleOAuthAvailable = google_oauth_is_configured();

if (isset($_POST['login_btn'])) {

    require_csrf(false);

    $uname = trim((string)($_POST['username'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');
    $loginUserKey = 'login-user:' . $uname;
    $loginIpKey = 'login-ip';
    if (login_rate_limited($conn, $loginUserKey, 5, 15) || login_rate_limited($conn, $loginIpKey, 20, 15)) {
        $error_msg = 'Terlalu banyak percubaan. Sila tunggu 15 minit dan cuba lagi.';
        goto end_login;
    }
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $uname);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        if (password_verify($pass, $row['password'])) {
            $user_school_id = (int)($row['school_id'] ?? 0);
            $renewalOnly = false;

            // Semak status sekolah jika bukan superadmin
            if ($row['role'] !== 'superadmin') {
                $chk_sch = mysqli_query($conn, "SELECT status, nama_sekolah, tarikh_luput FROM sekolah WHERE id = '$user_school_id'");
                if ($chk_sch && mysqli_num_rows($chk_sch) > 0) {
                    $sch_row = mysqli_fetch_assoc($chk_sch);
                    if ($sch_row['status'] === 'pending') {
                        $error_msg = "Pendaftaran Sekolah (" . htmlspecialchars($sch_row['nama_sekolah']) . ") sedang menunggu kelulusan Super Admin.";
                        goto end_login;
                    } elseif ($sch_row['status'] !== 'aktif') {
                        $error_msg = "Akses Sekolah (" . htmlspecialchars($sch_row['nama_sekolah']) . ") Telah Digantung! Sila hubungi Admin.";
                        // Elak teruskan login
                        goto end_login;
                    } elseif (!empty($sch_row['tarikh_luput']) && strtotime($sch_row['tarikh_luput']) < strtotime(date('Y-m-d'))) {
                        if ($row['role'] === 'admin') {
                            $renewalOnly = true;
                        } else {
                            $error_msg = 'Langganan sekolah telah tamat. Sila hubungi pentadbir sekolah untuk pembaharuan.';
                            goto end_login;
                        }
                    }
                }
            }

            if ($row['role'] !== 'superadmin' && (empty($sch_row) || $user_school_id < 1)) { $error_msg='Akaun sekolah tidak sah.'; goto end_login; }
            session_regenerate_id(true);
            unset($_SESSION['renewal_only']);
            if ($renewalOnly) $_SESSION['renewal_only'] = true;
            clear_login_failures($conn, $loginUserKey);
            clear_login_failures($conn, $loginIpKey);
            $_SESSION['user_id']    = $row['id'];
            $_SESSION['username']   = $row['username'];
            $_SESSION['role']       = $row['role']; 
            $_SESSION['nama_penuh'] = $row['nama_penuh'];
            $_SESSION['email']      = $row['email'] ?? '';
            $_SESSION['school_id']  = $user_school_id;
            $_SESSION['auth_version'] = (int)($row['auth_version'] ?? 1);
            $_SESSION['last_activity'] = time();

            catat_log($conn, $row['id'], $row['nama_penuh'], "Log masuk ke dalam sistem");

            if ($renewalOnly) { header('Location: renewal.php'); exit; }
            if ($row['role'] === 'superadmin') {
                header("Location: superadmin_dashboard.php");
            } elseif ($loginNext === 'renewal') {
                header('Location: renewal.php');
            } elseif ($loginNext === 'kiosk') {
                header('Location: kiosk.php?terminal=1');
            } else {
                header("Location: dashboard.php");
            }
            exit();

        } else {
            record_login_failure($conn, $loginUserKey);
            record_login_failure($conn, $loginIpKey);
            $error_msg = "ID atau kata laluan tidak sah.";
        }

    } else {
        record_login_failure($conn, $loginUserKey);
        record_login_failure($conn, $loginIpKey);
        $error_msg = "ID atau kata laluan tidak sah.";
    }
    end_login:
}
?>

<!DOCTYPE html>
<html lang="ms">
   <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
      
      <title>Login - Sistem RMT</title>
      
      <link rel="icon" href="images/app_icon.png" type="image/png" />
      <link rel="stylesheet" href="css/bootstrap.min.css" />
      <link rel="stylesheet" href="style.css" />
      <link rel="stylesheet" href="css/responsive.css" />
      <link rel="stylesheet" href="css/bootstrap-select.css" />
      <link rel="stylesheet" href="css/perfect-scrollbar.css" />
      <link rel="stylesheet" href="css/custom.css" />
      <link rel="stylesheet" href="css/auth-login.css" />
   </head>

   <body class="auth-page">
      <main class="auth-shell">
         <section class="auth-card" aria-labelledby="login-title">
            <aside class="auth-brand" aria-label="Pengenalan Sistem RMT">
               <img class="auth-logo" src="images/DRS_Logo_White.png" alt="Digital Registration System" />
               <div class="auth-brand-message">
                  <p class="auth-eyebrow">Sistem Pengurusan RMT</p>
                  <h1>Lebih mudah.<br>Lebih teratur.</h1>
                  <p class="auth-brand-copy">Urus pendaftaran, rekod RFID dan agihan makanan sekolah dalam satu sistem.</p>
               </div>

               <p class="auth-brand-footer">Digital Registration System</p>
            </aside>

            <div class="auth-form-panel">
               <div class="auth-form-wrap">
                  <header class="auth-heading">
                     <h2 id="login-title">Selamat kembali</h2>
                     <p>Log masuk untuk meneruskan ke ruang pengurusan anda.</p>
                  </header>

                  <?php if ($error_msg !== '') { ?>
                     <div class="auth-alert" role="alert">
                        <svg class="auth-icon-svg" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7.5v5.5"></path><path d="M12 16.5h.01"></path></svg>
                        <span><?php echo escape_html($error_msg); ?></span>
                     </div>
                  <?php } ?>

                  <form method="POST">
                     <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
                     <?php if ($loginNext !== ''): ?>
                     <input type="hidden" name="next" value="<?php echo escape_html($loginNext); ?>">
                     <?php endif; ?>

                     <div class="auth-field">
                        <div class="auth-label-row">
                           <label class="auth-label" for="username">Nama pengguna atau No. IC</label>
                        </div>
                        <div class="auth-input-wrap">
                           <input class="auth-input auth-input-plain" type="text" id="username" name="username" placeholder="Masukkan nama pengguna" autocomplete="username" autocapitalize="none" value="<?php echo escape_html((string)($_POST['username'] ?? '')); ?>" required autofocus />
                        </div>
                     </div>

                     <div class="auth-field">
                        <div class="auth-label-row">
                           <label class="auth-label" for="password">Kata laluan</label>
                           <a class="auth-forgot" href="forgot-password.php">Lupa kata laluan?</a>
                        </div>
                        <div class="auth-input-wrap">
                           <input class="auth-input" type="password" id="password" name="password" placeholder="Masukkan kata laluan" autocomplete="current-password" required />
                           <button class="auth-password-toggle" type="button" id="passwordToggle" aria-label="Tunjukkan kata laluan" aria-pressed="false">
                              <svg class="auth-icon-svg auth-eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 12s3.1-5 8.5-5 8.5 5 8.5 5-3.1 5-8.5 5-8.5-5-8.5-5Z"></path><circle cx="12" cy="12" r="2.25"></circle></svg>
                              <svg class="auth-icon-svg auth-eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4l16 16"></path><path d="M9.6 7.4A9.6 9.6 0 0 1 12 7c5.4 0 8.5 5 8.5 5a13.7 13.7 0 0 1-2.2 2.7"></path><path d="M6.1 8.3A14 14 0 0 0 3.5 12s3.1 5 8.5 5c.9 0 1.7-.1 2.4-.4"></path></svg>
                           </button>
                        </div>
                     </div>

                     <button type="submit" name="login_btn" class="auth-submit">Log Masuk</button>

                     <?php if ($googleOAuthAvailable) { ?>
                        <div class="auth-divider"><span>atau teruskan dengan</span></div>
                        <a href="google-login.php<?php echo $loginNext !== '' ? '?next=' . escape_html($loginNext) : ''; ?>" class="auth-google">
                           <span class="auth-google-mark" aria-hidden="true">G</span>
                           <span>Log masuk dengan Google</span>
                        </a>
                     <?php } ?>

                     <p class="auth-register">Belum mempunyai akaun? <a href="register_school.php">Daftar sekolah</a></p>
                  </form>
               </div>
            </div>
         </section>
      </main>

      <script src="js/jquery.min.js"></script>
      <script src="js/popper.min.js"></script>
      <script src="js/bootstrap.min.js"></script>
      <script src="js/animate.js"></script>
      <script src="js/bootstrap-select.js"></script>
      <script src="js/perfect-scrollbar.min.js"></script>
      <script src="js/custom.js"></script>
      <script>
         (function () {
            var button = document.getElementById('passwordToggle');
            var password = document.getElementById('password');
            if (!button || !password) return;

            button.addEventListener('click', function () {
               var showPassword = password.type === 'password';
               password.type = showPassword ? 'text' : 'password';
               button.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
               button.setAttribute('aria-label', showPassword ? 'Sembunyikan kata laluan' : 'Tunjukkan kata laluan');
            });
         }());
      </script>
   </body>
</html>