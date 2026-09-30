<?php
require_once __DIR__ . '/security.php';
app_start_session();
$h_sch_id = (int)($_SESSION['school_id'] ?? 0);
$h_nama_sekolah = $_SESSION['nama_sekolah'] ?? "Sekolah Kebangsaan Latihan Harian";
$h_logo_sekolah = $_SESSION['logo_sekolah'] ?? "images/default_logo.png";

if (isset($conn) && $h_sch_id > 0) {
    $res_sch_info = mysqli_query($conn, "SELECT nama_sekolah, logo FROM sekolah WHERE id = '$h_sch_id' LIMIT 1");
    if ($res_sch_info && mysqli_num_rows($res_sch_info) > 0) {
        $sch_row_info = mysqli_fetch_assoc($res_sch_info);
        if (!empty($sch_row_info['nama_sekolah'])) {
            $h_nama_sekolah = $sch_row_info['nama_sekolah'];
            $_SESSION['nama_sekolah'] = $h_nama_sekolah;
        }
        if (!empty($sch_row_info['logo'])) {
            $h_logo_sekolah = $sch_row_info['logo'];
            $_SESSION['logo_sekolah'] = $h_logo_sekolah;
        }
    }
    // Semak juga di jadual tetapan jika wujud override
    $res_cfg = mysqli_query($conn, "SELECT kunci, nilai FROM tetapan WHERE school_id = '$h_sch_id' AND kunci IN ('nama_sekolah', 'logo_sekolah')");
    if ($res_cfg) {
        while ($cfg_row = mysqli_fetch_assoc($res_cfg)) {
            if ($cfg_row['kunci'] === 'nama_sekolah' && !empty($cfg_row['nilai'])) {
                $h_nama_sekolah = $cfg_row['nilai'];
                $_SESSION['nama_sekolah'] = $h_nama_sekolah;
            }
            if ($cfg_row['kunci'] === 'logo_sekolah' && !empty($cfg_row['nilai'])) {
                $h_logo_sekolah = $cfg_row['nilai'];
                $_SESSION['logo_sekolah'] = $h_logo_sekolah;
            }
        }
    }
}

// Pastikan fail logo wujud dan jika masih logosklh, gunakan default_logo.png
if ($h_logo_sekolah === 'images/logosklh.png' || !file_exists($h_logo_sekolah)) {
    $h_logo_sekolah = 'images/default_logo.png';
    $_SESSION['logo_sekolah'] = $h_logo_sekolah;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="utf-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
   <title>MYRFID : <?php echo escape_html($h_nama_sekolah); ?></title>
   <meta name="csrf-token" content="<?php echo escape_html(csrf_token()); ?>">
   <meta name="keywords" content="">
   <meta name="description" content="">
   <meta name="author" content="">
   
   <link rel="stylesheet" href="css/bootstrap.min.css" />
   <link rel="stylesheet" href="style.css?v=<?php echo (int)@filemtime(__DIR__ . '/style.css'); ?>" />
   <style> 
   .sidebar_blog_1 .sidebar-header .logo_section {
       background: transparent !important;
       box-shadow: none !important;        
       border-bottom: 1px solid rgba(255,255,255,0.1); 
       text-align: center;                 
       padding: 20px 0 !important;         
       width: 100%;
       height: auto;
   }

   .logo_section img.logo_icon {
       width: 85px !important;     
       height: 85px !important;   
       border-radius: 50%;         
       object-fit: cover;          
       border: 3px solid rgba(255,255,255,0.25);
       padding: 3px;                
       margin: 0 auto;             
       box-shadow: 0 4px 12px rgba(0,0,0,0.35);
   }
   .logo_section a {
       display: block; 
   }
</style>
   <link rel="stylesheet" href="css/responsive.css?v=<?php echo (int)@filemtime(__DIR__ . '/css/responsive.css'); ?>" />
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
   <link rel="stylesheet" href="css/font-awesome.min.css" />
   <link rel="stylesheet" href="css/perfect-scrollbar.css" />
   <link rel="stylesheet" href="css/animate.css" />
   <link rel="stylesheet" href="css/dark-mode.css" />
   
   <script>
   (() => {
      window.addEventListener('pageshow', event => {
         if (event.persisted) {
            window.location.reload();
         }
      });

      const token = document.querySelector('meta[name="csrf-token"]').content;
      window.CSRF_TOKEN = token;
      const nativeFetch = window.fetch.bind(window);
      window.fetch = (input, init = {}) => {
         const url = new URL(typeof input === 'string' ? input : input.url, window.location.href);
         const method = String(init.method || (typeof input !== 'string' && input.method) || 'GET').toUpperCase();
         if (url.origin === window.location.origin && !['GET', 'HEAD', 'OPTIONS'].includes(method)) {
            const headers = new Headers(init.headers || (typeof input !== 'string' ? input.headers : undefined));
            headers.set('X-CSRF-Token', token);
            init = {...init, headers};
         }
         return nativeFetch(input, init);
      };
      document.addEventListener('DOMContentLoaded', () => {
         document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(form => {
            if (!form.querySelector('input[name="csrf_token"]')) {
               const input = document.createElement('input');
               input.type = 'hidden'; input.name = 'csrf_token'; input.value = token;
               form.appendChild(input);
            }
         });
         if (window.jQuery) {
            window.jQuery(document).ajaxSend((_event, xhr, settings) => {
               const method = String(settings.type || 'GET').toUpperCase();
               const url = new URL(settings.url, window.location.href);
               if (url.origin === window.location.origin && !['GET', 'HEAD', 'OPTIONS'].includes(method)) {
                  xhr.setRequestHeader('X-CSRF-Token', token);
               }
            });
         }
      });
   })();
   </script>
   </head>

<body class="dashboard dashboard_1">
   <div class="full_container">
      <div class="inner_container">
