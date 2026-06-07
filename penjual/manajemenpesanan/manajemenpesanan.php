<?php
/* halaman manajemen pesanan penjual.
   menampilkan semua pesanan masuk dengan alur status:
   menunggu → diproses → siap diambil → selesai (atau dibatalkan) */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';
// batalkan otomatis pesanan "Menunggu" yang sudah lewat hari (sekalian kembalikan stok)
include '../../3. komponen/autobatalpesanan.php';

// ambil id toko dan id penjual dari session
$idtoko    = (int)$_SESSION['id_toko'];
$idpenjual = (int)$_SESSION['id_user'];

// tandai halaman aktif untuk navbar
$halamansaatini = 'manajemenpesanan';

// ambil parameter filter status dan kata cari dari URL
$filter = $_GET['filter'] ?? 'Menunggu'; // default tampilkan pesanan menunggu
$cari   = trim($_GET['cari'] ?? '');

// id pesanan yang akan dibatalkan (0 = tidak ada) — dipakai modal konfirmasi css :target
$batalid = (int)($_GET['batal'] ?? 0);

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
    $qh = $conn->prepare("SELECT COUNT(*) FROM tb_order WHERE id_penjual=? AND status_order=? AND deleted=0");
    $qh->bind_param("is", $idpenjual, $st); $qh->execute();
    $hitungstatus[$st] = (int)$qh->get_result()->fetch_row()[0]; $qh->close();
}
// jumlah "semua" adalah total dari semua status
$hitungstatus['Semua'] = array_sum($hitungstatus);

// bangun kondisi query pesanan berdasarkan filter dan pencarian
$where = "o.id_penjual=$idpenjual AND o.deleted=0";
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
   urutan: FIELD() menempatkan status sesuai alur (Menunggu → Diproses → Siap Diambil
   → Selesai → Dibatalkan). lalu tanggal_order ASC = yang paling AWAL dipesan tampil di
   atas, supaya penjual memproses pesanan yang lebih dulu masuk dulu (FIFO/antrian). */
$hasilpesanan = $conn->query("SELECT o.*, u.username, u.email
                               FROM tb_order o
                               JOIN tb_user u ON o.id_user=u.id_user
                               WHERE $where
                               ORDER BY FIELD(o.status_order,'Menunggu','Diproses','Siap Diambil','Selesai','Dibatalkan'), o.tanggal_order ASC");

// kembalikan nama kelas css berdasarkan status (untuk pewarnaan kartu dan badge)
function kelasstatus(string $s): string {
    return match($s) {
        'Menunggu' => 'menunggu', 'Diproses' => 'diproses',
        'Siap Diambil' => 'siap', 'Selesai' => 'selesai',
        default => 'dibatalkan',
    };
}

/* jika ada ?batal=ID, ambil data pesanan tsb untuk ditampilkan di modal konfirmasi.
   hanya valid kalau pesanan milik penjual ini dan statusnya masih bisa dibatalkan
   (Menunggu/Diproses). $databatal null = modal tidak dirender. */
$databatal = null;
if ($batalid > 0) {
    $qb = $conn->prepare("SELECT id_order, status_order FROM tb_order
                          WHERE id_order=? AND id_penjual=? AND deleted=0
                            AND status_order IN ('Menunggu','Diproses')");
    $qb->bind_param("ii", $batalid, $idpenjual); $qb->execute();
    $databatal = $qb->get_result()->fetch_assoc(); $qb->close();
}

// url dasar halaman ini (tanpa parameter batal) — dipakai untuk menutup modal
$urldasar = 'manajemenpesanan.php?filter=' . urlencode($filter) . ($cari !== '' ? '&cari=' . urlencode($cari) : '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan Masuk - jajankita</title>
<!-- ?v=filemtime → cache-busting: browser ambil ulang penjual.css tiap kali file berubah,
     jadi perubahan style langsung kelihatan tanpa perlu hard-refresh manual -->
<link rel="stylesheet" href="../../3. komponen/penjual.css?v=<?= @filemtime(__DIR__ . '/../../3. komponen/penjual.css') ?>">
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

  <!-- daftar pesanan — tampilan baris ringkas (bukan kotak), tampil jika ada data -->
  <?php if ($hasilpesanan && $hasilpesanan->num_rows > 0): ?>
  <div class="list-pesanan">
    <?php while ($pesanan = $hasilpesanan->fetch_assoc()):
      // format nomor pesanan: EK-000001, EK-000002, dst
      $nomer  = 'EK-' . str_pad($pesanan['id_order'], 6, '0', STR_PAD_LEFT);
      $kelas  = kelasstatus($pesanan['status_order']);
      // ambil detail item pesanan (nama menu, jumlah, harga satuan, subtotal)
      $qd = $conn->prepare("SELECT d.jumlah, d.harga_satuan, d.subtotal, m.nama_menu FROM tb_detail_order d JOIN tb_menu m ON d.id_menu=m.id_menu WHERE d.id_order=? AND d.deleted=0");
      $qd->bind_param("i", $pesanan['id_order']); $qd->execute();
      $items = $qd->get_result()->fetch_all(MYSQLI_ASSOC); $qd->close();
      // ringkas item jadi satu baris teks: "2x Nasi Goreng, 1x Es Teh"
      $ringkasitem = [];
      foreach ($items as $it) $ringkasitem[] = $it['jumlah'] . 'x ' . $it['nama_menu'];
      $ringkasitem = implode(', ', $ringkasitem);
    ?>
    <!-- satu baris pesanan — kelas css dari status (untuk warna garis kiri) -->
    <div class="baris-pesanan <?= $kelas ?>">

      <!-- info pesanan (kiri): nomor + status + waktu, lalu pembeli + item + total -->
      <div class="info-pesanan">
        <div class="judul-pesanan">
          <span class="badge <?= $kelas ?>"><?= $pesanan['status_order'] ?></span>
          <span class="nomer-pesanan"><?= $nomer ?></span>
          <span class="tanggal-pesanan"><i class="fa-regular fa-clock" style="font-size:10px;"></i> <?= date('d M Y, H:i', strtotime($pesanan['tanggal_order'])) ?></span>
        </div>
        <div class="detail-pesanan">
          <span><i class="fa-solid fa-user" style="font-size:10px;"></i> <?= htmlspecialchars($pesanan['username']) ?></span>
          <span class="pisah">·</span>
          <span><?= htmlspecialchars($ringkasitem) ?></span>
          <span class="pisah">·</span>
          <span class="total-baris">Rp <?= number_format($pesanan['total_harga'],0,',','.') ?></span>
          <span class="pisah">·</span>
          <span><i class="fa-solid fa-wallet" style="font-size:10px;"></i> <?= htmlspecialchars($pesanan['metode_pembayaran']) ?></span>
        </div>
        <?php if (!empty($pesanan['catatan'])): ?>
        <!-- catatan khusus dari pembeli (jika ada) -->
        <div class="catatan-baris">
          <i class="fa-solid fa-note-sticky" style="font-size:10px;"></i> <?= htmlspecialchars($pesanan['catatan']) ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- tombol aksi (kanan) — berbeda tergantung status pesanan saat ini -->
      <div class="aksi-pesanan">

        <?php
        // tombol chat tersedia selama pesanan aktif (Menunggu/Diproses/Siap Diambil)
        // chat tertutup otomatis saat status Selesai/Dibatalkan
        $bisachat = in_array($pesanan['status_order'], ['Menunggu','Diproses','Siap Diambil'], true);
        ?>
        <?php if ($bisachat): ?>
        <!-- #latest = anchor di halaman chat supaya browser auto-scroll ke pesan terbaru tanpa JS -->
        <a href="chat.php?id_order=<?= $pesanan['id_order'] ?>#latest" class="tombolkecil">
          <i class="fa-solid fa-comments"></i> Chat Pembeli
        </a>
        <?php endif; ?>

        <?php if ($pesanan['status_order'] === 'Menunggu'): ?>
          <!-- pesanan menunggu: bisa diproses atau dibatalkan -->
          <a href="prosesmanajemenpesanan.php?aksi=proses&id=<?= $pesanan['id_order'] ?>&filter=<?= urlencode($filter) ?>"
             class="tombolkecil biru">
            <i class="fa-solid fa-fire-burner"></i> Proses
          </a>
          <!-- buka modal konfirmasi dulu via css :target sebelum benar-benar dibatalkan -->
          <a href="manajemenpesanan.php?filter=<?= urlencode($filter) ?><?= $cari !== '' ? '&cari='.urlencode($cari) : '' ?>&batal=<?= $pesanan['id_order'] ?>#konfirm-batal"
             class="tombolkecil merah">
            <i class="fa-solid fa-xmark"></i> Batalkan
          </a>
          <a href="struk.php?id=<?= $pesanan['id_order'] ?>&filter=<?= urlencode($filter) ?><?= $cari !== '' ? '&cari='.urlencode($cari) : '' ?>" class="tombolkecil hijau">
            <i class="fa-solid fa-print"></i> Struk
          </a>

        <?php elseif ($pesanan['status_order'] === 'Diproses'): ?>
          <!-- pesanan sedang diproses: bisa ditandai siap diambil atau dibatalkan -->
          <a href="prosesmanajemenpesanan.php?aksi=siap&id=<?= $pesanan['id_order'] ?>&filter=<?= urlencode($filter) ?>"
             class="tombolkecil hijau">
            <i class="fa-solid fa-bell"></i> Siap Diambil
          </a>
          <a href="struk.php?id=<?= $pesanan['id_order'] ?>&filter=<?= urlencode($filter) ?><?= $cari !== '' ? '&cari='.urlencode($cari) : '' ?>" class="tombolkecil hijau">
            <i class="fa-solid fa-print"></i> Struk
          </a>

        <?php elseif ($pesanan['status_order'] === 'Siap Diambil'): ?>
          <!-- pesanan siap diambil: hanya bisa diselesaikan -->
          <a href="prosesmanajemenpesanan.php?aksi=selesai&id=<?= $pesanan['id_order'] ?>&filter=<?= urlencode($filter) ?>"
             class="tombolkecil aktif-kecil">
            <i class="fa-solid fa-circle-check"></i> Selesai
          </a>
          <a href="struk.php?id=<?= $pesanan['id_order'] ?>&filter=<?= urlencode($filter) ?><?= $cari !== '' ? '&cari='.urlencode($cari) : '' ?>" class="tombolkecil hijau">
            <i class="fa-solid fa-print"></i> Struk
          </a>

        <?php elseif ($pesanan['status_order'] === 'Selesai'): ?>
          <!-- pesanan selesai: hanya bisa menstruk -->
          <a href="struk.php?id=<?= $pesanan['id_order'] ?>&filter=<?= urlencode($filter) ?><?= $cari !== '' ? '&cari='.urlencode($cari) : '' ?>" class="tombolkecil hijau">
            <i class="fa-solid fa-print"></i> Struk
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

<!-- modal konfirmasi batalkan pesanan — hanya dirender jika ada ?batal= valid.
     pakai css :target (tanpa javascript): modal muncul saat url berisi #konfirm-batal -->
<?php if ($databatal):
  $nomerbatal = 'EK-' . str_pad($databatal['id_order'], 6, '0', STR_PAD_LEFT);
?>
<div class="modaloverlay" id="konfirm-batal">
  <!-- klik area di luar modal → kembali ke url dasar (tutup modal) -->
  <a href="<?= $urldasar ?>" class="penutup-modal"></a>
  <div class="isimodal" style="max-width:380px;text-align:center;position:relative;z-index:1;">
    <div style="font-size:44px;color:var(--gagal,#dc2626);margin-bottom:10px;">
      <i class="fa-solid fa-circle-xmark"></i>
    </div>
    <div style="font-size:17px;font-weight:800;color:var(--utama);margin-bottom:8px;">Batalkan Pesanan?</div>
    <div style="font-size:13px;color:var(--tekssamar);margin-bottom:20px;">
      Pesanan <strong><?= $nomerbatal ?></strong> akan dibatalkan dan stok menu dikembalikan. Tindakan ini tidak bisa diurungkan.
    </div>
    <!-- konfirmasi: klik tombol ini baru benar-benar mengirim ke proses batal -->
    <a href="prosesmanajemenpesanan.php?aksi=batal&id=<?= (int)$databatal['id_order'] ?>&filter=<?= urlencode($filter) ?>"
       class="tombolutama blok" style="margin-bottom:10px;background:var(--gagal,#dc2626);border-color:var(--gagal,#dc2626);">
      <i class="fa-solid fa-xmark"></i> Ya, Batalkan
    </a>
    <a href="<?= $urldasar ?>" class="tombolringan blok">Kembali</a>
  </div>
</div>
<?php endif; ?>

</body>
</html>
