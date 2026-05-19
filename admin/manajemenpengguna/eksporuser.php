<?php
/* ============================================================
   EKSPOR PENGGUNA KE CSV — ADMIN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

$rolefilter = $_GET['role'] ?? 'semua';
$cari       = trim($_GET['cari'] ?? '');
if (!in_array($rolefilter, ['semua','admin','penjual','pembeli'])) $rolefilter = 'semua';

$sql = "SELECT u.id_user, u.username, u.email, u.role, u.created,
               t.nama_toko, t.status_toko
        FROM tb_user u
        LEFT JOIN tb_toko t ON u.id_user=t.id_user AND t.deleted=0
        WHERE u.deleted=0";
$params = []; $types = '';
if ($rolefilter !== 'semua') { $sql .= " AND u.role=?"; $params[] = $rolefilter; $types .= 's'; }
if ($cari !== '') {
    $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $likcari = "%$cari%"; $params[] = $likcari; $params[] = $likcari; $types .= 'ss';
}
$sql .= " ORDER BY u.created DESC";

$st = $conn->prepare($sql);
if ($params) { $st->bind_param($types, ...$params); }
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

$namafile = 'pengguna_ekantin_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $namafile . '"');
header('Cache-Control: no-cache, no-store');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 agar Excel baca benar
fputcsv($out, ['ID','Username','Email','Peran','Nama Toko','Status Toko','Bergabung'], ';');
foreach ($rows as $r) {
    fputcsv($out, [
        $r['id_user'],
        $r['username'],
        $r['email'],
        $r['role'],
        $r['nama_toko'] ?? '-',
        $r['status_toko'] ?? '-',
        !empty($r['created']) ? date('d/m/Y H:i', strtotime($r['created'])) : '-',
    ], ';');
}
fclose($out);
exit;
?>
