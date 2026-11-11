<?php
/**
 * includes/helpers.php — fungsi bersama: escaping, sesi, CSRF, login guard, backup.
 * File ini tidak menjalankan apa pun sampai fungsinya dipanggil.
 */
require_once __DIR__ . '/../config.php';

// ---- Escaping output (anti-XSS) ----
if (!function_exists('e')) {
    function e(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

// ---- Session aman ----
function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ---- CSRF ----
function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// Cetak hidden input untuk ditaruh di dalam <form method="POST">
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

// Wajib dipanggil di awal setiap proses POST
function csrf_verify(): void {
    start_session();
    $token = $_POST['csrf'] ?? '';
    if ($token === '' || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        exit('Permintaan tidak valid. Silakan muat ulang halaman.');
    }
}

// ---- Penjaga halaman admin + batas idle 30 menit ----
function require_login(): void {
    start_session();

    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }

    $MAX_IDLE = 30 * 60; // 30 menit
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $MAX_IDLE) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();

    // Regenerasi ID sesi berkala (15 menit) untuk cegah session fixation
    if (!isset($_SESSION['session_created'])) {
        $_SESSION['session_created'] = time();
    } elseif (time() - $_SESSION['session_created'] > 900) {
        session_regenerate_id(true);
        $_SESSION['session_created'] = time();
    }
}

// ---- Penjaga khusus role (panggil SETELAH require_login) ----
function require_role(string ...$roles): void {
    $role = $_SESSION['admin_role'] ?? '';
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        exit('Akses ditolak: halaman ini khusus ' . implode('/', $roles) . '.');
    }
}

// ---- Backup database otomatis: 1x per hari saat admin membuka dasbor ----
function auto_backup(): void {
    if (!defined('DB_NAME')) return; // koneksi.php belum dimuat

    $mysqldump = getenv('MYSQLDUMP_PATH') ?: '';
    if ($mysqldump === '' || !file_exists($mysqldump)) return; // hosting umum sering tidak menyediakan akses ini

    $dir  = __DIR__ . '/../backup/';
    $file = $dir . 'db_' . date('Y-m-d') . '.sql';
    if (file_exists($file)) return; // hari ini sudah dibackup

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $cmd = '"' . $mysqldump . '"'
        . ' --user=' . escapeshellarg(DB_USER)
        . ' --password=' . escapeshellarg(DB_PASS)
        . ' --host=' . escapeshellarg(DB_HOST)
        . ' --port=' . (int) DB_PORT
        . ' ' . escapeshellarg(DB_NAME)
        . ' > ' . escapeshellarg($file) . ' 2>&1';

    @exec($cmd, $out, $code);
    if ($code !== 0 || !file_exists($file) || filesize($file) === 0) {
        @unlink($file);
        error_log('auto_backup gagal dijalankan.');
    }
}
