# 📚 PANDUAN BELAJAR LENGKAP — APLIKASI E-KANTIN "jajankita"

> Dokumen ini dibuat untuk persiapan **presentasi & sesi tanya-jawab**. Dibaca dari atas ke bawah, kamu akan paham: apa aplikasinya, teknologi yang dipakai & alasannya, alur tiap fitur, kenapa kode dibuat begitu, kaitannya dengan mata pelajaran (umum & jurusan RPL), dan dampaknya bagi pengguna.
>
> **Tips presentasi:** jangan menghafal kode baris per baris. Pahami **alurnya** dan **alasannya**. Kalau juri tanya "kenapa begini?", jawab dengan alasan (keamanan / kemudahan / kebenaran data), bukan sekadar "karena memang ditulis begitu".

---

## DAFTAR ISI
1. [Gambaran Umum Aplikasi](#1-gambaran-umum-aplikasi)
2. [Teknologi yang Dipakai & Alasannya](#2-teknologi-yang-dipakai--alasannya)
3. [Struktur Folder Proyek](#3-struktur-folder-proyek)
4. [Skema Database (9 Tabel) & Relasinya](#4-skema-database-9-tabel--relasinya)
5. [10 Konsep Teknis Kunci (yang berulang di mana-mana)](#5-sepuluh-konsep-teknis-kunci)
6. [Alur Autentikasi (Daftar, Login, Logout, Guard)](#6-alur-autentikasi)
7. [Alur Pembeli](#7-alur-pembeli)
8. [Alur Penjual](#8-alur-penjual)
9. [Alur Admin](#9-alur-admin)
10. [Kaitan dengan Mata Pelajaran Umum](#10-kaitan-dengan-mata-pelajaran-umum)
11. [Kaitan dengan Mata Pelajaran Jurusan (RPL)](#11-kaitan-dengan-mata-pelajaran-jurusan-rpl)
12. [Dampak bagi Pengguna & Sekolah](#12-dampak-bagi-pengguna--sekolah)
13. [Bank Soal Tanya-Jawab + Jawaban](#13-bank-soal-tanya-jawab--jawaban)
14. [Keterbatasan & Rencana Pengembangan](#14-keterbatasan--rencana-pengembangan)
15. [⭐ Peta Detail Per Halaman (koneksi antar-halaman & database) — DOKUMEN PENDAMPING](#15--peta-detail-per-halaman)

---

## 1. GAMBARAN UMUM APLIKASI

**E-Kantin (nama brand: "jajankita")** adalah aplikasi web pemesanan makanan kantin sekolah. Bayangkan seperti GoFood/ShopeeFood, tapi versi khusus kantin SMK dengan **10 kantin (kios)** yang sudah tetap jumlahnya.

**Tiga jenis pengguna (role):**

| Role | Siapa | Bisa apa |
|------|-------|----------|
| **Pembeli** | Murid, Guru, Staff sekolah | Lihat menu, masukkan keranjang, checkout/pesan, lihat status pesanan, chat penjual, beri rating |
| **Penjual** | Pemilik kantin | Kelola menu (tambah/edit/hapus, stok), proses pesanan masuk, lihat laporan omzet, balas chat |
| **Admin** | Pengelola sekolah | Kelola semua pengguna, verifikasi pembeli baru, atur 10 kantin, lihat laporan global, kirim pengumuman |

**Alur paling inti (happy path):**
```
Pembeli daftar → Admin verifikasi → Pembeli login → pilih menu → keranjang →
checkout (bayar tunai di kantin) → Penjual terima & proses pesanan →
status "Siap Diambil" → pembeli ambil → "Selesai" → pembeli beri rating
```

---

## 2. TEKNOLOGI YANG DIPAKAI & ALASANNYA

| Teknologi | Untuk apa | Kenapa dipilih |
|-----------|-----------|----------------|
| **PHP murni** (tanpa framework) | Logika server (backend) | Murni = paham fundamental, tidak tergantung "sihir" framework. Cocok untuk belajar di SMK. |
| **MySQL / MariaDB** | Penyimpanan data | Database relasional standar, gratis, ada di XAMPP. |
| **mysqli** (bukan PDO) | Jembatan PHP ↔ MySQL | API resmi MySQL, mendukung **prepared statement** untuk keamanan. |
| **HTML + CSS murni** | Tampilan (frontend) | Tanpa Bootstrap/Tailwind → paham cara kerja layout & styling dari nol. |
| **JavaScript seminimal mungkin** | Hanya untuk tombol lihat/sembunyikan password | Filosofi proyek: logika utama di server (lebih aman), bukan di browser. |
| **XAMPP** | Server lokal (Apache + MySQL + PHP) | Paket all-in-one untuk development di komputer sendiri. |
| **Font Awesome** | Ikon | Mempercantik UI tanpa bikin gambar sendiri. |

> **Jawaban juri "kenapa tidak pakai framework/Laravel?"**
> "Karena tujuan proyek ini belajar fundamental. Dengan PHP murni saya paham betul cara kerja session, koneksi database, dan keamanan — bukan sekadar memanggil fungsi framework yang sudah jadi. Setelah paham dasarnya, pindah ke framework jadi lebih mudah."

---

## 3. STRUKTUR FOLDER PROYEK

Folder diberi nomor agar **urutannya logis** dan mudah dipahami:

```
E-Kantin/
├── 1. koneksi/          → koneksi.php (satu-satunya tempat setting database)
├── 3. komponen/         → bagian yang dipakai ulang: navbar, guard, helper
├── 4. autentifikasi/    → daftar, login, logout (pembeli & penjual)
├── admin/               → semua halaman admin (login, dashboard, kelola user/toko/laporan)
├── pembeli/             → semua halaman pembeli (index, keranjang, pesanan, profil)
├── penjual/             → semua halaman penjual (index, menu, pesanan, laporan, profil)
├── index.php            → halaman depan (landing page) pilih login sebagai apa
└── .sql                 → cadangan struktur + isi database
```

**Pola penamaan file yang konsisten:**
- `namafitur.php` → **menampilkan** halaman (UI/tampilan)
- `prosesnamafitur.php` → **memproses** data (logika: simpan/ubah/hapus ke database)

> Ini sesuai aturan proyek: **tampilan dan proses dipisah**. Manfaatnya: kode lebih rapi, mudah dicari, dan menerapkan prinsip *Separation of Concerns* (pemisahan tanggung jawab).

---

## 4. SKEMA DATABASE (9 TABEL) & RELASINYA

Database bernama `e_kantin`, terdiri dari 9 tabel yang saling terhubung.

### Daftar tabel & perannya

| Tabel | Peran | Kolom penting |
|-------|-------|---------------|
| **tb_user** | Semua pengguna (admin/penjual/pembeli) | id_user, username, password (hash), role, status_verifikasi, kelas |
| **tb_toko** | Data 10 kantin | id_toko, id_user (pemilik, bisa NULL), nama_toko, nomor_kantin, status_toko, foto_toko |
| **tb_menu** | Menu makanan tiap kantin | id_menu, nama_menu, harga, stok, kategori, foto, status (aktif/nonaktif), id_toko |
| **tb_keranjang** | Keranjang belanja pembeli (persisten) | id_keranjang, id_user, id_menu, jumlah |
| **tb_order** | Pesanan (header/induk) | id_order, id_user (pembeli), id_penjual, id_toko, **nama_toko_snapshot**, **nomor_kantin_snapshot**, **foto_toko_snapshot**, total_harga, status_order, metode_pembayaran, catatan, tanggal_order |
| **tb_detail_order** | Isi rincian tiap pesanan | id_detail, id_order, id_menu, **nama_menu_snapshot**, jumlah, **harga_satuan**, subtotal |
| **tb_rating** | Ulasan & bintang pembeli | id_rating, id_order, id_menu, rating (1-5), komentar |
| **tb_chat** | Chat pembeli ↔ penjual per pesanan | id_chat, id_order, id_pengirim, pesan, dibaca, created |
| **tb_riwayat_toko** | Riwayat perubahan/penugasan kantin | id_riwayat, id_toko, ... |

### Relasi antar tabel (Entity Relationship — versi teks)

```
tb_user (1) ───< (banyak) tb_toko        : 1 penjual punya 1 kantin (id_user di tb_toko)
tb_toko (1) ───< (banyak) tb_menu        : 1 kantin punya banyak menu (id_toko di tb_menu)
tb_user (1) ───< (banyak) tb_keranjang   : 1 pembeli punya banyak item keranjang
tb_user (1) ───< (banyak) tb_order       : 1 pembeli punya banyak pesanan
tb_order(1) ───< (banyak) tb_detail_order: 1 pesanan berisi banyak item (RELASI INTI)
tb_order(1) ───< (banyak) tb_chat        : 1 pesanan punya banyak pesan chat
tb_order(1) ───< (banyak) tb_rating      : rating diberikan per pesanan/menu
tb_menu (1) ───< (banyak) tb_detail_order: 1 menu bisa muncul di banyak pesanan
```

**Istilah:** `(1) ───< (banyak)` artinya relasi **one-to-many** (satu ke banyak). Contoh: satu pesanan (`tb_order`) berisi banyak baris item (`tb_detail_order`). Inilah inti basis data relasional.

### ⭐ Konsep penting yang sering ditanya juri:

**a) Kenapa ada `nama_menu_snapshot` dan `harga_satuan` di `tb_detail_order`?**
Ini disebut **snapshot (potret data saat transaksi)**. Saat pembeli memesan "Nasi Goreng Rp12.000", nama & harga itu **disalin** ke `tb_detail_order`. Kenapa? Karena nanti penjual mungkin **mengubah harga** Nasi Goreng jadi Rp15.000 atau **mengganti namanya**. Kalau struk lama hanya mengambil dari `tb_menu` yang sekarang, struk jadi salah. Dengan snapshot, **struk lama tetap menampilkan harga & nama saat pembelian** → data historis akurat. (Sama seperti struk belanja Indomaret yang tidak berubah meski harga barang naik besok.)

**b) Kenapa ada kolom `deleted` dan `deleted_at` (soft delete)?**
Data tidak benar-benar dihapus dari database (`DELETE`), tapi hanya **ditandai** `deleted=1` (*soft delete / hapus halus*). Kenapa?
- Pesanan lama yang memakai menu itu **tetap utuh** (tidak hilang/error).
- Bisa di-*restore* kalau salah hapus.
- Ada jejak audit (`deleted_at` = kapan dihapus).

**c) Kenapa `id_user` di `tb_toko` boleh NULL?**
Karena ada **10 slot kantin tetap**. Kalau penjualnya keluar/dihapus, kantinnya tidak ikut hilang — hanya `id_user` di-NULL-kan (kantin jadi "kosong" dan siap diisi penjual baru). Slot fisik kantin bersifat permanen.

---

## 5. SEPULUH KONSEP TEKNIS KUNCI

Konsep-konsep ini **berulang di hampir semua file**. Kuasai 10 ini, kamu bisa jawab 80% pertanyaan.

### 1️⃣ Prepared Statement (mencegah SQL Injection)
```php
$stmt = $conn->prepare("SELECT * FROM tb_user WHERE username=?");
$stmt->bind_param("s", $username);   // "s" = string, "i" = integer, "d" = desimal
$stmt->execute();
```
**Masalah yang dicegah:** Kalau query digabung langsung (`"... WHERE username='$username'"`), penyerang bisa mengetik `' OR '1'='1` untuk membobol login. Dengan prepared statement, input **dipisah** dari perintah SQL, jadi dianggap teks biasa, bukan perintah. **Ini pertahanan keamanan #1.**

### 2️⃣ Password Hashing (bcrypt)
```php
$hash = password_hash($password, PASSWORD_DEFAULT);  // saat daftar
password_verify($input, $hash);                       // saat login → true/false
```
Password **tidak pernah disimpan apa adanya**. Disimpan dalam bentuk *hash* (acak satu arah). Bahkan admin/programmer **tidak bisa tahu** password asli pengguna. bcrypt juga **sengaja lambat** supaya serangan tebak-tebakan (*brute force*) butuh waktu sangat lama.

### 3️⃣ Session (menjaga status login)
```php
session_name('sesi_pembeli'); session_start();
$_SESSION['id_user'] = $user['id_user'];
```
HTTP itu "lupa ingatan" (*stateless*) — tiap halaman dianggap pengunjung baru. **Session** mengingat "siapa yang sedang login". Proyek ini memakai **nama session berbeda per role** (`sesi_pembeli`, `sesi_penjual`, `sesi_admin`) supaya pembeli & penjual bisa login bersamaan di browser yang sama tanpa saling menimpa.

### 4️⃣ Pola PRG (Post → Redirect → Get)
Setelah form dikirim (POST) & data disimpan, server **mengarahkan ulang** (`header("Location: ...")`) ke halaman lain. **Tujuan:** kalau pengguna menekan refresh, data **tidak terkirim dua kali** (mencegah pesanan/akun ganda). Hampir semua file `proses*.php` memakai pola ini.

### 5️⃣ Guard (penjaga halaman / otorisasi)
File `guardpembeli.php`, `guardpenjual.php`, `guardadmin.php` di-*include* di **baris paling atas** tiap halaman. Tugasnya: cek "apakah kamu sudah login dan role-mu sesuai?". Kalau tidak → langsung ditendang ke halaman login. **Ini mencegah pembeli membuka halaman admin** hanya dengan mengetik URL.

### 6️⃣ htmlspecialchars() (mencegah XSS)
```php
<?= htmlspecialchars($namapengguna) ?>
```
Mengubah karakter berbahaya (`< > " &`) jadi teks biasa saat ditampilkan. **Mencegah XSS** (*Cross-Site Scripting*) — yaitu kalau ada yang menyisipkan `<script>jahat</script>` ke nama/komentar, script itu **tidak akan dijalankan** browser, hanya ditampilkan sebagai tulisan.

### 7️⃣ Soft Delete (hapus halus)
`UPDATE ... SET deleted=1` alih-alih `DELETE`. Lihat penjelasan di [Bagian 4b](#-konsep-penting-yang-sering-ditanya-juri).

### 8️⃣ Snapshot Data
Menyalin nama & harga saat transaksi ke `tb_detail_order`. Lihat [Bagian 4a](#-konsep-penting-yang-sering-ditanya-juri).

### 9️⃣ Validasi Berlapis (browser + server)
Form HTML punya validasi (`required`, `minlength`, `type="email"`) → cepat & ramah pengguna. **TAPI** validasi sebenarnya tetap diulang di server (`prosesregister.php`), karena validasi browser bisa dimatikan/dilewati lewat DevTools. **Aturan emas: jangan pernah percaya 100% data dari client.**

### 🔟 Single Source of Truth (satu sumber kebenaran)
Setting database **hanya** ditulis di `1. koneksi/koneksi.php`. Mau pindah server/ganti password DB? Cukup ubah 1 file, bukan puluhan file. Ini prinsip **DRY (Don't Repeat Yourself)**.

---

## 6. ALUR AUTENTIKASI

### A. Pendaftaran Pembeli (`register.php` → `prosesregister.php`)
1. Pembeli isi form: nama lengkap, kelas/jurusan (dari dropdown), username, email, password.
2. `prosesregister.php` memvalidasi: tidak kosong, panjang cukup, format email benar (`filter_var`), kelas valid (`kelasValid()`).
3. Cek duplikat username/email.
4. Password di-*hash* (`password_hash`).
5. Disimpan ke `tb_user` dengan `role='pembeli'` dan **`status_verifikasi='pending'`**.
6. Redirect ke login dengan pesan: *"menunggu verifikasi admin"*.

> **Poin penting:** pembeli baru **belum bisa langsung login** — harus diverifikasi admin dulu. Ini menjaga agar hanya warga sekolah asli yang masuk.

### B. Login Pembeli/Penjual (`login.php` → `proseslogin.php`)
1. Cari user berdasarkan username **ATAU** email (`WHERE (username=? OR email=?)`).
2. Verifikasi password (`password_verify`).
3. **Cek gerbang verifikasi:** kalau `pending` → ditolak ("tunggu admin"); kalau `ditolak` → ditolak ("hubungi admin").
4. Admin **dilarang** login lewat sini (harus lewat halaman admin) → pemisahan zona.
5. Tentukan nama session sesuai role (`match`), `session_start()`, simpan data ke `$_SESSION`.
6. **Bonus pembeli:** keranjang dari database dimuat ulang ke session (jadi keranjang tidak hilang setelah logout).
7. Redirect ke dashboard sesuai role.

### C. Login Admin (terpisah: `admin/login/`)
Sama seperti di atas, **tapi**: tidak ada cek verifikasi, dan ada pengecekan ketat **`role === 'admin'`** (pembeli/penjual yang nekat pakai form admin akan ditolak). Session-nya `sesi_admin`.

### D. Logout (`konfirmasilogout.php`)
Ada dialog konfirmasi "Yakin keluar?" (mencegah ketidaksengajaan). Kalau ya → `$_SESSION = []` + `session_destroy()` → redirect ke beranda.

### E. Guard (dijalankan tiap buka halaman)
Selain cek login & role, guard pembeli/penjual juga **mengambil ulang data terbaru dari database** tiap halaman dibuka. Manfaat: kalau admin mengubah/menghapus akun, perubahan **langsung terasa** tanpa perlu logout-login. Kalau akun ternyata sudah dihapus → user otomatis dikeluarkan.

---

## 7. ALUR PEMBELI

### 1. Beranda & Cari Menu (`pembeli/index/index.php`)
- Menampilkan **kantin yang sedang buka**, daftar menu dengan **filter** (kategori, pencarian, per-kantin), dan **5 menu terlaris**.
- Ada penghitung "pesanan aktif" di sapaan.
- Tiap kartu menu punya tombol **+ keranjang**.
- Query memakai JOIN antara `tb_menu` & `tb_toko`, dengan filter `status='aktif'`, `deleted=0`, `status_toko='buka'`.

### 2. Keranjang (`keranjang.php` + `proseskeranjang.php`)
- Keranjang disimpan **dua lapis**: di **session** (cepat diakses) dan di **database `tb_keranjang`** (persisten — tidak hilang saat logout).
- Bisa tambah qty, kurangi, hapus item.
- Keranjang **dikelompokkan per kantin**, karena satu pesanan = satu kantin (tiap kantin proses pesanannya sendiri).
- Subtotal dihitung `harga × jumlah`.

### 3. Checkout (`checkout.php` → `prosespesanan.php`) — INTI TRANSAKSI
Ini bagian terpenting. Saat pembeli checkout, terjadi rangkaian langkah:
1. Hitung total harga.
2. **Buat 1 baris di `tb_order`** (header pesanan: siapa pembeli, kantin mana, total, status awal `Menunggu`, metode `Tunai`).
3. **Untuk tiap item, buat baris di `tb_detail_order`** — termasuk **menyalin nama & harga (snapshot)**.
4. **Kurangi stok** menu di `tb_menu` (`stok = stok - jumlah`).
5. **Kosongkan keranjang** pembeli (session + database).
6. Redirect ke halaman pesanan / struk (pola PRG).

> Metode pembayaran **hanya Tunai** (bayar langsung di kantin) — QRIS/transfer sengaja dihapus karena ini kantin sekolah.

### 4. Pesanan & Status (`pesanan.php`, `detail.php`, `struk.php`)
- Pembeli melihat daftar pesanan (tab **Aktif** / **Riwayat**).
- Status berjalan: **Menunggu → Diproses → Siap Diambil → Selesai** (atau **Dibatalkan**).
- Bisa lihat detail & cetak struk.

### 5. Chat (`pembeli/pesanan/chat.php`)
- Chat **per pesanan** (bukan chat global) — pembeli bisa tanya "pedasnya sedang ya".
- **Tanpa JavaScript:** auto-refresh tiap 15 detik pakai `<meta http-equiv="refresh">`, dan auto-scroll ke pesan terbaru pakai anchor `#latest`.
- Chat otomatis ditutup kalau pesanan sudah Selesai/Dibatalkan (riwayat tetap bisa dibaca).

### 6. Rating (`rating.php` → `prosesrating.php`)
- Setelah pesanan **Selesai**, pembeli bisa beri **bintang 1-5 + komentar**.
- Disimpan ke `tb_rating`, lalu memengaruhi rata-rata rating toko/menu.

### 7. Profil (`profil.php`, `editprofil.php`, `gantipassword.php`)
- Pembeli **hanya boleh** ubah username, email (dan nama/kelas sesuai aturan). Identitas yang sudah diverifikasi admin terkunci.
- Ganti password: verifikasi password lama → hash password baru.

---

## 8. ALUR PENJUAL

### 1. Dashboard (`penjual/index/index.php`)
Menampilkan ringkasan bisnis **real-time**: jumlah pesanan hari ini, omzet, rating toko, rincian status pesanan, grafik pendapatan 7 hari, pesanan terbaru, dan menu terlaris. Semua query disaring `WHERE id_penjual=?` agar **penjual hanya melihat datanya sendiri** (isolasi data).

### 2. Manajemen Menu (`manajemenmenu.php` + `prosesmanajemenmenu.php`)
- **CRUD menu:** Tambah, Edit, Hapus (soft delete), atur **stok**, dan status **aktif/nonaktif**.
- **Upload foto menu** dengan validasi (tipe file gambar, ukuran). Nama file dibuat unik agar tidak bentrok.
- Menu nonaktif/habis stok tidak tampil ke pembeli.

### 3. Manajemen Pesanan (`manajemenpesanan.php` + `proses...`)
- Penjual melihat pesanan masuk dan **mengubah statusnya**: Menunggu → Diproses → Siap Diambil → Selesai/Dibatalkan.
- Bisa cetak struk dapur dan membalas chat pembeli.

### 4. Laporan (`penjual/laporan/laporan.php`)
- Rekap penjualan memakai **agregasi SQL**: `SUM(total)` untuk omzet, `COUNT(*)` untuk jumlah pesanan, `GROUP BY` tanggal/menu.
- Bisa difilter per periode.

### 5. Ulasan (`ulasan.php`)
- Penjual melihat semua rating & komentar pembeli untuk menunya. Rata-rata dihitung `AVG(rating)`.

### 6. Profil Toko (`profil.php`, `proseseditprofil.php`)
- Edit nama toko, **foto toko**, dan buka/tutup toko. (Foto profil hanya ada untuk penjual, lewat `tb_toko.foto_toko`.)

---

## 9. ALUR ADMIN

### 1. Dashboard (`admin/index/index.php`)
Ringkasan **seluruh platform**: jumlah pengguna per role (`GROUP BY role`), omzet global, produk terlaris lintas kantin, performa tiap kantin, dan **form kirim pengumuman** (ke pembeli & penjual).

### 2. Manajemen Pengguna (`admin/manajemenpengguna/`)
- **CRUD lengkap:** lihat (`user.php`), tambah (`tambahuser.php` — 2 langkah: pilih peran → isi form), edit, lihat detail, hapus.
- **Tambah penjual** sekaligus mengisinya ke salah satu **slot kantin kosong** + upload foto toko.
- **Hapus penjual** = `id_user` kantin di-NULL-kan (kantin jadi kosong), **bukan** menghapus kantin.
- **Ekspor data pengguna ke CSV** (`eksporuser.php`) — bisa dibuka di Excel.

### 3. Verifikasi Pembeli (`verifikasi.php` + `prosesverifikasi.php`)
- Halaman khusus berisi pembeli ber-status **pending**.
- Admin **Terima** (→ `verified`, pembeli bisa login) atau **Tolak** (→ `ditolak`).
- Pembeli pending **disembunyikan** dari halaman manajemen pengguna biasa (hanya muncul di sini sampai diproses).

### 4. Manajemen Kantin (`manajementoko/kantin.php`)
- Tampilan **10 slot kantin** (mana yang terisi, mana yang kosong).
- Bisa buka/tutup toko (`prosestoggletoko.php`).

### 5. Laporan Global (`admin/laporan/`)
- Rekap omzet & penjualan **semua kantin** dan **per kantin**.
- Bisa diekspor (`eksporlaporan.php`).

---

## 10. KAITAN DENGAN MATA PELAJARAN UMUM

Bagian ini sering ditanya juri: *"Aplikasimu nyambungnya ke pelajaran apa saja?"*

### 📐 Matematika
- **Aritmetika & logika transaksi:** subtotal = `harga × jumlah`; total = `Σ subtotal`; sisa stok = `stok − jumlah`.
- **Statistika:** laporan memakai **rata-rata** (`AVG` untuk rating), **jumlah** (`SUM` untuk omzet), **frekuensi** (`COUNT` untuk produk terlaris). Ini langsung penerapan materi statistika (mean, modus, penyajian data dalam tabel/grafik).
- **Logika matematika:** kondisi `IF`, operator `AND`/`OR`/`NOT` (mis. `WHERE status='aktif' AND deleted=0`) adalah penerapan **logika proposisi** (konjungsi, disjungsi, negasi).
- **Himpunan & relasi:** relasi antar tabel database = konsep **relasi & fungsi** (pemetaan satu-ke-banyak).

### 📝 Bahasa Indonesia
- **Teks prosedur:** alur "daftar → verifikasi → login → pesan" adalah teks prosedur.
- **Penggunaan bahasa baku & komunikatif** pada pesan error/sukses ("Akunmu sedang menunggu verifikasi admin").
- **Presentasi & laporan:** menyusun laporan proyek dan mempresentasikannya = kompetensi berbicara/menulis ilmiah.

### 🌐 Bahasa Inggris
- Istilah teknis: *username, password, status, order, cart, login, verified.* Memahami dokumentasi PHP (yang berbahasa Inggris) melatih *reading comprehension* teknis.

### 💰 Ekonomi / Kewirausahaan (PKK)
- Konsep **transaksi jual-beli, stok, omzet, laba**.
- **Manajemen persediaan (inventory):** stok berkurang otomatis tiap penjualan.
- **Laporan keuangan sederhana:** omzet per hari/kantin.
- Model bisnis **marketplace/platform** (mempertemukan penjual & pembeli).

### 🏛️ PPKn / Sosial
- **Etika digital & privasi data:** kenapa password di-hash, kenapa data pengguna dilindungi.
- **Verifikasi pengguna** mencerminkan tata tertib & keamanan komunitas sekolah.

---

## 11. KAITAN DENGAN MATA PELAJARAN JURUSAN (RPL)

> Jurusanmu **RPL — Rekayasa Perangkat Lunak**. Proyek ini adalah **gabungan hampir semua mapel produktif RPL**. Ini nilai jual terbesarmu saat presentasi.

### 🗄️ Basis Data (Database)
Inti proyek. Penerapan:
- **Perancangan tabel & tipe data** (int, varchar, enum, decimal, timestamp).
- **Primary Key & Foreign Key**, relasi **one-to-many**.
- **Normalisasi:** memisahkan `tb_order` (header) dan `tb_detail_order` (rincian) supaya tidak ada data berulang — ini bentuk normalisasi.
- **Query SQL:** SELECT, INSERT, UPDATE, JOIN, GROUP BY, agregasi (SUM/COUNT/AVG).

### 💻 Pemrograman Web / PWPB (Pemrograman Web & Perangkat Bergerak)
- **PHP (server-side):** logika backend, mengolah `$_POST`/`$_GET`, koneksi DB.
- **HTML & CSS (client-side):** struktur & tampilan, *responsive design* (media query 768px untuk mobile).
- **Form handling** & metode HTTP (GET vs POST).

### 🔐 Keamanan Sistem / Pemrograman Berorientasi keamanan
- Prepared statement (anti SQL Injection), password hashing, `htmlspecialchars` (anti XSS), validasi server, otorisasi via guard. **Ini topik premium** — kalau juri tanya keamanan, kamu sudah punya 4-5 contoh nyata.

### 📊 Analisis & Perancangan Sistem Informasi (APSI) / Rekayasa Perangkat Lunak
- **Identifikasi aktor & kebutuhan** (3 role: pembeli, penjual, admin).
- **Use case:** daftar, login, pesan, verifikasi, laporan.
- **Alur sistem / flowchart** transaksi (yang ada di dokumen ini).

### 🧩 Pemodelan Perangkat Lunak / Basis Data lanjut
- **ERD (Entity Relationship Diagram)** — relasi 9 tabel di [Bagian 4](#4-skema-database-9-tabel--relasinya).
- **Konsep state/status** pesanan (state machine: Menunggu→Diproses→Siap→Selesai).

### 🛠️ Pemrograman Dasar & Struktur Data
- Array bertingkat (struktur keranjang per-toko), perulangan (`foreach` menampilkan menu), percabangan (`if`/`match`).

### 🚀 Proyek Kreatif & Kewirausahaan (PKK)
- Membangun produk perangkat lunak nyata yang punya **nilai bisnis** dan bisa dipakai di sekolah → persis tujuan PKK.

---

## 12. DAMPAK BAGI PENGGUNA & SEKOLAH

### 👩‍🎓 Bagi Pembeli (murid/guru/staff)
- **Hemat waktu:** pesan dari kelas, ambil saat istirahat — tidak perlu antre lama.
- **Transparan:** lihat menu, harga, stok, dan status pesanan secara real-time.
- **Aman:** identitas terverifikasi, data password terlindungi.
- **Riwayat & struk** tercatat rapi.

### 🧑‍🍳 Bagi Penjual (pemilik kantin)
- **Kelola menu & stok** mudah, tanpa catat manual.
- **Laporan omzet otomatis** → tahu menu terlaris & pendapatan harian.
- **Kurangi salah pesanan** karena tercatat digital + ada chat konfirmasi.
- **Jangkauan lebih luas** — murid yang malas antre jadi mau pesan.

### 🧑‍💼 Bagi Admin/Sekolah
- **Kontrol penuh:** verifikasi pengguna, atur kantin, awasi transaksi.
- **Data terpusat** untuk evaluasi (kantin mana paling ramai, omzet total).
- **Mengurangi keramaian/antrean** di area kantin saat istirahat (dampak ketertiban).
- **Digitalisasi sekolah** — mendukung program sekolah modern & cashless-ready.

### 🌍 Dampak lebih luas
- **Edukasi literasi digital** bagi warga sekolah.
- **Mengurangi penggunaan kertas** (struk digital, laporan digital).
- **Melatih budaya tertib & jujur** (antrean digital, transaksi tercatat).

---

## 13. BANK SOAL TANYA-JAWAB + JAWABAN

> Latihan jawab ini dengan bahasamu sendiri.

**Q: Kenapa pakai PHP murni, bukan framework?**
A: Untuk memahami fundamental (session, koneksi DB, keamanan) tanpa "sihir" framework. Setelah paham dasar, pindah framework lebih mudah.

**Q: Bagaimana aplikasi mencegah peretasan?**
A: Empat lapis: (1) *Prepared statement* lawan SQL Injection, (2) *password hashing* bcrypt supaya password tak bisa dibaca, (3) `htmlspecialchars` lawan XSS, (4) *guard* + role supaya halaman tidak bisa diakses sembarang orang. Plus validasi diulang di server.

**Q: Apa bedanya `tb_order` dan `tb_detail_order`?**
A: `tb_order` = induk/header pesanan (1 baris per pesanan: siapa, total, status). `tb_detail_order` = rincian isi pesanan (banyak baris: tiap menu yang dibeli). Relasi one-to-many. Ini hasil normalisasi agar data tidak berulang.

**Q: Kenapa nama & harga disalin (snapshot) ke detail?**
A: Supaya struk lama tetap akurat walaupun penjual nanti mengubah harga atau nama menu.

**Q: Kenapa data dihapus pakai `deleted=1`, bukan `DELETE`?**
A: Soft delete — supaya pesanan lama yang memakai data itu tidak rusak, bisa di-restore, dan ada jejak audit.

**Q: Bagaimana stok berkurang?**
A: Saat checkout di `prosespesanan.php`, untuk tiap item dijalankan `UPDATE tb_menu SET stok = stok - jumlah`.

**Q: Kenapa pembeli harus diverifikasi admin dulu?**
A: Supaya hanya warga sekolah asli (identitas sesuai) yang bisa bertransaksi — menjaga keamanan & ketertiban.

**Q: Kenapa nama session beda-beda per role?**
A: Supaya pembeli & penjual bisa login bersamaan di satu browser tanpa session-nya saling menimpa.

**Q: Apa itu pola PRG?**
A: Post-Redirect-Get. Setelah simpan data (POST), server redirect ke halaman lain. Kalau user refresh, data tidak terkirim dua kali (cegah pesanan/akun ganda).

**Q: Bagaimana laporan dihitung?**
A: Dengan agregasi SQL: `SUM` (omzet), `COUNT` (jumlah pesanan), `AVG` (rata-rata rating), dikelompokkan `GROUP BY` tanggal/kantin/menu.

**Q: Kenapa chat tanpa JavaScript?**
A: Filosofi proyek menjaga logika di server. Auto-refresh pakai `<meta refresh>` 15 detik, auto-scroll pakai anchor `#latest`.

---

## 14. KETERBATASAN & RENCANA PENGEMBANGAN

Menyebutkan keterbatasan dengan jujur justru **menambah nilai** (menunjukkan kamu paham, bukan asal jadi).

**Keterbatasan saat ini:**
- Pembayaran baru **tunai** (belum QRIS/e-wallet).
- Bug kecil: toko yang ditutup penjual masih bisa menerima order (belum diperbaiki).
- Notifikasi belum *real-time* (chat & status pakai auto-refresh, belum WebSocket/push).
- Belum ada fitur reset password mandiri (masih lewat WhatsApp admin).

**Rencana pengembangan:**
- Integrasi pembayaran digital (QRIS).
- Notifikasi *push* / real-time.
- Aplikasi mobile (Android).
- Dashboard analitik lebih lengkap (prediksi menu laris).
- Fitur antrean & estimasi waktu tunggu.

---

---

## 15. ⭐ PETA DETAIL PER HALAMAN

> **Bagian ini menjawab persis permintaanmu:** untuk SETIAP file/halaman dijelaskan — terhubung ke halaman apa saja (link masuk, link keluar, form, redirect) dan **menyentuh tabel database mana** (operasi SELECT/INSERT/UPDATE-nya). Karena sangat panjang, isinya dipecah ke **4 dokumen pendamping** agar tidak satu file raksasa yang berat dibuka. Semua ada di folder yang sama dengan dokumen ini.

### 📂 Dokumen pendamping (buka & pelajari satu per satu)

| File | Isi | Jumlah halaman dipetakan |
|------|-----|--------------------------|
| **`PETA_01_AUTENTIKASI_DAN_KOMPONEN.md`** | Landing page, koneksi, semua navbar, semua guard, helper kelas/jurusan, pengumuman, daftar/login/logout, login admin | 17 file |
| **`PETA_02_PEMBELI.md`** | Beranda, keranjang, checkout, proses pesanan, daftar/detail pesanan, struk, rating, chat, profil | 14 file |
| **`PETA_03_PENJUAL.md`** | Dashboard, manajemen menu, manajemen pesanan, struk, chat, laporan, ulasan, profil | 13 file |
| **`PETA_04_ADMIN.md`** | Dashboard, pengumuman, manajemen pengguna (CRUD), verifikasi pembeli, manajemen kantin, laporan, ekspor CSV | 18 file |

**Format tiap file di dokumen pendamping** (hafalkan urutan ini, karena polanya sama untuk semua halaman):
- **Jenis** (Tampilan / Proses / Komponen)
- **Tujuan singkat**
- **Komponen yang di-include** (guard, koneksi, navbar)
- **Diakses / dipanggil dari** (link masuk)
- **Link keluar** (`<a href>` ke halaman lain)
- **Form action & method** (POST/GET ke file mana)
- **Redirect** (`header Location`) + kondisinya
- **Tabel database & operasinya** (SELECT/INSERT/UPDATE + kolom + JOIN)
- **Catatan teknik penting**

---

### 🗺️ PETA NAVIGASI GLOBAL (ringkasan "halaman → halaman")

Ini gambaran besar siapa terhubung ke siapa. Detail lengkap ada di dokumen pendamping.

**Pintu masuk:**
```
index.php (landing)
   ├─→ 4. autentifikasi/login.php        (pembeli & penjual)
   │       ├─→ register.php → prosesregister.php → (balik) login.php
   │       └─→ proseslogin.php → dashboard pembeli / penjual
   └─→ admin/login/loginadmin.php → prosesloginadmin.php → dashboard admin
```

**Alur Pembeli:**
```
pembeli/index/index.php  ──(form POST)──→  keranjang/proseskeranjang.php ──redirect──→ keranjang.php
        │                                                                                    │
        ├──→ pesanan/detail.php ──(POST)──→ proseskeranjang.php                              │
        └──→ pesanan/pesanan.php                                  keranjang.php ──→ pesanan/checkout.php?toko=X
                  │                                                                          │
                  ├──→ pesanan/chat.php (#latest, refresh 15 dtk)        (form POST) ────────┘
                  ├──→ pesanan/struk.php                                          ↓
                  └──→ pesanan/rating.php ──(POST)──→ prosesrating.php    pesanan/prosespesanan.php
                                                          │                       │ (sukses, PRG)
                                                          └──→ pesanan.php?tab=riwayat ←── struk.php?id_order=..&baru=1
profil/profil.php ──→ editprofil.php (& gantipassword.php)
```

**Alur Penjual:** (navbar menghubungkan semua halaman utama)
```
penjual/index/index.php (dashboard)
   ├─→ manajemenmenu/manajemenmenu.php ──(POST)──→ prosesmanajemenmenu.php ──redirect──→ manajemenmenu.php
   ├─→ manajemenpesanan/manajemenpesanan.php
   │        ├──(POST ubah status)──→ prosesmanajemenpesanan.php ──redirect──→ manajemenpesanan.php
   │        ├──→ struk.php
   │        └──→ chat.php  (UI + proses chat jadi satu file)
   ├─→ laporan/laporan.php
   ├─→ ulasan/ulasan.php
   └─→ profil/profil.php ──(POST)──→ proseseditprofil.php / prosesgantipassword.php
```

**Alur Admin:** (navbar menghubungkan semua halaman utama)
```
admin/index/index.php (dashboard) ──(POST)──→ proses_pengumuman.php / proses_pengumuman_penjual.php
   ├─→ manajemenpengguna/user.php
   │        ├─→ tambahuser.php ──(POST)──→ prosestambahuser.php ─→ user.php
   │        ├─→ edituser.php   ──(POST)──→ prosesedituser.php   ─→ user.php
   │        ├─→ viewuser.php
   │        ├─→ hapususer.php  ──(POST)──→ proseshapususer.php  ─→ user.php
   │        └─→ eksporuser.php (download CSV)
   ├─→ manajemenpengguna/verifikasi.php ──(POST)──→ prosesverifikasi.php ─→ verifikasi.php
   ├─→ manajementoko/kantin.php ──(POST)──→ prosestoggletoko.php ─→ kantin.php
   └─→ laporan/laporan.php ─→ eksporlaporan.php (download CSV)
```

---

### 🗄️ MATRIKS AKSES DATABASE (tabel mana disentuh oleh proses apa)

Semua proses terhubung ke **satu database: `e_kantin`** (lewat `$conn` dari `1. koneksi/koneksi.php`). Yang berbeda adalah **tabel** yang disentuh:

| Proses / Halaman | tb_user | tb_toko | tb_menu | tb_keranjang | tb_order | tb_detail_order | tb_rating | tb_chat | tb_riwayat_toko |
|------------------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| prosesregister | INSERT | | | | | | | | |
| proseslogin | SELECT | SELECT | SELECT(J) | SELECT(J) | | | | | |
| proseskeranjang | | SELECT | SELECT(J) | INS/DEL | | | | | |
| **prosespesanan (checkout)** | | SELECT | SELECT+UPDATE stok | DELETE | **INSERT** | **INSERT** | | | |
| pesanan/detail/struk (pembeli) | | | | | SELECT | SELECT | SELECT | | |
| prosesrating | | | | | SELECT | | INSERT | | |
| chat (pembeli & penjual) | SELECT | | | | SELECT | | | INS/UPD/SEL | |
| penjual/index (dashboard) | | SELECT | SELECT | | SELECT(agg) | SELECT(agg) | SELECT(AVG) | | |
| prosesmanajemenmenu | | | INS/UPD/soft-del + stok | | | | | | |
| prosesmanajemenpesanan | | | UPDATE stok (saat batal) | | UPDATE status_order | SELECT | | | |
| penjual/laporan | | SELECT | SELECT | | SELECT(agg) | SELECT(agg) | | | |
| penjual/ulasan | | | SELECT | | SELECT | | SELECT(AVG) | | |
| admin/index (dashboard) | SELECT(agg) | SELECT | SELECT | | SELECT(agg) | SELECT(agg) | SELECT | | |
| prosestambahuser | INSERT | UPDATE (isi kantin) | | | | | | | |
| prosesedituser | UPDATE | UPDATE | | | | | | | |
| **proseshapususer** | UPDATE (soft-del) | UPDATE (kosongkan: id_user=NULL) | soft-del | | | | | | INSERT (snapshot) |
| prosesverifikasi | UPDATE status_verifikasi | | | | | | | | |
| prosestoggletoko | | UPDATE status_toko | | | | | | | |
| admin/laporan + ekspor | | SELECT | SELECT | | SELECT(agg) | SELECT(agg) | | | |
| eksporuser | SELECT | | | | | | | | |
| proses_pengumuman | _(tidak ke DB — simpan ke file .txt)_ | | | | | | | | |

> **Keterangan:** `(J)` = lewat JOIN; `(agg)` = agregasi (SUM/COUNT/AVG/GROUP BY); `INS/UPD/DEL` = INSERT/UPDATE/DELETE; `soft-del` = UPDATE deleted=1.
>
> **Catatan menarik untuk presentasi:** `keranjang.php` & `checkout.php` **TIDAK menyentuh database** sama sekali — datanya murni dari `$_SESSION['keranjang']`. Database baru disentuh saat **`prosespesanan.php`** (saat benar-benar memesan). Dan **fitur pengumuman tidak pakai database** — disimpan ke file teks (`.txt`). Hal-hal "tak terduga" seperti ini bagus untuk menunjukkan kamu paham detail.

---

### 📖 CARA BELAJAR PAKAI DOKUMEN INI (saran urutan)

1. **Baca dulu `PANDUAN_BELAJAR_E-KANTIN.md` ini** dari Bagian 1–14 → dapat gambaran besar + konsep.
2. **Hafalkan [10 Konsep Kunci](#5-sepuluh-konsep-teknis-kunci)** → ini fondasi semua jawaban.
3. **Buka dokumen pendamping (`PETA_..`)** sambil **membuka file kode aslinya** berdampingan → cocokkan penjelasan dengan baris kode sungguhan.
4. **Latih alur transaksi** (checkout → prosespesanan) sampai hafal urutannya: cek toko → cek stok → INSERT order → INSERT detail → kurangi stok → hapus keranjang. Ini bagian paling sering ditanya.
5. **Latih [Bank Soal](#13-bank-soal-tanya-jawab--jawaban)** dengan menjawab pakai bahasamu sendiri (jangan menghafal kalimat).
6. **Siapkan 1 alur untuk didemokan langsung** saat presentasi (mis. pesan makanan dari awal sampai struk) — lebih meyakinkan daripada hanya bicara.

---

> **Penutup untuk presentasi:**
> *"E-Kantin adalah penerapan nyata ilmu RPL — dari perancangan database, pemrograman web, sampai keamanan sistem — untuk menyelesaikan masalah sehari-hari di sekolah: antrean kantin. Aplikasi ini bukan cuma tugas, tapi solusi yang bisa benar-benar dipakai."*

**Semangat presentasinya! 🚀 Kamu pasti bisa.**
