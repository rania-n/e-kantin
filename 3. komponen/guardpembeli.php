<?php
/* ============================================================
   GUARD PEMBELI
   Cek apakah pengguna sudah login dan rolenya adalah pembeli.
   Kalau belum, langsung redirect ke halaman login.

   File ini di-include dari folder: pembeli/sub/file.php
   Sehingga path ke autentifikasi adalah: ../../4. autentifikasi/
   ============================================================ */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'pembeli') {
    header("Location: ../../4. autentifikasi/login.php");
    exit;
}
?>
