<?php
/* halaman edit pengguna oleh admin.
   field yang ditampilkan menyesuaikan peran:
     - penjual : username, email, nama toko, FOTO TOKO, password
     - pembeli : username, email, nama lengkap, KELAS / STATUS, password (TANPA foto)
     - admin   : username, email, password (TANPA foto)

   peran tidak bisa diubah dari sini. foto profil pengguna (kolom tb_user.foto)
   memang TIDAK dipakai di mana pun untuk pembeli/admin — fitur foto hanya
   ada di toko penjual. */

include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';
include '../../3. komponen/kelas_jurusan.php';

$halamansaatini = 'user';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: user.php"); exit; }

// ambil data pengguna
$qu = $conn->prepare("SELECT * FROM tb_user WHERE id_user=? AND deleted=0");
$qu->bind_param("i", $id); $qu->execute();
$user = $qu->get_result()->fetch_assoc(); $qu->close();

if (!$user) { header("Location: user.php"); exit; }

// ambil data toko hanya kalau penjual
$toko = null;
if ($user['role'] === 'penjual') {
    $qt = $conn->prepare("SELECT * FROM tb_toko WHERE id_user=? AND deleted=0");
    $qt->bind_param("i", $id); $qt->execute();
    $toko = $qt->get_result()->fetch_assoc(); $qt->close();
}

// flash & oldinput dari proses sebelumnya
$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']);
}
$oldinput = $_SESSION['oldinput'] ?? [];
unset($_SESSION['oldinput']);

// pre-fill nilai: pakai oldinput kalau ada (sehabis error), kalau tidak pakai data DB
$valUsername    = $oldinput['username']     ?? $user['username'];
$valEmail       = $oldinput['email']        ?? $user['email'];
$valNamaLengkap = $oldinput['nama_lengkap'] ?? ($user['nama_lengkap'] ?? '');
$valKelas       = $oldinput['kelas']        ?? ($user['kelas'] ?? '');
$valNamaToko    = $oldinput['nama_toko']    ?? ($toko['nama_toko'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Pengguna - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-user-pen"></i> Edit Pengguna</h1>
      <p>Ubah data akun <?= htmlspecialchars($user['username']) ?> (<?= ucfirst($user['role']) ?>)</p>
    </div>
    <a href="viewuser.php?id=<?= $id ?>" class="tombolringan">
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
    <h3><i class="fa-solid fa-pen"></i> Ubah Informasi Akun</h3>
    <!-- enctype multipart/form-data hanya dibutuhkan kalau penjual (upload foto toko) -->
    <form method="POST" action="prosesedituser.php"
          <?= $user['role']==='penjual' ? 'enctype="multipart/form-data"' : '' ?>>
      <input type="hidden" name="id_user" value="<?= $id ?>">

      <!-- field dasar (semua peran) -->
      <div class="barisform">
        <div class="kelompokform">
          <label>Username <span style="color:var(--gagal);">*</span></label>
          <input type="text" name="username" required minlength="6" maxlength="50" autocomplete="off"
                 value="<?= htmlspecialchars($valUsername) ?>">
          <small>6–50 karakter, hanya huruf/angka/titik/garis bawah, tanpa spasi</small>
        </div>
        <div class="kelompokform">
          <label>Email <span style="color:var(--gagal);">*</span></label>
          <input type="email" name="email" required autocomplete="off"
                 value="<?= htmlspecialchars($valEmail) ?>">
        </div>
      </div>


      <?php if ($user['role'] === 'penjual' && $toko): ?>
      <!-- ===== KHUSUS PENJUAL: nama toko + foto toko ===== -->
      <div class="kelompokform">
        <label>Nama Toko</label>
        <input type="text" name="nama_toko" maxlength="100"
               value="<?= htmlspecialchars($valNamaToko) ?>">
      </div>
      <div class="kelompokform">
        <label>Foto Toko</label>
        <?php
        $fotoToko = $toko['foto_toko'] ?? '';
        $adaFotoToko = $fotoToko && file_exists(__DIR__ . '/../../2. aset/profil/' . $fotoToko);
        ?>
        <?php if ($adaFotoToko): ?>
        <div style="margin-bottom:8px;">
          <img src="../../2. aset/profil/<?= htmlspecialchars($fotoToko) ?>" alt="Foto Toko"
               style="width:80px;height:80px;object-fit:cover;border-radius:14px;border:2px solid var(--garis);">
        </div>
        <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;text-transform:none;color:var(--gagal);padding:4px 10px;margin-bottom:6px;border-radius:6px;cursor:pointer;background:var(--gagalbg);border:1px solid #FCA5A5;">
          <input type="checkbox" name="hapus_foto_toko" value="1" style="margin:0;cursor:pointer;">
          <i class="fa-solid fa-trash-can"></i> Hapus
        </label>
        <?php endif; ?>
        <input type="file" name="foto_toko" accept="image/jpeg,image/png,image/webp">
        <small>JPG/PNG/WEBP, maks. 2MB. Kosongkan jika tidak ingin mengganti.</small>
      </div>

      <?php elseif ($user['role'] === 'pembeli'): ?>
      <!-- ===== KHUSUS PEMBELI: nama lengkap + kelas/status (TANPA foto) ===== -->
      <div class="barisform">
        <div class="kelompokform">
          <label>Nama Lengkap <span style="color:var(--gagal);">*</span></label>
          <input type="text" name="nama_lengkap" required maxlength="100"
                 value="<?= htmlspecialchars($valNamaLengkap) ?>">
        </div>
        <div class="kelompokform">
          <label>Kelas / Status <span style="color:var(--gagal);">*</span></label>
          <?php tampilkanDropdownKelas($valKelas, true, 'kelas'); ?>
          <small>Murid pilih kelas + jurusan. Guru atau staf pilih opsi Non-Murid.</small>
        </div>
      </div>

      <?php endif; ?>
      <!-- admin: tidak ada field tambahan, tidak ada foto. -->


      <!-- field umum: peran (read-only) + password baru (opsional) -->
      <div class="kelompokform">
        <label>Peran</label>
        <input type="text" value="<?= ucfirst($user['role']) ?>" disabled>
        <small>Peran tidak dapat diubah melalui form ini.</small>
      </div>

      <div class="kelompokform">
        <label>Password Baru</label>
        <div style="position:relative;">
          <input type="password" name="password" id="pass_edit" minlength="8" maxlength="100"
                 placeholder="Kosongkan jika tidak ingin mengubah..." style="padding-right:44px;">
          <!-- tombol show/hide password — JS hanya dipakai untuk fitur password -->
          <button type="button" onclick="(function(b){var i=document.getElementById('pass_edit');i.type=i.type==='password'?'text':'password';b.querySelector('i').className=i.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash';})(this)"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#99627A;cursor:pointer;font-size:15px;padding:4px;">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
        <small>Isi hanya jika ingin mengganti password</small>
      </div>

      <div style="display:flex;gap:10px;margin-top:6px;">
        <a href="viewuser.php?id=<?= $id ?>" class="tombolringan">Batal</a>
        <button type="submit" class="tombolutama" style="flex:1;">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
        </button>
      </div>
    </form>
  </div>

</main>
</body>
</html>
