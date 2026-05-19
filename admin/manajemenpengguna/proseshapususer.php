<?php
/* ============================================================
   PROSES HAPUS PENGGUNA (SOFT DELETE) — ADMIN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: user.php"); exit; }

$id = (int)($_POST['id_user'] ?? 0);
if (!$id) { flash('gagal','Data tidak valid.'); redirect('user.php'); }

if ($id === (int)$_SESSION['id_user']) { flash('gagal','Tidak dapat menghapus akun sendiri.'); redirect('user.php'); }

$upd = $conn->prepare("UPDATE tb_user SET deleted=1 WHERE id_user=? AND deleted=0");
$upd->bind_param("i", $id); $upd->execute(); $upd->close();

$upt = $conn->prepare("UPDATE tb_toko SET deleted=1 WHERE id_user=? AND deleted=0");
$upt->bind_param("i", $id); $upt->execute(); $upt->close();

flash('sukses','Pengguna berhasil dihapus.');
redirect('user.php');

function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }
function redirect(string $url): void { header("Location: $url"); exit; }
?>
