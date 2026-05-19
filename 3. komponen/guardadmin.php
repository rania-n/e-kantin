<?php
/* ============================================================
   GUARD ADMIN — cek login role admin
   ============================================================ */
if (session_status() === PHP_SESSION_NONE) {
    session_name('sesi_admin');
    session_start();
}
if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../4. autentifikasi/login.php");
    exit;
}
?>
