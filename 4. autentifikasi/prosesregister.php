<?php

include "../1. koneksi/koneksi.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($email) || empty($password)) {
        header("Location: register.php?error=Semua field wajib diisi!&username=" . urlencode($username) . "&email=" . urlencode($email) . "&password=" . urlencode($password));
        exit;
    }

    if (strlen($username) < 6) {
        header("Location: register.php?error=Username minimal 6 karakter!&username=" . urlencode($username) . "&email=" . urlencode($email) . "&password=" . urlencode($password));
        exit;
    }

    if (strlen($password) < 8) {
        header("Location: register.php?error=Password minimal 8 karakter!&username=" . urlencode($username) . "&email=" . urlencode($email) . "&password=" . urlencode($password));
        exit;
    }

    $statement = $conn->prepare("SELECT id_user FROM tb_user WHERE email = ? OR username = ?");
    $statement->bind_param("ss", $email, $username);
    $statement->execute();
    $statement->store_result();

    if ($statement->num_rows > 0) {
        $statement->close();
        header("Location: register.php?error=Email atau Username sudah terdaftar!&username=" . urlencode($username) . "&email=" . urlencode($email) . "&password=" . urlencode($password));
        exit;
    }
    $statement->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = "pembeli";

    $statement = $conn->prepare("INSERT INTO tb_user (username, email, password, role) 
                            VALUES (?, ?, ?, ?)");
    $statement->bind_param("ssss", $username, $email, $hash, $role);

    if ($statement->execute()) {
        echo "<script>
                alert('Registrasi berhasil! Silakan login untuk melanjutkan.');
                window.location='login.php';
              </script>";
    } else {
        header("Location: register.php?error=Gagal mendaftar. Coba lagi nanti!");
    }

    $statement->close();
}
?>