<?php
include '../../1. koneksi/koneksi.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // 1. Soft Delete Toko milik user tersebut
    $sql_toko = "UPDATE tb_toko SET deleted = 1 WHERE id_user = '$id'";
    mysqli_query($conn, $sql_toko);

    // 2. Soft Delete User (Update kolom deleted menjadi 1)
    $sql_user = "UPDATE tb_user SET deleted = 1 WHERE id_user = '$id'";
    
    if (mysqli_query($conn, $sql_user)) {
        header("Location: user.php?status=sukses_hapus");
        exit(); 
    } else {
        echo "Gagal memproses soft delete: " . mysqli_error($conn);
    }
} else {
    header("Location: user.php");
    exit();
}
?>