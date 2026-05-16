<?php
include '../../1. koneksi/koneksi.php';

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (empty($id)) {
    header("Location: user.php");
    exit();
}

$query = mysqli_query($conn, "SELECT * FROM tb_user WHERE id_user = '$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    header("Location: user.php");
    exit();
}

$sudah_penjual = ($data['role'] == 'penjual');

if (isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    
    $role = ($sudah_penjual) ? 'penjual' : $_POST['role'];
    
    $cek_username = mysqli_query($conn, "SELECT * FROM tb_user WHERE username = '$username' AND id_user != '$id'");
    $cek_email = mysqli_query($conn, "SELECT * FROM tb_user WHERE email = '$email' AND id_user != '$id'");
    
    if (mysqli_num_rows($cek_username) > 0) {
        echo "<script>alert('Gagal! Username sudah terdaftar.'); window.history.back();</script>";
    } else if (mysqli_num_rows($cek_email) > 0) {
        echo "<script>alert('Gagal! Email sudah digunakan.'); window.history.back();</script>";
    } else {
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql_update = "UPDATE tb_user SET username='$username', email='$email', role='$role', password='$password' WHERE id_user='$id'";
        } else {
            $sql_update = "UPDATE tb_user SET username='$username', email='$email', role='$role' WHERE id_user='$id'";
        }

        if (mysqli_query($conn, $sql_update)) {
            echo "<script>alert('Data berhasil diperbarui!'); window.location='user.php';</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - <?= htmlspecialchars($data['username']); ?></title>
    <link rel="stylesheet" href="../../3. komponen/admin.css">
</head>
<body>
    <?php include '../../3. komponen/sidebaradmin.html'; ?>

    <div class="content">
        <h2 style="margin-bottom: 24px;">Edit Pengguna</h2>

        <form action="" method="POST" class="card">
            <div class="row">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($data['username']); ?>" required>
            </div>
            
            <div class="row">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($data['email']); ?>" required>
            </div>

            <div class="row">
                <label>Password Baru</label>
                <input type="password" name="password" placeholder="Kosongkan jika tidak ingin ganti">
            </div>

            <div class="row">
                <label>Role</label>
                <?php if ($sudah_penjual) : ?>
                   
                    <select name="role" style="background-color: #f5f5f5; cursor: not-allowed;" onchange="this.value='penjual'">
                        <option value="penjual" selected>Penjual (Terkunci)</option>
                    </select>
                    <small style="color: #d32f2f; font-size: 11px;">*Status Penjual tidak dapat diubah kembali menjadi Pembeli.</small>
                <?php else : ?>
                    <select name="role">
                        <option value="pembeli" <?= $data['role'] == 'pembeli' ? 'selected' : ''; ?>>Pembeli</option>
                        <option value="penjual" <?= $data['role'] == 'penjual' ? 'selected' : ''; ?>>Penjual</option>
                        <option value="admin" <?= $data['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                <?php endif; ?>
            </div>

            <div class="buttons">
                <button type="submit" name="submit" class="btn save">Simpan Perubahan</button>
                <a href="user.php" class="btn cancel" style="text-decoration: none; text-align: center; line-height: 40px;">Batal</a>
            </div>
        </form>
    </div>
</body>
</html> 