<?php
/* ============================================================
   SIDEBAR PENJUAL (PHP)
   Di-include di setiap halaman penjual.
   Membutuhkan $_SESSION['id_toko'], 'nama_toko', 'status_toko',
   dan variabel $halamansaatini (diset di setiap halaman).
   ============================================================ */
if (!isset($halamansaatini)) {
    $halamansaatini = basename($_SERVER['PHP_SELF'], '.php');
}
$namatoko   = htmlspecialchars($_SESSION['nama_toko']   ?? 'Toko Saya');
$statustoko = $_SESSION['status_toko'] ?? 'buka';
$inisialpenjual = strtoupper(mb_substr($_SESSION['username'] ?? 'P', 0, 2));
?>

<!-- Header Mobile -->
<div class="mobile-header">
  <button class="tombolhambur" onclick="toggleSidebar()" title="Menu">
    <i class="fa-solid fa-bars"></i>
  </button>
  <div class="judul">
    <i class="fa-solid fa-utensils"></i> eKantin — <?= $namatoko ?>
  </div>
</div>

<!-- Overlay mobile -->
<div class="overlay-sidebar" id="overlay" onclick="tutupSidebar()"></div>

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
      <span>Profil & Toko</span>
    </a>

    <a href="#"
       onclick="document.getElementById('modalkontak').classList.add('tampil'); return false;"
       class="sidebar-item">
      <i class="fa-solid fa-headset"></i>
      <span>Hubungi Admin</span>
    </a>

  </nav>

  <div class="sidebar-footer">
    <a href="#"
       onclick="document.getElementById('modallogoutpenjual').classList.add('tampil'); return false;"
       class="sidebar-item">
      <i class="fa-solid fa-right-from-bracket"></i>
      <span>Keluar</span>
    </a>
  </div>

</aside>

<!-- Modal kontak admin -->
<div class="modaloverlay" id="modalkontak"
     onclick="this.classList.remove('tampil')">
  <div class="isimodal" onclick="event.stopPropagation()" style="max-width:380px;text-align:center;">
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
    <button class="tombolringan blok"
            onclick="document.getElementById('modalkontak').classList.remove('tampil')">
      Tutup
    </button>
  </div>
</div>

<!-- Modal konfirmasi keluar -->
<div class="modaloverlay" id="modallogoutpenjual"
     onclick="this.classList.remove('tampil')">
  <div class="isimodal" onclick="event.stopPropagation()" style="max-width:380px;text-align:center;">
    <div style="font-size:44px; color:var(--utama); margin-bottom:10px;">
      <i class="fa-solid fa-right-from-bracket"></i>
    </div>
    <div style="font-size:18px; font-weight:800; color:var(--utama); margin-bottom:6px;">Yakin ingin keluar?</div>
    <div style="font-size:13px; color:var(--tekssamar); margin-bottom:20px;">Kamu perlu login lagi untuk mengakses panel penjual</div>
    <a href="../../4. autentifikasi/logout.php"
       class="tombolutama blok" style="margin-bottom:10px;">
      <i class="fa-solid fa-right-from-bracket"></i> Ya, Keluar
    </a>
    <button class="tombolringan blok"
            onclick="document.getElementById('modallogoutpenjual').classList.remove('tampil')">
      Batal
    </button>
  </div>
</div>

<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('aktif');
  document.getElementById('overlay').classList.toggle('tampil');
}
function tutupSidebar() {
  document.getElementById('sidebar').classList.remove('aktif');
  document.getElementById('overlay').classList.remove('tampil');
}
</script>
