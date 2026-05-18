<?php
/* ============================================================
   PROSES LOGIN
   Validasi login, set session, redirect ke halaman sesuai role.
   ============================================================ */
include "../1. koneksi/koneksi.php";
if (session_status() === PHP_SESSION_NONE) session_start();

// Hanya proses POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$usernameemail = trim($_POST['usernameemail'] ?? '');
$password      = trim($_POST['password']      ?? '');

// Validasi kosong
if (empty($usernameemail) || empty($password)) {
    header("Location: login.php?error=" . urlencode("Username/Email dan Password wajib diisi!"));
    exit;
}

// Cari user
$stmt = $conn->prepare("SELECT id_user, username, email, password, role
                         FROM tb_user
                         WHERE (username=? OR email=?) AND deleted=0");
$stmt->bind_param("ss", $usernameemail, $usernameemail);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: login.php?error=" . urlencode("Akun tidak ditemukan!") . "&usernameemail=" . urlencode($usernameemail));
    exit;
}

if (!password_verify($password, $user['password'])) {
    header("Location: login.php?error=" . urlencode("Password salah!") . "&usernameemail=" . urlencode($usernameemail));
    exit;
}

// Login berhasil — set session
$_SESSION['id_user']  = $user['id_user'];
$_SESSION['username'] = $user['username'];
$_SESSION['email']    = $user['email'];
$_SESSION['role']     = $user['role'];

// Kalau penjual, langsung ambil id_toko dan nama_toko ke session
if ($user['role'] === 'penjual') {
    $iduser = (int)$user['id_user'];
    $qt = $conn->prepare("SELECT id_toko, nama_toko, status_toko FROM tb_toko WHERE id_user=? AND deleted=0 LIMIT 1");
    $qt->bind_param("i", $iduser);
    $qt->execute();
    $datatoko = $qt->get_result()->fetch_assoc();
    $qt->close();
    if ($datatoko) {
        $_SESSION['id_toko']     = $datatoko['id_toko'];
        $_SESSION['nama_toko']   = $datatoko['nama_toko'];
        $_SESSION['status_toko'] = $datatoko['status_toko'] ?? 'buka';
    }
}

// Redirect berdasarkan role
switch ($user['role']) {
    case 'admin':
        header("Location: ../admin/index/index.php");
        break;
    case 'penjual':
        header("Location: ../penjual/index/index.php");
        break;
    case 'pembeli':
        header("Location: ../pembeli/index/index.php");
        break;
    default:
        header("Location: login.php?error=" . urlencode("Role tidak dikenali."));
}
exit;
?>
