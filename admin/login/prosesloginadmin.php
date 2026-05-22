<?php
/*
  proses login khusus admin.
  file ini menerima data dari form loginadmin.php, memverifikasi ke database,
  dan hanya mengizinkan masuk jika role pengguna adalah 'admin'.

  alur:
  1. pastikan request berasal dari form POST
  2. ambil dan validasi input
  3. cari pengguna di database (username atau email)
  4. verifikasi password
  5. pastikan role adalah 'admin' — tolak jika bukan
  6. mulai session admin dan simpan data
  7. redirect ke dashboard admin
*/
include '../../1. koneksi/koneksi.php';

// langkah 1: tolak akses langsung bukan dari form POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: loginadmin.php");
    exit;
}

// langkah 2: ambil dan bersihkan input dari form
$usernameemail = trim($_POST['usernameemail'] ?? '');
$password      = trim($_POST['password']      ?? '');

// validasi: kedua kolom harus diisi
if (empty($usernameemail) || empty($password)) {
    header("Location: loginadmin.php?error=" . urlencode("Username/Email dan Password wajib diisi!"));
    exit;
}

// langkah 3: cari pengguna di database berdasarkan username ATAU email
// AND deleted=0 memastikan akun yang sudah dihapus tidak bisa login
$stmt = $conn->prepare("SELECT id_user, username, email, password, role
                         FROM tb_user
                         WHERE (username=? OR email=?) AND deleted=0");
// prepared statement mencegah SQL injection
$stmt->bind_param("ss", $usernameemail, $usernameemail);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// jika pengguna tidak ditemukan
if (!$user) {
    header("Location: loginadmin.php?error=" . urlencode("Akun tidak ditemukan!") .
           "&usernameemail=" . urlencode($usernameemail));
    exit;
}

// langkah 4: verifikasi password
// password_verify membandingkan teks asli dengan hash bcrypt yang tersimpan di database
if (!password_verify($password, $user['password'])) {
    header("Location: loginadmin.php?error=" . urlencode("Password salah!") .
           "&usernameemail=" . urlencode($usernameemail));
    exit;
}

// langkah 5: pastikan pengguna ini adalah admin
// halaman ini HANYA untuk admin — penjual/pembeli yang mencoba masuk ditolak
if ($user['role'] !== 'admin') {
    header("Location: loginadmin.php?error=" . urlencode("Akun ini bukan admin. Gunakan halaman login utama.") .
           "&usernameemail=" . urlencode($usernameemail));
    exit;
}

// langkah 6: mulai session khusus admin
// nama session 'sesi_admin' memisahkan session admin dari penjual/pembeli
session_name('sesi_admin');
session_start();

// simpan data admin ke session — tersedia di semua halaman admin
$_SESSION['id_user']  = $user['id_user'];
$_SESSION['username'] = $user['username'];
$_SESSION['email']    = $user['email'];
$_SESSION['role']     = $user['role'];

// langkah 7: arahkan ke dashboard admin
header("Location: ../index/index.php");
exit;
?>
