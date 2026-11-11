<?php
/**
 * upload.php — form admin untuk mengunggah sumber referensi (PDF)
 */
require_once __DIR__ . '/auth.php';      // wajib login (memuat helpers + config)
require_once __DIR__ . '/koneksi.php';

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

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

$MAX_SIZE   = 10 * 1024 * 1024;          // maks 10 MB
$UPLOAD_DIR = UPLOAD_DIR;                // dari config.php

$panduanList = $pdo->query(
    'SELECT p.id, p.judul, k.nama_fakultas
     FROM panduan_subjek p
     JOIN kategori_fakultas k ON k.id = p.id_fakultas
     ORDER BY k.nama_fakultas, p.judul'
)->fetchAll();

$sukses = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $idPanduan   = (int)($_POST['id_panduan'] ?? 0);
    $judulSumber = trim((string)($_POST['judul_sumber'] ?? ''));
    $pengarang   = trim((string)($_POST['pengarang'] ?? ''));
    $penerbit    = trim((string)($_POST['penerbit'] ?? ''));
    $tahunTerbit = trim((string)($_POST['tahun_terbit'] ?? ''));
    $nomorDdc    = trim((string)($_POST['nomor_ddc'] ?? ''));

    // ---- Validasi input teks ----
    if ($idPanduan <= 0 || $judulSumber === '') {
        $error = 'Panduan dan judul sumber wajib diisi.';
    }
    // ---- Validasi file ter-upload ----
    elseif (!isset($_FILES['file_pdf']) || $_FILES['file_pdf']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Silakan pilih file PDF terlebih dahulu.';
    }
    elseif ($_FILES['file_pdf']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload gagal (kode error ' . (int)$_FILES['file_pdf']['error'] . ').';
    }
    elseif ($_FILES['file_pdf']['size'] > $MAX_SIZE) {
        $error = 'Ukuran file melebihi 10 MB.';
    }
    else {
        // ---- Validasi benar-benar PDF: ekstensi + MIME type asli ----
        $ext = strtolower(pathinfo($_FILES['file_pdf']['name'], PATHINFO_EXTENSION));
        $mime = detect_mime_type($_FILES['file_pdf']['tmp_name']);

        if ($ext !== 'pdf' || $mime !== 'application/pdf') {
            $error = 'File harus berupa PDF asli.';
        } else {
            // ---- Simpan dengan nama acak (mencegah timpa & injeksi path) ----
            if (!is_dir($UPLOAD_DIR)) {
                mkdir($UPLOAD_DIR, 0755, true);
            }
            $namaFile = bin2hex(random_bytes(16)) . '.pdf';

            if (!move_uploaded_file($_FILES['file_pdf']['tmp_name'], $UPLOAD_DIR . $namaFile)) {
                $error = 'Gagal menyimpan file ke server.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO sumber_referensi (id_panduan, judul_sumber, pengarang, penerbit, tahun_terbit, jenis_sumber, file_pdf, nomor_ddc)
                     VALUES (:p, :j, :pa, :pe, :t, :js, :f, :d)'
                );
                $stmt->execute([
                    'p'  => $idPanduan,
                    'j'  => $judulSumber,
                    'pa' => ($pengarang !== '' ? $pengarang : null),
                    'pe' => ($penerbit !== '' ? $penerbit : null),
                    't'  => ($tahunTerbit !== '' ? $tahunTerbit : null),
                    'js' => 'ebook',
                    'f'  => $namaFile,
                    'd'  => ($nomorDdc !== '' ? $nomorDdc : null),
                ]);
                $sukses = 'PDF berhasil diunggah dan ditambahkan ke panduan.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload PDF — Dasbor Pustakawan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-green-900 text-white shadow">
  <div class="max-w-3xl mx-auto px-4 h-16 flex items-center justify-between">
    <div class="flex items-center gap-2 font-bold text-lg">
      <i class="fa-solid fa-file-arrow-up"></i>
      <span>Upload Sumber PDF</span>
    </div>
    <a href="admin.php" class="text-sm text-green-200 hover:text-white transition">
      <i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Dasbor
    </a>
  </div>
</header>

<main class="max-w-3xl mx-auto px-4 py-8">

  <?php if ($sukses): ?>
  <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">
    <i class="fa-solid fa-circle-check mr-1"></i><?= e($sukses) ?>
  </div>
  <?php endif; ?>

  <?php if ($error): ?>
  <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700">
    <i class="fa-solid fa-circle-exclamation mr-1"></i><?= e($error) ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="upload.php" enctype="multipart/form-data"
        class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
    <?= csrf_field() ?>

    <div>
      <label class="block text-sm font-medium mb-1">Panduan Tujuan <span class="text-red-500">*</span></label>
      <select name="id_panduan" required
              class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600 bg-white">
        <option value="">— Pilih panduan —</option>
        <?php foreach ($panduanList as $p): ?>
        <option value="<?= (int)$p['id'] ?>">
          <?= e($p['nama_fakultas']) ?> — <?= e($p['judul']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Judul Sumber <span class="text-red-500">*</span></label>
      <input type="text" name="judul_sumber" required maxlength="200"
             placeholder="cth: Jurnal Manajemen Keuangan Vol. 12"
             class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Pengarang <span class="text-gray-400 font-normal">(opsional)</span></label>
        <input type="text" name="pengarang" maxlength="150"
               class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Penerbit <span class="text-gray-400 font-normal">(opsional)</span></label>
        <input type="text" name="penerbit" maxlength="100"
               class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Tahun Terbit <span class="text-gray-400 font-normal">(opsional)</span></label>
        <input type="text" name="tahun_terbit" maxlength="4" placeholder="cth: 2023"
               class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Nomor DDC <span class="text-gray-400 font-normal">(opsional)</span></label>
      <input type="text" name="nomor_ddc" maxlength="20"
             placeholder="cth: 658.15"
             class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">File PDF <span class="text-red-500">*</span></label>
      <input type="file" name="file_pdf" accept="application/pdf,.pdf" required
             class="w-full text-sm file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0
                    file:bg-green-800 file:text-white file:font-semibold hover:file:bg-green-700
                    border border-gray-300 rounded-lg">
      <p class="text-xs text-gray-400 mt-1">Hanya PDF, maksimal 10 MB.</p>
    </div>

    <button type="submit"
            class="w-full py-3 rounded-lg bg-green-800 hover:bg-green-700 text-white font-semibold transition">
      <i class="fa-solid fa-cloud-arrow-up mr-1"></i>Unggah Sekarang
    </button>
  </form>

</main>
</body>
</html>
