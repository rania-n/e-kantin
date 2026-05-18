<?php
/* ============================================================
   SEMUA ULASAN & RATING TOKO
   Tampilkan semua ulasan dari pembeli, dengan filter bintang.
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idtoko = (int)$_SESSION['id_toko'];
$halamansaatini = 'ulasan';

// Filter rating
$filterbintang = isset($_GET['bintang']) ? (int)$_GET['bintang'] : 0;
if ($filterbintang < 1 || $filterbintang > 5) $filterbintang = 0;

// Cari
$cari = trim($_GET['cari'] ?? '');

// Halaman
$halaman = max(1, (int)($_GET['hal'] ?? 1));
$perhal  = 15;
$offset  = ($halaman - 1) * $perhal;

// Bangun kondisi
$kondisi = "r.id_toko=$idtoko AND r.deleted=0";
if ($filterbintang > 0) $kondisi .= " AND r.rating_toko=$filterbintang";
if ($cari !== '') {
    $cariaman = $conn->real_escape_string($cari);
    $kondisi .= " AND (u.username LIKE '%$cariaman%' OR r.ulasan LIKE '%$cariaman%')";
}

// Total ulasan
$qtotal = $conn->query("SELECT COUNT(*) FROM tb_rating r JOIN tb_user u ON r.id_user=u.id_user WHERE $kondisi");
$totalulasan = (int)$qtotal->fetch_row()[0];
$tothalaman  = ceil($totalulasan / $perhal);

// Ambil ulasan
$qr = $conn->query("SELECT r.id_rating, r.rating_toko, r.ulasan, r.created, u.username,
                           o.id_order
                    FROM tb_rating r
                    JOIN tb_user u ON r.id_user=u.id_user
                    LEFT JOIN tb_order o ON r.id_order=o.id_order
                    WHERE $kondisi
                    ORDER BY r.created DESC
                    LIMIT $perhal OFFSET $offset");
$daftarulasan = $qr->fetch_all(MYSQLI_ASSOC);

// Statistik rating
$qstat = $conn->query("SELECT rating_toko, COUNT(*) AS jml FROM tb_rating WHERE id_toko=$idtoko AND deleted=0 GROUP BY rating_toko ORDER BY rating_toko DESC");
$statrating = [];
while ($row = $qstat->fetch_assoc()) $statrating[(int)$row['rating_toko']] = (int)$row['jml'];

$qrata = $conn->query("SELECT ROUND(AVG(rating_toko),1), COUNT(*) FROM tb_rating WHERE id_toko=$idtoko AND deleted=0");
$ratarow = $qrata->fetch_row();
$ratarating = (float)($ratarow[0] ?? 0);
$jmlrating  = (int)($ratarow[1] ?? 0);

function bintang(float $r, int $ukuran = 13): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $warna = $i <= $r ? '#F59E0B' : '#D1D5DB';
        $out .= "<i class='fa-solid fa-star' style='color:{$warna};font-size:{$ukuran}px;'></i>";
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Semua Ulasan - eKantin</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-star"></i> Semua Ulasan</h1>
      <p>Ulasan &amp; rating dari seluruh pembeli</p>
    </div>
    <!-- Cari ulasan -->
    <form method="GET" action="ulasan.php">
      <?php if ($filterbintang): ?><input type="hidden" name="bintang" value="<?= $filterbintang ?>"><?php endif; ?>
      <div class="kotakcari">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
               placeholder="Cari nama pembeli atau isi ulasan...">
      </div>
    </form>
  </div>

  <!-- Ringkasan rating -->
  <div class="kartu" style="margin-bottom:18px;">
    <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:center;">
      <div style="text-align:center;min-width:80px;">
        <div style="font-size:42px;font-weight:800;color:var(--utama);line-height:1;"><?= $ratarating ?: '—' ?></div>
        <div><?= bintang($ratarating, 14) ?></div>
        <div style="font-size:12px;color:var(--tekssamar);margin-top:4px;"><?= $jmlrating ?> ulasan</div>
      </div>
      <div style="flex:1;min-width:160px;">
        <?php for ($b = 5; $b >= 1; $b--): ?>
        <?php $jml = $statrating[$b] ?? 0; ?>
        <?php $persen = $jmlrating > 0 ? round($jml / $jmlrating * 100) : 0; ?>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
          <a href="ulasan.php?bintang=<?= $b ?><?= $cari?'&cari='.urlencode($cari):'' ?>"
             style="font-size:12px;color:var(--kedua);min-width:16px;text-align:right;font-weight:700;">
            <?= $b ?>
          </a>
          <i class="fa-solid fa-star" style="color:#F59E0B;font-size:11px;"></i>
          <div style="flex:1;background:var(--latar);border-radius:9999px;height:8px;overflow:hidden;">
            <div style="background:var(--tunggu);height:100%;width:<?= $persen ?>%;border-radius:9999px;transition:width .3s;"></div>
          </div>
          <span style="font-size:11px;color:var(--tekssamar);min-width:24px;"><?= $jml ?></span>
        </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>

  <!-- Filter bintang -->
  <div class="filter-bar" style="margin-bottom:16px;">
    <a href="ulasan.php<?= $cari?'?cari='.urlencode($cari):'' ?>"
       class="chip-filter <?= $filterbintang===0?'aktif':'' ?>">
      Semua
    </a>
    <?php for ($b = 5; $b >= 1; $b--): ?>
    <a href="ulasan.php?bintang=<?= $b ?><?= $cari?'&cari='.urlencode($cari):'' ?>"
       class="chip-filter <?= $filterbintang===$b?'aktif':'' ?>">
      <i class="fa-solid fa-star" style="color:#F59E0B;font-size:11px;"></i> <?= $b ?>
    </a>
    <?php endfor; ?>
  </div>

  <!-- Daftar ulasan -->
  <?php if (empty($daftarulasan)): ?>
  <div class="kosong">
    <div class="ikon-kosong"><i class="fa-solid fa-star"></i></div>
    <h3>Belum ada ulasan</h3>
    <p><?= $filterbintang ? "Tidak ada ulasan bintang $filterbintang" : "Belum ada ulasan dari pembeli" ?></p>
    <?php if ($filterbintang || $cari): ?>
    <a href="ulasan.php" class="tombolringan">Reset Filter</a>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <div>
    <?php foreach ($daftarulasan as $u): ?>
    <div class="kartu" style="margin-bottom:12px;padding:14px 16px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
        <div>
          <div style="font-weight:700;font-size:14px;"><?= htmlspecialchars($u['username']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);margin-top:2px;">
            <?= date('d M Y, H:i', strtotime($u['created'])) ?>
            <?php if ($u['id_order']): ?>
            &nbsp;&middot;&nbsp; Pesanan EK-<?= str_pad($u['id_order'],6,'0',STR_PAD_LEFT) ?>
            <?php endif; ?>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:4px;">
          <?= bintang($u['rating_toko'], 14) ?>
          <span style="font-weight:800;color:var(--tunggu);font-size:13px;margin-left:4px;"><?= $u['rating_toko'] ?></span>
        </div>
      </div>
      <?php if (!empty($u['ulasan'])): ?>
      <div style="margin-top:10px;font-size:13px;color:var(--tekskecil);background:var(--latar);padding:10px 14px;border-radius:10px;line-height:1.6;font-style:italic;">
        "<?= htmlspecialchars($u['ulasan']) ?>"
      </div>
      <?php else: ?>
      <div style="margin-top:8px;font-size:12px;color:var(--tekssamar);font-style:italic;">
        Tidak ada komentar
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Paginasi -->
  <?php if ($tothalaman > 1): ?>
  <div style="display:flex;justify-content:center;gap:6px;flex-wrap:wrap;margin-top:16px;">
    <?php if ($halaman > 1): ?>
    <a href="ulasan.php?hal=<?= $halaman-1 ?><?= $filterbintang?'&bintang='.$filterbintang:'' ?><?= $cari?'&cari='.urlencode($cari):'' ?>"
       class="chip-filter">
      <i class="fa-solid fa-chevron-left"></i>
    </a>
    <?php endif; ?>
    <?php for ($h = max(1,$halaman-2); $h <= min($tothalaman,$halaman+2); $h++): ?>
    <a href="ulasan.php?hal=<?= $h ?><?= $filterbintang?'&bintang='.$filterbintang:'' ?><?= $cari?'&cari='.urlencode($cari):'' ?>"
       class="chip-filter <?= $h===$halaman?'aktif':'' ?>">
      <?= $h ?>
    </a>
    <?php endfor; ?>
    <?php if ($halaman < $tothalaman): ?>
    <a href="ulasan.php?hal=<?= $halaman+1 ?><?= $filterbintang?'&bintang='.$filterbintang:'' ?><?= $cari?'&cari='.urlencode($cari):'' ?>"
       class="chip-filter">
      <i class="fa-solid fa-chevron-right"></i>
    </a>
    <?php endif; ?>
  </div>
  <div style="text-align:center;font-size:12px;color:var(--tekssamar);margin-top:8px;">
    Halaman <?= $halaman ?> dari <?= $tothalaman ?> (<?= $totalulasan ?> ulasan)
  </div>
  <?php endif; ?>

  <?php endif; ?>

</main>

</body>
</html>
