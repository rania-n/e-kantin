<?php
/* halaman keranjang belanja pembeli
   menampilkan semua item yang sudah ditambahkan, dikelompokkan per toko
   checkout dilakukan satu toko sekaligus, bukan semua toko sekaligus */

// guard memastikan hanya pembeli yang login yang bisa mengakses halaman ini
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

// keranjang disimpan di session, bukan database
// ini karena keranjang bersifat sementara dan bisa berubah sebelum checkout
// ?? [] artinya jika $_SESSION['keranjang'] tidak ada, gunakan array kosong
$keranjang  = $_SESSION['keranjang'] ?? [];

// hitung total harga semua item dari semua toko di keranjang
$totalsemua = 0;
foreach ($keranjang as $idt => $items) {
    foreach ($items as $k => $v) {
        // '_info' adalah data toko (nama, id), bukan item menu — lewati
        if ($k === '_info') continue;
        $totalsemua += $v['harga'] * $v['qty'];
    }
}

$pathbase = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Keranjang - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkus">

  <div class="judulhalaman">
    <h1><i class="fa-solid fa-cart-shopping"></i> Keranjang</h1>
    <p>Checkout dilakukan per kantin</p>
  </div>

  <!-- tampilkan pesan kosong jika keranjang tidak ada item sama sekali -->
  <?php if (empty($keranjang)): ?>
  <div class="kosong">
    <div class="ikonkosong"><i class="fa-solid fa-cart-shopping"></i></div>
    <h3>Keranjang masih kosong</h3>
    <p>Pilih menu dulu dari halaman beranda</p>
    <a href="../index/index.php" class="tombolutama">
      <i class="fa-solid fa-utensils"></i> Lihat Menu
    </a>
  </div>

  <?php else: ?>

  <!-- info bahwa checkout dilakukan per kantin, bukan semua sekaligus -->
  <div class="peringatan peringataninfo" style="margin-bottom:16px;">
    <i class="fa-solid fa-circle-info"></i>
    Tekan tombol checkout di setiap kantin untuk melanjutkan
  </div>

  <!-- loop setiap toko di keranjang — tiap toko punya grup item sendiri -->
  <?php foreach ($keranjang as $idtoko => $itemtoko):
    // ambil nama toko dari data _info yang disimpan saat item pertama ditambahkan
    $namatoko    = $itemtoko['_info']['nama_toko'] ?? 'Kantin';
    // hitung subtotal khusus toko ini
    $subtokototal = 0;
    foreach ($itemtoko as $k => $v) {
        if ($k === '_info') continue;
        $subtokototal += $v['harga'] * $v['qty'];
    }
  ?>

  <div style="margin-bottom:24px;">

    <!-- header toko: nama dan subtotal toko ini -->
    <div class="headertoko">
      <i class="fa-solid fa-store" style="color:var(--kedua);font-size:16px;"></i>
      <span class="namamenu"><?= htmlspecialchars($namatoko) ?></span>
      <span class="subtotaltoko">Rp <?= number_format($subtokototal,0,',','.') ?></span>
    </div>

    <!-- loop setiap item menu dalam toko ini -->
    <?php foreach ($itemtoko as $idmenu => $isi):
      // skip kunci '_info' karena itu bukan item menu
      if ($idmenu === '_info') continue;
    ?>
    <div class="kartukeranjang">
      <!-- gambar menu -->
      <img class="gambarmenu"
           src="../../2. aset/katalog/<?= htmlspecialchars($isi['foto']) ?>"
           alt="<?= htmlspecialchars($isi['nama_menu']) ?>"
           onerror="this.style.background='var(--latar)'">
      <div class="isikeranjang">
        <div class="ataskeranjang">
          <div>
            <div class="namamenu"><?= htmlspecialchars($isi['nama_menu']) ?></div>
            <div class="hargasatu">Rp <?= number_format($isi['harga'],0,',','.') ?> / porsi</div>
          </div>
          <!-- form hapus item: kirim POST ke proseskeranjang.php dengan aksi='hapus' -->
          <form method="POST" action="proseskeranjang.php">
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id_toko" value="<?= $idtoko ?>">
            <input type="hidden" name="id_menu" value="<?= $idmenu ?>">
            <button type="submit" class="tombolhapus" title="Hapus">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          </form>
        </div>

        <div class="tengahkeranjang">
          <!-- kontrol qty menggunakan dua form terpisah: kurang dan tambah_qty -->
          <!-- menggunakan form POST bukan JS karena tidak ada JS di proyek ini -->
          <div class="kontrolqty">
            <!-- form kurangi qty -->
            <form method="POST" action="proseskeranjang.php" style="display:contents;">
              <input type="hidden" name="aksi" value="kurang">
              <input type="hidden" name="id_toko" value="<?= $idtoko ?>">
              <input type="hidden" name="id_menu" value="<?= $idmenu ?>">
              <button type="submit" class="tombolqty">-</button>
            </form>
            <!-- tampilkan qty saat ini -->
            <span class="angkaqty"><?= $isi['qty'] ?></span>
            <!-- form tambah qty -->
            <form method="POST" action="proseskeranjang.php" style="display:contents;">
              <input type="hidden" name="aksi" value="tambah_qty">
              <input type="hidden" name="id_toko" value="<?= $idtoko ?>">
              <input type="hidden" name="id_menu" value="<?= $idmenu ?>">
              <button type="submit" class="tombolqty">+</button>
            </form>
          </div>
          <!-- subtotal item = harga x qty -->
          <div class="subtotalitem">Rp <?= number_format($isi['harga']*$isi['qty'],0,',','.') ?></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- tombol checkout untuk toko ini saja — menuju checkout.php dengan parameter id toko -->
    <a href="../pesanan/checkout.php?toko=<?= $idtoko ?>" class="tombolutama blok" style="margin-top:10px;">
      <i class="fa-solid fa-bag-shopping"></i>
      Checkout <?= htmlspecialchars($namatoko) ?> &mdash; Rp <?= number_format($subtokototal,0,',','.') ?>
    </a>

  </div>
  <?php endforeach; ?>

  <!-- ringkasan total belanja dari semua toko yang ada di keranjang -->
  <div class="ringkasan">
    <div class="judulbagian" style="margin:0 0 10px;"><i class="fa-solid fa-receipt"></i> Total Semua Kantin</div>
    <div class="barisringkasan total">
      <span>Total Belanja</span>
      <b>Rp <?= number_format($totalsemua,0,',','.') ?></b>
    </div>
  </div>

  <?php endif; ?>

</div>
</body>
</html>
