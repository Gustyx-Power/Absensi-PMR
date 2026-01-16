-- =============================================
-- Database: absensi_pmr
-- PMR Attendance System
-- Updated: 2026-01-17
-- =============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- =============================================
-- Create Database
-- =============================================
CREATE DATABASE IF NOT EXISTS `absensi_pmr` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `absensi_pmr`;

-- =============================================
-- Table: users
-- =============================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nis` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(20) DEFAULT NULL,
  `jabatan` enum('Pembina','Pengurus','Anggota') NOT NULL DEFAULT 'Anggota',
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nis` (`nis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: events
-- =============================================
DROP TABLE IF EXISTS `events`;
CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kegiatan` varchar(150) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `tolerance_time` time DEFAULT NULL COMMENT 'Batas waktu tidak terlambat',
  `jam_selesai` time NOT NULL,
  `batas_pulang` time DEFAULT NULL COMMENT 'Waktu mulai boleh absen pulang',
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: attendance
-- =============================================
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `status` enum('Hadir','Terlambat','Izin','Sakit','Alpha') NOT NULL DEFAULT 'Alpha',
  `waktu_absen` datetime DEFAULT NULL COMMENT 'Waktu check-in',
  `clock_out` datetime DEFAULT NULL COMMENT 'Waktu check-out',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`user_id`,`event_id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Sample Data: Users
-- =============================================
INSERT INTO `users` (`nis`, `nama`, `kelas`, `jabatan`, `password`) VALUES
('PMR001', 'Dr. Siti Pembina', NULL, 'Pembina', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('PMR002', 'Ahmad Ketua', '12 IPA 1', 'Pengurus', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('PMR003', 'Budi Santoso', '11 IPA 2', 'Anggota', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('PMR004', 'Citra Dewi', '11 IPS 1', 'Anggota', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('PMR005', 'Dani Pratama', '10 IPA 1', 'Anggota', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Note: Default password for all users is "password"

-- =============================================
-- Sample Data: Events
-- =============================================
INSERT INTO `events` (`nama_kegiatan`, `tanggal`, `jam_mulai`, `tolerance_time`, `jam_selesai`, `batas_pulang`, `deskripsi`) VALUES
('Latihan Rutin PMR', CURDATE(), '08:00:00', '08:15:00', '11:00:00', '10:45:00', 'Latihan rutin mingguan PMR'),
('Donor Darah', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '07:30:00', '07:45:00', '12:00:00', '11:45:00', 'Kegiatan donor darah bersama PMI'),
('Pelatihan P3K', DATE_ADD(CURDATE(), INTERVAL 14 DAY), '09:00:00', '09:15:00', '15:00:00', '14:45:00', 'Pelatihan pertolongan pertama kecelakaan');

COMMIT;

-- =============================================
-- Status Legend:
-- Hadir     = Check-in before tolerance_time
-- Terlambat = Check-in after tolerance_time
-- Izin      = Manual entry (with permission)
-- Sakit     = Manual entry (sick)
-- Alpha     = No attendance (auto-generated)
-- =============================================
