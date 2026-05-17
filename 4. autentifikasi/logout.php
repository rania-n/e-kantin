<?php
session_start();
session_unset();
session_destroy();
header("Location: ../4. autentifikasi/login.php");
exit;
?>