<?php
declare(strict_types=1);

/**
 * Load a local .env file without overriding variables supplied by the server.
 * Production deployments may continue to inject variables through PHP-FPM,
 * Apache, containers, or the operating system.
 */
function load_environment_file(string $path): void
{
    if (!is_file($path) || !is_readable($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_starts_with($line, 'export ')) $line = trim(substr($line, 7));

        $separator = strpos($line, '=');
        if ($separator === false) continue;
        $name = trim(substr($line, 0, $separator));
        if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $name) || getenv($name) !== false) continue;

        $value = trim(substr($line, $separator + 1));
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
    }
}

load_environment_file(__DIR__ . '/.env');

function app_is_https(): bool
{
    $nativeHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $trustedProxyHttps = getenv('TRUST_PROXY_HEADERS') === '1'
        && strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    return $nativeHttps || $trustedProxyHttps;
}

function app_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' data: https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'; worker-src 'self'; manifest-src 'self'; upgrade-insecure-requests");
    if (app_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => app_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('MYRFIDSESSID');
    session_start();

    $idleTimeout = max(900, (int)(getenv('SESSION_IDLE_TIMEOUT') ?: 7200));
    if (isset($_SESSION['user_id'], $_SESSION['last_activity']) && time() - (int)$_SESSION['last_activity'] > $idleTimeout) {
        session_unset();
        session_destroy();
        session_start();
    }
    if (isset($_SESSION['user_id'])) {
        $_SESSION['last_activity'] = time();
    }
}

function request_wants_json(): bool
{
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    $requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    return str_contains($accept, 'application/json') || $requestedWith === 'xmlhttprequest';
}

function google_oauth_configuration(): array
{
    return [
        'client_id' => trim((string)getenv('GOOGLE_CLIENT_ID')),
        'client_secret' => trim((string)getenv('GOOGLE_CLIENT_SECRET')),
        'redirect_uri' => trim((string)getenv('GOOGLE_REDIRECT_URI')),
    ];
}

function google_oauth_is_configured(): bool
{
    $config = google_oauth_configuration();
    if ($config['client_id'] === '' || $config['client_secret'] === '' || $config['redirect_uri'] === '') return false;
    if (!filter_var($config['redirect_uri'], FILTER_VALIDATE_URL)) return false;
    $host = strtolower((string)parse_url($config['redirect_uri'], PHP_URL_HOST));
    $scheme = strtolower((string)parse_url($config['redirect_uri'], PHP_URL_SCHEME));
    $localHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    return $scheme === 'https' || ($localHost && $scheme === 'http');
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function require_login(bool $json = false): void
{
    app_start_session();
    if (isset($_SESSION['user_id'])) {
        // Halaman terlindung tidak boleh dipaparkan semula daripada cache
        // selepas pengguna menamatkan sesi dan menekan butang Back.
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        return;
    }

    if ($json || request_wants_json()) {
        json_response(['status' => 'error', 'msg' => 'Sesi telah tamat. Sila log masuk semula.'], 401);
    }
    header('Location: index.php');
    exit;
}

function require_roles(array $roles, bool $json = false): void
{
    require_login($json);
    if (in_array((string)($_SESSION['role'] ?? ''), $roles, true)) {
        return;
    }

    if ($json || request_wants_json()) {
        json_response(['status' => 'error', 'msg' => 'Anda tidak mempunyai kebenaran untuk tindakan ini.'], 403);
    }
    http_response_code(403);
    exit('Akses ditolak.');
}

function require_active_account(mysqli $conn, bool $json = false): void
{
    require_login($json);
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $stmt = $conn->prepare('SELECT u.id, u.role, u.school_id, u.nama_penuh, u.email, u.auth_version, s.status AS school_status, s.tarikh_luput FROM users u LEFT JOIN sekolah s ON s.id = u.school_id WHERE u.id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $valid = is_array($account);
    if ($valid && isset($_SESSION['auth_version']) && (int)$_SESSION['auth_version'] !== (int)$account['auth_version']) {
        $valid = false;
    }
    if ($valid && $account['role'] !== 'superadmin') {
        $expired = !empty($account['tarikh_luput']) && strtotime((string)$account['tarikh_luput']) < strtotime(date('Y-m-d'));
        $valid = $account['school_status'] === 'aktif' && !$expired && (int)$account['school_id'] > 0;
    }
    if (!$valid) {
        session_unset();
        session_destroy();
        if ($json || request_wants_json()) {
            json_response(['status' => 'error', 'msg' => 'Akaun atau langganan tidak lagi aktif.'], 401);
        }
        header('Location: index.php?account=inactive');
        exit;
    }

    $_SESSION['role'] = $account['role'];
    $_SESSION['school_id'] = $account['role'] === 'superadmin' ? 0 : (int)$account['school_id'];
    $_SESSION['nama_penuh'] = $account['nama_penuh'];
    $_SESSION['email'] = $account['email'] ?? '';
    $_SESSION['auth_version'] = (int)$account['auth_version'];
    $_SESSION['last_activity'] = time();
}

function require_active_roles(mysqli $conn, array $roles, bool $json = false): void
{
    require_active_account($conn, $json);
    if (in_array((string)($_SESSION['role'] ?? ''), $roles, true)) return;
    if ($json || request_wants_json()) {
        json_response(['status' => 'error', 'msg' => 'Anda tidak mempunyai kebenaran untuk tindakan ini.'], 403);
    }
    http_response_code(403);
    exit('Akses ditolak.');
}

function current_school_id(): int
{
    require_login(request_wants_json());
    $schoolId = (int)($_SESSION['school_id'] ?? 0);
    if ($schoolId <= 0 && ($_SESSION['role'] ?? '') !== 'superadmin') {
        http_response_code(403);
        exit('Tenant sekolah tidak sah.');
    }
    return $schoolId;
}

function csrf_token(): string
{
    app_start_session();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(bool $json = false): void
{
    app_start_session();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        if ($json || request_wants_json()) {
            json_response(['status' => 'error', 'msg' => 'Kaedah permintaan tidak dibenarkan.'], 405);
        }
        http_response_code(405);
        exit('Method Not Allowed');
    }

    $provided = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '');
    $expected = (string)($_SESSION['csrf_token'] ?? '');
    if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
        if ($json || request_wants_json()) {
            json_response(['status' => 'error', 'msg' => 'Token keselamatan tidak sah. Muat semula halaman dan cuba lagi.'], 419);
        }
        http_response_code(419);
        exit('Token keselamatan tidak sah.');
    }
}

function escape_html(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function valid_iso_date(mixed $value, ?string $fallback = null): string
{
    $date = trim((string)$value);
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if ($parsed && $parsed->format('Y-m-d') === $date) {
        return $date;
    }
    return $fallback ?? date('Y-m-d');
}

function valid_class_name(mixed $value): bool
{
    $name = trim((string)$value);
    if ($name === '' || mb_strlen($name) > 50) return false;
    return preg_match("/^[\\p{L}\\p{N}][\\p{L}\\p{N}\\s.'()&\\/-]*$/u", $name) === 1;
}

function encrypt_secret(string $plaintext): string
{
    if ($plaintext === '') return '';
    $appKey = (string)getenv('APP_KEY');
    if (strlen($appKey) < 32) {
        throw new RuntimeException('APP_KEY mesti ditetapkan sekurang-kurangnya 32 aksara.');
    }
    $key = hash('sha256', $appKey, true);
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($ciphertext === false) throw new RuntimeException('Gagal menyulitkan rahsia.');
    return 'enc:' . base64_encode($iv . $tag . $ciphertext);
}

function decrypt_secret(string $stored): string
{
    if ($stored === '' || !str_starts_with($stored, 'enc:')) return $stored;
    $appKey = (string)getenv('APP_KEY');
    if (strlen($appKey) < 32) return '';
    $payload = base64_decode(substr($stored, 4), true);
    if ($payload === false || strlen($payload) < 29) return '';
    $iv = substr($payload, 0, 12);
    $tag = substr($payload, 12, 16);
    $ciphertext = substr($payload, 28);
    $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', hash('sha256', $appKey, true), OPENSSL_RAW_DATA, $iv, $tag);
    return $plain === false ? '' : $plain;
}

function client_ip_hash(): string
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash('sha256', $ip);
}

function login_rate_limited(mysqli $conn, string $username, int $maxAttempts = 5, int $windowMinutes = 15): bool
{
    $key = hash('sha256', strtolower(trim($username)) . '|' . client_ip_hash());
    $stmt = @$conn->prepare('SELECT attempts, last_attempt FROM auth_rate_limits WHERE rate_key = ? LIMIT 1');
    if (!$stmt) return false;
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) return false;
    return (int)$row['attempts'] >= $maxAttempts
        && strtotime((string)$row['last_attempt']) >= strtotime("-$windowMinutes minutes");
}

function record_login_failure(mysqli $conn, string $username, int $windowMinutes = 15): void
{
    $key = hash('sha256', strtolower(trim($username)) . '|' . client_ip_hash());
    $stmt = @$conn->prepare("INSERT INTO auth_rate_limits (rate_key, attempts, last_attempt) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = IF(last_attempt < DATE_SUB(NOW(), INTERVAL $windowMinutes MINUTE), 1, attempts + 1), last_attempt = NOW()");
    if (!$stmt) return;
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $stmt->close();

    // Elakkan jadual berkembang tanpa had akibat identiti rawak atau bot.
    if (random_int(1, 100) === 1) {
        @$conn->query('DELETE FROM auth_rate_limits WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }
}

function clear_login_failures(mysqli $conn, string $username): void
{
    $key = hash('sha256', strtolower(trim($username)) . '|' . client_ip_hash());
    $stmt = @$conn->prepare('DELETE FROM auth_rate_limits WHERE rate_key = ?');
    if (!$stmt) return;
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $stmt->close();
}

function record_superadmin_audit(mysqli $conn, string $action, string $details, ?int $adminId = null, ?string $adminName = null): void
{
    $adminId ??= (int)($_SESSION['user_id'] ?? 0);
    $adminName ??= (string)($_SESSION['nama_penuh'] ?? 'Superadmin');
    $action = mb_substr(trim($action), 0, 50);
    $details = mb_substr(trim($details), 0, 5000);
    $stmt = $conn->prepare('INSERT INTO sa_audit_logs (admin_id, nama_admin, tindakan, butiran) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $adminId, $adminName, $action, $details);
    $stmt->execute();
    $stmt->close();
}
