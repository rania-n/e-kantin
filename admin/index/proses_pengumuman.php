<?php
/*
   proses simpan pengumuman — hanya admin yang bisa akses.
   menyimpan teks ke 3. komponen/teks_pengumuman.txt.
*/
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$teks = trim($_POST['teks_pengumuman'] ?? '');

// batasi panjang 500 karakter
if (mb_strlen($teks) > 500) {
    $teks = mb_substr($teks, 0, 500);
}

$file = __DIR__ . '/../../3. komponen/teks_pengumuman.txt';
file_put_contents($file, $teks);

$_SESSION['flash_admin'] = [
    'pesan' => $teks ? 'Pengumuman berhasil disimpan dan akan tampil ke pembeli.' : 'Pengumuman berhasil dikosongkan.',
    'jenis' => 'sukses',
];
header("Location: index.php");
exit;
