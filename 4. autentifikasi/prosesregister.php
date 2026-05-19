<?php
/* ============================================================
   PROSES REGISTER PEMBELI
   ============================================================ */
include "../1. koneksi/koneksi.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.php"); exit;
}

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

// Validasi kosong
if (empty($username) || empty($email) || empty($password)) {
    header("Location: register.php?error=" . urlencode("Semua kolom wajib diisi!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}

// Validasi username
if (strlen($username) < 6) {
    header("Location: register.php?error=" . urlencode("Username minimal 6 karakter!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}
if (strlen($username) > 50) {
    header("Location: register.php?error=" . urlencode("Username maksimal 50 karakter!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}

// Validasi password
if (strlen($password) < 8) {
    header("Location: register.php?error=" . urlencode("Password minimal 8 karakter!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}
if (strlen($password) > 100) {
    header("Location: register.php?error=" . urlencode("Password terlalu panjang!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}

// Validasi email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.php?error=" . urlencode("Format email tidak valid!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}

// Cek duplikat
$cek = $conn->prepare("SELECT id_user FROM tb_user WHERE email=? OR username=?");
$cek->bind_param("ss", $email, $username);
$cek->execute();
$cek->store_result();
if ($cek->num_rows > 0) {
    $cek->close();
    header("Location: register.php?error=" . urlencode("Email atau Username sudah terdaftar!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}
$cek->close();

// Simpan ke database
$hash = password_hash($password, PASSWORD_DEFAULT);
$role = "pembeli";

$stmt = $conn->prepare("INSERT INTO tb_user (username, email, password, role, deleted) VALUES (?, ?, ?, ?, 0)");
$stmt->bind_param("ssss", $username, $email, $hash, $role);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: login.php?sukses=" . urlencode("Registrasi berhasil! Silakan login."));
    exit;
} else {
    $stmt->close();
    header("Location: register.php?error=" . urlencode("Gagal mendaftar, coba lagi nanti.") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}
?>
