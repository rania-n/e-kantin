<?php
/* ============================================================
   EKSPOR LAPORAN KE CSV — ADMIN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

$periode = $_GET['periode'] ?? '30';
if (!in_array($periode, ['7','30','90','365'])) $periode = '30';
$tglmulai = date('Y-m-d', strtotime("-$periode days"));

$qtoko = $conn->prepare("SELECT t.id_toko, t.nama_toko, t.status_toko,
                                 u.username,
                                 COUNT(DISTINCT o.id_order) AS total_order,
                                 COALESCE(SUM(o.total_harga),0) AS omset,
                                 COALESCE(SUM(CASE WHEN o.status_order='Selesai' THEN o.total_harga ELSE 0 END),0) AS pendapatan
                          FROM tb_toko t
                          JOIN tb_user u ON t.id_user=u.id_user
                          LEFT JOIN tb_order o ON t.id_toko=o.id_toko AND DATE(o.tanggal_order)>=? AND o.deleted=0
                          WHERE t.deleted=0
                          GROUP BY t.id_toko, t.nama_toko, t.status_toko, u.username
                          ORDER BY omset DESC");
$qtoko->bind_param("s", $tglmulai); $qtoko->execute();
$rows = $qtoko->get_result()->fetch_all(MYSQLI_ASSOC); $qtoko->close();

$namafile = 'laporan_ekantin_' . $periode . 'hari_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $namafile . '"');
header('Cache-Control: no-cache, no-store');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($out, ['ID Toko','Nama Toko','Pemilik','Status','Total Pesanan','Total Omset','Pendapatan Selesai'], ';');
foreach ($rows as $r) {
    fputcsv($out, [
        $r['id_toko'], $r['nama_toko'], $r['username'], $r['status_toko'],
        $r['total_order'],
        number_format($r['omset'],0,',','.'),
        number_format($r['pendapatan'],0,',','.'),
    ], ';');
}
fclose($out);
exit;
?>
