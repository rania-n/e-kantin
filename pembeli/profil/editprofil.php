<?php
/* ============================================================
   EDIT PROFIL
   ============================================================ */
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

$idpengguna = (int)$_SESSION['id_user'];
$error      = '';

// proses form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernamebaru = trim($_POST['username'] ?? '');
    $emailbaru    = trim($_POST['email']    ?? '');
    $daftarerror  = [];

    if (strlen($usernamebaru) < 6)                           $daftarerror[] = 'Username minimal 6 karakter';
    if (strlen($usernamebaru) > 50)                          $daftarerror[] = 'Username maksimal 50 karakter';
    if (!filter_var($emailbaru, FILTER_VALIDATE_EMAIL))      $daftarerror[] = 'Format email tidak valid';

    if (empty($daftarerror)) {
        $cek = $conn->prepare("SELECT id_user FROM tb_user WHERE (username=? OR email=?) AND id_user!=? AND deleted=0");
        $cek->bind_param("ssi", $usernamebaru, $emailbaru, $idpengguna);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) $daftarerror[] = 'Username atau email sudah digunakan akun lain';
        $cek->close();
    }

    if (empty($daftarerror)) {
        $upd = $conn->prepare("UPDATE tb_user SET username=?,email=? WHERE id_user=?");
        $upd->bind_param("ssi", $usernamebaru, $emailbaru, $idpengguna);
        $upd->execute(); $upd->close();
        $_SESSION['username'] = $usernamebaru;
        $_SESSION['email']    = $emailbaru;
        $_SESSION['flash']    = ['pesan' => 'Profil berhasil diperbarui', 'jenis' => 'sukses'];
        header("Location: profil.php"); exit;
    } else {
        $error = implode(', ', $daftarerror);
    }
}

// ambil data user
$q = $conn->prepare("SELECT * FROM tb_user WHERE id_user=? AND deleted=0");
$q->bind_param("i", $idpengguna); $q->execute();
$user = $q->get_result()->fetch_assoc(); $q->close();

// statistik
$s1 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND deleted=0 AND status_order NOT IN ('Dibatalkan')");
$s1->bind_param("i",$idpengguna); $s1->execute();
$totalpesanan = (int)$s1->get_result()->fetch_row()[0]; $s1->close();

$s2 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND status_order='Selesai' AND deleted=0");
$s2->bind_param("i",$idpengguna); $s2->execute();
$totalselesai = (int)$s2->get_result()->fetch_row()[0]; $s2->close();

$s3 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_user=? AND status_order='Selesai' AND deleted=0");
$s3->bind_param("i",$idpengguna); $s3->execute();
$totalbelanja = (int)$s3->get_result()->fetch_row()[0]; $s3->close();

$formatbelanja = $totalbelanja >= 1000000
    ? 'Rp ' . number_format($totalbelanja/1000000, 1) . 'jt'
    : 'Rp ' . number_format($totalbelanja/1000, 0) . 'rb';

$inisial  = strtoupper(mb_substr($user['username'] ?? 'U', 0, 2));
$pathbase = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profil - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="bungkussempit">

  <!-- hero profil -->
  <div class="heroprofil">
    <div class="avatar"><?= $inisial ?></div>
    <div class="namapengguna"><?= htmlspecialchars($user['username']) ?></div>
    <div class="emailpengguna"><?= htmlspecialchars($user['email']) ?></div>
    <span class="labelperan"><i class="fa-solid fa-user"></i> Pembeli</span>
  </div>

  <!-- statistik -->
  <div class="gridstat" style="margin-bottom:20px;">
    <div class="kotakstat">
      <div class="angkastat"><?= $totalpesanan ?></div>
      <div class="labelstat">Pesanan</div>
    </div>
    <div class="kotakstat">
      <div class="angkastat"><?= $totalselesai ?></div>
      <div class="labelstat">Selesai</div>
    </div>
    <div class="kotakstat">
      <div class="angkastat" style="font-size:13px;"><?= $formatbelanja ?></div>
      <div class="labelstat">Belanja</div>
    </div>
  </div>

  <?php if ($error): ?>
  <div class="peringatan peringatangagal">
    <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="editprofil.php">
    <div class="kartu">
      <h3>Informasi Akun</h3>

      <div class="kelompokform">
        <label>Username</label>
        <input type="text" name="username"
               value="<?= htmlspecialchars($user['username'] ?? '') ?>"
               required minlength="6" maxlength="50"
               placeholder="Username...">
      </div>

      <div class="kelompokform">
        <label>Email</label>
        <input type="email" name="email"
               value="<?= htmlspecialchars($user['email'] ?? '') ?>"
               required placeholder="Email...">
      </div>

      <div class="kelompokform">
        <label>Peran</label>
        <input type="text" value="Pembeli" disabled>
      </div>

      <div class="kelompokform">
        <label>Member Sejak</label>
        <input type="text" value="<?= !empty($user['created']) ? date('d M Y', strtotime($user['created'])) : '-' ?>" disabled>
      </div>

      <div style="display:flex;gap:10px;margin-top:4px;">
        <a href="profil.php" class="tombolringan" style="padding:10px 14px;font-size:13px;">
          Batal
        </a>
        <button type="submit" class="tombolutama" style="flex:2;padding:14px;font-size:15px;">
          <i class="fa-solid fa-floppy-disk"></i> Simpan
        </button>
      </div>
    </div>
  </form>

  <div style="height:24px;"></div>
</div>
</body>
</html>
