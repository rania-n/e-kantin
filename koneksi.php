<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "e_kantin"; // Sesuaikan dengan nama database kamu

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>