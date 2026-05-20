<?php
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$teks = trim($_POST['teks_pengumuman_penjual'] ?? '');
if (mb_strlen($teks) > 500) $teks = mb_substr($teks, 0, 500);

$file = __DIR__ . '/../../3. komponen/tekspengumumanpenjual.txt';
file_put_contents($file, $teks);

$_SESSION['flash_admin'] = [
    'pesan' => $teks ? 'Pengumuman ke penjual berhasil disimpan.' : 'Pengumuman ke penjual dikosongkan.',
    'jenis' => 'sukses',
];
header("Location: index.php");
exit;
