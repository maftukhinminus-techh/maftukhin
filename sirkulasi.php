<?php
/**
 * sirkulasi.php — Modul Sirkulasi Digital: peminjaman aktif, pengembalian, riwayat
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/koneksi.php';

$sukses = '';
$error  = '';

// ---- Proses pengembalian ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (($_POST['aksi'] ?? '') === 'kembalikan') {
        $pdo->prepare(
            "UPDATE peminjaman SET status = 'kembali', tanggal_kembali = NOW() WHERE id = :id AND status = 'dipinjam'"
        )->execute(['id' => (int)$_POST['id']]);
        $sukses = 'Peminjaman ditandai selesai (dikembalikan).';
    }
}

// ---- Statistik ----
$sedangDipinjam = (int) $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam'")->fetchColumn();
$terlambat      = (int) $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam' AND jatuh_tempo < CURDATE()")->fetchColumn();
$totalRiwayat   = (int) $pdo->query('SELECT COUNT(*) FROM peminjaman')->fetchColumn();

// ---- Data tabel ----
$aktif = $pdo->query(
    "SELECT p.*, s.judul_sumber, a.nim, a.nama,
            DATEDIFF(p.jatuh_tempo, CURDATE()) AS sisa_hari
     FROM peminjaman p
     JOIN sumber_referensi s ON s.id = p.id_sumber
     JOIN anggota a ON a.id = p.id_anggota
     WHERE p.status = 'dipinjam'
     ORDER BY p.jatuh_tempo ASC"
)->fetchAll();

$riwayat = $pdo->query(
    "SELECT p.*, s.judul_sumber, a.nim, a.nama
     FROM peminjaman p
     JOIN sumber_referensi s ON s.id = p.id_sumber
     JOIN anggota a ON a.id = p.id_anggota
     ORDER BY p.tanggal_pinjam DESC
     LIMIT 50"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sirkulasi — Dasbor Pustakawan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-green-900 text-white shadow">
  <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
    <div class="flex items-center gap-2 font-bold text-lg">
      <i class="fa-solid fa-arrow-right-arrow-left"></i><span>Sirkulasi Digital</span>
    </div>
    <div class="flex items-center gap-4 text-sm">
      <a href="anggota.php" class="text-green-200 hover:text-white transition"><i class="fa-solid fa-users mr-1"></i>Anggota</a>
      <a href="admin.php" class="text-green-200 hover:text-white transition"><i class="fa-solid fa-arrow-left mr-1"></i>Dasbor</a>
    </div>
  </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-8 space-y-8">

  <?php if ($sukses): ?>
  <div class="p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">
    <i class="fa-solid fa-circle-check mr-1"></i><?= e($sukses) ?>
  </div>
  <?php endif; ?>

  <!-- ================= STATISTIK ================= -->
  <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="p-5 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
      <div class="w-12 h-12 rounded-lg bg-yellow-100 text-yellow-700 flex items-center justify-center text-xl">
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
    <div class="p-5 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
      <div class="w-12 h-12 rounded-lg bg-green-100 text-green-800 flex items-center justify-center text-xl">
        <i class="fa-solid fa-clock-rotate-left"></i>
      </div>
      <div>
        <p class="text-2xl font-extrabold"><?= number_format($totalRiwayat) ?></p>
        <p class="text-sm text-gray-500">Total Transaksi</p>
      </div>
    </div>
  </section>

  <!-- ================= PEMINJAMAN AKTIF ================= -->
  <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="font-bold mb-4"><i class="fa-solid fa-book-open mr-2 text-green-700"></i>Peminjaman Aktif (<?= count($aktif) ?>)</h2>
    <?php if (!$aktif): ?>
      <p class="text-sm text-gray-500">Tidak ada peminjaman aktif saat ini.</p>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b">
            <th class="py-2 pr-4 font-medium">Pemustaka</th>
            <th class="py-2 pr-4 font-medium">Sumber</th>
            <th class="py-2 pr-4 font-medium">Tgl Pinjam</th>
            <th class="py-2 pr-4 font-medium">Jatuh Tempo</th>
            <th class="py-2 font-medium text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($aktif as $p): ?>
          <tr class="<?= $p['sisa_hari'] < 0 ? 'bg-red-50' : '' ?>">
            <td class="py-3 pr-4">
              <span class="font-medium"><?= e($p['nama']) ?></span>
              <span class="block text-xs text-gray-500"><?= e($p['nim']) ?></span>
            </td>
            <td class="py-3 pr-4"><?= e($p['judul_sumber']) ?></td>
            <td class="py-3 pr-4 text-gray-500"><?= e($p['tanggal_pinjam']) ?></td>
            <td class="py-3 pr-4">
              <?php if ($p['sisa_hari'] < 0): ?>
                <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-700 font-semibold">
                  Terlambat <?= abs((int)$p['sisa_hari']) ?> hari
                </span>
              <?php else: ?>
                <span class="text-gray-600"><?= e($p['jatuh_tempo']) ?> (<?= (int)$p['sisa_hari'] ?> hari lagi)</span>
              <?php endif; ?>
            </td>
            <td class="py-3 text-right">
              <form method="POST" class="inline" onsubmit="return confirm('Tandai peminjaman ini selesai/dikembalikan?');">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="kembalikan">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="px-3 py-1.5 rounded-lg bg-green-800 hover:bg-green-700 text-white text-xs font-semibold">
                  <i class="fa-solid fa-rotate-left mr-1"></i>Kembalikan
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <!-- ================= RIWAYAT ================= -->
  <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="font-bold mb-4"><i class="fa-solid fa-clock-rotate-left mr-2 text-green-700"></i>Riwayat Transaksi (50 terbaru)</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b">
            <th class="py-2 pr-4 font-medium">Pemustaka</th>
            <th class="py-2 pr-4 font-medium">Sumber</th>
            <th class="py-2 pr-4 font-medium">Pinjam</th>
            <th class="py-2 pr-4 font-medium">Kembali</th>
            <th class="py-2 font-medium">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($riwayat as $p): ?>
          <tr>
            <td class="py-2.5 pr-4"><?= e($p['nama']) ?> <span class="text-xs text-gray-400">(<?= e($p['nim']) ?>)</span></td>
            <td class="py-2.5 pr-4"><?= e($p['judul_sumber']) ?></td>
            <td class="py-2.5 pr-4 text-gray-500"><?= e($p['tanggal_pinjam']) ?></td>
            <td class="py-2.5 pr-4 text-gray-500"><?= e((string)$p['tanggal_kembali']) ?></td>
            <td class="py-2.5">
              <span class="text-xs px-2 py-1 rounded-full <?= $p['status'] === 'dipinjam' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' ?>">
                <?= e($p['status']) ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

</main>
</body>
</html>
