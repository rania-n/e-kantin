<?php
/* ============================================================
   profil dan toko penjual
   tab: profil saya (view) | edit profil dan toko | ganti password
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idpengguna = (int)$_SESSION['id_user'];
$idtoko     = (int)$_SESSION['id_toko'];
$halamansaatini = 'profil';

$qu = $conn->prepare("SELECT * FROM tb_user WHERE id_user=? AND deleted=0");
$qu->bind_param("i", $idpengguna); $qu->execute();
$user = $qu->get_result()->fetch_assoc(); $qu->close();

$qt = $conn->prepare("SELECT * FROM tb_toko WHERE id_toko=? AND deleted=0");
$qt->bind_param("i", $idtoko); $qt->execute();
$toko = $qt->get_result()->fetch_assoc(); $qt->close();

// statistik toko
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

// inisial dari nama toko (bukan username)
$namatoko  = $toko['nama_toko'] ?? $user['username'] ?? 'T';
$inisial   = strtoupper(mb_substr($namatoko, 0, 2));
$fotoprofil = $toko['foto_toko'] ?? '';

$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']);
}

$tabaktif = $_GET['tab'] ?? 'profil';
if (!in_array($tabaktif, ['profil', 'edit', 'password'])) $tabaktif = 'profil';

function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
function singkat(float $n): string {
    if ($n >= 1_000_000_000) { $v=$n/1_000_000_000; return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' M'; }
    if ($n >= 1_000_000)     { $v=$n/1_000_000;     return 'Rp '.rtrim(rtrim(number_format($v,1,',',''),'0'),',').' Jt'; }
    return 'Rp ' . number_format($n, 0, ',', '.');
}
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
<title>Profil &amp; Toko - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten">

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

  <!-- hero profil — avatar kotak dari foto toko atau inisial nama toko -->
  <div class="hero-profil">
    <div class="avatar">
      <?php if ($fotoprofil && file_exists("../../2. aset/profil/" . $fotoprofil)): ?>
        <img src="../../2. aset/profil/<?= htmlspecialchars($fotoprofil) ?>" alt="foto toko">
      <?php else: ?>
        <?= $inisial ?>
      <?php endif; ?>
    </div>
    <div class="nama"><?= htmlspecialchars($namatoko) ?></div>
    <div class="sub"><?= htmlspecialchars($user['email'] ?? '') ?></div>
    <span class="labelperan">
      <i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($user['username'] ?? '') ?>
    </span>
    <div style="display:flex;gap:20px;justify-content:center;margin-top:16px;flex-wrap:wrap;">
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
        <div style="font-size:15px;font-weight:800;"><?= singkat($totalpendapatan) ?></div>
        <div style="font-size:11px;opacity:.8;">Pendapatan</div>
      </div>
    </div>
  </div>

  <!-- tab view profil -->
  <?php if ($tabaktif === 'profil'): ?>

  <a href="profil.php?tab=edit" class="itempengaturan">
    <div class="ikonpengaturan">
      <i class="fa-solid fa-pen"></i>
    </div>
    <div class="tekspengaturan">
      <div class="judul">Edit Profil &amp; Toko</div>
      <div class="deskripsi">Ubah nama toko, username, email, dan foto toko</div>
    </div>
    <i class="fa-solid fa-chevron-right panahpengaturan"></i>
  </a>

  <a href="profil.php?tab=password" class="itempengaturan">
    <div class="ikonpengaturan biru">
      <i class="fa-solid fa-lock"></i>
    </div>
    <div class="tekspengaturan">
      <div class="judul">Ganti Password</div>
      <div class="deskripsi">Perbarui kata sandi akunmu</div>
    </div>
    <i class="fa-solid fa-chevron-right panahpengaturan"></i>
  </a>

  <a href="../laporan/laporan.php" class="itempengaturan">
    <div class="ikonpengaturan hijau">
      <i class="fa-solid fa-chart-bar"></i>
    </div>
    <div class="tekspengaturan">
      <div class="judul">Laporan Penjualan</div>
      <div class="deskripsi">Lihat rekap pendapatan dan statistik toko</div>
    </div>
    <i class="fa-solid fa-chevron-right panahpengaturan"></i>
  </a>

  <a href="../../4. autentifikasi/konfirmasilogout.php?peran=penjual" class="itempengaturan">
    <div class="ikonpengaturan merah">
      <i class="fa-solid fa-right-from-bracket"></i>
    </div>
    <div class="tekspengaturan">
      <div class="judul" style="color:var(--gagal);">Keluar</div>
      <div class="deskripsi">Logout dari akun penjual</div>
    </div>
    <i class="fa-solid fa-chevron-right panahpengaturan"></i>
  </a>

  <?php endif; ?>

  <!-- tab edit profil dan toko -->
  <?php if ($tabaktif === 'edit'): ?>
  <div class="kartu">
    <h3><i class="fa-solid fa-pen"></i> Edit Profil &amp; Toko</h3>
    <form method="POST" action="proseseditprofil.php" enctype="multipart/form-data">
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
      <div class="kelompokform">
        <label>Foto Toko</label>
        <?php if ($fotoprofil && file_exists("../../2. aset/profil/" . $fotoprofil)): ?>
        <div style="margin-bottom:8px;">
          <img src="../../2. aset/profil/<?= htmlspecialchars($fotoprofil) ?>"
               alt="foto toko saat ini"
               style="width:80px;height:80px;object-fit:cover;border-radius:14px;border:2px solid var(--garis);">
        </div>
        <?php endif; ?>
        <input type="file" name="foto_toko" accept="image/jpeg,image/png,image/webp"
               style="padding:8px;border:1.5px solid var(--garis);border-radius:8px;width:100%;font-size:13px;">
        <small>jpg, png, atau webp — maks 2mb. Kosongkan jika tidak ingin mengubah.</small>
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

  <!-- tab ganti password -->
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
