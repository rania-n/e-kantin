<?php
/* halaman detail toko — admin bisa melihat informasi lengkap toko,
   statistik (pesanan, pendapatan, rating, jumlah menu aktif),
   chart revenue harian dengan filter periode, dan daftar produk terlaris. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tandai menu "toko" sebagai aktif di navbar
$halamansaatini = 'toko';

// ambil id toko dari url, konversi ke integer
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// jika id tidak valid, kembalikan ke manajemen pengguna (penjual)
if (!$id) { header("Location: ../../admin/manajemenpengguna/user.php?role=penjual"); exit; }

// ambil data toko beserta informasi pemiliknya
// LEFT JOIN ke tb_user karena kantin bisa kosong (id_user NULL)
$qt = $conn->prepare("SELECT t.*, u.username, u.email, u.id_user AS id_pemilik
                      FROM tb_toko t
                      LEFT JOIN tb_user u ON t.id_user=u.id_user AND u.deleted=0
                      WHERE t.id_toko=? AND t.deleted=0");
$qt->bind_param("i", $id); $qt->execute();
$toko = $qt->get_result()->fetch_assoc(); $qt->close();

// jika toko tidak ditemukan, kembali ke daftar kantin
if (!$toko) { header("Location: kantin.php"); exit; }

// hitung total pesanan masuk ke toko ini dan total uangnya (semua status)
$qs1 = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_toko=? AND deleted=0");
$qs1->bind_param("i", $id); $qs1->execute();
$r1 = $qs1->get_result()->fetch_row(); $qs1->close();
$totalpesanan = (int)$r1[0]; $totalomset = (float)$r1[1];

// hitung pendapatan bersih: hanya dari pesanan yang sudah selesai
$qs2 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_toko=? AND status_order='Selesai' AND deleted=0");
$qs2->bind_param("i", $id); $qs2->execute();
$pendapatan = (float)$qs2->get_result()->fetch_row()[0]; $qs2->close();

// hitung rata-rata rating toko dan jumlah ulasan dari pembeli
$qs3 = $conn->prepare("SELECT ROUND(AVG(rating_toko),1), COUNT(*) FROM tb_rating WHERE id_toko=? AND deleted=0");
$qs3->bind_param("i", $id); $qs3->execute();
$r3 = $qs3->get_result()->fetch_row(); $qs3->close();
$rating = (float)($r3[0] ?? 0); $jmlrating = (int)($r3[1] ?? 0);

// hitung jumlah menu aktif yang dimiliki toko ini
$qs4 = $conn->prepare("SELECT COUNT(*) FROM tb_menu WHERE id_toko=? AND status='aktif' AND deleted=0");
$qs4->bind_param("i", $id); $qs4->execute();
$totalmenu = (int)$qs4->get_result()->fetch_row()[0]; $qs4->close();

// baca pilihan periode chart dari url, validasi agar hanya 7/14/30 yang diterima
$hari = (int)($_GET['hari'] ?? 7);
if (!in_array($hari, [7, 14, 30])) $hari = 7;

// bangun data chart: hitung revenue per hari dalam rentang periode yang dipilih
$chartdata = [];
for ($i = $hari - 1; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    // query per hari (satu query per iterasi) — hanya pesanan selesai yang dihitung
    $qc  = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_toko=? AND DATE(tanggal_order)=? AND status_order='Selesai' AND deleted=0");
    $qc->bind_param("is", $id, $tgl); $qc->execute();
    $nilai = (float)$qc->get_result()->fetch_row()[0]; $qc->close();
    // format label: hari <= 14 gunakan nama hari (Mon/Tue), lebih dari itu gunakan dd/mm
    $label = $hari <= 14 ? date('D', strtotime($tgl)) : date('d/m', strtotime($tgl));
    $chartdata[] = ['tgl'=>$tgl,'label'=>$label,'nilai'=>$nilai];
}

// nilai tertinggi untuk skala chart
$maxnilai = max(array_column($chartdata,'nilai')) ?: 1;

// ambil 5 produk terlaris toko ini (kecuali pesanan dibatalkan)
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

// fungsi pembantu: format angka ke rupiah lengkap
function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }

// fungsi pembantu: singkatkan nilai rupiah besar
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
      <!-- tampilkan nomor kantin sebagai identitas fisik, lalu nama toko -->
      <h1>
        <i class="fa-solid fa-store"></i>
        Kantin ke-<?= (int)($toko['nomor_kantin'] ?? '?') ?>
        <?php if (!empty($toko['nama_toko'])): ?>
        — <?= htmlspecialchars($toko['nama_toko']) ?>
        <?php else: ?>
        <span style="font-weight:400;color:var(--tekssamar);font-size:16px;">(Kosong)</span>
        <?php endif; ?>
      </h1>
      <p>
        Detail &amp; performa kantin
        <?php if (!empty($toko['username'])): ?>
        — pemilik: <?= htmlspecialchars($toko['username']) ?>
        <?php else: ?>
        — belum ada penjual
        <?php endif; ?>
      </p>
    </div>
    <div style="display:flex;gap:8px;">
      <a href="edittoko.php?id=<?= $id ?>" class="tombolutama">
        <i class="fa-solid fa-pen"></i> Edit
      </a>
      <?php if (!empty($toko['id_user'])): ?>
      <!-- tombol kosongkan kantin hanya tampil jika kantin terisi -->
      <a href="hapustoko.php?id=<?= $id ?>" class="tombolbahaya">
        <i class="fa-solid fa-store-slash"></i> Kosongkan
      </a>
      <?php endif; ?>
      <a href="kantin.php" class="tombolringan">
        <i class="fa-solid fa-arrow-left"></i> Kembali
      </a>
    </div>
  </div>

  <!-- kartu statistik toko: pesanan, pendapatan, rating, menu aktif -->
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

    <!-- informasi dasar toko dan link ke profil pemilik -->
    <div class="kartu">
      <h3><i class="fa-solid fa-circle-info"></i> Informasi Toko</h3>
      <!-- nomor kantin ditampilkan sebagai identitas fisik lokasi -->
      <div class="baris-info">
        <div class="label-info">Nomor Kantin</div>
        <div class="nilai-info" style="font-weight:800;font-size:18px;color:var(--utama);">
          Kantin ke-<?= (int)($toko['nomor_kantin'] ?? '?') ?>
        </div>
      </div>
      <div class="baris-info">
        <div class="label-info">Nama Toko</div>
        <div class="nilai-info">
          <?= !empty($toko['nama_toko']) ? htmlspecialchars($toko['nama_toko']) : '<em style="color:var(--tekssamar);">Belum ada nama (kantin kosong)</em>' ?>
        </div>
      </div>
      <div class="baris-info">
        <div class="label-info">Pemilik</div>
        <div class="nilai-info">
          <?php if (!empty($toko['id_pemilik'])): ?>
          <!-- link ke halaman detail pengguna pemilik toko -->
          <a href="../manajemenpengguna/viewuser.php?id=<?= $toko['id_pemilik'] ?>"
             style="color:var(--kedua);font-weight:700;">
            <?= htmlspecialchars($toko['username'] ?? '—') ?>
          </a>
          <?php else: ?>
          <em style="color:var(--tekssamar);">Kosong — belum ada penjual</em>
          <?php endif; ?>
        </div>
      </div>
      <div class="baris-info">
        <div class="label-info">Email</div>
        <div class="nilai-info">
          <?= !empty($toko['email']) ? htmlspecialchars($toko['email']) : '<em style="color:var(--tekssamar);">—</em>' ?>
        </div>
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
        <!-- total omset termasuk pesanan dari semua status -->
        <div class="nilai-info" style="font-weight:800;color:var(--utama);"><?= rp($totalomset) ?></div>
      </div>
    </div>

    <!-- daftar 5 produk terlaris toko ini -->
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

  <!-- chart revenue toko per hari dengan pilihan periode -->
  <div class="kartu">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <h3 style="margin:0;border:none;padding:0;">
        <i class="fa-solid fa-chart-bar"></i> Revenue <?= $hari ?> Hari Terakhir
      </h3>
      <!-- tab filter periode chart -->
      <div class="filter-bar" style="margin:0;gap:6px;">
        <a href="viewtoko.php?id=<?= $id ?>&hari=7"  class="chip-filter <?= $hari===7  ? 'aktif':'' ?>">7 Hari</a>
        <a href="viewtoko.php?id=<?= $id ?>&hari=14" class="chip-filter <?= $hari===14 ? 'aktif':'' ?>">14 Hari</a>
        <a href="viewtoko.php?id=<?= $id ?>&hari=30" class="chip-filter <?= $hari===30 ? 'aktif':'' ?>">30 Hari</a>
      </div>
    </div>
    <div class="area-chart">
      <?php
        // hitung dimensi svg chart sesuai jumlah data
        $n2     = count($chartdata);
        $svgw2  = max(700, $n2 * 30);
        $barw2  = max(8, min(40, (int)(($svgw2 - 80) / $n2) - 4));
        $gap2   = max(4, (int)(($svgw2 - 80 - $n2 * $barw2) / max(1, $n2 - 1)));
        $startx2 = 70; $chartH2 = 160;
      ?>
      <svg viewBox="0 0 <?=$svgw2?> 210" xmlns="http://www.w3.org/2000/svg" style="min-width:<?=min(700,$svgw2)?>px;">
        <?php for($g=0;$g<=4;$g++):$y=20+($g*40);?>
        <!-- garis skala horizontal -->
        <line x1="60" y1="<?=$y?>" x2="<?=$svgw2-10?>" y2="<?=$y?>" stroke="#E7CBCB" stroke-width="1" stroke-dasharray="4,4"/>
        <text x="55" y="<?=$y+4?>" text-anchor="end" fill="#99627A" font-size="9"><?=singkat(($maxnilai/4)*(4-$g))?></text>
        <?php endfor;?>
        <?php foreach($chartdata as $i=>$d):
          // posisi dan tinggi tiap bar
          $x=$startx2+$i*($barw2+$gap2);
          $barh=$d['nilai']>0?($d['nilai']/$maxnilai)*$chartH2:2;
          $by=180-$barh;
          $isToday=$d['tgl']===date('Y-m-d'); // hari ini diberi warna lebih gelap
        ?>
        <rect x="<?=$x?>" y="<?=$by?>" width="<?=$barw2?>" height="<?=$barh?>" rx="3" fill="<?=$isToday?'#643843':'#99627A'?>">
          <title><?=$d['label']?> — <?=rp($d['nilai'])?></title>
        </rect>
        <!-- label hari, ukuran font dikecilkan jika periode panjang -->
        <text x="<?=$x+$barw2/2?>" y="200" text-anchor="middle" fill="<?=$isToday?'#643843':'#99627A'?>"
              font-size="<?=$hari>14?'8':'10'?>" font-weight="<?=$isToday?'700':'400'?>"><?=$d['label']?></text>
        <?php if($d['nilai']>0&&$barw2>=20):?>
        <!-- nilai singkat di atas bar, hanya jika bar cukup lebar -->
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
