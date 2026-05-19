<?php
/* ============================================================
   PROSES TOGGLE STATUS TOKO — ADMIN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../admin/manajemenpengguna/user.php?role=penjual");
    exit;
}

$id_toko = isset($_POST['id_toko']) ? (int)$_POST['id_toko'] : 0;
if (!$id_toko) {
    $_SESSION['flash'] = ['pesan'=>'ID toko tidak valid.','jenis'=>'gagal'];
    header("Location: ../../admin/manajemenpengguna/user.php?role=penjual");
    exit;
}

$qt = $conn->prepare("SELECT status_toko FROM tb_toko WHERE id_toko=? AND deleted=0");
$qt->bind_param("i", $id_toko); $qt->execute();
$toko = $qt->get_result()->fetch_assoc(); $qt->close();

if (!$toko) {
    $_SESSION['flash'] = ['pesan'=>'Toko tidak ditemukan.','jenis'=>'gagal'];
    header("Location: ../../admin/manajemenpengguna/user.php?role=penjual");
    exit;
}

$statusbaru = $toko['status_toko'] === 'buka' ? 'tutup' : 'buka';
$qu = $conn->prepare("UPDATE tb_toko SET status_toko=? WHERE id_toko=?");
$qu->bind_param("si", $statusbaru, $id_toko);
$qu->execute(); $qu->close();

$_SESSION['flash'] = ['pesan'=>"Status toko berhasil diubah menjadi \"$statusbaru\".", 'jenis'=>'sukses'];
header("Location: ../../admin/manajemenpengguna/user.php?role=penjual");
exit;
