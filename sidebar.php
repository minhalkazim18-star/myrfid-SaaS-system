<?php
// Ambil data terkini pengguna dari database sekiranya sesi aktif
$sb_user_name  = $_SESSION['nama_penuh'] ?? 'Pengguna';
$sb_user_email = $_SESSION['email'] ?? '';

if (isset($_SESSION['user_id']) && isset($conn)) {
    $sb_uid = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    $sb_query = mysqli_query($conn, "SELECT nama_penuh, email, role FROM users WHERE id = '$sb_uid' LIMIT 1");
    if ($sb_query && mysqli_num_rows($sb_query) > 0) {
        $sb_row = mysqli_fetch_assoc($sb_query);
        if (!empty($sb_row['nama_penuh'])) {
            $sb_user_name = $sb_row['nama_penuh'];
            $_SESSION['nama_penuh'] = $sb_row['nama_penuh'];
        }
        if (!empty($sb_row['email'])) {
            $sb_user_email = $sb_row['email'];
            $_SESSION['email'] = $sb_row['email'];
        } elseif (!empty($sb_row['role'])) {
            $sb_user_email = strtoupper($sb_row['role']);
        }
    }
}
if (empty($sb_user_email)) {
    $sb_user_email = 'Online';
}

$sb_sch_id = (int)($_SESSION['school_id'] ?? 1);
$sb_nama_sekolah = $_SESSION['nama_sekolah'] ?? ($h_nama_sekolah ?? "Sekolah Kebangsaan Latihan Harian");
$sb_logo_sekolah = $_SESSION['logo_sekolah'] ?? ($h_logo_sekolah ?? "images/default_logo.png");

if (isset($conn) && $sb_sch_id > 0) {
    $res_sch_sb = mysqli_query($conn, "SELECT nama_sekolah, logo FROM sekolah WHERE id = '$sb_sch_id' LIMIT 1");
    if ($res_sch_sb && mysqli_num_rows($res_sch_sb) > 0) {
        $row_sch_sb = mysqli_fetch_assoc($res_sch_sb);
        if (!empty($row_sch_sb['nama_sekolah'])) $sb_nama_sekolah = $row_sch_sb['nama_sekolah'];
        if (!empty($row_sch_sb['logo'])) $sb_logo_sekolah = $row_sch_sb['logo'];
    }
    $res_cfg_sb = mysqli_query($conn, "SELECT kunci, nilai FROM tetapan WHERE school_id = '$sb_sch_id' AND kunci IN ('nama_sekolah', 'logo_sekolah')");
    if ($res_cfg_sb) {
        while ($cfg_row_sb = mysqli_fetch_assoc($res_cfg_sb)) {
            if ($cfg_row_sb['kunci'] === 'nama_sekolah' && !empty($cfg_row_sb['nilai'])) {
                $sb_nama_sekolah = $cfg_row_sb['nilai'];
            }
            if ($cfg_row_sb['kunci'] === 'logo_sekolah' && !empty($cfg_row_sb['nilai'])) {
                $sb_logo_sekolah = $cfg_row_sb['nilai'];
            }
        }
    }
}
if ($sb_logo_sekolah === 'images/logosklh.png' || $sb_logo_sekolah === 'images/default_logo.png' || !file_exists($sb_logo_sekolah) || filesize($sb_logo_sekolah) < 200) {
    $sb_logo_sekolah = 'images/DRS_Logo_White.png';
}

$sb_current_page = basename($_SERVER['PHP_SELF']);
if (empty($sb_current_page) || $sb_current_page === 'index.php') {
    $sb_current_page = 'dashboard.php';
}
$is_pengurusan = in_array($sb_current_page, ['pelajar.php', 'guru.php', 'kelas.php']);
$is_tetapan = in_array($sb_current_page, ['profil_saya.php', 'tetapan_sistem.php']);
?>
<style>
/* ==========================================================================
   MODERN DARK NAVY SIDEBAR (#15283c) - GLITCH-FREE & LIVE DB PROFILE
   ========================================================================== */

/* 0. LAYOUT SYNC (PREVENT OVERLAP WITH CONTENT & TOPBAR) */
#sidebar:not(.active) {
    width: 275px !important;
    min-width: 275px !important;
    max-width: 275px !important;
}
#sidebar:not(.active) ~ #content .topbar,
.topbar {
    padding-left: 275px !important;
    transition: padding-left 0.28s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
}
#sidebar:not(.active) ~ #content,
#content {
    padding-left: 300px !important;
    transition: padding-left 0.28s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
}

/* 1. MAIN SIDEBAR CONTAINER */
#sidebar {
    background: #15283c !important;
    background-image: none !important;
    color: #ffffff !important;
    transition: width 0.28s cubic-bezier(0.25, 0.8, 0.25, 1),
                min-width 0.28s cubic-bezier(0.25, 0.8, 0.25, 1),
                max-width 0.28s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
    position: fixed !important;
    z-index: 1050;
    top: 0;
    left: 0;
    height: 100vh;
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.25) !important;
    overflow-x: hidden !important;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Custom Scrollbar */
#sidebar::-webkit-scrollbar {
    width: 5px;
}
#sidebar::-webkit-scrollbar-track {
    background: transparent;
}
#sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
}
#sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.35);
}

/* 2. BRAND HEADER SECTION */
.sidebar-modern-header {
    padding: 20px 18px 16px 18px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    gap: 12px;
    height: 82px;
    text-decoration: none !important;
}
.sidebar-modern-header:hover {
    text-decoration: none !important;
}
.sidebar-modern-header .logo-wrapper {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 2px solid rgba(255, 255, 255, 0.2);
    padding: 2px;
    background: #15283c;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}
.sidebar-modern-header .logo-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}
.sidebar-modern-header .brand-info {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    white-space: nowrap !important;
}
.sidebar-modern-header .brand-main-title {
    font-size: 19px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 0.5px;
    line-height: 1.15;
}
.sidebar-modern-header .brand-sub-title {
    font-size: 9.5px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.65);
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-top: 3px;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* 3. NAVIGATION SECTION LABEL */
.nav-section-label {
    padding: 16px 20px 8px 20px;
    font-size: 11px;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.5);
    letter-spacing: 1.2px;
    text-transform: uppercase;
    white-space: nowrap !important;
    overflow: hidden !important;
}

/* 4. NAVIGATION MENU */
#sidebar ul.components {
    padding: 4px 12px 20px 12px !important;
    margin: 0 !important;
    list-style: none;
    flex-grow: 1;
}
#sidebar ul.components li {
    margin-bottom: 4px;
    position: relative;
}
#sidebar ul.components li a {
    display: flex !important;
    align-items: center !important;
    padding: 11px 12px !important;
    color: rgba(255, 255, 255, 0.88) !important;
    font-size: 13.5px !important;
    font-weight: 500 !important;
    border-radius: 10px;
    text-decoration: none !important;
    transition: background 0.2s ease, color 0.2s ease;
    gap: 10px;
    height: 44px;
}
#sidebar ul.components li a:hover {
    background: rgba(255, 255, 255, 0.08) !important;
    color: #ffffff !important;
}
#sidebar ul.components li.active:not(.sidebar-dropdown) > a {
    background: #2563eb !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
}
#sidebar ul.components li.sidebar-dropdown.active > a {
    background: rgba(255, 255, 255, 0.08) !important;
    color: #ffffff !important;
    font-weight: 600 !important;
}

.nav-icon {
    width: 22px !important;
    font-size: 18px !important;
    text-align: center !important;
    flex-shrink: 0 !important;
    transition: transform 0.2s ease;
}
#sidebar ul.components li a:hover .nav-icon {
    transform: scale(1.1);
}
.nav-label {
    flex: 1 1 auto;
    min-width: 0;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis;
    line-height: 1.35;
}

/* Subtle Icon Colors when not active */
#sidebar ul.components li:not(.active) a .icon-dash { color: #fbbf24; }
#sidebar ul.components li:not(.active) a .icon-kiosk { color: #22d3ee; }
#sidebar ul.components li:not(.active) a .icon-data { color: #38bdf8; }
#sidebar ul.components li:not(.active) a .icon-report { color: #34d399; }
#sidebar ul.components li:not(.active) a .icon-audit { color: #a78bfa; }
#sidebar ul.components li:not(.active) a .icon-settings { color: #fb7185; }

/* Active Icon Color */
#sidebar ul.components li.active > a .nav-icon {
    color: #ffffff !important;
}

.nav-action-icon {
    flex: 0 0 auto;
    margin-left: auto;
    color: rgba(255, 255, 255, 0.45);
    font-size: 10px !important;
}

/* Chevron Dropdown Arrow */
.nav-chevron {
    font-size: 11px !important;
    color: rgba(255, 255, 255, 0.6);
    transition: transform 0.3s ease;
    flex-shrink: 0;
    margin-left: auto;
}
#sidebar ul.components li.active > a .nav-chevron {
    color: #ffffff;
}
#sidebar ul.components li a.sidebar-submenu-toggle[aria-expanded="true"] .nav-chevron {
    transform: rotate(180deg);
}
#sidebar ul.components li a.sidebar-submenu-toggle::after {
    display: none !important;
}

/* 5. SUBMENU STYLING (SMOOTH & GLITCH-FREE) */
#sidebar ul.components ul,
#sidebar ul.components ul.collapse,
#sidebar ul.components ul.collapsing,
#sidebar ul.components ul.show {
    background: #0d1b2a !important;
    border-radius: 10px !important;
    margin: 4px 6px 6px 14px !important;
    padding: 6px 0 !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
}
#sidebar ul.components ul li {
    margin-bottom: 2px !important;
}
#sidebar ul.components ul li a,
#sidebar ul.components ul.collapse li a {
    padding: 9px 14px 9px 20px !important;
    font-size: 13.5px !important;
    color: rgba(255, 255, 255, 0.78) !important;
    border-radius: 8px !important;
    margin: 0 6px !important;
    height: 38px !important;
}
#sidebar ul.components ul li a:hover,
#sidebar ul.components ul.collapse li a:hover {
    background: rgba(255, 255, 255, 0.08) !important;
    color: #ffffff !important;
    padding-left: 20px !important;
}
#sidebar ul.components ul li.active a,
#sidebar ul.components ul.collapse li.active a,
#sidebar ul.components ul.show li.active a {
    color: #ffffff !important;
    background: #2563eb !important;
    font-weight: 600 !important;
    box-shadow: 0 3px 10px rgba(37, 99, 235, 0.3);
}

/* 6. BOTTOM USER PROFILE CARD */
.sidebar-modern-footer {
    margin-top: auto;
    padding: 14px 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    background: #15283c;
}
.sidebar-user-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    padding: 10px 12px;
    transition: all 0.2s ease;
}
.sidebar-user-card:hover {
    background: rgba(255, 255, 255, 0.09);
}
.user-avatar-circle {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 2px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    background: #214162;
    display: flex;
    align-items: center;
    justify-content: center;
}
.user-avatar-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.user-info-text {
    overflow: hidden;
    flex-grow: 1;
    white-space: nowrap !important;
}
.user-fullname {
    font-size: 13.5px;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}
.user-email-text {
    font-size: 11px;
    color: #1ed085;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}

/* ==========================================================================
   COLLAPSED MINI-SIDEBAR MODE (#sidebar.active) - SMOOTH ZERO-JUMP
   ========================================================================== */
#sidebar.active {
    min-width: 80px !important;
    max-width: 80px !important;
    width: 80px !important;
}
#sidebar.active ~ #content .topbar,
.topbar.active,
body:has(#sidebar.active) .topbar {
    padding-left: 80px !important;
}
#sidebar.active ~ #content,
#content.active {
    padding-left: 105px !important;
}
#sidebar.active .brand-info,
#sidebar.active .nav-section-label,
#sidebar.active .nav-label,
#sidebar.active .nav-chevron,
#sidebar.active .nav-action-icon,
#sidebar.active .user-info-text,
#sidebar.active ul.collapse {
    display: none !important;
}
#sidebar.active .sidebar-modern-header {
    justify-content: center !important;
    padding: 20px 0 16px 0 !important;
}
#sidebar.active ul.components {
    padding: 10px 8px !important;
}
#sidebar.active ul.components li a {
    justify-content: center !important;
    padding: 11px 0 !important;
}
#sidebar.active ul.components li a .nav-icon {
    margin: 0 !important;
    font-size: 20px !important;
}
#sidebar.active .sidebar-modern-footer {
    padding: 14px 8px !important;
}
#sidebar.active .sidebar-user-card {
    justify-content: center !important;
    padding: 10px 0 !important;
    border: none !important;
    background: transparent !important;
}
</style>

<nav id="sidebar">
    <!-- 1. Top Brand Header -->
    <a href="dashboard.php" class="sidebar-modern-header">
        <div class="logo-wrapper">
            <img src="<?php echo htmlspecialchars($sb_logo_sekolah); ?>" alt="Logo Sekolah" />
        </div>
        <div class="brand-info">
            <span class="brand-main-title">DRS</span>
            <span class="brand-sub-title" title="<?php echo htmlspecialchars($sb_nama_sekolah); ?>"><?php echo htmlspecialchars($sb_nama_sekolah); ?></span>
        </div>
    </a>

    <!-- 2. Section Label -->
    <div class="nav-section-label">MENU UTAMA</div>

    <!-- 3. Navigation Links -->
    <ul class="list-unstyled components">
        <li class="<?php echo ($sb_current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="fa fa-dashboard nav-icon icon-dash"></i>
                <span class="nav-label">Dashboard</span>
            </a>
        </li>

        <li class="<?php echo ($sb_current_page == 'kiosk.php') ? 'active' : ''; ?>">
            <a href="kiosk.php" target="_blank" rel="noopener" title="Buka terminal imbasan dalam tab baharu">
                <i class="fa fa-id-card-o nav-icon icon-kiosk"></i>
                <span class="nav-label">Terminal Imbasan</span>
                <i class="fa fa-external-link nav-action-icon" aria-hidden="true"></i>
            </a>
        </li>

        <!-- Pengurusan Data Dropdown -->
        <li class="sidebar-dropdown <?php echo $is_pengurusan ? 'active' : ''; ?>">
            <a href="#pengurusanDataSubmenu" data-toggle="collapse" role="button"
               aria-controls="pengurusanDataSubmenu" aria-expanded="<?php echo $is_pengurusan ? 'true' : 'false'; ?>"
               class="sidebar-submenu-toggle<?php echo $is_pengurusan ? '' : ' collapsed'; ?>" title="Pengurusan Data">
                <i class="fa fa-database nav-icon icon-data"></i>
                <span class="nav-label">Pengurusan Data</span>
                <i class="fa fa-chevron-down nav-chevron"></i>
            </a>
            <ul class="collapse list-unstyled <?php echo $is_pengurusan ? 'show' : ''; ?>" id="pengurusanDataSubmenu">
                <li class="<?php echo ($sb_current_page == 'pelajar.php') ? 'active' : ''; ?>">
                    <a href="pelajar.php" style="<?php echo ($sb_current_page == 'pelajar.php') ? 'color: #fff; font-weight: bold;' : ''; ?>">
                        <i class="fa fa-users nav-icon"></i>
                        <span class="nav-label">Pelajar</span>
                    </a>
                </li>
                <li class="<?php echo ($sb_current_page == 'guru.php') ? 'active' : ''; ?>">
                    <a href="guru.php" style="<?php echo ($sb_current_page == 'guru.php') ? 'color: #fff; font-weight: bold;' : ''; ?>">
                        <i class="fa fa-briefcase nav-icon"></i>
                        <span class="nav-label">Guru</span>
                    </a>
                </li>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin') { ?>
                <li class="<?php echo ($sb_current_page == 'kelas.php') ? 'active' : ''; ?>">
                    <a href="kelas.php" style="<?php echo ($sb_current_page == 'kelas.php') ? 'color: #fff; font-weight: bold;' : ''; ?>">
                        <i class="fa fa-building nav-icon"></i>
                        <span class="nav-label">Kelas</span>
                    </a>
                </li>
                <?php } ?>
            </ul>
        </li>

        <li class="<?php echo ($sb_current_page == 'laporan.php') ? 'active' : ''; ?>">
            <a href="laporan.php">
                <i class="fa fa-bar-chart nav-icon icon-report"></i>
                <span class="nav-label">Laporan</span>
            </a>
        </li>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin') { ?>
        <li class="<?php echo ($sb_current_page == 'log_aktiviti.php') ? 'active' : ''; ?>">
            <a href="log_aktiviti.php">
                <i class="fa fa-history nav-icon icon-audit"></i>
                <span class="nav-label">Audit Log</span>
            </a>
        </li>
        <?php } ?>

        <!-- Tetapan Dropdown -->
        <li class="sidebar-dropdown <?php echo $is_tetapan ? 'active' : ''; ?>">
            <a href="#tetapanSubmenu" data-toggle="collapse" role="button"
               aria-controls="tetapanSubmenu" aria-expanded="<?php echo $is_tetapan ? 'true' : 'false'; ?>"
               class="sidebar-submenu-toggle<?php echo $is_tetapan ? '' : ' collapsed'; ?>" title="Tetapan">
                <i class="fa fa-cog nav-icon icon-settings"></i>
                <span class="nav-label">Tetapan</span>
                <i class="fa fa-chevron-down nav-chevron"></i>
            </a>
            <ul class="collapse list-unstyled <?php echo $is_tetapan ? 'show' : ''; ?>" id="tetapanSubmenu">
                <li class="<?php echo ($sb_current_page == 'profil_saya.php') ? 'active' : ''; ?>">
                    <a href="profil_saya.php" style="<?php echo ($sb_current_page == 'profil_saya.php') ? 'color: #fff; font-weight: bold;' : ''; ?>">
                        <i class="fa fa-user-circle nav-icon"></i>
                        <span class="nav-label">Profil</span>
                    </a>
                </li>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin') { ?>
                <li class="<?php echo ($sb_current_page == 'tetapan_sistem.php') ? 'active' : ''; ?>">
                    <a href="tetapan_sistem.php" style="<?php echo ($sb_current_page == 'tetapan_sistem.php') ? 'color: #fff; font-weight: bold;' : ''; ?>">
                        <i class="fa fa-server nav-icon"></i>
                        <span class="nav-label">Tetapan Sistem</span>
                    </a>
                </li>
                <?php } ?>
            </ul>
        </li>
    </ul>

    <!-- 4. Bottom User Profile Card -->
    <div class="sidebar-modern-footer">
        <div class="sidebar-user-card">
            <div class="user-avatar-circle">
                <img src="images/users.png" alt="Avatar" />
            </div>
            <div class="user-info-text">
                <h6 class="user-fullname" title="<?php echo htmlspecialchars($sb_user_name); ?>">
                    <?php echo htmlspecialchars($sb_user_name); ?>
                </h6>
                <p class="user-email-text" title="<?php echo htmlspecialchars($sb_user_email); ?>">
                    <?php echo htmlspecialchars($sb_user_email); ?>
                </p>
            </div>
        </div>
    </div>
</nav>
