<?php
/* ============================================================
   KONFIRMASI HAPUS TOKO — ADMIN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';
$halamansaatini = 'toko';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: toko.php"); exit; }

$qt = $conn->prepare("SELECT t.nama_toko, u.username FROM tb_toko t JOIN tb_user u ON t.id_user=u.id_user WHERE t.id_toko=? AND t.deleted=0");
$qt->bind_param("i", $id); $qt->execute();
$toko = $qt->get_result()->fetch_assoc(); $qt->close();
if (!$toko) { header("Location: toko.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hapus Toko - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <div class="kotakkonfirm">
    <div class="ikon-konfirm"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <h2>Hapus Toko?</h2>
    <p>
      Kamu akan menghapus toko <strong>"<?= htmlspecialchars($toko['nama_toko']) ?>"</strong>
      milik <strong><?= htmlspecialchars($toko['username']) ?></strong>.
      Akun penjual tidak ikut dihapus. Tindakan ini tidak dapat dibatalkan.
    </p>
    <form method="POST" action="proseshapustoko.php">
      <input type="hidden" name="id_toko" value="<?= $id ?>">
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
        <a href="viewtoko.php?id=<?= $id ?>" class="tombolringan">
          <i class="fa-solid fa-xmark"></i> Batal
        </a>
        <button type="submit" class="tombolbahaya">
          <i class="fa-solid fa-trash"></i> Ya, Hapus Toko
        </button>
      </div>
    </form>
  </div>

</main>
</body>
</html>
