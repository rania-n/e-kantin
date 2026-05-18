<?php
/* ============================================================
   HALAMAN PROFIL
   ============================================================ */
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

$idpengguna = (int)$_SESSION['id_user'];
$inisial    = strtoupper(mb_substr($_SESSION['username'] ?? 'U', 0, 2));

// Ambil data user
$q = $conn->prepare("SELECT * FROM tb_user WHERE id_user=? AND deleted=0");
$q->bind_param("i", $idpengguna);
$q->execute();
$user = $q->get_result()->fetch_assoc();
$q->close();
if (!$user) { header("Location: ../../4. autentifikasi/logout.php"); exit; }

// Statistik
$s1 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND deleted=0");
$s1->bind_param("i",$idpengguna); $s1->execute();
$totalpesanan = (int)$s1->get_result()->fetch_row()[0]; $s1->close();

$s2 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND status_order='Selesai' AND deleted=0");
$s2->bind_param("i",$idpengguna); $s2->execute();
$totalselesai = (int)$s2->get_result()->fetch_row()[0]; $s2->close();

$s3 = $conn->prepare("SELECT COUNT(*) FROM tb_rating WHERE id_user=? AND deleted=0");
$s3->bind_param("i",$idpengguna); $s3->execute();
$totalrating = (int)$s3->get_result()->fetch_row()[0]; $s3->close();

$s4 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_user=? AND status_order='Selesai' AND deleted=0");
$s4->bind_param("i",$idpengguna); $s4->execute();
$totalbelanja = (int)$s4->get_result()->fetch_row()[0]; $s4->close();

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
<title>Profil - eKantin</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkus">

  <!-- Hero profil -->
  <div class="heroprofil">
    <div class="avatar"><?= $inisial ?></div>
    <div class="namapengguna"><?= htmlspecialchars($user['username']) ?></div>
    <div class="emailpengguna"><?= htmlspecialchars($user['email']) ?></div>
    <span class="labelperan"><i class="fa-solid fa-user"></i> Pembeli</span>
  </div>

  <!-- Statistik -->
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
      <div class="angkastat"><?= $totalrating ?></div>
      <div class="labelstat">Ulasan</div>
    </div>
    <div class="kotakstat">
      <div class="angkastat" style="font-size:13px;"><?= $formatbelanja ?></div>
      <div class="labelstat">Belanja</div>
    </div>
  </div>

  <!-- Menu akun -->
  <div class="judulbagian"><i class="fa-solid fa-user-gear"></i> Akun Saya</div>

  <a href="editprofil.php" class="itempengaturan">
    <div class="ikonpengaturan"><i class="fa-solid fa-user-pen"></i></div>
    <div class="tekspengaturan">
      <div class="judul">Edit Profil</div>
      <div class="deskripsi">Ubah username dan email</div>
    </div>
    <div class="panahpengaturan"><i class="fa-solid fa-chevron-right"></i></div>
  </a>

  <a href="gantipassword.php" class="itempengaturan">
    <div class="ikonpengaturan ungu"><i class="fa-solid fa-lock"></i></div>
    <div class="tekspengaturan">
      <div class="judul">Ganti Password</div>
      <div class="deskripsi">Ubah kata sandi akun</div>
    </div>
    <div class="panahpengaturan"><i class="fa-solid fa-chevron-right"></i></div>
  </a>

  <a href="../pesanan/pesanan.php?tab=riwayat" class="itempengaturan">
    <div class="ikonpengaturan hijau"><i class="fa-solid fa-clock-rotate-left"></i></div>
    <div class="tekspengaturan">
      <div class="judul">Riwayat Pesanan</div>
      <div class="deskripsi"><?= $totalpesanan ?> pesanan</div>
    </div>
    <div class="panahpengaturan"><i class="fa-solid fa-chevron-right"></i></div>
  </a>

  <div class="judulbagian" style="margin-top:20px;"><i class="fa-solid fa-ellipsis"></i> Lainnya</div>

  <div class="itempengaturan">
    <div class="ikonpengaturan"><i class="fa-solid fa-circle-info"></i></div>
    <div class="tekspengaturan">
      <div class="judul">Tentang Aplikasi</div>
      <div class="deskripsi">eKantin v1.0</div>
    </div>
    <div class="panahpengaturan"><i class="fa-solid fa-chevron-right"></i></div>
  </div>

  <!-- Tombol keluar (mobile only, desktop ada di sidebar) -->
  <div class="itempengaturan sembunyidesktop" onclick="document.getElementById('modalkeluar').classList.add('tampil')" style="cursor:pointer;">
    <div class="ikonpengaturan merah"><i class="fa-solid fa-right-from-bracket"></i></div>
    <div class="tekspengaturan">
      <div class="judul" style="color:var(--gagal);">Keluar</div>
      <div class="deskripsi">Logout dari akun</div>
    </div>
    <div class="panahpengaturan" style="color:var(--gagal);"><i class="fa-solid fa-chevron-right"></i></div>
  </div>

  <div style="height:24px;"></div>

</div>

<!-- Modal keluar -->
<div class="modaloverlay" id="modalkeluar" onclick="this.classList.remove('tampil')">
  <div class="isimodal" onclick="event.stopPropagation()">
    <div class="peganganmodal"></div>
    <div style="text-align:center;padding:10px 0 20px;">
      <div style="font-size:48px;margin-bottom:10px;color:var(--utama);">
        <i class="fa-solid fa-right-from-bracket"></i>
      </div>
      <div style="font-size:18px;font-weight:800;color:var(--utama);margin-bottom:6px;">Yakin ingin keluar?</div>
      <div style="font-size:13px;color:var(--tekssamar);">Kamu perlu login lagi untuk mengakses akun</div>
    </div>
    <a href="../../4. autentifikasi/logout.php"
       class="tombolutama blok" style="margin-bottom:10px;">
      <i class="fa-solid fa-right-from-bracket"></i> Ya, Keluar
    </a>
    <button class="tombolringan blok" onclick="document.getElementById('modalkeluar').classList.remove('tampil')">
      Batal
    </button>
  </div>
</div>

</body>
</html>
