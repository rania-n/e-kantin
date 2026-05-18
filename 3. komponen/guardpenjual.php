<?php
/* ============================================================
   GUARD PENJUAL
   Cek login role penjual. Juga ambil id_toko & nama_toko
   ke session kalau belum ada, agar bisa dipakai di semua halaman.
   ============================================================ */
if (session_status() === PHP_SESSION_NONE) {
    session_name('sesi_penjual');
    session_start();
}

if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'penjual') {
    header("Location: ../../4. autentifikasi/login.php");
    exit;
}

// Kalau id_toko belum ada di session, ambil dari DB
if (empty($_SESSION['id_toko'])) {
    // koneksi.php harus sudah di-include sebelum guard ini
    if (!isset($conn)) {
        require_once __DIR__ . '/../../1. koneksi/koneksi.php';
    }
    $iduser = (int)$_SESSION['id_user'];
    $qt = $conn->prepare("SELECT id_toko, nama_toko, status_toko FROM tb_toko WHERE id_user=? AND deleted=0 LIMIT 1");
    $qt->bind_param("i", $iduser);
    $qt->execute();
    $datatoko = $qt->get_result()->fetch_assoc();
    $qt->close();
    if ($datatoko) {
        $_SESSION['id_toko']     = $datatoko['id_toko'];
        $_SESSION['nama_toko']   = $datatoko['nama_toko'];
        $_SESSION['status_toko'] = $datatoko['status_toko'] ?? 'buka';
    } else {
        // Penjual belum punya toko — redirect ke setup (bisa diaktifkan nanti)
        // Untuk sekarang biarkan lanjut
        $_SESSION['id_toko']     = 0;
        $_SESSION['nama_toko']   = 'Toko Saya';
        $_SESSION['status_toko'] = 'buka';
    }
}
?>
