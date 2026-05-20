<?php
/* halaman struk pesanan digital
   menampilkan detail lengkap pesanan yang sudah dibuat:
   nomor antrian, informasi pesanan, daftar item, total harga
   juga bisa dicetak via browser (mode ?cetak=1)
   nomor antrian dihitung dari urutan order hari itu per toko */

// guard memastikan hanya pembeli yang login yang bisa masuk
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

// ambil id pesanan dari URL
$idpesanan  = (int)($_GET['id_order'] ?? 0);
// ambil id pengguna yang sedang login dari session
$idpengguna = (int)$_SESSION['id_user'];

// jika id pesanan tidak valid, kembali ke daftar pesanan
if (!$idpesanan) { header("Location: pesanan.php"); exit; }

// ambil data pesanan beserta nama tokonya
// id_user dicocokkan juga untuk memastikan struk ini memang milik pembeli yang login
$q = $conn->prepare("SELECT o.*,t.nama_toko FROM tb_order o LEFT JOIN tb_toko t ON o.id_toko=t.id_toko WHERE o.id_order=? AND o.id_user=? AND o.deleted=0");
$q->bind_param("ii", $idpesanan, $idpengguna);
$q->execute();
$pesanan = $q->get_result()->fetch_assoc();
$q->close();

// jika pesanan tidak ditemukan atau bukan milik pembeli ini, redirect
if (!$pesanan) { header("Location: pesanan.php"); exit; }

// ambil daftar item yang dipesan dari tabel detail_order
$d = $conn->prepare("SELECT d.*,m.nama_menu FROM tb_detail_order d JOIN tb_menu m ON d.id_menu=m.id_menu WHERE d.id_order=? AND d.deleted=0");
$d->bind_param("i", $idpesanan);
$d->execute();
$detail = $d->get_result()->fetch_all(MYSQLI_ASSOC);
$d->close();

/* hitung nomor antrian harian per toko
   logika: berapa banyak order di toko yang sama pada hari yang sama
   dengan id_order <= id pesanan ini (urutan berdasarkan id_order)
   nomor antrian reset otomatis setiap hari karena menggunakan DATE() */
$nomerantrian = null;
if ($pesanan['id_toko']) {
    $qa = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_toko=? AND DATE(tanggal_order)=DATE(?) AND id_order<=? AND deleted=0");
    $tglpesanan = $pesanan['tanggal_order'];
    // bind_param "isi": integer, string, integer
    $qa->bind_param("isi", $pesanan['id_toko'], $tglpesanan, $idpesanan);
    $qa->execute();
    $nomerantrian = (int)$qa->get_result()->fetch_row()[0];
    $qa->close();
}

// cek apakah pembeli sudah memberi rating untuk pesanan ini
// jika sudah, tombol "Beri Rating" disembunyikan
$cr = $conn->prepare("SELECT id_rating FROM tb_rating WHERE id_order=? AND id_user=?");
$cr->bind_param("ii", $idpesanan, $idpengguna);
$cr->execute();
$sudahrating = $cr->get_result()->num_rows > 0;
$cr->close();

// hitung ulang subtotal dari detail item
// biaya layanan = total_harga - subtotal item (selisihnya adalah biaya layanan)
$subtotalitem = 0;
foreach ($detail as $dt) $subtotalitem += $dt['subtotal'];
$biayalayanan = $pesanan['total_harga'] - $subtotalitem;
$namatoko     = $pesanan['nama_toko'] ?? 'Kantin';
// format nomor pesanan: EK-000001 (6 digit dengan nol di depan)
$nomerpesanan = 'EK-' . str_pad($idpesanan, 6, '0', STR_PAD_LEFT);

// fungsi bantu: kembalikan nama kelas CSS berdasarkan status pesanan
function kelasstatus(string $status): string {
    return match($status) {
        'Menunggu'    => 'menunggu',
        'Diproses'    => 'diproses',
        'Siap Diambil'=> 'siap',
        'Selesai'     => 'selesai',
        default       => 'dibatalkan',
    };
}

// fungsi bantu: kembalikan nama kelas ikon Font Awesome berdasarkan status
function ikonststatus(string $status): string {
    return match($status) {
        'Menunggu'     => 'fa-clock',
        'Diproses'     => 'fa-fire-burner',
        'Siap Diambil' => 'fa-bell',
        'Selesai'      => 'fa-circle-check',
        default        => 'fa-circle-xmark',
    };
}

$pathbase = '..';
// $cetak = true jika ada parameter ?cetak di URL — digunakan untuk menyembunyikan elemen saat cetak
$cetak = isset($_GET['cetak']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Struk <?= $nomerpesanan ?> - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<?php if ($cetak): ?>
<!-- style khusus cetak: sembunyikan navbar dan elemen non-struk -->
<style>
  .takprint { display: none !important; }
  .navbarpembeli { display: none !important; }
  body { padding-left: 0 !important; padding-bottom: 0 !important; background: white !important; }
  /* ukuran kertas kasir 80mm untuk cetak struk termal -->
  @page { size: 80mm auto; margin: 2mm 4mm; }
</style>
<?php endif; ?>
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkussempit">

  <!-- banner sukses muncul hanya saat baru saja membuat pesanan (?baru=1) -->
  <?php if (isset($_GET['baru'])): ?>
  <div class="bannerberhasil takprint">
    <div class="ikonberhasil"><i class="fa-solid fa-circle-check"></i></div>
    <h2>Pesanan Berhasil!</h2>
    <p>Penjual sedang menyiapkan pesananmu</p>
  </div>
  <?php else: ?>
  <!-- judul halaman biasa jika membuka struk dari riwayat -->
  <div class="judulhalaman takprint">
    <h1><i class="fa-solid fa-receipt"></i> Struk Pesanan</h1>
    <p><?= $nomerpesanan ?></p>
  </div>
  <?php endif; ?>

  <!-- nomor antrian harian — tampil jika berhasil dihitung -->
  <?php if ($nomerantrian): ?>
  <div class="antirian">
    <!-- str_pad: format nomor antrian menjadi 3 digit, misal 007 -->
    <div class="angkaantrian"><?= str_pad($nomerantrian, 3, '0', STR_PAD_LEFT) ?></div>
    <div class="labelantrian">
      Nomor Antrian di <?= htmlspecialchars($namatoko) ?> &mdash; <?= date('d M Y', strtotime($pesanan['tanggal_order'])) ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- konten struk digital -->
  <div class="bungkusstruk">

    <!-- kepala struk: nama aplikasi dan keterangan -->
    <div class="kepalastruk">
      <div class="ikonstruk"><i class="fa-solid fa-receipt"></i></div>
      <h2>jajankita &mdash; Struk Digital</h2>
      <p>Simpan sebagai bukti pembayaran</p>
    </div>

    <div class="isistruk">

      <!-- informasi umum pesanan -->
      <div class="barisstruk">
        <span class="labelstruk">No. Pesanan</span>
        <!-- font monospace agar nomor mudah dibaca -->
        <span class="nilaistruk" style="font-family:monospace;color:var(--utama);"><?= $nomerpesanan ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Kantin</span>
        <span class="nilaistruk"><i class="fa-solid fa-store"></i> <?= htmlspecialchars($namatoko) ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Tanggal</span>
        <!-- format: 05 Jan 2025, 14:30 -->
        <span class="nilaistruk"><?= date('d M Y, H:i', strtotime($pesanan['tanggal_order'])) ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Pembeli</span>
        <!-- ambil nama dari session, bukan dari database (lebih cepat) -->
        <span class="nilaistruk"><?= htmlspecialchars($_SESSION['username']) ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Metode Bayar</span>
        <span class="nilaistruk"><i class="fa-solid fa-wallet"></i> <?= htmlspecialchars($pesanan['metode_pembayaran']) ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Status</span>
        <!-- badge berwarna sesuai status pesanan -->
        <span class="badge <?= kelasstatus($pesanan['status_order']) ?>">
          <i class="fa-solid <?= ikonststatus($pesanan['status_order']) ?>"></i>
          <?= htmlspecialchars($pesanan['status_order']) ?>
        </span>
      </div>
      <!-- tampilkan catatan jika ada -->
      <?php if (!empty($pesanan['catatan'])): ?>
      <div class="barisstruk">
        <span class="labelstruk">Catatan</span>
        <span class="nilaistruk"><?= htmlspecialchars($pesanan['catatan']) ?></span>
      </div>
      <?php endif; ?>

      <!-- garis pemisah dekoratif antar bagian struk -->
      <div class="pemisah"><hr><span><i class="fa-solid fa-star"></i></span><hr></div>

      <!-- daftar item yang dipesan -->
      <div class="judulbagian" style="margin:0 0 12px;"><i class="fa-solid fa-list"></i> Detail Pesanan</div>
      <?php foreach ($detail as $item): ?>
      <div class="itemdetailstruk">
        <div class="namamenu"><?= htmlspecialchars($item['nama_menu']) ?></div>
        <div class="barisnilai">
          <!-- tampilkan qty @ harga satuan dan subtotal -->
          <span><?= $item['jumlah'] ?>x @ Rp <?= number_format($item['harga_satuan'],0,',','.') ?></span>
          <b>Rp <?= number_format($item['subtotal'],0,',','.') ?></b>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="pemisah"><hr><span><i class="fa-solid fa-star"></i></span><hr></div>

      <!-- ringkasan biaya -->
      <div class="barisstruk">
        <span class="labelstruk">Subtotal</span>
        <span class="nilaistruk">Rp <?= number_format($subtotalitem,0,',','.') ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Biaya Layanan</span>
        <!-- max(0,...) memastikan biaya layanan tidak tampil negatif -->
        <span class="nilaistruk">Rp <?= number_format(max(0,$biayalayanan),0,',','.') ?></span>
      </div>
      <!-- total yang harus dibayar -->
      <div class="totalstruk">
        <span class="label"><i class="fa-solid fa-coins"></i> Total Bayar</span>
        <span class="nilai">Rp <?= number_format($pesanan['total_harga'],0,',','.') ?></span>
      </div>

    </div>

    <!-- kaki struk: ucapan terima kasih -->
    <div class="kakistruk">
      <i class="fa-solid fa-heart"></i>
      <p>Terima kasih telah memesan di jajankita!</p>
      <p style="margin-top:4px;font-size:11px;">
        <?= $nomerpesanan ?> &nbsp;|&nbsp; <?= date('d/m/Y H:i', strtotime($pesanan['tanggal_order'])) ?>
      </p>
    </div>

  </div>

  <!-- tombol cetak struk — hanya muncul jika pesanan sudah selesai -->
  <?php if ($pesanan['status_order'] === 'Selesai'): ?>
  <!-- takprint: disembunyikan saat mode cetak agar tidak ikut tercetak -->
  <div class="takprint" style="margin-bottom:10px;">
    <!-- window.print() memicu dialog cetak browser -->
    <button onclick="window.print()" class="tombolutama blok">
      <i class="fa-solid fa-print"></i> Cetak Struk
    </button>
  </div>
  <?php endif; ?>

  <!-- tombol beri rating — hanya muncul jika selesai dan belum pernah memberi rating -->
  <?php if ($pesanan['status_order'] === 'Selesai' && !$sudahrating): ?>
  <a href="rating.php?id_order=<?= $idpesanan ?>" class="tombolutama blok takprint" style="margin-bottom:10px;">
    <i class="fa-solid fa-star"></i> Beri Rating dan Ulasan
  </a>
  <?php endif; ?>

  <!-- tombol kembali ke beranda -->
  <a href="../index/index.php" class="tombolringan blok takprint" style="margin-bottom:28px;">
    <i class="fa-solid fa-house"></i> Kembali ke Beranda
  </a>

</div>

</body>
</html>
