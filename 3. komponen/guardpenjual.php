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

// jika id_toko belum tersimpan di session, ambil data toko dari database
// ini terjadi misalnya saat baru login atau session belum lengkap
if (empty($_SESSION['id_toko'])) {

    // pastikan koneksi database tersedia — kalau belum di-include, include sekarang
    if (!isset($conn)) {
        require_once __DIR__ . '/../../1. koneksi/koneksi.php';
    }

    // ambil id_user dari session dan konversi ke integer untuk keamanan
    $iduser = (int)$_SESSION['id_user'];

    // cari data toko milik penjual ini berdasarkan id_user
    // deleted=0 berarti toko belum dihapus (soft delete)
    $qt = $conn->prepare("SELECT id_toko, nama_toko, status_toko FROM tb_toko WHERE id_user=? AND deleted=0 LIMIT 1");
    $qt->bind_param("i", $iduser); // "i" berarti tipe data integer
    $qt->execute();
    $datatoko = $qt->get_result()->fetch_assoc(); // ambil satu baris hasil query sebagai array asosiatif
    $qt->close(); // tutup statement untuk membebaskan memori

    if ($datatoko) {
        // jika data toko ditemukan, simpan ke session agar tidak perlu query ulang
        $_SESSION['id_toko']     = $datatoko['id_toko'];
        $_SESSION['nama_toko']   = $datatoko['nama_toko'];
        $_SESSION['status_toko'] = $datatoko['status_toko'] ?? 'buka'; // default buka jika kolom kosong
    } else {
        // penjual belum punya toko di database — isi session dengan nilai default
        // redirect ke halaman setup toko bisa diaktifkan di sini nanti jika diperlukan
        $_SESSION['id_toko']     = 0;
        $_SESSION['nama_toko']   = 'Toko Saya';
        $_SESSION['status_toko'] = 'buka';
    }
}
?>
