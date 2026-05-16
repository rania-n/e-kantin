<?php
include '../../1. koneksi/koneksi.php';

if (isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = 'penjual'; // Dikunci khusus penjual

    // --- PROSES SELEKSI (Validasi Duplikat) ---
    $cek_username = mysqli_query($conn, "SELECT username FROM tb_user WHERE username = '$username'");
    $cek_email = mysqli_query($conn, "SELECT email FROM tb_user WHERE email = '$email'");

    if (mysqli_num_rows($cek_username) > 0) {
        echo "<script>alert('Gagal! Username ini sudah terdaftar.'); window.history.back();</script>";
    } else if (mysqli_num_rows($cek_email) > 0) {
        echo "<script>alert('Gagal! Email ini sudah digunakan.'); window.history.back();</script>";
    } else {
        // --- PROSES INSERT USER ---
        $query_user = "INSERT INTO tb_user (username, email, password, role, deleted) 
                       VALUES ('$username', '$email', '$password', '$role', 0)";
        
        if (mysqli_query($conn, $query_user)) {
            $id_user_baru = mysqli_insert_id($conn);

            // --- OTOMATIS BUAT TOKO ---
            $nama_toko = "Toko " . $username;
            $query_toko = "INSERT INTO tb_toko (id_user, nama_toko, deleted) 
                           VALUES ('$id_user_baru', '$nama_toko', 0)";
            mysqli_query($conn, $query_toko);

            echo "<script>alert('Berhasil menambah Penjual dan Toko baru!'); window.location='user.php';</script>";
        } else {
            echo "Gagal: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Penjual Baru</title>
    <link rel="stylesheet" href="../../3. komponen/admin.css">
    <style>
        .form-container { 
            background: white; 
            padding: 25px; 
            border-radius: 12px; 
            max-width: 500px; 
            margin: 30px auto; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #555;
        }
        .form-group input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            box-sizing: border-box; 
            transition: 0.3s;
        }
        .form-group input:focus {
            border-color: #845b5b;
            outline: none;
            box-shadow: 0 0 0 2px rgba(132, 91, 91, 0.1);
        }
        .btn-submit { 
            background: #845b5b; 
            color: white; 
            border: none; 
            padding: 14px; 
            cursor: pointer; 
            border-radius: 8px; 
            width: 100%; 
            font-size: 16px; 
            font-weight: bold;
        }
        .btn-submit:hover { background: #6d4a4a; }
        .btn-batal { 
            display: block; 
            text-align: center; 
            margin-top: 15px; 
            color: #888; 
            text-decoration: none; 
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include '../../3. komponen/sidebaradmin.html'; ?>
    
    <div class="content">
        <div style="text-align: center; margin-top: 20px;">
            <h2>Tambah Penjual</h2>
            <p>Sistem akan otomatis membuatkan akun login dan profil toko.</p>
        </div>

        <div class="form-container">
            <form action="" method="POST">
                <!-- Role dikirim secara tersembunyi (Hidden) -->
                <input type="hidden" name="role" value="penjual">

                <div class="form-group">
                    <label>Username / Nama Akun</label>
                    <input type="text" name="username" required placeholder="Misal: kantin_sehat">
                </div>
                
                <div class="form-group">
                    <label>Email Aktif</label>
                    <input type="email" name="email" required placeholder="kantin@sekolah.com">
                </div>

                <div class="form-group">
                    <label>Password Akun</label>
                    <input type="password" name="password" required placeholder="Minimal 8 karakter">
                </div>

                <button type="submit" name="submit" class="btn-submit">Daftarkan Penjual</button>
                <a href="user.php" class="btn-batal">Kembali ke Manajemen User</a>
            </form>
        </div>
    </div>
</body>
</html>