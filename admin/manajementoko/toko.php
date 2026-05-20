<?php
/* halaman daftar toko sudah digabung ke halaman manajemen pengguna.
   file ini hanya berfungsi sebagai pengalih (redirect) ke halaman penjual. */

// mulai session jika belum dimulai, gunakan nama session khusus admin
if (session_status() === PHP_SESSION_NONE) {
    session_name('sesi_admin');
    session_start();
}

// arahkan langsung ke daftar pengguna dengan filter role penjual
header("Location: ../manajemenpengguna/user.php?role=penjual");
exit;
