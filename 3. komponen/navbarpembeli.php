<?php
/* ============================================================
   NAVBAR PEMBELI
   Di-include di setiap halaman pembeli.
   Setiap halaman harus mendefinisikan $pathbase = '..' sebelum
   include file ini, supaya link navbar mengarah ke folder yang benar.

   Contoh: di pembeli/index/index.php → $pathbase = '..';
   Contoh: di pembeli/keranjang/keranjang.php → $pathbase = '..';
   ============================================================ */

if (session_status() === PHP_SESSION_NONE) session_start();

// Pastikan $pathbase sudah didefinisikan oleh halaman yang include
if (!isset($pathbase)) $pathbase = '..';

// Cek halaman aktif berdasarkan nama file
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

// Ambil dan hapus flash message dari session (jika ada)
$flashpesan = '';
$flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']);
}

// Tentukan apakah tiap menu aktif
$berandaaktif  = ($halamansaatini === 'index') ? 'aktif' : '';
$keranjangaktif = ($halamansaatini === 'keranjang') ? 'aktif' : '';
$pesananaktif  = in_array($halamansaatini, ['pesanan','struk','rating']) ? 'aktif' : '';
$profilaktif   = in_array($halamansaatini, ['profil','editprofil','gantipassword']) ? 'aktif' : '';
?>

<nav class="navbarpembeli">

  <!-- Logo (hanya tampil di sidebar desktop) -->
  <div class="logosidebar">
    <div class="namaapp">
      <i class="fa-solid fa-utensils"></i>
      eKantin
    </div>
    <div class="taglineapp">Pesan & nikmati</div>
  </div>

  <!-- Beranda -->
  <a href="<?= $pathbase ?>/index/index.php" class="itemnav <?= $berandaaktif ?>">
    <i class="fa-solid fa-house"></i>
    <span>Beranda</span>
  </a>

  <!-- Keranjang -->
  <a href="<?= $pathbase ?>/keranjang/keranjang.php" class="itemnav <?= $keranjangaktif ?>">
    <i class="fa-solid fa-cart-shopping"></i>
    <span>Keranjang</span>
    <?php if ($jumlahkeranjang > 0): ?>
      <span class="lencanakeranjang"><?= $jumlahkeranjang ?></span>
    <?php endif; ?>
  </a>

  <!-- Pesanan -->
  <a href="<?= $pathbase ?>/pesanan/pesanan.php" class="itemnav <?= $pesananaktif ?>">
    <i class="fa-solid fa-receipt"></i>
    <span>Pesanan</span>
  </a>

  <!-- Profil -->
  <a href="<?= $pathbase ?>/profil/profil.php" class="itemnav <?= $profilaktif ?>">
    <i class="fa-solid fa-user"></i>
    <span>Profil</span>
  </a>

  <!-- Keluar (hanya tampil di sidebar desktop) -->
  <a href="../../4. autentifikasi/logout.php" class="itemnav itemkeluar">
    <i class="fa-solid fa-right-from-bracket"></i>
    <span>Keluar</span>
  </a>

</nav>

<!-- Flash Message (pesan setelah redirect) -->
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
