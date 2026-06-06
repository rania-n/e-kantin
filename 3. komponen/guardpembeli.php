<?php
/*
   guard pembeli — file ini bertugas menjaga halaman agar hanya bisa
   diakses oleh pengguna yang sudah login dengan peran pembeli.
   cara kerja: file ini di-include di bagian paling atas setiap halaman pembeli,
   sebelum konten apapun ditampilkan.
*/

// cek apakah session sudah aktif, kalau belum maka mulai session
// session_status() mengembalikan PHP_SESSION_NONE jika session belum berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_name('sesi_pembeli'); // gunakan nama session khusus pembeli agar tidak bentrok dengan session penjual/admin
    session_start(); // mulai session sehingga data login bisa dibaca dari $_SESSION
}

/*
   cek dua kondisi sekaligus dengan operator ||:
   1. $_SESSION['id_user'] kosong — artinya belum login sama sekali
   2. $_SESSION['role'] bukan 'pembeli' — artinya login tapi bukan sebagai pembeli

   path ke login: file ini ada di 3. komponen/, dipanggil dari pembeli/sub/file.php
   jadi path relatifnya naik dua level: ../../4. autentifikasi/login.php
*/
if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'pembeli') {
    // arahkan pengguna ke halaman login dan hentikan eksekusi script
    header("Location: ../../4. autentifikasi/login.php");
    exit; // exit wajib dipanggil setelah header redirect agar kode di bawahnya tidak ikut dijalankan
}

// SELALU refresh data user dari database setiap request — supaya update yang dilakukan
// admin (username, email, foto) langsung terlihat tanpa perlu logout/login dulu.
// kalau cuma mengandalkan data session saat login, perubahan baru muncul setelah logout.
if (!isset($conn)) { require_once __DIR__ . '/../1. koneksi/koneksi.php'; } // load koneksi kalau belum di-include
$iduser = (int)$_SESSION['id_user']; // cast ke int sebagai pengaman ekstra dari injection
// prepared statement: query disiapkan dulu lalu parameter diikat terpisah —
// ini mencegah sql injection karena nilai $iduser tidak digabung langsung ke string query.
$qu = $conn->prepare("SELECT username, email, foto, status_akun FROM tb_user WHERE id_user=? AND deleted=0");
$qu->bind_param("i", $iduser); $qu->execute(); // "i" = tipe integer untuk parameter pertama
$datauser = $qu->get_result()->fetch_assoc(); $qu->close(); // ambil 1 baris sebagai array asosiatif lalu tutup statement
if ($datauser) {
    // jika admin menonaktifkan akun saat session masih aktif → paksa logout
    if (($datauser['status_akun'] ?? 'aktif') === 'nonaktif') {
        session_destroy();
        header("Location: ../../4. autentifikasi/login.php?error=" . urlencode("Akunmu sedang dinonaktifkan oleh admin."));
        exit;
    }
    // timpa data session dengan data terbaru dari database
    $_SESSION['username'] = $datauser['username'];
    $_SESSION['email']    = $datauser['email'];
    $_SESSION['foto']     = $datauser['foto'];
} else {
    // akun ternyata sudah dihapus admin saat session masih aktif → paksa logout
    // session_destroy() menghapus semua data session di server agar pengguna benar-benar keluar
    session_destroy();
    // urlencode supaya pesan error aman jadi parameter url (spasi, simbol, dll)
    header("Location: ../../4. autentifikasi/login.php?error=" . urlencode("Akun sudah tidak aktif."));
    exit;
}
?>
