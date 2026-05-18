<?php
/* ============================================================
   HALAMAN KERANJANG
   ============================================================ */
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

$keranjang  = $_SESSION['keranjang'] ?? [];
$totalsemua = 0;
foreach ($keranjang as $idt => $items) {
    foreach ($items as $k => $v) {
        if ($k === '_info') continue;
        $totalsemua += $v['harga'] * $v['qty'];
    }
}

$pathbase = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Keranjang - eKantin</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkus">

  <div class="judulhalaman">
    <h1><i class="fa-solid fa-cart-shopping"></i> Keranjang</h1>
    <p>Checkout dilakukan per kantin</p>
  </div>

  <?php if (empty($keranjang)): ?>
  <div class="kosong">
    <div class="ikonkosong"><i class="fa-solid fa-cart-shopping"></i></div>
    <h3>Keranjang masih kosong</h3>
    <p>Pilih menu dulu dari halaman beranda</p>
    <a href="../index/index.php" class="tombolutama">
      <i class="fa-solid fa-utensils"></i> Lihat Menu
    </a>
  </div>

  <?php else: ?>

  <div class="peringatan peringataninfo" style="margin-bottom:16px;">
    <i class="fa-solid fa-circle-info"></i>
    Tekan tombol checkout di setiap kantin untuk melanjutkan
  </div>

  <?php foreach ($keranjang as $idtoko => $itemtoko):
    $namatoko    = $itemtoko['_info']['nama_toko'] ?? 'Kantin';
    $subtokototal = 0;
    foreach ($itemtoko as $k => $v) {
        if ($k === '_info') continue;
        $subtokototal += $v['harga'] * $v['qty'];
    }
  ?>

  <div style="margin-bottom:24px;">

    <!-- Header toko -->
    <div class="headertoko">
      <i class="fa-solid fa-store" style="color:var(--kedua);font-size:16px;"></i>
      <span class="namamenu"><?= htmlspecialchars($namatoko) ?></span>
      <span class="subtotaltoko">Rp <?= number_format($subtokototal,0,',','.') ?></span>
    </div>

    <!-- Item toko -->
    <?php foreach ($itemtoko as $idmenu => $isi):
      if ($idmenu === '_info') continue;
    ?>
    <div class="kartukeranjang">
      <img class="gambarmenu"
           src="../../2. aset/katalog/<?= htmlspecialchars($isi['foto']) ?>"
           alt="<?= htmlspecialchars($isi['nama_menu']) ?>"
           onerror="this.style.background='var(--latar)'">
      <div class="isikeranjang">
        <div class="ataskeranjang">
          <div>
            <div class="namamenu"><?= htmlspecialchars($isi['nama_menu']) ?></div>
            <div class="hargasatu">Rp <?= number_format($isi['harga'],0,',','.') ?> / porsi</div>
          </div>
          <!-- Tombol hapus -->
          <form method="POST" action="proseskeranjang.php">
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id_toko" value="<?= $idtoko ?>">
            <input type="hidden" name="id_menu" value="<?= $idmenu ?>">
            <button type="submit" class="tombolhapus" title="Hapus" onclick="return confirm('Hapus item ini?')">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          </form>
        </div>

        <?php if (!empty($isi['catatan'])): ?>
        <div style="font-size:11px;color:var(--tekssamar);">
          <i class="fa-solid fa-note-sticky"></i> <?= htmlspecialchars($isi['catatan']) ?>
        </div>
        <?php endif; ?>

        <div class="tengahkeranjang">
          <!-- Kontrol qty: pakai form button -->
          <div class="kontrolqty">
            <form method="POST" action="proseskeranjang.php" style="display:contents;">
              <input type="hidden" name="aksi" value="kurang">
              <input type="hidden" name="id_toko" value="<?= $idtoko ?>">
              <input type="hidden" name="id_menu" value="<?= $idmenu ?>">
              <button type="submit" class="tombolqty">-</button>
            </form>
            <span class="angkaqty"><?= $isi['qty'] ?></span>
            <form method="POST" action="proseskeranjang.php" style="display:contents;">
              <input type="hidden" name="aksi" value="tambah_qty">
              <input type="hidden" name="id_toko" value="<?= $idtoko ?>">
              <input type="hidden" name="id_menu" value="<?= $idmenu ?>">
              <button type="submit" class="tombolqty">+</button>
            </form>
          </div>
          <div class="subtotalitem">Rp <?= number_format($isi['harga']*$isi['qty'],0,',','.') ?></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- Tombol checkout per toko -->
    <a href="../pesanan/checkout.php?toko=<?= $idtoko ?>" class="tombolutama blok" style="margin-top:10px;">
      <i class="fa-solid fa-bag-shopping"></i>
      Checkout <?= htmlspecialchars($namatoko) ?> &mdash; Rp <?= number_format($subtokototal,0,',','.') ?>
    </a>

  </div>
  <?php endforeach; ?>

  <!-- Ringkasan total semua -->
  <div class="ringkasan">
    <div class="judulbagian" style="margin:0 0 10px;"><i class="fa-solid fa-receipt"></i> Total Semua Kantin</div>
    <div class="barisringkasan total">
      <span>Total Belanja</span>
      <b>Rp <?= number_format($totalsemua,0,',','.') ?></b>
    </div>
  </div>

  <?php endif; ?>

</div>
</body>
</html>
