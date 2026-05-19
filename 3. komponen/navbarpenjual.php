<?php
/* ============================================================
   SIDEBAR PENJUAL
   Sidebar toggle menggunakan CSS checkbox hack (tanpa JS).
   Modal kontak menggunakan CSS :target (tanpa JS).
   ============================================================ */
if (!isset($halamansaatini)) {
    $halamansaatini = basename($_SERVER['PHP_SELF'], '.php');
}
$namatoko       = htmlspecialchars($_SESSION['nama_toko']   ?? 'Toko Saya');
$statustoko     = $_SESSION['status_toko'] ?? 'buka';
$inisialpenjual = strtoupper(mb_substr($_SESSION['username'] ?? 'P', 0, 2));
?>

<!-- Checkbox tersembunyi untuk toggle sidebar (CSS hack) -->
<input type="checkbox" id="togel-sidebar" class="togel-input">

<!-- Header Mobile -->
<div class="mobile-header">
  <label for="togel-sidebar" class="tombolhambur" title="Menu">
    <i class="fa-solid fa-bars"></i>
  </label>
  <div class="judul">
    <i class="fa-solid fa-utensils"></i> eKantin — <?= $namatoko ?>
  </div>
</div>

<!-- Overlay mobile (label checkbox = klik untuk tutup sidebar) -->
<label for="togel-sidebar" class="overlay-sidebar"></label>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

  <div class="sidebar-logo">
    <div class="namaapp">
      <i class="fa-solid fa-utensils"></i> eKantin
    </div>
    <div class="namatoko"><?= $namatoko ?></div>
    <div class="statustoko <?= $statustoko === 'buka' ? 'buka' : 'tutup' ?>">
      <i class="fa-solid fa-circle"></i>
      <?= $statustoko === 'buka' ? 'Toko Buka' : 'Toko Tutup' ?>
    </div>
  </div>

  <nav class="sidebar-nav">

    <a href="../../penjual/index/index.php"
       class="sidebar-item <?= in_array($halamansaatini, ['index','dashboard']) ? 'aktif' : '' ?>">
      <i class="fa-solid fa-gauge-high"></i>
      <span>Dashboard</span>
    </a>

    <a href="../../penjual/manajemenpesanan/manajemenpesanan.php"
       class="sidebar-item <?= $halamansaatini === 'manajemenpesanan' ? 'aktif' : '' ?>">
      <i class="fa-solid fa-clipboard-list"></i>
      <span>Pesanan Masuk</span>
    </a>

    <a href="../../penjual/manajemenmenu/manajemenmenu.php"
       class="sidebar-item <?= $halamansaatini === 'manajemenmenu' ? 'aktif' : '' ?>">
      <i class="fa-solid fa-bowl-food"></i>
      <span>Kelola Menu</span>
    </a>

    <a href="../../penjual/laporan/laporan.php"
       class="sidebar-item <?= $halamansaatini === 'laporan' ? 'aktif' : '' ?>">
      <i class="fa-solid fa-chart-bar"></i>
      <span>Laporan Penjualan</span>
    </a>

    <a href="../../penjual/ulasan/ulasan.php"
       class="sidebar-item <?= $halamansaatini === 'ulasan' ? 'aktif' : '' ?>">
      <i class="fa-solid fa-star"></i>
      <span>Semua Ulasan</span>
    </a>

    <div class="sidebar-divider"></div>

    <a href="../../penjual/profil/profil.php"
       class="sidebar-item <?= in_array($halamansaatini, ['profil','editprofil','gantipassword']) ? 'aktif' : '' ?>">
      <i class="fa-solid fa-user-tie"></i>
      <span>Profil &amp; Toko</span>
    </a>

    <!-- Buka modal kontak via CSS :target -->
    <a href="#modal-kontak" class="sidebar-item">
      <i class="fa-solid fa-headset"></i>
      <span>Hubungi Admin</span>
    </a>

  </nav>

  <div class="sidebar-footer">
    <a href="../../4. autentifikasi/konfirmasilogout.php?peran=penjual"
       class="sidebar-item">
      <i class="fa-solid fa-right-from-bracket"></i>
      <span>Keluar</span>
    </a>
  </div>

</aside>

<!-- Modal kontak admin — dibuka via CSS :target (#modal-kontak) -->
<div class="modaloverlay" id="modal-kontak">
  <a href="#" class="penutup-modal"></a>
  <div class="isimodal" style="max-width:380px;text-align:center;position:relative;z-index:1;">
    <div style="font-size:44px; color:var(--kedua); margin-bottom:10px;">
      <i class="fa-solid fa-headset"></i>
    </div>
    <div style="font-size:18px; font-weight:800; color:var(--utama); margin-bottom:6px;">Hubungi Admin</div>
    <div style="font-size:13px; color:var(--tekssamar); margin-bottom:20px;">
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
