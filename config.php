<?php
/**
 * config.php — konstanta bersama aplikasi.
 * (Fungsi session & CSRF terpusat di includes/helpers.php)
 */

// Direktori upload dapat diatur dari environment hosting.
// Fallback: folder uploads/ di dalam proyek agar mudah dipakai di shared hosting.
$uploadDir = getenv('UPLOAD_DIR');
if (!$uploadDir) {
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
}
$uploadDir = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR;
define('UPLOAD_DIR', $uploadDir);
