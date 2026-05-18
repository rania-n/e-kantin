<?php
/* ============================================================
   LOGOUT
   Menghapus sesi penjual dan pembeli sekaligus agar bersih.
   ============================================================ */

// Hapus sesi penjual
session_name('sesi_penjual');
session_start();
$_SESSION = [];
session_destroy();
session_write_close();

// Hapus sesi pembeli
session_name('sesi_pembeli');
session_start();
$_SESSION = [];
session_destroy();

header("Location: login.php");
exit;
?>
