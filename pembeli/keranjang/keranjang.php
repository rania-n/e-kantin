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

// kata kunci pencarian dari kotak cari keranjang — bisa cari nama item atau nama kantin.
// berguna saat keranjang berisi banyak item dari banyak kantin biar tidak bingung.
$cari = trim($_GET['cari'] ?? '');

/* tentukan kantin mana yang cocok dengan pencarian.
   $tokococok[id_toko] = true jika nama kantin ATAU salah satu nama item-nya mengandung $cari.
   stripos = pencarian tanpa peduli huruf besar/kecil. kalau $cari kosong, semua cocok.
   pencocokan dilakukan per-kantin (bukan per-item) supaya subtotal & tombol checkout
   tiap kantin tetap konsisten dengan seluruh isinya. */
$tokococok = [];
foreach ($keranjang as $idt => $items) {
    if ($cari === '') { $tokococok[$idt] = true; continue; }
    // cocok jika nama kantin mengandung kata kunci
    $cocok = stripos($items['_info']['nama_toko'] ?? '', $cari) !== false;
    // kalau belum cocok, cek tiap nama item di kantin ini
    if (!$cocok) {
        foreach ($items as $k => $v) {
            if ($k === '_info') continue;
            if (stripos($v['nama_menu'] ?? '', $cari) !== false) { $cocok = true; break; }
        }
    }
    if ($cocok) $tokococok[$idt] = true;
}

// hitung total harga + kumpulkan id_menu HANYA dari kantin yang cocok pencarian
// (saat $cari kosong semua kantin ikut, jadi total = seluruh isi keranjang seperti biasa)
$totalsemua = 0;
$idmenusemua = [];
foreach ($keranjang as $idt => $items) {
    if (empty($tokococok[$idt])) continue;
    foreach ($items as $k => $v) {
        // '_info' adalah data toko (nama, id), bukan item menu — lewati
        if ($k === '_info') continue;
        $totalsemua += $v['harga'] * $v['qty'];
        $idmenusemua[] = (int)$v['id_menu'];
    }
}

// jumlah kantin yang cocok — dipakai untuk pesan "tidak ditemukan" saat mencari
$jumlahcocok = count($tokococok);

// ambil stok terkini setiap menu di keranjang dalam satu query (efisien).
// $stokmap[id_menu] = stok. dipakai untuk menampilkan stok & membatasi input jumlah.
$stokmap = [];
if (!empty($idmenusemua)) {
    // intval pada tiap elemen + implode → daftar id aman dimasukkan ke klausa IN
    $daftarid = implode(',', array_map('intval', $idmenusemua));
    $resstok  = $conn->query("SELECT id_menu, stok FROM tb_menu WHERE id_menu IN ($daftarid) AND deleted=0");
    if ($resstok) {
        while ($rs = $resstok->fetch_assoc()) $stokmap[(int)$rs['id_menu']] = (int)$rs['stok'];
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

  <!-- satu kotak cari: ketik nama item / kantin untuk menyaring, ✕ untuk hapus
       (= tampil semua). langsung tersaring otomatis (Enter/✕/pindah fokus), tanpa
       tombol. hanya muncul kalau keranjang tidak kosong. -->
  <?php if (!empty($keranjang)): ?>
  <form method="GET" action="keranjang.php" style="margin-bottom:16px;">
    <input type="search" name="cari" value="<?= htmlspecialchars($cari) ?>"
           onchange="this.form.submit()" onsearch="this.form.submit()"
           placeholder="Cari item atau kantin di keranjang..."
           style="width:100%;padding:11px 14px;border:1.5px solid var(--garis);border-radius:12px;font-size:14px;font-family:inherit;background:var(--putih);color:var(--teks);">
  </form>
  <?php endif; ?>

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

  <?php if ($cari !== '' && $jumlahcocok === 0): ?>
  <!-- pencarian aktif tapi tidak ada kantin/item yang cocok -->
  <div class="kosong">
    <div class="ikonkosong"><i class="fa-solid fa-magnifying-glass"></i></div>
    <h3>Tidak ada hasil</h3>
    <p>Tidak ada item atau kantin yang cocok dengan "<?= htmlspecialchars($cari) ?>"</p>
    <a href="keranjang.php" class="tombolutama">
      <i class="fa-solid fa-arrow-left"></i> Lihat Semua
    </a>
  </div>
  <?php else: ?>

  <!-- info bahwa checkout dilakukan per kantin, bukan semua sekaligus -->
  <div class="peringatan peringataninfo" style="margin-bottom:16px;">
    <i class="fa-solid fa-circle-info"></i>
    <?= $cari !== '' ? 'Menampilkan hasil pencarian "'.htmlspecialchars($cari).'". Tekan checkout di kantin yang dituju.' : 'Tekan tombol checkout di setiap kantin untuk melanjutkan' ?>
  </div>

  <!-- loop setiap toko di keranjang — tiap toko punya grup item sendiri.
       lewati kantin yang tidak cocok dengan pencarian (saat $cari kosong semua tampil) -->
  <?php foreach ($keranjang as $idtoko => $itemtoko):
    if (empty($tokococok[$idtoko])) continue;
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
      // stok terkini menu ini (0 kalau menu sudah dihapus/tidak ditemukan)
      $stokitem = $stokmap[(int)$idmenu] ?? 0;
      // tandai jika jumlah di keranjang sudah melebihi stok yang tersisa
      $lebihstok = $isi['qty'] > $stokitem;
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
            <!-- tampilkan stok terkini menu ini agar pembeli tahu batas pesanan -->
            <div style="font-size:11px;margin-top:2px;color:<?= $stokitem<=0 ? 'var(--gagal)' : ($stokitem<=5 ? 'var(--tunggu)' : 'var(--tekssamar)') ?>;">
              <i class="fa-solid fa-box" style="font-size:9px;"></i>
              <?= $stokitem<=0 ? 'Stok habis' : 'Stok tersedia: '.$stokitem ?>
            </div>
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
          <!-- kontrol qty: tombol -/+ untuk cepat, dan input angka untuk ketik jumlah custom.
               semua pakai form POST (tanpa JS) karena proyek ini memang tanpa javascript. -->
          <div class="kontrolqty" style="flex-wrap:wrap;">
            <!-- form kurangi qty -->
            <form method="POST" action="proseskeranjang.php" style="display:contents;">
              <input type="hidden" name="aksi" value="kurang">
              <input type="hidden" name="id_toko" value="<?= $idtoko ?>">
              <input type="hidden" name="id_menu" value="<?= $idmenu ?>">
              <button type="submit" class="tombolqty">-</button>
            </form>
            <!-- form set_qty: ketik jumlah langsung, otomatis ter-update saat angka berubah
                 (onchange submit form) — tanpa perlu menekan tombol centang.
                 max dibatasi stok terkini agar tidak melebihi yang tersedia.
                 tombol "Set" disediakan sebagai cadangan jika JS dimatikan (noscript). -->
            <form method="POST" action="proseskeranjang.php" style="display:flex;align-items:center;gap:4px;">
              <input type="hidden" name="aksi" value="set_qty">
              <input type="hidden" name="id_toko" value="<?= $idtoko ?>">
              <input type="hidden" name="id_menu" value="<?= $idmenu ?>">
              <input type="number" name="qty" value="<?= (int)$isi['qty'] ?>"
                     min="1" max="<?= max(1,$stokitem) ?>"
                     onchange="this.form.submit()"
                     title="Ketik jumlah, otomatis diperbarui"
                     style="width:54px;text-align:center;padding:6px 4px;border:1.5px solid var(--garis);border-radius:8px;font-family:inherit;font-size:14px;font-weight:700;">
              <noscript><button type="submit" class="tombolqty">Set</button></noscript>
            </form>
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
        <!-- peringatan jika jumlah di keranjang melebihi stok terkini (stok turun setelah ditambahkan) -->
        <?php if ($lebihstok): ?>
        <div style="font-size:11px;color:var(--gagal);margin-top:6px;">
          <i class="fa-solid fa-triangle-exclamation"></i>
          Jumlah melebihi stok tersedia (<?= $stokitem ?>). Mohon kurangi sebelum checkout.
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- tombol checkout untuk toko ini — id_toko diambil dari data _info yang disimpan secara eksplisit
         bukan dari variabel $idtoko kunci foreach, karena navbarpembeli.php yang di-include sebelumnya
         sudah menimpa $idtoko dengan kunci toko terakhir di keranjang saat include dijalankan -->
    <?php $idtokocheckout = (int)($itemtoko['_info']['id_toko'] ?? $idtoko); // cast int agar url aman ?>
    <a href="../pesanan/checkout.php?toko=<?= $idtokocheckout ?>" class="tombolutama blok" style="margin-top:10px;">
      <i class="fa-solid fa-bag-shopping"></i>
      Checkout <?= htmlspecialchars($namatoko) ?> &mdash; Rp <?= number_format($subtokototal,0,',','.') ?>
    </a>

  </div>
  <?php endforeach; ?>

  <!-- ringkasan total belanja. saat mencari, total dihitung hanya dari kantin yang
       sedang ditampilkan (yang cocok pencarian), jadi konsisten dengan yang terlihat. -->
  <div class="ringkasan">
    <div class="judulbagian" style="margin:0 0 10px;"><i class="fa-solid fa-receipt"></i> <?= $cari !== '' ? 'Total Kantin Tampil' : 'Total Semua Kantin' ?></div>
    <div class="barisringkasan total">
      <span>Total Belanja</span>
      <b>Rp <?= number_format($totalsemua,0,',','.') ?></b>
    </div>
  </div>

  <?php endif; // tutup if hasil-pencarian / tampil-normal ?>
  <?php endif; // tutup if keranjang-kosong / berisi ?>

</div>
</body>
</html>
