<?php
/* ============================================================
   proses edit profil dan toko penjual
   menangani upload foto toko jika ada
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profil.php"); exit;
}

$idpengguna   = (int)$_SESSION['id_user'];
$idtoko       = (int)$_SESSION['id_toko'];

$usernamebaru = trim($_POST['username']  ?? '');
$emailbaru    = trim($_POST['email']     ?? '');
$namatokobaru = trim($_POST['nama_toko'] ?? '');
$err = [];

if (strlen($usernamebaru) < 6)                        $err[] = "Username minimal 6 karakter.";
if (strlen($usernamebaru) > 50)                       $err[] = "Username maksimal 50 karakter.";
if (!filter_var($emailbaru, FILTER_VALIDATE_EMAIL))   $err[] = "Format email tidak valid.";
if (empty($namatokobaru))                             $err[] = "Nama toko wajib diisi.";
if (strlen($namatokobaru) > 100)                      $err[] = "Nama toko maksimal 100 karakter.";

// proses upload foto jika ada
$namafotobaru = null;
$adafoto = isset($_FILES['foto_toko']) && $_FILES['foto_toko']['error'] === UPLOAD_ERR_OK;
if ($adafoto) {
    $fotofile = $_FILES['foto_toko'];
    $ekstensi = strtolower(pathinfo($fotofile['name'], PATHINFO_EXTENSION));
    $tipediizinkan = ['jpg','jpeg','png','webp'];
    $maksukuran = 2 * 1024 * 1024; // 2mb

    if (!in_array($ekstensi, $tipediizinkan)) {
        $err[] = "Format foto tidak didukung. Gunakan jpg, png, atau webp.";
    } elseif ($fotofile['size'] > $maksukuran) {
        $err[] = "Ukuran foto maksimal 2MB.";
    } else {
        $namafotobaru = 'toko_' . $idtoko . '_' . time() . '.' . $ekstensi;
    }
}

if (empty($err)) {
    $cd = $conn->prepare("SELECT id_user FROM tb_user WHERE (username=? OR email=?) AND id_user!=? AND deleted=0");
    $cd->bind_param("ssi", $usernamebaru, $emailbaru, $idpengguna); $cd->execute();
    if ($cd->get_result()->num_rows) $err[] = "Username atau email sudah digunakan akun lain.";
    $cd->close();
}

if (empty($err)) {
    // simpan foto ke folder aset jika ada
    if ($namafotobaru) {
        $targetdir = __DIR__ . '/../../2. aset/profil/';
        if (!is_dir($targetdir)) mkdir($targetdir, 0755, true);
        move_uploaded_file($_FILES['foto_toko']['tmp_name'], $targetdir . $namafotobaru);
    }

    $upd = $conn->prepare("UPDATE tb_user SET username=?, email=? WHERE id_user=?");
    $upd->bind_param("ssi", $usernamebaru, $emailbaru, $idpengguna);
    $upd->execute(); $upd->close();

    if ($namafotobaru) {
        // kolom foto_toko harus ada di tb_toko — jalankan ALTER jika belum ada
        $updt = $conn->prepare("UPDATE tb_toko SET nama_toko=?, foto_toko=? WHERE id_toko=?");
        $updt->bind_param("ssi", $namatokobaru, $namafotobaru, $idtoko);
    } else {
        $updt = $conn->prepare("UPDATE tb_toko SET nama_toko=? WHERE id_toko=?");
        $updt->bind_param("si", $namatokobaru, $idtoko);
    }
    $updt->execute(); $updt->close();

    $_SESSION['username']  = $usernamebaru;
    $_SESSION['email']     = $emailbaru;
    $_SESSION['nama_toko'] = $namatokobaru;
    $_SESSION['flash']     = ['pesan' => 'Profil dan info toko berhasil diperbarui!', 'jenis' => 'sukses'];
    header("Location: profil.php"); exit;
} else {
    $_SESSION['flash'] = ['pesan' => implode(' ', $err), 'jenis' => 'gagal'];
    header("Location: profil.php?tab=edit"); exit;
}
?>
