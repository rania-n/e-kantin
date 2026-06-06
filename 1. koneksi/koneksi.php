<?php
/*
  file ini adalah pusat koneksi ke database mysql.
  semua file yang butuh akses ke database WAJIB include file ini terlebih dahulu.
  contoh pemakaian: include "../1. koneksi/koneksi.php";

  kenapa dipisah ke satu file? agar pengaturan koneksi (host, user, password, nama db)
  cukup diubah di satu tempat saja — tidak perlu mengedit puluhan file.
  pola seperti ini disebut "single source of truth".
*/

// variabel konfigurasi koneksi ke mysql server
$host = "localhost"; // server database — 'localhost' artinya database ada di komputer yang sama
$user = "root";      // username default xampp untuk mysql
$pass = "";          // password default xampp kosong (pada server hosting, ini WAJIB diisi)
$db   = "e_kantin";  // nama database yang dipakai aplikasi ini — sesuaikan dengan nama database kamu

// buat koneksi ke database menggunakan mysqli (mysql improved)
// hasilnya disimpan ke variabel $conn yang nanti dipakai di semua query
// contoh pemakaian di file lain: $conn->prepare("SELECT ..."), $conn->query("..."), dll
$conn = mysqli_connect($host, $user, $pass, $db);

// cek apakah koneksi berhasil dibuat
// jika gagal (misal: mysql belum dinyalakan, password salah, atau db belum dibuat),
// hentikan script dan tampilkan pesan error agar mudah dilacak penyebabnya
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// atur zona waktu php ke waktu jakarta (wib)
// penting: tanpa ini, fungsi seperti date() dan time() akan memakai zona waktu default server
// (sering kali utc), sehingga jam transaksi/pesanan bisa tidak sesuai dengan waktu lokal.
date_default_timezone_get();
date_default_timezone_set('Asia/Jakarta'); // Set timezone sesuai kebutuhan

/*
  CATATAN MIGRASI DATABASE:
  Penambahan kolom (status_akun, no_telepon, stok_dipotong) TIDAK lagi dijalankan
  otomatis di sini agar koneksi.php bersih dan tidak menjalankan ALTER tiap request.

  Kolom-kolom itu sekarang ada di file "migrasi.sql" (di folder utama proyek).
  WAJIB jalankan "migrasi.sql" di phpMyAdmin setiap kali setup database baru
  atau import ulang database, supaya aplikasi tidak error "Unknown column".
*/
?>
