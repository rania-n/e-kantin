<?php
/*
  file ini bertugas menghapus SEMUA sesi aktif secara sekaligus dan langsung redirect ke login.
  berbeda dengan konfirmasilogout.php yang meminta konfirmasi terlebih dahulu,
  file ini langsung bekerja tanpa tampilan html.

  kenapa perlu menghapus dua sesi (penjual dan pembeli)?
  karena aplikasi ini menggunakan nama sesi yang berbeda untuk setiap peran.
  jika hanya salah satu yang dihapus, sesi peran lainnya masih tersisa di server.
  logout.php dipakai untuk "bersih-bersih total" — misalnya saat ada masalah sesi
  atau saat dibutuhkan logout darurat.

  catatan: sesi admin tidak dihapus di sini karena admin menggunakan konfirmasilogout.php.
*/

// langkah 1: hapus sesi penjual
// session_name() harus dipanggil sebelum session_start() untuk menentukan sesi mana yang dibuka
session_name('sesi_penjual');
session_start();
// kosongkan semua variabel sesi (hapus isi array $_SESSION)
$_SESSION = [];
// hancurkan sesi di server (hapus file sesi)
session_destroy();
// tutup sesi dan pastikan perubahan ditulis ke server
session_write_close();

// langkah 2: hapus sesi pembeli dengan cara yang sama
// session_name() baru dipanggil lagi untuk mengganti ke nama sesi pembeli
session_name('sesi_pembeli');
session_start();
$_SESSION = [];
session_destroy();
// catatan: session_write_close() tidak dipanggil di sini karena langsung redirect setelahnya

// langkah 3: arahkan pengguna ke halaman login setelah semua sesi dihapus
header("Location: login.php");
exit;
?>
