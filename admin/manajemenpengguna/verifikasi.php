<?php
/* halaman verifikasi akun pembeli — admin only.
   menampilkan daftar pembeli dengan status_verifikasi='pending' sehingga admin
   bisa meninjau identitas (nama lengkap + kelas) dan menekan Terima/Tolak.

   tab juga menyediakan riwayat (verified & ditolak) supaya admin bisa membatalkan
   keputusan kalau perlu (mengembalikan ke pending). */

include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tandai menu aktif di navbar admin (sama dengan grup manajemen pengguna)
$halamansaatini = 'verifikasi';

// baca filter status dari url. default = 'pending' (yang butuh perhatian admin).
// catatan: akun ditolak punya deleted=1 (soft-delete), tapi tetap tampil di tab
// "Ditolak" halaman ini sebagai riwayat — tidak ikut ke tab "Terhapus" Manajemen Pengguna.
$filter = $_GET['filter'] ?? 'pending';
if (!in_array($filter, ['pending','verified','ditolak'])) $filter = 'pending';

// hitung jumlah per status untuk badge angka di tab.
// pending & verified pakai deleted=0, ditolak pakai deleted=1 — gabungkan dengan OR.
$jml = ['pending'=>0, 'verified'=>0, 'ditolak'=>0];
$qh = $conn->query("SELECT status_verifikasi, COUNT(*) AS jml FROM tb_user
                     WHERE role='pembeli'
                       AND (
                            (deleted=0 AND status_verifikasi IN ('pending','verified'))
                         OR (deleted=1 AND status_verifikasi='ditolak')
                       )
                     GROUP BY status_verifikasi");
if ($qh) {
    while ($r = $qh->fetch_assoc()) {
        if (isset($jml[$r['status_verifikasi']])) $jml[$r['status_verifikasi']] = (int)$r['jml'];
    }
}

// ambil daftar pembeli sesuai filter.
// untuk tab ditolak: cari yang deleted=1, untuk yang lain: deleted=0.
$kondisiHapus = ($filter === 'ditolak') ? 'deleted=1' : 'deleted=0';
$st = $conn->prepare("SELECT id_user, username, nama_lengkap, kelas, email, created, status_verifikasi
                       FROM tb_user
                       WHERE role='pembeli' AND {$kondisiHapus} AND status_verifikasi=?
                       ORDER BY created DESC");
$st->bind_param("s", $filter);
$st->execute();
$daftar = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// flash message: pesan satu kali dari proses verifikasi/penolakan sebelumnya
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
<title>Verifikasi Pembeli - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-user-check"></i> Verifikasi Pembeli</h1>
      <p>Tinjau identitas pembeli baru sebelum mereka bisa memesan</p>
    </div>
  </div>

  <?php if ($flashpesan): ?>
  <div class="flashpesan flash<?= $flashjenis ?>">
    <i class="fa-solid fa-<?= $flashjenis === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
  <?php endif; ?>

  <!-- info ringkas di atas tab -->
  <div class="peringatan peringataninfo" style="margin-bottom:16px;">
    <i class="fa-solid fa-info-circle"></i>
    Cocokkan <strong>Nama Lengkap</strong> dan <strong>Kelas</strong> di bawah dengan data siswa sebelum menyetujui.
    Pembeli dengan status <em>Menunggu</em> belum bisa login.
  </div>

  <!-- tab filter status. akun ditolak tampil di tab "Ditolak" (read-only — sudah
       di-soft-delete, tidak bisa dikembalikan ke Menunggu). -->
  <div class="filter-bar" style="margin-bottom:20px;">
    <a href="verifikasi.php?filter=pending"
       class="chip-filter <?= $filter==='pending'  ? 'aktif':'' ?>">
      <i class="fa-solid fa-hourglass-half"></i> Menunggu (<?= $jml['pending'] ?>)
    </a>
    <a href="verifikasi.php?filter=verified"
       class="chip-filter <?= $filter==='verified' ? 'aktif':'' ?>">
      <i class="fa-solid fa-circle-check"></i> Diterima (<?= $jml['verified'] ?>)
    </a>
    <a href="verifikasi.php?filter=ditolak"
       class="chip-filter <?= $filter==='ditolak' ? 'aktif':'' ?>"
       style="<?= $filter!=='ditolak' ? 'color:#dc2626;' : '' ?>">
      <i class="fa-solid fa-circle-xmark"></i> Ditolak (<?= $jml['ditolak'] ?>)
    </a>
  </div>

  <div class="kartu" style="padding:0;overflow:hidden;">
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th>Username</th>
            <th>Nama Lengkap</th>
            <th>Kelas</th>
            <th>Email</th>
            <th>Daftar</th>
            <th class="tengah">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($daftar)): ?>
          <tr>
            <td colspan="6">
              <div class="kosong">
                <div class="ikon-kosong">
                  <i class="fa-solid fa-<?= $filter==='pending' ? 'inbox' : ($filter==='verified' ? 'circle-check' : 'circle-xmark') ?>"></i>
                </div>
                <h3>
                  <?php if ($filter==='pending'): ?>Tidak ada akun menunggu
                  <?php elseif ($filter==='verified'): ?>Belum ada akun yang diverifikasi
                  <?php else: ?>Belum ada akun yang ditolak
                  <?php endif; ?>
                </h3>
                <p>
                  <?php if ($filter==='pending'): ?>Semua pendaftar sudah diproses.
                  <?php elseif ($filter==='verified'): ?>Akun yang sudah kamu setujui akan tampil di sini.
                  <?php else: ?>Akun yang kamu tolak akan tersimpan di sini sebagai riwayat.
                  <?php endif; ?>
                </p>
              </div>
            </td>
          </tr>
          <?php else: foreach ($daftar as $u): ?>
          <tr>
            <td>
              <div class="user-baris">
                <div class="avatar-tabel"><?= strtoupper(mb_substr($u['username'], 0, 2)) ?></div>
                <div>
                  <div class="nama"><?= htmlspecialchars($u['username']) ?></div>
                </div>
              </div>
            </td>
            <td><strong><?= htmlspecialchars($u['nama_lengkap'] ?: '—') ?></strong></td>
            <td><?= htmlspecialchars($u['kelas'] ?: '—') ?></td>
            <td style="font-size:12px;color:var(--tekssamar);"><?= htmlspecialchars($u['email']) ?></td>
            <td style="font-size:12px;white-space:nowrap;"><?= date('d M Y H:i', strtotime($u['created'])) ?></td>
            <td class="tengah">
              <div class="aksi-grup">
                <?php if ($filter==='pending'): ?>
                <!-- tombol Terima — POST agar tidak bisa di-GET sembarangan oleh URL crawler -->
                <form method="POST" action="prosesverifikasi.php" style="display:inline;"
                      onsubmit="return confirm('Setujui akun <?= htmlspecialchars($u['username'], ENT_QUOTES) ?> sebagai pembeli terverifikasi?');">
                  <input type="hidden" name="id_user" value="<?= (int)$u['id_user'] ?>">
                  <input type="hidden" name="aksi" value="terima">
                  <button type="submit" class="tombolkecil" style="background:var(--sukses);color:white;border-color:var(--sukses);">
                    <i class="fa-solid fa-check"></i> Terima
                  </button>
                </form>
                <!-- tombol Tolak — langsung soft-delete, tidak bisa dikembalikan dari sini -->
                <form method="POST" action="prosesverifikasi.php" style="display:inline;"
                      onsubmit="return confirm('Tolak dan HAPUS akun <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>? Akun akan langsung dihapus dan tidak bisa dikembalikan.');">
                  <input type="hidden" name="id_user" value="<?= (int)$u['id_user'] ?>">
                  <input type="hidden" name="aksi" value="tolak">
                  <button type="submit" class="tombolkecil merah">
                    <i class="fa-solid fa-xmark"></i> Tolak &amp; Hapus
                  </button>
                </form>
                <?php elseif ($filter==='verified'): ?>
                <!-- untuk tab verified: izinkan admin mengembalikan ke pending kalau salah klik Terima -->
                <form method="POST" action="prosesverifikasi.php" style="display:inline;"
                      onsubmit="return confirm('Kembalikan akun <?= htmlspecialchars($u['username'], ENT_QUOTES) ?> ke status Menunggu?');">
                  <input type="hidden" name="id_user" value="<?= (int)$u['id_user'] ?>">
                  <input type="hidden" name="aksi" value="reset">
                  <button type="submit" class="tombolkecil">
                    <i class="fa-solid fa-rotate-left"></i> Kembalikan ke Menunggu
                  </button>
                </form>
                <?php else: ?>
                <!-- tab ditolak: read-only — akun sudah dihapus, tidak bisa dikembalikan -->
                <span style="color:var(--tekssamar);font-size:12px;font-style:italic;">
                  <i class="fa-solid fa-lock"></i> Sudah dihapus
                </span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

</body>
</html>
