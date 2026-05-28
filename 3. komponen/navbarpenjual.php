<?php
/*
   sidebar penjual — komponen navigasi untuk halaman-halaman penjual.
   di desktop ditampilkan sebagai sidebar kiri yang bisa dilipat/dibuka.
   di mobile ditampilkan sebagai bottom tab bar di bagian bawah layar.

   cara toggle sidebar menggunakan css checkbox hack: tidak perlu javascript.
   ketika label diklik, checkbox berubah state (checked/unchecked), lalu css
   membaca state checkbox tersebut dengan selector :checked untuk menampilkan
   atau menyembunyikan sidebar.

   modal kontak admin menggunakan css :target: ketika link #modal-kontak diklik,
   browser menambahkan id tersebut ke url, dan css menampilkan elemen dengan id itu.
*/

// jika $halamansaatini belum didefinisikan oleh halaman pemanggil, tentukan sendiri
// ini penting agar kode tidak error saat file di-include dari halaman mana saja
if (!isset($halamansaatini)) {
    $halamansaatini = basename($_SERVER['PHP_SELF'], '.php');
}

// ambil data penjual dari session untuk ditampilkan di sidebar
// htmlspecialchars() melindungi dari XSS jika nama toko mengandung karakter khusus
$namatoko       = htmlspecialchars($_SESSION['nama_toko']   ?? 'Toko Saya');
$statustoko     = $_SESSION['status_toko'] ?? 'buka'; // status toko: 'buka' atau 'tutup'

// buat inisial penjual dari dua huruf pertama username, diubah ke huruf kapital
// mb_substr dipakai agar aman untuk karakter unicode/multibyte seperti huruf beraksara
$inisialpenjual = strtoupper(mb_substr($_SESSION['username'] ?? 'P', 0, 2));
?>

<!-- checkbox tersembunyi sebagai mekanisme toggle sidebar (css checkbox hack)
     saat label yang terhubung diklik, css akan membaca apakah checkbox ini checked atau tidak -->
<input type="checkbox" id="togel-sidebar" class="togel-input">

<!-- header yang hanya muncul di layar mobile (di atas konten) -->
<div class="mobile-header">
  <!-- label ini terhubung ke checkbox togel-sidebar via atribut for
       ketika diklik akan mengubah state checkbox dan memicu perubahan tampilan sidebar -->
  <label for="togel-sidebar" class="tombolhambur" title="Menu">
    <i class="fa-solid fa-bars"></i>
  </label>
  <!-- judul di tengah header mobile, menampilkan nama toko penjual.
       logo PNG via mask (lihat .logo-app di penjual.css) -->
  <div class="judul">
    <span class="logo-app"></span> jajankita — <?= $namatoko ?>
  </div>
</div>

<!-- overlay gelap di belakang sidebar saat mobile — label ini menutup sidebar saat diklik
     cara kerjanya sama: mengubah state checkbox sehingga css menyembunyikan sidebar -->
<label for="togel-sidebar" class="overlay-sidebar"></label>

<!-- sidebar utama penjual -->
<aside class="sidebar" id="sidebar">

  <!-- bagian atas sidebar: nama aplikasi, nama toko, dan status toko.
       logo besar (24px) supaya seimbang dengan teks "jajankita" bold 18px -->
  <div class="sidebar-logo">
    <div class="namaapp">
      <span class="logo-app besar"></span> jajankita
    </div>
    <div class="namatoko"><?= $namatoko ?></div>
    <!-- tampilkan status toko dengan warna berbeda: hijau jika buka, merah jika tutup
         class css 'buka' atau 'tutup' menentukan warna yang ditampilkan -->
    <div class="statustoko <?= $statustoko === 'buka' ? 'buka' : 'tutup' ?>">
      <i class="fa-solid fa-circle"></i>
      <?= $statustoko === 'buka' ? 'Toko Buka' : 'Toko Tutup' ?>
    </div>
  </div>

  <!-- daftar menu navigasi utama penjual -->
  <nav class="sidebar-nav">

    <!-- menu dashboard — aktif jika nama halaman adalah 'index' atau 'dashboard' -->
    <a href="../../penjual/index/index.php"
       class="sidebar-item <?= in_array($halamansaatini, ['index','dashboard']) ? 'aktif' : '' ?>">
      <i class="fa-solid fa-gauge-high"></i>
      <span>Dashboard</span>
    </a>

    <!-- menu pesanan masuk — aktif hanya di halaman manajemenpesanan -->
    <a href="../../penjual/manajemenpesanan/manajemenpesanan.php"
       class="sidebar-item <?= $halamansaatini === 'manajemenpesanan' ? 'aktif' : '' ?>">
      <i class="fa-solid fa-clipboard-list"></i>
      <span>Pesanan Masuk</span>
    </a>

    <!-- menu kelola menu makanan/minuman milik toko -->
    <a href="../../penjual/manajemenmenu/manajemenmenu.php"
       class="sidebar-item <?= $halamansaatini === 'manajemenmenu' ? 'aktif' : '' ?>">
      <i class="fa-solid fa-bowl-food"></i>
      <span>Kelola Menu</span>
    </a>

    <!-- menu laporan penjualan dalam bentuk ringkasan/grafik -->
    <a href="../../penjual/laporan/laporan.php"
       class="sidebar-item <?= $halamansaatini === 'laporan' ? 'aktif' : '' ?>">
      <i class="fa-solid fa-chart-bar"></i>
      <span>Laporan Penjualan</span>
    </a>

    <!-- menu melihat semua ulasan dari pembeli terhadap menu di toko ini -->
    <a href="../../penjual/ulasan/ulasan.php"
       class="sidebar-item <?= $halamansaatini === 'ulasan' ? 'aktif' : '' ?>">
      <i class="fa-solid fa-star"></i>
      <span>Semua Ulasan</span>
    </a>

    <!-- garis pemisah antara menu utama dan menu profil/tambahan -->
    <div class="sidebar-divider"></div>

    <!-- menu profil dan pengaturan toko — aktif di semua sub-halaman profil -->
    <a href="../../penjual/profil/profil.php"
       class="sidebar-item <?= in_array($halamansaatini, ['profil','editprofil','gantipassword']) ? 'aktif' : '' ?>">
      <i class="fa-solid fa-user-tie"></i>
      <span>Profil &amp; Toko</span>
    </a>

    <!-- link untuk membuka modal kontak admin menggunakan css :target
         href="#modal-kontak" mengubah fragment url sehingga elemen #modal-kontak menjadi :target
         dan css akan menampilkannya tanpa javascript -->
    <a href="#modal-kontak" class="sidebar-item">
      <i class="fa-solid fa-headset"></i>
      <span>Hubungi Admin</span>
    </a>

  </nav>

  <!-- bagian bawah sidebar: tombol keluar dari akun -->
  <div class="sidebar-footer">
    <!-- parameter peran=penjual memberitahu halaman konfirmasi logout session mana yang perlu dihapus -->
    <a href="../../4. autentifikasi/konfirmasilogout.php?peran=penjual"
       class="sidebar-item">
      <i class="fa-solid fa-right-from-bracket"></i>
      <span>Keluar</span>
    </a>
  </div>

</aside>

<!-- bottom navigation bar — hanya muncul di layar mobile sebagai pengganti sidebar
     berisi menu-menu utama yang paling sering diakses penjual -->
<nav class="bottomnav-penjual">
  <a href="../../penjual/index/index.php"
     class="tab-penjual <?= in_array($halamansaatini,['index','dashboard'])?'aktif':'' ?>">
    <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
  </a>
  <a href="../../penjual/manajemenpesanan/manajemenpesanan.php"
     class="tab-penjual <?= $halamansaatini==='manajemenpesanan'?'aktif':'' ?>">
    <i class="fa-solid fa-clipboard-list"></i><span>Pesanan Masuk</span>
  </a>
  <a href="../../penjual/manajemenmenu/manajemenmenu.php"
     class="tab-penjual <?= $halamansaatini==='manajemenmenu'?'aktif':'' ?>">
    <i class="fa-solid fa-bowl-food"></i><span>Kelola Menu</span>
  </a>
  <a href="../../penjual/laporan/laporan.php"
     class="tab-penjual <?= $halamansaatini==='laporan'?'aktif':'' ?>">
    <i class="fa-solid fa-chart-bar"></i><span>Laporan</span>
  </a>
  <a href="../../penjual/profil/profil.php"
     class="tab-penjual <?= in_array($halamansaatini,['profil','editprofil','gantipassword'])?'aktif':'' ?>">
    <i class="fa-solid fa-user-tie"></i><span>Profil</span>
  </a>
</nav>

<?php
// baca teks pengumuman khusus penjual dari file teks eksternal
// file ini terpisah dari pengumuman pembeli agar admin bisa membedakan pesannya
$_filePengumumanPenjual = __DIR__ . '/tekspengumumanpenjual.txt';

// jika file ada, baca dan bersihkan spasi di tepi — jika tidak ada, isi string kosong
$teks_pengumuman_penjual = file_exists($_filePengumumanPenjual) ? trim(file_get_contents($_filePengumumanPenjual)) : '';

// hanya tampilkan banner pengumuman jika teks tidak kosong
if (!empty($teks_pengumuman_penjual)):
?>
<!-- banner pengumuman sistem untuk penjual — class 'takprint' agar tidak ikut tercetak saat print struk -->
<div class="notif-pengumuman-penjual takprint">
  <i class="fa-solid fa-bullhorn"></i>
  <?= htmlspecialchars($teks_pengumuman_penjual) ?>
</div>
<?php endif; ?>

<!-- modal kontak admin — ditampilkan saat url mengandung fragment #modal-kontak
     cara kerja: css mendeteksi :target (elemen yang id-nya cocok dengan fragment url)
     dan mengubah display dari none menjadi flex/block -->
<div class="modaloverlay" id="modal-kontak">
  <!-- link ini menutup modal dengan mengganti fragment url kembali ke '#' (kosong) -->
  <a href="#" class="penutup-modal"></a>
  <div class="isimodal" style="max-width:360px;position:relative;z-index:1;">
    <div style="text-align:center;margin-bottom:18px;">
      <div style="font-size:40px;color:var(--kedua);margin-bottom:8px;">
        <i class="fa-solid fa-headset"></i>
      </div>
      <div style="font-size:17px;font-weight:800;color:var(--utama);margin-bottom:4px;">Hubungi Admin</div>
      <div style="font-size:13px;color:var(--tekssamar);">
        Butuh bantuan? Hubungi admin jajankita melalui:
      </div>
    </div>
    <!-- dua tombol kontak: email dan whatsapp -->
    <div style="display:flex;flex-direction:column;gap:10px;">
      <a href="mailto:ranianuril210@gmail.com" class="tombolutama blok">
        <i class="fa-solid fa-envelope"></i> Email Admin
      </a>
      <a href="https://wa.me/6285648830046" target="_blank" class="tombolkedua blok">
        <i class="fa-brands fa-whatsapp"></i> WhatsApp Admin
      </a>
      <!-- tombol tutup — mengarahkan ke '#' untuk menghapus :target dari url -->
      <a href="#" class="tombolringan blok">
        <i class="fa-solid fa-xmark"></i> Tutup
      </a>
    </div>
  </div>
</div>
