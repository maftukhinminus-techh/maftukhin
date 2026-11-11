# LibGuides Custom (Alternatif LibGuides)

Sistem perpustakaan digital: PHP murni + MySQL (PDO) + Vanilla JS + Tailwind CSS (CDN) + Chart.js (CDN).

## Peta 5 Modul Wajib

| Modul | File |
|---|---|
| 1. Manajemen User & Anggota | `kelola_pengguna.php` (khusus admin), `anggota.php`, `login.php`, `ganti_password.php` |
| 2. Data Bibliografi | `kelola_panduan.php`, `upload.php` (judul, pengarang, penerbit, tahun, jenis, DDC) |
| 3. Sirkulasi (digital) | `unduh.php` (transaksi saat unduh), `sirkulasi.php` (pengembalian & jatuh tempo) |
| 4. Pencarian Katalog | `index.php`, `panduan.php`, `tentang.php`, `pengumuman.php` |
| 5. Pelaporan & Dashboard | `admin.php` (grafik + statistik), `export.php` (CSV) |

Fitur tambahan: pendaftaran anggota mandiri (`daftar.php`), pengumuman dengan foto
(`kelola_pengumuman.php`, `foto.php`), CSRF protection, idle timeout 30 menit.

## Peran Pengguna

| Kemampuan | Administrator | Pustakawan |
|---|---|---|
| Dasbor, analitik, export CSV | Ya | Ya |
| CRUD panduan & sumber, upload PDF | Ya | Ya |
| Kelola anggota & sirkulasi | Ya | Ya |
| **Hapus** panduan/sumber/anggota | Ya | Tidak |
| Kelola akun pengguna | Ya | Tidak |

Pemustaka (mahasiswa) **tanpa login**: mencari katalog + mengunduh PDF dengan NIM & nama
yang terdaftar (tercatat sebagai peminjaman, jatuh tempo 7 hari).

## File Penting

| File / Folder | Fungsi |
|---|---|
| `db_setup_lengkap.sql` | Skema + data awal lengkap (import SEKALI di hosting, sudah termasuk v1–v6) |
| `update_v*.sql` | Riwayat perubahan — hanya untuk instalasi lama, **tidak perlu** untuk setup baru |
| `config.php` | Konstanta upload. Env `UPLOAD_DIR`, fallback ke folder `uploads/` proyek |
| `includes/helpers.php` | Escaping, CSRF, login guard, `require_role()`, auto-backup harian via env `MYSQLDUMP_PATH` |
| `koneksi.php` | PDO + env `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_DEBUG` |
| `vercel.json` | Konfigurasi deploy ke Vercel (runtime `@vercel/php`) |

## Instalasi Lokal (XAMPP)

1. Import `db_setup_lengkap.sql` di phpMyAdmin (membuat database `db_libguides_custom`).
2. Salin semua file ke `C:\xampp\htdocs\LibguidesCustom\`.
3. Di XAMPP lokal dengan MySQL port 3308, set env atau ubah fallback `DB_PORT` di `koneksi.php` menjadi `3308`.
4. Login: `admin` / `admin123` — segera ganti lewat menu Ganti Password.
5. Pastikan folder `uploads/` bisa ditulis (writable) dan ekstensi PHP `pdo_mysql` + `fileinfo` aktif.

## Deploy ke Vercel

Repositori ini sudah berisi `vercel.json` sehingga bisa langsung di-deploy ke Vercel
(runtime PHP via `@vercel/php`, tanpa konfigurasi tambahan).

**Penting — Vercel itu serverless:**

1. **Database**: Vercel tidak menyediakan MySQL. Pakai database MySQL eksternal yang
   bisa diakses dari internet, misalnya **Aiven**, **PlanetScale**, **Railway**,
   atau **TiDB Cloud** (semua ada paket gratis). Import `db_setup_lengkap.sql` di sana.
2. **Environment variables**: di dashboard Vercel (Settings → Environment Variables) isi:
   `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`. Pastikan `APP_DEBUG` tidak di-set ke `true`.
3. **Upload file**: filesystem Vercel *read-only* dan tidak permanen — file PDF/foto
   yang diunggah akan hilang saat redeploy. Untuk produksi sungguhan, arahkan
   `UPLOAD_DIR` ke penyimpanan eksternal (S3/R2) dengan mengubah `upload.php` dan
   `kelola_pengumuman.php`, atau gunakan shared hosting biasa (lihat di bawah).
4. Push repo ini ke GitHub, lalu di Vercel: **Add New Project → Import repo → Deploy**.

## Deploy ke Shared Hosting (alternatif yang direkomendasikan)

Aplikasi ini paling stabil di hosting PHP + MySQL biasa (cPanel/DirectAdmin):

1. Buat database MySQL di panel hosting, import `db_setup_lengkap.sql`.
2. Upload semua file ke `public_html` (atau subfolder).
3. Set env `DB_HOST` dst. di panel, atau edit fallback di `koneksi.php`.
4. Pastikan `uploads/.htaccess` ikut ter-upload (memblokir eksekusi skrip di folder upload).

## Catatan Keamanan

- `APP_DEBUG` default `false`; hanya set `true` saat pengembangan lokal.
- Folder `backup/` dan isi `uploads/` tidak di-commit ke git (lihat `.gitignore`).
- `_fix_admin.sql` hanya alat bantu reset password admin lokal — jangan diimport di hosting
  dan tidak ikut keperluan tugas.
- Auto-backup harian hanya berjalan bila env `MYSQLDUMP_PATH` tersedia (umumnya hanya di VPS).
