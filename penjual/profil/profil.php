<?php
/* ============================================================
   PROFIL & TOKO PENJUAL
   Tab: Profil Saya (view) | Edit Profil & Toko | Ganti Password
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idpengguna = (int)$_SESSION['id_user'];
$idtoko     = (int)$_SESSION['id_toko'];
$halamansaatini = 'profil';

// Ambil data user & toko
$qu = $conn->prepare("SELECT * FROM tb_user WHERE id_user=? AND deleted=0");
$qu->bind_param("i", $idpengguna); $qu->execute();
$user = $qu->get_result()->fetch_assoc(); $qu->close();

$qt = $conn->prepare("SELECT * FROM tb_toko WHERE id_toko=? AND deleted=0");
$qt->bind_param("i", $idtoko); $qt->execute();
$toko = $qt->get_result()->fetch_assoc(); $qt->close();

// Statistik toko
$qs1 = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_toko=? AND deleted=0");
$qs1->bind_param("i", $idtoko); $qs1->execute();
$totalpesanan = (int)$qs1->get_result()->fetch_row()[0]; $qs1->close();

$qs2 = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_toko=? AND status_order='Selesai' AND deleted=0");
$qs2->bind_param("i", $idtoko); $qs2->execute();
$totalpendapatan = (float)$qs2->get_result()->fetch_row()[0]; $qs2->close();

$qs3 = $conn->prepare("SELECT ROUND(AVG(rating_toko),1), COUNT(*) FROM tb_rating WHERE id_toko=? AND deleted=0");
$qs3->bind_param("i", $idtoko); $qs3->execute();
$rrow = $qs3->get_result()->fetch_row(); $qs3->close();
$ratarating = (float)($rrow[0] ?? 0);
$jmlrating  = (int)($rrow[1] ?? 0);

$qs4 = $conn->prepare("SELECT COUNT(*) FROM tb_menu WHERE id_toko=? AND status='aktif' AND deleted=0");
$qs4->bind_param("i", $idtoko); $qs4->execute();
$totalmenu = (int)$qs4->get_result()->fetch_row()[0]; $qs4->close();

$inisial = strtoupper(mb_substr($user['username'] ?? 'P', 0, 2));

// Flash message dari proses file
$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']);
}

$tabaktif = $_GET['tab'] ?? 'profil';
if (!in_array($tabaktif, ['profil', 'edit', 'password'])) $tabaktif = 'profil';

function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
function bintang(float $r): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $warna = $i <= $r ? '#F59E0B' : '#D1D5DB';
        $out .= "<i class='fa-solid fa-star' style='color:{$warna};font-size:12px;'></i>";
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil &amp; Toko - eKantin</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten" style="max-width:740px;">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-user-tie"></i> Profil &amp; Toko</h1>
      <p>Kelola akun dan informasi tokomu</p>
    </div>
  </div>

  <?php if ($flashpesan): ?>
  <div class="flashpesan flash<?= $flashjenis ?>">
    <i class="fa-solid fa-<?= $flashjenis === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
  <?php endif; ?>

  <!-- Hero profil -->
  <div class="hero-profil">
    <div class="avatar"><?= $inisial ?></div>
    <div class="nama"><?= htmlspecialchars($user['username'] ?? '') ?></div>
    <div class="sub"><?= htmlspecialchars($user['email'] ?? '') ?></div>
    <span class="labelperan">
      <i class="fa-solid fa-store"></i> Penjual — <?= htmlspecialchars($toko['nama_toko'] ?? '') ?>
    </span>
    <div style="display:flex;gap:16px;justify-content:center;margin-top:14px;flex-wrap:wrap;">
      <div style="text-align:center;">
        <div style="font-size:20px;font-weight:800;"><?= $totalpesanan ?></div>
        <div style="font-size:11px;opacity:.8;">Pesanan</div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:20px;font-weight:800;"><?= $totalmenu ?></div>
        <div style="font-size:11px;opacity:.8;">Menu Aktif</div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:20px;font-weight:800;"><?= $ratarating ?: '—' ?></div>
        <div style="font-size:11px;opacity:.8;"><?= $jmlrating ?> Ulasan</div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:14px;font-weight:800;"><?= rp($totalpendapatan) ?></div>
        <div style="font-size:11px;opacity:.8;">Total Pendapatan</div>
      </div>
    </div>
  </div>

  <!-- Tab navigasi -->
  <div class="tab-profil">
    <a href="profil.php?tab=profil" class="tab-item <?= $tabaktif==='profil'?'aktif':'' ?>">
      <i class="fa-solid fa-user"></i> Profil Saya
    </a>
    <a href="profil.php?tab=edit" class="tab-item <?= $tabaktif==='edit'?'aktif':'' ?>">
      <i class="fa-solid fa-pen"></i> Edit Profil
    </a>
    <a href="profil.php?tab=password" class="tab-item <?= $tabaktif==='password'?'aktif':'' ?>">
      <i class="fa-solid fa-lock"></i> Ganti Password
    </a>
  </div>

  <!-- ===== TAB: PROFIL SAYA (view) ===== -->
  <?php if ($tabaktif === 'profil'): ?>
  <div class="kartu">
    <h3><i class="fa-solid fa-user-circle"></i> Informasi Akun</h3>
    <div class="baris-info">
      <div class="label-info">Username</div>
      <div class="nilai-info"><?= htmlspecialchars($user['username'] ?? '') ?></div>
    </div>
    <div class="baris-info">
      <div class="label-info">Email</div>
      <div class="nilai-info"><?= htmlspecialchars($user['email'] ?? '') ?></div>
    </div>
    <div class="baris-info">
      <div class="label-info">Peran</div>
      <div class="nilai-info">Penjual</div>
    </div>
    <div class="baris-info">
      <div class="label-info">Member Sejak</div>
      <div class="nilai-info"><?= !empty($user['created']) ? date('d M Y', strtotime($user['created'])) : '-' ?></div>
    </div>
  </div>

  <div class="kartu">
    <h3><i class="fa-solid fa-store"></i> Informasi Toko</h3>
    <div class="baris-info">
      <div class="label-info">Nama Toko</div>
      <div class="nilai-info"><?= htmlspecialchars($toko['nama_toko'] ?? '') ?></div>
    </div>
    <div class="baris-info">
      <div class="label-info">Status Toko</div>
      <div class="nilai-info">
        <span class="badge <?= ($toko['status_toko'] ?? 'tutup') === 'buka' ? 'siap' : 'dibatalkan' ?>">
          <?= ($toko['status_toko'] ?? 'tutup') === 'buka' ? 'Toko Buka' : 'Toko Tutup' ?>
        </span>
      </div>
    </div>
    <div class="baris-info">
      <div class="label-info">Rating Toko</div>
      <div class="nilai-info">
        <?php if ($ratarating > 0): ?>
        <?= bintang($ratarating) ?> <span style="font-weight:700;color:var(--tunggu);margin-left:4px;"><?= $ratarating ?></span>
        (<?= $jmlrating ?> ulasan)
        <?php else: ?>
        Belum ada rating
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="profil.php?tab=edit" class="tombolutama" style="flex:1;">
      <i class="fa-solid fa-pen"></i> Edit Profil &amp; Toko
    </a>
    <a href="profil.php?tab=password" class="tombolringan" style="flex:1;">
      <i class="fa-solid fa-lock"></i> Ganti Password
    </a>
  </div>
  <?php endif; ?>

  <!-- ===== TAB: EDIT PROFIL & TOKO ===== -->
  <?php if ($tabaktif === 'edit'): ?>
  <div class="kartu">
    <h3><i class="fa-solid fa-pen"></i> Edit Informasi Akun &amp; Toko</h3>
    <form method="POST" action="proseseditprofil.php">
      <div class="barisform">
        <div class="kelompokform">
          <label>Username <span style="color:var(--gagal);">*</span></label>
          <input type="text" name="username"
                 value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                 required minlength="6" maxlength="50"
                 placeholder="Minimal 6 karakter...">
          <small>6–50 karakter</small>
        </div>
        <div class="kelompokform">
          <label>Email <span style="color:var(--gagal);">*</span></label>
          <input type="email" name="email"
                 value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                 required placeholder="Email aktif...">
        </div>
      </div>
      <div class="kelompokform">
        <label>Nama Toko <span style="color:var(--gagal);">*</span></label>
        <input type="text" name="nama_toko"
               value="<?= htmlspecialchars($toko['nama_toko'] ?? '') ?>"
               required maxlength="100" placeholder="Nama tokomu...">
      </div>
      <div class="barisform">
        <div class="kelompokform">
          <label>Peran</label>
          <input type="text" value="Penjual" disabled>
        </div>
        <div class="kelompokform">
          <label>Member Sejak</label>
          <input type="text" value="<?= !empty($user['created']) ? date('d M Y', strtotime($user['created'])) : '-' ?>" disabled>
        </div>
      </div>
      <div style="display:flex;gap:10px;">
        <a href="profil.php" class="tombolringan">Batal</a>
        <button type="submit" class="tombolutama" style="flex:1;">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- ===== TAB: GANTI PASSWORD ===== -->
  <?php if ($tabaktif === 'password'): ?>
  <div class="kartu">
    <h3><i class="fa-solid fa-lock"></i> Ganti Password</h3>
    <form method="POST" action="prosesgantipassword.php">
      <div class="kelompokform">
        <label>Password Lama <span style="color:var(--gagal);">*</span></label>
        <input type="password" name="password_lama" required placeholder="Password saat ini...">
      </div>
      <div class="kelompokform">
        <label>Password Baru <span style="color:var(--gagal);">*</span></label>
        <input type="password" name="password_baru" required minlength="8" maxlength="100"
               placeholder="Minimal 8 karakter...">
        <small>8–100 karakter</small>
      </div>
      <div class="kelompokform">
        <label>Konfirmasi Password Baru <span style="color:var(--gagal);">*</span></label>
        <input type="password" name="konfirmasi" required placeholder="Ulangi password baru...">
      </div>
      <div style="display:flex;gap:10px;">
        <a href="profil.php" class="tombolringan">Batal</a>
        <button type="submit" class="tombolutama" style="flex:1;">
          <i class="fa-solid fa-shield-halved"></i> Simpan Password Baru
        </button>
      </div>
    </form>
  </div>
  <?php endif; ?>

</main>

</body>
</html>
