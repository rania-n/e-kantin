<?php
/* proses simpan pengumuman untuk pembeli — hanya admin yang bisa akses.
   teks disimpan ke file 3. komponen/tekspengumuman.txt.
   file ini kemudian dibaca oleh halaman pembeli untuk ditampilkan sebagai banner. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// hanya terima request POST, jika bukan POST langsung kembalikan ke dashboard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// ambil teks pengumuman dari form, hilangkan spasi di awal dan akhir
$teks = trim($_POST['teks_pengumuman'] ?? '');

// batasi panjang teks maksimal 500 karakter agar tidak terlalu panjang
if (mb_strlen($teks) > 500) {
    $teks = mb_substr($teks, 0, 500);
}

// tentukan path file penyimpanan teks pengumuman
$file = __DIR__ . '/../../3. komponen/tekspengumuman.txt';

// tulis teks ke file (jika teks kosong, file menjadi kosong — artinya pengumuman dinonaktifkan)
file_put_contents($file, $teks);

// simpan flash message ke session agar bisa ditampilkan setelah redirect
$_SESSION['flash_admin'] = [
    'pesan' => $teks ? 'Pengumuman berhasil disimpan dan akan tampil ke pembeli.' : 'Pengumuman berhasil dikosongkan.',
    'jenis' => 'sukses',
];

// redirect kembali ke dashboard (mencegah form terkirim ulang jika halaman di-refresh)
header("Location: index.php");
exit;
