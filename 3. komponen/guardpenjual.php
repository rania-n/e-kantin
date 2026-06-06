<?php
/*
   guard penjual — file ini menjaga halaman penjual agar hanya bisa diakses
   oleh pengguna yang sudah login dengan peran penjual.
   selain mengecek login, guard ini juga mengambil data toko penjual dari database
   dan menyimpannya ke session supaya bisa dipakai di seluruh halaman penjual
   tanpa perlu query ulang setiap saat.
*/

// cek apakah session sudah aktif, kalau belum maka mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_name('sesi_penjual'); // nama session khusus penjual agar terpisah dari pembeli/admin
    session_start();
}

// jika id_user kosong atau role bukan penjual, tolak akses dan redirect ke login
if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'penjual') {
    header("Location: ../../4. autentifikasi/login.php");
    exit; // hentikan eksekusi agar kode berikutnya tidak dijalankan
}

// SELALU refresh data toko & username dari database setiap kali halaman penjual dibuka.
// supaya perubahan yang dilakukan admin (edit nama toko, foto, dll) langsung terlihat
// tanpa perlu logout/login dulu. cost-nya cuma 2 query ringan per request.
if (!isset($conn)) {
    // __DIR__ = folder file ini berada → path absolut, tidak bergantung dari mana file dipanggil
    require_once __DIR__ . '/../1. koneksi/koneksi.php';
}
$iduser = (int)$_SESSION['id_user']; // cast ke int sebagai pengaman ekstra

// refresh username/email/foto dari tb_user
// pakai prepared statement: aman dari sql injection karena parameter dipisah dari query
$qu = $conn->prepare("SELECT username, email, foto, status_akun FROM tb_user WHERE id_user=? AND deleted=0");
$qu->bind_param("i", $iduser); $qu->execute(); // bind parameter integer ke ?
$datauser = $qu->get_result()->fetch_assoc(); $qu->close(); // ambil 1 baris hasil
if ($datauser) {
    // jika admin menonaktifkan akun penjual saat session masih aktif → paksa logout
    if (($datauser['status_akun'] ?? 'aktif') === 'nonaktif') {
        session_destroy();
        header("Location: ../../4. autentifikasi/login.php?error=" . urlencode("Akunmu sedang dinonaktifkan oleh admin."));
        exit;
    }
    // overwrite data session pakai data terbaru dari db
    $_SESSION['username'] = $datauser['username'];
    $_SESSION['email']    = $datauser['email'];
    $_SESSION['foto']     = $datauser['foto'];
}

// refresh data toko dari tb_toko
// LIMIT 1 karena 1 penjual hanya punya 1 toko aktif
$qt = $conn->prepare("SELECT id_toko, nama_toko, status_toko, foto_toko FROM tb_toko WHERE id_user=? AND deleted=0 LIMIT 1");
$qt->bind_param("i", $iduser); $qt->execute();
$datatoko = $qt->get_result()->fetch_assoc(); $qt->close();
if ($datatoko) {
    // simpan info toko ke session agar bisa dipakai di seluruh halaman tanpa query ulang
    $_SESSION['id_toko']     = $datatoko['id_toko'];
    $_SESSION['nama_toko']   = $datatoko['nama_toko'];
    // operator ?? (null coalescing) — pakai nilai sebelah kiri kalau tidak null, kalau null pakai sebelah kanan
    $_SESSION['status_toko'] = $datatoko['status_toko'] ?? 'buka';
    $_SESSION['foto_toko']   = $datatoko['foto_toko'];
} else {
    // penjual belum/tidak punya toko aktif — default supaya halaman tidak rusak
    // (mencegah error "undefined index" saat halaman penjual mencoba membaca data toko)
    $_SESSION['id_toko']     = 0;
    $_SESSION['nama_toko']   = 'Toko Saya';
    $_SESSION['status_toko'] = 'buka';
    $_SESSION['foto_toko']   = null;
}
?>
