<?php
/* halaman manajemen pesanan penjual.
   menampilkan semua pesanan masuk dengan alur status:
   menunggu → diproses → siap diambil → selesai (atau dibatalkan) */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

// ambil id toko dari session
$idtoko = (int)$_SESSION['id_toko'];

// tandai halaman aktif untuk navbar
$halamansaatini = 'manajemenpesanan';

// ambil parameter filter status dan kata cari dari URL
$filter = $_GET['filter'] ?? 'Menunggu'; // default tampilkan pesanan menunggu
$cari   = trim($_GET['cari'] ?? '');

// ambil flash message dari session jika ada (pesan sukses/gagal dari proses sebelumnya)
$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']); // hapus agar tidak muncul lagi saat refresh
}

// daftar status yang valid untuk filter
$statuslist = ['Semua','Menunggu','Diproses','Siap Diambil','Selesai','Dibatalkan'];

// validasi filter — reset ke "Semua" jika nilainya tidak dikenal
if (!in_array($filter, $statuslist)) $filter = 'Semua';

// hitung jumlah pesanan per status untuk ditampilkan sebagai badge angka di tab filter
$hitungstatus = [];
foreach (['Menunggu','Diproses','Siap Diambil','Selesai','Dibatalkan'] as $st) {
    $qh = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_toko=? AND status_order=? AND deleted=0");
    $qh->bind_param("is", $idtoko, $st); $qh->execute();
    $hitungstatus[$st] = (int)$qh->get_result()->fetch_row()[0]; $qh->close();
}
// jumlah "semua" adalah total dari semua status
$hitungstatus['Semua'] = array_sum($hitungstatus);

// bangun kondisi query pesanan berdasarkan filter dan pencarian
$where = "o.id_toko=$idtoko AND o.deleted=0";
if ($filter !== 'Semua') {
    // escape untuk mencegah sql injection karena nilai langsung dimasukkan ke string query
    $filteraman = $conn->real_escape_string($filter);
    $where .= " AND o.status_order='$filteraman'";
}
if ($cari !== '') {
    // cari berdasarkan nomor pesanan atau nama pembeli
    $cariaman = $conn->real_escape_string($cari);
    $where .= " AND (o.id_order LIKE '%$cariaman%' OR u.username LIKE '%$cariaman%')";
}

/* ambil pesanan beserta nama pembeli.
   FIELD() digunakan untuk urutan kustom: menunggu tampil paling atas, dibatalkan paling bawah */
$hasilpesanan = $conn->query("SELECT o.*, u.username, u.email
                               FROM tb_order o
                               JOIN tb_user u ON o.id_user=u.id_user
                               WHERE $where
                               ORDER BY FIELD(o.status_order,'Menunggu','Diproses','Siap Diambil','Selesai','Dibatalkan'), o.tanggal_order DESC");

// kembalikan nama kelas css berdasarkan status (untuk pewarnaan kartu dan badge)
function kelasstatus(string $s): string {
    return match($s) {
        'Menunggu' => 'menunggu', 'Diproses' => 'diproses',
        'Siap Diambil' => 'siap', 'Selesai' => 'selesai',
        default => 'dibatalkan',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan Masuk - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten">

  <!-- header halaman dengan kotak pencarian pesanan -->
  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-clipboard-list"></i> Pesanan Masuk</h1>
      <p>Kelola semua pesanan dari pembeli</p>
    </div>
    <!-- form pencarian berdasarkan nomor pesanan atau nama pembeli -->
    <form method="GET" action="manajemenpesanan.php">
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
      <div class="kotakcari">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
               placeholder="Cari pesanan atau nama pembeli...">
        <button type="submit" class="tombolcari"><i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </form>
  </div>

  <!-- tampilkan flash message sukses atau gagal jika ada -->
  <?php if ($flashpesan): ?>
  <div class="flashpesan flash<?= $flashjenis ?>">
    <i class="fa-solid fa-<?= $flashjenis === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
  <?php endif; ?>

  <!-- tab filter status pesanan — setiap tab menampilkan jumlah pesanan di badge -->
  <div class="filter-bar" style="margin-bottom:20px;">
    <?php foreach ($statuslist as $st): ?>
    <a href="manajemenpesanan.php?filter=<?= urlencode($st) ?>"
       class="chip-filter <?= $filter === $st ? 'aktif' : '' ?>">
      <?= $st ?>
      <?php if (isset($hitungstatus[$st]) && $hitungstatus[$st] > 0): ?>
        <!-- badge angka: background putih transparan jika tab aktif, warna utama jika tidak aktif -->
        <span style="background:<?= $filter===$st ? 'rgba(255,255,255,.25)' : 'var(--utama)' ?>;color:<?= $filter===$st?'white':'var(--putihbg)' ?>;border-radius:9999px;padding:1px 7px;font-size:11px;margin-left:4px;">
          <?= $hitungstatus[$st] ?>
        </span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- grid kartu pesanan — tampil jika ada data -->
  <?php if ($hasilpesanan && $hasilpesanan->num_rows > 0): ?>
  <div class="grid-pesanan">
    <?php while ($pesanan = $hasilpesanan->fetch_assoc()):
      // format nomor pesanan: EK-000001, EK-000002, dst
      $nomer  = 'EK-' . str_pad($pesanan['id_order'], 6, '0', STR_PAD_LEFT);
      $kelas  = kelasstatus($pesanan['status_order']);
      // ambil detail item pesanan (nama menu, jumlah, harga satuan, subtotal)
      $qd = $conn->prepare("SELECT d.jumlah, d.harga_satuan, d.subtotal, m.nama_menu FROM tb_detail_order d JOIN tb_menu m ON d.id_menu=m.id_menu WHERE d.id_order=? AND d.deleted=0");
      $qd->bind_param("i", $pesanan['id_order']); $qd->execute();
      $items = $qd->get_result()->fetch_all(MYSQLI_ASSOC); $qd->close();
    ?>
    <!-- kartu pesanan — kelas css diambil dari status (untuk warna border kiri) -->
    <div class="kartu-pesanan <?= $kelas ?>">

      <!-- bagian atas kartu: nomor pesanan, waktu, dan badge status -->
      <div class="atas-pesanan">
        <div>
          <div class="nomer-pesanan"><?= $nomer ?></div>
          <div class="tanggal-pesanan">
            <?= date('d M Y, H:i', strtotime($pesanan['tanggal_order'])) ?>
          </div>
        </div>
        <span class="badge <?= $kelas ?>"><?= $pesanan['status_order'] ?></span>
      </div>

      <!-- nama pembeli -->
      <div class="pembeli-pesanan">
        <i class="fa-solid fa-user" style="font-size:11px;"></i>
        <?= htmlspecialchars($pesanan['username']) ?>
      </div>

      <!-- catatan khusus dari pembeli (jika ada) -->
      <?php if (!empty($pesanan['catatan'])): ?>
      <div class="catatan-pesanan">
        <i class="fa-solid fa-note-sticky" style="font-size:11px;"></i>
        <?= htmlspecialchars($pesanan['catatan']) ?>
      </div>
      <?php endif; ?>

      <!-- daftar item yang dipesan beserta subtotal per item -->
      <div class="item-pesanan">
        <?php foreach ($items as $it): ?>
        <div class="baris-item-pesanan">
          <span><?= $it['jumlah'] ?>x <?= htmlspecialchars($it['nama_menu']) ?></span>
          <span>Rp <?= number_format($it['subtotal'],0,',','.') ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- total harga dan metode pembayaran -->
      <div class="total-pesanan">
        Total: Rp <?= number_format($pesanan['total_harga'],0,',','.') ?>
      </div>
      <div class="metode-pesanan">
        <i class="fa-solid fa-wallet" style="font-size:10px;"></i>
        <?= htmlspecialchars($pesanan['metode_pembayaran']) ?>
      </div>

      <!-- tombol aksi yang ditampilkan berbeda-beda tergantung status pesanan saat ini -->
      <div class="aksi-pesanan">

        <?php if ($pesanan['status_order'] === 'Menunggu'): ?>
          <!-- pesanan menunggu: bisa diproses atau dibatalkan -->
          <a href="prosesmanajemenpesanan.php?aksi=proses&id=<?= $pesanan['id_order'] ?>&filter=<?= urlencode($filter) ?>"
             class="tombolkecil biru">
            <i class="fa-solid fa-fire-burner"></i> Proses
          </a>
          <a href="prosesmanajemenpesanan.php?aksi=batal&id=<?= $pesanan['id_order'] ?>&filter=<?= urlencode($filter) ?>"
             class="tombolkecil merah">
            <i class="fa-solid fa-xmark"></i> Batalkan
          </a>

        <?php elseif ($pesanan['status_order'] === 'Diproses'): ?>
          <!-- pesanan sedang diproses: bisa ditandai siap diambil atau dibatalkan -->
          <a href="prosesmanajemenpesanan.php?aksi=siap&id=<?= $pesanan['id_order'] ?>&filter=<?= urlencode($filter) ?>"
             class="tombolkecil hijau">
            <i class="fa-solid fa-bell"></i> Siap Diambil
          </a>
          <a href="prosesmanajemenpesanan.php?aksi=batal&id=<?= $pesanan['id_order'] ?>&filter=<?= urlencode($filter) ?>"
             class="tombolkecil merah">
            <i class="fa-solid fa-xmark"></i> Batalkan
          </a>

        <?php elseif ($pesanan['status_order'] === 'Siap Diambil'): ?>
          <!-- pesanan siap diambil: hanya bisa diselesaikan -->
          <a href="prosesmanajemenpesanan.php?aksi=selesai&id=<?= $pesanan['id_order'] ?>&filter=<?= urlencode($filter) ?>"
             class="tombolkecil aktif-kecil">
            <i class="fa-solid fa-circle-check"></i> Selesai
          </a>

        <?php elseif ($pesanan['status_order'] === 'Selesai'): ?>
          <!-- pesanan selesai: hanya bisa mencetak struk -->
          <a href="struk.php?id=<?= $pesanan['id_order'] ?>" class="tombolkecil hijau">
            <i class="fa-solid fa-print"></i> Cetak Struk
          </a>
        <?php endif; ?>

      </div>

    </div>
    <?php endwhile; ?>
  </div>

  <?php else: ?>
  <!-- tampilan kosong jika tidak ada pesanan atau pencarian tidak menemukan hasil -->
  <div class="kosong">
    <div class="ikon-kosong"><i class="fa-solid fa-clipboard-list"></i></div>
    <h3>Tidak ada pesanan</h3>
    <p><?= $cari ? 'Tidak ada hasil untuk pencarian tersebut' : 'Belum ada pesanan ' . strtolower($filter) ?></p>
    <?php if ($cari): ?>
    <a href="manajemenpesanan.php?filter=<?= urlencode($filter) ?>" class="tombolringan">Hapus Pencarian</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</main>

</body>
</html>
