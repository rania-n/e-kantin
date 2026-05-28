<?php
/* halaman daftar semua kantin (10 slot tetap).
   menampilkan status setiap kantin: terisi (ada penjual) atau kosong (belum ada penjual).
   fitur: tampilkan nomor kantin, pemilik, nama toko, status buka/tutup.
   admin bisa melihat kantin mana yang kosong dan tersedia untuk penjual baru. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tandai menu "kantin" sebagai aktif di navbar
$halamansaatini = 'kantin';

// cek apakah ada parameter ?cetak di url untuk mode print
$cetak = isset($_GET['cetak']);

// cek apakah migrasi nomor_kantin sudah dijalankan; halaman ini butuh kolom tersebut
$cekkolom = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'nomor_kantin'");
$migrasiSudah = ($cekkolom && $cekkolom->num_rows > 0);

// ambil semua kantin diurutkan berdasarkan nomor_kantin (hanya jika migrasi sudah jalan)
// LEFT JOIN ke tb_user untuk mendapatkan nama penjual (NULL jika kantin kosong)
$daftarkantin = [];
if ($migrasiSudah) {
    $qkantin = $conn->query(
        "SELECT t.id_toko, t.nomor_kantin, t.id_user, t.nama_toko,
                t.status_toko, t.deleted,
                u.username, u.email
         FROM tb_toko t
         LEFT JOIN tb_user u ON t.id_user = u.id_user AND u.deleted=0
         WHERE t.deleted = 0
         ORDER BY t.nomor_kantin ASC"
    );
    $daftarkantin = $qkantin ? $qkantin->fetch_all(MYSQLI_ASSOC) : [];
}

// hitung statistik untuk kartu ringkasan
$totalkantin  = count($daftarkantin);
$terisi       = 0; // kantin yang punya penjual
$kosong       = 0; // kantin tanpa penjual
$buka         = 0; // kantin yang statusnya buka
foreach ($daftarkantin as $k) {
    // username NULL jika id_user ada tapi penjualnya sudah dihapus (soft delete)
    if (!empty($k['username'])) { $terisi++; } else { $kosong++; }
    if ($k['status_toko'] === 'buka') $buka++;
}

// ambil flash message dari session (pesan hasil operasi sebelumnya)
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
<title>Status Kantin - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* kartu kantin: tampilan grid 2 kolom seperti kartu fisik kantin */
.grid-kantin {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
/* satu kartu kantin */
.kartu-kantin {
    border: 2px solid var(--garis);
    border-radius: 14px;
    padding: 18px;
    background: var(--putihbg);
    position: relative;
    transition: box-shadow .15s;
}
.kartu-kantin:hover { box-shadow: 0 4px 18px rgba(99,56,67,.1); }

/* kartu kantin yang kosong (tidak ada penjual) — border lebih terang */
.kartu-kantin.kosong {
    border-color: #e0e0e0;
    background: #fafafa;
}
/* nomor kantin yang besar di pojok kiri atas */
.nomor-kantin {
    font-size: 36px;
    font-weight: 900;
    color: var(--utama);
    line-height: 1;
    margin-bottom: 6px;
}
.kartu-kantin.kosong .nomor-kantin { color: #bdbdbd; }

/* label "Kantin ke-X" di bawah nomor */
.label-kantin {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--tekssamar);
    margin-bottom: 10px;
}
/* nama toko besar */
.nama-toko-kantin {
    font-size: 15px;
    font-weight: 800;
    color: var(--teks);
    margin-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
/* nama pemilik (username penjual) */
.pemilik-kantin {
    font-size: 12px;
    color: var(--tekssamar);
    margin-bottom: 10px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
/* badge di pojok kanan atas */
.badge-kantin {
    position: absolute;
    top: 14px;
    right: 14px;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
}
.badge-kantin.terisi  { background:#d4edda; color:#1a7a3a; }
.badge-kantin.kosong  { background:#f5f5f5; color:#9e9e9e; }
/* tombol aksi di bawah kartu */
.aksi-kantin {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid var(--latar);
}
/* strip atas kartu (warna berbeda untuk terisi vs kosong) */
.strip-kantin {
    height: 4px;
    border-radius: 4px 4px 0 0;
    margin: -18px -18px 14px;
    border-radius: 12px 12px 0 0;
}
.strip-kantin.terisi { background: var(--utama); }
.strip-kantin.kosong { background: #e0e0e0; }

/* sembunyikan tombol aksi saat cetak */
@media print {
    @page { size: A4; margin: 12mm; }
    .takprint { display: none !important; }
    .kartu-kantin { break-inside: avoid; border-color: #ccc !important; }
    .badge-kantin { position: static; display: inline-block; margin-bottom: 6px; }
    .grid-kantin { grid-template-columns: repeat(3, 1fr); gap: 10px; }
}
</style>
</head>
<body>

<?php if (!$cetak): ?>
<?php include '../../3. komponen/navbaradmin.php'; ?>
<?php endif; ?>

<main class="konten">

  <!-- header halaman dan tombol aksi -->
  <div class="header-halaman takprint">
    <div class="kiri">
      <h1><i class="fa-solid fa-store"></i> Status Kantin</h1>
      <p>10 slot kantin tetap — lihat yang terisi dan yang tersedia</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <!-- tombol Cetak: ekspor tabel slot kantin ke file XLS bergaris dengan identitas -->
      <button onclick="eksporXlsSeksi('seksi-kantin','status_kantin')" class="tombolringan takprint" style="background:var(--sukses);color:white;border-color:var(--sukses);">
        <i class="fa-solid fa-file-csv"></i> Cetak
      </button>
      <a href="../manajemenpengguna/tambahuser.php?role=penjual" class="tombolutama takprint">
        <i class="fa-solid fa-user-plus"></i> Tambah Penjual
      </a>
    </div>
  </div>

  <?php if ($flashpesan): ?>
  <div class="flashpesan flash<?= $flashjenis ?> takprint">
    <i class="fa-solid fa-<?= $flashjenis === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
  <?php endif; ?>

  <!-- kartu statistik ringkasan -->
  <div class="grid-stat takprint" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
    <div class="kartu-stat">
      <div class="ikon-stat"><i class="fa-solid fa-store"></i></div>
      <div class="isi-stat">
        <div class="nilai"><?= $totalkantin ?></div>
        <div class="label">Total Kantin</div>
        <div class="sub">Slot fisik tetap</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:#d4edda;color:#1a7a3a;">
        <i class="fa-solid fa-store"></i>
      </div>
      <div class="isi-stat">
        <div class="nilai"><?= $terisi ?></div>
        <div class="label">Kantin Terisi</div>
        <div class="sub">Ada penjual aktif</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:#f5f5f5;color:#9e9e9e;">
        <i class="fa-solid fa-store-slash"></i>
      </div>
      <div class="isi-stat">
        <div class="nilai"><?= $kosong ?></div>
        <div class="label">Kantin Kosong</div>
        <div class="sub">Tersedia untuk penjual baru</div>
      </div>
    </div>
    <div class="kartu-stat">
      <div class="ikon-stat" style="background:var(--suksebg);color:var(--sukses);">
        <i class="fa-solid fa-door-open"></i>
      </div>
      <div class="isi-stat">
        <div class="nilai"><?= $buka ?></div>
        <div class="label">Sedang Buka</div>
        <div class="sub">Bisa dipesan pembeli</div>
      </div>
    </div>
  </div>

  <!-- judul seksi untuk versi cetak -->
  <div style="text-align:center;margin-bottom:16px;display:none;" class="judulcetak">
    <h2 style="font-size:18px;font-weight:800;">Status Kantin jajankita</h2>
    <p style="font-size:12px;color:#666;">Dicetak: <?= date('d M Y H:i') ?></p>
  </div>
  <style>
  @media print { .judulcetak { display:block !important; } }
  </style>

  <!-- grid semua kartu kantin -->
  <div class="grid-kantin">
    <?php foreach ($daftarkantin as $k): ?>
    <?php $adaPemilik = !empty($k['username']); ?>
    <div class="kartu-kantin <?= $adaPemilik ? 'terisi' : 'kosong' ?>">

      <!-- strip warna di atas kartu -->
      <div class="strip-kantin <?= $adaPemilik ? 'terisi' : 'kosong' ?>"></div>

      <!-- badge status di pojok kanan atas -->
      <span class="badge-kantin <?= $adaPemilik ? 'terisi' : 'kosong' ?>">
        <?= $adaPemilik ? 'Terisi' : 'Kosong' ?>
      </span>

      <!-- nomor kantin besar -->
      <div class="nomor-kantin"><?= (int)$k['nomor_kantin'] ?></div>
      <div class="label-kantin">Kantin ke-<?= (int)$k['nomor_kantin'] ?></div>

      <?php if ($adaPemilik): ?>
      <!-- kantin terisi: tampilkan nama toko dan pemilik -->
      <div class="nama-toko-kantin">
        <?= htmlspecialchars($k['nama_toko'] ?? '—') ?>
      </div>
      <div class="pemilik-kantin">
        <i class="fa-solid fa-user" style="font-size:10px;"></i>
        <?= htmlspecialchars($k['username'] ?? '—') ?>
        <span style="color:var(--garis);">·</span>
        <!-- badge status buka/tutup toko -->
        <span class="badge <?= $k['status_toko'] === 'buka' ? 'buka' : 'tutup' ?>"
              style="font-size:9px;padding:2px 7px;">
          <?= $k['status_toko'] === 'buka' ? 'Buka' : 'Tutup' ?>
        </span>
      </div>
      <?php else: ?>
      <!-- kantin kosong -->
      <div class="nama-toko-kantin" style="color:#bdbdbd;font-style:italic;">
        — Belum ada penjual —
      </div>
      <div class="pemilik-kantin" style="color:#bdbdbd;">Tersedia untuk penjual baru</div>
      <?php endif; ?>

      <!-- tombol aksi per kartu (disembunyikan saat cetak) -->
      <div class="aksi-kantin takprint">
        <?php if ($adaPemilik): ?>
        <!-- jika terisi: tampilkan link ke detail toko, toggle status, dan kosongkan -->
        <a href="../manajemenpengguna/viewuser.php?id=<?= (int)$k['id_user'] ?>" class="tombolkecil">
          <i class="fa-solid fa-eye"></i> Detail
        </a>
        <!-- form toggle status toko (buka/tutup) tanpa halaman baru -->
        <form method="POST" action="prosestoggletoko.php" style="display:inline;">
          <input type="hidden" name="id_toko" value="<?= (int)$k['id_toko'] ?>">
          <button type="submit"
                  class="tombolkecil <?= $k['status_toko']==='buka' ? '' : 'merah' ?>"
                  title="Klik untuk ubah status toko">
            <?= $k['status_toko']==='buka' ? 'Tutup Toko' : 'Buka Toko' ?>
          </button>
        </form>
        <!-- kosongkan slot: hapus penjual + kosongkan kantin (efek setara).
             arahkan ke hapususer.php karena flow-nya identik. -->
        <a href="../manajemenpengguna/hapususer.php?id=<?= (int)$k['id_user'] ?>" class="tombolkecil merah" title="Kosongkan kantin sekaligus hapus akun penjual">
          <i class="fa-solid fa-store-slash"></i> Kosongkan
        </a>
        <?php else: ?>
        <!-- jika kosong: tombol cepat tambah penjual ke kantin ini -->
        <a href="../manajemenpengguna/tambahuser.php?role=penjual"
           class="tombolkecil" style="background:var(--utama);color:white;border-color:var(--utama);">
          <i class="fa-solid fa-user-plus"></i> Isi Kantin
        </a>
        <?php endif; ?>
      </div>

    </div>
    <?php endforeach; ?>
  </div>

  <?php if (empty($daftarkantin)): ?>
  <!-- tampilan jika tidak ada data kantin sama sekali (belum migrasi database) -->
  <div class="kosong">
    <div class="ikon-kosong"><i class="fa-solid fa-database"></i></div>
    <h3>Data kantin belum tersedia</h3>
    <p>Jalankan file <strong>migrasi_kantin.sql</strong> di phpMyAdmin terlebih dahulu.</p>
  </div>
  <?php endif; ?>

  <!-- tabel hidden untuk ekspor XLS — sumber data tombol "Cetak" di atas -->
  <div id="seksi-kantin" style="display:none;">
    <table>
      <thead>
        <tr>
          <th>No. Kantin</th>
          <th>Nama Toko</th>
          <th>Penjual</th>
          <th>Email</th>
          <th>Status Toko</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($daftarkantin as $k):
          $adaPemilik = !empty($k['id_user']) && !empty($k['username']);
        ?>
        <tr>
          <td>Kantin ke-<?= (int)$k['nomor_kantin'] ?></td>
          <td><?= htmlspecialchars($adaPemilik ? ($k['nama_toko'] ?? '—') : '— Kosong —') ?></td>
          <td><?= htmlspecialchars($adaPemilik ? $k['username'] : '—') ?></td>
          <td><?= htmlspecialchars($adaPemilik ? ($k['email'] ?? '—') : '—') ?></td>
          <td><?= $adaPemilik ? ($k['status_toko'] === 'buka' ? 'Buka' : 'Tutup') : 'Kosong' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</main>

<script>
/* ekspor XLS (HTML-in-Excel) — tabel bergaris dengan identitas. */
var IDENTITAS = {
  judul:    'Status Kantin',
  ringkasan: '<?= (int)$terisi ?> terisi / <?= (int)$kosong ?> kosong dari <?= (int)$totalkantin ?> slot',
};
function buildIdentitasHtml(j) {
  var tgl = new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});
  var lbl = 'border:1px solid #999;padding:6pt 10pt;background:#F8EBF1;font-weight:bold;width:160px;';
  var nil = 'border:1px solid #999;padding:6pt 10pt;';
  var h = '<table style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:11pt;margin-bottom:10pt;width:100%;">';
  h += '<tr><td colspan="2" style="background:#643843;color:white;font-weight:bold;font-size:14pt;text-align:center;padding:10pt;border:1px solid #444;">jajankita &mdash; ' + IDENTITAS.judul + '</td></tr>';
  if (j) h += '<tr><td style="'+lbl+'">Section</td><td style="'+nil+'">' + j + '</td></tr>';
  h += '<tr><td style="'+lbl+'">Ringkasan</td><td style="'+nil+'">' + IDENTITAS.ringkasan + '</td></tr>';
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
  unduhXls(buildIdentitasHtml(IDENTITAS.judul) + tableToBorderedHtml(t), namafile);
}
</script>
</body>
</html>
