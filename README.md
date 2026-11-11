# LibGuides Custom (Alternatif LibGuides)

Sistem perpustakaan digital: PHP murni + MySQL (PDO) + Vanilla JS + Tailwind CSS (CDN) + Chart.js (CDN).

## Peta 5 Modul Wajib

| Modul | File |
|---|---|
| 1. Manajemen User & Anggota | `kelola_pengguna.php` (khusus admin), `anggota.php`, `login.php`, `ganti_password.php` |
| 2. Data Bibliografi | `kelola_panduan.php`, `upload.php` (judul, pengarang, penerbit, tahun, jenis, DDC) |
| 3. Sirkulasi (digital) | `unduh.php` (transaksi saat unduh), `sirkulasi.php` (pengembalian & jatuh tempo) |
| 4. Pencarian Katalog | `index.php`, `panduan.php`, `tentang.php` |
| 5. Pelaporan & Dashboard | `admin.php` (grafik + statistik), `export.php` (CSV) |

## Peran Pengguna

| Kemampuan | Administrator | Pustakawan |
|---|---|---|
| Dasbor, analitik, export CSV | ✅ | ✅ |
| CRUD panduan & sumber, upload PDF | ✅ | ✅ |
| Kelola anggota & sirkulasi | ✅ | ✅ |
| **Hapus** panduan/sumber/anggota | ✅ | ❌ |
| Kelola akun pengguna | ✅ | ❌ |

Pemustaka (mahasiswa) **tanpa login**: mencari katalog + mengunduh PDF dengan mengisi NIM & nama (tercatat sebagai peminjaman, jatuh tempo 7 hari).

## File Penting

| File / Folder | Fungsi |
|---|---|
| `update_v4.sql` | Role pengguna + tabel `anggota`, `peminjaman` + kolom bibliografi (import sekali) |
| `config.php` | Konstanta: `UPLOAD_DIR` = `C:/xampp/LibguidesCustom_uploads/` (di luar htdocs) |
| `includes/helpers.php` | Escaping, CSRF, login guard, `require_role()`, auto-backup harian |
| `koneksi.php` | PDO + saklar `APP_DEBUG` (set `false` saat online) |

## Instalasi

1. Import berurutan: `db_libguides_custom.sql` → `update_v2.sql` → `update_v3.sql` → `update_v4.sql`
2. Sesuaikan `DB_PORT` dll di `koneksi.php`
3. Login: `admin` / `admin123` (role Administrator — **segera ganti password**)

## Catatan

- PDF disimpan di luar htdocs; satu-satunya akses lewat `unduh.php` (validasi nama file + pencatatan sirkulasi).
- Folder `backup/` menolak akses browser via `.htaccess`; folder `uploads/` lama boleh dihapus (file PDF aktif ada di `C:\xampp\LibguidesCustom_uploads\`).
"# ProjekAutomation" 
