<?php
session_start();
include '../../1. koneksi/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'penjual') {
    header("Location: ../../4. autentifikasi/login.php");
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$filter = $_POST['filter'] ?? $_GET['filter'] ?? 'Semua';

try {
    if ($action === 'add' || $action === 'edit') {
        $nama      = trim($_POST['nama_menu']);
        $harga     = (int)$_POST['harga'];
        $stok      = (int)$_POST['stok'];
        $kategori  = trim($_POST['kategori']);
        $deskripsi = trim($_POST['deskripsi']);

        // === PERBAIKAN FOTO EDIT ===
        $foto = '';
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            // Ada upload foto baru
            $allowed = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                throw new Exception('Format gambar hanya jpg, jpeg, png, webp!');
            }
            $foto = uniqid() . '.' . $ext;
            $target = '../../2. aset/katalog/' . $foto;
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
                throw new Exception('Gagal mengupload gambar');
            }
        } else {
            // TIDAK upload foto baru → pakai foto lama
            $foto = $_POST['foto_lama'] ?? '';
        }

        if ($action === 'add') {
            $sql = "INSERT INTO tb_menu (nama_menu, harga, stok, kategori, deskripsi, foto, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'aktif')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "siisss", $nama, $harga, $stok, $kategori, $deskripsi, $foto);
            mysqli_stmt_execute($stmt);
            $pesan = "Menu berhasil ditambahkan!";
            $tipe  = "success";
        } 
        else { // edit
            $id = (int)$_POST['id_menu'];
            $sql = "UPDATE tb_menu SET nama_menu=?, harga=?, stok=?, kategori=?, deskripsi=?, foto=? WHERE id_menu=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "siisssi", $nama, $harga, $stok, $kategori, $deskripsi, $foto, $id);
            mysqli_stmt_execute($stmt);
            $pesan = "Menu berhasil diupdate!";
            $tipe  = "success";
        }
    }

    elseif ($action === 'delete') {
        $id = (int)$_GET['id'];
        $sql = "UPDATE tb_menu SET deleted=1, deleted_at=NOW(), status='nonaktif' WHERE id_menu=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $pesan = "Menu berhasil dihapus!";
        $tipe  = "success";
    }

    elseif ($action === 'toggle') {
        $id = (int)$_GET['id'];
        $sql = "UPDATE tb_menu SET status = IF(status='aktif','nonaktif','aktif') WHERE id_menu=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $pesan = "Status menu berhasil diubah!";
        $tipe  = "success";
    }

} catch (Exception $e) {
    $pesan = $e->getMessage();
    $tipe  = "danger";
}

header("Location: manajemenmenu.php?filter=" . urlencode($filter) . "&pesan=" . urlencode($pesan) . "&tipe=" . $tipe);
exit();
?>