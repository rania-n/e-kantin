<?php
/* ============================================================
   CETAK STRUK PESANAN (untuk penjual)
   Format 80mm thermal printer - otomatis cetak saat dibuka
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idtoko    = (int)$_SESSION['id_toko'];
$idpesanan = (int)($_GET['id'] ?? 0);

if (!$idpesanan) { header("Location: manajemenpesanan.php"); exit; }

// Ambil data pesanan, pastikan milik toko ini
$q = $conn->prepare("SELECT o.*,u.username,u.email FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user WHERE o.id_order=? AND o.id_toko=? AND o.deleted=0");
$q->bind_param("ii", $idpesanan, $idtoko); $q->execute();
$pesanan = $q->get_result()->fetch_assoc(); $q->close();

if (!$pesanan) { header("Location: manajemenpesanan.php"); exit; }

// Ambil detail item
$qd = $conn->prepare("SELECT d.*,m.nama_menu FROM tb_detail_order d JOIN tb_menu m ON d.id_menu=m.id_menu WHERE d.id_order=? AND d.deleted=0");
$qd->bind_param("i", $idpesanan); $qd->execute();
$items = $qd->get_result()->fetch_all(MYSQLI_ASSOC); $qd->close();

// Hitung antrian harian
$qa = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_toko=? AND DATE(tanggal_order)=DATE(?) AND id_order<=? AND deleted=0");
$tglpesanan = $pesanan['tanggal_order'];
$qa->bind_param("isi", $idtoko, $tglpesanan, $idpesanan); $qa->execute();
$antrian = (int)$qa->get_result()->fetch_row()[0]; $qa->close();

$subtotalitem = array_sum(array_column($items, 'subtotal'));
$biayalayanan = $pesanan['total_harga'] - $subtotalitem;
$nomerpesanan = 'EK-' . str_pad($idpesanan, 6, '0', STR_PAD_LEFT);
$namatoko     = htmlspecialchars($_SESSION['nama_toko'] ?? 'eKantin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Struk <?= $nomerpesanan ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: 'Courier New', Courier, monospace;
  font-size: 12px;
  background: #f0f0f0;
  display: flex;
  justify-content: center;
  padding: 20px;
}
.struk {
  width: 80mm;
  background: white;
  padding: 6mm;
  line-height: 1.4;
}
.tengah  { text-align: center; }
.kanan   { text-align: right; }
hr       { border: none; border-top: 1px dashed #555; margin: 6px 0; }
.tebal   { font-weight: bold; }
.baris-item {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 2px;
}
.baris-item .nama { flex: 1; }
.total-box {
  border-top: 1px solid #555;
  padding-top: 6px;
  margin-top: 4px;
  font-weight: bold;
}
.antrian-box {
  text-align: center;
  border: 2px dashed #555;
  padding: 8px;
  margin: 8px 0;
}
.antrian-box .angka { font-size: 28px; font-weight: bold; }
.tombol-cetak {
  display: flex;
  gap: 10px;
  margin-top: 16px;
  justify-content: center;
}
.tombol-cetak button, .tombol-cetak a {
  padding: 8px 20px;
  border-radius: 6px;
  font-family: inherit;
  font-size: 12px;
  cursor: pointer;
  text-decoration: none;
  border: 1px solid #643843;
}
.tombol-cetak .btn-print { background: #643843; color: white; }
.tombol-cetak .btn-tutup { background: white; color: #643843; }
@media print {
  body { background: white; padding: 0; display: block; }
  .struk { width: auto; padding: 2mm; }
  .tombol-cetak { display: none; }
}
</style>
</head>
<body>

<div>
  <div class="struk">

    <!-- HEADER -->
    <div class="tengah">
      <div class="tebal" style="font-size:14px;"><?= $namatoko ?></div>
      <div>e-Kantin Sekolah</div>
      <hr>
    </div>

    <!-- NOMOR ANTRIAN -->
    <div class="antrian-box">
      <div style="font-size:10px;">NOMOR ANTRIAN HARI INI</div>
      <div class="angka"><?= str_pad($antrian, 3, '0', STR_PAD_LEFT) ?></div>
    </div>

    <!-- INFO PESANAN -->
    <div>
      <div class="baris-item"><span>No. Order</span><span class="tebal"><?= $nomerpesanan ?></span></div>
      <div class="baris-item"><span>Tanggal</span><span><?= date('d/m/Y H:i', strtotime($pesanan['tanggal_order'])) ?></span></div>
      <div class="baris-item"><span>Pembeli</span><span><?= htmlspecialchars($pesanan['username']) ?></span></div>
      <div class="baris-item"><span>Metode</span><span><?= htmlspecialchars($pesanan['metode_pembayaran']) ?></span></div>
      <?php if (!empty($pesanan['catatan'])): ?>
      <div class="baris-item"><span>Catatan</span><span><?= htmlspecialchars($pesanan['catatan']) ?></span></div>
      <?php endif; ?>
    </div>

    <hr>

    <!-- ITEM -->
    <?php foreach ($items as $it): ?>
    <div style="margin-bottom:4px;">
      <div><?= htmlspecialchars($it['nama_menu']) ?></div>
      <div class="baris-item">
        <span>&nbsp;<?= $it['jumlah'] ?>x @ <?= number_format($it['harga_satuan'],0,',','.') ?></span>
        <span>Rp <?= number_format($it['subtotal'],0,',','.') ?></span>
      </div>
    </div>
    <?php endforeach; ?>

    <hr>

    <!-- TOTAL -->
    <div class="baris-item"><span>Subtotal</span><span>Rp <?= number_format($subtotalitem,0,',','.') ?></span></div>
    <div class="baris-item"><span>Biaya Layanan</span><span>Rp <?= number_format(max(0,$biayalayanan),0,',','.') ?></span></div>
    <div class="total-box">
      <div class="baris-item">
        <span>TOTAL BAYAR</span>
        <span>Rp <?= number_format($pesanan['total_harga'],0,',','.') ?></span>
      </div>
    </div>

    <hr>

    <!-- FOOTER -->
    <div class="tengah" style="font-size:11px;">
      <div>Terima kasih telah memesan!</div>
      <div><?= $nomerpesanan ?></div>
      <div>Dicetak: <?= date('d/m/Y H:i') ?></div>
    </div>

  </div>

  <!-- Tombol aksi (tidak ikut cetak) -->
  <div class="tombol-cetak">
    <button class="btn-print" onclick="window.print()">
      Cetak Struk
    </button>
    <a href="manajemenpesanan.php" class="btn-tutup">Kembali</a>
  </div>
</div>

<script>
// Cetak otomatis saat halaman dibuka
window.addEventListener('load', function() {
  window.print();
});
</script>

</body>
</html>
