<?php
/* ============================================================
   BERANDA PEMBELI
   Hanya tampilkan menu dari toko yang statusnya 'buka'.
   ============================================================ */
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

$namapengguna = $_SESSION['username'];
$idpengguna   = (int)$_SESSION['id_user'];

// Filter dari URL
$kategori = isset($_GET['kategori']) ? $conn->real_escape_string($_GET['kategori']) : '';
$cari     = isset($_GET['cari'])     ? $conn->real_escape_string($_GET['cari'])     : '';
$idtoko   = isset($_GET['toko'])     ? (int)$_GET['toko']                           : 0;

// Daftar toko yang sedang BUKA saja
$hasiltoko  = $conn->query("SELECT t.id_toko, t.nama_toko FROM tb_toko t WHERE t.deleted=0 AND t.status_toko='buka' ORDER BY t.id_toko");
$daftartoko = $hasiltoko->fetch_all(MYSQLI_ASSOC);

// Cek apakah toko yang dipilih sedang tutup
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

// Query daftar menu — hanya dari toko yang buka
$kondisi = ["m.deleted=0", "m.status='aktif'", "m.stok>0", "t.status_toko='buka'"];
if ($kategori) $kondisi[] = "m.kategori='$kategori'";
if ($cari)     $kondisi[] = "m.nama_menu LIKE '%$cari%'";
if ($idtoko && !$tokotutup) $kondisi[] = "m.id_toko=$idtoko";
$where = implode(' AND ', $kondisi);
$hasilmenu = $conn->query("SELECT m.*,t.nama_toko FROM tb_menu m LEFT JOIN tb_toko t ON m.id_toko=t.id_toko WHERE $where ORDER BY m.id_menu");

// Kategori unik (hanya dari toko buka)
$hasilkat  = $conn->query("SELECT DISTINCT m.kategori FROM tb_menu m JOIN tb_toko t ON m.id_toko=t.id_toko WHERE m.deleted=0 AND m.status='aktif' AND m.stok>0 AND t.status_toko='buka' ORDER BY m.kategori");
$daftarkat = $hasilkat->fetch_all(MYSQLI_ASSOC);

// Nama toko aktif yang dipilih
$namatokoaktif = '';
foreach ($daftartoko as $t) {
    if ((int)$t['id_toko'] === $idtoko) { $namatokoaktif = $t['nama_toko']; break; }
}

// Jumlah pesanan aktif untuk sapaan
$hitungaktif = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND status_order IN ('Menunggu','Diproses','Siap Diambil') AND deleted=0");
$hitungaktif->bind_param("i", $idpengguna);
$hitungaktif->execute();
$jumlahaktif = $hitungaktif->get_result()->fetch_row()[0];
$hitungaktif->close();

// Produk terlaris (dari toko yang buka, hanya tampil di beranda tanpa filter)
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
                         GROUP BY m.id_menu, m.nama_menu, m.harga, m.foto, t.nama_toko, t.id_toko
                         ORDER BY terjual DESC
                         LIMIT 5");
    if ($qtl) $produkterlaris = $qtl->fetch_all(MYSQLI_ASSOC);
}

$pathbase = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beranda - eKantin</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<!-- SAPAAN -->
<div class="sapaan">
  <div class="salamkecil">Selamat datang</div>
  <h1>Halo, <?= htmlspecialchars($namapengguna) ?>!</h1>
  <div class="sapaaninfo">
    <?php if ($namatokoaktif): ?>
    <span class="sapaaninfobadge">
      <i class="fa-solid fa-store"></i>
      <?= htmlspecialchars($namatokoaktif) ?>
    </span>
    <?php endif; ?>
    <?php if ($jumlahaktif > 0): ?>
    <a href="../pesanan/pesanan.php" class="sapaaninfobadge" style="text-decoration:none;">
      <i class="fa-solid fa-clock"></i>
      <?= $jumlahaktif ?> pesanan aktif
    </a>
    <?php endif; ?>
  </div>
</div>

<div class="bungkus">

  <!-- PILIH KANTIN (hanya yang buka) -->
  <?php if (!empty($daftartoko)): ?>
  <div class="judulbagian"><i class="fa-solid fa-store"></i> Pilih Kantin</div>
  <div class="geserkantin" style="margin-bottom:16px;">

    <a href="index.php" class="itemkantin <?= $idtoko===0?'aktif':'' ?>">
      <div class="ikon"><i class="fa-solid fa-utensils"></i></div>
      <span class="namakan">Semua</span>
    </a>

    <?php
    $warnalogo = ['utama','kedua'];
    $iw = 0;
    foreach ($daftartoko as $toko):
      $inisialkantin = strtoupper(mb_substr($toko['nama_toko'], 0, 2));
      $warnakini = $warnalogo[$iw % count($warnalogo)];
      $iw++;
    ?>
    <a href="index.php?toko=<?= $toko['id_toko'] ?>" class="itemkantin <?= $idtoko===(int)$toko['id_toko']?'aktif':'' ?>">
      <div class="ikon" style="background:var(--<?= $warnakini ?>); color:var(--putihbg); font-size:14px; font-weight:800;">
        <?= $inisialkantin ?>
      </div>
      <span class="namakan"><?= htmlspecialchars($toko['nama_toko']) ?></span>
    </a>
    <?php endforeach; ?>

  </div>
  <?php endif; ?>

  <!-- Peringatan toko tutup -->
  <?php if ($tokotutup): ?>
  <div class="peringatan peringatangagal" style="margin-bottom:16px;">
    <i class="fa-solid fa-store-slash"></i>
    <span>
      Kantin <strong><?= htmlspecialchars($namatokoditutup) ?></strong> sedang tutup.
      Silakan pilih kantin lain atau kembali nanti.
    </span>
  </div>
  <?php endif; ?>

  <!-- CARI MENU -->
  <div class="formcari">
    <form method="GET" action="index.php">
      <?php if ($idtoko && !$tokotutup): ?><input type="hidden" name="toko" value="<?= $idtoko ?>"><?php endif; ?>
      <?php if ($kategori): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>"><?php endif; ?>
      <div class="kotakcari">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" placeholder="Cari menu favoritmu...">
        <button type="submit" class="tombolcari"><i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </form>
  </div>

  <!-- FILTER KATEGORI -->
  <?php if (!$tokotutup): ?>
  <?php
  $kategoriTetap = ['Makanan Berat','Makanan Ringan','Makanan Sehat','Minuman Ringan','Minuman Sehat'];
  $katDB = array_column($daftarkat, 'kategori');
  ?>
  <div class="filter-bar">
    <a href="index.php<?= $idtoko?'?toko='.$idtoko:'' ?>"
       class="chip-filter <?= $kategori===''?'aktif':'' ?>">Semua</a>
    <?php foreach ($kategoriTetap as $kn): ?>
    <a href="index.php?kategori=<?= urlencode($kn) ?><?= $idtoko?'&toko='.$idtoko:'' ?>"
       class="chip-filter <?= $kategori===$kn?'aktif':'' ?>"><?= $kn ?></a>
    <?php endforeach; ?>
    <?php foreach ($daftarkat as $k): ?>
      <?php if (!in_array($k['kategori'], $kategoriTetap)): ?>
      <a href="index.php?kategori=<?= urlencode($k['kategori']) ?><?= $idtoko?'&toko='.$idtoko:'' ?>"
         class="chip-filter <?= $kategori===$k['kategori']?'aktif':'' ?>"><?= htmlspecialchars($k['kategori']) ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- PRODUK TERLARIS -->
  <?php if (!empty($produkterlaris)): ?>
  <div class="judulbagian"><i class="fa-solid fa-fire"></i> Produk Terlaris</div>
  <div class="gridmenu" style="margin-bottom:20px;">
    <?php foreach ($produkterlaris as $tl): ?>
    <div class="kartumenu">
      <a href="../pesanan/detail.php?id=<?= $tl['id_menu'] ?>">
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
        <div class="hargamenu">Rp <?= number_format($tl['harga'],0,',','.') ?></div>
        <div class="stokmenu" style="color:var(--tunggu);font-weight:600;">
          <i class="fa-solid fa-fire" style="font-size:10px;"></i>
          <?= $tl['terjual'] ?> terjual
        </div>
        <form method="POST" action="../keranjang/proseskeranjang.php" class="formtambahcepat">
          <input type="hidden" name="aksi" value="tambah">
          <input type="hidden" name="id_menu" value="<?= $tl['id_menu'] ?>">
          <input type="hidden" name="qty" value="1">
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

  <!-- DAFTAR MENU -->
  <?php if (!$tokotutup): ?>
  <div class="judulbagian">
    <i class="fa-solid fa-bowl-food"></i>
    Daftar Menu
    <?php if ($hasilmenu && $hasilmenu->num_rows > 0): ?>
    <span style="font-size:11px;color:var(--tekssamar);font-weight:500;text-transform:none;letter-spacing:0;">
      (<?= $hasilmenu->num_rows ?> menu)
    </span>
    <?php endif; ?>
  </div>

  <?php if ($hasilmenu && $hasilmenu->num_rows > 0): ?>
  <div class="gridmenu">
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
        <?php if ($menu['nama_toko']): ?>
        <div class="namakantin">
          <i class="fa-solid fa-store" style="font-size:10px;"></i>
          <?= htmlspecialchars($menu['nama_toko']) ?>
        </div>
        <?php endif; ?>
        <div class="hargamenu">Rp <?= number_format($menu['harga'],0,',','.') ?></div>
        <div class="stokmenu">Stok: <?= $menu['stok'] ?></div>
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
