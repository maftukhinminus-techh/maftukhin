<?php
/**
 * index.php — Portal Publik Mahasiswa (alternatif LibGuides)
 */
require_once __DIR__ . '/koneksi.php';

// Helper escaping output (mencegah XSS)
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ============================================================
// 1. PROSES FORM PENCARIAN (POST) -> rekam ke log_pencarian
//    Pola Post/Redirect/Get agar refresh tidak mengirim ulang form
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keyword = trim((string)($_POST['keyword'] ?? ''));
    if ($keyword !== '') {
        $stmt = $pdo->prepare('INSERT INTO log_pencarian (keyword) VALUES (:kw)');
        $stmt->execute(['kw' => $keyword]);
        header('Location: index.php?q=' . urlencode($keyword));
        exit;
    }
    header('Location: index.php');
    exit;
}

// ============================================================
// 2. DATA UNTUK KPI CARDS
// ============================================================
$totalPanduan    = (int) $pdo->query('SELECT COUNT(*) FROM panduan_subjek')->fetchColumn();
$totalSumber     = (int) $pdo->query('SELECT COUNT(*) FROM sumber_referensi')->fetchColumn();
$pustakawanAktif = (int) $pdo->query('SELECT COUNT(*) FROM pustakawan WHERE status_aktif = 1')->fetchColumn();

// ============================================================
// 3. KATEGORI FAKULTAS (GRID)
// ============================================================
$fakultas = $pdo->query('SELECT * FROM kategori_fakultas ORDER BY nama_fakultas ASC')->fetchAll();

// ============================================================
// 4. TRENDING GUIDES (views terbanyak)
// ============================================================
$trending = $pdo->query(
    'SELECT p.id, p.judul, p.total_views, k.nama_fakultas
     FROM panduan_subjek p
     JOIN kategori_fakultas k ON k.id = p.id_fakultas
     ORDER BY p.total_views DESC
     LIMIT 6'
)->fetchAll();

// ============================================================
// 5. SIMULASI HASIL PENCARIAN (parameter GET setelah redirect)
//    Mencari di judul panduan, judul sumber, dan nomor DDC
// ============================================================
$q = trim((string)($_GET['q'] ?? ''));
$hasilPanduan = [];
$hasilSumber  = [];
$totalSumber  = 0;

// Sorting: whitelist nilai agar ORDER BY aman dari injection
$sortWhitelist = ['relevan' => '', 'terbaru' => 's.dibuat_pada DESC', 'ddc' => 's.nomor_ddc ASC'];
$sort = (string)($_GET['sort'] ?? 'relevan');
if (!array_key_exists($sort, $sortWhitelist)) $sort = 'relevan';
$orderBy = $sortWhitelist[$sort] !== '' ? 'ORDER BY ' . $sortWhitelist[$sort] : '';

// Pagination
$perPage   = 10;
$page      = max(1, (int)($_GET['page'] ?? 1));
$offset    = ($page - 1) * $perPage;
$totalPage = 1;

if ($q !== '') {
    $like = '%' . $q . '%';

    $stmt = $pdo->prepare(
        'SELECT p.id, p.judul, k.nama_fakultas
         FROM panduan_subjek p
         JOIN kategori_fakultas k ON k.id = p.id_fakultas
         WHERE p.judul LIKE :q
         ORDER BY p.total_views DESC
         LIMIT 10'
    );
    $stmt->execute(['q' => $like]);
    $hasilPanduan = $stmt->fetchAll();

    // Hitung total (untuk pagination), hanya sumber AKTIF
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM sumber_referensi
         WHERE status_aktif = 1 AND (judul_sumber LIKE :q1 OR nomor_ddc LIKE :q2)'
    );
    $stmt->execute(['q1' => $like, 'q2' => $like]);
    $totalSumber = (int)$stmt->fetchColumn();
    $totalPage   = max(1, (int)ceil($totalSumber / $perPage));
    if ($page > $totalPage) { $page = $totalPage; $offset = ($page - 1) * $perPage; }

    // LIMIT/OFFSET wajib integer -> bind PARAM_INT
    $stmt = $pdo->prepare(
        "SELECT s.id, s.id_panduan, s.judul_sumber, s.jenis_sumber, s.nomor_ddc, s.url_tautan, s.file_pdf
         FROM sumber_referensi s
         WHERE s.status_aktif = 1 AND (s.judul_sumber LIKE :q1 OR s.nomor_ddc LIKE :q2)
         $orderBy
         LIMIT :lim OFFSET :off"
    );
    $stmt->bindValue(':q1', $like);
    $stmt->bindValue(':q2', $like);
    $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $hasilSumber = $stmt->fetchAll();
}

// Helper URL pencarian (mempertahankan q + sort saat pindah halaman)
function searchUrl(string $q, string $sort, int $page): string {
    return 'index.php?q=' . urlencode($q) . '&sort=' . urlencode($sort) . '&page=' . $page;
}

// ============================================================
// 5b. PENGUMUMAN TERBARU (berita/acara/hari besar, untuk beranda)
// ============================================================
$pengumumanTerbaru = $pdo->query(
    "SELECT judul, isi, foto, jenis, tanggal_acara, dibuat_pada
     FROM pengumuman WHERE status_aktif = 1
     ORDER BY dibuat_pada DESC LIMIT 3"
)->fetchAll();

// ============================================================
// 6. PUSTAKAWAN AKTIF UNTUK WIDGET WHATSAPP
// ============================================================
$wa = $pdo->query(
    'SELECT nama, whatsapp FROM pustakawan WHERE status_aktif = 1 ORDER BY id ASC LIMIT 1'
)->fetch();
$waLink = $wa
    ? 'https://wa.me/' . $wa['whatsapp'] . '?text=' . rawurlencode('Halo ' . $wa['nama'] . ', saya ingin bertanya tentang referensi perpustakaan.')
    : '#';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LibGuides Kampus — Portal Panduan Perpustakaan</title>

<!-- Terapkan tema tersimpan SEBELUM render agar tidak berkedip -->
<script>
  if (localStorage.getItem('theme') === 'dark' ||
      (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
  }
</script>

<!-- Tailwind CSS (CDN) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class' };</script>

<!-- Font Awesome (CDN) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>

<body class="bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-100 min-h-screen transition-colors">

<!-- ================= HEADER ================= -->
<header class="sticky top-0 z-40 bg-white/80 dark:bg-gray-800/80 backdrop-blur border-b border-gray-200 dark:border-gray-700">
  <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-2 font-bold text-lg text-green-800 dark:text-green-400">
      <i class="fa-solid fa-book-open-reader"></i>
      <span>LibGuides Kampus</span>
    </a>

    <div class="flex items-center gap-2">
      <a href="daftar.php" title="Daftar Anggota"
         class="h-10 px-3 rounded-full flex items-center gap-1.5 text-sm font-medium border border-gray-300 dark:border-gray-600
                hover:bg-gray-100 dark:hover:bg-gray-700 transition">
        <i class="fa-solid fa-id-card"></i><span class="hidden sm:inline">Daftar Anggota</span>
      </a>
      <a href="login.php" title="Login Pustakawan"
         class="w-10 h-10 rounded-full flex items-center justify-center border border-gray-300 dark:border-gray-600
                hover:bg-gray-100 dark:hover:bg-gray-700 transition">
        <i class="fa-solid fa-user-tie"></i>
      </a>
      <!-- Toggle Dark/Light Mode -->
      <button id="themeToggle" type="button" aria-label="Ganti tema"
        class="w-10 h-10 rounded-full flex items-center justify-center border border-gray-300 dark:border-gray-600
               hover:bg-gray-100 dark:hover:bg-gray-700 transition">
        <i class="fa-solid fa-moon dark:hidden"></i>
        <i class="fa-solid fa-sun hidden dark:inline text-yellow-400"></i>
      </button>
    </div>
  </div>
</header>

<!-- ================= HERO + SEARCH ================= -->
<section class="bg-green-800 dark:bg-gray-800 text-white">
  <div class="max-w-6xl mx-auto px-4 py-16 text-center">
    <h1 class="text-3xl md:text-4xl font-extrabold mb-3">Temukan Panduan &amp; Referensi Anda</h1>
    <p class="text-green-100 dark:text-gray-300 mb-8">Panduan subjek per fakultas, jurnal, ebook, dan klasifikasi DDC koleksi fisik.</p>

    <form method="POST" action="index.php" class="max-w-2xl mx-auto">
      <div class="flex rounded-full overflow-hidden shadow-lg border-4 border-white/20">
        <input type="text" name="keyword" required
               value="<?= e($q) ?>"
               placeholder="Cari jurnal, DDC buku, atau panduan topik..."
               class="flex-1 px-6 py-4 text-gray-800 dark:text-gray-100 dark:bg-gray-900 focus:outline-none">
        <button type="submit"
                class="bg-yellow-500 hover:bg-yellow-400 text-green-900 font-semibold px-6 md:px-8 transition">
          <i class="fa-solid fa-magnifying-glass"></i>
          <span class="hidden md:inline ml-1">Cari</span>
        </button>
      </div>
    </form>
  </div>
</section>

<main class="max-w-6xl mx-auto px-4 py-10 space-y-14">

  <!-- ================= HASIL PENCARIAN (muncul setelah submit) ================= -->
  <?php if ($q !== ''): ?>
  <section>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <h2 class="text-xl font-bold">
        Hasil pencarian: <span class="text-green-700 dark:text-green-400">&ldquo;<?= e($q) ?>&rdquo;</span>
      </h2>
      <!-- Sorting -->
      <form method="GET" action="index.php" class="flex items-center gap-2 text-sm">
        <input type="hidden" name="q" value="<?= e($q) ?>">
        <label class="text-gray-500 dark:text-gray-400">Urutkan:</label>
        <select name="sort" onchange="this.form.submit()"
                class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus:outline-none">
          <option value="relevan" <?= $sort === 'relevan' ? 'selected' : '' ?>>Paling Relevan</option>
          <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Paling Baru</option>
          <option value="ddc" <?= $sort === 'ddc' ? 'selected' : '' ?>>DDC Terendah</option>
        </select>
      </form>
    </div>

    <?php if (!$hasilPanduan && !$hasilSumber): ?>
      <p class="text-gray-500 dark:text-gray-400">Tidak ditemukan hasil yang cocok. Coba kata kunci lain atau tanya pustakawan.</p>
    <?php endif; ?>

    <?php if ($hasilPanduan): ?>
      <h3 class="font-semibold mt-4 mb-2"><i class="fa-solid fa-compass mr-1"></i> Panduan Subjek</h3>
      <ul class="space-y-2">
        <?php foreach ($hasilPanduan as $r): ?>
        <li>
          <a href="panduan.php?id=<?= (int)$r['id'] ?>"
             class="block p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700
                    hover:border-green-600 dark:hover:border-green-400 transition">
            <span class="font-medium text-green-700 dark:text-green-400"><?= e($r['judul']) ?></span>
            <span class="block text-sm text-gray-500 dark:text-gray-400"><?= e($r['nama_fakultas']) ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($hasilSumber): ?>
      <h3 class="font-semibold mt-6 mb-2"><i class="fa-solid fa-bookmark mr-1"></i> Sumber Referensi</h3>
      <ul class="space-y-2">
        <?php foreach ($hasilSumber as $r): ?>
        <?php
          // Tentukan tautan: PDF lewat pintu unduh (sirkulasi), lalu URL eksternal, terakhir halaman panduan
          if (!empty($r['file_pdf'])) {
              $href   = 'unduh.php?id=' . (int)$r['id'];
              $ikon   = 'fa-solid fa-file-pdf';
              $target = '';
          } elseif (!empty($r['url_tautan'])) {
              $href   = $r['url_tautan'];
              $ikon   = 'fa-solid fa-arrow-up-right-from-square';
              $target = ' target="_blank" rel="noopener"';
          } else {
              $href   = 'panduan.php?id=' . (int)$r['id_panduan'];
              $ikon   = 'fa-solid fa-compass';
              $target = '';
          }
        ?>
        <li>
          <a href="<?= e($href) ?>"<?= $target ?>
             class="flex flex-wrap items-center gap-2 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm
                    border border-gray-100 dark:border-gray-700 hover:border-green-600 dark:hover:border-green-400 transition">
            <span class="font-medium text-green-700 dark:text-green-400">
              <i class="<?= $ikon ?> mr-1"></i><?= e($r['judul_sumber']) ?>
            </span>
            <span class="text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200"><?= e($r['jenis_sumber']) ?></span>
            <?php if ($r['nomor_ddc']): ?>
              <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                <i class="fa-solid fa-location-dot"></i> DDC <?= e($r['nomor_ddc']) ?>
              </span>
            <?php endif; ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>

      <!-- Pagination sumber -->
      <?php if ($totalPage > 1): ?>
      <div class="flex items-center justify-center gap-3 mt-4 text-sm">
        <?php if ($page > 1): ?>
        <a href="<?= searchUrl($q, $sort, $page - 1) ?>"
           class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
          <i class="fa-solid fa-chevron-left mr-1"></i>Sebelumnya
        </a>
        <?php endif; ?>
        <span class="text-gray-500 dark:text-gray-400">Halaman <?= $page ?> dari <?= $totalPage ?></span>
        <?php if ($page < $totalPage): ?>
        <a href="<?= searchUrl($q, $sort, $page + 1) ?>"
           class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
          Berikutnya<i class="fa-solid fa-chevron-right ml-1"></i>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <!-- ================= KPI CARDS ================= -->
  <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 text-center">
      <i class="fa-solid fa-compass text-3xl text-green-700 dark:text-green-400 mb-2"></i>
      <p class="text-3xl font-extrabold"><?= number_format($totalPanduan) ?></p>
      <p class="text-sm text-gray-500 dark:text-gray-400">Total Panduan</p>
    </div>
    <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 text-center">
      <i class="fa-solid fa-layer-group text-3xl text-green-700 dark:text-green-400 mb-2"></i>
      <p class="text-3xl font-extrabold"><?= number_format($totalSumber) ?></p>
      <p class="text-sm text-gray-500 dark:text-gray-400">Total Sumber Daya</p>
    </div>
    <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 text-center">
      <i class="fa-solid fa-user-tie text-3xl text-green-700 dark:text-green-400 mb-2"></i>
      <p class="text-3xl font-extrabold"><?= number_format($pustakawanAktif) ?></p>
      <p class="text-sm text-gray-500 dark:text-gray-400">Pustakawan Aktif</p>
    </div>
  </section>

  <!-- ================= PENGUMUMAN TERBARU ================= -->
  <?php if ($pengumumanTerbaru): ?>
  <section>
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-bold"><i class="fa-solid fa-bullhorn mr-2 text-green-700 dark:text-green-400"></i>Pengumuman</h2>
      <a href="pengumuman.php" class="text-sm text-green-700 dark:text-green-400 hover:underline">Lihat semua <i class="fa-solid fa-arrow-right ml-1"></i></a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <?php foreach ($pengumumanTerbaru as $pg): ?>
      <a href="pengumuman.php"
         class="overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700
                hover:border-green-600 dark:hover:border-green-400 hover:-translate-y-1 transition">
        <?php if (!empty($pg['foto'])): ?>
        <img src="foto.php?f=<?= e($pg['foto']) ?>" alt="" loading="lazy" class="w-full h-32 object-cover">
        <?php endif; ?>
        <div class="p-5">
        <span class="text-xs px-2 py-0.5 rounded-full font-medium
          <?= $pg['jenis'] === 'acara' ? 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300'
            : ($pg['jenis'] === 'hari_besar' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300'
            : 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300') ?>">
          <?= $pg['jenis'] === 'acara' ? 'Acara' : ($pg['jenis'] === 'hari_besar' ? 'Hari Besar' : 'Berita') ?>
        </span>
        <h3 class="font-semibold mt-2 mb-1 leading-snug"><?= e($pg['judul']) ?></h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2"><?= e(mb_strimwidth($pg['isi'], 0, 110, '…')) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ================= KATEGORI FAKULTAS ================= -->
  <section>
    <h2 class="text-xl font-bold mb-4"><i class="fa-solid fa-building-columns mr-2 text-green-700 dark:text-green-400"></i>Jelajahi per Fakultas</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
      <?php foreach ($fakultas as $f): ?>
      <a href="panduan.php?fakultas=<?= (int)$f['id'] ?>" class="group p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700
                        hover:border-green-600 dark:hover:border-green-400 hover:-translate-y-1 transition text-center">
        <i class="<?= e($f['ikon_fontawesome']) ?> text-3xl text-green-700 dark:text-green-400 mb-3 block"></i>
        <span class="text-sm font-medium leading-snug block"><?= e($f['nama_fakultas']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ================= TRENDING GUIDES ================= -->
  <section>
    <h2 class="text-xl font-bold mb-4"><i class="fa-solid fa-fire mr-2 text-orange-500"></i>Trending Guides</h2>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
      <?php foreach ($trending as $i => $g): ?>
      <a href="panduan.php?id=<?= (int)$g['id'] ?>" class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
        <span class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center font-bold
                     <?= $i < 3 ? 'bg-yellow-400 text-green-900' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300' ?>">
          <?= $i + 1 ?>
        </span>
        <div class="flex-1 min-w-0">
          <p class="font-medium truncate"><?= e($g['judul']) ?></p>
          <p class="text-sm text-gray-500 dark:text-gray-400 truncate"><?= e($g['nama_fakultas']) ?></p>
        </div>
        <span class="text-sm text-gray-500 dark:text-gray-400 shrink-0">
          <i class="fa-solid fa-eye mr-1"></i><?= number_format($g['total_views']) ?>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

</main>

<!-- ================= FOOTER ================= -->
<footer class="border-t border-gray-200 dark:border-gray-700 mt-8">
  <div class="max-w-6xl mx-auto px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
    &copy; <?= date('Y') ?> Perpustakaan Kampus — Sistem LibGuides Custom
    <span class="mx-2">•</span>
    <a href="tentang.php" class="hover:text-green-700 dark:hover:text-green-400 hover:underline">Tentang &amp; Cara Pakai</a>
  </div>
</footer>

<!-- ================= FLOATING WIDGET: ASK A LIBRARIAN ================= -->
<a href="<?= e($waLink) ?>" target="_blank" rel="noopener"
   class="group fixed bottom-6 right-6 z-50 flex items-center gap-2 bg-green-500 hover:bg-green-600
          text-white rounded-full shadow-lg px-4 py-3 transition">
  <i class="fa-brands fa-whatsapp text-2xl"></i>
  <span class="max-w-0 overflow-hidden group-hover:max-w-xs transition-all duration-300 whitespace-nowrap text-sm font-medium">
    Ask a Librarian
  </span>
</a>

<!-- ================= VANILLA JS: TOGGLE TEMA ================= -->
<script>
document.getElementById('themeToggle').addEventListener('click', function () {
  var isDark = document.documentElement.classList.toggle('dark');
  localStorage.setItem('theme', isDark ? 'dark' : 'light');
});
</script>
</body>
</html>
