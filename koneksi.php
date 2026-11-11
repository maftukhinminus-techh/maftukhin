<?php
/**
 * koneksi.php
 * Koneksi database menggunakan PDO + prepared statement.
 * Cukup require_once file ini di halaman lain, lalu gunakan $pdo.
 */

// ---- Mode debug: false default agar aman saat online/produksi ----
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN));
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ---- Konfigurasi ----
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'db_libguides_custom');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// ---- Opsi PDO ----
$options = [
    // Lempar exception saat ada error SQL (bukan silent fail)
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Fetch default: associative array
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Matikan emulasi prepared statement -> gunakan native prepared
    // statement dari MySQL, lebih aman dari SQL injection
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // JANGAN tampilkan $e->getMessage() di produksi (bisa bocorkan kredensial).
    // Detail error dicatat ke log server; pengguna hanya melihat pesan generik.
    error_log('Koneksi DB gagal: ' . $e->getMessage());
    http_response_code(500);
    die('Terjadi gangguan pada sistem. Silakan coba beberapa saat lagi.');
}

// ============================================================
// Contoh pemakaian Prepared Statement (referensi pola aman)
// ============================================================

// SELECT dengan parameter:
// $stmt = $pdo->prepare('SELECT * FROM panduan_subjek WHERE id_fakultas = :id');
// $stmt->execute(['id' => $idFakultas]);
// $rows = $stmt->fetchAll();

// INSERT dengan parameter:
// $stmt = $pdo->prepare('INSERT INTO log_pencarian (keyword) VALUES (:kw)');
// $stmt->execute(['kw' => $keyword]);
