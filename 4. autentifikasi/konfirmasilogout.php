<?php
/*
  halaman ini menampilkan konfirmasi sebelum pengguna benar-benar keluar (logout).
  tujuannya: mencegah pengguna tidak sengaja keluar hanya karena salah klik.

  halaman ini berfungsi ganda:
  - jika diakses dengan GET (pertama kali dibuka dari tombol logout): tampilkan dialog konfirmasi
  - jika diakses dengan POST dan tombol "ya, keluar" diklik: jalankan proses logout

  pendekatan ini murni php + html tanpa javascript — cocok untuk proyek sekolah.
*/

// ambil nilai 'peran' dari form POST atau dari query string GET
// 'peran' menentukan sesi mana yang akan dihapus (pembeli, penjual, atau admin)
$peran = $_POST['peran'] ?? $_GET['peran'] ?? '';

// cek apakah pengguna sudah mengklik tombol "ya, keluar"
// kondisi ini terpenuhi hanya jika: request adalah POST DAN nilai hidden input 'aksi' adalah 'keluar'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'keluar') {

    // tentukan nama sesi yang harus dihapus berdasarkan peran
    // setiap peran memiliki nama sesi sendiri agar tidak mengganggu sesi peran lain
    $namaSesi = match($peran) {
        'penjual' => 'sesi_penjual',
        'admin'   => 'sesi_admin',
        default   => 'sesi_pembeli',
    };

    // terapkan nama sesi yang tepat sebelum memulai sesi
    session_name($namaSesi);

    // mulai sesi hanya jika belum aktif — mencegah error "session already started"
    if (session_status() === PHP_SESSION_NONE) session_start();

    // kosongkan semua data di dalam sesi (hapus semua variabel $_SESSION)
    $_SESSION = [];

    // hancurkan sesi di server — menghapus file sesi dari penyimpanan server
    session_destroy();

    // tutup sesi dan pastikan semua perubahan ditulis sebelum redirect
    session_write_close();

    // arahkan ke halaman landing (beranda utama) setelah logout berhasil
    // semua peran (pembeli/penjual/admin) jatuh ke landing yang sama,
    // dari sana user bisa pilih mau login lagi atau keluar dari situs
    header("Location: ../index.php");
    exit;
}

// jika pengguna belum mengklik "ya, keluar", tentukan url halaman "batal / kembali"
// url ini dipakai di tombol batal agar pengguna kembali ke halaman yang sesuai perannya
$kembali = match($peran) {
    'penjual' => '../penjual/index/index.php',
    'pembeli' => '../pembeli/index/index.php',
    'admin'   => '../admin/index/index.php',
    default   => 'login.php', // jika peran tidak dikenali, arahkan ke login
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Konfirmasi Keluar - jajankita</title>
<!-- font poppins dari google fonts untuk tampilan yang rapi -->
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<!-- css bersama untuk halaman autentifikasi -->
<link rel="stylesheet" href="../3. komponen/autentifikasi.css">
<!-- library ikon font-awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="container" style="text-align:center;">
  <!-- ikon logout besar sebagai penanda visual halaman konfirmasi -->
  <div style="font-size:52px; color:#643843; margin-bottom:16px;">
    <i class="fa-solid fa-right-from-bracket"></i>
  </div>
  <h2>Yakin ingin keluar?</h2>
  <div class="sub">Kamu perlu login lagi untuk mengakses jajankita</div>

  <!-- form konfirmasi logout -->
  <!-- saat tombol "ya, keluar" diklik, form ini dikirim ke halaman ini sendiri (action ke konfirmasilogout.php) -->
  <!-- php di atas halaman akan menangkap POST ini dan menjalankan proses logout -->
  <form method="POST" action="konfirmasilogout.php">
    <!-- hidden input 'aksi' memberi tanda ke server bahwa pengguna mengkonfirmasi logout -->
    <input type="hidden" name="aksi" value="keluar">
    <!-- hidden input 'peran' meneruskan nilai peran agar server tahu sesi mana yang harus dihapus -->
    <!-- htmlspecialchars() dipakai untuk keamanan agar nilai peran tidak bisa menyisipkan html berbahaya -->
    <input type="hidden" name="peran" value="<?= htmlspecialchars($peran) ?>">
    <button type="submit">
      <i class="fa-solid fa-right-from-bracket"></i> Ya, Keluar
    </button>
  </form>

  <!-- tautan batal — mengarahkan pengguna kembali ke halaman utama sesuai perannya -->
  <!-- $kembali sudah ditentukan di bagian php di atas -->
  <div class="link">
    <a href="<?= htmlspecialchars($kembali) ?>">
      <i class="fa-solid fa-arrow-left"></i> Batal, kembali
    </a>
  </div>
</div>

</body>
</html>
