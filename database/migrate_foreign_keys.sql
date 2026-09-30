-- Referential-integrity migration. Run after migrate_security.sql.

ALTER TABLE `users`
  MODIFY `school_id` int NULL DEFAULT NULL;

UPDATE `users` SET `school_id` = NULL WHERE `role` = 'superadmin';

ALTER TABLE `users`
  ADD KEY `idx_users_school` (`school_id`),
  ADD CONSTRAINT `fk_users_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;

DELETE pr FROM `password_resets` pr
LEFT JOIN `users` u ON u.`email` = pr.`email`
WHERE u.`id` IS NULL OR pr.`expires_at` <= NOW();

ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_reset_user_email` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE `kelas`
  ADD CONSTRAINT `fk_kelas_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `pelajar`
  ADD CONSTRAINT `fk_pelajar_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `kehadiran`
  ADD KEY `idx_kehadiran_school_date` (`school_id`, `tarikh`),
  ADD CONSTRAINT `fk_kehadiran_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `transaksi_rmt`
  ADD CONSTRAINT `fk_transaksi_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `upgrade_requests`
  ADD CONSTRAINT `fk_upgrade_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `sebutharga`
  ADD CONSTRAINT `fk_quote_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `invois_sekolah`
  ADD KEY `idx_invoice_school` (`sekolah_id`),
  ADD CONSTRAINT `fk_invoice_school` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;
