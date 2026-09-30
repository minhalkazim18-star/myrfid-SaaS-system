<?php
require_once 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

function storePrivateQuotation(string $reference, string $contents): string {
    $dir = __DIR__ . '/storage/private/quotations';
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        throw new RuntimeException('Folder sebut harga tidak dapat diwujudkan.');
    }
    $safeReference = preg_replace('/[^A-Za-z0-9._-]/', '-', $reference);
    $filepath = $dir . '/' . $safeReference . '.pdf';
    if (file_put_contents($filepath, $contents, LOCK_EX) === false) {
        throw new RuntimeException('Fail sebut harga tidak dapat disimpan.');
    }
    @chmod($filepath, 0640);
    return $filepath;
}

function generateQuotationPDF($sch, $ref_no, $tarikh_terbit, $tarikh_luput) {
    global $conn; // assume included in caller
    
    // Fetch bank & system settings (global settings dengan school_id = 0 atau 1 untuk superadmin)
    $tetapan = [];
    $res = mysqli_query($conn, "SELECT kunci, nilai FROM tetapan WHERE school_id = 0 OR school_id = 1 ORDER BY school_id ASC");
    if($res) {
        while($row = mysqli_fetch_assoc($res)) {
            $tetapan[$row['kunci']] = $row['nilai'];
        }
    }
    
    $bank_name = htmlspecialchars($tetapan['bank_name'] ?? 'Maybank', ENT_QUOTES, 'UTF-8');
    $bank_acc = htmlspecialchars($tetapan['bank_acc'] ?? '1520 4060 7122', ENT_QUOTES, 'UTF-8');
    $bank_holder = htmlspecialchars($tetapan['bank_holder'] ?? 'Minhal Kazim Bin Mohd Shukri', ENT_QUOTES, 'UTF-8');
    
    $pelan = $sch['pelan'] ?? 'Trial';
    
    // Fetch plan details
    $harga = 'RM 0.00';
    $harga_renewal = 'RM 0.00';
    $kapasiti = 'Sila hubungi admin';
    
    $stmtPlan = $conn->prepare('SELECT * FROM pelan_struktur WHERE nama_pelan = ? LIMIT 1');
    $stmtPlan->bind_param('s', $pelan);
    $stmtPlan->execute();
    $q_p = $stmtPlan->get_result();
    if($row_p = mysqli_fetch_assoc($q_p)) {
        $harga = 'RM ' . number_format($row_p['harga_tahun1'], 2);
        $harga_renewal = 'RM ' . number_format($row_p['harga_renewal'], 2);
        $kapasiti = ($row_p['had_murid'] !== null) ? $row_p['had_murid'] . ' Murid' : 'Tanpa Had (Unlimited)';
    }
    $stmtPlan->close();
    
    $img_logo = __DIR__ . '/images/logo_company.png';
    $img_sign = __DIR__ . '/images/signature.png';

    // Fallback to base64 clear image if file not found to prevent DomPDF error
    $base64_logo = file_exists($img_logo) ? 'data:image/png;base64,' . base64_encode(file_get_contents($img_logo)) : '';
    $base64_sign = file_exists($img_sign) ? 'data:image/png;base64,' . base64_encode(file_get_contents($img_sign)) : '';

    $html = '
    <html>
    <head>
        <style>
            body { font-family: sans-serif; font-size: 13px; line-height: 1.5; color: #333; }
            .header-table { width: 100%; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
            .header-table td { vertical-align: top; }
            .details-table { width: 100%; margin-bottom: 25px; }
            .details-table td { padding: 5px; vertical-align: top; }
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .items-table th, .items-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            .items-table th { background-color: #f8fafc; color: #475569; }
            .bank-details { background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 30px; }
            .signature-block { margin-top: 30px; }
            .footer { margin-top: 40px; font-size: 11px; text-align: center; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        </style>
    </head>
    <body>
        <table class="header-table">
            <tr>
                <td width="60%">
                    '.($base64_logo ? '<img src="'.$base64_logo.'" style="max-height: 60px; margin-bottom: 10px;" /><br>' : '<div style="height:60px; background:#eee; width:150px; text-align:center; line-height:60px; margin-bottom:10px; color:#999;">[Sila muat naik logo]</div>').'
                    <strong style="font-size: 14px;">THE BRIDGE BUSINESS ALLIANCE SDN BHD</strong><br>
                    <span style="font-size: 12px; color: #555;">
                        Suite 4805 3-8, Block 4805 CBD Perdana, Flora CBD, 2,<br>
                        Jalan Perdana, Perdana 2, Cyberjaya, Selangor
                    </span>
                </td>
                <td width="40%" style="text-align: right;">
                    <h1 style="margin: 0; color: #1e293b; font-size: 26px; text-transform: uppercase;">SEBUT HARGA</h1>
                    <p style="margin-top: 5px; color: #64748b; font-size: 13px;">SaaS MyRFID Sistem Pengurusan RMT</p>
                </td>
            </tr>
        </table>
        
        <table class="details-table">
            <tr>
                <td width="50%">
                    <strong>Kepada:</strong><br>
                    '.htmlspecialchars($sch['nama_sekolah']).' ('.htmlspecialchars($sch['kod_sekolah']).')<br>
                    Emel: '.htmlspecialchars($sch['email_sekolah']).'<br>
                    No. Tel: '.htmlspecialchars($sch['no_tel']).'
                </td>
                <td width="50%" style="text-align:right;">
                    <strong>No. Rujukan:</strong> '.$ref_no.'<br>
                    <strong>Tarikh Terbit:</strong> '.date('d/m/Y', strtotime($tarikh_terbit)).'<br>
                    <strong>Tempoh Sah:</strong> Sehingga '.date('d/m/Y', strtotime($tarikh_luput)).'<br>
                </td>
            </tr>
        </table>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Perkara / Pakej</th>
                    <th>Tempoh</th>
                    <th>Harga</th>
                    <th style="text-align:right;">Jumlah (RM)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Langganan Sistem RMT MyRFID - Pelan '.htmlspecialchars($pelan).' (Tahun Pertama)</strong><br>
                        <span style="color: #64748b; font-size: 12px;">
                            Termasuk: Lesen perisian, 1x RFID Reader, Pemasangan, dan Sokongan Teknikal.<br>
                            Kapasiti: '.htmlspecialchars($kapasiti).'
                        </span>
                    </td>
                    <td>1 Tahun</td>
                    <td>'.$harga.'</td>
                    <td style="text-align:right;"><strong>'.$harga.'</strong></td>
                </tr>
            </tbody>
        </table>
        
        <p style="margin-bottom: 20px; font-size: 12px; color: #475569;">
            * Nota: Bayaran pembaharuan untuk tahun kedua dan seterusnya adalah <strong>'.$harga_renewal.'/tahun</strong> (termasuk sokongan dan kemas kini, tanpa perkakasan).
        </p>
        
        <div class="bank-details">
            <h4 style="margin-top:0; margin-bottom:8px;">Maklumat Pembayaran</h4>
            <p style="margin-bottom:0; font-size: 12px;">
                Sila buat bayaran ke akaun berikut dan balas emel ini berserta resit pembayaran:<br>
                <strong>Bank:</strong> '.$bank_name.' &nbsp;|&nbsp; 
                <strong>No. Akaun:</strong> '.$bank_acc.' &nbsp;|&nbsp; 
                <strong>Penama:</strong> '.$bank_holder.'
            </p>
        </div>
        
        <div class="signature-block">
            <p>Yang benar,</p>
            <p><strong>The Bridge Business Alliance Sdn Bhd</strong></p>
            <br>
            '.($base64_sign ? '<img src="'.$base64_sign.'" style="height: 70px;" />' : '<div style="height:70px; width:150px; border-bottom:1px solid #333; margin-bottom:5px;"></div>').'
            <br>
            <strong>Minhal Kazim Bin Mohd Shukri</strong><br>
            Software Engineering & Database Management
        </div>
        
        <div class="footer">
            Dokumen ini dijana oleh komputer.<br>
            MyRFID Sistem Pengurusan RMT &copy; '.date('Y').'
        </div>
    </body>
    </html>';

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $output = $dompdf->output();
    
    return storePrivateQuotation($ref_no, $output);
}

function generateUpgradeQuotationPDF($sch, $ref_no, $tarikh_terbit, $tarikh_luput) {
    global $conn; // assume included in caller
    
    // Fetch bank & system settings
    $tetapan = [];
    $res = mysqli_query($conn, "SELECT kunci, nilai FROM tetapan WHERE school_id = 0 OR school_id = 1 ORDER BY school_id ASC");
    if($res) {
        while($row = mysqli_fetch_assoc($res)) {
            $tetapan[$row['kunci']] = $row['nilai'];
        }
    }
    
    $bank_name = htmlspecialchars($tetapan['bank_name'] ?? 'Maybank', ENT_QUOTES, 'UTF-8');
    $bank_acc = htmlspecialchars($tetapan['bank_acc'] ?? '1520 4060 7122', ENT_QUOTES, 'UTF-8');
    $bank_holder = htmlspecialchars($tetapan['bank_holder'] ?? 'Minhal Kazim Bin Mohd Shukri', ENT_QUOTES, 'UTF-8');
    
    $harga = 'RM 150.00';
    $kapasiti = 'Tanpa Had (Unlimited)';
    
    $img_logo = __DIR__ . '/images/logo_company.png';
    $img_sign = __DIR__ . '/images/signature.png';

    $base64_logo = file_exists($img_logo) ? 'data:image/png;base64,' . base64_encode(file_get_contents($img_logo)) : '';
    $base64_sign = file_exists($img_sign) ? 'data:image/png;base64,' . base64_encode(file_get_contents($img_sign)) : '';

    $html = '
    <html>
    <head>
        <style>
            body { font-family: sans-serif; font-size: 13px; line-height: 1.5; color: #333; }
            .header-table { width: 100%; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
            .header-table td { vertical-align: top; }
            .details-table { width: 100%; margin-bottom: 25px; }
            .details-table td { padding: 5px; vertical-align: top; }
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .items-table th, .items-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            .items-table th { background-color: #f8fafc; color: #475569; }
            .bank-details { background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 30px; }
            .signature-block { margin-top: 30px; }
            .footer { margin-top: 40px; font-size: 11px; text-align: center; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        </style>
    </head>
    <body>
        <table class="header-table">
            <tr>
                <td width="60%">
                    '.($base64_logo ? '<img src="'.$base64_logo.'" style="max-height: 60px; margin-bottom: 10px;" /><br>' : '<div style="height:60px; background:#eee; width:150px; text-align:center; line-height:60px; margin-bottom:10px; color:#999;">[Sila muat naik logo]</div>').'
                    <strong style="font-size: 14px;">THE BRIDGE BUSINESS ALLIANCE SDN BHD</strong><br>
                    <span style="font-size: 12px; color: #555;">
                        Suite 4805 3-8, Block 4805 CBD Perdana, Flora CBD, 2,<br>
                        Jalan Perdana, Perdana 2, Cyberjaya, Selangor
                    </span>
                </td>
                <td width="40%" style="text-align: right;">
                    <h1 style="margin: 0; color: #1e293b; font-size: 26px; text-transform: uppercase;">SEBUT HARGA</h1>
                    <p style="margin-top: 5px; color: #64748b; font-size: 13px;">Naik Taraf Sistem Pengurusan RMT</p>
                </td>
            </tr>
        </table>
        
        <table class="details-table">
            <tr>
                <td width="50%">
                    <strong>Kepada:</strong><br>
                    '.htmlspecialchars($sch['nama_sekolah']).' ('.htmlspecialchars($sch['kod_sekolah']).')<br>
                    Emel: '.htmlspecialchars($sch['email_sekolah']).'<br>
                    No. Tel: '.htmlspecialchars($sch['no_tel']).'
                </td>
                <td width="50%" style="text-align:right;">
                    <strong>No. Rujukan:</strong> '.$ref_no.'<br>
                    <strong>Tarikh Terbit:</strong> '.date('d/m/Y', strtotime($tarikh_terbit)).'<br>
                    <strong>Tempoh Sah:</strong> Sehingga '.date('d/m/Y', strtotime($tarikh_luput)).'<br>
                </td>
            </tr>
        </table>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Perkara / Pakej</th>
                    <th>Tempoh</th>
                    <th>Harga</th>
                    <th style="text-align:right;">Jumlah (RM)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Naik Taraf Pelan (Basic ke Pro)</strong><br>
                        <span style="color: #64748b; font-size: 12px;">
                            Naik taraf kapasiti pendaftaran murid RMT kepada <strong>'.htmlspecialchars($kapasiti).'</strong>.<br>
                            Mengekalkan tempoh tarikh luput sedia ada.
                        </span>
                    </td>
                    <td>-</td>
                    <td>'.$harga.'</td>
                    <td style="text-align:right;"><strong>'.$harga.'</strong></td>
                </tr>
            </tbody>
        </table>
        
        <div class="bank-details">
            <h4 style="margin-top:0; margin-bottom:8px;">Maklumat Pembayaran</h4>
            <p style="margin-bottom:0; font-size: 12px;">
                Sila buat bayaran ke akaun berikut dan balas emel ini berserta resit pembayaran:<br>
                <strong>Bank:</strong> '.$bank_name.' &nbsp;|&nbsp; 
                <strong>No. Akaun:</strong> '.$bank_acc.' &nbsp;|&nbsp; 
                <strong>Penama:</strong> '.$bank_holder.'
            </p>
        </div>
        
        <div class="signature-block">
            <p>Yang benar,</p>
            <p><strong>The Bridge Business Alliance Sdn Bhd</strong></p>
            <br>
            '.($base64_sign ? '<img src="'.$base64_sign.'" style="height: 70px;" />' : '<div style="height:70px; width:150px; border-bottom:1px solid #333; margin-bottom:5px;"></div>').'
            <br>
            <strong>Minhal Kazim Bin Mohd Shukri</strong><br>
            Digital Solutions Consultant
        </div>
        
        <div class="footer">
            Dokumen ini dijana oleh komputer.<br>
            SaaS MyRFID Sistem Pengurusan RMT &copy; '.date('Y').'
        </div>
    </body>
    </html>';

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $output = $dompdf->output();
    
    return storePrivateQuotation($ref_no, $output);
}

function generateRenewalQuotationPDF($sch, $ref_no, $tarikh_terbit, $tarikh_luput, $renewalAmount) {
    global $conn; // assume included in caller
    
    // Fetch bank & system settings (global settings dengan school_id = 0 atau 1 untuk superadmin)
    $tetapan = [];
    $res = mysqli_query($conn, "SELECT kunci, nilai FROM tetapan WHERE school_id = 0 OR school_id = 1 ORDER BY school_id ASC");
    if($res) {
        while($row = mysqli_fetch_assoc($res)) {
            $tetapan[$row['kunci']] = $row['nilai'];
        }
    }
    
    $bank_name = htmlspecialchars($tetapan['bank_name'] ?? 'Maybank', ENT_QUOTES, 'UTF-8');
    $bank_acc = htmlspecialchars($tetapan['bank_acc'] ?? '1520 4060 7122', ENT_QUOTES, 'UTF-8');
    $bank_holder = htmlspecialchars($tetapan['bank_holder'] ?? 'Minhal Kazim Bin Mohd Shukri', ENT_QUOTES, 'UTF-8');
    
    $pelan = $sch['pelan'] ?? 'Trial';
    
    // Fetch plan details
    $harga = 'RM 0.00';
    $harga_renewal = 'RM 0.00';
    $kapasiti = 'Sila hubungi admin';
    
    $stmtPlan = $conn->prepare('SELECT * FROM pelan_struktur WHERE nama_pelan = ? LIMIT 1');
    $stmtPlan->bind_param('s', $pelan);
    $stmtPlan->execute();
    $q_p = $stmtPlan->get_result();
    if($row_p = mysqli_fetch_assoc($q_p)) {
        $harga = 'RM ' . number_format($row_p['harga_tahun1'], 2);
        $harga_renewal = 'RM ' . number_format($row_p['harga_renewal'], 2);
        $kapasiti = ($row_p['had_murid'] !== null) ? $row_p['had_murid'] . ' Murid' : 'Tanpa Had (Unlimited)';
    }
    $stmtPlan->close();
    
    $harga = 'RM ' . number_format((float)$renewalAmount, 2);
    $img_logo = __DIR__ . '/images/logo_company.png';
    $img_sign = __DIR__ . '/images/signature.png';

    // Fallback to base64 clear image if file not found to prevent DomPDF error
    $base64_logo = file_exists($img_logo) ? 'data:image/png;base64,' . base64_encode(file_get_contents($img_logo)) : '';
    $base64_sign = file_exists($img_sign) ? 'data:image/png;base64,' . base64_encode(file_get_contents($img_sign)) : '';

    $html = '
    <html>
    <head>
        <style>
            body { font-family: sans-serif; font-size: 13px; line-height: 1.5; color: #333; }
            .header-table { width: 100%; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
            .header-table td { vertical-align: top; }
            .details-table { width: 100%; margin-bottom: 25px; }
            .details-table td { padding: 5px; vertical-align: top; }
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .items-table th, .items-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            .items-table th { background-color: #f8fafc; color: #475569; }
            .bank-details { background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 30px; }
            .signature-block { margin-top: 30px; }
            .footer { margin-top: 40px; font-size: 11px; text-align: center; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        </style>
    </head>
    <body>
        <table class="header-table">
            <tr>
                <td width="60%">
                    '.($base64_logo ? '<img src="'.$base64_logo.'" style="max-height: 60px; margin-bottom: 10px;" /><br>' : '<div style="height:60px; background:#eee; width:150px; text-align:center; line-height:60px; margin-bottom:10px; color:#999;">[Sila muat naik logo]</div>').'
                    <strong style="font-size: 14px;">THE BRIDGE BUSINESS ALLIANCE SDN BHD</strong><br>
                    <span style="font-size: 12px; color: #555;">
                        Suite 4805 3-8, Block 4805 CBD Perdana, Flora CBD, 2,<br>
                        Jalan Perdana, Perdana 2, Cyberjaya, Selangor
                    </span>
                </td>
                <td width="40%" style="text-align: right;">
                    <h1 style="margin: 0; color: #1e293b; font-size: 26px; text-transform: uppercase;">SEBUT HARGA</h1>
                    <p style="margin-top: 5px; color: #64748b; font-size: 13px;">SaaS MyRFID Sistem Pengurusan RMT</p>
                </td>
            </tr>
        </table>
        
        <table class="details-table">
            <tr>
                <td width="50%">
                    <strong>Kepada:</strong><br>
                    '.htmlspecialchars($sch['nama_sekolah']).' ('.htmlspecialchars($sch['kod_sekolah']).')<br>
                    Emel: '.htmlspecialchars($sch['email_sekolah']).'<br>
                    No. Tel: '.htmlspecialchars($sch['no_tel']).'
                </td>
                <td width="50%" style="text-align:right;">
                    <strong>No. Rujukan:</strong> '.$ref_no.'<br>
                    <strong>Tarikh Terbit:</strong> '.date('d/m/Y', strtotime($tarikh_terbit)).'<br>
                    <strong>Tempoh Sah:</strong> Sehingga '.date('d/m/Y', strtotime($tarikh_luput)).'<br>
                </td>
            </tr>
        </table>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Perkara / Pakej</th>
                    <th>Tempoh</th>
                    <th>Harga</th>
                    <th style="text-align:right;">Jumlah (RM)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Langganan Sistem RMT MyRFID - Pelan '.htmlspecialchars($pelan).' (Pembaharuan Tahunan)</strong><br>
                        <span style="color: #64748b; font-size: 12px;">
                            Termasuk: Lesen perisian, sokongan dan kemas kini sistem. Tanpa perkakasan atau pemasangan baharu.<br>
                            Kapasiti: '.htmlspecialchars($kapasiti).'
                        </span>
                    </td>
                    <td>1 Tahun</td>
                    <td>'.$harga.'</td>
                    <td style="text-align:right;"><strong>'.$harga.'</strong></td>
                </tr>
            </tbody>
        </table>
        
        <p style="margin-bottom: 20px; font-size: 12px; color: #475569;">
            * Tempoh pembaharuan ialah 12 bulan. Bayaran awal menyambung tarikh tamat sedia ada; bagi langganan yang telah tamat, tempoh bermula pada tarikh pengaktifan semula. Tarikh akhir disahkan selepas bayaran diluluskan. Harga dalam dokumen ini sah sehingga tarikh yang dinyatakan.
        </p>
        
        <div class="bank-details">
            <h4 style="margin-top:0; margin-bottom:8px;">Maklumat Pembayaran</h4>
            <p style="margin-bottom:0; font-size: 12px;">
                Sila buat bayaran ke akaun berikut. Muat naik resit di portal pembaharuan atau balas e-mel sebut harga bersama resit:<br>
                <strong>Bank:</strong> '.$bank_name.' &nbsp;|&nbsp; 
                <strong>No. Akaun:</strong> '.$bank_acc.' &nbsp;|&nbsp; 
                <strong>Penama:</strong> '.$bank_holder.'
            </p>
        </div>
        
        <div class="signature-block">
            <p>Yang benar,</p>
            <p><strong>The Bridge Business Alliance Sdn Bhd</strong></p>
            <br>
            '.($base64_sign ? '<img src="'.$base64_sign.'" style="height: 70px;" />' : '<div style="height:70px; width:150px; border-bottom:1px solid #333; margin-bottom:5px;"></div>').'
            <br>
            <strong>Minhal Kazim Bin Mohd Shukri</strong><br>
            Software Engineering & Database Management
        </div>
        
        <div class="footer">
            Dokumen ini dijana oleh komputer.<br>
            MyRFID Sistem Pengurusan RMT &copy; '.date('Y').'
        </div>
    </body>
    </html>';

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $output = $dompdf->output();
    
    return storePrivateQuotation($ref_no, $output);
}
