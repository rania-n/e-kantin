<?php
/* halaman profil pembeli
   menampilkan informasi akun, statistik belanja,
   dan menu navigasi ke edit profil, ganti password, dan logout */

// guard memastikan hanya pembeli yang login yang bisa mengakses
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

// ambil id pengguna dari session
$idpengguna = (int)$_SESSION['id_user'];
// ambil 2 huruf pertama username sebagai inisial avatar
// mb_substr dipakai agar mendukung karakter unicode (huruf Indonesia)
$inisial    = strtoupper(mb_substr($_SESSION['username'] ?? 'U', 0, 2));

// ambil data lengkap user dari database untuk memastikan data terbaru
$q = $conn->prepare("SELECT * FROM tb_user WHERE id_user=? AND deleted=0");
$q->bind_param("i", $idpengguna);
$q->execute();
$user = $q->get_result()->fetch_assoc();
$q->close();
// jika user tidak ditemukan (mungkin sudah dihapus admin), paksa logout
if (!$user) { header("Location: ../../4. autentifikasi/logout.php"); exit; }

/* ambil statistik aktivitas pembeli:
   5 query dijalankan secara terpisah untuk memudahkan pembacaan */

// hitung total pesanan yang pernah dibuat (tidak termasuk yang dibatalkan)
$s1 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND deleted=0 AND status_order NOT IN ('Dibatalkan')");
$s1->bind_param("i",$idpengguna); $s1->execute();
$totalpesanan = (int)$s1->get_result()->fetch_row()[0]; $s1->close();

// hitung pesanan yang sudah berstatus Selesai
$s2 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND status_order='Selesai' AND deleted=0");
$s2->bind_param("i",$idpengguna); $s2->execute();
$totalselesai = (int)$s2->get_result()->fetch_row()[0]; $s2->close();

// hitung total rating/ulasan yang pernah diberikan
$s3 = $conn->prepare("SELECT COUNT(*) FROM tb_rating WHERE id_user=? AND deleted=0");
$s3->bind_param("i",$idpengguna); $s3->execute();
$totalrating = (int)$s3->get_result()->fetch_row()[0]; $s3->close();

// hitung total uang yang sudah dibelanjakan (hanya pesanan yang selesai)
// COALESCE mengembalikan 0 jika tidak ada data (menghindari null)
$s4 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_user=? AND status_order='Selesai' AND deleted=0");
$s4->bind_param("i",$idpengguna); $s4->execute();
$totalbelanja = (int)$s4->get_result()->fetch_row()[0]; $s4->close();

// hitung total item/porsi yang sudah dibeli dari semua pesanan selesai
// JOIN dengan detail_order karena jumlah item ada di tabel terpisah
$s5 = $conn->prepare("SELECT COALESCE(SUM(d.jumlah),0) FROM tb_detail_order d JOIN tb_order o ON d.id_order=o.id_order WHERE o.id_user=? AND o.status_order='Selesai' AND o.deleted=0 AND d.deleted=0");
$s5->bind_param("i",$idpengguna); $s5->execute();
$totalitem = (int)$s5->get_result()->fetch_row()[0]; $s5->close();

// format total belanja: jika >= 1 juta tampilkan dalam "jt", jika tidak dalam "rb"
$formatbelanja = $totalbelanja >= 1000000
    ? 'Rp ' . number_format($totalbelanja/1000000, 1) . 'jt'
    : 'Rp ' . number_format($totalbelanja/1000, 0) . 'rb';

$pathbase = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkus">

  <div class="judulhalaman">
    <h1><i class="fa-solid fa-user"></i> Profil Saya</h1>
    <p>Kelola akun dan lihat aktivitas belanjamu</p>
  </div>

  <!-- bagian hero: avatar inisial, nama, email, dan label peran -->
  <div class="heroprofil">
    <!-- avatar menggunakan inisial 2 huruf dari username -->
    <div class="avatar"><?= $inisial ?></div>
    <div class="namapengguna"><?= htmlspecialchars($user['username']) ?></div>
    <div class="emailpengguna"><?= htmlspecialchars($user['email']) ?></div>
    <span class="labelperan"><i class="fa-solid fa-user"></i> Pembeli</span>
  </div>

  <!-- grid statistik aktivitas pembeli -->
  <div class="gridstat" style="margin-bottom:20px;">
    <div class="kotakstat">
      <div class="angkastat"><?= $totalpesanan ?></div>
      <div class="labelstat">Pesanan</div>
    </div>
    <div class="kotakstat">
      <div class="angkastat"><?= $totalselesai ?></div>
      <div class="labelstat">Selesai</div>
    </div>
    <div class="kotakstat">
      <div class="angkastat"><?= $totalitem ?></div>
      <div class="labelstat">Item Dibeli</div>
    </div>
    <div class="kotakstat">
      <div class="angkastat"><?= $totalrating ?></div>
      <div class="labelstat">Ulasan</div>
    </div>
    <!-- format total belanja disingkat agar tidak overflow di layar kecil -->
    <div class="kotakstat">
      <div class="angkastat" style="font-size:13px;"><?= $formatbelanja ?></div>
      <div class="labelstat">Belanja</div>
    </div>
  </div>

  <!-- menu pengaturan akun -->
  <div class="judulbagian"><i class="fa-solid fa-user-gear"></i> Akun Saya</div>

  <!-- link ke halaman edit profil -->
  <a href="editprofil.php" class="itempengaturan">
    <div class="ikonpengaturan"><i class="fa-solid fa-user-pen"></i></div>
    <div class="tekspengaturan">
      <div class="judul">Edit Profil</div>
      <div class="deskripsi">Ubah username dan email</div>
    </div>
    <div class="panahpengaturan"><i class="fa-solid fa-chevron-right"></i></div>
  </a>

  <!-- link ke halaman ganti password -->
  <a href="gantipassword.php" class="itempengaturan">
    <div class="ikonpengaturan ungu"><i class="fa-solid fa-lock"></i></div>
    <div class="tekspengaturan">
      <div class="judul">Ganti Password</div>
      <div class="deskripsi">Ubah kata sandi akun</div>
    </div>
    <div class="panahpengaturan"><i class="fa-solid fa-chevron-right"></i></div>
  </a>

  <div class="judulbagian" style="margin-top:20px;"><i class="fa-solid fa-ellipsis"></i> Lainnya</div>

  <!-- informasi tentang aplikasi (tidak ada link, hanya informasi statis) -->
  <div class="itempengaturan">
    <div class="ikonpengaturan"><i class="fa-solid fa-circle-info"></i></div>
    <div class="tekspengaturan">
      <div class="judul">Tentang Website</div>
      <div class="deskripsi">jajankita v1.0</div>
    </div>
  </div>

  <!-- tombol hubungi admin: muncul di mobile (desktop: ada di sidebar navbar)
       #modal-kontak-pembeli adalah id modal yang dibuka dengan CSS :target -->
  <a href="#modal-kontak-pembeli" class="itempengaturan sembunyidesktop">
    <div class="ikonpengaturan"><i class="fa-solid fa-headset"></i></div>
    <div class="tekspengaturan">
      <div class="judul">Hubungi Admin</div>
      <div class="deskripsi">Bantuan &amp; informasi</div>
    </div>
    <div class="panahpengaturan"><i class="fa-solid fa-chevron-right"></i></div>
  </a>

  <!-- tombol keluar: hanya muncul di mobile (desktop: tombol logout ada di sidebar)
       sembunyidesktop = class CSS yang display:none di layar lebar -->
  <a href="../../4. autentifikasi/konfirmasilogout.php?peran=pembeli"
     class="itempengaturan sembunyidesktop" style="color:inherit;">
    <div class="ikonpengaturan merah"><i class="fa-solid fa-right-from-bracket"></i></div>
    <div class="tekspengaturan">
      <div class="judul" style="color:var(--gagal);">Keluar</div>
      <div class="deskripsi">Logout dari akun</div>
    </div>
    <div class="panahpengaturan" style="color:var(--gagal);"><i class="fa-solid fa-chevron-right"></i></div>
  </a>

  <div style="height:24px;"></div>

</div>

</body>
</html>
