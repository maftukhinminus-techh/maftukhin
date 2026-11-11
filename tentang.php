<?php
/**
 * tentang.php — halaman statis: cara pakai portal
 */
require_once __DIR__ . '/koneksi.php';
$wa = $pdo->query('SELECT nama, whatsapp FROM pustakawan WHERE status_aktif = 1 ORDER BY id ASC LIMIT 1')->fetch();
$waLink = $wa ? 'https://wa.me/' . $wa['whatsapp'] : '#';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tentang & Cara Pakai — LibGuides Kampus</title>
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

<main class="max-w-4xl mx-auto px-4 py-10 space-y-10">

  <section class="text-center">
    <h1 class="text-3xl font-extrabold mb-2">Tentang LibGuides Kampus</h1>
    <p class="text-gray-500 dark:text-gray-400 max-w-2xl mx-auto">
      Portal ini adalah panduan perpustakaan digital: kumpulan panduan subjek per fakultas,
      referensi jurnal &amp; ebook, serta penanda lokasi koleksi fisik memakai klasifikasi DDC.
    </p>
  </section>

  <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
    <h2 class="font-bold text-lg mb-4"><i class="fa-solid fa-magnifying-glass mr-2 text-green-700 dark:text-green-400"></i>Cara Mencari</h2>
    <ul class="space-y-3 text-sm">
      <li class="flex gap-3"><i class="fa-solid fa-keyboard mt-0.5 text-green-700 dark:text-green-400"></i>
        <span><strong>Kata kunci topik</strong> — mis. <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700">manajemen keuangan</code> untuk menemukan panduan dan jurnal terkait.</span></li>
      <li class="flex gap-3"><i class="fa-solid fa-location-dot mt-0.5 text-green-700 dark:text-green-400"></i>
        <span><strong>Nomor DDC</strong> — mis. <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700">658.15</code> untuk menemukan referensi dari koleksi fisik di rak.</span></li>
      <li class="flex gap-3"><i class="fa-solid fa-building-columns mt-0.5 text-green-700 dark:text-green-400"></i>
        <span><strong>Ikon fakultas</strong> — klik untuk melihat seluruh panduan yang dikurasi khusus fakultas Anda.</span></li>
    </ul>
  </section>

  <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
    <h2 class="font-bold text-lg mb-4"><i class="fa-solid fa-book mr-2 text-green-700 dark:text-green-400"></i>Tentang Nomor DDC</h2>
    <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
      DDC (Dewey Decimal Classification) adalah sistem nomor untuk mengelompokkan buku berdasarkan subjek.
      Nomor ini tertempel di punggung buku fisik perpustakaan — catat nomornya dari hasil pencarian,
      lalu temukan rak yang sesuai.
    </p>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
      <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50"><strong>000</strong> Karya Umum</div>
      <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50"><strong>300</strong> Ilmu Sosial</div>
      <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50"><strong>600</strong> Teknologi</div>
      <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50"><strong>610</strong> Kedokteran</div>
    </div>
  </section>

  <section class="bg-green-800 dark:bg-green-900 text-white rounded-xl shadow-sm p-6 flex flex-wrap items-center gap-4">
    <div class="flex-1 min-w-[220px]">
      <h2 class="font-bold text-lg mb-1"><i class="fa-brands fa-whatsapp mr-2"></i>Butuh Bantuan Pustakawan?</h2>
      <p class="text-sm text-green-100">Tanya langsung via WhatsApp pada jam layanan perpustakaan.</p>
    </div>
    <a href="<?= e($waLink) ?>" target="_blank" rel="noopener"
       class="px-5 py-3 rounded-full bg-white text-green-800 font-semibold hover:bg-green-50 transition">
      <i class="fa-brands fa-whatsapp mr-1"></i>Ask a Librarian
    </a>
  </section>

</main>
</body>
</html>
