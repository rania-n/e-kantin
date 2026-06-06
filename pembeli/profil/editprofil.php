<?php
/* halaman edit profil pembeli
   menangani dua kondisi:
   1. GET: tampilkan form dengan data saat ini
   2. POST: validasi dan simpan perubahan username + email

   catatan: nama lengkap, kelas, dan jurusan TIDAK bisa diubah pembeli karena
   itu identitas yang sudah diverifikasi admin. kalau pembeli salah tulis saat
   daftar, dia perlu menghubungi admin untuk perbaikan. */

// guard memastikan hanya pembeli yang login yang bisa mengakses
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

$idpengguna = (int)$_SESSION['id_user'];
$error      = ''; // pesan error yang akan ditampilkan di halaman jika validasi gagal

// proses form saat form dikirim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // trim menghapus spasi di awal/akhir input
    $usernamebaru = trim($_POST['username']   ?? '');
    $emailbaru    = trim($_POST['email']      ?? '');
    $noteleponbaru= trim($_POST['no_telepon'] ?? '');
    $daftarerror  = []; // kumpulkan semua error sebelum menampilkan

    // validasi panjang username
    if (strlen($usernamebaru) < 6)                           $daftarerror[] = 'Username minimal 6 karakter';
    if (strlen($usernamebaru) > 50)                          $daftarerror[] = 'Username maksimal 50 karakter';
    // validasi format email menggunakan filter bawaan PHP
    if (!filter_var($emailbaru, FILTER_VALIDATE_EMAIL))      $daftarerror[] = 'Format email tidak valid';
    // validasi nomor telepon: ambil angkanya saja, harus 8–15 digit
    $telpdigit = preg_replace('/\D/', '', $noteleponbaru);
    if (strlen($telpdigit) < 8 || strlen($telpdigit) > 15)   $daftarerror[] = 'Nomor telepon harus 8–15 digit angka';

    // jika tidak ada error format, cek duplikat di database
    if (empty($daftarerror)) {
        // cari user lain (selain diri sendiri) yang punya username atau email yang sama
        $cek = $conn->prepare("SELECT id_user FROM tb_user WHERE (username=? OR email=?) AND id_user!=? AND deleted=0");
        $cek->bind_param("ssi", $usernamebaru, $emailbaru, $idpengguna);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) $daftarerror[] = 'Username atau email sudah digunakan akun lain';
        $cek->close();
    }

    // jika semua validasi lolos, simpan perubahan ke database
    if (empty($daftarerror)) {
        $upd = $conn->prepare("UPDATE tb_user SET username=?,email=?,no_telepon=? WHERE id_user=?");
        $upd->bind_param("sssi", $usernamebaru, $emailbaru, $noteleponbaru, $idpengguna);
        $upd->execute(); $upd->close();
        // perbarui juga data di session agar navbar langsung menampilkan nama baru
        $_SESSION['username'] = $usernamebaru;
        $_SESSION['email']    = $emailbaru;
        // simpan pesan flash untuk ditampilkan setelah redirect
        $_SESSION['flash']    = ['pesan' => 'Profil berhasil diperbarui', 'jenis' => 'sukses'];
        // redirect ke halaman profil setelah berhasil simpan
        header("Location: profil.php"); exit;
    } else {
        // gabungkan semua pesan error menjadi satu string
        $error = implode(', ', $daftarerror);
    }
}

// ambil data user terbaru dari database (untuk isi nilai default form)
$q = $conn->prepare("SELECT * FROM tb_user WHERE id_user=? AND deleted=0");
$q->bind_param("i", $idpengguna); $q->execute();
$user = $q->get_result()->fetch_assoc(); $q->close();

/* ambil statistik untuk ditampilkan di bagian atas halaman
   (sama seperti di profil.php — ditampilkan ulang agar konsisten) */

// total pesanan tidak termasuk yang dibatalkan
$s1 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND deleted=0 AND status_order NOT IN ('Dibatalkan')");
$s1->bind_param("i",$idpengguna); $s1->execute();
$totalpesanan = (int)$s1->get_result()->fetch_row()[0]; $s1->close();

// total pesanan selesai
$s2 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_user=? AND status_order='Selesai' AND deleted=0");
$s2->bind_param("i",$idpengguna); $s2->execute();
$totalselesai = (int)$s2->get_result()->fetch_row()[0]; $s2->close();

// total uang yang sudah dibelanjakan dari pesanan selesai
$s3 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_user=? AND status_order='Selesai' AND deleted=0");
$s3->bind_param("i",$idpengguna); $s3->execute();
$totalbelanja = (int)$s3->get_result()->fetch_row()[0]; $s3->close();

// format total belanja singkat
$formatbelanja = $totalbelanja >= 1000000
    ? 'Rp ' . number_format($totalbelanja/1000000, 1) . 'jt'
    : 'Rp ' . number_format($totalbelanja/1000, 0) . 'rb';

// inisial untuk avatar
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

  <!-- hero profil di atas form -->
  <div class="heroprofil">
    <div class="avatar"><?= $inisial ?></div>
    <div class="namapengguna"><?= htmlspecialchars($user['username']) ?></div>
    <div class="emailpengguna"><?= htmlspecialchars($user['email']) ?></div>
    <span class="labelperan"><i class="fa-solid fa-user"></i> Pembeli</span>
  </div>

  <!-- statistik singkat untuk konteks -->
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

  <!-- tampilkan pesan error jika ada validasi yang gagal -->
  <?php if ($error): ?>
  <div class="peringatan peringatangagal">
    <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- form edit profil — dikirim ke diri sendiri (action="editprofil.php") -->
  <form method="POST" action="editprofil.php">
    <div class="kartu">
      <h3>Informasi Akun</h3>

      <!-- input username: nilai diisi dari data user yang ada di database -->
      <div class="kelompokform">
        <label>Username</label>
        <!-- value diisi dengan data saat ini — jika POST gagal, nilai tetap yang lama -->
        <input type="text" name="username"
               value="<?= htmlspecialchars($user['username'] ?? '') ?>"
               required minlength="6" maxlength="50"
               placeholder="Username...">
      </div>

      <!-- input email -->
      <div class="kelompokform">
        <label>Email</label>
        <input type="email" name="email"
               value="<?= htmlspecialchars($user['email'] ?? '') ?>"
               required placeholder="Email...">
      </div>

      <!-- input nomor telepon — hanya angka (huruf/simbol ditolak), 8–15 digit -->
      <div class="kelompokform">
        <label>Nomor Telepon / WhatsApp</label>
        <input type="tel" name="no_telepon"
               inputmode="numeric" pattern="[0-9]{8,15}" minlength="8" maxlength="15"
               oninput="this.value=this.value.replace(/\D/g,'')"
               value="<?= htmlspecialchars($user['no_telepon'] ?? '') ?>"
               required placeholder="cth: 081234567890" title="Hanya angka, 8–15 digit">
        <small style="color:var(--tekssamar);">Nomor aktif yang bisa dihubungi (hanya angka, 8–15 digit).</small>
      </div>

      <!-- field nama lengkap: read-only (sudah diverifikasi admin) -->
      <div class="kelompokform">
        <label>Nama Lengkap <span style="color:var(--tekssamar);font-size:11px;">(tidak bisa diubah)</span></label>
        <input type="text" value="<?= htmlspecialchars($user['nama_lengkap'] ?? '—') ?>" disabled>
        <small style="color:var(--tekssamar);">Hubungi admin kantin jika ada kesalahan data.</small>
      </div>

      <!-- field kelas: read-only -->
      <div class="kelompokform">
        <label>Kelas &amp; Jurusan <span style="color:var(--tekssamar);font-size:11px;">(tidak bisa diubah)</span></label>
        <input type="text" value="<?= htmlspecialchars($user['kelas'] ?? '—') ?>" disabled>
      </div>

      <!-- field peran: disabled karena tidak bisa diubah oleh pembeli sendiri -->
      <div class="kelompokform">
        <label>Peran</label>
        <input type="text" value="Pembeli" disabled>
      </div>

      <!-- field tanggal bergabung: read-only, hanya untuk informasi -->
      <div class="kelompokform">
        <label>Member Sejak</label>
        <!-- format tanggal: "05 Jan 2025" — '-' jika tidak ada data -->
        <input type="text" value="<?= !empty($user['created']) ? date('d M Y', strtotime($user['created'])) : '-' ?>" disabled>
      </div>

      <div style="display:flex;gap:10px;margin-top:4px;">
        <!-- tombol batal: kembali ke profil tanpa menyimpan -->
        <a href="profil.php" class="tombolringan" style="padding:10px 14px;font-size:13px;">
          Batal
        </a>
        <!-- tombol simpan: submit form -->
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
