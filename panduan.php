<?php
/**
 * panduan.php — Daftar panduan per fakultas (?fakultas=X)
 *               atau detail satu panduan (?id=X)
 */
require_once __DIR__ . '/koneksi.php';

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$idPanduan  = (int)($_GET['id'] ?? 0);
$idFakultas = (int)($_GET['fakultas'] ?? 0);

$modeDetail = null;   // data panduan jika mode detail
$fakultas   = null;   // data fakultas jika mode daftar
$daftar     = [];     // daftar panduan / sumber

if ($idPanduan > 0) {
    // ---- MODE DETAIL PANDUAN ----
    $stmt = $pdo->prepare(
        'SELECT p.*, k.nama_fakultas, k.ikon_fontawesome, k.id AS fakultas_id
         FROM panduan_subjek p
         JOIN kategori_fakultas k ON k.id = p.id_fakultas
         WHERE p.id = :id'
    );
    $stmt->execute(['id' => $idPanduan]);
    $modeDetail = $stmt->fetch();

    if ($modeDetail) {
        // Tambah view counter
        $pdo->prepare('UPDATE panduan_subjek SET total_views = total_views + 1 WHERE id = :id')
            ->execute(['id' => $idPanduan]);
        $modeDetail['total_views']++;

        // Ambil semua sumber referensinya
        $stmt = $pdo->prepare(
            'SELECT * FROM sumber_referensi WHERE id_panduan = :id AND status_aktif = 1 ORDER BY jenis_sumber, judul_sumber'
        );
        $stmt->execute(['id' => $idPanduan]);
        $daftar = $stmt->fetchAll();
    }
} elseif ($idFakultas > 0) {
    // ---- MODE DAFTAR PER FAKULTAS ----
    $stmt = $pdo->prepare('SELECT * FROM kategori_fakultas WHERE id = :id');
    $stmt->execute(['id' => $idFakultas]);
    $fakultas = $stmt->fetch();

    if ($fakultas) {
        $stmt = $pdo->prepare(
            'SELECT * FROM panduan_subjek WHERE id_fakultas = :id ORDER BY total_views DESC'
        );
        $stmt->execute(['id' => $idFakultas]);
        $daftar = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>
  <?= $modeDetail ? e($modeDetail['judul']) . ' — ' : '' ?>
  <?= $fakultas ? e($fakultas['nama_fakultas']) . ' — ' : '' ?>
  LibGuides Kampus
</title>
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
<body class="bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100 min-h-screen transition-colors">

<header class="sticky top-0 z-40 bg-white/80 dark:bg-gray-800/80 backdrop-blur border-b border-gray-200 dark:border-gray-700">
  <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-2 font-bold text-lg text-green-800 dark:text-green-400">
      <i class="fa-solid fa-book-open-reader"></i>
      <span>LibGuides Kampus</span>
    </a>
    <a href="index.php" class="text-sm text-gray-500 dark:text-gray-400 hover:text-green-700 dark:hover:text-green-400">
      <i class="fa-solid fa-arrow-left mr-1"></i>Semua Fakultas
    </a>
  </div>
</header>

<main class="max-w-4xl mx-auto px-4 py-10">

<?php if ($modeDetail): ?>
  <!-- ============ MODE DETAIL PANDUAN ============ -->
  <nav class="text-sm text-gray-500 dark:text-gray-400 mb-4">
    <a href="index.php" class="hover:text-green-700 dark:hover:text-green-400">Beranda</a>
    <i class="fa-solid fa-chevron-right text-xs mx-1"></i>
    <a href="panduan.php?fakultas=<?= (int)$modeDetail['fakultas_id'] ?>" class="hover:text-green-700 dark:hover:text-green-400">
      <?= e($modeDetail['nama_fakultas']) ?>
    </a>
    <i class="fa-solid fa-chevron-right text-xs mx-1"></i>
    <span class="text-gray-800 dark:text-gray-200"><?= e($modeDetail['judul']) ?></span>
  </nav>

  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-6">
    <h1 class="text-2xl font-bold mb-1"><?= e($modeDetail['judul']) ?></h1>
    <p class="text-sm text-gray-500 dark:text-gray-400">
      <i class="<?= e($modeDetail['ikon_fontawesome']) ?> mr-1"></i><?= e($modeDetail['nama_fakultas']) ?>
      <span class="mx-2">•</span>
      <i class="fa-solid fa-eye mr-1"></i><?= number_format($modeDetail['total_views']) ?> kali diakses
    </p>
  </div>

  <h2 class="font-bold mb-3"><i class="fa-solid fa-bookmark mr-2 text-green-700 dark:text-green-400"></i>Sumber Referensi</h2>

  <?php if (!$daftar): ?>
    <p class="text-gray-500 dark:text-gray-400">Belum ada sumber referensi pada panduan ini.</p>
  <?php endif; ?>

  <div class="space-y-3">
    <?php foreach ($daftar as $s): ?>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 flex flex-wrap items-center gap-3">
      <div class="w-10 h-10 shrink-0 rounded-lg bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 flex items-center justify-center">
        <i class="fa-solid <?= $s['jenis_sumber'] === 'jurnal' ? 'fa-newspaper' : ($s['jenis_sumber'] === 'ebook' ? 'fa-tablet-screen-button' : ($s['jenis_sumber'] === 'database' ? 'fa-database' : 'fa-book')) ?>"></i>
      </div>
      <div class="flex-1 min-w-0">
        <p class="font-medium"><?= e($s['judul_sumber']) ?></p>
        <div class="flex flex-wrap gap-2 mt-1">
          <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200"><?= e($s['jenis_sumber']) ?></span>
          <?php if ($s['nomor_ddc']): ?>
          <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
            <i class="fa-solid fa-location-dot"></i> DDC <?= e($s['nomor_ddc']) ?>
          </span>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($s['file_pdf']): ?>
      <a href="unduh.php?id=<?= (int)$s['id'] ?>"
         class="shrink-0 px-4 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white text-sm font-medium transition">
        <i class="fa-solid fa-file-pdf mr-1"></i>Buka PDF
      </a>
      <?php elseif ($s['url_tautan']): ?>
      <a href="<?= e($s['url_tautan']) ?>" target="_blank" rel="noopener"
         class="shrink-0 px-4 py-2 rounded-lg bg-green-800 hover:bg-green-700 text-white text-sm font-medium transition">
        <i class="fa-solid fa-arrow-up-right-from-square mr-1"></i>Kunjungi
      </a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

<?php elseif ($fakultas): ?>
  <!-- ============ MODE DAFTAR PER FAKULTAS ============ -->
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-6 flex items-center gap-4">
    <div class="w-14 h-14 shrink-0 rounded-xl bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 flex items-center justify-center text-2xl">
      <i class="<?= e($fakultas['ikon_fontawesome']) ?>"></i>
    </div>
    <div>
      <h1 class="text-2xl font-bold"><?= e($fakultas['nama_fakultas']) ?></h1>
      <p class="text-sm text-gray-500 dark:text-gray-400"><?= count($daftar) ?> panduan tersedia</p>
    </div>
  </div>

  <?php if (!$daftar): ?>
    <p class="text-gray-500 dark:text-gray-400">Belum ada panduan pada fakultas ini.</p>
  <?php endif; ?>

  <div class="space-y-3">
    <?php foreach ($daftar as $g): ?>
    <a href="panduan.php?id=<?= (int)$g['id'] ?>"
       class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700
              hover:border-green-600 dark:hover:border-green-400 hover:-translate-y-0.5 transition">
      <i class="fa-solid fa-compass text-xl text-green-700 dark:text-green-400 shrink-0"></i>
      <span class="flex-1 font-medium"><?= e($g['judul']) ?></span>
      <span class="text-sm text-gray-500 dark:text-gray-400 shrink-0">
        <i class="fa-solid fa-eye mr-1"></i><?= number_format($g['total_views']) ?>
      </span>
    </a>
    <?php endforeach; ?>
  </div>

<?php else: ?>
  <!-- ============ PARAMETER TIDAK VALID ============ -->
  <div class="text-center py-16">
    <i class="fa-solid fa-triangle-exclamation text-4xl text-yellow-500 mb-4"></i>
    <p class="text-gray-500 dark:text-gray-400 mb-4">Halaman tidak ditemukan.</p>
    <a href="index.php" class="text-green-700 dark:text-green-400 hover:underline">Kembali ke Beranda</a>
  </div>
<?php endif; ?>

</main>
</body>
</html>
