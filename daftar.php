<?php
/**
 * daftar.php — Pendaftaran anggota perpustakaan secara mandiri (publik, tanpa login)
 * Setelah terdaftar, pemustaka bisa mengunduh PDF lewat unduh.php.
 */
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/includes/helpers.php';

$sukses = false;
$error  = '';
$nimTerpakai = false;

$fakultasList = $pdo->query('SELECT nama_fakultas FROM kategori_fakultas ORDER BY nama_fakultas')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nim      = trim((string)($_POST['nim'] ?? ''));
    $nama     = trim((string)($_POST['nama'] ?? ''));
    $fakultas = trim((string)($_POST['fakultas'] ?? ''));
    $telepon  = trim((string)($_POST['telepon'] ?? ''));

    if ($nim === '' || $nama === '') {
        $error = 'NIM dan nama lengkap wajib diisi.';
    } elseif (!preg_match('/^[0-9]{6,20}$/', $nim)) {
        $error = 'NIM harus berupa angka 6–20 digit.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM anggota WHERE nim = :nim');
        $stmt->execute(['nim' => $nim]);

        if ($stmt->fetch()) {
            $nimTerpakai = true;
        } else {
            $pdo->prepare(
                'INSERT INTO anggota (nim, nama, fakultas, telepon, status_aktif)
                 VALUES (:nim, :nama, :fakultas, :telepon, 1)'
            )->execute([
                'nim'      => $nim,
                'nama'     => $nama,
                'fakultas' => ($fakultas !== '' ? $fakultas : null),
                'telepon'  => ($telepon !== '' ? $telepon : null),
            ]);
            $sukses = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pendaftaran Anggota — LibGuides Kampus</title>
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
<body class="bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100 min-h-screen flex items-center justify-center px-4 py-10">

<div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-8">

  <?php if ($sukses): ?>
    <!-- ================= SUKSES ================= -->
    <div class="text-center">
      <div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 flex items-center justify-center text-3xl mb-4">
        <i class="fa-solid fa-circle-check"></i>
      </div>
      <h1 class="text-xl font-bold mb-2">Pendaftaran Berhasil!</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
        Anda sekarang terdaftar sebagai anggota perpustakaan dan dapat mengunduh sumber digital kapan saja dengan NIM Anda.
      </p>
      <a href="index.php"
         class="inline-block px-6 py-3 rounded-lg bg-green-800 hover:bg-green-700 text-white font-semibold transition">
        <i class="fa-solid fa-magnifying-glass mr-1"></i>Mulai Mencari Katalog
      </a>
    </div>

  <?php else: ?>
    <!-- ================= FORM PENDAFTARAN ================= -->
    <div class="text-center mb-6">
      <div class="w-14 h-14 mx-auto rounded-full bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-400 flex items-center justify-center text-2xl mb-3">
        <i class="fa-solid fa-id-card"></i>
      </div>
      <h1 class="text-xl font-bold">Pendaftaran Anggota</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">Daftar sekali, unduh semua sumber digital perpustakaan.</p>
    </div>

    <?php if ($nimTerpakai): ?>
    <div class="mb-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300 text-sm">
      <i class="fa-solid fa-circle-info mr-1"></i>
      NIM ini <strong>sudah terdaftar</strong>. Anda bisa langsung mengunduh — tidak perlu mendaftar ulang.
    </div>
    <?php elseif ($error): ?>
    <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
      <i class="fa-solid fa-circle-exclamation mr-1"></i><?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="daftar.php" class="space-y-4">
      <?= csrf_field() ?>

      <div>
        <label class="block text-sm font-medium mb-1">NIM <span class="text-red-500">*</span></label>
        <input type="text" name="nim" required maxlength="20" inputmode="numeric"
               value="<?= e((string)($_POST['nim'] ?? '')) ?>"
               placeholder="Nomor Induk Mahasiswa"
               class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
        <input type="text" name="nama" required maxlength="100"
               value="<?= e((string)($_POST['nama'] ?? '')) ?>"
               placeholder="Sesuai kartu mahasiswa"
               class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Fakultas <span class="text-gray-400 font-normal">(opsional)</span></label>
        <select name="fakultas"
                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600">
          <option value="">— Pilih fakultas —</option>
          <?php foreach ($fakultasList as $f): ?>
          <option value="<?= e($f['nama_fakultas']) ?>"
                  <?= (($_POST['fakultas'] ?? '') === $f['nama_fakultas']) ? 'selected' : '' ?>>
            <?= e($f['nama_fakultas']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">No. WhatsApp <span class="text-gray-400 font-normal">(opsional)</span></label>
        <input type="text" name="telepon" maxlength="20"
               value="<?= e((string)($_POST['telepon'] ?? '')) ?>"
               placeholder="cth: 6281234567890"
               class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600">
      </div>

      <button type="submit"
              class="w-full py-3 rounded-lg bg-green-800 hover:bg-green-700 text-white font-semibold transition">
        <i class="fa-solid fa-user-plus mr-1"></i>Daftar Sekarang
      </button>
    </form>

    <a href="index.php" class="block text-center text-sm text-gray-500 dark:text-gray-400 hover:text-green-700 dark:hover:text-green-400 mt-5">
      <i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Beranda
    </a>
  <?php endif; ?>

</div>
</body>
</html>
