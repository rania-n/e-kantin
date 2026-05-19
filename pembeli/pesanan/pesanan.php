<?php
/* ============================================================
   HALAMAN PESANAN
   Tab Aktif: pesanan yang masih berjalan
   Tab Riwayat: pesanan yang sudah selesai atau dibatalkan
   ============================================================ */
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

$idpengguna = (int)$_SESSION['id_user'];
$tab    = in_array($_GET['tab'] ?? '', ['aktif','riwayat']) ? $_GET['tab'] : 'aktif';
$filter = $_GET['filter'] ?? 'semua';

// Hitung jumlah pesanan aktif (untuk badge tab)
$ha = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND deleted=0 AND status_order IN ('Menunggu','Diproses','Siap Diambil')");
$ha->bind_param("i", $idpengguna);
$ha->execute();
$jumlahaktif = (int)$ha->get_result()->fetch_row()[0];
$ha->close();

// Ambil daftar pesanan
if ($tab === 'aktif') {
    $q = $conn->prepare("SELECT o.*,t.nama_toko FROM tb_order o LEFT JOIN tb_toko t ON o.id_toko=t.id_toko WHERE o.id_user=? AND o.deleted=0 AND o.status_order IN ('Menunggu','Diproses','Siap Diambil') ORDER BY o.tanggal_order DESC");
    $q->bind_param("i", $idpengguna);
} else {
    // Filter riwayat
    $kondisifilter = "status_order IN ('Selesai','Dibatalkan')";
    if ($filter === 'selesai')    $kondisifilter = "status_order='Selesai'";
    if ($filter === 'dibatalkan') $kondisifilter = "status_order='Dibatalkan'";
    $q = $conn->prepare("SELECT o.*,t.nama_toko FROM tb_order o LEFT JOIN tb_toko t ON o.id_toko=t.id_toko WHERE o.id_user=? AND o.deleted=0 AND $kondisifilter ORDER BY o.tanggal_order DESC LIMIT 50");
    $q->bind_param("i", $idpengguna);
}
$q->execute();
$daftarpesanan = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();

// Fungsi: ambil item pesanan
function ambilItemPesanan($conn, int $idpesanan): array {
    $q = $conn->prepare("SELECT d.jumlah,m.nama_menu FROM tb_detail_order d JOIN tb_menu m ON d.id_menu=m.id_menu WHERE d.id_order=? AND d.deleted=0");
    $q->bind_param("i", $idpesanan);
    $q->execute();
    return $q->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fungsi: ambil data rating (null jika belum)
function ambilRating($conn, int $idpesanan, int $idpengguna): ?array {
    $q = $conn->prepare("SELECT rating_toko, ulasan FROM tb_rating WHERE id_order=? AND id_user=? AND deleted=0");
    $q->bind_param("ii", $idpesanan, $idpengguna);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    return $row ?: null;
}

// Fungsi: timeline aktif
function tahapTimeline(string $statusskrg): array {
    $all = [
        ['label' => 'Pesanan Diterima', 'match' => ['Menunggu','Diproses','Siap Diambil','Selesai']],
        ['label' => 'Sedang Dimasak',   'match' => ['Diproses','Siap Diambil','Selesai']],
        ['label' => 'Siap Diambil',     'match' => ['Siap Diambil','Selesai']],
        ['label' => 'Selesai',          'match' => ['Selesai']],
    ];
    $urutan = ['Menunggu','Diproses','Siap Diambil','Selesai'];
    $iskrg  = array_search($statusskrg, $urutan);
    foreach ($all as &$t) {
        if (in_array($statusskrg, $t['match'])) { $t['kelas'] = 'selesai'; }
        elseif ($iskrg !== false && array_search($t['match'][0], $urutan) === $iskrg + 1) { $t['kelas'] = 'berjalan'; }
        else { $t['kelas'] = ''; }
    }
    return $all;
}

function kelasStatusPesanan(string $s): string {
    return match($s) {
        'Menunggu'     => 'menunggu',
        'Diproses'     => 'diproses',
        'Siap Diambil' => 'siap',
        'Selesai'      => 'selesai',
        default        => 'dibatalkan',
    };
}

$pathbase = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan - eKantin</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkus">

  <div class="judulhalaman">
    <h1><i class="fa-solid fa-receipt"></i> Pesanan Saya</h1>
    <p>Pantau pesanan aktif dan lihat riwayat</p>
  </div>

  <!-- Tab switcher -->
  <div class="switchtab">
    <a href="pesanan.php?tab=aktif" class="<?= $tab==='aktif'?'aktif':'' ?>">
      <i class="fa-solid fa-clock"></i> Aktif
      <?php if ($jumlahaktif > 0): ?>
        <span class="angkatab"><?= $jumlahaktif ?></span>
      <?php endif; ?>
    </a>
    <a href="pesanan.php?tab=riwayat" class="<?= $tab==='riwayat'?'aktif':'' ?>">
      <i class="fa-solid fa-clock-rotate-left"></i> Riwayat
    </a>
  </div>

  <!-- Filter riwayat -->
  <?php if ($tab === 'riwayat'): ?>
  <div class="filterkategori" style="margin-bottom:16px;">
    <a href="pesanan.php?tab=riwayat&filter=semua"
       class="chipcategori <?= $filter==='semua'?'aktif':'' ?>">Semua</a>
    <a href="pesanan.php?tab=riwayat&filter=selesai"
       class="chipcategori <?= $filter==='selesai'?'aktif':'' ?>">
      <i class="fa-solid fa-circle-check"></i> Selesai
    </a>
    <a href="pesanan.php?tab=riwayat&filter=dibatalkan"
       class="chipcategori <?= $filter==='dibatalkan'?'aktif':'' ?>">
      <i class="fa-solid fa-circle-xmark"></i> Dibatalkan
    </a>
  </div>
  <?php endif; ?>

  <?php if (empty($daftarpesanan)): ?>
  <div class="kosong">
    <div class="ikonkosong">
      <i class="fa-solid <?= $tab==='aktif' ? 'fa-clock' : 'fa-clock-rotate-left' ?>"></i>
    </div>
    <h3><?= $tab==='aktif' ? 'Belum ada pesanan aktif' : 'Belum ada riwayat' ?></h3>
    <p><?= $tab==='aktif' ? 'Pesanan yang sedang diproses akan muncul di sini' : 'Pesanan yang selesai akan muncul di sini' ?></p>
    <a href="../index/index.php" class="tombolutama">
      <i class="fa-solid fa-utensils"></i> Pesan Sekarang
    </a>
  </div>

  <?php else: ?>
  <?php foreach ($daftarpesanan as $p):
    $nomer    = 'EK-' . str_pad($p['id_order'], 6, '0', STR_PAD_LEFT);
    $namatoko = $p['nama_toko'] ?? 'Kantin';
    $items       = ambilItemPesanan($conn, $p['id_order']);
    $ratingdata  = ambilRating($conn, $p['id_order'], $idpengguna);
    $rating      = $ratingdata !== null;
    $siap     = $p['status_order'] === 'Siap Diambil';
    $selesai  = $p['status_order'] === 'Selesai';
    $kelas    = kelasStatusPesanan($p['status_order']);
  ?>
  <div class="kartupesanan <?= $siap?'siap':'' ?>">

    <div class="ataspesanan">
      <div>
        <div class="nomerpesanan"><?= $nomer ?></div>
        <div class="tanggalpesanan"><?= date('d M Y, H:i', strtotime($p['tanggal_order'])) ?></div>
      </div>
      <span class="badge <?= $kelas ?>">
        <?= htmlspecialchars($p['status_order']) ?>
      </span>
    </div>

    <div class="namakantinpesanan">
      <i class="fa-solid fa-store"></i> <?= htmlspecialchars($namatoko) ?>
    </div>

    <div class="itempesanan">
      <?= implode(' &middot; ', array_map(fn($it) => $it['jumlah'].'x '.htmlspecialchars($it['nama_menu']), $items)) ?>
    </div>

    <?php if ($tab === 'aktif'): ?>

      <?php if ($siap): ?>
      <div class="peringatan peringatansukses" style="margin-bottom:12px;">
        <i class="fa-solid fa-bell"></i>
        <div><strong>Pesananmu sudah siap!</strong><br><small>Segera ambil di <?= htmlspecialchars($namatoko) ?></small></div>
      </div>
      <?php endif; ?>

      <!-- Timeline -->
      <div class="timeline">
        <?php foreach (tahapTimeline($p['status_order']) as $tahap): ?>
        <div class="itemtimeline <?= $tahap['kelas'] ?>">
          <div class="titiktimeline <?= $tahap['kelas'] ?>">
            <?php if ($tahap['kelas']==='selesai'):   ?><i class="fa-solid fa-check" style="font-size:10px;"></i>
            <?php elseif ($tahap['kelas']==='berjalan'): ?><i class="fa-solid fa-circle" style="font-size:8px;"></i>
            <?php endif; ?>
          </div>
          <div class="isitimeline">
            <?= $tahap['label'] ?>
            <?php if ($tahap['kelas']==='berjalan'): ?>
            <span style="font-size:11px;opacity:.7;">(sedang berlangsung)</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

    <div class="bawahpesanan">
      <div class="totalpesanan">Rp <?= number_format($p['total_harga'],0,',','.') ?></div>
      <div class="aksipesanan">
        <?php if ($selesai): ?>
        <a href="struk.php?id_order=<?= $p['id_order'] ?>" class="tombolkecil">
          <i class="fa-solid fa-receipt"></i> Struk
        </a>
        <?php endif; ?>
        <?php if ($tab==='riwayat' && $selesai && !$rating): ?>
        <a href="rating.php?id_order=<?= $p['id_order'] ?>" class="tombolkecil utama">
          <i class="fa-solid fa-star"></i> Rating
        </a>
        <?php endif; ?>
      </div>
    </div>

    <div style="font-size:11px;color:var(--tekssamar);margin-top:8px;">
      <i class="fa-solid fa-wallet"></i> <?= htmlspecialchars($p['metode_pembayaran']) ?>
    </div>

    <?php if ($tab==='riwayat' && $selesai && $rating && $ratingdata): ?>
    <div style="margin-top:10px;padding:10px;background:var(--latar);border-radius:10px;font-size:12px;">
      <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
        <?php for ($bi=1;$bi<=5;$bi++): ?>
          <i class="fa-solid fa-star" style="color:<?= $bi<=$ratingdata['rating_toko']?'#F59E0B':'#D1D5DB' ?>;font-size:13px;"></i>
        <?php endfor; ?>
        <strong style="color:var(--utama);"><?= $ratingdata['rating_toko'] ?>/5</strong>
      </div>
      <?php if (!empty($ratingdata['ulasan'])): ?>
      <div style="color:var(--teks);font-style:italic;">"<?= htmlspecialchars($ratingdata['ulasan']) ?>"</div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
  <?php endforeach; ?>

  <?php if ($tab==='aktif'): ?>
  <div style="text-align:center;padding:12px 0;font-size:12px;color:var(--tekssamar);">
    <i class="fa-solid fa-rotate"></i> Halaman otomatis refresh tiap 30 detik
  </div>
  <meta http-equiv="refresh" content="30">
  <?php endif; ?>

  <?php endif; ?>

</div>
</body>
</html>
