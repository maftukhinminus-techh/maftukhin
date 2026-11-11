<?php
/**
 * export.php — unduh laporan analitik sebagai CSV (khusus admin)
 * ?jenis=pencarian&tahun=2026&bulan=11  -> log pencarian per bulan
 * ?jenis=panduan                        -> semua panduan + views
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/koneksi.php';

$jenis = (string)($_GET['jenis'] ?? '');
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$bulan = (int)($_GET['bulan'] ?? date('n'));

$filename = 'laporan.csv';
$header   = [];
$rows     = [];

if ($jenis === 'pencarian') {
    $stmt = $pdo->prepare(
        'SELECT keyword, COUNT(*) AS total, MAX(waktu_pencarian) AS terakhir
         FROM log_pencarian
         WHERE YEAR(waktu_pencarian) = :t AND MONTH(waktu_pencarian) = :b
         GROUP BY keyword
         ORDER BY total DESC, keyword ASC'
    );
    $stmt->execute(['t' => $tahun, 'b' => $bulan]);
    $rows     = $stmt->fetchAll(PDO::FETCH_NUM);
    $header   = ['Kata Kunci', 'Jumlah Dicari', 'Terakhir Dicari'];
    $filename = "pencarian_{$tahun}_" . str_pad((string)$bulan, 2, '0', STR_PAD_LEFT) . '.csv';
}
elseif ($jenis === 'panduan') {
    $rows = $pdo->query(
        'SELECT p.judul, k.nama_fakultas, p.total_views, p.dibuat_pada
         FROM panduan_subjek p
         JOIN kategori_fakultas k ON k.id = p.id_fakultas
         ORDER BY p.total_views DESC'
    )->fetchAll(PDO::FETCH_NUM);
    $header   = ['Judul Panduan', 'Fakultas', 'Total Views', 'Dibuat Pada'];
    $filename = 'panduan_views.csv';
}
elseif ($jenis === 'sirkulasi') {
    $rows = $pdo->query(
        "SELECT a.nim, a.nama, s.judul_sumber, p.tanggal_pinjam, p.jatuh_tempo,
                p.tanggal_kembali, p.status
         FROM peminjaman p
         JOIN anggota a ON a.id = p.id_anggota
         JOIN sumber_referensi s ON s.id = p.id_sumber
         ORDER BY p.tanggal_pinjam DESC"
    )->fetchAll(PDO::FETCH_NUM);
    $header   = ['NIM', 'Nama Pemustaka', 'Sumber', 'Tanggal Pinjam', 'Jatuh Tempo', 'Tanggal Kembali', 'Status'];
    $filename = 'sirkulasi.csv';
}
else {
    http_response_code(400);
    exit('Parameter jenis tidak valid.');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar
fputcsv($out, $header);
foreach ($rows as $row) {
    fputcsv($out, $row);
}
fclose($out);
exit;
