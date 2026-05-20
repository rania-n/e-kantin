<?php
/* file ini memproses permintaan toggle buka/tutup toko dari dashboard penjual.
   setelah selesai, selalu redirect kembali ke halaman dashboard (index.php) */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

// ambil id toko dan status toko saat ini dari session
$idtoko     = (int)$_SESSION['id_toko'];
$statustoko = $_SESSION['status_toko'] ?? 'buka';

// proses hanya jika request adalah POST dan aksi yang dikirim adalah toggle_status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'toggle_status') {
    // tentukan status baru: jika toko sedang buka → tutup, jika tutup → buka
    $statusbaru = ($statustoko === 'buka') ? 'tutup' : 'buka';

    // update kolom status_toko di database
    $upd = $conn->prepare("UPDATE tb_toko SET status_toko=? WHERE id_toko=?");
    $upd->bind_param("si", $statusbaru, $idtoko); // "s" = string, "i" = integer
    $upd->execute();
    $upd->close();

    // simpan status baru ke session agar navbar dan dashboard langsung mencerminkan perubahan
    $_SESSION['status_toko'] = $statusbaru;
}

// kembalikan ke halaman dashboard setelah proses selesai
header("Location: index.php");
exit;
?>
