<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<link rel="stylesheet" href="autentifikasi.css">
</head>

<body>

<?php
$error         = $_GET['error'] ?? '';
$usernameemail = $_GET['usernameemail'] ?? '';
?>

<form action="proseslogin.php" method="POST">
<div class="container">

  <h2>Login</h2>
  <div class="sub">Masuk sebagai Pembeli</div>

  <?php if (!empty($error)): ?>
    <p style="color:red; text-align:center; margin-bottom:20px; font-weight:600;">
      <?= htmlspecialchars($error) ?>
    </p>
  <?php endif; ?>

  <input type="text" 
         id="user" 
         placeholder="Username atau Email" 
         name="usernameemail" 
         value="<?= htmlspecialchars($usernameemail) ?>" 
         required>

  <div class="pass-wrap">
    <input type="password" 
           id="pass" 
           placeholder="Password" 
           name="password" 
           required>
    <span class="eye" onclick="toggle()">👁</span>
  </div>

  <button type="submit">Login</button>
  <button class="google" onclick="loginGoogle()">Login dengan Google</button>

  <div class="link">
    Belum punya akun? <a href="register.php">Register</a>
  </div>

</div>
</form>

<script>
function toggle(){
  const p = document.getElementById("pass");
  p.type = p.type === "password" ? "text" : "password";
}

function loginGoogle(){
  alert("Login dengan Google belum dihubungkan, ini masih tampilan frontend.");
}
</script>

</body>
</html>