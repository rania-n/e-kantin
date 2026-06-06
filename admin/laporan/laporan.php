<?php
/* laporan platform — filter: periode (7/14/30/custom) + kantin (semua atau satu).
   mode semua  → data platform-wide (label "Omset Platform").
   mode kantin → data satu toko + menu & ulasan (label "Omset Kantin ke-X").
   tombol cetak: window.print() (seluruh halaman). cetak per-kantin: cetakKantin(n).
   tombol CSV per section: eksporXlsSeksi(id, namafile) — download tabel data ke .csv. */

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

// ── STATISTIK UTAMA ────────────────────────────────────────────────────────────
$qr1 = $conn->prepare("SELECT COUNT(*) FROM tb_order o WHERE DATE(o.tanggal_order) BETWEEN ? AND ?{$tokoW} AND o.deleted=0");
$qr1->bind_param("ss", $tglmulai, $tgljin); $qr1->execute();
$totalorder = (int)$qr1->get_result()->fetch_row()[0]; $qr1->close();

// total omset = HANYA pesanan Selesai (uang yang benar-benar masuk)
$qr2 = $conn->prepare("SELECT COALESCE(SUM(o.total_harga),0) FROM tb_order o WHERE DATE(o.tanggal_order) BETWEEN ? AND ? AND o.status_order='Selesai'{$tokoW} AND o.deleted=0");
$qr2->bind_param("ss", $tglmulai, $tgljin); $qr2->execute();
$totalomset = (float)$qr2->get_result()->fetch_row()[0]; $qr2->close();

// nilai dibatalkan = potensi omset hilang (dipisahkan supaya tidak masuk hitungan utama)
$qrb = $conn->prepare("SELECT COALESCE(SUM(o.total_harga),0) FROM tb_order o WHERE DATE(o.tanggal_order) BETWEEN ? AND ? AND o.status_order='Dibatalkan'{$tokoW} AND o.deleted=0");
$qrb->bind_param("ss", $tglmulai, $tgljin); $qrb->execute();
$totaldibatalkan = (float)$qrb->get_result()->fetch_row()[0]; $qrb->close();

$qstat = $conn->prepare("SELECT status_order, COUNT(*) AS jml FROM tb_order o WHERE DATE(o.tanggal_order) BETWEEN ? AND ?{$tokoW} AND o.deleted=0 GROUP BY status_order");
$qstat->bind_param("ss", $tglmulai, $tgljin); $qstat->execute();
$statpesanan = []; $res = $qstat->get_result();
while ($rs = $res->fetch_assoc()) $statpesanan[$rs['status_order']] = (int)$rs['jml'];
$qstat->close();

if ($modeSemua) {
    $qr3 = $conn->prepare("SELECT COUNT(*) FROM tb_user WHERE DATE(created) BETWEEN ? AND ? AND deleted=0");
    $qr3->bind_param("ss", $tglmulai, $tgljin); $qr3->execute();
    $userbarujml = (int)$qr3->get_result()->fetch_row()[0]; $qr3->close();
} else {
    $userbarujml = 0;
}

// stat per-kantin tambahan: menu aktif & pelanggan unik (cuma mode per-kantin)
$jmlmenuaktif = 0; $jmlpelangganunik = 0;
if (!$modeSemua && $idpenjualterpilih > 0) {
    // menu aktif yang dimiliki penjual sekarang di slot ini
    $qm = $conn->prepare("SELECT COUNT(*) FROM tb_menu WHERE id_penjual=? AND status='aktif' AND deleted=0");
    $qm->bind_param("i", $idpenjualterpilih); $qm->execute();
    $jmlmenuaktif = (int)$qm->get_result()->fetch_row()[0]; $qm->close();
    // pelanggan unik = jumlah pembeli berbeda yang pernah order ke penjual ini di periode
    $qp = $conn->prepare("SELECT COUNT(DISTINCT id_user) FROM tb_order WHERE id_penjual=? AND DATE(tanggal_order) BETWEEN ? AND ? AND status_order='Selesai' AND deleted=0");
    $qp->bind_param("iss", $idpenjualterpilih, $tglmulai, $tgljin); $qp->execute();
    $jmlpelangganunik = (int)$qp->get_result()->fetch_row()[0]; $qp->close();
}

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

// ── CHART ──────────────────────────────────────────────────────────────────────
// chart omset = hanya Selesai (uang masuk per hari)
$qchart = $conn->prepare("SELECT DATE(o.tanggal_order) AS tgl, COALESCE(SUM(o.total_harga),0) AS nilai FROM tb_order o WHERE DATE(o.tanggal_order) BETWEEN ? AND ? AND o.status_order='Selesai'{$tokoW} AND o.deleted=0 GROUP BY DATE(o.tanggal_order)");
$qchart->bind_param("ss", $tglmulai, $tgljin); $qchart->execute();
$rawchart = []; $resc = $qchart->get_result();
while ($row = $resc->fetch_assoc()) $rawchart[$row['tgl']] = (float)$row['nilai'];
$qchart->close();

function namahari(string $tgl): string {
    $map = ['Sun'=>'Min','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab'];
    return $map[date('D', strtotime($tgl))] ?? date('D', strtotime($tgl));
}

$selisih  = (int)ceil((strtotime($tgljin) - strtotime($tglmulai)) / 86400) + 1;
$chartdata = [];
for ($i = 0; $i < $selisih; $i++) {
    $tgl = date('Y-m-d', strtotime($tglmulai) + $i * 86400);
    $chartdata[] = ['tgl'=>$tgl,'label'=>date('d/m',strtotime($tgl)),'nilai'=>$rawchart[$tgl]??0.0];
}
$maxnilai = max(array_column($chartdata,'nilai')) ?: 1;

// ── PRODUK TERLARIS ────────────────────────────────────────────────────────────
// pakai snapshot dari order — nama menu/toko dibekukan saat order dibuat,
// jadi tetap tampil benar meski toko/menu sudah dihapus
$qtl = $conn->prepare(
    "SELECT COALESCE(d.nama_menu_snapshot, m.nama_menu) AS nama_menu,
            o.nama_toko_snapshot     AS nama_toko,
            o.nomor_kantin_snapshot  AS nomor_kantin,
            SUM(d.jumlah)   AS terjual,
            SUM(d.subtotal) AS omset
     FROM tb_detail_order d
     JOIN tb_order o ON d.id_order=o.id_order
     LEFT JOIN tb_menu m ON d.id_menu=m.id_menu
     WHERE DATE(o.tanggal_order) BETWEEN ? AND ?
       AND d.deleted=0 AND o.deleted=0 AND o.status_order='Selesai'{$tokoW}
     GROUP BY d.id_menu, nama_menu, nama_toko, nomor_kantin
     ORDER BY terjual DESC LIMIT 10"
);
$qtl->bind_param("ss", $tglmulai, $tgljin); $qtl->execute();
$terlaris = $qtl->get_result()->fetch_all(MYSQLI_ASSOC); $qtl->close();

// ── TOP PELANGGAN (gabungan) ──────────────────────────────────────────────────
// satu daftar pelanggan dengan kolom jumlah pesanan + total belanja,
// diurutkan dari paling banyak pesan. menggabung 2 list lama (terbanyak vs
// pengeluaran terbesar) supaya laporan tidak duplikatif.
$qpel = $conn->prepare(
    "SELECT u.username, COUNT(o.id_order) AS jml_order, COALESCE(SUM(o.total_harga),0) AS total_belanja
     FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user
     WHERE DATE(o.tanggal_order) BETWEEN ? AND ?{$tokoW}
       AND o.status_order='Selesai' AND o.deleted=0
     GROUP BY o.id_user, u.username
     ORDER BY jml_order DESC, total_belanja DESC LIMIT 10"
);
$qpel->bind_param("ss", $tglmulai, $tgljin); $qpel->execute();
$toppelanggan = $qpel->get_result()->fetch_all(MYSQLI_ASSOC); $qpel->close();

// ── DETAIL PESANAN SELESAI + DIBATALKAN ───────────────────────────────────────
// query satu baris per item menu agar bisa disusun di tabel detail lengkap.
// pakai snapshot nama_toko / nomor_kantin / nama_menu agar baris tetap akurat
// meski toko atau menu sudah dihapus / diubah / digantikan penjual baru.
$sqdet = $conn->prepare(
    "SELECT o.id_order, DATE_FORMAT(o.tanggal_order,'%d/%m/%Y %H:%i') AS tgl_format,
            u.username AS pembeli,
            o.nama_toko_snapshot AS nama_toko,
            COALESCE(o.nomor_kantin_snapshot, o.id_toko) AS nokantin,
            o.status_order, o.total_harga, o.metode_pembayaran,
            COALESCE(d.nama_menu_snapshot, m.nama_menu) AS nama_menu,
            d.jumlah, d.harga_satuan, d.subtotal
     FROM tb_order o
     JOIN tb_user u ON o.id_user=u.id_user
     LEFT JOIN tb_detail_order d ON o.id_order=d.id_order AND d.deleted=0
     LEFT JOIN tb_menu m ON d.id_menu=m.id_menu
     WHERE DATE(o.tanggal_order) BETWEEN ? AND ?
       AND o.status_order IN ('Selesai','Dibatalkan')
       AND o.deleted=0{$tokoW}
     ORDER BY o.tanggal_order DESC, o.id_order, nama_menu
     LIMIT 500"
);
$sqdet->bind_param("ss",$tglmulai,$tgljin); $sqdet->execute();
$resdet = $sqdet->get_result();
$pesanandetail = []; // kunci = id_order, value = array info pesanan + items
while ($rd = $resdet->fetch_assoc()) {
    $oid = $rd['id_order'];
    if (!isset($pesanandetail[$oid])) {
        $pesanandetail[$oid] = [
            'id'        => $oid,
            'tanggal'   => $rd['tgl_format'],
            'pembeli'   => $rd['pembeli'],
            'nama_toko' => $rd['nama_toko'],
            'nokantin'  => $rd['nokantin'],
            'status'    => $rd['status_order'],
            'total'     => (float)$rd['total_harga'],
            'metode'    => $rd['metode_pembayaran'],
            'items'     => []
        ];
    }
    if ($rd['nama_menu']) {
        $pesanandetail[$oid]['items'][] = [
            'nama'     => $rd['nama_menu'],
            'jumlah'   => (int)$rd['jumlah'],
            'harga'    => (float)$rd['harga_satuan'],
            'subtotal' => (float)$rd['subtotal']
        ];
    }
}
$sqdet->close();

// ── MENU + RATING + ULASAN ───────────────────────────────────
// menu hanya untuk mode per-kantin. distribusi rating dan ulasan terbaru
// untuk kedua mode: per-kantin filter by id_penjual, mode semua aggregate platform.
$daftarmenu = []; $distribusirating = []; $ulasankantin = [];

if ($modeSemua) {
    // distribusi rating platform-wide (semua penjual)
    $qdistp = $conn->query("SELECT rating_toko, COUNT(*) AS jml FROM tb_rating WHERE deleted=0 GROUP BY rating_toko ORDER BY rating_toko DESC");
    while ($r = $qdistp->fetch_assoc()) $distribusirating[(int)$r['rating_toko']] = (int)$r['jml'];

    // 10 ulasan terbaru platform-wide — tambahkan toko_snapshot dan menu yang dipesan
    $qulp = $conn->query(
        "SELECT r.rating_toko, r.ulasan, r.created, u.username,
                COALESCE(o.nama_toko_snapshot, CONCAT('Kantin ', o.id_toko)) AS nama_toko,
                (SELECT GROUP_CONCAT(COALESCE(d.nama_menu_snapshot, m2.nama_menu) SEPARATOR ', ')
                 FROM tb_detail_order d
                 LEFT JOIN tb_menu m2 ON d.id_menu=m2.id_menu
                 WHERE d.id_order=r.id_order AND d.deleted=0) AS menu_dipesan
         FROM tb_rating r
         JOIN tb_user u ON r.id_user=u.id_user
         LEFT JOIN tb_order o ON r.id_order=o.id_order
         WHERE r.deleted=0
         ORDER BY r.created DESC LIMIT 10"
    );
    $ulasankantin = $qulp->fetch_all(MYSQLI_ASSOC);
}

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

    $qdist = $conn->prepare("SELECT rating_toko, COUNT(*) AS jml FROM tb_rating WHERE id_penjual=? AND deleted=0 GROUP BY rating_toko ORDER BY rating_toko DESC");
    $qdist->bind_param("i", $idpenjualterpilih); $qdist->execute(); $resd = $qdist->get_result();
    while ($r = $resd->fetch_assoc()) $distribusirating[(int)$r['rating_toko']] = (int)$r['jml'];
    $qdist->close();

    // ulasan terbaru per-kantin dengan info menu yang dipesan saat rating diberikan
    $qul = $conn->prepare(
        "SELECT r.rating_toko, r.ulasan, r.created, u.username,
                (SELECT GROUP_CONCAT(COALESCE(d.nama_menu_snapshot, m2.nama_menu) SEPARATOR ', ')
                 FROM tb_detail_order d
                 LEFT JOIN tb_menu m2 ON d.id_menu=m2.id_menu
                 WHERE d.id_order=r.id_order AND d.deleted=0) AS menu_dipesan
         FROM tb_rating r JOIN tb_user u ON r.id_user=u.id_user
         WHERE r.id_penjual=? AND r.deleted=0
         ORDER BY r.created DESC LIMIT 10"
    );
    $qul->bind_param("i", $idpenjualterpilih); $qul->execute();
    $ulasankantin = $qul->get_result()->fetch_all(MYSQLI_ASSOC); $qul->close();
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
// rp(): format angka jadi "Rp 12.345" (titik ribuan, tanpa desimal).
function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
// singkat(): format ringkas untuk kartu stat — "Rp 1,2 M" / "Rp 850 Jt" supaya tidak panjang.
function singkat(float $n): string {
    if ($n >= 1_000_000_000) { $v=$n/1_000_000_000; return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' M'; }
    if ($n >= 1_000_000)     { $v=$n/1_000_000;     return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' Jt'; }
    return 'Rp ' . number_format($n, 0, ',', '.');
}
// bintanghtml(): cetak 5 bintang berwarna (kuning untuk yang aktif, abu untuk yang tidak).
function bintanghtml(float $r): string {
    $out = '';
    for ($i=1;$i<=5;$i++) { $w=$i<=$r?'#F59E0B':'#D1D5DB'; $out.="<i class='fa-solid fa-star' style='color:{$w};font-size:11px;'></i>"; }
    return $out;
}

// ── LABELS & CHART DIMS ────────────────────────────────────────────────────────
// siapkan label periode & kantin yang dipakai di header halaman + identitas ekspor
$labelperiode  = ['7'=>'7 Hari','14'=>'14 Hari','30'=>'30 Hari','custom'=>'Custom'];
$labelterpilih = $periode==='custom'
    ? date('d M Y', strtotime($tglmulai)).' – '.date('d M Y', strtotime($tgljin))
    : $labelperiode[$periode].' Terakhir';
$labelkantin = $nomorkantin > 0
    ? 'Kantin ke-'.$nomorkantin.(!empty($infokantinterpilih['nama_toko']) ? ' — '.htmlspecialchars($infokantinterpilih['nama_toko']) : '')
    : 'Semua Kantin';

// dimensi SVG chart batang: lebar svg, lebar tiap batang, gap antar batang.
// makin banyak hari → batang makin tipis, tapi lebar svg minimum tetap 700px.
$n    = count($chartdata);
$svgw = max(700, $n * 30);
$barw = max(8, min(40, (int)(($svgw - 80) / $n) - 4));
$gap  = max(4, (int)(($svgw - 80 - $n * $barw) / max(1, $n - 1)));
$startx = 70; $chartH = 160;
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
#kartu-cetak-kantin { display:none; }
.bar-dist { height:8px;background:#f0f0f0;border-radius:4px;overflow:hidden;flex:1; }
.bar-dist-isi { height:100%;background:var(--kedua);border-radius:4px; }
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

  /* cetak per kantin (popup overlay) */
  body.mode-cetak-kantin > *:not(#kartu-cetak-kantin) { display:none !important; }
  body.mode-cetak-kantin #kartu-cetak-kantin { display:block !important; }
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

</main>

<div id="kartu-cetak-kantin"></div>

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

/* cetakKantin: inject kartu satu kantin ke overlay dan print */
function cetakKantin(n) {
    var tr = document.querySelector('tr.baris-kantin[data-nomor="' + n + '"]');
    if (!tr) return;
    var nomatoko = tr.dataset.nomatoko || '';
    var penjual  = tr.dataset.penjual  || '';
    var terisi   = tr.dataset.terisi   === '1';
    var status   = tr.dataset.status;
    var order    = parseInt(tr.dataset.order)    || 0;
    var batal    = parseInt(tr.dataset.batal)    || 0;
    var rating   = parseFloat(tr.dataset.rating) || 0;
    var omset    = parseFloat(tr.dataset.omset)  || 0;
    var nilaibatal = parseFloat(tr.dataset.nilaibatal) || 0;
    var periode  = '<?= addslashes($labelterpilih) ?>';

    function rpFmt(v) { return 'Rp ' + Math.floor(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    var statusLabel = !terisi ? 'Kosong' : (status==='buka' ? 'Buka' : 'Tutup');
    var statusColor = !terisi ? '#9e9e9e' : (status==='buka' ? '#2e7d32' : '#c62828');
    var tglCetak = new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'});

    var html =
        '<div style="font-family:Arial,sans-serif;padding:28px;max-width:640px;margin:0 auto;color:#3D2C33;">'
        + '<div style="text-align:center;padding-bottom:14px;border-bottom:2px solid #643843;margin-bottom:22px;">'
        +   '<div style="font-size:22px;font-weight:900;color:#643843;">jajankita</div>'
        +   '<div style="font-size:15px;font-weight:700;margin-top:4px;">Laporan Kantin ke-' + n + '</div>'
        +   '<div style="font-size:11px;color:#8B6475;margin-top:3px;">Periode: ' + periode + ' &middot; Dicetak: ' + tglCetak + '</div>'
        + '</div>'
        + '<div style="display:flex;align-items:center;gap:16px;padding:14px 18px;background:#F8EBF1;border-radius:10px;margin-bottom:18px;">'
        +   '<div style="font-size:48px;font-weight:900;color:#643843;line-height:1;min-width:56px;text-align:center;">' + n + '</div>'
        +   '<div style="flex:1;">'
        +     '<div style="font-size:18px;font-weight:800;">' + (nomatoko || '— Kosong —') + '</div>'
        +     (penjual ? '<div style="font-size:12px;color:#8B6475;margin-top:2px;">Penjual: ' + penjual + '</div>' : '')
        +     '<div style="font-size:12px;font-weight:700;margin-top:4px;color:' + statusColor + ';">' + statusLabel + '</div>'
        +   '</div>'
        + '</div>'
        + '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
        + '<tr style="border-bottom:1px solid #EFD9D4;"><td style="padding:10px 0;color:#8B6475;">Total Pesanan</td><td style="padding:10px 0;font-weight:700;text-align:right;">' + order + '</td></tr>'
        + '<tr style="border-bottom:1px solid #EFD9D4;"><td style="padding:10px 0;color:#8B6475;">Pesanan Dibatalkan</td><td style="padding:10px 0;font-weight:700;text-align:right;color:#c62828;">' + batal + '</td></tr>'
        + '<tr style="border-bottom:1px solid #EFD9D4;"><td style="padding:10px 0;color:#8B6475;">Pesanan Berhasil</td><td style="padding:10px 0;font-weight:700;text-align:right;color:#2e7d32;">' + (order-batal) + '</td></tr>'
        + '<tr style="border-bottom:1px solid #EFD9D4;"><td style="padding:10px 0;color:#8B6475;">Rating Toko</td><td style="padding:10px 0;font-weight:700;text-align:right;">' + (rating>0?rating+' ★':'—') + '</td></tr>'
        + '<tr style="border-bottom:1px solid #EFD9D4;"><td style="padding:10px 0;color:#8B6475;">Nilai Dibatalkan</td><td style="padding:10px 0;font-weight:700;text-align:right;color:#c62828;">' + rpFmt(nilaibatal) + '</td></tr>'
        + '<tr><td style="padding:12px 0;font-weight:800;font-size:14px;">Total Omset (Selesai)</td><td style="padding:12px 0;font-weight:900;text-align:right;font-size:20px;color:#2e7d32;">' + rpFmt(omset) + '</td></tr>'
        + '</table></div>';

    var kartu = document.getElementById('kartu-cetak-kantin');
    kartu.innerHTML = html;
    document.body.classList.add('mode-cetak-kantin');
    window.onafterprint = function() {
        document.body.classList.remove('mode-cetak-kantin');
        window.onafterprint = null;
    };
    window.print();
}
</script>
</body>
</html>
