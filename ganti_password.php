<?php
/**
 * ganti_password.php — ubah password admin yang sedang login
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/koneksi.php';

$sukses = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $lama    = (string)($_POST['password_lama'] ?? '');
    $baru    = (string)($_POST['password_baru'] ?? '');
    $konfirm = (string)($_POST['password_konfirmasi'] ?? '');

    $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['admin_id']]);
    $hashLama = $stmt->fetchColumn();

    if (!password_verify($lama, (string)$hashLama)) {
        $error = 'Password lama salah.';
    } elseif (strlen($baru) < 8) {
        $error = 'Password baru minimal 8 karakter.';
    } elseif ($baru !== $konfirm) {
        $error = 'Konfirmasi password baru tidak sama.';
    } else {
        $stmt = $pdo->prepare('UPDATE admin_users SET password_hash = :h WHERE id = :id');
        $stmt->execute([
            'h'  => password_hash($baru, PASSWORD_DEFAULT),
            'id' => $_SESSION['admin_id'],
        ]);
        $sukses = 'Password berhasil diganti.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ganti Password — Dasbor Pustakawan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-green-900 text-white shadow">
  <div class="max-w-xl mx-auto px-4 h-16 flex items-center justify-between">
    <div class="flex items-center gap-2 font-bold text-lg">
      <i class="fa-solid fa-key"></i><span>Ganti Password</span>
    </div>
    <a href="admin.php" class="text-sm text-green-200 hover:text-white transition">
      <i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Dasbor
    </a>
  </div>
</header>

<main class="max-w-xl mx-auto px-4 py-8">

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

  <form method="POST" action="ganti_password.php"
        class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
    <?= csrf_field() ?>

    <div>
      <label class="block text-sm font-medium mb-1">Password Lama</label>
      <input type="password" name="password_lama" required
             class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Password Baru <span class="text-gray-400 font-normal">(min. 8 karakter)</span></label>
      <input type="password" name="password_baru" required minlength="8"
             class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Konfirmasi Password Baru</label>
      <input type="password" name="password_konfirmasi" required minlength="8"
             class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
    </div>

    <button type="submit"
            class="w-full py-3 rounded-lg bg-green-800 hover:bg-green-700 text-white font-semibold transition">
      <i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Password Baru
    </button>
  </form>

</main>
</body>
</html>
