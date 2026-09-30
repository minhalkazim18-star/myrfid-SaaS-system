<?php
define('FPDF_FONTPATH','font/');

require('fpdf.php');
include 'session_auth.php';
include 'db_connect.php';


$tarikh_mula  = valid_iso_date($_GET['tarikh_mula'] ?? '', date('Y-m-d'));
$tarikh_akhir = valid_iso_date($_GET['tarikh_akhir'] ?? '', date('Y-m-d'));
$filter_kelas = trim((string)($_GET['kelas'] ?? 'Semua'));


$pdf_sch_id = (int)($_SESSION['school_id'] ?? 1);
$sql = 'SELECT * FROM transaksi_rmt WHERE school_id = ? AND tarikh BETWEEN ? AND ?';
$has_class_filter = $filter_kelas !== '' && $filter_kelas !== 'Semua';
if ($has_class_filter) $sql .= ' AND nama_kelas = ?';
$sql .= ' ORDER BY tarikh ASC, waktu ASC';
$stmt = $conn->prepare($sql);
if ($has_class_filter) $stmt->bind_param('isss', $pdf_sch_id, $tarikh_mula, $tarikh_akhir, $filter_kelas);
else $stmt->bind_param('iss', $pdf_sch_id, $tarikh_mula, $tarikh_akhir);
$stmt->execute();
$result = $stmt->get_result();


class PDF extends FPDF
{
    public $school_name = 'SEKOLAH';
    public $school_logo = 'images/default_logo.png';
    public $school_address = '';

    function __construct() {
        parent::__construct();
        if (!empty($_SESSION['nama_sekolah'])) $this->school_name = $_SESSION['nama_sekolah'];
        if (!empty($_SESSION['logo_sekolah']) && $_SESSION['logo_sekolah'] !== 'images/logosklh.png') {
            $this->school_logo = $_SESSION['logo_sekolah'];
        }
    }

    function Header()
    {
        if (file_exists($this->school_logo)) {
            $this->Image($this->school_logo, 10, 6, 20);
        } else if (file_exists('images/default_logo.png')) {
            $this->Image('images/default_logo.png', 10, 6, 20);
        }
        
        $this->SetFont('Arial','B',12);
        
        $this->Cell(0,5,pdf_text(mb_strtoupper($this->school_name)),0,1,'C');
        $this->SetFont('Arial','',10);
        if ($this->school_address !== '') {
            $this->MultiCell(0,5,pdf_text($this->school_address),0,'C');
        }
        $this->Cell(0,5,'Laporan Kehadiran Rancangan Makanan Tambahan (RMT)',0,1,'C');
        $this->Ln(3);
        $lineY = $this->GetY();
        $this->Line(10, $lineY, 200, $lineY);
        $this->Ln(5);
    }

    
    function Footer()
    {
        
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        // Page number
        $this->Cell(0,10,'Muka Surat '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

function pdf_text(mixed $value): string
{
    $text = (string)$value;
    $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
    return $converted === false ? $text : $converted;
}


$pdf_sch_id = (int)($_SESSION['school_id'] ?? 1);
$pdf_nama_sekolah = $_SESSION['nama_sekolah'] ?? 'Sekolah';
$pdf_logo_sekolah = $_SESSION['logo_sekolah'] ?? "images/logosklh.png";
$pdf_alamat_sekolah = '';
if (isset($conn) && $pdf_sch_id > 0) {
    $schoolStmt = $conn->prepare('SELECT nama_sekolah, logo, alamat FROM sekolah WHERE id = ? LIMIT 1');
    $schoolStmt->bind_param('i', $pdf_sch_id);
    $schoolStmt->execute();
    $row_pdf = $schoolStmt->get_result()->fetch_assoc();
    $schoolStmt->close();
    if ($row_pdf) {
        if (!empty($row_pdf['nama_sekolah'])) $pdf_nama_sekolah = $row_pdf['nama_sekolah'];
        if (!empty($row_pdf['logo'])) $pdf_logo_sekolah = $row_pdf['logo'];
        if (!empty($row_pdf['alamat'])) $pdf_alamat_sekolah = $row_pdf['alamat'];
    }
    $settingStmt = $conn->prepare("SELECT kunci, nilai FROM tetapan WHERE school_id = ? AND kunci IN ('nama_sekolah', 'logo_sekolah')");
    $settingStmt->bind_param('i', $pdf_sch_id);
    $settingStmt->execute();
    $res_cfg_pdf = $settingStmt->get_result();
    while ($cfg_pdf = $res_cfg_pdf->fetch_assoc()) {
            if ($cfg_pdf['kunci'] === 'nama_sekolah' && !empty($cfg_pdf['nilai'])) $pdf_nama_sekolah = $cfg_pdf['nilai'];
            if ($cfg_pdf['kunci'] === 'logo_sekolah' && !empty($cfg_pdf['nilai'])) $pdf_logo_sekolah = $cfg_pdf['nilai'];
    }
    $settingStmt->close();
}
if ($pdf_logo_sekolah !== 'images/logosklh.png' && !file_exists($pdf_logo_sekolah)) {
    $pdf_logo_sekolah = 'images/logosklh.png';
}

$pdf = new PDF();
$pdf->school_name = $pdf_nama_sekolah;
$pdf->school_logo = $pdf_logo_sekolah;
$pdf->school_address = $pdf_alamat_sekolah;
$pdf->AliasNbPages(); 
$pdf->AddPage(); 
$pdf->SetFont('Arial','',10);


$pdf->SetFont('Arial','B',10);
$pdf->Cell(30,6,'Tarikh Laporan :',0,0);
$pdf->SetFont('Arial','',10);
$pdf->Cell(50,6, date('d/m/Y', strtotime($tarikh_mula)) . ' - ' . date('d/m/Y', strtotime($tarikh_akhir)), 0, 1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(30,6,'Kelas :',0,0);
$pdf->SetFont('Arial','',10);
$pdf->Cell(50,6, pdf_text($filter_kelas), 0, 1);
$pdf->Ln(5);

// HEADER TABLE
$pdf->SetFillColor(200,220,255); 
$pdf->SetFont('Arial','B',9);
$pdf->Cell(10,8,'Bil',1,0,'C',true);
$pdf->Cell(25,8,'Tarikh',1,0,'C',true);
$pdf->Cell(25,8,'Masa',1,0,'C',true);
$pdf->Cell(80,8,'Nama Pelajar',1,0,'L',true);
$pdf->Cell(30,8,'Kelas',1,0,'C',true);
$pdf->Cell(20,8,'Status',1,1,'C',true);


$pdf->SetFont('Arial','',9);
$bil = 1;

if(mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
        $pdf->Cell(10,7, $bil++, 1, 0,'C');
        $pdf->Cell(25,7, date('d/m/Y', strtotime($row['tarikh'])), 1, 0,'C');
        $pdf->Cell(25,7, date('h:i A', strtotime($row['waktu'])), 1, 0,'C');
        $pdf->Cell(80,7, pdf_text(mb_strtoupper($row['nama_penuh'])), 1, 0,'L');
        $pdf->Cell(30,7, pdf_text($row['nama_kelas']), 1, 0,'C');
        $pdf->Cell(20,7, 'Hadir', 1, 1,'C');
    }
} else {
    $pdf->Cell(190,7,'Tiada rekod dijumpai',1,1,'C');
}


$pdf->Ln(20); 


$y_awal = $pdf->GetY();
$pdf->SetFont('Arial','',10);
$pdf->Cell(95,5,'Disediakan Oleh:',0,0,'C');
$pdf->Cell(95,5,'Disahkan Oleh:',0,1,'C');

$pdf->Ln(20); 


$pdf->SetFont('Arial','B',10);
$pdf->Cell(95,5,pdf_text('( '.mb_strtoupper((string)($_SESSION['nama_penuh'] ?? '')).' )'),0,0,'C');
$pdf->Cell(95,5,'( GURU BESAR )',0,1,'C');


$pdf->SetFont('Arial','',10);
$pdf->Cell(95,5,'Guru Penyelaras RMT',0,0,'C');
$pdf->Cell(95,5,pdf_text($pdf_nama_sekolah),0,1,'C');


$pdf->Output();
?>
