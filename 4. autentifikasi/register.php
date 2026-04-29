<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<link rel="stylesheet" href="autentifikasi.css">
</head>
<body>

<?php
$error    = $_GET['error']    ?? '';
$username = $_GET['username'] ?? '';
$email    = $_GET['email']    ?? '';
$password = $_GET['password'] ?? '';
?>

<form action="prosesregister.php" method="POST">
<div class="container">

  <h2>Register</h2>
  <div class="sub">Daftar sebagai Pembeli</div>

  <?php if (!empty($error)): ?>
    <p style="color:red; text-align:center; margin-bottom:20px; font-weight:600;">
      <?= htmlspecialchars($error) ?>
    </p>
  <?php endif; ?>

  <input type="text" 
         id="user" 
         placeholder="Username" 
         name="username" 
         value="<?= htmlspecialchars($username) ?>" 
         required>

  <input type="email" 
         id="email" 
         placeholder="Email" 
         name="email" 
         value="<?= htmlspecialchars($email) ?>" 
         required>

  <div class="pass-wrap">
    <input type="password" 
           id="pass" 
           placeholder="Password" 
           name="password" 
           value="<?= htmlspecialchars($password) ?>"
           required>
    <span class="eye" onclick="togglePassword()">👁</span>
  </div>

  <button type="submit">Register</button>

  <div class="link">
    Sudah punya akun? <a href="login.php">Login</a>
  </div>

</div>
</form>

<script>
function togglePassword(){
  const pass = document.getElementById("pass");
  pass.type = pass.type === "password" ? "text" : "password";
}
</script>

</body>
</html>