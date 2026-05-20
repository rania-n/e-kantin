<?php
/* proses hapus toko (soft delete) — hanya mengubah kolom deleted menjadi 1
   dan menutup toko (status_toko='tutup'), data tidak dihapus dari database.
   akun penjual tidak ikut dihapus. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tolak akses langsung (bukan POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: toko.php"); exit; }

// ambil id toko dari form, konversi ke integer
$id = (int)($_POST['id_toko'] ?? 0);

// validasi: id harus ada
if (!$id) { flash('gagal','Data tidak valid.'); redirect('toko.php'); }

// soft delete toko: set deleted=1 dan status_toko='tutup' sekaligus
// ini mencegah toko yang sudah dihapus masih terlihat sebagai "buka"
$upd = $conn->prepare("UPDATE tb_toko SET deleted=1, status_toko='tutup' WHERE id_toko=? AND deleted=0");
$upd->bind_param("i", $id); $upd->execute(); $upd->close();

// simpan flash message dan redirect ke daftar toko
flash('sukses','Toko berhasil dihapus.');
redirect('toko.php');

// fungsi pembantu: simpan flash message ke session
function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }

// fungsi pembantu: redirect ke url tertentu dan hentikan eksekusi
function redirect(string $url): void { header("Location: $url"); exit; }
?>
