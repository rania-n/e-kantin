<?php
session_start();
include '../../1. koneksi/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'penjual') {
    header("Location: ../../4. autentifikasi/login.php");
    exit();
}

$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);
$filter = $_GET['filter'] ?? 'Semua';

try {
    if ($action === 'proses') {
        $sql = "UPDATE tb_order SET status_order = 'diproses', updated = NOW() WHERE id_order = ?";
        $pesan = "Pesanan berhasil diproses!";
    } 
    elseif ($action === 'tolak') {
        $sql = "UPDATE tb_order SET status_order = 'dibatalkan', updated = NOW() WHERE id_order = ?";
        $pesan = "Pesanan berhasil ditolak!";
    } 
    elseif ($action === 'selesai') {
        $sql = "UPDATE tb_order SET status_order = 'selesai', updated = NOW() WHERE id_order = ?";
        $pesan = "Pesanan ditandai selesai!";
    } else {
        $pesan = "Aksi tidak valid!";
        $tipe = "danger";
        header("Location: manajemenpesanan.php?filter=" . urlencode($filter) . "&pesan=" . urlencode($pesan) . "&tipe=danger");
        exit();
    }

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $tipe = "success";

} catch (Exception $e) {
    $pesan = "Terjadi kesalahan: " . $e->getMessage();
    $tipe = "danger";
}

header("Location: manajemenpesanan.php?filter=" . urlencode($filter) . "&pesan=" . urlencode($pesan) . "&tipe=" . $tipe);
exit();
?>