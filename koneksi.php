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

// Baca env dengan beberapa alias sekaligus.
// Railway menyediakan MYSQLHOST/MYSQLPORT/dst.; shared hosting biasanya pakai DB_HOST/dst.
function env_first(array $keys, string $default = ''): string {
    foreach ($keys as $key) {
        $val = getenv($key);
        if (is_string($val) && $val !== '') {
            return $val;
        }
    }
    return $default;
}

// ---- Konfigurasi ----
define('DB_HOST', env_first(['DB_HOST', 'MYSQLHOST'], 'localhost'));
define('DB_PORT', env_first(['DB_PORT', 'MYSQLPORT'], '3306'));
define('DB_NAME', env_first(['DB_NAME', 'MYSQLDATABASE'], 'db_libguides_custom'));
define('DB_USER', env_first(['DB_USER', 'MYSQLUSER'], 'root'));
define('DB_PASS', env_first(['DB_PASS', 'MYSQLPASSWORD'], ''));
define('DB_CHARSET', env_first(['DB_CHARSET'], 'utf8mb4'));

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
