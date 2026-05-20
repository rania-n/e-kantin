<?php
/* proses edit pengguna — menerima data dari form edituser.php via POST.
   bisa mengubah username, email, password (opsional), dan nama toko (jika penjual).
   role tidak bisa diubah. setelah berhasil, redirect ke halaman detail pengguna. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tolak akses langsung (bukan POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: user.php"); exit; }

// ambil dan bersihkan data dari form
$id       = (int)($_POST['id_user'] ?? 0);
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$namatoko = trim($_POST['nama_toko'] ?? '');

// validasi: id harus ada
if (!$id) { flash('gagal','Data tidak valid.'); redirect('user.php'); }

// validasi panjang username
if (strlen($username) < 6) { flash('gagal','Username minimal 6 karakter.'); redirect("edituser.php?id=$id"); }

// validasi format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('gagal','Format email tidak valid.'); redirect("edituser.php?id=$id"); }

// ambil role pengguna saat ini dari database — role tidak boleh berubah lewat form ini
$qr = $conn->prepare("SELECT role FROM tb_user WHERE id_user=? AND deleted=0");
$qr->bind_param("i", $id); $qr->execute();
$rowrole = $qr->get_result()->fetch_row(); $qr->close();

// jika pengguna tidak ditemukan (mungkin sudah dihapus)
if (!$rowrole) { flash('gagal','Pengguna tidak ditemukan.'); redirect('user.php'); }
$role = $rowrole[0];

// cek apakah username baru sudah dipakai pengguna lain (kecuali diri sendiri)
$ck = $conn->prepare("SELECT id_user FROM tb_user WHERE username=? AND id_user!=? AND deleted=0");
$ck->bind_param("si", $username, $id); $ck->execute();
if ($ck->get_result()->num_rows > 0) { $ck->close(); flash('gagal','Username sudah digunakan oleh pengguna lain.'); redirect("edituser.php?id=$id"); }
$ck->close();

// cek apakah email baru sudah dipakai pengguna lain
$ce = $conn->prepare("SELECT id_user FROM tb_user WHERE email=? AND id_user!=? AND deleted=0");
$ce->bind_param("si", $email, $id); $ce->execute();
if ($ce->get_result()->num_rows > 0) { $ce->close(); flash('gagal','Email sudah digunakan oleh pengguna lain.'); redirect("edituser.php?id=$id"); }
$ce->close();

// jika field password diisi, ikut-sertakan password baru dalam update
if ($password !== '') {
    // validasi panjang password baru
    if (strlen($password) < 8) { flash('gagal','Password minimal 8 karakter.'); redirect("edituser.php?id=$id"); }

    // hash password baru sebelum disimpan
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // update username, email, dan password sekaligus
    $upd = $conn->prepare("UPDATE tb_user SET username=?, email=?, password=? WHERE id_user=? AND deleted=0");
    $upd->bind_param("sssi", $username, $email, $hash, $id);
} else {
    // jika password dikosongkan, hanya update username dan email saja
    $upd = $conn->prepare("UPDATE tb_user SET username=?, email=? WHERE id_user=? AND deleted=0");
    $upd->bind_param("ssi", $username, $email, $id);
}

// jalankan query update pengguna
if (!$upd->execute()) { $upd->close(); flash('gagal','Gagal menyimpan perubahan.'); redirect("edituser.php?id=$id"); }
$upd->close();

// jika nama toko diisi dan pengguna adalah penjual, update nama tokonya juga
if ($namatoko !== '' && $role === 'penjual') {
    $upt = $conn->prepare("UPDATE tb_toko SET nama_toko=? WHERE id_user=? AND deleted=0");
    $upt->bind_param("si", $namatoko, $id); $upt->execute(); $upt->close();
}

// simpan flash message sukses dan arahkan ke halaman detail pengguna
flash('sukses','Pengguna berhasil diperbarui.');
redirect("viewuser.php?id=$id");

// fungsi pembantu: simpan flash message ke session
function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }

// fungsi pembantu: redirect ke url tertentu dan hentikan eksekusi
function redirect(string $url): void { header("Location: $url"); exit; }
?>
