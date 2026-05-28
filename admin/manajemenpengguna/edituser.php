<?php
/* halaman form edit pengguna — admin bisa mengubah username, email,
   nama toko (jika penjual), dan password pengguna.
   peran (role) tidak bisa diubah melalui form ini. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tandai menu "user" sebagai aktif di navbar
$halamansaatini = 'user';

// ambil id pengguna dari url, konversi ke integer untuk keamanan
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// jika id tidak valid, kembalikan ke daftar pengguna
if (!$id) { header("Location: user.php"); exit; }

// ambil data pengguna yang akan diedit, hanya yang belum dihapus
// pakai prepared statement (prepare + bind_param) agar aman dari sql injection
// tipe "i" = integer, "s" = string
$qu = $conn->prepare("SELECT * FROM tb_user WHERE id_user=? AND deleted=0");
$qu->bind_param("i", $id); $qu->execute();
// fetch_assoc() = ambil 1 baris hasil sebagai array asosiatif (key = nama kolom)
$user = $qu->get_result()->fetch_assoc(); $qu->close();

// jika pengguna tidak ditemukan, kembalikan ke daftar
if (!$user) { header("Location: user.php"); exit; }

// ambil data toko jika pengguna ini adalah penjual (untuk menampilkan field nama toko)
// konsep: relasi 1 user (penjual) ↔ 1 toko, dihubungkan lewat kolom id_user
$toko = null;
if ($user['role'] === 'penjual') {
    $qt = $conn->prepare("SELECT * FROM tb_toko WHERE id_user=? AND deleted=0");
    $qt->bind_param("i", $id); $qt->execute();
    $toko = $qt->get_result()->fetch_assoc(); $qt->close();
}

// ambil flash message dari session (misal: error validasi dari prosesedituser.php)
$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']); // hapus agar tidak muncul lagi di refresh berikutnya
}

// ambil data lama yang disimpan saat validasi gagal — agar form tidak kosong saat ada error
$oldinput = $_SESSION['oldinput'] ?? [];
unset($_SESSION['oldinput']);
// gunakan oldinput jika ada, fallback ke data pengguna dari database
$valUsername = !empty($oldinput['username']) ? $oldinput['username'] : $user['username'];
$valEmail    = !empty($oldinput['email'])    ? $oldinput['email']    : $user['email'];
$valNamaToko = isset($oldinput['nama_toko']) ? $oldinput['nama_toko'] : ($toko['nama_toko'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Pengguna - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-user-pen"></i> Edit Pengguna</h1>
      <p>Ubah data akun <?= htmlspecialchars($user['username']) ?></p>
    </div>
    <a href="viewuser.php?id=<?= $id ?>" class="tombolringan">
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
    <h3><i class="fa-solid fa-pen"></i> Ubah Informasi Akun</h3>
    <!-- form dikirim ke prosesedituser.php dengan metode POST.
         enctype multipart/form-data wajib supaya bisa upload file foto. -->
    <form method="POST" action="prosesedituser.php" enctype="multipart/form-data">
      <!-- id_user dikirim sebagai field tersembunyi agar proses tahu pengguna mana yang diedit -->
      <input type="hidden" name="id_user" value="<?= $id ?>">
      <div class="barisform">
        <div class="kelompokform">
          <label>Username <span style="color:var(--gagal);">*</span></label>
          <input type="text" name="username" required minlength="6" maxlength="50"
                 autocomplete="off"
                 value="<?= htmlspecialchars($valUsername) ?>">
          <small>6–50 karakter, hanya huruf/angka/titik/garis bawah, tanpa spasi</small>
        </div>
        <div class="kelompokform">
          <label>Email <span style="color:var(--gagal);">*</span></label>
          <!-- htmlspecialchars() = ubah karakter khusus html (< > " ') jadi entity,
                 cegah XSS saat menampilkan input pengguna kembali ke form -->
          <input type="email" name="email" required
                 autocomplete="off"
                 value="<?= htmlspecialchars($valEmail) ?>">
        </div>
      </div>

      <?php if ($user['role'] === 'penjual' && $toko): ?>
      <!-- PENJUAL: nama toko + foto toko (avatar penjual = foto toko, tidak ada foto user terpisah) -->
      <div class="kelompokform">
        <label>Nama Toko</label>
        <input type="text" name="nama_toko" maxlength="100"
               value="<?= htmlspecialchars($valNamaToko) ?>">
      </div>
      <div class="kelompokform">
        <label>Foto Toko</label>
        <?php
        $fotoToko = $toko['foto_toko'] ?? '';
        $adaFotoToko = $fotoToko && file_exists(__DIR__ . '/../../2. aset/profil/' . $fotoToko);
        ?>
        <?php if ($adaFotoToko): ?>
        <div style="margin-bottom:8px;">
          <img src="../../2. aset/profil/<?= htmlspecialchars($fotoToko) ?>" alt="Foto Toko"
               style="width:80px;height:80px;object-fit:cover;border-radius:14px;border:2px solid var(--garis);">
        </div>
        <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;text-transform:none;color:var(--gagal);padding:4px 10px;margin-bottom:6px;border-radius:6px;cursor:pointer;background:var(--gagalbg);border:1px solid #FCA5A5;">
          <input type="checkbox" name="hapus_foto_toko" value="1" style="margin:0;cursor:pointer;">
          <i class="fa-solid fa-trash-can"></i> Hapus
        </label>
        <?php endif; ?>
        <input type="file" name="foto_toko" accept="image/jpeg,image/png,image/webp">
        <small>JPG/PNG/WEBP, maks. 2MB. Kosongkan jika tidak ingin mengganti.</small>
      </div>
      <?php else: ?>
      <!-- PEMBELI / ADMIN: hanya foto profil user (tidak ada foto toko) -->
      <div class="kelompokform">
        <label>Foto Profil</label>
        <?php
        $fotoUser = $user['foto'] ?? '';
        $adaFotoUser = $fotoUser && file_exists(__DIR__ . '/../../2. aset/profil/' . $fotoUser);
        ?>
        <?php if ($adaFotoUser): ?>
        <div style="margin-bottom:8px;">
          <img src="../../2. aset/profil/<?= htmlspecialchars($fotoUser) ?>" alt="Foto"
               style="width:80px;height:80px;object-fit:cover;border-radius:14px;border:2px solid var(--garis);">
        </div>
        <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;text-transform:none;color:var(--gagal);padding:4px 10px;margin-bottom:6px;border-radius:6px;cursor:pointer;background:var(--gagalbg);border:1px solid #FCA5A5;">
          <input type="checkbox" name="hapus_foto" value="1" style="margin:0;cursor:pointer;">
          <i class="fa-solid fa-trash-can"></i> Hapus
        </label>
        <?php endif; ?>
        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
        <small>JPG/PNG/WEBP, maks. 2MB. Kosongkan jika tidak ingin mengganti.</small>
      </div>
      <?php endif; ?>
      <div class="kelompokform">
        <label>Peran</label>
        <!-- role ditampilkan tapi tidak bisa diubah (disabled = tidak dikirim ke server) -->
        <input type="text" value="<?= ucfirst($user['role']) ?>" disabled>
        <small>Peran tidak dapat diubah melalui form ini.</small>
      </div>
      <div class="kelompokform">
        <label>Password Baru</label>
        <div style="position:relative;">
          <!-- jika field ini dikosongkan, password lama tidak berubah -->
          <input type="password" name="password" id="pass_edit" minlength="8" maxlength="100"
                 placeholder="Kosongkan jika tidak ingin mengubah..." style="padding-right:44px;">
          <!-- tombol show/hide password: menggunakan javascript inline untuk toggle type input -->
          <button type="button" onclick="(function(b){var i=document.getElementById('pass_edit');i.type=i.type==='password'?'text':'password';b.querySelector('i').className=i.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash';})(this)"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#99627A;cursor:pointer;font-size:15px;padding:4px;">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
        <small>Isi hanya jika ingin mengganti password</small>
      </div>
      <div style="display:flex;gap:10px;margin-top:6px;">
        <a href="viewuser.php?id=<?= $id ?>" class="tombolringan">Batal</a>
        <button type="submit" class="tombolutama" style="flex:1;">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
        </button>
      </div>
    </form>
  </div>

</main>
</body>
</html>
