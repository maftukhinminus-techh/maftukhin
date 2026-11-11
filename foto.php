<?php
/**
 * foto.php — pintu aman menyajikan foto dokumentasi pengumuman.
 * File foto disimpan di luar htdocs (UPLOAD_DIR), sehingga tidak bisa
 * diakses langsung via URL — hanya lewat skrip ini dengan nama file
 * yang tervalidasi ketat.
 */
require_once __DIR__ . '/config.php';

$nama = (string)($_GET['f'] ?? '');

// Validasi: hanya nama file hasil sistem kita (foto_<hex>.<ext>), cegah path traversal
if (!preg_match('/^foto_[a-f0-9]{24}\.(jpg|jpeg|png|webp)$/', $nama)) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$path = UPLOAD_DIR . $nama;
if (!is_file($path)) {
    http_response_code(404);
    exit('File tidak ditemukan di server.');
}

$mimeOk = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
$ext = strtolower(pathinfo($nama, PATHINFO_EXTENSION));

header('Content-Type: ' . $mimeOk[$ext]);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400'); // cache 1 hari
readfile($path);
exit;
