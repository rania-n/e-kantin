<?php
/* proses hapus pengguna (soft delete) — hanya mengubah kolom deleted menjadi 1,
   data tidak benar-benar dihapus dari database sehingga bisa dipulihkan jika perlu.
   toko milik penjual juga ikut di-soft-delete secara otomatis.
   admin tidak bisa menghapus akunnya sendiri. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tolak akses langsung (bukan POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: user.php"); exit; }

// ambil id pengguna dari form, konversi ke integer
$id = (int)($_POST['id_user'] ?? 0);

// validasi id
if (!$id) { flash('gagal','Data tidak valid.'); redirect('user.php'); }

// cegah admin menghapus akunnya sendiri (id_user di session dibandingkan dengan id yang dikirim)
if ($id === (int)$_SESSION['id_user']) { flash('gagal','Tidak dapat menghapus akun sendiri.'); redirect('user.php'); }

// soft delete pengguna: set deleted=1, bukan DELETE FROM (data tetap ada di database)
$upd = $conn->prepare("UPDATE tb_user SET deleted=1 WHERE id_user=? AND deleted=0");
$upd->bind_param("i", $id); $upd->execute(); $upd->close();

// soft delete toko milik pengguna ini juga (jika ada), agar toko tidak muncul tanpa pemilik
$upt = $conn->prepare("UPDATE tb_toko SET deleted=1 WHERE id_user=? AND deleted=0");
$upt->bind_param("i", $id); $upt->execute(); $upt->close();

// simpan flash message sukses dan kembali ke daftar pengguna
flash('sukses','Pengguna berhasil dihapus.');
redirect('user.php');

// fungsi pembantu: simpan flash message ke session
function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }

// fungsi pembantu: redirect ke url tertentu dan hentikan eksekusi
function redirect(string $url): void { header("Location: $url"); exit; }
?>
