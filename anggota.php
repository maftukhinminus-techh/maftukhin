<?php
/**
 * anggota.php — CRUD data anggota perpustakaan (Modul Manajemen Anggota)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/koneksi.php';

$sukses = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = (string)($_POST['aksi'] ?? '');

    try {
        if ($aksi === 'tambah' || $aksi === 'edit') {
            $nim      = trim((string)$_POST['nim']);
            $nama     = trim((string)$_POST['nama']);
            $fakultas = trim((string)($_POST['fakultas'] ?? ''));
            $telepon  = trim((string)($_POST['telepon'] ?? ''));

            if ($nim === '' || $nama === '') {
                throw new RuntimeException('NIM dan nama wajib diisi.');
            }

            if ($aksi === 'tambah') {
                $stmt = $pdo->prepare(
                    'INSERT INTO anggota (nim, nama, fakultas, telepon) VALUES (:n, :m, :f, :t)'
                );
                $stmt->execute(['n' => $nim, 'm' => $nama, 'f' => ($fakultas ?: null), 't' => ($telepon ?: null)]);
                $sukses = 'Anggota berhasil ditambahkan.';
            } else {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare(
                    'UPDATE anggota SET nim = :n, nama = :m, fakultas = :f, telepon = :t WHERE id = :id'
                );
                $stmt->execute(['n' => $nim, 'm' => $nama, 'f' => ($fakultas ?: null), 't' => ($telepon ?: null), 'id' => $id]);
                $sukses = 'Data anggota diperbarui.';
            }
        }
        elseif ($aksi === 'toggle') {
            $pdo->prepare('UPDATE anggota SET status_aktif = 1 - status_aktif WHERE id = :id')
                ->execute(['id' => (int)$_POST['id']]);
            $sukses = 'Status anggota diperbarui.';
        }
        elseif ($aksi === 'hapus') {
            $pdo->prepare('DELETE FROM anggota WHERE id = :id')
                ->execute(['id' => (int)$_POST['id']]);
            $sukses = 'Anggota dihapus.';
        }
    } catch (PDOException $ex) {
        $msg = $ex->getMessage();
        $isDuplicate = (strpos($msg, 'Duplicate') !== false || strpos($msg, '1062') !== false);
        $error = $isDuplicate ? 'NIM sudah terdaftar.' : 'Kesalahan database.';
        error_log('anggota.php: ' . $msg);
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    }
}

// Pencarian anggota
$cari = trim((string)($_GET['cari'] ?? ''));
if ($cari !== '') {
    $stmt = $pdo->prepare(
        'SELECT * FROM anggota WHERE nim LIKE :c OR nama LIKE :c ORDER BY nama LIMIT 100'
    );
    $stmt->execute(['c' => '%' . $cari . '%']);
    $anggotaList = $stmt->fetchAll();
} else {
    $anggotaList = $pdo->query('SELECT * FROM anggota ORDER BY nama LIMIT 100')->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Anggota — Dasbor Pustakawan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-green-900 text-white shadow">
  <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
    <div class="flex items-center gap-2 font-bold text-lg">
      <i class="fa-solid fa-users"></i><span>Data Anggota</span>
    </div>
    <div class="flex items-center gap-4 text-sm">
      <a href="sirkulasi.php" class="text-green-200 hover:text-white transition"><i class="fa-solid fa-arrow-right-arrow-left mr-1"></i>Sirkulasi</a>
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
  <?php if ($error): ?>
  <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">
    <i class="fa-solid fa-circle-exclamation mr-1"></i><?= e($error) ?>
  </div>
  <?php endif; ?>

  <!-- ================= FORM TAMBAH ANGGOTA ================= -->
  <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="font-bold mb-4"><i class="fa-solid fa-user-plus mr-2 text-green-700"></i>Tambah Anggota</h2>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="tambah">
      <input type="text" name="nim" required maxlength="20" placeholder="NIM"
             class="px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
      <input type="text" name="nama" required maxlength="100" placeholder="Nama lengkap"
             class="px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
      <input type="text" name="fakultas" maxlength="100" placeholder="Fakultas (opsional)"
             class="px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
      <input type="text" name="telepon" maxlength="20" placeholder="Telepon (opsional)"
             class="px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
      <button class="py-2.5 rounded-lg bg-green-800 hover:bg-green-700 text-white font-semibold transition">
        <i class="fa-solid fa-plus mr-1"></i>Tambah
      </button>
    </form>
  </section>

  <!-- ================= TABEL ANGGOTA ================= -->
  <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <h2 class="font-bold"><i class="fa-solid fa-list mr-2 text-green-700"></i>Daftar Anggota (<?= count($anggotaList) ?>)</h2>
      <form method="GET" class="flex gap-2 text-sm">
        <input type="text" name="cari" value="<?= e($cari) ?>" placeholder="Cari NIM / nama..."
               class="px-3 py-1.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
        <button class="px-3 py-1.5 rounded-lg bg-green-800 text-white"><i class="fa-solid fa-magnifying-glass"></i></button>
      </form>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b">
            <th class="py-2 pr-4 font-medium">NIM</th>
            <th class="py-2 pr-4 font-medium">Nama</th>
            <th class="py-2 pr-4 font-medium">Fakultas</th>
            <th class="py-2 pr-4 font-medium">Telepon</th>
            <th class="py-2 pr-4 font-medium text-center">Status</th>
            <th class="py-2 font-medium text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($anggotaList as $a): ?>
          <tr class="align-top">
            <td class="py-3 pr-4 font-medium"><?= e($a['nim']) ?></td>
            <td class="py-3 pr-4">
              <?php if (isset($_GET['edit']) && (int)$_GET['edit'] === (int)$a['id']): ?>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-2">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="edit">
                  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                  <input type="text" name="nim" value="<?= e($a['nim']) ?>" required class="px-3 py-1.5 rounded border border-gray-300">
                  <input type="text" name="nama" value="<?= e($a['nama']) ?>" required class="px-3 py-1.5 rounded border border-gray-300">
                  <input type="text" name="fakultas" value="<?= e((string)$a['fakultas']) ?>" class="px-3 py-1.5 rounded border border-gray-300">
                  <div class="flex gap-1">
                    <input type="text" name="telepon" value="<?= e((string)$a['telepon']) ?>" class="flex-1 px-3 py-1.5 rounded border border-gray-300">
                    <button class="px-3 py-1.5 rounded bg-green-800 text-white"><i class="fa-solid fa-floppy-disk"></i></button>
                  </div>
                </form>
              <?php else: ?>
                <?= e($a['nama']) ?>
              <?php endif; ?>
            </td>
            <td class="py-3 pr-4 text-gray-500"><?= e((string)$a['fakultas']) ?></td>
            <td class="py-3 pr-4 text-gray-500"><?= e((string)$a['telepon']) ?></td>
            <td class="py-3 pr-4 text-center">
              <span class="text-xs px-2 py-1 rounded-full <?= $a['status_aktif'] ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500' ?>">
                <?= $a['status_aktif'] ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </td>
            <td class="py-3 text-right whitespace-nowrap">
              <a href="anggota.php?edit=<?= (int)$a['id'] ?>" title="Edit"
                 class="inline-block px-2.5 py-1.5 rounded bg-blue-100 text-blue-700 hover:bg-blue-200"><i class="fa-solid fa-pen"></i></a>
              <form method="POST" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="toggle">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button title="<?= $a['status_aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>"
                        class="px-2.5 py-1.5 rounded bg-gray-100 text-gray-600 hover:bg-gray-200">
                  <i class="fa-solid fa-toggle-<?= $a['status_aktif'] ? 'on text-green-600' : 'off' ?>"></i>
                </button>
              </form>
              <?php if (($_SESSION['admin_role'] ?? '') === 'admin'): ?>
              <form method="POST" class="inline" onsubmit="return confirm('Hapus anggota ini beserta riwayat peminjamannya?');">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button title="Hapus (khusus admin)" class="px-2.5 py-1.5 rounded bg-red-100 text-red-700 hover:bg-red-200"><i class="fa-solid fa-trash"></i></button>
              </form>
              <?php endif; ?>
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
