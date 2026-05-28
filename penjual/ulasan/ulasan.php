<?php
/* halaman semua ulasan dan rating toko penjual.
   menampilkan ringkasan statistik bintang, filter per bintang, pencarian,
   daftar ulasan per halaman (paginasi), dan bar distribusi rating */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

// ambil id toko dan id penjual dari session
$idtoko    = (int)$_SESSION['id_toko'];
$idpenjual = (int)$_SESSION['id_user'];

// tandai halaman aktif untuk navbar
$halamansaatini = 'ulasan';

// ambil filter bintang dari URL, validasi range 1-5 (0 = semua bintang)
$filterbintang = isset($_GET['bintang']) ? (int)$_GET['bintang'] : 0;
if ($filterbintang < 1 || $filterbintang > 5) $filterbintang = 0; // reset jika di luar range

// ambil kata cari dari URL
$cari = trim($_GET['cari'] ?? '');

// ambil nomor halaman untuk paginasi, minimal halaman 1
$halaman = max(1, (int)($_GET['hal'] ?? 1));
$perhal  = 15; // jumlah ulasan per halaman
$offset  = ($halaman - 1) * $perhal; // lewati ulasan dari halaman sebelumnya

// bangun kondisi query berdasarkan filter bintang dan pencarian
$kondisi = "r.id_penjual=$idpenjual AND r.deleted=0";
if ($filterbintang > 0) $kondisi .= " AND r.rating_toko=$filterbintang";
if ($cari !== '') {
    // escape untuk mencegah sql injection
    $cariaman = $conn->real_escape_string($cari);
    // cari berdasarkan username pembeli atau isi ulasan
    $kondisi .= " AND (u.username LIKE '%$cariaman%' OR r.ulasan LIKE '%$cariaman%')";
}

// hitung total ulasan sesuai kondisi untuk mengetahui jumlah halaman
$qtotal = $conn->query("SELECT COUNT(*) FROM tb_rating r JOIN tb_user u ON r.id_user=u.id_user WHERE $kondisi");
$totalulasan = (int)$qtotal->fetch_row()[0];
$tothalaman  = ceil($totalulasan / $perhal); // ceil = pembulatan ke atas

// ambil ulasan sesuai kondisi, diurutkan terbaru di atas, dengan LIMIT dan OFFSET untuk paginasi
$qr = $conn->query("SELECT r.id_rating, r.rating_toko, r.ulasan, r.created, u.username,
                           o.id_order
                    FROM tb_rating r
                    JOIN tb_user u ON r.id_user=u.id_user
                    LEFT JOIN tb_order o ON r.id_order=o.id_order
                    WHERE $kondisi
                    ORDER BY r.created DESC
                    LIMIT $perhal OFFSET $offset");
$daftarulasan = $qr->fetch_all(MYSQLI_ASSOC);

// ambil jumlah ulasan per bintang (1-5) untuk bar distribusi rating
$qstat = $conn->query("SELECT rating_toko, COUNT(*) AS jml FROM tb_rating WHERE id_penjual=$idpenjual AND deleted=0 GROUP BY rating_toko ORDER BY rating_toko DESC");
$statrating = [];
// simpan ke array asosiatif dengan kunci berupa nilai bintang (1-5)
while ($row = $qstat->fetch_assoc()) $statrating[(int)$row['rating_toko']] = (int)$row['jml'];

// ambil rata-rata rating dan total jumlah ulasan
$qrata = $conn->query("SELECT ROUND(AVG(rating_toko),1), COUNT(*) FROM tb_rating WHERE id_penjual=$idpenjual AND deleted=0");
$ratarow = $qrata->fetch_row();
$ratarating = (float)($ratarow[0] ?? 0);
$jmlrating  = (int)($ratarow[1] ?? 0);

/* fungsi bintang menerima parameter ukuran font opsional.
   ini agar bisa dipakai di berbagai tempat dengan ukuran berbeda */
function bintang(float $r, int $ukuran = 13): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $warna = $i <= $r ? '#F59E0B' : '#D1D5DB'; // kuning = penuh, abu = kosong
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
<title>Semua Ulasan - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten">

  <!-- header halaman dengan kotak pencarian ulasan -->
  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-star"></i> Semua Ulasan</h1>
      <p>Ulasan &amp; rating dari seluruh pembeli</p>
    </div>
    <!-- form pencarian — filter bintang dipertahankan sebagai hidden field saat mencari -->
    <form method="GET" action="ulasan.php">
      <?php if ($filterbintang): ?><input type="hidden" name="bintang" value="<?= $filterbintang ?>"><?php endif; ?>
      <div class="kotakcari">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
               placeholder="Cari nama pembeli atau isi ulasan...">
        <button type="submit" class="tombolcari"><i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </form>
  </div>

  <!-- ringkasan rating: angka rata-rata di kiri, bar distribusi bintang di kanan -->
  <div class="kartu" style="margin-bottom:18px;">
    <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:center;">
      <!-- nilai rata-rata dalam angka besar -->
      <div style="text-align:center;min-width:80px;">
        <div style="font-size:42px;font-weight:800;color:var(--utama);line-height:1;"><?= $ratarating ?: '—' ?></div>
        <div><?= bintang($ratarating, 14) ?></div>
        <div style="font-size:12px;color:var(--tekssamar);margin-top:4px;"><?= $jmlrating ?> ulasan</div>
      </div>
      <!-- bar distribusi: untuk setiap bintang 5 sampai 1, tampilkan progress bar -->
      <div style="flex:1;min-width:160px;">
        <?php for ($b = 5; $b >= 1; $b--): ?>
        <?php $jml = $statrating[$b] ?? 0; ?>
        <?php $persen = $jmlrating > 0 ? round($jml / $jmlrating * 100) : 0; ?>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
          <!-- angka bintang bisa diklik sebagai shortcut ke filter bintang tersebut -->
          <a href="ulasan.php?bintang=<?= $b ?><?= $cari?'&cari='.urlencode($cari):'' ?>"
             style="font-size:12px;color:var(--kedua);min-width:16px;text-align:right;font-weight:700;">
            <?= $b ?>
          </a>
          <i class="fa-solid fa-star" style="color:#F59E0B;font-size:11px;"></i>
          <!-- progress bar lebar proporsional terhadap persentase ulasan bintang ini -->
          <div style="flex:1;background:var(--latar);border-radius:9999px;height:8px;overflow:hidden;">
            <div style="background:var(--tunggu);height:100%;width:<?= $persen ?>%;border-radius:9999px;transition:width .3s;"></div>
          </div>
          <!-- jumlah ulasan untuk bintang ini -->
          <span style="font-size:11px;color:var(--tekssamar);min-width:24px;"><?= $jml ?></span>
        </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>

  <!-- filter tab bintang: "semua" dan bintang 5 sampai 1 -->
  <div class="filter-bar" style="margin-bottom:16px;">
    <!-- tab "semua" — aktif jika tidak ada filter bintang -->
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

  <!-- daftar ulasan -->
  <?php if (empty($daftarulasan)): ?>
  <!-- tampilan kosong jika tidak ada ulasan atau filter tidak menemukan hasil -->
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
    <!-- setiap kartu ulasan memuat nama pembeli, waktu, rating bintang, dan teks ulasan -->
    <div class="kartu" style="margin-bottom:12px;padding:14px 16px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
        <div>
          <!-- nama pembeli dalam huruf tebal -->
          <div style="font-weight:700;font-size:14px;"><?= htmlspecialchars($u['username']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);margin-top:2px;">
            <?= date('d M Y, H:i', strtotime($u['created'])) ?>
            <?php if ($u['id_order']): ?>
            <!-- tampilkan nomor pesanan terkait jika ada -->
            &nbsp;&middot;&nbsp; Pesanan EK-<?= str_pad($u['id_order'],6,'0',STR_PAD_LEFT) ?>
            <?php endif; ?>
          </div>
        </div>
        <!-- ikon bintang dan angka rating di sisi kanan -->
        <div style="display:flex;align-items:center;gap:4px;">
          <?= bintang($u['rating_toko'], 14) ?>
          <span style="font-weight:800;color:var(--tunggu);font-size:13px;margin-left:4px;"><?= $u['rating_toko'] ?></span>
        </div>
      </div>
      <?php if (!empty($u['ulasan'])): ?>
      <!-- teks ulasan ditampilkan dalam kotak dengan background abu muda dan gaya miring -->
      <div style="margin-top:10px;font-size:13px;color:var(--tekskecil);background:var(--latar);padding:10px 14px;border-radius:10px;line-height:1.6;font-style:italic;">
        "<?= htmlspecialchars($u['ulasan']) ?>"
      </div>
      <?php else: ?>
      <!-- jika pembeli tidak menulis teks, tampilkan keterangan -->
      <div style="margin-top:8px;font-size:12px;color:var(--tekssamar);font-style:italic;">
        Tidak ada komentar
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- paginasi — hanya ditampilkan jika ada lebih dari 1 halaman -->
  <?php if ($tothalaman > 1): ?>
  <div style="display:flex;justify-content:center;gap:6px;flex-wrap:wrap;margin-top:16px;">
    <?php if ($halaman > 1): ?>
    <!-- tombol halaman sebelumnya -->
    <a href="ulasan.php?hal=<?= $halaman-1 ?><?= $filterbintang?'&bintang='.$filterbintang:'' ?><?= $cari?'&cari='.urlencode($cari):'' ?>"
       class="chip-filter">
      <i class="fa-solid fa-chevron-left"></i>
    </a>
    <?php endif; ?>
    <?php
    // tampilkan nomor halaman: halaman saat ini ± 2 (maksimal 5 halaman terlihat)
    for ($h = max(1,$halaman-2); $h <= min($tothalaman,$halaman+2); $h++): ?>
    <a href="ulasan.php?hal=<?= $h ?><?= $filterbintang?'&bintang='.$filterbintang:'' ?><?= $cari?'&cari='.urlencode($cari):'' ?>"
       class="chip-filter <?= $h===$halaman?'aktif':'' ?>">
      <?= $h ?>
    </a>
    <?php endfor; ?>
    <?php if ($halaman < $tothalaman): ?>
    <!-- tombol halaman berikutnya -->
    <a href="ulasan.php?hal=<?= $halaman+1 ?><?= $filterbintang?'&bintang='.$filterbintang:'' ?><?= $cari?'&cari='.urlencode($cari):'' ?>"
       class="chip-filter">
      <i class="fa-solid fa-chevron-right"></i>
    </a>
    <?php endif; ?>
  </div>
  <!-- info posisi halaman dan total ulasan -->
  <div style="text-align:center;font-size:12px;color:var(--tekssamar);margin-top:8px;">
    Halaman <?= $halaman ?> dari <?= $tothalaman ?> (<?= $totalulasan ?> ulasan)
  </div>
  <?php endif; ?>

  <?php endif; ?>

</main>

</body>
</html>
