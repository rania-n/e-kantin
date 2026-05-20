<?php
/* halaman daftar pesanan pembeli
   menampilkan dua tab:
   - tab aktif: pesanan yang masih berjalan (Menunggu, Diproses, Siap Diambil)
   - tab riwayat: pesanan yang sudah selesai atau dibatalkan */

// guard memastikan hanya pembeli yang login yang bisa masuk
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

// ambil id pengguna dari session
$idpengguna = (int)$_SESSION['id_user'];
// tentukan tab aktif dari parameter URL, default ke 'aktif' jika tidak ada
$tab    = in_array($_GET['tab'] ?? '', ['aktif','riwayat']) ? $_GET['tab'] : 'aktif';
// filter tambahan khusus tab riwayat: semua, selesai, atau dibatalkan
$filter = $_GET['filter'] ?? 'semua';

// hitung jumlah pesanan aktif untuk ditampilkan sebagai badge angka di tab
$ha = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND deleted=0 AND status_order IN ('Menunggu','Diproses','Siap Diambil')");
$ha->bind_param("i", $idpengguna);
$ha->execute();
$jumlahaktif = (int)$ha->get_result()->fetch_row()[0];
$ha->close();

// ambil daftar pesanan sesuai tab yang dipilih
if ($tab === 'aktif') {
    // tab aktif: pesanan yang belum selesai, diurutkan terbaru di atas
    $q = $conn->prepare("SELECT o.*,t.nama_toko FROM tb_order o LEFT JOIN tb_toko t ON o.id_toko=t.id_toko WHERE o.id_user=? AND o.deleted=0 AND o.status_order IN ('Menunggu','Diproses','Siap Diambil') ORDER BY o.tanggal_order DESC");
    $q->bind_param("i", $idpengguna);
} else {
    // tab riwayat: pesanan selesai atau dibatalkan, bisa difilter lebih lanjut
    $kondisifilter = "status_order IN ('Selesai','Dibatalkan')";
    if ($filter === 'selesai')    $kondisifilter = "status_order='Selesai'";
    if ($filter === 'dibatalkan') $kondisifilter = "status_order='Dibatalkan'";
    // LIMIT 50 agar tidak memuat terlalu banyak data sekaligus
    $q = $conn->prepare("SELECT o.*,t.nama_toko FROM tb_order o LEFT JOIN tb_toko t ON o.id_toko=t.id_toko WHERE o.id_user=? AND o.deleted=0 AND $kondisifilter ORDER BY o.tanggal_order DESC LIMIT 50");
    $q->bind_param("i", $idpengguna);
}
$q->execute();
// fetch_all mengambil semua baris sekaligus ke dalam array PHP
$daftarpesanan = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();

// fungsi bantu: ambil daftar item (menu) dari satu pesanan
// dipanggil per pesanan saat loop di bawah
function ambilItemPesanan($conn, int $idpesanan): array {
    $q = $conn->prepare("SELECT d.jumlah,m.nama_menu FROM tb_detail_order d JOIN tb_menu m ON d.id_menu=m.id_menu WHERE d.id_order=? AND d.deleted=0");
    $q->bind_param("i", $idpesanan);
    $q->execute();
    return $q->get_result()->fetch_all(MYSQLI_ASSOC);
}

// fungsi bantu: cek apakah pembeli sudah memberi rating untuk pesanan ini
// mengembalikan array data rating atau null jika belum ada
function ambilRating($conn, int $idpesanan, int $idpengguna): ?array {
    $q = $conn->prepare("SELECT rating_toko, ulasan FROM tb_rating WHERE id_order=? AND id_user=? AND deleted=0");
    $q->bind_param("ii", $idpesanan, $idpengguna);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    // ternary: kembalikan null jika $row kosong/false
    return $row ?: null;
}

// fungsi bantu: buat data timeline untuk pesanan aktif
// mengembalikan array tahap dengan kelas CSS: 'selesai', 'berjalan', atau kosong
function tahapTimeline(string $statusskrg): array {
    $all = [
        ['label' => 'Pesanan Diterima', 'match' => ['Menunggu','Diproses','Siap Diambil','Selesai']],
        ['label' => 'Sedang Dimasak',   'match' => ['Diproses','Siap Diambil','Selesai']],
        ['label' => 'Siap Diambil',     'match' => ['Siap Diambil','Selesai']],
        ['label' => 'Selesai',          'match' => ['Selesai']],
    ];
    // urutan status untuk menentukan posisi saat ini
    $urutan = ['Menunggu','Diproses','Siap Diambil','Selesai'];
    $iskrg  = array_search($statusskrg, $urutan);
    foreach ($all as &$t) {
        // tahap yang sudah dilewati: beri kelas 'selesai'
        if (in_array($statusskrg, $t['match'])) { $t['kelas'] = 'selesai'; }
        // tahap berikutnya: beri kelas 'berjalan'
        elseif ($iskrg !== false && array_search($t['match'][0], $urutan) === $iskrg + 1) { $t['kelas'] = 'berjalan'; }
        else { $t['kelas'] = ''; }
    }
    return $all;
}

// fungsi bantu: kembalikan nama kelas CSS berdasarkan status pesanan
// kelas ini menentukan warna badge status di kartu pesanan
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
<title>Pesanan - jajankita</title>
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

  <!-- tab switcher: klik untuk pindah antara tab aktif dan riwayat -->
  <div class="switchtab">
    <a href="pesanan.php?tab=aktif" class="<?= $tab==='aktif'?'aktif':'' ?>">
      <i class="fa-solid fa-clock"></i> Aktif
      <!-- tampilkan angka jumlah pesanan aktif sebagai badge -->
      <?php if ($jumlahaktif > 0): ?>
        <span class="angkatab"><?= $jumlahaktif ?></span>
      <?php endif; ?>
    </a>
    <a href="pesanan.php?tab=riwayat" class="<?= $tab==='riwayat'?'aktif':'' ?>">
      <i class="fa-solid fa-clock-rotate-left"></i> Riwayat
    </a>
  </div>

  <!-- filter tambahan hanya muncul di tab riwayat -->
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

  <!-- tampilan kosong jika tidak ada pesanan -->
  <?php if (empty($daftarpesanan)): ?>
  <div class="kosong">
    <div class="ikonkosong">
      <!-- ikon berbeda tergantung tab yang aktif -->
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
    // buat nomor pesanan dengan format EK-000001 (6 digit, padded)
    $nomer    = 'EK-' . str_pad($p['id_order'], 6, '0', STR_PAD_LEFT);
    $namatoko = $p['nama_toko'] ?? 'Kantin';
    // ambil daftar item dan data rating untuk pesanan ini
    $items       = ambilItemPesanan($conn, $p['id_order']);
    $ratingdata  = ambilRating($conn, $p['id_order'], $idpengguna);
    // $rating = true jika sudah pernah memberi rating
    $rating      = $ratingdata !== null;
    $siap     = $p['status_order'] === 'Siap Diambil';
    $selesai  = $p['status_order'] === 'Selesai';
    $kelas    = kelasStatusPesanan($p['status_order']);
  ?>
  <!-- kartu pesanan — diberi kelas 'siap' jika status Siap Diambil untuk highlight -->
  <div class="kartupesanan <?= $siap?'siap':'' ?>">

    <div class="ataspesanan">
      <div>
        <div class="nomerpesanan"><?= $nomer ?></div>
        <!-- format tanggal: "05 Jan 2025, 14:30" -->
        <div class="tanggalpesanan"><?= date('d M Y, H:i', strtotime($p['tanggal_order'])) ?></div>
      </div>
      <!-- badge status berwarna sesuai fungsi kelasStatusPesanan -->
      <span class="badge <?= $kelas ?>">
        <?= htmlspecialchars($p['status_order']) ?>
      </span>
    </div>

    <div class="namakantinpesanan">
      <i class="fa-solid fa-store"></i> <?= htmlspecialchars($namatoko) ?>
    </div>

    <!-- ringkasan item: "2x Nasi Goreng · 1x Es Teh" -->
    <div class="itempesanan">
      <?= implode(' &middot; ', array_map(fn($it) => $it['jumlah'].'x '.htmlspecialchars($it['nama_menu']), $items)) ?>
    </div>

    <!-- timeline hanya ditampilkan di tab aktif -->
    <?php if ($tab === 'aktif'): ?>

      <!-- notifikasi highlight jika pesanan sudah siap diambil -->
      <?php if ($siap): ?>
      <div class="peringatan peringatansukses" style="margin-bottom:12px;">
        <i class="fa-solid fa-bell"></i>
        <div><strong>Pesananmu sudah siap!</strong><br><small>Segera ambil di <?= htmlspecialchars($namatoko) ?></small></div>
      </div>
      <?php endif; ?>

      <!-- timeline visual tahap pesanan -->
      <div class="timeline">
        <?php foreach (tahapTimeline($p['status_order']) as $tahap): ?>
        <div class="itemtimeline <?= $tahap['kelas'] ?>">
          <div class="titiktimeline <?= $tahap['kelas'] ?>">
            <!-- ikon centang untuk tahap selesai, lingkaran untuk tahap berjalan -->
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
      <!-- total harga pesanan ini -->
      <div class="totalpesanan">Rp <?= number_format($p['total_harga'],0,',','.') ?></div>
      <div class="aksipesanan">
        <!-- tombol struk hanya muncul jika pesanan sudah selesai -->
        <?php if ($selesai): ?>
        <a href="struk.php?id_order=<?= $p['id_order'] ?>" class="tombolkecil">
          <i class="fa-solid fa-receipt"></i> Struk
        </a>
        <?php endif; ?>
        <!-- tombol rating hanya muncul di riwayat, pesanan selesai, dan belum diberi rating -->
        <?php if ($tab==='riwayat' && $selesai && !$rating): ?>
        <a href="rating.php?id_order=<?= $p['id_order'] ?>" class="tombolkecil utama">
          <i class="fa-solid fa-star"></i> Rating
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- tampilkan metode pembayaran di bawah kartu -->
    <div style="font-size:11px;color:var(--tekssamar);margin-top:8px;">
      <i class="fa-solid fa-wallet"></i> <?= htmlspecialchars($p['metode_pembayaran']) ?>
    </div>

    <!-- tampilkan rating yang sudah diberikan di tab riwayat -->
    <?php if ($tab==='riwayat' && $selesai && $rating && $ratingdata): ?>
    <div style="margin-top:10px;padding:10px;background:var(--latar);border-radius:10px;font-size:12px;">
      <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
        <!-- tampilkan bintang berwarna emas sesuai nilai rating -->
        <?php for ($bi=1;$bi<=5;$bi++): ?>
          <i class="fa-solid fa-star" style="color:<?= $bi<=$ratingdata['rating_toko']?'#F59E0B':'#D1D5DB' ?>;font-size:13px;"></i>
        <?php endfor; ?>
        <strong style="color:var(--utama);"><?= $ratingdata['rating_toko'] ?>/5</strong>
      </div>
      <!-- tampilkan teks ulasan jika ada -->
      <?php if (!empty($ratingdata['ulasan'])): ?>
      <div style="color:var(--teks);font-style:italic;">"<?= htmlspecialchars($ratingdata['ulasan']) ?>"</div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
  <?php endforeach; ?>

  <?php if ($tab==='aktif'): ?>
  <!-- keterangan bahwa halaman akan auto-refresh untuk memperbarui status pesanan -->
  <div style="text-align:center;padding:12px 0;font-size:12px;color:var(--tekssamar);">
    <i class="fa-solid fa-rotate"></i> Halaman otomatis refresh tiap 30 detik
  </div>
  <!-- meta refresh: browser akan muat ulang halaman ini setiap 30 detik -->
  <meta http-equiv="refresh" content="30">
  <?php endif; ?>

  <?php endif; ?>

</div>
</body>
</html>
