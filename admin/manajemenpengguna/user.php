<?php
/* halaman daftar pengguna — admin only.
   filter: semua | penjual | pembeli | admin | terhapus
   terhapus = soft-deleted (deleted=1); kantin slot sudah dikosongkan.
   print: Cetak Semua (window.print) + per-user card (JS inject). */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tandai menu "user" sebagai aktif di navbar admin
$halamansaatini = 'user';

// baca filter dari url (parameter GET). operator ?? memberi nilai default jika kosong.
$rolefilter = $_GET['role'] ?? 'semua';
$cari       = trim($_GET['cari'] ?? '');
// validasi role agar query tidak terbuka untuk nilai sembarangan (proteksi SQL injection)
if (!in_array($rolefilter, ['semua','admin','penjual','pembeli','terhapus'])) $rolefilter = 'semua';

// cek migrasi kolom nomor_kantin dan deleted_at
$cekkolom  = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'nomor_kantin'");
$migrasiSudah = ($cekkolom && $cekkolom->num_rows > 0);

$cekdel   = $conn->query("SHOW COLUMNS FROM tb_user LIKE 'deleted_at'");
$adaDelAt = ($cekdel && $cekdel->num_rows > 0);

// cek migrasi kolom status_verifikasi (dari migrasi_verifikasi_chat.sql) —
// kalau sudah ada, halaman ini menyembunyikan pembeli yang belum verified
// supaya admin tidak melihat duplikasi data antara halaman ini dan halaman Verifikasi Pembeli.
$cekverif    = $conn->query("SHOW COLUMNS FROM tb_user LIKE 'status_verifikasi'");
$adaVerifKol = ($cekverif && $cekverif->num_rows > 0);

// klausa tambahan: pembeli yang muncul di halaman ini harus berstatus verified.
// penjual & admin tidak dipengaruhi (mereka selalu verified setelah migrasi backfill).
$filterVerif = $adaVerifKol ? "AND (u.role <> 'pembeli' OR u.status_verifikasi='verified')" : "";
// versi tanpa alias untuk query ringkasan (tidak memakai JOIN/alias)
$filterVerifPolos = $adaVerifKol ? "AND (role <> 'pembeli' OR status_verifikasi='verified')" : "";

// hitung jumlah per role (aktif) untuk badge tab — GROUP BY role -> kelompokkan per peran
// deleted=0 berarti soft-delete: baris tidak benar-benar dihapus, hanya ditandai
$qhitung = $conn->query("SELECT role, COUNT(*) AS jml FROM tb_user
                          WHERE deleted=0 {$filterVerifPolos}
                          GROUP BY role");
$jmlrole = ['admin'=>0,'penjual'=>0,'pembeli'=>0];
// loop hasil query: setiap baris berisi role + jumlah, lalu disimpan ke array $jmlrole
while ($r = $qhitung->fetch_assoc()) $jmlrole[$r['role']] = (int)$r['jml'];
$jmlsemua = array_sum($jmlrole);

// hitung berapa akun yang sudah terhapus (deleted=1) — untuk badge tab "Terhapus".
// pembeli yang ditolak admin TIDAK dihitung di sini — mereka tampil khusus di tab
// "Ditolak" halaman Verifikasi Pembeli sebagai riwayat verifikasi, bukan riwayat hapus.
$filterDitolakPolos = $adaVerifKol ? "AND NOT (role='pembeli' AND status_verifikasi='ditolak')" : "";
$qterhapus   = $conn->query("SELECT COUNT(*) AS jml FROM tb_user WHERE deleted=1 {$filterDitolakPolos}");
$jmlTerhapus = (int)$qterhapus->fetch_assoc()['jml'];

$kolomNomor  = $migrasiSudah ? "t.nomor_kantin," : "NULL AS nomor_kantin,";
$delAtSelect = $adaDelAt ? "u.deleted_at," : "NULL AS deleted_at,";
$delAtOrder  = $adaDelAt ? "u.deleted_at DESC" : "u.id_user DESC";

// cek apakah tabel tb_riwayat_toko ada
$cektbr = $conn->query("SHOW TABLES LIKE 'tb_riwayat_toko'");
$adaRiwayat = ($cektbr && $cektbr->num_rows > 0);

// filter tambahan untuk tab "terhapus": exclude pembeli yang ditolak admin
// supaya mereka hanya tampil di halaman Verifikasi (tab Ditolak), tidak dobel di sini.
$filterDitolakAlias = $adaVerifKol ? "AND NOT (u.role='pembeli' AND u.status_verifikasi='ditolak')" : "";

// bangun query sesuai tab aktif
if ($rolefilter === 'terhapus') {
    // pesanan_user = orders dibuat user ini (sebagai pembeli)
    // pesanan_toko = orders dilayani user ini (sebagai penjual, via id_penjual)
    // — keduanya disimpan agar template bisa pilih sesuai role
    if ($adaRiwayat) {
        $sql = "SELECT u.id_user, u.username, u.email, u.role, u.created, {$delAtSelect} u.foto,
                       (SELECT COUNT(*) FROM tb_order o  WHERE o.id_user=u.id_user     AND o.deleted=0) AS pesanan_user,
                       (SELECT COUNT(*) FROM tb_order o2 WHERE o2.id_penjual=u.id_user AND o2.deleted=0) AS pesanan_toko,
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
                WHERE u.deleted=1 {$filterDitolakAlias}";
        $params = []; $types = '';
        if ($cari !== '') {
            $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR r.nama_toko LIKE ?)";
            $likcari = "%$cari%";
            $params[] = $likcari; $params[] = $likcari; $params[] = $likcari; $types .= 'sss';
        }
    } else {
        $sql = "SELECT u.id_user, u.username, u.email, u.role, u.created, {$delAtSelect} u.foto,
                       (SELECT COUNT(*) FROM tb_order o  WHERE o.id_user=u.id_user     AND o.deleted=0) AS pesanan_user,
                       (SELECT COUNT(*) FROM tb_order o2 WHERE o2.id_penjual=u.id_user AND o2.deleted=0) AS pesanan_toko,
                       NULL AS id_toko, NULL AS nomor_kantin, NULL AS nama_toko, NULL AS foto_toko
                FROM tb_user u WHERE u.deleted=1 {$filterDitolakAlias}";
        $params = []; $types = '';
        if ($cari !== '') {
            $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
            $likcari = "%$cari%";
            $params[] = $likcari; $params[] = $likcari; $types .= 'ss';
        }
    }
    $sql .= " ORDER BY {$delAtOrder}";
} else {
    /* ======== QUERY TAB AKTIF (semua / penjual / pembeli / admin) ========
       ambil daftar user yang masih aktif (deleted=0) plus info toko-nya
       (kalau dia penjual). dipakai untuk semua tab kecuali "terhapus".

       penjelasan tiap kolom yang di-SELECT:
       - u.id_user, u.username, u.email, u.role, u.created, u.foto
           kolom dasar dari tb_user.
       - t.id_toko, t.nama_toko, t.status_toko, t.foto_toko
           data toko yang ditempati penjual ini. LEFT JOIN -> NULL kalau
           user bukan penjual atau belum punya kantin.
       - {$kolomNomor}
           variabel yang sudah disiapkan di atas: kalau migrasi kolom
           nomor_kantin sudah jalan, nilainya "t.nomor_kantin,".
           kalau belum, nilainya "NULL AS nomor_kantin,". jadi kode di
           bawah aman dipakai tanpa harus cek terus-terusan.
       - urut_role (CASE WHEN ... THEN ... END)
           kolom buatan untuk sorting. ekspresi CASE = if-else versi SQL.
           penjual diberi nilai 0, pembeli 1, admin 2. nanti di ORDER BY
           diurutkan ASC supaya penjual tampil paling atas, lalu pembeli,
           terakhir admin.
       - pesanan_toko (subquery)
           hitung jumlah pesanan yang DILAYANI user ini sebagai penjual.
           filter pakai id_penjual=u.id_user (bukan id_toko) — penting!
           kalau pakai id_toko, penjual baru yang menempati slot bekas
           penjual lama akan ikut menghitung pesanan penjual sebelumnya.
       - pesanan_user (subquery)
           hitung jumlah pesanan yang DIBUAT user ini sebagai pembeli.
           dipakai untuk kolom "Pesanan" di tab pembeli/admin. */
    $sql = "SELECT u.id_user, u.username, u.email, u.role, u.created, u.foto, u.status_akun,
                   t.id_toko, {$kolomNomor} t.nama_toko, t.status_toko, t.foto_toko,
                   CASE u.role WHEN 'penjual' THEN 0 WHEN 'pembeli' THEN 1 ELSE 2 END AS urut_role,
                   (SELECT COUNT(*) FROM tb_order o  WHERE o.id_penjual=u.id_user AND o.deleted=0) AS pesanan_toko,
                   (SELECT COUNT(*) FROM tb_order o2 WHERE o2.id_user=u.id_user     AND o2.deleted=0) AS pesanan_user
            FROM tb_user u
            LEFT JOIN tb_toko t ON u.id_user=t.id_user AND t.deleted=0
            WHERE u.deleted=0 {$filterVerif}";

    /* siapkan array kosong untuk parameter prepared statement.
       $types = string berisi tipe data tiap parameter ('s'=string, 'i'=int).
       $params = nilai-nilai yang akan disubstitusi ke "?".
       keduanya nanti dipakai bersama-sama di $st->bind_param($types, ...$params). */
    $params = []; $types = '';

    /* filter berdasarkan role kalau bukan "semua".
       contoh: kalau user klik tab "Penjual", $rolefilter = 'penjual'
       maka query ditambah: AND u.role='penjual' (lewat prepared statement). */
    if ($rolefilter !== 'semua') {
        $sql .= " AND u.role=?";
        $params[] = $rolefilter;
        $types .= 's'; // 's' = string
    }

    /* filter pencarian: cari kata kunci di username / email / nama_toko.
       operator LIKE dengan wildcard % mencari substring (cocok di tengah teks).
       contoh: cari "bu" akan match "ibu kantin", "bubur", "warungbu", dst.
       parameter $likcari dipakai 3x karena ada 3 placeholder "?". */
    if ($cari !== '') {
        $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR t.nama_toko LIKE ?)";
        $likcari = "%$cari%";
        $params[] = $likcari; $params[] = $likcari; $params[] = $likcari;
        $types .= 'sss'; // tiga parameter, semuanya string
    }

    /* ======== URUTAN BARIS DI TABEL (ORDER BY berlapis) ========
       SQL akan memakai kunci urutan secara berlapis: kalau kunci pertama
       hasilnya sama untuk dua baris, baru pakai kunci ke-2, lalu ke-3.

       1) urut_role ASC
            penjual dulu (0), pembeli (1), admin (2). semua penjual akan
            dikelompokkan bersebelahan di atas, baru disusul pembeli, dst.

       2) COALESCE(t.nomor_kantin, 999) ASC
            di dalam grup yang sama, sort menurut nomor kantin.
            -> untuk PENJUAL: kantin ke-1 dulu, lalu 2, 3, ... 10.
            -> COALESCE(a, b) = ambil a kalau tidak NULL, kalau NULL ambil b.
               Jadi penjual yang belum punya kantin (nomor_kantin = NULL)
               dianggap "999" dan diletakkan di paling bawah grup penjual.
            -> untuk PEMBELI/ADMIN: nomor_kantin selalu NULL karena mereka
               tidak punya baris di tb_toko. semua dianggap 999 -> nilainya
               sama -> kunci urutan ini "tidak berpengaruh" dan SQL pakai
               kunci ke-3 (username) untuk menentukan urutan.

       3) u.username ASC
            kunci terakhir: urut abjad nama. dipakai sebagai tie-breaker
            kalau dua baris punya urut_role dan nomor_kantin sama.

       variabel $sortKantin di-build kondisional supaya:
       - kalau migrasi kolom nomor_kantin SUDAH jalan, kita pakai sortingnya
       - kalau BELUM jalan, kolomnya belum ada -> jangan disebut di ORDER BY
         (kalau dipaksa akan error "unknown column 't.nomor_kantin'") */
    $sortKantin = $migrasiSudah ? "COALESCE(t.nomor_kantin, 999) ASC," : "";
    $sql .= " ORDER BY urut_role ASC, {$sortKantin} u.username ASC";
}

// prepared statement: pisahkan query dan data — aman dari SQL injection.
// $types berisi tipe data tiap parameter ('s'=string, 'i'=integer), $params adalah datanya.
// operator spread (...) memecah array $params menjadi argumen-argumen terpisah ke bind_param.
$st = $conn->prepare($sql);
if ($params) { $st->bind_param($types, ...$params); }
$st->execute();
// fetch_all(MYSQLI_ASSOC) mengubah semua baris hasil menjadi array asosiatif sekaligus
$daftaruser = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// flash message: pesan satu kali dari operasi sebelumnya (misal sehabis tambah/hapus user).
// dibaca dari session lalu langsung dihapus (unset) agar tidak muncul lagi saat halaman direfresh.
$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']);
}

// jika ada parameter ?suspend=ID di URL, ambil data user itu untuk modal konfirmasi
// aktif/nonaktif. modal hanya dirender bila data ditemukan (lihat bagian bawah halaman).
$suspendid   = (int)($_GET['suspend'] ?? 0);
$datasuspend = null;
if ($suspendid > 0) {
    $qs = $conn->prepare("SELECT id_user, username, role, status_akun FROM tb_user WHERE id_user=? AND deleted=0");
    $qs->bind_param("i", $suspendid); $qs->execute();
    $datasuspend = $qs->get_result()->fetch_assoc(); // null jika tidak ada
    $qs->close();
    // admin tidak bisa dinonaktifkan → abaikan modal jika targetnya admin
    if ($datasuspend && $datasuspend['role'] === 'admin') $datasuspend = null;
}

// jika ada ?toggletoko=ID_TOKO di URL, ambil data toko untuk modal konfirmasi buka/tutup.
$toggletokoid   = (int)($_GET['toggletoko'] ?? 0);
$datatoggletoko = null;
if ($toggletokoid > 0) {
    $qtt = $conn->prepare("SELECT id_toko, nama_toko, status_toko FROM tb_toko WHERE id_toko=? AND deleted=0");
    $qtt->bind_param("i", $toggletokoid); $qtt->execute();
    $datatoggletoko = $qtt->get_result()->fetch_assoc();
    $qtt->close();
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
.cetakjudul { display:none; }

@media print {
  .takprint { display:none !important; }
  @page { size:A4; margin:12mm; }
  .kartu { box-shadow:none !important; border:1px solid #ddd !important; }
  table  { font-size:11px; width:100%; }
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
      <?php if ($rolefilter !== 'terhapus'): ?>
      <?php
      // tombol Tambah Pengguna context-aware: kalau admin lagi di tab penjual/pembeli/admin,
      // langsung ke form khusus role itu (skip pemilih peran). kalau di tab "semua", ke pemilih peran.
      $urlTambah = in_array($rolefilter, ['penjual','pembeli','admin'], true)
                   ? 'tambahuser.php?role=' . urlencode($rolefilter)
                   : 'tambahuser.php';
      ?>
      <a href="<?= $urlTambah ?>" class="tombolutama">
        <i class="fa-solid fa-user-plus"></i> Tambah Pengguna
      </a>
      <?php endif; ?>
      <button onclick="eksporXlsSeksi('seksi-users','daftar_pengguna')" class="tombolringan" style="background:var(--sukses);color:white;border-color:var(--sukses);">
        <i class="fa-solid fa-file-csv"></i> Cetak
      </button>
    </div>
  </div>

  <?php if ($flashpesan): ?>
  <div class="flashpesan flash<?= $flashjenis ?>">
    <i class="fa-solid fa-<?= $flashjenis === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
  <?php endif; ?>

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

  <div class="kartu seksi-laporan" id="seksi-users" style="padding:0;overflow:hidden;">
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <th>Pengguna</th>
            <th class="tengah">Peran Terakhir</th>
            <th class="tengah">Pesanan</th>
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
          <?php else: foreach ($daftaruser as $u): // loop tiap akun terhapus jadi baris tabel
            $tglDaftar   = !empty($u['created'])    ? date('d M Y', strtotime($u['created']))            : '—';
            $tglBerhenti = !empty($u['deleted_at']) ? date('d M Y H:i', strtotime($u['deleted_at']))     : '—';
            $tglBprint   = !empty($u['deleted_at']) ? date('d M Y', strtotime($u['deleted_at']))         : '—';
            // untuk penjual: tampilkan pesanan yang pernah dia layani (id_penjual=u.id_user)
            // untuk pembeli/admin: tampilkan pesanan yang dia buat sebagai pembeli
            $pesanan = $u['role']==='penjual' ? (int)$u['pesanan_toko'] : (int)$u['pesanan_user'];
            $ud = json_encode([
                'username'   => $u['username'],
                'email'      => $u['email'],
                'role'       => $u['role'],
                'pesanan'    => $pesanan,
                'created'    => $tglDaftar,
                'deleted_at' => $tglBprint,
            ], JSON_HEX_APOS | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
          ?>
          <tr>
            <td>
              <div class="user-baris">
                <?php
                // tampilkan foto profil yang pernah dipasang user (kalau filenya masih ada).
                // fallback berbeda menurut role:
                //   penjual → gambar default warung (profilwarung.png) — siluet putih
                //   pembeli/admin → inisial 2 huruf username
                $fotoFile = !empty($u['foto']) ? $u['foto'] : '';
                if ($fotoFile && file_exists(__DIR__ . '/../../2. aset/profil/' . $fotoFile)) {
                    $fotoHtml = '<img src="../../2. aset/profil/' . htmlspecialchars($fotoFile) . '" style="width:100%;height:100%;object-fit:cover;border-radius:8px;" alt="Foto">';
                } elseif ($u['role'] === 'penjual') {
                    // filter brightness(0) invert(1) = PNG transparan jadi solid putih
                    $fotoHtml = '<img src="../../2. aset/profil/profilwarung.png" alt="Warung" style="width:70%;height:70%;object-fit:contain;filter:brightness(0) invert(1);opacity:.85;">';
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
            <td class="tengah" style="font-weight:700;"><?= $pesanan ?></td>
            <td style="font-size:12px;white-space:nowrap;"><?= $tglDaftar ?></td>
            <td style="font-size:12px;white-space:nowrap;color:<?= !empty($u['deleted_at']) ? '#dc2626' : 'var(--tekssamar)' ?>;"><?= $tglBerhenti ?></td>
            <td class="tengah takprint">
              <div class="aksi-grup">
                <a href="viewuser.php?id=<?= $u['id_user'] ?>" class="tombolkecil"><i class="fa-solid fa-eye"></i> Detail</a>
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
    Klik badge <strong>Buka</strong> atau <strong>Tutup</strong> di kolom Status Toko untuk mengubah status toko (ada konfirmasi dulu).
  </div>
  <?php endif; ?>

  <div class="kartu seksi-laporan" id="seksi-users" style="padding:0;overflow:hidden;">
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
                // ambil foto: prioritas foto_toko (kalau penjual punya foto warung),
                // baru fallback ke foto pribadi user.
                $fotoFile = '';
                if (!empty($u['foto_toko'])) {
                    $fotoFile = $u['foto_toko'];
                } elseif (!empty($u['foto'])) {
                    $fotoFile = $u['foto'];
                }
                // fallback per role: penjual → profilwarung.png; pembeli/admin → inisial
                if ($fotoFile && file_exists(__DIR__ . '/../../2. aset/profil/' . $fotoFile)) {
                    $fotoHtml = '<img src="../../2. aset/profil/' . htmlspecialchars($fotoFile) . '" style="width:100%;height:100%;object-fit:cover;border-radius:8px;" alt="Foto">';
                } elseif ($u['role'] === 'penjual') {
                    $fotoHtml = '<img src="../../2. aset/profil/profilwarung.png" alt="Warung" style="width:70%;height:70%;object-fit:contain;filter:brightness(0) invert(1);opacity:.85;">';
                } else {
                    $fotoHtml = strtoupper(mb_substr($u['username'], 0, 2));
                }
                ?>
                <div class="avatar-tabel"><?= $fotoHtml ?></div>
                <div>
                  <div class="nama">
                    <?= htmlspecialchars($u['username']) ?>
                    <?php if (($u['status_akun'] ?? 'aktif') === 'nonaktif'): ?>
                    <!-- penanda akun yang sedang dinonaktifkan admin -->
                    <span class="badge dibatalkan" style="font-size:10px;">Nonaktif</span>
                    <?php endif; ?>
                  </div>
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
              <!-- buka modal konfirmasi dulu sebelum mengubah status toko -->
              <a href="user.php?toggletoko=<?= (int)$u['id_toko'] ?>&role=<?= urlencode($rolefilter) ?>#konfirm-toggletoko"
                 class="badge <?= $u['status_toko']==='buka'?'buka':'tutup' ?>"
                 style="text-decoration:none;cursor:pointer;" title="Klik untuk ubah status toko">
                <?= $u['status_toko']==='buka'?'Buka':'Tutup' ?>
                <i class="fa-solid fa-arrows-rotate" style="font-size:9px;"></i>
              </a>
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
                <?php
                // tombol aktif/nonaktif akun — admin tidak bisa dinonaktifkan.
                // membuka modal konfirmasi dulu (#konfirm-status) sebelum benar-benar diubah.
                $statusakun = $u['status_akun'] ?? 'aktif';
                if ($u['role'] !== 'admin'):
                ?>
                <a href="user.php?suspend=<?= $u['id_user'] ?>&role=<?= urlencode($rolefilter) ?>#konfirm-status"
                   class="tombolkecil <?= $statusakun==='aktif' ? 'kuning' : 'hijau' ?>"
                   title="<?= $statusakun==='aktif' ? 'Nonaktifkan akun' : 'Aktifkan akun' ?>">
                  <i class="fa-solid fa-<?= $statusakun==='aktif' ? 'user-slash' : 'user-check' ?>"></i>
                  <?= $statusakun==='aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                </a>
                <?php endif; ?>
                <a href="hapususer.php?id=<?= $u['id_user'] ?>" class="tombolkecil merah"><i class="fa-solid fa-trash"></i> Hapus</a>
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

<!-- modal konfirmasi aktif/nonaktif akun — hanya dirender jika ada ?suspend=ID valid.
     muncul saat url mengandung #konfirm-status (lihat tombol di kolom Aksi). -->
<?php if ($datasuspend):
  $akanNonaktif = ($datasuspend['status_akun'] ?? 'aktif') === 'aktif';
?>
<div class="modaloverlay" id="konfirm-status">
  <!-- klik area luar → tutup modal (kembali ke daftar tanpa ?suspend) -->
  <a href="user.php?role=<?= urlencode($rolefilter) ?>" class="penutup-modal"></a>
  <div class="isimodal" style="text-align:center;">
    <div style="font-size:42px;color:var(--<?= $akanNonaktif ? 'gagal' : 'sukses' ?>);margin-bottom:10px;">
      <i class="fa-solid fa-<?= $akanNonaktif ? 'user-slash' : 'user-check' ?>"></i>
    </div>
    <div style="font-size:17px;font-weight:800;color:var(--utama);margin-bottom:8px;">
      <?= $akanNonaktif ? 'Nonaktifkan Akun?' : 'Aktifkan Akun?' ?>
    </div>
    <div style="font-size:13px;color:var(--tekssamar);margin-bottom:20px;">
      <?php if ($akanNonaktif): ?>
      Akun <strong><?= htmlspecialchars($datasuspend['username']) ?></strong> tidak akan bisa login
      dan langsung dikeluarkan jika sedang aktif. Cocok untuk pembeli yang tidak mengambil pesanan.
      <?php else: ?>
      Akun <strong><?= htmlspecialchars($datasuspend['username']) ?></strong> akan bisa login kembali seperti biasa.
      <?php endif; ?>
    </div>
    <!-- form konfirmasi: kirim POST ke prosesstatususer.php -->
    <form method="POST" action="prosesstatususer.php">
      <input type="hidden" name="id_user" value="<?= (int)$datasuspend['id_user'] ?>">
      <input type="hidden" name="role" value="<?= htmlspecialchars($rolefilter) ?>">
      <button type="submit" class="tombolutama blok"
              style="margin-bottom:10px;background:var(--<?= $akanNonaktif ? 'gagal' : 'sukses' ?>);border-color:var(--<?= $akanNonaktif ? 'gagal' : 'sukses' ?>);">
        <i class="fa-solid fa-<?= $akanNonaktif ? 'user-slash' : 'user-check' ?>"></i>
        <?= $akanNonaktif ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' ?>
      </button>
    </form>
    <a href="user.php?role=<?= urlencode($rolefilter) ?>" class="tombolringan blok">Batal</a>
  </div>
</div>
<?php endif; ?>

<!-- modal konfirmasi buka/tutup toko — hanya dirender jika ada ?toggletoko=ID valid -->
<?php if ($datatoggletoko):
  $akanTutupToko = ($datatoggletoko['status_toko'] === 'buka');
?>
<div class="modaloverlay" id="konfirm-toggletoko">
  <a href="user.php?role=<?= urlencode($rolefilter) ?>" class="penutup-modal"></a>
  <div class="isimodal" style="text-align:center;">
    <div style="font-size:42px;color:var(--<?= $akanTutupToko ? 'gagal' : 'sukses' ?>);margin-bottom:10px;">
      <i class="fa-solid fa-<?= $akanTutupToko ? 'store-slash' : 'store' ?>"></i>
    </div>
    <div style="font-size:17px;font-weight:800;color:var(--utama);margin-bottom:8px;">
      <?= $akanTutupToko ? 'Tutup Toko Ini?' : 'Buka Toko Ini?' ?>
    </div>
    <div style="font-size:13px;color:var(--tekssamar);margin-bottom:20px;">
      Toko <strong><?= htmlspecialchars($datatoggletoko['nama_toko'] ?? 'ini') ?></strong>
      akan <?= $akanTutupToko ? 'ditutup — pembeli tidak bisa memesan sampai dibuka lagi.' : 'dibuka kembali dan menu bisa dipesan pembeli.' ?>
    </div>
    <!-- form konfirmasi: kirim POST ke prosestoggletoko.php -->
    <form method="POST" action="../manajementoko/prosestoggletoko.php">
      <input type="hidden" name="id_toko" value="<?= (int)$datatoggletoko['id_toko'] ?>">
      <button type="submit" class="tombolutama blok"
              style="margin-bottom:10px;background:var(--<?= $akanTutupToko ? 'gagal' : 'sukses' ?>);border-color:var(--<?= $akanTutupToko ? 'gagal' : 'sukses' ?>);">
        <i class="fa-solid fa-<?= $akanTutupToko ? 'store-slash' : 'store' ?>"></i>
        <?= $akanTutupToko ? 'Ya, Tutup Toko' : 'Ya, Buka Toko' ?>
      </button>
    </form>
    <a href="user.php?role=<?= urlencode($rolefilter) ?>" class="tombolringan blok">Batal</a>
  </div>
</div>
<?php endif; ?>

<script>
/* ekspor XLS (HTML-in-Excel) — tabel bergaris dengan identitas. */
var IDENTITAS = {
  judul:   'Manajemen Pengguna',
  filter:  <?= json_encode('Filter: ' . ucfirst($rolefilter) . ($cari ? ' · cari "' . $cari . '"' : '')) ?>,
};
function buildIdentitasHtml(j) {
  var tgl = new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});
  var lbl = 'border:1px solid #999;padding:6pt 10pt;background:#F8EBF1;font-weight:bold;width:160px;';
  var nil = 'border:1px solid #999;padding:6pt 10pt;';
  var h = '<table style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:11pt;margin-bottom:10pt;width:100%;">';
  h += '<tr><td colspan="2" style="background:#643843;color:white;font-weight:bold;font-size:14pt;text-align:center;padding:10pt;border:1px solid #444;">jajankita &mdash; ' + IDENTITAS.judul + '</td></tr>';
  if (j) h += '<tr><td style="'+lbl+'">Section</td><td style="'+nil+'">' + j + '</td></tr>';
  h += '<tr><td style="'+lbl+'">Filter</td><td style="'+nil+'">' + IDENTITAS.filter + '</td></tr>';
  h += '<tr><td style="'+lbl+'">Tanggal Cetak</td><td style="'+nil+'">' + tgl + '</td></tr>';
  return h + '</table>';
}
function tableToBorderedHtml(t) {
  var c = t.cloneNode(true);
  c.setAttribute('border','1'); c.setAttribute('cellpadding','6'); c.setAttribute('cellspacing','0');
  c.setAttribute('style','border-collapse:collapse;font-family:Arial,sans-serif;font-size:11pt;width:100%;margin-bottom:8pt;');
  c.querySelectorAll('th').forEach(function(th){ th.setAttribute('style','background:#643843;color:white;border:1px solid #3d2230;padding:8pt 10pt;text-align:left;font-weight:bold;'); });
  c.querySelectorAll('tbody tr').forEach(function(tr,i){
    var bg = i%2===1 ? 'background:#FAF6F8;' : '';
    tr.querySelectorAll('td').forEach(function(td){ td.setAttribute('style','border:1px solid #c8c8c8;padding:6pt 10pt;vertical-align:top;'+bg); });
  });
  // buang kolom "Aksi" supaya bersih (kelas takprint di header dan cell)
  c.querySelectorAll('.takprint').forEach(function(el){ el.remove(); });
  c.querySelectorAll('i').forEach(function(ic){ ic.remove(); });
  return c.outerHTML;
}
function unduhXls(body, namafile) {
  var doc = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="UTF-8"></head><body>' + body + '</body></html>';
  var blob = new Blob(['﻿'+doc],{type:'application/vnd.ms-excel'});
  var url = URL.createObjectURL(blob); var a = document.createElement('a');
  a.href = url; a.download = namafile + '_' + new Date().toISOString().slice(0,10) + '.xls';
  document.body.appendChild(a); a.click(); document.body.removeChild(a);
  setTimeout(function(){ URL.revokeObjectURL(url); }, 100);
}
function eksporXlsSeksi(id, namafile) {
  var s = document.getElementById(id); if (!s) return;
  var t = s.querySelector('table'); if (!t) { alert('Tidak ada tabel data.'); return; }
  var html = buildIdentitasHtml(IDENTITAS.judul);
  html += tableToBorderedHtml(t);
  unduhXls(html, namafile);
}
</script>

</body>
</html>
