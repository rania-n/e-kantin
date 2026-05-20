<?php
/*
   navbar pembeli
   mobile  : bottom tab bar
   desktop : sidebar tetap kiri, warna gelap (utama) selaras admin dan penjual
   tanpa javascript sama sekali
*/
if (session_status() === PHP_SESSION_NONE) {
    session_name('sesi_pembeli');
    session_start();
}
if (!isset($pathbase)) $pathbase = '..';

$halamansaatini = basename($_SERVER['PHP_SELF'], '.php');

// hitung item keranjang
$jumlahkeranjang = 0;
if (!empty($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $idtoko => $itemtoko) {
        foreach ($itemtoko as $kunci => $isi) {
            if ($kunci === '_info') continue;
            $jumlahkeranjang += (int)($isi['qty'] ?? 0);
        }
    }
}

// flash message dari session
$flashpesan = '';
$flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']);
}

// pengumuman sistem dari file konfigurasi
require_once __DIR__ . '/pengumuman.php';

// notifikasi pesanan siap diambil — cek dari db
$jumlahsiap = 0;
if (!empty($_SESSION['id_user'])) {
    global $conn;
    if (isset($conn) && $conn) {
        $qsiap = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND status_order='Siap Diambil' AND deleted=0");
        if ($qsiap) {
            $id_user_aktif = (int)$_SESSION['id_user'];
            $qsiap->bind_param("i", $id_user_aktif);
            $qsiap->execute();
            $jumlahsiap = (int)$qsiap->get_result()->fetch_row()[0];
            $qsiap->close();
        }
    }
}

$berandaaktif   = $halamansaatini === 'index'                                        ? 'aktif' : '';
$keranjangaktif = $halamansaatini === 'keranjang'                                    ? 'aktif' : '';
$pesananaktif   = in_array($halamansaatini, ['pesanan','struk'])                     ? 'aktif' : '';
$ulasanaktif    = $halamansaatini === 'rating'                                       ? 'aktif' : '';
$profilaktif    = in_array($halamansaatini, ['profil','editprofil','gantipassword']) ? 'aktif' : '';
?>

<nav class="navbarpembeli">

  <div class="logosidebar">
    <div class="namaapp"><i class="fa-solid fa-utensils"></i> jajankita</div>
    <div class="taglineapp">Pesan &amp; nikmati</div>
  </div>

  <div class="sidebar-nav-pembeli">

    <a href="<?= $pathbase ?>/index/index.php" class="itemnav <?= $berandaaktif ?>">
      <i class="fa-solid fa-house"></i><span>Beranda</span>
    </a>

    <a href="<?= $pathbase ?>/keranjang/keranjang.php" class="itemnav <?= $keranjangaktif ?>">
      <i class="fa-solid fa-cart-shopping"></i><span>Keranjang</span>
      <?php if ($jumlahkeranjang > 0): ?>
        <span class="lencanakeranjang"><?= $jumlahkeranjang ?></span>
      <?php endif; ?>
    </a>

    <a href="<?= $pathbase ?>/pesanan/pesanan.php" class="itemnav <?= $pesananaktif ?>">
      <i class="fa-solid fa-receipt"></i><span>Pesanan</span>
      <?php if ($jumlahsiap > 0): ?>
        <span class="lencanakeranjang" style="background:var(--sukses);"><?= $jumlahsiap ?></span>
      <?php endif; ?>
    </a>

    <a href="<?= $pathbase ?>/pesanan/rating.php" class="itemnav <?= $ulasanaktif ?>">
      <i class="fa-solid fa-star"></i><span>Ulasan</span>
    </a>

    <a href="<?= $pathbase ?>/profil/profil.php" class="itemnav <?= $profilaktif ?>">
      <i class="fa-solid fa-user"></i><span>Profil</span>
    </a>

    <!-- hanya muncul di sidebar desktop -->
    <a href="#modal-kontak-pembeli" class="itemnav itemnav-kontak">
      <i class="fa-solid fa-headset"></i><span>Hubungi Admin</span>
    </a>

  </div>

  <div class="sidebar-footer-pembeli">
    <a href="../../4. autentifikasi/konfirmasilogout.php?peran=pembeli"
       class="itemnav itemkeluar">
      <i class="fa-solid fa-right-from-bracket"></i><span>Keluar</span>
    </a>
  </div>

</nav>

<!-- modal kontak admin via css :target -->
<div class="modaloverlay" id="modal-kontak-pembeli">
  <div class="isimodal" style="text-align:center;">
    <div class="peganganmodal"></div>
    <div style="font-size:40px;color:var(--kedua);margin-bottom:10px;">
      <i class="fa-solid fa-headset"></i>
    </div>
    <div style="font-size:17px;font-weight:800;color:var(--utama);margin-bottom:6px;">Hubungi Admin</div>
    <div style="font-size:13px;color:var(--tekssamar);margin-bottom:18px;">
      Butuh bantuan? Hubungi admin jajankita melalui:
    </div>
    <a href="mailto:admin@jajankita.my.id" class="tombolutama blok" style="margin-bottom:10px;">
      <i class="fa-solid fa-envelope"></i> admin@jajankita.my.id
    </a>
    <a href="https://wa.me/6281234567890" target="_blank" class="tombolkedua blok" style="margin-bottom:14px;">
      <i class="fa-brands fa-whatsapp"></i> WhatsApp Admin
    </a>
    <a href="#" class="tombolringan blok">
      <i class="fa-solid fa-xmark"></i> Tutup
    </a>
  </div>
</div>

<?php
/*
   notifikasi pesanan siap diambil — muncul sebelum konten beranda
   menggunakan div dengan background utama agar selaras dengan header sapaan
*/
?>

<?php if (!empty($teks_pengumuman)): ?>
<div class="notif-sistem">
  <i class="fa-solid fa-bullhorn"></i>
  <?= htmlspecialchars($teks_pengumuman) ?>
</div>
<?php endif; ?>

<?php if ($jumlahsiap > 0 && $halamansaatini === 'index'): ?>
<div class="notif-siap">
  <i class="fa-solid fa-bell"></i>
  <span><strong><?= $jumlahsiap ?> pesanan siap diambil!</strong> Segera ambil di kantin.</span>
  <a href="<?= $pathbase ?>/pesanan/pesanan.php" class="notif-link">Lihat →</a>
</div>
<?php endif; ?>

<?php if ($flashpesan): ?>
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
