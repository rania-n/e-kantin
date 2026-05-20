<?php
/* ekspor daftar pengguna ke file csv — hanya admin yang bisa akses.
   menghasilkan file csv berisi data pengguna sesuai filter yang aktif
   (role dan kata kunci pencarian). file langsung diunduh browser. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// baca filter dari url — sama seperti filter di halaman user.php
$rolefilter = $_GET['role'] ?? 'semua';
$cari       = trim($_GET['cari'] ?? '');

// validasi filter role agar hanya nilai yang diizinkan diterima
if (!in_array($rolefilter, ['semua','admin','penjual','pembeli'])) $rolefilter = 'semua';

// bangun query secara dinamis, sertakan info toko jika ada (left join)
$sql = "SELECT u.id_user, u.username, u.email, u.role, u.created,
               t.nama_toko, t.status_toko
        FROM tb_user u
        LEFT JOIN tb_toko t ON u.id_user=t.id_user AND t.deleted=0
        WHERE u.deleted=0";

// array untuk menyimpan parameter dan tipe data prepared statement
$params = []; $types = '';

// tambahkan filter role jika bukan semua
if ($rolefilter !== 'semua') { $sql .= " AND u.role=?"; $params[] = $rolefilter; $types .= 's'; }

// tambahkan filter pencarian jika ada kata kunci
if ($cari !== '') {
    $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $likcari = "%$cari%"; // % di kedua sisi: mencari kata di mana saja dalam teks
    $params[] = $likcari; $params[] = $likcari; $types .= 'ss';
}

// urutkan dari yang paling baru mendaftar
$sql .= " ORDER BY u.created DESC";

// jalankan query dengan prepared statement
$st = $conn->prepare($sql);
if ($params) { $st->bind_param($types, ...$params); }
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// buat nama file dengan timestamp agar tidak tertimpa file sebelumnya
$namafile = 'pengguna_ekantin_' . date('Ymd_His') . '.csv';

// kirim header http agar browser tahu ini file yang harus diunduh, bukan ditampilkan
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $namafile . '"');
header('Cache-Control: no-cache, no-store');
header('Pragma: no-cache');

// buka output stream php (data langsung dikirim ke browser)
$out = fopen('php://output', 'w');

// tulis BOM UTF-8 agar excel bisa membaca karakter indonesia dengan benar
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

// tulis baris judul kolom, pakai titik koma sebagai pemisah (standar excel indonesia)
fputcsv($out, ['ID','Username','Email','Peran','Nama Toko','Status Toko','Bergabung'], ';');

// tulis satu baris per pengguna
foreach ($rows as $r) {
    fputcsv($out, [
        $r['id_user'],
        $r['username'],
        $r['email'],
        $r['role'],
        $r['nama_toko'] ?? '-',    // jika bukan penjual atau tidak punya toko, isi dengan tanda hubung
        $r['status_toko'] ?? '-',  // sama
        !empty($r['created']) ? date('d/m/Y H:i', strtotime($r['created'])) : '-',
    ], ';');
}

// tutup output stream dan hentikan eksekusi
fclose($out);
exit;
?>
