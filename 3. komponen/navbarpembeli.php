<?php
/*
   navbar pembeli — komponen navigasi utama untuk halaman-halaman pembeli.
   di mobile: tampil sebagai bottom tab bar di bawah layar.
   di desktop: tampil sebagai sidebar tetap di sisi kiri, warna gelap selaras
   dengan tampilan admin dan penjual.
   dibuat tanpa javascript sama sekali — semua interaksi menggunakan css murni.
*/

// mulai session pembeli jika belum aktif
// session dibutuhkan untuk membaca data login, keranjang belanja, dan flash message
if (session_status() === PHP_SESSION_NONE) {
    session_name('sesi_pembeli'); // nama khusus agar tidak bentrok dengan session penjual/admin
    session_start();
}

// $pathbase adalah awalan path relatif untuk link antar halaman pembeli
// nilai defaultnya '..' karena kebanyakan halaman berada satu folder di bawah folder pembeli/
// halaman pemanggil bisa mendefinisikan $pathbase sendiri sebelum include file ini
if (!isset($pathbase)) $pathbase = '..';

// ambil nama file halaman yang sedang dibuka (tanpa ekstensi .php)
// ini dipakai untuk menentukan menu mana yang harus ditandai sebagai aktif
// contoh: jika sedang di halaman pesanan.php maka $halamansaatini = 'pesanan'
$halamansaatini = basename($_SERVER['PHP_SELF'], '.php');

// hitung total item di keranjang belanja untuk ditampilkan sebagai badge angka.
// PENTING: gunakan nama variabel loop SENDIRI ($idtoko_brg / $itemtoko_brg), JANGAN $idtoko.
// navbar ini di-include di tengah halaman (mis. beranda) yang juga memakai $idtoko untuk
// menyimpan kantin yang sedang dipilih pembeli. kalau loop ini memakai $idtoko, nilainya
// tertimpa jadi id toko terakhir di keranjang → dropdown "Pilih Kantin" jadi salah pilih.
$jumlahkeranjang = 0;
if (!empty($_SESSION['keranjang'])) {
    // keranjang disusun per toko: $_SESSION['keranjang'][id_toko][kunci_item]
    foreach ($_SESSION['keranjang'] as $idtoko_brg => $itemtoko_brg) {
        foreach ($itemtoko_brg as $kunci => $isi) {
            if ($kunci === '_info') continue; // '_info' adalah metadata toko, bukan item — skip
            $jumlahkeranjang += (int)($isi['qty'] ?? 0); // jumlahkan qty setiap item
        }
    }
}

// ambil flash message dari session jika ada
// flash message adalah pesan sementara (sukses/gagal) yang muncul sekali lalu hilang
$flashpesan = '';
$flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan']; // teks pesan yang akan ditampilkan
    $flashjenis = $_SESSION['flash']['jenis']; // 'sukses', 'gagal', atau 'info' — menentukan warna
    unset($_SESSION['flash']); // hapus dari session agar hanya muncul sekali (one-time message)
}

// muat komponen pengumuman sistem dari file terpisah
// file pengumuman.php akan mendefinisikan variabel $teks_pengumuman
require_once __DIR__ . '/pengumuman.php';

// hitung jumlah pesanan dengan status 'Siap Diambil' untuk badge notifikasi
// badge ini muncul di ikon pesanan dan di notifikasi banner di beranda
$jumlahsiap = 0;
if (!empty($_SESSION['id_user'])) {
    // global $conn diperlukan karena $conn didefinisikan di luar fungsi/file ini
    global $conn;
    if (isset($conn) && $conn) {
        // hitung pesanan milik user ini yang sudah siap diambil dan belum dihapus
        $qsiap = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND status_order='Siap Diambil' AND deleted=0");
        if ($qsiap) {
            $id_user_aktif = (int)$_SESSION['id_user']; // konversi ke integer untuk keamanan
            $qsiap->bind_param("i", $id_user_aktif);
            $qsiap->execute();
            $jumlahsiap = (int)$qsiap->get_result()->fetch_row()[0]; // ambil angka dari baris pertama
            $qsiap->close();
        }
    }
}

// tentukan kelas 'aktif' untuk setiap menu berdasarkan halaman yang sedang dibuka
// class 'aktif' dipakai oleh css untuk memberikan warna/style berbeda pada item yang dipilih
$berandaaktif   = $halamansaatini === 'index'                                        ? 'aktif' : '';
$keranjangaktif = $halamansaatini === 'keranjang'                                    ? 'aktif' : '';
$pesananaktif   = in_array($halamansaatini, ['pesanan','struk'])                     ? 'aktif' : ''; // struk masuk kelompok pesanan
$profilaktif    = in_array($halamansaatini, ['profil','editprofil','gantipassword']) ? 'aktif' : '';
?>

<!-- navbar utama pembeli — di desktop menjadi sidebar kiri, di mobile menjadi tab bawah -->
<nav class="navbarpembeli">

  <!-- bagian logo dan tagline aplikasi — hanya terlihat di sidebar desktop.
       <span class="logo-app besar"> = logo PNG via CSS mask, warna ikut color
       teks parent (var(--putihbg) terang). varian .besar = 24x24 px. -->
  <div class="logosidebar">
    <div class="namaapp"><span class="logo-app besar"></span> jajankita</div>
    <div class="taglineapp">Pesan &amp; nikmati</div>
  </div>

  <!-- daftar link navigasi utama -->
  <div class="sidebar-nav-pembeli">

    <!-- menu beranda — aktif jika nama halaman adalah 'index' -->
    <a href="<?= $pathbase ?>/index/index.php" class="itemnav <?= $berandaaktif ?>">
      <i class="fa-solid fa-house"></i><span>Beranda</span>
    </a>

    <!-- menu keranjang — menampilkan badge angka jika ada item di keranjang -->
    <a href="<?= $pathbase ?>/keranjang/keranjang.php" class="itemnav <?= $keranjangaktif ?>">
      <i class="fa-solid fa-cart-shopping"></i><span>Keranjang</span>
      <?php if ($jumlahkeranjang > 0): ?>
        <!-- badge angka hanya ditampilkan jika keranjang tidak kosong -->
        <span class="lencanakeranjang"><?= $jumlahkeranjang ?></span>
      <?php endif; ?>
    </a>

    <!-- menu pesanan — badge hijau muncul jika ada pesanan yang siap diambil -->
    <a href="<?= $pathbase ?>/pesanan/pesanan.php" class="itemnav <?= $pesananaktif ?>">
      <i class="fa-solid fa-receipt"></i><span>Pesanan</span>
      <?php if ($jumlahsiap > 0): ?>
        <!-- badge warna sukses (hijau) untuk membedakan dari badge keranjang (merah) -->
        <span class="lencanakeranjang" style="background:var(--sukses);"><?= $jumlahsiap ?></span>
      <?php endif; ?>
    </a>

    <!-- garis pemisah tipis antara menu utama dan menu tambahan -->
    <div style="height:1px;background:rgba(255,255,255,.15);margin:6px 4px;"></div>

    <!-- menu profil — aktif di halaman profil, editprofil, dan gantipassword -->
    <a href="<?= $pathbase ?>/profil/profil.php" class="itemnav <?= $profilaktif ?>">
      <i class="fa-solid fa-user"></i><span>Profil</span>
    </a>

    <!-- tombol kontak admin — hanya terlihat di sidebar desktop (disembunyikan di mobile via css)
         klik link ini mengubah fragment url ke #modal-kontak-pembeli sehingga modal terbuka via css :target -->
    <a href="#modal-kontak-pembeli" class="itemnav itemnav-kontak">
      <i class="fa-solid fa-headset"></i><span>Hubungi Admin</span>
    </a>

  </div>

  <!-- bagian bawah sidebar: tombol keluar dari akun -->
  <div class="sidebar-footer-pembeli">
    <!-- parameter peran=pembeli memberitahu halaman konfirmasi logout mana yang perlu dihapus -->
    <a href="../../4. autentifikasi/konfirmasilogout.php?peran=pembeli"
       class="itemnav itemkeluar">
      <i class="fa-solid fa-right-from-bracket"></i><span>Keluar</span>
    </a>
  </div>

</nav>

<!-- modal kontak admin — muncul saat url mengandung fragment #modal-kontak-pembeli
     teknik css :target: elemen dengan id yang cocok akan mendapat pseudo-class :target
     dan css mengubah visibilitasnya dari tersembunyi menjadi terlihat -->
<div class="modaloverlay" id="modal-kontak-pembeli">
  <div class="isimodal" style="text-align:center;">
    <!-- pegangan visual di bagian atas modal (seperti bottom sheet di aplikasi mobile) -->
    <div class="peganganmodal"></div>
    <div style="font-size:40px;color:var(--kedua);margin-bottom:10px;">
      <i class="fa-solid fa-headset"></i>
    </div>
    <div style="font-size:17px;font-weight:800;color:var(--utama);margin-bottom:6px;">Hubungi Admin</div>
    <div style="font-size:13px;color:var(--tekssamar);margin-bottom:18px;">
      Butuh bantuan? Hubungi admin jajankita melalui:
    </div>
    <!-- tombol kontak email — target="_blank" tidak perlu di sini karena href mailto akan membuka aplikasi email -->
    <a href="mailto:admin@jajankita.my.id" class="tombolutama blok" style="margin-bottom:10px;">
      <i class="fa-solid fa-envelope"></i> admin@jajankita.my.id
    </a>
    <!-- tombol whatsapp — target="_blank" membuka di tab baru agar tidak keluar dari aplikasi -->
    <a href="https://wa.me/6285648830046" target="_blank" class="tombolkedua blok" style="margin-bottom:14px;">
      <i class="fa-brands fa-whatsapp"></i> WhatsApp Admin
    </a>
    <!-- tombol tutup modal — href="#" menghapus fragment dari url sehingga :target tidak berlaku lagi -->
    <a href="#" class="tombolringan blok">
      <i class="fa-solid fa-xmark"></i> Tutup
    </a>
  </div>
</div>

<?php
/*
   area notifikasi — tiga blok berikut ditampilkan di bawah navbar dan di atas konten halaman.
   urutan: pengumuman sistem → notifikasi pesanan siap → flash message
*/
?>

<?php if (!empty($teks_pengumuman)): ?>
<!-- banner pengumuman sistem dari admin — ditampilkan jika isi file tekspengumuman.txt tidak kosong -->
<div class="notif-sistem">
  <i class="fa-solid fa-bullhorn"></i>
  <?= htmlspecialchars($teks_pengumuman) ?> <!-- htmlspecialchars mencegah XSS dari isi file teks -->
</div>
<?php endif; ?>

<?php if ($jumlahsiap > 0 && $halamansaatini === 'index'): ?>
<!-- notifikasi pesanan siap diambil — hanya muncul di halaman beranda (index)
     agar pembeli segera tahu tanpa harus membuka halaman pesanan dulu -->
<div class="notif-siap">
  <i class="fa-solid fa-bell"></i>
  <span><strong><?= $jumlahsiap ?> pesanan siap diambil!</strong> Segera ambil di kantin.</span>
  <a href="<?= $pathbase ?>/pesanan/pesanan.php" class="notif-link">Lihat →</a>
</div>
<?php endif; ?>

<?php if ($flashpesan): ?>
<!-- flash message — pesan sementara hasil dari aksi sebelumnya (misal: berhasil pesan, gagal bayar)
     jenis pesan menentukan ikon: sukses = centang, gagal = silang, lainnya = info -->
<div class="flash-wrapper">
  <div class="flashpesan flash<?= $flashjenis ?>">
    <?php if ($flashjenis === 'sukses'): ?>
      <i class="fa-solid fa-circle-check"></i>
    <?php elseif ($flashjenis === 'gagal'): ?>
      <i class="fa-solid fa-circle-xmark"></i>
    <?php else: ?>
      <i class="fa-solid fa-circle-info"></i>
    <?php endif; ?>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
</div>
<?php endif; ?>
