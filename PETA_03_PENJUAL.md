# PETA KONEKSI & DATABASE — FOLDER PENJUAL

Dokumen ini memetakan setiap file di folder `penjual\`: jenis file, include, link masuk/keluar, form, redirect, tabel database beserta operasinya, dan catatan teknik. Tujuannya supaya kamu paham bagaimana semua halaman penjual saling terhubung dan tabel apa yang disentuh.

Catatan umum (berlaku untuk hampir semua file):
- Semua file meng-include `1. koneksi/koneksi.php` (menyediakan `$conn` mysqli) dan `3. komponen/guardpenjual.php` (memastikan hanya penjual yang login bisa mengakses; menyediakan `$_SESSION['id_user']`, `id_toko`, `nama_toko`, `username`, `status_toko`).
- Halaman tampilan meng-include `3. komponen/navbarpenjual.php` dan memakai `$halamansaatini` untuk menandai menu navbar aktif.
- **Isolasi data**: query pesanan/laporan/ulasan selalu memakai `WHERE id_penjual=?` (dari session), sedangkan query menu memakai `WHERE id_toko=?`. Ini mencegah penjual melihat data milik penjual lain.
- **PRG (Post/Redirect/Get)**: semua file `proses*.php` mengakhiri dengan `header("Location: ...")` lalu `exit` supaya refresh tidak mengulang aksi. Pesan hasil disimpan via `$_SESSION['flash']` (pola flash message).

---

### penjual\index\index.php
- **Jenis:** Tampilan (UI) — dashboard.
- **Tujuan singkat:** Menampilkan statistik harian, grafik omset 7 hari, breakdown status, pesanan terbaru, produk terlaris, pelanggan setia, dan ulasan terbaru untuk penjual yang login.
- **Komponen yang di-include:** koneksi.php, guardpenjual.php, navbarpenjual.php.
- **Diakses / dipanggil dari:** navbar penjual (menu Dashboard); redirect dari `prosesindex.php`; halaman login penjual (entry point setelah login).
- **Link keluar (href ke halaman lain):**
  - `../manajemenpesanan/manajemenpesanan.php` (beberapa, termasuk `?filter=Menunggu`)
  - `../laporan/laporan.php`
  - `../ulasan/ulasan.php`
  - `../manajemenmenu/manajemenmenu.php`
- **Form action (tujuan submit) & method:** satu form ke `prosesindex.php` (POST) dengan `aksi=toggle_status` — tombol toggle buka/tutup toko.
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:**
  - `tb_order` — SELECT (berkali-kali, semua `WHERE id_penjual=? AND deleted=0`):
    - COUNT pesanan hari ini (`DATE(tanggal_order)=CURDATE()`), COUNT `status_order='Menunggu'`, COUNT `status_order='Diproses'`.
    - `COALESCE(SUM(total_harga),0)` omset hari ini (`status_order='Selesai'`).
    - Breakdown: `GROUP BY status_order` ambil COUNT + SUM(total_harga).
    - Chart: `SUM(total_harga) ... WHERE DATE(tanggal_order) BETWEEN ? AND ? AND status_order='Selesai' GROUP BY DATE(tanggal_order)`.
    - 5 pesanan terbaru: JOIN `tb_user` (`o.id_user=u.id_user`) ambil username, urut `FIELD(status_order, ...)` lalu `tanggal_order DESC`.
    - Top 5 pelanggan: JOIN `tb_user`, `GROUP BY id_user`, agregasi COUNT(id_order) + SUM(total_harga), urut jml_order DESC.
  - `tb_rating` — SELECT: `ROUND(AVG(rating_toko),1), COUNT(*)` (`WHERE id_penjual=?`); 5 ulasan terbaru JOIN `tb_user`.
  - `tb_menu` — SELECT COUNT menu aktif (`WHERE id_toko=? AND status='aktif' AND deleted=0`).
  - `tb_detail_order` — SELECT produk terlaris: JOIN `tb_menu` (`d.id_menu=m.id_menu`) + JOIN `tb_order` (`d.id_order=o.id_order`), agregasi `SUM(d.jumlah)`, `SUM(d.subtotal)`, `GROUP BY m.id_menu` (hanya `o.status_order='Selesai'`).
- **Catatan teknik penting:** semua query pakai prepared statement (`bind_param("i", $idpenjual)`). Isolasi data via `id_penjual`/`id_toko`. Chart digambar SVG murni (tanpa library JS). Tidak menulis DB.

---

### penjual\index\prosesindex.php
- **Jenis:** Proses (logika).
- **Tujuan singkat:** Memproses toggle status buka/tutup toko dari dashboard lalu kembali ke dashboard.
- **Komponen yang di-include:** koneksi.php, guardpenjual.php. (tidak ada navbar)
- **Diakses / dipanggil dari:** form POST di `index.php`.
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** tidak ada form (file proses).
- **Redirect (header Location):** selalu `index.php` setelah selesai (baik aksi dijalankan maupun tidak).
- **Tabel database & operasinya:**
  - `tb_toko` — UPDATE `status_toko` (`SET status_toko=? WHERE id_toko=?`); status dibalik antara `'buka'`/`'tutup'`.
- **Catatan teknik penting:** prepared statement; cek `REQUEST_METHOD==='POST'` dan `aksi==='toggle_status'`; status baru juga disimpan ke `$_SESSION['status_toko']` agar navbar langsung sinkron. PRG murni.

---

### penjual\manajemenmenu\manajemenmenu.php
- **Jenis:** Tampilan (UI).
- **Tujuan singkat:** Menampilkan daftar menu toko dalam grid kartu dengan filter kategori, pencarian, serta modal tambah/edit/hapus (CSS `:target`, tanpa JS).
- **Komponen yang di-include:** koneksi.php, guardpenjual.php, navbarpenjual.php.
- **Diakses / dipanggil dari:** navbar penjual (menu Kelola Menu); kartu "Menu Aktif" di `index.php`; redirect dari `prosesmanajemenmenu.php`.
- **Link keluar (href ke halaman lain):**
  - `manajemenmenu.php` (filter kategori, pencarian, `?edit=ID#modal-edit`, `?hapus=ID#konfirm-hapus`) — link internal halaman ini.
  - `prosesmanajemenmenu.php?aksi=toggle&id=...` (toggle aktif/nonaktif, GET)
  - `prosesmanajemenmenu.php?aksi=hapus&id=...` (konfirmasi hapus, GET)
  - `#modal-tambah` (anchor membuka modal)
- **Form action (tujuan submit) & method:**
  - Form pencarian → `manajemenmenu.php` (GET; field `cari`, hidden `filter`).
  - Form modal tambah → `prosesmanajemenmenu.php` (POST, `enctype=multipart/form-data`, `aksi=tambah`).
  - Form modal edit → `prosesmanajemenmenu.php` (POST, `enctype=multipart/form-data`, `aksi=edit`, hidden `id_menu` & `foto_lama`).
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:**
  - `tb_menu` — SELECT:
    - `SELECT * FROM tb_menu WHERE id_toko=$idtoko AND deleted=0 [+ kategori][+ nama_menu LIKE]` ORDER BY `created DESC` (daftar grid).
    - Jika `?edit=`: prepared `SELECT * ... WHERE id_menu=? AND id_toko=? AND deleted=0` untuk mengisi form edit.
- **Catatan teknik penting:** query daftar memakai string interpolasi tapi nilai di-escape dengan `real_escape_string` (filter & cari); query edit pakai prepared statement. Modal full CSS `:target` (`#modal-tambah`, `#modal-edit`, `#konfirm-hapus`). Validasi upload (accept tipe gambar) hanya di sisi HTML; soft-delete ditangani di file proses. Tidak menulis DB di file ini.

---

### penjual\manajemenmenu\prosesmanajemenmenu.php
- **Jenis:** Proses (logika).
- **Tujuan singkat:** Menangani semua aksi menu — tambah, edit, toggle status, dan hapus (soft delete) — lalu redirect kembali ke daftar menu.
- **Komponen yang di-include:** koneksi.php, guardpenjual.php. (tidak ada navbar)
- **Diakses / dipanggil dari:** form POST (tambah/edit) dan link GET (toggle/hapus) dari `manajemenmenu.php`.
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** tidak ada form (file proses).
- **Redirect (header Location):** selalu kembali ke `manajemenmenu.php?filter=...` (fungsi `kembali()`). Jika aksi gagal/sukses, pesan disimpan ke flash dulu.
- **Tabel database & operasinya:**
  - `tb_menu`:
    - **INSERT** (aksi `tambah`): kolom `nama_menu, harga, stok, kategori, deskripsi, foto, status('aktif'), id_toko, id_penjual` — mencatat pembuat menu.
    - **UPDATE** (aksi `edit`): `nama_menu, harga, stok, kategori, deskripsi, foto, updated=NOW()` `WHERE id_menu=? AND id_toko=?`; sebelum update ada SELECT cek kepemilikan (`WHERE id_menu=? AND id_toko=? AND deleted=0`).
    - **UPDATE status** (aksi `toggle`): SELECT `status` lalu UPDATE `status` (aktif↔nonaktif), `updated=NOW()`, `WHERE id_menu=? AND id_toko=?`.
    - **Soft delete** (aksi `hapus`): UPDATE `deleted=1, deleted_at=NOW(), status='nonaktif'` `WHERE id_menu=? AND id_toko=?` (sebelumnya SELECT cek kepemilikan).
- **Catatan teknik penting:** semua query prepared statement. **Upload foto**: validasi MIME (`image/jpeg|png|webp`) + ukuran maks 2MB, nama file unik `uniqid().ext`, `move_uploaded_file` ke `2. aset/katalog/`; saat edit tanpa foto baru memakai `foto_lama` dari hidden field. Validasi server-side ketat (nama, kategori whitelist, harga/stok range). Isolasi via `id_toko`. Error ditangkap `try/catch` → flash gagal. PRG.

---

### penjual\manajemenpesanan\manajemenpesanan.php
- **Jenis:** Tampilan (UI).
- **Tujuan singkat:** Menampilkan semua pesanan masuk dengan tab filter status + badge jumlah, pencarian, dan tombol aksi berbeda per status (proses/siap/selesai/batal/chat/struk).
- **Komponen yang di-include:** koneksi.php, guardpenjual.php, navbarpenjual.php.
- **Diakses / dipanggil dari:** navbar penjual (menu Pesanan Masuk); kartu/link di `index.php` (termasuk `?filter=Menunggu`); redirect dari `prosesmanajemenpesanan.php`, `struk.php`, `chat.php`.
- **Link keluar (href ke halaman lain):**
  - `manajemenpesanan.php?filter=...` (tab filter, hapus pencarian)
  - `chat.php?id_order=...#latest` (saat status aktif)
  - `prosesmanajemenpesanan.php?aksi=proses|batal|siap|selesai&id=...&filter=...` (GET)
  - `struk.php?id=...`
- **Form action (tujuan submit) & method:** form pencarian → `manajemenpesanan.php` (GET; field `cari`, hidden `filter`).
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:**
  - `tb_order` — SELECT:
    - COUNT per status untuk badge (loop, prepared `WHERE id_penjual=? AND status_order=? AND deleted=0`).
    - Daftar pesanan: `SELECT o.*, u.username, u.email FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user WHERE o.id_penjual=$idpenjual ... ORDER BY FIELD(status_order,...), tanggal_order DESC`.
  - `tb_detail_order` — SELECT per pesanan (loop): JOIN `tb_menu` (`d.id_menu=m.id_menu`) ambil `jumlah, harga_satuan, subtotal, nama_menu` `WHERE d.id_order=? AND d.deleted=0`.
- **Catatan teknik penting:** query badge & detail item pakai prepared statement; query daftar pakai string dengan `real_escape_string` untuk filter & cari. Tombol aksi dirender kondisional sesuai `status_order`. Tombol chat hanya muncul saat status Menunggu/Diproses/Siap Diambil. Tidak menulis DB di file ini.

---

### penjual\manajemenpesanan\prosesmanajemenpesanan.php
- **Jenis:** Proses (logika).
- **Tujuan singkat:** Memproses perubahan status pesanan (proses/siap/selesai/batal) dengan validasi transisi status, dan mengembalikan stok jika dibatalkan.
- **Komponen yang di-include:** koneksi.php, guardpenjual.php. (tidak ada navbar)
- **Diakses / dipanggil dari:** link GET dari `manajemenpesanan.php` (tombol Proses/Siap Diambil/Selesai/Batalkan).
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** tidak ada form (file proses, dipicu via GET).
- **Redirect (header Location):** selalu `manajemenpesanan.php?filter=...` (saat id invalid, pesanan tidak ditemukan, aksi tidak dikenali, transisi tidak diizinkan, atau sukses). Pesan disimpan ke flash.
- **Tabel database & operasinya:**
  - `tb_order`:
    - **SELECT** `id_order, status_order WHERE id_order=? AND id_penjual=? AND deleted=0` (cek kepemilikan & status saat ini).
    - **UPDATE status pesanan**: `SET status_order=?, updated=NOW() WHERE id_order=?` ke status baru sesuai peta transisi.
  - `tb_detail_order` — SELECT `id_menu, jumlah WHERE id_order=? AND deleted=0` (hanya saat aksi `batal`, untuk daftar item yang dikembalikan).
  - `tb_menu` — **UPDATE stok** (hanya saat `batal`): `SET stok = stok + ? WHERE id_menu=?` untuk tiap item (kembalikan stok).
- **Catatan teknik penting:** peta `$transisi` membatasi transisi yang sah (mis. `proses` hanya dari `Menunggu`). Cek kepemilikan `id_penjual` mencegah manipulasi pesanan orang lain. Semua prepared statement. PRG dengan flash.

---

### penjual\manajemenpesanan\struk.php
- **Jenis:** Tampilan (UI).
- **Tujuan singkat:** Menampilkan struk digital satu pesanan (identik dengan struk pembeli) lengkap nomor antrian, bisa dicetak via `window.print()`.
- **Komponen yang di-include:** koneksi.php, guardpenjual.php, navbarpenjual.php.
- **Diakses / dipanggil dari:** tombol "Cetak Struk" di `manajemenpesanan.php` (`struk.php?id=...`).
- **Link keluar (href ke halaman lain):** `manajemenpesanan.php` (tombol Kembali).
- **Form action (tujuan submit) & method:** tidak ada form. Tombol cetak memakai `onclick="window.print()"` (JS inline, bukan submit).
- **Redirect (header Location):** ke `manajemenpesanan.php` jika `id` kosong atau pesanan tidak ditemukan/bukan milik penjual ini.
- **Tabel database & operasinya:**
  - `tb_order` — SELECT `o.*, u.username` JOIN `tb_user` `WHERE o.id_order=? AND o.id_penjual=? AND o.deleted=0`; SELECT COUNT antrian `WHERE id_penjual=? AND DATE(tanggal_order)=DATE(?) AND id_order<=? AND deleted=0`.
  - `tb_detail_order` — SELECT `d.*, m.nama_menu` JOIN `tb_menu` (`d.id_menu=m.id_menu`) `WHERE d.id_order=? AND d.deleted=0`.
- **Catatan teknik penting:** semua prepared statement; cek kepemilikan via `id_penjual`. Nomor antrian dihitung per `id_penjual` (bukan per slot toko) supaya penjual baru di slot bekas tidak mewarisi antrian lama. Kelas `takprint` menyembunyikan elemen saat dicetak. Tidak menulis DB.

---

### penjual\manajemenpesanan\chat.php
- **Jenis:** Tampilan (UI) + Proses (logika) — file gabungan (render chat sekaligus memproses POST kirim pesan).
- **Tujuan singkat:** Chat penjual↔pembeli per pesanan; menampilkan riwayat + kartu identitas/detail pesanan, dan menyimpan pesan baru selama pesanan masih aktif.
- **Komponen yang di-include:** guardpenjual.php, koneksi.php, navbarpenjual.php.
- **Diakses / dipanggil dari:** tombol "Chat Pembeli" di `manajemenpesanan.php` (`chat.php?id_order=...#latest`).
- **Link keluar (href ke halaman lain):** `manajemenpesanan.php` (tombol Kembali).
- **Form action (tujuan submit) & method:** form kirim pesan → `chat.php` (POST; field `pesan`, hidden `id_order`). Hanya dirender jika status aktif.
- **Redirect (header Location):**
  - `manajemenpesanan.php` jika `id_order` kosong atau pesanan tidak ditemukan/bukan milik penjual.
  - Setelah POST kirim pesan → `chat.php?id_order=...#latest` (PRG).
- **Tabel database & operasinya:**
  - `tb_order` — SELECT data pesanan + `nama_toko_snapshot`, JOIN `tb_user` ambil `username, nama_lengkap, kelas` `WHERE o.id_order=? AND o.id_penjual=? AND o.deleted=0`.
  - `tb_detail_order` — SELECT item: `COALESCE(d.nama_menu_snapshot, m.nama_menu)` LEFT JOIN `tb_menu` `WHERE d.id_order=? AND d.deleted=0`.
  - `tb_chat`:
    - **INSERT** (saat POST): `id_order, id_pengirim, pesan` (`id_pengirim=idpenjual`).
    - **SELECT** semua pesan `WHERE id_order=?` ORDER BY `created ASC, id_chat ASC`.
    - **UPDATE** `dibaca=1` untuk pesan dari pembeli (`WHERE id_order=? AND id_pengirim=$idpembeli AND dibaca=0`) — menandai sudah dibaca.
- **Catatan teknik penting:** semua prepared statement; isolasi `id_penjual` mencegah intip chat lewat tebak `id_order`. Kirim pesan dibatasi 500 karakter dan hanya saat status aktif. PRG dengan anchor `#latest`. Auto-refresh `<meta http-equiv="refresh" content="15">` saat status aktif (polling tanpa JS). LEFT JOIN + snapshot menjaga item tetap tampil walau menu dihapus.

---

### penjual\laporan\laporan.php
- **Jenis:** Tampilan (UI).
- **Tujuan singkat:** Laporan penjualan dengan filter periode (7/14/30 hari/custom): ringkasan statistik, grafik omset, top pelanggan, rating & ulasan, produk terlaris, daftar menu, dan detail pesanan; bisa diekspor ke XLS/cetak.
- **Komponen yang di-include:** koneksi.php, guardpenjual.php, navbarpenjual.php.
- **Diakses / dipanggil dari:** navbar penjual (menu Laporan); banyak link "Lihat Detail/Laporan" di `index.php` (kartu omset, chart, produk terlaris, top pelanggan).
- **Link keluar (href ke halaman lain):** `laporan.php?periode=7|14|30|custom...` (tab filter periode, link internal).
- **Form action (tujuan submit) & method:** form periode custom → `laporan.php` (GET; field `dari`, `sampai`, hidden `periode=custom`).
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:** (semua SELECT, isolasi `id_penjual`, rentang `DATE(tanggal_order) BETWEEN ? AND ?`)
  - `tb_order`:
    - `SUM(total_harga), COUNT(*)` pesanan `Selesai` (omset + jml selesai).
    - `COUNT(*)` semua + `SUM(CASE WHEN status_order='Dibatalkan' ...)` (total & jml dibatalkan).
    - `SUM(total_harga)` pesanan `Dibatalkan` (nilai dibatalkan).
    - `COUNT(DISTINCT id_user)` pesanan `Selesai` (pembeli unik).
    - Top pelanggan: JOIN `tb_user`, `GROUP BY id_user`, COUNT(id_order) + SUM(total_harga), LIMIT 10.
    - Chart: `SUM(total_harga) ... status_order='Selesai' GROUP BY DATE(tanggal_order)`.
    - Detail pesanan: JOIN `tb_user`, LEFT JOIN `tb_detail_order` + LEFT JOIN `tb_menu`, status IN ('Selesai','Dibatalkan'), LIMIT 500.
  - `tb_detail_order` (+ JOIN `tb_menu` + JOIN `tb_order`): produk terlaris `SUM(jumlah) AS terjual, SUM(subtotal) AS omset GROUP BY m.id_menu` LIMIT 10 (status `Selesai`).
  - `tb_menu`: daftar semua menu penjual (`WHERE m.id_penjual=?`, termasuk yang `deleted`), dengan sub-query berkorelasi `SUM(d.jumlah)` untuk `terjual_periode` dan `terjual_total`.
  - `tb_rating`: `AVG(rating_toko), COUNT(*)` periode; distribusi `GROUP BY rating_toko`; 10 ulasan terbaru JOIN `tb_user` + sub-query `GROUP_CONCAT(COALESCE(nama_menu_snapshot, m2.nama_menu))` (menu yang dipesan saat ulasan).
- **Catatan teknik penting:** semua prepared statement dengan binding tanggal. Banyak **agregasi**: SUM, COUNT, COUNT(DISTINCT), AVG, GROUP BY, GROUP_CONCAT, sub-query berkorelasi. Pakai `nama_menu_snapshot` agar laporan tetap akurat walau menu dihapus. Validasi format tanggal custom (regex `Y-m-d`) + swap jika `dari>sampai`. Ekspor XLS murni client-side (JS bungkus tabel HTML jadi `application/vnd.ms-excel`). Tidak menulis DB.

---

### penjual\ulasan\ulasan.php
- **Jenis:** Tampilan (UI).
- **Tujuan singkat:** Menampilkan semua ulasan/rating toko: ringkasan rata-rata + bar distribusi bintang, filter per bintang, pencarian, dan daftar ulasan dengan paginasi.
- **Komponen yang di-include:** koneksi.php, guardpenjual.php, navbarpenjual.php.
- **Diakses / dipanggil dari:** navbar penjual (menu Ulasan); kartu "Rating Toko" dan "Lihat Semua" ulasan di `index.php`.
- **Link keluar (href ke halaman lain):** `ulasan.php?bintang=...`, `?hal=...`, `?cari=...` (filter, paginasi, reset — semua link internal).
- **Form action (tujuan submit) & method:** form pencarian → `ulasan.php` (GET; field `cari`, hidden `bintang` bila aktif).
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:** (semua SELECT)
  - `tb_rating` (JOIN `tb_user` via `r.id_user=u.id_user`, kadang LEFT JOIN `tb_order` via `r.id_order=o.id_order`):
    - `COUNT(*)` total ulasan sesuai kondisi (untuk paginasi).
    - Daftar ulasan: `r.id_rating, r.rating_toko, r.ulasan, r.created, u.username, o.id_order` `WHERE r.id_penjual=$idpenjual AND r.deleted=0 [+ rating_toko][+ LIKE]` ORDER BY `r.created DESC` LIMIT/OFFSET.
    - Distribusi: `rating_toko, COUNT(*) GROUP BY rating_toko`.
    - Rata-rata: `ROUND(AVG(rating_toko),1), COUNT(*)`.
- **Catatan teknik penting:** memakai `$conn->query()` (bukan prepared) tetapi nilai pencarian di-escape dengan `real_escape_string`; `id_penjual` dan `bintang` di-cast `(int)`. Paginasi via `LIMIT $perhal OFFSET $offset` (15/halaman). Isolasi via `id_penjual`. Tidak menulis DB.

---

### penjual\profil\profil.php
- **Jenis:** Tampilan (UI).
- **Tujuan singkat:** Halaman profil penjual dengan 3 tab (profil/edit/password via `?tab=`): menampilkan statistik toko, hero profil, dan form edit profil-toko & ganti password.
- **Komponen yang di-include:** koneksi.php, guardpenjual.php, navbarpenjual.php.
- **Diakses / dipanggil dari:** navbar penjual (menu Profil); redirect dari `proseseditprofil.php` & `prosesgantipassword.php`.
- **Link keluar (href ke halaman lain):**
  - `profil.php?tab=edit`, `profil.php?tab=password`, `profil.php` (navigasi tab/batal — internal).
  - `../../4. autentifikasi/konfirmasilogout.php?peran=penjual` (logout, hanya mobile).
  - `#modal-kontak` (hubungi admin, anchor; modal diasumsikan dari navbar).
- **Form action (tujuan submit) & method:**
  - Tab edit → `proseseditprofil.php` (POST, `enctype=multipart/form-data`; field username, email, nama_toko, foto_toko, hapus_foto).
  - Tab password → `prosesgantipassword.php` (POST; field password_lama, password_baru, konfirmasi).
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:** (semua SELECT)
  - `tb_user` — `SELECT * WHERE id_user=? AND deleted=0` (data akun).
  - `tb_toko` — `SELECT * WHERE id_toko=? AND deleted=0` (data toko, foto_toko).
  - `tb_order` — COUNT total pesanan (`WHERE id_penjual=?`); `COALESCE(SUM(total_harga),0)` pesanan `Selesai`.
  - `tb_rating` — `ROUND(AVG(rating_toko),1), COUNT(*) WHERE id_penjual=?`.
  - `tb_menu` — COUNT menu aktif (`WHERE id_toko=? AND status='aktif' AND deleted=0`).
- **Catatan teknik penting:** semua prepared statement. Foto toko dari `2. aset/profil/`, fallback `profilwarung.png` jika tidak ada. Toggle password pakai JS inline (ikon mata). Validasi tab dari whitelist. Tidak menulis DB.

---

### penjual\profil\proseseditprofil.php
- **Jenis:** Proses (logika).
- **Tujuan singkat:** Memvalidasi & menyimpan perubahan username/email (tb_user) dan nama/foto toko (tb_toko), termasuk upload/hapus foto toko.
- **Komponen yang di-include:** koneksi.php, guardpenjual.php. (tidak ada navbar)
- **Diakses / dipanggil dari:** form POST tab edit di `profil.php`.
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** tidak ada form (file proses).
- **Redirect (header Location):**
  - `profil.php` jika bukan POST.
  - `profil.php` (flash sukses) bila berhasil.
  - `profil.php?tab=edit` (flash gagal) bila ada error validasi.
- **Tabel database & operasinya:**
  - `tb_user` — SELECT cek duplikat (`WHERE (username=? OR email=?) AND id_user!=? AND deleted=0`); **UPDATE** `username, email WHERE id_user=?`.
  - `tb_toko`:
    - SELECT `foto_toko WHERE id_toko=?` (saat hapus foto).
    - **UPDATE** `foto_toko=NULL WHERE id_toko=?` (jika `hapus_foto=1`).
    - **UPDATE** `nama_toko, foto_toko WHERE id_toko=?` (jika ada foto baru) ATAU `nama_toko WHERE id_toko=?` (tanpa foto baru).
- **Catatan teknik penting:** semua prepared statement. **Validasi upload**: ekstensi (`jpg/jpeg/png/webp`) + ukuran maks 2MB; nama file unik `toko_{idtoko}_{time}.ext`, `move_uploaded_file` ke `2. aset/profil/` (buat folder bila belum ada). Hapus foto lama via `unlink`. Cek duplikat username/email lintas akun. Session (`username/email/nama_toko`) diperbarui setelah sukses. PRG dengan flash.

---

### penjual\profil\prosesgantipassword.php
- **Jenis:** Proses (logika).
- **Tujuan singkat:** Memverifikasi password lama, memvalidasi password baru, lalu menyimpan hash bcrypt baru.
- **Komponen yang di-include:** koneksi.php, guardpenjual.php. (tidak ada navbar)
- **Diakses / dipanggil dari:** form POST tab password di `profil.php`.
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** tidak ada form (file proses).
- **Redirect (header Location):**
  - `profil.php` jika bukan POST.
  - `profil.php` (flash sukses) bila password berhasil diubah.
  - `profil.php?tab=password` (flash gagal) bila validasi gagal / password lama salah.
- **Tabel database & operasinya:**
  - `tb_user`:
    - SELECT `password WHERE id_user=? AND deleted=0` (ambil hash lama).
    - **UPDATE** `password=? WHERE id_user=?` (simpan hash baru).
- **Catatan teknik penting:** prepared statement. Verifikasi password lama dengan `password_verify` (bcrypt); password baru di-`password_hash(..., PASSWORD_BCRYPT)`. Validasi: semua kolom wajib, panjang 8–100, konfirmasi harus cocok. PRG dengan flash.

---

## Ringkasan tabel yang disentuh folder penjual

| Tabel | Dibaca (SELECT) | Ditulis (INSERT/UPDATE) |
|---|---|---|
| `tb_order` | index, manajemenpesanan, struk, chat, laporan, profil | prosesmanajemenpesanan (UPDATE status) |
| `tb_detail_order` | index, manajemenpesanan, struk, chat, laporan | — (hanya dibaca; stok dikembalikan ke tb_menu) |
| `tb_menu` | index, manajemenmenu, struk, chat, laporan, profil | prosesmanajemenmenu (INSERT/UPDATE/soft delete), prosesmanajemenpesanan (UPDATE stok) |
| `tb_rating` | index, laporan, ulasan, profil | — |
| `tb_user` | index, manajemenpesanan, struk, chat, laporan, profil, proseseditprofil, prosesgantipassword | proseseditprofil (UPDATE), prosesgantipassword (UPDATE) |
| `tb_toko` | profil | prosesindex (UPDATE status), proseseditprofil (UPDATE) |
| `tb_chat` | chat | chat (INSERT pesan, UPDATE dibaca) |
