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

<!-- overlay blokir mobile — muncul di layar <900px (lihat admin.css .blokirmobile).
     panel admin tidak didukung di hp/tablet kecil karena tabel & sidebar tidak
     muat. overlay ini menutup seluruh halaman supaya admin tidak bisa dipakai
     dari layar kecil — bukan blokir keamanan, hanya blokir tampilan. -->
<div class="blokirmobile">
  <div class="ikon"><i class="fa-solid fa-desktop"></i></div>
  <h2>Panel Admin Hanya untuk Desktop</h2>
  <p>
    Buka halaman ini dari laptop atau komputer dengan layar minimal 900px.
    Tampilan mobile tidak didukung untuk panel admin.
  </p>
  <!-- tombol jalan keluar — kembalikan user mobile ke landing page utama.
       semua halaman admin berada 2 level di bawah root, jadi ../../index.php
       selalu menuju ke file landing root (C:/.../E-Kantin/index.php). -->
  <a href="../../index.php" class="tombol-kembali">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
  </a>
  <div class="info">
    jajankita &mdash; Admin Panel
  </div>
</div>

<!-- sidebar utama admin — posisi fixed di kiri layar -->
<aside class="sidebar">

  <!-- bagian atas sidebar: logo aplikasi dan nama admin yang sedang login.
       pakai logo kantin (.logo-app) supaya konsisten dengan halaman pembeli & penjual.
       teks "Admin Panel" yang membedakan konteks, bukan ikonnya. -->
  <div class="sidebar-logo">
    <div class="namaapp"><span class="logo-app besar"></span> Admin Panel</div>
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
