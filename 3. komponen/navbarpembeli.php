<?php
/* ============================================================
   NAVBAR PEMBELI — Di-include di setiap halaman pembeli.
   Setiap halaman wajib mendefinisikan $pathbase = '..'
   sebelum include file ini.
   ============================================================ */
if (session_status() === PHP_SESSION_NONE) {
    session_name('sesi_pembeli');
    session_start();
}
if (!isset($pathbase)) $pathbase = '..';

$halamansaatini = basename($_SERVER['PHP_SELF'], '.php');

// Hitung jumlah item keranjang dari SESSION
$jumlahkeranjang = 0;
if (!empty($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $idtoko => $itemtoko) {
        foreach ($itemtoko as $kunci => $isi) {
            if ($kunci === '_info') continue;
            $jumlahkeranjang += (int)($isi['qty'] ?? 0);
        }
    }
}

// Flash message
$flashpesan = '';
$flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']);
}

// Tentukan menu yang aktif
$berandaaktif   = $halamansaatini === 'index'                                              ? 'aktif' : '';
$keranjangaktif = $halamansaatini === 'keranjang'                                          ? 'aktif' : '';
$pesananaktif   = in_array($halamansaatini, ['pesanan','struk','rating'])                  ? 'aktif' : '';
$profilaktif    = in_array($halamansaatini, ['profil','editprofil','gantipassword'])        ? 'aktif' : '';
?>

<nav class="navbarpembeli">

  <div class="logosidebar">
    <div class="namaapp"><i class="fa-solid fa-utensils"></i> eKantin</div>
    <div class="taglineapp">Pesan &amp; nikmati</div>
  </div>

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
  </a>

  <a href="<?= $pathbase ?>/profil/profil.php" class="itemnav <?= $profilaktif ?>">
    <i class="fa-solid fa-user"></i><span>Profil</span>
  </a>

  <!-- Hubungi Admin — buka modal via CSS :target -->
  <a href="#modal-kontak-pembeli" class="itemnav">
    <i class="fa-solid fa-headset"></i><span>Hubungi Admin</span>
  </a>

  <!-- Tombol keluar — langsung ke halaman konfirmasi PHP -->
  <a href="../../4. autentifikasi/konfirmasilogout.php?peran=pembeli"
     class="itemnav itemkeluar">
    <i class="fa-solid fa-right-from-bracket"></i><span>Keluar</span>
  </a>

</nav>

<!-- Modal kontak admin pembeli — CSS :target -->
<div class="modaloverlay" id="modal-kontak-pembeli">
  <div class="isimodal" style="text-align:center;">
    <div class="peganganmodal"></div>
    <div style="font-size:40px;color:var(--kedua);margin-bottom:10px;">
      <i class="fa-solid fa-headset"></i>
    </div>
    <div style="font-size:17px;font-weight:800;color:var(--utama);margin-bottom:6px;">Hubungi Admin</div>
    <div style="font-size:13px;color:var(--tekssamar);margin-bottom:18px;">
      Butuh bantuan? Hubungi admin eKantin melalui:
    </div>
    <a href="mailto:admin@ekantin.sch.id"
       class="tombolutama blok" style="margin-bottom:10px;">
      <i class="fa-solid fa-envelope"></i> admin@ekantin.sch.id
    </a>
    <a href="https://wa.me/6281234567890"
       target="_blank"
       class="tombolkedua blok" style="margin-bottom:14px;">
      <i class="fa-brands fa-whatsapp"></i> WhatsApp Admin
    </a>
    <a href="#" class="tombolringan blok">
      <i class="fa-solid fa-xmark"></i> Tutup
    </a>
  </div>
</div>

<!-- Flash message (muncul setelah redirect) -->
<?php if ($flashpesan): ?>
<div style="padding: 0 16px; margin-top: 8px;">
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