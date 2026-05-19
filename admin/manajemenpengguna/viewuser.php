<?php
/* ============================================================
   DETAIL PENGGUNA — ADMIN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';
$halamansaatini = 'user';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: user.php"); exit; }

$qu = $conn->prepare("SELECT * FROM tb_user WHERE id_user=? AND deleted=0");
$qu->bind_param("i", $id); $qu->execute();
$user = $qu->get_result()->fetch_assoc(); $qu->close();
if (!$user) { header("Location: user.php"); exit; }

$inisial = strtoupper(mb_substr($user['username'],0,2));

// Statistik berdasarkan role
$toko = null; $totalpesanan = 0; $totalomset = 0; $ratarating = 0; $jmlrating = 0; $totalmenu = 0;
$totalorder_pembeli = 0; $totalbelanja = 0;

if ($user['role'] === 'penjual') {
    $qt = $conn->prepare("SELECT * FROM tb_toko WHERE id_user=? AND deleted=0");
    $qt->bind_param("i", $id); $qt->execute();
    $toko = $qt->get_result()->fetch_assoc(); $qt->close();

    if ($toko) {
        $idtoko = (int)$toko['id_toko'];

        $qs1 = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_toko=? AND deleted=0");
        $qs1->bind_param("i", $idtoko); $qs1->execute();
        $r1 = $qs1->get_result()->fetch_row(); $qs1->close();
        $totalpesanan = (int)$r1[0]; $totalomset = (float)$r1[1];

        $qs2 = $conn->prepare("SELECT ROUND(AVG(rating_toko),1), COUNT(*) FROM tb_rating WHERE id_toko=? AND deleted=0");
        $qs2->bind_param("i", $idtoko); $qs2->execute();
        $r2 = $qs2->get_result()->fetch_row(); $qs2->close();
        $ratarating = (float)($r2[0] ?? 0); $jmlrating = (int)($r2[1] ?? 0);

        $qs3 = $conn->prepare("SELECT COUNT(*) FROM tb_menu WHERE id_toko=? AND status='aktif' AND deleted=0");
        $qs3->bind_param("i", $idtoko); $qs3->execute();
        $totalmenu = (int)$qs3->get_result()->fetch_row()[0]; $qs3->close();
    }
} elseif ($user['role'] === 'pembeli') {
    $qb = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total_harga),0) FROM tb_order WHERE id_user=? AND deleted=0");
    $qb->bind_param("i", $id); $qb->execute();
    $rb = $qb->get_result()->fetch_row(); $qb->close();
    $totalorder_pembeli = (int)$rb[0]; $totalbelanja = (float)$rb[1];
}

function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Pengguna - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-user-circle"></i> Detail Pengguna</h1>
      <p>Informasi lengkap akun <?= htmlspecialchars($user['username']) ?></p>
    </div>
    <div style="display:flex;gap:8px;">
      <a href="edituser.php?id=<?= $id ?>" class="tombolutama">
        <i class="fa-solid fa-pen"></i> Edit
      </a>
      <a href="hapususer.php?id=<?= $id ?>" class="tombolbahaya">
        <i class="fa-solid fa-trash"></i> Hapus
      </a>
      <a href="user.php" class="tombolringan">
        <i class="fa-solid fa-arrow-left"></i> Kembali
      </a>
    </div>
  </div>

  <!-- Hero -->
  <div class="kartu" style="text-align:center;padding:28px;margin-bottom:18px;">
    <div style="width:64px;height:64px;border-radius:50%;background:var(--latar);color:var(--utama);
                display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;
                margin:0 auto 12px;"><?= $inisial ?></div>
    <div style="font-size:18px;font-weight:800;color:var(--utama);">
      <?= htmlspecialchars($user['username']) ?>
    </div>
    <div style="font-size:13px;color:var(--tekssamar);margin:4px 0 10px;">
      <?= htmlspecialchars($user['email']) ?>
    </div>
    <span class="badge <?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
  </div>

  <!-- Statistik -->
  <?php if ($user['role'] === 'penjual' && $toko): ?>
  <div class="grid-stat" style="margin-bottom:18px;">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-receipt"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $totalpesanan ?></div><div class="label">Total Pesanan</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-coins"></i></div>
      <div class="isi-stat"><div class="nilai" style="font-size:15px;"><?= rp($totalomset) ?></div><div class="label">Total Omset</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-star"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $ratarating ?: '—' ?></div><div class="label">Rating Toko</div><div class="sub"><?= $jmlrating ?> ulasan</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-utensils"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $totalmenu ?></div><div class="label">Menu Aktif</div></div>
    </div>
  </div>
  <?php elseif ($user['role'] === 'pembeli'): ?>
  <div class="grid-stat" style="margin-bottom:18px;">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-bag-shopping"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $totalorder_pembeli ?></div><div class="label">Total Pesanan</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-coins"></i></div>
      <div class="isi-stat"><div class="nilai" style="font-size:15px;"><?= rp($totalbelanja) ?></div><div class="label">Total Belanja</div></div>
    </div>
  </div>
  <?php endif; ?>

  <div class="grid-dua">

    <!-- Info Akun -->
    <div class="kartu">
      <h3><i class="fa-solid fa-user"></i> Informasi Akun</h3>
      <div class="baris-info">
        <div class="label-info">Username</div>
        <div class="nilai-info"><?= htmlspecialchars($user['username']) ?></div>
      </div>
      <div class="baris-info">
        <div class="label-info">Email</div>
        <div class="nilai-info"><?= htmlspecialchars($user['email']) ?></div>
      </div>
      <div class="baris-info">
        <div class="label-info">Peran</div>
        <div class="nilai-info"><span class="badge <?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></div>
      </div>
      <div class="baris-info">
        <div class="label-info">Bergabung</div>
        <div class="nilai-info"><?= !empty($user['created']) ? date('d M Y, H:i', strtotime($user['created'])) : '-' ?></div>
      </div>
    </div>

    <!-- Info Toko (jika penjual) -->
    <?php if ($user['role'] === 'penjual'): ?>
    <div class="kartu">
      <h3><i class="fa-solid fa-store"></i> Informasi Toko</h3>
      <?php if ($toko): ?>
      <div class="baris-info">
        <div class="label-info">Nama Toko</div>
        <div class="nilai-info"><?= htmlspecialchars($toko['nama_toko']) ?></div>
      </div>
      <div class="baris-info">
        <div class="label-info">Status</div>
        <div class="nilai-info">
          <span class="badge <?= $toko['status_toko'] === 'buka' ? 'buka' : 'tutup' ?>">
            <?= $toko['status_toko'] === 'buka' ? 'Buka' : 'Tutup' ?>
          </span>
        </div>
      </div>
      <div style="margin-top:14px;">
        <a href="../manajementoko/viewtoko.php?id=<?= $toko['id_toko'] ?>" class="tombolringan">
          <i class="fa-solid fa-arrow-right"></i> Lihat Detail Toko
        </a>
      </div>
      <?php else: ?>
      <div class="kosong" style="padding:20px;">
        <p>Toko belum dibuat atau sudah dihapus.</p>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>


</main>
</body>
</html>
