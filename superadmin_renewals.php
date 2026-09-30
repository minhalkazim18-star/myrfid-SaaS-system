<?php
require_once __DIR__.'/security.php';
require_once __DIR__.'/db_connect.php';
require_active_roles($conn,['superadmin'],false);
require_once __DIR__.'/renewal_helper.php';
$schoolId=filter_input(INPUT_GET,'school_id',FILTER_VALIDATE_INT)?:0;
$page=max(1,(int)($_GET['page']??1));$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 require_csrf(false);
 $id=(int)($_POST['id']??0);$action=is_string($_POST['action']??null)?$_POST['action']:'';
 try{
  if($action==='create'){
   $id=renewal_create($conn,(int)($_POST['school_id']??0),(int)$_SESSION['user_id']);
   $message='Sebut harga sedia untuk disemak. Klik Hantar e-mel selepas menyemak PDF.';
  }elseif($action==='send' || $action==='send_confirmation'){
   $sent=renewal_send($conn,$id,$action==='send_confirmation');
   $message=$sent?'E-mel diterima oleh pelayan penghantaran.':'E-mel gagal dihantar. Rekod kekal disimpan; cuba hantar semula.';
  }elseif($action==='confirm'){
   if(($_POST['verified']??'')!=='yes')throw new DomainException('Sahkan bahawa jumlah bayaran telah disemak.');
   renewal_confirm($conn,$id,(int)$_SESSION['user_id'],trim(is_string($_POST['payment_reference']??null)?$_POST['payment_reference']:''));
   $message='Bayaran disahkan dan langganan dilanjutkan.';
   try{$sent=renewal_send($conn,$id,true);$message.=$sent?' E-mel pengesahan dihantar.':' E-mel belum dihantar; gunakan butang hantar pengesahan.';}
   catch(Throwable $mailError){error_log('Renewal confirmation mail: '.$mailError->getMessage());$message.=' Gunakan butang hantar pengesahan untuk menghantar e-mel.';}
  }elseif($action==='cancel' || $action==='reject_receipt'){
   $reason=trim(is_string($_POST['reason']??null)?$_POST['reason']:'');
   if($reason==='' || mb_strlen($reason)>500)throw new DomainException('Isi sebab (maksimum 500 aksara).');
   $conn->begin_transaction();
   try{
    $r=renewal_query($conn,'SELECT * FROM subscription_renewals WHERE id=? FOR UPDATE','i',[$id])->fetch_assoc();
    if(!$r || !in_array($r['status'],['pending','submitted'],true))throw new DomainException('Rekod telah diproses.');
    if($action==='reject_receipt' && $r['status']!=='submitted')throw new DomainException('Tiada resit baharu untuk dipulangkan.');
    if($action==='cancel'){
     renewal_query($conn,"UPDATE subscription_renewals SET status='cancelled',rejection_note=? WHERE id=?",'si',[$reason,$id]);
    }else{
     renewal_query($conn,"UPDATE subscription_renewals SET status='pending',receipt_path=NULL,receipt_mime=NULL,rejection_note=? WHERE id=?",'si',[$reason,$id]);
    }
    record_superadmin_audit($conn,$action==='cancel'?'BATAL_PEMBAHARUAN':'PULANG_RESIT',"{$r['reference']}; $reason");
    $conn->commit();
    if($action==='reject_receipt' && !empty($r['receipt_path']) && is_file($r['receipt_path']))@unlink($r['receipt_path']);
   }catch(Throwable $e){$conn->rollback();throw $e;}
   $message=$action==='cancel'?'Sebut harga dibatalkan. Jana semula jika diperlukan.':'Resit dipulangkan. Sebab dipaparkan pada portal sekolah; maklumkan sekolah untuk memuat naik semula.';
  }else throw new DomainException('Tindakan tidak sah.');
  renewal_flash($message);header('Location: superadmin_renewals.php'.($schoolId?'?school_id='.$schoolId:''),true,303);exit;
 }catch(Throwable $e){error_log('SuperAdmin renewal: '.$e->getMessage());$error=$e instanceof DomainException?$e->getMessage():'Tindakan gagal. Semak pemasangan jadual pembaharuan dan PHP error log.';}
}
$schools=$conn->query("SELECT id,nama_sekolah,kod_sekolah,pelan FROM sekolah WHERE status='aktif' ORDER BY nama_sekolah")->fetch_all(MYSQLI_ASSOC);
$where=$schoolId?' WHERE r.school_id=?':'';$args=$schoolId?[$schoolId]:[];$types=$schoolId?'i':'';
$rows=[];$total=0;$pages=1;
try{
 $total=(int)renewal_query($conn,'SELECT COUNT(*) AS n FROM subscription_renewals r'.$where,$types,$args)->fetch_assoc()['n'];
 $pages=max(1,(int)ceil($total/20));$page=min($page,$pages);
 $rows=renewal_query($conn,'SELECT r.*,s.nama_sekolah,s.tarikh_luput AS current_expiry,s.email_sekolah FROM subscription_renewals r JOIN sekolah s ON s.id=r.school_id'.$where." ORDER BY CASE r.status WHEN 'submitted' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END,r.id DESC LIMIT 20 OFFSET ?",$types.'i',array_merge($args,[($page-1)*20]))->fetch_all(MYSQLI_ASSOC);
}catch(Throwable $e){error_log('Renewal list: '.$e->getMessage());$error='Jadual pembaharuan belum tersedia atau gagal dibaca. Jalankan renewal_migration.sql dahulu.';}
require_once __DIR__.'/renewal_view.php';renewal_header('Urus pembaharuan','superadmin_dashboard.php');
?>
<p class="muted">Urus sebut harga dan sahkan bayaran untuk menyambung langganan sekolah.</p>
<?php if($m=renewal_message()): ?><div class="notice" role="status"><?php echo escape_html($m); ?></div><?php endif; ?>
<?php if($error): ?><div class="notice error" role="alert"><?php echo escape_html($error); ?></div><?php endif; ?>
<section class="card create-panel"><div><h2>Sebut harga pembaharuan</h2><p class="muted">Pilih sekolah untuk menyediakan sebut harga mengikut harga pembaharuan pelan semasa.</p></div><form method="post" class="create-controls"><?php renewal_hidden(0,'create'); ?><label>Sekolah<select name="school_id" required aria-label="Sekolah"><option value="">Pilih sekolah</option><?php foreach($schools as $s): ?><option value="<?php echo (int)$s['id']; ?>" <?php echo $schoolId===(int)$s['id']?'selected':''; ?>><?php echo escape_html($s['nama_sekolah'].' · '.$s['pelan']); ?></option><?php endforeach; ?></select></label><button type="submit" class="primary">Jana sebut harga</button></form></section>
<div class="toolbar"><h2>Rekod pembaharuan <span class="count"><?php echo $total; ?> rekod</span></h2><form method="get" class="filter-form"><select name="school_id" aria-label="Tapis sekolah"><option value="0">Semua sekolah</option><?php foreach($schools as $s): ?><option value="<?php echo (int)$s['id']; ?>" <?php echo $schoolId===(int)$s['id']?'selected':''; ?>><?php echo escape_html($s['nama_sekolah']); ?></option><?php endforeach; ?></select><button>Tapis</button></form></div>
<?php if(!$rows): ?><div class="card empty-state"><strong>Belum ada rekod pembaharuan</strong>Jana sebut harga untuk sekolah atau pilih penapis lain.</div><?php endif; ?>
<?php foreach($rows as $r): ?><section class="card renewal-record"><div class="record-head"><div><h2><?php echo escape_html($r['nama_sekolah']); ?></h2><small><?php echo escape_html($r['email_sekolah']); ?></small></div><small>Langganan semasa sehingga <strong><?php echo escape_html(renewal_display_date($r['current_expiry'])); ?></strong></small></div><div class="record-body"><?php renewal_summary($r); ?></div><div class="record-bottom">
<details class="mail-details"><summary>Sejarah e-mel &amp; transaksi</summary><p>E-mel sebut harga: <?php echo escape_html($r['email_sent_at'] ? renewal_display_date($r['email_sent_at'],true) : 'Belum dihantar'); ?><br>Status penghantaran terakhir: <?php echo escape_html(renewal_mail_label($r['email_status'])); ?><br>E-mel pengesahan: <?php echo escape_html($r['activation_email_at'] ? renewal_display_date($r['activation_email_at'],true) : 'Belum dihantar'); ?><?php if($r['submitted_at']): ?><br>Resit diterima: <?php echo escape_html(renewal_display_date($r['submitted_at'],true)); ?><br>Rujukan transaksi: <?php echo escape_html($r['payment_reference']??'-'); ?><?php endif; ?></p></details>
<?php if($r['rejection_note']): ?><p class="notice"><?php echo escape_html($r['rejection_note']); ?></p><?php endif; ?>
<div class="actions">
<?php if(in_array($r['status'],['pending','submitted'],true)): ?>
<form method="post" data-confirm="Hantar sebut harga ke e-mel sekolah ini?"><?php renewal_hidden((int)$r['id'],'send'); ?><button type="submit">Hantar sebut harga</button></form>
<details class="full"><summary>Sahkan bayaran &amp; lanjutkan langganan</summary><p class="muted">Anggaran tarikh tamat baharu: <strong><?php echo escape_html(renewal_display_date(renewal_next_expiry($r['current_expiry']))); ?></strong>. Dikira semula ketika pengesahan. Semak jumlah RM <?php echo number_format((float)$r['amount'],2); ?> dengan transaksi bank. Sebut harga yang tamat masih boleh disahkan selepas semakan bayaran oleh SuperAdmin.</p><form method="post" data-confirm="Sahkan bayaran ini dan lanjutkan langganan 12 bulan?">
<?php renewal_hidden((int)$r['id'],'confirm'); ?><label>Rujukan transaksi / bayaran diterima melalui e-mel<input name="payment_reference" maxlength="150" value="<?php echo escape_html($r['payment_reference']??''); ?>" <?php echo empty($r['receipt_path'])?'required':''; ?>></label><p><label><input type="checkbox" name="verified" value="yes" required> Saya telah menyemak jumlah dan mengesahkan bayaran diterima.</label></p><button type="submit" class="primary">Sahkan pembaharuan</button></form></details>
<?php if($r['status']==='submitted'): ?><details class="full"><summary>Pulangkan resit untuk pembetulan</summary><form method="post" data-confirm="Pulangkan resit ini kepada sekolah?"><?php renewal_hidden((int)$r['id'],'reject_receipt'); ?><label>Sebab<textarea name="reason" maxlength="500" required></textarea></label><button type="submit">Pulangkan resit</button></form></details><?php endif; ?>
<details class="full"><summary class="danger">Batalkan sebut harga</summary><form method="post" data-confirm="Batalkan sebut harga ini? Pastikan bayaran belum diterima."><?php renewal_hidden((int)$r['id'],'cancel'); ?><label>Sebab<textarea name="reason" maxlength="500" required></textarea></label><button type="submit" class="danger">Batalkan</button></form></details>
<?php elseif($r['status']==='paid'): ?><form method="post" data-confirm="Hantar e-mel pengesahan pembaharuan?"><?php renewal_hidden((int)$r['id'],'send_confirmation'); ?><button type="submit">Hantar pengesahan</button></form><?php endif; ?>
</div></div></section><?php endforeach; ?>
<nav class="row pagination" aria-label="Halaman"><?php if($page>1): ?><a class="button" href="?school_id=<?php echo $schoolId; ?>&amp;page=<?php echo $page-1; ?>">Sebelumnya</a><?php endif; ?><span>Halaman <?php echo $page; ?> / <?php echo $pages; ?></span><?php if($page<$pages): ?><a class="button" href="?school_id=<?php echo $schoolId; ?>&amp;page=<?php echo $page+1; ?>">Seterusnya</a><?php endif; ?></nav>
<?php renewal_footer(); ?>
