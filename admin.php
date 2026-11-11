<?php
/**
 * admin.php — Dasbor Analitik Pustakawan (wajib login)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/koneksi.php';

// Backup database otomatis 1x per hari (tersimpan di folder backup/)
auto_backup();

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ============================================================
// DATA GRAFIK 1: 5 panduan paling sering diakses (Bar Chart)
// ============================================================
$topGuides = $pdo->query(
    'SELECT judul, total_views FROM panduan_subjek ORDER BY total_views DESC LIMIT 5'
)->fetchAll();

// ============================================================
// DATA GRAFIK 2: 5 keyword terbanyak (Doughnut Chart)
// ============================================================
$topKeywords = $pdo->query(
    'SELECT keyword, COUNT(*) AS total
     FROM log_pencarian
     GROUP BY keyword
     ORDER BY total DESC, keyword ASC
     LIMIT 5'
)->fetchAll();

// ============================================================
// DATA PENDUKUNG: ringkasan + aktivitas pencarian terbaru
// ============================================================
$totalPencarian = (int) $pdo->query('SELECT COUNT(*) FROM log_pencarian')->fetchColumn();
$totalViews     = (int) $pdo->query('SELECT COALESCE(SUM(total_views),0) FROM panduan_subjek')->fetchColumn();
$sedangDipinjam = (int) $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam'")->fetchColumn();
$terlambat      = (int) $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam' AND jatuh_tempo < CURDATE()")->fetchColumn();
$terbaru = $pdo->query(
    'SELECT keyword, waktu_pencarian FROM log_pencarian ORDER BY waktu_pencarian DESC LIMIT 10'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dasbor Pustakawan — Analitik LibGuides</title>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/style.css">
<!-- Chart.js (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">

<!-- ================= TOPBAR ================= -->
<header class="bg-green-900 text-white shadow">
  <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
    <div class="flex items-center gap-2 font-bold text-lg">
      <i class="fa-solid fa-chart-pie"></i>
      <span>Dasbor Pustakawan</span>
    </div>
    <div class="flex items-center gap-4 text-sm">
      <span class="text-green-300">
        <i class="fa-solid fa-circle-user mr-1"></i><?= e($_SESSION['admin_nama'] ?? 'Admin') ?>
        <span class="text-xs px-1.5 py-0.5 rounded bg-green-800"><?= e($_SESSION['admin_role'] ?? '') ?></span>
      </span>
      <a href="kelola_panduan.php" class="text-green-200 hover:text-white transition">
        <i class="fa-solid fa-compass mr-1"></i>Kelola Panduan
      </a>
      <a href="kelola_pengumuman.php" class="text-green-200 hover:text-white transition">
        <i class="fa-solid fa-bullhorn mr-1"></i>Pengumuman
      </a>
      <a href="anggota.php" class="text-green-200 hover:text-white transition">
        <i class="fa-solid fa-users mr-1"></i>Anggota
      </a>
      <a href="sirkulasi.php" class="text-green-200 hover:text-white transition">
        <i class="fa-solid fa-arrow-right-arrow-left mr-1"></i>Sirkulasi
      </a>
      <?php if (($_SESSION['admin_role'] ?? '') === 'admin'): ?>
      <a href="kelola_pengguna.php" class="text-green-200 hover:text-white transition">
        <i class="fa-solid fa-user-gear mr-1"></i>Pengguna
      </a>
      <?php endif; ?>
      <a href="upload.php" class="px-3 py-1.5 rounded-lg bg-yellow-400 text-green-900 font-semibold hover:bg-yellow-300 transition">
        <i class="fa-solid fa-file-arrow-up mr-1"></i>Upload PDF
      </a>
      <a href="ganti_password.php" class="text-green-200 hover:text-white transition">
        <i class="fa-solid fa-key mr-1"></i>Ganti Password
      </a>
      <a href="index.php" class="text-green-200 hover:text-white transition">
        <i class="fa-solid fa-globe mr-1"></i>Portal Publik
      </a>
      <a href="logout.php" class="text-green-200 hover:text-white transition">
        <i class="fa-solid fa-right-from-bracket mr-1"></i>Keluar
      </a>
    </div>
  </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-8 space-y-8">

  <!-- ================= RINGKASAN ================= -->
  <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="p-5 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
      <div class="w-12 h-12 rounded-lg bg-green-100 text-green-800 flex items-center justify-center text-xl">
        <i class="fa-solid fa-eye"></i>
      </div>
      <div>
        <p class="text-2xl font-extrabold"><?= number_format($totalViews) ?></p>
        <p class="text-sm text-gray-500">Total Views Panduan</p>
      </div>
    </div>
    <div class="p-5 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
      <div class="w-12 h-12 rounded-lg bg-yellow-100 text-yellow-700 flex items-center justify-center text-xl">
        <i class="fa-solid fa-magnifying-glass"></i>
      </div>
      <div>
        <p class="text-2xl font-extrabold"><?= number_format($totalPencarian) ?></p>
        <p class="text-sm text-gray-500">Total Pencarian</p>
      </div>
    </div>
    <div class="p-5 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
      <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xl">
        <i class="fa-solid fa-book-open-reader"></i>
      </div>
      <div>
        <p class="text-2xl font-extrabold"><?= number_format($sedangDipinjam) ?></p>
        <p class="text-sm text-gray-500">Sedang Dipinjam</p>
      </div>
    </div>
    <div class="p-5 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
      <div class="w-12 h-12 rounded-lg bg-red-100 text-red-700 flex items-center justify-center text-xl">
        <i class="fa-solid fa-clock"></i>
      </div>
      <div>
        <p class="text-2xl font-extrabold"><?= number_format($terlambat) ?></p>
        <p class="text-sm text-gray-500">Lewat Jatuh Tempo</p>
      </div>
    </div>
  </section>

  <!-- ================= GRAFIK ================= -->
  <section class="grid grid-cols-1 lg:grid-cols-5 gap-6">

    <!-- Bar Chart: Trending Guides -->
    <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <h2 class="font-bold mb-1"><i class="fa-solid fa-fire text-orange-500 mr-2"></i>5 Panduan Paling Sering Diakses</h2>
      <p class="text-sm text-gray-500 mb-4">Berdasarkan total_views pada tabel panduan_subjek.</p>
      <div class="relative h-72">
        <canvas id="chartGuides"></canvas>
      </div>
    </div>

    <!-- Doughnut Chart: Keyword Terbanyak -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <h2 class="font-bold mb-1"><i class="fa-solid fa-key text-green-700 mr-2"></i>5 Kata Kunci Terbanyak</h2>
      <p class="text-sm text-gray-500 mb-4">Berdasarkan tabel log_pencarian.</p>
      <div class="relative h-72">
        <canvas id="chartKeywords"></canvas>
      </div>
    </div>
  </section>

  <!-- ================= PENCARIAN TERBARU ================= -->
  <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <h2 class="font-bold"><i class="fa-solid fa-clock-rotate-left text-green-700 mr-2"></i>Aktivitas Pencarian Terbaru</h2>
      <!-- Export laporan CSV -->
      <div class="flex items-center gap-2 text-sm">
        <a href="export.php?jenis=pencarian&tahun=<?= date('Y') ?>&bulan=<?= date('n') ?>"
           class="px-3 py-1.5 rounded-lg bg-green-800 text-white hover:bg-green-700 transition">
          <i class="fa-solid fa-file-csv mr-1"></i>Pencarian Bulan Ini
        </a>
        <a href="export.php?jenis=panduan"
           class="px-3 py-1.5 rounded-lg border border-green-800 text-green-800 hover:bg-green-50 transition">
          <i class="fa-solid fa-file-csv mr-1"></i>Semua Panduan
        </a>
        <a href="export.php?jenis=sirkulasi"
           class="px-3 py-1.5 rounded-lg border border-green-800 text-green-800 hover:bg-green-50 transition">
          <i class="fa-solid fa-file-csv mr-1"></i>Sirkulasi
        </a>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b">
            <th class="py-2 pr-4 font-medium">#</th>
            <th class="py-2 pr-4 font-medium">Kata Kunci</th>
            <th class="py-2 font-medium">Waktu Pencarian</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($terbaru as $i => $row): ?>
          <tr>
            <td class="py-2 pr-4 text-gray-400"><?= $i + 1 ?></td>
            <td class="py-2 pr-4 font-medium"><?= e($row['keyword']) ?></td>
            <td class="py-2 text-gray-500"><?= e($row['waktu_pencarian']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

</main>

<!-- ================= VANILLA JS + CHART.JS ================= -->
<script>
// Data dari PHP (aman: json_encode menghasilkan literal JS valid)
const topGuides   = <?= json_encode($topGuides, JSON_UNESCAPED_UNICODE) ?>;
const topKeywords = <?= json_encode($topKeywords, JSON_UNESCAPED_UNICODE) ?>;

// ---- Bar Chart: 5 panduan terpopuler (horizontal agar judul panjang terbaca) ----
new Chart(document.getElementById('chartGuides'), {
  type: 'bar',
  data: {
    labels: topGuides.map(g => g.judul),
    datasets: [{
      label: 'Total Views',
      data: topGuides.map(g => Number(g.total_views)),
      backgroundColor: '#166534',
      borderRadius: 6
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

// ---- Doughnut Chart: 5 keyword terbanyak ----
new Chart(document.getElementById('chartKeywords'), {
  type: 'doughnut',
  data: {
    labels: topKeywords.map(k => k.keyword),
    datasets: [{
      data: topKeywords.map(k => Number(k.total)),
      backgroundColor: ['#166534', '#16a34a', '#84cc16', '#facc15', '#0d9488'],
      borderWidth: 2,
      borderColor: '#ffffff'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '60%',
    plugins: { legend: { position: 'bottom' } }
  }
});
</script>
</body>
</html>
