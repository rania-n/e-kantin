<?php
/*
   guard pembeli — file ini bertugas menjaga halaman agar hanya bisa
   diakses oleh pengguna yang sudah login dengan peran pembeli.
   cara kerja: file ini di-include di bagian paling atas setiap halaman pembeli,
   sebelum konten apapun ditampilkan.
*/

// cek apakah session sudah aktif, kalau belum maka mulai session
// session_status() mengembalikan PHP_SESSION_NONE jika session belum berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_name('sesi_pembeli'); // gunakan nama session khusus pembeli agar tidak bentrok dengan session penjual/admin
    session_start(); // mulai session sehingga data login bisa dibaca dari $_SESSION
}

/*
   cek dua kondisi sekaligus dengan operator ||:
   1. $_SESSION['id_user'] kosong — artinya belum login sama sekali
   2. $_SESSION['role'] bukan 'pembeli' — artinya login tapi bukan sebagai pembeli

   path ke login: file ini ada di 3. komponen/, dipanggil dari pembeli/sub/file.php
   jadi path relatifnya naik dua level: ../../4. autentifikasi/login.php
*/
if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'pembeli') {
    // arahkan pengguna ke halaman login dan hentikan eksekusi script
    header("Location: ../../4. autentifikasi/login.php");
    exit; // exit wajib dipanggil setelah header redirect agar kode di bawahnya tidak ikut dijalankan
}
?>
