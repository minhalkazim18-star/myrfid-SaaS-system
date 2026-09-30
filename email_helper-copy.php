<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/security.php';


/** Temporary diagnostics. Open the log in File Manager, never in the browser. */
function myrfid_smtp_log(string $message): void {
    foreach (($GLOBALS['myrfid_smtp_secrets'] ?? []) as $secret) {
        if ($secret !== '') $message = str_replace([$secret, base64_encode($secret)], '[REDACTED]', $message);
    }
    $message = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[EMAIL]', $message);
    $message = str_replace(["\r", "\n", '<?', '?>'], [' ', ' | ', '', ''], $message);
    // A PHP guard prevents the log contents being served by the PHP web server.
    $path = __DIR__ . '/smtp_diagnostic_log.php';
    $handle = @fopen($path, 'c+');
    if (!$handle) { error_log('MyRFID SMTP diagnostic log cannot be opened.'); return; }
    if (flock($handle, LOCK_EX)) {
        $stat = fstat($handle);
        if ($stat['size'] > 1048576) { ftruncate($handle, 0); rewind($handle); }
        fseek($handle, 0, SEEK_END);
        if (ftell($handle) === 0) fwrite($handle, "<?php http_response_code(404); exit; ?>\n");
        fwrite($handle, '[' . date('c') . '] ' . substr($message, 0, 4000) . "\n");
        fflush($handle);
        flock($handle, LOCK_UN);
    }
    fclose($handle);
    @chmod($path, 0640);
}

function myrfid_smtp_diagnostics(PHPMailer $mail): void {
    $GLOBALS['myrfid_smtp_secrets'][] = (string)$mail->Password;
    $mail->Timeout = 20;
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = static function ($message, $level): void {
        // Never log client commands, authentication payloads or message bodies.
        if (strpos($message, 'CLIENT -> SERVER:') !== false) return;
        if (strpos($message, 'SERVER -> CLIENT:') === 0
            || strpos($message, 'SMTP ERROR:') === 0
            || strpos($message, 'SMTP Error:') === 0) {
            myrfid_smtp_log($message);
        }
    };
    myrfid_smtp_log('START host=' . $mail->Host . ' port=' . $mail->Port
        . ' encryption=' . $mail->SMTPSecure . ' auth=' . ($mail->SMTPAuth ? 'yes' : 'no')
        . ' openssl=' . (extension_loaded('openssl') ? 'yes' : 'no'));
}

/**
 * Get SMTP settings from database
 */
function getSmtpSettings() {
    global $conn;
    // If $conn not available, try to include db_connect
    if (!isset($conn) || !$conn) {
        include_once __DIR__ . '/db_connect.php';
    }
    $res = mysqli_query($conn, "SELECT * FROM sa_smtp_settings WHERE is_active = 1 LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $settings = mysqli_fetch_assoc($res);
        $settings['smtp_password'] = decrypt_secret((string)($settings['smtp_password'] ?? ''));
        $GLOBALS['myrfid_smtp_secrets'][] = (string)$settings['smtp_password'];
        if ($settings['smtp_password'] === '') {
            myrfid_smtp_log('CONFIG: SMTP password empty after decrypt. Check saved password and server APP_KEY.');
        }
        return $settings;
    }
    myrfid_smtp_log('CONFIG: No active SMTP settings found.');
    return null;
}

/**
 * Build a configured PHPMailer instance using DB settings
 */
function getMailer() {
    $smtp = getSmtpSettings();
    $mail = new PHPMailer(true);

    if ($smtp && !empty($smtp['smtp_host']) && !empty($smtp['smtp_username'])) {
        $mail->isSMTP();
        $mail->Host       = $smtp['smtp_host'];
        $mail->SMTPAuth   = (bool)$smtp['smtp_auth'];
        $mail->Username   = $smtp['smtp_username'];
        $mail->Password   = $smtp['smtp_password'];
        $mail->Port       = (int)$smtp['smtp_port'];
        
        if ($smtp['smtp_encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($smtp['smtp_encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        myrfid_smtp_diagnostics($mail);
        $mail->setFrom($smtp['smtp_from_email'], $smtp['smtp_from_name']);
    } else {
        myrfid_smtp_log('CONFIG: SMTP host or username missing; falling back to PHP mail().');
        // Fallback: use PHP mail()
        $mail->isMail();
        $mail->setFrom('admin@myrfid.edu.my', 'SaaS MyRFID Admin');
    }

    $mail->CharSet = 'UTF-8';
    return $mail;
}

/**
 * Check if SMTP is configured (has real credentials)
 */
function isSmtpConfigured() {
    $smtp = getSmtpSettings();
    return ($smtp && !empty($smtp['smtp_host']) && !empty($smtp['smtp_username']) && !empty($smtp['smtp_password']));
}

/**
 * Shared email presentation. Update company text here for all five templates.
 * Existing SMTP/database configuration above is retained.
 */
function myrfid_email_company(): string {
    return 'The Bridge Business Alliance Sdn. Bhd.';
}

function myrfid_email_escape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function myrfid_email_paragraph(string $safeHtml): string {
    return '<p style="margin:0 0 18px;font-size:15px;line-height:1.75;color:#334155;">' . $safeHtml . '</p>';
}

function myrfid_email_details(array $rows): string {
    $html = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;margin:4px 0 24px;border:1px solid #e2e8f0;border-collapse:separate;border-radius:8px;background-color:#f8fafc;">';
    foreach ($rows as $label => $value) {
        $html .= '<tr><td style="padding:13px 16px;border-bottom:1px solid #e2e8f0;">'
            . '<span style="display:block;font-size:11px;line-height:1.5;font-weight:bold;letter-spacing:.5px;color:#64748b;">'
            . myrfid_email_escape($label) . '</span>'
            . '<span style="display:block;margin-top:4px;font-size:15px;line-height:1.6;font-weight:bold;color:#17243a;overflow-wrap:anywhere;">'
            . myrfid_email_escape($value) . '</span></td></tr>';
    }
    return $html . '</table>';
}

function myrfid_email_notice(string $title, string $safeHtml): string {
    return '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 22px;width:100%;">'
        . '<tr><td bgcolor="#eff5ff" style="padding:17px 18px;background-color:#eff5ff;border-left:3px solid #2563eb;">'
        . '<p style="margin:0 0 6px;font-size:13px;line-height:1.6;font-weight:bold;color:#1e3a5f;">' . myrfid_email_escape($title) . '</p>'
        . '<p style="margin:0;font-size:14px;line-height:1.75;color:#334155;">' . $safeHtml . '</p></td></tr></table>';
}

/** Hide the portal button when APP_URL is absent or a local development URL. */
function myrfid_email_portal_url(): string {
    $base = rtrim(trim((string)getenv('APP_URL')), '/');
    if ($base === '' || !filter_var($base, FILTER_VALIDATE_URL)) return '';
    $parts = parse_url($base);
    if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https') return '';
    if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) return '';
    $host = strtolower((string)($parts['host'] ?? ''));
    if ($host === '' || strpos($host, '.') === false || preg_match('/(?:^|\.)(localhost|local|test|invalid)$/i', $host)) return '';
    if (filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return '';
    // APP_URL should point to the application root, including its subfolder if any.
    return $base . '/portal';
}

function myrfid_email_portal_button(): string {
    $url = myrfid_email_portal_url();
    if ($url === '') return '';
    $safeUrl = myrfid_email_escape($url);
    return '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:6px 0 20px;">'
        . '<tr><td align="center" bgcolor="#2563eb" style="background-color:#2563eb;border-radius:6px;mso-padding-alt:14px 22px;">'
        . '<a href="' . $safeUrl . '" style="display:inline-block;padding:14px 22px;border:1px solid #2563eb;border-radius:6px;font-size:14px;line-height:20px;font-weight:bold;color:#ffffff;text-decoration:none;">Log masuk MyRFID</a>'
        . '</td></tr></table>'
        . '<p style="margin:0 0 24px;font-size:12px;line-height:1.7;color:#64748b;">Jika butang tidak berfungsi, buka pautan ini:<br>'
        . '<a href="' . $safeUrl . '" style="color:#2563eb;text-decoration:underline;word-break:break-all;">' . $safeUrl . '</a></p>';
}

function myrfid_email_expiry($value): string {
    $raw = trim((string)$value);
    if ($raw === '') return 'Sila semak dalam portal MyRFID';
    $timestamp = strtotime($raw);
    return $timestamp === false ? 'Sila semak dalam portal MyRFID' : date('d/m/Y', $timestamp);
}

/** Content HTML is built only by these templates; dynamic values are escaped. */
function myrfid_email_layout(string $label, string $title, string $preheader, string $content, string $footer): string {
    $company = myrfid_email_escape(myrfid_email_company());
    $safeLabel = myrfid_email_escape($label);
    $safeTitle = myrfid_email_escape($title);
    $safePreheader = myrfid_email_escape($preheader);
    $safeFooter = myrfid_email_escape($footer);
    return <<<HTML
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$safeTitle}</title>
<style>
body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;}
table,td{mso-table-lspace:0pt;mso-table-rspace:0pt;}
a{color:#2563eb;}
@media only screen and (max-width:600px){
.email-outer{padding:12px 8px!important;}
.email-body{padding:26px 22px!important;}
.email-header{padding:22px!important;}
.email-footer{padding:20px 22px!important;}
.email-title{font-size:24px!important;line-height:1.3!important;}
}
</style>
</head>
<body style="margin:0;padding:0;width:100%;background-color:#f3f5f8;font-family:Arial,Helvetica,sans-serif;">
<div style="display:none;font-size:1px;line-height:1px;color:#f3f5f8;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">{$safePreheader}</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f3f5f8" style="width:100%;background-color:#f3f5f8;">
<tr><td class="email-outer" align="center" style="padding:32px 16px;">
<!--[if mso]><table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"><tr><td><![endif]-->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff" style="width:100%;max-width:600px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:10px;">
<tr><td height="4" bgcolor="#2563eb" style="height:4px;line-height:4px;font-size:0;background-color:#2563eb;">&nbsp;</td></tr>
<tr><td class="email-header" style="padding:26px 34px;border-bottom:1px solid #e2e8f0;">
<p style="margin:0;font-size:25px;line-height:1.25;font-weight:bold;letter-spacing:-.6px;color:#17243a;">MyRFID</p>
<p style="margin:6px 0 0;font-size:12px;line-height:1.6;color:#64748b;">Sistem Pengurusan RMT Digital</p>
</td></tr>
<tr><td class="email-body" style="padding:30px 34px 28px;">
<p style="margin:0 0 10px;font-size:11px;line-height:1.5;font-weight:bold;letter-spacing:1.1px;color:#2563eb;">{$safeLabel}</p>
<h1 class="email-title" style="margin:0 0 24px;font-size:27px;line-height:1.3;font-weight:bold;letter-spacing:-.6px;color:#17243a;">{$safeTitle}</h1>
{$content}
<p style="margin:26px 0 6px;font-size:14px;line-height:1.7;color:#334155;">Sekian, terima kasih.</p>
<p style="margin:0;font-size:13px;line-height:1.7;color:#17243a;"><strong>Pengurusan<br>{$company}</strong></p>
</td></tr>
<tr><td class="email-footer" bgcolor="#f8fafc" style="padding:20px 34px;background-color:#f8fafc;border-top:1px solid #e2e8f0;">
<p style="margin:0;font-size:11px;line-height:1.7;color:#64748b;">{$safeFooter}</p>
</td></tr>
</table>
<!--[if mso]></td></tr></table><![endif]-->
</td></tr></table>
</body></html>
HTML;
}

function myrfid_email_signoff(): string {
    return "\n\nSekian, terima kasih.\nPengurusan\n" . myrfid_email_company();
}

/** All original public function names/arguments and return values are preserved. */
function sendQuotationEmail($to_email, $school_name, $pdf_path, $ref_no) {
    if (empty($to_email)) return false;
    try {
        // Do not tell recipients a quotation is attached when the PDF is missing.
        if (empty($pdf_path) || !is_file($pdf_path) || !is_readable($pdf_path)) {
            myrfid_smtp_log('sendQuotationEmail error: Quotation PDF is missing or unreadable.');
            return false;
        }
        $mail = getMailer();
        $mail->addAddress($to_email, $school_name);
        $mail->isHTML(true);
        $mail->Subject = 'Sebut Harga Langganan Sistem MyRFID RMT - ' . $ref_no;
        $content = myrfid_email_paragraph('Tuan/Puan,')
            . myrfid_email_paragraph('Terima kasih kerana mendaftar untuk sistem <strong>MyRFID RMT</strong>.')
            . myrfid_email_paragraph('Bersama-sama ini dilampirkan <strong>Sebut Harga Rasmi</strong> untuk rujukan pihak tuan/puan.')
            . myrfid_email_details(['SEKOLAH' => $school_name, 'NO. RUJUKAN SEBUT HARGA' => $ref_no, 'TEMPOH SAH' => '14 hari dari tarikh dikeluarkan'])
            . myrfid_email_notice('Arahan pembayaran', 'Sila jelaskan bayaran ke akaun yang tertera di dalam sebut harga dan balas e-mel ini berserta <strong>resit pembayaran</strong> untuk tujuan pengesahan dan pengaktifan akaun.')
            . myrfid_email_paragraph('Sekiranya terdapat sebarang pertanyaan, sila hubungi pihak kami.');
        $mail->Body = myrfid_email_layout('SEBUT HARGA', 'Sebut harga langganan MyRFID', 'Sebut harga ' . $ref_no . ' untuk ' . $school_name . ' dilampirkan bersama e-mel ini.', $content,
            "E-mel ini dihantar secara automatik. Sila gunakan fungsi 'Reply' pada e-mel ini untuk menghantar resit pembayaran anda.");
        $mail->AltBody = "Tuan/Puan,\n\nTerima kasih kerana mendaftar untuk sistem MyRFID RMT.\nSebut Harga Rasmi dilampirkan untuk rujukan pihak tuan/puan.\n\nSekolah: $school_name\nNo. rujukan: $ref_no\nTempoh sah: 14 hari dari tarikh dikeluarkan.\n\nSila jelaskan bayaran ke akaun yang tertera di dalam sebut harga dan balas e-mel ini berserta resit pembayaran untuk pengesahan dan pengaktifan akaun.\n\nSekiranya terdapat sebarang pertanyaan, sila hubungi pihak kami." . myrfid_email_signoff();
        $mail->addAttachment($pdf_path, "$ref_no.pdf");
        $mail->send();
        myrfid_smtp_log('SUCCESS: Quotation accepted by mail transport.');
        return true;
    } catch (\Throwable $e) {
        myrfid_smtp_log('sendQuotationEmail error: ' . $e->getMessage());
        return false;
    }
}

function sendRejectionEmail($to_email, $school_name, $reason) {
    if (empty($to_email)) return false;
    try {
        $mail = getMailer();
        $mail->addAddress($to_email, $school_name);
        $mail->isHTML(true);
        $mail->Subject = 'Status Pendaftaran Sistem MyRFID RMT';
        $content = myrfid_email_paragraph('Tuan/Puan,')
            . myrfid_email_paragraph('Dukacita dimaklumkan bahawa permohonan pendaftaran sistem MyRFID RMT untuk sekolah berikut <strong>telah ditolak</strong>.')
            . myrfid_email_details(['SEKOLAH' => $school_name, 'STATUS PERMOHONAN' => 'Ditolak']);
        if (trim((string)$reason) !== '') {
            $content .= myrfid_email_notice('Sebab penolakan', nl2br(myrfid_email_escape($reason)));
        }
        $content .= myrfid_email_paragraph('Sekiranya terdapat sebarang pertanyaan atau anda ingin memohon semula, sila hubungi pihak kami.');
        $mail->Body = myrfid_email_layout('STATUS PENDAFTARAN', 'Makluman keputusan permohonan', 'Keputusan pendaftaran MyRFID untuk ' . $school_name . '.', $content, 'E-mel ini dijana secara automatik oleh sistem MyRFID.');
        $mail->AltBody = "Tuan/Puan,\n\nPermohonan pendaftaran sistem MyRFID RMT untuk $school_name telah ditolak."
            . (trim((string)$reason) !== '' ? "\n\nSebab penolakan:\n$reason" : '')
            . "\n\nSekiranya terdapat sebarang pertanyaan atau anda ingin memohon semula, sila hubungi pihak kami." . myrfid_email_signoff();
        $mail->send();
        myrfid_smtp_log('SUCCESS: Rejection email accepted by mail transport.');
        return true;
    } catch (\Throwable $e) {
        myrfid_smtp_log('sendRejectionEmail error: ' . $e->getMessage());
        return false;
    }
}

function sendActivationEmail($to_email, $school_name, $tarikh_luput) {
    if (empty($to_email)) return false;
    try {
        $mail = getMailer();
        $mail->addAddress($to_email, $school_name);
        $mail->isHTML(true);
        $mail->Subject = 'Pengesahan Bayaran dan Pengaktifan Akaun MyRFID RMT';
        $expiry = myrfid_email_expiry($tarikh_luput);
        $content = myrfid_email_paragraph('Tuan/Puan,')
            . myrfid_email_paragraph('Bayaran langganan sistem MyRFID untuk <strong>' . myrfid_email_escape($school_name) . '</strong> telah berjaya disahkan. Akaun sekolah anda kini aktif.')
            . myrfid_email_details(['SEKOLAH' => $school_name, 'STATUS AKAUN' => 'Aktif', 'SAH SEHINGGA' => $expiry])
            . myrfid_email_paragraph('Anda kini boleh log masuk menggunakan nama pengguna dan kata laluan yang telah didaftarkan sebelum ini.')
            . myrfid_email_portal_button();
        $mail->Body = myrfid_email_layout('PENGESAHAN BAYARAN', 'Akaun MyRFID anda kini aktif', 'Bayaran disahkan. Akaun ' . $school_name . ' sah sehingga ' . $expiry . '.', $content, 'E-mel ini dijana secara automatik oleh sistem MyRFID.');
        $portal = myrfid_email_portal_url();
        $mail->AltBody = "Tuan/Puan,\n\nBayaran langganan MyRFID untuk $school_name telah berjaya disahkan.\n\nSekolah: $school_name\nStatus akaun: Aktif\nSah sehingga: $expiry\n\nAnda kini boleh log masuk menggunakan nama pengguna dan kata laluan yang telah didaftarkan sebelum ini."
            . ($portal !== '' ? "\n\nLog masuk MyRFID: $portal" : '') . myrfid_email_signoff();
        $mail->send();
        myrfid_smtp_log('SUCCESS: Activation email accepted by mail transport.');
        return true;
    } catch (\Throwable $e) {
        myrfid_smtp_log('sendActivationEmail error: ' . $e->getMessage());
        return false;
    }
}

function sendTrialActivationEmail($to_email, $school_name, $tarikh_luput) {
    if (empty($to_email)) return false;
    try {
        $mail = getMailer();
        $mail->addAddress($to_email, $school_name);
        $mail->isHTML(true);
        $mail->Subject = 'Pengaktifan Akaun Percubaan MyRFID RMT';
        $expiry = myrfid_email_expiry($tarikh_luput);
        $content = myrfid_email_paragraph('Tuan/Puan,')
            . myrfid_email_paragraph('Pendaftaran akaun percubaan MyRFID RMT untuk <strong>' . myrfid_email_escape($school_name) . '</strong> telah berjaya diluluskan.')
            . myrfid_email_details(['SEKOLAH' => $school_name, 'STATUS AKAUN' => 'Percubaan aktif', 'SAH SEHINGGA' => $expiry])
            . myrfid_email_paragraph('Anda kini boleh log masuk menggunakan nama pengguna dan kata laluan yang telah didaftarkan sebelum ini.')
            . myrfid_email_portal_button();
        $mail->Body = myrfid_email_layout('PENGAKTIFAN PERCUBAAN', 'Akaun percubaan anda kini aktif', 'Akaun percubaan ' . $school_name . ' sah sehingga ' . $expiry . '.', $content, 'E-mel ini dijana secara automatik oleh sistem MyRFID.');
        $portal = myrfid_email_portal_url();
        $mail->AltBody = "Tuan/Puan,\n\nPendaftaran akaun percubaan MyRFID RMT untuk $school_name telah diluluskan.\n\nSekolah: $school_name\nStatus akaun: Percubaan aktif\nSah sehingga: $expiry\n\nSila log masuk menggunakan nama pengguna dan kata laluan yang telah didaftarkan."
            . ($portal !== '' ? "\n\nLog masuk MyRFID: $portal" : '') . myrfid_email_signoff();
        $mail->send();
        myrfid_smtp_log('SUCCESS: Trial email accepted by mail transport.');
        return true;
    } catch (\Throwable $e) {
        myrfid_smtp_log('sendTrialActivationEmail error: ' . $e->getMessage());
        return false;
    }
}

function testSmtpConnection($host, $port, $username, $password, $encryption, $from_email, $from_name, $test_to) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->Port = (int)$port;
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        myrfid_smtp_diagnostics($mail);
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($test_to);
        $mail->isHTML(true);
        $mail->Subject = 'Ujian E-mel - MyRFID';
        $content = myrfid_email_paragraph('Tuan/Puan,')
            . myrfid_email_paragraph('Ini ialah e-mel ujian daripada sistem MyRFID. Jika anda menerima e-mel ini, penghantaran ujian ke alamat anda telah berjaya.')
            . myrfid_email_notice('Tiada tindakan diperlukan', 'E-mel ini dihantar melalui fungsi ujian SMTP. Ia bukan sebut harga atau pengesahan bayaran.');
        $mail->Body = myrfid_email_layout('UJIAN SISTEM', 'Ujian penghantaran e-mel', 'E-mel ujian SMTP daripada sistem MyRFID.', $content, 'E-mel ujian ini dijana secara automatik oleh sistem MyRFID.');
        $mail->AltBody = "Tuan/Puan,\n\nIni ialah e-mel ujian daripada sistem MyRFID. Jika anda menerima e-mel ini, penghantaran ujian ke alamat anda telah berjaya.\n\nTiada tindakan diperlukan. E-mel ini bukan sebut harga atau pengesahan bayaran." . myrfid_email_signoff();
        $mail->CharSet = 'UTF-8';
        $mail->send();
        myrfid_smtp_log('SUCCESS: Test email accepted by mail transport.');
        return ['success' => true, 'msg' => 'E-mel ujian berjaya dihantar ke ' . $test_to];
    } catch (\Throwable $e) {
        $detail = trim((string)($mail->ErrorInfo ?? ''));
        if ($detail === '') $detail = trim($e->getMessage());
        myrfid_smtp_log('SMTP connection test failed: ' . $detail);
        return ['success' => false, 'msg' => 'SMTP gagal. Semak smtp_diagnostic_log.php melalui File Manager.'];
    }
}
