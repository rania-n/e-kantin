<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Akun - jajankita</title>
<!-- file css bersama untuk halaman login dan register -->
<link rel="stylesheet" href="../3. komponen/autentifikasi.css">
<!-- library ikon font-awesome dari cdn -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php
/*
  bagian php ini mengambil nilai yang dikirim kembali dari prosesregister.php lewat url.
  ketika validasi gagal, pengguna diarahkan kembali ke halaman ini beserta pesan error
  dan nilai input yang sudah diketik (username & email) agar tidak perlu diketik ulang.

  contoh url yang diterima:
    register.php?error=Username+sudah+terdaftar!&username=budi&email=budi@mail.com

  catatan: password tidak dikembalikan ke form karena alasan keamanan —
  pengguna harus mengetik ulang password setiap kali ada error.
*/
// helper bersama untuk dropdown kelas (murid 10/11/12 + jurusan, atau guru/staf)
include "../3. komponen/kelas_jurusan.php";

$error        = $_GET['error']        ?? '';
$username     = $_GET['username']     ?? '';
$email        = $_GET['email']        ?? '';
$namalengkap  = $_GET['namalengkap']  ?? '';
$kelas        = $_GET['kelas']        ?? '';
?>

<!-- form dikirim ke prosesregister.php dengan metode POST -->
<!-- POST dipakai agar data sensitif seperti password tidak muncul di url browser -->
<!-- catatan: enctype tidak ditulis di sini karena defaultnya sudah application/x-www-form-urlencoded —
     cocok untuk teks biasa. enctype="multipart/form-data" hanya dibutuhkan jika form mengunggah file (mis. foto). -->
<form action="prosesregister.php" method="POST">
<div class="container">

  <!-- bagian logo di atas form (lihat penjelasan di login.php) -->
  <div class="logo-auth">
    <div class="ikon-logo"><span class="logo-app"></span></div>
    <div class="nama-logo">jajankita</div>
    <div class="tagline-logo">Pesan &amp; nikmati</div>
  </div>

  <h2>Daftar Akun</h2>
  <div class="sub">Buat akun pembeli baru</div>

  <?php if (!empty($error)): ?>
  <!-- tampilkan kotak merah berisi pesan error jika ada -->
  <!-- pesan error berasal dari prosesregister.php yang gagal validasi -->
  <div class="pesan-error">
    <i class="fa-solid fa-circle-xmark"></i>
    <!-- htmlspecialchars() dipakai untuk keamanan agar isi error tidak bisa menyisipkan html/script berbahaya -->
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- kolom input nama lengkap — wajib untuk verifikasi admin -->
  <div class="grup-input">
    <label for="namalengkap">Nama Lengkap</label>
    <input type="text" id="namalengkap" name="namalengkap"
           value="<?= htmlspecialchars($namalengkap) ?>"
           placeholder="Nama lengkap sesuai kartu pelajar"
           minlength="3" maxlength="100" required>
    <div class="keterangan">Wajib sesuai identitas asli untuk verifikasi admin</div>
  </div>

  <!-- kolom input kelas — pilih kombinasi tingkat+jurusan (untuk murid)
       atau status Guru / Staff Sekolah (untuk yang bukan murid). dipakai
       admin untuk verifikasi identitas. -->
  <div class="grup-input">
    <label for="kelas">Kelas / Status</label>
    <?php tampilkanDropdownKelas($kelas, true, 'kelas'); ?>
    <div class="keterangan">Murid pilih kelas + jurusan. Guru atau staf sekolah pilih opsi Non-Murid.</div>
  </div>

  <!-- kolom input username -->
  <div class="grup-input">
    <label for="user">Username</label>
    <input type="text" id="user" name="username"
           value="<?= htmlspecialchars($username) ?>"
           placeholder="Buat username unikmu"
           minlength="6" maxlength="50" required>
    <!-- atribut minlength dan maxlength memberi validasi awal di sisi browser (client-side) -->
    <!-- tetapi validasi utama tetap dilakukan di server (prosesregister.php) -->
    <div class="keterangan">6–50 karakter</div>
  </div>

  <!-- kolom input email -->
  <div class="grup-input">
    <label for="email">Email</label>
    <!-- type="email" membuat browser memvalidasi format email secara otomatis sebelum form dikirim -->
    <input type="email" id="email" name="email"
           value="<?= htmlspecialchars($email) ?>"
           placeholder="Alamat email aktif"
           required>
  </div>

  <!-- kolom input password dengan tombol show/hide -->
  <div class="grup-input">
    <label for="pass">Password</label>
    <!-- div pass-wrap membungkus input + tombol agar bisa ditata berdampingan dengan css -->
    <div class="pass-wrap">
      <input type="password" id="pass" name="password"
             placeholder="Buat password yang kuat"
             minlength="8" maxlength="100" required>
      <!-- tombol ini hanya untuk toggle tampilan password, bukan untuk submit form -->
      <!-- type="button" mencegah tombol ini men-submit form secara tidak sengaja -->
      <button type="button" class="tombol-lihat-pass" id="btn-eye-pass" title="Lihat password">
        <i class="fa-solid fa-eye" id="ikon-eye-pass"></i>
      </button>
    </div>
    <div class="keterangan">8–100 karakter</div>
  </div>

  <!-- tombol submit untuk mengirim data ke prosesregister.php -->
  <button type="submit"><i class="fa-solid fa-user-plus"></i> Daftar Sekarang</button>

  <!-- tautan ke halaman login bagi yang sudah punya akun -->
  <div class="link">
    Sudah punya akun? <a href="login.php">Login</a>
  </div>

  <!-- tautan kembali ke halaman utama / landing page -->
  <div class="kembali-landing">
    <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Kembali ke beranda</a>
  </div>

</div>
</form>

<script>
/*
  script show/hide password — sama persis dengan yang ada di login.php.
  dibungkus dalam IIFE agar variabel tidak bocor ke scope global.
*/
(function(){
  // ambil tombol mata berdasarkan id-nya
  var btn=document.getElementById('btn-eye-pass');
  // jika tombol tidak ada di halaman, hentikan agar tidak error
  if(!btn) return;
  // pasang event listener untuk klik pada tombol
  btn.addEventListener('click',function(){
    // ambil elemen input password dan ikon mata
    var inp=document.getElementById('pass'),ic=document.getElementById('ikon-eye-pass');
    // toggle tipe input antara 'password' (tersembunyi) dan 'text' (tampil)
    inp.type=inp.type==='password'?'text':'password';
    // sesuaikan ikon: mata terbuka saat password tersembunyi, mata-coret saat password tampil
    ic.className='fa-solid fa-'+(inp.type==='password'?'eye':'eye-slash');
  });
})();
</script>
</body>
</html>
