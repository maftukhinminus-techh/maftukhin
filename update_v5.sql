-- ============================================
-- UPDATE v5: Modul Pengumuman (Berita, Acara, Hari Besar)
-- Import SEKALI di phpMyAdmin (database db_libguides_custom)
-- ============================================

USE db_libguides_custom;

CREATE TABLE pengumuman (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    isi TEXT NOT NULL,
    jenis ENUM('berita','acara','hari_besar') NOT NULL DEFAULT 'berita',
    tanggal_acara DATE DEFAULT NULL,         -- hanya untuk jenis acara/hari besar
    status_aktif TINYINT(1) NOT NULL DEFAULT 1,
    dibuat_oleh INT UNSIGNED DEFAULT NULL,   -- id admin_users pembuat
    dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pengumuman_pembuat FOREIGN KEY (dibuat_oleh)
        REFERENCES admin_users (id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_status_jenis (status_aktif, jenis),
    INDEX idx_tanggal_acara (tanggal_acara)
) ENGINE=InnoDB;

-- Contoh data awal
INSERT INTO pengumuman (judul, isi, jenis, tanggal_acara, status_aktif) VALUES
('Layanan Perpustakaan Kembali Normal', 'Mulai minggu ini, layanan perpustakaan kembali beroperasi penuh pukul 08.00–16.00 WITA.', 'berita', NULL, 1),
('Pelatihan Literasi Informasi Daring', 'Perpustakaan mengadakan pelatihan pencarian jurnal internasional untuk mahasiswa tingkat akhir. Pendaftaran melalui pustakawan.', 'acara', CURDATE() + INTERVAL 7 DAY, 1),
('Hari Kunjungan Perpustakaan Nasional', 'Selamat Hari Kunjungan Perpustakaan Nasional! Ayo budayakan membaca.', 'hari_besar', CURDATE() + INTERVAL 14 DAY, 1);
