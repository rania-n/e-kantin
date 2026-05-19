<?php
/* ============================================================
   HALAMAN CHECKOUT
   ============================================================ */
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

$idtoko    = (int)($_GET['toko'] ?? 0);
$keranjang = $_SESSION['keranjang'] ?? [];

if (!$idtoko || !isset($keranjang[$idtoko])) {
    header("Location: ../keranjang/keranjang.php"); exit;
}

$itemtoko   = $keranjang[$idtoko];
$namatoko   = $itemtoko['_info']['nama_toko'] ?? 'Kantin';
$daftaritem = [];
$subtotal   = 0;

foreach ($itemtoko as $k => $v) {
    if ($k === '_info') continue;
    $subtotal    += $v['harga'] * $v['qty'];
    $daftaritem[] = $v;
}

if (empty($daftaritem)) { header("Location: ../keranjang/keranjang.php"); exit; }

$biayalayanan = 1000;
$totalbayar   = $subtotal + $biayalayanan;

$pathbase = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - eKantin</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkus" style="padding-bottom:30px;">

  <div class="headerkembali">
    <a href="../keranjang/keranjang.php" class="tombolkembali">
      <i class="fa-solid fa-arrow-left"></i>
    </a>
    <div class="teksheader">
      <h1>Konfirmasi Pesanan</h1>
      <p><i class="fa-solid fa-store"></i> <?= htmlspecialchars($namatoko) ?></p>
    </div>
  </div>

  <form method="POST" action="prosespesanan.php">
    <input type="hidden" name="id_toko" value="<?= $idtoko ?>">

    <!-- Detail item -->
    <div class="judulbagian"><i class="fa-solid fa-list"></i> Detail Pesanan</div>
    <div class="kartu">
      <?php foreach ($daftaritem as $item): ?>
      <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--latar);">
        <div style="flex:1;">
          <div style="font-size:14px;font-weight:700;"><?= htmlspecialchars($item['nama_menu']) ?></div>
          <div style="font-size:12px;color:var(--tekssamar);">
            <?= $item['qty'] ?> x Rp <?= number_format($item['harga'],0,',','.') ?>
          </div>
        </div>
        <div style="font-size:15px;font-weight:800;color:var(--utama);margin-left:12px;">
          Rp <?= number_format($item['harga']*$item['qty'],0,',','.') ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Catatan -->
    <div class="judulbagian"><i class="fa-solid fa-note-sticky"></i> Catatan untuk Penjual</div>
    <div class="kelompokform" style="margin-bottom:18px;">
      <textarea name="catatan" rows="2" placeholder="Misal: pisahkan sambelnya, jangan pedas..."></textarea>
    </div>

    <!-- Metode bayar -->
    <div class="judulbagian"><i class="fa-solid fa-wallet"></i> Metode Pembayaran</div>
    <div style="margin-bottom:18px;">
      <label class="pilihanmetode">
        <input type="radio" name="metode" value="Tunai" required checked>
        <span><i class="fa-solid fa-money-bill-wave"></i> Bayar di Tempat (Tunai)</span>
      </label>
      <label class="pilihanmetode">
        <input type="radio" name="metode" value="QRIS">
        <span><i class="fa-solid fa-qrcode"></i> QRIS / Dompet Digital</span>
      </label>
      <label class="pilihanmetode">
        <input type="radio" name="metode" value="Transfer">
        <span><i class="fa-solid fa-building-columns"></i> Transfer Bank</span>
      </label>
    </div>

    <!-- Ringkasan harga -->
    <div class="judulbagian"><i class="fa-solid fa-receipt"></i> Ringkasan Pembayaran</div>
    <div class="ringkasan" style="margin-bottom:20px;">
      <div class="barisringkasan">
        <span>Subtotal (<?= count($daftaritem) ?> item)</span>
        <b>Rp <?= number_format($subtotal,0,',','.') ?></b>
      </div>
      <div class="barisringkasan">
        <span>Biaya Layanan</span>
        <b>Rp <?= number_format($biayalayanan,0,',','.') ?></b>
      </div>
      <div class="barisringkasan total">
        <span>Total Bayar</span>
        <b>Rp <?= number_format($totalbayar,0,',','.') ?></b>
      </div>
    </div>

    <!-- Tombol pesan -->
    <button type="submit" class="tombolutama blok" style="padding:14px;font-size:15px;">
      <i class="fa-solid fa-bag-shopping"></i>
      Pesan &mdash; Rp <?= number_format($totalbayar,0,',','.') ?>
    </button>

  </form>

</div>
</body>
</html>
