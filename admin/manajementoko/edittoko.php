<?php
/* ============================================================
   EDIT TOKO — FORM — ADMIN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';
$halamansaatini = 'toko';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: toko.php"); exit; }

$qt = $conn->prepare("SELECT t.*, u.username FROM tb_toko t JOIN tb_user u ON t.id_user=u.id_user WHERE t.id_toko=? AND t.deleted=0");
$qt->bind_param("i", $id); $qt->execute();
$toko = $qt->get_result()->fetch_assoc(); $qt->close();
if (!$toko) { header("Location: toko.php"); exit; }

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
<title>Edit Toko - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-store"></i> Edit Toko</h1>
      <p>Ubah informasi <?= htmlspecialchars($toko['nama_toko']) ?></p>
    </div>
    <a href="viewtoko.php?id=<?= $id ?>" class="tombolringan">
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
    <h3><i class="fa-solid fa-pen"></i> Ubah Informasi Toko</h3>
    <form method="POST" action="prosesedittoko.php">
      <input type="hidden" name="id_toko" value="<?= $id ?>">
      <div class="kelompokform">
        <label>Nama Toko <span style="color:var(--gagal);">*</span></label>
        <input type="text" name="nama_toko" required maxlength="100"
               value="<?= htmlspecialchars($toko['nama_toko']) ?>">
      </div>
      <div class="kelompokform">
        <label>Pemilik</label>
        <input type="text" value="<?= htmlspecialchars($toko['username']) ?>" disabled>
      </div>
      <div class="kelompokform">
        <label>Status Toko</label>
        <select name="status_toko">
          <option value="buka"  <?= $toko['status_toko'] === 'buka'  ? 'selected' : '' ?>>Buka</option>
          <option value="tutup" <?= $toko['status_toko'] === 'tutup' ? 'selected' : '' ?>>Tutup</option>
        </select>
      </div>
      <div style="display:flex;gap:10px;margin-top:6px;">
        <a href="viewtoko.php?id=<?= $id ?>" class="tombolringan">Batal</a>
        <button type="submit" class="tombolutama" style="flex:1;">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
        </button>
      </div>
    </form>
  </div>

</main>
</body>
</html>
