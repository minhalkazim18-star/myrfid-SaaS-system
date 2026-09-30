<?php
require_once __DIR__ . '/security.php';
app_start_session();
include 'db_connect.php';
require_once 'email_helper.php';

$msg     = "";
$msgType = "";

if (isset($_POST['email'])) {
    require_csrf(false);
    $email = strtolower(trim((string)$_POST['email']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Jika e-mel ini berdaftar, pautan reset akan dihantar dalam masa beberapa minit.';
        $msgType = 'success';
        goto forgot_done;
    }
    if (login_rate_limited($conn, 'reset:' . $email, 5, 15) || login_rate_limited($conn, 'reset-ip', 10, 15)) {
        $msg = 'Terlalu banyak permintaan. Sila tunggu 15 minit sebelum mencuba lagi.';
        $msgType = 'danger';
        goto forgot_done;
    }
    record_login_failure($conn, 'reset:' . $email);
    record_login_failure($conn, 'reset-ip');

    // Check emel wujud
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && mysqli_num_rows($result) > 0) {
        $stmt->close();

        // Generate Token & Expiry (1 jam dari sekarang)
        $token  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Simpan dalam DB (padam request lama dulu)
        $stmt = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $email, $tokenHash, $expiry);
        $stmt->execute();
        $stmt->close();

        // Bina URL reset yang dinamik (bukan hardcoded localhost)
        $base_url = rtrim((string)getenv('APP_URL'), '/');
        $reset_url = $base_url . '/reset-password.php?token=' . $token;

        if ($base_url === '' || !isSmtpConfigured()) {
            error_log('Password reset requested but APP_URL or SMTP is not configured.');
        } else {
            try {
                $mail = getMailer();
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Reset Kata Laluan - Sistem MyRFID RMT';
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <div style='background: #1e293b; padding: 24px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0;'>&#127970; SaaS MyRFID</h2>
                            <p style='color: #94a3b8; margin: 4px 0 0;'>Sistem Pengurusan RMT Digital</p>
                        </div>
                        <div style='background: #f8fafc; padding: 32px;'>
                            <h3 style='color: #1e293b;'>Permintaan Reset Kata Laluan</h3>
                            <p>Tuan/Puan,</p>
                            <p>Kami telah menerima permintaan untuk menetapkan semula kata laluan akaun MyRFID RMT anda.</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='$reset_url' style='background: #2563eb; color: white; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 15px;'>
                                    Reset Kata Laluan
                                </a>
                            </div>
                            <div style='background: #fef9c3; border-left: 4px solid #eab308; padding: 12px 16px; border-radius: 4px; margin: 20px 0;'>
                                <p style='margin: 0; color: #713f12; font-size: 13px;'>&#9888; Pautan ini hanya sah selama <strong>1 jam</strong> dari masa e-mel ini dihantar.</p>
                            </div>
                            <p>Jika anda tidak memohon reset kata laluan, sila abaikan e-mel ini. Akaun anda masih selamat.</p>
                            <br>
                            <p>Sekian, terima kasih.</p>
                            <p><strong>Pengurusan SaaS MyRFID</strong></p>
                        </div>
                        <div style='background: #1e293b; padding: 16px; text-align: center;'>
                            <p style='color: #64748b; font-size: 12px; margin: 0;'>E-mel ini dijana secara automatik. Sila jangan balas.</p>
                        </div>
                    </div>
                ";
                $mail->AltBody = "Klik pautan berikut untuk reset kata laluan anda: $reset_url (Sah 1 jam sahaja)";
                $mail->send();
            } catch (Exception $e) {
                error_log("forgot-password send error: " . $e->getMessage());
            }
        }
        $msg = 'Jika e-mel ini berdaftar, pautan reset akan dihantar dalam masa beberapa minit.';
        $msgType = 'success';
    } else {
        $stmt->close();
        // Sengaja guna mesej generik supaya tidak dedah sama ada emel wujud atau tidak (security best practice)
        $msg     = "Jika e-mel ini berdaftar dalam sistem, anda akan menerima pautan reset dalam masa beberapa minit.";
        $msgType = "success";
    }
    forgot_done:
}
?>

<!DOCTYPE html>
<html lang="ms">
   <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
      
      <title>Lupa Kata Laluan - Sistem RMT</title>
      
      <link rel="icon" href="images/app_icon.png" type="image/png" />
      <link rel="stylesheet" href="style.css" />
      <link rel="stylesheet" href="css/auth-login.css" />
   </head>

   <body class="auth-page">
      <main class="auth-shell">
         <section class="auth-card" aria-labelledby="forgot-title">
            <aside class="auth-brand" aria-label="Pengenalan Sistem RMT">
               <img class="auth-logo" src="images/DRS_Logo_White.png" alt="Digital Registration System" />
               <div class="auth-brand-message">
                  <p class="auth-eyebrow">Pemulihan Akaun</p>
                  <h1>Akses semula<br>akaun anda.</h1>
                  <p class="auth-brand-copy">Kami akan menghantar pautan selamat ke alamat e-mel yang didaftarkan.</p>
               </div>
               <p class="auth-brand-footer">Digital Registration System</p>
            </aside>

            <div class="auth-form-panel">
               <div class="auth-form-wrap">
                  <header class="auth-heading">
                     <h2 id="forgot-title">Lupa kata laluan?</h2>
                     <p>Masukkan alamat e-mel akaun anda untuk menerima pautan tetapan semula.</p>
                  </header>

                  <?php if ($msg !== '') { ?>
                     <div class="auth-alert auth-alert-<?php echo $msgType === 'success' ? 'success' : 'danger'; ?>" role="alert">
                        <svg class="auth-icon-svg" viewBox="0 0 24 24" aria-hidden="true">
                           <?php if ($msgType === 'success') { ?>
                              <circle cx="12" cy="12" r="9"></circle><path d="m8 12 2.5 2.5L16.5 9"></path>
                           <?php } else { ?>
                              <circle cx="12" cy="12" r="9"></circle><path d="M12 7.5v5.5"></path><path d="M12 16.5h.01"></path>
                           <?php } ?>
                        </svg>
                        <span><?php echo escape_html($msg); ?></span>
                     </div>
                  <?php } ?>

                  <form method="POST">
                     <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">

                     <div class="auth-field">
                        <div class="auth-label-row">
                           <label class="auth-label" for="email">Alamat e-mel</label>
                        </div>
                        <div class="auth-input-wrap">
                           <input class="auth-input auth-input-plain" type="email" id="email" name="email"
                              placeholder="nama@sekolah.edu.my" autocomplete="email" inputmode="email"
                              value="<?php echo escape_html((string)($_POST['email'] ?? '')); ?>" required autofocus />
                        </div>
                        <p class="auth-field-note">Pautan tetapan semula sah selama satu jam selepas dihantar.</p>
                     </div>

                     <button type="submit" class="auth-submit">Hantar Pautan Reset</button>

                     <div class="auth-form-footer">
                        <a class="auth-back" href="index.php"><span aria-hidden="true">←</span> Kembali ke Log Masuk</a>
                     </div>
                  </form>
               </div>
            </div>
         </section>
      </main>
   </body>
</html>
