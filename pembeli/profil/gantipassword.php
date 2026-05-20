<?php
/* ============================================================
   GANTI PASSWORD
   ============================================================ */
include '../../3. komponen/guardpembeli.php';
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
    } elseif (strlen($passbaru) < 8) {
        $error = 'Password baru minimal 8 karakter';
    } elseif (strlen($passbaru) > 100) {
        $error = 'Password baru terlalu panjang (maksimal 100 karakter)';
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

// ambil data user
$qu = $conn->prepare("SELECT * FROM tb_user WHERE id_user=? AND deleted=0");
$qu->bind_param("i", $idpengguna); $qu->execute();
$user = $qu->get_result()->fetch_assoc(); $qu->close();

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
<title>Ganti Password - jajankita</title>
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
        <div style="position:relative;">
          <input type="password" name="password_lama" id="pass_lama"
                 required placeholder="Masukkan password lama..." style="padding-right:44px;">
          <button type="button" onclick="(function(b){var i=document.getElementById('pass_lama');i.type=i.type==='password'?'text':'password';b.querySelector('i').className=i.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash';})(this)"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--tekssamar);cursor:pointer;font-size:15px;padding:4px;">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="kelompokform">
        <label>Password Baru</label>
        <div style="position:relative;">
          <input type="password" name="password_baru" id="pass_baru"
                 required minlength="8" maxlength="100" placeholder="Minimal 8 karakter..."
                 style="padding-right:44px;">
          <button type="button" onclick="(function(b){var i=document.getElementById('pass_baru');i.type=i.type==='password'?'text':'password';b.querySelector('i').className=i.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash';})(this)"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--tekssamar);cursor:pointer;font-size:15px;padding:4px;">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="kelompokform">
        <label>Konfirmasi Password Baru</label>
        <div style="position:relative;">
          <input type="password" name="konfirmasi" id="pass_konfirmasi"
                 required placeholder="Ulangi password baru..." style="padding-right:44px;">
          <button type="button" onclick="(function(b){var i=document.getElementById('pass_konfirmasi');i.type=i.type==='password'?'text':'password';b.querySelector('i').className=i.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash';})(this)"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--tekssamar);cursor:pointer;font-size:15px;padding:4px;">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <div style="display:flex;gap:10px;margin-top:4px;">
        <a href="profil.php" class="tombolringan" style="padding:10px 14px;font-size:13px;">
          Batal
        </a>
        <button type="submit" class="tombolutama" style="flex:2;padding:14px;font-size:15px;">
          <i class="fa-solid fa-shield-halved"></i> Simpan
        </button>
      </div>
    </div>
  </form>

  <div style="height:24px;"></div>
</div>

</body>
</html>
