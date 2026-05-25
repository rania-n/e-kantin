<?php
/* proses edit toko — menerima data dari form edittoko.php via POST.
   bisa mengubah nama toko dan status (buka/tutup).
   setelah berhasil, redirect ke halaman detail toko. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tolak akses langsung (bukan POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: toko.php"); exit; }

// ambil dan bersihkan data dari form
$id       = (int)($_POST['id_toko'] ?? 0);
$namatoko = trim($_POST['nama_toko'] ?? '');
$status   = $_POST['status_toko'] ?? '';

// validasi: id toko harus ada
if (!$id) { flash('gagal','Data tidak valid.'); redirect('toko.php'); }

// validasi: nama toko tidak boleh kosong
if (empty($namatoko)) { flash('gagal','Nama toko wajib diisi.'); redirect("edittoko.php?id=$id"); }

// validasi: status hanya boleh 'buka' atau 'tutup'
if (!in_array($status, ['buka','tutup'])) { flash('gagal','Status tidak valid.'); redirect("edittoko.php?id=$id"); }

// update nama toko dan status di database menggunakan prepared statement
$upd = $conn->prepare("UPDATE tb_toko SET nama_toko=?, status_toko=? WHERE id_toko=? AND deleted=0");
$upd->bind_param("ssi", $namatoko, $status, $id); // "ssi" = string, string, integer
if (!$upd->execute()) { $upd->close(); flash('gagal','Gagal menyimpan perubahan.'); redirect("edittoko.php?id=$id"); }
$upd->close();

// cari id_user toko ini untuk redirect ke detail penjual
$qu = $conn->prepare("SELECT id_user FROM tb_toko WHERE id_toko=? AND deleted=0");
$qu->bind_param("i", $id); $qu->execute();
$iduser = (int)($qu->get_result()->fetch_row()[0] ?? 0); $qu->close();

flash('sukses','Toko berhasil diperbarui.');
redirect($iduser ? "../manajemenpengguna/viewuser.php?id=$iduser" : "kantin.php");

// fungsi pembantu: simpan flash message ke session
function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }

// fungsi pembantu: redirect ke url tertentu dan hentikan eksekusi
function redirect(string $url): void { header("Location: $url"); exit; }
?>
