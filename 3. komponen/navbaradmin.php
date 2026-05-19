<?php
/* ============================================================
   SIDEBAR ADMIN
   ============================================================ */
if (!isset($halamansaatini)) {
    $halamansaatini = basename($_SERVER['PHP_SELF'], '.php');
}
$namaadmin = htmlspecialchars($_SESSION['username'] ?? 'Admin');
?>

<aside class="sidebar">

  <div class="sidebar-logo">
    <div class="namaapp"><i class="fa-solid fa-shield-halved"></i> Admin Panel</div>
    <div class="namaadmin"><?= $namaadmin ?></div>
    <div class="peranadmin">jajankita</div>
  </div>

  <nav class="sidebar-nav">

    <a href="../../admin/index/index.php"
       class="sidebar-item <?= in_array($halamansaatini, ['index','dashboard']) ? 'aktif' : '' ?>">
      <i class="fa-solid fa-gauge-high"></i>
      <span>Dashboard</span>
    </a>

    <a href="../../admin/manajemenpengguna/user.php"
       class="sidebar-item <?= in_array($halamansaatini, ['user','viewuser','tambahuser','edituser','hapususer','toko','viewtoko','edittoko','hapustoko']) ? 'aktif' : '' ?>">
      <i class="fa-solid fa-users"></i>
      <span>Manajemen Pengguna</span>
    </a>

    <a href="../../admin/laporan/laporan.php"
       class="sidebar-item <?= $halamansaatini === 'laporan' ? 'aktif' : '' ?>">
      <i class="fa-solid fa-chart-bar"></i>
      <span>Laporan Platform</span>
    </a>

  </nav>

  <div class="sidebar-footer">
    <a href="../../4. autentifikasi/konfirmasilogout.php?peran=admin"
       class="sidebar-item">
      <i class="fa-solid fa-right-from-bracket"></i>
      <span>Keluar</span>
    </a>
  </div>

</aside>
