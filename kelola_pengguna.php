<?php
/**
 * kelola_pengguna.php — CRUD akun pustakawan (KHUSUS role admin)
 */
require_once __DIR__ . '/auth.php';
require_role('admin');   // pustakawan biasa ditolak di sini
require_once __DIR__ . '/koneksi.php';

$sukses = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = (string)($_POST['aksi'] ?? '');

    try {
        if ($aksi === 'tambah') {
            $username = trim((string)$_POST['username']);
            $nama     = trim((string)$_POST['nama']);
            $password = (string)$_POST['password'];
            $role     = (string)$_POST['role'];

            if ($username === '' || $nama === '') {
                throw new RuntimeException('Username dan nama wajib diisi.');
            }
            if (strlen($password) < 8) {
                throw new RuntimeException('Password minimal 8 karakter.');
            }
            if (!in_array($role, ['admin', 'pustakawan'], true)) {
                throw new RuntimeException('Role tidak valid.');
            }
            $pdo->prepare('INSERT INTO admin_users (username, password_hash, nama, role) VALUES (:u, :p, :n, :r)')
                ->execute([
                    'u' => $username,
                    'p' => password_hash($password, PASSWORD_DEFAULT),
                    'n' => $nama,
                    'r' => $role,
                ]);
            $sukses = 'Akun pengguna berhasil dibuat.';
        }
        elseif ($aksi === 'reset') {
            $id       = (int)$_POST['id'];
            $password = (string)$_POST['password'];
            if (strlen($password) < 8) {
                throw new RuntimeException('Password baru minimal 8 karakter.');
            }
            $pdo->prepare('UPDATE admin_users SET password_hash = :p WHERE id = :id')
                ->execute(['p' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
            $sukses = 'Password pengguna direset.';
        }
        elseif ($aksi === 'hapus') {
            $id = (int)$_POST['id'];
            if ($id === (int)$_SESSION['admin_id']) {
                throw new RuntimeException('Anda tidak bisa menghapus akun sendiri.');
            }
            $pdo->prepare('DELETE FROM admin_users WHERE id = :id')->execute(['id' => $id]);
            $sukses = 'Akun pengguna dihapus.';
        }
    } catch (PDOException $ex) {
        $msg = $ex->getMessage();
        $isDuplicate = (strpos($msg, 'Duplicate') !== false || strpos($msg, '1062') !== false);
        $error = $isDuplicate ? 'Username sudah dipakai.' : 'Kesalahan database.';
        error_log('kelola_pengguna.php: ' . $msg);
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    }
}

$penggunaList = $pdo->query('SELECT * FROM admin_users ORDER BY role, nama')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Pengguna — Dasbor Pustakawan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,600;0,700;1,600&family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-green-900 text-white shadow">
  <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
    <div class="flex items-center gap-2 font-bold text-lg">
      <i class="fa-solid fa-user-gear"></i><span>Kelola Pengguna</span>
    </div>
    <a href="admin.php" class="text-sm text-green-200 hover:text-white transition">
      <i class="fa-solid fa-arrow-left mr-1"></i>Dasbor
    </a>
  </div>
</header>

<main class="max-w-4xl mx-auto px-4 py-8 space-y-8">

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

  <!-- ================= FORM TAMBAH PENGGUNA ================= -->
  <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="font-bold mb-4"><i class="fa-solid fa-user-plus mr-2 text-green-700"></i>Tambah Akun Pustakawan</h2>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="tambah">
      <input type="text" name="username" required maxlength="50" placeholder="Username"
             class="px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
      <input type="text" name="nama" required maxlength="100" placeholder="Nama lengkap"
             class="px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
      <input type="password" name="password" required minlength="8" placeholder="Password (min. 8)"
             class="px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
      <select name="role" class="px-4 py-2.5 rounded-lg border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-green-600">
        <option value="pustakawan">Pustakawan</option>
        <option value="admin">Administrator</option>
      </select>
      <button class="py-2.5 rounded-lg bg-green-800 hover:bg-green-700 text-white font-semibold transition">
        <i class="fa-solid fa-plus mr-1"></i>Tambah
      </button>
    </form>
  </section>

  <!-- ================= TABEL PENGGUNA ================= -->
  <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="font-bold mb-4"><i class="fa-solid fa-list mr-2 text-green-700"></i>Daftar Pengguna (<?= count($penggunaList) ?>)</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b">
            <th class="py-2 pr-4 font-medium">Username</th>
            <th class="py-2 pr-4 font-medium">Nama</th>
            <th class="py-2 pr-4 font-medium text-center">Role</th>
            <th class="py-2 font-medium text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($penggunaList as $u): ?>
          <tr>
            <td class="py-3 pr-4 font-medium"><?= e($u['username']) ?></td>
            <td class="py-3 pr-4"><?= e($u['nama']) ?></td>
            <td class="py-3 pr-4 text-center">
              <span class="text-xs px-2 py-1 rounded-full <?= $u['role'] === 'admin' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                <?= e($u['role']) ?>
              </span>
            </td>
            <td class="py-3 text-right whitespace-nowrap">
              <!-- Reset password -->
              <form method="POST" class="inline-flex gap-1 items-center">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="reset">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <input type="password" name="password" required minlength="8" placeholder="Password baru"
                       class="w-32 px-2 py-1.5 rounded border border-gray-300 text-xs">
                <button title="Reset password" class="px-2.5 py-1.5 rounded bg-blue-100 text-blue-700 hover:bg-blue-200">
                  <i class="fa-solid fa-key"></i>
                </button>
              </form>
              <?php if ((int)$u['id'] !== (int)$_SESSION['admin_id']): ?>
              <form method="POST" class="inline" onsubmit="return confirm('Hapus akun pengguna ini?');">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <button title="Hapus" class="px-2.5 py-1.5 rounded bg-red-100 text-red-700 hover:bg-red-200"><i class="fa-solid fa-trash"></i></button>
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
