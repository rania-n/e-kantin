<?php
/* ============================================================
   LAPORAN PLATFORM — ADMIN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';
$halamansaatini = 'laporan';

// Filter periode (default: 30 hari)
$periode = $_GET['periode'] ?? '30';
if (!in_array($periode, ['7','30','90','365'])) $periode = '30';
$tglmulai = date('Y-m-d', strtotime("-$periode days"));

// Ringkasan platform
$qr1 = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total_harga),0) FROM tb_order WHERE DATE(tanggal_order)>=? AND deleted=0");
$qr1->bind_param("s", $tglmulai); $qr1->execute();
$r1 = $qr1->get_result()->fetch_row(); $qr1->close();
$totalorder = (int)$r1[0]; $totalomset = (float)$r1[1];

$qr2 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE DATE(tanggal_order)>=? AND status_order='Selesai' AND deleted=0");
$qr2->bind_param("s", $tglmulai); $qr2->execute();
$revenueselesai = (float)$qr2->get_result()->fetch_row()[0]; $qr2->close();

$qr3 = $conn->prepare("SELECT COUNT(*) FROM tb_user WHERE DATE(created)>=? AND deleted=0");
$qr3->bind_param("s", $tglmulai); $qr3->execute();
$userbarujml = (int)$qr3->get_result()->fetch_row()[0]; $qr3->close();

// Status pesanan breakdown
$qstat = $conn->prepare("SELECT status_order, COUNT(*) AS jml FROM tb_order WHERE DATE(tanggal_order)>=? AND deleted=0 GROUP BY status_order");
$qstat->bind_param("s", $tglmulai); $qstat->execute();
$statpesanan = []; $res = $qstat->get_result();
while ($rs = $res->fetch_assoc()) $statpesanan[$rs['status_order']] = (int)$rs['jml'];
$qstat->close();

// Performa toko di periode ini
$qtoko = $conn->prepare("SELECT t.id_toko, t.nama_toko, t.status_toko,
                                 COUNT(DISTINCT o.id_order) AS total_order,
                                 COALESCE(SUM(o.total_harga),0) AS omset,
                                 COALESCE(SUM(CASE WHEN o.status_order='Selesai' THEN o.total_harga ELSE 0 END),0) AS pendapatan
                          FROM tb_toko t
                          LEFT JOIN tb_order o ON t.id_toko=o.id_toko AND DATE(o.tanggal_order)>=? AND o.deleted=0
                          WHERE t.deleted=0
                          GROUP BY t.id_toko, t.nama_toko, t.status_toko
                          ORDER BY omset DESC");
$qtoko->bind_param("s", $tglmulai); $qtoko->execute();
$perftoko = $qtoko->get_result()->fetch_all(MYSQLI_ASSOC); $qtoko->close();

// Top produk platform
$qtl = $conn->prepare("SELECT m.nama_menu, t.nama_toko, SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omset
                        FROM tb_detail_order d
                        JOIN tb_menu m ON d.id_menu=m.id_menu
                        JOIN tb_toko t ON m.id_toko=t.id_toko
                        JOIN tb_order o ON d.id_order=o.id_order
                        WHERE DATE(o.tanggal_order)>=? AND d.deleted=0 AND o.deleted=0
                          AND o.status_order != 'Dibatalkan'
                        GROUP BY m.id_menu, m.nama_menu, t.nama_toko
                        ORDER BY terjual DESC LIMIT 10");
$qtl->bind_param("s", $tglmulai); $qtl->execute();
$terlaris = $qtl->get_result()->fetch_all(MYSQLI_ASSOC); $qtl->close();

// Chart harian (sesuai periode, max 30 titik)
$chartdata = [];
$langkah = $periode <= 30 ? 1 : (int)ceil($periode / 30);
for ($i = $periode - 1; $i >= 0; $i -= $langkah) {
    $tgl   = date('Y-m-d', strtotime("-$i days"));
    $tglak = date('Y-m-d', strtotime("-" . max(0, $i - $langkah + 1) . " days"));
    $qc    = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE DATE(tanggal_order) BETWEEN ? AND ? AND status_order='Selesai' AND deleted=0");
    $qc->bind_param("ss", $tgl, $tglak); $qc->execute();
    $nilai = (float)$qc->get_result()->fetch_row()[0]; $qc->close();
    $chartdata[] = ['tgl'=>$tgl,'label'=>date($periode<=30?'d/m':'W',$i===0?time():strtotime($tgl)),'nilai'=>$nilai];
}
$maxnilai = max(array_column($chartdata,'nilai')) ?: 1;

function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
function singkat(float $n): string {
    if ($n >= 1_000_000_000) { $v=$n/1_000_000_000; return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' M'; }
    if ($n >= 1_000_000)     { $v=$n/1_000_000;     return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' Jt'; }
    return 'Rp ' . number_format($n, 0, ',', '.');
}
$labelperiode = ['7'=>'7 Hari','30'=>'30 Hari','90'=>'3 Bulan','365'=>'1 Tahun'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Platform - Admin eKantin</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-chart-bar"></i> Laporan Platform</h1>
      <p>Ringkasan performa eKantin — <?= $labelperiode[$periode] ?> terakhir</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a href="eksporlaporan.php?periode=<?= $periode ?>" class="tombolringan">
        <i class="fa-solid fa-file-csv"></i> Ekspor CSV
      </a>
      <button onclick="window.print()" class="tombolringan takprint">
        <i class="fa-solid fa-print"></i> Cetak
      </button>
    </div>
  </div>

  <!-- Filter Periode -->
  <div class="filter-bar takprint">
    <?php foreach (['7'=>'7 Hari','30'=>'30 Hari','90'=>'3 Bulan','365'=>'1 Tahun'] as $val=>$lab): ?>
    <a href="laporan.php?periode=<?= $val ?>" class="chip-filter <?= $periode === $val ? 'aktif' : '' ?>">
      <?= $lab ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Ringkasan -->
  <div class="grid-stat">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-receipt"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $totalorder ?></div>
        <div class="label">Total Pesanan</div>
        <div class="sub">Periode <?= $labelperiode[$periode] ?></div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-coins"></i></div>
      <div class="isi-stat">
        <div class="nilai" style="font-size:15px;"><?= rp($revenueselesai) ?></div>
        <div class="label">Revenue Selesai</div>
        <div class="sub">Dari total <?= rp($totalomset) ?></div>
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
        <div class="sub">Periode <?= $labelperiode[$periode] ?></div>
      </div>
    </div>
  </div>

  <!-- Chart Revenue -->
  <div class="kartu">
    <h3><i class="fa-solid fa-chart-bar"></i> Revenue Platform — <?= $labelperiode[$periode] ?> Terakhir</h3>
    <div class="area-chart">
      <?php
      $n      = count($chartdata);
      $svgw   = max(700, $n * 30);
      $barw   = max(8, min(40, (int)(($svgw - 80) / $n) - 4));
      $gap    = max(4, (int)(($svgw - 80 - $n * $barw) / max(1, $n - 1)));
      $startx = 70; $chartH = 160;
      ?>
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
              font-size="<?=$periode>30?'8':'9'?>" font-weight="<?=$isToday?'700':'400'?>"><?=$d['label']?></text>
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

    <!-- Top Produk Platform -->
    <div class="kartu">
      <h3><i class="fa-solid fa-fire"></i> Produk Terlaris Platform</h3>
      <?php if (empty($terlaris)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada data penjualan</p></div>
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

    <!-- Status Pesanan -->
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

  <!-- Performa Toko -->
  <div class="kartu">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <h3 style="margin:0;border:none;padding:0;"><i class="fa-solid fa-store"></i> Performa Toko</h3>
    </div>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th>Nama Toko</th>
            <th class="tengah">Status</th>
            <th class="tengah">Total Pesanan</th>
            <th class="kanan" title="Semua pesanan masuk termasuk yang dibatalkan">Total Omset ⓘ</th>
            <th class="kanan" title="Hanya pesanan dengan status Selesai">Pendapatan Selesai ⓘ</th>
            <th class="tengah takprint">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($perftoko as $t): ?>
          <tr>
            <td><strong><?= htmlspecialchars($t['nama_toko']) ?></strong></td>
            <td class="tengah">
              <span class="badge <?= $t['status_toko'] === 'buka' ? 'buka' : 'tutup' ?>">
                <?= $t['status_toko'] === 'buka' ? 'Buka' : 'Tutup' ?>
              </span>
            </td>
            <td class="tengah"><?= $t['total_order'] ?></td>
            <td class="kanan"><?= rp($t['omset']) ?></td>
            <td class="kanan" style="font-weight:700;color:var(--sukses);"><?= rp($t['pendapatan']) ?></td>
            <td class="tengah takprint">
              <a href="../manajementoko/viewtoko.php?id=<?= $t['id_toko'] ?>" class="tombol-aksi" title="Detail">
                <i class="fa-solid fa-eye"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3"><strong>TOTAL</strong></td>
            <td class="kanan"><strong><?= rp(array_sum(array_column($perftoko,'omset'))) ?></strong></td>
            <td class="kanan" style="color:var(--sukses);"><strong><?= rp(array_sum(array_column($perftoko,'pendapatan'))) ?></strong></td>
            <td class="takprint"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

</main>
</body>
</html>
