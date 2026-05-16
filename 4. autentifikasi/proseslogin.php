<?php
include "../1. koneksi/koneksi.php"; 
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usernameemail = trim($_POST['usernameemail'] ?? '');
    $password      = trim($_POST['password'] ?? '');

    // Validasi kosong
    if (empty($usernameemail) || empty($password)) {
        $error = "Username/Email dan Password wajib diisi!";
    } 
    else {
        // Query
        $statement = $conn->prepare("SELECT id_user, username, email, password, role 
                                     FROM tb_user 
                                     WHERE username = ? OR email = ?");
        $statement->bind_param("ss", $usernameemail, $usernameemail);
        $statement->execute();
        $statement->store_result();

        if ($statement->num_rows === 0) {
            $error = "Username atau Email tidak ditemukan!";
        } 
        else {
            $statement->bind_result($id_user, $db_username, $db_email, $hashed_password, $role);
            $statement->fetch();

            // Cek password
            if (password_verify($password, $hashed_password)) {
                // Set session
                $_SESSION['id_user']  = $id_user;
                $_SESSION['username'] = $db_username;
                $_SESSION['email']    = $db_email;
                $_SESSION['role']     = $role;

                // ====================== REDIRECT BERDASARKAN ROLE ======================
                if ($role === 'admin') {
                    $redirect_url = '../admin/index/index.php';
                } 
                elseif ($role === 'penjual') {
                    $redirect_url = '../penjual/index/index.php';
                } 
                elseif ($role === 'pembeli') {
                    $redirect_url = '../pembeli/index.php';
                } 
                else {
                    $redirect_url = '../4. autentifikasi/login.php'; // default kalau role tidak dikenali
                }

                // Redirect otomatis
                echo "<script>
                        alert('Login berhasil! Selamat datang, " . addslashes($db_username) . "');
                        window.location.href = '" . $redirect_url . "';
                      </script>";
                echo '<br><a href="' . $redirect_url . '" style="font-size:18px;">→ Klik di sini jika tidak otomatis redirect</a>';
                exit;
            } 
            else {
                $error = "Password salah!";
            }
        }
        $statement->close();
    }
}

// Jika ada error, kembali ke halaman login
if (isset($error)) {
    header("Location: login.php?error=" . urlencode($error) 
           . "&usernameemail=" . urlencode($usernameemail ?? ''));
    exit;
}
?>