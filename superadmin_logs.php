<?php
require_once __DIR__ . '/security.php';
app_start_session();
require_once __DIR__ . '/db_connect.php';
require_active_roles($conn, ['superadmin'], false);

$logRows = [];
$logReadFailed = false;
try {
    $result = $conn->query("SELECT l.*, s.nama_sekolah, u.role
        FROM activity_logs l
        LEFT JOIN sekolah s ON l.school_id = s.id
        LEFT JOIN users u ON l.user_id = u.id
        ORDER BY l.id DESC LIMIT 1000");
    while ($row = $result->fetch_assoc()) {
        $schoolId = (int)($row['school_id'] ?? 0);
        $school = trim((string)($row['nama_sekolah'] ?? ''));
        if ($school === '') {
            $school = $schoolId === 0 ? 'Sistem Pusat' : 'Sekolah #' . $schoolId . ' (maklumat tiada)';
        }
        $timestamp = strtotime((string)($row['masa'] ?? ''));
        $logRows[] = [
            'id' => (string)$row['id'],
            'schoolId' => (string)$schoolId,
            'school' => $school,
            'user' => (string)($row['nama_user'] ?? 'Sistem'),
            'role' => trim((string)($row['role'] ?? '')) ?: 'Tidak tersedia',
            'activity' => (string)($row['aktiviti'] ?? ''),
            'dateKey' => $timestamp === false ? '' : date('Y-m-d', $timestamp),
            'date' => $timestamp === false ? 'Tarikh tidak tersedia' : date('d/m/Y', $timestamp),
            'time' => $timestamp === false ? '' : date('h:i A', $timestamp)
        ];
    }
    $result->free();
} catch (Throwable $e) {
    $logReadFailed = true;
    error_log('superadmin_logs read failed: ' . $e->getMessage());
}
$logsJson = json_encode($logRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
include __DIR__ . '/header.php';
?>
</div></div>
<style>
:root {
    --sa-navy: #0b2239;
    --sa-navy-soft: #163752;
    --sa-blue: #246bfd;
    --sa-blue-dark: #1754d1;
    --sa-green: #079669;
    --sa-amber: #d97706;
    --sa-red: #dc3b49;
    --sa-purple: #7c4fe0;
    --sa-text: #172033;
    --sa-muted: #64748b;
    --sa-border: #dde5ef;
    --sa-surface: #ffffff;
    --sa-bg: #f4f7fb;
    --sa-radius: 14px;
    --sa-shadow: 0 5px 18px rgba(15, 35, 58, .055);
}

body.dashboard.dashboard_1 {
    background: var(--sa-bg) !important;
    color: var(--sa-text);
    overflow-y: auto !important;
}

.superadmin-portal-wrapper {
    min-height: 100vh;
    display: block;
    background:
        radial-gradient(circle at 22% 0%, rgba(36, 107, 253, .045), transparent 28rem),
        var(--sa-bg);
}

.sa-sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 1040;
    width: 238px;
    height: 100vh;
    height: 100dvh;
    flex: 0 0 238px;
    padding: 0 14px 18px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    background: var(--sa-navy);
    box-shadow: 3px 0 18px rgba(2, 16, 29, .08);
}

.sa-brand,
.sa-brand:hover {
    min-height: 82px;
    padding: 0 10px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: #fff;
    text-decoration: none;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -.25px;
}

.sa-brand small {
    color: #79f0ce;
    font-size: inherit;
}

.sa-brand-mark {
    width: 36px;
    height: 36px;
    flex: 0 0 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    color: #fff;
    background: linear-gradient(145deg, #2483ff, #0bbd91);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.18);
}

.sa-side-nav {
    margin-top: 15px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.sa-side-label {
    padding: 0 12px 8px;
    color: #688099;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.3px;
    text-transform: uppercase;
}

.sa-side-nav > a,
.sa-sidebar .sa-logout-form button {
    width: 100%;
    min-height: 43px;
    display: inline-flex;
    align-items: center;
    gap: 11px;
    padding: 0 13px;
    border: 0;
    border-radius: 10px;
    color: #9fb2c5;
    background: transparent;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .18s ease, color .18s ease;
}

.sa-side-nav > a i,
.sa-sidebar .sa-logout-form button i {
    width: 20px;
    text-align: center;
    font-size: 14px;
}

.sa-side-nav > a:hover,
.sa-sidebar .sa-logout-form button:hover,
.sa-side-nav > a.active {
    color: #fff;
    background: rgba(255,255,255,.085);
    text-decoration: none;
}

.sa-side-nav > a.active {
    box-shadow: inset 3px 0 0 #4be0b5;
}

.sa-side-status {
    margin: auto 3px 12px;
    padding: 13px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 11px;
    background: rgba(255,255,255,.045);
}

.sa-live-dot {
    width: 8px;
    height: 8px;
    margin-top: 4px;
    flex: 0 0 8px;
    border-radius: 50%;
    background: #41d9a8;
    box-shadow: 0 0 0 4px rgba(65,217,168,.1);
}

.sa-side-status strong,
.sa-side-status small { display: block; }
.sa-side-status strong { color: #e5edf5; font-size: 10px; }
.sa-side-status small { margin-top: 3px; color: #8198ad; font-size: 8.5px; line-height: 1.45; }
.sa-sidebar .sa-logout-form { margin: 0 3px; }

.sa-workspace {
    width: calc(100% - 238px);
    min-width: 0;
    margin-left: 238px;
}

.sa-workspace-bar {
    position: sticky;
    top: 0;
    z-index: 1030;
    height: 72px;
    padding: 0 36px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    border-bottom: 1px solid var(--sa-border);
    background: rgba(255,255,255,.94);
    box-shadow: 0 2px 12px rgba(15,35,58,.025);
    backdrop-filter: blur(10px);
}

.sa-workspace-context {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #8a98aa;
    font-size: 11px;
}

.sa-workspace-context strong { color: #405067; }
.sa-workspace-actions { display: flex; align-items: center; gap: 10px; }

.sa-nav-icon {
    position: relative;
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: 1px solid #d8e1ec;
    border-radius: 10px;
    background: #fff;
    color: #526277;
    cursor: pointer;
}

/* style.css menetapkan ikon dalam semua butang kepada putih. Portal ini perlu
   mewarisi warna butang supaya ikon kekal jelas pada latar cerah dan gelap. */
.superadmin-portal-wrapper button i { color: inherit !important; }

.sa-notification-button {
    width: auto;
    min-width: 112px;
    padding: 0 13px;
    gap: 8px;
    font-size: 11px;
    font-weight: 700;
}

.sa-button-svg {
    position: relative;
    z-index: 1;
    width: 17px;
    height: 17px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.sa-nav-button-label { position: relative; z-index: 1; }

.sa-nav-icon::after { display: none; }

.sa-admin-chip {
    height: 40px;
    padding: 0 11px 0 5px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #d8e1ec;
    border-radius: 10px;
    background: #fff;
    color: #405067;
    font-size: 10px;
}

.sa-admin-chip > span {
    width: 29px;
    height: 29px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #eaf1ff;
    color: var(--sa-blue);
    font-size: 9px;
    font-weight: 800;
}

.sa-nav-icon.has-unread::before {
    content: '';
    position: absolute;
    inset: 6px;
    border-radius: 50%;
    background: rgba(36, 107, 253, .09);
    animation: sa-notification-pulse 2.4s ease-out infinite;
}

@keyframes sa-notification-pulse {
    0%, 55% { transform: scale(.75); opacity: 0; }
    70% { opacity: .7; }
    100% { transform: scale(1.45); opacity: 0; }
}

.sa-notification-count {
    position: absolute;
    top: 1px;
    right: 0;
    min-width: 16px;
    height: 16px;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border: 2px solid #fff;
    border-radius: 20px;
    background: #ef4444;
    color: #fff;
    font-size: 9px;
    line-height: 12px;
}

.sa-notification-menu {
    width: 340px;
    margin-top: 9px;
    padding: 0;
    overflow: hidden;
    border: 1px solid var(--sa-border);
    border-radius: 12px;
    box-shadow: 0 18px 50px rgba(15, 35, 58, .16);
}

.sa-notification-menu .dropdown-header {
    padding: 15px 17px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: #f8fafc;
    border-bottom: 1px solid var(--sa-border);
    color: var(--sa-text);
    font-weight: 700;
}

.sa-notification-menu .dropdown-header strong { display: block; color: var(--sa-text); font-size: 13px; }
.sa-notification-menu .dropdown-header small { display: block; margin-top: 2px; color: #8492a6; font-size: 10px; font-weight: 500; }

.sa-notification-menu .dropdown-header button {
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--sa-blue);
    font-size: 10px;
    font-weight: 700;
    cursor: pointer;
}

.sa-notification-menu .dropdown-header button:disabled { color: #a8b3c2; cursor: default; }
.sa-notification-list { max-height: 360px; overflow-y: auto; padding: 5px 0; }

.sa-notification-item {
    position: relative;
    width: 100%;
    min-height: 78px;
    padding: 12px 34px 12px 14px;
    display: flex;
    align-items: flex-start;
    gap: 11px;
    border: 0;
    border-bottom: 1px solid #edf1f6;
    background: #fff;
    text-align: left;
    cursor: pointer;
    transition: background .16s ease;
}

.sa-notification-item:last-child { border-bottom: 0; }
.sa-notification-item:hover { background: #f8fafc; }
.sa-notification-item.is-unread { background: #f4f8ff; }
.sa-notification-item.is-unread:hover { background: #ecf3ff; }
.sa-notification-item.is-read .sa-notification-copy { opacity: .72; }

.sa-notification-icon {
    width: 34px;
    height: 34px;
    flex: 0 0 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #fff7e8;
    color: var(--sa-amber);
    font-size: 13px;
}

.sa-notification-icon.expiry { background: #fff1f2; color: var(--sa-red); }
.sa-notification-copy { min-width: 0; display: flex; flex: 1; flex-direction: column; }
.sa-notification-title { margin-bottom: 2px; color: #243247; font-size: 11px; font-weight: 700; }
.sa-notification-message { color: #66758a; font-size: 10px; line-height: 1.45; white-space: normal; }
.sa-notification-time { margin-top: 4px; color: #94a3b8; font-size: 9px; }

.sa-read-indicator {
    position: absolute;
    top: 20px;
    right: 16px;
    width: 8px;
    height: 8px;
    border: 1px solid #cbd5e1;
    border-radius: 50%;
    background: #fff;
}

.sa-notification-item.is-unread .sa-read-indicator {
    border-color: var(--sa-blue);
    background: var(--sa-blue);
    box-shadow: 0 0 0 3px rgba(36, 107, 253, .12);
}

.sa-notification-empty {
    padding: 28px 18px;
    display: flex;
    align-items: center;
    flex-direction: column;
    color: #7b899d;
    text-align: center;
}

.sa-notification-empty i { margin-bottom: 8px; color: var(--sa-green); font-size: 22px; }
.sa-notification-empty strong { color: #405067; font-size: 12px; }
.sa-notification-empty span { margin-top: 3px; font-size: 10px; }
.sa-logout-form { margin: 0; }

.superadmin-main-body {
    width: 100% !important;
    max-width: 1580px !important;
    margin: 0 auto !important;
    padding: 30px 36px 52px !important;
    display: flex;
    flex-direction: column;
}

.sa-page-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 28px;
    margin-bottom: 20px;
}

.sa-dashboard-hero {
    position: relative;
    min-height: 190px;
    margin-bottom: 18px;
    padding: 28px 30px;
    overflow: hidden;
    align-items: center;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 18px;
    background:
        radial-gradient(circle at 84% 18%, rgba(74,224,181,.18), transparent 16rem),
        linear-gradient(120deg, #0b2239 0%, #123a5b 60%, #14526a 100%);
    box-shadow: 0 16px 38px rgba(11,34,57,.14);
}

.sa-dashboard-hero::after {
    content: '';
    position: absolute;
    right: -100px;
    bottom: -205px;
    width: 290px;
    height: 290px;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 50%;
    box-shadow: 0 0 0 48px rgba(255,255,255,.025), 0 0 0 96px rgba(255,255,255,.018);
}

.sa-hero-copy,
.sa-hero-overview { position: relative; z-index: 1; }
.sa-dashboard-hero .sa-eyebrow { color: #69ebc5; }
.sa-dashboard-hero h1 { color: #fff; font-size: clamp(27px, 2.4vw, 35px); }
.sa-dashboard-hero .sa-hero-copy > p { max-width: 610px; color: #b9cad9; line-height: 1.65; }
.sa-hero-actions { margin-top: 19px; display: flex; flex-wrap: wrap; gap: 9px; }
.sa-hero-btn {
    min-height: 40px;
    padding: 0 15px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 9px;
    font-size: 11px;
    font-weight: 750;
    cursor: pointer;
}
.sa-hero-btn-primary { border: 1px solid #42dbaf; background: #42dbaf; color: #082b2c; }
.sa-hero-btn-primary:hover { background: #64e7c1; border-color: #64e7c1; }
.sa-hero-btn-secondary { border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.08); color: #fff; }
.sa-hero-btn-secondary:hover { background: rgba(255,255,255,.14); }
.sa-hero-count {
    min-width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 20px;
    background: rgba(255,255,255,.15);
    color: #fff;
    font-size: 9px;
}
.sa-hero-overview {
    width: 218px;
    flex: 0 0 218px;
    padding: 17px 18px;
    border: 1px solid rgba(255,255,255,.13);
    border-radius: 13px;
    background: rgba(5,23,39,.3);
    backdrop-filter: blur(8px);
}
.sa-hero-live { margin-bottom: 14px; display: flex; align-items: center; gap: 7px; color: #92f0d4; font-size: 9px; font-weight: 750; text-transform: uppercase; letter-spacing: .7px; }
.sa-hero-live > span { width: 7px; height: 7px; border-radius: 50%; background: #41d9a8; box-shadow: 0 0 0 4px rgba(65,217,168,.1); }
.sa-hero-overview small,
.sa-hero-overview strong { display: block; }
.sa-hero-overview small { color: #8fa8bb; font-size: 9px; }
.sa-hero-overview strong { margin: 2px 0 6px; color: #fff; font-size: 17px; }
.sa-hero-overview p { margin: 0; color: #9db1c2; font-size: 9.5px; line-height: 1.5; }

.sa-eyebrow,
.sa-section-kicker {
    display: block;
    margin-bottom: 5px;
    color: var(--sa-blue);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.2px;
    text-transform: uppercase;
}

.sa-page-heading h1 {
    margin: 0 0 6px;
    color: var(--sa-text);
    font-size: clamp(25px, 2.3vw, 32px);
    font-weight: 800;
    letter-spacing: -.8px;
}

.sa-page-heading p,
.sa-action-heading p {
    margin: 0;
    color: var(--sa-muted);
    font-size: 13px;
}

.sa-page-actions { display: flex; align-items: center; gap: 10px; }

.sa-btn,
.btn-sa-register {
    height: 42px !important;
    padding: 0 16px !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 9px !important;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}

.sa-btn-secondary {
    border: 1px solid #cfd9e6;
    background: #fff;
    color: #334155;
}

.sa-btn-secondary:hover { color: var(--sa-blue); border-color: #a9bfdf; text-decoration: none; }

.btn-sa-register {
    border: 1px solid var(--sa-blue) !important;
    background: var(--sa-blue) !important;
    box-shadow: 0 4px 12px rgba(36, 107, 253, .2) !important;
}

.btn-sa-register:hover {
    transform: translateY(-1px) !important;
    background: var(--sa-blue-dark) !important;
    box-shadow: 0 7px 18px rgba(36, 107, 253, .25) !important;
}

.sa-kpi-grid {
    order: 2;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.sa-kpi-card {
    position: relative;
    min-height: 112px;
    padding: 17px 18px !important;
    overflow: hidden;
    border: 1px solid var(--sa-border) !important;
    border-radius: var(--sa-radius) !important;
    box-shadow: 0 7px 22px rgba(15, 35, 58, .065) !important;
}

.sa-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    height: 3px;
    background: #dbe8ff;
}

.sa-kpi-grid > div:nth-child(2) .sa-kpi-card::before { background: #c8f3e4; }
.sa-kpi-grid > div:nth-child(3) .sa-kpi-card::before { background: #ffe6ba; }
.sa-kpi-grid > div:nth-child(4) .sa-kpi-card::before { background: #eadbff; }

.sa-kpi-card:hover { transform: translateY(-2px) !important; box-shadow: 0 12px 28px rgba(15, 35, 58, .1) !important; }
.kpi-text-col .kpi-label { margin-bottom: 5px !important; font-size: 10px !important; letter-spacing: .8px !important; }
.kpi-text-col .kpi-number { font-size: 29px !important; letter-spacing: -.7px; }
.kpi-note { margin-top: 6px; color: #8492a6; font-size: 11px; }

.kpi-icon-wrapper {
    width: 46px !important;
    height: 46px !important;
    border-radius: 12px !important;
    font-size: 19px !important;
}

.sa-panel {
    background: var(--sa-surface);
    border: 1px solid var(--sa-border);
    border-radius: var(--sa-radius);
    box-shadow: var(--sa-shadow);
}

.sa-insights-grid {
    order: 4;
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(220px, .9fr) minmax(220px, .9fr);
    gap: 14px;
    margin-bottom: 18px;
}

.sa-panel-header {
    min-height: 68px;
    padding: 18px 20px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
}

.sa-table-title { font-size: 16px !important; letter-spacing: -.25px; }
.sa-section-kicker { margin-bottom: 3px; font-size: 9px; }
.sa-kicker-alert { color: var(--sa-red); }
.sa-panel-meta { color: #8492a6; font-size: 11px; font-weight: 600; }
.sa-chart-wrap { height: 215px; padding: 2px 18px 18px; }
.sa-chart-wrap-small { padding-left: 22px; padding-right: 22px; }

.sa-alert-panel { min-width: 0; }
.alert-list-container { height: 215px; overflow-y: auto; padding: 0 18px 18px; }
.alert-list-container .list-group-item { padding-left: 0 !important; padding-right: 0 !important; }

.sa-action-center { order: 3; margin-bottom: 18px; overflow: hidden; }
.sa-action-heading { padding: 20px 22px 13px; }

.sa-icon-button {
    width: auto;
    min-width: 106px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 12px;
    border: 1px solid var(--sa-border);
    border-radius: 9px;
    background: #fff;
    color: #526277;
    cursor: pointer;
    font-size: 10px;
    font-weight: 700;
}

.sa-icon-button:hover { color: var(--sa-blue); background: #f5f8ff; }

.sa-tabs {
    display: flex;
    gap: 4px;
    padding: 0 22px;
    border-bottom: 1px solid var(--sa-border);
}

.sa-tab {
    position: relative;
    padding: 11px 13px 13px;
    border: 0;
    background: transparent;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.sa-tab::after {
    content: '';
    position: absolute;
    right: 9px;
    bottom: -1px;
    left: 9px;
    height: 2px;
    border-radius: 2px;
    background: transparent;
}

.sa-tab.active { color: var(--sa-blue); }
.sa-tab.active::after { background: var(--sa-blue); }

.sa-tab span {
    min-width: 20px;
    height: 20px;
    margin-left: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
    border-radius: 20px;
    background: #edf2f8;
    color: #526277;
    font-size: 10px;
}

.sa-tab.active span { background: #eaf1ff; color: var(--sa-blue); }
.sa-tab-panel { min-height: 128px; padding: 8px 20px 14px; }
.sa-tab-panel[hidden] { display: none !important; }

.sa-directory { order: 5; overflow: hidden; }
.sa-directory-heading { padding: 20px 22px 16px; border-bottom: 1px solid var(--sa-border); }

.sa-search {
    width: min(310px, 100%);
    height: 40px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 0 12px;
    border: 1px solid #cfd9e6;
    border-radius: 9px;
    background: #fff;
    color: #94a3b8;
}

.sa-search:focus-within { border-color: #7aa4f9; box-shadow: 0 0 0 3px rgba(36,107,253,.1); }
.sa-search input { width: 100%; border: 0; outline: 0; background: transparent; color: var(--sa-text); font-size: 12px; }
.sa-directory .table-responsive { padding: 0 20px 14px; }
.sa-directory .table-tenant th:nth-last-child(3),
.sa-directory .table-tenant td:nth-last-child(3) { min-width: 150px; }
.sa-directory .table-tenant th:nth-last-child(2),
.sa-directory .table-tenant td:nth-last-child(2) { min-width: 132px; }
.sa-directory .table-tenant th:last-child,
.sa-directory .table-tenant td:last-child {
    min-width: 215px;
    padding-right: 16px !important;
    text-align: right !important;
}
.sa-directory .sa-row-actions {
    justify-content: flex-end;
    flex-wrap: nowrap;
}
.sa-directory .sa-action-btn { min-height: 31px; }

.sa-log-main { max-width: 1540px !important; }
.sa-log-panel { overflow: hidden; }
.sa-log-header { padding: 20px 22px 16px; border-bottom: 1px solid var(--sa-border); }
.sa-log-tools { display: flex; align-items: center; gap: 8px; }
.sa-log-tools .sa-search { width: 330px; }
.sa-log-tools .sa-btn { min-width: 72px; }
.sa-log-panel .table-responsive {
    max-height: calc(100vh - 250px);
    padding: 0 20px 18px;
    overflow: auto;
}
.sa-log-panel .table-tenant thead th {
    position: sticky;
    top: 0;
    z-index: 2;
}
.sa-log-panel .table-tenant td:first-child { white-space: nowrap; }
.sa-role-badge {
    padding: 4px 8px;
    border-radius: 6px;
    background: #edf2f7;
    color: #526277;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
}

.sa-settings-main { max-width: 1380px !important; }
.sa-settings-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 18px;
    align-items: start;
}
.sa-settings-panel { overflow: hidden; }
.sa-settings-header { padding: 20px 22px 16px; border-bottom: 1px solid var(--sa-border); }
.sa-config-badge {
    padding: 6px 9px;
    border-radius: 7px;
    background: #edf9f5;
    color: #087b5a;
    font-size: 9px;
    font-weight: 700;
}
.sa-settings-body { padding: 4px 24px; }
.sa-settings-section { padding: 20px 0 22px; border-bottom: 1px solid #edf1f6; }
.sa-settings-section:last-child { border-bottom: 0; }
.sa-settings-section-title { margin-bottom: 16px; display: flex; align-items: flex-start; gap: 11px; }
.sa-settings-section-title > span {
    width: 29px;
    height: 29px;
    flex: 0 0 29px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #eaf1ff;
    color: var(--sa-blue);
    font-size: 9px;
    font-weight: 800;
}
.sa-settings-section-title strong,
.sa-settings-section-title small { display: block; }
.sa-settings-section-title strong { color: #2a384c; font-size: 12px; }
.sa-settings-section-title small { margin-top: 2px; color: #8795a8; font-size: 9px; }
.sa-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
.sa-form-grid-server { grid-template-columns: minmax(0, 2fr) minmax(90px, .5fr) minmax(150px, .8fr); }
.sa-field label { margin: 0 0 6px; display: block; color: #526277; font-size: 10px; font-weight: 700; }
.sa-field .form-control { height: 42px; border-color: #d6e0eb; border-radius: 8px; font-size: 11px; }
.sa-field .form-control:focus { border-color: #80a7f6; box-shadow: 0 0 0 3px rgba(36,107,253,.09) !important; }
.sa-field .input-group .form-control { border-radius: 8px 0 0 8px; }
.sa-field .input-group-append { display: flex; }
.sa-field .input-group .btn { min-width: 43px; height: 42px; padding: 0; border-color: #d6e0eb; border-radius: 0 8px 8px 0; }
.sa-field .input-group .sa-password-toggle {
    min-width: 82px;
    padding: 0 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    color: #405067;
    background: #f8fafc;
    font-size: 10px;
    font-weight: 700;
}
.sa-field .input-group .sa-password-toggle:hover { color: var(--sa-blue); background: #f0f5ff; }
.sa-settings-footer {
    min-height: 70px;
    padding: 13px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    border-top: 1px solid var(--sa-border);
    background: #fbfcfe;
}
.sa-settings-footer > span { color: #7b899c; font-size: 9px; }
.sa-settings-help { display: grid; gap: 14px; }
.sa-help-card { padding: 20px; }
.sa-help-icon {
    width: 36px;
    height: 36px;
    margin-bottom: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #fff2eb;
    color: #dc4b28;
    font-size: 15px;
}
.sa-help-card h3 { margin: 0 0 8px; color: #29384c; font-size: 13px; font-weight: 750; }
.sa-help-card p,
.sa-help-card li { color: #718096; font-size: 9.5px; line-height: 1.65; }
.sa-help-card p { margin-bottom: 10px; }
.sa-help-card ol { margin: 0; padding-left: 17px; }
.sa-help-security .sa-help-icon { background: #edf9f5; color: var(--sa-green); }
.sa-help-security p { margin-bottom: 0; }

.table-tenant { margin-bottom: 0 !important; }
.table-tenant th {
    padding: 12px 10px !important;
    border-top: 0 !important;
    border-bottom: 1px solid var(--sa-border) !important;
    background: #f8fafc !important;
    color: #6a788d !important;
    font-size: 9px !important;
    letter-spacing: .75px !important;
}

.table-tenant td {
    padding: 13px 10px !important;
    border-bottom: 1px solid #edf1f6 !important;
    color: #334155 !important;
    font-size: 12px !important;
}

.table-tenant tr:last-child td { border-bottom: 0 !important; }
.table-tenant tr:hover td { background: #fbfcfe !important; }

.badge-tenant-aktif,
.badge-tenant-digantung,
.badge-tenant-pending,
.badge-tenant-tamat {
    padding: 5px 9px !important;
    font-size: 9px !important;
    letter-spacing: .35px;
    white-space: nowrap;
}

.table-tenant .btn.btn-sm {
    min-width: 30px;
    height: 30px;
    padding: 0 8px;
    border-radius: 7px;
    font-size: 11px;
}

.sa-row-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 6px;
}

.sa-action-btn {
    min-height: 29px;
    padding: 0 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    border: 1px solid transparent;
    border-radius: 7px;
    font-size: 9px;
    font-weight: 750;
    line-height: 1;
    white-space: nowrap;
    cursor: pointer;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
}
.sa-action-btn:hover { transform: translateY(-1px); }
.sa-action-btn-primary { color: #fff; background: #246bfd; border-color: #246bfd; box-shadow: 0 3px 8px rgba(36,107,253,.16); }
.sa-action-btn-success { color: #fff; background: #08a979; border-color: #08a979; box-shadow: 0 3px 8px rgba(8,169,121,.15); }
.sa-action-btn-danger { color: #c93443; background: #fff5f6; border-color: #f5b9bf; }
.sa-action-btn-warning { color: #9a5800; background: #fff7e7; border-color: #f5d28f; }
.sa-action-btn-neutral { color: #334155; background: #fff; border-color: #cbd8e6; }
.sa-action-btn-primary:hover,
.sa-action-btn-success:hover { color: #fff; box-shadow: 0 5px 12px rgba(15,35,58,.15); }
.sa-action-btn-danger:hover { color: #aa2432; background: #ffeaec; }
.sa-action-btn-warning:hover { color: #814800; background: #ffefcf; }
.sa-action-btn-neutral:hover { color: var(--sa-blue); border-color: #9eb7d8; background: #f8fbff; }

.sa-nav-icon:focus-visible,
.sa-icon-button:focus-visible,
.sa-action-btn:focus-visible,
.sa-hero-btn:focus-visible,
.btn-sa-register:focus-visible,
.sa-btn:focus-visible {
    outline: 3px solid rgba(36,107,253,.28) !important;
    outline-offset: 2px;
}

.sa-empty-state {
    padding: 28px 16px !important;
    text-align: center;
    color: var(--sa-muted) !important;
}

.sa-empty-state i {
    width: 34px;
    height: 34px;
    margin: 0 auto 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #ecfdf5;
    color: var(--sa-green);
    font-size: 15px;
}

.sa-error-state { color: var(--sa-red) !important; }
.sa-error-state i { background: #fff1f2; color: var(--sa-red); }

.modal-content { border-radius: 14px !important; overflow: hidden; }
.modal-header { border-bottom: 0; }
.modal-footer { border-top-color: var(--sa-border); }
.modal .form-control { min-height: 42px; border-color: #d5deea; border-radius: 8px; font-size: 13px; }
.modal .form-control:focus { border-color: #78a1f7; box-shadow: 0 0 0 3px rgba(36,107,253,.1); }
.modal .form-label { margin-bottom: 6px; font-size: 12px; }

@media (max-width: 1100px) {
    .sa-sidebar { width: 78px; flex-basis: 78px; padding-right: 10px; padding-left: 10px; }
    .sa-workspace { width: calc(100% - 78px); margin-left: 78px; }
    .sa-brand { justify-content: center; padding: 0; }
    .sa-brand > span:last-child,
    .sa-side-label,
    .sa-side-nav > a span,
    .sa-side-status,
    .sa-sidebar .sa-logout-form span { display: none; }
    .sa-side-nav > a,
    .sa-sidebar .sa-logout-form button { justify-content: center; padding: 0; }
    .sa-side-nav > a.active { box-shadow: inset 0 -3px 0 #4be0b5; }
    .sa-workspace-bar { padding-right: 24px; padding-left: 24px; }
    .superadmin-main-body { padding-right: 24px !important; padding-left: 24px !important; }
    .sa-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .sa-insights-grid { grid-template-columns: 1.6fr 1fr; }
    .sa-alert-panel { grid-column: 1 / -1; }
    .alert-list-container { height: auto; max-height: 220px; }
    .sa-settings-grid { grid-template-columns: 1fr; }
    .sa-settings-help { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 760px) {
    .superadmin-portal-wrapper { display: block; }
    .sa-sidebar {
        position: relative;
        top: auto;
        bottom: auto;
        width: 100%;
        height: 60px;
        padding: 0 12px;
        flex-basis: auto;
        flex-direction: row;
        align-items: center;
        overflow: visible;
    }
    .sa-workspace { width: 100%; margin-left: 0; }
    .sa-brand { min-height: 60px; margin-right: auto; }
    .sa-brand { font-size: 15px; }
    .sa-brand-mark { width: 30px; height: 30px; }
    .sa-side-nav { margin: 0; flex-direction: row; }
    .sa-side-nav > a { width: 36px; min-height: 36px; }
    .sa-sidebar .sa-logout-form { margin: 0 0 0 4px; }
    .sa-sidebar .sa-logout-form button { width: 36px; min-height: 36px; }
    .sa-workspace-bar { position: sticky; height: 58px; padding: 0 14px; }
    .sa-workspace-context,
    .sa-admin-chip { display: none; }
    .sa-workspace-actions { width: 100%; justify-content: flex-end; }
    .superadmin-main-body { padding: 22px 14px 42px !important; }
    .sa-page-heading { align-items: stretch; flex-direction: column; gap: 16px; }
    .sa-dashboard-hero { padding: 24px 22px; }
    .sa-hero-overview { width: 100%; flex-basis: auto; }
    .sa-notification-button { min-width: 42px; padding: 0 10px; }
    .sa-nav-button-label { display: none; }
    .sa-kpi-grid,
    .sa-insights-grid { grid-template-columns: 1fr; }
    .sa-alert-panel { grid-column: auto; }
    .sa-directory-heading { align-items: stretch; flex-direction: column; }
    .sa-search { width: 100%; }
    .sa-log-header { align-items: stretch; flex-direction: column; }
    .sa-log-tools,
    .sa-log-tools .sa-search { width: 100%; }
    .sa-form-grid,
    .sa-form-grid-server,
    .sa-settings-help { grid-template-columns: 1fr; }
    .sa-settings-body { padding-right: 18px; padding-left: 18px; }
    .sa-settings-footer { align-items: stretch; flex-direction: column; }
    .sa-settings-footer .btn-sa-register { width: 100%; }
    .sa-tab-panel { padding-left: 10px; padding-right: 10px; }
    .sa-notification-menu { width: min(330px, calc(100vw - 24px)); }
}

@media (max-width: 480px) {
    .sa-kpi-grid { grid-template-columns: 1fr; }
    .sa-brand > span:last-child { display: none; }
    .sa-workspace-actions .btn-sa-register { padding: 0 12px !important; font-size: 11px; }
    .sa-tabs { padding: 0 8px; overflow-x: auto; }
    .sa-tab { white-space: nowrap; }
    .sa-directory .table-responsive { padding-left: 8px; padding-right: 8px; }
}

:root{--sa-navy:#111e32;--sa-blue:#2563eb;--sa-blue-dark:#1d4ed8;--sa-text:#17243a;--sa-muted:#64748b;--sa-border:#e2e7ef;--sa-bg:#f6f8fb;--sa-radius:10px;--sa-shadow:0 2px 5px rgba(20,35,60,.025)}
body,body.dashboard.dashboard_1{font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif!important;background:var(--sa-bg)!important;color:var(--sa-text);margin:0;overflow-x:hidden}
.superadmin-portal-wrapper{background:var(--sa-bg)}
.sa-sidebar{background:var(--sa-navy);box-shadow:none}.sa-brand{font-size:17px;font-weight:650}.sa-brand small{color:#94b8ff}.sa-brand-mark{background:#2563eb;border-radius:9px;box-shadow:none}
.sa-side-label{color:#9aabc1;font-size:10px;font-weight:600}.sa-side-nav>a{font-size:13px;font-weight:500;border-radius:7px;color:#bac7d9}.sa-side-nav>a.active{background:#253a59;box-shadow:inset 3px 0 0 #73a2ff}.sa-side-status{background:rgba(255,255,255,.03)}
.sa-workspace-bar{box-shadow:none;border-bottom:1px solid var(--sa-border);height:68px}.sa-workspace-context{font-size:12px}
.superadmin-main-body.sa-log-main{max-width:1600px!important;padding:32px 36px 44px!important}.sa-page-heading{margin-bottom:24px;align-items:center}.sa-page-heading h1{font-size:30px;font-weight:700;letter-spacing:-1px}.sa-eyebrow{color:#64748b;font-size:11px;font-weight:600;letter-spacing:1px;margin-bottom:8px}.sa-page-heading p{font-size:14px;line-height:1.5}.log-scope{font-size:12px;color:#64748b;max-width:290px;line-height:1.6}
.sa-panel{box-shadow:var(--sa-shadow);border-radius:10px}.sa-log-header{padding:20px;gap:16px;flex-wrap:wrap}.sa-table-title{font-size:16px!important;font-weight:650}.log-caption{font-size:12px;color:#64748b;margin:6px 0 0}.sa-log-tools{display:flex;gap:8px}.sa-log-tools .sa-btn{height:38px!important;font-size:12px;font-weight:600;border-radius:7px!important}.log-export{background:#2563eb!important;border-color:#2563eb!important;color:white!important}.log-export:disabled{opacity:.45;cursor:not-allowed}
.log-filters{display:grid;grid-template-columns:minmax(190px,1.6fr) repeat(2,minmax(130px,1fr)) repeat(2,minmax(130px,.8fr)) auto;align-items:end;gap:12px;padding:18px 20px;background:#fbfcfe;border-bottom:1px solid var(--sa-border)}
.log-field{display:flex;flex-direction:column;gap:7px;min-width:0;margin:0}.log-field>span{font-size:11px;color:#526078;font-weight:600}.log-field input,.log-field select{width:100%;height:39px;padding:0 10px;background:white;border:1px solid #d9e1ec;border-radius:7px;color:#334155;font-size:12px;min-width:0;font-family:inherit}.log-field input:focus,.log-field select:focus{outline:2px solid #aac5fb;outline-offset:1px}.log-reset{height:39px;background:white;border:1px solid #d9e1ec;border-radius:7px;color:#526078;padding:0 12px;cursor:pointer;font-size:12px}
.log-feedback{padding:10px 20px;font-size:12px;color:#b42318;background:#fff3f1}.log-feedback[hidden]{display:none}
.sa-log-panel .table-responsive{max-height:none;padding:0;overflow-x:auto}.table-tenant{min-width:800px}.table-tenant th{position:static!important;white-space:nowrap;font-size:11px!important;text-transform:none!important;letter-spacing:0!important;color:#526078!important;font-weight:600!important;padding:13px 20px!important;background:#f8fafc!important}.table-tenant td{font-size:12px!important;padding:16px 20px!important;vertical-align:top;line-height:1.6;color:#334155!important}.table-tenant td:last-child{min-width:280px;max-width:520px;overflow-wrap:anywhere}.log-date,.log-school,.log-person{display:block;font-weight:600;color:#26364d}.log-time{display:block;color:#7a879a;font-size:11px;margin-top:2px}.sa-role-badge{font-size:10px;font-weight:600;text-transform:none;background:#eef2f7;color:#53657e;border-radius:5px;padding:4px 7px;white-space:nowrap}.log-kind{display:inline-block;padding:2px 7px;border-radius:5px;font-size:10px;font-weight:600;background:#f0f3f7;color:#596a80;margin-bottom:5px}.log-kind.login{background:#eef4ff;color:#315f9a}.log-kind.add{background:#edf8f2;color:#28704e}.log-kind.edit{background:#fff7e6;color:#896017}.log-kind.deactivate{background:#fff0f0;color:#a94040}.log-kind.activate{background:#edf8f2;color:#28704e}.log-kind.delete{background:#fff0f0;color:#a94040}.log-detail{display:block}.log-empty td{text-align:center;padding:48px 20px!important;color:#64748b!important}.log-empty strong{display:block;margin-bottom:6px;color:#334155;font-size:14px}.log-empty button{margin-top:14px}
.log-pagination{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 20px;border-top:1px solid var(--sa-border);font-size:12px;color:#64748b;flex-wrap:wrap}.log-page-controls{display:flex;gap:8px;align-items:center}.log-page-controls button{width:34px;height:34px;border:1px solid #d9e1ec;background:white;border-radius:6px;color:#334155;cursor:pointer}.log-page-controls button:disabled{opacity:.4;cursor:not-allowed}.log-page-controls select{height:34px;border:1px solid #d9e1ec;border-radius:6px;padding:0 7px;background:white;color:#334155;font-family:inherit;font-size:12px}.log-page-position{padding:0 5px;white-space:nowrap}.log-summary{font-variant-numeric:tabular-nums}.log-note{margin:12px 0 0;font-size:11px;color:#64748b;line-height:1.6}.log-error-note{font-size:12px;color:#b42318;padding:14px 18px;background:#fff3f1;border:1px solid #fecaca;border-radius:8px;margin-bottom:16px}
a:focus-visible,button:focus-visible{outline:3px solid #93b4f4!important;outline-offset:3px}
@media(max-width:1250px){.log-filters{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:760px){.superadmin-main-body.sa-log-main{padding:24px 16px!important}.sa-page-heading{gap:10px;align-items:stretch}.sa-page-heading h1{font-size:26px}.log-scope{max-width:none}.sa-log-header{padding:16px;align-items:stretch}.sa-log-tools{width:auto}.log-filters{padding:16px;grid-template-columns:1fr 1fr;gap:12px}.log-field:first-child{grid-column:1/-1}.log-reset{align-self:end}.table-tenant td,.table-tenant th{padding:14px 16px!important}.log-pagination{padding:14px 16px;gap:12px}.log-page-controls{width:100%;justify-content:space-between}.sa-side-nav>a.active{box-shadow:inset 0 -2px 0 #73a2ff}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{transition:none!important;animation:none!important;scroll-behavior:auto!important}}

</style>
<div class="superadmin-portal-wrapper">
    <aside class="sa-sidebar">
        <a class="sa-brand" href="superadmin_dashboard.php" aria-label="Dashboard SuperAdmin">
            <span class="sa-brand-mark"><i class="fa fa-shield"></i></span>
            <span><small>DRS</small> SuperAdmin</span>
        </a>
        <nav class="sa-side-nav" aria-label="Navigasi SuperAdmin">
            <span class="sa-side-label">Workspace</span>
            <a href="superadmin_dashboard.php"><i class="fa fa-th-large"></i><span>Dashboard</span></a>
            <a href="superadmin_users.php"><i class="fa fa-users" aria-hidden="true"></i><span>Pengguna</span></a>
<a class="active" href="superadmin_logs.php"><i class="fa fa-history"></i><span>Log Aktiviti</span></a>
            <a href="superadmin_tetapan.php"><i class="fa fa-sliders"></i><span>Tetapan Sistem</span></a>
        </nav>
        <div class="sa-side-status"><span class="sa-live-dot"></span><div><strong>Sistem beroperasi</strong><small>Perkhidmatan platform aktif</small></div></div>
        <form method="post" action="logout.php" class="sa-logout-form">
            <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
            <button type="submit"><i class="fa fa-sign-out"></i><span>Log Keluar</span></button>
        </form>
    </aside>

    <div class="sa-workspace">
        <header class="sa-workspace-bar">
            <div class="sa-workspace-context"><span>Platform</span><i class="fa fa-angle-right"></i><strong>Log Aktiviti</strong></div>
            <div class="sa-workspace-actions">
                <div class="dropdown">
                    <button type="button" class="sa-nav-icon sa-notification-button dropdown-toggle" data-toggle="dropdown" aria-label="Notifikasi sistem">
                        <svg class="sa-button-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><span class="sa-nav-button-label">Notifikasi</span><span id="bellBadge" class="sa-notification-count">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right sa-notification-menu">
                        <div class="dropdown-header">
                            <div><strong>Notifikasi</strong><small id="notificationSummary">Memuatkan...</small></div>
                            <button type="button" id="markAllNotifications" onclick="markAllNotificationsRead(event)" disabled>Tanda semua dibaca</button>
                        </div>
                        <div class="sa-notification-list" id="notificationList" aria-live="polite"><div class="text-center text-muted py-3">Memuatkan...</div></div>
                    </div>
                </div>
                <span class="sa-admin-chip"><span>SA</span><strong>SuperAdmin</strong></span>
                <a class="sa-btn sa-btn-secondary" href="superadmin_dashboard.php"><i class="fa fa-arrow-left"></i> Dashboard</a>
            </div>
        </header>


<main class="superadmin-main-body sa-log-main">
    <div class="sa-page-heading">
        <div><span class="sa-eyebrow">PENGURUSAN PLATFORM</span><h1>Log aktiviti</h1><p>Semak aktiviti pengguna dan perubahan dalam sistem.</p></div>
        <div class="log-scope">Paparan terhad kepada 1,000 rekod terkini.<br>Tarikh dan masa: Malaysia (UTC+8).</div>
    </div>
    <?php if ($logReadFailed): ?>
    <div class="log-error-note" role="alert">Rekod tidak dapat dimuatkan. Cuba muat semula halaman.</div>
    <?php endif; ?>
    <section class="sa-panel sa-log-panel" aria-labelledby="logTitle">
        <div class="sa-panel-header sa-log-header">
            <div><h2 class="sa-table-title" id="logTitle">Rekod aktiviti</h2><p class="log-caption"><?php echo number_format(count($logRows)); ?> rekod dimuatkan · Terkini dahulu</p></div>
            <div class="sa-log-tools">
                <button type="button" class="sa-btn sa-btn-secondary" onclick="window.location.reload()"><i class="fa fa-refresh" aria-hidden="true"></i> Muat semula</button>
                <button type="button" class="sa-btn sa-btn-secondary log-export" id="exportButton" onclick="exportLogsCsv()" disabled><i class="fa fa-download" aria-hidden="true"></i> Eksport hasil tapisan</button>
            </div>
        </div>
        <form id="logFilters" class="log-filters" onsubmit="return false;">
            <label class="log-field"><span>Carian</span><input type="search" id="logSearch" placeholder="Pengguna, sekolah atau aktiviti" autocomplete="off"></label>
            <label class="log-field"><span>Sekolah</span><select id="schoolFilter"><option value="">Semua sekolah</option></select></label>
            <label class="log-field"><span>Peranan semasa</span><select id="roleFilter"><option value="">Semua peranan</option></select></label>
            <label class="log-field"><span>Tarikh mula</span><input type="date" id="dateFrom" aria-describedby="filterFeedback"></label>
            <label class="log-field"><span>Tarikh akhir</span><input type="date" id="dateTo" aria-describedby="filterFeedback"></label>
            <button type="button" class="log-reset" onclick="resetLogFilters()">Reset penapis</button>
        </form>
        <div id="filterFeedback" class="log-feedback" role="alert" hidden></div>
        <div class="table-responsive" tabindex="0" aria-label="Jadual log aktiviti; tatal ke kanan untuk melihat semua lajur">
            <table class="table-tenant table table-hover table-borderless" id="logsTable">
                <thead><tr><th scope="col">Tarikh &amp; masa</th><th scope="col">Sekolah</th><th scope="col">Pengguna</th><th scope="col">Peranan semasa</th><th scope="col">Aktiviti</th></tr></thead>
                <tbody id="logTableBody"><tr class="log-empty"><td colspan="5">Memuatkan paparan log…</td></tr></tbody>
            </table>
        </div>
        <div class="log-pagination">
            <span id="logSummary" class="log-summary" role="status" aria-live="polite"></span>
            <div class="log-page-controls">
                <label for="pageSize" class="mb-0">Setiap halaman</label>
                <select id="pageSize"><option value="25">25</option><option value="50">50</option><option value="100">100</option></select>
                <button type="button" id="previousPage" aria-label="Halaman sebelumnya">&#8592;</button>
                <span id="pagePosition" class="log-page-position"></span>
                <button type="button" id="nextPage" aria-label="Halaman seterusnya">&#8594;</button>
            </div>
        </div>
    </section>
    <p class="log-note">Carian, tapisan dan eksport meliputi rekod yang dimuatkan sahaja. Eksport merangkumi semua hasil tapisan, termasuk halaman lain. Peranan merujuk kepada akaun pengguna semasa; label jenis aktiviti ialah panduan berdasarkan teks rekod.</p>
    <noscript><p class="log-error-note">Aktifkan JavaScript untuk melihat dan menapis rekod.</p></noscript>
</main></div></div>
<script id="logsData" type="application/json"><?php echo $logsJson ?: '[]'; ?></script>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>

<script>
'use strict';
var logRecords = [];
var filteredLogs = [];
var logPage = 1;
var saNotifications = [];
var notificationBusy = false;

function escapeLogHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
}
function roleLabel(role) {
    return ({superadmin:'SuperAdmin',admin:'Admin',gpk:'GPK',guru:'Guru',staff:'Staf'})[role] || role;
}
function activityKind(text) {
    var t = String(text).toLocaleLowerCase('ms');
    if (/nyahaktif|digantung|menggantung/.test(t)) return ['Nyahaktif / gantung','deactivate'];
    if (/padam|hapus/.test(t)) return ['Padam','delete'];
    if (/log masuk|login/.test(t)) return ['Log masuk','login'];
    if (/log keluar|logout/.test(t)) return ['Log keluar','login'];
    if (/kemaskini|kemas kini|mengemaskini|mengemas kini/.test(t)) return ['Kemas kini','edit'];
    if (/menambah|tambah|mendaftar|pendaftaran/.test(t)) return ['Tambah / daftar','add'];
    if (/mengaktifkan|pengaktifan/.test(t)) return ['Pengaktifan','activate'];
    return ['Aktiviti lain','other'];
}
function selectLogRecords(records, filters) {
    var term = filters.term.trim().toLocaleLowerCase('ms');
    if (filters.from && filters.to && filters.from > filters.to) return [];
    return records.filter(function(row) {
        if (filters.school && row.schoolId !== filters.school) return false;
        if (filters.role && row.role !== filters.role) return false;
        if (filters.from && (!row.dateKey || row.dateKey < filters.from)) return false;
        if (filters.to && (!row.dateKey || row.dateKey > filters.to)) return false;
        var haystack = [row.school,row.user,row.role,roleLabel(row.role),row.activity,row.date,row.time].join(' ').toLocaleLowerCase('ms');
        return !term || haystack.indexOf(term) !== -1;
    });
}
function logCsvCell(value) {
    var text = String(value == null ? '' : value);
    if (/^[\s\uFEFF]*[=+\-@]/.test(text) || /^[\t\r\n]/.test(text)) text = "'" + text;
    return '"' + text.replace(/"/g, '""') + '"';
}
function makeLogsCsv(rows) {
    var data = [['Tarikh','Masa','Sekolah','Pengguna','Peranan semasa','Aktiviti']];
    rows.forEach(function(row) { data.push([row.date,row.time,row.school,row.user,roleLabel(row.role),row.activity]); });
    return '\uFEFF' + data.map(function(row) { return row.map(logCsvCell).join(','); }).join('\r\n');
}
function applyLogFilters() {
    var filters = {
        term:document.getElementById('logSearch').value,
        school:document.getElementById('schoolFilter').value,
        role:document.getElementById('roleFilter').value,
        from:document.getElementById('dateFrom').value,
        to:document.getElementById('dateTo').value
    };
    var invalid = Boolean(filters.from && filters.to && filters.from > filters.to);
    var feedback = document.getElementById('filterFeedback');
    feedback.hidden = !invalid;
    feedback.textContent = invalid ? 'Tarikh akhir mesti sama atau selepas tarikh mula.' : '';
    document.getElementById('dateTo').setAttribute('aria-invalid', String(invalid));
    filteredLogs = selectLogRecords(logRecords, filters);
    logPage = 1;
    renderLogPage();
}
function resetLogFilters() {
    document.getElementById('logFilters').reset();
    applyLogFilters();
}
function renderLogPage() {
    var size = Number(document.getElementById('pageSize').value);
    var pages = Math.max(1,Math.ceil(filteredLogs.length / size));
    logPage = Math.min(pages,Math.max(1,logPage));
    var start = (logPage - 1) * size;
    var currentRows = filteredLogs.slice(start,start + size);
    var html = currentRows.map(function(row) {
        var kind = activityKind(row.activity);
        return '<tr><td><span class="log-date">' + escapeLogHtml(row.date) + '</span><span class="log-time">' + escapeLogHtml(row.time) + '</span></td>' +
            '<td><span class="log-school">' + escapeLogHtml(row.school) + '</span></td>' +
            '<td><span class="log-person">' + escapeLogHtml(row.user) + '</span></td>' +
            '<td><span class="sa-role-badge">' + escapeLogHtml(roleLabel(row.role)) + '</span></td>' +
            '<td><span class="log-kind ' + kind[1] + '">' + kind[0] + '</span><span class="log-detail">' + escapeLogHtml(row.activity) + '</span></td></tr>';
    }).join('');
    if (!currentRows.length) {
        html = '<tr class="log-empty"><td colspan="5"><strong>' + (logRecords.length ? 'Tiada rekod sepadan' : 'Tiada rekod untuk dipaparkan') + '</strong>' +
            (logRecords.length ? 'Cuba carian lain atau reset penapis.<br><button type="button" class="log-reset" onclick="resetLogFilters()">Reset penapis</button>' : 'Rekod aktiviti akan dipaparkan apabila tersedia.') + '</td></tr>';
    }
    document.getElementById('logTableBody').innerHTML = html;
    document.getElementById('logSummary').textContent = (currentRows.length ? (start+1) + '–' + (start+currentRows.length) : '0') + ' daripada ' + filteredLogs.length + ' hasil · ' + logRecords.length + ' rekod dimuatkan';
    document.getElementById('pagePosition').textContent = logPage + ' / ' + pages;
    document.getElementById('previousPage').disabled = logPage <= 1;
    document.getElementById('nextPage').disabled = logPage >= pages;
    document.getElementById('exportButton').disabled = filteredLogs.length === 0;
}
function exportLogsCsv() {
    if (!filteredLogs.length) return;
    var url = URL.createObjectURL(new Blob([makeLogsCsv(filteredLogs)],{type:'text/csv;charset=utf-8'}));
    var link = document.createElement('a');
    link.href = url;
    link.download = 'log-aktiviti-' + new Intl.DateTimeFormat('en-CA',{timeZone:'Asia/Kuala_Lumpur',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date()).replace(/\//g,'-') + '.csv';
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(function() { URL.revokeObjectURL(url); },1000);
}

function notificationRequest(formData) {
    var options = {headers:{'Accept':'application/json'}};
    if (formData) {
        options.method = 'POST';
        options.body = formData;
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) options.headers['X-CSRF-Token'] = meta.content;
    }
    return fetch('superadmin_ajax.php' + (formData ? '' : '?action=get_stats'),options)
        .then(function(response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        }).then(function(data) {
            if (data.status !== 'success') throw new Error('Permintaan notifikasi gagal.');
            return data;
        });
}
function renderLogNotifications() {
    var unread = saNotifications.filter(function(item) { return !item.is_read; }).length;
    var badge = document.getElementById('bellBadge');
    badge.textContent = unread > 99 ? '99+' : String(unread);
    badge.style.display = unread ? 'inline-flex' : 'none';
    document.querySelector('.sa-notification-button').classList.toggle('has-unread',unread>0);
    document.getElementById('notificationSummary').textContent = unread ? unread + ' belum dibaca' : 'Semua sudah dibaca';
    document.getElementById('markAllNotifications').disabled = unread === 0;
    var list = document.getElementById('notificationList');
    list.replaceChildren();
    if (!saNotifications.length) {
        list.innerHTML = '<div class="sa-notification-empty"><i class="fa fa-bell-o" aria-hidden="true"></i><strong>Tiada notifikasi</strong><span>Tiada pemberitahuan untuk dipaparkan.</span></div>';
        return;
    }
    saNotifications.slice(0,10).forEach(function(item) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'sa-notification-item ' + (item.is_read ? 'is-read' : 'is-unread');
        button.innerHTML = '<span class="sa-notification-icon"><i class="fa ' + (item.type === 'expiry' ? 'fa-clock-o' : 'fa-file-text-o') + '" aria-hidden="true"></i></span>' +
            '<span class="sa-notification-copy"><span class="sa-notification-title">' + escapeLogHtml(item.title) + '</span><span class="sa-notification-message">' + escapeLogHtml(item.message) + '</span><span class="sa-notification-time">' + escapeLogHtml(item.created_at) + '</span></span><span class="sa-read-indicator"></span>';
        button.addEventListener('click',function() { openLogNotification(item); });
        list.appendChild(button);
    });
}
function fetchNotifications() {
    if (notificationBusy) return;
    notificationBusy = true;
    notificationRequest().then(function(data) {
        saNotifications = Array.isArray(data.notifications) ? data.notifications : [];
        renderLogNotifications();
    }).catch(function() {
        document.getElementById('notificationSummary').textContent = 'Notifikasi tidak dapat dimuatkan.';
        document.getElementById('markAllNotifications').disabled = true;
    }).finally(function() { notificationBusy = false; });
}
function openLogNotification(item) {
    var destination = 'superadmin_dashboard.php' + (['#approvalPanel','#expiringSoonList'].includes(item.href) ? item.href : '');
    if (item.is_read) { window.location.href = destination; return; }
    var form = new FormData();
    form.append('action','mark_notification_read');
    form.append('notification_key',item.key);
    notificationRequest(form).catch(function() { /* Open the destination even if marking fails. */ })
        .finally(function() { window.location.href = destination; });
}
function markAllNotificationsRead(event) {
    event.preventDefault();event.stopPropagation();
    var button = document.getElementById('markAllNotifications');
    if (button.disabled) return;
    button.disabled = true;
    var form = new FormData();form.append('action','mark_all_notifications_read');
    notificationRequest(form).then(function() {
        saNotifications.forEach(function(item) { item.is_read = true; });
        renderLogNotifications();
    }).catch(function() {
        button.disabled = false;
        document.getElementById('notificationSummary').textContent = 'Gagal menanda notifikasi. Cuba lagi.';
    });
}

document.addEventListener('DOMContentLoaded',function() {
    logRecords = JSON.parse(document.getElementById('logsData').textContent);
    var schools = new Map();var roles = new Set();
    logRecords.forEach(function(row) { schools.set(row.schoolId,row.school);roles.add(row.role); });
    Array.from(schools.entries()).sort(function(a,b) { return a[1].localeCompare(b[1],'ms'); }).forEach(function(entry) {
        document.getElementById('schoolFilter').add(new Option(entry[1],entry[0]));
    });
    Array.from(roles).sort().forEach(function(role) { document.getElementById('roleFilter').add(new Option(roleLabel(role),role)); });
    document.getElementById('logSearch').addEventListener('input',applyLogFilters);
    ['schoolFilter','roleFilter','dateFrom','dateTo'].forEach(function(id) { document.getElementById(id).addEventListener('change',applyLogFilters); });
    document.getElementById('pageSize').addEventListener('change',function() { logPage=1;renderLogPage(); });
    document.getElementById('previousPage').addEventListener('click',function() { logPage--;renderLogPage(); });
    document.getElementById('nextPage').addEventListener('click',function() { logPage++;renderLogPage(); });
    applyLogFilters();fetchNotifications();
    setInterval(function() { if (!document.hidden) fetchNotifications(); },60000);
});

</script>
</body>
</html>
