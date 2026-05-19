<?php
/* ============================================================
   PROSES EDIT TOKO — ADMIN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: toko.php"); exit; }

$id       = (int)($_POST['id_toko'] ?? 0);
$namatoko = trim($_POST['nama_toko'] ?? '');
$status   = $_POST['status_toko'] ?? '';

if (!$id) { flash('gagal','Data tidak valid.'); redirect('toko.php'); }
if (empty($namatoko)) { flash('gagal','Nama toko wajib diisi.'); redirect("edittoko.php?id=$id"); }
if (!in_array($status, ['buka','tutup'])) { flash('gagal','Status tidak valid.'); redirect("edittoko.php?id=$id"); }

$upd = $conn->prepare("UPDATE tb_toko SET nama_toko=?, status_toko=? WHERE id_toko=? AND deleted=0");
$upd->bind_param("ssi", $namatoko, $status, $id);
if (!$upd->execute()) { $upd->close(); flash('gagal','Gagal menyimpan perubahan.'); redirect("edittoko.php?id=$id"); }
$upd->close();

flash('sukses','Toko berhasil diperbarui.');
redirect("viewtoko.php?id=$id");

function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }
function redirect(string $url): void { header("Location: $url"); exit; }
?>
