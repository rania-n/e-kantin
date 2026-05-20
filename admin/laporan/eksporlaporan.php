<?php
/* ekspor laporan toko ke file csv — hanya admin yang bisa akses.
   menghasilkan file csv berisi data semua toko: nama, pemilik, status,
   total pesanan, omset, dan pendapatan selesai dalam periode tertentu.
   file langsung diunduh oleh browser tanpa ditampilkan di layar. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// baca parameter periode dari url, default 30 hari, hanya nilai yang diizinkan yang diterima
$periode = $_GET['periode'] ?? '30';
if (!in_array($periode, ['7','30','90','365'])) $periode = '30';

// hitung tanggal mulai dari hari ini dikurangi jumlah hari periode
$tglmulai = date('Y-m-d', strtotime("-$periode days"));

/* ambil data semua toko beserta statistik pesanannya dalam periode.
   left join digunakan agar toko tanpa pesanan tetap muncul dengan nilai 0.
   case when: menghitung pendapatan hanya dari pesanan yang berstatus selesai. */
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

// buat nama file dengan format: laporan_ekantin_30hari_20240101_120000.csv
$namafile = 'laporan_ekantin_' . $periode . 'hari_' . date('Ymd_His') . '.csv';

// kirim header http agar browser tahu ini file csv yang harus diunduh
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $namafile . '"');
header('Cache-Control: no-cache, no-store');

// buka output stream php (data langsung dikirim ke browser, tidak disimpan di server)
$out = fopen('php://output', 'w');

// tulis BOM (byte order mark) UTF-8 agar excel membaca file dengan benar dan tidak garbled
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

// tulis baris header kolom csv, gunakan titik koma sebagai pemisah agar kompatibel dengan excel indonesia
fputcsv($out, ['ID Toko','Nama Toko','Pemilik','Status','Total Pesanan','Total Omset','Pendapatan Selesai'], ';');

// tulis satu baris per toko
foreach ($rows as $r) {
    fputcsv($out, [
        $r['id_toko'],
        $r['nama_toko'],
        $r['username'],
        $r['status_toko'],
        $r['total_order'],
        number_format($r['omset'],0,',','.'),      // format rupiah tanpa desimal
        number_format($r['pendapatan'],0,',','.'), // format rupiah tanpa desimal
    ], ';');
}

// tutup output stream dan hentikan eksekusi php
fclose($out);
exit;
?>
