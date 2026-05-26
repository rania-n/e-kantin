<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - jajankita</title>
<!-- file css khusus halaman autentifikasi (login & register) -->
<link rel="stylesheet" href="../3. komponen/autentifikasi.css">
<!-- library ikon font-awesome dari cdn, dipakai untuk ikon-ikon di halaman ini -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php
/*
  bagian php ini berjalan sebelum html dikirim ke browser.
  tujuannya: menangkap pesan error, pesan sukses, dan nilai input
  yang dikirim kembali oleh proseslogin.php lewat url (query string).

  contoh url yang diterima:
    login.php?error=Password+salah!&usernameemail=budi
    login.php?sukses=Registrasi+berhasil!

  operator ?? artinya: ambil nilai dari $_GET jika ada, kalau tidak ada pakai string kosong ''.
  ini mencegah error "undefined index" saat tidak ada nilai di url.
*/
$error         = $_GET['error']         ?? '';
$sukses        = $_GET['sukses']        ?? '';
$usernameemail = $_GET['usernameemail'] ?? '';
?>

<!-- form dikirim ke proseslogin.php menggunakan metode POST -->
<!-- POST dipakai agar data (username & password) tidak muncul di url -->
<form action="proseslogin.php" method="POST">
<div class="container">

  <!-- bagian logo di atas form -->
  <div class="logo-auth">
    <div class="ikon-logo"><i class="fa-solid fa-utensils"></i></div>
    <div class="nama-logo">jajankita</div>
    <div class="tagline-logo">Pesan &amp; nikmati</div>
  </div>

  <h2>Masuk</h2>
  <div class="sub">Login ke akunmu</div>

  <?php if (!empty($sukses)): ?>
  <!-- tampilkan kotak hijau berisi pesan sukses, hanya muncul jika variabel $sukses tidak kosong -->
  <!-- contoh: setelah pengguna berhasil daftar, dia diarahkan ke sini dengan pesan sukses -->
  <div class="pesan-sukses">
    <i class="fa-solid fa-circle-check"></i>
    <!-- htmlspecialchars() mengubah karakter berbahaya (< > " &) menjadi kode html yang aman -->
    <!-- ini penting untuk mencegah serangan xss (cross-site scripting) -->
    <?= htmlspecialchars($sukses) ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
  <!-- tampilkan kotak merah berisi pesan error, hanya muncul jika ada error dari proses login -->
  <!-- contoh: "Password salah!" atau "Akun tidak ditemukan!" -->
  <div class="pesan-error">
    <i class="fa-solid fa-circle-xmark"></i>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- kolom input username atau email -->
  <div class="grup-input">
    <label for="user">Username atau Email</label>
    <input type="text" id="user" name="usernameemail"
           value="<?= htmlspecialchars($usernameemail) ?>"
           placeholder="Masukkan username atau email"
           required>
    <!-- atribut value diisi ulang dari $usernameemail agar pengguna tidak perlu -->
    <!-- mengetik ulang username/email saat ada error -->
  </div>

  <!-- kolom input password dengan tombol show/hide -->
  <div class="grup-input">
    <label for="pass">Password</label>
    <!-- div pass-wrap membungkus input + tombol mata agar bisa ditata berdampingan -->
    <div class="pass-wrap">
      <input type="password" id="pass" name="password"
             placeholder="Masukkan password"
             maxlength="100" required>
      <!-- tombol ini untuk menampilkan/menyembunyikan teks password -->
      <!-- type="button" penting agar tombol tidak submit form secara tidak sengaja -->
      <button type="button" class="tombol-lihat-pass" id="btn-eye-pass" title="Lihat password">
        <i class="fa-solid fa-eye" id="ikon-eye-pass"></i>
      </button>
    </div>
  </div>

  <div class="link" style="margin-bottom: 10px;">
    Lupa password? 
    <a href="https://wa.me/6285648830046?text=Halo%20Admin%20jajankita,%20saya%20lupa%20password%20akun%20saya.%20Mohon%20bantuannya." 
       target="_blank">
       Hubungi Admin <i class="fa-brands fa-whatsapp"></i>
    </a>
  </div>

  <!-- tombol submit — mengirim form ke proseslogin.php -->
  <button type="submit"><i class="fa-solid fa-right-to-bracket"></i> Login</button>

  <!-- tautan ke halaman daftar bagi yang belum punya akun -->
  <div class="link">
    Belum punya akun? <a href="register.php">Daftar sekarang</a>
  </div>

  <!-- tautan kembali ke halaman utama / landing page -->
  <div class="kembali-landing">
    <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Kembali ke beranda</a>
  </div>

</div>
</form>

<script>
/*
  script ini menangani fungsi show/hide password.
  dibungkus dalam IIFE (immediately invoked function expression) — fungsi yang langsung
  dijalankan sendiri saat halaman dimuat — agar variabel di dalamnya tidak mencemari
  ruang lingkup global (global scope).
*/
(function(){
  // cari elemen tombol mata berdasarkan id-nya
  var btn=document.getElementById('btn-eye-pass');
  // jika tombol tidak ditemukan di halaman, hentikan script agar tidak error
  if(!btn) return;
  // pasang pendengar klik pada tombol
  btn.addEventListener('click',function(){
    // ambil elemen input password dan elemen ikon mata
    var inp=document.getElementById('pass'),ic=document.getElementById('ikon-eye-pass');
    // ganti tipe input: kalau sekarang 'password' ubah ke 'text' (tampilkan), dan sebaliknya
    inp.type=inp.type==='password'?'text':'password';
    // ganti ikon: kalau password tersembunyi tampilkan ikon mata biasa, kalau terlihat tampilkan mata-coret
    ic.className='fa-solid fa-'+(inp.type==='password'?'eye':'eye-slash');
  });
})();
</script>
</body>
</html>
