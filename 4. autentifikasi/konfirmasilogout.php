<?php
/* ============================================================
   KONFIRMASI KELUAR - Murni PHP + HTML tanpa JavaScript
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'keluar') {
    session_name('sesi_penjual');
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION = [];
    session_destroy();
    session_write_close();

    session_name('sesi_pembeli');
    session_start();
    $_SESSION = [];
    session_destroy();
    session_write_close();

    header("Location: login.php");
    exit;
}

$peran   = $_GET['peran'] ?? '';
$kembali = match($peran) {
    'penjual' => '../penjual/index/index.php',
    'pembeli' => '../pembeli/index/index.php',
    default   => 'login.php',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Konfirmasi Keluar - eKantin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<link rel="stylesheet" href="autentifikasi.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="container" style="text-align:center;">
  <div style="font-size:52px; color:#643843; margin-bottom:16px;">
    <i class="fa-solid fa-right-from-bracket"></i>
  </div>
  <h2>Yakin ingin keluar?</h2>
  <div class="sub">Kamu perlu login lagi untuk mengakses eKantin</div>

  <form method="POST" action="konfirmasilogout.php">
    <input type="hidden" name="aksi" value="keluar">
    <button type="submit">
      <i class="fa-solid fa-right-from-bracket"></i> Ya, Keluar
    </button>
  </form>

  <div class="link">
    <a href="<?= htmlspecialchars($kembali) ?>">
      <i class="fa-solid fa-arrow-left"></i> Batal, kembali
    </a>
  </div>
</div>

</body>
</html>
