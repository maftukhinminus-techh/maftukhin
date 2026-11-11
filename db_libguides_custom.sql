-- ============================================
-- 1. DATABASE
-- ============================================
CREATE DATABASE IF NOT EXISTS db_libguides_custom
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_libguides_custom;

-- ============================================
-- 2. TABEL INDUK (tanpa foreign key)
-- ============================================

-- Kategori berdasarkan fakultas
CREATE TABLE kategori_fakultas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_fakultas VARCHAR(100) NOT NULL,
    ikon_fontawesome VARCHAR(50) NOT NULL DEFAULT 'fa-solid fa-book'
) ENGINE=InnoDB;

-- Data pustakawan (untuk widget "Ask a Librarian")
CREATE TABLE pustakawan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    whatsapp VARCHAR(20) NOT NULL,          -- format internasional tanpa '+', cth: 6281234567890
    status_aktif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ============================================
-- 3. TABEL BERELASI
-- ============================================

-- Panduan subjek, milik satu fakultas
CREATE TABLE panduan_subjek (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(150) NOT NULL,
    id_fakultas INT UNSIGNED NOT NULL,
    total_views INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_panduan_fakultas
        FOREIGN KEY (id_fakultas)
        REFERENCES kategori_fakultas (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sumber referensi di dalam tiap panduan
CREATE TABLE sumber_referensi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_panduan INT UNSIGNED NOT NULL,
    judul_sumber VARCHAR(200) NOT NULL,
    jenis_sumber ENUM('buku','jurnal','ebook','database','website') NOT NULL DEFAULT 'buku',
    url_tautan VARCHAR(255) DEFAULT NULL,
    nomor_ddc VARCHAR(20) DEFAULT NULL,     -- klasifikasi fisik Dewey, cth: 658.15
    CONSTRAINT fk_sumber_panduan
        FOREIGN KEY (id_panduan)
        REFERENCES panduan_subjek (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Rekaman kata kunci pencarian mahasiswa (analitik)
CREATE TABLE log_pencarian (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(150) NOT NULL,
    waktu_pencarian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_keyword (keyword)
) ENGINE=InnoDB;

-- ============================================
-- 4. DUMMY DATA
-- ============================================

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

INSERT INTO log_pencarian (keyword, waktu_pencarian) VALUES
('skripsi akuntansi',     NOW() - INTERVAL 5 DAY),
('jurnal manajemen',      NOW() - INTERVAL 4 DAY),
('ddc 658',               NOW() - INTERVAL 4 DAY),
('skripsi akuntansi',     NOW() - INTERVAL 3 DAY),
('ebook anatomi',         NOW() - INTERVAL 3 DAY),
('jurnal manajemen',      NOW() - INTERVAL 2 DAY),
('skripsi akuntansi',     NOW() - INTERVAL 2 DAY),
('metodologi penelitian', NOW() - INTERVAL 1 DAY),
('jurnal manajemen',      NOW() - INTERVAL 12 HOUR),
('ebook anatomi',         NOW() - INTERVAL 1 HOUR);
