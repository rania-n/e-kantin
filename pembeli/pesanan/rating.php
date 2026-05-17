<?php
/* ============================================================
   HALAMAN BERI RATING
   Rating via form POST, bintang pilih via JS minimal (toggle class).
   ============================================================ */
include '../../3. komponen/guard.php';
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
<title>Beri Rating - eKantin</title>
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
    <input type="hidden" name="rating_toko" id="inputnilai" value="0">

    <!-- Rating bintang utama -->
    <div class="kartu" style="text-align:center;padding:22px 16px;">
      <div style="font-size:15px;font-weight:700;margin-bottom:4px;">Bagaimana pengalamanmu?</div>
      <div style="font-size:12px;color:var(--tekssamar);margin-bottom:4px;">Klik bintang untuk memberi nilai</div>

      <div class="bintangbesar" id="bintangutama">
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <button type="button" class="tombolbintang" data-nilai="<?= $i ?>" onclick="aturNilai(<?= $i ?>)">
          <i class="fa-solid fa-star"></i>
        </button>
        <?php endfor; ?>
      </div>
      <div class="keteranganbintang" id="keterangannilai">Pilih bintang di atas</div>
    </div>

    <!-- Ulasan teks -->
    <div class="kartu">
      <h3>Tulis Ulasan</h3>
      <div class="kelompokform">
        <textarea name="ulasan" id="isifulasan" rows="3"
                  placeholder="Ceritakan pengalamanmu..."></textarea>
      </div>

      <!-- Tag cepat -->
      <div style="font-size:12px;color:var(--tekssamar);margin-bottom:8px;">Pilih yang sesuai:</div>
      <div class="daftartag" id="daftartag">
        <span class="tagchip" onclick="toggleTag(this)">Enak Banget</span>
        <span class="tagchip" onclick="toggleTag(this)">Pelayanan Cepat</span>
        <span class="tagchip" onclick="toggleTag(this)">Worth It</span>
        <span class="tagchip" onclick="toggleTag(this)">Porsi Besar</span>
        <span class="tagchip" onclick="toggleTag(this)">Penjual Ramah</span>
        <span class="tagchip" onclick="toggleTag(this)">Pedas Pas</span>
      </div>
      <!-- Input tersembunyi untuk tag -->
      <input type="hidden" name="tag_terpilih" id="inputtag" value="">
    </div>

    <!-- Rating per menu -->
    <?php if (count($daftaritem) > 0): ?>
    <div class="kartu">
      <h3>Rating per Menu</h3>
      <?php foreach ($daftaritem as $item): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--latar);">
        <div style="flex:1;">
          <div style="font-size:14px;font-weight:700;"><?= htmlspecialchars($item['nama_menu']) ?></div>
          <div style="font-size:12px;color:var(--tekssamar);"><?= $item['jumlah'] ?> porsi</div>
          <div class="bintangkecil" id="bkecil-<?= $item['id_menu'] ?>">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <button type="button" class="tombolbintang" onclick="aturNilaiMenu(<?= $item['id_menu'] ?>, <?= $i ?>)">
              <i class="fa-solid fa-star"></i>
            </button>
            <?php endfor; ?>
          </div>
          <input type="hidden" name="nilai_menu[<?= $item['id_menu'] ?>]" id="nilaimenu-<?= $item['id_menu'] ?>" value="0">
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <button type="submit" class="tombolutama blok" style="padding:14px;font-size:15px;"
            onclick="return validasiRating()">
      <i class="fa-solid fa-paper-plane"></i> Kirim Rating
    </button>

  </form>

  <div style="height:24px;"></div>
</div>

<style>
.tombolbintang { color: #D1D5DB; }
.tombolbintang.aktif { color: #F59E0B; }
</style>
<script>
var nilaiterpilih = 0;
var keterangan = ['','Sangat Buruk','Kurang Memuaskan','Cukup','Bagus!','Luar Biasa!'];

function aturNilai(nilai) {
  nilaiterpilih = nilai;
  document.getElementById('inputnilai').value = nilai;
  var btns = document.querySelectorAll('#bintangutama .tombolbintang');
  btns.forEach(function(b, i) { b.classList.toggle('aktif', i < nilai); });
  document.getElementById('keterangannilai').textContent = keterangan[nilai] || '';
}

function aturNilaiMenu(idMenu, nilai) {
  document.getElementById('nilaimenu-' + idMenu).value = nilai;
  var btns = document.querySelectorAll('#bkecil-' + idMenu + ' .tombolbintang');
  btns.forEach(function(b, i) { b.classList.toggle('aktif', i < nilai); });
}

function toggleTag(el) {
  el.classList.toggle('dipilih');
  // Kumpulkan semua tag terpilih
  var dipilih = [];
  document.querySelectorAll('#daftartag .tagchip.dipilih').forEach(function(t) {
    dipilih.push(t.textContent.trim());
  });
  document.getElementById('inputtag').value = dipilih.join(', ');
}

function validasiRating() {
  if (nilaiterpilih === 0) {
    alert('Pilih rating bintang terlebih dahulu');
    return false;
  }
  // Gabungkan tag ke ulasan
  var tag = document.getElementById('inputtag').value;
  var ulasan = document.getElementById('isifulasan').value;
  if (tag && !ulasan) {
    document.getElementById('isifulasan').value = tag;
  } else if (tag && ulasan) {
    document.getElementById('isifulasan').value = ulasan + '\n' + tag;
  }
  return true;
}
</script>
</body>
</html>
