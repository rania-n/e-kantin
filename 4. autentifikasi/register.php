<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Akun - jajankita</title>
<link rel="stylesheet" href="autentifikasi.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php
$error    = $_GET['error']    ?? '';
$username = $_GET['username'] ?? '';
$email    = $_GET['email']    ?? '';
?>

<form action="prosesregister.php" method="POST">
<div class="container">

  <div class="logo-auth">
    <div class="ikon-logo"><i class="fa-solid fa-utensils"></i></div>
    <div class="nama-logo">jajankita</div>
    <div class="tagline-logo">Pesan &amp; nikmati</div>
  </div>

  <h2>Daftar Akun</h2>
  <div class="sub">Buat akun pembeli baru</div>

  <?php if (!empty($error)): ?>
  <div class="pesan-error">
    <i class="fa-solid fa-circle-xmark"></i>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <div class="grup-input">
    <label for="user">Username</label>
    <input type="text" id="user" name="username"
           value="<?= htmlspecialchars($username) ?>"
           placeholder="Buat username unikmu"
           minlength="6" maxlength="50" required>
    <div class="keterangan">6–50 karakter</div>
  </div>

  <div class="grup-input">
    <label for="email">Email</label>
    <input type="email" id="email" name="email"
           value="<?= htmlspecialchars($email) ?>"
           placeholder="Alamat email aktif"
           required>
  </div>

  <div class="grup-input">
    <label for="pass">Password</label>
    <div class="pass-wrap">
      <input type="password" id="pass" name="password"
             placeholder="Buat password yang kuat"
             minlength="8" maxlength="100" required>
    </div>
    <div class="keterangan">8–100 karakter</div>
  </div>

  <button type="submit"><i class="fa-solid fa-user-plus"></i> Daftar Sekarang</button>

  <div class="link">
    Sudah punya akun? <a href="login.php">Login</a>
  </div>

  <div class="kembali-landing">
    <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Kembali ke beranda</a>
  </div>

</div>
</form>

</body>
</html>
