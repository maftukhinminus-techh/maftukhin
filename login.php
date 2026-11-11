<?php
/**
 * login.php — halaman masuk pustakawan
 */
session_start();
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/includes/helpers.php';

// Sudah login? Langsung ke dasbor.
if (!empty($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = :u LIMIT 1');
    $stmt->execute(['u' => $username]);
    $admin = $stmt->fetch();

    // password_verify: membandingkan input dengan hash bcrypt di database
    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true); // cegah session fixation
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_nama'] = $admin['nama'];
        $_SESSION['admin_role'] = $admin['role'] ?? 'pustakawan';
        header('Location: admin.php');
        exit;
    }

    $error = 'Username atau password salah.';
}

$timeoutMsg = isset($_GET['timeout']) ? 'Sesi Anda berakhir karena tidak ada aktivitas. Silakan login kembali.' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Pustakawan — LibGuides Kampus</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-green-900 min-h-screen flex items-center justify-center px-4">

<div class="w-full max-w-sm bg-white rounded-2xl shadow-xl p-8">
  <div class="text-center mb-6">
    <div class="w-14 h-14 mx-auto rounded-full bg-green-100 text-green-800 flex items-center justify-center text-2xl mb-3">
      <i class="fa-solid fa-user-tie"></i>
    </div>
    <h1 class="text-xl font-bold text-gray-800">Login Pustakawan</h1>
    <p class="text-sm text-gray-500">Area khusus admin LibGuides</p>
  </div>

  <?php if ($timeoutMsg): ?>
  <div class="mb-4 p-3 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm">
    <i class="fa-solid fa-clock mr-1"></i><?= e($timeoutMsg) ?>
  </div>
  <?php endif; ?>

  <?php if ($error): ?>
  <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
    <i class="fa-solid fa-circle-exclamation mr-1"></i><?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="login.php" class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
      <input type="text" name="username" required autofocus
             class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
      <input type="password" name="password" required
             class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
    </div>
    <button type="submit"
            class="w-full py-2.5 rounded-lg bg-green-800 hover:bg-green-700 text-white font-semibold transition">
      <i class="fa-solid fa-right-to-bracket mr-1"></i>Masuk
    </button>
  </form>

  <a href="index.php" class="block text-center text-sm text-gray-500 hover:text-green-700 mt-5">
    <i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Portal Publik
  </a>
</div>

</body>
</html>
