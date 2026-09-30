<?php
require_once __DIR__ . '/security.php';
function renewal_query(mysqli $conn, string $sql, string $types = '', array $args = []) {
    $stmt = $conn->prepare($sql);
    if ($types !== '') $stmt->bind_param($types, ...$args);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}
function renewal_account(mysqli $conn): array {
    // This page performs its own current-account check, including revoked sessions.
    app_start_session();
    if (!isset($_SESSION['user_id'])) { header('Location: index.php?next=renewal'); exit; }
    require_login(false);
    $row = renewal_query($conn, 'SELECT u.id,u.role,u.school_id,u.auth_version,s.status AS school_status FROM users u LEFT JOIN sekolah s ON s.id=u.school_id WHERE u.id=?', 'i', [(int)$_SESSION['user_id']])->fetch_assoc();
    if (!$row || !isset($_SESSION['auth_version']) || (int)$row['auth_version'] !== (int)$_SESSION['auth_version']) {
        session_unset(); session_destroy(); header('Location: index.php'); exit;
    }
    if ($row['role'] !== 'superadmin' && ($row['role'] !== 'admin' || $row['school_status'] !== 'aktif' || (int)$row['school_id'] < 1)) {
        http_response_code(403); exit('Pembaharuan hanya boleh diurus oleh pentadbir sekolah yang tidak digantung.');
    }
    return $row;
}
function renewal_next_expiry(?string $expiry, ?string $today = null): string {
    $today = $today ?? date('Y-m-d');
    $base = ($expiry && substr($expiry,0,10) > $today) ? substr($expiry,0,10) : $today;
    $d = new DateTimeImmutable($base);
    $year = (int)$d->format('Y') + 1; $month = (int)$d->format('m'); $day = (int)$d->format('d');
    while (!checkdate($month,$day,$year)) $day--;
    return sprintf('%04d-%02d-%02d',$year,$month,$day);
}
function renewal_school(mysqli $conn, int $id, bool $lock = false): array {
    $school = renewal_query($conn, 'SELECT * FROM sekolah WHERE id=?' . ($lock ? ' FOR UPDATE' : ''), 'i', [$id])->fetch_assoc();
    if (!$school) throw new DomainException('Sekolah tidak dijumpai.');
    return $school;
}
function renewal_create(mysqli $conn, int $schoolId, int $adminId): int {
    $file = null;
    $conn->begin_transaction();
    try {
        $school = renewal_school($conn,$schoolId,true);
        if ($school['status'] !== 'aktif') throw new DomainException('Sekolah mesti berstatus aktif dan tidak digantung. Langganan yang tamat masih boleh diperbaharui.');
        $open = renewal_query($conn,"SELECT id FROM subscription_renewals WHERE school_id=? AND status IN ('pending','submitted') LIMIT 1",'i',[$schoolId])->fetch_assoc();
        if ($open) { $conn->commit(); return (int)$open['id']; }
        $plan = renewal_query($conn,'SELECT nama_pelan,harga_renewal FROM pelan_struktur WHERE nama_pelan=? LIMIT 1','s',[$school['pelan']])->fetch_assoc();
        if (!$plan || !is_numeric($plan['harga_renewal']) || (float)$plan['harga_renewal'] <= 0) throw new DomainException('Tetapkan harga pembaharuan berbayar untuk pelan sekolah ini terlebih dahulu. Pelan Trial perlu ditukar kepada pelan berbayar.');
        if (!filter_var($school['email_sekolah'],FILTER_VALIDATE_EMAIL)) throw new DomainException('E-mel sekolah tidak sah. Betulkan maklumat sekolah dahulu.');
        $ref = 'REN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(6)));
        $issued = date('Y-m-d'); $valid = date('Y-m-d',strtotime('+14 days'));
        require_once __DIR__ . '/generate_quotation.php';
        $file = generateRenewalQuotationPDF($school,$ref,$issued,$valid,$plan['harga_renewal']);
        renewal_query($conn,'INSERT INTO subscription_renewals (school_id,reference,plan_name,amount,issued_on,valid_until,pdf_path,created_by) VALUES (?,?,?,?,?,?,?,?)','issssssi',[$schoolId,$ref,$plan['nama_pelan'],(string)$plan['harga_renewal'],$issued,$valid,$file,$adminId]);
        $id = (int)renewal_query($conn, 'SELECT id FROM subscription_renewals WHERE reference=?', 's', [$ref])->fetch_assoc()['id'];
        record_superadmin_audit($conn,'JANA_PEMBAHARUAN',"Sekolah #$schoolId; sebut harga $ref");
        $conn->commit(); return $id;
    } catch (Throwable $e) { $conn->rollback(); if ($file && is_file($file)) @unlink($file); throw $e; }
}
function renewal_send(mysqli $conn, int $id, bool $confirmation = false): bool {
    $row = renewal_query($conn,'SELECT r.*,s.nama_sekolah,s.email_sekolah,s.tarikh_luput FROM subscription_renewals r JOIN sekolah s ON s.id=r.school_id WHERE r.id=?','i',[$id])->fetch_assoc();
    if (!$row) throw new DomainException('Rekod tidak dijumpai.');
    if ($confirmation ? $row['status'] !== 'paid' : !in_array($row['status'],['pending','submitted'],true)) throw new DomainException('Status rekod tidak sesuai untuk e-mel ini.');
    if (!$confirmation && $row['valid_until'] < date('Y-m-d')) throw new DomainException('Sebut harga telah tamat. Batalkan rekod lama dan jana semula selepas menyemak bayaran.');
    // Atomic claim prevents rapid double-click sends. Failed sends remain retryable after 60 seconds.
    $stmt=$conn->prepare("UPDATE subscription_renewals SET email_last_attempt=NOW(),email_status='sending' WHERE id=? AND (email_last_attempt IS NULL OR email_last_attempt < DATE_SUB(NOW(), INTERVAL 60 SECOND))");
    $stmt->bind_param('i',$id);$stmt->execute();$claimed=$stmt->affected_rows===1;$stmt->close();
    if (!$claimed) throw new DomainException('Sila tunggu 60 saat sebelum menghantar semula.');
    require_once __DIR__ . '/email_helper.php';
    $sent = sendRenewalEmail($row,$confirmation);
    renewal_query($conn,'UPDATE subscription_renewals SET email_status=?' . ($sent ? ($confirmation ? ',activation_email_at=NOW()' : ',email_sent_at=NOW()') : '') . ' WHERE id=?','si',[$sent?'sent':'failed',$id]);
    return $sent;
}
function renewal_confirm(mysqli $conn, int $id, int $adminId, string $reference): void {
    if (mb_strlen($reference)>150) throw new DomainException('Rujukan bayaran maksimum 150 aksara.');
    $lookup=renewal_query($conn,'SELECT school_id FROM subscription_renewals WHERE id=?','i',[$id])->fetch_assoc();
    if (!$lookup) throw new DomainException('Rekod tidak dijumpai.');
    $conn->begin_transaction();
    try {
        $school=renewal_school($conn,(int)$lookup['school_id'],true);
        $r=renewal_query($conn,'SELECT * FROM subscription_renewals WHERE id=? FOR UPDATE','i',[$id])->fetch_assoc();
        if (!$r || !in_array($r['status'],['pending','submitted'],true)) throw new DomainException('Rekod sudah diproses. Tiada lanjutan tambahan dibuat.');
        if ($school['status'] !== 'aktif') throw new DomainException('Sekolah digantung atau tidak aktif. Semak status sekolah terlebih dahulu.');
        if ($school['pelan'] !== $r['plan_name']) throw new DomainException('Pelan sekolah telah berubah. Semak dan jana sebut harga baharu.');
        if (empty($r['receipt_path']) && $reference==='') throw new DomainException('Isi rujukan bayaran yang diterima melalui e-mel/bank jika tiada resit dimuat naik.');
        $expiry=renewal_next_expiry($school['tarikh_luput']);
        renewal_query($conn,"UPDATE sekolah SET tarikh_luput=?,status_bayaran='Lunas' WHERE id=?",'si',[$expiry,(int)$school['id']]);
        renewal_query($conn,"UPDATE subscription_renewals SET status='paid',confirmed_by=?,confirmed_at=NOW(),new_expiry=?,payment_reference=? WHERE id=?",'issi',[$adminId,$expiry,$reference!==''?$reference:$r['payment_reference'],$id]);
        record_superadmin_audit($conn,'SAH_PEMBAHARUAN',"{$r['reference']}; sekolah #{$school['id']}; RM {$r['amount']}; tamat baharu $expiry");
        $conn->commit();
    } catch(Throwable $e){$conn->rollback();throw $e;}
}
function renewal_receipt(mysqli $conn, int $id, array $account, array $file, string $reference): void {
    if (mb_strlen($reference)>150) throw new DomainException('Rujukan maksimum 150 aksara.');
    if (($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']??'')) throw new DomainException('Pilih fail resit untuk dimuat naik.');
    if ((int)$file['size']>5*1024*1024 || (int)$file['size']<1) throw new DomainException('Saiz resit mestilah antara 1 bait hingga 5 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'][$mime]??null;
    if (!$ext) throw new DomainException('Resit mesti PDF, JPG atau PNG.');
    $newPath=null;
    $conn->begin_transaction();
    try {
        $r=renewal_query($conn,'SELECT * FROM subscription_renewals WHERE id=? AND school_id=? FOR UPDATE','ii',[$id,(int)$account['school_id']])->fetch_assoc();
        if (!$r || !in_array($r['status'],['pending','submitted'],true)) throw new DomainException('Rekod tidak boleh menerima resit.');
        if ($r['valid_until']<date('Y-m-d')) throw new DomainException('Sebut harga tamat. Hubungi pentadbir untuk semakan sebelum bayaran.');
        $dir=__DIR__.'/storage/private/renewal_receipts';
        if (!is_dir($dir) && !mkdir($dir,0750,true) && !is_dir($dir)) throw new RuntimeException('Receipt directory unavailable');
        $newPath=$dir.'/'.bin2hex(random_bytes(24)).'.'.$ext;
        if (!move_uploaded_file($file['tmp_name'],$newPath)) throw new RuntimeException('Receipt upload failed');
        @chmod($newPath,0640);
        renewal_query($conn,"UPDATE subscription_renewals SET status='submitted',receipt_path=?,receipt_mime=?,payment_reference=?,submitted_by=?,submitted_at=NOW(),rejection_note=NULL WHERE id=?",'sssii',[$newPath,$mime,$reference,(int)$account['id'],$id]);
        $conn->commit();
        if (!empty($r['receipt_path']) && is_file($r['receipt_path'])) @unlink($r['receipt_path']);
    }catch(Throwable $e){$conn->rollback();if($newPath && is_file($newPath))@unlink($newPath);throw $e;}
}
function renewal_status(string $value): string {
    return ['pending'=>'Menunggu bayaran','submitted'=>'Menunggu pengesahan','paid'=>'Selesai','cancelled'=>'Dibatalkan'][$value]??$value;
}
function renewal_flash(string $text): void { $_SESSION['renewal_flash']=$text; }
function renewal_message(): string { $m=(string)($_SESSION['renewal_flash']??'');unset($_SESSION['renewal_flash']);return $m; }