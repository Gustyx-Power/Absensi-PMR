-- =============================================
-- Database Schema: PMR Attendance System
-- Created: 2026-01-16
-- =============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS `absensi_pmr` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `absensi_pmr`;

-- =============================================
-- Table: users
-- Stores member data (Anggota, Pengurus, Pembina)
-- =============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nis` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Nomor Induk Siswa',
    `nama` VARCHAR(100) NOT NULL,
    `kelas` VARCHAR(20) NOT NULL COMMENT 'Contoh: X-1, XI-2, XII-3',
    `jabatan` ENUM('Anggota', 'Pengurus', 'Pembina') NOT NULL DEFAULT 'Anggota',
    `password` VARCHAR(255) NOT NULL COMMENT 'Hashed with password_hash()',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_nis` (`nis`),
    INDEX `idx_jabatan` (`jabatan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Table: events
-- Stores PMR activities/events
-- =============================================
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kegiatan` VARCHAR(150) NOT NULL,
    `tanggal` DATE NOT NULL,
    `jam_mulai` TIME NOT NULL,
    `jam_selesai` TIME NOT NULL,
    `deskripsi` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Table: attendance
-- Stores attendance records
-- =============================================
CREATE TABLE IF NOT EXISTS `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `status` ENUM('Hadir', 'Izin', 'Sakit', 'Alpha') NOT NULL DEFAULT 'Alpha',
    `waktu_absen` DATETIME NULL COMMENT 'Waktu saat melakukan absen',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    
    UNIQUE KEY `unique_attendance` (`user_id`, `event_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Sample Data (Optional - for testing)
-- =============================================

-- Insert sample Pembina (admin)
INSERT INTO `users` (`nis`, `nama`, `kelas`, `jabatan`, `password`) VALUES
('PEMBINA001', 'Bapak Ahmad', '-', 'Pembina', '$2y$10$8K1p/a0dL1LXMIgoEDFrVe8jzAfCMKNm9fxC6Y9I6x0p6qQxCvGqK');
-- Default password: admin123

-- Insert sample Pengurus
INSERT INTO `users` (`nis`, `nama`, `kelas`, `jabatan`, `password`) VALUES
('2024001', 'Siti Nurhaliza', 'XI-1', 'Pengurus', '$2y$10$8K1p/a0dL1LXMIgoEDFrVe8jzAfCMKNm9fxC6Y9I6x0p6qQxCvGqK');
-- Default password: admin123

-- Insert sample Anggota
INSERT INTO `users` (`nis`, `nama`, `kelas`, `jabatan`, `password`) VALUES
('2024002', 'Budi Santoso', 'X-2', 'Anggota', '$2y$10$8K1p/a0dL1LXMIgoEDFrVe8jzAfCMKNm9fxC6Y9I6x0p6qQxCvGqK'),
('2024003', 'Dewi Anggraini', 'X-3', 'Anggota', '$2y$10$8K1p/a0dL1LXMIgoEDFrVe8jzAfCMKNm9fxC6Y9I6x0p6qQxCvGqK');
-- Default password: admin123

-- Insert sample Event
INSERT INTO `events` (`nama_kegiatan`, `tanggal`, `jam_mulai`, `jam_selesai`, `deskripsi`) VALUES
('Latihan Rutin Minggu I', '2026-01-20', '08:00:00', '11:00:00', 'Latihan rutin PMR meliputi materi P3K dasar dan simulasi pertolongan pertama.'),
('Pelatihan Donor Darah', '2026-01-27', '09:00:00', '12:00:00', 'Edukasi dan simulasi proses donor darah bekerja sama dengan PMI.');
