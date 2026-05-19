<?php
/* Halaman daftar toko sudah digabung ke Manajemen Pengguna */
if (session_status() === PHP_SESSION_NONE) {
    session_name('sesi_admin');
    session_start();
}
header("Location: ../manajemenpengguna/user.php?role=penjual");
exit;
