<?php
/* ============================================================
   DASHBOARD PENJUAL
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idtoko   = (int)$_SESSION['id_toko'];
$statustoko = $_SESSION['status_toko'] ?? 'buka';
$halamansaatini = 'index';

// ==============================================================
// PROSES TOGGLE STATUS TOKO
// ==============================================================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'toggle_status') {
    $statusbaru = ($statustoko === 'buka') ? 'tutup' : 'buka';
    $upd = $conn->prepare("UPDATE tb_toko SET status_toko=? WHERE id_toko=?");
    $upd->bind_param("si", $statusbaru, $idtoko);
    $upd->execute();
    $upd->close();
    $_SESSION['status_toko'] = $statusbaru;
    header("Location: index.php");
    exit;
}

// ==============================================================
// AMBIL STATISTIK
// ==============================================================

// Pesanan hari ini
$q1 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_toko=? AND DATE(tanggal_order)=CURDATE() AND deleted=0");
$q1->bind_param("i", $idtoko); $q1->execute();
$pesananhari = (int)$q1->get_result()->fetch_row()[0]; $q1->close();

// Pesanan menunggu (perlu aksi)
$q2 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_toko=? AND status_order='Menunggu' AND deleted=0");
$q2->bind_param("i", $idtoko); $q2->execute();
$pesananmenunggu = (int)$q2->get_result()->fetch_row()[0]; $q2->close();

// Pendapatan hari ini
$q3 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_toko=? AND DATE(tanggal_order)=CURDATE() AND status_order='Selesai' AND deleted=0");
$q3->bind_param("i", $idtoko); $q3->execute();
$pendapatanhari = (float)$q3->get_result()->fetch_row()[0]; $q3->close();

// Rating rata-rata toko
$q4 = $conn->prepare("SELECT ROUND(AVG(rating_toko),1), COUNT(*) FROM tb_rating WHERE id_toko=?");
$q4->bind_param("i", $idtoko); $q4->execute();
$ratingrow = $q4->get_result()->fetch_row(); $q4->close();
$ratarating  = (float)($ratingrow[0] ?? 0);
$jmlrating   = (int)($ratingrow[1] ?? 0);

// Total menu aktif
$q5 = $conn->prepare("SELECT COUNT(*) FROM tb_menu WHERE id_toko=? AND status='aktif' AND deleted=0");
$q5->bind_param("i", $idtoko); $q5->execute();
$totalmenu = (int)$q5->get_result()->fetch_row()[0]; $q5->close();

// ==============================================================
// DATA CHART PENDAPATAN 7 HARI
// ==============================================================
$chartdata = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $qc = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_toko=? AND DATE(tanggal_order)=? AND status_order='Selesai' AND deleted=0");
    $qc->bind_param("is", $idtoko, $tgl);
    $qc->execute();
    $pendapatan = (float)$qc->get_result()->fetch_row()[0];
    $qc->close();
    $chartdata[] = ['tgl' => $tgl, 'label' => date('D', strtotime($tgl)), 'nilai' => $pendapatan];
}
$maxnilai = max(array_column($chartdata, 'nilai')) ?: 1;

// ==============================================================
// PESANAN TERBARU (5 terakhir)
// ==============================================================
$qp = $conn->prepare("SELECT o.id_order, o.tanggal_order, o.total_harga, o.status_order, o.metode_pembayaran, u.username
                       FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user
                       WHERE o.id_toko=? AND o.deleted=0
                       ORDER BY o.tanggal_order DESC LIMIT 5");
$qp->bind_param("i", $idtoko); $qp->execute();
$pesananterbaru = $qp->get_result()->fetch_all(MYSQLI_ASSOC); $qp->close();

// ==============================================================
// PRODUK TERLARIS (5 teratas)
// ==============================================================
$qtl = $conn->prepare("SELECT m.nama_menu, SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omset
                        FROM tb_detail_order d
                        JOIN tb_menu m ON d.id_menu=m.id_menu
                        JOIN tb_order o ON d.id_order=o.id_order
                        WHERE m.id_toko=? AND o.deleted=0 AND d.deleted=0
                        GROUP BY m.id_menu, m.nama_menu
                        ORDER BY terjual DESC LIMIT 5");
$qtl->bind_param("i", $idtoko); $qtl->execute();
$terlaris = $qtl->get_result()->fetch_all(MYSQLI_ASSOC); $qtl->close();

// ==============================================================
// RATING & ULASAN TERBARU
// ==============================================================
$qr = $conn->prepare("SELECT r.rating_toko, r.ulasan, r.created, u.username
                       FROM tb_rating r JOIN tb_user u ON r.id_user=u.id_user
                       WHERE r.id_toko=? AND r.deleted=0
                       ORDER BY r.created DESC LIMIT 5");
$qr->bind_param("i", $idtoko); $qr->execute();
$ulasanterbaru = $qr->get_result()->fetch_all(MYSQLI_ASSOC); $qr->close();

// ==============================================================
// FUNGSI BANTU
// ==============================================================
function rp(float $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}
function kelasstatus(string $s): string {
    return match($s) {
        'Menunggu' => 'menunggu', 'Diproses' => 'diproses',
        'Siap Diambil' => 'siap', 'Selesai' => 'selesai',
        default => 'dibatalkan',
    };
}
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
<title>Dashboard - eKantin</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten">

  <!-- Header -->
  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-gauge-high"></i> Dashboard</h1>
      <p>Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?> — <?= date('l, d M Y') ?></p>
    </div>
    <!-- Toggle buka/tutup toko -->
    <form method="POST" action="index.php">
      <input type="hidden" name="aksi" value="toggle_status">
      <div class="toggle-toko">
        <label class="saklar">
          <input type="checkbox" onchange="this.form.submit()"
                 <?= $statustoko === 'buka' ? 'checked' : '' ?>>
          <span class="slider"></span>
        </label>
        <div class="teks-toggle">
          <div class="status-text"
               style="color:<?= $statustoko === 'buka' ? 'var(--sukses)' : 'var(--gagal)' ?>;">
            Toko <?= $statustoko === 'buka' ? 'Buka' : 'Tutup' ?>
          </div>
          <div class="info-text">
            <?= $statustoko === 'buka' ? 'Pembeli bisa memesan' : 'Pembeli tidak bisa memesan' ?>
          </div>
        </div>
      </div>
    </form>
  </div>

  <!-- Peringatan ada pesanan menunggu -->
  <?php if ($pesananmenunggu > 0): ?>
  <div class="peringatan peringatantunggu">
    <i class="fa-solid fa-bell"></i>
    Ada <strong><?= $pesananmenunggu ?> pesanan</strong> yang menunggu konfirmasimu!
    <a href="../manajemenpesanan/manajemenpesanan.php?filter=Menunggu"
       style="margin-left:8px; font-weight:700; color:var(--tunggu); text-decoration:underline;">
      Lihat Sekarang
    </a>
  </div>
  <?php endif; ?>

  <!-- STATISTIK -->
  <div class="grid-stat">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-clock"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $pesananhari ?></div>
        <div class="label">Pesanan Hari Ini</div>
        <?php if ($pesananmenunggu > 0): ?>
        <div class="tren naik"><?= $pesananmenunggu ?> menunggu konfirmasi</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-coins"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= rp($pendapatanhari) ?></div>
        <div class="label">Pendapatan Hari Ini</div>
        <div class="tren" style="color:var(--tekssamar);">Dari pesanan selesai</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-star"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $ratarating > 0 ? $ratarating . ' / 5' : '—' ?></div>
        <div class="label">Rating Rata-rata</div>
        <div class="tren" style="color:var(--tekssamar);"><?= $jmlrating ?> ulasan</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-bowl-food"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $totalmenu ?></div>
        <div class="label">Menu Aktif</div>
        <a href="../manajemenmenu/manajemenmenu.php"
           style="font-size:11px;color:var(--kedua);font-weight:600;">Kelola menu</a>
      </div>
    </div>
  </div>

  <!-- CHART PENDAPATAN 7 HARI -->
  <div class="kartu">
    <h3><i class="fa-solid fa-chart-bar"></i> Pendapatan 7 Hari Terakhir</h3>
    <div class="area-chart">
      <svg viewBox="0 0 700 200" xmlns="http://www.w3.org/2000/svg" style="min-width:420px;">
        <!-- Garis grid -->
        <?php for ($g = 0; $g <= 4; $g++): ?>
        <?php $y = 20 + ($g * 40); ?>
        <line x1="60" y1="<?= $y ?>" x2="680" y2="<?= $y ?>" stroke="#E7CBCB" stroke-width="1" stroke-dasharray="4,4"/>
        <text x="55" y="<?= $y + 4 ?>" text-anchor="end" fill="#99627A" font-size="9">
          <?= rp(($maxnilai / 4) * (4 - $g)) ?>
        </text>
        <?php endfor; ?>

        <?php
        $barw  = 60;
        $gap   = 20;
        $startx = 70;
        $chartH = 160; // 20 to 180
        foreach ($chartdata as $i => $d):
            $x     = $startx + $i * ($barw + $gap);
            $barh  = $d['nilai'] > 0 ? ($d['nilai'] / $maxnilai) * $chartH : 2;
            $y     = 180 - $barh;
            $isToday = $d['tgl'] === date('Y-m-d');
            $barcolor = $isToday ? '#643843' : '#99627A';
        ?>
        <g class="bar-chart-group">
          <!-- Bar -->
          <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $barw ?>" height="<?= $barh ?>"
                rx="5" fill="<?= $barcolor ?>" class="bar-fill">
            <title><?= $d['label'] ?> — <?= rp($d['nilai']) ?></title>
          </rect>
          <!-- Label hari -->
          <text x="<?= $x + $barw/2 ?>" y="195" text-anchor="middle"
                fill="<?= $isToday ? '#643843' : '#99627A' ?>"
                font-size="10" font-weight="<?= $isToday ? '700' : '400' ?>">
            <?= $d['label'] ?>
          </text>
          <!-- Label nilai di atas bar (jika ada) -->
          <?php if ($d['nilai'] > 0): ?>
          <text x="<?= $x + $barw/2 ?>" y="<?= max($y - 4, 14) ?>" text-anchor="middle"
                fill="#643843" font-size="8" font-weight="600">
            <?= number_format($d['nilai']/1000, 0) ?>k
          </text>
          <?php endif; ?>
        </g>
        <?php endforeach; ?>
      </svg>
    </div>
  </div>

  <div class="grid-dua">

    <!-- PESANAN TERBARU -->
    <div class="kartu">
      <h3><i class="fa-solid fa-receipt"></i> Pesanan Terbaru</h3>
      <?php if (empty($pesananterbaru)): ?>
      <div class="kosong" style="padding:20px;">
        <div class="ikon-kosong"><i class="fa-solid fa-receipt"></i></div>
        <p>Belum ada pesanan</p>
      </div>
      <?php else: ?>
      <?php foreach ($pesananterbaru as $p): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--latar);">
        <div>
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

    <!-- PRODUK TERLARIS -->
    <div class="kartu">
      <h3><i class="fa-solid fa-fire"></i> Produk Terlaris</h3>
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

    <!-- RATING & ULASAN TERBARU -->
    <div class="kartu">
      <h3><i class="fa-solid fa-star"></i> Rating &amp; Ulasan Terbaru</h3>
      <?php if ($ratarating > 0): ?>
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

    <!-- INFO TOKO SINGKAT -->
    <div class="kartu">
      <h3><i class="fa-solid fa-store"></i> Info Toko</h3>
      <div style="display:flex;align-items:center;gap:14px;padding:10px 0;">
        <div style="width:56px;height:56px;border-radius:14px;background:var(--utama);color:var(--putihbg);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;flex-shrink:0;">
          <?= strtoupper(mb_substr($_SESSION['nama_toko']??'T', 0, 2)) ?>
        </div>
        <div>
          <div style="font-size:16px;font-weight:800;"><?= htmlspecialchars($_SESSION['nama_toko']??'Toko') ?></div>
          <div style="font-size:12px;color:var(--tekssamar);">Penjual: <?= htmlspecialchars($_SESSION['username']) ?></div>
          <div class="statustoko <?= $statustoko ?>" style="margin-top:6px;">
            <i class="fa-solid fa-circle" style="font-size:8px;"></i>
            <?= $statustoko === 'buka' ? 'Toko Buka' : 'Toko Tutup' ?>
          </div>
        </div>
      </div>
      <a href="../profil/profil.php" class="tombolringan blok mt14">
        <i class="fa-solid fa-pen"></i> Edit Profil &amp; Toko
      </a>
    </div>

  </div>

</main>

</body>
</html>
