<?php
/* halaman detail menu
   menampilkan informasi lengkap satu menu: gambar, nama, harga, stok, deskripsi
   pembeli bisa langsung memilih jumlah dan menambahkan ke keranjang dari sini */

// guard memastikan hanya pembeli yang login yang bisa masuk
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

// ambil id menu dari parameter URL — ?id=X
$idmenu = (int)($_GET['id'] ?? 0);
// jika id tidak valid (0), redirect ke beranda
if (!$idmenu) { header("Location: ../index/index.php"); exit; }

// ambil data menu beserta nama tokonya dari database
// LEFT JOIN tb_toko: data menu tetap muncul meski tokonya hilang
// prepared statement dengan placeholder ? lebih aman daripada gabung $idmenu langsung
$q = $conn->prepare("SELECT m.*,t.nama_toko FROM tb_menu m LEFT JOIN tb_toko t ON m.id_toko=t.id_toko WHERE m.id_menu=? AND m.deleted=0");
$q->bind_param("i", $idmenu);
$q->execute();
// fetch_assoc: ambil satu baris hasil sebagai array asosiatif (key = nama kolom)
$menu = $q->get_result()->fetch_assoc();
$q->close();

// jika menu tidak ditemukan (id salah atau sudah dihapus), kembali ke beranda
if (!$menu) { header("Location: ../index/index.php"); exit; }

// menu "tersedia" jika stok ada dan statusnya aktif
// menu yang stoknya habis atau dinonaktifkan tidak bisa dipesan
$tersedia = $menu['stok'] > 0 && $menu['status'] === 'aktif';
$pathbase = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- judul tab browser menggunakan nama menu -->
<title><?= htmlspecialchars($menu['nama_menu']) ?> - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkus" style="padding-bottom:0;">

  <!-- judul halaman dan nama toko -->
  <div style="margin-bottom:16px;">
    <h1 style="font-size:20px;font-weight:800;color:var(--utama);display:flex;align-items:center;gap:8px;">
      <i class="fa-solid fa-bowl-food"></i> Detail Menu
    </h1>
    <p style="font-size:13px;color:var(--tekssamar);margin-top:2px;">
      <i class="fa-solid fa-store"></i> <?= htmlspecialchars($menu['nama_toko'] ?? '') ?>
    </p>
  </div>

  <!-- gambar menu berukuran besar -->
  <div class="gambarbesar">
    <!-- onerror: tampilkan background kosong jika file gambar tidak ditemukan -->
    <img src="../../2. aset/katalog/<?= htmlspecialchars($menu['foto']) ?>"
         alt="<?= htmlspecialchars($menu['nama_menu']) ?>"
         onerror="this.style.background='var(--latar)'">
  </div>

  <!-- badge nama toko di bawah gambar -->
  <?php if ($menu['nama_toko']): ?>
  <div class="lencanatoko">
    <i class="fa-solid fa-store"></i> <?= htmlspecialchars($menu['nama_toko']) ?>
  </div>
  <?php endif; ?>

  <!-- nama menu -->
  <h2 style="font-size:22px;font-weight:800;color:var(--utama);margin-bottom:6px;">
    <?= htmlspecialchars($menu['nama_menu']) ?>
  </h2>

  <!-- info singkat: kategori, stok, dan status tersedia/habis -->
  <div class="infodetail">
    <span><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($menu['kategori']) ?></span>
    <span><i class="fa-solid fa-box"></i> Stok: <?= $menu['stok'] ?></span>
    <?php if ($tersedia): ?>
    <span style="color:var(--sukses);"><i class="fa-solid fa-circle-check"></i> Tersedia</span>
    <?php else: ?>
    <span style="color:var(--gagal);"><i class="fa-solid fa-circle-xmark"></i> Habis</span>
    <?php endif; ?>
  </div>

  <!-- harga menu dalam format ribuan -->
  <div style="font-size:24px;font-weight:800;color:var(--utama);margin-bottom:16px;">
    Rp <?= number_format($menu['harga'],0,',','.') ?>
  </div>

  <!-- deskripsi menu: nl2br mengubah baris baru (\n) menjadi tag <br> -->
  <div class="kotakdeskripsi">
    <h4>Deskripsi</h4>
    <p><?= nl2br(htmlspecialchars($menu['deskripsi'])) ?></p>
  </div>

  <!-- form tambah ke keranjang — hanya ditampilkan jika menu tersedia -->
  <?php if ($tersedia): ?>
  <form method="POST" action="../keranjang/proseskeranjang.php">
    <input type="hidden" name="aksi" value="tambah">
    <input type="hidden" name="id_menu" value="<?= $menu['id_menu'] ?>">
    <!-- kembali=keranjang artinya setelah proses, redirect ke halaman keranjang -->
    <input type="hidden" name="kembali" value="keranjang">

    <div class="kelompokform">
      <label>Jumlah</label>
      <div style="display:flex;align-items:center;gap:12px;">
        <!-- input angka qty: min 1, max sesuai stok yang tersedia -->
        <input type="number" name="qty" value="1" min="1" max="<?= $menu['stok'] ?>"
               style="width:90px;" required>
        <span style="font-size:13px;color:var(--tekssamar);">Maks. <?= $menu['stok'] ?> porsi</span>
      </div>
    </div>

    <div style="display:flex;gap:10px;margin-top:8px;">
      <!-- tombol batal: kembali ke beranda -->
      <a href="../index/index.php" class="tombolringan" style="padding:10px 14px;font-size:13px;">
        Batal
      </a>
      <!-- tombol submit: tambahkan ke keranjang -->
      <button type="submit" class="tombolutama" style="flex:2;padding:14px;">
        <i class="fa-solid fa-cart-plus"></i> Tambah ke Keranjang
      </button>
    </div>
  </form>

  <?php else: ?>
  <!-- tampilkan pesan jika menu tidak tersedia -->
  <div class="peringatan peringatangagal">
    <i class="fa-solid fa-circle-xmark"></i> Menu ini sedang tidak tersedia
  </div>
  <a href="../index/index.php" class="tombolringan blok" style="margin-top:12px;">
    Kembali ke Menu
  </a>
  <?php endif; ?>

  <!-- ruang kosong di bawah agar konten tidak tertutup navbar bawah -->
  <div class="ruangbawah"></div>
</div>
</body>
</html>
