-- ============================================================
-- db_setup_lengkap.sql
-- Setup database LENGKAP untuk LibGuides Custom (v1–v6 dalam satu file).
-- Import SEKALI di phpMyAdmin hosting Anda (atau lewat menu Import).
-- Sudah termasuk: skema dasar + update v2, v3, v4, v5, v6.
-- Akun admin default: username `admin`, password `admin123`
-- (SEGERA ganti lewat menu Ganti Password setelah login pertama).
-- ============================================================

CREATE DATABASE IF NOT EXISTS db_libguides_custom
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_libguides_custom;

-- ============================================================
-- 1. TABEL INDUK
-- ============================================================

CREATE TABLE IF NOT EXISTS kategori_fakultas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_fakultas VARCHAR(100) NOT NULL,
    ikon_fontawesome VARCHAR(50) NOT NULL DEFAULT 'fa-solid fa-book'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pustakawan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    whatsapp VARCHAR(20) NOT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ============================================================
-- 2. TABEL INTI (katalog)
-- ============================================================

CREATE TABLE IF NOT EXISTS panduan_subjek (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(150) NOT NULL,
    id_fakultas INT UNSIGNED NOT NULL,
    total_views INT UNSIGNED NOT NULL DEFAULT 0,
    dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_panduan_fakultas
        FOREIGN KEY (id_fakultas)
        REFERENCES kategori_fakultas (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sumber_referensi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_panduan INT UNSIGNED NOT NULL,
    judul_sumber VARCHAR(200) NOT NULL,
    pengarang VARCHAR(150) DEFAULT NULL,
    penerbit VARCHAR(150) DEFAULT NULL,
    tahun_terbit SMALLINT DEFAULT NULL,
    jenis_sumber ENUM('buku','jurnal','ebook','database','website') NOT NULL DEFAULT 'buku',
    url_tautan VARCHAR(255) DEFAULT NULL,
    file_pdf VARCHAR(255) DEFAULT NULL,
    nomor_ddc VARCHAR(20) DEFAULT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sumber_panduan
        FOREIGN KEY (id_panduan)
        REFERENCES panduan_subjek (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS log_pencarian (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(150) NOT NULL,
    waktu_pencarian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_keyword (keyword)
) ENGINE=InnoDB;

-- ============================================================
-- 3. AKUN & ANGGOTA
-- ============================================================

-- Password default: admin123  (SEGERA ganti setelah login pertama!)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('admin','pustakawan') NOT NULL DEFAULT 'pustakawan',
    dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS anggota (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(30) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    fakultas VARCHAR(100) DEFAULT NULL,
    telepon VARCHAR(20) DEFAULT NULL,
    terdaftar_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 4. SIRKULASI DIGITAL
-- ============================================================

CREATE TABLE IF NOT EXISTS peminjaman (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_sumber INT UNSIGNED NOT NULL,
    id_anggota INT UNSIGNED NOT NULL,
    tanggal_pinjam DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    jatuh_tempo DATE NOT NULL,
    tanggal_kembali DATETIME DEFAULT NULL,
    status ENUM('dipinjam','kembali') NOT NULL DEFAULT 'dipinjam',
    CONSTRAINT fk_pinjam_sumber
        FOREIGN KEY (id_sumber)
        REFERENCES sumber_referensi (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_pinjam_anggota
        FOREIGN KEY (id_anggota)
        REFERENCES anggota (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 5. PENGUMUMAN
-- ============================================================

CREATE TABLE IF NOT EXISTS pengumuman (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    isi TEXT NOT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    kategori ENUM('berita','acara','hari_besar') NOT NULL DEFAULT 'berita',
    tanggal_mulai DATE DEFAULT NULL,
    tanggal_selesai DATE DEFAULT NULL,
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 6. DATA AWAL (contoh katalog + akun admin)
-- ============================================================

INSERT INTO kategori_fakultas (nama_fakultas, ikon_fontawesome) VALUES
('Fakultas Ekonomi dan Bisnis',   'fa-solid fa-chart-line'),
('Fakultas Teknik',               'fa-solid fa-gears'),
('Fakultas Ilmu Sosial & Politik','fa-solid fa-landmark'),
('Fakultas Kedokteran',           'fa-solid fa-stethoscope'),
('Fakultas Keguruan & Ilmu Pendidikan', 'fa-solid fa-chalkboard-user');

INSERT INTO panduan_subjek (judul, id_fakultas, total_views) VALUES
('Manajemen Keuangan',          1, 245),
('Akuntansi Dasar',             1, 178),
('Struktur Beton Bertulang',    2, 132),
('Kebijakan Publik Indonesia',  3, 98),
('Anatomi Manusia',             4, 210),
('Metodologi Penelitian Pendidikan', 5, 156);

INSERT INTO sumber_referensi (id_panduan, judul_sumber, jenis_sumber, url_tautan, nomor_ddc) VALUES
(1, 'Financial Management: Theory & Practice', 'buku', NULL, '658.15 BRI'),
(1, 'Journal of Corporate Finance', 'jurnal', 'https://www.sciencedirect.com/journal/journal-of-corporate-finance', NULL),
(1, 'ProQuest Business Collection', 'database', 'https://www.proquest.com', NULL),
(2, 'Pengantar Akuntansi (Warren Reeve)', 'buku', NULL, '657 WAR'),
(3, 'Design of Concrete Structures', 'buku', NULL, '624.1834 NIL'),
(4, 'Jurnal Kebijakan dan Administrasi Publik', 'jurnal', 'https://jurnal.ugm.ac.id/jkap', NULL),
(5, 'Gray''s Anatomy for Students', 'ebook', 'https://www.clinicalkey.com', '611 GRA'),
(6, 'Research in Education (Best & Kahn)', 'buku', NULL, '370.72 BES');

INSERT INTO pustakawan (nama, email, whatsapp, status_aktif) VALUES
('Rina Kartika, S.IP.',   'rina.kartika@univ.ac.id', '6281234567890', 1),
('Budi Santoso, S.Hum.',  'budi.santoso@univ.ac.id', '6281298765432', 1),
('Sari Wulandari, M.Hum.','sari.wulan@univ.ac.id',   '6281311223344', 0);

-- Login: admin / admin123
INSERT INTO admin_users (username, password_hash, nama, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Perpustakaan', 'admin');
