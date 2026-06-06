# PETA 04 — ADMIN (Peta Koneksi & Database per File)

Dokumen ini memetakan koneksi antar-file dan operasi database untuk semua halaman di folder `admin\`.
Catatan umum:
- Semua file meng-`include` `1. koneksi/koneksi.php` (koneksi mysqli `$conn`) dan `3. komponen/guardadmin.php` (penjaga: hanya role admin yang boleh masuk; guard ini juga yang menjalankan `session_start()`).
- Tema visual & CSS: `3. komponen/admin.css`. Navbar: `3. komponen/navbaradmin.php`. Variabel `$halamansaatini` menandai menu aktif di navbar.
- Sistem 10 kantin: `tb_toko` punya 10 slot tetap (id_toko paten). Slot dikenali lewat kolom `nomor_kantin` (butuh `migrasi_kantin.sql`). Slot kosong = `id_user IS NULL`.
- Banyak query memakai prepared statement (cegah SQL injection) dan pola PRG (Post-Redirect-Get) untuk proses.

---

### admin\index\index.php
- **Jenis:** Tampilan (UI) — dashboard admin.
- **Tujuan singkat:** Menampilkan statistik platform, chart omset 7 hari, produk terlaris, performa toko, pengguna terbaru, dan dua form pengumuman.
- **Komponen yang di-include:** koneksi.php, guardadmin.php, navbaradmin.
- **Diakses / dipanggil dari:** menu navbar admin; redirect tujuan dari `proses_pengumuman.php` & `proses_pengumuman_penjual.php`.
- **Link keluar (href ke halaman lain):** `../laporan/laporan.php` (beberapa kartu & link), `../manajemenpengguna/user.php`, `../manajemenpengguna/user.php?role=penjual`, `../manajementoko/kantin.php`, `../manajemenpengguna/viewuser.php?id=<id_user>` (kolom Aksi performa toko).
- **Form action (tujuan submit) & method:** form pengumuman pembeli → `proses_pengumuman.php` (POST); form pengumuman penjual → `proses_pengumuman_penjual.php` (POST).
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:**
  - `tb_toko`: `SHOW COLUMNS ... LIKE 'nomor_kantin'` (deteksi migrasi); `SELECT COUNT(*) WHERE deleted=0 AND id_user IS NOT NULL` (total toko terisi); performa per toko `SELECT t.id_toko,t.id_user,t.nomor_kantin,t.nama_toko,t.status_toko` + subquery agregasi, `ORDER BY t.nomor_kantin ASC`.
  - `tb_user`: `SELECT role, COUNT(*) GROUP BY role WHERE deleted=0` (jumlah per role); `COUNT(*) WHERE DATE(created)=CURDATE()` (user baru hari ini); `SELECT id_user,username,email,role,created ... ORDER BY created DESC LIMIT 5` (pengguna terbaru).
  - `tb_menu` JOIN `tb_toko`: `COUNT(*) WHERE m.status='aktif' AND m.deleted=0 AND t.id_user IS NOT NULL` (menu aktif).
  - `tb_order`: `COUNT(*), COALESCE(SUM(total_harga),0) WHERE deleted=0` (total order & nilai); `SUM(total_harga) WHERE status_order='Selesai'` (revenue); `COUNT(*) WHERE DATE(tanggal_order)=CURDATE()` (order hari ini); chart prepared `SUM(total_harga) ... BETWEEN ? AND ? AND status_order='Selesai' GROUP BY DATE(tanggal_order)`.
  - `tb_detail_order` JOIN `tb_order` LEFT JOIN `tb_menu`: produk terlaris `SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omset ... WHERE o.status_order='Selesai' GROUP BY d.id_menu ... ORDER BY terjual DESC LIMIT 5`; pakai snapshot `nama_menu_snapshot`/`nama_toko_snapshot`.
  - `tb_rating`: subquery `AVG(rating_toko) WHERE id_penjual=t.id_user` di performa toko.
  - Performa toko per-penjual difilter `o.id_penjual=t.id_user` agar data penjual lama di slot yang sama tidak tercampur.
- **Catatan teknik penting:** prepared statement pada chart; sistem nomor_kantin (banner peringatan kalau `migrasi_kantin.sql` belum dijalankan); pengumuman disimpan ke file teks (`tekspengumuman.txt`, `tekspengumumanpenjual.txt`), bukan DB; ekspor XLS (HTML-in-Excel) via JS `eksporXlsSeksi`.

---

### admin\index\proses_pengumuman.php
- **Jenis:** Proses (logika).
- **Tujuan singkat:** Menyimpan teks pengumuman untuk pembeli ke file teks.
- **Komponen yang di-include:** koneksi.php, guardadmin.php.
- **Diakses / dipanggil dari:** form "Pengumuman ke Pembeli" di `index.php`.
- **Link keluar (href ke halaman lain):** tidak ada.
- **Form action (tujuan submit) & method:** menerima POST dari `index.php`; tidak menyajikan form.
- **Redirect (header Location):** `index.php` selalu (baik jika bukan POST maupun setelah simpan, pola PRG).
- **Tabel database & operasinya:** Tidak mengakses database. Menulis ke file `3. komponen/tekspengumuman.txt` via `file_put_contents`.
- **Catatan teknik penting:** batasi 500 karakter; teks kosong = pengumuman dinonaktifkan; flash message via `$_SESSION['flash_admin']`; PRG.

---

### admin\index\proses_pengumuman_penjual.php
- **Jenis:** Proses (logika).
- **Tujuan singkat:** Menyimpan teks pengumuman khusus penjual ke file teks.
- **Komponen yang di-include:** koneksi.php, guardadmin.php.
- **Diakses / dipanggil dari:** form "Pengumuman ke Penjual" di `index.php`.
- **Link keluar:** tidak ada.
- **Form action (tujuan submit) & method:** menerima POST (field `teks_pengumuman_penjual`) dari `index.php`.
- **Redirect (header Location):** `index.php` selalu (PRG / bukan-POST).
- **Tabel database & operasinya:** Tidak mengakses database. Menulis ke file `3. komponen/tekspengumumanpenjual.txt`.
- **Catatan teknik penting:** batasi 500 karakter; flash `$_SESSION['flash_admin']`; PRG.

---

### admin\manajemenpengguna\user.php
- **Jenis:** Tampilan (UI) — daftar pengguna.
- **Tujuan singkat:** Menampilkan daftar pengguna dengan filter tab (semua/penjual/pembeli/admin/terhapus) + pencarian.
- **Komponen yang di-include:** koneksi.php, guardadmin.php, navbaradmin.
- **Diakses / dipanggil dari:** navbar admin; kartu/link dari `index.php`; redirect dari proses tambah/hapus/edit user & `prosestoggletoko.php`.
- **Link keluar (href):** `tambahuser.php` / `tambahuser.php?role=<role>`, `user.php` & `user.php?role=...` (tab filter), `viewuser.php?id=<id>`, `edituser.php?id=<id>`, `hapususer.php?id=<id>`.
- **Form action (tujuan submit) & method:** form pencarian → `user.php` (GET, field `cari` + hidden `role`); form toggle status toko per baris penjual → `../manajementoko/prosestoggletoko.php` (POST, field `id_toko`).
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:**
  - `tb_toko`/`tb_user`: beberapa `SHOW COLUMNS LIKE` untuk deteksi migrasi (`nomor_kantin`, `deleted_at`, `status_verifikasi`) dan `SHOW TABLES LIKE 'tb_riwayat_toko'`.
  - `tb_user`: `SELECT role, COUNT(*) GROUP BY role WHERE deleted=0` (badge per role, plus filter verified untuk pembeli); `COUNT(*) WHERE deleted=1` (badge Terhapus, kecuali pembeli `ditolak`).
  - Tab aktif: `SELECT u.* , t.id_toko, nomor_kantin, t.nama_toko, t.status_toko, t.foto_toko, CASE role ... AS urut_role` + subquery `pesanan_toko` (`COUNT tb_order WHERE id_penjual=u.id_user`) & `pesanan_user` (`COUNT WHERE id_user=u.id_user`); `tb_user u LEFT JOIN tb_toko t ON u.id_user=t.id_user AND t.deleted=0 WHERE u.deleted=0`; filter role/cari via prepared (`LIKE` username/email/nama_toko); `ORDER BY urut_role, COALESCE(nomor_kantin,999), username`.
  - Tab terhapus: `SELECT ... WHERE u.deleted=1` + subquery pesanan; LEFT JOIN ke subquery `tb_riwayat_toko` (ambil baris `MAX(id_riwayat)` per user) untuk menampilkan toko terakhir; pembeli `ditolak` dikecualikan.
- **Catatan teknik penting:** prepared statement dengan `$types`/`$params` dinamis + operator spread; sistem nomor_kantin (sort `COALESCE(nomor_kantin,999)`); soft-delete (`deleted=0/1`); ekspor XLS JS; kompatibel pra-migrasi (fragmen SQL kondisional).

---

### admin\manajemenpengguna\tambahuser.php
- **Jenis:** Tampilan (UI) — form tambah pengguna (2 langkah).
- **Tujuan singkat:** Langkah 1 pemilih peran; langkah 2 form sesuai peran (penjual/pembeli/admin).
- **Komponen yang di-include:** koneksi.php, guardadmin.php, `3. komponen/kelas_jurusan.php` (helper dropdown kelas), navbaradmin.
- **Diakses / dipanggil dari:** `user.php` (tombol Tambah), `kantin.php` (Tambah Penjual / Isi Kantin); redirect balik dari `prosestambahuser.php` saat validasi gagal.
- **Link keluar (href):** `tambahuser.php?role=penjual|pembeli|admin` (kartu pemilih), `user.php`, `tambahuser.php` (ganti peran / batal), `../manajementoko/kantin.php` (jika semua kantin penuh).
- **Form action (tujuan submit) & method:** ketiga form → `prosestambahuser.php` (POST); form penjual pakai `enctype=multipart/form-data` (upload foto toko).
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:**
  - `tb_toko`: `SHOW COLUMNS LIKE 'nomor_kantin'`; jika role=penjual, `SELECT id_toko, nomor_kantin WHERE id_user IS NULL AND deleted=0 ORDER BY nomor_kantin ASC` (daftar slot kantin kosong untuk dropdown).
- **Catatan teknik penting:** sistem 10 kantin (dropdown slot kosong, label "Kantin ke-N"); oldinput dari session untuk repopulate; pembeli dibuat admin otomatis verified; JS hanya untuk show/hide password.

---

### admin\manajemenpengguna\prosestambahuser.php
- **Jenis:** Proses (logika).
- **Tujuan singkat:** Validasi & menyimpan pengguna baru; untuk penjual mengisi slot kantin + upload foto toko.
- **Komponen yang di-include:** koneksi.php, guardadmin.php, kelas_jurusan.php.
- **Diakses / dipanggil dari:** form di `tambahuser.php` (POST).
- **Link keluar:** tidak ada.
- **Form action & method:** menerima POST (`role`, `username`, `email`, `password`, `id_kantin`, `nama_toko`, `nama_lengkap`, `kelas`, file `foto_toko`).
- **Redirect (header Location):** bukan POST → `tambahuser.php`; validasi gagal → `tambahuser.php?role=<role>` (oldinput di session); sukses penjual → `user.php?role=penjual`; sukses pembeli/admin → `user.php?role=<role>`.
- **Tabel database & operasinya:**
  - `tb_user`: `UPDATE ... SET deleted=1, deleted_at=NOW() WHERE (username=? OR email=?) AND status_verifikasi='ditolak' AND deleted=0` (bebaskan slot username/email dari akun ditolak); `SELECT id_user WHERE username=?`/`email=?` (cek duplikat); **INSERT** `(username, nama_lengkap, kelas, email, password, role, status_verifikasi, deleted)` — `status_verifikasi='verified'` selalu (dibuat admin), `nama_lengkap`/`kelas` hanya untuk pembeli.
  - `tb_toko`: `SHOW COLUMNS LIKE 'nomor_kantin'` & `'tanggal_mulai'`; `SELECT id_toko, nomor_kantin WHERE id_toko=? AND id_user IS NULL AND deleted=0` (anti race-condition slot); **UPDATE** isi slot `SET id_user=?, nama_toko=?, [foto_toko=?,] status_toko='tutup'[, tanggal_mulai=NOW()] WHERE id_toko=? AND id_user IS NULL AND deleted=0`.
- **Catatan teknik penting:** prepared statement; `password_hash`; sistem nomor_kantin (slot diisi via id_toko, label "Kantin ke-N"); upload foto ke `2. aset/profil/` (validasi ext jpg/jpeg/png/webp + maks 2MB, nama `toko_<idkantin>_<time>.<ext>`); PRG; fungsi helper `flash()`/`redirect()`.

---

### admin\manajemenpengguna\edituser.php
- **Jenis:** Tampilan (UI) — form edit pengguna.
- **Tujuan singkat:** Menampilkan form edit dengan field menyesuaikan peran (penjual: nama+foto toko; pembeli: nama lengkap+kelas; admin: dasar saja).
- **Komponen yang di-include:** koneksi.php, guardadmin.php, kelas_jurusan.php, navbaradmin.
- **Diakses / dipanggil dari:** `user.php` (tombol Edit), `viewuser.php` (tombol Edit); redirect balik dari `prosesedituser.php` saat gagal.
- **Link keluar (href):** `viewuser.php?id=<id>` (kembali/batal).
- **Form action & method:** → `prosesedituser.php` (POST); `enctype=multipart/form-data` hanya jika penjual.
- **Redirect (header Location):** id kosong / user tidak ditemukan → `user.php`.
- **Tabel database & operasinya:**
  - `tb_user`: `SELECT * WHERE id_user=? AND deleted=0` (data user).
  - `tb_toko`: `SELECT * WHERE id_user=? AND deleted=0` (hanya jika penjual, untuk nama & foto toko).
- **Catatan teknik penting:** prepared statement; peran tidak bisa diubah (read-only); oldinput dari session; foto profil `tb_user.foto` tidak dipakai untuk pembeli/admin; JS hanya show/hide password.

---

### admin\manajemenpengguna\prosesedituser.php
- **Jenis:** Proses (logika).
- **Tujuan singkat:** Validasi & menyimpan perubahan akun; untuk penjual menangani nama/foto toko.
- **Komponen yang di-include:** koneksi.php, guardadmin.php, kelas_jurusan.php.
- **Diakses / dipanggil dari:** form `edituser.php` (POST).
- **Link keluar:** tidak ada.
- **Form action & method:** menerima POST (`id_user`, `username`, `email`, `password` opsional, `nama_toko`, `nama_lengkap`, `kelas`, file `foto_toko`, checkbox `hapus_foto_toko`).
- **Redirect (header Location):** bukan POST / id kosong → `user.php`; gagal validasi → `edituser.php?id=<id>`; sukses → `viewuser.php?id=<id>`.
- **Tabel database & operasinya:**
  - `tb_user`: `SELECT role WHERE id_user=? AND deleted=0`; cek duplikat `username`/`email` (`WHERE ...=? AND id_user!=?`); **UPDATE** dinamis `SET username=?, email=? [, nama_lengkap=?, kelas=?] [, password=?] WHERE id_user=? AND deleted=0` (nama_lengkap/kelas hanya pembeli; password hanya jika diisi).
  - `tb_toko` (hanya penjual): **UPDATE** `SET nama_toko=? WHERE id_user=? AND deleted=0`; `SELECT id_toko, foto_toko WHERE id_user=?`; **UPDATE** `SET foto_toko=? WHERE id_toko=?` (upload baru) atau `SET foto_toko=NULL` (hapus foto).
- **Catatan teknik penting:** prepared statement; SET-clause dibangun dinamis sesuai peran; `password_hash` hanya jika password baru diisi; upload foto ke `2. aset/profil/` (jpg/jpeg/png/webp, maks 2MB, hapus file lama via `unlink`); PRG; helper `flash()`/`redirect()`.

---

### admin\manajemenpengguna\viewuser.php
- **Jenis:** Tampilan (UI) — detail pengguna.
- **Tujuan singkat:** Menampilkan detail lengkap pengguna: penjual (statistik toko, chart omset, terlaris, rating/ulasan, menu, top pelanggan, detail pesanan), pembeli (statistik + toko favorit), admin (identitas).
- **Komponen yang di-include:** koneksi.php, guardadmin.php, navbaradmin.
- **Diakses / dipanggil dari:** `user.php`, `index.php` (performa toko), `kantin.php`, `laporan.php` (Detail Penjual); redirect dari `prosestoggletoko.php` (via `id_user_ref`).
- **Link keluar (href):** `user.php` (kembali), `edituser.php?id=<id>`, `hapususer.php?id=<id>`, filter periode chart `?id=<id>&hari=7|14|30|custom`.
- **Form action & method:** form toggle status toko (di hero penjual) → `../manajementoko/prosestoggletoko.php` (POST, `id_toko` + `id_user_ref`); form custom periode chart → `viewuser.php` (GET, `id`,`hari=custom`,`dari`,`sampai`).
- **Redirect (header Location):** id kosong / user tidak ditemukan → `user.php`.
- **Tabel database & operasinya:**
  - `tb_user`: `SELECT * WHERE id_user=?` (tanpa filter deleted — tetap bisa lihat akun terhapus read-only).
  - `tb_toko`: `SELECT * WHERE id_user=? AND deleted=0` (toko aktif penjual).
  - `tb_riwayat_toko`: `SHOW TABLES LIKE`; fallback `SELECT * WHERE id_user=? ORDER BY id_riwayat DESC LIMIT 1` (jika slot sudah dikosongkan).
  - `tb_order` (penjual, filter `id_penjual=?`): `COUNT(*)` total; `COUNT/SUM WHERE status_order='Selesai'` (omset); `COUNT/SUM WHERE status_order='Dibatalkan'`; `COUNT(DISTINCT id_user)` (pelanggan unik); chart `SUM(total_harga) ... BETWEEN ? AND ? AND status_order='Selesai' GROUP BY DATE`; detail pesanan `SELECT ... JOIN tb_user, LEFT JOIN tb_detail_order/tb_menu WHERE status_order IN ('Selesai','Dibatalkan') LIMIT 500`.
  - `tb_rating` (filter `id_penjual=?`): `AVG(rating_toko), COUNT`; distribusi `GROUP BY rating_toko`; ulasan terbaru `JOIN tb_user` + subquery `GROUP_CONCAT` menu dipesan, `ORDER BY created DESC LIMIT 10`.
  - `tb_menu`: `COUNT(*) WHERE id_toko=? AND status='aktif'` (menu aktif); produk terlaris `SUM(d.jumlah)/SUM(d.subtotal) JOIN tb_detail_order/tb_order WHERE status_order='Selesai' GROUP BY id_menu ... LIMIT 5`; daftar menu `WHERE m.id_penjual=?` + subquery terjual.
  - Top pelanggan: `tb_order JOIN tb_user GROUP BY id_user ORDER BY jml_order DESC LIMIT 10`.
  - Pembeli (filter `id_user=?`): `COUNT(*)` total pesanan; `SUM WHERE status_order='Selesai'` (total belanja); toko favorit `COALESCE(nama_toko_snapshot,...) GROUP BY id_penjual ORDER BY jml_order DESC LIMIT 5`.
- **Catatan teknik penting:** prepared statement di semua query; isolasi data penjual via `id_penjual` (bukan id_toko); snapshot (`nama_menu_snapshot`/`nama_toko_snapshot`/`nomor_kantin_snapshot`) agar data historis akurat; fallback ke `tb_riwayat_toko`; chart SVG murni PHP; ekspor XLS per-section & semua (JS).

---

### admin\manajemenpengguna\hapususer.php
- **Jenis:** Tampilan (UI) — konfirmasi hapus.
- **Tujuan singkat:** Halaman konfirmasi hapus pengguna / kosongkan kantin sebelum eksekusi.
- **Komponen yang di-include:** koneksi.php, guardadmin.php, navbaradmin.
- **Diakses / dipanggil dari:** `user.php` (tombol Hapus), `viewuser.php` (tombol Hapus), `kantin.php` (tombol Kosongkan).
- **Link keluar (href):** `viewuser.php?id=<id>` (batal).
- **Form action & method:** form konfirmasi → `proseshapususer.php` (POST, `id_user`).
- **Redirect (header Location):** id kosong / user tidak ditemukan → `user.php`.
- **Tabel database & operasinya:**
  - `tb_user`: `SELECT id_user, username, email, role WHERE id_user=? AND deleted=0`.
  - `tb_toko`: `SHOW COLUMNS LIKE 'nomor_kantin'`; jika penjual `SELECT nomor_kantin, nama_toko WHERE id_user=? AND deleted=0` (untuk pesan konfirmasi kantin).
- **Catatan teknik penting:** prepared statement; menjelaskan bahwa hapus penjual = kosongkan slot kantin (sistem 10 slot tetap); konfirmasi sebelum POST.

---

### admin\manajemenpengguna\proseshapususer.php
- **Jenis:** Proses (logika).
- **Tujuan singkat:** Soft-delete pengguna; untuk penjual: snapshot toko ke riwayat, kosongkan slot kantin, soft-delete menu.
- **Komponen yang di-include:** koneksi.php, guardadmin.php.
- **Diakses / dipanggil dari:** form `hapususer.php` (POST).
- **Link keluar:** tidak ada.
- **Form action & method:** menerima POST (`id_user`).
- **Redirect (header Location):** bukan POST / id kosong → `user.php`; hapus diri sendiri ditolak → `user.php`; sukses → `user.php`.
- **Tabel database & operasinya:**
  - `tb_user`: `SHOW COLUMNS LIKE 'deleted_at'`; **soft delete** `UPDATE SET deleted=1[, deleted_at=NOW()] WHERE id_user=? AND deleted=0`.
  - `tb_toko`: `SELECT id_toko, nomor_kantin, nama_toko, foto_toko WHERE id_user=? AND deleted=0`; **kosongkan kantin** `UPDATE SET id_user=NULL, nama_toko=NULL, foto_toko=NULL, status_toko='tutup' WHERE id_toko=?` (id_user=NULL); `SELECT tanggal_mulai WHERE id_toko=?` (untuk tgl_masuk riwayat).
  - `tb_riwayat_toko`: `SHOW TABLES LIKE` & `SHOW COLUMNS LIKE 'tgl_masuk'`; **INSERT** snapshot `(id_user, id_toko, nomor_kantin, nama_toko, foto_toko[, tgl_masuk], tgl_keluar)` `VALUES (...,NOW())`.
  - `tb_menu`: **soft delete** `UPDATE SET deleted=1 WHERE id_toko=?` (agar penjual baru tidak mewarisi menu lama).
- **Catatan teknik penting:** prepared statement; soft delete (data historis tetap utuh); proteksi admin tidak bisa hapus akun sendiri (cek `$_SESSION['id_user']`); sistem 10 slot kantin (slot dikosongkan id_user=NULL, tidak dihapus); fragmen SQL `{$delatkol}` aman (dikontrol kode); isolasi data via `id_penjual` di tb_order/tb_rating; PRG.

---

### admin\manajemenpengguna\verifikasi.php
- **Jenis:** Tampilan (UI) — verifikasi pembeli.
- **Tujuan singkat:** Menampilkan daftar pembeli per status verifikasi (pending/verified/ditolak) untuk ditinjau & disetujui/ditolak.
- **Komponen yang di-include:** koneksi.php, guardadmin.php, navbaradmin.
- **Diakses / dipanggil dari:** navbar admin (`$halamansaatini='verifikasi'`); redirect dari `prosesverifikasi.php`.
- **Link keluar (href):** `verifikasi.php?filter=pending|verified|ditolak` (tab).
- **Form action & method:** tiga form per baris → `prosesverifikasi.php` (POST, `id_user` + `aksi`=terima/tolak/reset), dengan `confirm()`.
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:**
  - `tb_user`: hitung badge `SELECT status_verifikasi, COUNT(*) GROUP BY status_verifikasi WHERE role='pembeli' AND ((deleted=0 AND status_verifikasi IN ('pending','verified')) OR (deleted=1 AND status_verifikasi='ditolak'))`; daftar `SELECT id_user, username, nama_lengkap, kelas, email, created, status_verifikasi WHERE role='pembeli' AND <deleted=0|1> AND status_verifikasi=? ORDER BY created DESC`.
- **Catatan teknik penting:** prepared statement; akun ditolak = soft-deleted (`deleted=1`) tapi tampil di tab Ditolak (read-only) bukan di tab Terhapus; aksi via POST (anti-GET crawler); JS hanya `confirm()`.

---

### admin\manajemenpengguna\prosesverifikasi.php
- **Jenis:** Proses (logika).
- **Tujuan singkat:** Mengubah `status_verifikasi` pembeli (terima/tolak/reset).
- **Komponen yang di-include:** koneksi.php, guardadmin.php.
- **Diakses / dipanggil dari:** form `verifikasi.php` (POST).
- **Link keluar:** tidak ada.
- **Form action & method:** menerima POST (`id_user`, `aksi`).
- **Redirect (header Location):** bukan POST / aksi tak dikenal / user tidak ditemukan / status sudah sama → `verifikasi.php`; sukses → `verifikasi.php?filter=<tabBalik>`.
- **Tabel database & operasinya:**
  - `tb_user`: `SELECT username, status_verifikasi WHERE id_user=? AND role='pembeli' AND deleted=0` (validasi target); **UPDATE** — tolak: `SET status_verifikasi='ditolak', deleted=1, deleted_at=NOW()` (soft-delete); terima/reset: `SET status_verifikasi=?` (verified/pending).
- **Catatan teknik penting:** prepared statement; tolak = langsung soft-delete (username/email bebas dipakai ulang); `match()` untuk pesan sukses; PRG.

---

### admin\manajemenpengguna\eksporuser.php
- **Jenis:** Proses (logika) — output CSV.
- **Tujuan singkat:** Mengekspor daftar pengguna ke file CSV sesuai filter role/pencarian.
- **Komponen yang di-include:** koneksi.php, guardadmin.php (TANPA navbar — output langsung file).
- **Diakses / dipanggil dari:** (endpoint ekspor; dipanggil via link/tombol dengan param `?role=`&`?cari=`).
- **Link keluar:** tidak ada.
- **Form action & method:** tidak ada form (baca `$_GET`).
- **Redirect (header Location):** tidak ada (kirim header CSV + `exit`).
- **Tabel database & operasinya:**
  - `tb_user` LEFT JOIN `tb_toko` (`ON u.id_user=t.id_user AND t.deleted=0`): `SELECT u.id_user, u.username, u.email, u.role, u.created, t.nama_toko, t.status_toko WHERE u.deleted=0` + filter role / `LIKE` username/email, `ORDER BY u.created DESC`.
- **Catatan teknik penting:** prepared statement; output CSV via `php://output`, header `Content-Type: text/csv` + `Content-Disposition: attachment`; BOM UTF-8; pemisah `;` (Excel Indonesia); nama file ber-timestamp.

---

### admin\manajementoko\kantin.php
- **Jenis:** Tampilan (UI) — status kantin.
- **Tujuan singkat:** Menampilkan 10 slot kantin tetap (terisi/kosong, pemilik, nama toko, status buka/tutup) dalam grid kartu.
- **Komponen yang di-include:** koneksi.php, guardadmin.php, navbaradmin (navbar disembunyikan saat mode `?cetak`).
- **Diakses / dipanggil dari:** navbar admin (`$halamansaatini='kantin'`); kartu Total Toko di `index.php`; redirect dari `prosestoggletoko.php` (default).
- **Link keluar (href):** `../manajemenpengguna/tambahuser.php?role=penjual` (Tambah Penjual / Isi Kantin), `../manajemenpengguna/viewuser.php?id=<id_user>` (Detail), `../manajemenpengguna/hapususer.php?id=<id_user>` (Kosongkan).
- **Form action & method:** form toggle status per kartu terisi → `prosestoggletoko.php` (POST, `id_toko`).
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:**
  - `tb_toko` LEFT JOIN `tb_user` (`ON t.id_user=u.id_user AND u.deleted=0`): `SHOW COLUMNS LIKE 'nomor_kantin'`; `SELECT t.id_toko, t.nomor_kantin, t.id_user, t.nama_toko, t.status_toko, t.deleted, u.username, u.email WHERE t.deleted=0 ORDER BY t.nomor_kantin ASC`.
  - Agregasi di PHP (bukan SQL): hitung terisi/kosong/buka dari hasil.
- **Catatan teknik penting:** sistem 10 slot tetap (butuh `migrasi_kantin.sql`; jika belum, tampil pesan kosong); kantin kosong = `username` NULL; ekspor XLS via JS dari tabel tersembunyi `#seksi-kantin`; mode cetak `?cetak`.

---

### admin\manajementoko\prosestoggletoko.php
- **Jenis:** Proses (logika).
- **Tujuan singkat:** Membalik status toko (buka↔tutup) tanpa halaman konfirmasi.
- **Komponen yang di-include:** koneksi.php, guardadmin.php.
- **Diakses / dipanggil dari:** form toggle di `user.php`, `viewuser.php`, `kantin.php` (POST).
- **Link keluar:** tidak ada.
- **Form action & method:** menerima POST (`id_toko`, opsional `id_user_ref`).
- **Redirect (header Location):** bukan POST / id tidak valid / toko tidak ditemukan → `../../admin/manajemenpengguna/user.php?role=penjual`; sukses + ada `id_user_ref` → `../../admin/manajemenpengguna/viewuser.php?id=<id_user_ref>`; selain itu → `user.php?role=penjual`.
- **Tabel database & operasinya:**
  - `tb_toko`: `SELECT status_toko WHERE id_toko=? AND deleted=0`; **UPDATE** `SET status_toko=? WHERE id_toko=?` (nilai kebalikan dari status saat ini).
- **Catatan teknik penting:** prepared statement; flash via session; redirect kontekstual (kembali ke viewuser jika dipanggil dari sana); PRG.

---

### admin\laporan\laporan.php
- **Jenis:** Tampilan (UI) — laporan platform.
- **Tujuan singkat:** Laporan platform dengan filter periode (7/14/30/custom) + kantin (semua atau satu): statistik, chart omset, top pelanggan, rating/ulasan, terlaris, menu, detail pesanan, dan performa per kantin (mode semua).
- **Komponen yang di-include:** koneksi.php, guardadmin.php, navbaradmin.
- **Diakses / dipanggil dari:** navbar admin (`$halamansaatini='laporan'`); tombol/kartu "Laporan Platform" di `index.php`.
- **Link keluar (href):** `laporan.php?periode=...&kantin=...` (filter periode), `../manajemenpengguna/viewuser.php?id=<id_user>` (Detail Penjual / aksi performa), `eksporlaporan.php?periode=...&kantin=...` (Ekspor CSV Detail).
- **Form action & method:** form pilih kantin → `laporan.php` (GET, `kantin` + hidden periode/dari/sampai); form custom periode → `laporan.php` (GET, `periode=custom`,`kantin`,`dari`,`sampai`).
- **Redirect (header Location):** tidak ada.
- **Tabel database & operasinya:**
  - `tb_toko`: `SHOW COLUMNS LIKE 'nomor_kantin'`; performa per toko prepared `SELECT t.id_toko, t.nomor_kantin, t.nama_toko, t.status_toko, t.id_user, u.username, COUNT(DISTINCT o.id_order), SUM(CASE WHEN status='Dibatalkan'...), SUM(CASE WHEN 'Selesai' THEN total_harga) AS pendapatan, SUM(CASE WHEN 'Dibatalkan'...) AS nilai_dibatalkan` + subquery rating; `LEFT JOIN tb_user`, `LEFT JOIN tb_order ON o.id_penjual=t.id_user AND DATE(tanggal_order) BETWEEN ? AND ?`; `GROUP BY ... ORDER BY nomor_kantin`.
  - `tb_order` (filter periode + opsional `o.id_penjual=<idpenjualterpilih>`): `COUNT(*)` total; `SUM WHERE status_order='Selesai'` (omset); `SUM WHERE 'Dibatalkan'`; `COUNT GROUP BY status_order`; chart `SUM ... status_order='Selesai' GROUP BY DATE`; `COUNT(DISTINCT id_user) WHERE 'Selesai'` (pelanggan unik per-kantin); detail pesanan `JOIN tb_user LEFT JOIN tb_detail_order/tb_menu WHERE status_order IN ('Selesai','Dibatalkan') LIMIT 500`.
  - `tb_user`: `COUNT(*) WHERE DATE(created) BETWEEN ? AND ? AND deleted=0` (user baru, mode semua).
  - `tb_menu`: `COUNT(*) WHERE id_penjual=? AND status='aktif'` (per-kantin); daftar menu per-kantin (`WHERE m.id_penjual=?` + LEFT JOIN order/detail untuk terjual periode); daftar menu platform (`WHERE m.deleted=0 AND status='aktif'` LEFT JOIN tb_toko + subquery terjual total, LIMIT 100).
  - `tb_rating`: per-kantin `AVG, COUNT WHERE id_penjual=?` + distribusi `GROUP BY rating_toko`; platform-wide `AVG, COUNT WHERE deleted=0` + distribusi; ulasan terbaru `JOIN tb_user [LEFT JOIN tb_order]` + subquery `GROUP_CONCAT` menu dipesan `ORDER BY created DESC LIMIT 10`.
  - `tb_detail_order` JOIN `tb_order` LEFT JOIN `tb_menu`: produk terlaris `SUM(d.jumlah)/SUM(d.subtotal) WHERE status_order='Selesai' GROUP BY ... ORDER BY terjual DESC LIMIT 10` (pakai snapshot).
- **Catatan teknik penting:** banyak prepared statement; agregasi `SUM`/`COUNT`/`AVG`/`GROUP BY`/`CASE WHEN`; mode semua vs per-kantin (filter `o.id_penjual`); isolasi data via id_penjual (bukan id_toko); snapshot kolom; omset = HANYA Selesai, nilai dibatalkan terpisah; chart SVG murni PHP; ekspor XLS per-section/semua + cetak per-kantin (JS); kompatibel pra-migrasi.

---

### admin\laporan\eksporlaporan.php
- **Jenis:** Proses (logika) — output CSV detail.
- **Tujuan singkat:** Mengekspor laporan detail (satu baris per item menu per pesanan) ke CSV sesuai periode & kantin.
- **Komponen yang di-include:** koneksi.php, guardadmin.php (TANPA navbar — output file).
- **Diakses / dipanggil dari:** link "Ekspor CSV Detail" di `laporan.php` (`?periode=...&dari=...&sampai=...&kantin=...`).
- **Link keluar:** tidak ada.
- **Form action & method:** tidak ada form (baca `$_GET`).
- **Redirect (header Location):** tidak ada (kirim header CSV + `exit`).
- **Tabel database & operasinya:**
  - `tb_toko`: `SHOW COLUMNS LIKE 'nomor_kantin'`; jika filter kantin `SELECT id_user WHERE nomor_kantin=? AND deleted=0 AND id_user IS NOT NULL LIMIT 1` (resolve id_penjual slot).
  - `tb_order o` JOIN `tb_user u` LEFT JOIN `tb_detail_order d` (`AND d.deleted=0`) LEFT JOIN `tb_menu m`: `SELECT o.id_order, tanggal, waktu, u.username, o.nama_toko_snapshot, COALESCE(nomor_kantin_snapshot, id_toko), status_order, COALESCE(nama_menu_snapshot,m.nama_menu,'—'), harga_satuan, jumlah, subtotal, total_harga, metode_pembayaran, catatan WHERE DATE(tanggal_order) BETWEEN ? AND ? AND status_order IN ('Selesai','Dibatalkan') AND deleted=0 [AND o.id_penjual=<id>] ORDER BY tanggal_order DESC, id_order, nama_menu`.
  - Ringkasan (PHP, per-pesanan): total/jumlah Selesai & Dibatalkan.
- **Catatan teknik penting:** prepared statement (`"ss"` tanggal); filter per-kantin via id_penjual; snapshot kolom (CSV historis akurat); output CSV via `php://output`, header `Content-Type: text/csv` + `attachment`; BOM UTF-8; pemisah `;`; baris keterangan + ringkasan di atas tabel; total pesanan hanya pada baris pertama tiap pesanan.
