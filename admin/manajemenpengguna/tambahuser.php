<?php
/* halaman form tambah pengguna baru — mendukung semua role:
   - penjual: akun + toko baru dibuat secara otomatis
   - pembeli: hanya akun
   - admin: hanya akun
   form ini mengirim data ke prosestambahuser.php untuk diproses. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tandai menu "user" sebagai aktif di navbar
$halamansaatini = 'user';

// ambil flash message dari session (misal: error validasi dari prosestambahuser.php)
$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']); // hapus setelah dibaca agar tidak muncul lagi
}

// baca role default dari url untuk menentukan opsi yang dipilih di dropdown
$roledefault = $_GET['role'] ?? 'penjual';

// validasi role default agar hanya nilai yang diizinkan yang digunakan
if (!in_array($roledefault, ['penjual','pembeli','admin'])) $roledefault = 'penjual';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Pengguna - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-user-plus"></i> Tambah Pengguna</h1>
      <p>Buat akun baru — penjual, pembeli, atau admin</p>
    </div>
    <a href="user.php" class="tombolringan">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
  </div>

  <?php if ($flashpesan): ?>
  <div class="flashpesan flash<?= $flashjenis ?>">
    <i class="fa-solid fa-<?= $flashjenis === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
  <?php endif; ?>

  <div class="kartu">
    <h3><i class="fa-solid fa-user-circle"></i> Data Pengguna Baru</h3>
    <!-- form dikirim ke prosestambahuser.php dengan metode POST -->
    <form method="POST" action="prosestambahuser.php">
      <div class="barisform">
        <div class="kelompokform">
          <label>Username <span style="color:var(--gagal);">*</span></label>
          <input type="text" name="username" required minlength="6" maxlength="50"
                 placeholder="Minimal 6 karakter...">
          <small>6–50 karakter, harus unik</small>
        </div>
        <div class="kelompokform">
          <label>Email <span style="color:var(--gagal);">*</span></label>
          <input type="email" name="email" required placeholder="Email aktif...">
        </div>
      </div>
      <div class="barisform">
        <div class="kelompokform">
          <label>Password <span style="color:var(--gagal);">*</span></label>
          <div style="position:relative;">
            <input type="password" name="password" id="pass_tambah" required minlength="8" maxlength="100"
                   placeholder="Minimal 8 karakter..." style="padding-right:44px;">
            <!-- tombol show/hide password menggunakan javascript inline -->
            <button type="button" onclick="(function(b){var i=document.getElementById('pass_tambah');i.type=i.type==='password'?'text':'password';b.querySelector('i').className=i.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash';})(this)"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#99627A;cursor:pointer;font-size:15px;padding:4px;">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
          <small>8–100 karakter</small>
        </div>
        <div class="kelompokform">
          <label>Peran <span style="color:var(--gagal);">*</span></label>
          <!-- dropdown role, nilai yang dipilih menentukan apakah field nama toko perlu diisi -->
          <select name="role" required>
            <option value="penjual" <?= $roledefault==='penjual'?'selected':'' ?>>Penjual (+ toko otomatis)</option>
            <option value="pembeli" <?= $roledefault==='pembeli'?'selected':'' ?>>Pembeli</option>
            <option value="admin"   <?= $roledefault==='admin'  ?'selected':'' ?>>Admin Platform</option>
          </select>
        </div>
      </div>
      <div class="kelompokform">
        <label>Nama Toko <span style="color:var(--tekssamar);font-size:11px;">(hanya untuk Penjual)</span></label>
        <!-- field ini hanya relevan jika role yang dipilih adalah penjual -->
        <input type="text" name="nama_toko" maxlength="100"
               placeholder="Nama toko — isi jika peran Penjual...">
        <small>Toko otomatis dikaitkan ke akun Penjual. Kosongkan untuk Pembeli/Admin.</small>
      </div>
      <div style="display:flex;gap:10px;margin-top:6px;flex-wrap:wrap;">
        <a href="user.php" class="tombolringan">Batal</a>
        <button type="submit" class="tombolutama" style="flex:1;">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Pengguna
        </button>
      </div>
    </form>
  </div>

</main>
</body>
</html>
