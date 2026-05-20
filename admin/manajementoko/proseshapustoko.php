<?php
/* ============================================================
   PROSES HAPUS TOKO (SOFT DELETE) — ADMIN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: toko.php"); exit; }

$id = (int)($_POST['id_toko'] ?? 0);
if (!$id) { flash('gagal','Data tidak valid.'); redirect('toko.php'); }

$upd = $conn->prepare("UPDATE tb_toko SET deleted=1, status_toko='tutup' WHERE id_toko=? AND deleted=0");
$upd->bind_param("i", $id); $upd->execute(); $upd->close();

flash('sukses','Toko berhasil dihapus.');
redirect('toko.php');

function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }
function redirect(string $url): void { header("Location: $url"); exit; }
?>
