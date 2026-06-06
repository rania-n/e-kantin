# PETA 02 — PEMBELI: Peta Koneksi & Database per File

Dokumen ini memetakan alur navigasi (link masuk/keluar, form, redirect) dan akses database untuk setiap file di folder `pembeli\`. Semua file me-include `guardpembeli.php` (cek login + peran pembeli, juga menjalankan `session_start()`) dan `koneksi.php` (menyediakan `$conn` = objek mysqli). Navbar (`navbarpembeli.php`) di-include di semua halaman tampilan dan dipakai untuk badge keranjang + pesan flash.

Catatan penting tentang scope: `navbarpembeli.php` menjalankan `foreach` keranjang yang MENIMPA variabel `$idtoko` dan `$itemtoko` di halaman yang meng-include-nya. Karena itu `checkout.php` dan `keranjang.php` menyimpan id toko ke variabel terpisah sebelum include navbar.

---

### pembeli\index\index.php
- **Jenis:** Tampilan (UI) — ada sedikit logika query di atas.
- **Tujuan singkat:** Beranda pembeli: daftar kantin buka, filter kategori, pencarian menu, produk terlaris, dan daftar semua menu aktif.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`, `navbarpembeli.php`.
- **Diakses / dipanggil dari:** Tombol "Lihat Menu"/"Pesan Sekarang"/"Kembali ke Beranda" dari hampir semua halaman pembeli (keranjang kosong, detail, struk, pesanan kosong); redirect dari `proseskeranjang.php` (kembali=index), `detail.php`, dan navbar.
- **Link keluar (href ke halaman lain):**
  - `index.php` (dengan query `?kategori=`, `?toko=`, kombinasi) — filter kantin & kategori (baris 155, 172, 249, 253, 259, 366).
  - `../pesanan/pesanan.php` — badge "X pesanan aktif" (baris 138).
  - `../pesanan/detail.php?id=<id_menu>` — klik gambar menu / produk terlaris (baris 273, 328).
- **Form action (tujuan submit) & method:**
  - Form pencarian: `action="index.php"` method **GET** (baris 217) — kirim `cari`, plus hidden `toko` & `kategori`.
  - Form tambah cepat ke keranjang (produk terlaris & kartu menu): `action="../keranjang/proseskeranjang.php"` method **POST** (baris 293, 346) — kirim `aksi=tambah`, `id_menu`, `qty=1`, `kembali=index`.
- **Redirect (header Location):** Tidak ada (selain yang dijalankan di dalam `guardpembeli.php`).
- **Tabel database & operasinya:**
  - `tb_toko`: `SHOW COLUMNS ... LIKE 'nomor_kantin'` (cek migrasi, baris 26). `SELECT id_toko, nama_toko, foto_toko, nomor_kantin WHERE deleted=0 AND status_toko='buka' AND id_user IS NOT NULL ORDER BY nomor_kantin` (daftar kantin buka, baris 33). Prepared `SELECT nama_toko, status_toko WHERE id_toko=? AND deleted=0` (cek toko dipilih sedang tutup, baris 41).
  - `tb_menu` JOIN `tb_toko` (LEFT JOIN): `SELECT m.*, t.nama_toko ... WHERE m.deleted=0 AND m.status='aktif' AND m.stok>0 AND t.status_toko='buka'` plus filter kategori/cari/toko dinamis (daftar menu, baris 61).
  - `tb_menu` JOIN `tb_toko`: `SELECT DISTINCT m.kategori ...` (chip kategori, baris 64).
  - `tb_order`: prepared `SELECT COUNT(*) WHERE id_user=? AND status_order IN ('Menunggu','Diproses','Siap Diambil') AND deleted=0` (jumlah pesanan aktif, baris 76).
  - Produk terlaris (baris 92): `SELECT ... SUM(d.jumlah) AS terjual FROM tb_detail_order d JOIN tb_menu m JOIN tb_toko t JOIN tb_order o WHERE ... AND o.status_order='Selesai' GROUP BY m.id_menu... ORDER BY terjual DESC LIMIT 5` — **JOIN 4 tabel** (tb_detail_order ↔ tb_menu ↔ tb_toko ↔ tb_order).
- **Catatan teknik penting:** `real_escape_string` untuk `$kategori`/`$cari` (query daftar menu memakai string `$where` dinamis, bukan prepared); `(int)` untuk `$idtoko`. Cek toko buka prepared. `htmlspecialchars` di setiap output teks. `number_format(...,0,',','.')` format Rupiah. Filter kategori & toko adalah dua dimensi independen yang bisa aktif bersamaan.

---

### pembeli\keranjang\keranjang.php
- **Jenis:** Tampilan (UI).
- **Tujuan singkat:** Menampilkan isi keranjang (dari session) dikelompokkan per toko, dengan kontrol qty/hapus dan tombol checkout per kantin.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`, `navbarpembeli.php`. (Database tidak diquery langsung di file ini — datanya dari session.)
- **Diakses / dipanggil dari:** Navbar (ikon keranjang); redirect dari `proseskeranjang.php` (default & aksi hapus/ubah qty); redirect dari `prosespesanan.php`/`checkout.php` saat gagal; tombol "Batal" di checkout & detail.
- **Link keluar (href ke halaman lain):**
  - `../index/index.php` — "Lihat Menu" saat keranjang kosong (baris 53).
  - `../pesanan/checkout.php?toko=<id_toko>` — tombol checkout per toko (baris 147).
- **Form action (tujuan submit) & method:** Semua ke `proseskeranjang.php` method **POST**:
  - Hapus item: `aksi=hapus`, `id_toko`, `id_menu` (baris 105).
  - Kurang qty: `aksi=kurang`, `id_toko`, `id_menu` (baris 120).
  - Tambah qty: `aksi=tambah_qty`, `id_toko`, `id_menu` (baris 129).
- **Redirect (header Location):** Tidak ada.
- **Tabel database & operasinya:** Tidak mengakses database (keranjang dibaca dari `$_SESSION['keranjang']`).
- **Catatan teknik penting:** Keranjang disimpan di session sebagai array bersarang `[id_toko][id_menu] => [...]`, dengan kunci khusus `_info` per toko (menyimpan `nama_toko` & `id_toko`). Subtotal per toko & total semua dihitung manual (`harga * qty`). `id_toko` checkout diambil dari `_info['id_toko']` (di-cast int, baris 146) — bukan dari kunci foreach `$idtoko` karena navbar sudah menimpa `$idtoko`.

---

### pembeli\keranjang\proseskeranjang.php
- **Jenis:** Proses (logika) — tanpa HTML.
- **Tujuan singkat:** Menangani aksi keranjang (tambah, hapus, tambah_qty, kurang) di session dan menyinkronkannya ke tabel `tb_keranjang`, lalu redirect ke halaman asal.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`. (Tidak ada navbar.)
- **Diakses / dipanggil dari:** Form POST dari `index.php` (tambah cepat), `detail.php` (tambah dengan qty), `keranjang.php` (hapus/kurang/tambah_qty).
- **Link keluar (href ke halaman lain):** Tidak ada (hanya redirect).
- **Form action (tujuan submit) & method:** Tidak ada form (file ini penerima POST).
- **Redirect (header Location):** Semua diakhiri `exit`.
  - Jika method bukan POST → `keranjang.php` (baris 14).
  - Fungsi `kembalikan($kembali)`: `index` → `../index/index.php`; `detail` → `$_SESSION['detail_url']` (fallback `../index/index.php`); default → `keranjang.php` (baris 68-76). Dipakai di akhir aksi `tambah`.
  - Aksi `hapus`, `kurang`, `tambah_qty`, dan `default` → selalu `keranjang.php` (baris 189, 196, 223, 231, 235).
- **Tabel database & operasinya:**
  - `tb_menu` JOIN `tb_toko` (LEFT JOIN, prepared): `SELECT m.*, t.nama_toko WHERE m.id_menu=? AND m.deleted=0 AND m.status='aktif'` (ambil data menu saat aksi tambah, baris 112).
  - `tb_toko` (prepared): `SELECT status_toko WHERE id_toko=? AND deleted=0` (cek toko buka sebelum tambah, baris 125).
  - `tb_menu` (prepared): `SELECT stok WHERE id_menu=? AND deleted=0` (cek stok saat tambah_qty, baris 214).
  - `tb_keranjang` (prepared): `DELETE WHERE id_user=? AND id_menu=?` lalu `INSERT (id_user, id_menu, jumlah) VALUES (?,?,?)` — pola delete+insert untuk simpan/update (fungsi `simpanItemKeranjangDB`, baris 85-91); `DELETE WHERE id_user=? AND id_menu=?` saat hapus/qty 0 (fungsi `hapusItemKeranjangDB`, baris 95-99).
- **Catatan teknik penting:** Semua query pakai prepared statement. `(int)` untuk semua id, `max(1,...)` untuk qty. Qty dibatasi tidak melebihi stok (saat tambah & tambah_qty). Pesan flash via `$_SESSION['flash']` (fungsi `setFlash`). Keranjang disinkronkan ke DB agar tidak hilang saat logout/login. `bersihkanTokoKosong` menghapus slot toko jika sudah tak ada item selain `_info`.

---

### pembeli\pesanan\checkout.php
- **Jenis:** Tampilan (UI) dengan validasi/redirect di awal.
- **Tujuan singkat:** Halaman konfirmasi pesanan satu toko: ringkasan item, catatan, metode bayar (Tunai), dan total sebelum memesan.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`, `navbarpembeli.php`. (Tidak query database — data dari session.)
- **Diakses / dipanggil dari:** Tombol "Checkout <nama toko>" di `keranjang.php` (`checkout.php?toko=X`); redirect kembali dari `prosespesanan.php` saat toko tutup/stok kurang/error transaksi.
- **Link keluar (href ke halaman lain):**
  - `../keranjang/keranjang.php` — tombol "Batal" (baris 169).
- **Form action (tujuan submit) & method:** `action="prosespesanan.php"` method **POST** (baris 102) — kirim hidden `id_toko`, `token_checkout` (md5), `catatan` (textarea), `metode=Tunai` (hidden).
- **Redirect (header Location):**
  - `../keranjang/keranjang.php` jika `$idtoko` tidak valid / toko tak ditemukan di keranjang (baris 46) atau daftar item kosong (baris 62).
- **Tabel database & operasinya:** Tidak mengakses database (item dibaca dari `$_SESSION['keranjang'][$idtoko]`).
- **Catatan teknik penting:** Header `Cache-Control: no-store` + `Pragma: no-cache` (baris 9-10) agar form tidak ter-cache. Ada dua cara cari toko: cocok kunci array langsung, lalu fallback scan + bandingkan `_info['id_toko']` (anti ketidakcocokan tipe string/int). `$idtokosudahpilih` disimpan sebelum include navbar (karena navbar menimpa `$idtoko`). `token_checkout = md5(id_toko . '_' . session_id())`. Subtotal & totalbayar dihitung manual (tidak ada biaya layanan). `htmlspecialchars` & `number_format` untuk tampilan.

---

### pembeli\pesanan\prosespesanan.php
- **Jenis:** Proses (logika) — tanpa HTML.
- **Tujuan singkat:** Membuat pesanan dari keranjang satu toko: validasi → cek toko buka → cek stok → simpan order dalam transaksi → redirect ke struk.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`. (Tidak ada navbar.)
- **Diakses / dipanggil dari:** Form POST dari `checkout.php`.
- **Link keluar (href ke halaman lain):** Tidak ada (hanya redirect).
- **Form action (tujuan submit) & method:** Tidak ada form (file penerima POST).
- **Redirect (header Location):** Semua diakhiri `exit`.
  - Bukan POST → `../keranjang/keranjang.php` (baris 14).
  - Keranjang/toko tidak valid → `../keranjang/keranjang.php` (baris 34); item kosong → idem (baris 52).
  - Toko tutup → `checkout.php?toko=$idtoko` (baris 73); stok tidak cukup → `checkout.php?toko=$idtoko` (baris 92).
  - **Sukses** → `struk.php?id_order=$idpesananbaru&baru=1` (baris 172) — pola PRG.
  - Error/exception → rollback lalu `checkout.php?toko=$idtoko` (baris 181).
- **Tabel database & operasinya (urutan):**
  1. `tb_toko` (prepared): `SELECT status_toko, nama_toko, id_user, nomor_kantin, foto_toko WHERE id_toko=? AND deleted=0` (cek buka + ambil data snapshot, baris 63).
  2. `tb_menu` (prepared, loop per item): `SELECT stok WHERE id_menu=? AND deleted=0 AND status='aktif'` (validasi stok terbaru, baris 81).
  3. **Mulai transaksi** (`begin_transaction`).
  4. `tb_order` (prepared INSERT, baris 114): kolom `id_user, id_toko, id_penjual, nama_toko_snapshot, nomor_kantin_snapshot, foto_toko_snapshot, total_harga, status_order='Menunggu', metode_pembayaran, catatan, tanggal_order=NOW()`. Ambil `$conn->insert_id` sebagai `id_order`.
  5. `tb_detail_order` (prepared INSERT, loop per item, baris 141): kolom `id_order, id_menu, nama_menu_snapshot, jumlah, harga_satuan, subtotal` — relasi 1 order ↔ N detail.
  6. `tb_menu` (prepared UPDATE, loop per item, baris 152): `SET stok=GREATEST(0,stok-?) WHERE id_menu=?` (kurangi stok, cegah negatif).
  7. `commit()`; hapus slot toko dari session.
  8. `tb_keranjang` (prepared DELETE, loop per item, baris 166): `DELETE WHERE id_user=? AND id_menu=?` (bersihkan keranjang DB).
- **Catatan teknik penting:** Transaksi ACID (all-or-nothing) dengan `begin_transaction`/`commit`/`rollback`. Snapshot toko (nama/nomor/foto) & menu (nama) dibekukan ke order agar histori tetap benar meski data berubah. `metode` dipaksa `'Tunai'` di server (tidak percaya input client). Validasi stok dilakukan sebelum transaksi agar efisien. Pola PRG setelah sukses. Flash via `$_SESSION['flash']`.

---

### pembeli\pesanan\pesanan.php
- **Jenis:** Tampilan (UI) dengan banyak fungsi bantu query.
- **Tujuan singkat:** Daftar pesanan pembeli dengan dua tab (Aktif & Riwayat), timeline status, dan ringkasan rating.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`, `navbarpembeli.php`.
- **Diakses / dipanggil dari:** Navbar (menu Pesanan); badge "pesanan aktif" di `index.php`; redirect dari `prosesrating.php` (ke `?tab=riwayat`); tombol "Batal"/kembali di `rating.php`, `chat.php`; halaman pesanan kosong link balik.
- **Link keluar (href ke halaman lain):**
  - `pesanan.php?tab=aktif` / `?tab=riwayat` / `?tab=riwayat&filter=...` — tab & filter (baris 145, 152, 160-167).
  - `../index/index.php` — "Pesan Sekarang" saat kosong (baris 182).
  - `chat.php?id_order=<id>#latest` — tombol Chat (tab aktif, baris 268).
  - `struk.php?id_order=<id>` — tombol Struk (status Selesai, baris 274).
  - `rating.php?id_order=<id>` — tombol Rating (riwayat, selesai, belum dirating, baris 280).
- **Form action (tujuan submit) & method:** Tidak ada form.
- **Redirect (header Location):** Tidak ada.
- **Tabel database & operasinya:**
  - `tb_order` (prepared): `SELECT COUNT(*) WHERE id_user=? AND deleted=0 AND status_order IN ('Menunggu','Diproses','Siap Diambil')` (badge tab aktif, baris 21).
  - `tb_order` (prepared): `SELECT o.*, o.nama_toko_snapshot AS nama_toko WHERE id_user=? AND deleted=0 AND status_order IN (...)` (tab aktif, baris 34) ATAU `... AND $kondisifilter ORDER BY tanggal_order DESC LIMIT 50` (tab riwayat dengan filter Selesai/Dibatalkan, baris 46).
  - `tb_detail_order` LEFT JOIN `tb_menu` (fungsi `ambilItemPesanan`, baris 64): `SELECT d.jumlah, COALESCE(d.nama_menu_snapshot, m.nama_menu) AS nama_menu WHERE d.id_order=? AND d.deleted=0`.
  - `tb_rating` (fungsi `ambilRating`, baris 78): `SELECT rating_toko, ulasan WHERE id_order=? AND id_user=? AND deleted=0`.
- **Catatan teknik penting:** Semua query prepared. Nama toko dari `nama_toko_snapshot` (bukan JOIN tb_toko) agar riwayat menampilkan nama saat order dibuat. `COALESCE` untuk fallback nama menu; LEFT JOIN agar item hilang-pun tetap tampil. Nomor pesanan `EK-` + `str_pad(id_order,6,'0')`. Fungsi `tahapTimeline` & `kelasStatusPesanan` (pakai `match()`) menentukan kelas CSS. `<meta http-equiv="refresh" content="30">` di tab aktif (auto-refresh status). `htmlspecialchars` di semua output.

---

### pembeli\pesanan\detail.php
- **Jenis:** Tampilan (UI).
- **Tujuan singkat:** Detail satu menu (gambar besar, harga, stok, deskripsi) dengan form pilih jumlah dan tambah ke keranjang.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`, `navbarpembeli.php`.
- **Diakses / dipanggil dari:** Klik kartu menu / produk terlaris di `index.php` (`detail.php?id=X`).
- **Link keluar (href ke halaman lain):**
  - `../index/index.php` — tombol "Batal" dan "Kembali ke Menu" (baris 121, 136).
- **Form action (tujuan submit) & method:** `action="../keranjang/proseskeranjang.php"` method **POST** (baris 103) — kirim `aksi=tambah`, `id_menu`, `kembali=keranjang`, `qty` (input number, min 1 max stok).
- **Redirect (header Location):**
  - `../index/index.php` jika `id` tidak valid (baris 13) atau menu tidak ditemukan (baris 26).
- **Tabel database & operasinya:**
  - `tb_menu` LEFT JOIN `tb_toko` (prepared): `SELECT m.*, t.nama_toko WHERE m.id_menu=? AND m.deleted=0` (baris 18).
- **Catatan teknik penting:** Prepared statement; `(int)` untuk id. `$tersedia` = stok > 0 && status='aktif' (form tambah hanya muncul jika tersedia). `nl2br(htmlspecialchars(...))` untuk deskripsi. `kembali=keranjang` → setelah tambah diarahkan ke keranjang.

---

### pembeli\pesanan\struk.php
- **Jenis:** Tampilan (UI).
- **Tujuan singkat:** Menampilkan struk digital satu pesanan: nomor antrian harian, info pesanan, daftar item, total; bisa dicetak.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`, `navbarpembeli.php`.
- **Diakses / dipanggil dari:** Redirect sukses dari `prosespesanan.php` (`?id_order=X&baru=1`); tombol "Struk" di `pesanan.php` (status Selesai).
- **Link keluar (href ke halaman lain):**
  - `rating.php?id_order=<id>` — "Beri Rating" (selesai & belum dirating, baris 212).
  - `../index/index.php` — "Kembali ke Beranda" (baris 218).
- **Form action (tujuan submit) & method:** Tidak ada form.
- **Redirect (header Location):**
  - `pesanan.php` jika `id_order` tidak valid (baris 21) atau pesanan tidak ditemukan/bukan milik pembeli (baris 34).
- **Tabel database & operasinya:**
  - `tb_order` (prepared): `SELECT o.*, o.nama_toko_snapshot AS nama_toko WHERE id_order=? AND id_user=? AND deleted=0` (baris 25) — filter `id_user` mencegah akses pesanan orang lain.
  - `tb_detail_order` LEFT JOIN `tb_menu` (prepared): `SELECT d.*, COALESCE(d.nama_menu_snapshot, m.nama_menu) AS nama_menu WHERE d.id_order=? AND d.deleted=0` (baris 37).
  - `tb_order` (prepared): `SELECT COUNT(*) WHERE id_penjual=? AND DATE(tanggal_order)=DATE(?) AND id_order<=? AND deleted=0` (hitung nomor antrian harian per penjual, baris 55).
  - `tb_rating` (prepared): `SELECT id_rating WHERE id_order=? AND id_user=?` (cek sudah rating, baris 66).
- **Catatan teknik penting:** Nomor antrian dihitung per `id_penjual` + per hari (`DATE()`), bukan per slot toko — supaya antrian penjual baru tak tercampur penjual lama. Nama toko & menu dari snapshot. Subtotal dihitung ulang dari kolom `subtotal` detail. Banner sukses tampil saat `?baru` ada. `str_pad` untuk nomor pesanan (6 digit) & nomor antrian (3 digit). Kelas `takprint` menyembunyikan elemen saat cetak. `htmlspecialchars` & `number_format`.

---

### pembeli\pesanan\rating.php
- **Jenis:** Tampilan (UI) dengan validasi/redirect di awal.
- **Tujuan singkat:** Form beri rating bintang (1-5), ulasan teks, dan tag cepat untuk pesanan yang sudah selesai.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`, `navbarpembeli.php`.
- **Diakses / dipanggil dari:** Tombol "Rating" di `pesanan.php` (riwayat); tombol "Beri Rating" di `struk.php`.
- **Link keluar (href ke halaman lain):**
  - `pesanan.php?tab=riwayat` — tombol "Batal" (baris 141).
- **Form action (tujuan submit) & method:** `action="prosesrating.php"` method **POST** (baris 88) — kirim hidden `id_order`, radio `rating_toko` (1-5, required), `ulasan` (textarea), `tag[]` (checkbox banyak).
- **Redirect (header Location):**
  - `pesanan.php` jika `id_order` tidak valid (baris 16) atau pesanan tidak ditemukan (baris 30).
  - `pesanan.php?tab=riwayat` jika status bukan Selesai/Siap Diambil (baris 32).
  - `struk.php?id_order=$idpesanan` jika sudah pernah memberi rating (baris 40).
- **Tabel database & operasinya:**
  - `tb_order` (prepared): `SELECT o.*, o.nama_toko_snapshot AS nama_toko WHERE id_order=? AND id_user=? AND deleted=0` (baris 21) — filter id_user.
  - `tb_rating` (prepared): `SELECT id_rating WHERE id_order=? AND id_user=?` (cek duplikat, baris 37).
  - `tb_detail_order` LEFT JOIN `tb_menu` (prepared): `SELECT d.id_menu, d.jumlah, COALESCE(d.nama_menu_snapshot, m.nama_menu) AS nama_menu WHERE d.id_order=? AND d.deleted=0` (konteks item, baris 45).
- **Catatan teknik penting:** Rating hanya untuk status Selesai/Siap Diambil; tidak boleh dobel (redirect ke struk jika sudah). Bintang pakai CSS radio trick (flex row-reverse + selector `~`), tag pakai CSS checkbox trick — tanpa JS. `md5($tag)` jadi id unik label. Snapshot nama toko/menu. `htmlspecialchars`.

---

### pembeli\pesanan\prosesrating.php
- **Jenis:** Proses (logika) — tanpa HTML.
- **Tujuan singkat:** Menyimpan rating & ulasan ke `tb_rating` setelah validasi & verifikasi kepemilikan pesanan.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`. (Tidak ada navbar.)
- **Diakses / dipanggil dari:** Form POST dari `rating.php`.
- **Link keluar (href ke halaman lain):** Tidak ada (hanya redirect).
- **Form action (tujuan submit) & method:** Tidak ada form (file penerima POST).
- **Redirect (header Location):** Semua diakhiri `exit`.
  - Bukan POST → `pesanan.php` (baris 12).
  - Data tidak valid (id/nilai bintang) → `pesanan.php?tab=riwayat` (baris 37).
  - Pesanan tidak ditemukan / belum selesai → `pesanan.php?tab=riwayat` (baris 53).
  - Sudah pernah rating → `pesanan.php?tab=riwayat` (baris 63).
  - Gagal insert → `pesanan.php?tab=riwayat` (baris 77).
  - **Sukses** → `pesanan.php?tab=riwayat` (baris 83).
- **Tabel database & operasinya:**
  - `tb_order` (prepared): `SELECT id_toko, id_penjual, status_order WHERE id_order=? AND id_user=? AND deleted=0` (verifikasi kepemilikan & status, baris 44).
  - `tb_rating` (prepared): `SELECT id_rating WHERE id_order=? AND id_user=?` (cek duplikat, baris 58).
  - `tb_rating` (prepared INSERT, baris 71): kolom `id_order, id_user, id_toko, id_penjual, rating_toko, ulasan`.
- **Catatan teknik penting:** Prepared statement; verifikasi `id_user` mencegah rating pesanan orang lain. Tag (`tag[]`) digabung ke teks `ulasan` (dipisah koma; jika ada ulasan, ditambah di baris baru). Nilai bintang divalidasi 1-5. Flash via `$_SESSION['flash']`. Pola PRG.

---

### pembeli\pesanan\chat.php
- **Jenis:** Tampilan (UI) + Proses (menangani POST kirim pesan di file yang sama).
- **Tujuan singkat:** Chat pembeli ↔ penjual per pesanan; kirim/baca pesan selama pesanan masih aktif, riwayat tetap terbaca saat selesai/dibatalkan.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`, `navbarpembeli.php`.
- **Diakses / dipanggil dari:** Tombol "Chat Penjual" di `pesanan.php` (tab aktif); redirect internal sendiri setelah kirim pesan (PRG).
- **Link keluar (href ke halaman lain):**
  - `pesanan.php?tab=aktif` atau `?tab=riwayat` — tombol "Kembali" (tergantung status, baris 411).
- **Form action (tujuan submit) & method:** `action="chat.php"` method **POST** (baris 492) — kirim hidden `id_order`, `pesan` (textarea, maxlength 500). Form hanya muncul jika status aktif.
- **Redirect (header Location):**
  - `pesanan.php` jika `id_order` tidak valid (baris 29) atau pesanan tidak ditemukan/bukan milik pembeli (baris 47).
  - Setelah POST kirim pesan → `chat.php?id_order=<id>#latest` (PRG, baris 94).
- **Tabel database & operasinya:**
  - `tb_order` (prepared): `SELECT o.id_order, id_user, id_penjual, status_order, nama_toko_snapshot AS nama_toko, tanggal_order, total_harga, catatan, metode_pembayaran WHERE id_order=? AND id_user=? AND deleted=0` (baris 34) — filter id_user.
  - `tb_detail_order` LEFT JOIN `tb_menu` (prepared): `SELECT d.jumlah, d.harga_satuan, d.subtotal, COALESCE(d.nama_menu_snapshot, m.nama_menu) AS nama_menu WHERE d.id_order=? AND d.deleted=0` (kartu detail, baris 56).
  - `tb_chat` (prepared INSERT, hanya saat POST & status aktif & pesan valid, baris 87): kolom `id_order, id_pengirim, pesan`.
  - `tb_chat` (prepared SELECT, baris 99): `SELECT id_chat, id_pengirim, pesan, created WHERE id_order=? ORDER BY created ASC, id_chat ASC` (daftar pesan).
  - `tb_chat` (prepared UPDATE, baris 112): `SET dibaca=1 WHERE id_order=? AND id_pengirim=? AND dibaca=0` (tandai pesan dari penjual sebagai dibaca).
- **Catatan teknik penting:** `$statusAktif` (Menunggu/Diproses/Siap Diambil) menentukan boleh kirim & tampilnya `<meta refresh 15s>`. Tanpa JS: PRG untuk kirim, anchor `#latest` untuk auto-scroll, meta refresh untuk update. Bubble `mine` vs `theirs` dibandingkan `id_pengirim === id_user`. `nl2br(htmlspecialchars())` untuk isi pesan. `mb_strlen` batas 500. Snapshot nama toko.

---

### pembeli\profil\profil.php
- **Jenis:** Tampilan (UI).
- **Tujuan singkat:** Halaman profil pembeli: info akun, statistik belanja, dan navigasi ke edit profil / ganti password / logout.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`, `navbarpembeli.php`.
- **Diakses / dipanggil dari:** Navbar (menu Profil); redirect "Batal"/sukses dari `editprofil.php` & `gantipassword.php`.
- **Link keluar (href ke halaman lain):**
  - `editprofil.php` — "Edit Profil" (baris 120).
  - `gantipassword.php` — "Ganti Password" (baris 130).
  - `#modal-kontak-pembeli` — "Hubungi Admin" (anchor modal CSS :target, baris 152).
  - `../../4. autentifikasi/konfirmasilogout.php?peran=pembeli` — "Keluar" (baris 163).
- **Form action (tujuan submit) & method:** Tidak ada form.
- **Redirect (header Location):**
  - `../../4. autentifikasi/logout.php` jika user tidak ditemukan di DB (mis. dihapus admin) (baris 23).
- **Tabel database & operasinya:**
  - `tb_user` (prepared): `SELECT * WHERE id_user=? AND deleted=0` (baris 17).
  - `tb_order` (prepared, 3 query COUNT/SUM): `COUNT(*) ... status_order NOT IN ('Dibatalkan')` (total pesanan, baris 29); `COUNT(*) ... status_order='Selesai'` (selesai, baris 34); `COALESCE(SUM(total_harga),0) ... status_order='Selesai'` (total belanja, baris 45).
  - `tb_rating` (prepared): `SELECT COUNT(*) WHERE id_user=? AND deleted=0` (total ulasan, baris 39).
  - `tb_detail_order` JOIN `tb_order` (prepared): `SELECT COALESCE(SUM(d.jumlah),0) ... WHERE o.id_user=? AND o.status_order='Selesai' AND ...` (total item dibeli, baris 51).
- **Catatan teknik penting:** 5 query statistik dipisah agar mudah dibaca. `COALESCE(...,0)` cegah null. Format belanja disingkat ("jt"/"rb"). Inisial avatar `mb_substr` 2 huruf. `htmlspecialchars`. Tombol "Keluar"/"Hubungi Admin" hanya mobile (class `sembunyidesktop`).

---

### pembeli\profil\editprofil.php
- **Jenis:** Tampilan (UI) + Proses (POST di file yang sama).
- **Tujuan singkat:** Edit username & email pembeli (GET tampilkan form, POST validasi & simpan). Nama lengkap/kelas/jurusan read-only.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`, `navbarpembeli.php`.
- **Diakses / dipanggil dari:** Link "Edit Profil" di `profil.php`; submit ke diri sendiri.
- **Link keluar (href ke halaman lain):**
  - `profil.php` — tombol "Batal" (baris 188).
- **Form action (tujuan submit) & method:** `action="editprofil.php"` method **POST** (baris 138) — kirim `username`, `email`.
- **Redirect (header Location):**
  - `profil.php` setelah simpan sukses (baris 52). (Jika validasi gagal, tetap di halaman dan tampilkan `$error`.)
- **Tabel database & operasinya:**
  - `tb_user` (prepared): `SELECT id_user WHERE (username=? OR email=?) AND id_user!=? AND deleted=0` (cek duplikat saat POST, baris 34).
  - `tb_user` (prepared UPDATE): `SET username=?, email=? WHERE id_user=?` (simpan, baris 43).
  - `tb_user` (prepared): `SELECT * WHERE id_user=? AND deleted=0` (isi form, baris 60).
  - `tb_order` (prepared, 3 query): COUNT total pesanan (baris 68), COUNT selesai (baris 73), `COALESCE(SUM(total_harga),0)` selesai (baris 78) — statistik header.
- **Catatan teknik penting:** Validasi server: username 6-50 char, email `filter_var(...FILTER_VALIDATE_EMAIL)`, cek duplikat (kecuali diri sendiri). Setelah sukses perbarui `$_SESSION['username']` & `$_SESSION['email']` agar navbar ikut update. Flash via session. Field nama_lengkap/kelas/peran/created ditampilkan `disabled`. Pola PRG. `htmlspecialchars`.

---

### pembeli\profil\gantipassword.php
- **Jenis:** Tampilan (UI) + Proses (POST di file yang sama).
- **Tujuan singkat:** Ganti password pembeli: verifikasi password lama, validasi password baru, hash bcrypt, simpan.
- **Komponen yang di-include:** `guardpembeli.php`, `koneksi.php`, `navbarpembeli.php`.
- **Diakses / dipanggil dari:** Link "Ganti Password" di `profil.php`; submit ke diri sendiri.
- **Link keluar (href ke halaman lain):**
  - `profil.php` — tombol "Batal" (baris 183).
- **Form action (tujuan submit) & method:** `action="gantipassword.php"` method **POST** (baris 135) — kirim `password_lama`, `password_baru`, `konfirmasi`.
- **Redirect (header Location):** Tidak ada — pesan `$error`/`$sukses` ditampilkan di halaman yang sama (tidak pakai PRG di sini).
- **Tabel database & operasinya:**
  - `tb_user` (prepared): `SELECT password WHERE id_user=? AND deleted=0` (ambil hash lama saat POST, baris 35).
  - `tb_user` (prepared UPDATE): `SET password=? WHERE id_user=?` (simpan hash baru, baris 47).
  - `tb_user` (prepared): `SELECT * WHERE id_user=? AND deleted=0` (data hero, baris 56).
  - `tb_order` (prepared, 3 query): COUNT total pesanan (baris 62), COUNT selesai (baris 66), `COALESCE(SUM(total_harga),0)` selesai (baris 70) — statistik header.
- **Catatan teknik penting:** Validasi server: semua kolom wajib, password baru 8-100 char, konfirmasi cocok. `password_verify` cek password lama, `password_hash(..., PASSWORD_BCRYPT)` untuk hash baru. Input password tidak di-`trim` (spasi bisa disengaja). Tombol show/hide pakai inline JS minimal (hanya untuk aksesibilitas tampilan). `htmlspecialchars`.

---

## Ringkasan Alur Utama

1. **Belanja:** `index.php`/`detail.php` → form POST `proseskeranjang.php` (aksi tambah, sinkron ke `tb_keranjang`) → redirect ke beranda/keranjang.
2. **Keranjang:** `keranjang.php` (baca session) → kontrol qty/hapus via `proseskeranjang.php` → tombol checkout per toko.
3. **Pesan:** `checkout.php?toko=X` → form POST `prosespesanan.php` (transaksi: INSERT `tb_order` + INSERT `tb_detail_order` snapshot + UPDATE stok `tb_menu` + DELETE `tb_keranjang`) → redirect `struk.php?...&baru=1` (PRG).
4. **Pasca-pesan:** `pesanan.php` (tab aktif/riwayat) → `chat.php` (tb_chat), `struk.php`, `rating.php` → POST `prosesrating.php` (INSERT `tb_rating`).
5. **Akun:** `profil.php` → `editprofil.php` (UPDATE `tb_user`), `gantipassword.php` (UPDATE `tb_user` password bcrypt).
