<?php
/* ============================================================
   PROSES GANTI PASSWORD PENJUAL
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profil.php"); exit;
}

$idpengguna = (int)$_SESSION['id_user'];
$passlama   = $_POST['password_lama']  ?? '';
$passbaru   = $_POST['password_baru']  ?? '';
$konfirmasi = $_POST['konfirmasi']     ?? '';

$err = '';
if (empty($passlama) || empty($passbaru) || empty($konfirmasi)) {
    $err = "Semua kolom wajib diisi.";
} elseif (strlen($passbaru) < 8) {
    $err = "Password baru minimal 8 karakter.";
} elseif (strlen($passbaru) > 100) {
    $err = "Password baru terlalu panjang (maksimal 100 karakter).";
} elseif ($passbaru !== $konfirmasi) {
    $err = "Konfirmasi password tidak cocok.";
} else {
    $qu = $conn->prepare("SELECT password FROM tb_user WHERE id_user=? AND deleted=0");
    $qu->bind_param("i", $idpengguna); $qu->execute();
    $passdb = $qu->get_result()->fetch_row()[0] ?? ''; $qu->close();

    if (!password_verify($passlama, $passdb)) {
        $err = "Password lama salah.";
    } else {
        $hash = password_hash($passbaru, PASSWORD_BCRYPT);
        $upd  = $conn->prepare("UPDATE tb_user SET password=? WHERE id_user=?");
        $upd->bind_param("si", $hash, $idpengguna); $upd->execute(); $upd->close();
        $_SESSION['flash'] = ['pesan' => 'Password berhasil diubah!', 'jenis' => 'sukses'];
        header("Location: profil.php"); exit;
    }
}

$_SESSION['flash'] = ['pesan' => $err, 'jenis' => 'gagal'];
header("Location: profil.php?tab=password"); exit;
?>
