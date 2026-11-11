<?php
/**
 * kelola_pengumuman.php — CRUD berita/acara/hari besar
 * Pustakawan: tambah, edit, toggle. Hapus: khusus admin.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/koneksi.php';

function detect_mime_type(string $tmpPath): string {
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $tmpPath) ?: '';
            finfo_close($finfo);
            if ($mime !== '') {
                return $mime;
            }
        }
    }
    if (function_exists('mime_content_type')) {
        return mime_content_type($tmpPath) ?: '';
    }
    return '';
}

$sukses = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = (string)($_POST['aksi'] ?? '');

    // Helper: proses upload foto -> kembalikan nama file / null / lempar error
    $prosesFoto = function (): ?string {
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
            return null; // foto opsional
        }
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload foto gagal (kode ' . (int)$_FILES['foto']['error'] . ').');
        }
        if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('Ukuran foto maksimal 5 MB.');
        }
        $ext  = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $mime = detect_mime_type($_FILES['foto']['tmp_name']);
        $mimeOk = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        if (!isset($mimeOk[$ext]) || $mimeOk[$ext] !== $mime) {
            throw new RuntimeException('Foto harus berupa JPG, PNG, atau WebP asli.');
        }
        $namaFile = 'foto_' . bin2hex(random_bytes(12)) . '.' . $ext;
        if (!is_dir(UPLOAD_DIR)) { @mkdir(UPLOAD_DIR, 0755, true); }
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], UPLOAD_DIR . $namaFile)) {
            throw new RuntimeException('Gagal menyimpan foto ke server.');
        }
        return $namaFile;
    };

    try {
        if ($aksi === 'tambah' || $aksi === 'edit') {
            $judul        = trim((string)$_POST['judul']);
            $isi          = trim((string)$_POST['isi']);
            $jenis        = (string)$_POST['jenis'];
            $tanggalAcara = trim((string)($_POST['tanggal_acara'] ?? ''));

            if ($judul === '' || $isi === '') {
                throw new RuntimeException('Judul dan isi wajib diisi.');
            }
            if (!in_array($jenis, ['berita', 'acara', 'hari_besar'], true)) {
                throw new RuntimeException('Jenis pengumuman tidak valid.');
            }
            // Tanggal hanya wajib untuk acara/hari besar
            $tgl = null;
            if ($jenis !== 'berita') {
                if ($tanggalAcara === '') {
                    throw new RuntimeException('Tanggal acara wajib diisi untuk jenis ini.');
                }
                $tgl = $tanggalAcara;
            }

            if ($aksi === 'tambah') {
                $foto = $prosesFoto();
                $pdo->prepare(
                    'INSERT INTO pengumuman (judul, isi, foto, jenis, tanggal_acara, dibuat_oleh)
                     VALUES (:j, :i, :f, :t, :d, :o)'
                )->execute([
                    'j' => $judul, 'i' => $isi, 'f' => $foto, 't' => $jenis,
                    'd' => $tgl, 'o' => $_SESSION['admin_id'],
                ]);
                $sukses = 'Pengumuman berhasil dipublikasikan.';
            } else {
                $id = (int)$_POST['id'];
                $fotoBaru = $prosesFoto();
                if ($fotoBaru !== null) {
                    // Hapus foto lama dari disk
                    $stmt = $pdo->prepare('SELECT foto FROM pengumuman WHERE id = :id');
                    $stmt->execute(['id' => $id]);
                    $lama = $stmt->fetchColumn();
                    if ($lama && is_file(UPLOAD_DIR . basename($lama))) {
                        @unlink(UPLOAD_DIR . basename($lama));
                    }
                    $pdo->prepare(
                        'UPDATE pengumuman SET judul = :j, isi = :i, foto = :f, jenis = :t, tanggal_acara = :d WHERE id = :id'
                    )->execute(['j' => $judul, 'i' => $isi, 'f' => $fotoBaru, 't' => $jenis, 'd' => $tgl, 'id' => $id]);
                } else {
                    $pdo->prepare(
                        'UPDATE pengumuman SET judul = :j, isi = :i, jenis = :t, tanggal_acara = :d WHERE id = :id'
                    )->execute(['j' => $judul, 'i' => $isi, 't' => $jenis, 'd' => $tgl, 'id' => $id]);
                }
                $sukses = 'Pengumuman diperbarui.';
            }
        }
        elseif ($aksi === 'toggle') {
            $pdo->prepare('UPDATE pengumuman SET status_aktif = 1 - status_aktif WHERE id = :id')
                ->execute(['id' => (int)$_POST['id']]);
            $sukses = 'Status pengumuman diperbarui.';
        }
        elseif ($aksi === 'hapus') {
            require_role('admin');   // hanya admin boleh menghapus
            // Hapus juga file fotonya dari disk
            $stmt = $pdo->prepare('SELECT foto FROM pengumuman WHERE id = :id');
            $stmt->execute(['id' => (int)$_POST['id']]);
            $foto = $stmt->fetchColumn();
            if ($foto && is_file(UPLOAD_DIR . basename($foto))) {
                @unlink(UPLOAD_DIR . basename($foto));
            }
            $pdo->prepare('DELETE FROM pengumuman WHERE id = :id')
                ->execute(['id' => (int)$_POST['id']]);
            $sukses = 'Pengumuman dihapus permanen.';
        }
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    } catch (Throwable $ex) {
        error_log('kelola_pengumuman: ' . $ex->getMessage());
        $error = 'Terjadi kesalahan sistem.';
    }
}

$list = $pdo->query(
    'SELECT p.*, a.nama AS penulis
     FROM pengumuman p
     LEFT JOIN admin_users a ON a.id = p.dibuat_oleh
     ORDER BY p.dibuat_pada DESC'
)->fetchAll();

$labelJenis = [
    'berita'     => 'Berita',
    'acara'      => 'Acara',
    'hari_besar' => 'Hari Besar',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Pengumuman — Dasbor Pustakawan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-green-900 text-white shadow">
  <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
    <div class="flex items-center gap-2 font-bold text-lg">
      <i class="fa-solid fa-bullhorn"></i><span>Kelola Pengumuman</span>
    </div>
    <div class="flex items-center gap-4 text-sm">
      <a href="pengumuman.php" target="_blank" class="text-green-200 hover:text-white transition"><i class="fa-solid fa-globe mr-1"></i>Lihat Portal</a>
      <a href="admin.php" class="text-green-200 hover:text-white transition"><i class="fa-solid fa-arrow-left mr-1"></i>Dasbor</a>
    </div>
  </div>
</header>

<main class="max-w-5xl mx-auto px-4 py-8 space-y-8">

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

  <!-- ================= FORM TAMBAH ================= -->
  <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="font-bold mb-4"><i class="fa-solid fa-plus mr-2 text-green-700"></i>Buat Pengumuman</h2>
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="tambah">

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Judul <span class="text-red-500">*</span></label>
          <input type="text" name="judul" required maxlength="200"
                 class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Jenis <span class="text-red-500">*</span></label>
          <select name="jenis" id="jenis" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-green-600">
            <option value="berita">Berita</option>
            <option value="acara">Acara</option>
            <option value="hari_besar">Hari Besar</option>
          </select>
        </div>
      </div>

      <div id="wrapTanggal" class="hidden">
        <label class="block text-sm font-medium mb-1">Tanggal Acara <span class="text-red-500">*</span></label>
        <input type="date" name="tanggal_acara" id="tanggal_acara"
               class="px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Isi Pengumuman <span class="text-red-500">*</span></label>
        <textarea name="isi" required rows="3"
                  class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600"></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Foto Dokumentasi <span class="text-gray-400 font-normal">(opsional, JPG/PNG/WebP maks 5 MB)</span></label>
        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp"
               class="w-full text-sm file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0
                      file:bg-green-800 file:text-white file:font-semibold hover:file:bg-green-700
                      border border-gray-300 rounded-lg">
      </div>

      <button class="px-6 py-2.5 rounded-lg bg-green-800 hover:bg-green-700 text-white font-semibold transition">
        <i class="fa-solid fa-paper-plane mr-1"></i>Publikasikan
      </button>
    </form>
  </section>

  <!-- ================= TABEL PENGUMUMAN ================= -->
  <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="font-bold mb-4"><i class="fa-solid fa-list mr-2 text-green-700"></i>Semua Pengumuman (<?= count($list) ?>)</h2>
    <div class="space-y-3">
      <?php foreach ($list as $p): ?>
      <div class="p-4 rounded-lg border border-gray-200 <?= $p['status_aktif'] ? '' : 'opacity-50' ?>">
        <?php if (isset($_GET['edit']) && (int)$_GET['edit'] === (int)$p['id']): ?>
          <!-- Form edit inline -->
          <form method="POST" enctype="multipart/form-data" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <input type="text" name="judul" value="<?= e($p['judul']) ?>" required maxlength="200"
                     class="md:col-span-2 px-3 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
              <select name="jenis" class="px-3 py-2 rounded border border-gray-300 bg-white">
                <?php foreach ($labelJenis as $k => $v): ?>
                <option value="<?= $k ?>" <?= $p['jenis'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <input type="date" name="tanggal_acara" value="<?= e((string)$p['tanggal_acara']) ?>"
                   class="px-3 py-2 rounded border border-gray-300">
            <textarea name="isi" required rows="2"
                      class="w-full px-3 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600"><?= e($p['isi']) ?></textarea>
            <div class="flex items-center gap-3">
              <input type="file" name="foto" accept="image/jpeg,image/png,image/webp"
                     class="text-xs file:mr-2 file:py-1.5 file:px-3 file:rounded file:border-0 file:bg-green-800 file:text-white">
              <?php if ($p['foto']): ?>
              <span class="text-xs text-gray-400">Foto saat ini: ada (pilih file baru untuk mengganti)</span>
              <?php endif; ?>
            </div>
            <div class="flex gap-2">
              <button class="px-4 py-2 rounded-lg bg-green-800 text-white text-sm font-semibold"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan</button>
              <a href="kelola_pengumuman.php" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-600 text-sm">Batal</a>
            </div>
          </form>
        <?php else: ?>
          <div class="flex flex-wrap items-start gap-3">
            <?php if ($p['foto']): ?>
            <img src="foto.php?f=<?= e($p['foto']) ?>" alt="" loading="lazy"
                 class="w-24 h-24 object-cover rounded-lg border border-gray-200 shrink-0">
            <?php endif; ?>
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800 font-medium"><?= $labelJenis[$p['jenis']] ?></span>
                <?php if ($p['tanggal_acara']): ?>
                <span class="text-xs text-gray-500"><i class="fa-regular fa-calendar mr-1"></i><?= e($p['tanggal_acara']) ?></span>
                <?php endif; ?>
                <span class="text-xs text-gray-400">
                  oleh <?= e($p['penulis'] ?? '-') ?> • <?= e($p['dibuat_pada']) ?>
                </span>
              </div>
              <p class="font-medium"><?= e($p['judul']) ?></p>
              <p class="text-sm text-gray-500 mt-1"><?= e($p['isi']) ?></p>
            </div>
            <div class="shrink-0 flex gap-1">
              <a href="kelola_pengumuman.php?edit=<?= (int)$p['id'] ?>" title="Edit"
                 class="px-2.5 py-1.5 rounded bg-blue-100 text-blue-700 hover:bg-blue-200"><i class="fa-solid fa-pen"></i></a>
              <form method="POST" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="toggle">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button title="<?= $p['status_aktif'] ? 'Sembunyikan' : 'Tampilkan' ?>"
                        class="px-2.5 py-1.5 rounded bg-gray-100 text-gray-600 hover:bg-gray-200">
                  <i class="fa-solid fa-toggle-<?= $p['status_aktif'] ? 'on text-green-600' : 'off' ?>"></i>
                </button>
              </form>
              <?php if (($_SESSION['admin_role'] ?? '') === 'admin'): ?>
              <form method="POST" class="inline" onsubmit="return confirm('Hapus pengumuman ini secara permanen?');">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button title="Hapus (khusus admin)" class="px-2.5 py-1.5 rounded bg-red-100 text-red-700 hover:bg-red-200"><i class="fa-solid fa-trash"></i></button>
              </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

</main>

<!-- Vanilla JS: kolom tanggal hanya muncul untuk acara/hari besar -->
<script>
const jenis = document.getElementById('jenis');
const wrap  = document.getElementById('wrapTanggal');
const tgl   = document.getElementById('tanggal_acara');
function syncTanggal() {
  const butuh = jenis.value !== 'berita';
  wrap.classList.toggle('hidden', !butuh);
  tgl.required = butuh;
}
jenis.addEventListener('change', syncTanggal);
syncTanggal();
</script>
</body>
</html>
