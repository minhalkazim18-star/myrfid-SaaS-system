<?php
require_once __DIR__ . '/security.php';
app_start_session();
include 'db_connect.php';

require_active_roles($conn, ['superadmin'], false);

include 'header.php';
?>
      </div> <!-- close inner_container from header.php -->
   </div> <!-- close full_container from header.php -->

<style>
/* ==========================================================================
   SUPER ADMIN PORTAL MODERN DESIGN SYSTEM (FULL WIDTH & CLEAN)
   ========================================================================== */
body {
    background: #f1f5f9 !important;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    overflow-y: scroll !important;
}
.superadmin-portal-wrapper {
    width: 100%;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f8fafc;
}

/* SLEEK FLOATING TOAST NOTIFICATION (NO BROWSER ALERT POPUPS) */
.sa-toast-container {
    position: fixed;
    top: 28px;
    right: 28px;
    z-index: 99999;
    pointer-events: none;
}
.sa-toast {
    background: #ffffff;
    border-radius: 14px;
    padding: 16px 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    border-left: 5px solid #2563eb;
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 320px;
    max-width: 440px;
    transform: translateY(-20px);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.sa-toast.show {
    transform: translateY(0);
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}
.sa-toast.success { border-left-color: #10b981; }
.sa-toast.warning { border-left-color: #f59e0b; }
.sa-toast.error { border-left-color: #ef4444; }
.sa-toast-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
.sa-toast.success .sa-toast-icon { background: #d1fae5; color: #059669; }
.sa-toast.warning .sa-toast-icon { background: #fef3c7; color: #b45309; }
.sa-toast.error .sa-toast-icon { background: #fee2e2; color: #dc2626; }
.sa-toast-body { flex: 1; }
.sa-toast-title { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 2px; }
.sa-toast-msg { font-size: 13px; color: #475569; line-height: 1.4; }
.sa-toast-close {
    background: transparent;
    border: none;
    font-size: 20px;
    color: #94a3b8;
    cursor: pointer;
    line-height: 1;
}

/* TOP SUPER ADMIN HEADER BAR */
.superadmin-header-bar {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    padding: 24px 40px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    width: 100%;
}
.sa-title-group {
    display: flex;
    align-items: center;
    gap: 18px;
}
.sa-badge-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
    flex-shrink: 0;
}
.sa-main-title {
    font-size: 24px;
    font-weight: 800;
    margin: 0;
    color: #ffffff;
    letter-spacing: 0.3px;
}
.sa-sub-title {
    font-size: 13.5px;
    color: #94a3b8;
    margin: 3px 0 0 0;
}
.btn-sa-register {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff !important;
    font-weight: 600;
    border: none;
    padding: 0 24px;
    height: 48px;
    border-radius: 10px;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
    transition: all 0.25s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}
.btn-sa-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(37, 99, 235, 0.45);
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.btn-sa-logout {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: all 0.25s ease;
    text-decoration: none;
}
.btn-sa-logout:hover {
    background: rgba(239, 68, 68, 0.9);
    color: #ffffff;
    border-color: transparent;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}


/* MAIN CONTENT AREA */
.superadmin-main-body {
    padding: 32px 40px;
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
    flex: 1;
}

/* KPI CARDS */
.sa-kpi-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 24px 26px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.25s ease;
    height: 100%;
}
.sa-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}
.kpi-text-col .kpi-label {
    font-size: 12.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #64748b;
    margin-bottom: 8px;
    white-space: nowrap;
}
.kpi-text-col .kpi-number {
    font-size: 32px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
}
.kpi-icon-wrapper {
    width: 58px;
    height: 58px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}
.icon-blue { background: #eff6ff; color: #2563eb; }
.icon-green { background: #ecfdf5; color: #059669; }
.icon-amber { background: #fffbeb; color: #d97706; }
.icon-purple { background: #f3e8ff; color: #9333ea; }

/* TENANT DIRECTORY TABLE */
.sa-table-card {
    background: #ffffff;
    border-radius: 16px;
    margin-top: 28px;
    padding: 28px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    width: 100%;
}
.sa-table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}
.sa-table-title {
    font-size: 19px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}
.sa-search-box {
    position: relative;
    width: 320px;
}
.sa-search-box input {
    width: 100%;
    padding: 11px 16px 11px 40px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    font-size: 14px;
    outline: none;
    transition: all 0.2s ease;
}
.sa-search-box input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}
.sa-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.table-tenant {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
.table-tenant th {
    background: #f8fafc;
    color: #475569;
    font-size: 12.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 16px 18px;
    border-bottom: 2px solid #e2e8f0;
}
.table-tenant td {
    padding: 18px;
    font-size: 14.5px;
    color: #1e293b;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.table-tenant tr:hover td {
    background: #f8fafc;
}

/* Status Badges */
.badge-tenant-aktif {
    background: #dcfce7;
    color: #166534;
    font-weight: 700;
    font-size: 12px;
    padding: 7px 14px;
    border-radius: 30px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.badge-tenant-digantung {
    background: #fee2e2;
    color: #991b1b;
    font-weight: 700;
    font-size: 12px;
    padding: 7px 14px;
    border-radius: 30px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.badge-tenant-tamat {
    background: #fff7ed;
    color: #c2410c;
    font-weight: 700;
    font-size: 12px;
    padding: 7px 14px;
    border-radius: 30px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid #fed7aa;
}

/* Vibrant Action Buttons in Table */
.btn-action-edit { background: #3b82f6; color: #ffffff !important; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; padding: 8px 14px; display: inline-flex; align-items: center; transition: all 0.2s ease; cursor: pointer; box-shadow: 0 2px 6px rgba(59,130,246,0.3); }
.btn-action-edit:hover { background: #2563eb; color: #ffffff !important; transform: translateY(-1px); }
.btn-action-add { background: #8b5cf6; color: #ffffff !important; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; padding: 8px 14px; display: inline-flex; align-items: center; transition: all 0.2s ease; cursor: pointer; box-shadow: 0 2px 6px rgba(139,92,246,0.3); }
.btn-action-add:hover { background: #7c3aed; color: #ffffff !important; transform: translateY(-1px); }
.btn-action-suspend { background: #ef4444; color: #ffffff !important; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; padding: 8px 14px; display: inline-flex; align-items: center; transition: all 0.2s ease; cursor: pointer; box-shadow: 0 2px 6px rgba(239,68,68,0.3); }
.btn-action-suspend:hover { background: #dc2626; color: #ffffff !important; transform: translateY(-1px); }
.btn-action-activate { background: #10b981; color: #ffffff !important; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; padding: 8px 14px; display: inline-flex; align-items: center; transition: all 0.2s ease; cursor: pointer; box-shadow: 0 2px 6px rgba(16,185,129,0.3); }
.btn-action-activate:hover { background: #059669; color: #ffffff !important; transform: translateY(-1px); }

.sa-directory .sa-row-actions{flex-wrap:wrap;}

#schoolsTable .sa-school-actions{display:flex;flex-wrap:nowrap;gap:7px;justify-content:flex-end;white-space:nowrap}#schoolsTable th:last-child,#schoolsTable td:last-child{min-width:185px}#schoolsTable .sa-school-actions>.sa-action-btn{min-height:33px;padding:0 11px;font-size:11px;border-radius:7px}
.sa-school-menu[popover]{position:fixed;inset:auto;margin:0;padding:6px;background:white;border:1px solid #dce4ef;border-radius:10px;box-shadow:0 12px 35px #10213b26;width:210px;max-height:calc(100vh - 24px);overflow-y:auto;color:#23374d}
.sa-school-menu[popover]:popover-open{display:flex;flex-direction:column;gap:2px}.sa-school-menu>button,.sa-school-menu>a{display:block;width:100%;text-align:left;padding:10px 11px;background:transparent;border:0;border-radius:6px;font-family:inherit;font-size:12px;color:#334b65;text-decoration:none;cursor:pointer}.sa-school-menu>button:hover,.sa-school-menu>a:hover{background:#f0f5fc}.sa-school-menu>.sa-menu-status{border-top:1px solid #e7edf5;border-radius:0;color:#9b541c;margin-top:4px}.sa-school-menu>button:focus-visible,.sa-school-menu>a:focus-visible{outline:2px solid #246bfd;outline-offset:-2px}
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


/* Dashboard refresh: all dashboard styles are included in this file. */
:root { --sa-navy:#111e32; --sa-blue:#2563eb; --sa-blue-dark:#1d4ed8; --sa-text:#17243a; --sa-muted:#64748b; --sa-border:#e2e7ef; --sa-bg:#f6f8fb; --sa-radius:10px; --sa-shadow:0 2px 5px rgba(20,35,60,.025); }
body.dashboard.dashboard_1, body {font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif!important;}
.superadmin-portal-wrapper {background:var(--sa-bg);}
.sa-sidebar {background:var(--sa-navy);box-shadow:none;}
.sa-brand {font-size:17px;font-weight:650;}
.sa-brand small {color:#94b8ff;}
.sa-brand-mark {background:#2563eb;border-radius:9px;box-shadow:none;}
.sa-side-label {color:#9aabc1;font-size:10px;font-weight:600;}
.sa-side-nav>a {font-size:13px;font-weight:500;border-radius:7px;color:#bac7d9;}
.sa-side-nav>a.active {background:#253a59;box-shadow:inset 3px 0 0 #73a2ff;}
.sa-workspace-bar {box-shadow:none;border-bottom:1px solid var(--sa-border);height:68px;}
.sa-workspace-context {font-size:12px;}
.superadmin-main-body {max-width:1600px!important;padding:32px 36px 44px!important;gap:0;}
.sa-dashboard-hero {padding:0 0 24px;margin:0;background:none;border:0;box-shadow:none;border-radius:0;min-height:0;overflow:visible;align-items:center;}
.sa-dashboard-hero::before,.sa-dashboard-hero::after {display:none;}
.sa-dashboard-hero .sa-eyebrow {color:#64748b;font-size:11px;font-weight:600;letter-spacing:1px;margin-bottom:8px;}
.sa-dashboard-hero h1 {color:#17243a!important;font-size:30px;font-weight:700;letter-spacing:-1px;margin-bottom:8px;}
.sa-dashboard-hero p {color:#64748b!important;font-size:14px;line-height:1.5;}
.sa-hero-actions {margin:0;gap:10px;}
.sa-hero-btn-secondary {color:#334155;background:white;border:1px solid #d9e1ec;font-size:12px;}
.sa-hero-btn-secondary:hover {background:#edf3ff;color:#1d4ed8;}
.sa-date-chip {display:flex;align-items:center;gap:9px;color:#64748b;font-size:12px;padding:10px 12px;white-space:nowrap;}
.btn-sa-register {box-shadow:none!important;border-radius:7px!important;font-weight:600;}
.sa-kpi-grid {gap:16px;margin-bottom:20px;}
.sa-kpi-card {min-height:120px;padding:20px!important;box-shadow:none!important;border-radius:10px!important;}
.sa-kpi-card::before {display:none;}
.sa-kpi-card:hover {transform:none!important;box-shadow:none!important;}
.kpi-text-col .kpi-label {text-transform:none!important;font-size:12px!important;letter-spacing:0!important;font-weight:550;color:#526078;}
.kpi-text-col .kpi-number {font-size:30px!important;font-weight:650;line-height:1.3;color:#17243a!important;}
.kpi-note {color:#64748b;font-size:11px;}
.kpi-icon-wrapper {background:#f0f4fa!important;color:#53719a!important;border-radius:9px!important;width:40px!important;height:40px!important;font-size:17px!important;}
.sa-priority-strip {order:3;display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;}
.sa-priority {display:flex;align-items:center;gap:14px;padding:16px 18px;background:#eff5ff;border:1px solid #d7e5fc;border-radius:9px;cursor:pointer;text-align:left;color:#203c68;}
.sa-priority:hover {background:#e5efff;}
.sa-priority>i:first-child {font-size:19px;color:#426dab;}
.sa-priority-copy {flex:1;min-width:0;}
.sa-priority-copy strong,.sa-priority-copy small {display:block;}
.sa-priority-copy strong {font-size:13px;font-weight:650;}
.sa-priority-copy small {font-size:11px;margin-top:4px;color:#526b8b;}
.sa-priority:last-child {background:#fff;border-color:var(--sa-border);}
.sa-panel {border-radius:10px;box-shadow:var(--sa-shadow);}
.sa-action-center {order:4;margin-bottom:20px;}
.sa-directory {order:5;margin-bottom:20px;}
.sa-insights-grid {order:6;margin:0;grid-template-columns:minmax(0,1.5fr) minmax(0,1fr) minmax(0,1fr);gap:16px;}
.sa-section-kicker {display:none;}
.sa-panel-header {min-height:65px;padding:20px 20px 14px;}
.sa-table-title {font-size:16px!important;font-weight:650;}
.sa-action-heading p {font-size:12px;margin-top:5px;}
.sa-panel-meta {font-weight:400;color:#64748b;}
.sa-chart-wrap {height:180px;}
.alert-list-container {height:180px;}
.sa-tabs {padding:0 20px;gap:16px;}
.sa-tab {font-weight:600;font-size:12px;padding:12px 2px;}
.sa-tab::after {left:0;right:0;}
.sa-tab-panel {padding:0 16px 8px;min-height:100px;}
.table-tenant th {font-size:10px!important;text-transform:none!important;letter-spacing:0!important;font-weight:600!important;padding:12px!important;color:#526078!important;background:#f8fafc!important;white-space:nowrap;}
.table-tenant td {font-size:12px!important;padding:15px 12px!important;}
.table-tenant td[colspan] {text-align:center!important;}
.sa-empty-state {padding:22px 16px!important;font-size:12px!important;}
.sa-empty-state i {background:#f1f5f9;color:#8190a5;font-size:14px;width:30px;height:30px;}
.sa-error-state i {color:#c93443;background:#fff1f2;}
.sa-directory .table-responsive {padding:0 16px 8px;}
.sa-directory-heading {padding:18px 20px;}
.sa-search {height:38px;background:#fbfcfe;border-radius:7px;}
.sa-action-btn {font-weight:600;font-size:11px;min-height:32px;box-shadow:none;}
.sa-side-status {background:rgba(255,255,255,.03);}
button:focus-visible,a:focus-visible {outline:3px solid #93b4f4!important;outline-offset:3px;}
@media(max-width:1100px) { .superadmin-main-body {padding:26px 24px!important;} .sa-insights-grid {grid-template-columns:1fr 1fr;} .sa-alert-panel {grid-column:1/-1;} }
@media(max-width:760px) { .superadmin-main-body {padding:24px 16px!important;} .sa-dashboard-hero {padding:0 0 20px;gap:16px;} .sa-dashboard-hero h1 {font-size:26px;} .sa-hero-actions {flex-wrap:wrap;} .sa-kpi-grid {grid-template-columns:1fr 1fr;gap:10px;} .sa-kpi-card {padding:14px!important;min-height:116px;} .kpi-icon-wrapper {display:none!important;} .sa-priority-strip,.sa-insights-grid {grid-template-columns:1fr;} .sa-priority-strip {gap:10px;} .sa-tabs {gap:16px;padding:0 16px;} .sa-panel-header {padding:16px;} .sa-side-nav>a.active {box-shadow:inset 0 -2px 0 #73a2ff;} .sa-action-heading {align-items:flex-start;} .sa-icon-button {min-width:36px;padding:0 10px;} .sa-icon-button span:last-child {display:none;} .sa-date-chip {padding-left:0;} }
@media(prefers-reduced-motion:reduce) { *,*::before,*::after {scroll-behavior:auto!important;transition:none!important;animation:none!important;} }


.sa-directory .sa-row-actions{flex-wrap:wrap;}
</style>

<!-- FLOATING TOAST NOTIFICATION CONTAINER -->
<div class="sa-toast-container">
    <div id="saToast" class="sa-toast">
        <div class="sa-toast-icon" id="saToastIcon"><i class="fa fa-check"></i></div>
        <div class="sa-toast-body">
            <div class="sa-toast-title" id="saToastTitle">Berjaya</div>
            <div class="sa-toast-msg" id="saToastMsg">Operasi selesai.</div>
        </div>
        <button type="button" class="sa-toast-close" onclick="hideToast()">&times;</button>
    </div>
</div>

<div class="superadmin-portal-wrapper">
    <aside class="sa-sidebar">
        <a class="sa-brand" href="superadmin_dashboard.php" aria-label="Dashboard SuperAdmin">
            <span class="sa-brand-mark"><i class="fa fa-shield"></i></span>
            <span><small>DRS</small> SuperAdmin</span>
        </a>
        <nav class="sa-side-nav" aria-label="Navigasi SuperAdmin">
            <span class="sa-side-label">Workspace</span>
            <a class="active" href="superadmin_dashboard.php"><i class="fa fa-th-large"></i><span>Dashboard</span></a>
            <a href="superadmin_users.php"><i class="fa fa-users" aria-hidden="true"></i><span>Pengguna</span></a>
<a href="superadmin_logs.php"><i class="fa fa-history"></i><span>Log Aktiviti</span></a>
            <a href="superadmin_tetapan.php"><i class="fa fa-sliders"></i><span>Tetapan Sistem</span></a>
        </nav>
        <div class="sa-side-status">
            <span class="sa-live-dot"></span>
            <div><strong>Sistem beroperasi</strong><small>Perkhidmatan platform aktif</small></div>
        </div>
        <form method="post" action="logout.php" class="sa-logout-form">
            <input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
            <button type="submit"><i class="fa fa-sign-out"></i><span>Log Keluar</span></button>
        </form>
    </aside>

    <div class="sa-workspace">
        <header class="sa-workspace-bar">
            <div class="sa-workspace-context">
                <span>Platform</span><i class="fa fa-angle-right"></i><strong>Dashboard</strong>
            </div>
            <div class="sa-workspace-actions">
                <div class="dropdown">
                    <button type="button" class="sa-nav-icon sa-notification-button dropdown-toggle" data-toggle="dropdown" title="Notifikasi Sistem" aria-label="Notifikasi sistem">
                        <svg class="sa-button-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                        <span class="sa-nav-button-label">Notifikasi</span>
                        <span id="bellBadge" class="sa-notification-count">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right sa-notification-menu">
                        <div class="dropdown-header">
                            <div><strong>Notifikasi</strong><small id="notificationSummary">Memuatkan...</small></div>
                            <button type="button" id="markAllNotifications" onclick="markAllNotificationsRead(event)" disabled>Tanda semua dibaca</button>
                        </div>
                        <div class="sa-notification-list" id="notificationList" aria-live="polite">
                            <div class="text-center text-muted py-3">Memuatkan...</div>
                        </div>
                    </div>
                </div>
                <span class="sa-admin-chip"><span>SA</span><strong>SuperAdmin</strong></span>
                <button class="btn-sa-register" data-toggle="modal" data-target="#modalDaftarSekolah"><i class="fa fa-plus"></i> Sekolah Baharu</button>
            </div>
        </header>

    <!-- MAIN CONTENT BODY -->
    <main class="superadmin-main-body">
        <div class="sa-page-heading sa-dashboard-hero">
            <div class="sa-hero-copy">
                <span class="sa-eyebrow">PENGURUSAN PLATFORM</span>
                <h1>Dashboard</h1>
                <p>Urus sekolah, semak bayaran dan pantau langganan.</p>
            </div>
            <div class="sa-hero-actions">
                <span class="sa-date-chip"><i class="fa fa-calendar-o" aria-hidden="true"></i><?php echo escape_html(date('d/m/Y')); ?></span>
                <button type="button" class="sa-hero-btn sa-hero-btn-secondary" onclick="document.getElementById('tenantDirectory').scrollIntoView({behavior:'smooth',block:'start'})">Direktori sekolah <span aria-hidden="true">&#8595;</span></button>
            </div>
        </div>

        <!-- KPI OVERVIEW CARDS (REAL-TIME AJAX) -->
        <div class="sa-kpi-grid">
            <div>
                <div class="sa-kpi-card">
                    <div class="kpi-text-col">
                        <div class="kpi-label">Jumlah sekolah</div>
                        <div class="kpi-number" id="stat-total-schools">...</div>
                        <div class="kpi-note">Semua sekolah berdaftar</div>
                    </div>
                    <div class="kpi-icon-wrapper icon-blue">
                        <i class="fa fa-building"></i>
                    </div>
                </div>
            </div>
            <div>
                <div class="sa-kpi-card">
                    <div class="kpi-text-col">
                        <div class="kpi-label">Sekolah Aktif</div>
                        <div class="kpi-number text-success" id="stat-active-schools">...</div>
                        <div class="kpi-note">Akses sedang dibuka</div>
                    </div>
                    <div class="kpi-icon-wrapper icon-green">
                        <i class="fa fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div>
                <div class="sa-kpi-card">
                    <div class="kpi-text-col">
                        <div class="kpi-label">Pelajar berdaftar</div>
                        <div class="kpi-number" id="stat-total-students">...</div>
                        <div class="kpi-note">Merentas semua sekolah</div>
                    </div>
                    <div class="kpi-icon-wrapper icon-amber">
                        <i class="fa fa-users"></i>
                    </div>
                </div>
            </div>
            <div>
                <div class="sa-kpi-card">
                    <div class="kpi-text-col">
                        <div class="kpi-label">Staf & pentadbir</div>
                        <div class="kpi-number" id="stat-total-admins">...</div>
                        <div class="kpi-note">Tidak termasuk SuperAdmin</div>
                    </div>
                    <div class="kpi-icon-wrapper icon-purple">
                        <i class="fa fa-user-secret"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- ANALYTICS CHARTS SECTION -->
        <section class="sa-insights-grid" aria-label="Analitik platform">
            <!-- Line Chart: Pendaftaran -->
            <div class="sa-panel sa-trend-panel">
                    <div class="sa-panel-header">
                        <div><span class="sa-section-kicker">Pertumbuhan</span><h2 class="sa-table-title">Trend pendaftaran</h2></div>
                        <span class="sa-panel-meta">Rekod pendaftaran terkini</span>
                    </div>
                    <div class="sa-chart-wrap">
                        <canvas id="trendChart"></canvas>
                    </div>
            </div>
            
            <!-- Pie Chart: Pecahan Pelan -->
            <div class="sa-panel sa-plan-panel">
                    <div class="sa-panel-header">
                        <div><span class="sa-section-kicker">Langganan</span><h2 class="sa-table-title">Pecahan pelan</h2></div>
                    </div>
                    <div class="sa-chart-wrap sa-chart-wrap-small">
                        <canvas id="planChart"></canvas>
                    </div>
            </div>
            
            <!-- Alert Box: Hampir Tamat -->
            <div class="sa-panel sa-alert-panel">
                    <div class="sa-panel-header">
                        <div><span class="sa-section-kicker sa-kicker-alert">Perlu dipantau</span><h2 class="sa-table-title">Status langganan</h2></div>
                    </div>
                    <div class="alert-list-container">
                        <a href="superadmin_renewals.php">Urus pembaharuan &amp; bukti bayaran →</a><ul class="list-group list-group-flush" id="expiredSchoolsList"></ul><h3 style="font-size:13px;margin-top:18px">Hampir tamat (30 hari)</h3><ul class="list-group list-group-flush" id="expiringSoonList">
                            <li class="list-group-item text-center text-muted border-0"><i class="fa fa-spinner fa-spin"></i> Memuatkan...</li>
                        </ul>
                    </div>
            </div>
        </section>

        <div class="sa-priority-strip" aria-label="Tindakan pantas">
            <button type="button" class="sa-priority" onclick="openActionPanel('paymentPanel')">
                <i class="fa fa-credit-card" aria-hidden="true"></i>
                <span class="sa-priority-copy"><strong><span id="priorityPaymentCount">—</span> bayaran menunggu</strong><small>Semak bayaran dan status sebut harga</small></span>
                <i class="fa fa-angle-right" aria-hidden="true"></i>
            </button>
            <button type="button" class="sa-priority" onclick="openActionPanel('approvalPanel')">
                <i class="fa fa-file-text-o" aria-hidden="true"></i>
                <span class="sa-priority-copy"><strong><span id="heroApprovalCount">—</span> permohonan baharu</strong><small>Semak dan luluskan pendaftaran sekolah</small></span>
                <i class="fa fa-angle-right" aria-hidden="true"></i>
            </button>
        </div>
        <section class="sa-panel sa-action-center">
            <div class="sa-panel-header sa-action-heading">
                <div>
                    <span class="sa-section-kicker">Aliran kerja</span>
                    <h2 class="sa-table-title">Pusat tindakan</h2>
                    <p>Semak permohonan yang memerlukan keputusan atau susulan.</p>
                </div>
                <button type="button" class="sa-icon-button" onclick="refreshActionCentre()" title="Muat semula"><span aria-hidden="true">&#8635;</span><span>Muat semula</span></button>
            </div>
            <div class="sa-tabs" role="tablist" aria-label="Kategori tindakan">
                <button class="sa-tab active" type="button" data-target="approvalPanel" role="tab" aria-selected="true">Kelulusan <span id="approvalCount">0</span></button>
                <button class="sa-tab" type="button" data-target="paymentPanel" role="tab" aria-selected="false">Bayaran <span id="paymentCount">0</span></button>
                <button class="sa-tab" type="button" data-target="upgradePanel" role="tab" aria-selected="false">Naik taraf <span id="upgradeCount">0</span></button>
            </div>

                <!-- PENDING APPROVAL SECTION -->
        <div class="sa-tab-panel active" id="approvalPanel" role="tabpanel">
            <div class="table-responsive">
                <table class="table-tenant table table-borderless table-hover" id="pendingApprovalTable" width="100%">
                    <thead>
                        <tr>
                            <th style="width: 5%">#</th>
                            <th style="width: 15%">Kod Sekolah</th>
                            <th style="width: 20%">Nama Sekolah</th>
                            <th style="width: 15%">Hubungan</th>
                            <th style="width: 10%" class="text-center">Pelan</th>
                            <th style="width: 10%">Tarikh Daftar</th>
                            <th style="width: 10%" class="text-center">Status</th>
                            <th style="width: 15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="pendingApprovalTbody">
                        <tr><td colspan="8" class="text-center py-4"><i class="fa fa-spinner fa-spin"></i> Memuat turun data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PENDING PAYMENT SECTION -->
        <div class="sa-tab-panel" id="paymentPanel" role="tabpanel" hidden>
            <div class="table-responsive">
                <table class="table-tenant table table-borderless table-hover" id="pendingPaymentTable" width="100%">
                    <thead>
                        <tr>
                            <th style="width: 5%">#</th>
                            <th style="width: 15%">No. Rujukan Sebut Harga</th>
                            <th style="width: 20%">Nama Sekolah</th>
                            <th style="width: 15%">Pelan Langganan</th>
                            <th style="width: 12%">T. Hantar</th>
                            <th style="width: 12%">T. Luput</th>
                            <th style="width: 10%" class="text-center">Status</th>
                            <th style="width: 11%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="pendingPaymentTbody">
                        <tr><td colspan="8" class="text-center py-4"><i class="fa fa-spinner fa-spin"></i> Memuat turun data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PENDING UPGRADE SECTION -->
        <div class="sa-tab-panel" id="upgradePanel" role="tabpanel" hidden>
            <div class="table-responsive">
                <table class="table-tenant table table-borderless table-hover" id="pendingUpgradeTable" width="100%">
                    <thead>
                        <tr>
                            <th style="width: 5%">#</th>
                            <th style="width: 15%">Kod Sekolah</th>
                            <th style="width: 30%">Nama Sekolah</th>
                            <th style="width: 20%">Tarikh Permohonan</th>
                            <th style="width: 15%" class="text-center">Status Semasa</th>
                            <th style="width: 15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="pendingUpgradeTbody">
                        <tr><td colspan="6" class="text-center py-4"><i class="fa fa-spinner fa-spin"></i> Memuat turun data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        </section>

        <!-- TENANT DIRECTORY TABLE CARD -->
        <section class="sa-panel sa-directory" id="tenantDirectory">
            <div class="sa-panel-header sa-directory-heading">
                <div><span class="sa-section-kicker">Pengurusan tenant</span><h2 class="sa-table-title">Direktori sekolah</h2></div>
                <label class="sa-search" for="schoolSearch"><i class="fa fa-search"></i><input id="schoolSearch" type="search" placeholder="Cari sekolah, kod atau e-mel" autocomplete="off"></label>
            </div>
            <div class="table-responsive">
                <table class="table-tenant table table-borderless table-hover" id="schoolsTable" width="100%">
                    <thead>
                        <tr>
                            <th style="width: 5%">#</th>
                            <th style="width: 10%">Kod Sekolah</th>
                            <th style="width: 18%">Nama Sekolah</th>
                            <th style="width: 15%">Hubungan</th>
                            <th style="width: 7%" class="text-center">Pelajar</th>
                            <th style="width: 7%" class="text-center">Staf</th>
                            <th style="width: 15%">Langganan</th>
                            <th style="width: 8%" class="text-center">Status</th>
                            <th style="width: 15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="schoolsTbody">
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa fa-spinner fa-spin fa-2x mb-2"></i>
                                <p class="mb-0">Memuat turun data senarai sekolah SaaS...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        </main>
    </div>
</div>

<!-- MODAL: DAFTAR SEKOLAH BAHARU -->
<div class="modal fade" id="modalDaftarSekolah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-dark text-white px-4 py-3 rounded-top-4">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-plus-circle text-primary mr-2"></i> Daftar Sekolah Baharu &amp; Pentadbir Utama</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formDaftarSekolah" onsubmit="handleRegisterSchool(event)">
                <div class="modal-body p-4">
                    <h6 class="font-weight-bold text-primary mb-3"><i class="fa fa-building mr-2"></i> 1. Maklumat Sekolah (Tenant)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label font-weight-bold">Kod Sekolah <span class="text-danger">*</span></label><input type="text" class="form-control" name="kod_sekolah" placeholder="Cth: SKLH002" required /></div>
                        <div class="col-md-8"><label class="form-label font-weight-bold">Nama Sekolah <span class="text-danger">*</span></label><input type="text" class="form-control" name="nama_sekolah" placeholder="Cth: SK Seri Makmur" required /></div>
                        <div class="col-md-6 mb-3"><label class="form-label font-weight-bold">E-mel Sekolah</label><input type="email" class="form-control" name="email_sekolah" placeholder="Cth: admin@sk.edu.my" /></div>
                        <div class="col-md-6 mb-3"><label class="form-label font-weight-bold">No. Telefon Sekolah</label><input type="text" class="form-control" name="no_tel" placeholder="Cth: 03-88881234" /></div>
                    </div>
                    <hr class="my-4" />
                    <h6 class="font-weight-bold text-primary mb-3"><i class="fa fa-user-circle mr-2"></i> 2. Maklumat Pentadbir Utama</h6>
                    <div class="row">
                        <div class="col-md-12"><label class="form-label font-weight-bold">Nama Penuh Pentadbir <span class="text-danger">*</span></label><input type="text" class="form-control" name="admin_nama" placeholder="Cth: Cikgu Ahmad Bin Razak" required /></div>
                        <div class="col-md-12"><label class="form-label font-weight-bold">E-mel Pentadbir <span class="text-danger">*</span></label><input type="email" class="form-control" name="admin_email" placeholder="Cth: cikgu@moe-dl.edu.my" required /></div>
                        <div class="col-md-6 mb-3"><label class="form-label font-weight-bold">Username <span class="text-danger">*</span></label><input type="text" class="form-control" name="admin_username" placeholder="Cth: admin_serimakmur" required /></div>
                        <div class="col-md-6 mb-3"><label class="form-label font-weight-bold">Kata Laluan <span class="text-danger">*</span></label><input type="password" class="form-control" name="admin_password" minlength="10" autocomplete="new-password" placeholder="Minimum 10 aksara" required /></div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 font-weight-bold" id="btnSubmitRegister"><i class="fa fa-check mr-1"></i> Daftar Sekolah Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: TAMBAH ADMIN -->
<div class="modal fade" id="modalTambahAdmin" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-primary text-white px-4 py-3 rounded-top-4">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-user-plus mr-2"></i> Tambah Admin Sekolah (#<span id="targetSchIdLabel"></span>)</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formTambahAdmin" onsubmit="handleAddAdmin(event)">
                <input type="hidden" name="school_id" id="targetSchIdInput" />
                <div class="modal-body p-4">
                    <div class="mb-3"><label class="form-label font-weight-bold">Nama Penuh <span class="text-danger">*</span></label><input type="text" class="form-control" name="nama_penuh" required /></div>
                    <div class="mb-3"><label class="form-label font-weight-bold">Username <span class="text-danger">*</span></label><input type="text" class="form-control" name="username" required /></div>
                    <div class="mb-3"><label class="form-label font-weight-bold">E-mel</label><input type="email" class="form-control" name="email" /></div>
                    <div class="mb-3"><label class="form-label font-weight-bold">Kata Laluan <span class="text-danger">*</span></label><input type="password" class="form-control" name="password" minlength="10" autocomplete="new-password" placeholder="Minimum 10 aksara" required /></div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary font-weight-bold">Cipta Akaun Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: KEMASKINI SEKOLAH -->
<div class="modal fade" id="modalEditSekolah" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-warning text-dark px-4 py-3 rounded-top-4">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-edit mr-2"></i> Kemaskini Maklumat Sekolah</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formEditSekolah" onsubmit="handleEditSchool(event)">
                <input type="hidden" name="school_id" id="editSchIdInput" />
                <div class="modal-body p-4">
                    <div class="mb-3"><label class="form-label font-weight-bold">Kod Sekolah <span class="text-danger">*</span></label><input type="text" class="form-control" name="kod_sekolah" id="editKodSekolah" required /></div>
                    <div class="mb-3"><label class="form-label font-weight-bold">Nama Sekolah <span class="text-danger">*</span></label><input type="text" class="form-control" name="nama_sekolah" id="editNamaSekolah" required /></div>
                    <div class="mb-3"><label class="form-label font-weight-bold">E-mel Sekolah</label><input type="email" class="form-control" name="email_sekolah" id="editEmailSekolah" /></div>
                    <div class="mb-3"><label class="form-label font-weight-bold">No. Telefon</label><input type="text" class="form-control" name="no_tel" id="editNoTel" /></div>
                    <div class="mb-3"><label class="form-label font-weight-bold">Alamat Sekolah</label><textarea class="form-control" name="alamat" id="editAlamat" rows="3" maxlength="1000"></textarea></div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Pelan Langganan</label>
                        <select class="form-control" name="pelan" id="editPelan">
                            <option value="Trial">Trial (Percubaan)</option>
                            <option value="Basic">Basic</option>
                            <option value="Pro">Pro</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label font-weight-bold">Tarikh Luput Langganan</label><input type="date" class="form-control" name="tarikh_luput" id="editTarikhLuput" /></div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning font-weight-bold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: TOLAK PENDAFTARAN -->
<div class="modal fade" id="modalRejectReg" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-times-circle mr-2"></i> Tolak Pendaftaran</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4">
                <p>Adakah anda pasti mahu <strong>MENOLAK</strong> pendaftaran <strong id="rejectSchName"></strong>?</p>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Sebab Penolakan (Pilihan)</label>
                    <textarea class="form-control" id="rejectReason" rows="3" placeholder="Cth: Maklumat tidak lengkap..."></textarea>
                </div>
                <input type="hidden" id="rejectSchId" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger font-weight-bold" id="btnConfirmReject"><i class="fa fa-trash mr-1"></i> Ya, Tolak &amp; Padam</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: SAHKAN BAYARAN -->
<div class="modal fade" id="modalConfirmPayment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-check-circle mr-2"></i> Sahkan Penerimaan Bayaran</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4">
                <p>Sahkan bahawa bayaran untuk sebut harga <strong id="payQuoteRef"></strong> daripada <strong id="paySchName"></strong> telah diterima?</p>
                <div class="mb-3 text-left">
                    <label class="form-label font-weight-bold" style="font-size:14px;">Tetapkan Tarikh Luput Baharu <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="payTarikhLuput" required />
                    <small class="text-muted">Sistem telah menetapkan 1 tahun dari hari ini sebagai lalai.</small>
                </div>
                <p class="text-muted text-left mb-0" style="font-size:13px;">Sekolah akan diaktifkan sebagai tenant dan tarikh luput akan dikemas kini apabila disahkan.</p>
                <input type="hidden" id="payQuoteId" />
                <input type="hidden" id="paySchId" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success font-weight-bold" id="btnConfirmPaymentAction"><i class="fa fa-check mr-1"></i> Ya, Sahkan Bayaran</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: TUKAR STATUS SEKOLAH -->
<div class="modal fade" id="modalConfirmStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px; overflow:hidden;">
            <div class="modal-body p-4 text-center">
                <div style="width:64px;height:64px;border-radius:50%;background:#fee2e2;color:#ef4444;display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 16px;"><i class="fa fa-exclamation-triangle"></i></div>
                <h5 class="font-weight-bold mb-2" style="color:#0f172a;font-size:18px;">Tukar Status Sekolah</h5>
                <p class="text-muted mb-4" style="font-size:13.5px;">Adakah anda pasti mahu menukar status akses untuk sekolah ini?</p>
                <div class="d-flex justify-content-center" style="gap: .5rem;">
                    <button type="button" class="btn btn-light px-3 py-2 font-weight-bold" style="border-radius:10px;font-size:13px;" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger px-3 py-2 font-weight-bold" style="border-radius:10px;background:#ef4444;border:none;font-size:13px;" id="btnConfirmStatusAction">Ya, Tukar Status</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/Chart.min.js"></script>
<script>
// Toast helper
var toastTimeout = null;
var saActionTabTouched = false;
var saInitialPaymentLoaded = false;
var trendChartInstance = null;
var planChartInstance = null;
function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = String(value == null ? '' : value);
    return div.innerHTML;
}
function showToast(title, message, type) {
    var toast = document.getElementById('saToast');
    var icon = document.getElementById('saToastIcon');
    document.getElementById('saToastTitle').innerText = title;
    document.getElementById('saToastMsg').innerText = message;
    var toastType = type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success');
    toast.className = 'sa-toast ' + toastType;
    icon.innerHTML = toastType === 'error'
        ? '<i class="fa fa-exclamation-triangle"></i>'
        : (toastType === 'warning' ? '<i class="fa fa-envelope-o"></i>' : '<i class="fa fa-check"></i>');
    toast.classList.add('show');
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(function() { document.getElementById('saToast').classList.remove('show'); }, 4500);
}

function hideToast() {
    document.getElementById('saToast').classList.remove('show');
}

function requestJson(url, options) {
    return fetch(url, options || {}).then(function(response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        var contentType = response.headers.get('content-type') || '';
        if (contentType.indexOf('application/json') === -1) {
            throw new Error('Respons pelayan bukan JSON. Sesi mungkin telah tamat.');
        }
        return response.json();
    });
}

function openActionPanel(panelId) {
    var tab = document.querySelector('.sa-tab[data-target="' + panelId + '"]');
    if (tab) tab.click();
    document.querySelector('.sa-action-center').scrollIntoView({behavior:'smooth',block:'start'});
}

function setCount(id, value) {
    var element = document.getElementById(id);
    if (element) element.textContent = String(value || 0);
    if (id === 'paymentCount') document.getElementById('priorityPaymentCount').textContent = String(value || 0);
}

function emptyRow(colspan, message, isError) {
    return '<tr><td colspan="' + colspan + '" class="sa-empty-state' + (isError ? ' sa-error-state' : '') + '">' +
        '<i class="fa ' + (isError ? 'fa-exclamation-triangle' : 'fa-check') + '"></i>' + escapeHtml(message) + '</td></tr>';
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sa-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            saActionTabTouched = true;
            document.querySelectorAll('.sa-tab').forEach(function(item) {
                var selected = item === tab;
                item.classList.toggle('active', selected);
                item.setAttribute('aria-selected', selected ? 'true' : 'false');
            });
            document.querySelectorAll('.sa-tab-panel').forEach(function(panel) {
                var selected = panel.id === tab.dataset.target;
                panel.hidden = !selected;
                panel.classList.toggle('active', selected);
            });
        });
    });

    var schoolSearch = document.getElementById('schoolSearch');
    if (schoolSearch) {
        schoolSearch.addEventListener('input', function() {
            var term = this.value.trim().toLocaleLowerCase('ms');
            document.querySelectorAll('#schoolsTbody tr[data-search]').forEach(function(row) {
                row.hidden = term !== '' && row.dataset.search.indexOf(term) === -1;
            });
        });
    }

    loadRealTimeStats();
    loadPendingApproval();
    loadPendingPayment();
    loadPendingUpgrade();
    loadTenantSchools();
    loadChartsData();
    window.setInterval(loadRealTimeStats, 60000);
});

function refreshActionCentre() {
    loadPendingApproval();
    loadPendingPayment();
    loadPendingUpgrade();
    loadRealTimeStats();
}

function openRegisterModal() { $('#modalDaftarSekolah').modal('show'); }
function closeModal(id) { $('#' + id).modal('hide'); }

function renderNotifications(notifications) {
    window.saNotifications = Array.isArray(notifications) ? notifications : [];
    var unread = window.saNotifications.filter(function(item) { return !item.is_read; }).length;
    var badge = document.getElementById('bellBadge');
    var bell = document.querySelector('.sa-nav-icon');
    var summary = document.getElementById('notificationSummary');
    var markAllButton = document.getElementById('markAllNotifications');

    if (unread > 0) {
        badge.textContent = unread > 99 ? '99+' : String(unread);
        badge.style.display = 'inline-flex';
        bell.classList.add('has-unread');
        bell.setAttribute('aria-label', 'Notifikasi, ' + unread + ' belum dibaca');
    } else {
        badge.style.display = 'none';
        bell.classList.remove('has-unread');
        bell.setAttribute('aria-label', 'Notifikasi, semua sudah dibaca');
    }

    summary.textContent = unread > 0 ? unread + ' belum dibaca' : 'Semua sudah dibaca';
    markAllButton.disabled = unread === 0;

    if (window.saNotifications.length === 0) {
        document.getElementById('notificationList').innerHTML =
            '<div class="sa-notification-empty"><i class="fa fa-check-circle"></i><strong>Tiada notifikasi</strong><span>Semua urusan platform terkawal.</span></div>';
        return;
    }

    var html = window.saNotifications.slice(0, 10).map(function(item) {
        var unreadClass = item.is_read ? ' is-read' : ' is-unread';
        var icon = item.type === 'expiry' ? 'fa-clock-o' : 'fa-file-text-o';
        return '<button type="button" class="sa-notification-item' + unreadClass + '" onclick="openNotification(' +
            "'" + escapeHtml(item.key) + "','" + escapeHtml(item.href) + "'" + ')">' +
            '<span class="sa-notification-icon ' + escapeHtml(item.type) + '"><i class="fa ' + icon + '"></i></span>' +
            '<span class="sa-notification-copy"><span class="sa-notification-title">' + escapeHtml(item.title) + '</span>' +
            '<span class="sa-notification-message">' + escapeHtml(item.message) + '</span>' +
            '<span class="sa-notification-time">' + escapeHtml(item.created_at) + '</span></span>' +
            '<span class="sa-read-indicator" title="' + (item.is_read ? 'Sudah dibaca' : 'Belum dibaca') + '"></span>' +
            '</button>';
    }).join('');
    document.getElementById('notificationList').innerHTML = html;
}

function openNotification(notificationKey, href) {
    var notification = (window.saNotifications || []).find(function(item) { return item.key === notificationKey; });
    if (notification && !notification.is_read) {
        notification.is_read = true;
        renderNotifications(window.saNotifications);
        var formData = new FormData();
        formData.append('action', 'mark_notification_read');
        formData.append('notification_key', notificationKey);
        requestJson('superadmin_ajax.php', { method: 'POST', body: formData }).catch(function(error) {
            console.error('Gagal menanda notifikasi:', error);
            loadRealTimeStats();
        });
    }

    if (window.jQuery) $('.sa-nav-icon').dropdown('hide');

    if (href === '#approvalPanel') {
        var approvalTab = document.querySelector('.sa-tab[data-target="approvalPanel"]');
        if (approvalTab) approvalTab.click();
        document.querySelector('.sa-action-center').scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        var target = document.querySelector(href);
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function markAllNotificationsRead(event) {
    event.preventDefault();
    event.stopPropagation();
    var button = document.getElementById('markAllNotifications');
    if (button.disabled) return;
    button.disabled = true;
    var formData = new FormData();
    formData.append('action', 'mark_all_notifications_read');
    requestJson('superadmin_ajax.php', { method: 'POST', body: formData })
        .then(function(data) {
            if (data.status !== 'success') throw new Error(data.msg || 'Gagal menanda notifikasi.');
            (window.saNotifications || []).forEach(function(item) { item.is_read = true; });
            renderNotifications(window.saNotifications);
        })
        .catch(function(error) {
            button.disabled = false;
            showToast('Notifikasi', error.message || 'Gagal mengemas kini notifikasi.', 'error');
        });
}

function loadRealTimeStats() {
    requestJson('superadmin_ajax.php?action=get_stats')
        .then(data => {
            if (data.status === 'success') {
                $('#stat-total-schools').text(data.stats.total_schools);
                $('#stat-active-schools').text(data.stats.active_schools);
                document.getElementById('stat-total-students').innerText = data.stats.total_students;
                document.getElementById('stat-total-admins').innerText = data.stats.total_admins;
                renderNotifications(data.notifications || []);
            }
        })
        .catch(err => console.error('Error fetching stats:', err));
}

// 1.5 Load Chart Data
function loadChartsData() {
    requestJson('superadmin_ajax.php?action=get_charts')
        .then(data => {
            if (data.status === 'success') {
                renderTrendChart(data.monthly_trend);
                renderPlanChart(data.plan_breakdown);
                renderExpiringAlerts(data.expiring_soon);
                var expiredList=document.getElementById('expiredSchoolsList');
                expiredList.innerHTML=(data.expired_schools||[]).length ? '<li class="list-group-item"><strong>Sudah tamat</strong></li>'+(data.expired_schools||[]).map(function(item){return '<li class="list-group-item">'+escapeHtml(item.nama_sekolah)+'<br><small>Tamat: '+escapeHtml(item.tarikh_luput_my)+'</small> <a href="superadmin_renewals.php?school_id='+encodeURIComponent(item.id)+'">Perbaharui</a></li>';}).join('') : '';

            }
        })
        .catch(err => console.error('Error fetching charts:', err));
}

function renderTrendChart(data) {
    if (typeof Chart === 'undefined') return;
    var canvas = document.getElementById('trendChart');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var labels = data.map(item => item.bulan);
    var values = data.map(item => item.jumlah);
    
    if (trendChartInstance) trendChartInstance.destroy();
    trendChartInstance = new Chart(ctx, {
        type: data.length < 2 ? 'bar' : 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sekolah Mendaftar',
                data: values,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 3,
                lineTension: 0.4,
                fill: false,
                maxBarThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: false },
            scales: {
                yAxes: [{
                    ticks: { beginAtZero: true, stepSize: 1 }
                }]
            }
        }
    });
}

function renderPlanChart(data) {
    if (typeof Chart === 'undefined') return;
    var canvas = document.getElementById('planChart');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var labels = data.map(item => item.pelan);
    var values = data.map(item => item.jumlah);
    var colors = labels.map(label => label === 'Premium' ? '#17243a' : (label === 'Basic' ? '#2563eb' : '#94a8c4'));

    if (planChartInstance) planChartInstance.destroy();
    planChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { position: 'bottom' },
            cutoutPercentage: 70
        }
    });
}

function renderExpiringAlerts(data) {
    var list = document.getElementById('expiringSoonList');
    if (data.length === 0) {
        list.innerHTML = '<li class="list-group-item text-center text-muted border-0 mt-4"><i class="fa fa-check-circle text-success fa-2x mb-2 d-block"></i>Tiada langganan aktif yang tamat dalam 30 hari akan datang.</li>';
        return;
    }
    
    var html = '';
    data.forEach(item => {
        html += '<li class="list-group-item px-0 py-3" style="border-bottom: 1px solid #f1f5f9;">' +
                    '<div class="d-flex justify-content-between align-items-center">' +
                        '<div>' +
                            '<h6 class="mb-0" style="font-size:14px; font-weight:700; color:#0f172a;">'+escapeHtml(item.nama_sekolah)+'</h6>' +
                            '<small style="color:#64748b;">'+escapeHtml(item.pelan)+' &bull; '+escapeHtml(item.kod_sekolah)+'</small>' +
                        '</div>' +
                        '<span class="badge bg-danger rounded-pill" style="font-size:11px;">'+item.tarikh_luput_my+'</span>' +
                    '</div>' +
                '</li>';
    });
    list.innerHTML = html;
}

// 0.1 Load Senarai Menunggu Kelulusan
function loadPendingApproval() {
    var tbody = document.getElementById('pendingApprovalTbody');
    requestJson('superadmin_ajax.php?action=get_pending_approval&_=' + new Date().getTime())
        .then(data => {
            if (data.status === 'success') {
                var schools = data.schools;
                setCount('approvalCount', schools.length);
                setCount('heroApprovalCount', schools.length);
                window.pendingSchools = Object.fromEntries(schools.map(school => [String(school.id), school]));
                if (schools.length === 0) {
                    tbody.innerHTML = emptyRow(8, 'Tiada pendaftaran baharu menunggu kelulusan.', false);
                    return;
                }
                var html = '';
                schools.forEach(function(sch, index) {
                    var pelanBadge = '';
                    if(sch.pelan === 'Premium') pelanBadge = '<span style="background:rgba(139, 92, 246, 0.1); color:#8b5cf6; padding:3px 8px; border-radius:6px; font-size:12px; font-weight:700;"><i class="fa fa-star mr-1"></i>Premium</span>';
                    else if(sch.pelan === 'Pro') pelanBadge = '<span style="background:rgba(79, 70, 229, 0.1); color:#4f46e5; padding:3px 8px; border-radius:6px; font-size:12px; font-weight:700;"><i class="fa fa-rocket mr-1"></i>Pro</span>';
                    else if(sch.pelan === 'Basic') pelanBadge = '<span style="background:rgba(59, 130, 246, 0.1); color:#3b82f6; padding:3px 8px; border-radius:6px; font-size:12px; font-weight:700;"><i class="fa fa-cube mr-1"></i>Basic</span>';
                    else pelanBadge = '<span style="background:rgba(245, 158, 11, 0.1); color:#d97706; padding:3px 8px; border-radius:6px; font-size:12px; font-weight:700;"><i class="fa fa-clock-o mr-1"></i>Trial</span>';
                    
                    var statusBadge = '<span class="badge-tenant-pending"><i class="fa fa-clock-o"></i> MENUNGGU KELULUSAN</span>';
                    var actionBtns = `<div class="sa-row-actions">
                                        <button class="sa-action-btn sa-action-btn-success" onclick="approveReg('${sch.id}', this)" title="Lulus dan hantar sebut harga"><span aria-hidden="true">&#10003;</span><span>Lulus</span></button>
                                        <button class="sa-action-btn sa-action-btn-danger" onclick="rejectReg('${sch.id}')" title="Tolak permohonan"><span aria-hidden="true">&#215;</span><span>Tolak</span></button>
                                      </div>`;

                    html += '<tr>' +
                        '<td>' + (index + 1) + '</td>' +
                        '<td><strong>' + escapeHtml(sch.kod_sekolah) + '</strong></td>' +
                        '<td><strong style="color: #0f172a;">' + escapeHtml(sch.nama_sekolah) + '</strong></td>' +
                        '<td><div style="font-size: 13.5px;">' + escapeHtml(sch.email_sekolah) + '</div><div style="font-size: 12px; color: #64748b;">' + escapeHtml(sch.no_tel) + '</div></td>' +
                        '<td class="text-center">' + pelanBadge + '</td>' +
                        '<td>' + sch.tarikh_daftar + '</td>' +
                        '<td class="text-center">' + statusBadge + '</td>' +
                        '<td class="text-center">' + actionBtns + '</td>' +
                    '</tr>';
                });
                
                tbody.innerHTML = html;
            } else {
                setCount('approvalCount', 0);
                setCount('heroApprovalCount', 0);
                tbody.innerHTML = emptyRow(8, data.msg || 'Permohonan tidak dapat dimuatkan.', true);
            }
        })
        .catch(err => {
            console.error('Error loadPendingApproval:', err);
            setCount('approvalCount', 0);
            setCount('heroApprovalCount', 0);
            tbody.innerHTML = emptyRow(8, 'Permohonan tidak dapat dimuatkan. Cuba muat semula.', true);
        });
}

// 0.2 Load Senarai Menunggu Bayaran
function loadPendingPayment() {
    var tbody = document.getElementById('pendingPaymentTbody');
    requestJson('superadmin_ajax.php?action=get_pending_payment&_=' + new Date().getTime())
        .then(data => {
            if (data.status === 'success') {
                var quotes = data.quotations;
                setCount('paymentCount', quotes.length);
                if (!saInitialPaymentLoaded) {
                    saInitialPaymentLoaded = true;
                    if (quotes.length > 0 && !saActionTabTouched) {
                        document.querySelector('.sa-tab[data-target="paymentPanel"]').click();
                    }
                }
                window.pendingQuotes = Object.fromEntries(quotes.map(quote => [String(quote.quotation_id), quote]));
                if (quotes.length === 0) {
                    tbody.innerHTML = emptyRow(8, 'Tiada sebut harga yang sedang menunggu bayaran.', false);
                    return;
                }
                var html = '';
                quotes.forEach(function(q, index) {
                    var pelanBadge = '';
                    if (q.jenis === 'Naik Taraf') {
                        pelanBadge = '<span style="background:rgba(139, 92, 246, 0.1); color:#8b5cf6; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700;"><i class="fa fa-arrow-up mr-1"></i>NAIK TARAF KE PRO</span><br><small class="text-muted" style="font-size:10px;">Dari: ' + escapeHtml(q.pelan) + '</small>';
                    } else {
                        var iconType = q.pelan === 'Pro' ? 'fa-rocket' : (q.pelan === 'Premium' ? 'fa-star' : 'fa-cube');
                        var color = q.pelan === 'Pro' ? '#4f46e5' : (q.pelan === 'Premium' ? '#8b5cf6' : '#3b82f6');
                        var bg = q.pelan === 'Pro' ? 'rgba(79,70,229,0.1)' : (q.pelan === 'Premium' ? 'rgba(139,92,246,0.1)' : 'rgba(59,130,246,0.1)');
                        pelanBadge = '<span style="background:'+bg+'; color:'+color+'; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700;"><i class="fa '+iconType+' mr-1"></i>' + escapeHtml(String(q.pelan).toUpperCase()) + '</span><br><small class="text-muted" style="font-size:10px;">Langganan Baharu</small>';
                    }
                    var statusBadge = '<span class="badge" style="background:rgba(249, 115, 22, 0.1); color:#ea580c; border:1px solid rgba(249, 115, 22, 0.2);"><i class="fa fa-money"></i> MENUNGGU BAYARAN</span>';
                    var actionBtns = `<div class="sa-row-actions">
                                        <button class="sa-action-btn sa-action-btn-primary" onclick="confirmPayment('${q.quotation_id}')" title="Sahkan bayaran"><span aria-hidden="true">&#10003;</span><span>Sahkan</span></button>
                                        <button class="sa-action-btn sa-action-btn-neutral" onclick="resendQuotation('${q.quotation_id}', this)" title="Hantar semula e-mel sebut harga"><span aria-hidden="true">&#8599;</span><span>Hantar semula</span></button>
                                      </div>`;
                    
                    var luputStyle = q.is_overdue ? 'color: #dc2626; font-weight: bold;' : '';

                    html += '<tr>' +
                        '<td>' + (index + 1) + '</td>' +
                        '<td><strong>' + escapeHtml(q.no_rujukan) + '</strong></td>' +
                        '<td><strong style="color: #0f172a;">' + escapeHtml(q.nama_sekolah) + '</strong><br><small class="text-muted">' + escapeHtml(q.kod_sekolah) + '</small></td>' +
                        '<td>' + pelanBadge + '</td>' +
                        '<td>' + q.tarikh_terbit_fmt + '</td>' +
                        '<td style="' + luputStyle + '">' + q.tarikh_luput_fmt + '</td>' +
                        '<td class="text-center">' + statusBadge + '</td>' +
                        '<td class="text-center">' + actionBtns + '</td>' +
                    '</tr>';
                });
                
                tbody.innerHTML = html;
            } else {
                setCount('paymentCount', 0);
                tbody.innerHTML = emptyRow(8, data.msg || 'Sebut harga tidak dapat dimuatkan.', true);
            }
        })
        .catch(err => {
            console.error('Error loadPendingPayment:', err);
            setCount('paymentCount', 0);
            tbody.innerHTML = emptyRow(8, 'Sebut harga tidak dapat dimuatkan. Cuba muat semula.', true);
        });
}

function approveReg(schId, btn) {
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    var formData = new FormData();
    formData.append('action', 'approve_registration');
    formData.append('school_id', schId);
    
    fetch('superadmin_ajax.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                showToast(data.email_sent === false ? 'Selesai dengan amaran' : 'Berjaya!', data.msg, data.email_sent === false ? 'warning' : 'success');
                loadPendingApproval();
                loadPendingPayment();
                loadRealTimeStats();
            } else {
                showToast('Ralat!', data.msg, 'error');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        });
}

function rejectReg(schId) {
    var school = (window.pendingSchools || {})[String(schId)] || {};
    document.getElementById('rejectSchId').value = schId;
    document.getElementById('rejectSchName').innerText = school.nama_sekolah || '';
    document.getElementById('rejectReason').value = '';
    $('#modalRejectReg').modal('show');
}

document.addEventListener('DOMContentLoaded', function() {
    var br = document.getElementById('btnConfirmReject');
    if (br) br.addEventListener('click', function() {
    var schId = document.getElementById('rejectSchId').value;
    var reason = document.getElementById('rejectReason').value;
    var btn = this;
    var originalText = btn.innerText;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Memproses...';
    btn.disabled = true;
    
    var formData = new FormData();
    formData.append('action', 'reject_registration');
    formData.append('school_id', schId);
    formData.append('reason', reason);
    
    fetch('superadmin_ajax.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            if(data.status === 'success') {
                showToast(data.email_sent === false ? 'Selesai dengan amaran' : 'Berjaya!', data.msg, data.email_sent === false ? 'warning' : 'success');
                $('#modalRejectReg').modal('hide');
                loadPendingApproval();
                loadRealTimeStats();
            } else {
                showToast('Ralat!', data.msg, 'error');
            }
        });
    });
});

function confirmPayment(quoteId) {
    var quote = (window.pendingQuotes || {})[String(quoteId)];
    if (!quote) return;
    document.getElementById('payQuoteId').value = quoteId;
    document.getElementById('paySchId').value = quote.school_id;
    document.getElementById('payQuoteRef').innerText = quote.no_rujukan;
    document.getElementById('paySchName').innerText = quote.nama_sekolah;
    
    // Set default expiry date to 1 year from today
    var d = new Date();
    d.setFullYear(d.getFullYear() + 1);
    var month = '' + (d.getMonth() + 1), day = '' + d.getDate(), year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    document.getElementById('payTarikhLuput').value = [year, month, day].join('-');
    
    $('#modalConfirmPayment').modal('show');
}

document.addEventListener('DOMContentLoaded', function() { 
    var b = document.getElementById('btnConfirmPaymentAction');
    if (b) b.addEventListener('click', function() {
    var quoteId = document.getElementById('payQuoteId').value;
    var schId = document.getElementById('paySchId').value;
    var tarikhLuput = document.getElementById('payTarikhLuput').value;
    
    if(!tarikhLuput) {
        showToast('Maklumat belum lengkap', 'Sila tetapkan tarikh luput baharu.', 'error');
        return;
    }
    
    var btn = this;
    var originalText = btn.innerText;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Mengesahkan...';
    btn.disabled = true;
    
    var formData = new FormData();
    formData.append('action', 'confirm_payment');
    formData.append('quotation_id', quoteId);
    formData.append('school_id', schId);
    formData.append('tarikh_luput', tarikhLuput);
    
    fetch('superadmin_ajax.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            if(data.status === 'success') {
                showToast(data.email_sent === false ? 'Selesai dengan amaran' : 'Berjaya!', data.msg, data.email_sent === false ? 'warning' : 'success');
                $('#modalConfirmPayment').modal('hide');
                loadPendingPayment();
                loadTenantSchools();
                loadRealTimeStats();
            } else {
                showToast('Ralat!', data.msg, 'error');
            }
        });
    });
});



// 0.3 Load Permintaan Naik Taraf
function loadPendingUpgrade() {
    var tbody = document.getElementById('pendingUpgradeTbody');
    if (!tbody) return;
    requestJson('superadmin_ajax.php?action=get_pending_upgrade&_=' + new Date().getTime())
        .then(data => {
            if (data.status === 'success') {
                var upgrades = data.data;
                setCount('upgradeCount', upgrades.length);
                if (upgrades.length === 0) {
                    tbody.innerHTML = emptyRow(6, 'Tiada permohonan naik taraf yang menunggu.', false);
                    return;
                }
                var html = '';
                upgrades.forEach(function(u, index) {
                    var pelanBadge = '<span style="background:rgba(59,130,246,0.1);color:#3b82f6;padding:3px 8px;border-radius:6px;font-size:12px;font-weight:700;"><i class="fa fa-cube mr-1"></i>Basic</span>';
                    var actionBtns = `<div class="sa-row-actions"><button class="sa-action-btn sa-action-btn-success" onclick="approveUpgrade('${u.id}', this)" title="Jana sebut harga naik taraf"><span aria-hidden="true">&#8593;</span><span>Jana sebut harga</span></button></div>`;
                    html += '<tr>' +
                        '<td>' + (index + 1) + '</td>' +
                        '<td><strong>' + escapeHtml(u.kod_sekolah) + '</strong></td>' +
                        '<td><strong style="color:#0f172a;">' + escapeHtml(u.nama_sekolah) + '</strong></td>' +
                        '<td>' + u.created_at + '</td>' +
                        '<td class="text-center">' + pelanBadge + '</td>' +
                        '<td class="text-center">' + actionBtns + '</td>' +
                    '</tr>';
                });
                tbody.innerHTML = html;
            } else {
                setCount('upgradeCount', 0);
                tbody.innerHTML = emptyRow(6, data.msg || 'Permintaan naik taraf tidak dapat dimuatkan.', true);
            }
        })
        .catch(err => {
            console.error('Error loadPendingUpgrade:', err);
            setCount('upgradeCount', 0);
            tbody.innerHTML = emptyRow(6, 'Permintaan naik taraf tidak dapat dimuatkan. Cuba muat semula.', true);
        });
}

function approveUpgrade(reqId, btn) {
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    btn.disabled = true;
    var fd = new FormData();
    fd.append('action', 'approve_upgrade');
    fd.append('req_id', reqId);
    fetch('superadmin_ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                showToast('Sebut Harga Dijana!', data.msg, 'success');
                loadPendingUpgrade();
                loadPendingPayment();
                loadRealTimeStats();
            } else {
                showToast('Ralat!', data.msg, 'error');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        });
}
function loadTenantSchools() {
    var tbody = document.getElementById('schoolsTbody');
    requestJson('superadmin_ajax.php?action=get_schools')
        .then(data => {
            if (data.status === 'success') {
                var schools = data.schools;
                window.tenantSchools = Object.fromEntries(schools.map(school => [String(school.id), school]));
                if (schools.length === 0) {
                    tbody.innerHTML = emptyRow(9, 'Belum ada sekolah aktif atau digantung.', false);
                    return;
                }

                var html = '';
                schools.forEach(function(sch, index) {
                    var pelLimitBadge = "<strong>" + sch.jum_pelajar + " / " + (sch.had_murid === 'unlimited' ? '&infin;' : sch.had_murid) + "</strong>";
                    if(sch.had_murid !== 'unlimited') {
                        var pct = (sch.jum_pelajar / sch.had_murid) * 100;
                        if(pct >= 90) {
                            pelLimitBadge = '<span class="badge badge-danger p-2" title="Kapasiti hampir penuh!"><i class="fa fa-warning"></i> ' + sch.jum_pelajar + ' / ' + sch.had_murid + '</span>';
                        }
                    }
                    var badge = '';
                    if (sch.is_expired) {
                        badge = '<span class="badge-tenant-tamat"><i class="fa fa-clock-o"></i> TAMAT TEMPOH</span>';
                    } else if (sch.status === 'aktif') {
                        badge = '<span class="badge-tenant-aktif"><i class="fa fa-check-circle"></i> AKTIF</span>';
                    } else if (sch.status === 'pending') {
                        badge = '<span class="badge-tenant-pending"><i class="fa fa-clock-o"></i> PENDING</span>';
                    } else {
                        badge = '<span class="badge-tenant-digantung"><i class="fa fa-ban"></i> DIGANTUNG</span>';
                    }
                    
                    var pelanBadge = '';
                    if(sch.pelan === 'Premium') {
                        pelanBadge = '<span style="background:rgba(139, 92, 246, 0.1); color:#8b5cf6; padding:3px 8px; border-radius:6px; font-size:12px; font-weight:700; text-transform:uppercase;"><i class="fa fa-star mr-1"></i>Premium</span>';
                    } else if(sch.pelan === 'Pro') {
                        pelanBadge = '<span style="background:rgba(79, 70, 229, 0.1); color:#4f46e5; padding:3px 8px; border-radius:6px; font-size:12px; font-weight:700; text-transform:uppercase;"><i class="fa fa-rocket mr-1"></i>Pro</span>';
                    } else if(sch.pelan === 'Basic') {
                        pelanBadge = '<span style="background:rgba(59, 130, 246, 0.1); color:#3b82f6; padding:3px 8px; border-radius:6px; font-size:12px; font-weight:700; text-transform:uppercase;"><i class="fa fa-cube mr-1"></i>Basic</span>';
                    } else {
                        pelanBadge = '<span style="background:rgba(245, 158, 11, 0.1); color:#d97706; padding:3px 8px; border-radius:6px; font-size:12px; font-weight:700; text-transform:uppercase;"><i class="fa fa-clock-o mr-1"></i>Trial</span>';
                    }

                    if(sch.tarikh_luput) {
                        pelanBadge += '<div style="font-size: 11px; color: #64748b; margin-top: 6px;"><i class="fa fa-calendar-times-o"></i> Tamat: ' + sch.tarikh_luput.split(' ')[0] + '</div>';
                    }

                    var schoolId = Number(sch.id);
                    var actionBtns = '';
                    if (sch.status === 'pending') {
                        actionBtns = `<div class="sa-row-actions"><button class="sa-action-btn sa-action-btn-success" onclick="approveSchool(${schoolId})">Lulus</button><button class="sa-action-btn sa-action-btn-danger" onclick="rejectSchool(${schoolId})">Tolak</button></div>`;
                    } else {
                        var statusLabel = sch.status === 'aktif' ? 'Gantung sekolah' : 'Aktifkan sekolah';
                        var primaryAction = sch.status === 'aktif' ? `<a class="sa-action-btn sa-action-btn-primary" href="superadmin_renewals.php?school_id=${schoolId}">Perbaharui</a>` : '';
                        actionBtns = `<div class="sa-row-actions sa-school-actions">${primaryAction}
                            <button type="button" class="sa-action-btn sa-action-btn-neutral" popovertarget="schoolMenu${schoolId}" data-school-menu="schoolMenu${schoolId}" aria-label="Tindakan sekolah">Urus <span aria-hidden="true">⌄</span></button>
                            <div id="schoolMenu${schoolId}" popover class="sa-school-menu" role="group" aria-label="Tindakan sekolah">
                                <button type="button" onclick="openEditSchoolModal('${schoolId}')">Edit sekolah</button>
                                <a href="superadmin_users.php?school_id=${schoolId}">Lihat pengguna</a>
                                <button type="button" onclick="impersonateSchool('${schoolId}')">Masuk sebagai sekolah</button>
                                <button type="button" class="sa-menu-status" onclick="toggleSchoolStatus('${schoolId}')">${statusLabel}</button>
                            </div></div>`;
                    }
                    var searchText = [sch.kod_sekolah, sch.nama_sekolah, sch.email_sekolah, sch.no_tel, sch.pelan, sch.status].join(' ').toLocaleLowerCase('ms');
                    html += '<tr data-search="' + escapeHtml(searchText) + '">' +
                        '<td>' + (index + 1) + '</td>' +
                        '<td><strong>' + escapeHtml(sch.kod_sekolah) + '</strong></td>' +
                        '<td><strong style="color: #0f172a;">' + escapeHtml(sch.nama_sekolah) + '</strong></td>' +
                        '<td><div style="font-size: 13.5px;">' + escapeHtml(sch.email_sekolah) + '</div><div style="font-size: 12px; color: #64748b;">' + escapeHtml(sch.no_tel) + '</div></td>' +
                        '<td class="text-center">' + pelLimitBadge + '</td>' +
                        '<td class="text-center"><strong>' + sch.jum_staf + '</strong></td>' +
                        '<td>' + pelanBadge + '</td>' +
                        '<td class="text-center">' + badge + '</td>' +
                        '<td class="text-center">' + actionBtns + '</td>' +
                    '</tr>';
                });
                
                tbody.innerHTML = html;
            }
        })
        .catch(err => {
            console.error('Error loading schools:', err);
            tbody.innerHTML = emptyRow(9, 'Direktori sekolah tidak dapat dimuatkan. Cuba muat semula.', true);
        });
}


// 3. Register New School (AJAX Form Submit)
function handleRegisterSchool(e) {
    e.preventDefault();
    var form = document.getElementById('formDaftarSekolah');
    var btn = document.getElementById('btnSubmitRegister');
    var formData = new FormData(form);
    formData.append('action', 'add_school');

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Mendaftar...';

    fetch('superadmin_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-check mr-1"></i> Daftar Sekolah Sekarang';

        if (data.status === 'success') {
            showToast('Pendaftaran Berjaya!', data.msg, 'success');
            form.reset();
            $('#modalDaftarSekolah').modal('hide');
            loadRealTimeStats();
            loadTenantSchools();
        } else {
            showToast('Pendaftaran Gagal!', data.msg, 'error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-check mr-1"></i> Daftar Sekolah Sekarang';
        showToast('Ralat Sistem!', 'Berlaku masalah sambungan AJAX.', 'error');
    });
}

// 4. Toggle Status Sekolah (AJAX + Custom Sleek Modal)
var targetToggleSchId = null;

function toggleSchoolStatus(schId) {
    targetToggleSchId = schId;
    $('#modalConfirmStatus').modal('show');
}

document.getElementById('btnConfirmStatusAction').addEventListener('click', function() {
    if (!targetToggleSchId) return;
    $('#modalConfirmStatus').modal('hide');

    var schId = targetToggleSchId;
    var formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('school_id', schId);

    fetch('superadmin_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('Status Dikemaskini', 'Status akses sekolah telah berjaya diubah.', 'success');
            loadRealTimeStats();
            loadTenantSchools();
        } else {
            showToast('Gagal', data.msg, 'error');
        }
    })
    .catch(err => showToast('Ralat', 'Gagal menghubungi pelayan AJAX.', 'error'));
});

// 5. Add Admin Modal Open & Submit (AJAX)
function openAddAdminModal(schId) {
    document.getElementById('targetSchIdInput').value = schId;
    document.getElementById('targetSchIdLabel').innerText = schId;
    $('#modalTambahAdmin').modal('show');
}

function handleAddAdmin(e) {
    e.preventDefault();
    var form = document.getElementById('formTambahAdmin');
    var formData = new FormData(form);
    formData.append('action', 'add_admin');

    fetch('superadmin_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('Akaun Dicipta', data.msg, 'success');
            form.reset();
            $('#modalTambahAdmin').modal('hide');
            loadRealTimeStats();
            loadTenantSchools();
        } else {
            showToast('Gagal Cipta Admin', data.msg, 'error');
        }
    });
}

// 6. Edit School Modal Open & Submit (AJAX)
function openEditSchoolModal(schId) {
    const school = (window.tenantSchools || {})[String(schId)];
    if (!school) return;
    document.getElementById('editSchIdInput').value = schId;
    document.getElementById('editKodSekolah').value = school.kod_sekolah;
    document.getElementById('editNamaSekolah').value = school.nama_sekolah;
    document.getElementById('editEmailSekolah').value = school.email_sekolah === '-' ? '' : school.email_sekolah;
    document.getElementById('editNoTel').value = school.no_tel === '-' ? '' : school.no_tel;
    document.getElementById('editAlamat').value = school.alamat || '';
    document.getElementById('editPelan').value = school.pelan || 'Trial';
    document.getElementById('editTarikhLuput').value = school.tarikh_luput_iso || '';
    $('#modalEditSekolah').modal('show');
}

function impersonateSchool(schId) {
    const fd = new FormData();
    fd.append('school_id', schId);
    fetch('impersonate.php', {method: 'POST', body: fd})
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') window.location.href = data.redirect;
            else showToast('Akses Gagal', data.msg, 'error');
        });
}

function handleEditSchool(e) {
    e.preventDefault();
    var form = document.getElementById('formEditSekolah');
    var formData = new FormData(form);
    formData.append('action', 'edit_school');

    fetch('superadmin_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showToast('Sekolah Dikemaskini', data.msg, 'success');
            $('#modalEditSekolah').modal('hide');
            loadRealTimeStats();
            loadTenantSchools();
        } else {
            showToast('Gagal Mengemaskini', data.msg, 'error');
        }
    });
}

function resendQuotation(quoteId, btn) {
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    var fd = new FormData();
    fd.append('action', 'resend_quotation');
    fd.append('quote_id', quoteId);
    
    fetch('superadmin_ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Berjaya', data.msg, 'success');
            } else {
                showToast('Ralat', data.msg, 'error');
            }
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        })
        .catch(err => {
            showToast('Ralat Sistem', 'Sila semak konsol untuk maklumat lanjut.', 'error');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
}

</script>
<script>
document.addEventListener('click',function(event){
    var trigger=event.target.closest('[data-school-menu]');
    if(trigger){
        var menu=document.getElementById(trigger.dataset.schoolMenu);if(!menu)return;
        var rect=trigger.getBoundingClientRect(),width=Math.min(210,window.innerWidth-24);
        menu.style.width=width+'px';menu.style.left=Math.max(12,Math.min(rect.right-width,window.innerWidth-width-12))+'px';
        menu.style.top=(rect.bottom+198>window.innerHeight?Math.max(12,rect.top-190):rect.bottom+7)+'px';
    }else{
        var action=event.target.closest('.sa-school-menu button,.sa-school-menu a');
        if(action){var menu=action.closest('.sa-school-menu');if(menu.hidePopover)menu.hidePopover();}
    }
});
window.addEventListener('resize',function(){document.querySelectorAll('.sa-school-menu').forEach(function(menu){if(menu.hidePopover)menu.hidePopover();});});
</script></body>
</html>