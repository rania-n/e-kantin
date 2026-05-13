<?php
include '../../1. koneksi/koneksi.php';

// Mengambil ID dari URL
$id = $_GET['id'];

// Pastikan ID tidak kosong untuk menghindari error
if (isset($id)) {
    // Perintah SQL untuk Hard Delete (Menghapus permanen dari database)
    $sql = "DELETE FROM tb_toko WHERE id_toko = '$id'";

    if (mysqli_query($conn, $sql)) {
        // Jika berhasil, kembali ke halaman daftar toko
        header("Location: toko.php"); 
        exit();
    } else {
        // Menampilkan error jika gagal, misalnya karena ID toko sedang digunakan di tabel lain (Foreign Key)
        echo "Gagal menghapus data secara permanen: " . mysqli_error($conn);
    }
} else {
    // Jika ID tidak ditemukan di URL
    header("Location: toko.php");
    exit();
}
?>