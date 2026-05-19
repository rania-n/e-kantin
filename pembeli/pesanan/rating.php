<?php
/* ============================================================
   HALAMAN BERI RATING
   Rating via form POST, bintang pilih via JS minimal (toggle class).
   ============================================================ */
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

$idpesanan  = (int)($_GET['id_order'] ?? 0);
$idpengguna = (int)$_SESSION['id_user'];

if (!$idpesanan) { header("Location: pesanan.php"); exit; }

$q = $conn->prepare("SELECT o.*,t.nama_toko FROM tb_order o LEFT JOIN tb_toko t ON o.id_toko=t.id_toko WHERE o.id_order=? AND o.id_user=? AND o.deleted=0");
$q->bind_param("ii", $idpesanan, $idpengguna);
$q->execute();
$pesanan = $q->get_result()->fetch_assoc();
$q->close();

if (!$pesanan) { header("Location: pesanan.php"); exit; }
if (!in_array($pesanan['status_order'], ['Selesai','Siap Diambil'])) { header("Location: pesanan.php?tab=riwayat"); exit; }

// Cek duplikat
$cr = $conn->prepare("SELECT id_rating FROM tb_rating WHERE id_order=? AND id_user=?");
$cr->bind_param("ii", $idpesanan, $idpengguna);
$cr->execute();
if ($cr->get_result()->num_rows > 0) { header("Location: struk.php?id_order=$idpesanan"); exit; }
$cr->close();

// Ambil item pesanan
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

  <div class="headerkembali">
    <a href="pesanan.php?tab=riwayat" class="tombolkembali"><i class="fa-solid fa-arrow-left"></i></a>
    <div class="teksheader">
      <h1>Beri Rating</h1>
      <p><?= $nomerpesanan ?></p>
    </div>
  </div>

  <!-- Info kantin -->
  <div class="heroprofil" style="margin-bottom:16px;">
    <div class="avatar" style="font-size:22px;"><i class="fa-solid fa-store"></i></div>
    <div class="namapengguna"><?= htmlspecialchars($namatoko) ?></div>
    <div class="emailpengguna"><?= $nomerpesanan ?> &middot; <?= date('d M Y', strtotime($pesanan['tanggal_order'])) ?></div>
  </div>

  <form method="POST" action="prosesrating.php">
    <input type="hidden" name="id_order" value="<?= $idpesanan ?>">

    <!-- Rating bintang — CSS radio trick (reverse DOM order + flex row-reverse) -->
    <div class="kartu" style="text-align:center;padding:22px 16px;">
      <div style="font-size:15px;font-weight:700;margin-bottom:4px;">Bagaimana pengalamanmu?</div>
      <div style="font-size:12px;color:var(--tekssamar);margin-bottom:14px;">Pilih bintang (wajib)</div>

      <div class="bintang-pilih">
        <!-- Urutan terbalik di DOM agar CSS ~ bekerja dengan benar -->
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

    <!-- Ulasan teks -->
    <div class="kartu">
      <h3>Tulis Ulasan</h3>
      <div class="kelompokform">
        <textarea name="ulasan" rows="3" placeholder="Ceritakan pengalamanmu..."></textarea>
      </div>

      <!-- Tag cepat — CSS checkbox, nilai dikirim via name="tag[]" -->
      <div style="font-size:12px;color:var(--tekssamar);margin-bottom:8px;">Pilih yang sesuai:</div>
      <div class="daftartag">
        <?php foreach (['Enak Banget','Pelayanan Cepat','Worth It','Porsi Besar','Penjual Ramah','Pedas Pas'] as $tag): ?>
        <input type="checkbox" name="tag[]" id="tag-<?= md5($tag) ?>"
               value="<?= htmlspecialchars($tag) ?>" class="tag-input">
        <label for="tag-<?= md5($tag) ?>" class="tagchip"><?= $tag ?></label>
        <?php endforeach; ?>
      </div>
    </div>

    <button type="submit" class="tombolutama blok" style="padding:14px;font-size:15px;">
      <i class="fa-solid fa-paper-plane"></i> Kirim Rating
    </button>

  </form>

  <div style="height:24px;"></div>
</div>

<style>
/* ===== CSS Star Rating (tanpa JS) ===== */
.bintang-pilih {
  display: flex;
  flex-direction: row-reverse;
  justify-content: center;
  gap: 2px;
}
.bintang-pilih input[type="radio"] { display: none; }
.bintang-pilih label {
  font-size: 38px;
  color: #D1D5DB;
  cursor: pointer;
  line-height: 1;
  padding: 2px;
}
/* Bintang yang diceklis dan semua setelahnya (nilai lebih rendah) = emas */
.bintang-pilih input:checked ~ label,
.bintang-pilih label:hover,
.bintang-pilih label:hover ~ label {
  color: #F59E0B;
}

/* ===== CSS Tag Checkbox ===== */
.tag-input { display: none; }
.tag-input:checked + .tagchip {
  background: var(--utama);
  color: var(--putihbg);
  border-color: var(--utama);
}
</style>
</body>
</html>
