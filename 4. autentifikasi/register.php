<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Akun - eKantin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<link rel="stylesheet" href="autentifikasi.css">
</head>
<body>

<?php
$error    = $_GET['error']    ?? '';
$username = $_GET['username'] ?? '';
$email    = $_GET['email']    ?? '';
?>

<form action="prosesregister.php" method="POST">
<div class="container">

  <h2>Daftar Akun</h2>
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
         minlength="6"
         maxlength="50"
         required>
  <small style="color:#888;font-size:11px;display:block;margin-top:-10px;margin-bottom:14px;">6–50 karakter</small>

  <input type="email"
         id="email"
         placeholder="Email"
         name="email"
         value="<?= htmlspecialchars($email) ?>"
         required>

  <div class="pass-wrap">
    <input type="password"
           placeholder="Password"
           name="password"
           minlength="8"
           maxlength="100"
           required>
  </div>
  <small style="color:#888;font-size:11px;display:block;margin-top:-10px;margin-bottom:14px;">8–100 karakter</small>

  <button type="submit">Daftar</button>

  <div class="link">
    Sudah punya akun? <a href="login.php">Login</a>
  </div>

</div>
</form>

</body>
</html>
