<?php
/*
  file ini memproses data pendaftaran akun baru yang dikirim dari form di register.php.
  file ini HANYA berisi logika php — tidak ada tampilan html sama sekali.
  pola seperti ini disebut "proses terpisah" agar kode lebih rapi dan mudah dirawat.

  alur kerja:
  1. pastikan request datang dari form (POST)
  2. ambil dan bersihkan data input
  3. validasi semua kolom: tidak boleh kosong, panjang karakter, format email
  4. cek apakah username atau email sudah terdaftar di database (duplikat)
  5. jika semua valid, simpan akun baru ke database dengan password yang sudah di-hash
  6. arahkan ke halaman login dengan pesan sukses, atau kembali ke register jika gagal
*/
include "../1. koneksi/koneksi.php";

// langkah 1: pastikan request ini berasal dari pengiriman form (metode POST)
// jika bukan (misalnya pengguna mengakses url ini langsung), arahkan ke register dan hentikan script
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.php"); exit;
}

// langkah 2: ambil data dari form dan bersihkan spasi di awal/akhir dengan trim()
// operator ?? memastikan script tidak error jika kunci tidak ada di $_POST
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

// langkah 3a: validasi bahwa semua kolom tidak kosong
// empty() mengembalikan true jika string kosong (""), null, atau 0
if (empty($username) || empty($email) || empty($password)) {
    // kembalikan nilai username dan email ke url agar pengguna tidak perlu ketik ulang
    // urlencode() mengubah karakter spesial (spasi, &, =, dll) agar aman dipakai di url
    header("Location: register.php?error=" . urlencode("Semua kolom wajib diisi!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}

// langkah 3b: validasi panjang username — minimal 6 karakter
// strlen() menghitung jumlah karakter dalam string
if (strlen($username) < 6) {
    header("Location: register.php?error=" . urlencode("Username minimal 6 karakter!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}

// validasi panjang username — maksimal 50 karakter (sesuai kolom di database)
if (strlen($username) > 50) {
    header("Location: register.php?error=" . urlencode("Username maksimal 50 karakter!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}

// langkah 3c: validasi panjang password — minimal 8 karakter untuk keamanan dasar
if (strlen($password) < 8) {
    header("Location: register.php?error=" . urlencode("Password minimal 8 karakter!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}

// validasi panjang password — maksimal 100 karakter (batas yang wajar)
if (strlen($password) > 100) {
    header("Location: register.php?error=" . urlencode("Password terlalu panjang!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}

// langkah 3d: validasi format email menggunakan filter bawaan php
// FILTER_VALIDATE_EMAIL memeriksa apakah email memiliki format yang benar (ada @, ada domain, dll)
// mengembalikan false jika format tidak valid
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.php?error=" . urlencode("Format email tidak valid!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}

// langkah 4: cek apakah email atau username sudah terdaftar di database (mencegah duplikat)
// query ini mencari baris yang emailnya SAMA ATAU usernamenya sama dengan input pengguna
$cek = $conn->prepare("SELECT id_user FROM tb_user WHERE email=? OR username=?");
$cek->bind_param("ss", $email, $username);
$cek->execute();
// store_result() diperlukan sebelum bisa mengakses num_rows pada prepared statement
$cek->store_result();
// num_rows > 0 berarti sudah ada data yang cocok — artinya email/username sudah dipakai
if ($cek->num_rows > 0) {
    $cek->close();
    header("Location: register.php?error=" . urlencode("Email atau Username sudah terdaftar!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}
$cek->close();

// langkah 5: semua validasi lolos — simpan akun baru ke database

// hash password menggunakan algoritma bcrypt (password default php)
// password TIDAK BOLEH disimpan dalam bentuk teks asli (plain text) di database karena berbahaya.
// password_hash() mengubah teks password menjadi string acak yang tidak bisa dikembalikan.
// saat login nanti, password_verify() akan membandingkan teks asli dengan hash ini.
//
// catatan penting tentang bcrypt + salt:
// - bcrypt otomatis menambahkan "salt" (string acak unik) ke setiap password sebelum di-hash.
// - akibatnya, dua pengguna yang punya password sama akan menghasilkan hash YANG BERBEDA.
// - ini mencegah serangan rainbow table (tabel hash yang sudah dihitung sebelumnya).
// - salt ikut disimpan di dalam string hash, jadi tidak perlu disimpan di kolom terpisah.
$hash = password_hash($password, PASSWORD_DEFAULT);

// semua pengguna yang daftar sendiri otomatis mendapat peran 'pembeli'
// penjual dan admin hanya bisa dibuat oleh admin lewat panel admin
$role = "pembeli";

// simpan data ke tabel tb_user menggunakan prepared statement (aman dari sql injection)
// deleted=0 berarti akun aktif (belum dihapus)
$stmt = $conn->prepare("INSERT INTO tb_user (username, email, password, role, deleted) VALUES (?, ?, ?, ?, 0)");
$stmt->bind_param("ssss", $username, $email, $hash, $role);

// langkah 6: cek apakah query berhasil dieksekusi
if ($stmt->execute()) {
    // berhasil: tutup koneksi dan arahkan ke login dengan pesan sukses
    $stmt->close();
    header("Location: login.php?sukses=" . urlencode("Registrasi berhasil! Silakan login."));
    exit;
} else {
    // gagal: bisa karena masalah server/database
    // kembalikan pengguna ke form register dengan pesan error umum
    $stmt->close();
    header("Location: register.php?error=" . urlencode("Gagal mendaftar, coba lagi nanti.") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit;
}
?>
