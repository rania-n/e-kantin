<?php
/* halaman beri rating dan ulasan
   pembeli memberikan bintang (1-5) untuk toko dan bisa menambahkan
   teks ulasan serta tag cepat (checkbox) setelah pesanan selesai
   bintang dipilih menggunakan CSS radio trick — tanpa JavaScript */

// guard memastikan hanya pembeli yang login yang bisa masuk
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

// ambil id pesanan dari URL
$idpesanan  = (int)($_GET['id_order'] ?? 0);
$idpengguna = (int)$_SESSION['id_user'];

// jika id pesanan tidak valid, kembali ke daftar pesanan
if (!$idpesanan) { header("Location: pesanan.php"); exit; }

// ambil data pesanan beserta nama toko
// cocokkan dengan id_user agar pembeli tidak bisa mengakses pesanan milik orang lain
$q = $conn->prepare("SELECT o.*,t.nama_toko FROM tb_order o LEFT JOIN tb_toko t ON o.id_toko=t.id_toko WHERE o.id_order=? AND o.id_user=? AND o.deleted=0");
$q->bind_param("ii", $idpesanan, $idpengguna);
$q->execute();
$pesanan = $q->get_result()->fetch_assoc();
$q->close();

// jika pesanan tidak ditemukan, redirect ke daftar pesanan
if (!$pesanan) { header("Location: pesanan.php"); exit; }
// rating hanya boleh diberikan jika pesanan sudah Selesai atau Siap Diambil
if (!in_array($pesanan['status_order'], ['Selesai','Siap Diambil'])) { header("Location: pesanan.php?tab=riwayat"); exit; }

// cek apakah pembeli sudah pernah memberi rating untuk pesanan ini
// jika sudah, alihkan ke struk — tidak boleh memberi rating dua kali
$cr = $conn->prepare("SELECT id_rating FROM tb_rating WHERE id_order=? AND id_user=?");
$cr->bind_param("ii", $idpesanan, $idpengguna);
$cr->execute();
if ($cr->get_result()->num_rows > 0) { header("Location: struk.php?id_order=$idpesanan"); exit; }
$cr->close();

// ambil daftar item pesanan (untuk ditampilkan sebagai konteks)
$qi = $conn->prepare("SELECT d.id_menu,d.jumlah,m.nama_menu FROM tb_detail_order d JOIN tb_menu m ON d.id_menu=m.id_menu WHERE d.id_order=? AND d.deleted=0");
$qi->bind_param("i", $idpesanan);
$qi->execute();
$daftaritem = $qi->get_result()->fetch_all(MYSQLI_ASSOC);
$qi->close();

$namatoko     = $pesanan['nama_toko'] ?? 'Kantin';
$nomerpesanan = 'EK-' . str_pad($idpesanan, 6, '0', STR_PAD_LEFT);

$pathbase = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beri Rating - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkussempit">

  <div class="judulhalaman">
    <h1><i class="fa-solid fa-star"></i> Beri Rating</h1>
    <p><?= $nomerpesanan ?></p>
  </div>

  <!-- tampilkan info kantin yang diberi rating -->
  <div class="heroprofil" style="margin-bottom:16px;">
    <div class="avatar" style="font-size:22px;"><i class="fa-solid fa-store"></i></div>
    <div class="namapengguna"><?= htmlspecialchars($namatoko) ?></div>
    <div class="emailpengguna"><?= $nomerpesanan ?> &middot; <?= date('d M Y', strtotime($pesanan['tanggal_order'])) ?></div>
  </div>

  <!-- form rating dikirim ke prosesrating.php via POST -->
  <form method="POST" action="prosesrating.php">
    <input type="hidden" name="id_order" value="<?= $idpesanan ?>">

    <!-- pemilih bintang menggunakan CSS radio trick
         teknik: urutan elemen di DOM dibalik (b5 paling atas, b1 paling bawah)
         lalu ditampilkan dari kanan ke kiri dengan flex-direction: row-reverse
         selector CSS "input:checked ~ label" mewarnai bintang yang dipilih dan semua sebelumnya -->
    <div class="kartu" style="text-align:center;padding:22px 16px;">
      <div style="font-size:15px;font-weight:700;margin-bottom:4px;">Bagaimana pengalamanmu?</div>
      <div style="font-size:12px;color:var(--tekssamar);margin-bottom:14px;">Pilih bintang (wajib)</div>

      <!-- .bintang-pilih menggunakan flex row-reverse agar b5 tampil paling kiri di layar -->
      <div class="bintang-pilih">
        <!-- urutan terbalik di DOM: b5 ditulis duluan, b1 ditulis terakhir -->
        <!-- required hanya di b5 agar form tidak bisa disubmit tanpa memilih bintang -->
        <input type="radio" name="rating_toko" id="b5" value="5" required>
        <label for="b5" title="Luar Biasa!"><i class="fa-solid fa-star"></i></label>
        <input type="radio" name="rating_toko" id="b4" value="4">
        <label for="b4" title="Bagus!"><i class="fa-solid fa-star"></i></label>
        <input type="radio" name="rating_toko" id="b3" value="3">
        <label for="b3" title="Cukup"><i class="fa-solid fa-star"></i></label>
        <input type="radio" name="rating_toko" id="b2" value="2">
        <label for="b2" title="Kurang"><i class="fa-solid fa-star"></i></label>
        <input type="radio" name="rating_toko" id="b1" value="1">
        <label for="b1" title="Sangat Buruk"><i class="fa-solid fa-star"></i></label>
      </div>
      <div style="font-size:11px;color:var(--tekssamar);margin-top:8px;">Arahkan ke bintang untuk melihat keterangan</div>
    </div>

    <!-- kotak ulasan teks dan pilihan tag cepat -->
    <div class="kartu">
      <h3>Tulis Ulasan</h3>
      <div class="kelompokform">
        <!-- textarea opsional untuk ulasan teks bebas -->
        <textarea name="ulasan" rows="3" placeholder="Ceritakan pengalamanmu..."></textarea>
      </div>

      <!-- tag cepat menggunakan CSS checkbox trick
           .tag-input disembunyikan (display:none), .tagchip terlihat sebagai tombol
           saat checkbox diceklis, CSS :checked + .tagchip mengubah tampilan chip -->
      <div style="font-size:12px;color:var(--tekssamar);margin-bottom:8px;">Pilih yang sesuai:</div>
      <div class="daftartag">
        <?php foreach (['Enak Banget','Pelayanan Cepat','Worth It','Porsi Besar','Penjual Ramah','Pedas Pas'] as $tag): ?>
        <!-- md5($tag) dipakai sebagai id unik untuk atribut for/id — agar label klik berfungsi -->
        <input type="checkbox" name="tag[]" id="tag-<?= md5($tag) ?>"
               value="<?= htmlspecialchars($tag) ?>" class="tag-input">
        <label for="tag-<?= md5($tag) ?>" class="tagchip"><?= $tag ?></label>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="display:flex;gap:10px;">
      <!-- tombol batal: kembali ke riwayat pesanan -->
      <a href="pesanan.php?tab=riwayat" class="tombolringan" style="padding:10px 14px;font-size:13px;">
        Batal
      </a>
      <!-- tombol kirim: submit form ke prosesrating.php -->
      <button type="submit" class="tombolutama" style="flex:2;padding:14px;font-size:15px;">
        <i class="fa-solid fa-paper-plane"></i> Kirim Rating
      </button>
    </div>

  </form>

  <div style="height:24px;"></div>
</div>

<style>
/* css star rating tanpa javascript
   menggunakan teknik flex row-reverse + selector ~ (sibling) */
.bintang-pilih {
  display: flex;
  /* row-reverse: elemen b5 yang ditulis pertama di DOM tampil paling kiri di layar */
  flex-direction: row-reverse;
  justify-content: center;
  gap: 2px;
}
/* sembunyikan radio button asli — hanya label bintang yang terlihat */
.bintang-pilih input[type="radio"] { display: none; }
.bintang-pilih label {
  font-size: 38px;
  color: #D1D5DB; /* warna abu default */
  cursor: pointer;
  line-height: 1;
  padding: 2px;
}
/* bintang yang diceklis dan semua label setelahnya (= nilai lebih rendah) berubah jadi emas
   ini bekerja karena urutan DOM dibalik: setelah b5 adalah b4, b3, b2, b1 */
.bintang-pilih input:checked ~ label,
.bintang-pilih label:hover,
.bintang-pilih label:hover ~ label {
  color: #F59E0B; /* warna emas */
}

/* css tag checkbox — sembunyikan input, style label sebagai chip */
.tag-input { display: none; }
/* saat checkbox diceklis, chip berubah warna menjadi warna utama */
.tag-input:checked + .tagchip {
  background: var(--utama);
  color: var(--putihbg);
  border-color: var(--utama);
}
</style>
</body>
</html>
