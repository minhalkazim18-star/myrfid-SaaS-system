<?php
require_once __DIR__.'/security.php';
require_once __DIR__.'/db_connect.php';
require_once __DIR__.'/renewal_helper.php';
$account=renewal_account($conn);
if($account['role']==='superadmin'){unset($_SESSION['renewal_only']);header('Location: superadmin_renewals.php');exit;}
$school=renewal_school($conn,(int)$account['school_id']);
$expired=!empty($school['tarikh_luput']) && substr($school['tarikh_luput'],0,10)<date('Y-m-d');
if(!$expired)unset($_SESSION['renewal_only']);else $_SESSION['renewal_only']=true;
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 require_csrf(false);
 try{
  renewal_receipt($conn,(int)($_POST['id']??0),$account,$_FILES['receipt']??[],trim(is_string($_POST['payment_reference']??null)?$_POST['payment_reference']:''));
  renewal_flash('Resit diterima. Sila tunggu pengesahan SuperAdmin.');header('Location: renewal.php',true,303);exit;
 }catch(Throwable $e){error_log('Renewal receipt: '.$e->getMessage());$error=$e instanceof DomainException?$e->getMessage():'Resit gagal disimpan. Cuba lagi atau hubungi pengurusan.';}
}
$rows=renewal_query($conn,'SELECT * FROM subscription_renewals WHERE school_id=? ORDER BY id DESC LIMIT 50','i',[(int)$account['school_id']])->fetch_all(MYSQLI_ASSOC);
require_once __DIR__.'/renewal_view.php';
renewal_header('Pembaharuan langganan',$expired?'renewal.php':'dashboard.php');
?>
<p class="muted"><?php echo escape_html($school['nama_sekolah']); ?> · <?php echo escape_html($school['kod_sekolah']); ?></p>
<div class="notice"><?php echo $expired?'Langganan telah tamat. Akses ini terhad kepada urusan pembaharuan. Data sekolah kekal disimpan.':'Langganan sekolah masih aktif. Pembaharuan awal menyambung tarikh tamat sedia ada.'; ?> Tarikh tamat: <strong><?php echo escape_html($school['tarikh_luput']??'Tiada'); ?></strong></div>
<?php if($m=renewal_message()): ?><div class="notice" role="status"><?php echo escape_html($m); ?></div><?php endif; ?>
<?php if($error): ?><div class="notice error" role="alert"><?php echo escape_html($error); ?></div><?php endif; ?>
<?php if(!$rows): ?><div class="card">Belum ada sebut harga pembaharuan. Sila hubungi pengurusan MyRFID untuk mendapatkan sebut harga.</div><?php endif; ?>
<?php foreach($rows as $r): ?><section class="card"><?php renewal_summary($r); ?>
<?php if($r['rejection_note']): ?><p class="notice error">Resit perlu disemak semula: <?php echo escape_html($r['rejection_note']); ?></p><?php endif; ?>
<?php if(in_array($r['status'],['pending','submitted'],true) && $r['valid_until']>=date('Y-m-d')): ?>
<form method="post" enctype="multipart/form-data" class="actions" data-confirm="Hantar resit ini untuk pengesahan bayaran?">
<?php renewal_hidden((int)$r['id'],'upload'); ?><div class="full"><label>Resit bayaran (PDF, JPG atau PNG; maksimum 5 MB)<input type="file" name="receipt" accept="application/pdf,image/jpeg,image/png" required></label></div><div class="full"><label>Rujukan transaksi<input name="payment_reference" maxlength="150" value="<?php echo escape_html($r['payment_reference']??''); ?>"></label></div><button type="submit" class="primary"><?php echo $r['receipt_path']?'Ganti & hantar semula resit':'Hantar resit'; ?></button></form>
<?php endif; ?></section><?php endforeach; renewal_footer(); ?>