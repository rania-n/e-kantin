<?php
/* halaman daftar semua pengguna platform — hanya admin yang bisa akses.
   menampilkan tabel pengguna dengan fitur filter role (penjual/pembeli/admin)
   dan pencarian berdasarkan username, email, atau nama toko.
   penjual memiliki kolom tambahan: info toko dan tombol toggle status toko. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tandai menu "user" sebagai aktif di navbar
$halamansaatini = 'user';

// baca filter role dari url, default semua; validasi agar hanya nilai yang diizinkan diterima
$rolefilter = $_GET['role'] ?? 'semua';
$cari       = trim($_GET['cari'] ?? '');
if (!in_array($rolefilter, ['semua','admin','penjual','pembeli'])) $rolefilter = 'semua';

// hitung jumlah pengguna per role untuk ditampilkan di badge tab filter
$qhitung = $conn->query("SELECT role, COUNT(*) AS jml FROM tb_user WHERE deleted=0 GROUP BY role");
$jmlrole = ['admin'=>0,'penjual'=>0,'pembeli'=>0];
while ($r = $qhitung->fetch_assoc()) $jmlrole[$r['role']] = (int)$r['jml'];
$jmlsemua = array_sum($jmlrole); // total semua pengguna dari semua role

/* bangun query utama secara dinamis berdasarkan filter yang aktif.
   menggunakan subquery untuk menghitung pesanan toko dan pesanan user
   agar tidak terjadi penggandaan baris akibat join berganda.
   urut_role: penjual di atas, pembeli di tengah, admin di bawah. */
/* query utama: ambil semua pengguna beserta info kantin yang ditempati (jika penjual).
   kolom nomor_kantin disertakan untuk ditampilkan di kolom "Info Toko".
   left join ke tb_toko agar pengguna tanpa toko (pembeli/admin) tetap muncul.
   kantin sekarang bisa punya id_user=NULL (kosong), tapi join di sini ke tb_user jadi aman. */
$sql = "SELECT u.id_user, u.username, u.email, u.role, u.created,
               t.id_toko, t.nomor_kantin, t.nama_toko, t.status_toko,
               CASE u.role WHEN 'penjual' THEN 0 WHEN 'pembeli' THEN 1 ELSE 2 END AS urut_role,
               (SELECT COUNT(*) FROM tb_order o  WHERE o.id_toko=t.id_toko  AND o.deleted=0) AS pesanan_toko,
               (SELECT COUNT(*) FROM tb_order o2 WHERE o2.id_user=u.id_user AND o2.deleted=0) AS pesanan_user
        FROM tb_user u
        LEFT JOIN tb_toko t ON u.id_user=t.id_user AND t.deleted=0
        WHERE u.deleted=0";

// array untuk menampung parameter dan tipe data yang akan di-bind ke prepared statement
$params = []; $types = '';

// tambahkan kondisi filter role jika bukan "semua"
if ($rolefilter !== 'semua') { $sql .= " AND u.role=?"; $params[] = $rolefilter; $types .= 's'; }

// tambahkan kondisi pencarian jika ada kata kunci
if ($cari !== '') {
    $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR t.nama_toko LIKE ?)";
    $likcari = "%$cari%"; // karakter % artinya cocok dengan apa saja di posisi itu
    $params[] = $likcari; $params[] = $likcari; $params[] = $likcari; $types .= 'sss';
}
$sql .= " ORDER BY urut_role ASC, u.username ASC";

// jalankan query dengan prepared statement agar aman dari sql injection
$st = $conn->prepare($sql);
if ($params) { $st->bind_param($types, ...$params); } // spread operator: unpack array jadi argumen
$st->execute();
$daftaruser = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// ambil flash message dari session (pesan hasil operasi sebelumnya), lalu hapus agar tidak muncul lagi
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
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-users"></i> Manajemen Pengguna</h1>
      <p>Kelola semua akun pengguna platform jajankita</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <!-- tombol ekspor csv: meneruskan filter yang sedang aktif ke halaman ekspor -->
      <a href="eksporuser.php?role=<?= htmlspecialchars($rolefilter) ?>&cari=<?= urlencode($cari) ?>" class="tombolringan">
        <i class="fa-solid fa-file-csv"></i> Ekspor CSV
      </a>
      <a href="tambahuser.php" class="tombolutama">
        <i class="fa-solid fa-user-plus"></i> Tambah Pengguna
      </a>
    </div>
  </div>

  <?php if ($flashpesan): ?>
  <div class="flashpesan flash<?= $flashjenis ?>">
    <i class="fa-solid fa-<?= $flashjenis === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
  <?php endif; ?>

  <!-- ringkasan jumlah pengguna per role dalam bentuk kartu statistik -->
  <div class="grid-stat" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-users"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $jmlsemua ?></div>
        <div class="label">Total Pengguna</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:var(--tunggubg);color:#B45309;">
        <i class="fa-solid fa-store"></i>
      </div>
      <div class="isi-stat">
        <div class="nilai"><?= $jmlrole['penjual'] ?></div>
        <div class="label">Penjual</div>
        <div class="sub">Pemilik toko</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-bag-shopping"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $jmlrole['pembeli'] ?></div>
        <div class="label">Pembeli</div>
        <div class="sub">Pelanggan</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:var(--infobg);color:var(--info);">
        <i class="fa-solid fa-user-shield"></i>
      </div>
      <div class="isi-stat">
        <div class="nilai"><?= $jmlrole['admin'] ?></div>
        <div class="label">Admin</div>
        <div class="sub">Pengelola platform</div>
      </div>
    </div>
  </div>

  <!-- tab filter berdasarkan role, menampilkan jumlah di setiap tab -->
  <div class="filter-bar">
    <a href="user.php" class="chip-filter <?= $rolefilter === 'semua' ? 'aktif' : '' ?>">
      Semua (<?= $jmlsemua ?>)
    </a>
    <a href="user.php?role=penjual" class="chip-filter <?= $rolefilter === 'penjual' ? 'aktif' : '' ?>">
      <i class="fa-solid fa-store"></i> Penjual (<?= $jmlrole['penjual'] ?>)
    </a>
    <a href="user.php?role=pembeli" class="chip-filter <?= $rolefilter === 'pembeli' ? 'aktif' : '' ?>">
      <i class="fa-solid fa-bag-shopping"></i> Pembeli (<?= $jmlrole['pembeli'] ?>)
    </a>
    <a href="user.php?role=admin" class="chip-filter <?= $rolefilter === 'admin' ? 'aktif' : '' ?>">
      <i class="fa-solid fa-user-shield"></i> Admin (<?= $jmlrole['admin'] ?>)
    </a>
  </div>

  <!-- form pencarian: input teks dikiri, tombol kirim di kanan -->
  <form method="GET" action="user.php" style="margin-bottom:16px;">
    <?php if ($rolefilter !== 'semua'): ?>
    <!-- simpan filter role yang sedang aktif agar tidak hilang saat submit pencarian -->
    <input type="hidden" name="role" value="<?= htmlspecialchars($rolefilter) ?>">
    <?php endif; ?>
    <div class="kotakcari">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" name="cari" placeholder="Cari username, email, atau nama toko..."
             value="<?= htmlspecialchars($cari) ?>">
      <button type="submit" class="tombolcari"><i class="fa-solid fa-arrow-right"></i></button>
    </div>
  </form>

  <!-- tips toggle status toko hanya ditampilkan jika filter menampilkan penjual -->
  <?php if (in_array($rolefilter, ['semua','penjual'])): ?>
  <div class="peringatan peringataninfo" style="margin-bottom:16px;">
    <i class="fa-solid fa-lightbulb"></i>
    Klik badge <strong>Buka</strong> atau <strong>Tutup</strong> di kolom Status Toko untuk mengubah status toko langsung tanpa perlu masuk ke halaman toko.
  </div>
  <?php endif; ?>

  <!-- tabel daftar pengguna -->
  <div class="kartu" style="padding:0;overflow:hidden;">
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th>Pengguna</th>
            <th class="tengah">Peran</th>
            <th>Info Toko</th>
            <th class="tengah">Status Toko</th>
            <th class="tengah">Pesanan</th>
            <th>Bergabung</th>
            <th>Aksi</th>
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
          <?php else: ?>
          <?php
          // label pemisah seksi per role (hanya ditampilkan di mode "semua")
          $labelseksi = [
              'penjual' => '<i class="fa-solid fa-store"></i>&nbsp; Penjual — Pemilik Toko',
              'pembeli'  => '<i class="fa-solid fa-bag-shopping"></i>&nbsp; Pembeli',
              'admin'    => '<i class="fa-solid fa-user-shield"></i>&nbsp; Admin Platform',
          ];
          $seksiaktif = ''; // melacak role terakhir untuk tahu kapan harus menampilkan baris pemisah
          foreach ($daftaruser as $u):
              // tampilkan baris pemisah seksi setiap kali role berubah (di mode semua)
              if ($rolefilter === 'semua' && $u['role'] !== $seksiaktif):
                  $seksiaktif = $u['role'];
          ?>
          <tr>
            <td colspan="7" style="background:var(--latar);padding:9px 18px;border-bottom:1px solid var(--garis);">
              <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--utama);">
                <?= $labelseksi[$seksiaktif] ?? ucfirst($seksiaktif) ?>
              </span>
            </td>
          </tr>
          <?php endif; ?>
          <tr>
            <td>
              <div class="user-baris">
                <!-- avatar berupa 2 huruf pertama username, huruf kapital -->
                <div class="avatar-tabel"><?= strtoupper(mb_substr($u['username'],0,2)) ?></div>
                <div>
                  <div class="nama"><?= htmlspecialchars($u['username']) ?></div>
                  <small style="color:var(--tekssamar);"><?= htmlspecialchars($u['email']) ?></small>
                </div>
              </div>
            </td>
            <td class="tengah">
              <span class="badge <?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span>
            </td>
            <td>
              <!-- info kantin hanya ditampilkan untuk penjual yang punya kantin -->
              <?php if ($u['role'] === 'penjual' && $u['id_toko']): ?>
              <!-- tampilkan nomor kantin sebagai identitas utama -->
              <div style="font-size:11px;font-weight:700;color:var(--tekssamar);text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px;">
                Kantin ke-<?= (int)$u['nomor_kantin'] ?>
              </div>
              <div style="font-size:13px;font-weight:700;color:var(--teks);margin-bottom:3px;">
                <?= htmlspecialchars($u['nama_toko'] ?? '—') ?>
              </div>
              <a href="../manajementoko/viewtoko.php?id=<?= $u['id_toko'] ?>"
                 style="font-size:11px;color:var(--info);">
                Lihat detail kantin →
              </a>
              <?php else: ?>
              <span style="color:var(--tekssamar);font-size:12px;">—</span>
              <?php endif; ?>
            </td>
            <td class="tengah">
              <?php if ($u['role'] === 'penjual' && $u['id_toko']): ?>
              <!-- tombol toggle status toko: klik langsung ubah buka/tutup tanpa halaman baru -->
              <form method="POST" action="../manajementoko/prosestoggletoko.php" style="display:inline;">
                <input type="hidden" name="id_toko" value="<?= $u['id_toko'] ?>">
                <button type="submit"
                        class="badge <?= $u['status_toko']==='buka' ? 'buka' : 'tutup' ?>"
                        style="border:none;cursor:pointer;font-family:inherit;"
                        title="Klik untuk ubah status toko">
                  <?= $u['status_toko']==='buka' ? 'Buka' : 'Tutup' ?>
                  <i class="fa-solid fa-arrows-rotate" style="font-size:9px;"></i>
                </button>
              </form>
              <?php else: ?>
              <span style="color:var(--tekssamar);font-size:12px;">—</span>
              <?php endif; ?>
            </td>
            <td class="tengah" style="font-weight:700;">
              <!-- tampilkan jumlah pesanan sesuai role: penjual pakai pesanan_toko, pembeli pakai pesanan_user -->
              <?php if ($u['role'] === 'penjual'): ?>
                <?= (int)$u['pesanan_toko'] ?>
              <?php elseif ($u['role'] === 'pembeli'): ?>
                <?= (int)$u['pesanan_user'] ?>
              <?php else: ?>
                <span style="color:var(--tekssamar);">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;white-space:nowrap;">
              <?= !empty($u['created']) ? date('d M Y', strtotime($u['created'])) : '-' ?>
            </td>
            <td>
              <div class="aksi-grup">
                <a href="viewuser.php?id=<?= $u['id_user'] ?>" class="tombolkecil">
                  <i class="fa-solid fa-eye"></i> Detail
                </a>
                <a href="edituser.php?id=<?= $u['id_user'] ?>" class="tombolkecil">
                  <i class="fa-solid fa-pen"></i> Edit
                </a>
                <a href="hapususer.php?id=<?= $u['id_user'] ?>" class="tombolkecil merah">
                  <i class="fa-solid fa-trash"></i> Hapus
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>
</body>
</html>
