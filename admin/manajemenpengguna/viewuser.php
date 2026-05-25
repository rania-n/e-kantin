<?php
/* detail pengguna untuk admin — penjual: semua data toko + kantin digabung (stat, tren omset,
   produk terlaris, rating, ulasan, daftar menu, pelanggan). pembeli: stat + toko favorit.
   fallback ke tb_riwayat_toko jika slot sudah dikosongkan penuh (migrasi riwayat dijalankan).
   print via window.print(); takprint menyembunyikan nav dan tombol aksi. */

include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

$halamansaatini = 'user';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: user.php"); exit; }

$qu = $conn->prepare("SELECT * FROM tb_user WHERE id_user=?");
$qu->bind_param("i", $id); $qu->execute();
$user = $qu->get_result()->fetch_assoc(); $qu->close();
if (!$user) { header("Location: user.php"); exit; }

$isDihapus   = (int)($user['deleted'] ?? 0) === 1;
$adaDelAtVu  = array_key_exists('deleted_at', $user);
$tglBerhenti = ($adaDelAtVu && !empty($user['deleted_at'])) ? date('d M Y, H:i', strtotime($user['deleted_at'])) : null;

$inisial = strtoupper(mb_substr($user['username'], 0, 2));

// ── init variabel ──────────────────────────────────────────────────────────────
$toko = null; $riwayat = null;
$totalpesanan = $totalomset = $omsetselesai = $jmlselesai = 0;
$ratarating   = $jmlrating  = $totalmenu    = $jmlpelanggan = 0;
$distribusirating = []; $ulasanterbaru = []; $terlaris = [];
$daftarmenu = []; $toppelanggan = []; $statuspesanan = [];
$totalorder_pembeli = $totalbelanja = 0; $tokofavorit = [];
$charthari = []; $maxomsethari = 1; $topmahal = [];
$modecustom = ($_GET['hari'] ?? '') === 'custom';
if ($modecustom) {
    $dari   = (!empty($_GET['dari']))   ? date('Y-m-d', strtotime($_GET['dari']))   : date('Y-m-d', strtotime('-7 days'));
    $sampai = (!empty($_GET['sampai'])) ? date('Y-m-d', strtotime($_GET['sampai'])) : date('Y-m-d');
    if ($sampai < $dari) $sampai = $dari;
    $nhari  = (int)ceil((strtotime($sampai) - strtotime($dari)) / 86400) + 1;
} else {
    $nhari  = in_array((int)($_GET['hari'] ?? 7), [7,14,30]) ? (int)($_GET['hari'] ?? 7) : 7;
    $dari   = date('Y-m-d', strtotime('-' . ($nhari - 1) . ' days'));
    $sampai = date('Y-m-d');
}

// ── PENJUAL ────────────────────────────────────────────────────────────────────
if ($user['role'] === 'penjual') {
    // cari toko aktif (slot belum dikosongkan penuh — id_user masih ada)
    $qt = $conn->prepare("SELECT * FROM tb_toko WHERE id_user=? AND deleted=0");
    $qt->bind_param("i", $id); $qt->execute();
    $toko = $qt->get_result()->fetch_assoc(); $qt->close();

    // fallback: cari di riwayat jika slot sudah dikosongkan (migrasi riwayat sudah jalan)
    if (!$toko && $isDihapus) {
        $cektbr = $conn->query("SHOW TABLES LIKE 'tb_riwayat_toko'");
        if ($cektbr && $cektbr->num_rows > 0) {
            $qrw = $conn->prepare("SELECT * FROM tb_riwayat_toko WHERE id_user=? ORDER BY id_riwayat DESC LIMIT 1");
            $qrw->bind_param("i", $id); $qrw->execute();
            $riwayat = $qrw->get_result()->fetch_assoc(); $qrw->close();
        }
    }

    // $it = id_toko untuk semua query statistik (dari toko aktif atau riwayat)
    $it = $toko ? (int)$toko['id_toko'] : ($riwayat ? (int)$riwayat['id_toko'] : 0);

    if ($it) {
        $s = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_toko=? AND deleted=0");
        $s->bind_param("i",$it); $s->execute(); $r=$s->get_result()->fetch_row(); $s->close();
        $totalpesanan=(int)$r[0]; $totalomset=(float)$r[1];

        $s = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_toko=? AND status_order='Selesai' AND deleted=0");
        $s->bind_param("i",$it); $s->execute(); $r=$s->get_result()->fetch_row(); $s->close();
        $jmlselesai=(int)$r[0]; $omsetselesai=(float)$r[1];

        $s = $conn->prepare("SELECT ROUND(AVG(rating_toko),1), COUNT(*) FROM tb_rating WHERE id_toko=? AND deleted=0");
        $s->bind_param("i",$it); $s->execute(); $r=$s->get_result()->fetch_row(); $s->close();
        $ratarating=(float)($r[0]??0); $jmlrating=(int)($r[1]??0);

        $s = $conn->prepare("SELECT COUNT(*) FROM tb_menu WHERE id_toko=? AND status='aktif' AND deleted=0");
        $s->bind_param("i",$it); $s->execute(); $totalmenu=(int)$s->get_result()->fetch_row()[0]; $s->close();

        $s = $conn->prepare("SELECT COUNT(DISTINCT id_user) FROM tb_order WHERE id_toko=? AND deleted=0");
        $s->bind_param("i",$it); $s->execute(); $jmlpelanggan=(int)$s->get_result()->fetch_row()[0]; $s->close();

        $s = $conn->prepare("SELECT rating_toko, COUNT(*) AS jml FROM tb_rating WHERE id_toko=? AND deleted=0 GROUP BY rating_toko ORDER BY rating_toko DESC");
        $s->bind_param("i",$it); $s->execute(); $res=$s->get_result();
        while ($r=$res->fetch_assoc()) $distribusirating[(int)$r['rating_toko']]=(int)$r['jml'];
        $s->close();

        $s = $conn->prepare(
            "SELECT r.rating_toko, r.ulasan, r.created, u.username
             FROM tb_rating r JOIN tb_user u ON r.id_user=u.id_user
             WHERE r.id_toko=? AND r.deleted=0
             ORDER BY r.created DESC LIMIT 5"
        );
        $s->bind_param("i",$it); $s->execute(); $ulasanterbaru=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

        $s = $conn->prepare(
            "SELECT m.nama_menu, m.harga, SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omset
             FROM tb_detail_order d
             JOIN tb_menu m ON d.id_menu=m.id_menu
             JOIN tb_order o ON d.id_order=o.id_order
             WHERE m.id_toko=? AND d.deleted=0 AND o.deleted=0 AND o.status_order!='Dibatalkan'
             GROUP BY m.id_menu, m.nama_menu, m.harga
             ORDER BY terjual DESC LIMIT 5"
        );
        $s->bind_param("i",$it); $s->execute(); $terlaris=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

        $s = $conn->prepare(
            "SELECT m.nama_menu, m.harga, m.status,
                    COALESCE((SELECT SUM(d2.jumlah) FROM tb_detail_order d2
                              JOIN tb_order o2 ON d2.id_order=o2.id_order
                              WHERE d2.id_menu=m.id_menu AND d2.deleted=0
                                AND o2.deleted=0 AND o2.status_order!='Dibatalkan'),0) AS terjual
             FROM tb_menu m WHERE m.id_toko=? AND m.deleted=0
             ORDER BY (m.status='aktif') DESC, terjual DESC, m.nama_menu ASC"
        );
        $s->bind_param("i",$it); $s->execute(); $daftarmenu=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

        $s = $conn->prepare(
            "SELECT u.username, COUNT(o.id_order) AS jml_order, COALESCE(SUM(o.total_harga),0) AS total_belanja
             FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user
             WHERE o.id_toko=? AND o.status_order='Selesai' AND o.deleted=0
             GROUP BY o.id_user, u.username ORDER BY jml_order DESC LIMIT 5"
        );
        $s->bind_param("i",$it); $s->execute(); $toppelanggan=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

        $s = $conn->prepare(
            "SELECT u.username, COUNT(o.id_order) AS jml_order, COALESCE(SUM(o.total_harga),0) AS total_belanja
             FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user
             WHERE o.id_toko=? AND o.status_order='Selesai' AND o.deleted=0
             GROUP BY o.id_user, u.username ORDER BY total_belanja DESC LIMIT 5"
        );
        $s->bind_param("i",$it); $s->execute(); $topmahal=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

        $s = $conn->prepare("SELECT status_order, COUNT(*) AS jml FROM tb_order WHERE id_toko=? AND deleted=0 GROUP BY status_order");
        $s->bind_param("i",$it); $s->execute(); $res=$s->get_result();
        while ($r=$res->fetch_assoc()) $statuspesanan[$r['status_order']]=(int)$r['jml'];
        $s->close();

        // tren omset — tanpa JS
        $sqchart = $conn->prepare(
            "SELECT DATE(tanggal_order) AS tgl, COALESCE(SUM(total_harga),0) AS nilai
             FROM tb_order WHERE id_toko=? AND deleted=0
               AND DATE(tanggal_order) BETWEEN ? AND ?
             GROUP BY DATE(tanggal_order)"
        );
        $sqchart->bind_param("iss",$it,$dari,$sampai); $sqchart->execute();
        $reschart = $sqchart->get_result(); $tmpmap = [];
        while ($rc = $reschart->fetch_assoc()) $tmpmap[$rc['tgl']] = (float)$rc['nilai'];
        $sqchart->close();
        $selisih = (int)ceil((strtotime($sampai) - strtotime($dari)) / 86400) + 1;
        for ($i = 0; $i < $selisih; $i++) {
            $tglhari = date('Y-m-d', strtotime($dari) + $i * 86400);
            $charthari[] = ['tgl' => $tglhari, 'nilai' => $tmpmap[$tglhari] ?? 0];
        }
        $vals = array_column($charthari, 'nilai');
        $maxomsethari = max($vals ?: [1]);
        if ($maxomsethari <= 0) $maxomsethari = 1;
    }

// ── PEMBELI ────────────────────────────────────────────────────────────────────
} elseif ($user['role'] === 'pembeli') {
    $s = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_user=? AND deleted=0");
    $s->bind_param("i",$id); $s->execute(); $r=$s->get_result()->fetch_row(); $s->close();
    $totalorder_pembeli=(int)$r[0]; $totalbelanja=(float)$r[1];

    $s = $conn->prepare(
        "SELECT t.nama_toko, t.id_toko, COUNT(o.id_order) AS jml_order, COALESCE(SUM(o.total_harga),0) AS total_belanja
         FROM tb_order o JOIN tb_toko t ON o.id_toko=t.id_toko
         WHERE o.id_user=? AND o.status_order='Selesai' AND o.deleted=0
         GROUP BY o.id_toko, t.id_toko, t.nama_toko ORDER BY jml_order DESC LIMIT 5"
    );
    $s->bind_param("i",$id); $s->execute(); $tokofavorit=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
}

$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']);
}

// helper vars untuk hero penjual — bisa dari toko aktif atau riwayat
$tokosumber  = $toko ?? $riwayat ?? [];
$nomorkantin = (int)($tokosumber['nomor_kantin'] ?? 0);
$namatoko    = !empty($toko) ? ($toko['nama_toko'] ?? '') : (!empty($riwayat) ? ($riwayat['nama_toko'] ?? '') : '');
$statustoko  = !empty($toko) ? ($toko['status_toko'] ?? 'tutup') : 'tutup';
$idtoko      = (int)($tokosumber['id_toko'] ?? 0);

// dimensi SVG chart (sama persis dengan laporan platform)
$svgn    = count($charthari);
$svgw    = max(400, $svgn * 30);
$svgbarw = max(8, min(40, (int)(($svgw - 80) / max(1, $svgn)) - 4));
$svggap  = max(4, (int)(($svgw - 80 - $svgn * $svgbarw) / max(1, $svgn - 1)));

function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
function singkat(float $n): string {
    if ($n >= 1_000_000_000) { $v=$n/1_000_000_000; return 'Rp '.rtrim(rtrim(number_format($v,1,',','.'),'0'),',').' M'; }
    if ($n >= 1_000_000)     { $v=$n/1_000_000;     return 'Rp '.rtrim(rtrim(number_format($v,1,',','.'),'0'),',').' Jt'; }
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function bintanghtml(float $r): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $w = $i <= $r ? '#F59E0B' : '#D1D5DB';
        $out .= "<i class='fa-solid fa-star' style='color:{$w};font-size:12px;'></i>";
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Pengguna - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.cetakjudul { display:none; }
.bar-dist { height:8px;background:#f0f0f0;border-radius:4px;overflow:hidden;flex:1; }
.bar-dist-isi { height:100%;background:var(--kedua);border-radius:4px; }
/* bar status pesanan */
.bar-status-wrap { flex:1;background:#f0f0f0;border-radius:3px;height:14px;overflow:hidden; }
.bar-status-isi  { height:100%;background:var(--utama);border-radius:3px; }
@media print {
  .takprint { display:none !important; }
  .cetakjudul { display:block !important; margin-bottom:14px; }
  @page { size:A4; margin:12mm; }
  .kartu { box-shadow:none !important; border:1px solid #ddd !important; page-break-inside:avoid; }
  .grid-stat { grid-template-columns:repeat(3,1fr) !important; }
  .grid-dua  { display:flex; flex-wrap:wrap; gap:14px; }
  .grid-dua > .kartu { flex:1; min-width:200px; }
  table { font-size:11px; }
}
</style>
</head>
<body>

<div class="takprint"><?php include '../../3. komponen/navbaradmin.php'; ?></div>

<main class="konten">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-user-circle"></i> Detail Pengguna<?= ($user['role']==='penjual'&&($toko||$riwayat)) ? ' &amp; Kantin' : '' ?></h1>
      <p><?= htmlspecialchars($user['username']) ?> — <?= ucfirst($user['role']) ?><?= $isDihapus ? ' <span style="color:#dc2626;font-weight:700;">· Terhapus</span>' : '' ?></p>
    </div>
    <div class="takprint" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
      <button onclick="window.print()" class="tombolringan">
        <i class="fa-solid fa-print"></i> Cetak
      </button>
      <a href="edituser.php?id=<?= $id ?>" class="tombolutama">
        <i class="fa-solid fa-pen"></i> Edit
      </a>
      <?php if (!$isDihapus): ?>
      <a href="hapususer.php?id=<?= $id ?>" class="tombolbahaya">
        <i class="fa-solid fa-trash"></i> Hapus
      </a>
      <?php endif; ?>
      <a href="user.php" class="tombolringan">
        <i class="fa-solid fa-arrow-left"></i> Kembali
      </a>
    </div>
  </div>

  <div class="cetakjudul">
    <strong style="font-size:16px;">Detail Pengguna<?= ($user['role']==='penjual'&&($toko||$riwayat))?' &amp; Kantin':'' ?> — jajankita</strong>
    <div style="font-size:11px;color:#888;margin-top:2px;">Dicetak: <?= date('d M Y, H:i') ?></div>
    <hr style="margin:8px 0;border:none;border-top:2px solid #ddd;">
  </div>

  <?php if ($flashpesan): ?>
  <div class="flashpesan flash<?= $flashjenis ?> takprint">
    <i class="fa-solid fa-<?= $flashjenis === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
  <?php endif; ?>

<?php if ($isDihapus): ?>
<div style="background:#fee2e2;border:1.5px solid #fca5a5;border-radius:10px;padding:12px 18px;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
  <i class="fa-solid fa-user-slash" style="color:#dc2626;font-size:15px;flex-shrink:0;"></i>
  <span style="color:#dc2626;font-size:13px;font-weight:700;">Akun ini telah dihapus<?= $tglBerhenti ? ' pada '.$tglBerhenti : '' ?>. Data historis tetap tersimpan untuk referensi.</span>
</div>
<?php endif; ?>

<?php if ($user['role'] === 'penjual' && ($toko || $riwayat)): ?>
<!-- ════════════════ PENJUAL + TOKO ════════════════ -->

  <!-- Hero: info penjual + info toko dalam 1 kartu -->
  <div class="kartu" style="margin-bottom:18px;">
    <div style="display:flex;align-items:stretch;gap:0;flex-wrap:wrap;">

      <!-- Info Penjual -->
      <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:220px;padding-right:20px;">
        <div style="width:68px;height:68px;border-radius:50%;background:var(--latar);color:var(--utama);
                    display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;
                    flex-shrink:0;"><?= $inisial ?></div>
        <div>
          <div style="font-size:18px;font-weight:800;color:var(--teks);"><?= htmlspecialchars($user['username']) ?></div>
          <div style="font-size:13px;color:var(--tekssamar);"><?= htmlspecialchars($user['email']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);margin-top:4px;">
            <i class="fa-solid fa-calendar" style="font-size:9px;"></i>
            Bergabung <?= !empty($user['created']) ? date('d M Y', strtotime($user['created'])) : '—' ?>
          </div>
          <span class="badge penjual" style="margin-top:6px;display:inline-block;">Penjual</span>
          <?php if ($isDihapus && $tglBerhenti): ?>
          <div style="font-size:11px;color:#dc2626;margin-top:5px;"><i class="fa-solid fa-user-slash" style="font-size:9px;"></i> Berhenti <?= $tglBerhenti ?></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Divider -->
      <div style="width:1.5px;background:var(--garis);margin:0 20px;align-self:stretch;min-height:60px;"></div>

      <!-- Info Toko/Kantin -->
      <div style="flex:1;min-width:220px;display:flex;align-items:center;gap:14px;">
        <?php if ($nomorkantin > 0): ?>
        <div style="text-align:center;min-width:64px;">
          <div style="font-size:44px;font-weight:900;color:var(--utama);line-height:1;"><?= $nomorkantin ?></div>
          <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--tekssamar);">Kantin</div>
        </div>
        <?php endif; ?>
        <div style="flex:1;">
          <?php if (empty($namatoko)): ?>
          <div style="font-size:17px;font-weight:800;color:#bdbdbd;font-style:italic;">
            <?= $isDihapus ? '— Slot telah dikosongkan —' : '— Belum diisi —' ?>
          </div>
          <?php else: ?>
          <div style="font-size:17px;font-weight:800;color:var(--teks);"><?= htmlspecialchars($namatoko) ?></div>
          <?php endif; ?>
          <?php if ($riwayat && !$toko): ?>
          <div style="font-size:10px;color:#bdbdbd;font-style:italic;margin-top:2px;">Data historis — slot sudah dibebaskan</div>
          <?php endif; ?>
          <div style="display:flex;align-items:center;gap:8px;margin-top:6px;flex-wrap:wrap;">
            <?php if (!$isDihapus && $toko): ?>
            <form method="POST" action="../manajementoko/prosestoggletoko.php" style="display:inline;margin:0;">
              <input type="hidden" name="id_toko"     value="<?= $toko['id_toko'] ?>">
              <input type="hidden" name="id_user_ref" value="<?= $user['id_user'] ?>">
              <button type="submit" class="badge <?= $statustoko==='buka'?'buka':'tutup' ?>"
                      style="border:none;cursor:pointer;font-family:inherit;" title="Klik untuk ubah status">
                <?= $statustoko==='buka'?'Buka':'Tutup' ?>
                <i class="fa-solid fa-arrows-rotate takprint" style="font-size:9px;"></i>
              </button>
            </form>
            <?php else: ?>
            <span class="badge <?= $statustoko==='buka'?'buka':'tutup' ?>"><?= $statustoko==='buka'?'Buka':'Tutup' ?></span>
            <?php endif; ?>
            <?php if ($ratarating > 0): ?>
            <span style="font-size:13px;font-weight:700;color:var(--kedua);"><?= $ratarating ?> ★</span>
            <span style="font-size:12px;color:var(--tekssamar);">(<?= $jmlrating ?> ulasan)</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--latar);font-size:10px;color:var(--tekssamar);display:flex;gap:14px;flex-wrap:wrap;">
      <span>ID Akun: #<?= $user['id_user'] ?></span>
      <span>·</span>
      <span>ID Toko: #<?= $idtoko ?></span>
      <?php if ($nomorkantin > 0): ?><span>·</span><span>Kantin ke-<?= $nomorkantin ?></span><?php endif; ?>
      <span>·</span>
      <span>Bergabung: <?= !empty($user['created']) ? date('d M Y, H:i', strtotime($user['created'])) : '—' ?></span>
    </div>
  </div>

  <!-- Stat Cards (6) -->
  <div class="grid-stat" style="grid-template-columns:repeat(3,1fr);margin-bottom:18px;">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-receipt"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $totalpesanan ?></div><div class="label">Total Pesanan</div><div class="sub">Semua status</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-coins"></i></div>
      <div class="isi-stat"><div class="nilai" style="font-size:13px;"><?= rp($totalomset) ?></div><div class="label">Total Omset</div><div class="sub">Semua status</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:var(--suksebg);color:var(--sukses);"><i class="fa-solid fa-circle-check"></i></div>
      <div class="isi-stat"><div class="nilai" style="font-size:13px;"><?= rp($omsetselesai) ?></div><div class="label">Omset Selesai</div><div class="sub"><?= $jmlselesai ?> pesanan</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:#fffbeb;color:#D97706;"><i class="fa-solid fa-star"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $ratarating ?: '—' ?></div><div class="label">Rating Toko</div><div class="sub"><?= $jmlrating ?> ulasan</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-utensils"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $totalmenu ?></div><div class="label">Menu Aktif</div><div class="sub"><?= count($daftarmenu) ?> total menu</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-users"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $jmlpelanggan ?></div><div class="label">Pelanggan</div><div class="sub">Pembeli unik</div></div>
    </div>
  </div>

  <!-- Tren Omset (SVG) -->
  <div class="kartu" style="margin-bottom:18px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
      <h3 style="margin:0;"><i class="fa-solid fa-chart-bar"></i> Tren Omset
        <?= $modecustom ? date('d M',strtotime($dari)).'–'.date('d M Y',strtotime($sampai)) : $nhari.' Hari Terakhir' ?>
      </h3>
      <div style="display:flex;gap:4px;flex-wrap:wrap;" class="takprint">
        <a href="?id=<?= $id ?>&hari=7"      class="chip-filter <?= !$modecustom&&$nhari===7 ?'aktif':'' ?>" style="font-size:11px;padding:4px 10px;">7 Hari</a>
        <a href="?id=<?= $id ?>&hari=14"     class="chip-filter <?= !$modecustom&&$nhari===14?'aktif':'' ?>" style="font-size:11px;padding:4px 10px;">14 Hari</a>
        <a href="?id=<?= $id ?>&hari=30"     class="chip-filter <?= !$modecustom&&$nhari===30?'aktif':'' ?>" style="font-size:11px;padding:4px 10px;">30 Hari</a>
        <a href="?id=<?= $id ?>&hari=custom" class="chip-filter <?= $modecustom?'aktif':'' ?>" style="font-size:11px;padding:4px 10px;">Custom</a>
      </div>
    </div>
    <?php if ($modecustom): ?>
    <form method="GET" action="viewuser.php" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:12px;" class="takprint">
      <input type="hidden" name="id"   value="<?= $id ?>">
      <input type="hidden" name="hari" value="custom">
      <div><label style="font-size:11px;font-weight:700;color:var(--tekssamar);display:block;margin-bottom:3px;">DARI</label>
        <input type="date" name="dari"   value="<?= $dari ?>"   style="padding:7px 10px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;font-family:inherit;"></div>
      <div><label style="font-size:11px;font-weight:700;color:var(--tekssamar);display:block;margin-bottom:3px;">SAMPAI</label>
        <input type="date" name="sampai" value="<?= $sampai ?>" style="padding:7px 10px;border:1.5px solid var(--garis);border-radius:8px;font-size:13px;font-family:inherit;"></div>
      <button type="submit" class="tombolutama" style="padding:8px 14px;font-size:13px;align-self:flex-end;"><i class="fa-solid fa-filter"></i> Terapkan</button>
    </form>
    <?php endif; ?>
    <div style="overflow-x:auto;">
      <svg viewBox="0 0 <?= $svgw ?> 160" xmlns="http://www.w3.org/2000/svg" style="min-width:<?= min(400,$svgw) ?>px;">
        <?php for ($g = 0; $g <= 4; $g++): $gy = 10 + ($g * 26); ?>
        <line x1="60" y1="<?= $gy ?>" x2="<?= $svgw-10 ?>" y2="<?= $gy ?>" stroke="#E7CBCB" stroke-width="1" stroke-dasharray="4,4"/>
        <text x="55" y="<?= $gy+4 ?>" text-anchor="end" fill="#99627A" font-size="9"><?= singkat(($maxomsethari/4)*(4-$g)) ?></text>
        <?php endfor; ?>
        <?php foreach ($charthari as $ci => $ch):
          $cx  = 70 + $ci * ($svgbarw + $svggap);
          $bh  = $ch['nilai'] > 0 ? max(2, ($ch['nilai'] / $maxomsethari) * 100) : 2;
          $by  = 114 - $bh;
          $isT = $ch['tgl'] === date('Y-m-d');
          $fc  = $isT ? '#643843' : '#99627A';
          $lbl = $svgn <= 14 ? date('d/m', strtotime($ch['tgl'])) : date('d', strtotime($ch['tgl']));
        ?>
        <rect x="<?= $cx ?>" y="<?= $by ?>" width="<?= $svgbarw ?>" height="<?= $bh ?>" rx="3" fill="<?= $fc ?>">
          <title><?= date('d M Y', strtotime($ch['tgl'])) ?> — <?= rp($ch['nilai']) ?></title>
        </rect>
        <text x="<?= $cx + $svgbarw/2 ?>" y="132" text-anchor="middle" fill="<?= $fc ?>"
              font-size="<?= $svgn > 20 ? '7' : '9' ?>" font-weight="<?= $isT ? '700' : '400' ?>"><?= $lbl ?></text>
        <?php if ($ch['nilai'] > 0 && $svgbarw >= 20): ?>
        <text x="<?= $cx + $svgbarw/2 ?>" y="<?= max($by-3, 10) ?>" text-anchor="middle"
              fill="#643843" font-size="8" font-weight="600"><?= number_format($ch['nilai']/1000, 0) ?>k</text>
        <?php endif; ?>
        <?php endforeach; ?>
      </svg>
    </div>
    <?php if (array_sum(array_column($charthari,'nilai')) === 0): ?>
    <p style="color:var(--tekssamar);font-size:13px;padding:8px 0;">Belum ada transaksi dalam periode ini.</p>
    <?php endif; ?>
  </div>

  <!-- Produk Terlaris + Status Pesanan -->
  <div class="grid-dua" style="margin-bottom:18px;">
    <div class="kartu">
      <h3><i class="fa-solid fa-fire"></i> Produk Terlaris</h3>
      <?php if (empty($terlaris)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada data penjualan</p></div>
      <?php else: ?>
      <?php $medal=['emas','perak','perunggu']; ?>
      <?php foreach ($terlaris as $i => $t): ?>
      <div class="baris-produk">
        <div class="rangking-produk <?= $medal[$i]??'' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($t['nama_menu']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);"><?= rp($t['harga']) ?> · omset <?= rp($t['omset']) ?></div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;"><?= (int)$t['terjual'] ?>×</div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="kartu">
      <h3><i class="fa-solid fa-list-check"></i> Breakdown Status Pesanan</h3>
      <?php
      $slist  = ['Menunggu','Diproses','Siap Diambil','Selesai','Dibatalkan'];
      $sbadge = ['Menunggu'=>'menunggu','Diproses'=>'diproses','Siap Diambil'=>'siap','Selesai'=>'selesai','Dibatalkan'=>'dibatalkan'];
      $warna  = ['Menunggu'=>'#F59E0B','Diproses'=>'#2563EB','Siap Diambil'=>'#16A34A','Selesai'=>'#99627A','Dibatalkan'=>'#DC2626'];
      foreach ($slist as $s):
          $jml = $statuspesanan[$s] ?? 0;
          $pct = $totalpesanan > 0 ? round($jml / $totalpesanan * 100) : 0;
      ?>
      <div style="padding:7px 0;border-bottom:1px solid var(--latar);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
          <span class="badge <?= $sbadge[$s] ?>"><?= $s ?></span>
          <strong style="font-size:13px;"><?= $jml ?></strong>
        </div>
        <div class="bar-status-wrap">
          <div class="bar-status-isi" style="width:<?= $pct ?>%;background:<?= $warna[$s] ?>;"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Rating & Ulasan + Pelanggan Terbanyak -->
  <div class="grid-dua" style="margin-bottom:18px;">

    <div class="kartu">
      <h3><i class="fa-solid fa-star"></i> Rating &amp; Ulasan</h3>
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--latar);">
        <div style="font-size:42px;font-weight:900;color:var(--kedua);line-height:1;"><?= $ratarating ?: '—' ?></div>
        <div>
          <?php if ($ratarating > 0): ?>
          <div><?= bintanghtml($ratarating) ?></div>
          <?php endif; ?>
          <div style="font-size:12px;color:var(--tekssamar);margin-top:4px;"><?= $jmlrating ?> ulasan masuk</div>
        </div>
      </div>
      <?php if ($jmlrating > 0): ?>
      <?php for ($bin=5; $bin>=1; $bin--):
          $j = $distribusirating[$bin] ?? 0;
          $pct = round($j / $jmlrating * 100);
      ?>
      <div style="display:flex;align-items:center;gap:8px;margin:4px 0;">
        <span style="font-size:11px;font-weight:700;min-width:18px;text-align:right;"><?= $bin ?>★</span>
        <div class="bar-dist"><div class="bar-dist-isi" style="width:<?= $pct ?>%;"></div></div>
        <span style="font-size:11px;color:var(--tekssamar);min-width:26px;"><?= $j ?></span>
      </div>
      <?php endfor; ?>
      <?php else: ?>
      <p style="color:var(--tekssamar);font-size:13px;">Belum ada ulasan</p>
      <?php endif; ?>
      <?php if (!empty($ulasanterbaru)): ?>
      <div style="margin-top:14px;border-top:1px solid var(--latar);padding-top:12px;">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--tekssamar);margin-bottom:10px;">Ulasan Terbaru</div>
        <?php foreach ($ulasanterbaru as $ul): ?>
        <div style="margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--latar);">
          <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
            <span style="font-size:12px;font-weight:700;"><?= htmlspecialchars($ul['username']) ?></span>
            <span style="font-size:10px;color:var(--tekssamar);"><?= !empty($ul['created']) ? date('d M Y', strtotime($ul['created'])) : '' ?></span>
          </div>
          <div style="margin-bottom:4px;"><?= bintanghtml((float)$ul['rating_toko']) ?></div>
          <?php if (!empty($ul['ulasan'])): ?>
          <div style="font-size:12px;color:var(--teks);font-style:italic;line-height:1.4;">
            "<?= htmlspecialchars(mb_substr($ul['ulasan'],0,140)) ?><?= mb_strlen($ul['ulasan'])>140?'...':'' ?>"
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="kartu">
      <h3><i class="fa-solid fa-trophy"></i> Pelanggan Terbanyak Pesan</h3>
      <?php if (empty($toppelanggan)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada data pelanggan</p></div>
      <?php else: ?>
      <?php $medal=['emas','perak','perunggu']; ?>
      <?php foreach ($toppelanggan as $i => $p): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--latar);">
        <div class="rangking-produk <?= $medal[$i]??'' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['username']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);"><?= rp($p['total_belanja']) ?></div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;"><?= $p['jml_order'] ?>× pesan</div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="kartu">
      <h3><i class="fa-solid fa-wallet"></i> Pelanggan Pengeluaran Terbesar</h3>
      <?php if (empty($topmahal)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada data pelanggan</p></div>
      <?php else: ?>
      <?php $medal=['emas','perak','perunggu']; ?>
      <?php foreach ($topmahal as $i => $p): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--latar);">
        <div class="rangking-produk <?= $medal[$i]??'' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['username']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);"><?= $p['jml_order'] ?> pesanan</div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;"><?= rp($p['total_belanja']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Daftar Menu -->
  <?php if (!empty($daftarmenu)): ?>
  <div class="kartu" style="margin-bottom:18px;padding:0;overflow:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--latar);">
      <h3 style="margin:0;"><i class="fa-solid fa-utensils"></i> Daftar Menu (<?= count($daftarmenu) ?> item)</h3>
    </div>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th>Nama Menu</th>
            <th class="kanan">Harga</th>
            <th class="tengah">Status</th>
            <th class="tengah">Total Terjual</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftarmenu as $m): ?>
          <tr>
            <td style="font-weight:600;"><?= htmlspecialchars($m['nama_menu']) ?></td>
            <td class="kanan"><?= rp((float)$m['harga']) ?></td>
            <td class="tengah">
              <span class="badge <?= $m['status']==='aktif'?'selesai':'dibatalkan' ?>"><?= ucfirst($m['status']) ?></span>
            </td>
            <td class="tengah" style="font-weight:700;color:var(--utama);"><?= (int)$m['terjual'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

<?php elseif ($user['role'] === 'penjual' && !$toko && !$riwayat): ?>
<!-- ════════════════ PENJUAL TANPA TOKO ════════════════ -->

  <div class="kartu" style="margin-bottom:18px;text-align:center;padding:32px;">
    <div style="width:68px;height:68px;border-radius:50%;background:var(--latar);color:var(--utama);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;margin:0 auto 12px;"><?= $inisial ?></div>
    <div style="font-size:18px;font-weight:800;"><?= htmlspecialchars($user['username']) ?></div>
    <div style="font-size:13px;color:var(--tekssamar);margin:4px 0 8px;"><?= htmlspecialchars($user['email']) ?></div>
    <span class="badge penjual">Penjual</span>
    <div style="margin-top:10px;font-size:11px;color:var(--tekssamar);">
      <i class="fa-solid fa-calendar" style="font-size:9px;"></i>
      Bergabung <?= !empty($user['created']) ? date('d M Y, H:i', strtotime($user['created'])) : '—' ?>
      <span style="margin:0 6px;">·</span> ID #<?= $user['id_user'] ?>
      <?php if ($isDihapus && $tglBerhenti): ?>
      <div style="margin-top:4px;color:#dc2626;font-weight:700;"><i class="fa-solid fa-user-slash" style="font-size:9px;"></i> Berhenti <?= $tglBerhenti ?></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="kartu">
    <h3><i class="fa-solid fa-store"></i> Informasi Toko</h3>
    <div class="kosong" style="padding:20px;"><p>Penjual ini belum menempati slot kantin atau kantinnya sudah dikosongkan.</p></div>
  </div>

<?php elseif ($user['role'] === 'pembeli'): ?>
<!-- ════════════════ PEMBELI ════════════════ -->

  <div class="kartu" style="margin-bottom:18px;text-align:center;padding:32px;">
    <div style="width:68px;height:68px;border-radius:50%;background:var(--latar);color:var(--utama);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;margin:0 auto 12px;"><?= $inisial ?></div>
    <div style="font-size:18px;font-weight:800;"><?= htmlspecialchars($user['username']) ?></div>
    <div style="font-size:13px;color:var(--tekssamar);margin:4px 0 8px;"><?= htmlspecialchars($user['email']) ?></div>
    <span class="badge pembeli">Pembeli</span>
    <div style="margin-top:10px;font-size:11px;color:var(--tekssamar);">
      <i class="fa-solid fa-calendar" style="font-size:9px;"></i>
      Bergabung <?= !empty($user['created']) ? date('d M Y, H:i', strtotime($user['created'])) : '—' ?>
      <span style="margin:0 6px;">·</span> ID #<?= $user['id_user'] ?>
      <?php if ($isDihapus && $tglBerhenti): ?>
      <div style="margin-top:4px;color:#dc2626;font-weight:700;"><i class="fa-solid fa-user-slash" style="font-size:9px;"></i> Berhenti <?= $tglBerhenti ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid-stat" style="grid-template-columns:repeat(2,1fr);margin-bottom:18px;">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-bag-shopping"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $totalorder_pembeli ?></div><div class="label">Total Pesanan</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-coins"></i></div>
      <div class="isi-stat"><div class="nilai" style="font-size:14px;"><?= rp($totalbelanja) ?></div><div class="label">Total Belanja</div></div>
    </div>
  </div>

  <div class="kartu">
    <h3><i class="fa-solid fa-heart"></i> Toko Favorit</h3>
      <?php if (empty($tokofavorit)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada transaksi selesai</p></div>
      <?php else: ?>
      <?php $medal=['emas','perak','perunggu']; ?>
      <?php foreach ($tokofavorit as $i => $tf): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--latar);">
        <div class="rangking-produk <?= $medal[$i]??'' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;"><?= htmlspecialchars($tf['nama_toko'] ?? '—') ?></div>
          <div style="font-size:11px;color:var(--tekssamar);"><?= rp($tf['total_belanja']) ?></div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;"><?= $tf['jml_order'] ?>× pesan</div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
  </div>

<?php else: ?>
<!-- ════════════════ ADMIN ════════════════ -->

  <div class="kartu" style="margin-bottom:18px;text-align:center;padding:32px;">
    <div style="width:68px;height:68px;border-radius:50%;background:var(--infobg);color:var(--info);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;margin:0 auto 12px;"><?= $inisial ?></div>
    <div style="font-size:18px;font-weight:800;"><?= htmlspecialchars($user['username']) ?></div>
    <div style="font-size:13px;color:var(--tekssamar);margin:4px 0 8px;"><?= htmlspecialchars($user['email']) ?></div>
    <span class="badge admin">Admin Platform</span>
    <div style="margin-top:10px;font-size:11px;color:var(--tekssamar);">
      <i class="fa-solid fa-calendar" style="font-size:9px;"></i>
      Bergabung <?= !empty($user['created']) ? date('d M Y, H:i', strtotime($user['created'])) : '—' ?>
      <span style="margin:0 6px;">·</span> ID #<?= $user['id_user'] ?>
    </div>
  </div>

<?php endif; ?>

</main>
</body>
</html>
