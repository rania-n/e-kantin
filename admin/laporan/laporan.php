<?php
/* laporan platform (ADMIN) — filter: periode (7/14/30/custom) + kantin (semua/satu).
   CATATAN: laporan KEUANGAN (omset, grafik omset, nilai dibatalkan, pendapatan, dll)
   sengaja TIDAK ada di admin — itu hanya di laporan PENJUAL. admin hanya menampilkan
   data non-keuangan: info kantin + rating, daftar menu (+ stok), dan daftar pengguna.
   tombol CSV per section: eksporXlsSeksi(id, namafile); semua sekaligus: eksporXlsSemua(). */

include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

$halamansaatini = 'laporan';

// cek apakah kolom nomor_kantin sudah ada di tb_toko (hasil migrasi skema baru).
// kalau belum ada, query dijalankan dalam mode kompatibilitas dengan id_toko sebagai fallback.
$cekkolom    = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'nomor_kantin'");
$migrasiSudah = ($cekkolom && $cekkolom->num_rows > 0);

// ── FILTER PERIODE ─────────────────────────────────────────────────────────────
$periode = $_GET['periode'] ?? '7';
if (!in_array($periode, ['7','14','30','custom'])) $periode = '7';

if ($periode === 'custom') {
    $dari   = (isset($_GET['dari'])   && $_GET['dari'])   ? $_GET['dari']   : date('Y-m-d', strtotime('-7 days'));
    $sampai = (isset($_GET['sampai']) && $_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');
    $tglmulai = date('Y-m-d', strtotime($dari));
    $tgljin   = date('Y-m-d', strtotime($sampai));
    if ($tgljin < $tglmulai) $tgljin = $tglmulai;
} else {
    $tglmulai = date('Y-m-d', strtotime("-$periode days"));
    $tgljin   = date('Y-m-d');
    $dari = $tglmulai; $sampai = $tgljin;
}

// ── FILTER KANTIN ──────────────────────────────────────────────────────────────
// nomor_kantin = nomor slot 1..N (bukan id_toko). 0 berarti mode "semua kantin".
$nomorkantin = (int)($_GET['kantin'] ?? 0);

// fragmen SQL dinamis: pakai nomor_kantin kalau kolomnya ada, kalau tidak fallback ke id_toko
$kolomNomor = $migrasiSudah ? "t.nomor_kantin," : "NULL AS nomor_kantin,";
$groupNomor = $migrasiSudah ? ", t.nomor_kantin" : "";
$orderKolom = $migrasiSudah ? "t.nomor_kantin ASC" : "t.id_toko ASC";

/* PENTING: performa per kantin difilter by id_penjual current seller.
   omset (pendapatan) HANYA dari pesanan Selesai — uang yang benar-benar masuk.
   nilai_dibatalkan = potensi omset hilang, ditampilkan TERPISAH untuk info. */
$qtoko = $conn->prepare(
    "SELECT t.id_toko, $kolomNomor t.nama_toko, t.status_toko,
            t.id_user, u.username AS nama_penjual,
            COUNT(DISTINCT o.id_order) AS total_order,
            COALESCE(SUM(CASE WHEN o.status_order='Dibatalkan' THEN 1 ELSE 0 END),0) AS jml_dibatalkan,
            COALESCE(SUM(CASE WHEN o.status_order='Selesai'    THEN o.total_harga ELSE 0 END),0) AS pendapatan,
            COALESCE(SUM(CASE WHEN o.status_order='Dibatalkan' THEN o.total_harga ELSE 0 END),0) AS nilai_dibatalkan,
            (SELECT COALESCE(ROUND(AVG(r.rating_toko),1),0)
             FROM tb_rating r WHERE r.id_penjual=t.id_user AND r.deleted=0) AS rating
     FROM tb_toko t
     LEFT JOIN tb_user u ON t.id_user=u.id_user AND u.deleted=0
     LEFT JOIN tb_order o ON o.id_penjual=t.id_user
       AND DATE(o.tanggal_order) BETWEEN ? AND ? AND o.deleted=0
     WHERE t.deleted=0
     GROUP BY t.id_toko, t.nama_toko, t.status_toko, t.id_user, u.username $groupNomor
     ORDER BY $orderKolom"
);
$qtoko->bind_param("ss", $tglmulai, $tgljin); $qtoko->execute();
$perftoko = $qtoko->get_result()->fetch_all(MYSQLI_ASSOC); $qtoko->close();

// resolve nomor_kantin yang diminta user → id_toko + info kantin terpilih.
// kalau nomornya tidak ketemu di list, reset ke 0 (mode semua).
$idtokoterpilih   = 0;
$infokantinterpilih = null;
if ($nomorkantin > 0) {
    foreach ($perftoko as $t) {
        if ((int)$t['nomor_kantin'] === $nomorkantin) {
            $idtokoterpilih     = (int)$t['id_toko'];
            $infokantinterpilih = $t;
            break;
        }
    }
    if (!$idtokoterpilih) $nomorkantin = 0;
}

$modeSemua  = ($idtokoterpilih === 0);
// label omset disamakan di kedua mode (sebelumnya "Revenue" di mode semua)
$judulOmset = 'Omset';

/* dalam mode per-kantin, filter berdasarkan id_penjual penjual yang sekarang
   menempati slot (bukan id_toko). Ini supaya hanya data milik penjual sekarang
   yang dihitung, bukan tercampur dengan data penjual lama di slot yang sama. */
$idpenjualterpilih = (int)($infokantinterpilih['id_user'] ?? 0);
$tokoW = $idpenjualterpilih > 0 ? " AND o.id_penjual={$idpenjualterpilih}" : "";

// ── DATA UNTUK TAMPILAN ──────────────────────────────────────────────────────
// catatan: query keuangan (omset, nilai dibatalkan, jumlah pesanan, pelanggan, dll)
// sudah dihapus dari laporan admin — laporan keuangan hanya ada di sisi PENJUAL.
// admin hanya menampilkan data non-keuangan: rating, daftar menu (+stok), pengguna.

// rating: per-kantin filter by id_penjual, mode semua aggregate platform-wide
$ratingkantin = 0; $jmlratingkantin = 0;
if (!$modeSemua) {
    $qrat = $conn->prepare("SELECT COALESCE(ROUND(AVG(rating_toko),1),0), COUNT(*) FROM tb_rating WHERE id_penjual=? AND deleted=0");
    $qrat->bind_param("i", $idpenjualterpilih); $qrat->execute();
    $rr = $qrat->get_result()->fetch_row(); $qrat->close();
    $ratingkantin = (float)($rr[0]??0); $jmlratingkantin = (int)($rr[1]??0);
} else {
    // platform-wide: rata-rata + jumlah ulasan semua penjual
    $qrat = $conn->query("SELECT COALESCE(ROUND(AVG(rating_toko),1),0), COUNT(*) FROM tb_rating WHERE deleted=0");
    $rr = $qrat->fetch_row();
    $ratingkantin = (float)($rr[0]??0); $jmlratingkantin = (int)($rr[1]??0);
}

// ── DAFTAR MENU (+ STOK) ─────────────────────────────────────
// laporan admin hanya menampilkan daftar menu (+ stok). rating ringkas sudah
// dihitung di atas ($ratingkantin). distribusi rating & daftar ulasan tidak
// ditampilkan di admin, jadi query-nya tidak dibuat di sini.
$daftarmenu = [];

if (!$modeSemua && $idpenjualterpilih > 0) {
    // menu: hanya yang milik penjual sekarang (id_penjual=current seller)
    $qmenu = $conn->prepare(
        "SELECT m.nama_menu, m.harga, m.status, m.stok AS stok_akhir,
                COALESCE(SUM(CASE WHEN o.status_order='Selesai' THEN d.jumlah ELSE 0 END),0) AS terjual
         FROM tb_menu m
         LEFT JOIN tb_detail_order d ON m.id_menu=d.id_menu AND d.deleted=0
         LEFT JOIN tb_order o ON d.id_order=o.id_order AND o.deleted=0
           AND DATE(o.tanggal_order) BETWEEN ? AND ?
         WHERE m.id_penjual=? AND m.deleted=0
         GROUP BY m.id_menu, m.nama_menu, m.harga, m.status, m.stok
         ORDER BY (m.status='aktif') DESC, terjual DESC, m.nama_menu ASC"
    );
    $qmenu->bind_param("ssi", $tglmulai, $tgljin, $idpenjualterpilih); $qmenu->execute();
    $daftarmenu = $qmenu->get_result()->fetch_all(MYSQLI_ASSOC); $qmenu->close();
}

if ($modeSemua) {
    // semua menu aktif platform-wide.
    // "terjual" (laku) dihitung HANYA dalam periode yang dipilih supaya laporan penjualan
    // sesuai label "x Hari Terakhir". m.stok = stok akhir (stok saat ini).
    $qmenu_all = $conn->prepare(
        "SELECT m.nama_menu, m.harga, m.status, m.stok AS stok_akhir,
                COALESCE(t.nama_toko, CONCAT('Kantin ', m.id_toko)) AS nama_toko,
                COALESCE((SELECT SUM(d.jumlah) FROM tb_detail_order d
                          JOIN tb_order o ON d.id_order=o.id_order
                          WHERE d.id_menu=m.id_menu AND d.deleted=0
                            AND o.deleted=0 AND o.status_order='Selesai'
                            AND DATE(o.tanggal_order) BETWEEN ? AND ?),0) AS terjual
         FROM tb_menu m
         LEFT JOIN tb_toko t ON m.id_toko=t.id_toko
         WHERE m.deleted=0 AND m.status='aktif'
         ORDER BY terjual DESC, m.nama_menu ASC LIMIT 100"
    );
    $qmenu_all->bind_param("ss", $tglmulai, $tgljin); $qmenu_all->execute();
    $daftarmenu = $qmenu_all->get_result()->fetch_all(MYSQLI_ASSOC); $qmenu_all->close();
}

// ── HELPER ─────────────────────────────────────────────────────────────────────
// rp(): format angka jadi "Rp 12.345" (titik ribuan, tanpa desimal). dipakai kolom Harga menu.
function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }

// ── LABELS & CHART DIMS ────────────────────────────────────────────────────────
// siapkan label periode & kantin yang dipakai di header halaman + identitas ekspor
$labelperiode  = ['7'=>'7 Hari','14'=>'14 Hari','30'=>'30 Hari','custom'=>'Custom'];
$labelterpilih = $periode==='custom'
    ? date('d M Y', strtotime($tglmulai)).' – '.date('d M Y', strtotime($tgljin))
    : $labelperiode[$periode].' Terakhir';
$labelkantin = $nomorkantin > 0
    ? 'Kantin ke-'.$nomorkantin.(!empty($infokantinterpilih['nama_toko']) ? ' — '.htmlspecialchars($infokantinterpilih['nama_toko']) : '')
    : 'Semua Kantin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Platform - Admin jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.seksi-judul { display:flex;align-items:center;justify-content:space-between;margin-bottom:14px; }
.seksi-judul h3 { margin:0; }
.cetakjudul { display:none; }


@media print {
  .cetakjudul { display:block !important; margin-bottom:14px; font-family:sans-serif; }
  @page { size:A4 landscape; margin:10mm; }
  .takprint       { display:none !important; }
  .sembunyi-cetak { display:none !important; }
  .tabel-wrapper  { overflow:visible !important; }
  .tabel-wrapper table { min-width:0 !important; width:100%; font-size:11px; }
  .tabel-wrapper td, .tabel-wrapper th { padding:5px 6px; }
  .kartu { box-shadow:none !important; border:1px solid #ddd !important; page-break-inside:avoid; }
}
</style>
</head>
<body>
<?php include '../../3. komponen/navbaradmin.php'; ?>

<div class="cetakjudul" style="padding:0 24px;">
  <strong style="font-size:16px;"><i class="fa-solid fa-chart-bar"></i> Laporan Platform — jajankita</strong>
  <div style="font-size:12px;color:#555;margin-top:3px;">
    <?= htmlspecialchars($labelkantin) ?> &nbsp;|&nbsp; <?= htmlspecialchars($labelterpilih) ?> &nbsp;|&nbsp; Dicetak: <?= date('d M Y, H:i') ?>
  </div>
  <hr style="margin:8px 0;border:none;border-top:2px solid #ddd;">
</div>

<main class="konten">

  <div class="header-halaman takprint">
    <div class="kiri">
      <h1><i class="fa-solid fa-chart-bar"></i> Laporan Platform</h1>
      <p><?= $labelkantin ?> — <?= $labelterpilih ?></p>
    </div>
  </div>

  <!-- ── PANEL FILTER & CETAK ───────────────────────────────────────────── -->
  <div class="takprint" style="background:var(--putihbg);border:1.5px solid var(--garis);border-radius:12px;padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <i class="fa-solid fa-filter" style="color:var(--kedua);font-size:15px;"></i>
    <span style="font-size:13px;font-weight:700;color:var(--teks);">Pilih Kantin:</span>
    <form method="GET" action="laporan.php" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
      <input type="hidden" name="periode" value="<?= $periode ?>">
      <?php if ($periode==='custom'): ?>
      <input type="hidden" name="dari"   value="<?= $dari ?>">
      <input type="hidden" name="sampai" value="<?= $sampai ?>">
      <?php endif; ?>
      <!-- dropdown biasa: klik → semua kantin tampil, pilih satu → laporan langsung
           diterapkan otomatis (onchange submit), tanpa tombol. -->
      <select name="kantin" onchange="this.form.submit()"
              style="padding:7px 12px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;font-family:inherit;background:white;color:var(--teks);min-width:240px;">
        <option value="0" <?= $nomorkantin===0?'selected':'' ?>>— Semua Kantin —</option>
        <?php foreach ($perftoko as $t):
          if (empty($t['id_user']) || empty($t['nama_penjual'])) continue;
          $opt = 'Kantin ke-'.(int)$t['nomor_kantin'];
          if (!empty($t['nama_toko'])) $opt .= ' — '.htmlspecialchars($t['nama_toko']);
        ?>
        <option value="<?= (int)$t['nomor_kantin'] ?>" <?= $nomorkantin==(int)$t['nomor_kantin']?'selected':'' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
      <noscript>
        <button type="submit" class="tombolutama" style="padding:8px 14px;font-size:13px;">
          <i class="fa-solid fa-check"></i> Terapkan
        </button>
      </noscript>
    </form>
    <span style="color:var(--garis);font-size:18px;font-weight:300;">|</span>
    <button onclick="eksporXlsSemua('laporan_platform')" class="tombolringan" style="padding:8px 16px;font-size:13px;background:var(--sukses);color:white;border-color:var(--sukses);">
      <i class="fa-solid fa-file-csv"></i> Cetak Semua
    </button>
  </div>

  <!-- ── FILTER PERIODE ─────────────────────────────────────────────────── -->
  <div class="takprint" style="margin-bottom:18px;">
    <div class="filter-bar" style="margin-bottom:10px;">
      <a href="laporan.php?periode=7&kantin=<?= $nomorkantin ?>"  class="chip-filter <?= $periode==='7' ?'aktif':'' ?>">7 Hari</a>
      <a href="laporan.php?periode=14&kantin=<?= $nomorkantin ?>" class="chip-filter <?= $periode==='14'?'aktif':'' ?>">14 Hari</a>
      <a href="laporan.php?periode=30&kantin=<?= $nomorkantin ?>" class="chip-filter <?= $periode==='30'?'aktif':'' ?>">30 Hari</a>
      <a href="laporan.php?periode=custom&dari=<?= $dari ?>&sampai=<?= $sampai ?>&kantin=<?= $nomorkantin ?>"
         class="chip-filter <?= $periode==='custom'?'aktif':'' ?>">Custom</a>
    </div>
    <?php if ($periode==='custom'): ?>
    <form method="GET" action="laporan.php" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
      <input type="hidden" name="periode" value="custom">
      <input type="hidden" name="kantin"  value="<?= $nomorkantin ?>">
      <div><label style="font-size:11px;font-weight:700;color:var(--tekssamar);display:block;margin-bottom:4px;">DARI</label>
        <input type="date" name="dari"   value="<?= $dari ?>"   style="padding:8px 12px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;"></div>
      <div><label style="font-size:11px;font-weight:700;color:var(--tekssamar);display:block;margin-bottom:4px;">SAMPAI</label>
        <input type="date" name="sampai" value="<?= $sampai ?>" style="padding:8px 12px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;"></div>
      <button type="submit" class="tombolutama" style="align-self:flex-end;"><i class="fa-solid fa-filter"></i> Terapkan</button>
    </form>
    <?php endif; ?>
  </div>

  <!-- ── INFO KANTIN (per-kantin) ───────────────────────────────────────── -->
  <?php if (!$modeSemua && $infokantinterpilih): ?>
  <div class="kartu seksi-laporan" id="seksi-infokantin" style="margin-bottom:18px;background:var(--putihbg);">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-store"></i> Info Kantin</h3>
    </div>
    <div style="display:flex;align-items:center;gap:18px;">
      <div style="font-size:52px;font-weight:900;color:var(--utama);line-height:1;min-width:60px;text-align:center;"><?= $nomorkantin ?></div>
      <div style="flex:1;">
        <div style="font-size:20px;font-weight:800;color:var(--teks);">
          <?= !empty($infokantinterpilih['nama_toko']) ? htmlspecialchars($infokantinterpilih['nama_toko']) : '— Kosong —' ?>
        </div>
        <?php if (!empty($infokantinterpilih['nama_penjual'])): ?>
        <div style="font-size:13px;color:var(--tekssamar);margin-top:4px;">
          <i class="fa-solid fa-user" style="font-size:10px;"></i> <?= htmlspecialchars($infokantinterpilih['nama_penjual']) ?>
        </div>
        <?php endif; ?>
        <div style="margin-top:8px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
          <span class="badge <?= $infokantinterpilih['status_toko']==='buka'?'buka':'tutup' ?>"><?= $infokantinterpilih['status_toko']==='buka'?'Buka':'Tutup' ?></span>
          <?php if ($ratingkantin > 0): ?>
          <span style="font-size:13px;font-weight:700;color:var(--kedua);"><?= $ratingkantin ?> ★</span>
          <span style="font-size:12px;color:var(--tekssamar);">(<?= $jmlratingkantin ?> ulasan)</span>
          <?php endif; ?>
        </div>
      </div>
      <?php if (!empty($infokantinterpilih['id_user'])): ?>
      <a href="../manajemenpengguna/viewuser.php?id=<?= (int)$infokantinterpilih['id_user'] ?>" class="tombolringan takprint" style="font-size:12px;">
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Detail Penjual
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── DAFTAR MENU — selalu tampil (per-kantin atau platform-wide aktif) ── -->
  <?php if (!empty($daftarmenu)): ?>
  <div class="kartu seksi-laporan" id="seksi-menu" style="margin-bottom:18px;">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-utensils"></i> <?= $modeSemua ? 'Daftar Menu Platform' : 'Menu Kantin' ?> (<?= count($daftarmenu) ?> item) — <?= $labelterpilih ?></h3>
      <button onclick="eksporXlsSeksi('seksi-menu','laporan_penjualan')" class="tombolkecil takprint" style="background:var(--sukses);color:white;"><i class="fa-solid fa-file-csv"></i> Cetak</button>
    </div>
    <!-- keterangan rumus laporan penjualan agar kolom mudah dipahami -->
    <div style="font-size:11px;color:var(--tekssamar);margin-bottom:10px;">
      <i class="fa-solid fa-circle-info"></i>
      <strong>Laku</strong> = jumlah terjual (pesanan Selesai) pada periode ini ·
      <strong>Stok Akhir</strong> = stok tersisa saat ini ·
      <strong>Stok Awal</strong> = Stok Akhir + Laku (perkiraan stok di awal periode).
    </div>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th>Nama Menu</th>
            <?php if ($modeSemua): ?>
            <th>Toko / Kantin</th>
            <?php endif; ?>
            <th class="kanan">Harga</th>
            <th class="tengah">Status</th>
            <th class="tengah">Stok Awal</th>
            <th class="tengah">Laku</th>
            <th class="tengah">Stok Akhir</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftarmenu as $m):
            // laku = terjual periode; stok akhir = stok sekarang; stok awal = akhir + laku
            $laku      = (int)$m['terjual'];
            $stokakhir = (int)($m['stok_akhir'] ?? 0);
            $stokawal  = $stokakhir + $laku;
          ?>
          <tr>
            <td style="font-weight:600;"><?= htmlspecialchars($m['nama_menu']) ?></td>
            <?php if ($modeSemua): ?>
            <td><?= htmlspecialchars($m['nama_toko'] ?? '—') ?></td>
            <?php endif; ?>
            <td class="kanan"><?= rp((float)$m['harga']) ?></td>
            <td class="tengah"><span class="badge <?= $m['status']==='aktif'?'selesai':'dibatalkan' ?>"><?= ucfirst($m['status']) ?></span></td>
            <td class="tengah"><?= $stokawal ?></td>
            <td class="tengah" style="font-weight:700;color:var(--utama);"><?= $laku ?></td>
            <td class="tengah"><?= $stokakhir ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── DAFTAR PENGGUNA PLATFORM — HANYA tampil di mode "Semua Kantin"
       (saat satu kantin dipilih, section ini disembunyikan). ikut ter-ekspor
       lewat tombol "Cetak Semua". ───── -->
  <?php if ($modeSemua): ?>
  <?php
  // ambil semua pengguna yang belum dihapus (platform-wide, tidak terpengaruh filter kantin)
  $qusr = $conn->query("SELECT username, nama_lengkap, role, kelas, email, no_telepon, status_verifikasi, status_akun
                        FROM tb_user WHERE deleted=0
                        ORDER BY FIELD(role,'admin','penjual','pembeli'), username");
  $daftarpengguna = $qusr ? $qusr->fetch_all(MYSQLI_ASSOC) : [];
  ?>
  <div class="kartu seksi-laporan" id="seksi-pengguna" style="margin-bottom:18px;">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-users"></i> Daftar Pengguna (<?= count($daftarpengguna) ?>)</h3>
      <button onclick="eksporXlsSeksi('seksi-pengguna','daftar_pengguna')" class="tombolkecil takprint" style="background:var(--sukses);color:white;"><i class="fa-solid fa-file-csv"></i> Cetak</button>
    </div>
    <?php if (empty($daftarpengguna)): ?>
    <div class="kosong" style="padding:24px;"><p>Belum ada pengguna</p></div>
    <?php else: ?>
    <div class="tabel-wrapper">
      <table style="min-width:760px;">
        <thead>
          <tr>
            <th>Username</th>
            <th>Nama Lengkap</th>
            <th class="tengah" style="width:90px;">Peran</th>
            <th>Kelas / Status</th>
            <th>Email</th>
            <th style="width:130px;">No. HP</th>
            <th class="tengah" style="width:110px;">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftarpengguna as $p): ?>
          <tr>
            <td style="font-weight:700;"><?= htmlspecialchars($p['username']) ?></td>
            <td><?= htmlspecialchars($p['nama_lengkap'] ?: '—') ?></td>
            <td class="tengah"><span class="badge <?= htmlspecialchars($p['role']) ?>"><?= ucfirst($p['role']) ?></span></td>
            <td><?= htmlspecialchars($p['kelas'] ?: '—') ?></td>
            <td style="font-size:12px;color:var(--tekssamar);"><?= htmlspecialchars($p['email']) ?></td>
            <td style="font-size:12px;"><?= htmlspecialchars($p['no_telepon'] ?: '—') ?></td>
            <td class="tengah" style="font-size:11px;">
              <?= htmlspecialchars(ucfirst($p['status_verifikasi'] ?? '—')) ?><?= (($p['status_akun'] ?? 'aktif') === 'nonaktif') ? ' · Nonaktif' : '' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; // tutup blok Daftar Pengguna (hanya mode Semua Kantin) ?>

</main>

<script>
/* ===== EKSPOR XLS (HTML-in-Excel) — TABEL BERGARIS DENGAN IDENTITAS =====
   Strategi: bungkus HTML table dalam file dengan ekstensi .xls dan mime type
   application/vnd.ms-excel. Excel akan buka sebagai workbook dengan border yang
   sudah di-style. Lebih bagus dari plain CSV karena ada visual table + header
   identitas (kantin, periode, dst) langsung tampil di atas data. */

var IDENTITAS = {
    judul:   <?= $modeSemua ? "'Laporan Platform'" : "'Laporan Per Kantin'" ?>,
    kantin:  <?= json_encode($labelkantin) ?>,
    penjual: <?= json_encode($infokantinterpilih['nama_penjual'] ?? '') ?>,
    periode: <?= json_encode($labelterpilih) ?>,
};

/* baris identitas di atas tabel data — kantin, periode, tanggal cetak */
function buildIdentitasHtml(judulSection) {
    var tgl = new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});
    var cssLabel = 'border:1px solid #999;padding:6pt 10pt;background:#F8EBF1;font-weight:bold;width:160px;';
    var cssNilai = 'border:1px solid #999;padding:6pt 10pt;';
    var html = '<table style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:11pt;margin-bottom:10pt;width:100%;">';
    html += '<tr><td colspan="2" style="background:#643843;color:white;font-weight:bold;font-size:14pt;text-align:center;padding:10pt;border:1px solid #444;">jajankita &mdash; ' + IDENTITAS.judul + '</td></tr>';
    if (judulSection)        html += '<tr><td style="'+cssLabel+'">Section</td><td style="'+cssNilai+'">' + judulSection + '</td></tr>';
    if (IDENTITAS.kantin)    html += '<tr><td style="'+cssLabel+'">Kantin/Toko</td><td style="'+cssNilai+'">' + IDENTITAS.kantin + '</td></tr>';
    if (IDENTITAS.penjual)   html += '<tr><td style="'+cssLabel+'">Penjual</td><td style="'+cssNilai+'">' + IDENTITAS.penjual + '</td></tr>';
    if (IDENTITAS.periode)   html += '<tr><td style="'+cssLabel+'">Periode</td><td style="'+cssNilai+'">' + IDENTITAS.periode + '</td></tr>';
    html += '<tr><td style="'+cssLabel+'">Tanggal Cetak</td><td style="'+cssNilai+'">' + tgl + '</td></tr>';
    html += '</table>';
    return html;
}

/* clone tabel lalu inject border & padding inline supaya Excel render bergaris.
   improvements: zebra stripe pada body, header gelap, tfoot dengan background
   khusus, padding seragam. ikon font-awesome dibuang karena bikin sel berantakan. */
function tableToBorderedHtml(table) {
    var clone = table.cloneNode(true);
    clone.setAttribute('border', '1');
    clone.setAttribute('cellpadding', '6');
    clone.setAttribute('cellspacing', '0');
    clone.setAttribute('style', 'border-collapse:collapse;font-family:Arial,sans-serif;font-size:11pt;width:100%;margin-bottom:8pt;');
    // header tabel: warna utama coklat tua + teks putih
    clone.querySelectorAll('th').forEach(function(th){
        th.setAttribute('style', 'background:#643843;color:white;border:1px solid #3d2230;padding:8pt 10pt;text-align:left;font-weight:bold;');
    });
    // body tabel: zebra stripe untuk readability
    clone.querySelectorAll('tbody tr').forEach(function(tr, i){
        var bg = i % 2 === 1 ? 'background:#FAF6F8;' : '';
        tr.querySelectorAll('td').forEach(function(td){
            td.setAttribute('style', 'border:1px solid #c8c8c8;padding:6pt 10pt;vertical-align:top;' + bg);
        });
    });
    // footer tabel: background pink muda + bold (untuk total)
    clone.querySelectorAll('tfoot td').forEach(function(td){
        td.setAttribute('style', 'border:1px solid #999;padding:7pt 10pt;background:#F8EBF1;font-weight:bold;');
    });
    // ikon font-awesome dihapus
    clone.querySelectorAll('i').forEach(function(ic){ ic.remove(); });
    return clone.outerHTML;
}

/* download file .xls dengan content HTML — excel akan render sebagai spreadsheet */
function unduhXls(htmlBody, namafile) {
    var doc = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">'
            + '<head><meta charset="UTF-8"></head><body>' + htmlBody + '</body></html>';
    var blob = new Blob(['﻿' + doc], { type: 'application/vnd.ms-excel' });
    var url  = URL.createObjectURL(blob);
    var a    = document.createElement('a');
    var tgl  = new Date().toISOString().slice(0,10);
    a.href = url; a.download = namafile + '_' + tgl + '.xls';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    setTimeout(function(){ URL.revokeObjectURL(url); }, 100);
}

/* ekspor 1 section: identitas + judul section + tabel bergaris */
function eksporXlsSeksi(idSection, namafile) {
    var section = document.getElementById(idSection);
    if (!section) return;
    var tables  = section.querySelectorAll('table');
    if (!tables.length) { alert('Tidak ada tabel data di section ini.'); return; }
    var judul   = section.querySelector('h3') ? section.querySelector('h3').innerText.trim() : '';
    var html = buildIdentitasHtml(judul);
    tables.forEach(function(t){ html += tableToBorderedHtml(t) + '<br>'; });
    unduhXls(html, namafile);
}

/* ekspor SEMUA section yang punya tabel — laporan lengkap dalam 1 file.
   tiap section di-headline dengan bar coklat tua + jarak antar section. */
function eksporXlsSemua(namafile) {
    var html = buildIdentitasHtml('Laporan Lengkap');
    document.querySelectorAll('.seksi-laporan').forEach(function(section){
        var tables = section.querySelectorAll('table');
        if (!tables.length) return;
        var judul = section.querySelector('h3') ? section.querySelector('h3').innerText.trim() : '';
        html += '<div style="height:14pt;"></div>';
        html += '<table style="border-collapse:collapse;width:100%;margin-bottom:4pt;"><tr><td style="background:#643843;color:white;font-family:Arial,sans-serif;font-size:13pt;font-weight:bold;padding:8pt 12pt;border:1px solid #3d2230;">' + judul + '</td></tr></table>';
        tables.forEach(function(t){ html += tableToBorderedHtml(t); });
    });
    unduhXls(html, namafile);
}

</script>
</body>
</html>
