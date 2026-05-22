<?php
/* halaman laporan platform untuk admin.
   menampilkan ringkasan pesanan, omset, pengguna baru, chart harian,
   produk terlaris, breakdown status pesanan, dan performa tiap toko.
   mendukung filter periode: 7/14/30 hari atau custom tanggal.
   mendukung filter per kantin (nomor_kantin) atau semua kantin.
   mendukung cetak: ?cetak=1 untuk seluruh data, ?cetak=kantin&nomor=X untuk satu kantin. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tandai menu "laporan" sebagai aktif di navbar
$halamansaatini = 'laporan';

// cek apakah migrasi nomor_kantin sudah dijalankan di phpMyAdmin
$cekkolom = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'nomor_kantin'");
$migrasiSudah = ($cekkolom && $cekkolom->num_rows > 0);

// baca mode cetak dari url:
// cetak=1 → cetak semua laporan
// cetak=kantin → cetak laporan satu kantin saja (perlu parameter nomor=X)
$cetakmode    = $_GET['cetak'] ?? ''; // '' | '1' | 'kantin'
$cetaknomor   = (int)($_GET['nomor'] ?? 0); // nomor_kantin yang dicetak (jika mode=kantin)
$cetakglobal  = ($cetakmode === '1');
$cetakperkant = ($cetakmode === 'kantin' && $cetaknomor > 0);
$sedangcetak  = $cetakglobal || $cetakperkant;

// baca pilihan periode dari url, default 7 hari jika tidak ada atau tidak valid
$periode = $_GET['periode'] ?? '7';
if (!in_array($periode, ['7','14','30','custom'])) $periode = '7';

// tentukan tanggal mulai dan tanggal akhir sesuai pilihan periode
if ($periode === 'custom') {
    // jika custom, baca dari parameter url ?dari= dan ?sampai=
    $dari   = isset($_GET['dari'])   && $_GET['dari']   ? $_GET['dari']   : date('Y-m-d', strtotime('-7 days'));
    $sampai = isset($_GET['sampai']) && $_GET['sampai'] ? $_GET['sampai'] : date('Y-m-d');
    $tglmulai = date('Y-m-d', strtotime($dari));
    $tgljin   = date('Y-m-d', strtotime($sampai));
    // pastikan tanggal akhir tidak lebih awal dari tanggal mulai
    if ($tgljin < $tglmulai) $tgljin = $tglmulai;
} else {
    // hitung tanggal mulai berdasarkan jumlah hari ke belakang dari hari ini
    $tglmulai = date('Y-m-d', strtotime("-$periode days"));
    $tgljin   = date('Y-m-d');
    $dari = $tglmulai; $sampai = $tgljin;
}

// hitung total pesanan dalam periode
$qr1 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE DATE(tanggal_order) BETWEEN ? AND ? AND deleted=0");
$qr1->bind_param("ss", $tglmulai, $tgljin); $qr1->execute();
$totalorder = (int)$qr1->get_result()->fetch_row()[0]; $qr1->close();

// hitung total omset (pesanan selesai + dibatalkan) dalam periode
$qr2 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE DATE(tanggal_order) BETWEEN ? AND ? AND status_order IN ('Selesai','Dibatalkan') AND deleted=0");
$qr2->bind_param("ss", $tglmulai, $tgljin); $qr2->execute();
$totalomset = (float)$qr2->get_result()->fetch_row()[0]; $qr2->close();

// hitung jumlah pengguna baru yang daftar dalam periode
$qr3 = $conn->prepare("SELECT COUNT(*) FROM tb_user WHERE DATE(created) BETWEEN ? AND ? AND deleted=0");
$qr3->bind_param("ss", $tglmulai, $tgljin); $qr3->execute();
$userbarujml = (int)$qr3->get_result()->fetch_row()[0]; $qr3->close();

// ambil jumlah pesanan per status dalam periode
$qstat = $conn->prepare("SELECT status_order, COUNT(*) AS jml FROM tb_order WHERE DATE(tanggal_order) BETWEEN ? AND ? AND deleted=0 GROUP BY status_order");
$qstat->bind_param("ss", $tglmulai, $tgljin); $qstat->execute();
$statpesanan = []; $res = $qstat->get_result();
while ($rs = $res->fetch_assoc()) $statpesanan[$rs['status_order']] = (int)$rs['jml'];
$qstat->close();

/* ambil performa setiap kantin dalam periode.
   SEMUA kantin ditampilkan, termasuk yang kosong (id_user IS NULL).
   kolom nomor_kantin hanya diikutsertakan jika migrasi sudah jalan.
   left join ke tb_user untuk mendapatkan nama penjual (NULL jika kosong). */
$kolomNomor  = $migrasiSudah ? "t.nomor_kantin,"          : "NULL AS nomor_kantin,";
$groupNomor  = $migrasiSudah ? ", t.nomor_kantin"          : "";
$orderKolom  = $migrasiSudah ? "t.nomor_kantin ASC"       : "t.id_toko ASC";
$qtoko = $conn->prepare(
    "SELECT t.id_toko, $kolomNomor t.nama_toko, t.status_toko,
            t.id_user, u.username AS nama_penjual,
            COUNT(DISTINCT o.id_order) AS total_order,
            COALESCE(SUM(CASE WHEN o.status_order='Dibatalkan' THEN 1 ELSE 0 END),0) AS jml_dibatalkan,
            COALESCE(SUM(CASE WHEN o.status_order IN ('Selesai','Dibatalkan') THEN o.total_harga ELSE 0 END),0) AS pendapatan,
            (SELECT COALESCE(ROUND(AVG(r.rating_toko),1),0) FROM tb_rating r WHERE r.id_toko=t.id_toko AND r.deleted=0) AS rating
     FROM tb_toko t
     LEFT JOIN tb_user u ON t.id_user=u.id_user AND u.deleted=0
     LEFT JOIN tb_order o ON t.id_toko=o.id_toko
       AND DATE(o.tanggal_order) BETWEEN ? AND ? AND o.deleted=0
     WHERE t.deleted=0
     GROUP BY t.id_toko, t.nama_toko, t.status_toko, t.id_user, u.username $groupNomor
     ORDER BY $orderKolom"
);
$qtoko->bind_param("ss", $tglmulai, $tgljin); $qtoko->execute();
$perftoko = $qtoko->get_result()->fetch_all(MYSQLI_ASSOC); $qtoko->close();

// ambil 10 produk terlaris di seluruh platform dalam periode
// kolom nomor_kantin hanya dipilih jika migrasi sudah berjalan
$kolomNomorTl = $migrasiSudah ? "t.nomor_kantin," : "NULL AS nomor_kantin,";
$groupNomorTl = $migrasiSudah ? ", t.nomor_kantin" : "";
$qtl = $conn->prepare(
    "SELECT m.nama_menu, t.nama_toko, $kolomNomorTl
            SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omset
     FROM tb_detail_order d
     JOIN tb_menu m ON d.id_menu=m.id_menu
     JOIN tb_toko t ON m.id_toko=t.id_toko
     JOIN tb_order o ON d.id_order=o.id_order
     WHERE DATE(o.tanggal_order) BETWEEN ? AND ?
       AND d.deleted=0 AND o.deleted=0 AND o.status_order != 'Dibatalkan'
     GROUP BY m.id_menu, m.nama_menu, t.nama_toko $groupNomorTl
     ORDER BY terjual DESC LIMIT 10"
);
$qtl->bind_param("ss", $tglmulai, $tgljin); $qtl->execute();
$terlaris = $qtl->get_result()->fetch_all(MYSQLI_ASSOC); $qtl->close();

/* fungsi pembantu: mengubah kode hari bahasa inggris menjadi bahasa indonesia */
function namahari(string $tgl): string {
    $map = ['Sun'=>'Min','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab'];
    return $map[date('D', strtotime($tgl))] ?? date('D', strtotime($tgl));
}

// ambil total revenue per hari dalam periode
$qchart = $conn->prepare("SELECT DATE(tanggal_order) AS tgl, COALESCE(SUM(total_harga),0) AS nilai FROM tb_order WHERE DATE(tanggal_order) BETWEEN ? AND ? AND status_order IN ('Selesai','Dibatalkan') AND deleted=0 GROUP BY DATE(tanggal_order)");
$qchart->bind_param("ss", $tglmulai, $tgljin); $qchart->execute();
$rawchart = [];
$resc = $qchart->get_result();
while ($row = $resc->fetch_assoc()) $rawchart[$row['tgl']] = (float)$row['nilai'];
$qchart->close();

// hitung jumlah hari antara tanggal mulai dan akhir (inklusif)
$selisih = (int)ceil((strtotime($tgljin) - strtotime($tglmulai)) / 86400) + 1;

// bangun array chart lengkap per hari, tanggal tanpa data diisi 0
$chartdata = [];
for ($i = 0; $i < $selisih; $i++) {
    $tgl = date('Y-m-d', strtotime($tglmulai) + $i * 86400);
    $chartdata[] = ['tgl'=>$tgl,'label'=>namahari($tgl).' '.date('d',strtotime($tgl)),'nilai'=>$rawchart[$tgl]??0.0];
}

// cari nilai maksimum untuk skala chart (minimal 1 agar tidak dibagi 0)
$maxnilai = max(array_column($chartdata,'nilai')) ?: 1;

// fungsi pembantu: format angka menjadi rupiah lengkap
function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }

// fungsi pembantu: singkatkan nilai rupiah besar
function singkat(float $n): string {
    if ($n >= 1_000_000_000) { $v=$n/1_000_000_000; return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' M'; }
    if ($n >= 1_000_000)     { $v=$n/1_000_000;     return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' Jt'; }
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// mapping label tombol filter periode
$labelperiode = ['7'=>'7 Hari','14'=>'14 Hari','30'=>'30 Hari','custom'=>'Custom'];

// buat label judul yang menampilkan rentang tanggal yang dipilih
$labelterpilih = $periode === 'custom'
    ? date('d M Y', strtotime($tglmulai)) . ' – ' . date('d M Y', strtotime($tgljin))
    : $labelperiode[$periode] . ' Terakhir';

// hitung dimensi svg chart agar proporsional dengan jumlah data
$n      = count($chartdata);
$svgw   = max(700, $n * 30);
$barw   = max(8, min(40, (int)(($svgw - 80) / $n) - 4));
$gap    = max(4, (int)(($svgw - 80 - $n * $barw) / max(1, $n - 1)));
$startx = 70; $chartH = 160;

// buat string parameter url saat ini untuk dipertahankan di link cetak
$urlparams = http_build_query(array_filter([
    'periode' => $periode,
    'dari'    => ($periode==='custom') ? $dari : '',
    'sampai'  => ($periode==='custom') ? $sampai : '',
]));
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
/* setting ukuran kertas saat dicetak */
@media print { @page { size: A4; margin: 12mm; } }

/* tombol cetak kecil di pojok kanan header tiap kartu */
.btn-cetak-mini {
  background:none; border:1px solid var(--garis); border-radius:6px;
  padding:4px 9px; cursor:pointer; color:var(--tekssamar);
  font-size:11px; display:inline-flex; align-items:center; gap:4px;
  font-family:inherit;
}
.btn-cetak-mini:hover { background:var(--utama); color:white; border-color:var(--utama); }
@media print { .btn-cetak-mini { display:none !important; } }

/* tombol cetak per seksi — tampil di setiap baris tabel -->
.btn-cetak-satu {
    font-size:10px;
    padding:4px 10px;
    border:1px solid var(--garis);
    border-radius:6px;
    background:var(--latar);
    color:var(--teks);
    cursor:pointer;
    white-space:nowrap;
    text-decoration:none;
    display:inline-block;
}
.btn-cetak-satu:hover { background:var(--utama);color:white;border-color:var(--utama); }

/* kotak cetak per kantin — dipakai oleh halaman cetak satu kantin */
.seksi-kantin-cetak {
    border: 1px solid var(--garis);
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 18px;
    break-inside: avoid;
}

/* judul yang hanya muncul saat dicetak */
.judulcetak {
    display: none;
    text-align: center;
    margin-bottom: 20px;
}
@media print {
    .judulcetak { display: block !important; }
    .takprint   { display: none !important; }
    .seksi-kantin-cetak { break-inside: avoid; border: 1px solid #ccc; }
}
</style>
</head>
<body>

<?php if (!$sedangcetak): ?>
<?php include '../../3. komponen/navbaradmin.php'; ?>
<?php endif; ?>

<main class="konten">

  <!-- header halaman dengan tombol-tombol cetak -->
  <div class="header-halaman takprint">
    <div class="kiri">
      <h1><i class="fa-solid fa-chart-bar"></i> Laporan Platform</h1>
      <p>Ringkasan performa jajankita — <?= $labelterpilih ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <!-- tombol cetak SEMUA laporan — JS diizinkan untuk print -->
      <button onclick="window.print()" class="tombolringan takprint">
        <i class="fa-solid fa-print"></i> Cetak Semua
      </button>
      <!-- link ke halaman cetak per-kantin (membuka jendela baru untuk print) -->
      <a href="laporan.php?cetak=kantin&nomor=0&<?= $urlparams ?>"
         onclick="window.open(this.href,'_blank','width=900,height=700');return false;"
         class="tombolringan takprint">
        <i class="fa-solid fa-store"></i> Cetak Per Kantin
      </a>
    </div>
  </div>

  <!-- judul yang muncul di versi cetak -->
  <div class="judulcetak">
    <h2>Laporan Platform jajankita</h2>
    <p>Periode: <?= $labelterpilih ?> | Dicetak: <?= date('d M Y H:i') ?></p>
  </div>

  <!-- filter periode: chip untuk memilih 7/14/30 hari atau custom -->
  <div class="takprint" style="margin-bottom:18px;">
    <div class="filter-bar" style="margin-bottom:10px;">
      <a href="laporan.php?periode=7"  class="chip-filter <?= $periode==='7'  ?'aktif':'' ?>">7 Hari</a>
      <a href="laporan.php?periode=14" class="chip-filter <?= $periode==='14' ?'aktif':'' ?>">14 Hari</a>
      <a href="laporan.php?periode=30" class="chip-filter <?= $periode==='30' ?'aktif':'' ?>">30 Hari</a>
      <a href="laporan.php?periode=custom&dari=<?= $dari ?>&sampai=<?= $sampai ?>"
         class="chip-filter <?= $periode==='custom' ?'aktif':'' ?>">Custom</a>
    </div>
    <?php if ($periode === 'custom'): ?>
    <!-- form input tanggal custom -->
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

  <!-- ringkasan statistik utama -->
  <div style="display:flex;justify-content:flex-end;margin-bottom:6px;" class="takprint">
    <button onclick="cetakBagian('seksi-statistik','Ringkasan Statistik — <?= addslashes($labelterpilih) ?>')" class="btn-cetak-mini">
      <i class="fa-solid fa-print"></i> Cetak Ringkasan
    </button>
  </div>
  <div class="grid-stat" id="seksi-statistik">
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

  <!-- chart revenue harian dalam periode yang dipilih -->
  <div class="kartu" id="seksi-chart">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <h3 style="margin:0;border:none;padding:0;"><i class="fa-solid fa-chart-bar"></i> Revenue Platform — <?= $labelterpilih ?></h3>
      <button onclick="cetakBagian('seksi-chart','Revenue Platform — <?= addslashes($labelterpilih) ?>')" class="btn-cetak-mini takprint">
        <i class="fa-solid fa-print"></i> Cetak
      </button>
    </div>
    <div class="area-chart">
      <svg viewBox="0 0 <?=$svgw?> 210" xmlns="http://www.w3.org/2000/svg" style="min-width:<?=min(700,$svgw)?>px;">
        <?php for($g=0;$g<=4;$g++):$y=20+($g*40);?>
        <!-- garis skala horizontal -->
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

    <!-- daftar 10 produk terlaris -->
    <div class="kartu" id="seksi-terlaris">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <h3 style="margin:0;border:none;padding:0;"><i class="fa-solid fa-fire"></i> Produk Terlaris Platform</h3>
        <button onclick="cetakBagian('seksi-terlaris','Produk Terlaris Platform')" class="btn-cetak-mini takprint">
          <i class="fa-solid fa-print"></i> Cetak
        </button>
      </div>
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
            <!-- tampilkan nama toko dan nomor kantin sebagai lokasi -->
            <?= htmlspecialchars($t['nama_toko']) ?>
            <?php if (!empty($t['nomor_kantin'])): ?>
            <span style="color:var(--garis);">·</span> Kantin ke-<?= (int)$t['nomor_kantin'] ?>
            <?php endif; ?>
            · <?= rp($t['omset']) ?>
          </div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;">
          <?= $t['terjual'] ?> terjual
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- rincian jumlah pesanan per status -->
    <div class="kartu" id="seksi-status">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <h3 style="margin:0;border:none;padding:0;"><i class="fa-solid fa-list-check"></i> Breakdown Status Pesanan</h3>
        <button onclick="cetakBagian('seksi-status','Breakdown Status Pesanan')" class="btn-cetak-mini takprint">
          <i class="fa-solid fa-print"></i> Cetak
        </button>
      </div>
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

  <!-- ================================================================
       TABEL PERFORMA SEMUA KANTIN (termasuk yang kosong)
       Bisa dicetak seluruhnya atau per satu kantin
  ================================================================ -->
  <div class="kartu">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <h3 style="margin:0;border:none;padding:0;">
        <i class="fa-solid fa-store"></i> Performa Per Kantin
        <span style="font-size:12px;font-weight:500;color:var(--tekssamar);">— <?= $labelterpilih ?></span>
      </h3>
      <!-- tombol cetak semua kantin (disembunyikan saat print) -->
      <a href="laporan.php?cetak=1&<?= $urlparams ?>"
         onclick="var w=window.open(this.href,'_blank','width=900,height=700');return false;"
         class="tombolringan takprint" style="font-size:12px;">
        <i class="fa-solid fa-print"></i> Cetak Tabel Ini
      </a>
    </div>
    <div class="tabel-wrapper">
      <table style="min-width:680px;">
        <thead>
          <tr>
            <!-- kolom nomor kantin sebagai identitas fisik -->
            <th class="tengah">No. Kantin</th>
            <th>Nama Toko / Pemilik</th>
            <th class="tengah">Status</th>
            <th class="tengah">Total Pesanan</th>
            <th class="tengah">Dibatalkan</th>
            <th class="tengah">Rating</th>
            <th class="kanan">Total Omset</th>
            <!-- kolom aksi dan cetak disembunyikan saat print -->
            <th class="tengah takprint">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($perftoko)): ?>
          <tr><td colspan="8"><div class="kosong" style="padding:20px;"><p>Belum ada kantin</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($perftoko as $t): ?>
          <tr>
            <!-- nomor kantin besar dan menonjol -->
            <td class="tengah">
              <strong style="font-size:18px;color:var(--utama);"><?= (int)$t['nomor_kantin'] ?></strong>
            </td>
            <td>
              <?php if (!empty($t['nama_toko'])): ?>
              <!-- kantin terisi: tampilkan nama toko dan nama penjual -->
              <strong><?= htmlspecialchars($t['nama_toko']) ?></strong>
              <div style="font-size:11px;color:var(--tekssamar);">
                <i class="fa-solid fa-user" style="font-size:9px;"></i>
                <?= htmlspecialchars($t['nama_penjual'] ?? '—') ?>
              </div>
              <?php else: ?>
              <!-- kantin kosong: tampilkan placeholder -->
              <em style="color:var(--tekssamar);font-size:13px;">— Kosong —</em>
              <?php endif; ?>
            </td>
            <td class="tengah">
              <?php if (empty($t['id_user'])): ?>
              <!-- badge kosong untuk kantin tanpa penjual -->
              <span class="badge" style="background:#f5f5f5;color:#9e9e9e;">Kosong</span>
              <?php else: ?>
              <span class="badge <?= $t['status_toko'] === 'buka' ? 'buka' : 'tutup' ?>">
                <?= $t['status_toko'] === 'buka' ? 'Buka' : 'Tutup' ?>
              </span>
              <?php endif; ?>
            </td>
            <td class="tengah"><?= (int)$t['total_order'] ?></td>
            <td class="tengah" style="color:var(--gagal);"><?= (int)$t['jml_dibatalkan'] ?></td>
            <td class="tengah"><?= $t['rating'] > 0 ? $t['rating'].' ★' : '—' ?></td>
            <td class="kanan" style="font-weight:700;color:var(--sukses);"><?= rp($t['pendapatan']) ?></td>
            <td class="tengah takprint">
              <div style="display:flex;gap:4px;justify-content:center;flex-wrap:wrap;">
                <!-- tombol lihat detail toko -->
                <a href="../manajementoko/viewtoko.php?id=<?= $t['id_toko'] ?>" class="tombol-aksi" title="Detail">
                  <i class="fa-solid fa-eye"></i>
                </a>
                <!-- tombol cetak satu baris kantin ini saja -->
                <!-- link ini membuka jendela baru khusus print satu kantin -->
                <a href="laporan.php?cetak=kantin&nomor=<?= (int)$t['nomor_kantin'] ?>&<?= $urlparams ?>"
                   onclick="window.open(this.href,'_blank','width=800,height=600');return false;"
                   class="tombol-aksi" title="Cetak kantin ini" style="background:var(--info);color:white;border-color:var(--info);">
                  <i class="fa-solid fa-print"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <?php if (!empty($perftoko)): ?>
        <!-- baris total di bawah tabel -->
        <tfoot>
          <tr>
            <td colspan="6"><strong>TOTAL SELURUH KANTIN</strong></td>
            <td class="kanan" style="color:var(--sukses);"><strong><?= rp(array_sum(array_column($perftoko,'pendapatan'))) ?></strong></td>
            <td class="takprint"></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>

  <?php
  /* ================================================================
     SEKSI CETAK PER KANTIN
     Ditampilkan hanya jika mode cetak = 'kantin' (buka di jendela baru)
     Setiap kantin ditampilkan dalam kotak terpisah yang bisa di-print
  ================================================================ */
  if ($cetakperkant || $cetakglobal):
  ?>
  <!-- judul cetak -->
  <div class="judulcetak" style="display:block;margin-top:30px;padding-top:20px;border-top:2px solid var(--garis);">
    <h2>Laporan Per Kantin</h2>
    <p>Periode: <?= $labelterpilih ?> | Dicetak: <?= date('d M Y H:i') ?></p>
  </div>

  <?php foreach ($perftoko as $t):
    // jika mode cetak satu kantin, skip yang tidak sesuai
    if ($cetakperkant && (int)$t['nomor_kantin'] !== $cetaknomor) continue;
  ?>
  <!-- kotak data satu kantin — masing-masing bisa di-print terpisah -->
  <div class="seksi-kantin-cetak">
    <!-- judul kantin: nomor dan nama -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
      <div style="font-size:28px;font-weight:900;color:var(--utama);line-height:1;">
        <?= (int)$t['nomor_kantin'] ?>
      </div>
      <div>
        <div style="font-size:15px;font-weight:800;">
          <?= !empty($t['nama_toko']) ? htmlspecialchars($t['nama_toko']) : '— Kosong —' ?>
        </div>
        <div style="font-size:12px;color:var(--tekssamar);">
          Kantin ke-<?= (int)$t['nomor_kantin'] ?>
          <?php if (!empty($t['nama_penjual'])): ?>
          · Penjual: <?= htmlspecialchars($t['nama_penjual']) ?>
          <?php endif; ?>
        </div>
      </div>
      <div style="margin-left:auto;">
        <?php if (empty($t['id_user'])): ?>
        <span class="badge" style="background:#f5f5f5;color:#9e9e9e;">Kosong</span>
        <?php else: ?>
        <span class="badge <?= $t['status_toko']==='buka'?'buka':'tutup' ?>">
          <?= $t['status_toko']==='buka'?'Buka':'Tutup' ?>
        </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- data statistik kantin dalam satu periode -->
    <table style="width:100%;font-size:13px;border-collapse:collapse;">
      <tr style="border-bottom:1px solid var(--latar);">
        <td style="padding:6px 0;color:var(--tekssamar);">Total Pesanan</td>
        <td style="padding:6px 0;font-weight:700;text-align:right;"><?= (int)$t['total_order'] ?></td>
      </tr>
      <tr style="border-bottom:1px solid var(--latar);">
        <td style="padding:6px 0;color:var(--tekssamar);">Pesanan Dibatalkan</td>
        <td style="padding:6px 0;font-weight:700;text-align:right;color:var(--gagal);"><?= (int)$t['jml_dibatalkan'] ?></td>
      </tr>
      <tr style="border-bottom:1px solid var(--latar);">
        <td style="padding:6px 0;color:var(--tekssamar);">Rating</td>
        <td style="padding:6px 0;font-weight:700;text-align:right;">
          <?= $t['rating'] > 0 ? $t['rating'] . ' ★' : '—' ?>
        </td>
      </tr>
      <tr>
        <td style="padding:6px 0;color:var(--tekssamar);">Total Omset</td>
        <td style="padding:6px 0;font-weight:800;text-align:right;color:var(--sukses);font-size:15px;">
          <?= rp($t['pendapatan']) ?>
        </td>
      </tr>
    </table>

    <!-- tombol cetak kantin ini saja (hanya tampil di layar, tidak saat print) -->
    <div class="takprint" style="margin-top:12px;text-align:right;">
      <button onclick="window.print()" class="tombolringan" style="font-size:12px;">
        <i class="fa-solid fa-print"></i> Cetak Kantin ke-<?= (int)$t['nomor_kantin'] ?>
      </button>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if ($cetakglobal): ?>
  <!-- total semua kantin — hanya di mode cetak global -->
  <div class="seksi-kantin-cetak" style="border-color:var(--utama);">
    <strong style="font-size:14px;">TOTAL SELURUH KANTIN</strong>
    <div style="font-size:18px;font-weight:900;color:var(--sukses);margin-top:8px;">
      <?= rp(array_sum(array_column($perftoko,'pendapatan'))) ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- tombol cetak halaman ini (hanya di mode cetak) -->
  <div class="takprint" style="text-align:center;margin-top:20px;">
    <button onclick="window.print()" class="tombolutama">
      <i class="fa-solid fa-print"></i>
      <?= $cetakperkant ? "Cetak Kantin ke-{$cetaknomor}" : 'Cetak Semua Kantin' ?>
    </button>
  </div>
  <?php endif; ?>

</main>

<!-- fungsi JS untuk cetak satu seksi — diizinkan karena hanya untuk keperluan print -->
<script>
function cetakBagian(id, judul) {
    var el = document.getElementById(id);
    if (!el) return;

    var tgl = new Date().toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'});
    var jam = new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});

    var css = [
        ':root{--utama:#643843;--kedua:#99627A;--latar:#EFD9D4;--putihbg:#F8EBF1;--putih:#FFFFFF;--garis:#E7CBCB;--teks:#3D2C33;--tekssamar:#8B6475;--sukses:#2e7d32;--gagal:#c62828;}',
        'body{font-family:Poppins,"Segoe UI",sans-serif;padding:20px;color:#3D2C33;font-size:13px;}',
        '.header-cetak{display:flex;justify-content:space-between;align-items:center;padding-bottom:10px;border-bottom:2px solid #643843;margin-bottom:18px;}',
        '.header-cetak h2{font-size:16px;font-weight:800;color:#643843;margin:0;}',
        '.header-cetak span{font-size:11px;color:#99627A;}',
        '.baris-produk{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #EFD9D4;}',
        '.rangking-produk{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;background:#E7CBCB;color:#643843;}',
        '.rangking-produk.emas{background:#FFF9C4;color:#F57F17;}',
        '.rangking-produk.perak{background:#F5F5F5;color:#616161;}',
        '.rangking-produk.perunggu{background:#FBE9E7;color:#BF360C;}',
        '.badge{display:inline-block;padding:3px 8px;border-radius:20px;font-size:11px;font-weight:700;}',
        '.badge.menunggu{background:#FFF8E1;color:#F57F17;}',
        '.badge.diproses{background:#E3F2FD;color:#1565C0;}',
        '.badge.siap{background:#E8F5E9;color:#2E7D32;}',
        '.badge.selesai{background:#E8F5E9;color:#1B5E20;}',
        '.badge.dibatalkan{background:#FFEBEE;color:#C62828;}',
        '.kartu-stat{display:inline-block;border:1px solid #E7CBCB;border-radius:12px;padding:14px 18px;margin:6px;min-width:160px;}',
        '.grid-stat{display:flex;flex-wrap:wrap;gap:10px;}',
        '.nilai{font-size:24px;font-weight:800;color:#643843;}',
        '.label{font-size:13px;font-weight:600;}',
        '.sub{font-size:11px;color:#99627A;}',
        '.area-chart{overflow-x:auto;}',
        'table{width:100%;border-collapse:collapse;}',
        'th,td{padding:8px 10px;text-align:left;border-bottom:1px solid #E7CBCB;font-size:12px;}',
        'th{font-size:11px;font-weight:700;color:#99627A;text-transform:uppercase;}',
        '.tengah{text-align:center;} .kanan{text-align:right;}',
        '.btn-cetak-mini,.takprint{display:none!important;}',
        '@media print{@page{size:A4;margin:12mm;} .btn-cetak-mini,.takprint{display:none!important;}}',
    ].join('');

    var fa = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css';

    var w = window.open('', '_blank', 'width=820,height=680');
    w.document.write(
        '<!DOCTYPE html><html lang="id"><head>'
        + '<meta charset="UTF-8">'
        + '<title>' + judul + ' — jajankita</title>'
        + '<link rel="stylesheet" href="' + fa + '">'
        + '<style>' + css + '</style>'
        + '</head><body>'
        + '<div class="header-cetak">'
        + '  <h2>' + judul + '</h2>'
        + '  <span>jajankita &mdash; ' + tgl + ', ' + jam + '</span>'
        + '</div>'
        + el.innerHTML
        + '<script>window.onload=function(){window.print();};<\/script>'
        + '</body></html>'
    );
    w.document.close();
}
</script>
</body>
</html>
