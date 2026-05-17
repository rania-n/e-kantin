<?php
/* ============================================================
   GANTI PASSWORD
   ============================================================ */
include '../../3. komponen/guard.php';
include '../../1. koneksi/koneksi.php';

$idpengguna = (int)$_SESSION['id_user'];
$error      = '';
$sukses     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passlama   = $_POST['password_lama']   ?? '';
    $passbaru   = $_POST['password_baru']   ?? '';
    $konfirmasi = $_POST['konfirmasi']      ?? '';

    if (empty($passlama) || empty($passbaru) || empty($konfirmasi)) {
        $error = 'Semua kolom wajib diisi';
    } elseif (strlen($passbaru) < 6) {
        $error = 'Password baru minimal 6 karakter';
    } elseif ($passbaru !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok';
    } else {
        $q = $conn->prepare("SELECT password FROM tb_user WHERE id_user=? AND deleted=0");
        $q->bind_param("i", $idpengguna); $q->execute();
        $user = $q->get_result()->fetch_assoc(); $q->close();

        if (!password_verify($passlama, $user['password'])) {
            $error = 'Password lama salah';
        } else {
            $hash = password_hash($passbaru, PASSWORD_BCRYPT);
            $upd  = $conn->prepare("UPDATE tb_user SET password=? WHERE id_user=?");
            $upd->bind_param("si", $hash, $idpengguna);
            $upd->execute(); $upd->close();
            $sukses = 'Password berhasil diubah';
        }
    }
}

$pathbase = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ganti Password - eKantin</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkussempit">

  <div class="headerkembali">
    <a href="profil.php" class="tombolkembali"><i class="fa-solid fa-arrow-left"></i></a>
    <div class="teksheader">
      <h1>Ganti Password</h1>
      <p>Ubah kata sandi akunmu</p>
    </div>
  </div>

  <?php if ($error): ?>
  <div class="peringatan peringatangagal">
    <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>
  <?php if ($sukses): ?>
  <div class="peringatan peringatansukses">
    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($sukses) ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="gantipassword.php">
    <div class="kartu">
      <h3>Ubah Kata Sandi</h3>

      <div class="kelompokform">
        <label>Password Lama</label>
        <div class="inputpassword">
          <input type="password" name="password_lama" id="p1"
                 required placeholder="Masukkan password lama...">
          <button type="button" class="tombollihatpass" onclick="togglePass('p1')">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="kelompokform">
        <label>Password Baru</label>
        <div class="inputpassword">
          <input type="password" name="password_baru" id="p2"
                 required minlength="6" placeholder="Minimal 6 karakter...">
          <button type="button" class="tombollihatpass" onclick="togglePass('p2')">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="kelompokform">
        <label>Konfirmasi Password Baru</label>
        <div class="inputpassword">
          <input type="password" name="konfirmasi" id="p3"
                 required placeholder="Ulangi password baru...">
          <button type="button" class="tombollihatpass" onclick="togglePass('p3')">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>
    </div>

    <button type="submit" class="tombolutama blok" style="padding:14px;font-size:15px;">
      <i class="fa-solid fa-shield-halved"></i> Simpan Password Baru
    </button>
  </form>

  <div style="height:24px;"></div>
</div>

<script>
/* Toggle lihat/sembunyikan password - JS minimal, hanya untuk UX input */
function togglePass(id) {
  var el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
