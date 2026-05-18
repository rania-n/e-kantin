<?php
/* ============================================================
   LAPORAN PENJUALAN
   Filter: hari ini / minggu ini / bulan ini / periode custom
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idtoko = (int)$_SESSION['id_toko'];
$halamansaatini = 'laporan';

// Filter periode
$periode  = $_GET['periode'] ?? 'bulan';
$tglawal  = '';
$tglakhir = '';

switch ($periode) {
    case 'hari':
        $tglawal  = date('Y-m-d');
        $tglakhir = date('Y-m-d');
        $labelprd = 'Hari Ini (' . date('d M Y') . ')';
        break;
    case 'minggu':
        $tglawal  = date('Y-m-d', strtotime('monday this week'));
        $tglakhir = date('Y-m-d', strtotime('sunday this week'));
        $labelprd = 'Minggu Ini (' . date('d', strtotime($tglawal)) . '–' . date('d M Y', strtotime($tglakhir)) . ')';
        break;
    case 'custom':
        $tglawal  = $_GET['dari'] ?? date('Y-m-01');
        $tglakhir = $_GET['sampai'] ?? date('Y-m-d');
        // Validasi format tanggal
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglawal))  $tglawal  = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglakhir)) $tglakhir = date('Y-m-d');
        if ($tglawal > $tglakhir) [$tglawal, $tglakhir] = [$tglakhir, $tglawal];
        $labelprd = date('d M Y', strtotime($tglawal)) . ' — ' . date('d M Y', strtotime($tglakhir));
        break;
    default: // bulan
        $periode  = 'bulan';
        $tglawal  = date('Y-m-01');
        $tglakhir = date('Y-m-t');
        $labelprd = 'Bulan Ini (' . date('M Y') . ')';
}

// ==============================================================
// RINGKASAN LAPORAN
// ==============================================================

// Total pendapatan (pesanan selesai)
$q1 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0), COUNT(*) FROM tb_order WHERE id_toko=? AND status_order='Selesai' AND deleted=0 AND DATE(tanggal_order) BETWEEN ? AND ?");
$q1->bind_param("iss", $idtoko, $tglawal, $tglakhir); $q1->execute();
$r1 = $q1->get_result()->fetch_row(); $q1->close();
$totalpendapatan = (float)$r1[0];
$totalpesananselesai = (int)$r1[1];

// Total semua pesanan (termasuk batal)
$q2 = $conn->prepare("SELECT COUNT(*), SUM(CASE WHEN status_order='Dibatalkan' THEN 1 ELSE 0 END) FROM tb_order WHERE id_toko=? AND deleted=0 AND DATE(tanggal_order) BETWEEN ? AND ?");
$q2->bind_param("iss", $idtoko, $tglawal, $tglakhir); $q2->execute();
$r2 = $q2->get_result()->fetch_row(); $q2->close();
$totalpesanan = (int)$r2[0];
$totaldibatal = (int)$r2[1];

// Rata-rata nilai pesanan
$ratarata = $totalpesananselesai > 0 ? $totalpendapatan / $totalpesananselesai : 0;

// ==============================================================
// PRODUK TERLARIS
// ==============================================================
$qtl = $conn->prepare("SELECT m.nama_menu, SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omset
                        FROM tb_detail_order d
                        JOIN tb_menu m ON d.id_menu=m.id_menu
                        JOIN tb_order o ON d.id_order=o.id_order
                        WHERE m.id_toko=? AND o.deleted=0 AND d.deleted=0
                          AND o.status_order='Selesai'
                          AND DATE(o.tanggal_order) BETWEEN ? AND ?
                        GROUP BY m.id_menu, m.nama_menu
                        ORDER BY terjual DESC LIMIT 10");
$qtl->bind_param("iss", $idtoko, $tglawal, $tglakhir); $qtl->execute();
$terlaris = $qtl->get_result()->fetch_all(MYSQLI_ASSOC); $qtl->close();

// ==============================================================
// DAFTAR PESANAN SELESAI (terbaru, untuk detail laporan)
// ==============================================================
$qo = $conn->prepare("SELECT o.id_order, o.tanggal_order, o.total_harga, o.metode_pembayaran, u.username
                       FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user
                       WHERE o.id_toko=? AND o.status_order='Selesai' AND o.deleted=0
                         AND DATE(o.tanggal_order) BETWEEN ? AND ?
                       ORDER BY o.tanggal_order DESC LIMIT 50");
$qo->bind_param("iss", $idtoko, $tglawal, $tglakhir); $qo->execute();
$daftarorder = $qo->get_result()->fetch_all(MYSQLI_ASSOC); $qo->close();

function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Penjualan - eKantin</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten">

  <div class="header-halaman takprint">
    <div class="kiri">
      <h1><i class="fa-solid fa-chart-bar"></i> Laporan Penjualan</h1>
      <p><?= $labelprd ?></p>
    </div>
    <button onclick="window.print()" class="tombolutama">
      <i class="fa-solid fa-print"></i> Cetak Laporan
    </button>
  </div>

  <!-- HEADER CETAK -->
  <div class="taklihat" style="display:none;" id="headercetak">
    <div style="text-align:center;margin-bottom:16px;">
      <div style="font-size:20px;font-weight:800;"><?= htmlspecialchars($_SESSION['nama_toko']??'') ?></div>
      <div style="font-size:14px;">Laporan Penjualan — <?= $labelprd ?></div>
      <div style="font-size:12px;color:#666;">Dicetak: <?= date('d M Y H:i') ?></div>
    </div>
    <hr style="border-color:#ccc;margin-bottom:16px;">
  </div>

  <!-- Filter periode -->
  <div class="filter-bar takprint" style="margin-bottom:16px;flex-wrap:wrap;align-items:center;gap:10px;">
    <?php foreach (['hari'=>'Hari Ini','minggu'=>'Minggu Ini','bulan'=>'Bulan Ini'] as $p => $label): ?>
    <a href="laporan.php?periode=<?= $p ?>"
       class="chip-filter <?= $periode===$p?'aktif':'' ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>

    <!-- Custom range -->
    <form method="GET" action="laporan.php" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
      <input type="hidden" name="periode" value="custom">
      <input type="date" name="dari"
             value="<?= $periode==='custom' ? $tglawal : date('Y-m-01') ?>"
             style="padding:7px 10px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;color:var(--teks);">
      <span style="font-size:13px;color:var(--tekssamar);">s.d.</span>
      <input type="date" name="sampai"
             value="<?= $periode==='custom' ? $tglakhir : date('Y-m-d') ?>"
             style="padding:7px 10px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;color:var(--teks);">
      <button type="submit" class="chip-filter <?= $periode==='custom'?'aktif':'' ?>">Tampilkan</button>
    </form>
  </div>

  <!-- RINGKASAN -->
  <div class="ringkasan-laporan">
    <div class="kotak-laporan">
      <div class="nilai"><?= rp($totalpendapatan) ?></div>
      <div class="label">Total Pendapatan</div>
    </div>
    <div class="kotak-laporan">
      <div class="nilai"><?= $totalpesananselesai ?></div>
      <div class="label">Pesanan Selesai</div>
    </div>
    <div class="kotak-laporan">
      <div class="nilai"><?= rp($ratarata) ?></div>
      <div class="label">Rata-rata Per Pesanan</div>
    </div>
    <div class="kotak-laporan">
      <div class="nilai"><?= $totaldibatal ?></div>
      <div class="label">Pesanan Dibatalkan</div>
    </div>
  </div>

  <div class="grid-dua">

    <!-- PRODUK TERLARIS -->
    <div class="kartu">
      <h3><i class="fa-solid fa-fire"></i> Produk Terlaris</h3>
      <?php if (empty($terlaris)): ?>
      <div class="kosong" style="padding:20px;">
        <p>Belum ada data produk terjual</p>
      </div>
      <?php else: ?>
      <?php $medalwarna = ['emas','perak','perunggu']; ?>
      <?php foreach ($terlaris as $i => $t): ?>
      <div class="baris-produk">
        <div class="rangking-produk <?= $medalwarna[$i] ?? '' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;">
          <div style="font-size:13px;font-weight:700;"><?= htmlspecialchars($t['nama_menu']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);"><?= rp($t['omset']) ?> omset</div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);"><?= $t['terjual'] ?> porsi</div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- INFO LAPORAN -->
    <div class="kartu">
      <h3><i class="fa-solid fa-circle-info"></i> Ringkasan</h3>
      <div style="font-size:13px;line-height:2;">
        <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--latar);padding:4px 0;">
          <span>Periode</span>
          <strong><?= $labelprd ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--latar);padding:4px 0;">
          <span>Total Pesanan Masuk</span>
          <strong><?= $totalpesanan ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--latar);padding:4px 0;">
          <span>Pesanan Selesai</span>
          <strong><?= $totalpesananselesai ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--latar);padding:4px 0;">
          <span>Pesanan Dibatalkan</span>
          <strong><?= $totaldibatal ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--latar);padding:4px 0;">
          <span>Total Pendapatan</span>
          <strong style="color:var(--utama);"><?= rp($totalpendapatan) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:4px 0;">
          <span>Rata-rata Per Pesanan</span>
          <strong><?= rp($ratarata) ?></strong>
        </div>
      </div>
    </div>

  </div>

  <!-- DAFTAR PESANAN SELESAI -->
  <?php if (!empty($daftarorder)): ?>
  <div class="kartu">
    <h3><i class="fa-solid fa-list"></i> Detail Transaksi Selesai</h3>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th>No. Pesanan</th>
            <th>Tanggal</th>
            <th>Pembeli</th>
            <th>Metode</th>
            <th style="text-align:right;">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftarorder as $o): ?>
          <tr>
            <td><strong>EK-<?= str_pad($o['id_order'],6,'0',STR_PAD_LEFT) ?></strong></td>
            <td><?= date('d M Y H:i', strtotime($o['tanggal_order'])) ?></td>
            <td><?= htmlspecialchars($o['username']) ?></td>
            <td><?= htmlspecialchars($o['metode_pembayaran']) ?></td>
            <td style="text-align:right;font-weight:700;color:var(--utama);"><?= rp($o['total_harga']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" style="font-weight:700;text-align:right;background:var(--latar);">TOTAL</td>
            <td style="font-weight:800;text-align:right;background:var(--latar);color:var(--utama);"><?= rp($totalpendapatan) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  <?php endif; ?>

</main>

<style>
@media print {
  .takprint { display: none !important; }
  #headercetak { display: block !important; }
  .sidebar, .mobile-header { display: none !important; }
  .konten { margin-left: 0 !important; padding: 16px !important; }
  body { background: white !important; }
  .kartu { box-shadow: none; border: 1px solid #ddd; }
}
</style>

</body>
</html>
