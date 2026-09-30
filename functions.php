<?php
require_once __DIR__ . '/security.php';


function log_aktiviti($conn, $aktiviti) {
    try {
        date_default_timezone_set("Asia/Kuala_Lumpur"); 
        app_start_session();
        $user_id = (int)($_SESSION['user_id'] ?? 0);
        $sch_id = (int)($_SESSION['school_id'] ?? 1);
        $nama_user = $_SESSION['nama_penuh'] ?? $_SESSION['username'] ?? 'System'; 
        $masa = date('Y-m-d H:i:s');

        $stmt = @$conn->prepare("INSERT INTO activity_logs (school_id, user_id, nama_user, aktiviti, masa) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iisss", $sch_id, $user_id, $nama_user, $aktiviti, $masa);
            $stmt->execute();
            $stmt->close();
        } else {
            // Fallback jika tiada school_id atau id
            $stmt2 = @$conn->prepare("INSERT INTO activity_logs (user_id, nama_user, aktiviti, masa) VALUES (?, ?, ?, ?)");
            if ($stmt2) {
                $stmt2->bind_param("isss", $user_id, $nama_user, $aktiviti, $masa);
                $stmt2->execute();
                $stmt2->close();
            }
        }
    } catch (Throwable $t) {
        // Abaikan ralat log supaya tidak menggangu proses utama AJAX
    }
}

function popup_and_redirect($msg, $url, $icon = 'info') {
    // Tukar \n dan newline sebenar kepada <br> untuk HTML
    $msg = str_replace(['\r\n', '\n', "\r\n", "\n"], '<br>', $msg);
    $safeMsg = json_encode($msg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $safeUrl = json_encode($url, JSON_UNESCAPED_SLASHES);
    $safeIcon = json_encode($icon, JSON_UNESCAPED_SLASHES);
    echo "<!DOCTYPE html><html><head>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4'></script>
    <style>body{background:#f8fafc; font-family:sans-serif;}</style>
    </head><body><script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Makluman',
            html: $safeMsg,
            icon: $safeIcon,
            confirmButtonColor: '#2563eb',
            allowOutsideClick: false
        }).then((result) => {
            window.location.href = $safeUrl;
        });
    });
    </script></body></html>";
    exit;
}
?>
