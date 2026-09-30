<?php
require_once __DIR__ . '/security.php';
app_start_session();
require_once __DIR__ . '/db_connect.php';
require_active_roles($conn, ['superadmin'], false);
header('Cache-Control: no-store');

function users_param(string $key, string $default = ''): string {
    return isset($_GET[$key]) && is_string($_GET[$key]) ? trim($_GET[$key]) : $default;
}
function users_query(mysqli $conn, string $sql, string $types = '', array $params = []) {
    $stmt = $conn->prepare($sql);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}
function users_school(array $row): string {
    if (!empty($row['nama_sekolah'])) return (string)$row['nama_sekolah'];
    return empty($row['school_id']) ? 'Tiada sekolah / akaun pusat' : 'Sekolah #' . (int)$row['school_id'] . ' (maklumat tiada)';
}
function users_school_status(array $row): string {
    if (empty($row['school_id'])) return 'Tidak berkenaan';
    if (empty($row['nama_sekolah'])) return 'Maklumat sekolah tiada';
    $status = (string)($row['school_status'] ?? '');
    if ($status === 'aktif' && !empty($row['tarikh_luput']) && substr($row['tarikh_luput'], 0, 10) < date('Y-m-d')) return 'Langganan tamat';
    return ['aktif'=>'Aktif','pending'=>'Menunggu kelulusan','digantung'=>'Digantung','ditolak'=>'Ditolak'][$status] ?? ($status !== '' ? ucfirst($status) : 'Tidak tersedia');
}
function users_page_url(int $page): string {
    global $search, $schoolFilter, $roleFilter, $pageSize;
    return 'superadmin_users.php?' . http_build_query(['q'=>$search,'school_id'=>$schoolFilter,'role'=>$roleFilter,'size'=>$pageSize,'page'=>$page]);
}
function users_snapshot(array $row): string {
    $values = [];
    foreach (['id','nama_penuh','username','email','no_tel','jawatan'] as $key) $values[$key] = $row[$key] ?? null;
    return hash_hmac('sha256', json_encode($values, JSON_INVALID_UTF8_SUBSTITUTE), csrf_token());
}
function users_delete_snapshot(array $row): string {
    $values = [];
    foreach (['id','school_id','role','nama_penuh','username','email'] as $key) $values[$key] = isset($row[$key]) ? (string)$row[$key] : null;
    return hash_hmac('sha256', 'delete-user:' . json_encode($values, JSON_INVALID_UTF8_SUBSTITUTE), csrf_token());
}
function users_sql_identifier(string $value): string {
    return '`' . str_replace('`', '``', $value) . '`';
}
function users_delete_dependencies(mysqli $conn, array $user): array {
    $blocked = [];
    // Inspect actual foreign keys, including CASCADE / SET NULL: do not silently
    // destroy or rewrite related business records. Password tokens are handled below.
    $relations = $conn->query("SELECT TABLE_SCHEMA,TABLE_NAME,COLUMN_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME='users'")->fetch_all(MYSQLI_ASSOC);
    $db = (string)$conn->query('SELECT DATABASE() AS db')->fetch_assoc()['db'];
    foreach ($relations as $ref) {
        if ($ref['TABLE_SCHEMA'] === $db && $ref['TABLE_NAME'] === 'password_resets' && $ref['COLUMN_NAME'] === 'email' && $ref['REFERENCED_COLUMN_NAME'] === 'email') continue;
        $key = $ref['REFERENCED_COLUMN_NAME'];
        if (!array_key_exists($key, $user)) throw new RuntimeException('Unknown referenced user column');
        if ($user[$key] === null) continue;
        $table = users_sql_identifier($ref['TABLE_SCHEMA']) . '.' . users_sql_identifier($ref['TABLE_NAME']);
        $column = users_sql_identifier($ref['COLUMN_NAME']);
        if (users_query($conn, "SELECT 1 FROM $table WHERE $column = ? LIMIT 1", 's', [(string)$user[$key]])->fetch_row()) $blocked[] = $ref['TABLE_NAME'];
    }
    // Older installations have relationships without declared foreign keys.
    // Audit tables retain their original actor ID and name; they are never deleted.
    $refs = $conn->query("SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND COLUMN_NAME IN ('user_id','admin_id','created_by','submitted_by','confirmed_by','guru_id') AND TABLE_NAME NOT IN ('users','activity_logs','sa_audit_logs')")->fetch_all(MYSQLI_ASSOC);
    foreach ($refs as $ref) {
        $table=users_sql_identifier($ref['TABLE_NAME']); $column=users_sql_identifier($ref['COLUMN_NAME']);
        if (users_query($conn,"SELECT 1 FROM $table WHERE $column = ? LIMIT 1",'i',[(int)$user['id']])->fetch_row()) $blocked[]=$ref['TABLE_NAME'];
    }
    return array_values(array_unique($blocked));
}
$deleteError = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
    require_csrf(false);
    $deleteTransaction = false;
    try {
        $rawId = $_POST['id'] ?? '';
        if (!is_string($rawId) || !ctype_digit($rawId) || (int)$rawId < 1) throw new InvalidArgumentException('ID pengguna tidak sah.');
        $deleteId = (int)$rawId;
        if ($deleteId === (int)$_SESSION['user_id']) throw new InvalidArgumentException('Akaun yang sedang digunakan tidak boleh dipadam.');
        $confirm = is_string($_POST['confirm_delete'] ?? null) ? trim($_POST['confirm_delete']) : '';
        if ($confirm !== 'PADAM ' . $deleteId) throw new InvalidArgumentException('Pengesahan tidak sepadan. Taip PADAM diikuti ID pengguna.');
        $signature = is_string($_POST['delete_snapshot'] ?? null) ? $_POST['delete_snapshot'] : '';
        $allowTestAdminDelete = ($_POST['allow_test_admin_delete'] ?? '') === 'yes';
        $usedTestAdminDelete = false;
        $conn->begin_transaction(); $deleteTransaction = true;
        // Lock admin rows in a consistent order so concurrent deletions cannot
        // remove both remaining admins of one school.
        $admins = $conn->query("SELECT id,school_id FROM users WHERE role='admin' ORDER BY id FOR UPDATE")->fetch_all(MYSQLI_ASSOC);
        $target = users_query($conn,'SELECT * FROM users WHERE id=? FOR UPDATE','i',[$deleteId])->fetch_assoc();
        if (!$target) throw new InvalidArgumentException('Pengguna ini sudah tiada. Muat semula senarai.');
        if ($target['role'] === 'superadmin') throw new InvalidArgumentException('Akaun SuperAdmin dilindungi dan tidak boleh dipadam melalui halaman ini.');
        if (!hash_equals(users_delete_snapshot($target), $signature)) throw new InvalidArgumentException('Maklumat pengguna telah berubah. Muat semula halaman sebelum cuba lagi.');
        if ($target['role'] === 'admin' && (int)$target['school_id'] > 0) {
            $remaining = array_filter($admins, static fn(array $a): bool => (int)$a['school_id'] === (int)$target['school_id'] && (int)$a['id'] !== $deleteId);
            if (!$remaining) {
                $schoolExists = users_query($conn,'SELECT id FROM sekolah WHERE id=?','i',[(int)$target['school_id']])->fetch_assoc();
                if ($schoolExists && !$allowTestAdminDelete) throw new InvalidArgumentException('Ini pentadbir terakhir sekolah. Jika akaun ini data ujian, tandakan pengesahan akaun ujian dalam borang Padam.');
                $usedTestAdminDelete = (bool)$schoolExists;
            }
        }
        $dependencies = users_delete_dependencies($conn,$target);
        if ($dependencies) throw new InvalidArgumentException('Pengguna masih dirujuk oleh rekod dalam: ' . implode(', ', $dependencies) . '. Pemadaman dihentikan supaya rekod berkaitan tidak terjejas.');
        if (!empty($target['email'])) {
            $sameEmail = users_query($conn,'SELECT id FROM users WHERE email=? AND id<>? LIMIT 1','si',[$target['email'],$deleteId])->fetch_assoc();
            if ($sameEmail) throw new InvalidArgumentException('E-mel pengguna dikongsi oleh akaun lain. Semak akaun pendua dahulu.');
            $hasResetTable = $conn->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='password_resets'")->fetch_row();
            if ($hasResetTable) users_query($conn,'DELETE FROM password_resets WHERE email=?','s',[$target['email']]);
        }
        // Audit and deletion succeed together, or the transaction is rolled back.
        record_superadmin_audit($conn,'PADAM_PENGGUNA','ID: ' . $deleteId . '; nama: ' . (string)$target['nama_penuh'] . '; username: ' . (string)$target['username'] . '; sekolah: ' . (string)$target['school_id'] . ($usedTestAdminDelete ? '; pengesahan data ujian: padam pentadbir terakhir' : ''));
        $stmt=$conn->prepare('DELETE FROM users WHERE id=?');
        $stmt->bind_param('i',$deleteId);$stmt->execute();$deleted=$stmt->affected_rows;$stmt->close();
        if ($deleted !== 1) throw new RuntimeException('Unexpected deleted user count');
        $conn->commit(); $deleteTransaction=false;
        $_SESSION['users_saved_message']='Pengguna berjaya dipadam. Log aktiviti lama dikekalkan.';
        $query=http_build_query(array_intersect_key($_GET,array_flip(['q','school_id','role','size','page'])));
        header('Location: superadmin_users.php' . ($query!==''?'?'.$query:''),true,303);exit;
    } catch (Throwable $e) {
        if ($deleteTransaction) $conn->rollback();
        if ($e instanceof InvalidArgumentException) $deleteError=$e->getMessage();
        elseif ((int)$e->getCode()===1451) $deleteError='Database menghalang pemadaman kerana masih ada rekod berkaitan. Tiada perubahan disimpan.';
        else { error_log('SuperAdmin delete user: '.$e->getMessage());$deleteError='Pengguna gagal dipadam. Semak PHP error log atau hubungi pengurusan hosting.'; }
    }
}
$editError = ''; $failedEdit = null;
$successMessage = (string)($_SESSION['users_saved_message'] ?? '');
unset($_SESSION['users_saved_message']);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? 'edit_user') === 'edit_user') {
    require_csrf(false);
    $input = [];
    foreach (['id','nama_penuh','username','email','no_tel','jawatan','snapshot'] as $key) {
        $input[$key] = isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
    }
    $failedEdit = $input;
    $transaction = false;
    try {
        if (!ctype_digit($input['id']) || (int)$input['id'] < 1) throw new InvalidArgumentException('Pengguna tidak sah.');
        foreach (['nama_penuh'=>100,'username'=>50,'email'=>100,'no_tel'=>15,'jawatan'=>50] as $key=>$limit) {
            if (mb_strlen($input[$key]) > $limit) throw new InvalidArgumentException('Medan ' . $key . ' melebihi had ' . $limit . ' aksara.');
        }
        if ($input['nama_penuh'] === '' || $input['username'] === '' || $input['email'] === '') throw new InvalidArgumentException('Nama, username dan e-mel wajib diisi.');
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Alamat e-mel tidak sah.');
        if (preg_match('/[\s\x00-\x1F\x7F]/u', $input['username'])) throw new InvalidArgumentException('Username tidak boleh mengandungi ruang atau aksara kawalan.');
        $id = (int)$input['id'];
        $conn->begin_transaction(); $transaction = true;
        $old = users_query($conn, 'SELECT id,nama_penuh,username,email,no_tel,jawatan FROM users WHERE id = ? FOR UPDATE', 'i', [$id])->fetch_assoc();
        if (!$old) throw new InvalidArgumentException('Pengguna ini tidak lagi wujud.');
        if (!hash_equals(users_snapshot($old), $input['snapshot'])) throw new InvalidArgumentException('Rekod telah berubah. Tutup borang dan muat semula halaman sebelum edit semula.');
        $duplicate = users_query($conn, 'SELECT id FROM users WHERE id <> ? AND (username = ? OR email = ?) LIMIT 1', 'iss', [$id,$input['username'],$input['email']])->fetch_assoc();
        if ($duplicate) throw new InvalidArgumentException('Username atau e-mel sudah digunakan oleh pengguna lain.');
        $changed = [];
        foreach (['nama_penuh','username','email','no_tel','jawatan'] as $key) if ((string)($old[$key] ?? '') !== $input[$key]) $changed[] = $key;
        if ($changed) {
            $invalidate = in_array('email',$changed,true) || in_array('username',$changed,true) ? 1 : 0;
            users_query($conn, 'UPDATE users SET nama_penuh=?, username=?, email=?, no_tel=?, jawatan=?, auth_version=auth_version+? WHERE id=?', 'sssssii', [$input['nama_penuh'],$input['username'],$input['email'],$input['no_tel'],$input['jawatan'],$invalidate,$id]);
            record_superadmin_audit($conn, 'Kemaskini pengguna', 'ID pengguna: ' . $id . '; medan: ' . implode(', ', $changed));
        }
        $conn->commit(); $transaction = false;
        $_SESSION['users_saved_message'] = $changed ? 'Maklumat pengguna berjaya dikemas kini.' : 'Tiada perubahan dibuat.';
        // Preserve directory filters without allowing an external redirect.
        $query = http_build_query(array_intersect_key($_GET, array_flip(['q','school_id','role','size','page'])));
        header('Location: superadmin_users.php' . ($query !== '' ? '?' . $query : ''), true, 303);
        exit;
    } catch (Throwable $e) {
        if ($transaction) $conn->rollback();
        if ($e instanceof InvalidArgumentException) $editError = $e->getMessage();
        elseif ((int)$e->getCode() === 1062) $editError = 'Username atau e-mel sudah digunakan oleh pengguna lain.';
        else { error_log('SuperAdmin edit user: ' . $e->getMessage()); $editError = 'Maklumat gagal disimpan. Cuba lagi.'; }
    }
}
$search = mb_substr(users_param('q'), 0, 150);
$schoolFilter = users_param('school_id');
if ($schoolFilter !== 'central' && !preg_match('/^[1-9][0-9]{0,9}$/', $schoolFilter)) $schoolFilter = '';
$roleFilter = mb_substr(users_param('role'), 0, 30);
$pageSize = (int)users_param('size', '25');
if (!in_array($pageSize, [25,50,100], true)) $pageSize = 25;
$page = max(1, min(1000000, (int)users_param('page', '1')));
$rows = $schools = $roles = [];
$total = 0; $pages = 1; $offset = 0; $readError = false;
try {
    $schools = $conn->query('SELECT id, nama_sekolah, kod_sekolah FROM sekolah ORDER BY nama_sekolah')->fetch_all(MYSQLI_ASSOC);
    $roles = $conn->query("SELECT DISTINCT role FROM users WHERE role <> '' ORDER BY role")->fetch_all(MYSQLI_ASSOC);
    $where = ['1=1']; $types = ''; $params = [];
    if ($search !== '') {
        $like = '%' . str_replace(['!','%','_'], ['!!','!%','!_'], $search) . '%';
        $where[] = "(u.nama_penuh LIKE ? ESCAPE '!' OR u.username LIKE ? ESCAPE '!' OR u.email LIKE ? ESCAPE '!' OR s.nama_sekolah LIKE ? ESCAPE '!' OR s.kod_sekolah LIKE ? ESCAPE '!')";
        $types .= 'sssss'; $params = array_fill(0, 5, $like);
    }
    if ($schoolFilter === 'central') { $where[] = '(u.school_id IS NULL OR u.school_id = 0)'; }
    elseif ($schoolFilter !== '') { $where[] = 'u.school_id = ?'; $types .= 'i'; $params[] = (int)$schoolFilter; }
    if ($roleFilter !== '') { $where[] = 'u.role = ?'; $types .= 's'; $params[] = $roleFilter; }
    $from = ' FROM users u LEFT JOIN sekolah s ON s.id = u.school_id WHERE ' . implode(' AND ', $where);
    $countResult = users_query($conn, 'SELECT COUNT(*) AS total' . $from, $types, $params);
    $total = (int)$countResult->fetch_assoc()['total'];
    $pages = max(1, (int)ceil($total / $pageSize)); $page = min($page, $pages); $offset = ($page - 1) * $pageSize;
    // Select only fields needed for this directory. Never select passwords.
    $sql = 'SELECT u.id, u.school_id, u.nama_penuh, u.username, u.email, u.role, u.no_tel, u.jawatan, s.nama_sekolah, s.kod_sekolah, s.status AS school_status, s.tarikh_luput' . $from . ' ORDER BY u.nama_penuh, u.id LIMIT ? OFFSET ?';
    $rows = users_query($conn, $sql, $types . 'ii', array_merge($params, [$pageSize, $offset]))->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $e) {
    $readError = true;
    error_log('SuperAdmin user directory: ' . $e->getMessage());
}
$detailRows = []; $editRows = [];
foreach ($rows as $row) {
    $editRows[(string)$row['id']] = array_intersect_key($row, array_flip(['id','nama_penuh','username','email','no_tel','jawatan']));
    $editRows[(string)$row['id']]['snapshot'] = users_snapshot($row);
    $editRows[(string)$row['id']]['delete_snapshot'] = users_delete_snapshot($row);
    $editRows[(string)$row['id']]['is_school_admin'] = $row['role'] === 'admin';
    $editRows[(string)$row['id']]['can_delete'] = $row['role'] !== 'superadmin' && (int)$row['id'] !== (int)$_SESSION['user_id'];
    $detailRows[(string)$row['id']] = [
        'ID pengguna' => (string)$row['id'], 'Nama penuh' => $row['nama_penuh'] ?: 'Tidak tersedia',
        'Username' => $row['username'] ?: 'Tidak tersedia', 'E-mel' => $row['email'] ?: 'Tidak tersedia',
        'Peranan' => $row['role'], 'Sekolah' => users_school($row),
        'Kod sekolah' => $row['kod_sekolah'] ?: 'Tidak berkenaan',
        'Status sekolah' => users_school_status($row), 'No. telefon' => $row['no_tel'] ?: 'Tidak tersedia',
        'Jawatan' => $row['jawatan'] ?: 'Tidak tersedia'
    ];
}
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


.users-filters{grid-template-columns:minmax(200px,1.5fr) minmax(170px,1fr) minmax(110px,.65fr) 100px auto auto}
.users-submit{background:#2563eb;color:white;border-color:#2563eb}.users-reset{display:inline-flex;align-items:center;justify-content:center;text-decoration:none!important}.table-tenant td:last-child{min-width:120px}.table-tenant td{overflow-wrap:anywhere}.sa-directory .sa-row-actions{flex-wrap:wrap}
#userDetails{border:1px solid #e2e7ef;border-radius:12px;width:min(560px,calc(100vw - 32px));max-height:85vh;padding:24px;color:#17243a;background:white;box-shadow:0 24px 80px #0f172a33}
#userDetails::backdrop{background:#111e3288}.users-modal-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px}.users-modal-head h2{font-size:20px;font-weight:650;margin:0}#userDetailFields{margin:0}#userDetailFields dt{font-size:11px;color:#64748b;font-weight:600;margin-top:16px}#userDetailFields dd{font-size:14px;color:#17243a;margin:4px 0 0;overflow-wrap:anywhere}
@media(max-width:1200px){.users-filters{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:760px){.users-filters{grid-template-columns:1fr 1fr}.users-filters .log-field:first-child{grid-column:1/-1}}

#userDetails {position:fixed;inset:0;margin:auto!important;box-sizing:border-box;width:min(640px,calc(100vw - 32px));max-height:calc(100dvh - 40px);overflow:auto;padding:24px;}
#userDetails:not([open]){display:none}#userDetails[open]{display:block}
#userDetailFields{display:grid;grid-template-columns:140px minmax(0,1fr);gap:12px 16px}#userDetailFields dt,#userDetailFields dd{margin:0;align-self:start}
.users-edit-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.users-edit-grid label{display:block;color:#526580;font-size:12px;font-weight:600;margin:0}.users-edit-grid input{display:block;box-sizing:border-box;width:100%;margin-top:7px;border:1px solid #d5deeb;border-radius:8px;padding:10px 12px;color:#17243a;font-size:14px;background:#fff}.users-edit-grid input:focus{outline:2px solid #246bfd;outline-offset:2px}
.users-modal-actions{display:flex;justify-content:flex-end;gap:10px;border-top:1px solid #e2e7ef;margin-top:24px;padding-top:18px}.users-save{border:0;border-radius:8px;padding:10px 16px;color:white;background:#246bfd;font-weight:600;cursor:pointer}.users-feedback{padding:12px 16px;background:#eaf8f1;color:#166443;border-radius:8px}.users-error{color:#a32131;background:#fff0f2;padding:12px;border-radius:8px}.users-help{font-size:12px;color:#64748b;margin:16px 0}
@media(max-width:560px){.users-edit-grid{grid-template-columns:1fr}#userDetails{padding:20px}#userDetailFields{grid-template-columns:110px minmax(0,1fr)}}

.users-delete{padding:10px 14px;border:1px solid #e3a7ae;border-radius:8px;color:#a32234;background:#fff3f4;font:inherit;font-size:13px;cursor:pointer}.users-delete:disabled{opacity:.5;cursor:not-allowed}.users-delete-confirm{display:block;width:100%;box-sizing:border-box;padding:12px;border:1px solid #d5deeb;border-radius:8px;margin-top:8px}#deleteUserIdentity{overflow-wrap:anywhere}#userDeleteForm[hidden]{display:none}
</style><div class="superadmin-portal-wrapper">
    <aside class="sa-sidebar">
        <a class="sa-brand" href="superadmin_dashboard.php" aria-label="Dashboard SuperAdmin">
            <span class="sa-brand-mark"><i class="fa fa-shield"></i></span>
            <span><small>DRS</small> SuperAdmin</span>
        </a>
        <nav class="sa-side-nav" aria-label="Navigasi SuperAdmin">
            <span class="sa-side-label">Workspace</span>
            <a href="superadmin_dashboard.php"><i class="fa fa-th-large"></i><span>Dashboard</span></a>
            <a class="active" href="superadmin_users.php"><i class="fa fa-users" aria-hidden="true"></i><span>Pengguna</span></a>
<a href="superadmin_logs.php"><i class="fa fa-history"></i><span>Log Aktiviti</span></a>
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
            <div class="sa-workspace-context"><span>Platform</span><i class="fa fa-angle-right"></i><strong>Pengguna</strong></div>
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
<?php if ($successMessage !== ''): ?><p class="users-feedback" role="status"><?php echo escape_html($successMessage); ?></p><?php endif; ?>
<?php if ($deleteError !== ''): ?><p class="users-error" role="alert"><?php echo escape_html($deleteError); ?></p><?php endif; ?>
<div class="sa-page-heading"><div><span class="sa-eyebrow">PENGURUSAN PLATFORM</span><h1>Pengguna</h1><p>Senarai akaun pengguna merentas semua sekolah.</p></div><span class="log-scope">Urus maklumat pengguna<br>Peranan dan status sekolah semasa</span></div>
<section class="sa-panel sa-log-panel">
<div class="sa-panel-header sa-log-header"><div><h2 class="sa-table-title">Direktori pengguna</h2><p class="log-caption"><?php echo $readError ? 'Data tidak dapat dimuatkan' : number_format($total) . ' pengguna sepadan'; ?></p></div></div>
<form method="get" action="superadmin_users.php" class="log-filters users-filters">
<label class="log-field"><span>Carian</span><input type="search" name="q" maxlength="150" value="<?php echo escape_html($search); ?>" placeholder="Nama, username, e-mel atau sekolah"></label>
<label class="log-field"><span>Sekolah</span><select name="school_id"><option value="">Semua sekolah</option><option value="central" <?php echo $schoolFilter === 'central' ? 'selected' : ''; ?>>Tiada sekolah / akaun pusat</option>
<?php foreach ($schools as $school): ?><option value="<?php echo (int)$school['id']; ?>" <?php echo $schoolFilter === (string)$school['id'] ? 'selected' : ''; ?>><?php echo escape_html($school['nama_sekolah'] . ' (' . $school['kod_sekolah'] . ')'); ?></option><?php endforeach; ?>
<?php if ($schoolFilter !== '' && $schoolFilter !== 'central' && !in_array($schoolFilter, array_map('strval', array_column($schools, 'id')), true)): ?><option selected value="<?php echo escape_html($schoolFilter); ?>">Sekolah #<?php echo escape_html($schoolFilter); ?> (maklumat tiada)</option><?php endif; ?>
</select></label>
<label class="log-field"><span>Peranan</span><select name="role"><option value="">Semua peranan</option><?php foreach ($roles as $role): ?><option value="<?php echo escape_html($role['role']); ?>" <?php echo $roleFilter === $role['role'] ? 'selected' : ''; ?>><?php echo escape_html(ucfirst($role['role'])); ?></option><?php endforeach; ?></select></label>
<label class="log-field"><span>Setiap halaman</span><select name="size"><?php foreach ([25,50,100] as $size): ?><option value="<?php echo $size; ?>" <?php echo $pageSize === $size ? 'selected' : ''; ?>><?php echo $size; ?></option><?php endforeach; ?></select></label>
<button type="submit" class="log-reset users-submit">Cari</button><a class="log-reset users-reset" href="superadmin_users.php">Reset</a>
</form>
<div class="table-responsive" tabindex="0" aria-label="Senarai pengguna; tatal ke kanan untuk melihat semua lajur">
<table class="table-tenant table table-borderless table-hover"><thead><tr><th scope="col">Pengguna</th><th scope="col">E-mel</th><th scope="col">Sekolah</th><th scope="col">Peranan</th><th scope="col">Status sekolah</th><th scope="col">Tindakan</th></tr></thead><tbody>
<?php if ($readError): ?><tr class="log-empty"><td colspan="6" role="alert"><strong>Rekod tidak dapat dimuatkan.</strong>Cuba muat semula halaman.</td></tr>
<?php elseif (!$rows): ?><tr class="log-empty"><td colspan="6"><strong>Tiada pengguna sepadan</strong>Cuba kata carian lain atau reset penapis.</td></tr>
<?php else: foreach ($rows as $row): ?>
<tr><td><span class="log-person"><?php echo escape_html($row['nama_penuh'] ?: 'Nama tidak tersedia'); ?></span><span class="log-time"><?php echo escape_html($row['username'] ?: 'Username tidak tersedia'); ?></span><?php if ((int)$row['id'] === (int)($_SESSION['user_id'] ?? 0)): ?><span class="sa-role-badge">Akaun anda</span><?php endif; ?></td>
<td><?php echo escape_html($row['email'] ?: 'Tidak tersedia'); ?></td>
<td><span class="log-school"><?php echo escape_html(users_school($row)); ?></span><span class="log-time"><?php echo escape_html($row['kod_sekolah'] ?? ''); ?></span></td>
<td><span class="sa-role-badge"><?php echo escape_html(ucfirst($row['role'])); ?></span></td>
<td><?php echo escape_html(users_school_status($row)); ?></td>
<td><button type="button" class="log-reset user-detail-button" data-user-id="<?php echo (int)$row['id']; ?>">Butiran / Edit</button></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<div class="log-pagination"><span><?php echo $readError ? 'Data tidak tersedia' : ($total ? ($offset+1) . '–' . min($offset+$pageSize,$total) : '0') . ' daripada ' . number_format($total) . ' pengguna'; ?></span><nav class="log-page-controls" aria-label="Halaman pengguna">
<?php if ($page > 1): ?><a class="log-reset users-reset" href="<?php echo escape_html(users_page_url($page-1)); ?>">Sebelumnya</a><?php endif; ?>
<span class="log-page-position">Halaman <?php echo $page; ?> / <?php echo $pages; ?></span>
<?php if ($page < $pages): ?><a class="log-reset users-reset" href="<?php echo escape_html(users_page_url($page+1)); ?>">Seterusnya</a><?php endif; ?>
</nav></div>
</section><p class="log-note">Status sekolah merujuk kepada sekolah dan langganannya, bukan status aktif individu. Carian meliputi semua akaun dalam pangkalan data.</p>
</main></div></div>
<dialog id="userDetails" aria-labelledby="detailTitle">
<div class="users-modal-head"><h2 id="detailTitle">Butiran pengguna</h2><button type="button" class="log-reset" id="closeUserDetails">Tutup</button></div>
<div id="usersView"><dl id="userDetailFields"></dl><div class="users-modal-actions"><button type="button" class="users-delete" id="startUserDelete">Padam pengguna</button><button type="button" class="users-save" id="startUserEdit">Edit maklumat</button></div></div>
<form method="post" id="userEditForm" hidden><input type="hidden" name="action" value="edit_user">
<input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
<input type="hidden" name="id"><input type="hidden" name="snapshot">
<p id="userEditError" class="users-error" role="alert" hidden></p>
<div class="users-edit-grid">
<label>Nama penuh<input type="text" name="nama_penuh" maxlength="100" required></label>
<label>Username<input type="text" name="username" maxlength="50" required></label>
<label>E-mel<input type="email" name="email" maxlength="100" required></label>
<label>No. telefon<input type="tel" name="no_tel" maxlength="15"></label>
<label>Jawatan<input type="text" name="jawatan" maxlength="50"></label>
</div><p class="users-help">Perubahan username atau e-mel akan meminta pengguna log masuk semula. Pastikan alamat e-mel betul, terutama bagi akaun yang log masuk melalui Google.</p>
<div class="users-modal-actions"><button type="button" class="log-reset" id="cancelUserEdit">Batal</button><button type="submit" class="users-save" id="saveUserEdit">Simpan perubahan</button></div>
</form>
<form method="post" id="userDeleteForm" hidden>
<input type="hidden" name="csrf_token" value="<?php echo escape_html(csrf_token()); ?>">
<input type="hidden" name="action" value="delete_user"><input type="hidden" name="id"><input type="hidden" name="delete_snapshot">
<p class="users-error">Pemadaman akaun adalah kekal. Pengguna tidak lagi boleh log masuk. Log aktiviti lama dikekalkan.</p>
<p id="deleteUserIdentity"></p>
<label for="confirmDeleteUser">Taip <strong id="deleteUserPhrase"></strong> untuk mengesahkan</label>
<input id="confirmDeleteUser" name="confirm_delete" type="text" autocomplete="off" required maxlength="40" class="users-delete-confirm">
<div id="testAdminDeleteOption" hidden style="margin-top:18px;padding:14px;border:1px solid #e4c49d;border-radius:8px;background:#fffbf3">
<label style="display:flex;align-items:flex-start;gap:10px;font-size:13px;line-height:1.6"><input type="checkbox" name="allow_test_admin_delete" value="yes" style="width:auto;flex:0 0 auto;margin-top:5px"> <span>Saya sahkan ini akaun ujian dan benarkan pemadaman walaupun pentadbir terakhir sekolah.</span></label>
<p class="users-help" style="margin-bottom:0">Rekod sekolah tidak dipadam. Jika sekolah masih wujud, ia boleh tinggal tanpa pentadbir selepas tindakan ini.</p>
</div>
<p class="users-help">Pemadaman tetap disekat jika ada rekod operasi berkaitan. Akaun sendiri dan SuperAdmin kekal dilindungi. Jika rekod sekolah sudah tiada, syarat pentadbir pengganti tidak dikenakan.</p>
<div class="users-modal-actions"><button type="button" class="log-reset" id="cancelUserDelete">Batal</button><button type="submit" class="users-delete" id="submitUserDelete" disabled>Padam secara kekal</button></div>
</form></dialog>
<script type="application/json" id="userEditData"><?php echo json_encode(['rows'=>(object)$editRows,'failed'=>$failedEdit,'error'=>$editError], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?></script>
<script type="application/json" id="userData"><?php echo json_encode((object)$detailRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?></script>
<script src="js/jquery.min.js"></script><script src="js/bootstrap.bundle.min.js"></script>
<script>var saNotifications=[];var notificationBusy=false;
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


document.addEventListener('DOMContentLoaded',function(){
    var users=JSON.parse(document.getElementById('userData').textContent);
    var dialog=document.getElementById('userDetails');
    var editData=JSON.parse(document.getElementById('userEditData').textContent);
    var form=document.getElementById('userEditForm'),view=document.getElementById('usersView'),selectedId=null;
    var deleteForm=document.getElementById('userDeleteForm'),deleteButton=document.getElementById('startUserDelete');
    var deleteConfirm=document.getElementById('confirmDeleteUser'),deleteSubmit=document.getElementById('submitUserDelete');
    deleteButton.addEventListener('click',function(){
        var data=editData.rows[selectedId];if(!data || !data.can_delete)return;
        form.hidden=true;view.hidden=true;deleteForm.hidden=false;
        deleteForm.elements.namedItem('id').value=data.id;
        deleteForm.elements.namedItem('delete_snapshot').value=data.delete_snapshot;
        deleteForm.elements.namedItem('allow_test_admin_delete').checked=false;
        document.getElementById('testAdminDeleteOption').hidden=!data.is_school_admin;
        deleteConfirm.value='';deleteSubmit.disabled=true;deleteSubmit.textContent='Padam secara kekal';
        document.getElementById('detailTitle').textContent='Padam pengguna';
        document.getElementById('deleteUserIdentity').textContent=(data.nama_penuh||data.username||'Pengguna')+' · ID '+data.id;
        document.getElementById('deleteUserPhrase').textContent='PADAM '+data.id;
        deleteConfirm.focus();
    });
    deleteConfirm.addEventListener('input',function(){deleteSubmit.disabled=deleteConfirm.value.trim()!=='PADAM '+deleteForm.elements.namedItem('id').value;});
    deleteForm.addEventListener('submit',function(event){
        if(deleteConfirm.value.trim()!=='PADAM '+deleteForm.elements.namedItem('id').value){event.preventDefault();return;}
        deleteSubmit.disabled=true;deleteSubmit.textContent='Memadam…';
    });
    document.getElementById('cancelUserDelete').addEventListener('click',function(){dialog.close();});
    function showEdit(data,error){
        ['id','snapshot','nama_penuh','username','email','no_tel','jawatan'].forEach(function(key){form.elements.namedItem(key).value=data[key]==null?'':data[key];});
        var message=document.getElementById('userEditError');message.textContent=error||'';message.hidden=!error;
        deleteForm.hidden=true;view.hidden=true;form.hidden=false;document.getElementById('detailTitle').textContent='Edit pengguna';
        form.elements.namedItem('nama_penuh').focus();
    }
    document.getElementById('startUserEdit').addEventListener('click',function(){if(editData.rows[selectedId])showEdit(editData.rows[selectedId],'');});
    document.getElementById('cancelUserEdit').addEventListener('click',function(){dialog.close();});
    form.addEventListener('submit',function(){var button=document.getElementById('saveUserEdit');button.disabled=true;button.textContent='Menyimpan…';});
    if(editData.failed){selectedId=editData.failed.id;dialog.showModal();showEdit(editData.failed,editData.error);}

    document.querySelectorAll('.user-detail-button').forEach(function(button){
        button.addEventListener('click',function(){
            selectedId=button.dataset.userId;var user=users[selectedId];if(!user)return;
            deleteForm.hidden=true;deleteButton.hidden=!editData.rows[selectedId].can_delete;form.hidden=true;view.hidden=false;document.getElementById('detailTitle').textContent='Butiran pengguna';
            var fields=document.getElementById('userDetailFields');fields.replaceChildren();
            Object.keys(user).forEach(function(label){
                var dt=document.createElement('dt');dt.textContent=label;
                var dd=document.createElement('dd');dd.textContent=user[label];
                fields.appendChild(dt);fields.appendChild(dd);
            });
            dialog.showModal();
        });
    });
    document.getElementById('closeUserDetails').addEventListener('click',function(){dialog.close();});
    fetchNotifications();setInterval(function(){if(!document.hidden)fetchNotifications();},60000);
});
</script></body></html>