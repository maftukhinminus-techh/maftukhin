<?php
/**
 * auth.php — penjaga halaman admin (wrapper).
 * require_once di bagian atas setiap halaman khusus admin.
 * Semua logika ada di includes/helpers.php.
 */
require_once __DIR__ . '/includes/helpers.php';
require_login();
