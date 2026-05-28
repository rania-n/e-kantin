<?php
/* halaman struk pesanan dari sisi penjual.
   tampilan identik dengan struk pembeli.
   bisa dicetak via tombol cetak (window.print()) */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

// ambil id toko dan id penjual dari session, id pesanan dari parameter URL
$idtoko    = (int)$_SESSION['id_toko'];
$idpenjual = (int)$_SESSION['id_user'];
$idpesanan = (int)($_GET['id'] ?? 0);

// tandai halaman aktif untuk navbar
$halamansaatini = 'manajemenpesanan';

// cek apakah parameter ?cetak ada di URL (mode cetak)
$cetak = isset($_GET['cetak']);

// jika tidak ada id pesanan, redirect ke halaman pesanan
if (!$idpesanan) { header("Location: manajemenpesanan.php"); exit; }

// ambil data pesanan beserta nama pembeli — pastikan pesanan milik penjual ini
$q = $conn->prepare("SELECT o.*,u.username FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user WHERE o.id_order=? AND o.id_penjual=? AND o.deleted=0");
$q->bind_param("ii", $idpesanan, $idpenjual); $q->execute();
$pesanan = $q->get_result()->fetch_assoc(); $q->close();

// jika pesanan tidak ditemukan, redirect ke halaman pesanan
if (!$pesanan) { header("Location: manajemenpesanan.php"); exit; }

// ambil semua item yang ada dalam pesanan ini
$qd = $conn->prepare("SELECT d.*,m.nama_menu FROM tb_detail_order d JOIN tb_menu m ON d.id_menu=m.id_menu WHERE d.id_order=? AND d.deleted=0");
$qd->bind_param("i", $idpesanan); $qd->execute();
$items = $qd->get_result()->fetch_all(MYSQLI_ASSOC); $qd->close();

/* hitung nomor antrian per PENJUAL (bukan per slot toko):
   berapa pesanan yang masuk ke penjual ini pada hari yang sama dengan tanggal
   pesanan ini, yang id_order-nya <= id pesanan ini.
   pakai id_penjual supaya penjual baru di slot bekas tidak mewarisi antrian lama. */
$qa = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_penjual=? AND DATE(tanggal_order)=DATE(?) AND id_order<=? AND deleted=0");
$tglpesanan = $pesanan['tanggal_order'];
$qa->bind_param("isi", $idpenjual, $tglpesanan, $idpesanan); $qa->execute();
$antrian = (int)$qa->get_result()->fetch_row()[0]; $qa->close();

// hitung subtotal semua item untuk ditampilkan di struk
$subtotalitem = array_sum(array_column($items, 'subtotal'));

// format nomor pesanan: EK-000001
$nomerpesanan = 'EK-' . str_pad($idpesanan, 6, '0', STR_PAD_LEFT);

// nama toko dari session, dengan fallback "jajankita" jika session kosong
$namatoko     = htmlspecialchars($_SESSION['nama_toko'] ?? 'jajankita');

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Struk <?= $nomerpesanan ?> - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  /* pastikan area konten menggunakan lebar penuh saat mencetak struk */
  .konten { padding: 16px; }
  .konten-sempit { max-width: none; }
</style>
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten">
<div class="konten-sempit">

  <!-- header halaman — kelas "takprint" menyembunyikan elemen ini saat dicetak -->
  <div class="header-halaman takprint" style="margin-bottom:16px;">
    <div class="kiri">
      <h1><i class="fa-solid fa-receipt"></i> Struk Pesanan</h1>
      <p><?= $nomerpesanan ?></p>
    </div>
  </div>

  <!-- nomor antrian ditampilkan besar di atas struk — format 3 digit dengan awalan nol -->
  <div class="antirian">
    <div class="angkaantrian"><?= str_pad($antrian, 3, '0', STR_PAD_LEFT) ?></div>
    <div class="labelantrian">
      Nomor Antrian di <?= $namatoko ?> &mdash; <?= date('d M Y', strtotime($pesanan['tanggal_order'])) ?>
    </div>
  </div>

  <!-- kotak struk utama -->
  <div class="bungkusstruk">

    <!-- kepala struk: ikon, judul, dan deskripsi singkat -->
    <div class="kepalastruk">
      <div class="ikonstruk"><i class="fa-solid fa-receipt"></i></div>
      <h2>jajankita &mdash; Struk Digital</h2>
      <p>Simpan sebagai bukti pembayaran</p>
    </div>

    <!-- isi struk: info pesanan dan detail item -->
    <div class="isistruk" style="padding-bottom:0;">

      <!-- baris informasi pesanan: nomor, nama toko, tanggal, pembeli, metode bayar, status -->
      <div class="barisstruk">
        <span class="labelstruk">No. Pesanan</span>
        <span class="nilaistruk" style="font-family:monospace;color:var(--utama);"><?= $nomerpesanan ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Kantin</span>
        <span class="nilaistruk"><i class="fa-solid fa-store"></i> <?= $namatoko ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Tanggal</span>
        <span class="nilaistruk"><?= date('d M Y, H:i', strtotime($pesanan['tanggal_order'])) ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Pembeli</span>
        <span class="nilaistruk"><?= htmlspecialchars($pesanan['username']) ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Metode Bayar</span>
        <span class="nilaistruk"><i class="fa-solid fa-wallet"></i> <?= htmlspecialchars($pesanan['metode_pembayaran']) ?></span>
      </div>
      <!-- catatan pesanan hanya ditampilkan jika ada -->
      <?php if (!empty($pesanan['catatan'])): ?>
      <div class="barisstruk">
        <span class="labelstruk">Catatan</span>
        <span class="nilaistruk"><?= htmlspecialchars($pesanan['catatan']) ?></span>
      </div>
      <?php endif; ?>

      <!-- pemisah dekoratif antara info pesanan dan detail item -->
      <div class="pemisah"><hr><span><i class="fa-solid fa-star"></i></span><hr></div>

      <div class="judulbagian" style="margin:0 0 12px;"><i class="fa-solid fa-list"></i> Detail Pesanan</div>

      <!-- daftar setiap item: nama menu, jumlah, harga satuan, dan subtotal -->
      <?php foreach ($items as $it): ?>
      <div class="itemdetailstruk">
        <div class="namamenu"><?= htmlspecialchars($it['nama_menu']) ?></div>
        <div class="barisnilai">
          <span><?= $it['jumlah'] ?>x @ Rp <?= number_format($it['harga_satuan'],0,',','.') ?></span>
          <b>Rp <?= number_format($it['subtotal'],0,',','.') ?></b>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- pemisah sebelum ringkasan harga -->
      <div class="pemisah"><hr><span><i class="fa-solid fa-star"></i></span><hr></div>

      <!-- ringkasan harga: hanya subtotal, tidak ada biaya tambahan -->
      <div class="barisstruk">
        <span class="labelstruk">Subtotal</span>
        <span class="nilaistruk">Rp <?= number_format($subtotalitem,0,',','.') ?></span>
      </div>

    </div><!-- tutup isistruk -->

    <!-- baris total bayar yang lebih besar dan menonjol -->
    <div class="totalstruk">
      <span class="label"><i class="fa-solid fa-coins"></i> Total Bayar</span>
      <span class="nilai">Rp <?= number_format($pesanan['total_harga'],0,',','.') ?></span>
    </div>

    <!-- kaki struk: pesan terima kasih dan info singkat pesanan -->
    <div class="kakistruk">
      <i class="fa-solid fa-heart"></i>
      <p>Terima kasih telah memesan di jajankita!</p>
      <p style="margin-top:4px;font-size:11px;">
        <?= $nomerpesanan ?> &nbsp;|&nbsp; <?= date('d/m/Y H:i', strtotime($pesanan['tanggal_order'])) ?>
      </p>
    </div>

  </div>

  <!-- tombol cetak dan kembali — disembunyikan saat mencetak -->
  <div class="takprint" style="margin-top:4px;">
    <button onclick="window.print()" class="tombolutama blok">
      <i class="fa-solid fa-print"></i> Cetak Struk
    </button>
    <a href="manajemenpesanan.php" class="tombolringan blok" style="margin-top:10px;justify-content:center;">
      <i class="fa-solid fa-clipboard-list"></i> Kembali ke Pesanan
    </a>
  </div>


</div>
</main>

</body>
</html>
