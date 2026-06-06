# PETA 01 — Autentikasi & Komponen

> Peta koneksi antar-file & database untuk **E-Kantin (jajankita)**.
> Bagian ini mencakup landing page, koneksi database, komponen bersama (navbar, guard, helper, pengumuman), dan seluruh alur autentikasi (register, login, logout) baik pembeli/penjual maupun admin.
>
> Catatan umum struktur folder:
> - `1. koneksi/` → koneksi database
> - `3. komponen/` → file yang di-include berulang (navbar, guard, helper)
> - `4. autentifikasi/` → register, login, logout pembeli & penjual
> - `admin/login/` → login khusus admin
>
> Database memakai **MySQLi** dengan **prepared statement**. Tabel yang muncul di bagian ini: `tb_user`, `tb_toko`, `tb_order`, `tb_keranjang`, `tb_menu`.

---

### index.php
- **Jenis:** Tampilan (UI) — landing page murni HTML/CSS, tidak ada PHP.
- **Tujuan singkat:** Halaman depan publik yang memperkenalkan aplikasi dan mengarahkan pengunjung ke login/daftar.
- **Komponen yang di-include:** tidak ada (file statis, tanpa PHP sama sekali).
- **Diakses / dipanggil dari:** dibuka langsung sebagai root project; juga tujuan redirect/link dari banyak file: `logout.php`, `konfirmasilogout.php`, `navbaradmin.php` (tombol blokir mobile `../../index.php`), serta link "Kembali ke beranda" di `login.php`, `register.php`, `loginadmin.php`. Footer-nya juga me-link ke `index.php` sendiri.
- **Link keluar (href ke halaman lain):**
  - `4. autentifikasi/login.php` (tombol "Masuk", baris 194)
  - `4. autentifikasi/register.php` (tombol "Daftar Gratis", baris 197)
  - `admin/login/loginadmin.php` (link "Admin" tersembunyi di label domain, baris 206)
  - `index.php` (link nama di footer, baris 243)
  - `https://wa.me/6285648830046` (WhatsApp admin, target `_blank`)
  - `mailto:admin@jajankita.my.id` (email admin)
- **Form action (tujuan submit) & method:** tidak ada form.
- **Redirect (header Location):** tidak ada (tidak ada PHP).
- **Tabel database & operasinya:** Tidak mengakses database.
- **Catatan teknik penting:** Logo aplikasi dirender via CSS mask (`.logo-app`) dari `2. aset/logo/logo.png` dengan warna mengikuti `currentColor`. Layout grid fitur 2x2 di desktop, 1 kolom di mobile (<720px). Tanpa JavaScript.

---

### 1. koneksi/koneksi.php
- **Jenis:** Komponen (di-include) — konfigurasi & koneksi DB.
- **Tujuan singkat:** Membuat satu objek koneksi MySQLi `$conn` yang dipakai semua file yang menyentuh database (single source of truth).
- **Komponen yang di-include:** tidak ada.
- **Diakses / dipanggil dari:** di-include oleh hampir semua file logika/guard. Di bagian ini: `prosesregister.php` (`include "../1. koneksi/koneksi.php"`), `proseslogin.php`, `prosesloginadmin.php` (`include '../../1. koneksi/koneksi.php'`), `guardpembeli.php` & `guardpenjual.php` (`require_once __DIR__ . '/../1. koneksi/koneksi.php'` jika `$conn` belum ada). Navbar pembeli memakai `global $conn` (mengandalkan koneksi yang sudah dimuat guard).
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** tidak ada form.
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:** Tidak menjalankan query, tetapi **membuat koneksi** ke database `e_kantin` di `localhost`, user `root`, password kosong (default XAMPP).
- **Catatan teknik penting:** `$conn = mysqli_connect(...)`; jika gagal `die()` dengan pesan error. Mengatur timezone PHP ke `Asia/Jakarta` (baris 34) agar `date()`/`time()` sesuai WIB. Baris 33 `date_default_timezone_get()` tidak berefek (return value diabaikan) — tidak berbahaya.

---

### 3. komponen/navbarpembeli.php
- **Jenis:** Komponen (di-include) — navigasi pembeli (sidebar desktop / bottom-tab mobile).
- **Tujuan singkat:** Menampilkan navigasi pembeli plus badge keranjang, badge pesanan siap, banner pengumuman, dan flash message.
- **Komponen yang di-include:** `require_once __DIR__ . '/pengumuman.php'` (baris 51) untuk variabel `$teks_pengumuman`. Mengandalkan `$conn` dari koneksi yang sudah dimuat (`global $conn`, baris 58) — tidak meng-include koneksi sendiri.
- **Diakses / dipanggil dari:** di-include oleh halaman-halaman pembeli (mis. `pembeli/index/index.php`, `keranjang`, `pesanan`, `profil`). Mengandalkan `$pathbase` opsional dari halaman pemanggil (default `..`).
- **Link keluar (href ke halaman lain):**
  - `<?= $pathbase ?>/index/index.php` (Beranda)
  - `<?= $pathbase ?>/keranjang/keranjang.php` (Keranjang)
  - `<?= $pathbase ?>/pesanan/pesanan.php` (Pesanan; juga di notif banner)
  - `<?= $pathbase ?>/profil/profil.php` (Profil)
  - `#modal-kontak-pembeli` (buka modal kontak via CSS `:target`)
  - `../../4. autentifikasi/konfirmasilogout.php?peran=pembeli` (Keluar)
  - `mailto:admin@jajankita.my.id`, `https://wa.me/6285648830046` (di dalam modal kontak)
- **Form action (tujuan submit) & method:** tidak ada form.
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:**
  - `tb_order` → **SELECT** `COUNT(*)` (baris 61) dengan filter `id_user=?`, `status_order='Siap Diambil'`, `deleted=0`. Tujuan: menghitung jumlah pesanan siap diambil milik user untuk badge notifikasi. Hanya dijalankan jika `$_SESSION['id_user']` ada dan `$conn` tersedia.
- **Catatan teknik penting:** `session_name('sesi_pembeli')` + `session_start()` jika belum aktif. Prepared statement (`bind_param("i")`). Flash message bersifat one-time (`unset($_SESSION['flash'])`). Badge keranjang dihitung dari struktur `$_SESSION['keranjang'][$idtoko][$kunci]` (lewati kunci `_info`). `htmlspecialchars()` pada teks pengumuman & flash untuk cegah XSS. Tanpa JavaScript (modal pakai CSS `:target`).

---

### 3. komponen/navbarpenjual.php
- **Jenis:** Komponen (di-include) — sidebar penjual (desktop) / bottom-nav (mobile).
- **Tujuan singkat:** Menampilkan navigasi penjual beserta nama toko, status toko, banner pengumuman penjual, dan modal kontak admin.
- **Komponen yang di-include:** tidak ada include eksplisit; **membaca file teks** `__DIR__ . '/tekspengumumanpenjual.txt'` langsung via `file_get_contents` (baris 170-173). Mengandalkan data session yang sudah diisi `guardpenjual.php` (`$_SESSION['nama_toko']`, `status_toko`, `username`).
- **Diakses / dipanggil dari:** di-include oleh halaman penjual (mis. `penjual/index/index.php`, `manajemenpesanan`, `manajemenmenu`, `laporan`, `ulasan`, `profil`). Mengandalkan `$halamansaatini` opsional (di-set sendiri jika belum ada).
- **Link keluar (href ke halaman lain):**
  - `../../penjual/index/index.php` (Dashboard)
  - `../../penjual/manajemenpesanan/manajemenpesanan.php` (Pesanan Masuk)
  - `../../penjual/manajemenmenu/manajemenmenu.php` (Kelola Menu)
  - `../../penjual/laporan/laporan.php` (Laporan)
  - `../../penjual/ulasan/ulasan.php` (Semua Ulasan)
  - `../../penjual/profil/profil.php` (Profil & Toko)
  - `#modal-kontak` (modal kontak via CSS `:target`)
  - `../../4. autentifikasi/konfirmasilogout.php?peran=penjual` (Keluar)
  - `mailto:admin@jajankita.my.id`, `https://wa.me/6285648830046` (modal kontak)
- **Form action (tujuan submit) & method:** tidak ada form.
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:** Tidak mengakses database (semua data toko dibaca dari session).
- **Catatan teknik penting:** Toggle sidebar mobile pakai **CSS checkbox hack** (`#togel-sidebar`), modal pakai **CSS `:target`** — tanpa JavaScript. `htmlspecialchars()` pada nama toko & teks pengumuman. Inisial penjual via `mb_substr` (aman unicode). Banner pengumuman penjual dibaca dari file teks terpisah dari pengumuman pembeli.

---

### 3. komponen/navbaradmin.php
- **Jenis:** Komponen (di-include) — sidebar tetap panel admin + overlay blokir mobile.
- **Tujuan singkat:** Menampilkan navigasi admin dan menutup tampilan di layar kecil (<900px) karena panel admin hanya untuk desktop.
- **Komponen yang di-include:** tidak ada include. Mengandalkan `$_SESSION['username']` (diisi setelah login admin).
- **Diakses / dipanggil dari:** di-include oleh halaman admin (mis. `admin/index/index.php`, `admin/manajemenpengguna/*`, `admin/manajementoko/kantin.php`, `admin/laporan/laporan.php`). Mengandalkan `$halamansaatini` opsional.
- **Link keluar (href ke halaman lain):**
  - `../../index.php` (tombol "Kembali ke Beranda" pada overlay blokir mobile)
  - `../../admin/index/index.php` (Dashboard)
  - `../../admin/manajemenpengguna/user.php` (Manajemen Pengguna)
  - `../../admin/manajemenpengguna/verifikasi.php` (Verifikasi Pembeli)
  - `../../admin/manajementoko/kantin.php` (Status Kantin)
  - `../../admin/laporan/laporan.php` (Laporan Platform)
  - `../../4. autentifikasi/konfirmasilogout.php?peran=admin` (Keluar)
- **Form action (tujuan submit) & method:** tidak ada form.
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:** Tidak mengakses database.
- **Catatan teknik penting:** Penanda menu aktif memetakan banyak nama halaman ke satu menu (mis. menu "Manajemen Pengguna" aktif untuk `user`, `viewuser`, `tambahuser`, `edituser`, `hapususer`, `toko`, `viewtoko`, `edittoko`, `detailtoko`). Overlay `.blokirmobile` hanya blokir tampilan (CSS), bukan keamanan. `htmlspecialchars()` pada username admin.

---

### 3. komponen/guardpembeli.php
- **Jenis:** Proses (logika) — penjaga akses (di-include di atas halaman pembeli).
- **Tujuan singkat:** Memastikan hanya pembeli yang sudah login boleh mengakses halaman, sekaligus me-refresh data user dari DB tiap request.
- **Komponen yang di-include:** `require_once __DIR__ . '/../1. koneksi/koneksi.php'` (baris 33) jika `$conn` belum di-set.
- **Diakses / dipanggil dari:** di-include di paling atas setiap halaman pembeli (mis. `pembeli/index/index.php`, `keranjang`, `pesanan`, `profil`, dll).
- **Link keluar (href ke halaman lain):** tidak ada (bukan UI).
- **Form action (tujuan submit) & method:** tidak ada form.
- **Redirect (header Location):**
  - `../../4. autentifikasi/login.php` — jika `$_SESSION['id_user']` kosong **atau** `role` bukan `'pembeli'` (baris 26).
  - `../../4. autentifikasi/login.php?error=Akun sudah tidak aktif.` — jika user tidak ditemukan di DB (sudah dihapus admin); session dihancurkan dulu (`session_destroy()`, baris 48-50).
- **Tabel database & operasinya:**
  - `tb_user` → **SELECT** `username, email, foto` (baris 37) dengan `WHERE id_user=? AND deleted=0`. Tujuan: refresh data session agar perubahan oleh admin langsung terlihat tanpa logout.
- **Catatan teknik penting:** `session_name('sesi_pembeli')`. Prepared statement, cast `(int)$_SESSION['id_user']`. Jika data ada → timpa `$_SESSION['username'/'email'/'foto']`; jika tidak ada → paksa logout. `exit` selalu mengikuti `header()`.

---

### 3. komponen/guardpenjual.php
- **Jenis:** Proses (logika) — penjaga akses halaman penjual.
- **Tujuan singkat:** Memastikan hanya penjual yang login boleh akses, dan me-refresh data user + data toko dari DB ke session tiap request.
- **Komponen yang di-include:** `require_once __DIR__ . '/../1. koneksi/koneksi.php'` (baris 27) jika `$conn` belum ada.
- **Diakses / dipanggil dari:** di-include di atas setiap halaman penjual (mis. `penjual/index/index.php`, `manajemenpesanan`, `manajemenmenu`, `laporan`, `ulasan`, `profil`).
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** tidak ada form.
- **Redirect (header Location):**
  - `../../4. autentifikasi/login.php` — jika `id_user` kosong **atau** `role` bukan `'penjual'` (baris 18). (Tidak ada redirect "akun dihapus" seperti guard pembeli — jika user tidak ditemukan, data session sekadar tidak di-refresh.)
- **Tabel database & operasinya:**
  - `tb_user` → **SELECT** `username, email, foto` (baris 33) `WHERE id_user=? AND deleted=0`. Tujuan: refresh identitas penjual.
  - `tb_toko` → **SELECT** `id_toko, nama_toko, status_toko, foto_toko` (baris 45) `WHERE id_user=? AND deleted=0 LIMIT 1`. Tujuan: muat data toko ke session.
- **Catatan teknik penting:** `session_name('sesi_penjual')`. Dua prepared statement terpisah. Jika toko tidak ditemukan → set default (`id_toko=0`, `nama_toko='Toko Saya'`, `status_toko='buka'`, `foto_toko=null`) agar halaman tidak error. Null coalescing `?? 'buka'` untuk status.

---

### 3. komponen/guardadmin.php
- **Jenis:** Proses (logika) — penjaga akses halaman admin.
- **Tujuan singkat:** Memastikan hanya user dengan role `admin` dan session aktif yang boleh mengakses halaman admin.
- **Komponen yang di-include:** tidak ada (tidak menyentuh DB, tidak include koneksi).
- **Diakses / dipanggil dari:** di-include di atas setiap halaman admin (mis. `admin/index/index.php`, `admin/manajemenpengguna/*`, `admin/manajementoko/*`, `admin/laporan/laporan.php`).
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** tidak ada form.
- **Redirect (header Location):**
  - `../../admin/login/loginadmin.php` — jika `id_user` kosong **atau** `role` bukan `'admin'` (baris 24). Catatan: redirect ke **login admin**, bukan login umum.
- **Tabel database & operasinya:** Tidak mengakses database (tidak ada refresh data dari DB — beda dari guard pembeli/penjual).
- **Catatan teknik penting:** `session_name('sesi_admin')`. Guard paling ringan; murni cek session + role lalu `exit`.

---

### 3. komponen/kelas_jurusan.php
- **Jenis:** Komponen (di-include) — helper / library fungsi (tanpa output saat di-include kecuali fungsi render dipanggil).
- **Tujuan singkat:** Menyediakan daftar kelas/jurusan SMK + opsi Guru/Staff dan fungsi render dropdown serta validasi server, dipakai bersama oleh form registrasi dan form tambah/edit user admin.
- **Komponen yang di-include:** tidak ada.
- **Diakses / dipanggil dari:** `register.php` (`include "../3. komponen/kelas_jurusan.php"`, memanggil `tampilkanDropdownKelas()`), `prosesregister.php` (`include "../3. komponen/kelas_jurusan.php"`, memakai `kelasValid()`). Juga ditujukan untuk dipakai form admin tambah/edit user (sesuai komentar di file).
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** tidak ada form (hanya merender elemen `<select>` saat `tampilkanDropdownKelas()` dipanggil oleh form lain).
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:** Tidak mengakses database. (Nilai kelas disimpan ke kolom `kelas` di `tb_user` oleh file lain.)
- **Catatan teknik penting:** Semua fungsi dibungkus `if (!function_exists('daftarTingkatKelas'))` agar aman dari double-include. Fungsi: `daftarTingkatKelas()` (10/11/12), `daftarJurusan()` (10 jurusan, kode→nama), `daftarPembeliNonMurid()` (`Guru`, `Staff Sekolah`), `daftarSemuaKelas()` (30 kombinasi murid + 2 non-murid), `kelasValid()` (validasi `in_array` strict), `tampilkanDropdownKelas()` (render `<select>` dengan `<optgroup>`, pre-fill, atribut `required` opsional). Nilai kelas yang disimpan ke DB berupa string seperti `"10 RPL"`, `"Guru"`, `"Staff Sekolah"`.

---

### 3. komponen/pengumuman.php
- **Jenis:** Komponen (di-include) — pembaca file teks pengumuman.
- **Tujuan singkat:** Membaca isi `tekspengumuman.txt` ke variabel `$teks_pengumuman` agar bisa ditampilkan sebagai banner di halaman pembeli.
- **Komponen yang di-include:** tidak ada.
- **Diakses / dipanggil dari:** `navbarpembeli.php` (`require_once __DIR__ . '/pengumuman.php'`, baris 51). (Pengumuman penjual punya file teks sendiri yang dibaca langsung di `navbarpenjual.php`, bukan lewat file ini.)
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** tidak ada form.
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:** Tidak mengakses database (sumber data = file teks `tekspengumuman.txt`, bukan DB).
- **Catatan teknik penting:** Path absolut via `__DIR__ . '/tekspengumuman.txt'`. `file_exists` → jika ada baca + `trim()`, jika tidak ada string kosong (banner otomatis tidak muncul). Konten ditampilkan dengan `htmlspecialchars()` di navbar (cegah XSS).

---

### 4. autentifikasi/register.php
- **Jenis:** Tampilan (UI) — form pendaftaran pembeli.
- **Tujuan singkat:** Menampilkan form daftar akun pembeli baru dan menampilkan kembali pesan error + input sebelumnya jika validasi gagal.
- **Komponen yang di-include:** `include "../3. komponen/kelas_jurusan.php"` (baris 27) untuk merender dropdown kelas.
- **Diakses / dipanggil dari:** `index.php` (tombol "Daftar Gratis"), `login.php` (link "Daftar sekarang"), dan dari `prosesregister.php` via redirect saat validasi gagal (membawa `?error=...&username=...&email=...&namalengkap=...&kelas=...`).
- **Link keluar (href ke halaman lain):**
  - `login.php` (link "Login", baris 126)
  - `../index.php` (Kembali ke beranda, baris 131)
- **Form action (tujuan submit) & method:** `action="prosesregister.php"`, `method="POST"` (baris 40).
- **Redirect (header Location):** tidak ada (file UI; redirect dilakukan oleh prosesregister.php).
- **Tabel database & operasinya:** Tidak mengakses database secara langsung. (Hanya membaca `$_GET` untuk repopulate.)
- **Catatan teknik penting:** Field: `namalengkap`, `kelas` (dari dropdown helper), `username`, `email`, `password`. `htmlspecialchars()` pada semua nilai repopulate. Validasi client-side via `minlength`/`maxlength`/`type="email"` (validasi utama tetap di server). Tombol show/hide password via JS kecil (IIFE) — satu-satunya JS, hanya untuk field password.

---

### 4. autentifikasi/prosesregister.php
- **Jenis:** Proses (logika) — pemroses pendaftaran (tanpa HTML).
- **Tujuan singkat:** Memvalidasi data registrasi, mencegah duplikat, lalu menyimpan akun pembeli baru berstatus `pending` (menunggu verifikasi admin).
- **Komponen yang di-include:** `include "../1. koneksi/koneksi.php"` (baris 15) dan `include "../3. komponen/kelas_jurusan.php"` (baris 17, untuk `kelasValid()`).
- **Diakses / dipanggil dari:** form di `register.php` (submit POST).
- **Link keluar (href ke halaman lain):** tidak ada (semua via redirect).
- **Form action (tujuan submit) & method:** tidak ada form (ini target submit).
- **Redirect (header Location):**
  - `register.php` — jika request bukan POST (baris 21).
  - `register.php?error=...` + query repopulate (`balikUrlForm`) — pada tiap kegagalan validasi: ada kolom kosong; kelas tidak valid; nama lengkap <3 atau >100; username <6 atau >50; password <8 atau >100; format email tidak valid; gagal insert.
  - `register.php?error=Email atau Username sudah terdaftar!` — jika ditemukan duplikat aktif (baris 104).
  - `login.php?sukses=Pendaftaran berhasil! ... menunggu verifikasi admin...` — jika insert sukses (baris 121).
- **Tabel database & operasinya:**
  - `tb_user` → **UPDATE** (baris 89-92): `SET deleted=1, deleted_at=NOW()` `WHERE (email=? OR username=?) AND status_verifikasi='ditolak' AND deleted=0`. Tujuan: membebaskan username/email dari akun yang sebelumnya ditolak admin agar bisa dipakai ulang.
  - `tb_user` → **SELECT** `id_user` (baris 98) `WHERE (email=? OR username=?) AND deleted=0`. Tujuan: cek duplikat akun aktif.
  - `tb_user` → **INSERT** (baris 114-116): kolom `username, nama_lengkap, kelas, email, password, role, status_verifikasi, deleted`. Nilai: role `'pembeli'`, `status_verifikasi='pending'`, `deleted=0`, password ter-hash.
- **Catatan teknik penting:** PRG (Post/Redirect/Get) — selalu redirect, tidak pernah menampilkan HTML. Prepared statement untuk semua query. `password_hash($password, PASSWORD_DEFAULT)` (bcrypt). `urlencode()` pada semua pesan & nilai repopulate. Validasi pakai `filter_var(..., FILTER_VALIDATE_EMAIL)` dan `kelasValid()`.

---

### 4. autentifikasi/login.php
- **Jenis:** Tampilan (UI) — form login pembeli & penjual.
- **Tujuan singkat:** Menampilkan form login (username/email + password) dan menampilkan pesan error/sukses dari proses login atau registrasi.
- **Komponen yang di-include:** tidak ada (hanya CSS `../3. komponen/autentifikasi.css`).
- **Diakses / dipanggil dari:** `index.php` (tombol "Masuk"), `register.php` (link "Login"), `prosesregister.php` (redirect sukses dengan `?sukses=`), `proseslogin.php` (redirect error dengan `?error=`), `guardpembeli.php` & `guardpenjual.php` (redirect saat belum login).
- **Link keluar (href ke halaman lain):**
  - `https://wa.me/6285648830046?text=...` (Lupa password → hubungi admin, target `_blank`)
  - `register.php` (link "Daftar sekarang", baris 109)
  - `../index.php` (Kembali ke beranda, baris 114)
- **Form action (tujuan submit) & method:** `action="proseslogin.php"`, `method="POST"` (baris 34).
- **Redirect (header Location):** tidak ada (file UI).
- **Tabel database & operasinya:** Tidak mengakses database.
- **Catatan teknik penting:** Membaca `$_GET['error']`, `$_GET['sukses']`, `$_GET['usernameemail']` lalu `htmlspecialchars()`. Repopulate field username/email. Show/hide password via JS (IIFE), hanya untuk field password.

---

### 4. autentifikasi/proseslogin.php
- **Jenis:** Proses (logika) — pemroses login pembeli & penjual (tanpa HTML).
- **Tujuan singkat:** Memverifikasi kredensial, menerapkan gate verifikasi, memulai session sesuai role, lalu mengarahkan ke dashboard yang tepat.
- **Komponen yang di-include:** `include "../1. koneksi/koneksi.php"` (baris 18). `session_start()` sengaja ditunda sampai role diketahui (agar nama session benar).
- **Diakses / dipanggil dari:** form di `login.php` (submit POST).
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** tidak ada form (ini target submit).
- **Redirect (header Location):**
  - `login.php` — jika bukan POST (baris 28).
  - `login.php?error=Username/Email dan Password wajib diisi!` — jika input kosong (baris 40).
  - `login.php?error=Akun tidak ditemukan!&usernameemail=...` — jika user tak ada (baris 63).
  - `login.php?error=Password salah!&usernameemail=...` — jika `password_verify` gagal (baris 72).
  - `login.php?error=Akunmu masih menunggu verifikasi admin...` — jika `status_verifikasi='pending'` (baris 80).
  - `login.php?error=Akunmu ditolak oleh admin...` — jika `status_verifikasi='ditolak'` (baris 85).
  - `login.php?error=Akun admin harus login lewat halaman admin.` — jika `role='admin'` (baris 93).
  - `../penjual/index/index.php` — login sukses sebagai penjual (baris 184).
  - `../pembeli/index/index.php` — login sukses sebagai pembeli (baris 187).
  - `login.php?error=Role tidak dikenali.` — fallback role tak dikenal (baris 191).
- **Tabel database & operasinya:**
  - `tb_user` → **SELECT** `id_user, username, email, password, role, status_verifikasi` (baris 48-50) `WHERE (username=? OR email=?) AND deleted=0`. Tujuan: cari akun untuk verifikasi.
  - `tb_toko` → **SELECT** `id_toko, nama_toko, status_toko` (baris 123) `WHERE id_user=? AND deleted=0 LIMIT 1`. Hanya jika role penjual; isi data toko ke session.
  - `tb_keranjang` **JOIN** `tb_menu` **JOIN** `tb_toko` → **SELECT** (baris 143-148): `k.id_menu, k.jumlah, m.nama_menu, m.harga, m.foto, m.id_toko, t.nama_toko` dari `tb_keranjang k JOIN tb_menu m ON k.id_menu=m.id_menu JOIN tb_toko t ON m.id_toko=t.id_toko` `WHERE k.id_user=? AND m.deleted=0 AND m.status='aktif' AND t.deleted=0`. Hanya jika role pembeli; memuat ulang keranjang dari DB ke `$_SESSION['keranjang']`.
- **Catatan teknik penting:** Pemisahan session multi-role: nama session ditentukan via `match($user['role'])` → `sesi_penjual` / `sesi_pembeli` sebelum `session_start()`. Prepared statement seluruhnya. `password_verify` (hash bcrypt). Admin ditolak sebelum session dibuat. Keranjang dibangun ulang dengan struktur `$_SESSION['keranjang'][idtoko]['_info'|idmenu]`. PRG penuh.

---

### 4. autentifikasi/logout.php
- **Jenis:** Proses (logika) — logout total (tanpa HTML, tanpa konfirmasi).
- **Tujuan singkat:** Menghapus paksa session penjual DAN pembeli sekaligus lalu redirect ke landing (logout darurat / bersih-bersih).
- **Komponen yang di-include:** tidak ada.
- **Diakses / dipanggil dari:** tidak ada link/form yang menunjuk ke file ini di antara file yang dipetakan (dipakai sebagai utilitas darurat; navbar memakai `konfirmasilogout.php`).
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** tidak ada form.
- **Redirect (header Location):** `../index.php` (selalu, setelah session dihapus, baris 42).
- **Tabel database & operasinya:** Tidak mengakses database.
- **Catatan teknik penting:** Menghapus dua session berurutan: `sesi_penjual` lalu `sesi_pembeli`, masing-masing `$_SESSION = []` + `session_destroy()`. Session admin TIDAK disentuh di sini. Kombinasi `$_SESSION = []` (kosongkan memori) + `session_destroy()` (hapus file session) untuk pembersihan total.

---

### 4. autentifikasi/konfirmasilogout.php
- **Jenis:** Tampilan (UI) + Proses (logika) — file berfungsi ganda (GET = dialog konfirmasi, POST = jalankan logout).
- **Tujuan singkat:** Meminta konfirmasi sebelum logout dan, jika dikonfirmasi, menghapus session sesuai `peran` lalu redirect ke landing.
- **Komponen yang di-include:** tidak ada (hanya CSS `../3. komponen/autentifikasi.css`).
- **Diakses / dipanggil dari:** tombol "Keluar" di ketiga navbar:
  - `navbarpembeli.php` → `...konfirmasilogout.php?peran=pembeli`
  - `navbarpenjual.php` → `...konfirmasilogout.php?peran=penjual`
  - `navbaradmin.php` → `...konfirmasilogout.php?peran=admin`
- **Link keluar (href ke halaman lain):** tautan "Batal, kembali" → `$kembali` yang ditentukan via `match($peran)`:
  - `penjual` → `../penjual/index/index.php`
  - `pembeli` → `../pembeli/index/index.php`
  - `admin` → `../admin/index/index.php`
  - default → `login.php`
- **Form action (tujuan submit) & method:** `action="konfirmasilogout.php"`, `method="POST"` (submit ke dirinya sendiri); membawa hidden `aksi=keluar` dan `peran=<...>`.
- **Redirect (header Location):** `../index.php` — hanya saat POST dengan `aksi='keluar'`, setelah session peran terkait dihapus (baris 47).
- **Tabel database & operasinya:** Tidak mengakses database.
- **Catatan teknik penting:** Nama session yang dihapus ditentukan `match($peran)` → `sesi_penjual` / `sesi_admin` / default `sesi_pembeli`. `$_SESSION = []` + `session_destroy()` + `session_write_close()`. Murni PHP+HTML tanpa JavaScript. `htmlspecialchars()` pada `$peran` dan `$kembali` (cegah XSS dari query string).

---

### admin/login/loginadmin.php
- **Jenis:** Tampilan (UI) + sedikit Proses — form login khusus admin dengan pre-cek session.
- **Tujuan singkat:** Menampilkan form login admin terpisah; jika admin sudah login langsung diarahkan ke dashboard.
- **Komponen yang di-include:** tidak ada (CSS `../../3. komponen/autentifikasi.css`). Memanggil `session_name('sesi_admin')` + `session_start()` sendiri.
- **Diakses / dipanggil dari:** `index.php` (link "Admin" tersembunyi), `guardadmin.php` (redirect saat belum login admin), `prosesloginadmin.php` (redirect error dengan `?error=`).
- **Link keluar (href ke halaman lain):** `../../index.php` (Kembali ke beranda, baris 125).
- **Form action (tujuan submit) & method:** `action="prosesloginadmin.php"`, `method="POST"` (baris 68).
- **Redirect (header Location):** `../index/index.php` — jika sudah ada session admin valid (`id_user` ada dan `role='admin'`, baris 62).
- **Tabel database & operasinya:** Tidak mengakses database (hanya cek session).
- **Catatan teknik penting:** `session_name('sesi_admin')` sebelum `session_start()`. Membaca `$_GET['error']` & `$_GET['usernameemail']` dengan `htmlspecialchars()`. Halaman login admin TIDAK diblokir di mobile (hanya dashboard yang diblokir via `navbaradmin.php`). Show/hide password via JS (IIFE).

---

### admin/login/prosesloginadmin.php
- **Jenis:** Proses (logika) — pemroses login admin (tanpa HTML).
- **Tujuan singkat:** Memverifikasi kredensial dan hanya mengizinkan masuk jika role `admin`, lalu memulai `sesi_admin` dan ke dashboard.
- **Komponen yang di-include:** `include '../../1. koneksi/koneksi.php'` (baris 16).
- **Diakses / dipanggil dari:** form di `loginadmin.php` (submit POST).
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** tidak ada form (ini target submit).
- **Redirect (header Location):**
  - `loginadmin.php` — jika bukan POST (baris 20).
  - `loginadmin.php?error=Username/Email dan Password wajib diisi!` — jika input kosong (baris 30).
  - `loginadmin.php?error=Akun tidak ditemukan!&usernameemail=...` — user tak ada (baris 47).
  - `loginadmin.php?error=Password salah!&usernameemail=...` — `password_verify` gagal (baris 55).
  - `loginadmin.php?error=Akun ini bukan admin. Gunakan halaman login utama.&usernameemail=...` — role bukan admin (baris 63).
  - `../index/index.php` — login admin sukses (baris 80).
- **Tabel database & operasinya:**
  - `tb_user` → **SELECT** `id_user, username, email, password, role` (baris 36-38) `WHERE (username=? OR email=?) AND deleted=0`. Tujuan: cari akun untuk verifikasi admin.
- **Catatan teknik penting:** Tidak ada gate `status_verifikasi` (admin diasumsikan selalu valid). `session_name('sesi_admin')` + `session_start()` setelah role dipastikan admin. Prepared statement, `password_verify` (bcrypt). PRG penuh. Menyimpan `id_user, username, email, role` ke session.

---

## Ringkasan keterhubungan kunci

- **Pemisahan session multi-role:** tiga nama session berbeda — `sesi_pembeli`, `sesi_penjual`, `sesi_admin` — diterapkan via `session_name()` sebelum `session_start()` di guard, proses login, dan logout. Memungkinkan login berbeda role di tab berbeda tanpa saling menimpa.
- **Alur autentikasi pembeli/penjual:** `index.php` / `register.php` → `login.php` → `proseslogin.php` → dashboard `pembeli/` atau `penjual/`. Guard (`guardpembeli.php` / `guardpenjual.php`) menjaga tiap halaman dan me-refresh data dari DB.
- **Alur admin terpisah:** `index.php` (link tersembunyi) → `loginadmin.php` → `prosesloginadmin.php` → `admin/index/index.php`. Dijaga `guardadmin.php`.
- **Logout:** navbar (semua role) → `konfirmasilogout.php?peran=...` (konfirmasi) → hapus session peran → `index.php`. `logout.php` adalah utilitas darurat untuk hapus session pembeli+penjual sekaligus.
- **Verifikasi pembeli:** registrasi menyetel `status_verifikasi='pending'`; `proseslogin.php` memblokir `pending`/`ditolak`; admin meninjau via `admin/manajemenpengguna/verifikasi.php`.
- **Tabel tersentuh di bagian ini:** `tb_user` (SELECT/INSERT/UPDATE), `tb_toko` (SELECT), `tb_order` (SELECT COUNT), `tb_keranjang`+`tb_menu`+`tb_toko` (SELECT JOIN). Semua query memakai prepared statement.
