<?php
/* ============================================================
   laporan penjualan
   filter: hari ini / minggu ini / bulan ini / custom tanggal
   cetak: tambahkan ?cetak=1 pada url
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idtoko = (int)$_SESSION['id_toko'];
$halamansaatini = 'laporan';
$cetak = isset($_GET['cetak']);

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
        $tglawal  = $_GET['dari']   ?? date('Y-m-01');
        $tglakhir = $_GET['sampai'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglawal))  $tglawal  = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglakhir)) $tglakhir = date('Y-m-d');
        if ($tglawal > $tglakhir) [$tglawal, $tglakhir] = [$tglakhir, $tglawal];
        $labelprd = date('d M Y', strtotime($tglawal)) . ' — ' . date('d M Y', strtotime($tglakhir));
        break;
    default:
        $periode  = 'bulan';
        $tglawal  = date('Y-m-01');
        $tglakhir = date('Y-m-t');
        $labelprd = 'Bulan Ini (' . date('M Y') . ')';
}

// ringkasan pendapatan
$q1 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0), COUNT(*) FROM tb_order WHERE id_toko=? AND status_order='Selesai' AND deleted=0 AND DATE(tanggal_order) BETWEEN ? AND ?");
$q1->bind_param("iss", $idtoko, $tglawal, $tglakhir); $q1->execute();
$r1 = $q1->get_result()->fetch_row(); $q1->close();
$totalpendapatan    = (float)$r1[0];
$totalpesananselesai = (int)$r1[1];

$q2 = $conn->prepare("SELECT COUNT(*), SUM(CASE WHEN status_order='Dibatalkan' THEN 1 ELSE 0 END) FROM tb_order WHERE id_toko=? AND deleted=0 AND DATE(tanggal_order) BETWEEN ? AND ?");
$q2->bind_param("iss", $idtoko, $tglawal, $tglakhir); $q2->execute();
$r2 = $q2->get_result()->fetch_row(); $q2->close();
$totalpesanan = (int)$r2[0];
$totaldibatal = (int)$r2[1];

$ratarata = $totalpesananselesai > 0 ? $totalpendapatan / $totalpesananselesai : 0;

// produk terlaris
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

// daftar pesanan selesai
$qo = $conn->prepare("SELECT o.id_order, o.tanggal_order, o.total_harga, o.metode_pembayaran, u.username
                       FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user
                       WHERE o.id_toko=? AND o.status_order='Selesai' AND o.deleted=0
                         AND DATE(o.tanggal_order) BETWEEN ? AND ?
                       ORDER BY o.tanggal_order DESC LIMIT 50");
$qo->bind_param("iss", $idtoko, $tglawal, $tglakhir); $qo->execute();
$daftarorder = $qo->get_result()->fetch_all(MYSQLI_ASSOC); $qo->close();

// chart harian — satu query group by, per hari tanpa kompresi, nama hari indonesia
function namahari(string $tgl): string {
    $map = ['Sun'=>'Min','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab'];
    return $map[date('D', strtotime($tgl))] ?? date('D', strtotime($tgl));
}
$qchart = $conn->prepare("SELECT DATE(tanggal_order) AS tgl, COALESCE(SUM(total_harga),0) AS nilai FROM tb_order WHERE id_toko=? AND DATE(tanggal_order) BETWEEN ? AND ? AND status_order='Selesai' AND deleted=0 GROUP BY DATE(tanggal_order)");
$qchart->bind_param("iss", $idtoko, $tglawal, $tglakhir); $qchart->execute();
$rawchart = []; $resc = $qchart->get_result();
while ($row = $resc->fetch_assoc()) $rawchart[$row['tgl']] = (float)$row['nilai'];
$qchart->close();
$selisih = (int)ceil((strtotime($tglakhir) - strtotime($tglawal)) / 86400) + 1;
$chartdata = [];
for ($i = 0; $i < $selisih; $i++) {
    $tgl = date('Y-m-d', strtotime($tglawal) + $i * 86400);
    $chartdata[] = ['tgl'=>$tgl,'label'=>namahari($tgl).' '.date('d',strtotime($tgl)),'nilai'=>$rawchart[$tgl]??0.0];
}
$maxnilai = max(array_column($chartdata,'nilai')) ?: 1;

function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
function singkat(float $n): string {
    if ($n >= 1_000_000_000) { $v=$n/1_000_000_000; return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' M'; }
    if ($n >= 1_000_000)     { $v=$n/1_000_000;     return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' Jt'; }
    return 'Rp ' . number_format($n, 0, ',', '.');
}

$n      = count($chartdata);
$svgw   = max(700, $n * 30);
$barw   = max(8, min(40, (int)(($svgw - 80) / $n) - 4));
$gap    = max(4, (int)(($svgw - 80 - $n * $barw) / max(1, $n - 1)));
$startx = 70; $chartH = 160;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Penjualan - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  @media print {
    @page { size: A4 portrait; margin: 15mm; }
    .kartu { box-shadow: none !important; border: 1px solid #ddd !important; break-inside: avoid; }
  }
</style>
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

  <!-- header cetak (tersembunyi saat normal) -->
  <?php if ($cetak): ?>
  <div style="text-align:center;margin-bottom:18px;">
    <div style="font-size:18px;font-weight:800;"><?= htmlspecialchars($_SESSION['nama_toko']??'Toko') ?></div>
    <div style="font-size:13px;">Laporan Penjualan — <?= $labelprd ?></div>
    <div style="font-size:11px;color:#666;">Dicetak: <?= date('d M Y H:i') ?></div>
    <hr style="border-color:#ccc;margin:12px 0;">
  </div>
  <?php endif; ?>

  <!-- filter periode -->
  <div class="takprint" style="margin-bottom:16px;">
    <div class="filter-bar" style="margin-bottom:10px;flex-wrap:wrap;">
      <?php foreach (['hari'=>'Hari Ini','minggu'=>'Minggu Ini','bulan'=>'Bulan Ini'] as $p => $lab): ?>
      <a href="laporan.php?periode=<?= $p ?>" class="chip-filter <?= $periode===$p?'aktif':'' ?>"><?= $lab ?></a>
      <?php endforeach; ?>
      <a href="laporan.php?periode=custom&dari=<?= $tglawal ?>&sampai=<?= $tglakhir ?>"
         class="chip-filter <?= $periode==='custom'?'aktif':'' ?>">Custom</a>
    </div>
    <?php if ($periode === 'custom'): ?>
    <form method="GET" action="laporan.php" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
      <input type="hidden" name="periode" value="custom">
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--tekssamar);display:block;margin-bottom:4px;">DARI</label>
        <input type="date" name="dari" value="<?= $tglawal ?>"
               style="padding:8px 12px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;font-family:inherit;">
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--tekssamar);display:block;margin-bottom:4px;">SAMPAI</label>
        <input type="date" name="sampai" value="<?= $tglakhir ?>"
               style="padding:8px 12px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;font-family:inherit;">
      </div>
      <button type="submit" class="tombolutama" style="align-self:flex-end;">
        <i class="fa-solid fa-filter"></i> Terapkan
      </button>
    </form>
    <?php endif; ?>
  </div>

  <!-- ringkasan statistik — konsisten dengan grid-stat -->
  <div class="grid-stat">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-coins"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= singkat($totalpendapatan) ?></div>
        <div class="label">Total Pendapatan</div>
        <div class="tren" style="color:var(--tekssamar);">Pesanan selesai</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-check-circle"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $totalpesananselesai ?></div>
        <div class="label">Pesanan Selesai</div>
        <div class="tren" style="color:var(--tekssamar);"><?= $totalpesanan ?> total masuk</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-calculator"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= singkat($ratarata) ?></div>
        <div class="label">Rata-rata / Pesanan</div>
        <div class="tren" style="color:var(--tekssamar);">Per pesanan selesai</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-ban"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $totaldibatal ?></div>
        <div class="label">Dibatalkan</div>
        <div class="tren" style="color:var(--tekssamar);"><?= $labelprd ?></div>
      </div>
    </div>
  </div>

  <!-- diagram batang pendapatan harian -->
  <div class="kartu">
    <h3><i class="fa-solid fa-chart-bar"></i> Pendapatan Harian — <?= $labelprd ?></h3>
    <?php if ($totalpendapatan <= 0): ?>
    <div class="kosong" style="padding:20px;"><p>Belum ada pendapatan pada periode ini</p></div>
    <?php else: ?>
    <div class="area-chart">
      <svg viewBox="0 0 <?=$svgw?> 210" xmlns="http://www.w3.org/2000/svg" style="min-width:<?=min(700,$svgw)?>px;">
        <?php for($g=0;$g<=4;$g++):$y=20+($g*40);?>
        <line x1="60" y1="<?=$y?>" x2="<?=$svgw-10?>" y2="<?=$y?>" stroke="#E7CBCB" stroke-width="1" stroke-dasharray="4,4"/>
        <text x="55" y="<?=$y+4?>" text-anchor="end" fill="#99627A" font-size="9"><?=singkat(($maxnilai/4)*(4-$g))?></text>
        <?php endfor;?>
        <?php foreach($chartdata as $i=>$d):
          $x=$startx+$i*($barw+$gap);
          $barh=$d['nilai']>0?($d['nilai']/$maxnilai)*$chartH:2;
          $by=180-$barh;
          $isToday=$d['tgl']===date('Y-m-d');
        ?>
        <rect x="<?=$x?>" y="<?=$by?>" width="<?=$barw?>" height="<?=$barh?>" rx="3" fill="<?=$isToday?'#643843':'#99627A'?>">
          <title><?=$d['label']?> — <?=rp($d['nilai'])?></title>
        </rect>
        <text x="<?=$x+$barw/2?>" y="200" text-anchor="middle" fill="<?=$isToday?'#643843':'#99627A'?>"
              font-size="<?=$n>20?'7':'9'?>" font-weight="<?=$isToday?'700':'400'?>"><?=$d['label']?></text>
        <?php if($d['nilai']>0&&$barw>=20):?>
        <text x="<?=$x+$barw/2?>" y="<?=max($by-4,14)?>" text-anchor="middle" fill="#643843" font-size="8" font-weight="600">
          <?=number_format($d['nilai']/1000,0)?>k
        </text>
        <?php endif;?>
        <?php endforeach;?>
      </svg>
    </div>
    <?php endif; ?>
  </div>

  <div class="grid-dua">

    <!-- produk terlaris -->
    <div class="kartu">
      <h3><i class="fa-solid fa-fire"></i> Produk Terlaris</h3>
      <?php if (empty($terlaris)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada data produk terjual</p></div>
      <?php else: ?>
      <?php $medalwarna = ['emas','perak','perunggu']; ?>
      <?php foreach ($terlaris as $i => $t): ?>
      <div class="baris-produk">
        <div class="rangking-produk <?= $medalwarna[$i] ?? '' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= htmlspecialchars($t['nama_menu']) ?>
          </div>
          <div style="font-size:11px;color:var(--tekssamar);"><?= rp($t['omset']) ?> omset</div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;"><?= $t['terjual'] ?> porsi</div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- ringkasan info -->
    <div class="kartu">
      <h3><i class="fa-solid fa-circle-info"></i> Ringkasan</h3>
      <div style="font-size:13px;line-height:1.8;">
        <?php
        $barisinfo = [
          'Periode'              => $labelprd,
          'Total Pesanan Masuk'  => $totalpesanan,
          'Pesanan Selesai'      => $totalpesananselesai,
          'Pesanan Dibatalkan'   => $totaldibatal,
          'Total Pendapatan'     => rp($totalpendapatan),
          'Rata-rata Per Pesanan'=> rp($ratarata),
        ];
        foreach ($barisinfo as $k => $v):
        ?>
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:4px;border-bottom:1px solid var(--latar);padding:5px 0;">
          <span style="color:var(--tekssamar);"><?= $k ?></span>
          <strong style="<?= $k==='Total Pendapatan'?'color:var(--utama);':'' ?>"><?= $v ?></strong>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- daftar transaksi -->
  <?php if (!empty($daftarorder)): ?>
  <div class="kartu">
    <h3><i class="fa-solid fa-list"></i> Detail Transaksi Selesai</h3>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th>No. Pesanan</th>
            <th>Tanggal</th>
            <th class="sembunyimobile">Pembeli</th>
            <th class="sembunyimobile">Metode</th>
            <th style="text-align:right;">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftarorder as $o): ?>
          <tr>
            <td><strong>EK-<?= str_pad($o['id_order'],6,'0',STR_PAD_LEFT) ?></strong></td>
            <td style="white-space:nowrap;"><?= date('d M Y H:i', strtotime($o['tanggal_order'])) ?></td>
            <td class="sembunyimobile"><?= htmlspecialchars($o['username']) ?></td>
            <td class="sembunyimobile"><?= htmlspecialchars($o['metode_pembayaran']) ?></td>
            <td style="text-align:right;font-weight:700;color:var(--utama);white-space:nowrap;"><?= rp($o['total_harga']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" style="font-weight:700;background:var(--latar);padding:10px 16px;">TOTAL</td>
            <td style="font-weight:800;text-align:right;background:var(--latar);padding:10px 16px;color:var(--utama);"><?= rp($totalpendapatan) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  <?php endif; ?>

</main>
</body>
</html>
