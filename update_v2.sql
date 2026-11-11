-- ============================================
-- UPDATE v2: kolom file_pdf + tabel admin
-- Jalankan SEKALI di phpMyAdmin (pilih database db_libguides_custom)
-- ============================================

USE db_libguides_custom;

-- Kolom untuk menyimpan nama file PDF yang di-upload
ALTER TABLE sumber_referensi
    ADD COLUMN file_pdf VARCHAR(255) DEFAULT NULL AFTER url_tautan;

-- Akun pustakawan untuk login admin
-- Password default: admin123  (ganti setelah bisa login)
CREATE TABLE admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Hash di bawah ini adalah password 'admin123'
INSERT INTO admin_users (username, password_hash, nama) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Perpustakaan');
