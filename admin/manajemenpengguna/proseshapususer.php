<?php
/* proses hapus pengguna (soft delete pada tb_user).
   PENTING: kantin (tb_toko) TIDAK ikut dihapus — kantin hanya dikosongkan:
   id_user di-set NULL dan nama_toko dihapus, agar slot kantin tersedia
   untuk penjual berikutnya. Ini sesuai dengan konsep 10 kantin tetap.
   admin tidak bisa menghapus akunnya sendiri. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tolak akses langsung (bukan POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: user.php"); exit; }

// ambil id pengguna dari form, konversi ke integer untuk keamanan
$id = (int)($_POST['id_user'] ?? 0);

// validasi: id harus ada dan bukan 0
if (!$id) { flash('gagal','Data tidak valid.'); redirect('user.php'); }

// cegah admin menghapus akunnya sendiri
if ($id === (int)$_SESSION['id_user']) {
    flash('gagal','Tidak dapat menghapus akun sendiri.');
    redirect('user.php');
}

// soft delete pengguna: set deleted=1 pada tb_user
// data tetap ada di database (tidak dihapus permanen) untuk keperluan riwayat
$upd = $conn->prepare("UPDATE tb_user SET deleted=1 WHERE id_user=? AND deleted=0");
$upd->bind_param("i", $id);
$upd->execute();
$upd->close();

// KOSONGKAN kantin milik penjual ini — jangan soft-delete kantin!
// id_user diset NULL agar slot kantin tersedia untuk penjual berikutnya.
// nama_toko diset NULL karena pemiliknya sudah tidak ada.
// status_toko diset 'tutup' agar kantin kosong tidak terlihat buka di sisi pembeli.
// deleted tetap 0 — kantin TIDAK dihapus dari database.
$upt = $conn->prepare(
    "UPDATE tb_toko
     SET id_user=NULL, nama_toko=NULL, status_toko='tutup'
     WHERE id_user=? AND deleted=0"
);
$upt->bind_param("i", $id);
$upt->execute();
$upt->close();

// simpan flash message sukses dan kembali ke daftar pengguna
flash('sukses','Pengguna berhasil dihapus. Kantin yang ditempatinya kini tersedia untuk penjual baru.');
redirect('user.php');

// fungsi pembantu: simpan flash message ke session
function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }

// fungsi pembantu: redirect dan hentikan eksekusi
function redirect(string $url): void { header("Location: $url"); exit; }
?>
