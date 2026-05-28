<?php
/*
   navbar admin — komponen sidebar tetap untuk panel admin.
   sidebar ini muncul di sisi kiri layar dan berisi navigasi ke semua
   halaman yang bisa diakses admin. tidak menggunakan javascript sama sekali.
*/

// jika $halamansaatini belum didefinisikan oleh halaman pemanggil, tentukan sendiri
// basename($_SERVER['PHP_SELF'], '.php') mengambil nama file saat ini tanpa ekstensi
if (!isset($halamansaatini)) {
    $halamansaatini = basename($_SERVER['PHP_SELF'], '.php');
}

// ambil username admin dari session untuk ditampilkan di sidebar
// htmlspecialchars() mencegah XSS dengan mengubah karakter < > " menjadi entitas HTML
$namaadmin = htmlspecialchars($_SESSION['username'] ?? 'Admin');
?>

<!-- sidebar utama admin — posisi fixed di kiri layar -->
<aside class="sidebar">

  <!-- bagian atas sidebar: logo aplikasi dan nama admin yang sedang login -->
  <div class="sidebar-logo">
    <div class="namaapp"><i class="fa-solid fa-shield-halved"></i> Admin Panel</div>
    <div class="namaadmin"><?= $namaadmin ?></div>
    <div class="peranadmin">jajankita</div>
  </div>

  <!-- daftar menu navigasi utama admin -->
  <nav class="sidebar-nav">

    <!-- menu dashboard — aktif jika nama halaman adalah 'index' atau 'dashboard' -->
    <a href="../../admin/index/index.php"
       class="sidebar-item <?= in_array($halamansaatini, ['index','dashboard']) ? 'aktif' : '' ?>">
      <i class="fa-solid fa-gauge-high"></i>
      <span>Dashboard</span>
    </a>

    <!-- menu manajemen pengguna — aktif di semua halaman terkait user dan toko -->
    <a href="../../admin/manajemenpengguna/user.php"
       class="sidebar-item <?= in_array($halamansaatini, ['user','viewuser','tambahuser','edituser','hapususer','toko','viewtoko','edittoko','detailtoko']) ? 'aktif' : '' ?>">
      <i class="fa-solid fa-users"></i>
      <span>Manajemen Pengguna</span>
    </a>

    <!-- menu status kantin — tampilkan semua 10 slot kantin (kosong/terisi) -->
    <a href="../../admin/manajementoko/kantin.php"
       class="sidebar-item <?= in_array($halamansaatini, ['kantin']) ? 'aktif' : '' ?>">
      <i class="fa-solid fa-store"></i>
      <span>Status Kantin</span>
    </a>

    <!-- menu laporan platform — aktif hanya jika nama halaman persis 'laporan' -->
    <a href="../../admin/laporan/laporan.php"
       class="sidebar-item <?= $halamansaatini === 'laporan' ? 'aktif' : '' ?>">
      <i class="fa-solid fa-chart-bar"></i>
      <span>Laporan Platform</span>
    </a>

  </nav>

  <!-- bagian bawah sidebar: tombol keluar dari akun -->
  <div class="sidebar-footer">
    <!-- link keluar mengarah ke halaman konfirmasi logout dengan parameter peran=admin -->
    <a href="../../4. autentifikasi/konfirmasilogout.php?peran=admin"
       class="sidebar-item">
      <i class="fa-solid fa-right-from-bracket"></i>
      <span>Keluar</span>
    </a>
  </div>

</aside>
