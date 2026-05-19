<?php
/* ============================================================
   PROSES TAMBAH PENGGUNA — ADMIN
   Mendukung semua role: penjual (+ toko), pembeli, admin
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: tambahuser.php"); exit; }

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email']    ?? '');
$namatoko = trim($_POST['nama_toko'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? 'penjual';

if (!in_array($role, ['penjual','pembeli','admin'])) { flash('gagal','Peran tidak valid.'); redirect('tambahuser.php'); }
if (strlen($username) < 6 || strlen($username) > 50) { flash('gagal','Username harus 6–50 karakter.'); redirect('tambahuser.php'); }
if (!filter_var($email, FILTER_VALIDATE_EMAIL))        { flash('gagal','Format email tidak valid.'); redirect('tambahuser.php'); }
if (strlen($password) < 8)                             { flash('gagal','Password minimal 8 karakter.'); redirect('tambahuser.php'); }
if ($role === 'penjual' && empty($namatoko))           { flash('gagal','Nama toko wajib diisi untuk Penjual.'); redirect('tambahuser.php'); }

$ck = $conn->prepare("SELECT id_user FROM tb_user WHERE username=? AND deleted=0");
$ck->bind_param("s", $username); $ck->execute();
if ($ck->get_result()->num_rows > 0) { $ck->close(); flash('gagal','Username sudah terdaftar.'); redirect('tambahuser.php'); }
$ck->close();

$ce = $conn->prepare("SELECT id_user FROM tb_user WHERE email=? AND deleted=0");
$ce->bind_param("s", $email); $ce->execute();
if ($ce->get_result()->num_rows > 0) { $ce->close(); flash('gagal','Email sudah digunakan.'); redirect('tambahuser.php'); }
$ce->close();

$hash = password_hash($password, PASSWORD_DEFAULT);

$ins = $conn->prepare("INSERT INTO tb_user (username, email, password, role, deleted) VALUES (?,?,?,?,0)");
$ins->bind_param("ssss", $username, $email, $hash, $role);
if (!$ins->execute()) { $ins->close(); flash('gagal','Gagal menyimpan pengguna. Coba lagi.'); redirect('tambahuser.php'); }
$iduser = $conn->insert_id;
$ins->close();

if ($role === 'penjual' && !empty($namatoko)) {
    $insto = $conn->prepare("INSERT INTO tb_toko (id_user, nama_toko, status_toko, deleted) VALUES (?,?,'tutup',0)");
    $insto->bind_param("is", $iduser, $namatoko);
    $insto->execute(); $insto->close();
    flash('sukses', "Penjual \"$username\" berhasil ditambahkan beserta toko \"$namatoko\".");
    redirect('user.php?role=penjual');
} else {
    flash('sukses', ucfirst($role) . " \"$username\" berhasil ditambahkan.");
    redirect('user.php?role=' . $role);
}

function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }
function redirect(string $url): void { header("Location: $url"); exit; }
?>
