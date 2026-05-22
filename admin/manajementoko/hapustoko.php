<?php
/* halaman konfirmasi kosongkan kantin.
   menampilkan nomor kantin, nama toko, dan nama penjualnya beserta peringatan.
   PENTING: kantin tidak benar-benar dihapus dari database — hanya dikosongkan.
   penjual juga tidak ikut dihapus, hanya dilepas dari kantin ini.
   gunakan menu "Hapus Pengguna" jika ingin menghapus akun penjual juga. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tandai menu "kantin" sebagai aktif di navbar
$halamansaatini = 'kantin';

// ambil id toko dari url, konversi ke integer
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// jika id tidak valid, kembalikan ke daftar kantin
if (!$id) { header("Location: kantin.php"); exit; }

// ambil data toko beserta nomor kantin dan nama pemilik
// LEFT JOIN karena kantin bisa saja sudah kosong (id_user NULL)
$qt = $conn->prepare(
    "SELECT t.id_toko, t.nomor_kantin, t.nama_toko, t.id_user,
            u.username
     FROM tb_toko t
     LEFT JOIN tb_user u ON t.id_user = u.id_user AND u.deleted=0
     WHERE t.id_toko=? AND t.deleted=0"
);
$qt->bind_param("i", $id); $qt->execute();
$toko = $qt->get_result()->fetch_assoc(); $qt->close();

// jika toko tidak ditemukan, kembalikan ke daftar
if (!$toko) { header("Location: kantin.php"); exit; }

// jika kantin sudah kosong, tidak perlu dikosongkan lagi
if (!$toko['id_user']) {
    header("Location: kantin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kosongkan Kantin - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <!-- kotak konfirmasi kosongkan kantin -->
  <div class="kotakkonfirm">
    <div class="ikon-konfirm"><i class="fa-solid fa-store-slash"></i></div>
    <h2>Kosongkan Kantin?</h2>
    <p>
      Kamu akan melepas penjual <strong><?= htmlspecialchars($toko['username'] ?? '—') ?></strong>
      dari <strong>Kantin ke-<?= (int)$toko['nomor_kantin'] ?></strong>
      ("<?= htmlspecialchars($toko['nama_toko'] ?? '—') ?>").
      <br><br>
      <!-- penjelasan perbedaan: kantin tidak dihapus, hanya dikosongkan -->
      Kantin <strong>tidak dihapus</strong> dari database — hanya dikosongkan sehingga
      tersedia untuk penjual baru. Akun penjual <strong>tidak ikut dihapus</strong>.
      Jika ingin menghapus akun penjual, gunakan menu Hapus Pengguna.
    </p>
    <!-- form konfirmasi: id_toko dikirim ke proseshapustoko.php via POST -->
    <form method="POST" action="proseshapustoko.php">
      <input type="hidden" name="id_toko" value="<?= $id ?>">
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
        <!-- tombol batal: kembali ke detail toko tanpa mengosongkan -->
        <a href="viewtoko.php?id=<?= $id ?>" class="tombolringan">
          <i class="fa-solid fa-xmark"></i> Batal
        </a>
        <!-- tombol kosongkan: mengirim form POST ke proseshapustoko.php -->
        <button type="submit" class="tombolbahaya">
          <i class="fa-solid fa-store-slash"></i> Ya, Kosongkan Kantin
        </button>
      </div>
    </form>
  </div>

</main>
</body>
</html>
