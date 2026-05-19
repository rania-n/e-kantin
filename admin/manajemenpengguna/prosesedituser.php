<?php
/* ============================================================
   PROSES EDIT PENGGUNA — ADMIN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: user.php"); exit; }

$id       = (int)($_POST['id_user'] ?? 0);
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$namatoko = trim($_POST['nama_toko'] ?? '');

if (!$id) { flash('gagal','Data tidak valid.'); redirect('user.php'); }
if (strlen($username) < 6) { flash('gagal','Username minimal 6 karakter.'); redirect("edituser.php?id=$id"); }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('gagal','Format email tidak valid.'); redirect("edituser.php?id=$id"); }

// Ambil role saat ini — role tidak bisa diubah
$qr = $conn->prepare("SELECT role FROM tb_user WHERE id_user=? AND deleted=0");
$qr->bind_param("i", $id); $qr->execute();
$rowrole = $qr->get_result()->fetch_row(); $qr->close();
if (!$rowrole) { flash('gagal','Pengguna tidak ditemukan.'); redirect('user.php'); }
$role = $rowrole[0];

$ck = $conn->prepare("SELECT id_user FROM tb_user WHERE username=? AND id_user!=? AND deleted=0");
$ck->bind_param("si", $username, $id); $ck->execute();
if ($ck->get_result()->num_rows > 0) { $ck->close(); flash('gagal','Username sudah digunakan oleh pengguna lain.'); redirect("edituser.php?id=$id"); }
$ck->close();

$ce = $conn->prepare("SELECT id_user FROM tb_user WHERE email=? AND id_user!=? AND deleted=0");
$ce->bind_param("si", $email, $id); $ce->execute();
if ($ce->get_result()->num_rows > 0) { $ce->close(); flash('gagal','Email sudah digunakan oleh pengguna lain.'); redirect("edituser.php?id=$id"); }
$ce->close();

if ($password !== '') {
    if (strlen($password) < 8) { flash('gagal','Password minimal 8 karakter.'); redirect("edituser.php?id=$id"); }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE tb_user SET username=?, email=?, password=? WHERE id_user=? AND deleted=0");
    $upd->bind_param("sssi", $username, $email, $hash, $id);
} else {
    $upd = $conn->prepare("UPDATE tb_user SET username=?, email=? WHERE id_user=? AND deleted=0");
    $upd->bind_param("ssi", $username, $email, $id);
}

if (!$upd->execute()) { $upd->close(); flash('gagal','Gagal menyimpan perubahan.'); redirect("edituser.php?id=$id"); }
$upd->close();

if ($namatoko !== '' && $role === 'penjual') {
    $upt = $conn->prepare("UPDATE tb_toko SET nama_toko=? WHERE id_user=? AND deleted=0");
    $upt->bind_param("si", $namatoko, $id); $upt->execute(); $upt->close();
}

flash('sukses','Pengguna berhasil diperbarui.');
redirect("viewuser.php?id=$id");

function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }
function redirect(string $url): void { header("Location: $url"); exit; }
?>
