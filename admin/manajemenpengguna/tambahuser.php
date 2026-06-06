<?php
/* halaman tambah pengguna oleh admin.
   alur 2 langkah (pure PHP, tanpa JS):
   1. tampilkan pemilih peran kalau ?role= belum ada di URL
   2. tampilkan form khusus sesuai peran kalau ?role= valid

   pemilih peran dipisah supaya tiap form hanya menampilkan field yang relevan —
   admin tidak bisa salah isi field yang bukan untuk peran tersebut. */

include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';
// helper bersama untuk dropdown kelas (khusus pembeli)
include '../../3. komponen/kelas_jurusan.php';

$halamansaatini = 'user';

// ambil flash & oldinput dari proses sebelumnya
$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']);
}
$oldinput = $_SESSION['oldinput'] ?? [];
unset($_SESSION['oldinput']);

// baca peran dari URL — kalau tidak ada / tidak valid, tampilkan pemilih peran.
$role = $_GET['role'] ?? '';
if (!in_array($role, ['penjual','pembeli','admin'])) $role = '';

// cek migrasi kolom kantin (dipakai khusus form penjual)
$cekkolom = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'nomor_kantin'");
$migrasiSudah = ($cekkolom && $cekkolom->num_rows > 0);

// ambil daftar kantin kosong — hanya dibutuhkan kalau role = penjual
$kantinkosong = [];
if ($role === 'penjual') {
    $kolomNomor = $migrasiSudah ? "nomor_kantin" : "NULL AS nomor_kantin";
    $orderKolom = $migrasiSudah ? "nomor_kantin ASC" : "id_toko ASC";
    $resKantin  = $conn->query("SELECT id_toko, {$kolomNomor} FROM tb_toko WHERE id_user IS NULL AND deleted=0 ORDER BY $orderKolom");
    $kantinkosong = $resKantin ? $resKantin->fetch_all(MYSQLI_ASSOC) : [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Pengguna - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-user-plus"></i> Tambah Pengguna</h1>
      <p>
        <?php if ($role === ''): ?>
          Pilih peran pengguna baru yang ingin ditambahkan
        <?php else: ?>
          Isi data <?= htmlspecialchars(ucfirst($role)) ?> baru
        <?php endif; ?>
      </p>
    </div>
    <a href="<?= $role === '' ? 'user.php' : 'tambahuser.php' ?>" class="tombolringan">
      <i class="fa-solid fa-arrow-left"></i>
      <?= $role === '' ? 'Kembali ke Daftar' : 'Ganti Peran' ?>
    </a>
  </div>

  <?php if ($flashpesan): ?>
  <div class="flashpesan flash<?= $flashjenis ?>">
    <i class="fa-solid fa-<?= $flashjenis === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
  <?php endif; ?>


  <?php if ($role === ''): // ============== LANGKAH 1: PEMILIH PERAN ============== ?>

  <!-- pemilih peran: 3 kartu disusun vertikal (atas-bawah), masing-masing
       full-width nempel kanan-kiri. layout horizontal di dalam kartu:
       ikon kiri + teks kanan. -->
  <div class="kartu-pilih-peran-grid">
    <a href="tambahuser.php?role=penjual" class="kartu-pilih-peran">
      <div class="ikon-pilih"><i class="fa-solid fa-store"></i></div>
      <div class="isi-pilih">
        <h3>Penjual</h3>
        <p>Pemilik kantin. Akan menempati salah satu dari 10 slot kantin yang tersedia.</p>
      </div>
      <i class="fa-solid fa-chevron-right panah-pilih"></i>
    </a>
    <a href="tambahuser.php?role=pembeli" class="kartu-pilih-peran">
      <div class="ikon-pilih"><i class="fa-solid fa-bag-shopping"></i></div>
      <div class="isi-pilih">
        <h3>Pembeli</h3>
        <p>Murid, guru, atau staf sekolah. Otomatis terverifikasi karena dibuat admin.</p>
      </div>
      <i class="fa-solid fa-chevron-right panah-pilih"></i>
    </a>
    <a href="tambahuser.php?role=admin" class="kartu-pilih-peran">
      <div class="ikon-pilih"><i class="fa-solid fa-user-shield"></i></div>
      <div class="isi-pilih">
        <h3>Admin Platform</h3>
        <p>Pengelola platform. Bisa kelola pengguna, kantin, dan melihat laporan.</p>
      </div>
      <i class="fa-solid fa-chevron-right panah-pilih"></i>
    </a>
  </div>

  <!-- gaya kartu pemilih peran. inline-style supaya tidak mengubah file CSS bersama. -->
  <style>
    .kartu-pilih-peran-grid {
      display: flex;
      flex-direction: column; /* susun ke bawah, bukan ke samping */
      gap: 12px;
      margin-top: 8px;
    }
    .kartu-pilih-peran {
      display: flex;
      align-items: center;
      gap: 16px;
      width: 100%; /* fit kanan-kiri */
      background: white;
      border: 1.5px solid var(--garis);
      border-radius: 14px;
      padding: 16px 18px;
      text-decoration: none;
      color: inherit;
      transition: all .15s ease;
    }
    .kartu-pilih-peran:hover {
      border-color: var(--utama);
      background: var(--putihbg);
    }
    .kartu-pilih-peran .ikon-pilih {
      width: 52px; height: 52px; border-radius: 50%;
      background: var(--latar); color: var(--utama);
      display: flex; align-items: center; justify-content: center;
      font-size: 22px;
      flex-shrink: 0;
    }
    .kartu-pilih-peran .isi-pilih { flex: 1; min-width: 0; }
    .kartu-pilih-peran h3 {
      color: var(--utama);
      font-size: 16px; font-weight: 800;
      margin: 0 0 4px;
    }
    .kartu-pilih-peran p {
      color: var(--tekssamar);
      font-size: 13px; line-height: 1.45;
      margin: 0;
    }
    .kartu-pilih-peran .panah-pilih {
      color: var(--tekssamar);
      font-size: 14px;
      flex-shrink: 0;
    }
    .kartu-pilih-peran:hover .panah-pilih { color: var(--utama); }

    /* mobile: kurangi padding & ikon */
    @media (max-width: 600px) {
      .kartu-pilih-peran { padding: 14px; gap: 12px; }
      .kartu-pilih-peran .ikon-pilih { width: 44px; height: 44px; font-size: 18px; }
      .kartu-pilih-peran h3 { font-size: 15px; }
      .kartu-pilih-peran p  { font-size: 12px; }
    }
  </style>


  <?php elseif ($role === 'penjual'): // ============ FORM PENJUAL ============ ?>

  <?php if (empty($kantinkosong)): ?>
  <!-- semua kantin terisi → admin harus hapus penjual dulu -->
  <div class="peringatan peringataninfo" style="margin-bottom:16px;">
    <i class="fa-solid fa-store-slash"></i>
    <strong>Semua kantin sudah terisi.</strong>
    Hapus dulu penjual yang ada agar slotnya menjadi tersedia.
    <a href="../manajementoko/kantin.php" style="color:var(--info);font-weight:700;">Lihat status kantin →</a>
  </div>
  <?php endif; ?>

  <div class="kartu">
    <h3><i class="fa-solid fa-store"></i> Data Penjual Baru</h3>
    <!-- enctype multipart/form-data wajib untuk upload file foto -->
    <form method="POST" action="prosestambahuser.php" autocomplete="off" enctype="multipart/form-data">
      <input type="hidden" name="role" value="penjual">

      <div class="barisform">
        <div class="kelompokform">
          <label>Username <span style="color:var(--gagal);">*</span></label>
          <input type="text" name="username" required minlength="6" maxlength="50" autocomplete="off"
                 value="<?= htmlspecialchars($oldinput['username'] ?? '') ?>"
                 placeholder="Minimal 6 karakter...">
          <small>6–50 karakter, hanya huruf/angka/titik/garis bawah, tanpa spasi</small>
        </div>
        <div class="kelompokform">
          <label>Email <span style="color:var(--gagal);">*</span></label>
          <input type="email" name="email" required autocomplete="off"
                 value="<?= htmlspecialchars($oldinput['email'] ?? '') ?>"
                 placeholder="Email aktif...">
        </div>
      </div>

      <div class="barisform">
        <div class="kelompokform">
          <label>Nomor Telepon / WhatsApp <span style="color:var(--gagal);">*</span></label>
          <!-- nomor aktif supaya pengguna bisa dihubungi kalau ada kendala.
               inputmode numeric + pattern + oninput strip → hanya angka yang bisa masuk. -->
          <input type="tel" name="no_telepon" required autocomplete="off"
                 inputmode="numeric" pattern="[0-9]{8,15}" minlength="8" maxlength="15"
                 oninput="this.value=this.value.replace(/\D/g,'')"
                 value="<?= htmlspecialchars($oldinput['no_telepon'] ?? '') ?>"
                 placeholder="cth: 081234567890" title="Hanya angka, 8–15 digit">
          <small>Nomor aktif yang bisa dihubungi (hanya angka, 8–15 digit).</small>
        </div>
      </div>

      <div class="barisform">
        <div class="kelompokform">
          <label>Password <span style="color:var(--gagal);">*</span></label>
          <div style="position:relative;">
            <input type="password" name="password" id="pass_tambah" required
                   minlength="8" maxlength="100" autocomplete="new-password"
                   placeholder="Minimal 8 karakter..." style="padding-right:44px;">
            <!-- tombol show/hide password — JS diizinkan KHUSUS untuk fitur password -->
            <button type="button"
                    onclick="(function(b){var i=document.getElementById('pass_tambah');i.type=i.type==='password'?'text':'password';b.querySelector('i').className=i.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash';})(this)"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#99627A;cursor:pointer;font-size:15px;padding:4px;">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
          <small>8–100 karakter</small>
        </div>
        <div class="kelompokform">
          <label>Pilih Kantin <span style="color:var(--gagal);">*</span></label>
          <select name="id_kantin" required>
            <option value="">— Pilih kantin tersedia —</option>
            <?php foreach ($kantinkosong as $k): ?>
            <option value="<?= (int)$k['id_toko'] ?>"
              <?= isset($oldinput['id_kantin']) && (int)$oldinput['id_kantin']===(int)$k['id_toko'] ? 'selected' : '' ?>>
              <?= $k['nomor_kantin'] !== null ? 'Kantin ke-' . (int)$k['nomor_kantin'] : 'Kantin #' . (int)$k['id_toko'] ?>
            </option>
            <?php endforeach; ?>
          </select>
          <small><?= count($kantinkosong) ?> slot kantin tersedia dari 10.</small>
        </div>
      </div>

      <div class="kelompokform">
        <label>Nama Toko <span style="color:var(--gagal);">*</span></label>
        <input type="text" name="nama_toko" maxlength="100" required
               value="<?= htmlspecialchars($oldinput['nama_toko'] ?? '') ?>"
               placeholder="Nama toko yang akan muncul di aplikasi pembeli...">
      </div>

      <!-- upload foto toko — opsional, bisa dipasang sekarang atau nanti -->
      <div class="kelompokform">
        <label>Foto Toko <span style="color:var(--tekssamar);font-size:11px;">(opsional)</span></label>
        <input type="file" name="foto_toko" accept="image/jpeg,image/png,image/webp">
        <small>JPG/PNG/WEBP, maks. 2MB. Kosongkan jika ingin diisi nanti oleh penjual.</small>
      </div>

      <div style="display:flex;gap:10px;margin-top:6px;flex-wrap:wrap;">
        <a href="tambahuser.php" class="tombolringan">Batal</a>
        <button type="submit" class="tombolutama" style="flex:1;">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Penjual
        </button>
      </div>
    </form>
  </div>


  <?php elseif ($role === 'pembeli'): // ============ FORM PEMBELI ============ ?>

  <div class="peringatan peringataninfo" style="margin-bottom:16px;">
    <i class="fa-solid fa-circle-check"></i>
    Pembeli yang ditambahkan admin otomatis <strong>terverifikasi</strong> — bisa langsung login.
  </div>

  <div class="kartu">
    <h3><i class="fa-solid fa-bag-shopping"></i> Data Pembeli Baru</h3>
    <form method="POST" action="prosestambahuser.php" autocomplete="off">
      <input type="hidden" name="role" value="pembeli">

      <div class="barisform">
        <div class="kelompokform">
          <label>Username <span style="color:var(--gagal);">*</span></label>
          <input type="text" name="username" required minlength="6" maxlength="50" autocomplete="off"
                 value="<?= htmlspecialchars($oldinput['username'] ?? '') ?>"
                 placeholder="Minimal 6 karakter...">
          <small>6–50 karakter, hanya huruf/angka/titik/garis bawah, tanpa spasi</small>
        </div>
        <div class="kelompokform">
          <label>Email <span style="color:var(--gagal);">*</span></label>
          <input type="email" name="email" required autocomplete="off"
                 value="<?= htmlspecialchars($oldinput['email'] ?? '') ?>"
                 placeholder="Email aktif...">
        </div>
      </div>

      <div class="barisform">
        <div class="kelompokform">
          <label>Nomor Telepon / WhatsApp <span style="color:var(--gagal);">*</span></label>
          <!-- nomor aktif supaya pengguna bisa dihubungi kalau ada kendala.
               inputmode numeric + pattern + oninput strip → hanya angka yang bisa masuk. -->
          <input type="tel" name="no_telepon" required autocomplete="off"
                 inputmode="numeric" pattern="[0-9]{8,15}" minlength="8" maxlength="15"
                 oninput="this.value=this.value.replace(/\D/g,'')"
                 value="<?= htmlspecialchars($oldinput['no_telepon'] ?? '') ?>"
                 placeholder="cth: 081234567890" title="Hanya angka, 8–15 digit">
          <small>Nomor aktif yang bisa dihubungi (hanya angka, 8–15 digit).</small>
        </div>
      </div>

      <div class="barisform">
        <div class="kelompokform">
          <label>Password <span style="color:var(--gagal);">*</span></label>
          <div style="position:relative;">
            <input type="password" name="password" id="pass_tambah" required
                   minlength="8" maxlength="100" autocomplete="new-password"
                   placeholder="Minimal 8 karakter..." style="padding-right:44px;">
            <button type="button"
                    onclick="(function(b){var i=document.getElementById('pass_tambah');i.type=i.type==='password'?'text':'password';b.querySelector('i').className=i.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash';})(this)"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#99627A;cursor:pointer;font-size:15px;padding:4px;">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
          <small>8–100 karakter</small>
        </div>
        <div class="kelompokform">
          <label>Nama Lengkap <span style="color:var(--gagal);">*</span></label>
          <input type="text" name="nama_lengkap" maxlength="100" required
                 value="<?= htmlspecialchars($oldinput['nama_lengkap'] ?? '') ?>"
                 placeholder="Nama lengkap sesuai identitas...">
        </div>
      </div>

      <div class="kelompokform">
        <label>Kelas / Status <span style="color:var(--gagal);">*</span></label>
        <?php tampilkanDropdownKelas($oldinput['kelas'] ?? '', true, 'kelas'); ?>
        <small>Murid pilih kelas + jurusan. Guru atau staf sekolah pilih opsi Non-Murid.</small>
      </div>

      <div style="display:flex;gap:10px;margin-top:6px;flex-wrap:wrap;">
        <a href="tambahuser.php" class="tombolringan">Batal</a>
        <button type="submit" class="tombolutama" style="flex:1;">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Pembeli
        </button>
      </div>
    </form>
  </div>


  <?php elseif ($role === 'admin'): // ============ FORM ADMIN ============ ?>

  <div class="kartu">
    <h3><i class="fa-solid fa-user-shield"></i> Data Admin Baru</h3>
    <form method="POST" action="prosestambahuser.php" autocomplete="off">
      <input type="hidden" name="role" value="admin">

      <div class="barisform">
        <div class="kelompokform">
          <label>Username <span style="color:var(--gagal);">*</span></label>
          <input type="text" name="username" required minlength="6" maxlength="50" autocomplete="off"
                 value="<?= htmlspecialchars($oldinput['username'] ?? '') ?>"
                 placeholder="Minimal 6 karakter...">
          <small>6–50 karakter, hanya huruf/angka/titik/garis bawah, tanpa spasi</small>
        </div>
        <div class="kelompokform">
          <label>Email <span style="color:var(--gagal);">*</span></label>
          <input type="email" name="email" required autocomplete="off"
                 value="<?= htmlspecialchars($oldinput['email'] ?? '') ?>"
                 placeholder="Email aktif...">
        </div>
      </div>

      <div class="barisform">
        <div class="kelompokform">
          <label>Nomor Telepon / WhatsApp <span style="color:var(--gagal);">*</span></label>
          <!-- nomor aktif supaya pengguna bisa dihubungi kalau ada kendala.
               inputmode numeric + pattern + oninput strip → hanya angka yang bisa masuk. -->
          <input type="tel" name="no_telepon" required autocomplete="off"
                 inputmode="numeric" pattern="[0-9]{8,15}" minlength="8" maxlength="15"
                 oninput="this.value=this.value.replace(/\D/g,'')"
                 value="<?= htmlspecialchars($oldinput['no_telepon'] ?? '') ?>"
                 placeholder="cth: 081234567890" title="Hanya angka, 8–15 digit">
          <small>Nomor aktif yang bisa dihubungi (hanya angka, 8–15 digit).</small>
        </div>
      </div>

      <div class="kelompokform">
        <label>Password <span style="color:var(--gagal);">*</span></label>
        <div style="position:relative;">
          <input type="password" name="password" id="pass_tambah" required
                 minlength="8" maxlength="100" autocomplete="new-password"
                 placeholder="Minimal 8 karakter..." style="padding-right:44px;">
          <button type="button"
                  onclick="(function(b){var i=document.getElementById('pass_tambah');i.type=i.type==='password'?'text':'password';b.querySelector('i').className=i.type==='password'?'fa-solid fa-eye':'fa-solid fa-eye-slash';})(this)"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#99627A;cursor:pointer;font-size:15px;padding:4px;">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
        <small>8–100 karakter</small>
      </div>

      <div style="display:flex;gap:10px;margin-top:6px;flex-wrap:wrap;">
        <a href="tambahuser.php" class="tombolringan">Batal</a>
        <button type="submit" class="tombolutama" style="flex:1;">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Admin
        </button>
      </div>
    </form>
  </div>

  <?php endif; ?>

</main>
</body>
</html>
