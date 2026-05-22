<?php
/* halaman dashboard utama untuk admin.
   menampilkan statistik platform, chart revenue 7 hari terakhir,
   top produk, top pelanggan, performa toko, pengguna terbaru,
   dan form pengumuman untuk pembeli dan penjual. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tandai menu "index" sebagai aktif di navbar
$halamansaatini = 'index';

// cek apakah migrasi_kantin.sql sudah dijalankan (kolom nomor_kantin harus ada)
// jika belum, halaman tetap berjalan dengan query lama, dan banner peringatan akan tampil
$cekkolom = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'nomor_kantin'");
$migrasiSudah = ($cekkolom && $cekkolom->num_rows > 0);

// chart selalu menampilkan 7 hari terakhir, tidak bisa diubah dari url
$hari = 7;

// ambil jumlah pengguna per role (admin, penjual, pembeli) — hanya yang belum dihapus
$q = $conn->query("SELECT role, COUNT(*) AS jml FROM tb_user WHERE deleted=0 GROUP BY role");
$statuser = ['admin'=>0,'penjual'=>0,'pembeli'=>0];
while ($r = $q->fetch_assoc()) $statuser[$r['role']] = (int)$r['jml'];

// jumlahkan semua role menjadi total pengguna
$totaluser = array_sum($statuser);

// hitung total toko aktif (belum dihapus)
$qtoko = $conn->query("SELECT COUNT(*) FROM tb_toko WHERE deleted=0");
$totaltoko = (int)$qtoko->fetch_row()[0];

// hitung total menu yang statusnya aktif dan belum dihapus
$qmenu = $conn->query("SELECT COUNT(*) FROM tb_menu WHERE status='aktif' AND deleted=0");
$totalmenu = (int)$qmenu->fetch_row()[0];

// hitung total pesanan dan total nilai uangnya (coalesce: jika null maka 0)
$qorder = $conn->query("SELECT COUNT(*), COALESCE(SUM(total_harga),0) FROM tb_order WHERE deleted=0");
$rorder = $qorder->fetch_row();
$totalorder   = (int)$rorder[0];

// hitung total revenue hanya dari pesanan yang sudah selesai
$qselesai = $conn->query("SELECT COALESCE(SUM(total_harga),0) FROM tb_order WHERE status_order='Selesai' AND deleted=0");
$revenueselesai = (float)$qselesai->fetch_row()[0];

// hitung jumlah pesanan yang dibuat hari ini saja
$qhari = $conn->query("SELECT COUNT(*) FROM tb_order WHERE DATE(tanggal_order)=CURDATE() AND deleted=0");
$orderhari = (int)$qhari->fetch_row()[0];

// hitung berapa pengguna baru yang daftar hari ini
$quserbaru = $conn->query("SELECT COUNT(*) FROM tb_user WHERE DATE(created)=CURDATE() AND deleted=0");
$userbaru = (int)$quserbaru->fetch_row()[0];

/* fungsi pembantu: mengubah kode hari bahasa inggris (Sun, Mon, dst)
   menjadi singkatan bahasa indonesia (Min, Sen, Sel, dst) */
function namahari(string $tgl): string {
    $map = ['Sun'=>'Min','Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab'];
    return $map[date('D', strtotime($tgl))] ?? date('D', strtotime($tgl));
}

// tentukan rentang tanggal 7 hari terakhir (6 hari lalu sampai hari ini)
$tgl7dari   = date('Y-m-d', strtotime('-6 days'));
$tgl7sampai = date('Y-m-d');

// ambil total revenue per hari dalam rentang 7 hari, hanya pesanan selesai
// prepared statement: cara aman mengirim parameter ke query (mencegah sql injection)
$qchart = $conn->prepare("SELECT DATE(tanggal_order) AS tgl, COALESCE(SUM(total_harga),0) AS nilai FROM tb_order WHERE DATE(tanggal_order) BETWEEN ? AND ? AND status_order='Selesai' AND deleted=0 GROUP BY DATE(tanggal_order)");
$qchart->bind_param("ss", $tgl7dari, $tgl7sampai); // "ss" = dua parameter bertipe string
$qchart->execute();
$rawchart = []; // array sementara: kunci tanggal, nilai uang
$resc = $qchart->get_result();
while ($row = $resc->fetch_assoc()) $rawchart[$row['tgl']] = (float)$row['nilai'];
$qchart->close(); // tutup prepared statement setelah selesai

// bangun array chart lengkap 7 hari, tanggal yang tidak ada datanya diisi 0
$chartdata = [];
for ($i = $hari - 1; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $chartdata[] = ['tgl'=>$tgl,'label'=>namahari($tgl),'nilai'=>$rawchart[$tgl]??0.0];
}

// cari nilai tertinggi untuk menentukan skala tinggi bar chart (minimal 1 agar tidak dibagi 0)
$maxnilai = max(array_column($chartdata, 'nilai')) ?: 1;

// ambil 5 pelanggan yang paling banyak memesan (berdasarkan jumlah pesanan selesai)
$qtopbeli = $conn->query("SELECT u.username, COUNT(o.id_order) AS jml_order, COALESCE(SUM(o.total_harga),0) AS total_belanja FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user WHERE o.deleted=0 AND o.status_order='Selesai' GROUP BY o.id_user, u.username ORDER BY jml_order DESC LIMIT 5");
$topbelibanyak = $qtopbeli->fetch_all(MYSQLI_ASSOC);

// ambil 5 pelanggan dengan total belanja terbesar
$qtopmahal = $conn->query("SELECT u.username, COUNT(o.id_order) AS jml_order, COALESCE(SUM(o.total_harga),0) AS total_belanja FROM tb_order o JOIN tb_user u ON o.id_user=u.id_user WHERE o.deleted=0 AND o.status_order='Selesai' GROUP BY o.id_user, u.username ORDER BY total_belanja DESC LIMIT 5");
$topbelimahal = $qtopmahal->fetch_all(MYSQLI_ASSOC);

// baca isi file teks pengumuman untuk pembeli (jika file ada, baca isinya; jika tidak, kosong)
$_filePengumuman = __DIR__ . '/../../3. komponen/tekspengumuman.txt';
$teksPengumumanSaatIni = file_exists($_filePengumuman) ? trim(file_get_contents($_filePengumuman)) : '';

// baca isi file teks pengumuman khusus untuk penjual
$_filePengumumanPenjual = __DIR__ . '/../../3. komponen/tekspengumumanpenjual.txt';
$teksPengumumanPenjual = file_exists($_filePengumumanPenjual) ? trim(file_get_contents($_filePengumumanPenjual)) : '';

// ambil flash message (pesan sementara setelah redirect), lalu hapus dari session agar tidak muncul dua kali
$flash = null;
if (!empty($_SESSION['flash_admin'])) { $flash = $_SESSION['flash_admin']; unset($_SESSION['flash_admin']); }

// ambil 5 produk terlaris di seluruh platform (dari semua toko, kecuali pesanan dibatalkan)
$qtl = $conn->query("SELECT m.nama_menu, t.nama_toko, SUM(d.jumlah) AS terjual, SUM(d.subtotal) AS omset
                     FROM tb_detail_order d
                     JOIN tb_menu m ON d.id_menu=m.id_menu
                     JOIN tb_toko t ON m.id_toko=t.id_toko
                     JOIN tb_order o ON d.id_order=o.id_order
                     WHERE d.deleted=0 AND o.deleted=0 AND o.status_order != 'Dibatalkan'
                     GROUP BY m.id_menu, m.nama_menu, t.nama_toko
                     ORDER BY terjual DESC LIMIT 5");
$terlaris = $qtl->fetch_all(MYSQLI_ASSOC);

/* ambil performa setiap toko.
   jika migrasi sudah dijalankan: tampilkan nomor_kantin dan filter hanya toko yang terisi.
   jika belum: gunakan query lama tanpa nomor_kantin agar tidak crash. */
if ($migrasiSudah) {
    // query baru: nomor_kantin ada, filter id_user IS NOT NULL, urut nomor_kantin
    $qtoko2 = $conn->query("SELECT t.id_toko, t.nomor_kantin, t.nama_toko, t.status_toko,
                                    (SELECT COUNT(*) FROM tb_order o WHERE o.id_toko=t.id_toko AND o.deleted=0) AS total_order,
                                    (SELECT COUNT(*) FROM tb_order o WHERE o.id_toko=t.id_toko AND o.status_order='Dibatalkan' AND o.deleted=0) AS jml_dibatalkan,
                                    (SELECT COALESCE(SUM(o2.total_harga),0) FROM tb_order o2 WHERE o2.id_toko=t.id_toko AND o2.status_order IN ('Selesai','Dibatalkan') AND o2.deleted=0) AS omset,
                                    (SELECT COALESCE(ROUND(AVG(r.rating_toko),1),0) FROM tb_rating r WHERE r.id_toko=t.id_toko AND r.deleted=0) AS rating
                             FROM tb_toko t
                             WHERE t.deleted=0 AND t.id_user IS NOT NULL
                             ORDER BY t.nomor_kantin ASC");
} else {
    // query lama: kompatibel sebelum migrasi dijalankan
    $qtoko2 = $conn->query("SELECT t.id_toko, NULL AS nomor_kantin, t.nama_toko, t.status_toko,
                                    (SELECT COUNT(*) FROM tb_order o WHERE o.id_toko=t.id_toko AND o.deleted=0) AS total_order,
                                    (SELECT COUNT(*) FROM tb_order o WHERE o.id_toko=t.id_toko AND o.status_order='Dibatalkan' AND o.deleted=0) AS jml_dibatalkan,
                                    (SELECT COALESCE(SUM(o2.total_harga),0) FROM tb_order o2 WHERE o2.id_toko=t.id_toko AND o2.status_order IN ('Selesai','Dibatalkan') AND o2.deleted=0) AS omset,
                                    (SELECT COALESCE(ROUND(AVG(r.rating_toko),1),0) FROM tb_rating r WHERE r.id_toko=t.id_toko AND r.deleted=0) AS rating
                             FROM tb_toko t
                             WHERE t.deleted=0
                             ORDER BY omset DESC");
}
$perftoko = $qtoko2->fetch_all(MYSQLI_ASSOC);

// ambil 5 pengguna yang paling baru mendaftar
$qnew = $conn->query("SELECT id_user, username, email, role, created FROM tb_user WHERE deleted=0 ORDER BY created DESC LIMIT 5");
$penggunabarufull = $qnew->fetch_all(MYSQLI_ASSOC);

// fungsi pembantu: format angka menjadi format rupiah (contoh: Rp 15.000)
function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }

/* fungsi pembantu: singkat nilai rupiah besar agar tidak melebihi lebar kartu statistik.
   contoh: 1.500.000 -> Rp 1,5 Jt | 2.000.000.000 -> Rp 2 M */
function singkat(float $n): string {
    if ($n >= 1_000_000_000) {
        $v = $n / 1_000_000_000;
        return 'Rp ' . rtrim(rtrim(number_format($v, 1, ',', ''), '0'), ',') . ' M';
    }
    if ($n >= 1_000_000) {
        $v = $n / 1_000_000;
        return 'Rp ' . rtrim(rtrim(number_format($v, 1, ',', ''), '0'), ',') . ' Jt';
    }
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// hitung dimensi svg chart berdasarkan jumlah data agar selalu proporsional
$n      = count($chartdata);
$svgw   = max(700, $n * 30);           // lebar svg minimal 700px
$barw   = max(8, min(40, (int)(($svgw - 80) / $n) - 4)); // lebar tiap bar
$gap    = max(4, (int)(($svgw - 80 - $n * $barw) / max(1, $n - 1))); // jarak antar bar
$startx = 70; $chartH = 160;           // titik mulai bar dan tinggi area chart
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbaradmin.php'; ?>

<main class="konten">

  <?php if (!$migrasiSudah): ?>
  <!-- banner wajib: tampil jika migrasi_kantin.sql belum dijalankan di phpMyAdmin -->
  <div class="peringatan peringatangagal" style="margin-bottom:16px;">
    <i class="fa-solid fa-database"></i>
    <strong>Migrasi database belum dijalankan!</strong>
    Buka <strong>phpMyAdmin</strong>, pilih database <code>e_kantin</code>,
    klik tab <strong>SQL</strong>, lalu jalankan isi file
    <strong><code>migrasi_kantin.sql</code></strong> yang ada di root folder E-Kantin.
    Setelah itu refresh halaman ini.
    Beberapa fitur baru (nomor kantin, sistem 10 slot) belum aktif sampai migrasi dijalankan.
  </div>
  <?php endif; ?>

  <?php if ($flash): ?>
  <div class="peringatan <?= $flash['jenis'] === 'sukses' ? 'peringatansukses' : 'peringatangagal' ?>" style="margin-bottom:16px;">
    <i class="fa-solid fa-<?= $flash['jenis'] === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flash['pesan']) ?>
  </div>
  <?php endif; ?>

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-gauge-high"></i> Dashboard Admin</h1>
      <p>Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?> — <?= date('l, d M Y') ?></p>
    </div>
    <a href="../laporan/laporan.php" class="tombolutama">
      <i class="fa-solid fa-chart-bar"></i> Laporan Platform
    </a>
  </div>

  <!-- STATISTIK UTAMA -->
  <div class="grid-stat">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-users"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $totaluser ?></div>
        <div class="label">Total Pengguna</div>
        <div class="sub"><?= $statuser['pembeli'] ?> pembeli · <?= $statuser['penjual'] ?> penjual</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-store"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $totaltoko ?></div>
        <div class="label">Total Toko</div>
        <div class="sub"><?= $totalmenu ?> menu aktif</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-receipt"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $totalorder ?></div>
        <div class="label">Total Pesanan</div>
        <div class="sub"><?= $orderhari ?> pesanan hari ini</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-coins"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= singkat($revenueselesai) ?></div>
        <div class="label">Total Revenue</div>
        <div class="sub">Pesanan selesai</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-user-plus"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $userbaru ?></div>
        <div class="label">User Baru Hari Ini</div>
        <div class="sub"><?= $statuser['admin'] ?> admin terdaftar</div>
      </div>
    </div>
  </div>

  <!-- CHART REVENUE -->
  <div class="kartu">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <h3 style="margin:0;border:none;padding:0;">
        <i class="fa-solid fa-chart-bar"></i> Revenue Platform — 7 Hari Terakhir
      </h3>
      <a href="../laporan/laporan.php" style="font-size:12px;color:var(--kedua);font-weight:600;white-space:nowrap;">
        Lihat Semua Laporan →
      </a>
    </div>
    <div class="area-chart">
      <!-- svg bar chart dibuat murni dari php, tanpa library javascript -->
      <svg viewBox="0 0 <?= $svgw ?> 210" xmlns="http://www.w3.org/2000/svg" style="min-width:<?= min(700,$svgw) ?>px;">
        <?php for ($g = 0; $g <= 4; $g++): $gy = 20 + ($g * 40); ?>
        <!-- garis horizontal pembatas skala nilai -->
        <line x1="60" y1="<?= $gy ?>" x2="<?= $svgw - 10 ?>" y2="<?= $gy ?>" stroke="#E7CBCB" stroke-width="1" stroke-dasharray="4,4"/>
        <text x="55" y="<?= $gy+4 ?>" text-anchor="end" fill="#99627A" font-size="9"><?= singkat(($maxnilai/4)*(4-$g)) ?></text>
        <?php endfor; ?>
        <?php foreach ($chartdata as $i => $d):
          // hitung posisi dan tinggi tiap bar berdasarkan nilai relatif terhadap maksimum
          $x    = $startx + $i * ($barw + $gap);
          $barh = $d['nilai'] > 0 ? ($d['nilai'] / $maxnilai) * $chartH : 2; // minimal tinggi 2px agar bar tetap terlihat
          $by   = 180 - $barh;
          $isToday = $d['tgl'] === date('Y-m-d'); // hari ini diberi warna lebih gelap
        ?>
        <rect x="<?= $x ?>" y="<?= $by ?>" width="<?= $barw ?>" height="<?= $barh ?>"
              rx="3" fill="<?= $isToday ? '#643843' : '#99627A' ?>">
          <title><?= $d['label'] ?> — <?= rp($d['nilai']) ?></title>
        </rect>
        <!-- label hari di bawah bar -->
        <text x="<?= $x + $barw/2 ?>" y="200" text-anchor="middle"
              fill="<?= $isToday ? '#643843' : '#99627A' ?>"
              font-size="<?= $hari > 14 ? '8' : '10' ?>"
              font-weight="<?= $isToday ? '700' : '400' ?>"><?= $d['label'] ?></text>
        <?php if ($d['nilai'] > 0 && $barw >= 20): ?>
        <!-- label nilai singkat di atas bar, hanya tampil jika bar cukup lebar -->
        <text x="<?= $x + $barw/2 ?>" y="<?= max($by-4, 14) ?>" text-anchor="middle" fill="#643843" font-size="8" font-weight="600">
          <?= number_format($d['nilai']/1000, 0) ?>k
        </text>
        <?php endif; ?>
        <?php endforeach; ?>
      </svg>
    </div>
  </div>

  <div class="grid-dua">

    <!-- TOP PRODUK -->
    <div class="kartu" id="seksi-terlaris">
      <h3><i class="fa-solid fa-fire"></i> Produk Terlaris Platform</h3>
      <?php if (empty($terlaris)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada data penjualan</p></div>
      <?php else: ?>
      <?php $medal = ['emas','perak','perunggu']; ?>
      <?php foreach ($terlaris as $i => $t): ?>
      <div class="baris-produk">
        <!-- badge peringkat: emas (#1), perak (#2), perunggu (#3), sisanya tanpa kelas -->
        <div class="rangking-produk <?= $medal[$i] ?? '' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= htmlspecialchars($t['nama_menu']) ?>
          </div>
          <div style="font-size:11px;color:var(--tekssamar);">
            <?= htmlspecialchars($t['nama_toko']) ?> · <?= rp($t['omset']) ?>
          </div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;">
          <?= $t['terjual'] ?> terjual
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- PENGGUNA TERBARU -->
    <div class="kartu" id="seksi-pengguna-baru">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <h3 style="margin:0;border:none;padding:0;">
          <i class="fa-solid fa-user-plus"></i> Pengguna Terbaru
        </h3>
        <a href="../manajemenpengguna/user.php" style="font-size:12px;color:var(--kedua);font-weight:600;">Lihat Semua →</a>
      </div>
      <?php if (empty($penggunabarufull)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada pengguna</p></div>
      <?php else: ?>
      <?php foreach ($penggunabarufull as $u): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid var(--latar);">
        <!-- avatar berupa dua huruf pertama username, huruf besar -->
        <div class="avatar-tabel"><?= strtoupper(mb_substr($u['username'],0,2)) ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= htmlspecialchars($u['username']) ?>
          </div>
          <div style="font-size:11px;color:var(--tekssamar);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= htmlspecialchars($u['email']) ?>
          </div>
        </div>
        <span class="badge <?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

  <!-- TOP PELANGGAN -->
  <div class="grid-dua">
    <div class="kartu" id="seksi-pelanggan-banyak">
      <h3><i class="fa-solid fa-cart-shopping"></i> Pelanggan Terbanyak Pesan</h3>
      <?php if (empty($topbelibanyak)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada data</p></div>
      <?php else: ?>
      <?php $medal = ['emas','perak','perunggu']; ?>
      <?php foreach ($topbelibanyak as $i => $p): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--latar);">
        <div class="rangking-produk <?= $medal[$i]??'' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['username']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);"><?= rp($p['total_belanja']) ?></div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;"><?= $p['jml_order'] ?>× pesan</div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="kartu" id="seksi-pelanggan-mahal">
      <h3><i class="fa-solid fa-wallet"></i> Pelanggan Pengeluaran Terbesar</h3>
      <?php if (empty($topbelimahal)): ?>
      <div class="kosong" style="padding:20px;"><p>Belum ada data</p></div>
      <?php else: ?>
      <?php foreach ($topbelimahal as $i => $p): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--latar);">
        <div class="rangking-produk <?= $medal[$i]??'' ?>">#<?= $i+1 ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['username']) ?></div>
          <div style="font-size:11px;color:var(--tekssamar);"><?= $p['jml_order'] ?> pesanan</div>
        </div>
        <div style="font-size:13px;font-weight:700;color:var(--utama);white-space:nowrap;"><?= singkat($p['total_belanja']) ?></div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- PERFORMA TOKO -->
  <div class="kartu" id="seksi-performa">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <h3 style="margin:0;border:none;padding:0;">
        <i class="fa-solid fa-store"></i> Performa Toko
      </h3>
      <a href="../manajemenpengguna/user.php?role=penjual" style="font-size:12px;color:var(--kedua);font-weight:600;">Kelola Penjual →</a>
    </div>
    <div class="tabel-wrapper">
      <table>
        <thead>
          <tr>
            <!-- kolom nomor kantin hanya tampil jika migrasi sudah dijalankan -->
            <?php if ($migrasiSudah): ?>
            <th class="tengah">No.</th>
            <?php endif; ?>
            <th>Nama Toko</th>
            <th class="tengah">Status</th>
            <th class="tengah">Total Pesanan</th>
            <th class="tengah">Dibatalkan</th>
            <th class="tengah">Rating</th>
            <th class="kanan">Total Omset</th>
            <th class="tengah">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($perftoko)): ?>
          <tr><td colspan="8"><div class="kosong" style="padding:20px;"><p>Belum ada toko</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($perftoko as $t): ?>
          <tr>
            <!-- kolom nomor kantin hanya ditampilkan jika migrasi sudah dijalankan -->
            <?php if ($migrasiSudah): ?>
            <td class="tengah" style="font-weight:800;font-size:16px;color:var(--utama);">
              <?= (int)$t['nomor_kantin'] ?>
            </td>
            <?php endif; ?>
            <td><strong><?= htmlspecialchars($t['nama_toko']) ?></strong></td>
            <td class="tengah">
              <span class="badge <?= $t['status_toko'] === 'buka' ? 'buka' : 'tutup' ?>">
                <?= $t['status_toko'] === 'buka' ? 'Buka' : 'Tutup' ?>
              </span>
            </td>
            <td class="tengah"><?= $t['total_order'] ?></td>
            <td class="tengah" style="color:var(--gagal);"><?= (int)$t['jml_dibatalkan'] ?></td>
            <td class="tengah"><?= $t['rating'] > 0 ? $t['rating'] . ' ★' : '—' ?></td>
            <td class="kanan" style="font-weight:700;color:var(--sukses);"><?= rp($t['omset']) ?></td>
            <td class="tengah">
              <a href="../manajementoko/viewtoko.php?id=<?= $t['id_toko'] ?>" class="tombol-aksi" title="Lihat">
                <i class="fa-solid fa-eye"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- PENGUMUMAN — dua kolom: pembeli & penjual -->
  <div class="grid-dua">
    <div class="kartu">
      <h3><i class="fa-solid fa-bullhorn"></i> Pengumuman ke Pembeli</h3>
      <p style="font-size:12px;color:var(--tekssamar);margin-bottom:12px;">
        Muncul sebagai banner di beranda pembeli. Kosongkan untuk menonaktifkan.
      </p>
      <!-- form dikirim ke proses_pengumuman.php untuk disimpan ke file txt -->
      <form method="POST" action="proses_pengumuman.php">
        <textarea name="teks_pengumuman" rows="3" maxlength="500"
          style="width:100%;box-sizing:border-box;padding:10px 12px;border:1.5px solid var(--garis);border-radius:10px;font-size:13px;resize:vertical;font-family:inherit;"
          placeholder="Contoh: Kantin tutup hari Jumat karena libur nasional."><?= htmlspecialchars($teksPengumumanSaatIni) ?></textarea>
        <div style="display:flex;justify-content:flex-end;margin-top:10px;">
          <button type="submit" class="tombolutama"><i class="fa-solid fa-paper-plane"></i> Kirim</button>
        </div>
      </form>
    </div>
    <div class="kartu">
      <h3><i class="fa-solid fa-store"></i> Pengumuman ke Penjual</h3>
      <p style="font-size:12px;color:var(--tekssamar);margin-bottom:12px;">
        Muncul sebagai banner di semua halaman penjual. Kosongkan untuk menonaktifkan.
      </p>
      <!-- form dikirim ke proses_pengumuman_penjual.php untuk disimpan ke file txt -->
      <form method="POST" action="proses_pengumuman_penjual.php">
        <textarea name="teks_pengumuman_penjual" rows="3" maxlength="500"
          style="width:100%;box-sizing:border-box;padding:10px 12px;border:1.5px solid var(--garis);border-radius:10px;font-size:13px;resize:vertical;font-family:inherit;"
          placeholder="Contoh: Harap perbarui menu sebelum Senin."><?= htmlspecialchars($teksPengumumanPenjual) ?></textarea>
        <div style="display:flex;justify-content:flex-end;margin-top:10px;">
          <button type="submit" class="tombolutama"><i class="fa-solid fa-paper-plane"></i> Kirim</button>
        </div>
      </form>
    </div>
  </div>

</main>

</body>
</html>
