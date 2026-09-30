<?php
require_once __DIR__ . '/security.php';
app_start_session();
$sch_id = isset($_SESSION['user_id']) ? current_school_id() : 0;
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sistem Kehadiran RMT - Terminal Kiosk Rasmi</title>
    
    <!-- Favicon & PWA App Icon -->
    <link rel="icon" href="images/app_icon.png" type="image/png">
    <link rel="apple-touch-icon" href="images/app_icon.png">
    <link rel="manifest" href="manifest.webmanifest">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="theme-color" content="#0a2540">
    <meta name="csrf-token" content="<?php echo escape_html($csrf); ?>">
    <meta name="apple-mobile-web-app-title" content="Kiosk RMT">
    <meta name="mobile-web-app-capable" content="yes">
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) window.location.reload();
        });
    </script>
    
    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Roboto+Mono:wght@600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-navy: #0a2540;
            --accent-blue: #0d6efd;
            --accent-gold: #ffc107;
            --bg-light: #f0f4f8;
            --card-border: #cbd5e1;
            --text-main: #0f172a;
            --text-muted: #475569;
        }

        body {
            background-color: var(--bg-light);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        /* Formal White Card Box */
        .card-formal {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(10, 37, 64, 0.08);
            transition: all 0.3s ease;
        }

        /* Top Header (Royal Navy KPM Style) */
        .header-navy {
            background-color: var(--primary-navy);
            color: #ffffff;
            padding: 18px 35px;
            border-bottom: 5px solid var(--accent-gold);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .school-logo-box {
            background: #ffffff;
            padding: 6px;
            border-radius: 12px;
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .school-logo {
            width: 65px;
            height: 65px;
            object-fit: contain;
        }

        .school-title {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin: 0;
            color: #ffffff;
            text-transform: uppercase;
        }

        /* Digital Clock */
        .clock-display {
            font-family: 'Roboto Mono', monospace;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent-gold);
            line-height: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .date-display {
            font-size: 1.05rem;
            font-weight: 600;
            color: #e2e8f0;
            margin-top: 4px;
        }

        /* Scan Zone Target */
        .scan-zone {
            background: #f8fafc;
            border: 3px dashed var(--accent-blue);
            border-radius: 20px;
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: inset 0 0 20px rgba(13, 110, 253, 0.05);
        }

        .scan-zone:hover {
            background: #f0f7ff;
            border-color: #0a58ca;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(13, 110, 253, 0.15);
        }

        .scan-icon-img {
            width: 105px;
            height: auto;
            margin-bottom: 15px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        /* High Contrast Result Banner */
        .result-container {
            border-radius: 16px;
            padding: 25px 20px;
            margin-top: 20px;
            text-align: center;
            min-height: 155px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border: 2px solid #cbd5e1;
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        .result-idle {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #334155;
        }

        .result-success {
            background-color: #d1e7dd !important;
            border: 3px solid #198754 !important;
            color: #0f5132 !important;
            box-shadow: 0 8px 25px rgba(25, 135, 84, 0.25);
        }

        .result-duplicate {
            background-color: #fff3cd !important;
            border: 3px solid #ffc107 !important;
            color: #664d03 !important;
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.25);
        }

        .result-error {
            background-color: #f8d7da !important;
            border: 3px solid #dc3545 !important;
            color: #842029 !important;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.25);
        }

        .result-student-name {
            font-size: 2.1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .result-student-class {
            font-size: 1.35rem;
            font-weight: 700;
        }

        /* Stats Counter Boxes */
        .stat-box {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            height: 100%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }

        .stat-num {
            font-size: 3.2rem;
            font-weight: 800;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 5px 0 0 0;
            line-height: 1.1;
        }

        /* Formal Table */
        .table-recent th {
            background-color: #1e293b !important;
            color: #ffffff !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.95rem;
            padding: 14px 18px;
            border: none;
        }

        .table-recent td {
            padding: 15px 18px;
            font-size: 1.15rem;
            font-weight: 600;
            color: #1e293b;
            vertical-align: middle;
        }

        /* Invisible Input */
        #rfid_input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        /* Onscreen Keypad */
        .keypad-btn {
            height: 60px;
            font-size: 1.5rem;
            font-weight: 700;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #0f172a;
        }
        .keypad-btn:active {
            background: #0d6efd;
            color: #ffffff;
        }

        .terminal-state {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 11px;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #dbe4ee;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .terminal-state-dot {
            width: 8px;
            height: 8px;
            background: #f59e0b;
            border-radius: 50%;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.12);
        }

        .terminal-state.is-online .terminal-state-dot {
            background: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
        }

        .terminal-state.is-offline .terminal-state-dot {
            background: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
        }
    </style>
</head>
<body>
<!-- ========================================================================= -->
<!-- TERMINAL KIOSK RMT UTAMA (FORMAL, JELAS, HIGH CONTRAST & FULLSCREEN)      -->
<!-- ========================================================================= -->

<!-- SIMPLE CENTERED LAYOUT -->
<div class="container d-flex flex-column align-items-center justify-content-center flex-grow-1 py-5">
    <div class="card-formal p-4 p-md-5 text-center position-relative" style="max-width: 650px; width: 100%; padding-top: 4.5rem !important;">
        
        <!-- Fullscreen Button (Top Right) -->
        <button onclick="toggleFullScreen()" class="btn btn-sm btn-light position-absolute" style="top: 15px; right: 15px; border-radius: 8px;" title="Skrin Penuh (F11)">
            <i class="fa fa-arrows-alt text-muted"></i>
        </button>

        <div id="terminal_state" class="terminal-state position-absolute" style="top: 15px; left: 15px;">
            <span class="terminal-state-dot"></span>
            <span id="terminal_state_text">Menyemak terminal</span>
        </div>

        <!-- School Logo & Name -->
        <img id="kiosk_logo" src="images/logo_drs.png" style="height: 90px; margin-bottom: 20px; object-fit: contain;" alt="Logo Sekolah">
        <h2 id="kiosk_school_name" style="font-weight: 800; color: #0a2540; margin-bottom: 5px; text-transform: uppercase;">MEMUATKAN DATA...</h2>
        <p id="clock_date" class="text-muted" style="font-weight: 600; font-size: 1.1rem; margin-bottom: 15px;">Tarikh Hari Ini</p>
        
        <!-- Minimalist Stats & Clock Pill -->
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
            <span class="badge bg-dark px-3 py-2 shadow-sm" style="font-size: 1.1rem;">
                <i class="fa fa-clock-o me-1 text-warning"></i> <span id="clock_time" style="font-family: 'Roboto Mono', monospace;">00:00:00</span>
            </span>
            <span class="badge bg-success px-3 py-2 shadow-sm" style="font-size: 1.1rem;" title="Jumlah Kehadiran Hari Ini">
                <i class="fa fa-users me-1"></i> Hadir: <span id="stat_hadir">0</span> / <span id="stat_layak">0</span>
            </span>
        </div>

        <!-- Scan Prompt -->
        <div class="mt-2 mb-3" onclick="focusInput()" style="cursor: pointer;">
            <img src="images/touch.png" style="width: 110px; animation: float 3s infinite ease-in-out;" alt="Touch Icon">
            <h4 class="mt-4" style="font-weight: 800; color: #0a2540;">SILA SENTUH KAD PELAJAR</h4>
            <p class="text-muted" style="font-weight: 600; margin-bottom: 0;">pada alat pengimbas RFID</p>
        </div>

        <!-- Dynamic Result Box -->
        <div id="result_display" class="result-container result-idle mt-4" style="min-height: 120px; padding: 20px;">
            <i id="result_icon" class="fa fa-id-card-o fa-4x mb-2 text-secondary" aria-hidden="true"></i>
            <h4 class="result-student-name m-0" id="result_title" style="font-size: 1.4rem;">SEDIA MENERIMA IMBASAN</h4>
            <p class="result-student-class mb-0 text-muted mt-2" id="result_desc">Sistem sedang aktif dan sedia.</p>
        </div>

        <a id="activation_login" href="index.php?next=kiosk" class="btn btn-primary mt-3" style="display:none; border-radius: 9px; font-weight: 700;">
            <i class="fa fa-lock me-2"></i>Log masuk untuk aktifkan telefon ini
        </a>

        <!-- Manual Input Toggle -->
        <button class="btn btn-outline-secondary mt-4" type="button" data-bs-toggle="collapse" data-bs-target="#collapseKeypad" style="font-weight: 600; border-radius: 8px;">
            <i class="fa fa-keyboard-o me-1"></i> Tiada Kad? Taip Manual
        </button>

        <!-- Collapse Onscreen Keypad -->
        <div class="collapse mt-3" id="collapseKeypad">
            <div class="p-3 rounded-4" style="background: #f8fafc; border: 1px solid #cbd5e1; max-width: 400px; margin: auto;">
                <input type="text" id="manual_input" class="form-control text-center mb-3" placeholder="Taip ID Kad..." style="height: 55px; font-size: 1.5rem; font-weight: 700; color: #0a2540; border-radius: 10px;">
                <div class="row g-2">
                    <?php foreach([1,2,3,4,5,6,7,8,9] as $num): ?>
                    <div class="col-4"><button onclick="keypadPress('<?php echo $num; ?>')" class="btn keypad-btn w-100"><?php echo $num; ?></button></div>
                    <?php endforeach; ?>
                    <div class="col-4"><button onclick="keypadClear()" class="btn keypad-btn w-100 text-danger"><i class="fa fa-eraser"></i></button></div>
                    <div class="col-4"><button onclick="keypadPress('0')" class="btn keypad-btn w-100">0</button></div>
                    <div class="col-4"><button onclick="submitManual()" class="btn keypad-btn w-100 btn-success text-white" style="background: #198754;"><i class="fa fa-check"></i></button></div>
                </div>
            </div>
        </div>

        <div class="mt-4 text-center">
            <button id="install_app_btn" type="button" class="btn btn-outline-primary btn-sm me-2" style="display:none; border-radius: 8px; font-weight: 700;">
                <i class="fa fa-download me-1"></i> Pasang Aplikasi
            </button>
            <?php if ($sch_id > 0): ?>
            <a href="dashboard.php" class="text-muted text-decoration-none" style="font-size: 0.9rem; font-weight: 600;"><i class="fa fa-arrow-left me-1"></i> Kembali ke Dashboard</a>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- HIDDEN ALWAYS-FOCUSED INPUT FOR USB RFID / BARCODE SCANNER -->
<input type="text" id="rfid_input" autocomplete="off" autofocus inputmode="none" style="position: absolute; opacity: 0; pointer-events: none;">

<script src="js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const TERMINAL_STORAGE_KEY = 'myrfid_rmt_terminal_v1';
const sessionSchoolId = <?php echo (int)$sch_id; ?>;
const sessionCsrf = <?php echo json_encode($csrf); ?>;
let terminalCredentials = loadTerminalCredentials();
if (terminalCredentials && sessionSchoolId > 0 && Number(terminalCredentials.school_id) !== sessionSchoolId) {
    localStorage.removeItem(TERMINAL_STORAGE_KEY);
    terminalCredentials = null;
}
let currentSchId = terminalCredentials ? Number(terminalCredentials.school_id) : sessionSchoolId;
let terminalReady = false;
let installPrompt = null;

function loadTerminalCredentials() {
    try {
        const parsed = JSON.parse(localStorage.getItem(TERMINAL_STORAGE_KEY) || 'null');
        if (!parsed || !parsed.school_id || !parsed.device_id || !parsed.api_key) return null;
        return parsed;
    } catch (error) {
        localStorage.removeItem(TERMINAL_STORAGE_KEY);
        return null;
    }
}

function terminalHeaders() {
    if (!terminalCredentials) return {};
    return {
        'X-RFID-Device-ID': terminalCredentials.device_id,
        'X-RFID-API-Key': terminalCredentials.api_key
    };
}

function setTerminalState(mode, text) {
    $('#terminal_state').removeClass('is-online is-offline').addClass(mode === 'online' ? 'is-online' : (mode === 'offline' ? 'is-offline' : ''));
    $('#terminal_state_text').text(text);
}

function showActivationRequired(message) {
    terminalReady = false;
    setTerminalState('offline', 'Belum diaktifkan');
    $('#result_display').removeClass('result-idle result-success result-duplicate').addClass('result-error');
    $('#result_icon').attr('class', 'fa fa-lock fa-4x mb-2 text-danger');
    $('#result_title').text('TERMINAL BELUM DIAKTIFKAN').removeClass('text-dark text-primary text-success text-warning').addClass('text-danger');
    $('#result_desc').text(message || 'Log masuk sebagai guru atau pentadbir untuk mengaktifkan telefon ini sekali sahaja.');
    $('#activation_login').show();
}

function activateTerminal() {
    if (terminalCredentials) {
        refreshKioskData();
        return;
    }
    if (sessionSchoolId <= 0) {
        showActivationRequired();
        return;
    }

    setTerminalState('', 'Mengaktifkan terminal');
    $.ajax({
        url: 'kiosk_api.php',
        method: 'POST',
        headers: {'X-CSRF-Token': sessionCsrf},
        data: {
            action: 'activate',
            csrf_token: sessionCsrf,
            label: 'Telefon PWA RMT - ' + new Date().toLocaleDateString('ms-MY')
        },
        dataType: 'json'
    }).done(function(resp) {
        terminalCredentials = {
            school_id: Number(resp.school_id),
            device_id: resp.device_id,
            api_key: resp.api_key
        };
        currentSchId = terminalCredentials.school_id;
        localStorage.setItem(TERMINAL_STORAGE_KEY, JSON.stringify(terminalCredentials));
        refreshKioskData();
    }).fail(function(xhr) {
        showActivationRequired((xhr.responseJSON && xhr.responseJSON.msg) || 'Telefon ini tidak dapat diaktifkan. Sila log masuk semula.');
    });
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('service-worker.js').catch(function () {});
    });
}

window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    installPrompt = event;
    $('#install_app_btn').show();
});

$('#install_app_btn').on('click', function () {
    if (!installPrompt) return;
    installPrompt.prompt();
    installPrompt.userChoice.finally(function () {
        installPrompt = null;
        $('#install_app_btn').hide();
    });
});

// 1. Digital Clock & Date Update
function updateClock() {
    const now = new Date();
    const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
    const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    
    document.getElementById('clock_time').innerText = now.toLocaleTimeString('en-US', timeOptions);
    document.getElementById('clock_date').innerText = now.toLocaleDateString('ms-MY', dateOptions);
}
setInterval(updateClock, 1000);
updateClock();

// 2. Fullscreen Toggle
function toggleFullScreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {});
    } else {
        if (document.exitFullscreen) document.exitFullscreen();
    }
}

// 3. Audio Synth (Web Audio API for Instant Feedback)
const AudioContextClass = window.AudioContext || window.webkitAudioContext;
const audioCtx = AudioContextClass ? new AudioContextClass() : null;
function playSound(type) {
    if (!audioCtx) return;
    if (audioCtx.state === 'suspended') audioCtx.resume();
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.connect(gain);
    gain.connect(audioCtx.destination);

    if (type === 'success') {
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
        osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.1); // A5
        gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.35);
    } else if (type === 'duplicate') {
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(400, audioCtx.currentTime);
        osc.frequency.setValueAtTime(300, audioCtx.currentTime + 0.15);
        gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.4);
    } else {
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(180, audioCtx.currentTime);
        gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.5);
    }
}

// 4. Focus Input Enforcement
function focusInput() {
    if (terminalReady) $('#rfid_input').focus();
}
$(document).on('click', function(e){
    if(!$(e.target).is('input, button, select, a, .btn, .modal, .modal *')) {
        focusInput();
    }
});
focusInput();

// 5. Load School Info & Live Feed
function refreshKioskData() {
    if (!terminalCredentials) return;
    $.ajax({
        url: 'kiosk_api.php',
        method: 'POST',
        headers: terminalHeaders(),
        data: {action: 'stats', school_id: currentSchId},
        dataType: 'json',
        success: function(resp) {
            if (resp.status === 'success') {
                const firstConnection = !terminalReady;
                terminalReady = true;
                setTerminalState('online', 'Terminal aktif');
                $('#activation_login').hide();
                $('#kiosk_school_name').text(resp.sekolah.nama.toUpperCase());
                $('#kiosk_logo').attr('src', resp.sekolah.logo);

                $('#stat_layak').text(resp.statistik.layak);
                $('#stat_hadir').text(resp.statistik.hadir);
                if (firstConnection) resetResultToIdle();
                focusInput();
            }
        },
        error: function(xhr) {
            if (xhr.status === 401) {
                localStorage.removeItem(TERMINAL_STORAGE_KEY);
                terminalCredentials = null;
                showActivationRequired((xhr.responseJSON && xhr.responseJSON.msg) || 'Akses terminal telah dibatalkan.');
            } else {
                setTerminalState('offline', 'Tiada sambungan');
            }
        }
    });
}
activateTerminal();
setInterval(refreshKioskData, 10000);

// 6. RFID Scan Handling
function processCardScan(rfidNo) {
    if(!terminalReady || !terminalCredentials || !rfidNo || rfidNo.trim() === '') return;
    rfidNo = rfidNo.trim();

    $('#result_display').removeClass('result-idle result-success result-duplicate result-error').addClass('result-idle');
    $('#result_icon').attr('class', 'fa fa-spinner fa-spin fa-4x mb-2 text-primary');
    $('#result_title').text('SEDIA MEMPROSES KAD...').removeClass('text-success text-warning text-danger').addClass('text-primary');
    $('#result_desc').text('ID Kad: ' + rfidNo);

    $.ajax({
        url: 'process_rmt.php',
        method: 'POST',
        headers: terminalHeaders(),
        data: {rfid_uid: rfidNo, school_id: currentSchId},
        dataType: 'json',
        success: function(resp) {
            $('#result_icon').removeClass('fa-spinner fa-spin');
            
            if (resp.status === 'success') {
                playSound('success');
                $('#result_display').removeClass('result-idle').addClass('result-success');
                $('#result_icon').attr('class', 'fa fa-check-circle fa-5x mb-2 text-success');
                $('#result_title').text(resp.nama).removeClass('text-primary text-warning text-danger').addClass('text-success');
                $('#result_desc').html(`<b>Tahun / Kelas:</b> ${escapeHtml(resp.kelas)} <span class="badge bg-success ms-2 px-3 py-1" style="font-size: 1.1rem;">${escapeHtml(resp.waktu)}</span><br><small class="text-dark fw-bold mt-2 d-block" style="font-size: 1.15rem;">${escapeHtml(resp.msg)}</small>`);
                
                refreshKioskData();
            } else if (resp.status === 'duplicate') {
                playSound('duplicate');
                $('#result_display').removeClass('result-idle').addClass('result-duplicate');
                $('#result_icon').attr('class', 'fa fa-exclamation-triangle fa-5x mb-2 text-warning');
                $('#result_title').text(resp.nama).removeClass('text-primary text-success text-danger').addClass('text-warning');
                $('#result_desc').html(`<b>Kelas:</b> ${escapeHtml(resp.kelas ?? '-')}<br><span class="badge bg-warning text-dark mt-2 px-3 py-2" style="font-size: 1.15rem;">${escapeHtml(resp.msg)}</span>`);
            } else {
                playSound('error');
                $('#result_display').removeClass('result-idle').addClass('result-error');
                $('#result_icon').attr('class', 'fa fa-times-circle fa-5x mb-2 text-danger');
                $('#result_title').text('KAD TIDAK SAH').removeClass('text-primary text-success text-warning').addClass('text-danger');
                $('#result_desc').html(`<span class="badge bg-danger mt-2 px-3 py-2" style="font-size: 1.15rem;">${escapeHtml(resp.msg ?? 'Kad tidak dikenali dalam sistem.')}</span>`);
            }

            setTimeout(resetResultToIdle, 4500);
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            if (xhr.status === 409 && response.status === 'duplicate') {
                playSound('duplicate');
                $('#result_display').removeClass('result-idle').addClass('result-duplicate');
                $('#result_icon').attr('class', 'fa fa-exclamation-triangle fa-5x mb-2 text-warning');
                $('#result_title').text(response.nama || 'SUDAH DIREKOD').removeClass('text-primary text-success text-danger').addClass('text-warning');
                $('#result_desc').text(response.msg || 'Kehadiran telah direkodkan hari ini.');
                setTimeout(resetResultToIdle, 4500);
                return;
            }
            if (xhr.status === 401) {
                localStorage.removeItem(TERMINAL_STORAGE_KEY);
                terminalCredentials = null;
                showActivationRequired(response.msg || 'Akses terminal telah tamat atau dibatalkan.');
                return;
            }
            playSound('error');
            $('#result_display').removeClass('result-idle').addClass('result-error');
            $('#result_icon').attr('class', 'fa fa-exclamation-circle fa-5x mb-2 text-danger');
            $('#result_title').text('RALAT RANGKAIAN').removeClass('text-primary text-success text-warning').addClass('text-danger');
            $('#result_desc').text('Tidak dapat berhubung dengan pangkalan data server.');
            setTimeout(resetResultToIdle, 4000);
        }
    });
}

function escapeHtml(value) {
    return $('<div>').text(String(value ?? '')).html();
}

function resetResultToIdle() {
    if (!terminalReady) return;
    $('#result_display').removeClass('result-success result-duplicate result-error').addClass('result-idle');
    $('#result_icon').attr('class', 'fa fa-id-card-o fa-4x mb-2 text-secondary');
    $('#result_title').text('SEDIA MENERIMA IMBASAN KAD...').removeClass('text-success text-warning text-danger text-primary').addClass('text-dark');
    $('#result_desc').text('Sila sentuh kad pada pengimbas untuk merekod kehadiran.');
}

let scannerBuffer = '';
let scannerTimer = null;

function submitScannerBuffer() {
    const value = scannerBuffer || String($('#rfid_input').val() || '');
    scannerBuffer = '';
    $('#rfid_input').val('');
    if (value) processCardScan(value);
}

// Pembaca RFID USB melalui OTG biasanya bertindak sebagai papan kekunci.
// Tangkap UID walaupun model reader tidak menghantar kekunci Enter di hujung.
document.addEventListener('keydown', function (event) {
    if (!terminalReady || event.target === document.getElementById('manual_input')) return;
    if (event.key === 'Enter' || event.key === 'Tab') {
        event.preventDefault();
        clearTimeout(scannerTimer);
        submitScannerBuffer();
        return;
    }
    if (/^[A-Za-z0-9:_-]$/.test(event.key)) {
        scannerBuffer += event.key;
        clearTimeout(scannerTimer);
        scannerTimer = setTimeout(submitScannerBuffer, 140);
    }
});

function keypadPress(num) {
    let current = $('#manual_input').val();
    $('#manual_input').val(current + num);
}
function keypadClear() {
    $('#manual_input').val('');
}
function submitManual() {
    let rfid = $('#manual_input').val();
    if(rfid) {
        processCardScan(rfid);
        $('#manual_input').val('');
        const keypadPanel = document.getElementById('collapseKeypad');
        if (keypadPanel && window.bootstrap) {
            bootstrap.Collapse.getOrCreateInstance(keypadPanel, {toggle: false}).hide();
        }
        focusInput();
    }
}
</script>
</body>
</html>
