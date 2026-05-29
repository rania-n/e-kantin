<?php
/*
  proses pendaftaran akun pembeli baru.
  file ini HANYA logika php — tidak ada tampilan html.

  alur kerja:
  1. pastikan request datang dari form (POST)
  2. ambil dan bersihkan data input
  3. validasi: tidak kosong, panjang karakter, format email, kelas dari daftar resmi
  4. bebaskan slot dari akun ditolak yang username/email-nya sama
  5. cek duplikat username/email di akun aktif
  6. simpan akun baru dengan password ter-hash + status_verifikasi='pending'
  7. redirect ke login dengan pesan "menunggu verifikasi admin"
*/
include "../1. koneksi/koneksi.php";
// helper bersama untuk validasi opsi kelas resmi
include "../3. komponen/kelas_jurusan.php";

// langkah 1: pastikan POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.php"); exit;
}

// langkah 2: ambil & bersihkan data
$username    = trim($_POST['username']    ?? '');
$email       = trim($_POST['email']       ?? '');
$password    = trim($_POST['password']    ?? '');
$namalengkap = trim($_POST['namalengkap'] ?? '');
$kelas       = trim($_POST['kelas']       ?? '');

// fungsi bantu: bangun query string untuk redirect balik ke form supaya semua
// kolom (kecuali password) tetap terisi setelah error.
function balikUrlForm(string $username, string $email, string $namalengkap, string $kelas): string {
    return "&username="    . urlencode($username) .
           "&email="       . urlencode($email) .
           "&namalengkap=" . urlencode($namalengkap) .
           "&kelas="       . urlencode($kelas);
}
$balik = balikUrlForm($username, $email, $namalengkap, $kelas);

// langkah 3a: tidak boleh kosong
if (empty($username) || empty($email) || empty($password) || empty($namalengkap) || empty($kelas)) {
    header("Location: register.php?error=" . urlencode("Semua kolom wajib diisi (termasuk kelas / status)!") . $balik);
    exit;
}

// langkah 3b: kelas harus dari daftar resmi
// (cegah pengguna nakal kirim nilai sembarangan lewat devtools)
if (!kelasValid($kelas)) {
    header("Location: register.php?error=" . urlencode("Pilihan kelas / status tidak valid!") . $balik);
    exit;
}

// langkah 3c: panjang nama lengkap
if (strlen($namalengkap) < 3 || strlen($namalengkap) > 100) {
    header("Location: register.php?error=" . urlencode("Nama lengkap minimal 3 dan maksimal 100 karakter!") . $balik);
    exit;
}

// langkah 3d: panjang username
if (strlen($username) < 6) {
    header("Location: register.php?error=" . urlencode("Username minimal 6 karakter!") . $balik);
    exit;
}
if (strlen($username) > 50) {
    header("Location: register.php?error=" . urlencode("Username maksimal 50 karakter!") . $balik);
    exit;
}

// langkah 3e: panjang password
if (strlen($password) < 8) {
    header("Location: register.php?error=" . urlencode("Password minimal 8 karakter!") . $balik);
    exit;
}
if (strlen($password) > 100) {
    header("Location: register.php?error=" . urlencode("Password terlalu panjang!") . $balik);
    exit;
}

// langkah 3f: format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.php?error=" . urlencode("Format email tidak valid!") . $balik);
    exit;
}

// langkah 4: bebaskan slot dari akun yang sudah DITOLAK admin sebelumnya.
// akun ditolak tidak bisa login, jadi username & email-nya boleh dipakai lagi.
// tanpa langkah ini, cek duplikat di bawah akan menemukan baris ditolak.
$bersihkan = $conn->prepare(
    "UPDATE tb_user SET deleted=1, deleted_at=NOW()
     WHERE (email=? OR username=?) AND status_verifikasi='ditolak' AND deleted=0"
);
$bersihkan->bind_param("ss", $email, $username);
$bersihkan->execute();
$bersihkan->close();

// langkah 5: cek duplikat — abaikan baris yang sudah dihapus
$cek = $conn->prepare("SELECT id_user FROM tb_user WHERE (email=? OR username=?) AND deleted=0");
$cek->bind_param("ss", $email, $username);
$cek->execute();
$cek->store_result();
if ($cek->num_rows > 0) {
    $cek->close();
    header("Location: register.php?error=" . urlencode("Email atau Username sudah terdaftar!") . $balik);
    exit;
}
$cek->close();

// langkah 6: hash password dan simpan akun
$hash        = password_hash($password, PASSWORD_DEFAULT);
$role        = "pembeli";
$statusVerif = "pending"; // wajib lewat verifikasi admin sebelum bisa login

$stmt = $conn->prepare("INSERT INTO tb_user
    (username, nama_lengkap, kelas, email, password, role, status_verifikasi, deleted)
    VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
$stmt->bind_param("sssssss", $username, $namalengkap, $kelas, $email, $hash, $role, $statusVerif);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: login.php?sukses=" . urlencode("Pendaftaran berhasil! Akunmu sedang menunggu verifikasi admin. Kamu akan bisa login setelah admin menyetujui."));
    exit;
} else {
    $stmt->close();
    header("Location: register.php?error=" . urlencode("Gagal mendaftar, coba lagi nanti.") . $balik);
    exit;
}
?>
