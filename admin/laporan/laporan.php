<?php
/* ============================================================
   laporan platform admin
   filter: 7 hari, 14 hari, 30 hari, atau custom tanggal
   cetak: tambahkan ?cetak=1 pada url untuk mode cetak
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';
$halamansaatini = 'laporan';

$cetak = isset($_GET['cetak']);

// filter periode
$periode = $_GET['periode'] ?? '7';
if (!in_array($periode, ['7','14','30','custom'])) $periode = '7';

if ($periode === 'custom') {
    $dari   = isset($_GET['dari'])   && $_GET['dari']   ? $_GET['dari']   : date('Y-m-d', strtotime('-7 days'));
    $sampai = isset($_GET['sampai']) && $_GET['sampai'] ? $_GET['sampai'] : date('Y-m-d');
    $tglmulai = date('Y-m-d', strtotime($dari));
    $tgljin   = date('Y-m-d', strtotime($sampai));
    if ($tgljin < $tglmulai) $tgljin = $tglmulai;
} else {
    $tglmulai = date('Y-m-d', strtotime("-$periode days"));
    $tgljin   = date('Y-m-d');
    $dari = $tglmulai; $sampai = $tgljin;
}

// ringkasan platform
$qr1 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE DATE(tanggal_order) BETWEEN ? AND ? AND deleted=0");
$qr1->bind_param("ss", $tglmulai, $tgljin); $qr1->execute();
$totalorder = (int)$qr1->get_result()->fetch_row()[0]; $qr1->close();

$qr2 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE DATE(tanggal_order) BETWEEN ? AND ? AND status_order IN ('Selesai','Dibatalkan') AND deleted=0");
$qr2->bind_param("ss", $tglmulai, $tgljin); $qr2->execute();
$totalomset = (float)$qr2->get_result()->fetch_row()[0]; $qr2->close();

$qr3 = $conn->prepare("SELECT COUNT(*) FROM tb_user WHERE DATE(created) BETWEEN ? AND ? AND deleted=0");
$qr3->bind_param("ss", $tglmulai, $tgljin); $qr3->execute();
$userbarujml = (int)$qr3->get_result()->fetch_row()[0]; $qr3->close();

// status pesanan breakdown
$qstat = $conn->prepare("SELECT status_order, COUNT(*) AS jml FROM tb_order WHERE DATE(tanggal_order) BETWEEN ? AND ? AND deleted=0 GROUP BY status_order");
$qstat->bind_param("ss", $tglmulai, $tgljin); $qstat->execute();
$statpesanan = []; $res = $qstat->get_result();
while ($rs = $res->fetch_assoc()) $statpesanan[$rs['status_order']] = (int)$rs['jml'];
$qstat->close();

// performa toko — total omset, dibatalkan, dan rating
$qtoko = $conn->prepare("SELECT t.id_toko, t.nama_toko, t.status_toko,
                                 COUNT(DISTINCT o.id_order) AS total_order,
                                 COALESCE(SUM(CASE WHEN o.status_order='Dibatalkan' THEN 1 ELSE 0 END),0) AS jml_dibatalkan,
                                 COALESCE(SUM(CASE WHEN o.status_order IN ('Selesai','Dibatalkan') THEN o.total_harga ELSE 0 END),0) AS pendapatan,
                                 (SELECT COALESCE(ROUND(AVG(r.rating_toko),1),0) FROM tb_rating r WHERE r.id_toko=t.id_toko AND r.deleted=0) AS rating
                          FROM tb_toko t
                          LEFT JOIN tb_order o ON t.id_toko=o.id_toko
                            AND DATE(o.tanggal_order) BETWEEN ? AND ? AND o.deleted=0
                          WHERE t.deleted=0
                          GROUP BY t.id_toko, t.nama_toko, t.status_toko
                          ORDER BY pendapatan DESC");
$qtoko->bind_param("ss", $tglmulai, $tgljin); $qtoko->execute();
$perftoko = $qtoko->get_result()->fetch_all(MYSQLI_ASSOC); $qtoko->close();

// top produk platform
$qtl = $conn->prepare("SELECT m.nama_menu, t.nama_toko, SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omset
                        FROM tb_detail_order d
                        JOIN tb_menu m ON d.id_menu=m.id_menu
                        JOIN tb_toko t ON m.id_toko=t.id_toko
                        JOIN tb_order o ON d.id_order=o.id_order
                        WHERE DATE(o.tanggal_order) BETWEEN ? AND ?
                          AND d.deleted=0 AND o.deleted=0 AND o.status_order != 'Dibatalkan'
                        GROUP BY m.id_menu, m.nama_menu, t.nama_toko
                        ORDER BY terjual DESC LIMIT 10");
$qtl->bind_param("ss", $tglmulai, $tgljin); $qtl->execute();
$terlaris = $qtl->get_result()->fetch_all(MYSQLI_ASSOC); $qtl->close();

// chart harian — satu query group by, per hari tanpa kompresi, nama hari indonesia
function namahari(string $tgl): string {
    $map = ['Sun'=>'Min','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab'];
    return $map[date('D', strtotime($tgl))] ?? date('D', strtotime($tgl));
}
$qchart = $conn->prepare("SELECT DATE(tanggal_order) AS tgl, COALESCE(SUM(total_harga),0) AS nilai FROM tb_order WHERE DATE(tanggal_order) BETWEEN ? AND ? AND status_order IN ('Selesai','Dibatalkan') AND deleted=0 GROUP BY DATE(tanggal_order)");
$qchart->bind_param("ss", $tglmulai, $tgljin); $qchart->execute();
$rawchart = [];
$resc = $qchart->get_result();
while ($row = $resc->fetch_assoc()) $rawchart[$row['tgl']] = (float)$row['nilai'];
$qchart->close();

$selisih = (int)ceil((strtotime($tgljin) - strtotime($tglmulai)) / 86400) + 1;
$chartdata = [];
for ($i = 0; $i < $selisih; $i++) {
    $tgl = date('Y-m-d', strtotime($tglmulai) + $i * 86400);
    $chartdata[] = ['tgl'=>$tgl,'label'=>namahari($tgl).' '.date('d',strtotime($tgl)),'nilai'=>$rawchart[$tgl]??0.0];
}
$maxnilai = max(array_column($chartdata,'nilai')) ?: 1;

function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
function singkat(float $n): string {
    if ($n >= 1_000_000_000) { $v=$n/1_000_000_000; return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' M'; }
    if ($n >= 1_000_000)     { $v=$n/1_000_000;     return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' Jt'; }
    return 'Rp ' . number_format($n, 0, ',', '.');
}

$labelperiode = ['7'=>'7 Hari','14'=>'14 Hari','30'=>'30 Hari','custom'=>'Custom'];
$labelterpilih = $periode === 'custom'
    ? date('d M Y', strtotime($tglmulai)) . ' – ' . date('d M Y', strtotime($tgljin))
    : $labelperiode[$periode] . ' Terakhir';

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
<title>Laporan Platform - Admin jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  @media print { @page { size: A4; margin: 12mm; } }
</style>
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <div class="header-halaman takprint">
    <div class="kiri">
      <h1><i class="fa-solid fa-chart-bar"></i> Laporan Platform</h1>
      <p>Ringkasan performa jajankita — <?= $labelterpilih ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <button onclick="window.print()" class="tombolringan takprint">
        <i class="fa-solid fa-print"></i> Cetak
      </button>
    </div>
  </div>

  <!-- filter periode -->
  <div class="takprint" style="margin-bottom:18px;">
    <div class="filter-bar" style="margin-bottom:10px;">
      <a href="laporan.php?periode=7"  class="chip-filter <?= $periode==='7'  ?'aktif':'' ?>">7 Hari</a>
      <a href="laporan.php?periode=14" class="chip-filter <?= $periode==='14' ?'aktif':'' ?>">14 Hari</a>
      <a href="laporan.php?periode=30" class="chip-filter <?= $periode==='30' ?'aktif':'' ?>">30 Hari</a>
      <a href="laporan.php?periode=custom&dari=<?= $dari ?>&sampai=<?= $sampai ?>"
         class="chip-filter <?= $periode==='custom' ?'aktif':'' ?>">Custom</a>
    </div>
    <?php if ($periode === 'custom'): ?>
    <form method="GET" action="laporan.php" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
      <input type="hidden" name="periode" value="custom">
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--tekssamar);display:block;margin-bottom:4px;">DARI</label>
        <input type="date" name="dari" value="<?= $dari ?>"
               style="padding:8px 12px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;">
      </div>
      <div>
        <label style="font-size:11px;font-weight:700;color:var(--tekssamar);display:block;margin-bottom:4px;">SAMPAI</label>
        <input type="date" name="sampai" value="<?= $sampai ?>"
               style="padding:8px 12px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;">
      </div>
      <button type="submit" class="tombolutama" style="align-self:flex-end;">
        <i class="fa-solid fa-filter"></i> Terapkan
      </button>
    </form>
    <?php endif; ?>
  </div>

  <!-- ringkasan statistik -->
  <div class="grid-stat">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-receipt"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $totalorder ?></div>
        <div class="label">Total Pesanan</div>
        <div class="sub"><?= $labelterpilih ?></div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-coins"></i></div>
      <div class="isi-stat">
        <div class="nilai" style="font-size:15px;"><?= singkat($totalomset) ?></div>
        <div class="label">Total Omset</div>
        <div class="sub">Selesai + Dibatalkan</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-check-circle"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $statpesanan['Selesai'] ?? 0 ?></div>
        <div class="label">Pesanan Selesai</div>
        <div class="sub"><?= $statpesanan['Dibatalkan'] ?? 0 ?> dibatalkan</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-user-plus"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $userbarujml ?></div>
        <div class="label">Pengguna Baru</div>
        <div class="sub"><?= $labelterpilih ?></div>
      </div>
    </div>
  </div>

  <!-- chart revenue -->
  <div class="kartu">
    <h3><i class="fa-solid fa-chart-bar"></i> Revenue Platform — <?= $labelterpilih ?></h3>
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
  </div>

  <div class="grid-dua">

    <!-- top produk platform -->
    <div class="kartu">
      <h3><i class="fa-solid fa-fire"></i> Produk Terlaris Platform</h3>
      <?php if (empty($terlaris)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada data penjualan pada periode ini</p></div>
      <?php else: ?>
      <?php $medal = ['emas','perak','perunggu']; ?>
      <?php foreach ($terlaris as $i => $t): ?>
      <div class="baris-produk">
        <div class="rangking-produk <?= $medal[$i] ?? '' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= htmlspecialchars($t['nama_menu']) ?>
          </div>
          <div style="font-size:11px;color:var(--tekssamar);">
            <?= htmlspecialchars($t['nama_toko']) ?> · <?= rp($t['omset']) ?>
          </div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;">
          <?= $t['terjual'] ?> terjual
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- breakdown status pesanan -->
    <div class="kartu">
      <h3><i class="fa-solid fa-list-check"></i> Breakdown Status Pesanan</h3>
      <?php
      $statuslist = ['Menunggu','Diproses','Siap Diambil','Selesai','Dibatalkan'];
      $statbadge  = ['Menunggu'=>'menunggu','Diproses'=>'diproses','Siap Diambil'=>'siap','Selesai'=>'selesai','Dibatalkan'=>'dibatalkan'];
      ?>
      <?php foreach ($statuslist as $s): ?>
      <?php $jml = $statpesanan[$s] ?? 0; ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--latar);">
        <span class="badge <?= $statbadge[$s] ?>"><?= $s ?></span>
        <strong style="font-size:15px;white-space:nowrap;"><?= $jml ?> pesanan</strong>
      </div>
      <?php endforeach; ?>
    </div>

  </div>

  <!-- performa toko — total omset selesai dan dibatalkan -->
  <div class="kartu">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <h3 style="margin:0;border:none;padding:0;"><i class="fa-solid fa-store"></i> Performa Toko</h3>
    </div>
    <div class="tabel-wrapper">
      <table style="min-width:600px;">
        <thead>
          <tr>
            <th>Nama Toko</th>
            <th class="tengah">Status</th>
            <th class="tengah">Total Pesanan</th>
            <th class="tengah">Dibatalkan</th>
            <th class="tengah">Rating</th>
            <th class="kanan">Total Omset</th>
            <th class="tengah takprint">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($perftoko)): ?>
          <tr><td colspan="7"><div class="kosong" style="padding:20px;"><p>Belum ada toko</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($perftoko as $t): ?>
          <tr>
            <td><strong><?= htmlspecialchars($t['nama_toko']) ?></strong></td>
            <td class="tengah">
              <span class="badge <?= $t['status_toko'] === 'buka' ? 'buka' : 'tutup' ?>">
                <?= $t['status_toko'] === 'buka' ? 'Buka' : 'Tutup' ?>
              </span>
            </td>
            <td class="tengah"><?= $t['total_order'] ?></td>
            <td class="tengah" style="color:var(--gagal);"><?= (int)$t['jml_dibatalkan'] ?></td>
            <td class="tengah"><?= $t['rating'] > 0 ? $t['rating'].' ★' : '—' ?></td>
            <td class="kanan" style="font-weight:700;color:var(--sukses);"><?= rp($t['pendapatan']) ?></td>
            <td class="tengah takprint">
              <a href="../manajementoko/viewtoko.php?id=<?= $t['id_toko'] ?>" class="tombol-aksi" title="Detail">
                <i class="fa-solid fa-eye"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <?php if (!empty($perftoko)): ?>
        <tfoot>
          <tr>
            <td colspan="5"><strong>TOTAL PLATFORM</strong></td>
            <td class="kanan" style="color:var(--sukses);"><strong><?= rp(array_sum(array_column($perftoko,'pendapatan'))) ?></strong></td>
            <td class="takprint"></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>

</main>
</body>
</html>
