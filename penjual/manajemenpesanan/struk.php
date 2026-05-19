<?php
/* ============================================================
   CETAK STRUK PESANAN (PENJUAL)
   Tampilan mirip struk pembeli, compact saat dicetak.
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idtoko    = (int)$_SESSION['id_toko'];
$idpesanan = (int)($_GET['id'] ?? 0);

if (!$idpesanan) { header("Location: manajemenpesanan.php"); exit; }

$q = $conn->prepare("SELECT o.*,u.username,u.email FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user WHERE o.id_order=? AND o.id_toko=? AND o.deleted=0");
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
<title>Struk <?= $nomerpesanan ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== VARIABEL ===== */
:root {
  --utama:    #643843;
  --kedua:    #99627A;
  --latar:    #EFD9D4;
  --putihbg:  #F8EBF1;
  --putih:    #FFFFFF;
  --teks:     #643843;
  --tekssamar:#99627A;
  --garis:    #E7CBCB;
  --sukses:   #16A34A;
  --suksesbg: #DCFCE7;
  --tunggu:   #F59E0B;
  --tunggubg: #FEF3C7;
  --gagal:    #DC2626;
  --gagalbg:  #FEE2E2;
  --info:     #2563EB;
  --infobg:   #DBEAFE;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: 'Segoe UI', system-ui, sans-serif;
  background: var(--latar);
  color: var(--teks);
  min-height: 100vh;
  display: flex;
  justify-content: center;
  padding: 20px 16px 40px;
}
.bungkus {
  width: 100%;
  max-width: 440px;
}
/* Header kembali */
.headerkembali {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}
.tombolkembali {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: var(--putih);
  border: 1.5px solid var(--garis);
  display: flex; align-items: center; justify-content: center;
  color: var(--utama);
  font-size: 14px;
  text-decoration: none;
  flex-shrink: 0;
}
.teksheader h1 { font-size: 17px; font-weight: 800; color: var(--utama); }
.teksheader p  { font-size: 12px; color: var(--tekssamar); }

/* Antrian */
.antirian {
  background: var(--utama);
  color: var(--putihbg);
  border-radius: 14px;
  padding: 16px;
  text-align: center;
  margin-bottom: 14px;
}
.angkaantrian {
  font-size: 56px;
  font-weight: 900;
  line-height: 1;
  letter-spacing: -2px;
}
.labelantrian { font-size: 12px; opacity: .8; margin-top: 6px; }

/* Kartu struk */
.bungkusstruk {
  background: var(--putih);
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid var(--garis);
  box-shadow: 0 2px 12px rgba(100,56,67,.08);
  margin-bottom: 14px;
}
.kepalastruk {
  background: var(--utama);
  color: var(--putihbg);
  padding: 20px 20px 16px;
  text-align: center;
}
.ikonstruk {
  font-size: 28px;
  margin-bottom: 8px;
}
.kepalastruk h2 { font-size: 15px; font-weight: 800; }
.kepalastruk p  { font-size: 11px; opacity: .8; margin-top: 3px; }

.isistruk { padding: 16px 20px; }

.barisstruk {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
  padding: 8px 0;
  border-bottom: 1px solid var(--latar);
  font-size: 13px;
}
.barisstruk:last-child { border-bottom: none; }
.labelstruk { color: var(--tekssamar); min-width: 100px; flex-shrink: 0; }
.nilaistruk { font-weight: 600; text-align: right; word-break: break-all; }

.pemisah {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 10px 0;
  color: var(--garis);
  font-size: 11px;
}
.pemisah hr { flex: 1; border: none; border-top: 1px dashed var(--garis); }

.itemdetailstruk {
  padding: 8px 0;
  border-bottom: 1px solid var(--latar);
}
.itemdetailstruk:last-child { border-bottom: none; }
.itemdetailstruk .namamenu { font-size: 13px; font-weight: 700; margin-bottom: 3px; }
.itemdetailstruk .barisnilai {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: var(--tekssamar);
}
.itemdetailstruk .barisnilai b { color: var(--teks); }

.totalstruk {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--latar);
  padding: 12px 20px;
  border-top: 2px solid var(--garis);
  font-weight: 800;
}
.totalstruk .label { font-size: 13px; color: var(--tekssamar); }
.totalstruk .nilai { font-size: 18px; color: var(--utama); }

.kakistruk {
  text-align: center;
  padding: 14px 20px;
  border-top: 1px solid var(--latar);
  font-size: 11px;
  color: var(--tekssamar);
}

/* Badge status */
.badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700;
}
.badge.menunggu  { background: var(--tunggubg); color: var(--tunggu); }
.badge.diproses  { background: var(--infobg);   color: var(--info);   }
.badge.siap      { background: var(--suksesbg); color: var(--sukses); }
.badge.selesai   { background: var(--latar);    color: var(--tekssamar); }
.badge.dibatalkan{ background: var(--gagalbg);  color: var(--gagal); }

/* Tombol aksi */
.grup-tombol {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}
.tombol-aksi {
  flex: 1;
  padding: 12px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 700;
  border: 1.5px solid var(--garis);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  text-decoration: none;
  font-family: inherit;
}
.tombol-cetak  { background: var(--utama); color: var(--putihbg); border-color: var(--utama); }
.tombol-kembali{ background: var(--putih); color: var(--utama); }

/* ===== PRINT ===== */
@media print {
  body {
    background: white;
    padding: 0;
    display: block;
  }
  .bungkus {
    max-width: 100%;
    width: 80mm; /* lebar thermal standard */
    margin: 0;
    padding: 0;
  }
  .headerkembali,
  .grup-tombol { display: none !important; }
  .antirian {
    background: white !important;
    color: black !important;
    border: 2px dashed black;
    border-radius: 4px;
    padding: 8px;
    margin-bottom: 6px;
  }
  .angkaantrian {
    font-size: 40px;
    color: black !important;
  }
  .labelantrian { color: #333 !important; }
  .bungkusstruk {
    border: none;
    box-shadow: none;
    border-radius: 0;
  }
  .kepalastruk {
    background: white !important;
    color: black !important;
    border-bottom: 1px solid #ddd;
    padding: 8px 10px;
  }
  .ikonstruk { font-size: 18px !important; }
  .kepalastruk h2 { font-size: 13px !important; }
  .isistruk { padding: 8px 10px; }
  .barisstruk { padding: 4px 0; font-size: 11px; }
  .labelstruk { min-width: 80px; }
  .itemdetailstruk { padding: 4px 0; }
  .itemdetailstruk .namamenu { font-size: 11px; }
  .itemdetailstruk .barisnilai { font-size: 10px; }
  .totalstruk { padding: 8px 10px; }
  .totalstruk .nilai { font-size: 14px; }
  .kakistruk { padding: 8px 10px; font-size: 10px; }
  .badge { font-size: 10px; padding: 2px 6px; }
  /* Pastikan tidak lebih dari 1 halaman */
  * { page-break-inside: avoid; }
}
</style>
</head>
<body>

<div class="bungkus">

  <div class="headerkembali">
    <a href="manajemenpesanan.php" class="tombolkembali"><i class="fa-solid fa-arrow-left"></i></a>
    <div class="teksheader">
      <h1>Struk Pesanan</h1>
      <p><?= $nomerpesanan ?></p>
    </div>
  </div>

  <!-- NOMOR ANTRIAN -->
  <div class="antirian">
    <div style="font-size:11px;margin-bottom:4px;opacity:.8;">NOMOR ANTRIAN HARI INI</div>
    <div class="angkaantrian"><?= str_pad($antrian, 3, '0', STR_PAD_LEFT) ?></div>
    <div class="labelantrian"><?= $namatoko ?> &mdash; <?= date('d M Y', strtotime($pesanan['tanggal_order'])) ?></div>
  </div>

  <!-- STRUK -->
  <div class="bungkusstruk">

    <div class="kepalastruk">
      <div class="ikonstruk"><i class="fa-solid fa-receipt"></i></div>
      <h2><?= $namatoko ?> &mdash; Struk Pesanan</h2>
      <p>e-Kantin Sekolah</p>
    </div>

    <div class="isistruk">

      <div class="barisstruk">
        <span class="labelstruk">No. Pesanan</span>
        <span class="nilaistruk" style="font-family:monospace;color:var(--utama);"><?= $nomerpesanan ?></span>
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
        <span class="nilaistruk"><i class="fa-solid fa-wallet" style="font-size:11px;"></i> <?= htmlspecialchars($pesanan['metode_pembayaran']) ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Status</span>
        <span class="nilaistruk">
          <span class="badge <?= kelasstatus($pesanan['status_order']) ?>">
            <i class="fa-solid <?= ikonStatus($pesanan['status_order']) ?>"></i>
            <?= htmlspecialchars($pesanan['status_order']) ?>
          </span>
        </span>
      </div>
      <?php if (!empty($pesanan['catatan'])): ?>
      <div class="barisstruk">
        <span class="labelstruk">Catatan</span>
        <span class="nilaistruk"><?= htmlspecialchars($pesanan['catatan']) ?></span>
      </div>
      <?php endif; ?>

      <div class="pemisah"><hr><span><i class="fa-solid fa-utensils"></i></span><hr></div>

      <div style="font-size:11px;font-weight:700;color:var(--tekssamar);text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;">
        Detail Pesanan
      </div>

      <?php foreach ($items as $it): ?>
      <div class="itemdetailstruk">
        <div class="namamenu"><?= htmlspecialchars($it['nama_menu']) ?></div>
        <div class="barisnilai">
          <span><?= $it['jumlah'] ?>x @ Rp <?= number_format($it['harga_satuan'],0,',','.') ?></span>
          <b>Rp <?= number_format($it['subtotal'],0,',','.') ?></b>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="pemisah"><hr><span><i class="fa-solid fa-star"></i></span><hr></div>

      <div class="barisstruk">
        <span class="labelstruk">Subtotal</span>
        <span class="nilaistruk">Rp <?= number_format($subtotalitem,0,',','.') ?></span>
      </div>
      <div class="barisstruk">
        <span class="labelstruk">Biaya Layanan</span>
        <span class="nilaistruk">Rp <?= number_format(max(0,$biayalayanan),0,',','.') ?></span>
      </div>

    </div>

    <div class="totalstruk">
      <span class="label"><i class="fa-solid fa-coins"></i> Total Bayar</span>
      <span class="nilai">Rp <?= number_format($pesanan['total_harga'],0,',','.') ?></span>
    </div>

    <div class="kakistruk">
      <i class="fa-solid fa-heart" style="color:var(--utama);"></i>
      <p style="margin-top:4px;">Terima kasih sudah memesan!</p>
      <p style="margin-top:2px;"><?= $nomerpesanan ?> &nbsp;|&nbsp; Dicetak: <?= date('d/m/Y H:i') ?></p>
    </div>

  </div>

  <!-- Tombol aksi (tidak ikut cetak) -->
  <div class="grup-tombol">
    <button class="tombol-aksi tombol-cetak" onclick="window.print()">
      <i class="fa-solid fa-print"></i> Cetak Struk
    </button>
    <a href="manajemenpesanan.php" class="tombol-aksi tombol-kembali">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
  </div>

</div>

</body>
</html>
