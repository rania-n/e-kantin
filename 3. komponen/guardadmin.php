<?php
/*
   guard admin — file ini menjaga halaman admin agar hanya bisa diakses
   oleh pengguna yang sudah login dengan peran admin.
   cara penggunaan: include file ini di bagian paling atas setiap halaman admin,
   sebelum konten apapun ditampilkan kepada pengguna.
*/

// cek apakah session sudah aktif, kalau belum maka mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_name('sesi_admin'); // nama session khusus admin agar terpisah dari pembeli/penjual
    session_start();
}

/*
   cek dua kondisi:
   1. id_user kosong — pengguna belum login sama sekali
   2. role bukan 'admin' — pengguna login tapi bukan admin (misal: penjual atau pembeli mencoba akses halaman admin)

   operator ?? '' dipakai sebagai nilai default jika kunci 'role' tidak ada di session
*/
if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    // tolak akses dan arahkan ke halaman login KHUSUS ADMIN (bukan login umum pembeli/penjual)
    header("Location: ../../admin/login/loginadmin.php");
    exit; // wajib ada agar kode di bawah tidak ikut dijalankan meski sudah di-redirect
}
?>
