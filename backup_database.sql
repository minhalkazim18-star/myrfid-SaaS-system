-- MyRFID schema-only backup. No production or sample personal data is included.
-- Generated after database/migrate_security.sql and database/migrate_foreign_keys.sql.
-- Includes security hardening and SuperAdmin notification read tracking.

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `school_id` int NOT NULL DEFAULT '1',
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `nama_user` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `aktiviti` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `masa` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_school_time` (`school_id`,`masa`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_rate_limits` (
  `rate_key` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `attempts` int unsigned NOT NULL DEFAULT '0',
  `last_attempt` datetime NOT NULL,
  PRIMARY KEY (`rate_key`),
  KEY `idx_auth_rate_last_attempt` (`last_attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invois_sekolah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invois_sekolah` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sekolah_id` int NOT NULL,
  `no_invois` varchar(50) NOT NULL,
  `tarikh_invois` date NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `status` enum('Lunas','Tertunggak','Dibatalkan') DEFAULT 'Tertunggak',
  `tarikh_bayaran` datetime DEFAULT NULL,
  `butiran` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_invoice_school` (`sekolah_id`),
  CONSTRAINT `fk_invoice_school` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kehadiran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kehadiran` (
  `school_id` int NOT NULL DEFAULT '1',
  `id` int NOT NULL AUTO_INCREMENT,
  `rfid_uid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_penuh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_kelas` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tarikh` date NOT NULL DEFAULT (curdate()),
  `masa` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_masuk` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kehadiran_school_date` (`school_id`,`tarikh`),
  CONSTRAINT `fk_kehadiran_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kelas` (
  `school_id` int NOT NULL DEFAULT '1',
  `id_kelas` int NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_kelas`),
  UNIQUE KEY `uk_kelas_school_nama` (`school_id`,`nama_kelas`),
  KEY `idx_kelas_school` (`school_id`),
  CONSTRAINT `fk_kelas_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_password_reset_token` (`token`),
  KEY `email` (`email`),
  KEY `idx_password_reset_expiry` (`expires_at`),
  CONSTRAINT `fk_password_reset_user_email` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pelajar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pelajar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_id` int NOT NULL DEFAULT '1',
  `rfid_uid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_penuh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `darjah` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_kelas` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1: Aktif, 0: Tidak Aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pelajar_school_rfid` (`school_id`,`rfid_uid`),
  KEY `idx_pelajar_school_status` (`school_id`,`status`),
  KEY `idx_pelajar_school_kelas` (`school_id`,`nama_kelas`),
  CONSTRAINT `fk_pelajar_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pelan_struktur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pelan_struktur` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_pelan` varchar(50) NOT NULL,
  `had_murid` int DEFAULT NULL,
  `harga_tahun1` decimal(10,2) NOT NULL,
  `harga_renewal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sa_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sa_audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `nama_admin` varchar(100) NOT NULL,
  `tindakan` varchar(50) NOT NULL,
  `butiran` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sa_smtp_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sa_smtp_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `smtp_host` varchar(255) DEFAULT 'sandbox.smtp.mailtrap.io',
  `smtp_port` int DEFAULT '587',
  `smtp_auth` tinyint(1) DEFAULT '1',
  `smtp_username` varchar(255) DEFAULT '',
  `smtp_password` varchar(255) DEFAULT '',
  `smtp_from_email` varchar(255) DEFAULT 'admin@myrfid.edu.my',
  `smtp_from_name` varchar(255) DEFAULT 'SaaS MyRFID Admin',
  `smtp_encryption` enum('tls','ssl','none') DEFAULT 'tls',
  `is_active` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sebutharga`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sebutharga` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_id` int NOT NULL,
  `jenis` enum('Pendaftaran','Naik Taraf','Pembaharuan') NOT NULL DEFAULT 'Pendaftaran',
  `no_rujukan` varchar(50) NOT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `tarikh_terbit` datetime NOT NULL,
  `tarikh_luput` datetime NOT NULL,
  `status` enum('menunggu bayaran','selesai','batal') DEFAULT 'menunggu bayaran',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sebutharga_no_rujukan` (`no_rujukan`),
  KEY `idx_quote_school_status` (`school_id`,`status`),
  CONSTRAINT `fk_quote_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sekolah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sekolah` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kod_sekolah` varchar(20) NOT NULL,
  `nama_sekolah` varchar(150) NOT NULL,
  `email_sekolah` varchar(100) DEFAULT NULL,
  `no_tel` varchar(20) DEFAULT NULL,
  `alamat` text,
  `anggaran_murid` int NOT NULL DEFAULT '0',
  `status` enum('aktif','digantung','pending','menunggu bayaran','ditolak') NOT NULL DEFAULT 'pending',
  `pelan` enum('Trial','Basic','Premium','Pro') DEFAULT 'Trial',
  `tarikh_luput` date DEFAULT NULL,
  `status_bayaran` enum('Lunas','Tertunggak','Percuma') DEFAULT 'Percuma',
  `logo` varchar(255) DEFAULT 'images/logosklh.png',
  `tarikh_daftar` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kod_sekolah` (`kod_sekolah`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tetapan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tetapan` (
  `school_id` int NOT NULL DEFAULT '1',
  `id` int NOT NULL AUTO_INCREMENT,
  `kunci` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nilai` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_school_kunci` (`school_id`,`kunci`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transaksi_rmt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaksi_rmt` (
  `school_id` int NOT NULL DEFAULT '1',
  `id` int NOT NULL AUTO_INCREMENT,
  `rfid_uid` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_penuh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_kelas` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tarikh` date NOT NULL,
  `waktu` time NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'BERJAYA',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_transaksi_daily_scan` (`school_id`,`rfid_uid`,`tarikh`),
  KEY `idx_transaksi_school_date` (`school_id`,`tarikh`),
  CONSTRAINT `fk_transaksi_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `upgrade_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `upgrade_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_id` int NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_upgrade_school_status` (`school_id`,`status`),
  CONSTRAINT `fk_upgrade_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_id` int DEFAULT NULL,
  `nama_penuh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `auth_version` int unsigned NOT NULL DEFAULT '1',
  `role` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'guru',
  `no_tel` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jawatan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `profile_pic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_school` (`school_id`),
  CONSTRAINT `fk_users_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `superadmin_notification_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `superadmin_notification_reads` (
  `user_id` int NOT NULL,
  `notification_key` varchar(190) NOT NULL,
  `read_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`notification_key`),
  KEY `idx_superadmin_notification_read_at` (`read_at`),
  CONSTRAINT `fk_superadmin_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rfid_devices`;
CREATE TABLE `rfid_devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `school_id` int NOT NULL,
  `device_id` varchar(64) NOT NULL,
  `label` varchar(100) NOT NULL,
  `api_key_hash` char(64) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_used_at` datetime DEFAULT NULL,
  `last_ip_hash` char(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rfid_device_school_id` (`school_id`,`device_id`),
  UNIQUE KEY `uk_rfid_device_key_hash` (`api_key_hash`),
  KEY `idx_rfid_device_school_active` (`school_id`,`is_active`),
  CONSTRAINT `fk_rfid_device_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rfid_device_school` FOREIGN KEY (`school_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
