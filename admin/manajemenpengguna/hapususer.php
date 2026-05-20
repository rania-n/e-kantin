<?php
/* halaman konfirmasi hapus pengguna — menampilkan nama pengguna
   dan peringatan bahwa toko miliknya juga akan ikut dihapus.
   penghapusan baru dieksekusi setelah admin klik tombol konfirmasi. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tandai menu "user" sebagai aktif di navbar
$halamansaatini = 'user';

// ambil id pengguna dari url, konversi ke integer
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// jika id tidak valid, kembalikan ke daftar pengguna
if (!$id) { header("Location: user.php"); exit; }

// ambil data pengguna yang akan dihapus (hanya yang belum dihapus)
$qu = $conn->prepare("SELECT id_user, username, email, role FROM tb_user WHERE id_user=? AND deleted=0");
$qu->bind_param("i", $id); $qu->execute();
$user = $qu->get_result()->fetch_assoc(); $qu->close();

// jika pengguna tidak ditemukan, kembalikan ke daftar
if (!$user) { header("Location: user.php"); exit; }

// jika pengguna ini adalah penjual, ambil nama tokonya untuk ditampilkan dalam peringatan
$toko = null;
if ($user['role'] === 'penjual') {
    $qt = $conn->prepare("SELECT nama_toko FROM tb_toko WHERE id_user=? AND deleted=0");
    $qt->bind_param("i", $id); $qt->execute();
    $toko = $qt->get_result()->fetch_assoc(); $qt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hapus Pengguna - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <!-- kotak konfirmasi di tengah halaman -->
  <div class="kotakkonfirm">
    <div class="ikon-konfirm"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <h2>Hapus Pengguna?</h2>
    <p>
      Kamu akan menghapus akun <strong><?= htmlspecialchars($user['username']) ?></strong>
      (<?= htmlspecialchars($user['email']) ?>).
      <?php if ($toko): ?>
      <!-- peringatan tambahan jika pengguna ini adalah penjual yang punya toko -->
      Toko <strong>"<?= htmlspecialchars($toko['nama_toko']) ?>"</strong> juga akan ikut dihapus.
      <?php endif; ?>
      Tindakan ini tidak dapat dibatalkan.
    </p>
    <!-- form konfirmasi hapus: id_user dikirim ke proseshapususer.php -->
    <form method="POST" action="proseshapususer.php">
      <input type="hidden" name="id_user" value="<?= $id ?>">
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
        <!-- tombol batal: kembali ke halaman detail tanpa menghapus -->
        <a href="viewuser.php?id=<?= $id ?>" class="tombolringan">
          <i class="fa-solid fa-xmark"></i> Batal
        </a>
        <!-- tombol hapus: mengirim form POST ke proseshapususer.php -->
        <button type="submit" class="tombolbahaya">
          <i class="fa-solid fa-trash"></i> Ya, Hapus Permanen
        </button>
      </div>
    </form>
  </div>

</main>
</body>
</html>
