<?php
/* ============================================================
   STRUK PESANAN PENJUAL
   Tampilan dalam layout penjual (ada sidebar/navbar).
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idtoko    = (int)$_SESSION['id_toko'];
$idpesanan = (int)($_GET['id'] ?? 0);
$halamansaatini = 'manajemenpesanan';

if (!$idpesanan) { header("Location: manajemenpesanan.php"); exit; }

$q = $conn->prepare("SELECT o.*,u.username FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user WHERE o.id_order=? AND o.id_toko=? AND o.deleted=0");
$q->bind_param("ii", $idpesanan, $idtoko); $q->execute();
$pesanan = $q->get_result()->fetch_assoc(); $q->close();

if (!$pesanan) { header("Location: manajemenpesanan.php"); exit; }

$qd = $conn->prepare("SELECT d.*,m.nama_menu FROM tb_detail_order d JOIN tb_menu m ON d.id_menu=m.id_menu WHERE d.id_order=? AND d.deleted=0");
$qd->bind_param("i", $idpesanan); $qd->execute();
$items = $qd->get_result()->fetch_all(MYSQLI_ASSOC); $qd->close();

// Nomor antrian harian per toko
$qa = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_toko=? AND DATE(tanggal_order)=DATE(?) AND id_order<=? AND deleted=0");
$tglpesanan = $pesanan['tanggal_order'];
$qa->bind_param("isi", $idtoko, $tglpesanan, $idpesanan); $qa->execute();
$antrian = (int)$qa->get_result()->fetch_row()[0]; $qa->close();

$subtotalitem = array_sum(array_column($items, 'subtotal'));
$biayalayanan = $pesanan['total_harga'] - $subtotalitem;
$nomerpesanan = 'EK-' . str_pad($idpesanan, 6, '0', STR_PAD_LEFT);
$namatoko     = htmlspecialchars($_SESSION['nama_toko'] ?? 'eKantin');

function kelasstatus(string $s): string {
    return match($s) {
        'Menunggu' => 'menunggu', 'Diproses' => 'diproses',
        'Siap Diambil' => 'siap', 'Selesai' => 'selesai',
        default => 'dibatalkan',
    };
}
function ikonStatus(string $s): string {
    return match($s) {
        'Menunggu'     => 'fa-clock',
        'Diproses'     => 'fa-fire-burner',
        'Siap Diambil' => 'fa-bell',
        'Selesai'      => 'fa-circle-check',
        default        => 'fa-circle-xmark',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Struk <?= $nomerpesanan ?> - eKantin</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== Area struk (terpusat, maks 440px) ===== */
.area-struk {
  max-width: 440px;
  margin: 0 auto;
}
/* Antrian */
.kotak-antrian {
  background: var(--utama);
  color: var(--putihbg);
  border-radius: 14px;
  padding: 16px;
  text-align: center;
  margin-bottom: 14px;
}
.angka-antrian {
  font-size: 56px;
  font-weight: 900;
  line-height: 1;
  letter-spacing: -2px;
}
.label-antrian { font-size: 12px; opacity: .8; margin-top: 6px; }

/* Kartu struk */
.kartu-struk {
  background: var(--putih);
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid var(--garis);
  box-shadow: var(--bayang);
  margin-bottom: 14px;
}
.kepala-struk {
  background: var(--utama);
  color: var(--putihbg);
  padding: 18px 20px 14px;
  text-align: center;
}
.kepala-struk .ikon { font-size: 26px; margin-bottom: 6px; }
.kepala-struk h2 { font-size: 14px; font-weight: 800; }
.kepala-struk p  { font-size: 11px; opacity: .8; margin-top: 2px; }

.isi-struk { padding: 14px 18px; }
.baris-struk {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
  padding: 7px 0;
  border-bottom: 1px solid var(--latar);
  font-size: 13px;
}
.baris-struk:last-child { border-bottom: none; }
.label-struk { color: var(--tekssamar); min-width: 100px; flex-shrink: 0; }
.nilai-struk  { font-weight: 600; text-align: right; word-break: break-all; }

.pemisah-struk {
  display: flex; align-items: center; gap: 8px;
  margin: 8px 0; color: var(--garis); font-size: 11px;
}
.pemisah-struk hr { flex: 1; border: none; border-top: 1px dashed var(--garis); }

.item-detail {
  padding: 7px 0;
  border-bottom: 1px solid var(--latar);
}
.item-detail:last-child { border-bottom: none; }
.item-detail .nama { font-size: 13px; font-weight: 700; margin-bottom: 2px; }
.item-detail .sub  { display: flex; justify-content: space-between; font-size: 12px; color: var(--tekssamar); }
.item-detail .sub b { color: var(--teks); }

.total-struk {
  display: flex; justify-content: space-between; align-items: center;
  background: var(--latar);
  padding: 12px 18px;
  border-top: 2px solid var(--garis);
  font-weight: 800;
}
.total-struk .label { font-size: 13px; color: var(--tekssamar); }
.total-struk .nilai { font-size: 18px; color: var(--utama); }

.kaki-struk {
  text-align: center;
  padding: 12px 18px;
  border-top: 1px solid var(--latar);
  font-size: 11px;
  color: var(--tekssamar);
}

/* ===== PRINT ===== */
@media print {
  .sidebar, .mobile-header, .togel-input,
  .overlay-sidebar, .grup-tombol-struk { display: none !important; }
  body { background: white; }
  .konten { margin-left: 0 !important; padding: 0 !important; }
  .area-struk { max-width: 80mm; margin: 0; }
  .kotak-antrian {
    background: white !important; color: black !important;
    border: 2px dashed black; border-radius: 4px; padding: 8px; margin-bottom: 6px;
  }
  .angka-antrian { font-size: 40px !important; color: black !important; }
  .kepala-struk { background: white !important; color: black !important; border-bottom: 1px solid #ddd; padding: 8px 10px; }
  .kepala-struk .ikon { font-size: 16px !important; }
  .kepala-struk h2 { font-size: 12px !important; }
  .isi-struk { padding: 6px 10px; }
  .baris-struk { padding: 3px 0; font-size: 11px; }
  .item-detail { padding: 3px 0; }
  .item-detail .nama { font-size: 11px; }
  .item-detail .sub  { font-size: 10px; }
  .total-struk { padding: 6px 10px; }
  .total-struk .nilai { font-size: 14px; }
  .kaki-struk { padding: 6px 10px; font-size: 10px; }
}
</style>
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten">

  <div class="header-halaman takprint">
    <div class="kiri">
      <h1><i class="fa-solid fa-receipt"></i> Struk Pesanan</h1>
      <p><?= $nomerpesanan ?></p>
    </div>
    <a href="manajemenpesanan.php" class="tombolringan">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="area-struk">

    <!-- ANTRIAN -->
    <div class="kotak-antrian">
      <div style="font-size:11px;margin-bottom:4px;opacity:.8;">NOMOR ANTRIAN HARI INI</div>
      <div class="angka-antrian"><?= str_pad($antrian, 3, '0', STR_PAD_LEFT) ?></div>
      <div class="label-antrian"><?= $namatoko ?> &mdash; <?= date('d M Y', strtotime($pesanan['tanggal_order'])) ?></div>
    </div>

    <!-- STRUK -->
    <div class="kartu-struk">

      <div class="kepala-struk">
        <div class="ikon"><i class="fa-solid fa-receipt"></i></div>
        <h2><?= $namatoko ?> &mdash; Struk Pesanan</h2>
        <p>e-Kantin Sekolah</p>
      </div>

      <div class="isi-struk">
        <div class="baris-struk">
          <span class="label-struk">No. Pesanan</span>
          <span class="nilai-struk" style="font-family:monospace;color:var(--utama);"><?= $nomerpesanan ?></span>
        </div>
        <div class="baris-struk">
          <span class="label-struk">Tanggal</span>
          <span class="nilai-struk"><?= date('d M Y, H:i', strtotime($pesanan['tanggal_order'])) ?></span>
        </div>
        <div class="baris-struk">
          <span class="label-struk">Pembeli</span>
          <span class="nilai-struk"><?= htmlspecialchars($pesanan['username']) ?></span>
        </div>
        <div class="baris-struk">
          <span class="label-struk">Metode Bayar</span>
          <span class="nilai-struk"><?= htmlspecialchars($pesanan['metode_pembayaran']) ?></span>
        </div>
        <div class="baris-struk">
          <span class="label-struk">Status</span>
          <span class="nilai-struk">
            <span class="badge <?= kelasstatus($pesanan['status_order']) ?>">
              <i class="fa-solid <?= ikonStatus($pesanan['status_order']) ?>"></i>
              <?= htmlspecialchars($pesanan['status_order']) ?>
            </span>
          </span>
        </div>
        <?php if (!empty($pesanan['catatan'])): ?>
        <div class="baris-struk">
          <span class="label-struk">Catatan</span>
          <span class="nilai-struk"><?= htmlspecialchars($pesanan['catatan']) ?></span>
        </div>
        <?php endif; ?>

        <div class="pemisah-struk"><hr><span><i class="fa-solid fa-utensils"></i></span><hr></div>

        <div style="font-size:11px;font-weight:700;color:var(--tekssamar);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">
          Detail Pesanan
        </div>

        <?php foreach ($items as $it): ?>
        <div class="item-detail">
          <div class="nama"><?= htmlspecialchars($it['nama_menu']) ?></div>
          <div class="sub">
            <span><?= $it['jumlah'] ?>x @ Rp <?= number_format($it['harga_satuan'],0,',','.') ?></span>
            <b>Rp <?= number_format($it['subtotal'],0,',','.') ?></b>
          </div>
        </div>
        <?php endforeach; ?>

        <div class="pemisah-struk"><hr><span><i class="fa-solid fa-star"></i></span><hr></div>

        <div class="baris-struk">
          <span class="label-struk">Subtotal</span>
          <span class="nilai-struk">Rp <?= number_format($subtotalitem,0,',','.') ?></span>
        </div>
        <div class="baris-struk">
          <span class="label-struk">Biaya Layanan</span>
          <span class="nilai-struk">Rp <?= number_format(max(0,$biayalayanan),0,',','.') ?></span>
        </div>
      </div>

      <div class="total-struk">
        <span class="label"><i class="fa-solid fa-coins"></i> Total Bayar</span>
        <span class="nilai">Rp <?= number_format($pesanan['total_harga'],0,',','.') ?></span>
      </div>

      <div class="kaki-struk">
        <i class="fa-solid fa-heart" style="color:var(--utama);"></i>
        <p style="margin-top:4px;">Terima kasih sudah memesan!</p>
        <p style="margin-top:2px;"><?= $nomerpesanan ?> &nbsp;|&nbsp; Dicetak: <?= date('d/m/Y H:i') ?></p>
      </div>

    </div>

    <!-- Tombol aksi (tidak ikut cetak) -->
    <div class="grup-tombol-struk takprint" style="display:flex;gap:10px;margin-bottom:20px;">
      <button class="tombolutama" style="flex:1;" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Cetak Struk
      </button>
      <a href="manajemenpesanan.php" class="tombolringan" style="flex:1;">
        <i class="fa-solid fa-arrow-left"></i> Kembali
      </a>
    </div>

  </div>

</main>

</body>
</html>
