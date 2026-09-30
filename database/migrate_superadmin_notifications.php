<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';

$conn->query("CREATE TABLE IF NOT EXISTS superadmin_notification_reads (
    user_id INT NOT NULL,
    notification_key VARCHAR(190) NOT NULL,
    read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, notification_key),
    KEY idx_superadmin_notification_read_at (read_at),
    CONSTRAINT fk_superadmin_notification_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

echo "SuperAdmin notification migration complete.\n";
