<?php
/**
 * kelola_panduan.php — CRUD panduan subjek + kelola sumber referensinya
 * Aksi: tambah, edit, hapus panduan; tambah link (non-PDF), toggle, hapus sumber.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/koneksi.php';

$UPLOAD_DIR = UPLOAD_DIR;
$sukses = '';
$error  = '';

// ============================================================
// PROSES SEMUA AKSI POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = (string)($_POST['aksi'] ?? '');

    try {
        // ---------- TAMBAH PANDUAN ----------
        if ($aksi === 'tambah_panduan') {
            $judul      = trim((string)$_POST['judul']);
            $idFakultas = (int)$_POST['id_fakultas'];
            if ($judul === '' || $idFakultas <= 0) {
                throw new RuntimeException('Judul dan fakultas wajib diisi.');
            }
            $pdo->prepare('INSERT INTO panduan_subjek (judul, id_fakultas) VALUES (:j, :f)')
                ->execute(['j' => $judul, 'f' => $idFakultas]);
            $sukses = 'Panduan baru berhasil ditambahkan.';
        }

        // ---------- EDIT PANDUAN ----------
        elseif ($aksi === 'edit_panduan') {
            $id         = (int)$_POST['id'];
            $judul      = trim((string)$_POST['judul']);
            $idFakultas = (int)$_POST['id_fakultas'];
            if ($id <= 0 || $judul === '' || $idFakultas <= 0) {
                throw new RuntimeException('Data tidak lengkap.');
            }
            $pdo->prepare('UPDATE panduan_subjek SET judul = :j, id_fakultas = :f WHERE id = :id')
                ->execute(['j' => $judul, 'f' => $idFakultas, 'id' => $id]);
            $sukses = 'Panduan berhasil diperbarui.';
        }

        // ---------- HAPUS PANDUAN (+ hapus file PDF di disk) — khusus admin ----------
        elseif ($aksi === 'hapus_panduan') {
            require_role('admin');
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare('SELECT file_pdf FROM sumber_referensi WHERE id_panduan = :id AND file_pdf IS NOT NULL');
            $stmt->execute(['id' => $id]);
            foreach ($stmt->fetchAll() as $row) {
                $path = $UPLOAD_DIR . basename($row['file_pdf']);
                if (is_file($path)) @unlink($path);
            }
            // Baris sumber ikut terhapus otomatis (ON DELETE CASCADE)
            $pdo->prepare('DELETE FROM panduan_subjek WHERE id = :id')->execute(['id' => $id]);
            $sukses = 'Panduan beserta seluruh sumbernya berhasil dihapus.';
        }

        // ---------- TAMBAH SUMBER LINK (non-PDF) ----------
        elseif ($aksi === 'tambah_sumber') {
            $idPanduan   = (int)$_POST['id_panduan'];
            $judulSumber = trim((string)$_POST['judul_sumber']);
            $jenis       = (string)$_POST['jenis_sumber'];
            $url         = trim((string)($_POST['url_tautan'] ?? ''));
            $ddc         = trim((string)($_POST['nomor_ddc'] ?? ''));

            $jenisValid = ['buku', 'jurnal', 'ebook', 'database', 'website'];
            if ($idPanduan <= 0 || $judulSumber === '') {
                throw new RuntimeException('Panduan dan judul sumber wajib diisi.');
            }
            if (!in_array($jenis, $jenisValid, true)) {
                throw new RuntimeException('Jenis sumber tidak valid.');
            }
            if ($url !== '' && !preg_match('#^https?://#i', $url)) {
                throw new RuntimeException('URL harus diawali http:// atau https://');
            }
            $pdo->prepare(
                'INSERT INTO sumber_referensi (id_panduan, judul_sumber, jenis_sumber, url_tautan, nomor_ddc)
                 VALUES (:p, :j, :t, :u, :d)'
            )->execute([
                'p' => $idPanduan, 'j' => $judulSumber, 't' => $jenis,
                'u' => ($url !== '' ? $url : null),
                'd' => ($ddc !== '' ? $ddc : null),
            ]);
            $sukses = 'Sumber referensi berhasil ditambahkan.';
        }

        // ---------- TOGGLE STATUS SUMBER ----------
        elseif ($aksi === 'toggle_sumber') {
            $id = (int)$_POST['id'];
            $pdo->prepare('UPDATE sumber_referensi SET status_aktif = 1 - status_aktif WHERE id = :id')
                ->execute(['id' => $id]);
            $sukses = 'Status sumber diperbarui.';
        }

        // ---------- HAPUS SUMBER (+ hapus file PDF) — khusus admin ----------
        elseif ($aksi === 'hapus_sumber') {
            require_role('admin');
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare('SELECT file_pdf FROM sumber_referensi WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $pdf = $stmt->fetchColumn();
            if ($pdf) {
                $path = $UPLOAD_DIR . basename($pdf);
                if (is_file($path)) @unlink($path);
            }
            $pdo->prepare('DELETE FROM sumber_referensi WHERE id = :id')->execute(['id' => $id]);
            $sukses = 'Sumber referensi berhasil dihapus.';
        }
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    } catch (Throwable $ex) {
        error_log('kelola_panduan: ' . $ex->getMessage());
        $error = 'Terjadi kesalahan sistem.';
    }
}

// ============================================================
// DATA TAMPILAN
// ============================================================
$fakultasList = $pdo->query('SELECT * FROM kategori_fakultas ORDER BY nama_fakultas')->fetchAll();

$panduanList = $pdo->query(
    'SELECT p.*, k.nama_fakultas,
            (SELECT COUNT(*) FROM sumber_referensi s WHERE s.id_panduan = p.id) AS jumlah_sumber
     FROM panduan_subjek p
     JOIN kategori_fakultas k ON k.id = p.id_fakultas
     ORDER BY k.nama_fakultas, p.judul'
)->fetchAll();

$sumberList = [];
if (isset($_GET['kelola'])) {
    $stmt = $pdo->prepare('SELECT * FROM sumber_referensi WHERE id_panduan = :id ORDER BY jenis_sumber, judul_sumber');
    $stmt->execute(['id' => (int)$_GET['kelola']]);
    $sumberList = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Panduan — Dasbor Pustakawan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-green-900 text-white shadow">
  <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
    <div class="flex items-center gap-2 font-bold text-lg">
      <i class="fa-solid fa-compass"></i><span>Kelola Panduan</span>
    </div>
    <div class="flex items-center gap-4 text-sm">
      <a href="upload.php" class="text-green-200 hover:text-white transition"><i class="fa-solid fa-file-arrow-up mr-1"></i>Upload PDF</a>
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

  <!-- ================= FORM TAMBAH PANDUAN ================= -->
  <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="font-bold mb-4"><i class="fa-solid fa-plus mr-2 text-green-700"></i>Tambah Panduan Baru</h2>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="tambah_panduan">
      <input type="text" name="judul" required maxlength="150" placeholder="Judul panduan"
             class="px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
      <select name="id_fakultas" required
              class="px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600 bg-white">
        <option value="">— Pilih fakultas —</option>
        <?php foreach ($fakultasList as $f): ?>
        <option value="<?= (int)$f['id'] ?>"><?= e($f['nama_fakultas']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="py-2.5 rounded-lg bg-green-800 hover:bg-green-700 text-white font-semibold transition">
        <i class="fa-solid fa-plus mr-1"></i>Tambah
      </button>
    </form>
  </section>

  <!-- ================= TABEL PANDUAN ================= -->
  <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="font-bold mb-4"><i class="fa-solid fa-list mr-2 text-green-700"></i>Daftar Panduan (<?= count($panduanList) ?>)</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b">
            <th class="py-2 pr-4 font-medium">Judul</th>
            <th class="py-2 pr-4 font-medium">Fakultas</th>
            <th class="py-2 pr-4 font-medium text-center">Views</th>
            <th class="py-2 pr-4 font-medium text-center">Sumber</th>
            <th class="py-2 font-medium text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($panduanList as $p): ?>
          <tr class="align-top">
            <td class="py-3 pr-4">
              <?php if (isset($_GET['edit']) && (int)$_GET['edit'] === (int)$p['id']): ?>
                <!-- Form edit inline -->
                <form method="POST" class="flex flex-wrap gap-2">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="edit_panduan">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <input type="text" name="judul" value="<?= e($p['judul']) ?>" required maxlength="150"
                         class="flex-1 min-w-[180px] px-3 py-1.5 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
                  <select name="id_fakultas" class="px-3 py-1.5 rounded border border-gray-300 bg-white">
                    <?php foreach ($fakultasList as $f): ?>
                    <option value="<?= (int)$f['id'] ?>" <?= (int)$f['id'] === (int)$p['id_fakultas'] ? 'selected' : '' ?>>
                      <?= e($f['nama_fakultas']) ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                  <button class="px-3 py-1.5 rounded bg-green-800 text-white"><i class="fa-solid fa-floppy-disk"></i></button>
                  <a href="kelola_panduan.php" class="px-3 py-1.5 rounded bg-gray-200 text-gray-600"><i class="fa-solid fa-xmark"></i></a>
                </form>
              <?php else: ?>
                <span class="font-medium"><?= e($p['judul']) ?></span>
              <?php endif; ?>
            </td>
            <td class="py-3 pr-4 text-gray-500"><?= e($p['nama_fakultas']) ?></td>
            <td class="py-3 pr-4 text-center"><?= number_format($p['total_views']) ?></td>
            <td class="py-3 pr-4 text-center"><?= (int)$p['jumlah_sumber'] ?></td>
            <td class="py-3 text-right whitespace-nowrap">
              <a href="kelola_panduan.php?edit=<?= (int)$p['id'] ?>" title="Edit"
                 class="inline-block px-2.5 py-1.5 rounded bg-blue-100 text-blue-700 hover:bg-blue-200"><i class="fa-solid fa-pen"></i></a>
              <a href="kelola_panduan.php?kelola=<?= (int)$p['id'] ?>" title="Kelola sumber"
                 class="inline-block px-2.5 py-1.5 rounded bg-yellow-100 text-yellow-700 hover:bg-yellow-200"><i class="fa-solid fa-bookmark"></i></a>
              <?php if (($_SESSION['admin_role'] ?? '') === 'admin'): ?>
              <form method="POST" class="inline" onsubmit="return confirm('Hapus panduan ini beserta semua sumbernya? File PDF ikut terhapus.');">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="hapus_panduan">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button title="Hapus (khusus admin)" class="px-2.5 py-1.5 rounded bg-red-100 text-red-700 hover:bg-red-200"><i class="fa-solid fa-trash"></i></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>

          <!-- ===== Panel kelola sumber (muncul di bawah baris terkait) ===== -->
          <?php if (isset($_GET['kelola']) && (int)$_GET['kelola'] === (int)$p['id']): ?>
          <tr>
            <td colspan="5" class="py-4 bg-gray-50 rounded-lg">
              <div class="px-4">
                <h3 class="font-semibold mb-3"><i class="fa-solid fa-bookmark mr-1 text-yellow-600"></i>Sumber pada: <?= e($p['judul']) ?></h3>

                <!-- Form tambah sumber (link / non-PDF) -->
                <form method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-2 mb-4 p-3 bg-white rounded-lg border border-gray-200">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="tambah_sumber">
                  <input type="hidden" name="id_panduan" value="<?= (int)$p['id'] ?>">
                  <input type="text" name="judul_sumber" required maxlength="200" placeholder="Judul sumber"
                         class="md:col-span-2 px-3 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
                  <select name="jenis_sumber" class="px-3 py-2 rounded border border-gray-300 bg-white">
                    <option value="buku">buku</option>
                    <option value="jurnal">jurnal</option>
                    <option value="ebook">ebook</option>
                    <option value="database">database</option>
                    <option value="website">website</option>
                  </select>
                  <input type="url" name="url_tautan" placeholder="https://... (opsional)"
                         class="px-3 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
                  <input type="text" name="nomor_ddc" maxlength="20" placeholder="DDC (opsional)"
                         class="px-3 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
                  <button class="py-2 rounded bg-green-800 hover:bg-green-700 text-white font-semibold"><i class="fa-solid fa-plus"></i></button>
                </form>

                <?php if (!$sumberList): ?>
                  <p class="text-sm text-gray-500">Belum ada sumber. Tambahkan lewat form di atas, atau upload PDF lewat menu Upload PDF.</p>
                <?php else: ?>
                <table class="w-full text-sm bg-white rounded-lg border border-gray-200">
                  <tbody class="divide-y divide-gray-100">
                    <?php foreach ($sumberList as $s): ?>
                    <tr>
                      <td class="py-2.5 px-3">
                        <span class="font-medium"><?= e($s['judul_sumber']) ?></span>
                        <div class="flex flex-wrap gap-1.5 mt-1">
                          <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800"><?= e($s['jenis_sumber']) ?></span>
                          <?php if ($s['file_pdf']): ?>
                          <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700"><i class="fa-solid fa-file-pdf"></i> PDF</span>
                          <?php endif; ?>
                          <?php if ($s['nomor_ddc']): ?>
                          <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800">DDC <?= e($s['nomor_ddc']) ?></span>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="py-2.5 px-3 text-center whitespace-nowrap">
                        <span class="text-xs px-2 py-1 rounded-full <?= $s['status_aktif'] ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500' ?>">
                          <?= $s['status_aktif'] ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                      </td>
                      <td class="py-2.5 px-3 text-right whitespace-nowrap">
                        <form method="POST" class="inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="aksi" value="toggle_sumber">
                          <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                          <button title="<?= $s['status_aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                  class="px-2.5 py-1.5 rounded bg-gray-100 text-gray-600 hover:bg-gray-200">
                            <i class="fa-solid fa-toggle-<?= $s['status_aktif'] ? 'on text-green-600' : 'off' ?>"></i>
                          </button>
                        </form>
                        <?php if (($_SESSION['admin_role'] ?? '') === 'admin'): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Hapus sumber ini?');">
                          <?= csrf_field() ?>
                          <input type="hidden" name="aksi" value="hapus_sumber">
                          <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                          <button title="Hapus (khusus admin)" class="px-2.5 py-1.5 rounded bg-red-100 text-red-700 hover:bg-red-200"><i class="fa-solid fa-trash"></i></button>
                        </form>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

</main>
</body>
</html>
