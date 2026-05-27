<?php
/* halaman daftar pengguna — admin only.
   filter: semua | penjual | pembeli | admin | terhapus
   terhapus = soft-deleted (deleted=1); kantin slot sudah dikosongkan.
   print: Cetak Semua (window.print) + per-user card (JS inject). */

include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

$halamansaatini = 'user';

$rolefilter = $_GET['role'] ?? 'semua';
$cari       = trim($_GET['cari'] ?? '');
if (!in_array($rolefilter, ['semua','admin','penjual','pembeli','terhapus'])) $rolefilter = 'semua';

// cek migrasi kolom nomor_kantin dan deleted_at
$cekkolom  = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'nomor_kantin'");
$migrasiSudah = ($cekkolom && $cekkolom->num_rows > 0);

$cekdel   = $conn->query("SHOW COLUMNS FROM tb_user LIKE 'deleted_at'");
$adaDelAt = ($cekdel && $cekdel->num_rows > 0);

// hitung jumlah per role (aktif) untuk badge tab
$qhitung = $conn->query("SELECT role, COUNT(*) AS jml FROM tb_user WHERE deleted=0 GROUP BY role");
$jmlrole = ['admin'=>0,'penjual'=>0,'pembeli'=>0];
while ($r = $qhitung->fetch_assoc()) $jmlrole[$r['role']] = (int)$r['jml'];
$jmlsemua = array_sum($jmlrole);

$qterhapus   = $conn->query("SELECT COUNT(*) AS jml FROM tb_user WHERE deleted=1");
$jmlTerhapus = (int)$qterhapus->fetch_assoc()['jml'];

$kolomNomor  = $migrasiSudah ? "t.nomor_kantin," : "NULL AS nomor_kantin,";
$delAtSelect = $adaDelAt ? "u.deleted_at," : "NULL AS deleted_at,";
$delAtOrder  = $adaDelAt ? "u.deleted_at DESC" : "u.id_user DESC";

// cek apakah tabel tb_riwayat_toko ada
$cektbr = $conn->query("SHOW TABLES LIKE 'tb_riwayat_toko'");
$adaRiwayat = ($cektbr && $cektbr->num_rows > 0);

// bangun query sesuai tab aktif
if ($rolefilter === 'terhapus') {
    if ($adaRiwayat) {
        $sql = "SELECT u.id_user, u.username, u.email, u.role, u.created, {$delAtSelect} u.foto,
                       (SELECT COUNT(*) FROM tb_order o WHERE o.id_user=u.id_user AND o.deleted=0) AS pesanan_user,
                       r.id_toko, r.nomor_kantin, r.nama_toko, r.foto_toko
                FROM tb_user u 
                LEFT JOIN (
                    SELECT r1.* FROM tb_riwayat_toko r1
                    INNER JOIN (
                        SELECT id_user, MAX(id_riwayat) as max_id
                        FROM tb_riwayat_toko
                        GROUP BY id_user
                    ) r2 ON r1.id_user = r2.id_user AND r1.id_riwayat = r2.max_id
                ) r ON u.id_user = r.id_user
                WHERE u.deleted=1";
        $params = []; $types = '';
        if ($cari !== '') {
            $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR r.nama_toko LIKE ?)";
            $likcari = "%$cari%";
            $params[] = $likcari; $params[] = $likcari; $params[] = $likcari; $types .= 'sss';
        }
    } else {
        $sql = "SELECT u.id_user, u.username, u.email, u.role, u.created, {$delAtSelect} u.foto,
                       (SELECT COUNT(*) FROM tb_order o WHERE o.id_user=u.id_user AND o.deleted=0) AS pesanan_user,
                       NULL AS id_toko, NULL AS nomor_kantin, NULL AS nama_toko, NULL AS foto_toko
                FROM tb_user u WHERE u.deleted=1";
        $params = []; $types = '';
        if ($cari !== '') {
            $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
            $likcari = "%$cari%";
            $params[] = $likcari; $params[] = $likcari; $types .= 'ss';
        }
    }
    $sql .= " ORDER BY {$delAtOrder}";
} else {
    $sql = "SELECT u.id_user, u.username, u.email, u.role, u.created, u.foto,
                   t.id_toko, {$kolomNomor} t.nama_toko, t.status_toko, t.foto_toko,
                   CASE u.role WHEN 'penjual' THEN 0 WHEN 'pembeli' THEN 1 ELSE 2 END AS urut_role,
                   (SELECT COUNT(*) FROM tb_order o  WHERE o.id_toko=t.id_toko  AND o.deleted=0) AS pesanan_toko,
                   (SELECT COUNT(*) FROM tb_order o2 WHERE o2.id_user=u.id_user AND o2.deleted=0) AS pesanan_user
            FROM tb_user u
            LEFT JOIN tb_toko t ON u.id_user=t.id_user AND t.deleted=0
            WHERE u.deleted=0";
    $params = []; $types = '';
    if ($rolefilter !== 'semua') { $sql .= " AND u.role=?"; $params[] = $rolefilter; $types .= 's'; }
    if ($cari !== '') {
        $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR t.nama_toko LIKE ?)";
        $likcari = "%$cari%";
        $params[] = $likcari; $params[] = $likcari; $params[] = $likcari; $types .= 'sss';
    }
    $sql .= " ORDER BY urut_role ASC, u.username ASC";
}

$st = $conn->prepare($sql);
if ($params) { $st->bind_param($types, ...$params); }
$st->execute();
$daftaruser = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

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
<title>Manajemen Pengguna - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
#kartu-cetak-user { display:none; }
.cetakjudul { display:none; }

@media print {
  .takprint { display:none !important; }
  .cetakjudul { display:block !important; margin-bottom:16px; font-family:sans-serif; }
  .cetakjudul h3 { margin:0 0 2px; font-size:16px; }
  .cetakjudul p  { margin:0; font-size:11px; color:#666; }
  @page { size:A4; margin:12mm; }
  .kartu { box-shadow:none !important; border:1px solid #ddd !important; }
  table  { font-size:11px; width:100%; }

  /* mode cetak per-user: sembunyikan konten utama, tampilkan kartu */
  body.mode-cetak-user .konten { display:none !important; }
  body.mode-cetak-user .cetakjudul { display:none !important; }
  body.mode-cetak-user #kartu-cetak-user { display:block !important; }
}
</style>
</head>
<body>

<div class="takprint"><?php include '../../3. komponen/navbaradmin.php'; ?></div>

<main class="konten">

  <!-- judul hanya muncul saat print semua (bukan cetak per-user) -->
  <div class="cetakjudul">
    <h3><i class="fa-solid fa-users"></i> Manajemen Pengguna — jajankita</h3>
    <p>Dicetak: <?= date('d M Y H:i') ?> &nbsp;|&nbsp; Filter: <?= ucfirst($rolefilter) ?><?= $cari ? " &nbsp;| Cari: {$cari}" : '' ?></p>
    <hr style="margin:8px 0;">
  </div>

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-users"></i> Manajemen Pengguna</h1>
      <p>Kelola semua akun pengguna platform jajankita</p>
    </div>
    <div class="takprint" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
      <a href="eksporuser.php?role=<?= htmlspecialchars($rolefilter) ?>&cari=<?= urlencode($cari) ?>" class="tombolringan">
        <i class="fa-solid fa-file-csv"></i> Ekspor CSV
      </a>
      <?php if ($rolefilter !== 'terhapus'): ?>
      <a href="tambahuser.php" class="tombolutama">
        <i class="fa-solid fa-user-plus"></i> Tambah Pengguna
      </a>
      <?php endif; ?>
      <button onclick="window.print()" class="tombolringan">
        <i class="fa-solid fa-print"></i> Cetak Semua
      </button>
    </div>
  </div>

  <?php if ($flashpesan): ?>
  <div class="flashpesan flash<?= $flashjenis ?>">
    <i class="fa-solid fa-<?= $flashjenis === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
  <?php endif; ?>

  <!-- stat kartu -->
  <div class="grid-stat takprint" style="grid-template-columns:repeat(5,1fr);margin-bottom:20px;">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-users"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $jmlsemua ?></div><div class="label">Total Aktif</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:var(--tunggubg);color:#B45309;"><i class="fa-solid fa-store"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $jmlrole['penjual'] ?></div><div class="label">Penjual</div><div class="sub">Pemilik kantin</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-bag-shopping"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $jmlrole['pembeli'] ?></div><div class="label">Pembeli</div><div class="sub">Pelanggan</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:var(--infobg);color:var(--info);"><i class="fa-solid fa-user-shield"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $jmlrole['admin'] ?></div><div class="label">Admin</div><div class="sub">Pengelola platform</div></div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:#fee2e2;color:#dc2626;"><i class="fa-solid fa-user-slash"></i></div>
      <div class="isi-stat"><div class="nilai"><?= $jmlTerhapus ?></div><div class="label">Terhapus</div><div class="sub">Akun nonaktif</div></div>
    </div>
  </div>

  <!-- tab filter -->
  <div class="filter-bar takprint">
    <a href="user.php" class="chip-filter <?= $rolefilter==='semua'     ? 'aktif':'' ?>">Semua (<?= $jmlsemua ?>)</a>
    <a href="user.php?role=penjual"  class="chip-filter <?= $rolefilter==='penjual'  ? 'aktif':'' ?>"><i class="fa-solid fa-store"></i> Penjual (<?= $jmlrole['penjual'] ?>)</a>
    <a href="user.php?role=pembeli"  class="chip-filter <?= $rolefilter==='pembeli'  ? 'aktif':'' ?>"><i class="fa-solid fa-bag-shopping"></i> Pembeli (<?= $jmlrole['pembeli'] ?>)</a>
    <a href="user.php?role=admin"    class="chip-filter <?= $rolefilter==='admin'    ? 'aktif':'' ?>"><i class="fa-solid fa-user-shield"></i> Admin (<?= $jmlrole['admin'] ?>)</a>
    <a href="user.php?role=terhapus" class="chip-filter <?= $rolefilter==='terhapus' ? 'aktif':'' ?>" style="<?= $rolefilter!=='terhapus' ? 'color:#dc2626;' : '' ?>">
      <i class="fa-solid fa-user-slash"></i> Terhapus (<?= $jmlTerhapus ?>)
    </a>
  </div>

  <!-- form pencarian -->
  <form method="GET" action="user.php" class="takprint" style="margin-bottom:16px;">
    <?php if ($rolefilter !== 'semua'): ?>
    <input type="hidden" name="role" value="<?= htmlspecialchars($rolefilter) ?>">
    <?php endif; ?>
    <div class="kotakcari">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" name="cari"
             placeholder="Cari username, email<?= $rolefilter !== 'terhapus' ? ', atau nama toko' : '' ?>..."
             value="<?= htmlspecialchars($cari) ?>">
      <button type="submit" class="tombolcari"><i class="fa-solid fa-arrow-right"></i></button>
    </div>
  </form>

  <?php if ($rolefilter === 'terhapus'): ?>
  <!-- ======================== TAB TERHAPUS ======================== -->

  <div class="peringatan peringataninfo takprint" style="margin-bottom:16px;">
    <i class="fa-solid fa-info-circle"></i>
    Akun terhapus tidak dapat login. Kantin yang ditempati penjual terhapus telah dikosongkan dan tersedia untuk penjual baru. Data pesanan lama tetap tersimpan.
    <?php if (!$adaDelAt): ?>
    <br><strong>Catatan:</strong> Kolom <code>deleted_at</code> belum ada — jalankan <code>migrasi_deletedat.sql</code> agar tanggal berhenti tercatat.
    <?php endif; ?>
  </div>

  <div class="kartu" style="padding:0;overflow:hidden;">
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th>Pengguna</th>
            <th class="tengah">Peran Terakhir</th>
            <th class="tengah">Pesanan Dibuat</th>
            <th>Bergabung</th>
            <th>Berhenti</th>
            <th class="tengah takprint">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($daftaruser)): ?>
          <tr>
            <td colspan="6">
              <div class="kosong">
                <div class="ikon-kosong"><i class="fa-solid fa-user-check"></i></div>
                <h3>Tidak ada akun terhapus</h3>
                <p>Semua akun masih aktif.</p>
              </div>
            </td>
          </tr>
          <?php else: foreach ($daftaruser as $u):
            $tglDaftar   = !empty($u['created'])    ? date('d M Y', strtotime($u['created']))            : '—';
            $tglBerhenti = !empty($u['deleted_at']) ? date('d M Y H:i', strtotime($u['deleted_at']))     : '—';
            $tglBprint   = !empty($u['deleted_at']) ? date('d M Y', strtotime($u['deleted_at']))         : '—';
            $ud = json_encode([
                'username'   => $u['username'],
                'email'      => $u['email'],
                'role'       => $u['role'],
                'pesanan'    => (int)$u['pesanan_user'],
                'created'    => $tglDaftar,
                'deleted_at' => $tglBprint,
            ], JSON_HEX_APOS | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
          ?>
          <tr>
            <td>
              <div class="user-baris">
                <?php
                $fotoFile = !empty($u['foto']) ? $u['foto'] : '';
                if ($fotoFile && file_exists(__DIR__ . '/../../2. aset/profil/' . $fotoFile)) {
                    $fotoHtml = '<img src="../../2. aset/profil/' . htmlspecialchars($fotoFile) . '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="Foto">';
                } else {
                    $fotoHtml = strtoupper(mb_substr($u['username'], 0, 2));
                }
                ?>
                <div class="avatar-tabel" style="background:#fee2e2;color:#dc2626;"><?= $fotoHtml ?></div>
                <div>
                  <div class="nama" style="text-decoration:line-through;color:var(--tekssamar);"><?= htmlspecialchars($u['username']) ?></div>
                  <small style="color:var(--tekssamar);"><?= htmlspecialchars($u['email']) ?></small>
                </div>
              </div>
            </td>
            <td class="tengah"><span class="badge <?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
            <td class="tengah" style="font-weight:700;"><?= (int)$u['pesanan_user'] ?></td>
            <td style="font-size:12px;white-space:nowrap;"><?= $tglDaftar ?></td>
            <td style="font-size:12px;white-space:nowrap;color:<?= !empty($u['deleted_at']) ? '#dc2626' : 'var(--tekssamar)' ?>;"><?= $tglBerhenti ?></td>
            <td class="tengah takprint">
              <div class="aksi-grup">
                <a href="viewuser.php?id=<?= $u['id_user'] ?>" class="tombolkecil"><i class="fa-solid fa-eye"></i> Detail</a>
                <button onclick='cetakUser(<?= $ud ?>)' class="tombolkecil"><i class="fa-solid fa-print"></i> Cetak</button>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php else: ?>
  <!-- ======================== TAB AKTIF ======================== -->

  <?php if (in_array($rolefilter, ['semua','penjual'])): ?>
  <div class="peringatan peringataninfo takprint" style="margin-bottom:16px;">
    <i class="fa-solid fa-lightbulb"></i>
    Klik badge <strong>Buka</strong> atau <strong>Tutup</strong> di kolom Status Toko untuk mengubah status toko langsung.
  </div>
  <?php endif; ?>

  <div class="kartu" style="padding:0;overflow:hidden;">
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th>Pengguna</th>
            <th class="tengah">Peran</th>
            <th>Info Kantin &amp; Toko</th>
            <th class="tengah takprint">Status Toko</th>
            <th class="tengah">Pesanan</th>
            <th>Bergabung</th>
            <th class="tengah takprint">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($daftaruser)): ?>
          <tr>
            <td colspan="7">
              <div class="kosong">
                <div class="ikon-kosong"><i class="fa-solid fa-users-slash"></i></div>
                <h3>Tidak ada pengguna</h3>
                <p>Coba ubah filter atau kata kunci pencarian.</p>
              </div>
            </td>
          </tr>
          <?php else:
          $labelseksi = [
              'penjual' => '<i class="fa-solid fa-store"></i>&nbsp; Penjual — Pemilik Kantin',
              'pembeli' => '<i class="fa-solid fa-bag-shopping"></i>&nbsp; Pembeli',
              'admin'   => '<i class="fa-solid fa-user-shield"></i>&nbsp; Admin Platform',
          ];
          $seksiaktif = '';
          foreach ($daftaruser as $u):
              if ($rolefilter === 'semua' && $u['role'] !== $seksiaktif):
                  $seksiaktif = $u['role']; ?>
          <tr>
            <td colspan="7" style="background:var(--latar);padding:9px 18px;border-bottom:1px solid var(--garis);">
              <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--utama);">
                <?= $labelseksi[$seksiaktif] ?? ucfirst($seksiaktif) ?>
              </span>
            </td>
          </tr>
          <?php endif;
            $pesanan   = $u['role']==='penjual' ? (int)$u['pesanan_toko'] : (int)$u['pesanan_user'];
            $tglDaftar = !empty($u['created']) ? date('d M Y', strtotime($u['created'])) : '—';
            $ud = json_encode([
                'username'    => $u['username'],
                'email'       => $u['email'],
                'role'        => $u['role'],
                'nomor_kantin'=> isset($u['nomor_kantin']) ? $u['nomor_kantin'] : null,
                'nama_toko'   => $u['nama_toko'] ?? null,
                'pesanan'     => $pesanan,
                'created'     => $tglDaftar,
                'deleted_at'  => null,
            ], JSON_HEX_APOS | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
          ?>
          <tr>
            <td>
              <div class="user-baris">
                <?php
                $fotoFile = '';
                if (!empty($u['foto_toko'])) {
                    $fotoFile = $u['foto_toko'];
                } elseif (!empty($u['foto'])) {
                    $fotoFile = $u['foto'];
                }
                if ($fotoFile && file_exists(__DIR__ . '/../../2. aset/profil/' . $fotoFile)) {
                    $fotoHtml = '<img src="../../2. aset/profil/' . htmlspecialchars($fotoFile) . '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="Foto">';
                } else {
                    $fotoHtml = strtoupper(mb_substr($u['username'], 0, 2));
                }
                ?>
                <div class="avatar-tabel"><?= $fotoHtml ?></div>
                <div>
                  <div class="nama"><?= htmlspecialchars($u['username']) ?></div>
                  <small style="color:var(--tekssamar);"><?= htmlspecialchars($u['email']) ?></small>
                </div>
              </div>
            </td>
            <td class="tengah"><span class="badge <?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
            <td>
              <?php if ($u['role'] === 'penjual' && $u['id_toko']): ?>
              <?php if ($u['nomor_kantin'] !== null): ?>
              <div style="font-size:11px;font-weight:700;color:var(--tekssamar);text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px;">
                Kantin ke-<?= (int)$u['nomor_kantin'] ?>
              </div>
              <?php endif; ?>
              <div style="font-size:13px;font-weight:700;color:var(--teks);margin-bottom:3px;">
                <?= htmlspecialchars($u['nama_toko'] ?? '—') ?>
              </div>
              <a href="viewuser.php?id=<?= $u['id_user'] ?>" class="takprint"
                 style="font-size:11px;color:var(--info);">Lihat detail kantin →</a>
              <?php else: ?>
              <span style="color:var(--tekssamar);font-size:12px;">—</span>
              <?php endif; ?>
            </td>
            <td class="tengah takprint">
              <?php if ($u['role'] === 'penjual' && $u['id_toko']): ?>
              <form method="POST" action="../manajementoko/prosestoggletoko.php" style="display:inline;">
                <input type="hidden" name="id_toko" value="<?= $u['id_toko'] ?>">
                <button type="submit" class="badge <?= $u['status_toko']==='buka'?'buka':'tutup' ?>"
                        style="border:none;cursor:pointer;font-family:inherit;" title="Klik untuk ubah status toko">
                  <?= $u['status_toko']==='buka'?'Buka':'Tutup' ?>
                  <i class="fa-solid fa-arrows-rotate" style="font-size:9px;"></i>
                </button>
              </form>
              <?php else: ?>
              <span style="color:var(--tekssamar);font-size:12px;">—</span>
              <?php endif; ?>
            </td>
            <td class="tengah" style="font-weight:700;"><?= $pesanan ?></td>
            <td style="font-size:12px;white-space:nowrap;"><?= $tglDaftar ?></td>
            <td class="tengah takprint">
              <div class="aksi-grup">
                <a href="viewuser.php?id=<?= $u['id_user'] ?>" class="tombolkecil"><i class="fa-solid fa-eye"></i> Detail</a>
                <a href="edituser.php?id=<?= $u['id_user'] ?>" class="tombolkecil"><i class="fa-solid fa-pen"></i> Edit</a>
                <a href="hapususer.php?id=<?= $u['id_user'] ?>" class="tombolkecil merah"><i class="fa-solid fa-trash"></i> Hapus</a>
                <button onclick='cetakUser(<?= $ud ?>)' class="tombolkecil"><i class="fa-solid fa-print"></i> Cetak</button>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</main>

<!-- kartu cetak per-pengguna (diisi via JS, muncul hanya saat print per-user) -->
<div id="kartu-cetak-user"></div>

<script>
function cetakUser(u) {
  var peran = u.role ? (u.role.charAt(0).toUpperCase() + u.role.slice(1)) : '—';
  var html  = '<div style="font-family:Arial,sans-serif;padding:24px;max-width:600px;margin:0 auto;">';
  html += '<h2 style="margin:0 0 4px;font-size:18px;">Detail Pengguna</h2>';
  html += '<p style="margin:0 0 14px;color:#888;font-size:11px;">Dicetak: ' + new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) + '</p>';
  html += '<hr style="margin-bottom:16px;border:none;border-top:2px solid #eee;">';
  html += '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
  var baris = function(label, nilai, warna) {
    return '<tr style="border-bottom:1px solid #f5f5f5;">'
      + '<td style="padding:8px 4px;font-weight:700;width:160px;color:' + (warna||'#555') + ';">' + label + '</td>'
      + '<td style="padding:8px 4px;color:' + (warna||'#222') + ';">' + (nilai||'—') + '</td>'
      + '</tr>';
  };
  html += baris('Username', u.username);
  html += baris('Email', u.email);
  html += baris('Peran', peran);
  if (u.nomor_kantin) html += baris('Kantin', 'Kantin ke-' + u.nomor_kantin);
  if (u.nama_toko)    html += baris('Nama Toko', u.nama_toko);
  html += baris('Total Pesanan', u.pesanan !== undefined ? String(u.pesanan) : '—');
  html += baris('Bergabung', u.created);
  if (u.deleted_at)   html += baris('Berhenti', u.deleted_at, '#dc2626');
  html += '</table></div>';

  document.getElementById('kartu-cetak-user').innerHTML = html;
  document.body.classList.add('mode-cetak-user');
  window.print();
  window.onafterprint = function() {
    document.body.classList.remove('mode-cetak-user');
    document.getElementById('kartu-cetak-user').innerHTML = '';
  };
}
</script>

</body>
</html>
