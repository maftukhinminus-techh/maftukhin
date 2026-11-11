<?php
/**
 * pengumuman.php — Portal publik: Berita, Acara & Hari Besar
 * ?jenis=berita|acara|hari_besar untuk memfilter
 */
require_once __DIR__ . '/koneksi.php';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$jenisValid = ['semua', 'berita', 'acara', 'hari_besar'];
$jenis = (string)($_GET['jenis'] ?? 'semua');
if (!in_array($jenis, $jenisValid, true)) $jenis = 'semua';

if ($jenis === 'semua') {
    $list = $pdo->query(
        "SELECT * FROM pengumuman WHERE status_aktif = 1
         ORDER BY COALESCE(tanggal_acara, dibuat_pada) DESC, dibuat_pada DESC"
    )->fetchAll();
} else {
    $stmt = $pdo->prepare(
        "SELECT * FROM pengumuman WHERE status_aktif = 1 AND jenis = :j
         ORDER BY COALESCE(tanggal_acara, dibuat_pada) DESC, dibuat_pada DESC"
    );
    $stmt->execute(['j' => $jenis]);
    $list = $stmt->fetchAll();
}

$labelJenis = [
    'berita'     => ['Berita', 'fa-newspaper', 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'],
    'acara'      => ['Acara', 'fa-calendar-days', 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'],
    'hari_besar' => ['Hari Besar', 'fa-star', 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'],
];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Berita & Acara — LibGuides Kampus</title>
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
      <i class="fa-solid fa-book-open-reader"></i><span>LibGuides Kampus</span>
    </a>
    <a href="index.php" class="text-sm text-gray-500 dark:text-gray-400 hover:text-green-700 dark:hover:text-green-400">
      <i class="fa-solid fa-arrow-left mr-1"></i>Beranda
    </a>
  </div>
</header>

<main class="max-w-4xl mx-auto px-4 py-10 space-y-8">

  <section class="text-center">
    <h1 class="text-3xl font-extrabold mb-2">Berita, Acara &amp; Hari Besar</h1>
    <p class="text-gray-500 dark:text-gray-400">Kabar terbaru dan agenda dari perpustakaan kampus.</p>
  </section>

  <!-- Filter jenis -->
  <nav class="flex flex-wrap justify-center gap-2 text-sm">
    <?php
    $tabs = ['semua' => 'Semua', 'berita' => 'Berita', 'acara' => 'Acara', 'hari_besar' => 'Hari Besar'];
    foreach ($tabs as $key => $label):
        $aktif = $jenis === $key;
    ?>
    <a href="pengumuman.php?jenis=<?= $key ?>"
       class="px-4 py-2 rounded-full border transition <?= $aktif
           ? 'bg-green-800 text-white border-green-800'
           : 'border-gray-300 dark:border-gray-600 hover:border-green-600 dark:hover:border-green-400' ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <?php if (!$list): ?>
    <p class="text-center text-gray-500 dark:text-gray-400 py-10">Belum ada pengumuman pada kategori ini.</p>
  <?php endif; ?>

  <section class="space-y-4">
    <?php foreach ($list as $p): $meta = $labelJenis[$p['jenis']]; ?>
    <article class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
      <?php if (!empty($p['foto'])): ?>
      <img src="foto.php?f=<?= e($p['foto']) ?>" alt="Dokumentasi <?= e($p['judul']) ?>" loading="lazy"
           class="w-full h-52 object-cover">
      <?php endif; ?>
      <div class="p-6">
      <div class="flex flex-wrap items-center gap-2 mb-2">
        <span class="text-xs px-2.5 py-1 rounded-full font-medium <?= $meta[2] ?>">
          <i class="fa-solid <?= $meta[1] ?> mr-1"></i><?= $meta[0] ?>
        </span>
        <?php if ($p['tanggal_acara']): ?>
        <span class="text-xs text-gray-500 dark:text-gray-400">
          <i class="fa-regular fa-calendar mr-1"></i><?= date('d M Y', strtotime($p['tanggal_acara'])) ?>
        </span>
        <?php endif; ?>
        <span class="text-xs text-gray-400 dark:text-gray-500 ml-auto">
          Dipublikasikan <?= date('d M Y', strtotime($p['dibuat_pada'])) ?>
        </span>
      </div>
      <h2 class="text-lg font-bold mb-1"><?= e($p['judul']) ?></h2>
      <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed"><?= nl2br(e($p['isi'])) ?></p>
      </div>
    </article>
    <?php endforeach; ?>
  </section>

</main>
</body>
</html>
