<?php
require_once __DIR__ . '/../db_connect.php';

function column_exists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows === 1;
    $stmt->close();
    return $exists;
}

if (!column_exists($conn, 'users', 'auth_version')) {
    $conn->query('ALTER TABLE users ADD COLUMN auth_version INT UNSIGNED NOT NULL DEFAULT 1 AFTER password');
}

$conn->query("CREATE TABLE IF NOT EXISTS rfid_devices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id INT NOT NULL,
    device_id VARCHAR(64) NOT NULL,
    label VARCHAR(100) NOT NULL,
    api_key_hash CHAR(64) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_used_at DATETIME NULL,
    last_ip_hash CHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_rfid_device_school_id (school_id, device_id),
    UNIQUE KEY uk_rfid_device_key_hash (api_key_hash),
    KEY idx_rfid_device_school_active (school_id, is_active),
    CONSTRAINT fk_rfid_device_school FOREIGN KEY (school_id) REFERENCES sekolah(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rfid_device_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$index = $conn->query("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sebutharga' AND INDEX_NAME = 'uk_sebutharga_no_rujukan' LIMIT 1");
if ($index->num_rows === 0) {
    $duplicates = $conn->query('SELECT no_rujukan FROM sebutharga GROUP BY no_rujukan HAVING COUNT(*) > 1');
    $renameDuplicate = $conn->prepare('UPDATE sebutharga SET no_rujukan = CONCAT(LEFT(no_rujukan, 42), ?, id) WHERE no_rujukan = ? AND id <> ?');
    while ($duplicate = $duplicates->fetch_assoc()) {
        $reference = (string)$duplicate['no_rujukan'];
        $first = $conn->prepare('SELECT MIN(id) AS id FROM sebutharga WHERE no_rujukan = ?');
        $first->bind_param('s', $reference);
        $first->execute();
        $firstId = (int)$first->get_result()->fetch_assoc()['id'];
        $first->close();
        $suffix = '-DUP-';
        $renameDuplicate->bind_param('ssi', $suffix, $reference, $firstId);
        $renameDuplicate->execute();
    }
    $renameDuplicate->close();
    $conn->query('ALTER TABLE sebutharga ADD UNIQUE KEY uk_sebutharga_no_rujukan (no_rujukan)');
}

$legacyQuotes = $conn->query("SELECT id, pdf_path FROM sebutharga WHERE pdf_path LIKE 'uploads/quotations/%'");
$updatePath = $conn->prepare('UPDATE sebutharga SET pdf_path = ? WHERE id = ?');
while ($quote = $legacyQuotes->fetch_assoc()) {
    $privatePath = dirname(__DIR__) . '/storage/private/quotations/' . basename((string)$quote['pdf_path']);
    if (is_file($privatePath)) {
        $quoteId = (int)$quote['id'];
        $updatePath->bind_param('si', $privatePath, $quoteId);
        $updatePath->execute();
    }
}
$updatePath->close();

echo "Security hardening migration complete.\n";
