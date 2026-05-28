<?php
/* halaman dashboard penjual — menampilkan statistik, grafik pendapatan,
   pesanan terbaru, produk terlaris, pelanggan setia, dan ulasan terbaru */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

// ambil id toko dan status toko dari session
$idtoko     = (int)$_SESSION['id_toko'];
$statustoko = $_SESSION['status_toko'] ?? 'buka';
// id penjual untuk isolasi data — memastikan hanya data milik penjual ini yang tampil
$idpenjual  = (int)$_SESSION['id_user'];

// tandai halaman aktif untuk navbar
$halamansaatini = 'index';

// hitung total pesanan yang masuk hari ini untuk penjual ini
$q1 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_penjual=? AND DATE(tanggal_order)=CURDATE() AND deleted=0");
$q1->bind_param("i", $idpenjual); $q1->execute();
$pesananhari = (int)$q1->get_result()->fetch_row()[0]; $q1->close();

// hitung pesanan yang statusnya masih "menunggu" konfirmasi penjual
$q2 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_penjual=? AND status_order='Menunggu' AND deleted=0");
$q2->bind_param("i", $idpenjual); $q2->execute();
$pesananmenunggu = (int)$q2->get_result()->fetch_row()[0]; $q2->close();

// hitung pesanan yang sedang diproses (sudah diterima, belum siap)
$q2b = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_penjual=? AND status_order='Diproses' AND deleted=0");
$q2b->bind_param("i", $idpenjual); $q2b->execute();
$pesanandiproses = (int)$q2b->get_result()->fetch_row()[0]; $q2b->close();

// hitung total pendapatan hari ini — hanya pesanan yang sudah selesai
// COALESCE mengembalikan 0 jika tidak ada data (agar tidak null)
$q3 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_penjual=? AND DATE(tanggal_order)=CURDATE() AND status_order='Selesai' AND deleted=0");
$q3->bind_param("i", $idpenjual); $q3->execute();
$pendapatanhari = (float)$q3->get_result()->fetch_row()[0]; $q3->close();

// ambil rata-rata rating dan jumlah ulasan untuk penjual ini
$q4 = $conn->prepare("SELECT ROUND(AVG(rating_toko),1), COUNT(*) FROM tb_rating WHERE id_penjual=? AND deleted=0");
$q4->bind_param("i", $idpenjual); $q4->execute();
$ratingrow  = $q4->get_result()->fetch_row(); $q4->close();
$ratarating = (float)($ratingrow[0] ?? 0);
$jmlrating  = (int)($ratingrow[1] ?? 0);

// hitung total menu yang statusnya aktif (yang bisa dipesan pembeli) — menu milik slot toko
$q5 = $conn->prepare("SELECT COUNT(*) FROM tb_menu WHERE id_toko=? AND status='aktif' AND deleted=0");
$q5->bind_param("i", $idtoko); $q5->execute();
$totalmenu = (int)$q5->get_result()->fetch_row()[0]; $q5->close();

// breakdown status pesanan — semua waktu (Menunggu/Diproses/Siap/Selesai/Dibatalkan)
// dipakai di section breakdown supaya penjual cepat tahu komposisi pesanannya
$qbreak = $conn->prepare("SELECT status_order, COUNT(*) AS jml, COALESCE(SUM(total_harga),0) AS total
                          FROM tb_order WHERE id_penjual=? AND deleted=0
                          GROUP BY status_order");
$qbreak->bind_param("i", $idpenjual); $qbreak->execute();
$breakdownstatus = [
    'Menunggu'     => ['jml'=>0,'total'=>0],
    'Diproses'     => ['jml'=>0,'total'=>0],
    'Siap Diambil' => ['jml'=>0,'total'=>0],
    'Selesai'      => ['jml'=>0,'total'=>0],
    'Dibatalkan'   => ['jml'=>0,'total'=>0],
];
$res = $qbreak->get_result();
while ($r = $res->fetch_assoc()) {
    if (isset($breakdownstatus[$r['status_order']])) {
        $breakdownstatus[$r['status_order']]['jml']   = (int)$r['jml'];
        $breakdownstatus[$r['status_order']]['total'] = (float)$r['total'];
    }
}
$qbreak->close();

// fungsi bantu: ubah kode hari inggris (Sun, Mon, dst) ke singkatan hari indonesia
$periodehari = 7;
function namahari(string $tgl): string {
    $map = ['Sun'=>'Min','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab'];
    return $map[date('D', strtotime($tgl))] ?? date('D', strtotime($tgl));
}

// tentukan rentang tanggal: dari 6 hari lalu sampai hari ini
$tglchart7dari   = date('Y-m-d', strtotime('-6 days'));
$tglchart7sampai = date('Y-m-d');

// ambil total pendapatan per hari dari database, dikelompokkan per tanggal
$qchart = $conn->prepare("SELECT DATE(tanggal_order) AS tgl, COALESCE(SUM(total_harga),0) AS nilai FROM tb_order WHERE id_penjual=? AND DATE(tanggal_order) BETWEEN ? AND ? AND status_order='Selesai' AND deleted=0 GROUP BY DATE(tanggal_order)");
$qchart->bind_param("iss", $idpenjual, $tglchart7dari, $tglchart7sampai); $qchart->execute();
$rawchart = []; $resc = $qchart->get_result();

// simpan hasil query ke array asosiatif dengan kunci tanggal
while ($row = $resc->fetch_assoc()) $rawchart[$row['tgl']] = (float)$row['nilai'];
$qchart->close();

// buat array data chart lengkap 7 hari — tanggal yang tidak ada di db diisi 0
$chartdata = [];
for ($i = $periodehari - 1; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $chartdata[] = ['tgl'=>$tgl,'label'=>date('d/m',strtotime($tgl)),'nilai'=>$rawchart[$tgl]??0.0];
}

// cari nilai terbesar untuk skala sumbu y chart (minimal 1 agar tidak bagi 0)
$maxnilai = max(array_column($chartdata, 'nilai')) ?: 1;

// ambil 5 pesanan terbaru — pesanan "menunggu" dan "diproses" diurutkan lebih atas
// FIELD() membuat urutan kustom: menunggu=1, diproses=2, dst
$qp = $conn->prepare("SELECT o.id_order, o.tanggal_order, o.total_harga, o.status_order, o.metode_pembayaran, u.username
                       FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user
                       WHERE o.id_penjual=? AND o.deleted=0
                       ORDER BY FIELD(o.status_order,'Menunggu','Diproses','Siap Diambil','Selesai','Dibatalkan'),
                                o.tanggal_order DESC
                       LIMIT 5");
$qp->bind_param("i", $idpenjual); $qp->execute();
$pesananterbaru = $qp->get_result()->fetch_all(MYSQLI_ASSOC); $qp->close();

// ambil 5 produk terlaris — hanya yang benar-benar terjual (Selesai)
$qtl = $conn->prepare("SELECT m.nama_menu, SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omset
                        FROM tb_detail_order d
                        JOIN tb_menu m ON d.id_menu=m.id_menu
                        JOIN tb_order o ON d.id_order=o.id_order
                        WHERE o.id_penjual=? AND o.deleted=0 AND d.deleted=0
                          AND o.status_order='Selesai'
                        GROUP BY m.id_menu, m.nama_menu
                        ORDER BY terjual DESC LIMIT 5");
$qtl->bind_param("i", $idpenjual); $qtl->execute();
$terlaris = $qtl->get_result()->fetch_all(MYSQLI_ASSOC); $qtl->close();

// top 5 pelanggan — gabung: terbanyak pesan dulu, lalu total belanja sebagai tie-breaker
$qsetia = $conn->prepare("SELECT u.username, COUNT(o.id_order) AS jml_order, COALESCE(SUM(o.total_harga),0) AS total_belanja
                          FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user
                          WHERE o.id_penjual=? AND o.status_order='Selesai' AND o.deleted=0
                          GROUP BY o.id_user, u.username
                          ORDER BY jml_order DESC, total_belanja DESC LIMIT 5");
$qsetia->bind_param("i", $idpenjual); $qsetia->execute();
$pelanggansetia = $qsetia->get_result()->fetch_all(MYSQLI_ASSOC); $qsetia->close();

// ambil 5 ulasan terbaru beserta nama pembeli yang memberikan ulasan
$qr = $conn->prepare("SELECT r.rating_toko, r.ulasan, r.created, u.username
                       FROM tb_rating r JOIN tb_user u ON r.id_user=u.id_user
                       WHERE r.id_penjual=? AND r.deleted=0
                       ORDER BY r.created DESC LIMIT 5");
$qr->bind_param("i", $idpenjual); $qr->execute();
$ulasanterbaru = $qr->get_result()->fetch_all(MYSQLI_ASSOC); $qr->close();

// format angka ke rupiah, contoh: 15000 → "Rp 15.000"
function rp(float $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// format angka besar ke singkatan: 1.500.000 → "Rp 1,5 Jt", 2.000.000.000 → "Rp 2 M"
function singkat(float $n): string {
    if ($n >= 1_000_000_000) { $v=$n/1_000_000_000; return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' M'; }
    if ($n >= 1_000_000)     { $v=$n/1_000_000;     return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' Jt'; }
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// kembalikan nama kelas CSS berdasarkan status pesanan (dipakai untuk pewarnaan badge)
function kelasstatus(string $s): string {
    return match($s) {
        'Menunggu' => 'menunggu', 'Diproses' => 'diproses',
        'Siap Diambil' => 'siap', 'Selesai' => 'selesai',
        default => 'dibatalkan',
    };
}

// buat tampilan ikon bintang penuh (kuning) dan kosong (abu) sesuai nilai rating
function bintang(float $r): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $r
            ? '<i class="fa-solid fa-star" style="color:#F59E0B;font-size:13px;"></i>'
            : '<i class="fa-regular fa-star" style="color:#D1D5DB;font-size:13px;"></i>';
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten">

  <!-- judul halaman dan tombol toggle buka/tutup toko -->
  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-gauge-high"></i> Dashboard</h1>
      <p>Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?> — <?= date('l, d M Y') ?></p>
    </div>
    <!-- toggle buka/tutup toko — tanpa js, pakai submit button -->
    <form method="POST" action="prosesindex.php">
      <input type="hidden" name="aksi" value="toggle_status">
      <button type="submit" class="tombol-toggle-toko <?= $statustoko === 'buka' ? 'toko-buka' : 'toko-tutup' ?>">
        <i class="fa-solid fa-circle-dot"></i>
        Toko <?= $statustoko === 'buka' ? 'Buka' : 'Tutup' ?>
        <span style="font-size:11px;font-weight:500;opacity:.8;">
          — klik untuk <?= $statustoko === 'buka' ? 'tutup' : 'buka' ?>
        </span>
      </button>
    </form>
  </div>

  <!-- peringatan muncul hanya jika ada pesanan yang perlu ditangani -->
  <?php if ($pesananmenunggu > 0 || $pesanandiproses > 0): ?>
  <div class="peringatan peringatantunggu">
    <i class="fa-solid fa-bell"></i>
    <span>
      <?php if ($pesananmenunggu > 0): ?>
        <strong><?= $pesananmenunggu ?> pesanan menunggu</strong> konfirmasimu<?= $pesanandiproses > 0 ? ' &amp; ' : '' ?>
      <?php endif; ?>
      <?php if ($pesanandiproses > 0): ?>
        <strong><?= $pesanandiproses ?> pesanan sedang diproses</strong>
      <?php endif; ?>
    </span>
    <a href="../manajemenpesanan/manajemenpesanan.php?filter=Menunggu"
       style="margin-left:8px; font-weight:700; color:var(--tunggu); text-decoration:underline;">
      Lihat Sekarang
    </a>
  </div>
  <?php endif; ?>

  <!-- kartu statistik utama — setiap kartu link ke halaman detail terkait -->
  <div class="grid-stat">
    <a href="../manajemenpesanan/manajemenpesanan.php" class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-clock"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $pesananhari ?></div>
        <div class="label">Pesanan Hari Ini</div>
        <?php if ($pesananmenunggu > 0): ?>
        <div class="tren naik"><?= $pesananmenunggu ?> menunggu konfirmasi</div>
        <?php endif; ?>
      </div>
    </a>
    <a href="../laporan/laporan.php" class="kartu-stat">
      <div class="ikon-stat" style="background:var(--suksebg);color:var(--sukses);"><i class="fa-solid fa-coins"></i></div>
      <div class="isi-stat">
        <div class="nilai" style="color:var(--sukses);"><?= singkat($pendapatanhari) ?></div>
        <div class="label">Omset Hari Ini</div>
        <div class="tren" style="color:var(--tekssamar);">Pesanan selesai</div>
      </div>
    </a>
    <a href="../ulasan/ulasan.php" class="kartu-stat">
      <div class="ikon-stat" style="background:#fffbeb;color:#D97706;"><i class="fa-solid fa-star"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $ratarating > 0 ? $ratarating : '—' ?></div>
        <div class="label">Rating Toko</div>
        <div class="tren" style="color:var(--tekssamar);"><?= $jmlrating ?> ulasan</div>
      </div>
    </a>
    <a href="../manajemenmenu/manajemenmenu.php" class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-bowl-food"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $totalmenu ?></div>
        <div class="label">Menu Aktif</div>
        <div class="tren" style="color:var(--tekssamar);">Kelola menu</div>
      </div>
    </a>
  </div>

  <!-- diagram batang pendapatan 7 hari terakhir dalam format SVG murni (tanpa library chart) -->
  <div class="kartu">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <h3 style="margin:0;border:none;padding:0;"><i class="fa-solid fa-chart-bar"></i> Omset — 7 Hari Terakhir</h3>
      <a href="../laporan/laporan.php" style="font-size:12px;color:var(--kedua);font-weight:600;white-space:nowrap;">
        Lihat Semua Laporan →
      </a>
    </div>
    <div class="area-chart">
      <?php
      // hitung dimensi bar chart: lebar svg, lebar setiap batang, dan jarak antar batang
      $jumlahbar = count($chartdata);
      $svgw  = max(700, $jumlahbar * 30);
      $barw  = max(8, min(40, (int)(($svgw - 80) / $jumlahbar) - 4));
      $gap   = max(4, (int)(($svgw - 80 - $jumlahbar * $barw) / max(1, $jumlahbar - 1)));
      $startx = 70;
      $chartH = 160;
      ?>
      <svg viewBox="0 0 <?= $svgw ?> 210" xmlns="http://www.w3.org/2000/svg" style="min-width:<?= min(700,$svgw) ?>px;">
        <?php
        // gambar 5 garis horizontal grid beserta label nilai di sisi kiri
        for ($g = 0; $g <= 4; $g++):
          $y = 20 + ($g * 40); ?>
        <line x1="60" y1="<?= $y ?>" x2="<?= $svgw - 10 ?>" y2="<?= $y ?>" stroke="#E7CBCB" stroke-width="1" stroke-dasharray="4,4"/>
        <text x="55" y="<?= $y + 4 ?>" text-anchor="end" fill="#99627A" font-size="9">
          <?= rp(($maxnilai / 4) * (4 - $g)) ?>
        </text>
        <?php endfor; ?>

        <?php
        // gambar setiap batang — hari ini diberi warna lebih gelap
        foreach ($chartdata as $i => $d):
          $x     = $startx + $i * ($barw + $gap);
          // tinggi batang proporsional terhadap nilai maksimum; minimal 2px agar tetap terlihat
          $barh  = $d['nilai'] > 0 ? ($d['nilai'] / $maxnilai) * $chartH : 2;
          $y     = 180 - $barh;
          $isToday = $d['tgl'] === date('Y-m-d');
          $barcolor = $isToday ? '#643843' : '#99627A';
        ?>
        <g class="bar-chart-group">
          <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $barw ?>" height="<?= $barh ?>"
                rx="3" fill="<?= $barcolor ?>" class="bar-fill">
            <!-- tooltip muncul saat kursor diarahkan ke batang -->
            <title><?= $d['label'] ?> — <?= rp($d['nilai']) ?></title>
          </rect>
          <!-- label hari di bawah batang -->
          <text x="<?= $x + $barw/2 ?>" y="200" text-anchor="middle"
                fill="<?= $isToday ? '#643843' : '#99627A' ?>"
                font-size="<?= $periodehari > 14 ? '8' : '10' ?>"
                font-weight="<?= $isToday ? '700' : '400' ?>">
            <?= $d['label'] ?>
          </text>
          <?php
          // tampilkan label nilai di atas batang jika batang cukup lebar dan ada nilai
          if ($d['nilai'] > 0 && $barw >= 20): ?>
          <text x="<?= $x + $barw/2 ?>" y="<?= max($y - 4, 14) ?>" text-anchor="middle"
                fill="#643843" font-size="8" font-weight="600">
            <?php $_n=$d['nilai']; echo $_n>=1000000 ? number_format($_n/1000000,1).'Jt' : ($_n>=1000 ? number_format($_n/1000,0).'k' : number_format($_n,0)); ?>
          </text>
          <?php endif; ?>
        </g>
        <?php endforeach; ?>
      </svg>
    </div>
  </div>

  <!-- ── BREAKDOWN STATUS PESANAN — list ringkas (sama pola Pesanan Terbaru/Top Pelanggan) ── -->
  <div class="kartu" style="margin-bottom:14px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <h3 style="margin:0;border:none;padding:0;"><i class="fa-solid fa-layer-group"></i> Breakdown Status Pesanan</h3>
      <a href="../manajemenpesanan/manajemenpesanan.php" style="font-size:12px;color:var(--kedua);font-weight:600;">Lihat Detail →</a>
    </div>
    <?php
    // map status → kelas badge sama persis dengan list Pesanan
    $petastatus = [
        'Menunggu'     => 'menunggu',
        'Diproses'     => 'diproses',
        'Siap Diambil' => 'siap',
        'Selesai'      => 'selesai',
        'Dibatalkan'   => 'dibatalkan',
    ];
    foreach ($breakdownstatus as $stat => $data):
        $kelas = $petastatus[$stat];
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--latar);">
      <div>
        <span class="badge <?= $kelas ?>"><?= $stat ?></span>
        <div style="font-size:11px;color:var(--tekssamar);margin-top:4px;"><?= $data['jml'] ?> pesanan</div>
      </div>
      <div style="font-size:13px;font-weight:700;color:var(--utama);"><?= rp($data['total']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- grid dua kolom: pesanan terbaru + produk terlaris + top pelanggan + ulasan terbaru -->
  <div class="grid-dua">

    <!-- kartu pesanan terbaru — pesanan menunggu/diproses diutamakan di atas -->
    <div class="kartu">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <h3 style="margin:0;border:none;padding:0;"><i class="fa-solid fa-receipt"></i> Pesanan Terbaru</h3>
        <a href="../manajemenpesanan/manajemenpesanan.php" style="font-size:12px;color:var(--kedua);font-weight:600;">Lihat Semua →</a>
      </div>
      <?php if (empty($pesananterbaru)): ?>
      <div class="kosong" style="padding:20px;">
        <div class="ikon-kosong"><i class="fa-solid fa-receipt"></i></div>
        <p>Belum ada pesanan</p>
      </div>
      <?php else: ?>
      <?php foreach ($pesananterbaru as $p): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--latar);">
        <div>
          <!-- nomor pesanan diformat 6 digit dengan awalan EK- -->
          <div style="font-size:13px;font-weight:700;">EK-<?= str_pad($p['id_order'],6,'0',STR_PAD_LEFT) ?></div>
          <div style="font-size:12px;color:var(--tekssamar);">
            <?= htmlspecialchars($p['username']) ?> &middot; <?= date('H:i', strtotime($p['tanggal_order'])) ?>
          </div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:13px;font-weight:700;color:var(--utama);"><?= rp($p['total_harga']) ?></div>
          <span class="badge <?= kelasstatus($p['status_order']) ?>"><?= $p['status_order'] ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <a href="../manajemenpesanan/manajemenpesanan.php"
         class="tombolringan blok mt14">
        <i class="fa-solid fa-list"></i> Lihat Semua Pesanan
      </a>
      <?php endif; ?>
    </div>

    <!-- kartu produk terlaris — 3 teratas diberi warna medali emas/perak/perunggu -->
    <div class="kartu">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <h3 style="margin:0;border:none;padding:0;"><i class="fa-solid fa-fire"></i> Produk Terlaris</h3>
        <a href="../laporan/laporan.php" style="font-size:12px;color:var(--kedua);font-weight:600;">Lihat Detail →</a>
      </div>
      <?php if (empty($terlaris)): ?>
      <div class="kosong" style="padding:20px;">
        <p>Belum ada data penjualan</p>
      </div>
      <?php else: ?>
      <?php $medalwarna = ['emas','perak','perunggu']; ?>
      <?php foreach ($terlaris as $i => $t): ?>
      <div class="baris-produk">
        <div class="rangking-produk <?= $medalwarna[$i] ?? '' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;">
          <div style="font-size:13px;font-weight:700;"><?= htmlspecialchars($t['nama_menu']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);"><?= rp($t['omset']) ?> total omset</div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);"><?= $t['terjual'] ?> terjual</div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- kartu pelanggan setia — diurutkan dari yang paling banyak belanja -->
    <div class="kartu">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <h3 style="margin:0;border:none;padding:0;"><i class="fa-solid fa-trophy"></i> Top Pelanggan</h3>
        <a href="../laporan/laporan.php" style="font-size:12px;color:var(--kedua);font-weight:600;">Lihat Detail →</a>
      </div>
      <?php if (empty($pelanggansetia)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada data pelanggan</p></div>
      <?php else: ?>
      <?php $medal = ['emas','perak','perunggu']; ?>
      <?php foreach ($pelanggansetia as $i => $p): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--latar);">
        <div class="rangking-produk <?= $medal[$i]??'' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <!-- text-overflow ellipsis agar nama panjang tidak merusak layout -->
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['username']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);"><?= $p['jml_order'] ?> pesanan</div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;"><?= singkat($p['total_belanja']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- kartu rating dan ulasan terbaru — tampilkan rata-rata bintang + 5 ulasan terakhir -->
    <div class="kartu">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <h3 style="margin:0;border:none;padding:0;"><i class="fa-solid fa-star"></i> Rating &amp; Ulasan Terbaru</h3>
        <a href="../ulasan/ulasan.php" style="font-size:12px;color:var(--kedua);font-weight:600;">
          Lihat Semua <i class="fa-solid fa-arrow-right" style="font-size:10px;"></i>
        </a>
      </div>
      <?php
      // tampilkan nilai rata-rata rating hanya jika ada data rating
      if ($ratarating > 0): ?>
      <div style="text-align:center;padding:12px 0;border-bottom:1px solid var(--latar);margin-bottom:10px;">
        <div style="font-size:36px;font-weight:800;color:var(--utama);"><?= $ratarating ?></div>
        <div><?= bintang($ratarating) ?></div>
        <div style="font-size:12px;color:var(--tekssamar);margin-top:4px;"><?= $jmlrating ?> ulasan masuk</div>
      </div>
      <?php endif; ?>
      <?php if (empty($ulasanterbaru)): ?>
      <div class="kosong" style="padding:20px;">
        <p>Belum ada ulasan dari pembeli</p>
      </div>
      <?php else: ?>
      <?php foreach ($ulasanterbaru as $u): ?>
      <div style="padding:10px 0;border-bottom:1px solid var(--latar);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
          <span style="font-size:13px;font-weight:700;"><?= htmlspecialchars($u['username']) ?></span>
          <span><?= bintang($u['rating_toko']) ?></span>
        </div>
        <?php if (!empty($u['ulasan'])): ?>
        <div style="font-size:12px;color:var(--tekskecil);font-style:italic;">
          <!-- potong ulasan panjang di 120 karakter, sisanya diganti titik-titik -->
          "<?= htmlspecialchars(mb_substr($u['ulasan'], 0, 120)) ?><?= mb_strlen($u['ulasan']) > 120 ? '...' : '' ?>"
        </div>
        <?php endif; ?>
        <div style="font-size:10px;color:var(--tekssamar);margin-top:4px;">
          <?= date('d M Y', strtotime($u['created'])) ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

</main>

</body>
</html>
