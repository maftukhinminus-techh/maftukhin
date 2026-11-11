<?php
/**
 * unduh.php — pintu unduh PDF + pencatatan sirkulasi digital.
 * Pemustaka mengisi NIM + nama (tanpa login), transaksi tercatat
 * di tabel peminjaman dengan jatuh tempo 7 hari, lalu file terunduh.
 */
session_start();
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/includes/helpers.php';

$JATUH_TEMPO_HARI = 7;
$error = '';

// ============================================================
// PROSES FORM UNDUH (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $idSumber = (int)($_POST['id'] ?? 0);
    $nim      = trim((string)($_POST['nim'] ?? ''));
    $nama     = trim((string)($_POST['nama'] ?? ''));

    // Ambil data sumber + validasi nama file (cegah path traversal)
    $stmt = $pdo->prepare('SELECT judul_sumber, file_pdf, status_aktif FROM sumber_referensi WHERE id = :id');
    $stmt->execute(['id' => $idSumber]);
    $sumber = $stmt->fetch();

    if (!$sumber || empty($sumber['file_pdf'])
        || !preg_match('/^[a-f0-9]{32}\.pdf$/', $sumber['file_pdf'])
        || !(int)$sumber['status_aktif']) {
        http_response_code(404);
        exit('File tidak ditemukan.');
    }
    if ($nim === '' || $nama === '') {
        $error = 'NIM dan nama wajib diisi untuk mengunduh.';
    } else {
        // Cari anggota berdasarkan NIM — wajib terdaftar dulu lewat daftar.php
        $stmt = $pdo->prepare('SELECT id, nama FROM anggota WHERE nim = :nim');
        $stmt->execute(['nim' => $nim]);
        $anggota = $stmt->fetch();

        if (!$anggota) {
            $error = 'NIM Anda belum terdaftar sebagai anggota. Silakan daftar terlebih dahulu melalui tombol di bawah.';
        } else {
            $idAnggota = (int)$anggota['id'];

        // Catat transaksi sirkulasi
        $pdo->prepare(
            'INSERT INTO peminjaman (id_sumber, id_anggota, jatuh_tempo)
             VALUES (:s, :a, DATE_ADD(CURDATE(), INTERVAL :h DAY))'
        )->execute(['s' => $idSumber, 'a' => $idAnggota, 'h' => $JATUH_TEMPO_HARI]);

        // Kirim file
        $path = UPLOAD_DIR . $sumber['file_pdf'];
        if (!is_file($path)) {
            http_response_code(404);
            exit('File tidak ditemukan di server.');
        }
        $namaUnduh = preg_replace('/[^A-Za-z0-9 _\-\.]/', '', $sumber['judul_sumber']);
        $namaUnduh = trim($namaUnduh) !== '' ? $namaUnduh . '.pdf' : 'dokumen.pdf';

        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="' . $namaUnduh . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
        }
    }
}

// ============================================================
// TAMPILAN FORM (GET)
// ============================================================
$idSumber = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT s.judul_sumber, s.pengarang, s.nomor_ddc, s.file_pdf, s.status_aktif, p.judul AS judul_panduan
     FROM sumber_referensi s
     JOIN panduan_subjek p ON p.id = s.id_panduan
     WHERE s.id = :id'
);
$stmt->execute(['id' => $idSumber]);
$sumber = $stmt->fetch();

$valid = $sumber && !empty($sumber['file_pdf'])
    && preg_match('/^[a-f0-9]{32}\.pdf$/', $sumber['file_pdf'])
    && (int)$sumber['status_aktif'] === 1;
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unduh Sumber — LibGuides Kampus</title>
<script>
  if (localStorage.getItem('theme') === 'dark' ||
      (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
  }
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class' };</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100 min-h-screen flex items-center justify-center px-4">

<div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-8">

  <?php if (!$valid): ?>
    <div class="text-center">
      <i class="fa-solid fa-triangle-exclamation text-4xl text-yellow-500 mb-4"></i>
      <p class="text-gray-500 dark:text-gray-400 mb-4">File tidak tersedia.</p>
      <a href="index.php" class="text-green-700 dark:text-green-400 hover:underline">Kembali ke Beranda</a>
    </div>
  <?php else: ?>
    <div class="text-center mb-6">
      <div class="w-14 h-14 mx-auto rounded-full bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 flex items-center justify-center text-2xl mb-3">
        <i class="fa-solid fa-file-pdf"></i>
      </div>
      <h1 class="text-lg font-bold"><?= e($sumber['judul_sumber']) ?></h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        <?= e($sumber['judul_panduan']) ?>
        <?php if ($sumber['nomor_ddc']): ?> • DDC <?= e($sumber['nomor_ddc']) ?><?php endif; ?>
      </p>
    </div>

    <?php if ($error): ?>
    <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
      <i class="fa-solid fa-circle-exclamation mr-1"></i><?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="unduh.php" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$idSumber ?>">

      <p class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
        <i class="fa-solid fa-circle-info mr-1"></i>
        Unduhan ini tercatat sebagai peminjaman digital perpustakaan (masa pinjam <?= $JATUH_TEMPO_HARI ?> hari).
      </p>

      <div>
        <label class="block text-sm font-medium mb-1">NIM</label>
        <input type="text" name="nim" required maxlength="20" placeholder="Nomor Induk Mahasiswa"
               class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
        <input type="text" name="nama" required maxlength="100" placeholder="Sesuai kartu mahasiswa"
               class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600">
      </div>

      <button type="submit"
              class="w-full py-3 rounded-lg bg-red-600 hover:bg-red-500 text-white font-semibold transition">
        <i class="fa-solid fa-download mr-1"></i>Unduh PDF
      </button>
    </form>

    <p class="text-center text-xs text-gray-500 dark:text-gray-400 mt-4">
      Belum terdaftar sebagai anggota?
      <a href="daftar.php" class="text-green-700 dark:text-green-400 font-semibold hover:underline">Daftar di sini</a> — gratis, kurang dari 1 menit.
    </p>

    <a href="javascript:history.back()" class="block text-center text-sm text-gray-500 dark:text-gray-400 hover:text-green-700 dark:hover:text-green-400 mt-5">
      <i class="fa-solid fa-arrow-left mr-1"></i>Kembali
    </a>
  <?php endif; ?>

</div>
</body>
</html>
