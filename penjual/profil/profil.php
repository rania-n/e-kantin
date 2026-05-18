<?php
/* ============================================================
   PROFIL & TOKO PENJUAL
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idpengguna = (int)$_SESSION['id_user'];
$idtoko     = (int)$_SESSION['id_toko'];
$halamansaatini = 'profil';

// Ambil data user
$qu = $conn->prepare("SELECT * FROM tb_user WHERE id_user=? AND deleted=0");
$qu->bind_param("i", $idpengguna); $qu->execute();
$user = $qu->get_result()->fetch_assoc(); $qu->close();

// Ambil data toko
$qt = $conn->prepare("SELECT * FROM tb_toko WHERE id_toko=? AND deleted=0");
$qt->bind_param("i", $idtoko); $qt->execute();
$toko = $qt->get_result()->fetch_assoc(); $qt->close();

$inisial = strtoupper(mb_substr($user['username'] ?? 'P', 0, 2));
$error   = '';
$sukses  = '';

// ==============================================================
// PROSES FORM
// ==============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // ----- Edit profil -----
    if ($aksi === 'editprofil') {
        $usernamebaru = trim($_POST['username']    ?? '');
        $emailbaru    = trim($_POST['email']       ?? '');
        $namatokobaru = trim($_POST['nama_toko']   ?? '');
        $err = [];

        if (strlen($usernamebaru) < 3)                        $err[] = "Username minimal 3 karakter.";
        if (!filter_var($emailbaru, FILTER_VALIDATE_EMAIL))   $err[] = "Format email tidak valid.";
        if (empty($namatokobaru))                             $err[] = "Nama toko wajib diisi.";
        if (strlen($namatokobaru) > 100)                      $err[] = "Nama toko maksimal 100 karakter.";

        if (empty($err)) {
            // Cek duplikat username/email
            $cd = $conn->prepare("SELECT id_user FROM tb_user WHERE (username=? OR email=?) AND id_user!=? AND deleted=0");
            $cd->bind_param("ssi", $usernamebaru, $emailbaru, $idpengguna); $cd->execute();
            if ($cd->get_result()->num_rows) $err[] = "Username atau email sudah digunakan akun lain.";
            $cd->close();
        }

        if (empty($err)) {
            // Update user
            $upd = $conn->prepare("UPDATE tb_user SET username=?, email=? WHERE id_user=?");
            $upd->bind_param("ssi", $usernamebaru, $emailbaru, $idpengguna);
            $upd->execute(); $upd->close();
            // Update toko
            $updt = $conn->prepare("UPDATE tb_toko SET nama_toko=? WHERE id_toko=?");
            $updt->bind_param("si", $namatokobaru, $idtoko);
            $updt->execute(); $updt->close();
            // Update session
            $_SESSION['username']  = $usernamebaru;
            $_SESSION['email']     = $emailbaru;
            $_SESSION['nama_toko'] = $namatokobaru;
            $sukses = "Profil dan info toko berhasil diperbarui!";
            // Refresh data
            $qu2 = $conn->prepare("SELECT * FROM tb_user WHERE id_user=? AND deleted=0");
            $qu2->bind_param("i",$idpengguna); $qu2->execute();
            $user = $qu2->get_result()->fetch_assoc(); $qu2->close();
            $qt2 = $conn->prepare("SELECT * FROM tb_toko WHERE id_toko=? AND deleted=0");
            $qt2->bind_param("i",$idtoko); $qt2->execute();
            $toko = $qt2->get_result()->fetch_assoc(); $qt2->close();
        } else {
            $error = implode(' ', $err);
        }
    }

    // ----- Ganti password -----
    elseif ($aksi === 'gantipassword') {
        $passlama   = $_POST['password_lama']   ?? '';
        $passbaru   = $_POST['password_baru']   ?? '';
        $konfirmasi = $_POST['konfirmasi']      ?? '';

        if (empty($passlama)||empty($passbaru)||empty($konfirmasi))  $error = "Semua kolom wajib diisi.";
        elseif (strlen($passbaru) < 6)                               $error = "Password baru minimal 6 karakter.";
        elseif ($passbaru !== $konfirmasi)                           $error = "Konfirmasi password tidak cocok.";
        else {
            if (!password_verify($passlama, $user['password'])) {
                $error = "Password lama salah.";
            } else {
                $hash = password_hash($passbaru, PASSWORD_BCRYPT);
                $upd  = $conn->prepare("UPDATE tb_user SET password=? WHERE id_user=?");
                $upd->bind_param("si", $hash, $idpengguna); $upd->execute(); $upd->close();
                $sukses = "Password berhasil diubah!";
            }
        }
    }
}

$tabaktif = $_GET['tab'] ?? 'profil';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil &amp; Toko - eKantin</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten" style="max-width:700px;">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-user-tie"></i> Profil &amp; Toko</h1>
      <p>Kelola akun dan informasi tokomu</p>
    </div>
  </div>

  <?php if ($error): ?>
  <div class="flashpesan flashgagal"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($sukses): ?>
  <div class="flashpesan flashsukses"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($sukses) ?></div>
  <?php endif; ?>

  <!-- Hero profil -->
  <div class="hero-profil">
    <div class="avatar"><?= $inisial ?></div>
    <div class="nama"><?= htmlspecialchars($user['username']??'') ?></div>
    <div class="sub"><?= htmlspecialchars($user['email']??'') ?></div>
    <span class="labelperan"><i class="fa-solid fa-store"></i> Penjual — <?= htmlspecialchars($toko['nama_toko']??'') ?></span>
  </div>

  <!-- Tab -->
  <div style="display:flex;gap:6px;margin-bottom:20px;">
    <a href="profil.php?tab=profil"
       class="chip-filter <?= $tabaktif==='profil'?'aktif':'' ?>">
      <i class="fa-solid fa-user"></i> Edit Profil &amp; Toko
    </a>
    <a href="profil.php?tab=password"
       class="chip-filter <?= $tabaktif==='password'?'aktif':'' ?>">
      <i class="fa-solid fa-lock"></i> Ganti Password
    </a>
  </div>

  <!-- TAB: Edit profil + toko -->
  <?php if ($tabaktif !== 'password'): ?>
  <div class="kartu">
    <h3><i class="fa-solid fa-pen"></i> Informasi Akun</h3>
    <form method="POST" action="profil.php?tab=profil">
      <input type="hidden" name="aksi" value="editprofil">
      <div class="barisform">
        <div class="kelompokform">
          <label>Username</label>
          <input type="text" name="username"
                 value="<?= htmlspecialchars($user['username']??'') ?>"
                 required minlength="3" maxlength="50">
        </div>
        <div class="kelompokform">
          <label>Email</label>
          <input type="email" name="email"
                 value="<?= htmlspecialchars($user['email']??'') ?>"
                 required>
        </div>
      </div>
      <div class="kelompokform">
        <label>Nama Toko</label>
        <input type="text" name="nama_toko"
               value="<?= htmlspecialchars($toko['nama_toko']??'') ?>"
               required maxlength="100">
      </div>
      <div class="barisform">
        <div class="kelompokform">
          <label>Peran</label>
          <input type="text" value="Penjual" disabled>
        </div>
        <div class="kelompokform">
          <label>Member Sejak</label>
          <input type="text" value="<?= !empty($user['created']) ? date('d M Y', strtotime($user['created'])) : '-' ?>" disabled>
        </div>
      </div>
      <button type="submit" class="tombolutama" style="padding:12px 24px;">
        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
      </button>
    </form>
  </div>
  <?php endif; ?>

  <!-- TAB: Ganti password -->
  <?php if ($tabaktif === 'password'): ?>
  <div class="kartu">
    <h3><i class="fa-solid fa-lock"></i> Ganti Password</h3>
    <form method="POST" action="profil.php?tab=password">
      <input type="hidden" name="aksi" value="gantipassword">
      <div class="kelompokform">
        <label>Password Lama</label>
        <div class="inputpassword">
          <input type="password" name="password_lama" id="p1" required placeholder="Password lama...">
          <button type="button" class="tombollihatpass" onclick="togglePass('p1')">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>
      <div class="kelompokform">
        <label>Password Baru</label>
        <div class="inputpassword">
          <input type="password" name="password_baru" id="p2" required minlength="6" placeholder="Minimal 6 karakter...">
          <button type="button" class="tombollihatpass" onclick="togglePass('p2')">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>
      <div class="kelompokform">
        <label>Konfirmasi Password Baru</label>
        <div class="inputpassword">
          <input type="password" name="konfirmasi" id="p3" required placeholder="Ulangi password baru...">
          <button type="button" class="tombollihatpass" onclick="togglePass('p3')">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="tombolutama" style="padding:12px 24px;">
        <i class="fa-solid fa-shield-halved"></i> Simpan Password Baru
      </button>
    </form>
  </div>
  <?php endif; ?>

</main>

<script>
function togglePass(id) {
  var el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>
