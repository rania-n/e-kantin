<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - eKantin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<link rel="stylesheet" href="autentifikasi.css">
</head>

<body>

<?php
$error         = $_GET['error']         ?? '';
$sukses        = $_GET['sukses']        ?? '';
$usernameemail = $_GET['usernameemail'] ?? '';
?>

<form action="proseslogin.php" method="POST">
<div class="container">

  <h2>Login</h2>
  <div class="sub">Masuk ke akunmu</div>

  <?php if (!empty($sukses)): ?>
    <p style="color:green; text-align:center; margin-bottom:20px; font-weight:600;">
      <?= htmlspecialchars($sukses) ?>
    </p>
  <?php endif; ?>

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
           placeholder="Password"
           name="password"
           maxlength="100"
           required>
  </div>

  <button type="submit">Login</button>

  <div class="link">
    Belum punya akun? <a href="register.php">Daftar</a>
  </div>

</div>
</form>

</body>
</html>
