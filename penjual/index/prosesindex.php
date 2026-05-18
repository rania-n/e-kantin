<?php
/* ============================================================
   PROSES DASHBOARD PENJUAL
   Menangani toggle buka/tutup toko.
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idtoko     = (int)$_SESSION['id_toko'];
$statustoko = $_SESSION['status_toko'] ?? 'buka';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'toggle_status') {
    $statusbaru = ($statustoko === 'buka') ? 'tutup' : 'buka';
    $upd = $conn->prepare("UPDATE tb_toko SET status_toko=? WHERE id_toko=?");
    $upd->bind_param("si", $statusbaru, $idtoko);
    $upd->execute();
    $upd->close();
    $_SESSION['status_toko'] = $statusbaru;
}

header("Location: index.php");
exit;
?>
