<?php
/* laporan platform — filter: periode (7/14/30/custom) + kantin (semua atau satu).
   mode semua  → label "Revenue", data platform-wide.
   mode kantin → label "Omset",   data satu toko + menu & ulasan.
   cetak laporan: window.print() (semua). cetak per-kotak: cetakSeksi(id). */

include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

$halamansaatini = 'laporan';

$cekkolom    = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'nomor_kantin'");
$migrasiSudah = ($cekkolom && $cekkolom->num_rows > 0);

// ── FILTER PERIODE ─────────────────────────────────────────────────────────────
$periode = $_GET['periode'] ?? '7';
if (!in_array($periode, ['7','14','30','custom'])) $periode = '7';

if ($periode === 'custom') {
    $dari   = (isset($_GET['dari'])   && $_GET['dari'])   ? $_GET['dari']   : date('Y-m-d', strtotime('-7 days'));
    $sampai = (isset($_GET['sampai']) && $_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');
    $tglmulai = date('Y-m-d', strtotime($dari));
    $tgljin   = date('Y-m-d', strtotime($sampai));
    if ($tgljin < $tglmulai) $tgljin = $tglmulai;
} else {
    $tglmulai = date('Y-m-d', strtotime("-$periode days"));
    $tgljin   = date('Y-m-d');
    $dari = $tglmulai; $sampai = $tgljin;
}

// ── FILTER KANTIN ──────────────────────────────────────────────────────────────
$nomorkantin = (int)($_GET['kantin'] ?? 0);

$kolomNomor = $migrasiSudah ? "t.nomor_kantin," : "NULL AS nomor_kantin,";
$groupNomor = $migrasiSudah ? ", t.nomor_kantin" : "";
$orderKolom = $migrasiSudah ? "t.nomor_kantin ASC" : "t.id_toko ASC";

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

// resolve nomor_kantin → id_toko
$idtokoterpilih   = 0;
$infokantinterpilih = null;
if ($nomorkantin > 0) {
    foreach ($perftoko as $t) {
        if ((int)$t['nomor_kantin'] === $nomorkantin) {
            $idtokoterpilih     = (int)$t['id_toko'];
            $infokantinterpilih = $t;
            break;
        }
    }
    if (!$idtokoterpilih) $nomorkantin = 0;
}

$modeSemua  = ($idtokoterpilih === 0);
$judulOmset = $modeSemua ? 'Revenue' : 'Omset';

$tokoW  = $idtokoterpilih > 0 ? " AND o.id_toko={$idtokoterpilih}"  : "";
$tokoWt = $idtokoterpilih > 0 ? " AND id_toko={$idtokoterpilih}"    : "";

// ── STATISTIK UTAMA ────────────────────────────────────────────────────────────
$qr1 = $conn->prepare("SELECT COUNT(*) FROM tb_order o WHERE DATE(o.tanggal_order) BETWEEN ? AND ?{$tokoW} AND o.deleted=0");
$qr1->bind_param("ss", $tglmulai, $tgljin); $qr1->execute();
$totalorder = (int)$qr1->get_result()->fetch_row()[0]; $qr1->close();

$qr2 = $conn->prepare("SELECT COALESCE(SUM(o.total_harga),0) FROM tb_order o WHERE DATE(o.tanggal_order) BETWEEN ? AND ? AND o.status_order IN ('Selesai','Dibatalkan'){$tokoW} AND o.deleted=0");
$qr2->bind_param("ss", $tglmulai, $tgljin); $qr2->execute();
$totalomset = (float)$qr2->get_result()->fetch_row()[0]; $qr2->close();

$qstat = $conn->prepare("SELECT status_order, COUNT(*) AS jml FROM tb_order o WHERE DATE(o.tanggal_order) BETWEEN ? AND ?{$tokoW} AND o.deleted=0 GROUP BY status_order");
$qstat->bind_param("ss", $tglmulai, $tgljin); $qstat->execute();
$statpesanan = []; $res = $qstat->get_result();
while ($rs = $res->fetch_assoc()) $statpesanan[$rs['status_order']] = (int)$rs['jml'];
$qstat->close();

if ($modeSemua) {
    $qr3 = $conn->prepare("SELECT COUNT(*) FROM tb_user WHERE DATE(created) BETWEEN ? AND ? AND deleted=0");
    $qr3->bind_param("ss", $tglmulai, $tgljin); $qr3->execute();
    $userbarujml = (int)$qr3->get_result()->fetch_row()[0]; $qr3->close();
} else {
    $userbarujml = 0;
}

$ratingkantin = 0;
if (!$modeSemua) {
    $qrat = $conn->prepare("SELECT COALESCE(ROUND(AVG(rating_toko),1),0), COUNT(*) FROM tb_rating WHERE id_toko=? AND deleted=0");
    $qrat->bind_param("i", $idtokoterpilih); $qrat->execute();
    $rr = $qrat->get_result()->fetch_row(); $qrat->close();
    $ratingkantin = (float)($rr[0]??0); $jmlratingkantin = (int)($rr[1]??0);
} else {
    $jmlratingkantin = 0;
}

// ── CHART ──────────────────────────────────────────────────────────────────────
$qchart = $conn->prepare("SELECT DATE(o.tanggal_order) AS tgl, COALESCE(SUM(o.total_harga),0) AS nilai FROM tb_order o WHERE DATE(o.tanggal_order) BETWEEN ? AND ? AND o.status_order IN ('Selesai','Dibatalkan'){$tokoW} AND o.deleted=0 GROUP BY DATE(o.tanggal_order)");
$qchart->bind_param("ss", $tglmulai, $tgljin); $qchart->execute();
$rawchart = []; $resc = $qchart->get_result();
while ($row = $resc->fetch_assoc()) $rawchart[$row['tgl']] = (float)$row['nilai'];
$qchart->close();

function namahari(string $tgl): string {
    $map = ['Sun'=>'Min','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab'];
    return $map[date('D', strtotime($tgl))] ?? date('D', strtotime($tgl));
}

$selisih  = (int)ceil((strtotime($tgljin) - strtotime($tglmulai)) / 86400) + 1;
$chartdata = [];
for ($i = 0; $i < $selisih; $i++) {
    $tgl = date('Y-m-d', strtotime($tglmulai) + $i * 86400);
    $chartdata[] = ['tgl'=>$tgl,'label'=>namahari($tgl).' '.date('d',strtotime($tgl)),'nilai'=>$rawchart[$tgl]??0.0];
}
$maxnilai = max(array_column($chartdata,'nilai')) ?: 1;

// ── PRODUK TERLARIS ────────────────────────────────────────────────────────────
$kolomNomorTl = $migrasiSudah ? "t.nomor_kantin," : "NULL AS nomor_kantin,";
$groupNomorTl = $migrasiSudah ? ", t.nomor_kantin" : "";
$tokoJoinTl   = $idtokoterpilih > 0 ? " AND t.id_toko={$idtokoterpilih}" : "";
$qtl = $conn->prepare(
    "SELECT m.nama_menu, t.nama_toko, $kolomNomorTl
            SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omset
     FROM tb_detail_order d
     JOIN tb_menu m ON d.id_menu=m.id_menu
     JOIN tb_toko t ON m.id_toko=t.id_toko{$tokoJoinTl}
     JOIN tb_order o ON d.id_order=o.id_order
     WHERE DATE(o.tanggal_order) BETWEEN ? AND ?
       AND d.deleted=0 AND o.deleted=0 AND o.status_order != 'Dibatalkan'
     GROUP BY m.id_menu, m.nama_menu, t.nama_toko $groupNomorTl
     ORDER BY terjual DESC LIMIT 10"
);
$qtl->bind_param("ss", $tglmulai, $tgljin); $qtl->execute();
$terlaris = $qtl->get_result()->fetch_all(MYSQLI_ASSOC); $qtl->close();

// ── PELANGGAN ──────────────────────────────────────────────────────────────────
$qpelbanyak = $conn->prepare(
    "SELECT u.username, COUNT(o.id_order) AS jml_order, COALESCE(SUM(o.total_harga),0) AS total_belanja
     FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user
     WHERE DATE(o.tanggal_order) BETWEEN ? AND ?{$tokoW}
       AND o.status_order='Selesai' AND o.deleted=0
     GROUP BY o.id_user, u.username ORDER BY jml_order DESC LIMIT 5"
);
$qpelbanyak->bind_param("ss", $tglmulai, $tgljin); $qpelbanyak->execute();
$topbelibanyak = $qpelbanyak->get_result()->fetch_all(MYSQLI_ASSOC); $qpelbanyak->close();

$qpelmahal = $conn->prepare(
    "SELECT u.username, COUNT(o.id_order) AS jml_order, COALESCE(SUM(o.total_harga),0) AS total_belanja
     FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user
     WHERE DATE(o.tanggal_order) BETWEEN ? AND ?{$tokoW}
       AND o.status_order='Selesai' AND o.deleted=0
     GROUP BY o.id_user, u.username ORDER BY total_belanja DESC LIMIT 5"
);
$qpelmahal->bind_param("ss", $tglmulai, $tgljin); $qpelmahal->execute();
$topbelimahal = $qpelmahal->get_result()->fetch_all(MYSQLI_ASSOC); $qpelmahal->close();

// ── MENU + RATING + ULASAN (per-kantin mode) ───────────────────────────────────
$daftarmenu = []; $distribusirating = []; $ulasankantin = [];
if (!$modeSemua && $idtokoterpilih > 0) {
    $qmenu = $conn->prepare(
        "SELECT m.nama_menu, m.harga, m.status,
                COALESCE(SUM(CASE WHEN o.status_order!='Dibatalkan' THEN d.jumlah ELSE 0 END),0) AS terjual
         FROM tb_menu m
         LEFT JOIN tb_detail_order d ON m.id_menu=d.id_menu AND d.deleted=0
         LEFT JOIN tb_order o ON d.id_order=o.id_order AND o.deleted=0
           AND DATE(o.tanggal_order) BETWEEN ? AND ?
         WHERE m.id_toko=? AND m.deleted=0
         GROUP BY m.id_menu, m.nama_menu, m.harga, m.status
         ORDER BY (m.status='aktif') DESC, terjual DESC, m.nama_menu ASC"
    );
    $qmenu->bind_param("ssi", $tglmulai, $tgljin, $idtokoterpilih); $qmenu->execute();
    $daftarmenu = $qmenu->get_result()->fetch_all(MYSQLI_ASSOC); $qmenu->close();

    $qdist = $conn->prepare("SELECT rating_toko, COUNT(*) AS jml FROM tb_rating WHERE id_toko=? AND deleted=0 GROUP BY rating_toko ORDER BY rating_toko DESC");
    $qdist->bind_param("i", $idtokoterpilih); $qdist->execute(); $resd = $qdist->get_result();
    while ($r = $resd->fetch_assoc()) $distribusirating[(int)$r['rating_toko']] = (int)$r['jml'];
    $qdist->close();

    $qul = $conn->prepare(
        "SELECT r.rating_toko, r.ulasan, r.created, u.username
         FROM tb_rating r JOIN tb_user u ON r.id_user=u.id_user
         WHERE r.id_toko=? AND r.deleted=0
         ORDER BY r.created DESC LIMIT 5"
    );
    $qul->bind_param("i", $idtokoterpilih); $qul->execute();
    $ulasankantin = $qul->get_result()->fetch_all(MYSQLI_ASSOC); $qul->close();
}

// ── HELPER ─────────────────────────────────────────────────────────────────────
function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
function singkat(float $n): string {
    if ($n >= 1_000_000_000) { $v=$n/1_000_000_000; return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' M'; }
    if ($n >= 1_000_000)     { $v=$n/1_000_000;     return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' Jt'; }
    return 'Rp ' . number_format($n, 0, ',', '.');
}
function bintanghtml(float $r): string {
    $out = '';
    for ($i=1;$i<=5;$i++) { $w=$i<=$r?'#F59E0B':'#D1D5DB'; $out.="<i class='fa-solid fa-star' style='color:{$w};font-size:11px;'></i>"; }
    return $out;
}

// ── LABELS & CHART DIMS ────────────────────────────────────────────────────────
$labelperiode  = ['7'=>'7 Hari','14'=>'14 Hari','30'=>'30 Hari','custom'=>'Custom'];
$labelterpilih = $periode==='custom'
    ? date('d M Y', strtotime($tglmulai)).' – '.date('d M Y', strtotime($tgljin))
    : $labelperiode[$periode].' Terakhir';
$labelkantin = $nomorkantin > 0
    ? 'Kantin ke-'.$nomorkantin.(!empty($infokantinterpilih['nama_toko']) ? ' — '.htmlspecialchars($infokantinterpilih['nama_toko']) : '')
    : 'Semua Kantin';

$n    = count($chartdata);
$svgw = max(700, $n * 30);
$barw = max(8, min(40, (int)(($svgw - 80) / $n) - 4));
$gap  = max(4, (int)(($svgw - 80 - $n * $barw) / max(1, $n - 1)));
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
#kartu-cetak-kantin { display:none; }
.bar-dist { height:8px;background:#f0f0f0;border-radius:4px;overflow:hidden;flex:1; }
.bar-dist-isi { height:100%;background:var(--kedua);border-radius:4px; }
.seksi-judul { display:flex;align-items:center;justify-content:space-between;margin-bottom:14px; }
.seksi-judul h3 { margin:0; }
.cetakjudul { display:none; }

@media print {
  .cetakjudul { display:block !important; margin-bottom:14px; font-family:sans-serif; }
  @page { size:A4 landscape; margin:10mm; }
  .takprint       { display:none !important; }
  .sembunyi-cetak { display:none !important; }
  .tabel-wrapper  { overflow:visible !important; }
  .tabel-wrapper table { min-width:0 !important; width:100%; font-size:11px; }
  .tabel-wrapper td, .tabel-wrapper th { padding:5px 6px; }
  .kartu { box-shadow:none !important; border:1px solid #ddd !important; page-break-inside:avoid; }

  /* cetak per kantin */
  body.mode-cetak-kantin > *:not(#kartu-cetak-kantin) { display:none !important; }
  body.mode-cetak-kantin #kartu-cetak-kantin { display:block !important; }

  /* cetak per seksi */
  body.mode-cetak-seksi .seksi-laporan:not(.seksi-aktif-cetak) { display:none !important; }
  body.mode-cetak-seksi .grid-dua  { display:block !important; }
  body.mode-cetak-seksi .seksi-aktif-cetak { width:100% !important; }
  body.mode-cetak-seksi .seksi-stat-wrapper .grid-stat { display:grid !important; }
}
</style>
</head>
<body>
<?php include '../../3. komponen/navbaradmin.php'; ?>

<div class="cetakjudul" style="padding:0 24px;">
  <strong style="font-size:16px;"><i class="fa-solid fa-chart-bar"></i> Laporan Platform — jajankita</strong>
  <div style="font-size:12px;color:#555;margin-top:3px;">
    <?= htmlspecialchars($labelkantin) ?> &nbsp;|&nbsp; <?= htmlspecialchars($labelterpilih) ?> &nbsp;|&nbsp; Dicetak: <?= date('d M Y, H:i') ?>
  </div>
  <hr style="margin:8px 0;border:none;border-top:2px solid #ddd;">
</div>

<main class="konten">

  <div class="header-halaman takprint">
    <div class="kiri">
      <h1><i class="fa-solid fa-chart-bar"></i> Laporan Platform</h1>
      <p><?= $labelkantin ?> — <?= $labelterpilih ?></p>
    </div>
  </div>

  <!-- ── PANEL FILTER & CETAK ───────────────────────────────────────────── -->
  <div class="takprint" style="background:var(--putihbg);border:1.5px solid var(--garis);border-radius:12px;padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <i class="fa-solid fa-filter" style="color:var(--kedua);font-size:15px;"></i>
    <span style="font-size:13px;font-weight:700;color:var(--teks);">Pilih Kantin:</span>
    <form method="GET" action="laporan.php" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
      <input type="hidden" name="periode" value="<?= $periode ?>">
      <?php if ($periode==='custom'): ?>
      <input type="hidden" name="dari"   value="<?= $dari ?>">
      <input type="hidden" name="sampai" value="<?= $sampai ?>">
      <?php endif; ?>
      <select name="kantin" style="padding:7px 12px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;font-family:inherit;background:white;color:var(--teks);min-width:220px;">
        <option value="0" <?= $nomorkantin===0?'selected':'' ?>>— Semua Kantin —</option>
        <?php foreach ($perftoko as $t):
          if (empty($t['id_user']) || empty($t['nama_penjual'])) continue;
          $opt = 'Kantin ke-'.(int)$t['nomor_kantin'];
          if (!empty($t['nama_toko'])) $opt .= ' — '.htmlspecialchars($t['nama_toko']);
        ?>
        <option value="<?= (int)$t['nomor_kantin'] ?>" <?= $nomorkantin==(int)$t['nomor_kantin']?'selected':'' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="tombolutama" style="padding:8px 14px;font-size:13px;">
        <i class="fa-solid fa-check"></i> Terapkan
      </button>
    </form>
    <span style="color:var(--garis);font-size:18px;font-weight:300;">|</span>
    <button onclick="window.print()" class="tombolringan" style="padding:8px 16px;font-size:13px;">
      <i class="fa-solid fa-print"></i> Cetak Laporan
    </button>
  </div>

  <!-- ── FILTER PERIODE ─────────────────────────────────────────────────── -->
  <div class="takprint" style="margin-bottom:18px;">
    <div class="filter-bar" style="margin-bottom:10px;">
      <a href="laporan.php?periode=7&kantin=<?= $nomorkantin ?>"  class="chip-filter <?= $periode==='7' ?'aktif':'' ?>">7 Hari</a>
      <a href="laporan.php?periode=14&kantin=<?= $nomorkantin ?>" class="chip-filter <?= $periode==='14'?'aktif':'' ?>">14 Hari</a>
      <a href="laporan.php?periode=30&kantin=<?= $nomorkantin ?>" class="chip-filter <?= $periode==='30'?'aktif':'' ?>">30 Hari</a>
      <a href="laporan.php?periode=custom&dari=<?= $dari ?>&sampai=<?= $sampai ?>&kantin=<?= $nomorkantin ?>"
         class="chip-filter <?= $periode==='custom'?'aktif':'' ?>">Custom</a>
    </div>
    <?php if ($periode==='custom'): ?>
    <form method="GET" action="laporan.php" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
      <input type="hidden" name="periode" value="custom">
      <input type="hidden" name="kantin"  value="<?= $nomorkantin ?>">
      <div><label style="font-size:11px;font-weight:700;color:var(--tekssamar);display:block;margin-bottom:4px;">DARI</label>
        <input type="date" name="dari"   value="<?= $dari ?>"   style="padding:8px 12px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;"></div>
      <div><label style="font-size:11px;font-weight:700;color:var(--tekssamar);display:block;margin-bottom:4px;">SAMPAI</label>
        <input type="date" name="sampai" value="<?= $sampai ?>" style="padding:8px 12px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;"></div>
      <button type="submit" class="tombolutama" style="align-self:flex-end;"><i class="fa-solid fa-filter"></i> Terapkan</button>
    </form>
    <?php endif; ?>
  </div>

  <!-- ── INFO KANTIN (per-kantin) ───────────────────────────────────────── -->
  <?php if (!$modeSemua && $infokantinterpilih): ?>
  <div class="kartu seksi-laporan" id="seksi-infokantin" style="margin-bottom:18px;background:var(--putihbg);">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-store"></i> Info Kantin</h3>
      <button onclick="cetakSeksi('seksi-infokantin')" class="tombolkecil takprint"><i class="fa-solid fa-print"></i> Cetak</button>
    </div>
    <div style="display:flex;align-items:center;gap:18px;">
      <div style="font-size:52px;font-weight:900;color:var(--utama);line-height:1;min-width:60px;text-align:center;"><?= $nomorkantin ?></div>
      <div style="flex:1;">
        <div style="font-size:20px;font-weight:800;color:var(--teks);">
          <?= !empty($infokantinterpilih['nama_toko']) ? htmlspecialchars($infokantinterpilih['nama_toko']) : '— Kosong —' ?>
        </div>
        <?php if (!empty($infokantinterpilih['nama_penjual'])): ?>
        <div style="font-size:13px;color:var(--tekssamar);margin-top:4px;">
          <i class="fa-solid fa-user" style="font-size:10px;"></i> <?= htmlspecialchars($infokantinterpilih['nama_penjual']) ?>
        </div>
        <?php endif; ?>
        <div style="margin-top:8px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
          <span class="badge <?= $infokantinterpilih['status_toko']==='buka'?'buka':'tutup' ?>"><?= $infokantinterpilih['status_toko']==='buka'?'Buka':'Tutup' ?></span>
          <?php if ($ratingkantin > 0): ?>
          <span style="font-size:13px;font-weight:700;color:var(--kedua);"><?= $ratingkantin ?> ★</span>
          <span style="font-size:12px;color:var(--tekssamar);">(<?= $jmlratingkantin ?> ulasan)</span>
          <?php endif; ?>
        </div>
      </div>
      <?php if (!empty($infokantinterpilih['id_user'])): ?>
      <a href="../manajemenpengguna/viewuser.php?id=<?= (int)$infokantinterpilih['id_user'] ?>" class="tombolringan takprint" style="font-size:12px;">
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Detail Penjual
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── STATISTIK UTAMA ────────────────────────────────────────────────── -->
  <div class="seksi-laporan seksi-stat-wrapper" id="seksi-stat" style="margin-bottom:18px;">
    <div class="takprint" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
      <small style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--tekssamar);">Statistik Utama — <?= $labelterpilih ?></small>
      <button onclick="cetakSeksi('seksi-stat')" class="tombolkecil"><i class="fa-solid fa-print"></i> Cetak</button>
    </div>
    <div class="grid-stat">
      <div class="kartu-stat">
        <div class="ikon-stat"><i class="fa-solid fa-receipt"></i></div>
        <div class="isi-stat"><div class="nilai"><?= $totalorder ?></div><div class="label">Total Pesanan</div><div class="sub"><?= $labelterpilih ?></div></div>
      </div>
      <div class="kartu-stat">
        <div class="ikon-stat"><i class="fa-solid fa-coins"></i></div>
        <div class="isi-stat"><div class="nilai" style="font-size:15px;"><?= singkat($totalomset) ?></div><div class="label">Total <?= $judulOmset ?></div><div class="sub">Selesai + Dibatalkan</div></div>
      </div>
      <div class="kartu-stat">
        <div class="ikon-stat"><i class="fa-solid fa-circle-check"></i></div>
        <div class="isi-stat"><div class="nilai"><?= $statpesanan['Selesai']??0 ?></div><div class="label">Pesanan Selesai</div><div class="sub"><?= $statpesanan['Dibatalkan']??0 ?> dibatalkan</div></div>
      </div>
      <?php if ($modeSemua): ?>
      <div class="kartu-stat">
        <div class="ikon-stat"><i class="fa-solid fa-user-plus"></i></div>
        <div class="isi-stat"><div class="nilai"><?= $userbarujml ?></div><div class="label">Pengguna Baru</div><div class="sub"><?= $labelterpilih ?></div></div>
      </div>
      <?php else: ?>
      <div class="kartu-stat">
        <div class="ikon-stat" style="background:#fffbeb;color:#D97706;"><i class="fa-solid fa-star"></i></div>
        <div class="isi-stat"><div class="nilai"><?= $ratingkantin ?: '—' ?></div><div class="label">Rating Toko</div><div class="sub"><?= $jmlratingkantin ?> ulasan</div></div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── CHART ──────────────────────────────────────────────────────────── -->
  <div class="kartu seksi-laporan" id="seksi-chart" style="margin-bottom:18px;">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-chart-bar"></i> <?= $judulOmset ?> <?= $modeSemua ? 'Platform' : ('Kantin ke-'.$nomorkantin) ?> — <?= $labelterpilih ?></h3>
      <button onclick="cetakSeksi('seksi-chart')" class="tombolkecil takprint"><i class="fa-solid fa-print"></i> Cetak</button>
    </div>
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

  <!-- ── TERLARIS + BREAKDOWN ───────────────────────────────────────────── -->
  <div class="grid-dua" style="margin-bottom:18px;">

    <div class="kartu seksi-laporan" id="seksi-terlaris">
      <div class="seksi-judul">
        <h3 style="margin:0;"><i class="fa-solid fa-fire"></i> Produk Terlaris <?= $modeSemua ? 'Platform' : 'Kantin Ini' ?></h3>
        <button onclick="cetakSeksi('seksi-terlaris')" class="tombolkecil takprint"><i class="fa-solid fa-print"></i> Cetak</button>
      </div>
      <?php if (empty($terlaris)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada data penjualan periode ini</p></div>
      <?php else: ?>
      <?php $medal=['emas','perak','perunggu']; ?>
      <?php foreach ($terlaris as $i => $t): ?>
      <div class="baris-produk">
        <div class="rangking-produk <?= $medal[$i]??'' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($t['nama_menu']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);">
            <?= htmlspecialchars($t['nama_toko']) ?>
            <?php if (!empty($t['nomor_kantin'])): ?><span style="color:var(--garis);">·</span> Kantin ke-<?= (int)$t['nomor_kantin'] ?><?php endif; ?>
            · <?= rp($t['omset']) ?>
          </div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;"><?= $t['terjual'] ?> terjual</div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="kartu seksi-laporan" id="seksi-breakdown">
      <div class="seksi-judul">
        <h3 style="margin:0;"><i class="fa-solid fa-list-check"></i> Breakdown Status Pesanan</h3>
        <button onclick="cetakSeksi('seksi-breakdown')" class="tombolkecil takprint"><i class="fa-solid fa-print"></i> Cetak</button>
      </div>
      <?php
      $statuslist = ['Menunggu','Diproses','Siap Diambil','Selesai','Dibatalkan'];
      $statbadge  = ['Menunggu'=>'menunggu','Diproses'=>'diproses','Siap Diambil'=>'siap','Selesai'=>'selesai','Dibatalkan'=>'dibatalkan'];
      $statwarna  = ['Menunggu'=>'#F59E0B','Diproses'=>'#2563EB','Siap Diambil'=>'#16A34A','Selesai'=>'#99627A','Dibatalkan'=>'#DC2626'];
      foreach ($statuslist as $s): $jml=$statpesanan[$s]??0; $pct=$totalorder>0?round($jml/$totalorder*100):0; ?>
      <div style="padding:7px 0;border-bottom:1px solid var(--latar);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
          <span class="badge <?= $statbadge[$s] ?>"><?= $s ?></span>
          <strong style="font-size:13px;"><?= $jml ?></strong>
        </div>
        <div style="height:12px;background:#f0f0f0;border-radius:3px;overflow:hidden;">
          <div style="height:100%;width:<?= $pct ?>%;background:<?= $statwarna[$s] ?>;border-radius:3px;"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>

  <!-- ── PELANGGAN ──────────────────────────────────────────────────────── -->
  <div class="grid-dua" style="margin-bottom:18px;">

    <div class="kartu seksi-laporan" id="seksi-pelbanyak">
      <div class="seksi-judul">
        <h3 style="margin:0;"><i class="fa-solid fa-cart-shopping"></i> Pelanggan Terbanyak Pesan</h3>
        <button onclick="cetakSeksi('seksi-pelbanyak')" class="tombolkecil takprint"><i class="fa-solid fa-print"></i> Cetak</button>
      </div>
      <?php if (empty($topbelibanyak)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada transaksi selesai periode ini</p></div>
      <?php else: ?>
      <?php $medal=['emas','perak','perunggu']; ?>
      <?php foreach ($topbelibanyak as $i => $p): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--latar);">
        <div class="rangking-produk <?= $medal[$i]??'' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['username']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);"><?= rp($p['total_belanja']) ?></div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;"><?= $p['jml_order'] ?>× pesan</div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="kartu seksi-laporan" id="seksi-pelmahal">
      <div class="seksi-judul">
        <h3 style="margin:0;"><i class="fa-solid fa-wallet"></i> Pelanggan Pengeluaran Terbesar</h3>
        <button onclick="cetakSeksi('seksi-pelmahal')" class="tombolkecil takprint"><i class="fa-solid fa-print"></i> Cetak</button>
      </div>
      <?php if (empty($topbelimahal)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada transaksi selesai periode ini</p></div>
      <?php else: ?>
      <?php foreach ($topbelimahal as $i => $p): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--latar);">
        <div class="rangking-produk <?= $medal[$i]??'' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['username']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);"><?= $p['jml_order'] ?> pesanan</div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;"><?= singkat($p['total_belanja']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

  <!-- ── PER-KANTIN: MENU + RATING & ULASAN ────────────────────────────── -->
  <?php if (!$modeSemua): ?>

  <!-- Menu kantin -->
  <?php if (!empty($daftarmenu)): ?>
  <div class="kartu seksi-laporan" id="seksi-menu" style="margin-bottom:18px;padding:0;overflow:hidden;">
    <div class="seksi-judul" style="padding:14px 20px;border-bottom:1px solid var(--latar);margin-bottom:0;">
      <h3 style="margin:0;"><i class="fa-solid fa-utensils"></i> Menu Kantin (<?= count($daftarmenu) ?> item) — <?= $labelterpilih ?></h3>
      <button onclick="cetakSeksi('seksi-menu')" class="tombolkecil takprint"><i class="fa-solid fa-print"></i> Cetak</button>
    </div>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th>Nama Menu</th>
            <th class="kanan">Harga</th>
            <th class="tengah">Status</th>
            <th class="tengah">Terjual Periode Ini</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftarmenu as $m): ?>
          <tr>
            <td style="font-weight:600;"><?= htmlspecialchars($m['nama_menu']) ?></td>
            <td class="kanan"><?= rp((float)$m['harga']) ?></td>
            <td class="tengah"><span class="badge <?= $m['status']==='aktif'?'selesai':'dibatalkan' ?>"><?= ucfirst($m['status']) ?></span></td>
            <td class="tengah" style="font-weight:700;color:var(--utama);"><?= (int)$m['terjual'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Rating & Ulasan kantin -->
  <div class="kartu seksi-laporan" id="seksi-rating" style="margin-bottom:18px;">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-star"></i> Rating &amp; Ulasan Kantin</h3>
      <button onclick="cetakSeksi('seksi-rating')" class="tombolkecil takprint"><i class="fa-solid fa-print"></i> Cetak</button>
    </div>

    <!-- Rata-rata + distribusi -->
    <div style="display:flex;gap:24px;flex-wrap:wrap;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--latar);">
      <div style="display:flex;align-items:center;gap:14px;">
        <div style="font-size:44px;font-weight:900;color:var(--kedua);line-height:1;"><?= $ratingkantin ?: '—' ?></div>
        <div>
          <?php if ($ratingkantin > 0): ?><div style="margin-bottom:3px;"><?= bintanghtml($ratingkantin) ?></div><?php endif; ?>
          <div style="font-size:12px;color:var(--tekssamar);"><?= $jmlratingkantin ?> ulasan</div>
        </div>
      </div>
      <div style="flex:1;min-width:160px;">
        <?php if ($jmlratingkantin > 0): ?>
        <?php for ($bin=5;$bin>=1;$bin--):
            $j = $distribusirating[$bin] ?? 0;
            $pct = round($j / $jmlratingkantin * 100);
        ?>
        <div style="display:flex;align-items:center;gap:8px;margin:4px 0;">
          <span style="font-size:11px;font-weight:700;min-width:18px;text-align:right;"><?= $bin ?>★</span>
          <div class="bar-dist"><div class="bar-dist-isi" style="width:<?= $pct ?>%;"></div></div>
          <span style="font-size:11px;color:var(--tekssamar);min-width:24px;"><?= $j ?></span>
        </div>
        <?php endfor; ?>
        <?php else: ?>
        <p style="color:var(--tekssamar);font-size:13px;margin:0;">Belum ada ulasan</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Ulasan terbaru -->
    <?php if (!empty($ulasankantin)): ?>
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--tekssamar);margin-bottom:12px;">Ulasan Terbaru</div>
    <?php foreach ($ulasankantin as $ul): ?>
    <div style="margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--latar);">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px;">
        <span style="font-size:13px;font-weight:700;"><?= htmlspecialchars($ul['username']) ?></span>
        <span style="font-size:11px;color:var(--tekssamar);"><?= !empty($ul['created']) ? date('d M Y', strtotime($ul['created'])) : '' ?></span>
      </div>
      <div style="margin-bottom:5px;"><?= bintanghtml((float)$ul['rating_toko']) ?></div>
      <?php if (!empty($ul['ulasan'])): ?>
      <div style="font-size:13px;color:var(--teks);font-style:italic;line-height:1.4;">
        "<?= htmlspecialchars(mb_substr($ul['ulasan'],0,160)) ?><?= mb_strlen($ul['ulasan'])>160?'...':'' ?>"
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <p style="color:var(--tekssamar);font-size:13px;">Belum ada ulasan tersimpan</p>
    <?php endif; ?>
  </div>

  <?php endif; ?>

  <!-- ── PERFORMA PER KANTIN (mode semua) ──────────────────────────────── -->
  <?php if ($modeSemua): ?>
  <div class="kartu seksi-laporan" id="seksi-performa">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-store"></i> Performa Per Kantin — <?= $labelterpilih ?></h3>
      <button onclick="cetakSeksi('seksi-performa')" class="tombolkecil takprint"><i class="fa-solid fa-print"></i> Cetak</button>
    </div>
    <div class="tabel-wrapper">
      <table style="min-width:720px;">
        <thead>
          <tr>
            <th class="tengah">No.</th>
            <th>Nama Toko / Penjual</th>
            <th class="tengah">Status</th>
            <th class="tengah">Rating</th>
            <th class="tengah">Total Pesanan</th>
            <th class="tengah">Dibatalkan</th>
            <th class="kanan">Total Omset</th>
            <th class="tengah takprint">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($perftoko)): ?>
          <tr><td colspan="8"><div class="kosong" style="padding:20px;"><p>Belum ada kantin</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($perftoko as $t): ?>
          <tr class="baris-kantin"
              data-nomor="<?= (int)$t['nomor_kantin'] ?>"
              data-nomatoko="<?= htmlspecialchars($t['nama_toko']??'',ENT_QUOTES) ?>"
              data-penjual="<?= htmlspecialchars($t['nama_penjual']??'',ENT_QUOTES) ?>"
              data-status="<?= $t['status_toko']==='buka'?'buka':'tutup' ?>"
              data-terisi="<?= !empty($t['nama_penjual'])?'1':'0' ?>"
              data-order="<?= (int)$t['total_order'] ?>"
              data-batal="<?= (int)$t['jml_dibatalkan'] ?>"
              data-rating="<?= (float)$t['rating'] ?>"
              data-omset="<?= (float)$t['pendapatan'] ?>">
            <td class="tengah"><strong style="font-size:18px;color:var(--utama);"><?= (int)$t['nomor_kantin'] ?></strong></td>
            <td>
              <?php if (!empty($t['id_user']) && !empty($t['nama_penjual'])): ?>
              <strong><?= htmlspecialchars($t['nama_toko'] ?? '—') ?></strong>
              <div style="font-size:11px;color:var(--tekssamar);"><i class="fa-solid fa-user" style="font-size:9px;"></i> <?= htmlspecialchars($t['nama_penjual']) ?></div>
              <?php else: ?>
              <em style="color:var(--tekssamar);font-size:13px;">— Kosong —</em>
              <?php endif; ?>
            </td>
            <td class="tengah">
              <?php if (empty($t['id_user']) || empty($t['nama_penjual'])): ?>
              <span class="badge" style="background:#f5f5f5;color:#9e9e9e;">Kosong</span>
              <?php else: ?>
              <span class="badge <?= $t['status_toko']==='buka'?'buka':'tutup' ?>"><?= $t['status_toko']==='buka'?'Buka':'Tutup' ?></span>
              <?php endif; ?>
            </td>
            <td class="tengah"><?= $t['rating']>0 ? $t['rating'].' ★' : '—' ?></td>
            <td class="tengah"><?= (int)$t['total_order'] ?></td>
            <td class="tengah" style="color:var(--gagal);"><?= (int)$t['jml_dibatalkan'] ?></td>
            <td class="kanan" style="font-weight:700;color:var(--sukses);"><?= rp($t['pendapatan']) ?></td>
            <td class="tengah takprint">
              <div style="display:flex;gap:4px;justify-content:center;">
                <?php if (!empty($t['id_user'])): ?>
                <a href="../manajemenpengguna/viewuser.php?id=<?= (int)$t['id_user'] ?>" class="tombol-aksi" title="Detail">
                  <i class="fa-solid fa-eye"></i>
                </a>
                <?php endif; ?>
                <button onclick="cetakKantin(<?= (int)$t['nomor_kantin'] ?>)" class="tombol-aksi" title="Cetak Kantin Ini" style="border:none;cursor:pointer;background:var(--latar);">
                  <i class="fa-solid fa-print"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <?php if (!empty($perftoko)): ?>
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
  <?php endif; ?>

</main>

<div id="kartu-cetak-kantin"></div>

<script>
/* cetakSeksi: sembunyikan semua seksi kecuali yang dipilih, lalu print */
function cetakSeksi(id) {
    document.querySelectorAll('.seksi-laporan').forEach(function(el) {
        el.classList.remove('seksi-aktif-cetak');
    });
    var t = document.getElementById(id);
    if (t) t.classList.add('seksi-aktif-cetak');
    document.body.classList.add('mode-cetak-seksi');
    window.print();
    window.onafterprint = function() {
        document.body.classList.remove('mode-cetak-seksi');
        if (t) t.classList.remove('seksi-aktif-cetak');
        window.onafterprint = null;
    };
}

/* cetakKantin: inject kartu satu kantin ke overlay dan print */
function cetakKantin(n) {
    var tr = document.querySelector('tr.baris-kantin[data-nomor="' + n + '"]');
    if (!tr) return;
    var nomatoko = tr.dataset.nomatoko || '';
    var penjual  = tr.dataset.penjual  || '';
    var terisi   = tr.dataset.terisi   === '1';
    var status   = tr.dataset.status;
    var order    = parseInt(tr.dataset.order)    || 0;
    var batal    = parseInt(tr.dataset.batal)    || 0;
    var rating   = parseFloat(tr.dataset.rating) || 0;
    var omset    = parseFloat(tr.dataset.omset)  || 0;
    var periode  = '<?= addslashes($labelterpilih) ?>';

    function rpFmt(v) { return 'Rp ' + Math.floor(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    var statusLabel = !terisi ? 'Kosong' : (status==='buka' ? 'Buka' : 'Tutup');
    var statusColor = !terisi ? '#9e9e9e' : (status==='buka' ? '#2e7d32' : '#c62828');
    var tglCetak = new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'});

    var html =
        '<div style="font-family:Arial,sans-serif;padding:28px;max-width:640px;margin:0 auto;color:#3D2C33;">'
        + '<div style="text-align:center;padding-bottom:14px;border-bottom:2px solid #643843;margin-bottom:22px;">'
        +   '<div style="font-size:22px;font-weight:900;color:#643843;">jajankita</div>'
        +   '<div style="font-size:15px;font-weight:700;margin-top:4px;">Laporan Kantin ke-' + n + '</div>'
        +   '<div style="font-size:11px;color:#8B6475;margin-top:3px;">Periode: ' + periode + ' &middot; Dicetak: ' + tglCetak + '</div>'
        + '</div>'
        + '<div style="display:flex;align-items:center;gap:16px;padding:14px 18px;background:#F8EBF1;border-radius:10px;margin-bottom:18px;">'
        +   '<div style="font-size:48px;font-weight:900;color:#643843;line-height:1;min-width:56px;text-align:center;">' + n + '</div>'
        +   '<div style="flex:1;">'
        +     '<div style="font-size:18px;font-weight:800;">' + (nomatoko || '— Kosong —') + '</div>'
        +     (penjual ? '<div style="font-size:12px;color:#8B6475;margin-top:2px;">Penjual: ' + penjual + '</div>' : '')
        +     '<div style="font-size:12px;font-weight:700;margin-top:4px;color:' + statusColor + ';">' + statusLabel + '</div>'
        +   '</div>'
        + '</div>'
        + '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
        + '<tr style="border-bottom:1px solid #EFD9D4;"><td style="padding:10px 0;color:#8B6475;">Total Pesanan</td><td style="padding:10px 0;font-weight:700;text-align:right;">' + order + '</td></tr>'
        + '<tr style="border-bottom:1px solid #EFD9D4;"><td style="padding:10px 0;color:#8B6475;">Pesanan Dibatalkan</td><td style="padding:10px 0;font-weight:700;text-align:right;color:#c62828;">' + batal + '</td></tr>'
        + '<tr style="border-bottom:1px solid #EFD9D4;"><td style="padding:10px 0;color:#8B6475;">Pesanan Berhasil</td><td style="padding:10px 0;font-weight:700;text-align:right;color:#2e7d32;">' + (order-batal) + '</td></tr>'
        + '<tr style="border-bottom:1px solid #EFD9D4;"><td style="padding:10px 0;color:#8B6475;">Rating Toko</td><td style="padding:10px 0;font-weight:700;text-align:right;">' + (rating>0?rating+' ★':'—') + '</td></tr>'
        + '<tr><td style="padding:12px 0;font-weight:800;font-size:14px;">Total Omset</td><td style="padding:12px 0;font-weight:900;text-align:right;font-size:20px;color:#2e7d32;">' + rpFmt(omset) + '</td></tr>'
        + '</table></div>';

    var kartu = document.getElementById('kartu-cetak-kantin');
    kartu.innerHTML = html;
    document.body.classList.add('mode-cetak-kantin');
    window.onafterprint = function() {
        document.body.classList.remove('mode-cetak-kantin');
        window.onafterprint = null;
    };
    window.print();
}
</script>
</body>
</html>
