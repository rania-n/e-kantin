<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin - jajankita</title>
<!-- css autentifikasi dipakai bersama login pembeli/penjual, tapi halaman ini terpisah -->
<link rel="stylesheet" href="../../3. komponen/autentifikasi.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* override warna logo admin agar berbeda dari halaman login umum */
.ikon-logo { background: #1e3a5f; }
.nama-logo  { color: #1e3a5f; }
.tagline-logo { color: #4a6fa5; }
/* warna tombol submit mengikuti palet admin (biru gelap) */
form button[type="submit"] {
    background: #1e3a5f;
}
form button[type="submit"]:hover {
    background: #16304f;
}
/* tanda admin panel di bawah logo */
.label-admin {
    display: inline-block;
    margin: 6px auto 0;
    background: #e8f0fe;
    color: #1e3a5f;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
/* halaman login admin TIDAK diblokir di mobile — form login muat di hp.
   yang diblokir hanya halaman dashboard/tabel/laporan setelah login (lihat
   navbaradmin.php yang punya overlay .blokirmobile). */
</style>
</head>
<body>

<?php
/*
  halaman login khusus admin — terpisah dari login pembeli dan penjual.
  admin HANYA bisa masuk melalui halaman ini, bukan melalui login.php umum.
  alur: ambil pesan dari URL → tampilkan form → kirim ke prosesloginadmin.php
*/

// ambil pesan error atau sukses dari URL (dikirim oleh prosesloginadmin.php via redirect)
$error         = $_GET['error']         ?? '';
$usernameemail = $_GET['usernameemail'] ?? '';

// jika admin sudah login (session aktif), langsung arahkan ke dashboard
// session_name('sesi_admin') HARUS dipanggil sebelum session_start() agar php tahu
// sesi mana yang akan dibuka — ini kunci pemisahan sesi multi-role (admin/penjual/pembeli)
if (session_status() === PHP_SESSION_NONE) {
    session_name('sesi_admin');
    session_start();
}
// jika sudah punya session admin yang valid, tidak perlu login lagi
if (!empty($_SESSION['id_user']) && ($_SESSION['role'] ?? '') === 'admin') {
    header("Location: ../index/index.php");
    exit;
}
?>

<!-- form dikirim ke prosesloginadmin.php menggunakan POST -->
<form action="prosesloginadmin.php" method="POST">
<div class="container">

  <!-- logo dan identitas panel admin.
       pakai .logo-app (sama dengan login pembeli/penjual) — branding konsisten.
       background admin tetap biru tua via override .ikon-logo di style atas. -->
  <div class="logo-auth">
    <div class="ikon-logo"><span class="logo-app"></span></div>
    <div class="nama-logo">jajankita</div>
    <div class="tagline-logo">Panel Administrasi</div>
    <!-- label ini membedakan secara visual bahwa ini halaman login admin -->
    <span class="label-admin"><i class="fa-solid fa-lock"></i> Admin Only</span>
  </div>

  <h2>Masuk Admin</h2>
  <div class="sub">Akses terbatas — hanya untuk admin</div>

  <?php if (!empty($error)): ?>
  <!-- tampilkan kotak merah berisi pesan error login -->
  <div class="pesan-error">
    <i class="fa-solid fa-circle-xmark"></i>
    <!-- htmlspecialchars mencegah XSS: karakter < > " & diubah ke entitas HTML -->
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- kolom input username atau email admin -->
  <div class="grup-input">
    <label for="user">Username atau Email</label>
    <input type="text" id="user" name="usernameemail"
           value="<?= htmlspecialchars($usernameemail) ?>"
           placeholder="Username atau email admin"
           required>
    <!-- value diisi ulang dari URL agar admin tidak perlu mengetik ulang jika ada error -->
  </div>

  <!-- kolom input password dengan tombol show/hide (JS diizinkan untuk password) -->
  <div class="grup-input">
    <label for="pass">Password</label>
    <div class="pass-wrap">
      <input type="password" id="pass" name="password"
             placeholder="Password admin"
             maxlength="100" required>
      <!-- tombol mata: toggle tampilkan/sembunyikan password -->
      <button type="button" class="tombol-lihat-pass" id="btn-eye-pass" title="Lihat password">
        <i class="fa-solid fa-eye" id="ikon-eye-pass"></i>
      </button>
    </div>
  </div>

  <!-- tombol submit login admin -->
  <button type="submit">
    <i class="fa-solid fa-right-to-bracket"></i> Masuk sebagai Admin
  </button>

  <!-- tautan kembali ke halaman utama, bukan ke login umum -->
  <div class="kembali-landing">
    <a href="../../index.php"><i class="fa-solid fa-arrow-left"></i> Kembali ke beranda</a>
  </div>

</div>
</form>

<script>
/* script show/hide password — JS diizinkan khusus untuk field password */
(function(){
  var btn = document.getElementById('btn-eye-pass');
  if (!btn) return;
  btn.addEventListener('click', function(){
    var inp = document.getElementById('pass');
    var ic  = document.getElementById('ikon-eye-pass');
    // toggle tipe input antara 'password' (tersembunyi) dan 'text' (tampil)
    inp.type = inp.type === 'password' ? 'text' : 'password';
    // sesuaikan ikon mata
    ic.className = 'fa-solid fa-' + (inp.type === 'password' ? 'eye' : 'eye-slash');
  });
})();
</script>
</body>
</html>
