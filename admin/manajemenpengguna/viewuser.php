<?php
/* detail pengguna untuk admin — penjual: semua data toko + kantin digabung (stat, tren omset,
   produk terlaris, rating, ulasan, daftar menu, pelanggan). pembeli: stat + toko favorit.
   fallback ke tb_riwayat_toko jika slot sudah dikosongkan penuh (migrasi riwayat dijalankan).
   statistik penjual difilter berdasarkan tanggal_mulai agar data penjual lama tidak ikut terhitung.
   print via window.print(); takprint menyembunyikan nav dan tombol aksi. */

include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

$halamansaatini = 'user';

// ambil id user dari url, redirect kembali kalau id tidak valid
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: user.php"); exit; }

// ambil data user — pakai prepared statement untuk cegah sql injection
$qu = $conn->prepare("SELECT * FROM tb_user WHERE id_user=?");
$qu->bind_param("i", $id); $qu->execute();
$user = $qu->get_result()->fetch_assoc(); $qu->close();
if (!$user) { header("Location: user.php"); exit; }

// status soft-delete user — kalau sudah dihapus, halaman ditampilkan read-only
$isDihapus   = (int)($user['deleted'] ?? 0) === 1;
$adaDelAtVu  = array_key_exists('deleted_at', $user);
$tglBerhenti = ($adaDelAtVu && !empty($user['deleted_at'])) ? date('d M Y, H:i', strtotime($user['deleted_at'])) : null;

// dua huruf pertama username, dipakai sebagai fallback avatar bila foto kosong
$inisial = strtoupper(mb_substr($user['username'], 0, 2));

// ── init variabel ──────────────────────────────────────────────────────────────
$toko = null; $riwayat = null;
$totalpesanan = $totalomset = $omsetselesai = $jmlselesai = $jmldibatalkan = $nilaidibatalkan = 0;
$ratarating   = $jmlrating  = $totalmenu    = $jmlpelanggan = 0;
$distribusirating = []; $ulasanterbaru = []; $terlaris = [];
$daftarmenu = []; $toppelanggan = []; $statuspesanan = [];
$totalorder_pembeli = $totalbelanja = 0; $tokofavorit = [];
$charthari = []; $maxomsethari = 1;
$pesanandetail = []; // array detail pesanan selesai + dibatalkan
// mode periode chart omset: custom (date range), atau preset 7/14/30 hari
$modecustom = ($_GET['hari'] ?? '') === 'custom';
if ($modecustom) {
    // mode custom — admin pilih sendiri rentang tanggal lewat form
    $dari   = (!empty($_GET['dari']))   ? date('Y-m-d', strtotime($_GET['dari']))   : date('Y-m-d', strtotime('-7 days'));
    $sampai = (!empty($_GET['sampai'])) ? date('Y-m-d', strtotime($_GET['sampai'])) : date('Y-m-d');
    if ($sampai < $dari) $sampai = $dari; // proteksi: tanggal mundur dipaksa sama
    $nhari  = (int)ceil((strtotime($sampai) - strtotime($dari)) / 86400) + 1;
} else {
    // mode preset — validasi hanya boleh 7/14/30, fallback ke 7
    $nhari  = in_array((int)($_GET['hari'] ?? 7), [7,14,30]) ? (int)($_GET['hari'] ?? 7) : 7;
    $dari   = date('Y-m-d', strtotime('-' . ($nhari - 1) . ' days'));
    $sampai = date('Y-m-d');
}

// ── PENJUAL ────────────────────────────────────────────────────────────────────
if ($user['role'] === 'penjual') {
    // cari toko aktif (slot belum dikosongkan penuh — id_user masih ada)
    $qt = $conn->prepare("SELECT * FROM tb_toko WHERE id_user=? AND deleted=0 ORDER BY id_toko DESC LIMIT 1");
    $qt->bind_param("i", $id); $qt->execute();
    $toko = $qt->get_result()->fetch_assoc(); $qt->close();

    // fallback: cari di riwayat jika slot sudah dikosongkan (migrasi riwayat sudah jalan)
    if (!$toko) {
        $cektbr = $conn->query("SHOW TABLES LIKE 'tb_riwayat_toko'");
        if ($cektbr && $cektbr->num_rows > 0) {
            $qrw = $conn->prepare("SELECT * FROM tb_riwayat_toko WHERE id_user=? ORDER BY id_riwayat DESC LIMIT 1");
            $qrw->bind_param("i", $id); $qrw->execute();
            $riwayat = $qrw->get_result()->fetch_assoc(); $qrw->close();
        }
    }

    // $it = id_toko untuk semua query statistik (dari toko aktif atau riwayat)
    $it = $toko ? (int)$toko['id_toko'] : ($riwayat ? (int)$riwayat['id_toko'] : 0);

    // isolasi data penjual: semua query pesanan/rating difilter WHERE id_penjual=$id
    // id_penjual disimpan di tb_order dan tb_rating saat transaksi dibuat — tidak perlu filter tanggal

    if ($it) {
        // total pesanan (semua status) — untuk tahu volume pesanan masuk
        $s = $conn->prepare("SELECT COUNT(*) FROM tb_order o WHERE o.id_penjual=? AND o.deleted=0");
        $s->bind_param("i",$id); $s->execute(); $totalpesanan=(int)$s->get_result()->fetch_row()[0]; $s->close();

        // omset = HANYA pesanan Selesai (uang yang benar-benar diterima penjual)
        $s = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total_harga),0) FROM tb_order o WHERE o.id_penjual=? AND o.status_order='Selesai' AND o.deleted=0");
        $s->bind_param("i",$id); $s->execute(); $r=$s->get_result()->fetch_row(); $s->close();
        $jmlselesai=(int)$r[0]; $omsetselesai=(float)$r[1];
        // $totalomset disamakan dengan $omsetselesai supaya kompatibel dengan template lama
        $totalomset = $omsetselesai;

        // nilai dibatalkan — potensi omset hilang (ditampilkan terpisah)
        $s = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total_harga),0) FROM tb_order o WHERE o.id_penjual=? AND o.status_order='Dibatalkan' AND o.deleted=0");
        $s->bind_param("i",$id); $s->execute(); $r=$s->get_result()->fetch_row(); $s->close();
        $jmldibatalkan=(int)$r[0]; $nilaidibatalkan=(float)$r[1];

        // rata-rata + jumlah rating toko — filter id_penjual supaya rating penjual lama tidak ikut
        $s = $conn->prepare("SELECT ROUND(AVG(rating_toko),1), COUNT(*) FROM tb_rating r WHERE r.id_penjual=? AND r.deleted=0");
        $s->bind_param("i",$id); $s->execute(); $r=$s->get_result()->fetch_row(); $s->close();
        $ratarating=(float)($r[0]??0); $jmlrating=(int)($r[1]??0);

        // jumlah menu aktif — filter id_toko (slot) bukan id_penjual karena menu nempel ke toko
        $s = $conn->prepare("SELECT COUNT(*) FROM tb_menu WHERE id_toko=? AND status='aktif' AND deleted=0");
        $s->bind_param("i",$it); $s->execute(); $totalmenu=(int)$s->get_result()->fetch_row()[0]; $s->close();

        // jumlah pelanggan unik (count distinct id_user yang pernah pesan ke penjual ini)
        $s = $conn->prepare("SELECT COUNT(DISTINCT o.id_user) FROM tb_order o WHERE o.id_penjual=? AND o.deleted=0");
        $s->bind_param("i",$id); $s->execute(); $jmlpelanggan=(int)$s->get_result()->fetch_row()[0]; $s->close();

        // distribusi rating per bintang (5,4,3,2,1) — untuk tabel breakdown rating
        $s = $conn->prepare("SELECT rating_toko, COUNT(*) AS jml FROM tb_rating r WHERE r.id_penjual=? AND r.deleted=0 GROUP BY rating_toko ORDER BY rating_toko DESC");
        $s->bind_param("i",$id); $s->execute(); $res=$s->get_result();
        while ($r=$res->fetch_assoc()) $distribusirating[(int)$r['rating_toko']]=(int)$r['jml'];
        $s->close();

        // ulasan terbaru beserta menu yang dipesan saat rating diberikan
        $s = $conn->prepare(
            "SELECT r.rating_toko, r.ulasan, r.created, u.username,
                    (SELECT GROUP_CONCAT(COALESCE(d.nama_menu_snapshot, m2.nama_menu) SEPARATOR ', ')
                     FROM tb_detail_order d
                     LEFT JOIN tb_menu m2 ON d.id_menu=m2.id_menu
                     WHERE d.id_order=r.id_order AND d.deleted=0) AS menu_dipesan
             FROM tb_rating r JOIN tb_user u ON r.id_user=u.id_user
             WHERE r.id_penjual=? AND r.deleted=0
             ORDER BY r.created DESC LIMIT 10"
        );
        $s->bind_param("i",$id); $s->execute(); $ulasanterbaru=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

        // produk terlaris — hanya yang benar-benar terjual (Selesai), bukan yang masih diproses
        $s = $conn->prepare(
            "SELECT m.nama_menu, m.harga, SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omset
             FROM tb_detail_order d
             JOIN tb_menu m ON d.id_menu=m.id_menu
             JOIN tb_order o ON d.id_order=o.id_order
             WHERE o.id_penjual=? AND d.deleted=0 AND o.deleted=0 AND o.status_order='Selesai'
             GROUP BY m.id_menu, m.nama_menu, m.harga
             ORDER BY terjual DESC LIMIT 5"
        );
        $s->bind_param("i",$id); $s->execute(); $terlaris=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

        // daftar menu — total terjual = jumlah pesanan Selesai (real)
        $s = $conn->prepare(
            "SELECT m.nama_menu, m.harga, m.status, m.deleted,
                    COALESCE((SELECT SUM(d2.jumlah) FROM tb_detail_order d2
                              JOIN tb_order o2 ON d2.id_order=o2.id_order
                              WHERE d2.id_menu=m.id_menu AND d2.deleted=0
                                AND o2.id_penjual=? AND o2.deleted=0 AND o2.status_order='Selesai'),0) AS terjual
             FROM tb_menu m WHERE m.id_penjual=?
             ORDER BY m.deleted ASC, (m.status='aktif') DESC, terjual DESC, m.nama_menu ASC"
        );
        $s->bind_param("ii",$id,$id); $s->execute(); $daftarmenu=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

        // top pelanggan gabungan — urut by jumlah pesanan dulu, lalu total belanja
        $s = $conn->prepare(
            "SELECT u.username, COUNT(o.id_order) AS jml_order, COALESCE(SUM(o.total_harga),0) AS total_belanja
             FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user
             WHERE o.id_penjual=? AND o.status_order='Selesai' AND o.deleted=0
             GROUP BY o.id_user, u.username
             ORDER BY jml_order DESC, total_belanja DESC LIMIT 10"
        );
        $s->bind_param("i",$id); $s->execute(); $toppelanggan=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();

        // tren omset per hari — HANYA Selesai (uang masuk per hari)
        $sqchart = $conn->prepare(
            "SELECT DATE(tanggal_order) AS tgl, COALESCE(SUM(total_harga),0) AS nilai
             FROM tb_order WHERE id_penjual=? AND deleted=0
               AND status_order='Selesai'
               AND DATE(tanggal_order) BETWEEN ? AND ?
             GROUP BY DATE(tanggal_order)"
        );
        $sqchart->bind_param("iss",$id,$dari,$sampai); $sqchart->execute();
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

        // detail pesanan selesai dan dibatalkan — satu baris per item menu
        $sqdet = $conn->prepare(
            "SELECT o.id_order, DATE_FORMAT(o.tanggal_order,'%d/%m/%Y %H:%i') AS tgl_format,
                    u.username AS pembeli, o.status_order, o.total_harga,
                    m.nama_menu, d.jumlah, d.harga_satuan, d.subtotal
             FROM tb_order o
             JOIN tb_user u  ON o.id_user=u.id_user
             LEFT JOIN tb_detail_order d ON o.id_order=d.id_order AND d.deleted=0
             LEFT JOIN tb_menu m ON d.id_menu=m.id_menu
             WHERE o.id_penjual=? AND o.status_order IN ('Selesai','Dibatalkan') AND o.deleted=0
             ORDER BY o.tanggal_order DESC, o.id_order, m.nama_menu
             LIMIT 500"
        );
        $sqdet->bind_param("i",$id); $sqdet->execute();
        $resdet = $sqdet->get_result();
        while ($rd = $resdet->fetch_assoc()) {
            $oid = $rd['id_order'];
            if (!isset($pesanandetail[$oid])) {
                $pesanandetail[$oid] = [
                    'id'      => $oid,
                    'tanggal' => $rd['tgl_format'],
                    'pembeli' => $rd['pembeli'],
                    'status'  => $rd['status_order'],
                    'total'   => (float)$rd['total_harga'],
                    'items'   => []
                ];
            }
            if ($rd['nama_menu']) {
                $pesanandetail[$oid]['items'][] = [
                    'nama'    => $rd['nama_menu'],
                    'jumlah'  => (int)$rd['jumlah'],
                    'harga'   => (float)$rd['harga_satuan'],
                    'subtotal'=> (float)$rd['subtotal']
                ];
            }
        }
        $sqdet->close();
    }

// ── PEMBELI ────────────────────────────────────────────────────────────────────
} elseif ($user['role'] === 'pembeli') {
    // total pesanan = COUNT semua status (volume), total belanja = SUM Selesai only
    $s = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND deleted=0");
    $s->bind_param("i",$id); $s->execute(); $totalorder_pembeli=(int)$s->get_result()->fetch_row()[0]; $s->close();

    $s = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_user=? AND status_order='Selesai' AND deleted=0");
    $s->bind_param("i",$id); $s->execute(); $totalbelanja=(float)$s->get_result()->fetch_row()[0]; $s->close();

    // toko favorit — pakai snapshot nama_toko + group by id_penjual supaya beda penjual
    // di slot yang sama tidak digabung. coalesce ke "Kantin ke-X" kalau snapshot null.
    $s = $conn->prepare(
        "SELECT COALESCE(o.nama_toko_snapshot, CONCAT('Kantin ke-', COALESCE(o.nomor_kantin_snapshot, o.id_toko))) AS nama_toko,
                o.id_penjual,
                COUNT(o.id_order) AS jml_order,
                COALESCE(SUM(o.total_harga),0) AS total_belanja
         FROM tb_order o
         WHERE o.id_user=? AND o.status_order='Selesai' AND o.deleted=0
         GROUP BY o.id_penjual, nama_toko
         ORDER BY jml_order DESC LIMIT 5"
    );
    $s->bind_param("i",$id); $s->execute(); $tokofavorit=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
}

// flash message — pesan sementara setelah redirect (mis. sukses toggle status toko)
// dihapus setelah dibaca supaya tidak muncul lagi saat halaman direfresh
$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']);
}

// helper vars untuk hero penjual — bisa dari toko aktif atau riwayat
$tokosumber  = $toko ?: ($riwayat ?: []);
$nomorkantin = (int)($tokosumber['nomor_kantin'] ?? 0);
$namatoko    = !empty($toko) ? ($toko['nama_toko'] ?? '') : (!empty($riwayat) ? ($riwayat['nama_toko'] ?? '') : '');
$statustoko  = !empty($toko) ? ($toko['status_toko'] ?? 'tutup') : 'tutup';
$idtoko      = (int)($tokosumber['id_toko'] ?? 0);

$fotoProfile = '';
if (!empty($tokosumber['foto_toko'])) {
    $fotoProfile = $tokosumber['foto_toko'];
} elseif (!empty($user['foto'])) {
    $fotoProfile = $user['foto'];
}
// border-radius kotak (8px) bukan bulat agar sinkron dengan desain manajemen pengguna.
// fallback role-aware: penjual → profilwarung.png; pembeli/admin → inisial 2 huruf.
if ($fotoProfile && file_exists(__DIR__ . '/../../2. aset/profil/' . $fotoProfile)) {
    $avatarHtml = '<img src="../../2. aset/profil/' . htmlspecialchars($fotoProfile) . '" style="width:100%;height:100%;object-fit:cover;border-radius:8px;" alt="Foto">';
} elseif (($user['role'] ?? '') === 'penjual') {
    // gambar silhouette warung — PNG transparan dijadikan putih dengan filter
    $avatarHtml = '<img src="../../2. aset/profil/profilwarung.png" alt="Warung" style="width:70%;height:70%;object-fit:contain;filter:brightness(0) invert(1);opacity:.85;">';
} else {
    $avatarHtml = $inisial;
}

// dimensi SVG chart — identik laporan.php
$svgn    = count($charthari);
$svgw    = max(700, $svgn * 30);
$svgbarw = max(8, min(40, (int)(($svgw - 80) / max(1, $svgn)) - 4));
$svggap  = max(4, (int)(($svgw - 80 - $svgn * $svgbarw) / max(1, $svgn - 1)));

// format angka jadi rupiah — contoh: 15000 -> "Rp 15.000"
function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
// singkat nilai besar supaya muat di kartu statistik — contoh: 1.500.000 -> "Rp 1,5 Jt", 2.000.000.000 -> "Rp 2 M"
function singkat(float $n): string {
    if ($n >= 1_000_000_000) { $v=$n/1_000_000_000; return 'Rp '.rtrim(rtrim(number_format($v,1,',','.'),'0'),',').' M'; }
    if ($n >= 1_000_000)     { $v=$n/1_000_000;     return 'Rp '.rtrim(rtrim(number_format($v,1,',','.'),'0'),',').' Jt'; }
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// render 5 bintang — yang aktif kuning (#F59E0B), sisanya abu-abu (#D1D5DB)
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
/* judul section: flex row supaya h3 di kiri & tombol cetak di kanan rapi */
.seksi-judul { display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
.seksi-judul h3 { margin:0; }
@media print {
  .takprint { display:none !important; }
  .cetakjudul { display:block !important; margin-bottom:14px; }
  @page { size:A4; margin:12mm; }
  .kartu { box-shadow:none !important; border:1px solid #ddd !important; page-break-inside:avoid; }
  .grid-stat { grid-template-columns:repeat(3,1fr) !important; }
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
      <button onclick="eksporXlsSemua('detail_pengguna')" class="tombolringan" style="background:var(--sukses);color:white;border-color:var(--sukses);">
        <i class="fa-solid fa-file-csv"></i> Cetak Semua
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

      <!-- info penjual -->
      <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:220px;padding-right:20px;">
        <!-- avatar kotak (border-radius:8px) agar sinkron dengan halaman daftar pengguna -->
        <div style="width:68px;height:68px;border-radius:8px;background:var(--latar);color:var(--utama);
                    display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;
                    flex-shrink:0;overflow:hidden;"><?= $avatarHtml ?></div>
        <div>
          <div style="font-size:18px;font-weight:800;color:var(--teks);"><?= htmlspecialchars($user['username']) ?></div>
          <div style="font-size:13px;color:var(--tekssamar);"><?= htmlspecialchars($user['email']) ?></div>
          <?php if (!empty($user['no_telepon'] ?? '')): ?>
          <div style="font-size:12px;color:var(--tekssamar);margin-top:2px;"><i class="fa-solid fa-phone" style="font-size:10px;"></i> <?= htmlspecialchars($user['no_telepon']) ?></div>
          <?php endif; ?>
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

      <!-- divider -->
      <div style="width:1.5px;background:var(--garis);margin:0 20px;align-self:stretch;min-height:60px;"></div>

      <!-- info toko/kantin -->
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
            <!-- buka modal konfirmasi dulu sebelum ubah status toko (tanpa js, css :target) -->
            <a href="#konfirm-toggletoko" class="badge <?= $statustoko==='buka'?'buka':'tutup' ?>"
               style="text-decoration:none;cursor:pointer;" title="Klik untuk ubah status">
              <?= $statustoko==='buka'?'Buka':'Tutup' ?>
              <i class="fa-solid fa-arrows-rotate takprint" style="font-size:9px;"></i>
            </a>
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

  <!-- stat cards — omset HANYA dari pesanan Selesai (uang yang benar-benar diterima),
       sedangkan jumlah & nilai dibatalkan ditampilkan terpisah untuk info lengkap -->
  <div class="seksi-laporan" id="seksi-stat" style="margin-bottom:18px;">
  <div class="takprint" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
    <small style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--tekssamar);">Statistik Utama</small>
  </div>
  <!-- urutan stat cards sinkron dengan laporan platform per-kantin Group 2:
       Total Pesanan, Rating Toko, Omset Selesai, Nilai Dibatalkan, Menu Aktif, Pelanggan -->
  <div class="grid-stat" style="grid-template-columns:repeat(3,1fr);">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-receipt"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $totalpesanan ?></div><div class="label">Total Pesanan</div><div class="sub">Semua status</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:var(--suksebg);color:var(--sukses);"><i class="fa-solid fa-coins"></i></div>
      <div class="isi-stat"><div class="nilai" style="font-size:13px;color:var(--sukses);"><?= rp($omsetselesai) ?></div><div class="label">Omset Selesai</div><div class="sub"><?= $jmlselesai ?> pesanan selesai</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:#fee2e2;color:#dc2626;"><i class="fa-solid fa-circle-xmark"></i></div>
      <div class="isi-stat"><div class="nilai" style="font-size:13px;color:#dc2626;"><?= rp($nilaidibatalkan) ?></div><div class="label">Nilai Dibatalkan</div><div class="sub"><?= $jmldibatalkan ?> pesanan dibatalkan</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:#fffbeb;color:#D97706;"><i class="fa-solid fa-star"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $ratarating ?: '—' ?></div><div class="label">Rating Toko</div><div class="sub"><?= $jmlrating ?> ulasan</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-utensils"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $totalmenu ?></div><div class="label">Menu Aktif</div><div class="sub">Bisa dipesan</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-users"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $jmlpelanggan ?></div><div class="label">Pembeli Berbeda</div><div class="sub">Pelanggan unik</div></div>
    </div>
  </div>
  </div><!-- /seksi-stat -->

  <!-- ── TREN OMSET (SVG chart) ───────────────────────────────────────────── -->
  <div class="kartu seksi-laporan" id="seksi-chart" style="margin-bottom:18px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
      <h3 style="margin:0;"><i class="fa-solid fa-chart-bar"></i> Omset — <?= $modecustom ? date('d M Y',strtotime($dari)).' — '.date('d M Y',strtotime($sampai)) : $nhari.' Hari Terakhir' ?></h3>
      <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;" class="takprint">
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
    <div class="area-chart">
      <svg viewBox="0 0 <?= $svgw ?> 210" xmlns="http://www.w3.org/2000/svg" style="min-width:<?= min(700,$svgw) ?>px;">
        <?php for ($g = 0; $g <= 4; $g++): $gy = 20 + ($g * 40); ?>
        <line x1="60" y1="<?= $gy ?>" x2="<?= $svgw-10 ?>" y2="<?= $gy ?>" stroke="#E7CBCB" stroke-width="1" stroke-dasharray="4,4"/>
        <text x="55" y="<?= $gy+4 ?>" text-anchor="end" fill="#99627A" font-size="9"><?= singkat(($maxomsethari/4)*(4-$g)) ?></text>
        <?php endfor; ?>
        <?php foreach ($charthari as $ci => $ch):
          $cx  = 70 + $ci * ($svgbarw + $svggap);
          $bh  = $ch['nilai'] > 0 ? ($ch['nilai'] / $maxomsethari) * 160 : 2;
          $by  = 180 - $bh;
          $isT = $ch['tgl'] === date('Y-m-d');
          $fc  = $isT ? '#643843' : '#99627A';
          $lbl = date('d/m', strtotime($ch['tgl']));
        ?>
        <rect x="<?= $cx ?>" y="<?= $by ?>" width="<?= $svgbarw ?>" height="<?= $bh ?>" rx="3" fill="<?= $fc ?>">
          <title><?= date('d M Y', strtotime($ch['tgl'])) ?> — <?= rp($ch['nilai']) ?></title>
        </rect>
        <text x="<?= $cx + $svgbarw/2 ?>" y="200" text-anchor="middle" fill="<?= $fc ?>"
              font-size="<?= $svgn > 20 ? '7' : '9' ?>" font-weight="<?= $isT ? '700' : '400' ?>"><?= $lbl ?></text>
        <?php if ($ch['nilai'] > 0 && $svgbarw >= 20): ?>
        <text x="<?= $cx + $svgbarw/2 ?>" y="<?= max($by-4, 14) ?>" text-anchor="middle"
              fill="#643843" font-size="8" font-weight="600"><?php $_n=$ch['nilai']; echo $_n>=1000000 ? number_format($_n/1000000,1).'Jt' : ($_n>=1000 ? number_format($_n/1000,0).'k' : number_format($_n,0)); ?></text>
        <?php endif; ?>
        <?php endforeach; ?>
      </svg>
    </div>
    <?php if (array_sum(array_column($charthari,'nilai')) === 0): ?>
    <p style="color:var(--tekssamar);font-size:13px;padding:8px 0;">Belum ada transaksi dalam periode ini.</p>
    <?php endif; ?>
  </div>

  <!-- ── TOP PELANGGAN (gabung) — tabel rank, username, jml, total, rata-rata ── -->
  <div class="kartu seksi-laporan" id="seksi-pelanggan" style="margin-bottom:18px;">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-trophy"></i> Top Pelanggan</h3>
      <button onclick="eksporXlsSeksi('seksi-pelanggan','top_pelanggan')" class="tombolkecil takprint" style="background:var(--sukses);color:white;"><i class="fa-solid fa-file-csv"></i> Cetak</button>
    </div>
    <?php if (empty($toppelanggan)): ?>
    <div class="kosong" style="padding:24px;"><p>Belum ada data pelanggan</p></div>
    <?php else: ?>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th class="tengah" style="width:50px;">Rank</th>
            <th>Username Pembeli</th>
            <th class="tengah" style="width:120px;">Jumlah Pesanan</th>
            <th class="kanan" style="width:140px;">Total Belanja</th>
            <th class="kanan" style="width:140px;">Rata-rata / Pesanan</th>
          </tr>
        </thead>
        <tbody>
          <?php $medal=['emas','perak','perunggu']; ?>
          <?php foreach ($toppelanggan as $i => $p):
              $rpeso = $p['jml_order'] > 0 ? $p['total_belanja'] / $p['jml_order'] : 0;
          ?>
          <tr>
            <td class="tengah"><div class="rangking-produk <?= $medal[$i]??'' ?>" style="display:inline-block;">#<?= $i+1 ?></div></td>
            <td style="font-weight:700;"><?= htmlspecialchars($p['username']) ?></td>
            <td class="tengah" style="font-weight:700;color:var(--utama);"><?= $p['jml_order'] ?>× pesan</td>
            <td class="kanan" style="font-weight:700;color:var(--sukses);"><?= rp($p['total_belanja']) ?></td>
            <td class="kanan"><?= rp($rpeso) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── RATING & ULASAN — standalone section dengan tabel CSV-exportable ───── -->
  <div class="kartu seksi-laporan" id="seksi-rating" style="margin-bottom:18px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
      <h3 style="margin:0;"><i class="fa-solid fa-star"></i> Rating &amp; Ulasan</h3>
      <button onclick="eksporXlsSeksi('seksi-rating','rating_ulasan')" class="tombolkecil takprint" style="background:var(--sukses);color:white;"><i class="fa-solid fa-file-csv"></i> Cetak</button>
    </div>
    <!-- ringkasan rata-rata: angka besar + bintang + jumlah ulasan (sederhana) -->
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--latar);">
      <div style="font-size:42px;font-weight:900;color:var(--kedua);line-height:1;"><?= $ratarating ?: '—' ?></div>
      <div>
        <?php if ($ratarating > 0): ?><div style="margin-bottom:3px;"><?= bintanghtml($ratarating) ?></div><?php endif; ?>
        <div style="font-size:12px;color:var(--tekssamar);"><?= $jmlrating ?> ulasan masuk</div>
      </div>
    </div>

    <!-- distribusi rating per bintang (tabel) -->
    <?php if ($jmlrating > 0): ?>
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--tekssamar);margin-bottom:8px;">Distribusi Rating</div>
    <div class="tabel-wrapper" style="margin-bottom:14px;">
      <table>
        <thead>
          <tr><th style="width:80px;">Bintang</th><th class="tengah" style="width:140px;">Jumlah Ulasan</th><th class="kanan">Persentase</th></tr>
        </thead>
        <tbody>
          <?php for ($bin=5;$bin>=1;$bin--):
            $j = $distribusirating[$bin] ?? 0;
            $pct = $jmlrating > 0 ? round($j / $jmlrating * 100, 1) : 0;
          ?>
          <tr>
            <td style="font-weight:700;"><?= $bin ?> bintang</td>
            <td class="tengah"><?= $j ?> ulasan</td>
            <td class="kanan"><?= $pct ?>%</td>
          </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- daftar ulasan beserta menu yang dipesan saat rating diberikan -->
    <?php if (!empty($ulasanterbaru)): ?>
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--tekssamar);margin-bottom:8px;">Daftar Ulasan</div>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th style="width:120px;">Pembeli</th>
            <th class="tengah" style="width:70px;">Rating</th>
            <th style="width:180px;">Menu Dipesan</th>
            <th>Ulasan</th>
            <th class="tengah" style="width:100px;">Tanggal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ulasanterbaru as $ul): ?>
          <tr>
            <td style="font-weight:700;"><?= htmlspecialchars($ul['username']) ?></td>
            <td class="tengah" style="font-weight:700;color:#D97706;"><?= (int)$ul['rating_toko'] ?>★</td>
            <td style="font-size:12px;"><?= htmlspecialchars($ul['menu_dipesan'] ?? '—') ?></td>
            <td style="font-size:12px;font-style:italic;"><?= !empty($ul['ulasan']) ? '"' . htmlspecialchars($ul['ulasan']) . '"' : '—' ?></td>
            <td class="tengah" style="font-size:11px;color:var(--tekssamar);"><?= !empty($ul['created']) ? date('d M Y', strtotime($ul['created'])) : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── PRODUK TERLARIS — tabel rank, nama menu, harga, terjual, omset ───── -->
  <div class="kartu seksi-laporan" id="seksi-terlaris" style="margin-bottom:18px;">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-fire"></i> Produk Terlaris</h3>
      <button onclick="eksporXlsSeksi('seksi-terlaris','produk_terlaris')" class="tombolkecil takprint" style="background:var(--sukses);color:white;"><i class="fa-solid fa-file-csv"></i> Cetak</button>
    </div>
    <?php if (empty($terlaris)): ?>
    <div class="kosong" style="padding:24px;"><p>Belum ada data penjualan</p></div>
    <?php else: ?>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th class="tengah" style="width:50px;">Rank</th>
            <th>Nama Menu</th>
            <th class="kanan" style="width:110px;">Harga</th>
            <th class="tengah" style="width:90px;">Terjual</th>
            <th class="kanan" style="width:140px;">Total Omset</th>
          </tr>
        </thead>
        <tbody>
          <?php $medal=['emas','perak','perunggu']; ?>
          <?php foreach ($terlaris as $i => $t): ?>
          <tr>
            <td class="tengah"><div class="rangking-produk <?= $medal[$i]??'' ?>" style="display:inline-block;">#<?= $i+1 ?></div></td>
            <td style="font-weight:700;"><?= htmlspecialchars($t['nama_menu']) ?></td>
            <td class="kanan"><?= rp((float)$t['harga']) ?></td>
            <td class="tengah" style="font-weight:700;color:var(--utama);"><?= (int)$t['terjual'] ?>×</td>
            <td class="kanan" style="font-weight:700;color:var(--sukses);"><?= rp((float)$t['omset']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── DAFTAR MENU — tabel semua menu termasuk yang dihapus ─────────────── -->
  <?php if (!empty($daftarmenu)): ?>
  <div class="kartu seksi-laporan" id="seksi-menu" style="margin-bottom:18px;">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-utensils"></i> Daftar Menu (<?= count($daftarmenu) ?> item)</h3>
      <button onclick="eksporXlsSeksi('seksi-menu','daftar_menu')" class="tombolkecil takprint" style="background:var(--sukses);color:white;"><i class="fa-solid fa-file-csv"></i> Cetak</button>
    </div>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th>Nama Menu</th>
            <th class="kanan" style="width:110px;">Harga</th>
            <th class="tengah" style="width:100px;">Status</th>
            <th class="tengah" style="width:130px;">Total Terjual</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftarmenu as $m): ?>
          <tr style="<?= $m['deleted'] ? 'opacity:.5;' : '' ?>">
            <td style="font-weight:600;">
              <?= htmlspecialchars($m['nama_menu']) ?>
              <?php if ($m['deleted']): ?>
              <span style="font-size:10px;color:#dc2626;font-weight:400;margin-left:4px;">(dihapus)</span>
              <?php endif; ?>
            </td>
            <td class="kanan"><?= rp((float)$m['harga']) ?></td>
            <td class="tengah">
              <span class="badge <?= $m['deleted'] ? 'dibatalkan' : ($m['status']==='aktif'?'selesai':'dibatalkan') ?>">
                <?= $m['deleted'] ? 'Dihapus' : ucfirst($m['status']) ?>
              </span>
            </td>
            <td class="tengah" style="font-weight:700;color:var(--utama);"><?= (int)$m['terjual'] ?>×</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── DETAIL PESANAN SELESAI & DIBATALKAN ──────────────────────────────── -->
  <div class="kartu seksi-laporan" id="seksi-detail" style="margin-bottom:18px;">
    <div class="seksi-judul">
      <h3 style="margin:0;"><i class="fa-solid fa-list-check"></i> Detail Pesanan Selesai &amp; Dibatalkan</h3>
      <div style="display:flex;gap:6px;align-items:center;">
        <span style="font-size:11px;color:var(--tekssamar);" class="takprint"><?= count($pesanandetail) ?> pesanan</span>
        <button onclick="eksporXlsSeksi('seksi-detail','detail_pesanan')" class="tombolkecil takprint" style="background:var(--sukses);color:white;"><i class="fa-solid fa-file-csv"></i> Cetak</button>
      </div>
    </div>
    <?php if (empty($pesanandetail)): ?>
    <div class="kosong" style="padding:24px;"><p>Belum ada pesanan selesai atau dibatalkan</p></div>
    <?php else: ?>
    <div class="tabel-wrapper">
      <table style="min-width:680px;">
        <thead>
          <tr>
            <th class="tengah" style="width:40px;">ID</th>
            <th style="width:120px;">Tanggal</th>
            <th>Pembeli</th>
            <th class="tengah" style="width:80px;">Status</th>
            <th>Rincian Menu</th>
            <th class="kanan" style="width:110px;">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pesanandetail as $po): ?>
          <tr>
            <td class="tengah" style="font-weight:700;color:var(--tekssamar);font-size:11px;">#<?= $po['id'] ?></td>
            <td style="font-size:11px;color:var(--tekssamar);white-space:nowrap;"><?= $po['tanggal'] ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($po['pembeli']) ?></td>
            <td class="tengah">
              <span class="badge <?= $po['status']==='Selesai'?'selesai':'dibatalkan' ?>"><?= $po['status'] ?></span>
            </td>
            <td style="font-size:12px;">
              <?php if (empty($po['items'])): ?>
              <span style="color:var(--tekssamar);font-style:italic;">—</span>
              <?php else: ?>
              <?php foreach ($po['items'] as $item): ?>
              <div style="line-height:1.6;">
                <strong><?= htmlspecialchars($item['nama']) ?></strong>
                <span style="color:var(--tekssamar);">×<?= $item['jumlah'] ?></span>
                <span style="color:var(--tekssamar);">@ <?= rp($item['harga']) ?></span>
                = <span style="color:var(--utama);font-weight:700;"><?= rp($item['subtotal']) ?></span>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </td>
            <td class="kanan" style="font-weight:800;color:var(--sukses);"><?= rp($po['total']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="5"><strong>TOTAL SELESAI</strong></td>
            <td class="kanan" style="color:var(--sukses);font-weight:800;"><?= rp($omsetselesai) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>
  </div>

<?php elseif ($user['role'] === 'penjual' && !$toko && !$riwayat): ?>
<!-- ════════════════ PENJUAL TANPA TOKO ════════════════ -->

  <div class="kartu" style="margin-bottom:18px;text-align:center;padding:32px;">
    <!-- avatar kotak 8px agar sinkron dengan halaman daftar pengguna -->
    <div style="width:68px;height:68px;border-radius:8px;background:var(--latar);color:var(--utama);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;margin:0 auto 12px;overflow:hidden;"><?= $avatarHtml ?></div>
    <div style="font-size:18px;font-weight:800;"><?= htmlspecialchars($user['username']) ?></div>
    <div style="font-size:13px;color:var(--tekssamar);margin:4px 0 8px;"><?= htmlspecialchars($user['email']) ?></div>
    <?php if (!empty($user['no_telepon'] ?? '')): ?>
    <div style="font-size:12px;color:var(--tekssamar);margin:-4px 0 8px;"><i class="fa-solid fa-phone" style="font-size:10px;"></i> <?= htmlspecialchars($user['no_telepon']) ?></div>
    <?php endif; ?>
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
<!-- section pembeli: hero kartu identitas + stat ringkas (total pesanan & total belanja) + toko favorit -->

  <div class="kartu" style="margin-bottom:18px;text-align:center;padding:32px;">
    <!-- avatar kotak 8px agar sinkron dengan halaman daftar pengguna -->
    <div style="width:68px;height:68px;border-radius:8px;background:var(--latar);color:var(--utama);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;margin:0 auto 12px;overflow:hidden;"><?= $avatarHtml ?></div>
    <div style="font-size:18px;font-weight:800;"><?= htmlspecialchars($user['username']) ?></div>
    <div style="font-size:13px;color:var(--tekssamar);margin:4px 0 8px;"><?= htmlspecialchars($user['email']) ?></div>
    <?php if (!empty($user['no_telepon'] ?? '')): ?>
    <div style="font-size:12px;color:var(--tekssamar);margin:-4px 0 8px;"><i class="fa-solid fa-phone" style="font-size:10px;"></i> <?= htmlspecialchars($user['no_telepon']) ?></div>
    <?php endif; ?>
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

  <!-- daftar toko favorit pembeli — urut berdasarkan jumlah pesanan selesai terbanyak -->
  <div class="kartu">
    <h3><i class="fa-solid fa-heart"></i> Toko Favorit</h3>
      <?php if (empty($tokofavorit)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada transaksi selesai</p></div>
      <?php else: ?>
      <?php $medal=['emas','perak','perunggu']; // medali 3 besar — sisanya tanpa kelas ?>
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
<!-- section admin platform: tampilan minimal, hanya kartu identitas (admin tidak punya statistik transaksi) -->

  <div class="kartu" style="margin-bottom:18px;text-align:center;padding:32px;">
    <!-- avatar kotak 8px agar sinkron -->
    <div style="width:68px;height:68px;border-radius:8px;background:var(--infobg);color:var(--info);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;margin:0 auto 12px;overflow:hidden;"><?= $avatarHtml ?></div>
    <div style="font-size:18px;font-weight:800;"><?= htmlspecialchars($user['username']) ?></div>
    <div style="font-size:13px;color:var(--tekssamar);margin:4px 0 8px;"><?= htmlspecialchars($user['email']) ?></div>
    <?php if (!empty($user['no_telepon'] ?? '')): ?>
    <div style="font-size:12px;color:var(--tekssamar);margin:-4px 0 8px;"><i class="fa-solid fa-phone" style="font-size:10px;"></i> <?= htmlspecialchars($user['no_telepon']) ?></div>
    <?php endif; ?>
    <span class="badge admin">Admin Platform</span>
    <div style="margin-top:10px;font-size:11px;color:var(--tekssamar);">
      <i class="fa-solid fa-calendar" style="font-size:9px;"></i>
      Bergabung <?= !empty($user['created']) ? date('d M Y, H:i', strtotime($user['created'])) : '—' ?>
      <span style="margin:0 6px;">·</span> ID #<?= $user['id_user'] ?>
    </div>
  </div>

<?php endif; ?>

</main>

<!-- modal konfirmasi buka/tutup toko — muncul saat url berisi #konfirm-toggletoko -->
<?php if (!$isDihapus && $toko):
  $akanTutupToko = ($statustoko === 'buka');
?>
<div class="modaloverlay" id="konfirm-toggletoko">
  <a href="#" class="penutup-modal"></a>
  <div class="isimodal" style="text-align:center;">
    <div style="font-size:42px;color:var(--<?= $akanTutupToko ? 'gagal' : 'sukses' ?>);margin-bottom:10px;">
      <i class="fa-solid fa-<?= $akanTutupToko ? 'store-slash' : 'store' ?>"></i>
    </div>
    <div style="font-size:17px;font-weight:800;color:var(--utama);margin-bottom:8px;">
      <?= $akanTutupToko ? 'Tutup Toko Ini?' : 'Buka Toko Ini?' ?>
    </div>
    <div style="font-size:13px;color:var(--tekssamar);margin-bottom:20px;">
      Toko <strong><?= htmlspecialchars($namatoko ?: 'ini') ?></strong>
      akan <?= $akanTutupToko ? 'ditutup — pembeli tidak bisa memesan sampai dibuka lagi.' : 'dibuka kembali dan menu bisa dipesan pembeli.' ?>
    </div>
    <!-- form konfirmasi: kirim POST ke prosestoggletoko.php (kembali ke halaman detail ini) -->
    <form method="POST" action="../manajementoko/prosestoggletoko.php">
      <input type="hidden" name="id_toko"     value="<?= (int)$toko['id_toko'] ?>">
      <input type="hidden" name="id_user_ref" value="<?= (int)$user['id_user'] ?>">
      <button type="submit" class="tombolutama blok"
              style="margin-bottom:10px;background:var(--<?= $akanTutupToko ? 'gagal' : 'sukses' ?>);border-color:var(--<?= $akanTutupToko ? 'gagal' : 'sukses' ?>);">
        <i class="fa-solid fa-<?= $akanTutupToko ? 'store-slash' : 'store' ?>"></i>
        <?= $akanTutupToko ? 'Ya, Tutup Toko' : 'Ya, Buka Toko' ?>
      </button>
    </form>
    <a href="#" class="tombolringan blok">Batal</a>
  </div>
</div>
<?php endif; ?>

<script>
/* ekspor XLS (HTML-in-Excel) — tabel bergaris dengan identitas
   trik: bikin file html dengan mime application/vnd.ms-excel supaya excel mau buka langsung. */

// metadata identitas yang dicetak di header tabel ekspor — diisi dari PHP saat render
var IDENTITAS = {
  judul:   'Detail Pengguna',
  kantin:  <?= json_encode(($nomorkantin > 0 ? 'Kantin ke-' . $nomorkantin : '—') . ($namatoko ? ' — ' . $namatoko : '')) ?>,
  penjual: <?= json_encode($user['username'] ?? '') ?>,
  periode: <?= json_encode($modecustom ? date('d M Y', strtotime($dari)) . ' – ' . date('d M Y', strtotime($sampai)) : $nhari . ' Hari Terakhir') ?>,
};

// bangun tabel header identitas (judul + kantin + penjual + periode + tanggal cetak) untuk file ekspor
function buildIdentitasHtml(judulSection) {
  var tgl = new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});
  var cssLabel = 'border:1px solid #999;padding:6pt 10pt;background:#F8EBF1;font-weight:bold;width:160px;';
  var cssNilai = 'border:1px solid #999;padding:6pt 10pt;';
  var html = '<table style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:11pt;margin-bottom:10pt;width:100%;">';
  html += '<tr><td colspan="2" style="background:#643843;color:white;font-weight:bold;font-size:14pt;text-align:center;padding:10pt;border:1px solid #444;">jajankita &mdash; ' + IDENTITAS.judul + '</td></tr>';
  if (judulSection)        html += '<tr><td style="'+cssLabel+'">Section</td><td style="'+cssNilai+'">' + judulSection + '</td></tr>';
  if (IDENTITAS.kantin)    html += '<tr><td style="'+cssLabel+'">Kantin/Toko</td><td style="'+cssNilai+'">' + IDENTITAS.kantin + '</td></tr>';
  if (IDENTITAS.penjual)   html += '<tr><td style="'+cssLabel+'">Penjual/User</td><td style="'+cssNilai+'">' + IDENTITAS.penjual + '</td></tr>';
  if (IDENTITAS.periode)   html += '<tr><td style="'+cssLabel+'">Periode</td><td style="'+cssNilai+'">' + IDENTITAS.periode + '</td></tr>';
  html += '<tr><td style="'+cssLabel+'">Tanggal Cetak</td><td style="'+cssNilai+'">' + tgl + '</td></tr>';
  html += '</table>';
  return html;
}

// kloning tabel HTML lalu sisipkan inline style (border, padding, warna) — supaya tampil rapi di Excel
// inline style penting: Excel tidak baca CSS file eksternal, jadi semua styling harus dilampir per-elemen
function tableToBorderedHtml(table) {
  var clone = table.cloneNode(true);
  clone.setAttribute('border', '1');
  clone.setAttribute('cellpadding', '6');
  clone.setAttribute('cellspacing', '0');
  clone.setAttribute('style', 'border-collapse:collapse;font-family:Arial,sans-serif;font-size:11pt;width:100%;margin-bottom:8pt;');
  // styling th: header berwarna pink-tua sesuai tema utama
  clone.querySelectorAll('th').forEach(function(th){
    th.setAttribute('style', 'background:#643843;color:white;border:1px solid #3d2230;padding:8pt 10pt;text-align:left;font-weight:bold;');
  });
  // styling tbody: baris ganjil/genap beda warna (zebra) untuk keterbacaan
  clone.querySelectorAll('tbody tr').forEach(function(tr, i){
    var bg = i % 2 === 1 ? 'background:#FAF6F8;' : '';
    tr.querySelectorAll('td').forEach(function(td){
      td.setAttribute('style', 'border:1px solid #c8c8c8;padding:6pt 10pt;vertical-align:top;' + bg);
    });
  });
  // styling tfoot: baris total dengan background highlight
  clone.querySelectorAll('tfoot td').forEach(function(td){
    td.setAttribute('style', 'border:1px solid #999;padding:7pt 10pt;background:#F8EBF1;font-weight:bold;');
  });
  // buang ikon font-awesome — supaya tidak muncul kotak kosong di Excel
  clone.querySelectorAll('i').forEach(function(ic){ ic.remove(); });
  return clone.outerHTML;
}

// rakit dokumen html lengkap, bungkus jadi blob, lalu trigger download via link sementara
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

// ekspor satu section saja (dipanggil tombol "Cetak" di tiap kartu)
// idSection = id elemen kartu (mis. 'seksi-pelanggan'), namafile = nama file output tanpa ekstensi
function eksporXlsSeksi(idSection, namafile) {
  var section = document.getElementById(idSection);
  if (!section) return;
  var tables = section.querySelectorAll('table');
  if (!tables.length) { alert('Tidak ada tabel data di section ini.'); return; }
  var judul = section.querySelector('h3') ? section.querySelector('h3').innerText.trim() : '';
  var html = buildIdentitasHtml(judul);
  tables.forEach(function(t){ html += tableToBorderedHtml(t) + '<br>'; });
  unduhXls(html, namafile);
}

// ekspor semua section sekaligus (dipanggil tombol "Cetak Semua" di header halaman)
// loop semua .seksi-laporan, sisipkan judul section di atas tiap tabel
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
