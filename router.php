<?php
declare(strict_types=1);

// Safe router for local development: php -S 127.0.0.1:8000 router.php
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = rawurldecode(is_string($uriPath) ? $uriPath : '/');
$normalized = '/' . ltrim($path, '/');

header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' data: https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'; worker-src 'self'; manifest-src 'self'");

$blocked = str_contains($normalized, "\0")
    || preg_match('#(?:^|/)\.\.(?:/|$)#', $normalized)
    || preg_match('#^/(?:\.git|\.agents|\.codex)(?:/|$)#i', $normalized)
    || preg_match('#^/(?:database|vendor|PHPMailer|storage|uploads/quotations|invoices)(?:/|$)#i', $normalized)
    || strcasecmp($normalized, '/js/settings.html') === 0
    || preg_match('#^/(?:\.env(?:\..*)?|composer\.(?:json|lock)|backup_database\.sql|l\.html|d\.html|create_smtp_table\.php)$#i', $normalized)
    || preg_match('#^/(?:security|db_connect|functions|session_auth|header|sidebar|topbar|footer|log_helper|email_helper|generate_quotation|modal_(?:tambah_guru|edit_guru|pelajar|edit_pelajar|delete))\.php$#i', $normalized)
    || preg_match('#^/uploads/.*\.(?:php|phtml|phar)$#i', $normalized);
if ($blocked) {
    http_response_code(403);
    exit('Forbidden');
}

if (in_array($normalized, ['/portal', '/portal/', '/login', '/login/'], true)) {
    require __DIR__ . '/index.php';
    return true;
}

$candidate = __DIR__ . $normalized;
if ($normalized !== '/' && is_file($candidate)) {
    return false;
}

if ($normalized === '/') {
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__ . '/index.html');
    return true;
}

http_response_code(404);
echo 'Not Found';
return true;
