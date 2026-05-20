<?php
/* halaman konfirmasi pesanan (checkout)
   menampilkan ringkasan item dari satu toko yang dipilih,
   form catatan, pilihan metode pembayaran, dan total biaya
   sebelum pembeli menekan tombol "Pesan" untuk memproses order */

// cegah browser menyimpan cache halaman checkout agar tidak ada form lama yang terisi otomatis
// ini mencegah pengguna melihat konfirmasi toko yang salah karena halaman tersimpan di cache
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// guard memastikan hanya pembeli yang login yang bisa mengakses
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

// ambil id toko dari parameter URL — checkout dilakukan per toko
$idtoko    = (int)($_GET['toko'] ?? 0);
// ambil keranjang dari session
$keranjang = $_SESSION['keranjang'] ?? [];

// validasi: jika id toko tidak valid atau toko tidak ada di keranjang, kembali ke keranjang
if (!$idtoko || !isset($keranjang[$idtoko])) {
    header("Location: ../keranjang/keranjang.php"); exit;
}

// ambil item dari toko yang dipilih
$itemtoko   = $keranjang[$idtoko];
// ambil nama toko dari data _info yang tersimpan di session
$namatoko   = $itemtoko['_info']['nama_toko'] ?? 'Kantin';
$daftaritem = [];
$subtotal   = 0;

// pisahkan _info dari item menu, hitung subtotal
foreach ($itemtoko as $k => $v) {
    if ($k === '_info') continue; // lewati data info toko
    $subtotal    += $v['harga'] * $v['qty'];
    $daftaritem[] = $v;
}

// jika tidak ada item (keranjang toko kosong), kembali ke keranjang
if (empty($daftaritem)) { header("Location: ../keranjang/keranjang.php"); exit; }

// biaya layanan tetap Rp1.000 per transaksi
$biayalayanan = 1000;
$totalbayar   = $subtotal + $biayalayanan;

$pathbase = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkus" style="padding-bottom:30px;">

  <!-- judul halaman dengan nama toko yang sedang di-checkout -->
  <div style="margin-bottom:18px;">
    <h1 style="font-size:20px;font-weight:800;color:var(--utama);display:flex;align-items:center;gap:8px;">
      <i class="fa-solid fa-bag-shopping"></i> Konfirmasi Pesanan
    </h1>
    <p style="font-size:13px;color:var(--tekssamar);margin-top:2px;">
      <i class="fa-solid fa-store"></i> <?= htmlspecialchars($namatoko) ?>
    </p>
  </div>

  <!-- form pengiriman ke prosespesanan.php via POST -->
  <form method="POST" action="prosespesanan.php">
    <!-- kirim id toko ke proses — dipakai untuk mengambil item dari keranjang session -->
    <input type="hidden" name="id_toko" value="<?= $idtoko ?>">
    <!-- token unik per sesi untuk mencegah pengiriman form yang tidak sengaja ke toko yang salah -->
    <input type="hidden" name="token_checkout" value="<?= htmlspecialchars(md5($idtoko . '_' . session_id())) ?>">

    <!-- daftar item yang akan dipesan -->
    <div class="judulbagian"><i class="fa-solid fa-list"></i> Detail Pesanan</div>
    <div class="kartu">
      <?php foreach ($daftaritem as $item): ?>
      <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--latar);">
        <div style="flex:1;">
          <div style="font-size:14px;font-weight:700;"><?= htmlspecialchars($item['nama_menu']) ?></div>
          <div style="font-size:12px;color:var(--tekssamar);">
            <!-- tampilkan qty x harga satuan -->
            <?= $item['qty'] ?> x Rp <?= number_format($item['harga'],0,',','.') ?>
          </div>
        </div>
        <!-- subtotal per item = harga x qty -->
        <div style="font-size:15px;font-weight:800;color:var(--utama);margin-left:12px;">
          Rp <?= number_format($item['harga']*$item['qty'],0,',','.') ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- input catatan opsional untuk penjual, misalnya "tidak pedas" -->
    <div class="judulbagian"><i class="fa-solid fa-note-sticky"></i> Catatan untuk Penjual</div>
    <div class="kelompokform" style="margin-bottom:18px;">
      <textarea name="catatan" rows="2" placeholder="Misal: pisahkan sambelnya, jangan pedas..."></textarea>
    </div>

    <!-- pilihan metode pembayaran — wajib dipilih, default Tunai -->
    <div class="judulbagian"><i class="fa-solid fa-wallet"></i> Metode Pembayaran</div>
    <div style="margin-bottom:18px;">
      <!-- radio button: hanya satu yang bisa dipilih sekaligus -->
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

    <!-- ringkasan harga dan tombol aksi -->
    <div class="judulbagian"><i class="fa-solid fa-receipt"></i> Ringkasan Pembayaran</div>
    <div class="kartu">
      <div class="ringkasan" style="margin-bottom:16px;">
        <!-- subtotal = harga semua item tanpa biaya layanan -->
        <div class="barisringkasan">
          <span>Subtotal (<?= count($daftaritem) ?> item)</span>
          <b>Rp <?= number_format($subtotal,0,',','.') ?></b>
        </div>
        <!-- biaya layanan tetap Rp1.000 -->
        <div class="barisringkasan">
          <span>Biaya Layanan</span>
          <b>Rp <?= number_format($biayalayanan,0,',','.') ?></b>
        </div>
        <!-- total yang harus dibayar pembeli -->
        <div class="barisringkasan total">
          <span>Total Bayar</span>
          <b>Rp <?= number_format($totalbayar,0,',','.') ?></b>
        </div>
      </div>
      <div style="display:flex;gap:10px;">
        <!-- tombol batal: kembali ke keranjang tanpa memproses -->
        <a href="../keranjang/keranjang.php" class="tombolringan" style="padding:10px 14px;font-size:13px;">
          Batal
        </a>
        <!-- tombol submit: kirim form ke prosespesanan.php -->
        <button type="submit" class="tombolutama" style="flex:2;padding:14px;font-size:15px;">
          <i class="fa-solid fa-bag-shopping"></i>
          Pesan &mdash; Rp <?= number_format($totalbayar,0,',','.') ?>
        </button>
      </div>
    </div>

  </form>

</div>
</body>
</html>
