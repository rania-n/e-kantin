<?php
/* ============================================================
   DETAIL MENU
   ============================================================ */
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

$idmenu = (int)($_GET['id'] ?? 0);
if (!$idmenu) { header("Location: ../index/index.php"); exit; }

$q = $conn->prepare("SELECT m.*,t.nama_toko FROM tb_menu m LEFT JOIN tb_toko t ON m.id_toko=t.id_toko WHERE m.id_menu=? AND m.deleted=0");
$q->bind_param("i", $idmenu);
$q->execute();
$menu = $q->get_result()->fetch_assoc();
$q->close();

if (!$menu) { header("Location: ../index/index.php"); exit; }

$tersedia = $menu['stok'] > 0 && $menu['status'] === 'aktif';
$pathbase = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($menu['nama_menu']) ?> - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkus" style="padding-bottom:0;">

  <a href="../index/index.php" class="tombolkembali" style="display:inline-flex;align-items:center;gap:22px;color:var(--utama);font-weight:600;font-size:14px;padding:10px 0;margin-bottom:10px;">
    <i class="fa-solid fa-arrow-left" style="margin-left:82px;"></i>Kembali
  </a>
  

  <!-- Gambar -->
  <div class="gambarbesar">
    <img src="../../2. aset/katalog/<?= htmlspecialchars($menu['foto']) ?>"
         alt="<?= htmlspecialchars($menu['nama_menu']) ?>"
         onerror="this.style.background='var(--latar)'">
  </div>

  <!-- Badge toko -->
  <?php if ($menu['nama_toko']): ?>
  <div class="lencanatoko">
    <i class="fa-solid fa-store"></i> <?= htmlspecialchars($menu['nama_toko']) ?>
  </div>
  <?php endif; ?>

  <!-- Nama & harga -->
  <h2 style="font-size:22px;font-weight:800;color:var(--utama);margin-bottom:6px;">
    <?= htmlspecialchars($menu['nama_menu']) ?>
  </h2>

  <!-- Info -->
  <div class="infodetail">
    <span><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($menu['kategori']) ?></span>
    <span><i class="fa-solid fa-box"></i> Stok: <?= $menu['stok'] ?></span>
    <?php if ($tersedia): ?>
    <span style="color:var(--sukses);"><i class="fa-solid fa-circle-check"></i> Tersedia</span>
    <?php else: ?>
    <span style="color:var(--gagal);"><i class="fa-solid fa-circle-xmark"></i> Habis</span>
    <?php endif; ?>
  </div>

  <div style="font-size:24px;font-weight:800;color:var(--utama);margin-bottom:16px;">
    Rp <?= number_format($menu['harga'],0,',','.') ?>
  </div>

  <!-- Deskripsi -->
  <div class="kotakdeskripsi">
    <h4>Deskripsi</h4>
    <p><?= nl2br(htmlspecialchars($menu['deskripsi'])) ?></p>
  </div>

  <?php if ($tersedia): ?>
  <!-- Form tambah ke keranjang -->
  <form method="POST" action="../keranjang/proseskeranjang.php">
    <input type="hidden" name="aksi" value="tambah">
    <input type="hidden" name="id_menu" value="<?= $menu['id_menu'] ?>">
    <input type="hidden" name="kembali" value="keranjang">

    <div class="kelompokform">
      <label>Jumlah</label>
      <div style="display:flex;align-items:center;gap:12px;">
        <input type="number" name="qty" value="1" min="1" max="<?= $menu['stok'] ?>"
               style="width:90px;" required>
        <span style="font-size:13px;color:var(--tekssamar);">Maks. <?= $menu['stok'] ?> porsi</span>
      </div>
    </div>

    <button type="submit" class="tombolutama blok" style="margin-top:8px;padding:14px;">
      <i class="fa-solid fa-cart-plus"></i>
      Tambah ke Keranjang &mdash; Rp <?= number_format($menu['harga'],0,',','.') ?>
    </button>
  </form>

  <?php else: ?>
  <div class="peringatan peringatangagal">
    <i class="fa-solid fa-circle-xmark"></i> Menu ini sedang tidak tersedia
  </div>
  <?php endif; ?>

  <div class="ruangbawah"></div>
</div>
</body>
</html>
