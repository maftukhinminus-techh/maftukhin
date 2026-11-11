-- ============================================
-- UPDATE v3: status aktif sumber + timestamp + index
-- Import SEKALI di phpMyAdmin (database db_libguides_custom)
-- ============================================

USE db_libguides_custom;

-- Sumber bisa dinonaktifkan tanpa menghapus file
ALTER TABLE sumber_referensi
    ADD COLUMN status_aktif TINYINT(1) NOT NULL DEFAULT 1 AFTER nomor_ddc;

-- Timestamp untuk fitur "urutkan terbaru" & audit
ALTER TABLE panduan_subjek
    ADD COLUMN dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE sumber_referensi
    ADD COLUMN dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Index untuk export analitik per bulan
ALTER TABLE log_pencarian
    ADD INDEX idx_waktu (waktu_pencarian);

-- Catatan: index id_fakultas & id_panduan sudah otomatis dibuat oleh FOREIGN KEY.
