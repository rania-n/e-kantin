<?php
/* ekspor laporan ke file csv detail — satu baris per item menu per pesanan.
   format spreadsheet: id pesanan, tanggal, pembeli, toko, kantin, status,
   nama menu, harga satuan, jumlah, subtotal, total pesanan, metode bayar, catatan.
   ini memungkinkan admin melakukan pivot/filter di excel untuk analisis mendalam. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// baca parameter periode
$periode = $_GET['periode'] ?? '30';
if (!in_array($periode, ['7','14','30','90','365','custom'])) $periode = '30';

if ($periode === 'custom') {
    $tglmulai = isset($_GET['dari'])   ? date('Y-m-d', strtotime($_GET['dari']))   : date('Y-m-d', strtotime('-30 days'));
    $tgljin   = isset($_GET['sampai']) ? date('Y-m-d', strtotime($_GET['sampai'])) : date('Y-m-d');
    if ($tgljin < $tglmulai) $tgljin = $tglmulai;
} else {
    $tglmulai = date('Y-m-d', strtotime("-$periode days"));
    $tgljin   = date('Y-m-d');
}

// filter kantin (0 = semua kantin)
$nomorkantin = (int)($_GET['kantin'] ?? 0);
$tokoW = ''; // kondisi tambahan jika filter per kantin
if ($nomorkantin > 0) {
    // temukan id_toko berdasarkan nomor_kantin
    $cekkt = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'nomor_kantin'");
    $adank  = ($cekkt && $cekkt->num_rows > 0);
    if ($adank) {
        $qtid = $conn->prepare("SELECT id_toko FROM tb_toko WHERE nomor_kantin=? AND deleted=0 LIMIT 1");
        $qtid->bind_param("i", $nomorkantin); $qtid->execute();
        $rowkt = $qtid->get_result()->fetch_row(); $qtid->close();
        if ($rowkt) $tokoW = " AND o.id_toko=" . (int)$rowkt[0];
    }
}

// cek apakah kolom nomor_kantin sudah ada untuk ditampilkan di csv
$cekkolom    = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'nomor_kantin'");
$migrasiSudah = ($cekkolom && $cekkolom->num_rows > 0);
$kolomNomor   = $migrasiSudah ? "COALESCE(t.nomor_kantin, t.id_toko)" : "t.id_toko";

/* query utama: satu baris per item menu per pesanan (detail order).
   pesanan tanpa detail item tetap muncul sebagai satu baris dengan kolom menu kosong.
   hanya pesanan selesai dan dibatalkan yang diekspor (yang relevan untuk laporan keuangan). */
$sql = "SELECT
    o.id_order,
    DATE_FORMAT(o.tanggal_order,'%d/%m/%Y') AS tanggal,
    DATE_FORMAT(o.tanggal_order,'%H:%i')    AS waktu,
    u.username          AS pembeli,
    t.nama_toko,
    {$kolomNomor}       AS nokantin,
    o.status_order,
    COALESCE(m.nama_menu,'—')              AS nama_menu,
    COALESCE(d.harga_satuan, 0)            AS harga_satuan,
    COALESCE(d.jumlah, 0)                  AS jumlah,
    COALESCE(d.subtotal, 0)                AS subtotal,
    o.total_harga,
    COALESCE(o.metode_pembayaran,'—')      AS metode_bayar,
    COALESCE(o.catatan,'')                 AS catatan
FROM tb_order o
JOIN tb_user u  ON o.id_user=u.id_user
JOIN tb_toko t  ON o.id_toko=t.id_toko
LEFT JOIN tb_detail_order d ON o.id_order=d.id_order AND d.deleted=0
LEFT JOIN tb_menu m ON d.id_menu=m.id_menu
WHERE DATE(o.tanggal_order) BETWEEN ? AND ?
  AND o.status_order IN ('Selesai','Dibatalkan')
  AND o.deleted=0{$tokoW}
ORDER BY o.tanggal_order DESC, o.id_order, m.nama_menu";

$qd = $conn->prepare($sql);
$qd->bind_param("ss", $tglmulai, $tgljin); $qd->execute();
$rows = $qd->get_result()->fetch_all(MYSQLI_ASSOC); $qd->close();

// hitung ringkasan untuk baris summary di atas tabel
$totalselesai    = 0; $totaldibatalkan = 0; $jmlselesai = 0; $jmldibatalkan = 0;
$idterakhir = null;
foreach ($rows as $r) {
    if ($r['id_order'] !== $idterakhir) {
        // hitung per-pesanan, bukan per-item (karena multi-item per pesanan)
        if ($r['status_order'] === 'Selesai')    { $totalselesai    += $r['total_harga']; $jmlselesai++; }
        if ($r['status_order'] === 'Dibatalkan') { $totaldibatalkan += $r['total_harga']; $jmldibatalkan++; }
        $idterakhir = $r['id_order'];
    }
}
$labelperiode = $periode === 'custom'
    ? date('d/m/Y', strtotime($tglmulai)) . ' - ' . date('d/m/Y', strtotime($tgljin))
    : $periode . ' Hari Terakhir';
$labelkantin = $nomorkantin > 0 ? 'Kantin ke-' . $nomorkantin : 'Semua Kantin';

// buat nama file csv dengan tanggal generate
$namafile = 'laporan_detail_ekantin_' . date('Ymd_His') . '.csv';

// kirim header http agar browser mengunduh file csv
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $namafile . '"');
header('Cache-Control: no-cache, no-store');

$out = fopen('php://output', 'w');

// tulis BOM utf-8 agar excel membaca karakter indonesia dengan benar
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

// ── baris keterangan laporan ────────────────────────────────────────────────
fputcsv($out, ['LAPORAN DETAIL E-KANTIN — jajankita'], ';');
fputcsv($out, ['Periode', $labelperiode], ';');
fputcsv($out, ['Filter Kantin', $labelkantin], ';');
fputcsv($out, ['Tanggal Ekspor', date('d/m/Y H:i:s')], ';');
fputcsv($out, [], ';'); // baris kosong

// ── ringkasan ───────────────────────────────────────────────────────────────
fputcsv($out, ['RINGKASAN'], ';');
fputcsv($out, ['Jumlah Pesanan Selesai', $jmlselesai], ';');
fputcsv($out, ['Total Omset Selesai (Rp)', number_format($totalselesai, 0, ',', '.')], ';');
fputcsv($out, ['Jumlah Pesanan Dibatalkan', $jmldibatalkan], ';');
fputcsv($out, ['Total Nilai Dibatalkan (Rp)', number_format($totaldibatalkan, 0, ',', '.')], ';');
fputcsv($out, ['Total Seluruh Pesanan', $jmlselesai + $jmldibatalkan], ';');
fputcsv($out, [], ';'); // baris kosong

// ── header kolom tabel detail ───────────────────────────────────────────────
fputcsv($out, [
    'ID Pesanan',
    'Tanggal',
    'Waktu',
    'Pembeli',
    'Nama Toko',
    'Kantin ke-',
    'Status',
    'Nama Menu',
    'Harga Satuan (Rp)',
    'Jumlah',
    'Subtotal (Rp)',
    'Total Pesanan (Rp)',
    'Metode Bayar',
    'Catatan'
], ';');

// ── data baris per item ─────────────────────────────────────────────────────
$idsebelumnya = null;
foreach ($rows as $r) {
    $oidtampil  = $r['id_order'];
    // total pesanan hanya tampil pada baris pertama item tiap pesanan (agar tidak duplikat)
    $totalTampil = ($oidtampil !== $idsebelumnya) ? number_format($r['total_harga'], 0, ',', '.') : '';
    $metodeTampil = ($oidtampil !== $idsebelumnya) ? $r['metode_bayar'] : '';
    $catatanTampil = ($oidtampil !== $idsebelumnya) ? $r['catatan'] : '';
    $idsebelumnya = $oidtampil;

    fputcsv($out, [
        '#' . $r['id_order'],
        $r['tanggal'],
        $r['waktu'],
        $r['pembeli'],
        $r['nama_toko'] ?? '—',
        'Kantin ke-' . $r['nokantin'],
        $r['status_order'],
        $r['nama_menu'],
        $r['harga_satuan'] > 0 ? number_format($r['harga_satuan'], 0, ',', '.') : '',
        $r['jumlah'] > 0 ? $r['jumlah'] : '',
        $r['subtotal'] > 0 ? number_format($r['subtotal'], 0, ',', '.') : '',
        $totalTampil,
        $metodeTampil,
        $catatanTampil
    ], ';');
}

// tutup output stream
fclose($out);
exit;
?>
