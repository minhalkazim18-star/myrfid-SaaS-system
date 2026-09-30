<?php
require_once __DIR__ . '/security.php';

set_exception_handler(static function (Throwable $e): void {
    error_log('Unhandled application error: ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        if (request_wants_json()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['status' => 'error', 'msg' => 'Permintaan tidak dapat diproses. Sila cuba lagi.'], JSON_UNESCAPED_UNICODE);
            return;
        }
    }
    echo 'Permintaan tidak dapat diproses. Sila cuba lagi.';
});

date_default_timezone_set("Asia/Kuala_Lumpur");

$serverName = strtolower((string)($_SERVER['SERVER_NAME'] ?? ''));
$isLocalRuntime = PHP_SAPI === 'cli' || in_array($serverName, ['localhost', '127.0.0.1', '::1'], true);
$environment = strtolower((string)(getenv('APP_ENV') ?: ($isLocalRuntime ? 'development' : 'production')));
$requiredDatabaseVariables = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
$missingDatabaseVariables = array_values(array_filter(
    $requiredDatabaseVariables,
    static fn(string $name): bool => getenv($name) === false || trim((string)getenv($name)) === ''
));
if ($missingDatabaseVariables !== [] && $environment !== 'development' && $environment !== 'testing') {
    error_log('Database configuration is incomplete: ' . implode(', ', $missingDatabaseVariables));
    http_response_code(500);
    exit('Konfigurasi pangkalan data tidak lengkap.');
}

$servername = (string)(getenv('DB_HOST') ?: '127.0.0.1');
$port = (int)(getenv('DB_PORT') ?: 3306);
$username = (string)(getenv('DB_USER') ?: 'root');
$password = (string)(getenv('DB_PASSWORD') ?: '');
$dbname = (string)(getenv('DB_NAME') ?: 'myrfid');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_connect($servername, $username, $password, $dbname, $port);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (Throwable $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Sambungan pangkalan data tidak tersedia.');
}


if (!function_exists('catat_log')) {
    function catat_log($conn, $user_id, $nama_user, $aktiviti) {
        $sch_id = (int)($_SESSION['school_id'] ?? 0);
        $masa = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO activity_logs (school_id, user_id, nama_user, aktiviti, masa) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $sch_id, $user_id, $nama_user, $aktiviti, $masa);
        $stmt->execute();
        $stmt->close();
    }
}
?>
