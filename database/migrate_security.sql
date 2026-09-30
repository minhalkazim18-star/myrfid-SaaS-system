-- Security and tenant-integrity migration for the current MyRFID schema.
-- Take a database backup before running this migration in another environment.

CREATE TABLE IF NOT EXISTS `auth_rate_limits` (
  `rate_key` char(64) NOT NULL,
  `attempts` int unsigned NOT NULL DEFAULT 0,
  `last_attempt` datetime NOT NULL,
  PRIMARY KEY (`rate_key`),
  KEY `idx_auth_rate_last_attempt` (`last_attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `superadmin_notification_reads` (
  `user_id` int NOT NULL,
  `notification_key` varchar(190) NOT NULL,
  `read_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `notification_key`),
  KEY `idx_superadmin_notification_read_at` (`read_at`),
  CONSTRAINT `fk_superadmin_notification_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `kelas`
  DROP INDEX `nama_kelas`,
  ADD UNIQUE KEY `uk_kelas_school_nama` (`school_id`, `nama_kelas`),
  ADD KEY `idx_kelas_school` (`school_id`);

ALTER TABLE `pelajar`
  DROP INDEX `rfid_uid`,
  ADD UNIQUE KEY `uk_pelajar_school_rfid` (`school_id`, `rfid_uid`),
  ADD KEY `idx_pelajar_school_status` (`school_id`, `status`),
  ADD KEY `idx_pelajar_school_kelas` (`school_id`, `nama_kelas`);

ALTER TABLE `transaksi_rmt`
  ADD UNIQUE KEY `uk_transaksi_daily_scan` (`school_id`, `rfid_uid`, `tarikh`),
  ADD KEY `idx_transaksi_school_date` (`school_id`, `tarikh`);

ALTER TABLE `activity_logs`
  ADD KEY `idx_activity_school_time` (`school_id`, `masa`);

ALTER TABLE `password_resets`
  DROP INDEX `token`,
  ADD UNIQUE KEY `uk_password_reset_token` (`token`),
  ADD KEY `idx_password_reset_expiry` (`expires_at`);

UPDATE `users` SET `email` = NULL WHERE `email` = '';
ALTER TABLE `users`
  ADD UNIQUE KEY `uk_users_email` (`email`);

ALTER TABLE `upgrade_requests`
  ADD KEY `idx_upgrade_school_status` (`school_id`, `status`);

ALTER TABLE `sebutharga`
  ADD KEY `idx_quote_school_status` (`school_id`, `status`);
