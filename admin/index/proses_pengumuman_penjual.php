<?php
/* proses simpan pengumuman khusus untuk penjual — hanya admin yang bisa akses.
   teks disimpan ke file 3. komponen/tekspengumumanpenjual.txt.
   file ini dibaca oleh halaman-halaman penjual untuk ditampilkan sebagai banner. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// hanya terima request POST, jika bukan POST langsung kembalikan ke dashboard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// ambil teks pengumuman dari form, hilangkan spasi di awal dan akhir
$teks = trim($_POST['teks_pengumuman_penjual'] ?? '');

// batasi panjang teks maksimal 500 karakter
if (mb_strlen($teks) > 500) $teks = mb_substr($teks, 0, 500);

// tentukan path file penyimpanan teks pengumuman penjual
$file = __DIR__ . '/../../3. komponen/tekspengumumanpenjual.txt';

// tulis teks ke file (kosong berarti pengumuman dinonaktifkan)
file_put_contents($file, $teks);

// simpan flash message ke session untuk ditampilkan setelah redirect
$_SESSION['flash_admin'] = [
    'pesan' => $teks ? 'Pengumuman ke penjual berhasil disimpan.' : 'Pengumuman ke penjual dikosongkan.',
    'jenis' => 'sukses',
];

// redirect kembali ke dashboard
header("Location: index.php");
exit;
