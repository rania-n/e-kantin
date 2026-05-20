<?php
/* halaman beranda pembeli
   menampilkan daftar toko yang sedang buka, filter kategori,
   pencarian menu, produk terlaris, dan daftar semua menu aktif */

// guard memastikan hanya pembeli yang login yang bisa masuk
// jika belum login atau bukan pembeli, akan di-redirect ke halaman login
include '../../3. komponen/guardpembeli.php';
// koneksi.php menyediakan variabel $conn untuk query ke database
include '../../1. koneksi/koneksi.php';

// ambil nama dan id pengguna dari session yang disimpan saat login
$namapengguna = $_SESSION['username'];
$idpengguna   = (int)$_SESSION['id_user'];

// ambil parameter filter dari URL (GET)
// real_escape_string mencegah SQL injection pada input teks
$kategori = isset($_GET['kategori']) ? $conn->real_escape_string($_GET['kategori']) : '';
$cari     = isset($_GET['cari'])     ? $conn->real_escape_string($_GET['cari'])     : '';
// (int) memastikan id_toko hanya berisi angka, bukan teks berbahaya
$idtoko   = isset($_GET['toko'])     ? (int)$_GET['toko']                           : 0;

// ambil semua toko yang sedang buka (status_toko='buka') dan belum dihapus (deleted=0)
// pembeli hanya bisa melihat toko yang aktif buka
$hasiltoko  = $conn->query("SELECT t.id_toko, t.nama_toko, t.foto_toko FROM tb_toko t WHERE t.deleted=0 AND t.status_toko='buka' ORDER BY t.id_toko");
$daftartoko = $hasiltoko->fetch_all(MYSQLI_ASSOC);

// jika pembeli memilih toko tertentu lewat URL ?toko=X, cek apakah toko itu sedang tutup
// ini perlu karena link toko bisa tersimpan di bookmark meskipun toko sudah tutup
$tokotutup = false;
$namatokoditutup = '';
if ($idtoko > 0) {
    $cektoko = $conn->prepare("SELECT nama_toko, status_toko FROM tb_toko WHERE id_toko=? AND deleted=0");
    $cektoko->bind_param("i", $idtoko);
    $cektoko->execute();
    $datatoko = $cektoko->get_result()->fetch_assoc();
    $cektoko->close();
    if ($datatoko && $datatoko['status_toko'] === 'tutup') {
        $tokotutup = true;
        $namatokoditutup = $datatoko['nama_toko'];
    }
}

// bangun kondisi WHERE untuk query daftar menu
// hanya tampilkan menu yang aktif, stok > 0, dan tokonya sedang buka
$kondisi = ["m.deleted=0", "m.status='aktif'", "m.stok>0", "t.status_toko='buka'"];
if ($kategori) $kondisi[] = "m.kategori='$kategori'"; // tambah filter kategori jika ada
if ($cari)     $kondisi[] = "m.nama_menu LIKE '%$cari%'"; // filter pencarian nama menu
// hanya filter per toko jika toko tidak tutup
if ($idtoko && !$tokotutup) $kondisi[] = "m.id_toko=$idtoko";
$where = implode(' AND ', $kondisi);
// LEFT JOIN supaya data toko tetap muncul meski ada data yang tidak cocok
$hasilmenu = $conn->query("SELECT m.*,t.nama_toko FROM tb_menu m LEFT JOIN tb_toko t ON m.id_toko=t.id_toko WHERE $where ORDER BY m.id_menu");

// ambil kategori unik hanya dari menu toko yang buka, untuk ditampilkan sebagai chip filter
$hasilkat  = $conn->query("SELECT DISTINCT m.kategori FROM tb_menu m JOIN tb_toko t ON m.id_toko=t.id_toko WHERE m.deleted=0 AND m.status='aktif' AND m.stok>0 AND t.status_toko='buka' ORDER BY m.kategori");
$daftarkat = $hasilkat->fetch_all(MYSQLI_ASSOC);

// cari nama toko yang sedang dipilih untuk ditampilkan di sapaan
$namatokoaktif = '';
foreach ($daftartoko as $t) {
    if ((int)$t['id_toko'] === $idtoko) { $namatokoaktif = $t['nama_toko']; break; }
}

// hitung jumlah pesanan aktif milik pembeli ini
// "aktif" artinya belum selesai: Menunggu, Diproses, atau Siap Diambil
// ditampilkan di sapaan sebagai pengingat
$hitungaktif = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND status_order IN ('Menunggu','Diproses','Siap Diambil') AND deleted=0");
$hitungaktif->bind_param("i", $idpengguna);
$hitungaktif->execute();
$jumlahaktif = $hitungaktif->get_result()->fetch_row()[0];
$hitungaktif->close();

// ambil 5 produk terlaris berdasarkan jumlah terjual
// hanya ditampilkan di beranda tanpa filter (supaya tidak membingungkan)
$produkterlaris = [];
if (!$kategori && !$cari && !$idtoko) {
    $qtl = $conn->query("SELECT m.id_menu, m.nama_menu, m.harga, m.foto, t.nama_toko, t.id_toko,
                                SUM(d.jumlah) AS terjual
                         FROM tb_detail_order d
                         JOIN tb_menu m ON d.id_menu=m.id_menu
                         JOIN tb_toko t ON m.id_toko=t.id_toko
                         JOIN tb_order o ON d.id_order=o.id_order
                         WHERE d.deleted=0 AND o.deleted=0 AND t.status_toko='buka'
                           AND m.deleted=0 AND m.status='aktif' AND m.stok>0
                           AND o.status_order != 'Dibatalkan'
                         GROUP BY m.id_menu, m.nama_menu, m.harga, m.foto, t.nama_toko, t.id_toko
                         ORDER BY terjual DESC
                         LIMIT 5");
    if ($qtl) $produkterlaris = $qtl->fetch_all(MYSQLI_ASSOC);
}

// $pathbase dipakai oleh navbar untuk menentukan path relatif ke folder lain
$pathbase = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beranda - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<!-- bagian sapaan pembeli di bagian atas halaman -->
<div class="sapaan">
  <div class="salamkecil">Selamat datang</div>
  <!-- htmlspecialchars mencegah XSS: karakter < > & ' " diubah jadi entitas HTML -->
  <h1>Halo, <?= htmlspecialchars($namapengguna) ?>!</h1>
  <div class="sapaaninfo">
    <!-- tampilkan nama toko aktif jika pembeli sedang memfilter per toko -->
    <?php if ($namatokoaktif): ?>
    <span class="sapaaninfobadge">
      <i class="fa-solid fa-store"></i>
      <?= htmlspecialchars($namatokoaktif) ?>
    </span>
    <?php endif; ?>
    <!-- tampilkan jumlah pesanan aktif jika ada, sebagai pengingat -->
    <?php if ($jumlahaktif > 0): ?>
    <a href="../pesanan/pesanan.php" class="sapaaninfobadge" style="text-decoration:none;">
      <i class="fa-solid fa-clock"></i>
      <?= $jumlahaktif ?> pesanan aktif
    </a>
    <?php endif; ?>
  </div>
</div>

<div class="bungkus">

  <!-- daftar toko yang sedang buka — hanya toko aktif yang ditampilkan -->
  <?php if (!empty($daftartoko)): ?>
  <div class="judulbagian"><i class="fa-solid fa-store"></i> Pilih Kantin</div>
  <div class="geserkantin" style="margin-bottom:16px;">

    <!-- tombol "Semua" untuk menghapus filter toko -->
    <a href="index.php" class="itemkantin <?= $idtoko===0?'aktif':'' ?>">
      <div class="ikon"><i class="fa-solid fa-utensils"></i></div>
      <span class="namakan">Semua</span>
    </a>

    <?php
    // warna inisial logo bergantian antara 'utama' dan 'kedua'
    $warnalogo = ['utama','kedua'];
    $iw = 0;
    foreach ($daftartoko as $toko):
      // ambil 2 huruf pertama nama toko sebagai inisial avatar
      $inisialkantin = strtoupper(mb_substr($toko['nama_toko'], 0, 2));
      $warnakini = $warnalogo[$iw % count($warnalogo)];
      $fototoko  = $toko['foto_toko'] ?? '';
      $iw++;
    ?>
    <a href="index.php?toko=<?= $toko['id_toko'] ?>" class="itemkantin <?= $idtoko===(int)$toko['id_toko']?'aktif':'' ?>">
      <!-- tampilkan foto toko jika ada, jika tidak tampilkan inisial berwarna -->
      <?php if ($fototoko && file_exists("../../2. aset/profil/" . $fototoko)): ?>
      <div class="ikon" style="overflow:hidden;padding:0;">
        <img src="../../2. aset/profil/<?= htmlspecialchars($fototoko) ?>"
             alt="<?= htmlspecialchars($toko['nama_toko']) ?>"
             style="width:100%;height:100%;object-fit:cover;">
      </div>
      <?php else: ?>
      <div class="ikon" style="background:var(--<?= $warnakini ?>);color:var(--putihbg);font-size:14px;font-weight:800;">
        <?= $inisialkantin ?>
      </div>
      <?php endif; ?>
      <span class="namakan"><?= htmlspecialchars($toko['nama_toko']) ?></span>
    </a>
    <?php endforeach; ?>

  </div>
  <?php endif; ?>

  <!-- peringatan jika toko yang dipilih sedang tutup -->
  <?php if ($tokotutup): ?>
  <div class="peringatan peringatangagal" style="margin-bottom:16px;">
    <i class="fa-solid fa-store-slash"></i>
    <span>
      Kantin <strong><?= htmlspecialchars($namatokoditutup) ?></strong> sedang tutup.
      Silakan pilih kantin lain atau kembali nanti.
    </span>
  </div>
  <?php endif; ?>

  <!-- form pencarian menu dengan metode GET supaya URL bisa di-bookmark/share -->
  <div class="formcari">
    <form method="GET" action="index.php">
      <!-- pertahankan filter toko dan kategori saat pencarian dilakukan -->
      <?php if ($idtoko && !$tokotutup): ?><input type="hidden" name="toko" value="<?= $idtoko ?>"><?php endif; ?>
      <?php if ($kategori): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>"><?php endif; ?>
      <div class="kotakcari">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" placeholder="Cari menu favoritmu...">
        <button type="submit" class="tombolcari"><i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </form>
  </div>

  <!-- chip filter kategori — hanya tampil jika toko tidak tutup -->
  <?php if (!$tokotutup): ?>
  <?php
  // kategori tetap selalu ditampilkan meski tidak ada menu di database
  $kategoriTetap = ['Makanan Berat','Makanan Ringan','Makanan Sehat','Minuman Ringan','Minuman Sehat'];
  // ambil kolom 'kategori' dari array hasil query database
  $katDB = array_column($daftarkat, 'kategori');
  ?>
  <div class="filter-bar">
    <!-- chip "Semua" — reset filter kategori -->
    <a href="index.php<?= $idtoko?'?toko='.$idtoko:'' ?>"
       class="chip-filter <?= $kategori===''?'aktif':'' ?>">Semua</a>
    <!-- tampilkan kategori tetap terlebih dahulu -->
    <?php foreach ($kategoriTetap as $kn): ?>
    <a href="index.php?kategori=<?= urlencode($kn) ?><?= $idtoko?'&toko='.$idtoko:'' ?>"
       class="chip-filter <?= $kategori===$kn?'aktif':'' ?>"><?= $kn ?></a>
    <?php endforeach; ?>
    <!-- tampilkan kategori tambahan dari database yang tidak ada di daftar tetap -->
    <?php foreach ($daftarkat as $k): ?>
      <?php if (!in_array($k['kategori'], $kategoriTetap)): ?>
      <a href="index.php?kategori=<?= urlencode($k['kategori']) ?><?= $idtoko?'&toko='.$idtoko:'' ?>"
         class="chip-filter <?= $kategori===$k['kategori']?'aktif':'' ?>"><?= htmlspecialchars($k['kategori']) ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- bagian produk terlaris — hanya muncul di beranda tanpa filter aktif -->
  <?php if (!empty($produkterlaris)): ?>
  <div class="judulbagian"><i class="fa-solid fa-fire"></i> Produk Terlaris</div>
  <div class="gridmenu" style="margin-bottom:20px;">
    <?php foreach ($produkterlaris as $tl): ?>
    <div class="kartumenu">
      <!-- klik gambar menuju halaman detail menu -->
      <a href="../pesanan/detail.php?id=<?= $tl['id_menu'] ?>">
        <!-- onerror: jika gambar gagal dimuat, tampilkan background kosong -->
        <img class="gambarmenu"
             src="../../2. aset/katalog/<?= htmlspecialchars($tl['foto']) ?>"
             alt="<?= htmlspecialchars($tl['nama_menu']) ?>"
             onerror="this.style.background='var(--latar)'">
      </a>
      <div class="isikartu">
        <div class="namamenu"><?= htmlspecialchars($tl['nama_menu']) ?></div>
        <div class="namakantin">
          <i class="fa-solid fa-store" style="font-size:10px;"></i>
          <?= htmlspecialchars($tl['nama_toko']) ?>
        </div>
        <!-- number_format: format angka ribuan dengan titik, desimal dengan koma -->
        <div class="hargamenu">Rp <?= number_format($tl['harga'],0,',','.') ?></div>
        <div class="stokmenu" style="color:var(--tunggu);font-weight:600;">
          <i class="fa-solid fa-fire" style="font-size:10px;"></i>
          <?= $tl['terjual'] ?> terjual
        </div>
        <!-- form tambah cepat ke keranjang langsung dari kartu produk -->
        <form method="POST" action="../keranjang/proseskeranjang.php" class="formtambahcepat">
          <input type="hidden" name="aksi" value="tambah">
          <input type="hidden" name="id_menu" value="<?= $tl['id_menu'] ?>">
          <!-- qty default 1 saat tambah cepat -->
          <input type="hidden" name="qty" value="1">
          <!-- kembali=index artinya setelah proses, redirect kembali ke halaman beranda -->
          <input type="hidden" name="kembali" value="index">
          <button type="submit" class="tomboltambah" title="Tambah ke keranjang">
            <i class="fa-solid fa-plus"></i>
          </button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- daftar semua menu — tidak tampil jika toko yang dipilih tutup -->
  <?php if (!$tokotutup): ?>
  <div class="judulbagian">
    <i class="fa-solid fa-bowl-food"></i>
    Daftar Menu
    <!-- tampilkan jumlah menu yang ditemukan -->
    <?php if ($hasilmenu && $hasilmenu->num_rows > 0): ?>
    <span style="font-size:11px;color:var(--tekssamar);font-weight:500;text-transform:none;letter-spacing:0;">
      (<?= $hasilmenu->num_rows ?> menu)
    </span>
    <?php endif; ?>
  </div>

  <?php if ($hasilmenu && $hasilmenu->num_rows > 0): ?>
  <div class="gridmenu">
    <!-- fetch_assoc mengambil satu baris hasil query setiap iterasi -->
    <?php while ($menu = $hasilmenu->fetch_assoc()): ?>
    <div class="kartumenu">
      <a href="../pesanan/detail.php?id=<?= $menu['id_menu'] ?>">
        <img class="gambarmenu"
             src="../../2. aset/katalog/<?= htmlspecialchars($menu['foto']) ?>"
             alt="<?= htmlspecialchars($menu['nama_menu']) ?>"
             onerror="this.style.background='var(--latar)'">
      </a>
      <div class="isikartu">
        <div class="namamenu"><?= htmlspecialchars($menu['nama_menu']) ?></div>
        <!-- nama toko hanya muncul jika ada (tampil di mode "semua toko") -->
        <?php if ($menu['nama_toko']): ?>
        <div class="namakantin">
          <i class="fa-solid fa-store" style="font-size:10px;"></i>
          <?= htmlspecialchars($menu['nama_toko']) ?>
        </div>
        <?php endif; ?>
        <div class="hargamenu">Rp <?= number_format($menu['harga'],0,',','.') ?></div>
        <div class="stokmenu">Stok: <?= $menu['stok'] ?></div>
        <!-- form tambah ke keranjang dari kartu menu biasa -->
        <form method="POST" action="../keranjang/proseskeranjang.php" class="formtambahcepat">
          <input type="hidden" name="aksi" value="tambah">
          <input type="hidden" name="id_menu" value="<?= $menu['id_menu'] ?>">
          <input type="hidden" name="qty" value="1">
          <input type="hidden" name="kembali" value="index">
          <button type="submit" class="tomboltambah" title="Tambah ke keranjang">
            <i class="fa-solid fa-plus"></i>
          </button>
        </form>
      </div>
    </div>
    <?php endwhile; ?>
  </div>

  <?php else: ?>
  <!-- tampilan kosong jika tidak ada menu yang sesuai filter/pencarian -->
  <div class="kosong">
    <div class="ikonkosong"><i class="fa-solid fa-bowl-food"></i></div>
    <h3>Menu tidak ditemukan</h3>
    <p>Coba kata kunci lain atau pilih kategori berbeda</p>
    <a href="index.php" class="tombolringan">Lihat semua menu</a>
  </div>
  <?php endif; ?>
  <?php endif; ?>

</div>
</body>
</html>
