<?php
/* ============================================================
   EDIT PENGGUNA — FORM — ADMIN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';
$halamansaatini = 'user';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: user.php"); exit; }

$qu = $conn->prepare("SELECT * FROM tb_user WHERE id_user=? AND deleted=0");
$qu->bind_param("i", $id); $qu->execute();
$user = $qu->get_result()->fetch_assoc(); $qu->close();
if (!$user) { header("Location: user.php"); exit; }

$toko = null;
if ($user['role'] === 'penjual') {
    $qt = $conn->prepare("SELECT * FROM tb_toko WHERE id_user=? AND deleted=0");
    $qt->bind_param("i", $id); $qt->execute();
    $toko = $qt->get_result()->fetch_assoc(); $qt->close();
}

$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Pengguna - Admin eKantin</title>
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
    <form method="POST" action="prosesedituser.php">
      <input type="hidden" name="id_user" value="<?= $id ?>">
      <div class="barisform">
        <div class="kelompokform">
          <label>Username <span style="color:var(--gagal);">*</span></label>
          <input type="text" name="username" required minlength="6" maxlength="50"
                 value="<?= htmlspecialchars($user['username']) ?>">
          <small>6–50 karakter</small>
        </div>
        <div class="kelompokform">
          <label>Email <span style="color:var(--gagal);">*</span></label>
          <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>">
        </div>
      </div>
      <?php if ($user['role'] === 'penjual' && $toko): ?>
      <div class="kelompokform">
        <label>Nama Toko</label>
        <input type="text" name="nama_toko" maxlength="100"
               value="<?= htmlspecialchars($toko['nama_toko']) ?>">
      </div>
      <?php endif; ?>
      <div class="kelompokform">
        <label>Peran</label>
        <input type="text" value="<?= ucfirst($user['role']) ?>" disabled>
        <small>Peran tidak dapat diubah melalui form ini.</small>
      </div>
      <div class="kelompokform">
        <label>Password Baru</label>
        <input type="password" name="password" minlength="8" maxlength="100"
               placeholder="Kosongkan jika tidak ingin mengubah...">
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
