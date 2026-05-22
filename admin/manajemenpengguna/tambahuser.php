<?php
/* halaman form tambah pengguna baru.
   mendukung semua role: penjual (dipilihkan kantin kosong), pembeli, admin.
   penjual wajib memilih kantin yang tersedia (kosong) dan mengisi nama toko.
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
if (!in_array($roledefault, ['penjual','pembeli','admin'])) $roledefault = 'penjual';

// ambil daftar kantin yang masih kosong (id_user IS NULL) untuk dropdown penjual
// kantin kosong adalah kantin yang belum punya penjual — tersedia untuk ditempati
$kantinkosong = $conn->query(
    "SELECT id_toko, nomor_kantin FROM tb_toko
     WHERE id_user IS NULL AND deleted=0
     ORDER BY nomor_kantin ASC"
)->fetch_all(MYSQLI_ASSOC);
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
      <p>Buat akun baru — penjual (dengan kantin), pembeli, atau admin</p>
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

  <?php if (empty($kantinkosong)): ?>
  <!-- peringatan jika semua 10 kantin sudah terisi — admin harus hapus penjual dulu -->
  <div class="peringatan peringataninfo" style="margin-bottom:16px;">
    <i class="fa-solid fa-store-slash"></i>
    <strong>Semua kantin sudah terisi.</strong>
    Untuk menambah penjual baru, hapus terlebih dahulu penjual yang menempati kantin,
    sehingga slotnya menjadi tersedia.
    <a href="../manajementoko/kantin.php" style="color:var(--info);font-weight:700;">Lihat status kantin →</a>
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
          <!-- div pass-wrap memposisikan tombol mata di dalam kotak input -->
          <div style="position:relative;">
            <input type="password" name="password" id="pass_tambah" required
                   minlength="8" maxlength="100"
                   placeholder="Minimal 8 karakter..."
                   style="padding-right:44px;">
            <!-- tombol show/hide password — JS diizinkan untuk password -->
            <button type="button"
                    onclick="(function(b){var i=document.getElementById('pass_tambah');i.type=i.type==='password'?'text':'password';b.querySelector('i').className=i.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash';})(this)"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#99627A;cursor:pointer;font-size:15px;padding:4px;">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
          <small>8–100 karakter</small>
        </div>
        <div class="kelompokform">
          <label>Peran <span style="color:var(--gagal);">*</span></label>
          <!-- dropdown role: nilai yang dipilih menentukan validasi di server -->
          <select name="role" required>
            <option value="penjual" <?= $roledefault==='penjual'?'selected':'' ?>>Penjual (pilih kantin)</option>
            <option value="pembeli" <?= $roledefault==='pembeli'?'selected':'' ?>>Pembeli</option>
            <option value="admin"   <?= $roledefault==='admin'  ?'selected':'' ?>>Admin Platform</option>
          </select>
        </div>
      </div>

      <!-- seksi khusus penjual: pilih kantin dan nama toko -->
      <!-- field ini selalu tampil (tidak bisa sembunyikan tanpa JS) -->
      <!-- server hanya memvalidasinya jika role = penjual -->
      <div class="kartu" style="background:var(--latar);padding:14px 16px;margin-bottom:16px;border:1.5px dashed var(--garis);">
        <div style="font-size:12px;font-weight:700;color:var(--tekssamar);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">
          <i class="fa-solid fa-store"></i> Khusus Penjual — Isi jika Peran = Penjual
        </div>
        <div class="barisform">
          <div class="kelompokform" style="margin-bottom:0;">
            <label>Pilih Kantin <span style="color:var(--tekssamar);font-size:11px;">(hanya untuk Penjual)</span></label>
            <!-- dropdown kantin kosong: nilai yang dikirim adalah id_toko -->
            <select name="id_kantin">
              <option value="">— Pilih kantin tersedia —</option>
              <?php foreach ($kantinkosong as $k): ?>
              <!-- tampilkan nomor kantin agar admin tahu slot mana yang dipilih -->
              <option value="<?= (int)$k['id_toko'] ?>">
                Kantin ke-<?= (int)$k['nomor_kantin'] ?>
              </option>
              <?php endforeach; ?>
            </select>
            <small>
              <?= count($kantinkosong) ?> kantin tersedia dari 10 slot.
              <a href="../manajementoko/kantin.php">Lihat semua status kantin →</a>
            </small>
          </div>
          <div class="kelompokform" style="margin-bottom:0;">
            <label>Nama Toko <span style="color:var(--tekssamar);font-size:11px;">(hanya untuk Penjual)</span></label>
            <input type="text" name="nama_toko" maxlength="100"
                   placeholder="Nama toko di kantin ini...">
            <small>Nama toko yang akan muncul di aplikasi pembeli.</small>
          </div>
        </div>
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
