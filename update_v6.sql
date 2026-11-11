-- ============================================
-- UPDATE v6: Dokumentasi foto pada pengumuman
-- Import SEKALI di phpMyAdmin (database db_libguides_custom)
-- ============================================

USE db_libguides_custom;

ALTER TABLE pengumuman
    ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER isi;
