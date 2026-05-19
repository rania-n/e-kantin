<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - jajankita</title>
<link rel="stylesheet" href="autentifikasi.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php
$error         = $_GET['error']         ?? '';
$sukses        = $_GET['sukses']        ?? '';
$usernameemail = $_GET['usernameemail'] ?? '';
?>

<form action="proseslogin.php" method="POST">
<div class="container">

  <div class="logo-auth">
    <div class="ikon-logo"><i class="fa-solid fa-utensils"></i></div>
    <div class="nama-logo">jajankita</div>
    <div class="tagline-logo">Pesan &amp; nikmati</div>
  </div>

  <h2>Masuk</h2>
  <div class="sub">Login ke akunmu</div>

  <?php if (!empty($sukses)): ?>
  <div class="pesan-sukses">
    <i class="fa-solid fa-circle-check"></i>
    <?= htmlspecialchars($sukses) ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
  <div class="pesan-error">
    <i class="fa-solid fa-circle-xmark"></i>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <div class="grup-input">
    <label for="user">Username atau Email</label>
    <input type="text" id="user" name="usernameemail"
           value="<?= htmlspecialchars($usernameemail) ?>"
           placeholder="Masukkan username atau email"
           required>
  </div>

  <div class="grup-input">
    <label for="pass">Password</label>
    <div class="pass-wrap">
      <input type="password" id="pass" name="password"
             placeholder="Masukkan password"
             maxlength="100" required>
    </div>
  </div>

  <button type="submit"><i class="fa-solid fa-right-to-bracket"></i> Login</button>

  <div class="link">
    Belum punya akun? <a href="register.php">Daftar sekarang</a>
  </div>

  <div class="kembali-landing">
    <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Kembali ke beranda</a>
  </div>

</div>
</form>

</body>
</html>
