<?php
require_once __DIR__ . '/security.php';
app_start_session();
include 'db_connect.php';

require_active_roles($conn, ['superadmin'], false);

include 'header.php';
?>
      </div>
   </div>

   <style>
   body {
       background: #f8fafc !important;
       font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
       margin: 0;
       padding: 0;
   }
   .superadmin-portal-wrapper {
       width: 100%;
       min-height: calc(100vh - 60px);
       display: flex;
       flex-direction: column;
       background: #f8fafc;
   }
   .superadmin-header-bar {
       background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
       color: #ffffff;
       padding: 30px 40px;
       border-bottom: 1px solid rgba(255, 255, 255, 0.1);
       box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
   }
   .sa-table-card {
       background: #ffffff;
       border-radius: 20px;
       box-shadow: 0 10px 30px rgba(0,0,0,0.04);
       border: 1px solid rgba(0,0,0,0.04);
       margin: -30px 40px 40px 40px; /* pull up over the header slightly */
       padding: 40px;
       position: relative;
       z-index: 10;
   }
   .sa-table-title {
       font-size: 20px;
       font-weight: 700;
       color: #1e293b;
       margin-bottom: 25px;
       display: flex;
       align-items: center;
       gap: 10px;
   }
   .form-label {
       font-size: 14px;
       font-weight: 600;
       color: #475569;
       margin-bottom: 8px;
   }
   .form-control, .form-select {
       border-radius: 10px;
       border: 1px solid #cbd5e1;
       padding: 12px 16px;
       font-size: 14px;
       transition: all 0.2s;
       box-shadow: none !important;
   }
   .form-control:focus, .form-select:focus {
       border-color: #6366f1;
       box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1) !important;
   }
   .input-group .btn {
       border-radius: 0 10px 10px 0;
       border: 1px solid #cbd5e1;
       border-left: none;
       background: #f8fafc;
       color: #64748b;
       padding: 0 20px;
   }
   .input-group .form-control {
       border-radius: 10px 0 0 10px;
   }
   .btn-primary {
       background: linear-gradient(135deg, #2563eb, #1d4ed8);
       border: none;
       border-radius: 10px;
       padding: 12px 28px;
       font-weight: 600;
       box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
       transition: all 0.3s;
   }
   .btn-primary:hover {
       transform: translateY(-2px);
       box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
   }
   /* Toast */
   .sa-toast-container { position: fixed; top: 28px; right: 28px; z-index: 99999; pointer-events: none; }
   .sa-toast { background: #ffffff; border-radius: 14px; padding: 16px 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-left: 5px solid #2563eb; display: flex; align-items: center; gap: 14px; transform: translateY(-20px); opacity: 0; visibility: hidden; transition: all 0.35s; pointer-events: none; }
   .sa-toast.show { transform: translateY(0); opacity: 1; visibility: visible; pointer-events: auto; }
   .sa-toast.success { border-left-color: #10b981; }
   .sa-toast.error { border-left-color: #ef4444; }
   .sa-toast-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
   .sa-toast.success .sa-toast-icon { background: #d1fae5; color: #059669; }
   .sa-toast.error .sa-toast-icon { background: #fee2e2; color: #dc2626; }
   </style>
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
body,body.dashboard.dashboard_1{font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif!important;background:var(--sa-bg)!important;color:var(--sa-text);margin:0;overflow-x:hidden}.superadmin-portal-wrapper{background:var(--sa-bg);min-height:100vh}.sa-sidebar{background:var(--sa-navy);box-shadow:none}.sa-brand{font-size:17px;font-weight:650}.sa-brand small{color:#94b8ff}.sa-brand-mark{background:#2563eb;border-radius:9px;box-shadow:none}.sa-side-label{color:#9aabc1;font-size:10px;font-weight:600}.sa-side-nav>a{font-size:13px;font-weight:500;border-radius:7px;color:#bac7d9}.sa-side-nav>a.active{background:#253a59;box-shadow:inset 3px 0 0 #73a2ff}.sa-side-status{background:rgba(255,255,255,.03)}.sa-workspace-bar{box-shadow:none;border-bottom:1px solid var(--sa-border);height:68px}
.superadmin-main-body.sa-settings-main{max-width:1500px!important;padding:32px 36px 44px!important}.sa-page-heading{margin-bottom:24px}.sa-page-heading h1{font-size:30px;font-weight:700;letter-spacing:-1px}.sa-eyebrow{color:#64748b;font-size:11px;font-weight:600;letter-spacing:1px;margin-bottom:8px}.sa-page-heading p{font-size:14px;line-height:1.5}.sa-settings-grid{grid-template-columns:minmax(0,1fr) 290px;gap:20px;align-items:start}.sa-panel{border-radius:10px;box-shadow:var(--sa-shadow)}.sa-section-kicker{display:none}.sa-settings-header{padding:20px 24px;min-height:76px}.sa-table-title{font-size:16px!important;font-weight:650;margin:0;line-height:1.4}.settings-subtitle{font-size:12px;color:#64748b;margin:5px 0 0}.sa-config-badge{background:#f1f5f9;color:#526078;font-size:10px;font-weight:500;white-space:nowrap}.sa-settings-body{padding:0 24px}.sa-settings-section{padding:23px 0}.sa-settings-section-title{gap:12px;margin-bottom:18px}.sa-settings-section-title>span{background:#f0f4fa;color:#526d96;border-radius:7px;font-size:11px;font-weight:600;width:30px;height:30px;flex-basis:30px}.sa-settings-section-title strong{font-size:13px;font-weight:650;color:#26364d}.sa-settings-section-title small{font-size:11px;color:#64748b;margin-top:4px}.sa-field label{font-size:12px;font-weight:550;color:#45556d;margin-bottom:8px}.sa-field .form-control{height:43px;font-family:inherit;font-size:13px;padding:10px 12px;border:1px solid #d9e1ec;border-radius:7px;color:#26364d}.sa-field select.form-control{padding:0 10px}.sa-field .input-group .form-control{border-radius:7px 0 0 7px}.sa-field .input-group .sa-password-toggle{height:43px;font-size:11px;border-radius:0 7px 7px 0;min-width:80px}.field-help{display:block;font-size:11px;color:#64748b;margin-top:7px;line-height:1.6}.sa-settings-footer{padding:16px 24px;min-height:76px;background:#fbfcfe}.sa-settings-footer>span{font-size:11px;line-height:1.5}.btn-sa-register{background:#2563eb!important;color:white!important;box-shadow:none!important;border-radius:7px!important;font-size:12px;font-weight:600}.btn-sa-register:disabled{opacity:.5;cursor:not-allowed}.sa-help-card{padding:20px}.sa-help-icon{background:#eff4fc;color:#426dab;border-radius:8px}.sa-help-card h3{font-size:13px;font-weight:650;text-transform:none}.sa-help-card p,.sa-help-card li{font-size:12px;color:#64748b;line-height:1.7}.sa-help-card ol{padding-left:18px}.settings-side-note{padding:12px;border-radius:7px;background:#f6f8fb;font-size:11px;line-height:1.6;color:#526078}.settings-message{margin-bottom:18px;padding:12px 16px;border-radius:8px;background:#eff5ff;border:1px solid #d7e5fc;color:#315f9a;font-size:12px;line-height:1.6}.settings-message.error{background:#fff3f1;border-color:#fecaca;color:#a82d24}.settings-message.success{background:#edf8f2;border-color:#c9e7d6;color:#256547}.settings-message[hidden]{display:none}.settings-message button{border:0;text-decoration:underline;background:transparent;color:inherit;font-weight:600;cursor:pointer;margin-left:8px}.smtp-form-fields{border:0;margin:0;padding:0;min-width:0}.smtp-form-fields:disabled{opacity:.65}.settings-test{margin-top:20px;padding:22px 24px}.settings-test h2{font-size:16px;font-weight:650;margin:0 0 6px}.settings-test p{font-size:12px;color:#64748b;line-height:1.6;margin-bottom:16px}.settings-test-row{display:flex;gap:10px;align-items:flex-end}.settings-test-row .sa-field{flex:1;min-width:0}.settings-test-row button{height:43px!important;flex-shrink:0}.settings-test-result{margin-top:14px;margin-bottom:0;overflow-wrap:anywhere}.settings-test-status{font-size:11px;color:#64748b;margin-left:auto}.settings-main-stack{min-width:0}.sa-toast{max-width:420px;font-size:12px}.sa-toast-title{font-weight:650}.sa-toast-msg{margin-top:4px;line-height:1.6}.sa-toast-container{max-width:calc(100vw - 32px)}
a:focus-visible,button:focus-visible{outline:3px solid #93b4f4!important;outline-offset:3px}
@media(max-width:1100px){.sa-settings-grid{grid-template-columns:1fr}.sa-settings-help{grid-template-columns:1fr 1fr}}
@media(max-width:760px){.superadmin-main-body.sa-settings-main{padding:24px 16px!important}.sa-page-heading h1{font-size:26px}.sa-settings-header{padding:18px;flex-wrap:wrap;gap:10px}.sa-settings-body{padding:0 18px}.sa-settings-footer{padding:16px 18px}.sa-settings-help{grid-template-columns:1fr}.sa-form-grid-server{grid-template-columns:1fr 1fr}.sa-field-host{grid-column:1/-1}.settings-test{padding:20px 18px}.settings-test-row{flex-direction:column;align-items:stretch}.sa-side-nav>a.active{box-shadow:inset 0 -2px 0 #73a2ff}.sa-toast-container{top:12px;right:16px}.sa-settings-footer .btn-sa-register{width:100%}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{transition:none!important;animation:none!important}}

</style>

   <div class="sa-toast-container" id="saToastContainer">
       <div class="sa-toast" id="saToast">
           <div class="sa-toast-icon" id="saToastIcon"><i class="fa fa-info-circle"></i></div>
           <div class="sa-toast-body">
               <div class="sa-toast-title" id="saToastTitle">Notifikasi</div>
               <div class="sa-toast-msg" id="saToastMsg">Mesej di sini</div>
           </div>
           <button class="sa-toast-close" onclick="document.getElementById('saToast').classList.remove('show');" style="background:none; border:none; font-size:20px; color:#94a3b8; cursor:pointer;">&times;</button>
       </div>
   </div>

   <div class="superadmin-portal-wrapper">
       <aside class="sa-sidebar">
           <a class="sa-brand" href="superadmin_dashboard.php" aria-label="Dashboard SuperAdmin"><span class="sa-brand-mark"><i class="fa fa-shield"></i></span><span><small>DRS</small> SuperAdmin</span></a>
           <nav class="sa-side-nav" aria-label="Navigasi SuperAdmin">
               <span class="sa-side-label">Workspace</span>
               <a href="superadmin_dashboard.php"><i class="fa fa-th-large"></i><span>Dashboard</span></a>
               <a href="superadmin_users.php"><i class="fa fa-users" aria-hidden="true"></i><span>Pengguna</span></a>
<a href="superadmin_logs.php"><i class="fa fa-history"></i><span>Log Aktiviti</span></a>
               <a class="active" href="superadmin_tetapan.php"><i class="fa fa-sliders"></i><span>Tetapan Sistem</span></a>
           </nav>
           <div class="sa-side-status"><span class="sa-live-dot"></span><div><strong>Sistem beroperasi</strong><small>Perkhidmatan platform aktif</small></div></div>
           <form method="post" action="logout.php" class="sa-logout-form"><input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>"><button type="submit"><i class="fa fa-sign-out"></i><span>Log Keluar</span></button></form>
       </aside>

       <div class="sa-workspace">
           <header class="sa-workspace-bar">
               <div class="sa-workspace-context"><span>Platform</span><i class="fa fa-angle-right"></i><strong>Tetapan Sistem</strong></div>
               <div class="sa-workspace-actions">
                   <div class="dropdown">
                       <button type="button" class="sa-nav-icon sa-notification-button dropdown-toggle" data-toggle="dropdown" aria-label="Notifikasi sistem"><svg class="sa-button-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><span class="sa-nav-button-label">Notifikasi</span><span id="bellBadge" class="sa-notification-count">0</span></button>
                       <div class="dropdown-menu dropdown-menu-right sa-notification-menu">
                           <div class="dropdown-header"><div><strong>Notifikasi</strong><small id="notificationSummary">Memuatkan...</small></div><button type="button" id="markAllNotifications" onclick="markAllNotificationsRead(event)" disabled>Tanda semua dibaca</button></div>
                           <div class="sa-notification-list" id="notificationList" aria-live="polite"><div class="text-center text-muted py-3">Memuatkan...</div></div>
                       </div>
                   </div>
                   <span class="sa-admin-chip"><span>SA</span><strong>SuperAdmin</strong></span>
                   <a class="sa-btn sa-btn-secondary" href="superadmin_dashboard.php"><i class="fa fa-arrow-left"></i> Dashboard</a>
               </div>
           </header>

           <main class="superadmin-main-body sa-settings-main">
               <div class="sa-page-heading"><div><span class="sa-eyebrow">Konfigurasi platform</span><h1>Tetapan sistem</h1><p>Urus sambungan e-mel automatik untuk operasi SuperAdmin.</p></div></div>
               <div id="settingsFeedback" class="settings-message" role="status" aria-live="polite">Memuatkan tetapan SMTP…</div>
               <div class="sa-settings-grid"><div class="settings-main-stack">
                   <form id="smtpForm" class="sa-panel sa-settings-panel" onsubmit="saveSmtpSettings(event)">
                       <div class="sa-panel-header sa-settings-header"><div><span class="sa-section-kicker">Perkhidmatan e-mel</span><h2 class="sa-table-title">Konfigurasi e-mel</h2><p class="settings-subtitle">Sambungan untuk e-mel automatik MyRFID.</p></div><span class="sa-config-badge"><i class="fa fa-lock"></i> Password disulitkan</span></div>
                       <fieldset id="smtpFields" class="smtp-form-fields" disabled><div class="sa-settings-body">
                           <div class="sa-settings-section">
                               <div class="sa-settings-section-title"><span>01</span><div><strong>Sambungan pelayan</strong><small>Alamat dan kaedah sambungan SMTP.</small></div></div>
                               <div class="sa-form-grid sa-form-grid-server">
                                   <div class="sa-field sa-field-host"><label for="smtp_host">SMTP host</label><input type="text" class="form-control" id="smtp_host" placeholder="smtp.gmail.com" required maxlength="255" spellcheck="false" /></div>
                                   <div class="sa-field"><label for="smtp_port">Port</label><input type="number" class="form-control" id="smtp_port" placeholder="587" min="1" max="65535" step="1" required /></div>
                                   <div class="sa-field"><label for="smtp_encryption">Enkripsi</label><select class="form-control" id="smtp_encryption"><option value="tls">STARTTLS (587)</option><option value="ssl">SSL/TLS (465)</option><option value="none">Tiada</option></select></div>
                               </div>
                           </div>
                           <div class="sa-settings-section">
                               <div class="sa-settings-section-title"><span>02</span><div><strong>Kelayakan akaun</strong><small>Akaun yang dibenarkan menghantar e-mel.</small></div></div>
                               <div class="sa-form-grid">
                                   <div class="sa-field"><label for="smtp_username">Username / e-mel SMTP</label><input type="text" class="form-control" id="smtp_username" placeholder="nama@gmail.com" autocomplete="username" required maxlength="255" spellcheck="false" /></div>
                                   <div class="sa-field"><label for="smtp_password">Password SMTP / App Password</label><div class="input-group"><input type="password" class="form-control" id="smtp_password" placeholder="Masukkan password SMTP" autocomplete="new-password" /><div class="input-group-append"><button class="btn btn-outline-secondary sa-password-toggle" type="button" onclick="toggleSmtpPass()" aria-label="Lihat kata laluan"><i class="fa fa-eye" id="smtpPassEyeIcon"></i><span id="smtpPassToggleLabel">Lihat</span></button></div></div><small class="field-help" id="passwordHint">Masukkan password SMTP untuk akaun ini.</small></div>
                               </div>
                           </div>
                           <div class="sa-settings-section">
                               <div class="sa-settings-section-title"><span>03</span><div><strong>Identiti penghantar</strong><small>Nama dan alamat yang dilihat oleh penerima.</small></div></div>
                               <div class="sa-form-grid">
                                   <div class="sa-field"><label for="smtp_from_email">E-mel penghantar</label><input type="email" class="form-control" id="smtp_from_email" placeholder="nama@gmail.com" required maxlength="254" /><small class="field-help" id="senderHint">Alamat yang dipaparkan sebagai penghantar.</small></div>
                                   <div class="sa-field"><label for="smtp_from_name">Nama penghantar</label><input type="text" class="form-control" id="smtp_from_name" placeholder="MyRFID Admin" required maxlength="255" /></div>
                               </div>
                           </div>
                       </div>
                       </fieldset><div class="sa-settings-footer"><span id="saveStatus" role="status">Memuatkan tetapan…</span><button class="btn-sa-register" type="submit" id="btnSaveSmtp" disabled><i class="fa fa-save"></i> Simpan Tetapan</button></div>
                   </form>
                   <form class="sa-panel settings-test" id="testForm" onsubmit="testSmtpSettings(event)">
                       <h2>Uji penghantaran e-mel</h2>
                       <p>Hantar satu e-mel ujian menggunakan tetapan borang di atas. Ujian tidak menyimpan perubahan.</p>
                       <div class="settings-test-row">
                           <div class="sa-field"><label for="test_to">E-mel penerima ujian</label><input class="form-control" type="email" id="test_to" placeholder="nama@contoh.com" required maxlength="254" autocomplete="email"></div>
                           <button type="submit" class="btn-sa-register" id="btnTestSmtp" disabled><i class="fa fa-paper-plane-o" aria-hidden="true"></i> Hantar ujian</button>
                       </div>
                       <div id="testFeedback" class="settings-message settings-test-result" role="status" aria-live="polite" hidden></div>
                   </form>
                   </div>

                   <aside class="sa-settings-help">
                       <div class="sa-panel sa-help-card"><span class="sa-help-icon"><i class="fa fa-google"></i></span><h3>Menggunakan Gmail?</h3><p>Gunakan App Password untuk akaun Gmail yang dipilih.</p><ol><li>Hos: <strong>smtp.gmail.com</strong></li><li>Port 587 + STARTTLS atau 465 + SSL/TLS.</li><li>Semak e-mel penghantar sepadan dengan akaun atau alias penghantar yang dibenarkan.</li></ol></div>
                       <div class="sa-panel sa-help-card sa-help-security"><span class="sa-help-icon"><i class="fa fa-shield"></i></span><h3>Keselamatan</h3><p>Kosongkan ruangan password untuk mengekalkan password yang telah disimpan. Isi hanya apabila mahu menukarnya.</p><div class="settings-side-note">Tetapan berjaya disimpan tidak semestinya sambungan SMTP berjaya. Gunakan ujian e-mel untuk menyemak penghantaran.</div></div>
                   </aside>
               </div>
           </main>
       </div>
   </div>


<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
'use strict';
var smtpState = {loaded:false,busy:false,hasPassword:false,snapshot:''};
var smtpFieldNames = ['smtp_host','smtp_port','smtp_encryption','smtp_username','smtp_from_email','smtp_from_name'];
var toastTimeout;
function showToast(title,message,type) {
    clearTimeout(toastTimeout);
    document.getElementById('saToastTitle').textContent=title;
    document.getElementById('saToastMsg').textContent=message;
    document.getElementById('saToast').className='sa-toast show '+(type==='success'?'success':'error');
    document.getElementById('saToastIcon').innerHTML='<i class="fa '+(type==='success'?'fa-check-circle':'fa-exclamation-circle')+'" aria-hidden="true"></i>';
    toastTimeout=setTimeout(function(){document.getElementById('saToast').classList.remove('show');},5000);
}
function settingsFeedback(id,message,type) {
    var element=document.getElementById(id);
    element.hidden=!message;
    element.textContent=message;
    element.className='settings-message '+(id==='testFeedback'?'settings-test-result ':'')+(type||'');
}
function readSmtpForm() {
    var result={};
    smtpFieldNames.forEach(function(id){result[id]=document.getElementById(id).value.trim();});
    result.smtp_password=document.getElementById('smtp_password').value.trim();
    return result;
}
function smtpSnapshot(values) {
    return JSON.stringify(smtpFieldNames.map(function(id){return values[id];}));
}
function smtpIsDirty() {
    var values=readSmtpForm();
    return Boolean(values.smtp_password || smtpSnapshot(values)!==smtpState.snapshot);
}
function setSmtpBusy(busy) {
    smtpState.busy=busy;
    document.getElementById('smtpFields').disabled=busy||!smtpState.loaded;
    document.getElementById('btnSaveSmtp').disabled=busy||!smtpState.loaded;
    document.getElementById('btnTestSmtp').disabled=busy||!smtpState.loaded;
    document.getElementById('test_to').disabled=busy||!smtpState.loaded;
}
function updatePasswordHint() {
    document.getElementById('passwordHint').textContent=smtpState.hasPassword?'Password telah disimpan. Kosongkan untuk kekalkan; isi untuk tukar.':'Belum ada password tersimpan. Masukkan password SMTP.';
    document.getElementById('smtp_password').placeholder=smtpState.hasPassword?'Kosongkan untuk kekalkan password':'Masukkan password SMTP';
}
function updateSmtpHints() {
    var values=readSmtpForm();
    document.getElementById('saveStatus').textContent=smtpIsDirty()?'Ada perubahan belum disimpan.':'Tetapan sama seperti yang disimpan.';
    var gmail=values.smtp_host.toLowerCase()==='smtp.gmail.com';
    document.getElementById('senderHint').textContent=gmail&&values.smtp_username.toLowerCase()!==values.smtp_from_email.toLowerCase()?'Penghantar berbeza daripada akaun Gmail. Semak bahawa alamat ini ialah alias yang dibenarkan.':'Alamat yang dipaparkan sebagai penghantar.';
}
function toggleSmtpPass() {
    var input=document.getElementById('smtp_password');
    var show=input.type==='password';
    input.type=show?'text':'password';
    document.getElementById('smtpPassEyeIcon').className=show?'fa fa-eye-slash':'fa fa-eye';
    document.getElementById('smtpPassToggleLabel').textContent=show?'Sembunyi':'Lihat';
    var button=document.querySelector('.sa-password-toggle');
    button.setAttribute('aria-pressed',String(show));
    button.setAttribute('aria-label',show?'Sembunyikan password baharu':'Lihat password baharu');
}
async function smtpRequest(action,values) {
    var options={headers:{Accept:'application/json'}};
    var url='superadmin_ajax.php';
    if(values) {
        var fd=new FormData();fd.append('action',action);
        Object.keys(values).forEach(function(key){fd.append(key,values[key]);});
        options.method='POST';options.body=fd;
        var meta=document.querySelector('meta[name="csrf-token"]');
        if(meta)options.headers['X-CSRF-Token']=meta.content;
    } else {url+='?action='+encodeURIComponent(action);options.cache='no-store';}
    var response=await fetch(url,options);
    var type=response.headers.get('content-type')||'';
    if(!type.includes('application/json')) throw new Error('Respons pelayan tidak sah. Semak sesi log masuk dan cuba semula.');
    var data=await response.json();
    if(!response.ok||data.status!=='success') throw new Error(data.msg||'Permintaan tidak berjaya (HTTP '+response.status+').');
    return data;
}
async function loadSmtpSettings() {
    if(smtpState.busy)return;
    smtpState.loaded=false;setSmtpBusy(true);
    settingsFeedback('settingsFeedback','Memuatkan tetapan SMTP…','');
    try {
        var data=await smtpRequest('get_smtp');
        if(!data.smtp)throw new Error('Tetapan SMTP tidak diterima daripada pelayan.');
        var smtp=data.smtp;
        smtpFieldNames.forEach(function(id){document.getElementById(id).value=smtp[id]==null?'':String(smtp[id]);});
        document.getElementById('smtp_port').value=smtp.smtp_port||587;
        document.getElementById('smtp_encryption').value=smtp.smtp_encryption||'tls';
        smtpState.hasPassword=Boolean(smtp.smtp_password);
        document.getElementById('smtp_password').value='';
        smtpState.snapshot=smtpSnapshot(readSmtpForm());smtpState.loaded=true;
        updatePasswordHint();updateSmtpHints();
        settingsFeedback('settingsFeedback','','');
    } catch(error) {
        settingsFeedback('settingsFeedback',error.message,'error');
        var retry=document.createElement('button');retry.type='button';retry.textContent='Cuba semula';retry.addEventListener('click',loadSmtpSettings);
        document.getElementById('settingsFeedback').appendChild(retry);
        document.getElementById('saveStatus').textContent='Tetapan belum dimuatkan.';
    } finally {setSmtpBusy(false);}
}
function smtpValidationError(values,hasPassword) {
    if(!/^[A-Za-z0-9.-]+$/.test(values.smtp_host))return 'Isi hos sahaja, contohnya smtp.gmail.com, tanpa https:// atau nombor port.';
    var port=Number(values.smtp_port);
    if(!Number.isInteger(port)||port<1||port>65535)return 'Port mesti antara 1 hingga 65535.';
    if(!['tls','ssl','none'].includes(values.smtp_encryption))return 'Pilih jenis enkripsi yang sah.';
    if(!values.smtp_username)return 'Masukkan username SMTP.';
    if(!values.smtp_password&&!hasPassword)return 'Masukkan password SMTP terlebih dahulu.';
    // Existing server identifies any asterisk as a masked saved password.
    if(values.smtp_password.includes('*'))return 'Versi pelayan ini menganggap aksara * sebagai password bertopeng. Password baharu yang mengandungi * tidak boleh digunakan melalui borang ini.';
    return '';
}
function validateSmtpForm() {
    if(!smtpState.loaded||smtpState.busy)return null;
    if(!document.getElementById('smtpForm').reportValidity())return null;
    var values=readSmtpForm();
    var error=smtpValidationError(values,smtpState.hasPassword);
    if(error){settingsFeedback('settingsFeedback',error,'error');return null;}
    settingsFeedback('settingsFeedback','','');return values;
}
function smtpPayload(values,action,hasPassword) {
    var payload=Object.assign({},values);
    // Save: blank preserves password. Test: server resolves this mask from DB.
    if(action==='test_smtp'&&!payload.smtp_password&&hasPassword)payload.smtp_password='************';
    return payload;
}
async function saveSmtpSettings(event) {
    if(event)event.preventDefault();
    var values=validateSmtpForm();if(!values)return;
    setSmtpBusy(true);
    var button=document.getElementById('btnSaveSmtp');
    button.innerHTML='<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Menyimpan…';
    try {
        await smtpRequest('save_smtp',smtpPayload(values,'save_smtp',smtpState.hasPassword));
        if(values.smtp_password)smtpState.hasPassword=true;
        smtpFieldNames.forEach(function(id){document.getElementById(id).value=values[id];});
        var password=document.getElementById('smtp_password');password.value='';password.type='password';
        document.getElementById('smtpPassEyeIcon').className='fa fa-eye';
        document.getElementById('smtpPassToggleLabel').textContent='Lihat';
        document.querySelector('.sa-password-toggle').setAttribute('aria-pressed','false');
        document.querySelector('.sa-password-toggle').setAttribute('aria-label','Lihat password baharu');
        smtpState.snapshot=smtpSnapshot(readSmtpForm());
        updatePasswordHint();updateSmtpHints();
        settingsFeedback('settingsFeedback','Tetapan berjaya disimpan. Gunakan ujian e-mel untuk menyemak sambungan.','success');
        showToast('Tetapan disimpan','Perubahan akan digunakan untuk e-mel seterusnya.','success');
    } catch(error){settingsFeedback('settingsFeedback',error.message,'error');}
    finally {button.innerHTML='<i class="fa fa-save" aria-hidden="true"></i> Simpan Tetapan';setSmtpBusy(false);}
}
async function testSmtpSettings(event) {
    if(event)event.preventDefault();
    var values=validateSmtpForm();if(!values)return;
    if(!document.getElementById('testForm').reportValidity())return;
    var payload=smtpPayload(values,'test_smtp',smtpState.hasPassword);
    payload.test_to=document.getElementById('test_to').value.trim();
    setSmtpBusy(true);
    var button=document.getElementById('btnTestSmtp');
    button.innerHTML='<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Menguji…';
    settingsFeedback('testFeedback','Sedang menghubungi pelayan SMTP. Tunggu keputusan ujian.','');
    try {
        var data=await smtpRequest('test_smtp',payload);
        settingsFeedback('testFeedback',(data.msg||'E-mel ujian diterima oleh pelayan SMTP.')+' Semak peti masuk dan folder spam penerima.','success');
    } catch(error){settingsFeedback('testFeedback',error.message,'error');}
    finally {button.innerHTML='<i class="fa fa-paper-plane-o" aria-hidden="true"></i> Hantar ujian';setSmtpBusy(false);}
}
document.addEventListener('DOMContentLoaded',function(){
    document.querySelector('.sa-password-toggle').setAttribute('aria-pressed','false');
    document.getElementById('smtpForm').addEventListener('input',function(){if(!smtpState.loaded)return;updateSmtpHints();settingsFeedback('testFeedback','','');settingsFeedback('settingsFeedback','','');});
    loadSmtpSettings();fetchNotifications();
    setInterval(function(){if(!document.hidden)fetchNotifications();},60000);
});
window.addEventListener('beforeunload',function(event){if(smtpState.loaded&&(smtpIsDirty()||smtpState.busy)){event.preventDefault();event.returnValue='';}});

var saNotifications=[];var notificationBusy=false;
function escapeLogHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
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


</script>
</body>
</html>
