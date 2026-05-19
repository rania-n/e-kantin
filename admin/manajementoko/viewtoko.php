<?php
/* ============================================================
   DETAIL TOKO — ADMIN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';
$halamansaatini = 'toko';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: ../../admin/manajemenpengguna/user.php?role=penjual"); exit; }

$qt = $conn->prepare("SELECT t.*, u.username, u.email, u.id_user AS id_pemilik
                      FROM tb_toko t JOIN tb_user u ON t.id_user=u.id_user
                      WHERE t.id_toko=? AND t.deleted=0");
$qt->bind_param("i", $id); $qt->execute();
$toko = $qt->get_result()->fetch_assoc(); $qt->close();
if (!$toko) { header("Location: toko.php"); exit; }

// Statistik
$qs1 = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_toko=? AND deleted=0");
$qs1->bind_param("i", $id); $qs1->execute();
$r1 = $qs1->get_result()->fetch_row(); $qs1->close();
$totalpesanan = (int)$r1[0]; $totalomset = (float)$r1[1];

$qs2 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_toko=? AND status_order='Selesai' AND deleted=0");
$qs2->bind_param("i", $id); $qs2->execute();
$pendapatan = (float)$qs2->get_result()->fetch_row()[0]; $qs2->close();

$qs3 = $conn->prepare("SELECT ROUND(AVG(rating_toko),1), COUNT(*) FROM tb_rating WHERE id_toko=? AND deleted=0");
$qs3->bind_param("i", $id); $qs3->execute();
$r3 = $qs3->get_result()->fetch_row(); $qs3->close();
$rating = (float)($r3[0] ?? 0); $jmlrating = (int)($r3[1] ?? 0);

$qs4 = $conn->prepare("SELECT COUNT(*) FROM tb_menu WHERE id_toko=? AND status='aktif' AND deleted=0");
$qs4->bind_param("i", $id); $qs4->execute();
$totalmenu = (int)$qs4->get_result()->fetch_row()[0]; $qs4->close();

// Chart dengan filter periode
$hari = (int)($_GET['hari'] ?? 7);
if (!in_array($hari, [7, 14, 30])) $hari = 7;

$chartdata = [];
for ($i = $hari - 1; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $qc  = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_toko=? AND DATE(tanggal_order)=? AND status_order='Selesai' AND deleted=0");
    $qc->bind_param("is", $id, $tgl); $qc->execute();
    $nilai = (float)$qc->get_result()->fetch_row()[0]; $qc->close();
    $label = $hari <= 14 ? date('D', strtotime($tgl)) : date('d/m', strtotime($tgl));
    $chartdata[] = ['tgl'=>$tgl,'label'=>$label,'nilai'=>$nilai];
}
$maxnilai = max(array_column($chartdata,'nilai')) ?: 1;

// Top produk
$qtl = $conn->prepare("SELECT m.nama_menu, SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omset
                        FROM tb_detail_order d
                        JOIN tb_menu m ON d.id_menu=m.id_menu
                        JOIN tb_order o ON d.id_order=o.id_order
                        WHERE m.id_toko=? AND d.deleted=0 AND o.deleted=0
                          AND o.status_order != 'Dibatalkan'
                        GROUP BY m.id_menu, m.nama_menu
                        ORDER BY terjual DESC LIMIT 5");
$qtl->bind_param("i", $id); $qtl->execute();
$terlaris = $qtl->get_result()->fetch_all(MYSQLI_ASSOC); $qtl->close();

function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
function singkat(float $n): string {
    if ($n >= 1_000_000_000) { $v=$n/1_000_000_000; return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' M'; }
    if ($n >= 1_000_000)     { $v=$n/1_000_000;     return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' Jt'; }
    return 'Rp ' . number_format($n, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Toko - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-store"></i> <?= htmlspecialchars($toko['nama_toko']) ?></h1>
      <p>Detail &amp; performa toko — pemilik: <?= htmlspecialchars($toko['username']) ?></p>
    </div>
    <div style="display:flex;gap:8px;">
      <a href="edittoko.php?id=<?= $id ?>" class="tombolutama">
        <i class="fa-solid fa-pen"></i> Edit
      </a>
      <a href="hapustoko.php?id=<?= $id ?>" class="tombolbahaya">
        <i class="fa-solid fa-trash"></i> Hapus
      </a>
      <a href="toko.php" class="tombolringan">
        <i class="fa-solid fa-arrow-left"></i> Kembali
      </a>
    </div>
  </div>

  <!-- Statistik -->
  <div class="grid-stat">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-receipt"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $totalpesanan ?></div>
        <div class="label">Total Pesanan</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-coins"></i></div>
      <div class="isi-stat">
        <div class="nilai" style="font-size:15px;"><?= rp($pendapatan) ?></div>
        <div class="label">Pendapatan Selesai</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-star"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $rating ?: '—' ?></div>
        <div class="label">Rating</div>
        <div class="sub"><?= $jmlrating ?> ulasan</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-utensils"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $totalmenu ?></div>
        <div class="label">Menu Aktif</div>
      </div>
    </div>
  </div>

  <div class="grid-dua">

    <!-- Info Toko -->
    <div class="kartu">
      <h3><i class="fa-solid fa-circle-info"></i> Informasi Toko</h3>
      <div class="baris-info">
        <div class="label-info">Nama Toko</div>
        <div class="nilai-info"><?= htmlspecialchars($toko['nama_toko']) ?></div>
      </div>
      <div class="baris-info">
        <div class="label-info">Pemilik</div>
        <div class="nilai-info">
          <a href="../manajemenpengguna/viewuser.php?id=<?= $toko['id_pemilik'] ?>"
             style="color:var(--kedua);font-weight:700;">
            <?= htmlspecialchars($toko['username']) ?>
          </a>
        </div>
      </div>
      <div class="baris-info">
        <div class="label-info">Email</div>
        <div class="nilai-info"><?= htmlspecialchars($toko['email']) ?></div>
      </div>
      <div class="baris-info">
        <div class="label-info">Status</div>
        <div class="nilai-info">
          <span class="badge <?= $toko['status_toko'] === 'buka' ? 'buka' : 'tutup' ?>">
            <?= $toko['status_toko'] === 'buka' ? 'Buka' : 'Tutup' ?>
          </span>
        </div>
      </div>
      <div class="baris-info">
        <div class="label-info">Total Omset</div>
        <div class="nilai-info" style="font-weight:800;color:var(--utama);"><?= rp($totalomset) ?></div>
      </div>
    </div>

    <!-- Top Produk -->
    <div class="kartu">
      <h3><i class="fa-solid fa-fire"></i> Produk Terlaris</h3>
      <?php if (empty($terlaris)): ?>
      <div class="kosong" style="padding:20px;">
        <p>Belum ada data penjualan</p>
      </div>
      <?php else: ?>
      <?php $medal = ['emas','perak','perunggu']; ?>
      <?php foreach ($terlaris as $i => $t): ?>
      <div class="baris-produk">
        <div class="rangking-produk <?= $medal[$i] ?? '' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= htmlspecialchars($t['nama_menu']) ?>
          </div>
          <div style="font-size:11px;color:var(--tekssamar);"><?= rp($t['omset']) ?></div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);"><?= $t['terjual'] ?> terjual</div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

  <!-- Chart Revenue -->
  <div class="kartu">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <h3 style="margin:0;border:none;padding:0;">
        <i class="fa-solid fa-chart-bar"></i> Revenue <?= $hari ?> Hari Terakhir
      </h3>
      <div class="filter-bar" style="margin:0;gap:6px;">
        <a href="viewtoko.php?id=<?= $id ?>&hari=7"  class="chip-filter <?= $hari===7  ? 'aktif':'' ?>">7 Hari</a>
        <a href="viewtoko.php?id=<?= $id ?>&hari=14" class="chip-filter <?= $hari===14 ? 'aktif':'' ?>">14 Hari</a>
        <a href="viewtoko.php?id=<?= $id ?>&hari=30" class="chip-filter <?= $hari===30 ? 'aktif':'' ?>">30 Hari</a>
      </div>
    </div>
    <div class="area-chart">
      <?php
        $n2     = count($chartdata);
        $svgw2  = max(700, $n2 * 30);
        $barw2  = max(8, min(40, (int)(($svgw2 - 80) / $n2) - 4));
        $gap2   = max(4, (int)(($svgw2 - 80 - $n2 * $barw2) / max(1, $n2 - 1)));
        $startx2 = 70; $chartH2 = 160;
      ?>
      <svg viewBox="0 0 <?=$svgw2?> 210" xmlns="http://www.w3.org/2000/svg" style="min-width:<?=min(700,$svgw2)?>px;">
        <?php for($g=0;$g<=4;$g++):$y=20+($g*40);?>
        <line x1="60" y1="<?=$y?>" x2="<?=$svgw2-10?>" y2="<?=$y?>" stroke="#E7CBCB" stroke-width="1" stroke-dasharray="4,4"/>
        <text x="55" y="<?=$y+4?>" text-anchor="end" fill="#99627A" font-size="9"><?=singkat(($maxnilai/4)*(4-$g))?></text>
        <?php endfor;?>
        <?php foreach($chartdata as $i=>$d):
          $x=$startx2+$i*($barw2+$gap2);
          $barh=$d['nilai']>0?($d['nilai']/$maxnilai)*$chartH2:2;
          $by=180-$barh;
          $isToday=$d['tgl']===date('Y-m-d');
        ?>
        <rect x="<?=$x?>" y="<?=$by?>" width="<?=$barw2?>" height="<?=$barh?>" rx="3" fill="<?=$isToday?'#643843':'#99627A'?>">
          <title><?=$d['label']?> — <?=rp($d['nilai'])?></title>
        </rect>
        <text x="<?=$x+$barw2/2?>" y="200" text-anchor="middle" fill="<?=$isToday?'#643843':'#99627A'?>"
              font-size="<?=$hari>14?'8':'10'?>" font-weight="<?=$isToday?'700':'400'?>"><?=$d['label']?></text>
        <?php if($d['nilai']>0&&$barw2>=20):?>
        <text x="<?=$x+$barw2/2?>" y="<?=max($by-4,14)?>" text-anchor="middle" fill="#643843" font-size="8" font-weight="600">
          <?=number_format($d['nilai']/1000,0)?>k
        </text>
        <?php endif;?>
        <?php endforeach;?>
      </svg>
    </div>
  </div>


</main>
</body>
</html>
