<?php
/* ============================================================
   struk pesanan penjual
   tampilan identik dengan pembeli, cetak via ?cetak=1
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idtoko    = (int)$_SESSION['id_toko'];
$idpesanan = (int)($_GET['id'] ?? 0);
$halamansaatini = 'manajemenpesanan';
$cetak = isset($_GET['cetak']);

if (!$idpesanan) { header("Location: manajemenpesanan.php"); exit; }

$q = $conn->prepare("SELECT o.*,u.username FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user WHERE o.id_order=? AND o.id_toko=? AND o.deleted=0");
$q->bind_param("ii", $idpesanan, $idtoko); $q->execute();
$pesanan = $q->get_result()->fetch_assoc(); $q->close();

if (!$pesanan) { header("Location: manajemenpesanan.php"); exit; }

$qd = $conn->prepare("SELECT d.*,m.nama_menu FROM tb_detail_order d JOIN tb_menu m ON d.id_menu=m.id_menu WHERE d.id_order=? AND d.deleted=0");
$qd->bind_param("i", $idpesanan); $qd->execute();
$items = $qd->get_result()->fetch_all(MYSQLI_ASSOC); $qd->close();

$qa = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_toko=? AND DATE(tanggal_order)=DATE(?) AND id_order<=? AND deleted=0");
$tglpesanan = $pesanan['tanggal_order'];
$qa->bind_param("isi", $idtoko, $tglpesanan, $idpesanan); $qa->execute();
$antrian = (int)$qa->get_result()->fetch_row()[0]; $qa->close();

$subtotalitem = array_sum(array_column($items, 'subtotal'));
$biayalayanan = $pesanan['total_harga'] - $subtotalitem;
$nomerpesanan = 'EK-' . str_pad($idpesanan, 6, '0', STR_PAD_LEFT);
$namatoko     = htmlspecialchars($_SESSION['nama_toko'] ?? 'jajankita');

function kelasstatus(string $s): string {
    return match($s) {
        'Menunggu' => 'menunggu', 'Diproses' => 'diproses',
        'Siap Diambil' => 'siap', 'Selesai' => 'selesai',
        default => 'dibatalkan',
    };
}
function ikonStatus(string $s): string {
    return match($s) {
        'Menunggu'     => 'fa-clock',
        'Diproses'     => 'fa-fire-burner',
        'Siap Diambil' => 'fa-bell',
        'Selesai'      => 'fa-circle-check',
        default        => 'fa-circle-xmark',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Struk <?= $nomerpesanan ?> - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  .konten { padding: 16px; }
  .konten-sempit { max-width: none; }
</style>
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten">
<div class="konten-sempit">

  <!-- header kembali -->
  <div class="headerkembali takprint">
    <a href="manajemenpesanan.php" class="tombolkembali"><i class="fa-solid fa-arrow-left"></i></a>
    <div class="teksheader">
      <h1>Struk Pesanan</h1>
      <p><?= $nomerpesanan ?></p>
    </div>
  </div>

  <!-- nomor antrian — style tunggubg identik pembeli -->
  <div class="antirian">
    <div class="angkaantrian"><?= str_pad($antrian, 3, '0', STR_PAD_LEFT) ?></div>
    <div class="labelantrian">
      Nomor Antrian di <?= $namatoko ?> &mdash; <?= date('d M Y', strtotime($pesanan['tanggal_order'])) ?>
    </div>
  </div>

  <!-- struk -->
  <div class="bungkusstruk">

    <div class="kepalastruk">
      <div class="ikonstruk"><i class="fa-solid fa-receipt"></i></div>
      <h2>jajankita &mdash; Struk Digital</h2>
      <p>Simpan sebagai bukti pembayaran</p>
    </div>

    <div class="isistruk" style="padding-bottom:0;">

      <div class="barisstruk">
        <span class="labelstruk">No. Pesanan</span>
        <span class="nilaistruk" style="font-family:monospace;color:var(--utama);"><?= $nomerpesanan ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Kantin</span>
        <span class="nilaistruk"><i class="fa-solid fa-store"></i> <?= $namatoko ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Tanggal</span>
        <span class="nilaistruk"><?= date('d M Y, H:i', strtotime($pesanan['tanggal_order'])) ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Pembeli</span>
        <span class="nilaistruk"><?= htmlspecialchars($pesanan['username']) ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Metode Bayar</span>
        <span class="nilaistruk"><i class="fa-solid fa-wallet"></i> <?= htmlspecialchars($pesanan['metode_pembayaran']) ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Status</span>
        <span class="badge <?= kelasstatus($pesanan['status_order']) ?>">
          <i class="fa-solid <?= ikonStatus($pesanan['status_order']) ?>"></i>
          <?= htmlspecialchars($pesanan['status_order']) ?>
        </span>
      </div>
      <?php if (!empty($pesanan['catatan'])): ?>
      <div class="barisstruk">
        <span class="labelstruk">Catatan</span>
        <span class="nilaistruk"><?= htmlspecialchars($pesanan['catatan']) ?></span>
      </div>
      <?php endif; ?>

      <div class="pemisah"><hr><span><i class="fa-solid fa-star"></i></span><hr></div>

      <div class="judulbagian" style="margin:0 0 12px;"><i class="fa-solid fa-list"></i> Detail Pesanan</div>

      <?php foreach ($items as $it): ?>
      <div class="itemdetailstruk">
        <div class="namamenu"><?= htmlspecialchars($it['nama_menu']) ?></div>
        <div class="barisnilai">
          <span><?= $it['jumlah'] ?>x @ Rp <?= number_format($it['harga_satuan'],0,',','.') ?></span>
          <b>Rp <?= number_format($it['subtotal'],0,',','.') ?></b>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="pemisah"><hr><span><i class="fa-solid fa-star"></i></span><hr></div>

      <div class="barisstruk">
        <span class="labelstruk">Subtotal</span>
        <span class="nilaistruk">Rp <?= number_format($subtotalitem,0,',','.') ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Biaya Layanan</span>
        <span class="nilaistruk">Rp <?= number_format(max(0,$biayalayanan),0,',','.') ?></span>
      </div>

    </div><!-- tutup isistruk -->

    <div class="totalstruk">
      <span class="label"><i class="fa-solid fa-coins"></i> Total Bayar</span>
      <span class="nilai">Rp <?= number_format($pesanan['total_harga'],0,',','.') ?></span>
    </div>

    <div class="kakistruk">
      <i class="fa-solid fa-heart"></i>
      <p>Terima kasih telah memesan di jajankita!</p>
      <p style="margin-top:4px;font-size:11px;">
        <?= $nomerpesanan ?> &nbsp;|&nbsp; <?= date('d/m/Y H:i', strtotime($pesanan['tanggal_order'])) ?>
      </p>
    </div>

  </div>

  <!-- tombol aksi -->
  <div class="takprint" style="margin-top:4px;">
    <button onclick="window.print()" class="tombolutama blok">
      <i class="fa-solid fa-print"></i> Cetak Struk
    </button>
  </div>


</div>
</main>

</body>
</html>
