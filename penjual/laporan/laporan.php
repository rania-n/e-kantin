<?php
/* halaman laporan penjualan penjual.
   mendukung filter periode: hari ini, minggu ini, bulan ini, dan custom tanggal.
   bisa dicetak via tombol cetak yang memanggil window.print() */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

// ambil id toko dari session
$idtoko    = (int)$_SESSION['id_toko'];
// id penjual untuk isolasi data — memastikan hanya data milik penjual ini yang tampil
$idpenjual = (int)$_SESSION['id_user'];

// tandai halaman aktif untuk navbar
$halamansaatini = 'laporan';

// cek apakah parameter ?cetak=1 ada di url (untuk mode cetak)
$cetak = isset($_GET['cetak']);

// ambil periode dari parameter GET, default ke "7" (7 hari terakhir).
// filter ini sinkron dengan laporan platform admin: 7/14/30/Custom.
$periode = $_GET['periode'] ?? '7';
if (!in_array($periode, ['7','14','30','custom'])) $periode = '7';

if ($periode === 'custom') {
    // ambil tanggal awal dan akhir dari parameter GET
    $tglawal  = $_GET['dari']   ?? date('Y-m-d', strtotime('-7 days'));
    $tglakhir = $_GET['sampai'] ?? date('Y-m-d');
    // validasi format tanggal harus yyyy-mm-dd, jika salah gunakan default
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglawal))  $tglawal  = date('Y-m-d', strtotime('-7 days'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglakhir)) $tglakhir = date('Y-m-d');
    if ($tglawal > $tglakhir) [$tglawal, $tglakhir] = [$tglakhir, $tglawal];
    $labelprd = date('d M Y', strtotime($tglawal)) . ' — ' . date('d M Y', strtotime($tglakhir));
} else {
    // 7/14/30 hari terakhir berakhir hari ini, dimulai N-1 hari sebelumnya
    $n = (int)$periode;
    $tglawal  = date('Y-m-d', strtotime('-' . ($n - 1) . ' days'));
    $tglakhir = date('Y-m-d');
    $labelprd = $n . ' Hari Terakhir';
}

// ambil total pendapatan dan jumlah pesanan selesai pada periode ini
$q1 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0), COUNT(*) FROM tb_order WHERE id_penjual=? AND status_order='Selesai' AND deleted=0 AND DATE(tanggal_order) BETWEEN ? AND ?");
$q1->bind_param("iss", $idpenjual, $tglawal, $tglakhir); $q1->execute();
$r1 = $q1->get_result()->fetch_row(); $q1->close();
$totalpendapatan    = (float)$r1[0];
$totalpesananselesai = (int)$r1[1];

// ambil total semua pesanan masuk dan jumlah yang dibatalkan pada periode ini
$q2 = $conn->prepare("SELECT COUNT(*), SUM(CASE WHEN status_order='Dibatalkan' THEN 1 ELSE 0 END) FROM tb_order WHERE id_penjual=? AND deleted=0 AND DATE(tanggal_order) BETWEEN ? AND ?");
$q2->bind_param("iss", $idpenjual, $tglawal, $tglakhir); $q2->execute();
$r2 = $q2->get_result()->fetch_row(); $q2->close();
$totalpesanan = (int)$r2[0];
$totaldibatal = (int)$r2[1];

// hitung rata-rata nilai per pesanan selesai (hindari pembagian dengan nol)
$ratarata = $totalpesananselesai > 0 ? $totalpendapatan / $totalpesananselesai : 0;

// ambil 10 produk terlaris pada periode ini — hanya dari pesanan yang selesai
$qtl = $conn->prepare("SELECT m.nama_menu, SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omset
                        FROM tb_detail_order d
                        JOIN tb_menu m ON d.id_menu=m.id_menu
                        JOIN tb_order o ON d.id_order=o.id_order
                        WHERE o.id_penjual=? AND o.deleted=0 AND d.deleted=0
                          AND o.status_order='Selesai'
                          AND DATE(o.tanggal_order) BETWEEN ? AND ?
                        GROUP BY m.id_menu, m.nama_menu
                        ORDER BY terjual DESC LIMIT 10");
$qtl->bind_param("iss", $idpenjual, $tglawal, $tglakhir); $qtl->execute();
$terlaris = $qtl->get_result()->fetch_all(MYSQLI_ASSOC); $qtl->close();

// ── data tambahan: pelanggan, rating, ulasan, dan detail pesanan lengkap ────────

// nilai pesanan yang dibatalkan periode ini (potensi pendapatan hilang, terpisah dari omset)
$qbd = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_penjual=? AND status_order='Dibatalkan' AND deleted=0 AND DATE(tanggal_order) BETWEEN ? AND ?");
$qbd->bind_param("iss", $idpenjual, $tglawal, $tglakhir); $qbd->execute();
$nilaidibatal = (float)$qbd->get_result()->fetch_row()[0]; $qbd->close();

// jumlah pembeli unik (pesanan selesai) — tahu berapa banyak orang yang benar-benar beli
$qpu = $conn->prepare("SELECT COUNT(DISTINCT id_user) FROM tb_order WHERE id_penjual=? AND status_order='Selesai' AND deleted=0 AND DATE(tanggal_order) BETWEEN ? AND ?");
$qpu->bind_param("iss", $idpenjual, $tglawal, $tglakhir); $qpu->execute();
$jmlpelangganunik = (int)$qpu->get_result()->fetch_row()[0]; $qpu->close();

// top 10 pelanggan — gabung "terbanyak pesan" dan "pengeluaran terbesar" jadi satu list
// urut by jml_order DESC, lalu total_belanja DESC sebagai tie-breaker
$qpel = $conn->prepare("SELECT u.username, COUNT(o.id_order) AS jml_order, COALESCE(SUM(o.total_harga),0) AS total_belanja
                        FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user
                        WHERE o.id_penjual=? AND o.status_order='Selesai' AND o.deleted=0
                          AND DATE(o.tanggal_order) BETWEEN ? AND ?
                        GROUP BY o.id_user, u.username
                        ORDER BY jml_order DESC, total_belanja DESC LIMIT 10");
$qpel->bind_param("iss", $idpenjual, $tglawal, $tglakhir); $qpel->execute();
$toppelanggan = $qpel->get_result()->fetch_all(MYSQLI_ASSOC); $qpel->close();

// daftar semua menu penjual ini (termasuk yang sudah dihapus) — laporan historis lengkap
// terjual dihitung dari pesanan Selesai pada periode ini saja
$qmenu = $conn->prepare(
    "SELECT m.nama_menu, m.harga, m.status, m.deleted,
            COALESCE((SELECT SUM(d2.jumlah) FROM tb_detail_order d2
                      JOIN tb_order o2 ON d2.id_order=o2.id_order
                      WHERE d2.id_menu=m.id_menu AND d2.deleted=0
                        AND o2.id_penjual=? AND o2.deleted=0 AND o2.status_order='Selesai'
                        AND DATE(o2.tanggal_order) BETWEEN ? AND ?),0) AS terjual_periode,
            COALESCE((SELECT SUM(d3.jumlah) FROM tb_detail_order d3
                      JOIN tb_order o3 ON d3.id_order=o3.id_order
                      WHERE d3.id_menu=m.id_menu AND d3.deleted=0
                        AND o3.id_penjual=? AND o3.deleted=0 AND o3.status_order='Selesai'),0) AS terjual_total
     FROM tb_menu m WHERE m.id_penjual=?
     ORDER BY m.deleted ASC, (m.status='aktif') DESC, terjual_periode DESC, m.nama_menu ASC"
);
$qmenu->bind_param("issii", $idpenjual, $tglawal, $tglakhir, $idpenjual, $idpenjual);
$qmenu->execute(); $daftarmenu = $qmenu->get_result()->fetch_all(MYSQLI_ASSOC); $qmenu->close();

// rata-rata dan jumlah rating periode ini
$qrate = $conn->prepare("SELECT COALESCE(ROUND(AVG(rating_toko),1),0), COUNT(*) FROM tb_rating WHERE id_penjual=? AND deleted=0 AND DATE(created) BETWEEN ? AND ?");
$qrate->bind_param("iss", $idpenjual, $tglawal, $tglakhir); $qrate->execute();
$rrate = $qrate->get_result()->fetch_row(); $qrate->close();
$ratarating = (float)($rrate[0] ?? 0);
$jmlrating  = (int)($rrate[1] ?? 0);

// distribusi rating per bintang (1-5)
$qdist = $conn->prepare("SELECT rating_toko, COUNT(*) AS jml FROM tb_rating WHERE id_penjual=? AND deleted=0 AND DATE(created) BETWEEN ? AND ? GROUP BY rating_toko ORDER BY rating_toko DESC");
$qdist->bind_param("iss", $idpenjual, $tglawal, $tglakhir); $qdist->execute();
$distribusirating = [];
$resd = $qdist->get_result();
while ($r = $resd->fetch_assoc()) $distribusirating[(int)$r['rating_toko']] = (int)$r['jml'];
$qdist->close();

// 10 ulasan terbaru periode ini — beserta daftar menu yang dipesan saat rating diberikan
// pakai sub-query GROUP_CONCAT supaya tiap ulasan punya kolom "menu yang dipesan"
$qul = $conn->prepare(
    "SELECT r.rating_toko, r.ulasan, r.created, u.username,
            (SELECT GROUP_CONCAT(COALESCE(d.nama_menu_snapshot, m2.nama_menu) SEPARATOR ', ')
             FROM tb_detail_order d
             LEFT JOIN tb_menu m2 ON d.id_menu=m2.id_menu
             WHERE d.id_order=r.id_order AND d.deleted=0) AS menu_dipesan
     FROM tb_rating r JOIN tb_user u ON r.id_user=u.id_user
     WHERE r.id_penjual=? AND r.deleted=0
       AND DATE(r.created) BETWEEN ? AND ?
     ORDER BY r.created DESC LIMIT 10"
);
$qul->bind_param("iss", $idpenjual, $tglawal, $tglakhir); $qul->execute();
$ulasanterbaru = $qul->get_result()->fetch_all(MYSQLI_ASSOC); $qul->close();

/* detail pesanan: Selesai + Dibatalkan beserta items menu-nya.
   pakai snapshot nama_menu agar tetap akurat meski menu sudah dihapus.
   satu baris query per item — di-group per id_order saat parsing. */
$qdet = $conn->prepare(
    "SELECT o.id_order, DATE_FORMAT(o.tanggal_order,'%d/%m/%Y %H:%i') AS tgl_format,
            u.username AS pembeli, o.status_order, o.total_harga, o.metode_pembayaran,
            COALESCE(d.nama_menu_snapshot, m.nama_menu) AS nama_menu,
            d.jumlah, d.harga_satuan, d.subtotal
     FROM tb_order o
     JOIN tb_user u ON o.id_user=u.id_user
     LEFT JOIN tb_detail_order d ON o.id_order=d.id_order AND d.deleted=0
     LEFT JOIN tb_menu m ON d.id_menu=m.id_menu
     WHERE o.id_penjual=? AND o.status_order IN ('Selesai','Dibatalkan') AND o.deleted=0
       AND DATE(o.tanggal_order) BETWEEN ? AND ?
     ORDER BY o.tanggal_order DESC, o.id_order, nama_menu
     LIMIT 500");
$qdet->bind_param("iss", $idpenjual, $tglawal, $tglakhir); $qdet->execute();
$resdet = $qdet->get_result();
$pesanandetail = []; // dikumpulkan per id_order (key = id_order)
while ($rd = $resdet->fetch_assoc()) {
    $oid = $rd['id_order'];
    if (!isset($pesanandetail[$oid])) {
        $pesanandetail[$oid] = [
            'id'      => $oid,
            'tanggal' => $rd['tgl_format'],
            'pembeli' => $rd['pembeli'],
            'status'  => $rd['status_order'],
            'total'   => (float)$rd['total_harga'],
            'metode'  => $rd['metode_pembayaran'],
            'items'   => []
        ];
    }
    if ($rd['nama_menu']) {
        $pesanandetail[$oid]['items'][] = [
            'nama'     => $rd['nama_menu'],
            'jumlah'   => (int)$rd['jumlah'],
            'harga'    => (float)$rd['harga_satuan'],
            'subtotal' => (float)$rd['subtotal']
        ];
    }
}
$qdet->close();

// fungsi bantu: ubah kode hari inggris ke singkatan hari indonesia
function namahari(string $tgl): string {
    $map = ['Sun'=>'Min','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab'];
    return $map[date('D', strtotime($tgl))] ?? date('D', strtotime($tgl));
}

// ambil data pendapatan per hari dari database untuk chart batang
$qchart = $conn->prepare("SELECT DATE(tanggal_order) AS tgl, COALESCE(SUM(total_harga),0) AS nilai FROM tb_order WHERE id_penjual=? AND DATE(tanggal_order) BETWEEN ? AND ? AND status_order='Selesai' AND deleted=0 GROUP BY DATE(tanggal_order)");
$qchart->bind_param("iss", $idpenjual, $tglawal, $tglakhir); $qchart->execute();
$rawchart = []; $resc = $qchart->get_result();

// simpan ke array asosiatif dengan kunci tanggal
while ($row = $resc->fetch_assoc()) $rawchart[$row['tgl']] = (float)$row['nilai'];
$qchart->close();

// hitung jumlah hari dalam periode untuk mengisi semua titik pada chart
$selisih = (int)ceil((strtotime($tglakhir) - strtotime($tglawal)) / 86400) + 1; // 86400 = detik per hari

// buat array chart lengkap — hari tanpa data diisi 0
$chartdata = [];
for ($i = 0; $i < $selisih; $i++) {
    $tgl = date('Y-m-d', strtotime($tglawal) + $i * 86400);
    $chartdata[] = ['tgl'=>$tgl,'label'=>date('d/m',strtotime($tgl)),'nilai'=>$rawchart[$tgl]??0.0];
}

// nilai maksimum untuk skala sumbu y (minimal 1 agar tidak bagi 0)
$maxnilai = max(array_column($chartdata,'nilai')) ?: 1;

// format angka ke rupiah
function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }

// format angka besar ke singkatan (jt, m)
function singkat(float $n): string {
    if ($n >= 1_000_000_000) { $v=$n/1_000_000_000; return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' M'; }
    if ($n >= 1_000_000)     { $v=$n/1_000_000;     return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' Jt'; }
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// helper render 5 bintang berwarna sesuai nilai rating (untuk ulasan)
function bintanghtml(float $r): string {
    $out = '';
    for ($i=1;$i<=5;$i++) {
        $w = $i<=$r ? '#F59E0B' : '#D1D5DB';
        $out .= "<i class='fa-solid fa-star' style='color:{$w};font-size:11px;'></i>";
    }
    return $out;
}

// hitung dimensi chart svg berdasarkan jumlah hari
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
  .cetakjudul { display:none; }
  /* judul setiap section: flex row supaya h3 di kiri & tombol di kanan rapi */
  .seksi-judul { display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
  .seksi-judul h3 { margin:0; }
  /* aturan cetak minimal: hilangkan shadow, pertahankan layout web supaya tampilan tidak aneh */
  @media print {
    .cetakjudul { display:block !important; margin-bottom:14px; font-family:sans-serif; }
    @page { size:A4 portrait; margin:12mm; }
    .takprint { display:none !important; }
    .kartu { box-shadow:none !important; border:1px solid #ddd !important; page-break-inside:avoid; }
  }
</style>
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten">

  <!-- header halaman dengan tombol cetak — kelas "takprint" menyembunyikan elemen saat dicetak -->
  <div class="header-halaman takprint">
    <div class="kiri">
      <h1><i class="fa-solid fa-chart-bar"></i> Laporan Penjualan</h1>
      <p><?= $labelprd ?></p>
    </div>
    <button onclick="eksporXlsSemua('laporan_penjualan')" class="tombolutama" style="background:var(--sukses);border-color:var(--sukses);">
      <i class="fa-solid fa-file-csv"></i> Cetak Semua
    </button>
  </div>

  <!-- judul cetak — selalu muncul saat print (semua atau per-section) -->
  <div class="cetakjudul" style="text-align:center;margin-bottom:18px;">
    <div style="font-size:18px;font-weight:800;"><?= htmlspecialchars($_SESSION['nama_toko']??'Toko') ?></div>
    <div style="font-size:13px;">Laporan Penjualan — <?= $labelprd ?></div>
    <div style="font-size:11px;color:#666;">Dicetak: <?= date('d M Y H:i') ?></div>
    <hr style="border-color:#ccc;margin:12px 0;">
  </div>

  <!-- pilihan filter periode 7/14/30 hari terakhir + custom — sinkron dengan laporan admin -->
  <div class="takprint" style="margin-bottom:16px;">
    <div class="filter-bar" style="margin-bottom:10px;flex-wrap:wrap;">
      <?php foreach (['7'=>'7 Hari','14'=>'14 Hari','30'=>'30 Hari'] as $p => $lab): ?>
      <a href="laporan.php?periode=<?= $p ?>" class="chip-filter <?= $periode===$p?'aktif':'' ?>"><?= $lab ?></a>
      <?php endforeach; ?>
      <a href="laporan.php?periode=custom&dari=<?= $tglawal ?>&sampai=<?= $tglakhir ?>"
         class="chip-filter <?= $periode==='custom'?'aktif':'' ?>">Custom</a>
    </div>
    <?php if ($periode === 'custom'): ?>
    <!-- form input tanggal awal dan akhir untuk periode custom -->
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

  <!-- ringkasan statistik 6 kartu — total pendapatan hanya pesanan Selesai,
       nilai dibatalkan ditampilkan terpisah supaya jelas potensi yang hilang -->
  <div class="seksi-laporan" id="seksi-stat" style="margin-bottom:18px;">
  <div class="takprint" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
    <small style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--tekssamar);">Statistik Utama — <?= $labelprd ?></small>
  </div>
  <!-- 4 stat cards: Omset Selesai (hijau) + Nilai Dibatalkan (merah) + Pembeli Berbeda + Rating Toko.
       jumlah pesanan selesai/dibatalkan dimuat di sub-label, rata-rata dihapus karena redundan -->
  <div class="grid-stat">
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:var(--suksebg);color:var(--sukses);"><i class="fa-solid fa-coins"></i></div>
      <div class="isi-stat">
        <div class="nilai" style="color:var(--sukses);"><?= singkat($totalpendapatan) ?></div>
        <div class="label">Omset Selesai</div>
        <div class="tren" style="color:var(--tekssamar);"><?= $totalpesananselesai ?> pesanan selesai</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:#fee2e2;color:#dc2626;"><i class="fa-solid fa-circle-xmark"></i></div>
      <div class="isi-stat">
        <div class="nilai" style="color:#dc2626;"><?= singkat($nilaidibatal) ?></div>
        <div class="label">Nilai Dibatalkan</div>
        <div class="tren" style="color:var(--tekssamar);"><?= $totaldibatal ?> pesanan dibatalkan</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-users"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $jmlpelangganunik ?></div>
        <div class="label">Pembeli Berbeda</div>
        <div class="tren" style="color:var(--tekssamar);">Pelanggan unik</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:#fffbeb;color:#D97706;"><i class="fa-solid fa-star"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $ratarating > 0 ? $ratarating : '—' ?></div>
        <div class="label">Rating Toko</div>
        <div class="tren" style="color:var(--tekssamar);"><?= $jmlrating ?> ulasan</div>
      </div>
    </div>
  </div>
  </div><!-- /seksi-stat -->

  <!-- diagram batang pendapatan harian dalam format svg -->
  <div class="kartu seksi-laporan" id="seksi-chart" style="margin-bottom:18px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
      <h3 style="margin:0;"><i class="fa-solid fa-chart-bar"></i> Omset — <?= $labelprd ?></h3>
    </div>
    <?php if ($totalpendapatan <= 0): ?>
    <div class="kosong" style="padding:20px;"><p>Belum ada omset pada periode ini</p></div>
    <?php else: ?>
    <div class="area-chart">
      <svg viewBox="0 0 <?=$svgw?> 210" xmlns="http://www.w3.org/2000/svg" style="min-width:<?=min(700,$svgw)?>px;">
        <?php
        // gambar garis grid horizontal dan label nilai di sumbu y
        for($g=0;$g<=4;$g++):$y=20+($g*40);?>
        <line x1="60" y1="<?=$y?>" x2="<?=$svgw-10?>" y2="<?=$y?>" stroke="#E7CBCB" stroke-width="1" stroke-dasharray="4,4"/>
        <text x="55" y="<?=$y+4?>" text-anchor="end" fill="#99627A" font-size="9"><?=singkat(($maxnilai/4)*(4-$g))?></text>
        <?php endfor;?>
        <?php
        // gambar setiap batang beserta label hari di bawahnya
        foreach($chartdata as $i=>$d):
          $x=$startx+$i*($barw+$gap);
          $barh=$d['nilai']>0?($d['nilai']/$maxnilai)*$chartH:2; // minimal 2px agar batang terlihat
          $by=180-$barh;
          $isToday=$d['tgl']===date('Y-m-d'); // hari ini diberi warna lebih gelap
        ?>
        <rect x="<?=$x?>" y="<?=$by?>" width="<?=$barw?>" height="<?=$barh?>" rx="3" fill="<?=$isToday?'#643843':'#99627A'?>">
          <title><?=$d['label']?> — <?=rp($d['nilai'])?></title>
        </rect>
        <!-- label hari di bawah batang, ukuran font diperkecil jika banyak data -->
        <text x="<?=$x+$barw/2?>" y="200" text-anchor="middle" fill="<?=$isToday?'#643843':'#99627A'?>"
              font-size="<?=$n>20?'7':'9'?>" font-weight="<?=$isToday?'700':'400'?>"><?=$d['label']?></text>
        <?php if($d['nilai']>0&&$barw>=20):?>
        <!-- label nilai di atas batang (hanya jika batang cukup lebar) -->
        <text x="<?=$x+$barw/2?>" y="<?=max($by-4,14)?>" text-anchor="middle" fill="#643843" font-size="8" font-weight="600">
          <?php $_n=$d['nilai']; echo $_n>=1000000 ? number_format($_n/1000000,1).'Jt' : ($_n>=1000 ? number_format($_n/1000,0).'k' : number_format($_n,0)); ?>
        </text>
        <?php endif;?>
        <?php endforeach;?>
      </svg>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── TOP PELANGGAN (gabung) — tabel rank, username, jml pesanan, total belanja ── -->
  <div class="kartu seksi-laporan" id="seksi-pelanggan" style="margin-bottom:18px;">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-trophy"></i> Top Pelanggan — <?= $labelprd ?></h3>
      <button onclick="eksporXlsSeksi('seksi-pelanggan','top_pelanggan')" class="tombolkecil takprint" style="background:var(--sukses);color:white;"><i class="fa-solid fa-file-csv"></i> Cetak</button>
    </div>
    <?php if (empty($toppelanggan)): ?>
    <div class="kosong" style="padding:24px;"><p>Belum ada data pelanggan periode ini</p></div>
    <?php else: ?>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th class="tengah" style="width:50px;">Rank</th>
            <th>Username Pembeli</th>
            <th class="tengah" style="width:120px;">Jumlah Pesanan</th>
            <th class="kanan" style="width:140px;">Total Belanja</th>
            <th class="kanan" style="width:140px;">Rata-rata / Pesanan</th>
          </tr>
        </thead>
        <tbody>
          <?php $medal = ['emas','perak','perunggu']; ?>
          <?php foreach ($toppelanggan as $i => $p):
              $ratapeso = $p['jml_order'] > 0 ? $p['total_belanja'] / $p['jml_order'] : 0;
          ?>
          <tr>
            <td class="tengah"><div class="rangking-produk <?= $medal[$i]??'' ?>" style="display:inline-block;">#<?= $i+1 ?></div></td>
            <td style="font-weight:700;"><?= htmlspecialchars($p['username']) ?></td>
            <td class="tengah" style="font-weight:700;color:var(--utama);"><?= $p['jml_order'] ?>× pesan</td>
            <td class="kanan" style="font-weight:700;color:var(--sukses);"><?= rp($p['total_belanja']) ?></td>
            <td class="kanan"><?= rp($ratapeso) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── RATING & ULASAN PERIODE ────────────────────────────────────────────── -->
  <div class="kartu seksi-laporan" id="seksi-rating" style="margin-bottom:18px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
      <h3 style="margin:0;"><i class="fa-solid fa-star"></i> Rating &amp; Ulasan — <?= $labelprd ?></h3>
      <button onclick="eksporXlsSeksi('seksi-rating','rating_ulasan')" class="tombolkecil takprint" style="background:var(--sukses);color:white;"><i class="fa-solid fa-file-csv"></i> Cetak</button>
    </div>

    <!-- ringkasan rata-rata: angka besar + bintang + jumlah ulasan (sederhana) -->
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--latar);">
      <div style="font-size:42px;font-weight:900;color:var(--kedua);line-height:1;"><?= $ratarating ?: '—' ?></div>
      <div>
        <?php if ($ratarating > 0): ?><div style="margin-bottom:3px;"><?= bintanghtml($ratarating) ?></div><?php endif; ?>
        <div style="font-size:12px;color:var(--tekssamar);"><?= $jmlrating ?> ulasan periode ini</div>
      </div>
    </div>

    <!-- tabel distribusi rating per bintang -->
    <?php if ($jmlrating > 0): ?>
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--tekssamar);margin-bottom:8px;">Distribusi Rating</div>
    <div class="tabel-wrapper" style="margin-bottom:14px;">
      <table>
        <thead>
          <tr><th style="width:80px;">Bintang</th><th class="tengah" style="width:140px;">Jumlah Ulasan</th><th class="kanan">Persentase</th></tr>
        </thead>
        <tbody>
          <?php for ($bin=5;$bin>=1;$bin--):
            $j = $distribusirating[$bin] ?? 0;
            $pct = $jmlrating > 0 ? round($j / $jmlrating * 100, 1) : 0;
          ?>
          <tr>
            <td style="font-weight:700;"><?= $bin ?> bintang</td>
            <td class="tengah"><?= $j ?> ulasan</td>
            <td class="kanan"><?= $pct ?>%</td>
          </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- tabel ulasan terbaru — beserta menu yang dipesan saat rating diberikan -->
    <?php if (!empty($ulasanterbaru)): ?>
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--tekssamar);margin-bottom:8px;">Daftar Ulasan</div>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th style="width:120px;">Pembeli</th>
            <th class="tengah" style="width:70px;">Rating</th>
            <th style="width:180px;">Menu Dipesan</th>
            <th>Ulasan</th>
            <th class="tengah" style="width:100px;">Tanggal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ulasanterbaru as $ul): ?>
          <tr>
            <td style="font-weight:700;"><?= htmlspecialchars($ul['username']) ?></td>
            <td class="tengah" style="font-weight:700;color:#D97706;"><?= (int)$ul['rating_toko'] ?>★</td>
            <td style="font-size:12px;"><?= htmlspecialchars($ul['menu_dipesan'] ?? '—') ?></td>
            <td style="font-size:12px;font-style:italic;"><?= !empty($ul['ulasan']) ? '"' . htmlspecialchars($ul['ulasan']) . '"' : '—' ?></td>
            <td class="tengah" style="font-size:11px;color:var(--tekssamar);"><?= !empty($ul['created']) ? date('d M Y', strtotime($ul['created'])) : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── PRODUK TERLARIS — tabel rank, nama, terjual, omset ────────────────── -->
  <div class="kartu seksi-laporan" id="seksi-terlaris" style="margin-bottom:18px;">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-fire"></i> Produk Terlaris — <?= $labelprd ?></h3>
      <button onclick="eksporXlsSeksi('seksi-terlaris','produk_terlaris')" class="tombolkecil takprint" style="background:var(--sukses);color:white;"><i class="fa-solid fa-file-csv"></i> Cetak</button>
    </div>
    <?php if (empty($terlaris)): ?>
    <div class="kosong" style="padding:24px;"><p>Belum ada data produk terjual periode ini</p></div>
    <?php else: ?>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th class="tengah" style="width:50px;">Rank</th>
            <th>Nama Menu</th>
            <th class="tengah" style="width:100px;">Terjual</th>
            <th class="kanan" style="width:140px;">Total Omset</th>
          </tr>
        </thead>
        <tbody>
          <?php $medalwarna = ['emas','perak','perunggu']; ?>
          <?php foreach ($terlaris as $i => $t): ?>
          <tr>
            <td class="tengah"><div class="rangking-produk <?= $medalwarna[$i] ?? '' ?>" style="display:inline-block;">#<?= $i+1 ?></div></td>
            <td style="font-weight:700;"><?= htmlspecialchars($t['nama_menu']) ?></td>
            <td class="tengah" style="font-weight:700;color:var(--utama);"><?= (int)$t['terjual'] ?> porsi</td>
            <td class="kanan" style="font-weight:700;color:var(--sukses);"><?= rp($t['omset']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── DAFTAR MENU — tabel lengkap semua menu termasuk yang sudah dihapus ── -->
  <div class="kartu seksi-laporan" id="seksi-menu" style="margin-bottom:18px;">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-utensils"></i> Daftar Menu (<?= count($daftarmenu) ?> item)</h3>
      <button onclick="eksporXlsSeksi('seksi-menu','daftar_menu')" class="tombolkecil takprint" style="background:var(--sukses);color:white;"><i class="fa-solid fa-file-csv"></i> Cetak</button>
    </div>
    <?php if (empty($daftarmenu)): ?>
    <div class="kosong" style="padding:24px;"><p>Belum ada menu</p></div>
    <?php else: ?>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th>Nama Menu</th>
            <th class="kanan" style="width:110px;">Harga</th>
            <th class="tengah" style="width:100px;">Status</th>
            <th class="tengah" style="width:120px;">Terjual Periode</th>
            <th class="tengah" style="width:130px;">Terjual Semua Waktu</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftarmenu as $m): ?>
          <tr style="<?= $m['deleted'] ? 'opacity:.5;' : '' ?>">
            <td style="font-weight:600;">
              <?= htmlspecialchars($m['nama_menu']) ?>
              <?php if ($m['deleted']): ?>
              <span style="font-size:10px;color:#dc2626;font-weight:400;margin-left:4px;">(dihapus)</span>
              <?php endif; ?>
            </td>
            <td class="kanan"><?= rp((float)$m['harga']) ?></td>
            <td class="tengah">
              <span class="badge <?= $m['deleted'] ? 'dibatalkan' : ($m['status']==='aktif'?'selesai':'dibatalkan') ?>">
                <?= $m['deleted'] ? 'Dihapus' : ucfirst($m['status']) ?>
              </span>
            </td>
            <td class="tengah" style="font-weight:700;color:var(--utama);"><?= (int)$m['terjual_periode'] ?>×</td>
            <td class="tengah"><?= (int)$m['terjual_total'] ?>×</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── DETAIL PESANAN SELESAI & DIBATALKAN ─────────────────────────────────
       satu baris per pesanan, lengkap dengan rincian menu yang dipesan.
       konsisten dengan format Detail Pesanan di laporan platform admin. -->
  <div class="kartu seksi-laporan" id="seksi-detail">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-list-check"></i> Detail Pesanan Selesai &amp; Dibatalkan</h3>
      <div style="display:flex;gap:6px;align-items:center;">
        <span style="font-size:11px;color:var(--tekssamar);" class="takprint"><?= count($pesanandetail) ?> pesanan</span>
        <button onclick="eksporXlsSeksi('seksi-detail','detail_pesanan')" class="tombolkecil takprint" style="background:var(--sukses);color:white;"><i class="fa-solid fa-file-csv"></i> Cetak</button>
      </div>
    </div>
    <?php if (empty($pesanandetail)): ?>
    <div class="kosong" style="padding:24px;"><p>Belum ada pesanan selesai atau dibatalkan pada periode ini</p></div>
    <?php else: ?>
    <div class="tabel-wrapper">
      <table style="min-width:720px;">
        <thead>
          <tr>
            <th class="tengah" style="width:40px;">ID</th>
            <th style="width:130px;">Tanggal</th>
            <th>Pembeli</th>
            <th class="tengah" style="width:90px;">Status</th>
            <th>Rincian Menu</th>
            <th style="text-align:right;width:110px;">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pesanandetail as $po): ?>
          <tr>
            <td class="tengah" style="font-weight:700;color:var(--tekssamar);font-size:11px;">#<?= $po['id'] ?></td>
            <td style="font-size:11px;color:var(--tekssamar);white-space:nowrap;"><?= $po['tanggal'] ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($po['pembeli']) ?></td>
            <td class="tengah">
              <span class="badge <?= $po['status']==='Selesai'?'selesai':'dibatalkan' ?>"><?= $po['status'] ?></span>
            </td>
            <td style="font-size:12px;">
              <?php if (empty($po['items'])): ?>
              <span style="color:var(--tekssamar);font-style:italic;">—</span>
              <?php else: ?>
              <?php foreach ($po['items'] as $item): ?>
              <div style="line-height:1.7;">
                <strong><?= htmlspecialchars($item['nama']) ?></strong>
                <span style="color:var(--tekssamar);">×<?= $item['jumlah'] ?></span>
                <span style="color:var(--tekssamar);">@ <?= rp($item['harga']) ?></span>
                = <span style="color:var(--utama);font-weight:700;"><?= rp($item['subtotal']) ?></span>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </td>
            <td style="text-align:right;font-weight:800;color:<?= $po['status']==='Selesai'?'var(--sukses)':'var(--gagal,#dc2626)' ?>;">
              <?= rp($po['total']) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <?php
          // hitung total per status dari array $pesanandetail
          $totselesai    = array_sum(array_map(fn($p)=>$p['status']==='Selesai'   ?$p['total']:0, $pesanandetail));
          $totdibatalkan = array_sum(array_map(fn($p)=>$p['status']==='Dibatalkan'?$p['total']:0, $pesanandetail));
          ?>
          <tr>
            <td colspan="5" style="font-weight:700;background:var(--latar);padding:10px 16px;">TOTAL SELESAI</td>
            <td style="text-align:right;font-weight:800;background:var(--latar);padding:10px 16px;color:var(--sukses);"><?= rp($totselesai) ?></td>
          </tr>
          <tr>
            <td colspan="5" style="font-weight:700;background:var(--latar);padding:10px 16px;">TOTAL DIBATALKAN</td>
            <td style="text-align:right;font-weight:800;background:var(--latar);padding:10px 16px;color:var(--gagal,#dc2626);"><?= rp($totdibatalkan) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div>

</main>

<script>
/* ===== EKSPOR XLS (HTML-in-Excel) — TABEL BERGARIS DENGAN IDENTITAS =====
   Strategi: bungkus HTML table dalam file ekstensi .xls dengan mime type
   application/vnd.ms-excel. Excel akan buka sebagai workbook dengan border yang
   sudah di-style. Lebih bagus dari CSV biasa karena ada visual table + header
   identitas (toko, penjual, periode, dst) langsung tampil di atas data. */

// objek identitas yang di-inject ke tiap file ekspor (judul, kantin, penjual, periode)
var IDENTITAS = {
  judul:   'Laporan Penjualan',
  kantin:  <?= json_encode('Kantin — ' . ($_SESSION['nama_toko'] ?? '—')) ?>,
  penjual: <?= json_encode($_SESSION['username'] ?? '') ?>,
  periode: <?= json_encode($labelprd) ?>,
};

/* buildIdentitasHtml(): bangun header identitas (banner judul + tabel info)
   yang ditaruh di atas data ekspor. memuat: section, kantin, penjual, periode, tanggal cetak. */
function buildIdentitasHtml(judulSection) {
  var tgl = new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});
  var cssLabel = 'border:1px solid #999;padding:6pt 10pt;background:#F8EBF1;font-weight:bold;width:160px;';
  var cssNilai = 'border:1px solid #999;padding:6pt 10pt;';
  var html = '<table style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:11pt;margin-bottom:10pt;width:100%;">';
  html += '<tr><td colspan="2" style="background:#643843;color:white;font-weight:bold;font-size:14pt;text-align:center;padding:10pt;border:1px solid #444;">jajankita &mdash; ' + IDENTITAS.judul + '</td></tr>';
  if (judulSection)        html += '<tr><td style="'+cssLabel+'">Section</td><td style="'+cssNilai+'">' + judulSection + '</td></tr>';
  if (IDENTITAS.kantin)    html += '<tr><td style="'+cssLabel+'">Kantin/Toko</td><td style="'+cssNilai+'">' + IDENTITAS.kantin + '</td></tr>';
  if (IDENTITAS.penjual)   html += '<tr><td style="'+cssLabel+'">Penjual</td><td style="'+cssNilai+'">' + IDENTITAS.penjual + '</td></tr>';
  if (IDENTITAS.periode)   html += '<tr><td style="'+cssLabel+'">Periode</td><td style="'+cssNilai+'">' + IDENTITAS.periode + '</td></tr>';
  html += '<tr><td style="'+cssLabel+'">Tanggal Cetak</td><td style="'+cssNilai+'">' + tgl + '</td></tr>';
  html += '</table>';
  return html;
}

/* tableToBorderedHtml(): clone table lalu inject border + padding inline supaya Excel
   render bergaris rapi. style: header coklat tua + teks putih, body zebra stripe,
   tfoot pink muda bold. ikon font-awesome dihapus karena bikin sel berantakan. */
function tableToBorderedHtml(table) {
  var clone = table.cloneNode(true);
  clone.setAttribute('border', '1');
  clone.setAttribute('cellpadding', '6');
  clone.setAttribute('cellspacing', '0');
  clone.setAttribute('style', 'border-collapse:collapse;font-family:Arial,sans-serif;font-size:11pt;width:100%;margin-bottom:8pt;');
  // style header tabel (warna utama)
  clone.querySelectorAll('th').forEach(function(th){
    th.setAttribute('style', 'background:#643843;color:white;border:1px solid #3d2230;padding:8pt 10pt;text-align:left;font-weight:bold;');
  });
  // body tabel + zebra stripe pada baris ganjil
  clone.querySelectorAll('tbody tr').forEach(function(tr, i){
    var bg = i % 2 === 1 ? 'background:#FAF6F8;' : '';
    tr.querySelectorAll('td').forEach(function(td){
      td.setAttribute('style', 'border:1px solid #c8c8c8;padding:6pt 10pt;vertical-align:top;' + bg);
    });
  });
  // footer tabel (baris total) — background pink + bold
  clone.querySelectorAll('tfoot td').forEach(function(td){
    td.setAttribute('style', 'border:1px solid #999;padding:7pt 10pt;background:#F8EBF1;font-weight:bold;');
  });
  // buang ikon font-awesome supaya tidak muncul karakter aneh di Excel
  clone.querySelectorAll('i').forEach(function(ic){ ic.remove(); });
  return clone.outerHTML;
}

/* unduhXls(): bungkus html body dalam dokumen html mini lalu trigger download .xls.
   excel akan baca content type vnd.ms-excel dan render sebagai spreadsheet. */
function unduhXls(htmlBody, namafile) {
  var doc = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">'
          + '<head><meta charset="UTF-8"></head><body>' + htmlBody + '</body></html>';
  var blob = new Blob(['﻿' + doc], { type: 'application/vnd.ms-excel' });
  var url  = URL.createObjectURL(blob);
  var a    = document.createElement('a');
  var tgl  = new Date().toISOString().slice(0,10);
  a.href = url; a.download = namafile + '_' + tgl + '.xls';
  document.body.appendChild(a); a.click(); document.body.removeChild(a);
  setTimeout(function(){ URL.revokeObjectURL(url); }, 100);
}

/* eksporXlsSeksi(): ekspor 1 section saja — identitas + judul section + tabel di dalamnya. */
function eksporXlsSeksi(idSection, namafile) {
  var section = document.getElementById(idSection);
  if (!section) return;
  var tables = section.querySelectorAll('table');
  if (!tables.length) { alert('Tidak ada tabel data di section ini.'); return; }
  var judul = section.querySelector('h3') ? section.querySelector('h3').innerText.trim() : '';
  var html = buildIdentitasHtml(judul);
  tables.forEach(function(t){ html += tableToBorderedHtml(t) + '<br>'; });
  unduhXls(html, namafile);
}

/* eksporXlsSemua(): ekspor SEMUA section yang punya tabel ke 1 file ekspor lengkap.
   tiap section dipisah dengan bar judul coklat tua + spasi vertikal supaya rapi. */
function eksporXlsSemua(namafile) {
  var html = buildIdentitasHtml('Laporan Lengkap');
  document.querySelectorAll('.seksi-laporan').forEach(function(section){
    var tables = section.querySelectorAll('table');
    if (!tables.length) return;
    var judul = section.querySelector('h3') ? section.querySelector('h3').innerText.trim() : '';
    html += '<div style="height:14pt;"></div>';
    html += '<table style="border-collapse:collapse;width:100%;margin-bottom:4pt;"><tr><td style="background:#643843;color:white;font-family:Arial,sans-serif;font-size:13pt;font-weight:bold;padding:8pt 12pt;border:1px solid #3d2230;">' + judul + '</td></tr></table>';
    tables.forEach(function(t){ html += tableToBorderedHtml(t); });
  });
  unduhXls(html, namafile);
}
</script>
</body>
</html>
