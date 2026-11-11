-- ============================================
-- UPDATE v4: Modul User/Anggota + Bibliografi + Sirkulasi Digital
-- Import SEKALI di phpMyAdmin (database db_libguides_custom)
-- ============================================

USE db_libguides_custom;

-- 1. Role pada akun pengguna (admin / pustakawan)
ALTER TABLE admin_users
    ADD COLUMN role ENUM('admin','pustakawan') NOT NULL DEFAULT 'pustakawan' AFTER nama;

-- Akun 'admin' yang sudah ada otomatis menjadi Administrator
UPDATE admin_users SET role = 'admin' WHERE username = 'admin';

-- 2. Data anggota perpustakaan (pemustaka, tanpa akun login)
CREATE TABLE anggota (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    fakultas VARCHAR(100) DEFAULT NULL,
    telepon VARCHAR(20) DEFAULT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    terdaftar_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Data bibliografi lengkap pada sumber referensi
ALTER TABLE sumber_referensi
    ADD COLUMN pengarang VARCHAR(150) DEFAULT NULL AFTER judul_sumber,
    ADD COLUMN penerbit VARCHAR(100) DEFAULT NULL AFTER pengarang,
    ADD COLUMN tahun_terbit VARCHAR(4) DEFAULT NULL AFTER penerbit;

-- 4. Sirkulasi digital: setiap unduhan = transaksi peminjaman
CREATE TABLE peminjaman (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_sumber INT UNSIGNED NOT NULL,
    id_anggota INT UNSIGNED NOT NULL,
    tanggal_pinjam DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    jatuh_tempo DATE NOT NULL,
    tanggal_kembali DATETIME DEFAULT NULL,
    status ENUM('dipinjam','kembali') NOT NULL DEFAULT 'dipinjam',
    CONSTRAINT fk_pinjam_sumber FOREIGN KEY (id_sumber)
        REFERENCES sumber_referensi (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pinjam_anggota FOREIGN KEY (id_anggota)
        REFERENCES anggota (id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_jatuh_tempo (jatuh_tempo)
) ENGINE=InnoDB;

-- Dummy anggota untuk pengujian
INSERT INTO anggota (nim, nama, fakultas, telepon) VALUES
('250911725803', 'Muhammad Maftukhin Siroj', 'Fakultas Keguruan & Ilmu Pendidikan', '6281234567890'),
('210123456789', 'Siti Rahma', 'Fakultas Ekonomi dan Bisnis', '6281299998888');
