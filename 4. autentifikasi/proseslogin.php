<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../1. koneksi/koneksi.php"; 
session_start();

echo "<h3>🔧 DEBUG LOGIN MODE (hapus nanti)</h3>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "✅ POST diterima<br>";

    $usernameemail = trim($_POST['usernameemail'] ?? '');
    $password      = trim($_POST['password'] ?? '');

    echo "Username/Email input: " . htmlspecialchars($usernameemail) . "<br>";
    echo "Password length: " . strlen($password) . " karakter<br>";

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

        echo "Jumlah data ditemukan di DB: " . $statement->num_rows . "<br>";

        if ($statement->num_rows === 0) {
            $error = "Username atau Email tidak ditemukan!";
        } 
        else {
            $statement->bind_result($id_user, $db_username, $db_email, $hashed_password, $role);
            $statement->fetch();

            echo "Hash dari database: " . htmlspecialchars($hashed_password) . "<br>";
            echo "Panjang hash: " . strlen($hashed_password) . " karakter<br>";

            // Cek password_verify
            $verify_result = password_verify($password, $hashed_password);
            echo "password_verify() hasil: " . ($verify_result ? '<b style="color:green">TRUE → LOGIN BERHASIL</b>' : '<b style="color:red">FALSE → Password salah</b>') . "<br>";

            if ($verify_result) {
                // Set session
                $_SESSION['id_user']  = $id_user;
                $_SESSION['username'] = $db_username;
                $_SESSION['email']    = $db_email;

                echo "<h3 style='color:green'>✅ Login berhasil! Session sudah diset.</h3>";
                echo "<script>
                        alert('Login berhasil! Selamat datang, " . addslashes($db_username) . "');
                        window.location.href = '../pembeli/index.php';
                      </script>";
                echo '<br><a href="../pembeli/index.php" style="font-size:18px;">→ Klik sini kalau tidak otomatis redirect</a>';
                exit;
            } 
            else {
                $error = "Password salah!";
            }
        }
        $statement->close();
    }
}

if (isset($error)) {
    echo "<br><b style='color:red'>Error: " . htmlspecialchars($error) . "</b>";
}

// Redirect normal kalau tidak ada debug
header("Location: login.php?error=" . urlencode($error ?? '') 
       . "&usernameemail=" . urlencode($usernameemail ?? ''));
exit;
?>