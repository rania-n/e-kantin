<?php
/* halaman konfirmasi pesanan (checkout)
   menampilkan ringkasan item dari satu toko yang dipilih,
   form catatan, pilihan metode pembayaran, dan total biaya
   sebelum pembeli menekan tombol "Pesan" untuk memproses order */

// cegah browser menyimpan cache halaman checkout agar tidak ada form lama yang terisi otomatis
// ini mencegah pengguna melihat konfirmasi toko yang salah karena halaman tersimpan di cache
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// guard memastikan hanya pembeli yang login yang bisa mengakses
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

// ambil id toko dari parameter URL — checkout dilakukan per toko
$idtoko    = (int)($_GET['toko'] ?? 0);
// ambil keranjang dari session
$keranjang = $_SESSION['keranjang'] ?? [];
// reset itemtoko sebelum dicari
$itemtoko  = null;

// cari toko berdasarkan kunci array session (cara utama)
// kunci array session adalah id_toko sebagai integer, sama dengan nilai dari url
if ($idtoko && isset($keranjang[$idtoko])) {
    $itemtoko = $keranjang[$idtoko]; // ambil seluruh data toko berikut item-itemnya
}

// fallback: jika kunci tidak cocok (misalnya perbedaan tipe string/int saat deserialisasi),
// scan seluruh keranjang dan cocokkan id_toko dari _info yang tersimpan secara eksplisit
// cara ini lebih tahan terhadap ketidakcocokan tipe karena membandingkan nilai cast ke int
if (!$itemtoko && $idtoko) {
    foreach ($keranjang as $kuncitoko => $datatoko) {
        // bandingkan id_toko yang tersimpan di _info dengan id dari url
        if ((int)($datatoko['_info']['id_toko'] ?? 0) === $idtoko) {
            $itemtoko = $datatoko;          // simpan data toko yang cocok
            $idtoko   = (int)$kuncitoko;   // selaraskan $idtoko dengan kunci yang ditemukan
            break;                          // hentikan pencarian setelah toko ditemukan
        }
    }
}

// jika toko tidak ditemukan lewat kedua cara, tolak dan arahkan kembali ke keranjang
// kondisi ini terjadi jika url dimanipulasi atau session sudah kedaluwarsa
if (!$idtoko || !$itemtoko) {
    header("Location: ../keranjang/keranjang.php"); exit;
}

// ambil nama toko dari data _info yang tersimpan di session
$namatoko   = $itemtoko['_info']['nama_toko'] ?? 'Kantin';
$daftaritem = [];
$subtotal   = 0;

// pisahkan _info dari item menu, hitung subtotal
foreach ($itemtoko as $k => $v) {
    if ($k === '_info') continue; // lewati data info toko
    $subtotal    += $v['harga'] * $v['qty'];
    $daftaritem[] = $v;
}

// jika tidak ada item (keranjang toko kosong), kembali ke keranjang
if (empty($daftaritem)) { header("Location: ../keranjang/keranjang.php"); exit; }

// total yang dibayar sama dengan subtotal — tidak ada biaya layanan
$totalbayar = $subtotal;

$pathbase = '..';

// simpan id toko yang sudah divalidasi ke variabel terpisah sebelum html dimulai
// navbarpembeli.php yang di-include di dalam template menjalankan foreach dengan
// variabel bernama $idtoko dan $itemtoko — karena include berbagi scope, keduanya
// akan tertimpa oleh nilai toko terakhir di keranjang setelah include selesai
// solusi: simpan dulu ke variabel baru sebelum include dijalankan
$idtokosudahpilih = $idtoko;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkus" style="padding-bottom:30px;">

  <!-- judul halaman dengan nama toko yang sedang di-checkout -->
  <div style="margin-bottom:18px;">
    <h1 style="font-size:20px;font-weight:800;color:var(--utama);display:flex;align-items:center;gap:8px;">
      <i class="fa-solid fa-bag-shopping"></i> Konfirmasi Pesanan
    </h1>
    <p style="font-size:13px;color:var(--tekssamar);margin-top:2px;">
      <i class="fa-solid fa-store"></i> <?= htmlspecialchars($namatoko) ?>
    </p>
  </div>

  <!-- form pengiriman ke prosespesanan.php via POST -->
  <form method="POST" action="prosespesanan.php">
    <!-- kirim id toko ke proses — pakai variabel yang disimpan sebelum navbarpembeli.php menimpa $idtoko -->
    <input type="hidden" name="id_toko" value="<?= $idtokosudahpilih ?>">
    <!-- token dihitung dari id toko yang sudah disimpan, bukan $idtoko yang sudah tertimpa -->
    <input type="hidden" name="token_checkout" value="<?= htmlspecialchars(md5($idtokosudahpilih . '_' . session_id())) ?>">

    <!-- daftar item yang akan dipesan -->
    <div class="judulbagian"><i class="fa-solid fa-list"></i> Detail Pesanan</div>
    <div class="kartu">
      <?php foreach ($daftaritem as $item): ?>
      <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:10px 0;border-bottom:1px solid var(--latar);">
        <div style="flex:1;">
          <div style="font-size:14px;font-weight:700;"><?= htmlspecialchars($item['nama_menu']) ?></div>
          <div style="font-size:12px;color:var(--tekssamar);">
            <!-- tampilkan qty x harga satuan -->
            <?= $item['qty'] ?> x Rp <?= number_format($item['harga'],0,',','.') ?>
          </div>
        </div>
        <!-- subtotal per item = harga x qty -->
        <div style="font-size:15px;font-weight:800;color:var(--utama);margin-left:12px;">
          Rp <?= number_format($item['harga']*$item['qty'],0,',','.') ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- input catatan opsional untuk penjual, misalnya "tidak pedas" -->
    <div class="judulbagian"><i class="fa-solid fa-note-sticky"></i> Catatan untuk Penjual</div>
    <div class="kelompokform" style="margin-bottom:18px;">
      <textarea name="catatan" rows="2" placeholder="Misal: pisahkan sambelnya, jangan pedas..."></textarea>
    </div>

    <!-- metode pembayaran: hanya tunai (bayar di tempat ke penjual langsung) -->
    <div class="judulbagian"><i class="fa-solid fa-wallet"></i> Metode Pembayaran</div>
    <div style="margin-bottom:18px;">
      <!-- input hidden: metode selalu Tunai, tidak perlu dipilih lagi -->
      <input type="hidden" name="metode" value="Tunai">
      <!-- tampilkan info bahwa pembayaran hanya tunai -->
      <div class="pilihanmetode" style="cursor:default;border-color:var(--utama);background:rgba(99,56,67,.05);">
        <span>
          <i class="fa-solid fa-money-bill-wave" style="color:var(--utama);"></i>
          <strong>Bayar Tunai di Kantin</strong>
          <br>
          <small style="color:var(--tekssamar);font-weight:400;">
            Pembayaran dilakukan langsung ke penjual saat mengambil pesanan di kantin.
          </small>
        </span>
      </div>
    </div>

    <!-- ringkasan harga dan tombol aksi -->
    <div class="judulbagian"><i class="fa-solid fa-receipt"></i> Ringkasan Pembayaran</div>
    <div class="kartu">
      <div class="ringkasan" style="margin-bottom:16px;">
        <!-- jumlah item dan harga total keseluruhan -->
        <div class="barisringkasan">
          <span>Subtotal (<?= count($daftaritem) ?> item)</span>
          <b>Rp <?= number_format($subtotal,0,',','.') ?></b>
        </div>
        <!-- total sama dengan subtotal karena tidak ada biaya tambahan -->
        <div class="barisringkasan total">
          <span>Total Bayar</span>
          <b>Rp <?= number_format($totalbayar,0,',','.') ?></b>
        </div>
      </div>
      <div style="display:flex;gap:10px;">
        <!-- tombol batal: kembali ke keranjang tanpa memproses -->
        <a href="../keranjang/keranjang.php" class="tombolringan" style="padding:10px 14px;font-size:13px;">
          Batal
        </a>
        <!-- tombol submit: kirim form ke prosespesanan.php -->
        <button type="submit" class="tombolutama" style="flex:2;padding:14px;font-size:15px;">
          <i class="fa-solid fa-bag-shopping"></i>
          Pesan &mdash; Rp <?= number_format($totalbayar,0,',','.') ?>
        </button>
      </div>
    </div>

  </form>

</div>
</body>
</html>
