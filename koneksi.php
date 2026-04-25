<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_e-canteen"; // Sesuaikan dengan nama database kamu

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>