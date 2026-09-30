<?php
require_once __DIR__.'/security.php';
require_once __DIR__.'/db_connect.php';
require_once __DIR__.'/renewal_helper.php';
$account=renewal_account($conn);
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT)?:0;
$r=renewal_query($conn,'SELECT * FROM subscription_renewals WHERE id=?','i',[$id])->fetch_assoc();
if (!$r || ($account['role']!=='superadmin' && (int)$r['school_id']!==(int)$account['school_id'])) {http_response_code(404);exit('Dokumen tidak dijumpai.');}
$receipt=($_GET['type']??'')==='receipt';
$path=realpath((string)($receipt?$r['receipt_path']:$r['pdf_path']));
$base=realpath(__DIR__.'/storage/private/'.($receipt?'renewal_receipts':'quotations'));
if (!$path || !$base || !str_starts_with($path,$base.DIRECTORY_SEPARATOR) || !is_file($path)) {http_response_code(404);exit('Dokumen tidak tersedia.');}
$mime=$receipt?(string)$r['receipt_mime']:'application/pdf';
$ext=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'][$mime]??null;
if (!$ext){http_response_code(404);exit;}
header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header("Content-Security-Policy: sandbox");
header('Content-Type: '.$mime);
header('Content-Disposition: attachment; filename="'.($receipt?'Resit-':'').preg_replace('/[^A-Za-z0-9-]/','-',$r['reference']).'.'.$ext.'"');
header('Content-Length: '.filesize($path));readfile($path);exit;