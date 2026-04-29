<?php

include "../1. koneksi/koneksi.php"; 
session_start();

$error         = "";
$usernameemail = "";
$password      = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usernameemail = trim($_POST['usernameemail']);
    $password      = trim($_POST['password']);

    if (empty($usernameemail) || empty($password)) {
        $error = "Username/Email dan Password wajib diisi!";
    } 
    else {
        // Cek username atau email
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
    
                    // DEBUG (hapus setelah berhasil)
            echo "Password input: " . $password . "<br>";
            echo "Hash dari DB: " . $hashed_password . "<br>";
        if (password_verify($password, $hashed_password)) {
    $_SESSION['id_user']  = $id_user;
    $_SESSION['username'] = $db_username;   // ← pakai yang db
    $_SESSION['email']    = $db_email;      // ← pakai yang db

    echo "<script>
            alert('Login berhasil! Selamat datang, " . htmlspecialchars($db_username) . "'););
                        window.location.href = '../pembeli/index.php';
                      </script>";
                exit;
            } else {
                $error = "Password salah!";
            }
        }
        $statement->close();
    }
}

header("Location: login.php?error=" . urlencode($error) 
       . "&usernameemail=" . urlencode($usernameemail) 
       . "&password=" . urlencode($password));
exit;
?>