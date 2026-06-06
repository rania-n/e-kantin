<?php
/* halaman kelola menu penjual.
   menampilkan daftar menu milik toko dalam format grid kartu.
   modal tambah, edit, dan konfirmasi hapus menggunakan CSS :target (tanpa javascript) */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

// ambil id toko dari session
$idtoko = (int)$_SESSION['id_toko'];

// tandai halaman aktif untuk navbar
$halamansaatini = 'manajemenmenu';

// ambil parameter filter, pencarian, dan id untuk aksi edit/hapus dari URL
$filter   = $_GET['filter'] ?? 'Semua';
$cari     = trim($_GET['cari'] ?? '');
$editid   = (int)($_GET['edit']   ?? 0); // id menu yang akan diedit (0 = tidak ada)
$hapusid  = (int)($_GET['hapus']  ?? 0); // id menu yang akan dihapus (0 = tidak ada)
$toggleid = (int)($_GET['toggle'] ?? 0); // id menu yang akan diubah status (0 = tidak ada)

// ambil flash message dari session jika ada (pesan sukses/gagal dari proses sebelumnya)
$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']); // hapus flash setelah dibaca agar tidak muncul dua kali
}

// daftar kategori menu yang tersedia
$kategorilist = ['Makanan Berat','Makanan Ringan','Makanan Sehat','Minuman Ringan','Minuman Sehat'];

// validasi: jika filter bukan nilai yang dikenal, reset ke "Semua"
if (!in_array($filter, array_merge(['Semua'], $kategorilist))) $filter = 'Semua';

// bangun kondisi query menu — hanya tampilkan menu milik toko ini yang belum dihapus
$kondisi = "id_toko=$idtoko AND deleted=0";
if ($filter !== 'Semua') {
    // escape nilai filter untuk mencegah sql injection
    $filteraman = $conn->real_escape_string($filter);
    $kondisi .= " AND kategori='$filteraman'";
}
if ($cari !== '') {
    // cari berdasarkan nama menu (pakai LIKE untuk pencarian parsial)
    $cariaman = $conn->real_escape_string($cari);
    $kondisi .= " AND nama_menu LIKE '%$cariaman%'";
}

// jalankan query dengan kondisi yang sudah dibangun, urutkan terbaru di atas
$hasilmenu = $conn->query("SELECT * FROM tb_menu WHERE $kondisi ORDER BY created DESC");

// ambil data menu yang akan diedit (hanya jika ada parameter ?edit= di URL)
$dataedit = null;
if ($editid > 0) {
    $qe = $conn->prepare("SELECT * FROM tb_menu WHERE id_menu=? AND id_toko=? AND deleted=0");
    $qe->bind_param("ii", $editid, $idtoko);
    $qe->execute();
    $dataedit = $qe->get_result()->fetch_assoc(); // null jika tidak ditemukan
    $qe->close();
}

// ambil data menu yang akan di-toggle status (hanya jika ada parameter ?toggle= di URL)
// dipakai untuk modal konfirmasi sebelum benar-benar mengubah status aktif/nonaktif
$datatoggle = null;
if ($toggleid > 0) {
    $qt = $conn->prepare("SELECT id_menu, nama_menu, status FROM tb_menu WHERE id_menu=? AND id_toko=? AND deleted=0");
    $qt->bind_param("ii", $toggleid, $idtoko);
    $qt->execute();
    $datatoggle = $qt->get_result()->fetch_assoc();
    $qt->close();
}

// url dasar halaman ini tanpa fragment (#) dan tanpa parameter edit/hapus
// dipakai untuk link "tutup modal"
$urldasar = 'manajemenmenu.php?filter=' . urlencode($filter) . ($cari ? '&cari=' . urlencode($cari) : '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Menu - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten">

  <!-- header halaman dengan kotak pencarian dan tombol tambah menu -->
  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-bowl-food"></i> Kelola Menu</h1>
      <p>Daftar menu yang dijual di <?= htmlspecialchars($_SESSION['nama_toko']??'') ?></p>
    </div>
    <div class="grup-aksi">
      <!-- form pencarian menu berdasarkan nama -->
      <form method="GET" action="manajemenmenu.php">
        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
        <div class="kotakcari">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                 placeholder="Cari nama menu...">
          <button type="submit" class="tombolcari"><i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </form>
      <!-- link ke #modal-tambah akan membuka modal melalui css :target -->
      <a href="#modal-tambah" class="tombolutama">
        <i class="fa-solid fa-plus"></i> Tambah Menu
      </a>
    </div>
  </div>

  <!-- tampilkan flash message sukses atau gagal jika ada -->
  <?php if ($flashpesan): ?>
  <div class="flashpesan flash<?= $flashjenis ?>">
    <i class="fa-solid fa-<?= $flashjenis === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
  <?php endif; ?>

  <!-- filter tab kategori menu -->
  <div class="filter-bar">
    <?php $paramcari = $cari ? '&cari=' . urlencode($cari) : ''; ?>
    <!-- tab "semua" — aktif jika filter saat ini adalah "Semua" -->
    <a href="manajemenmenu.php<?= $cari ? '?cari='.urlencode($cari) : '' ?>"
       class="chip-filter <?= $filter === 'Semua' ? 'aktif' : '' ?>">
      Semua
    </a>
    <?php foreach ($kategorilist as $k): ?>
    <a href="manajemenmenu.php?filter=<?= urlencode($k) ?><?= $paramcari ?>"
       class="chip-filter <?= $filter === $k ? 'aktif' : '' ?>">
      <?= $k ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- grid kartu menu — tampil jika ada data -->
  <?php if ($hasilmenu && $hasilmenu->num_rows > 0): ?>
  <div class="grid-menu">
    <?php while ($menu = $hasilmenu->fetch_assoc()): ?>
    <div class="kartu-menu">
      <!-- gambar menu; jika gambar tidak ada, tampilkan area abu-abu -->
      <img class="gambar-menu"
           src="../../2. aset/katalog/<?= htmlspecialchars($menu['foto']) ?>"
           alt="<?= htmlspecialchars($menu['nama_menu']) ?>"
           onerror="this.style.background='var(--latar)'; this.style.height='140px'">
      <div class="isi-kartu">
        <div class="nama-menu"><?= htmlspecialchars($menu['nama_menu']) ?></div>
        <div class="kategori-menu"><?= htmlspecialchars($menu['kategori']) ?></div>
        <div class="harga-menu">Rp <?= number_format($menu['harga'],0,',','.') ?></div>
        <div class="stok-menu">
          <i class="fa-solid fa-box" style="font-size:10px;"></i>
          Stok: <strong><?= $menu['stok'] ?></strong>
          <?php if ($menu['stok'] <= 5 && $menu['stok'] > 0): ?>
          <!-- peringatan stok hampir habis jika stok 1-5 -->
          <span style="color:var(--tunggu);font-size:10px;"> (hampir habis)</span>
          <?php elseif ($menu['stok'] == 0): ?>
          <span style="color:var(--gagal);font-size:10px;"> (habis)</span>
          <?php endif; ?>
        </div>
        <div style="margin-top:6px;">
          <!-- badge status menu: aktif (hijau) atau nonaktif (abu) -->
          <span class="badge <?= $menu['status'] === 'aktif' ? 'siap' : 'selesai' ?>">
            <?= $menu['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?>
          </span>
        </div>
      </div>
      <div class="aksi-menu">
        <!-- tombol edit: reload halaman dengan ?edit=ID lalu buka modal edit via css :target -->
        <a href="manajemenmenu.php?edit=<?= $menu['id_menu'] ?>&filter=<?= urlencode($filter) ?><?= $cari ? '&cari='.urlencode($cari) : '' ?>#modal-edit"
           class="tombolkecil">
          <i class="fa-solid fa-pen"></i> Edit
        </a>
        <!-- toggle aktif/nonaktif: buka modal konfirmasi dulu via css :target sebelum diubah -->
        <a href="manajemenmenu.php?toggle=<?= $menu['id_menu'] ?>&filter=<?= urlencode($filter) ?><?= $cari ? '&cari='.urlencode($cari) : '' ?>#konfirm-toggle"
           class="tombolkecil <?= $menu['status'] === 'aktif' ? 'kuning' : 'hijau' ?>">
          <i class="fa-solid fa-<?= $menu['status'] === 'aktif' ? 'ban' : 'check' ?>"></i>
          <?= $menu['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
        </a>
        <!-- tombol hapus: buka modal konfirmasi dulu via css :target sebelum benar-benar menghapus -->
        <a href="manajemenmenu.php?hapus=<?= $menu['id_menu'] ?>&filter=<?= urlencode($filter) ?><?= $cari ? '&cari='.urlencode($cari) : '' ?>#konfirm-hapus"
           class="tombolkecil merah">
          <i class="fa-solid fa-trash"></i>
        </a>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php else: ?>
  <!-- tampilan kosong jika tidak ada menu atau pencarian tidak menemukan hasil -->
  <div class="kosong">
    <div class="ikon-kosong"><i class="fa-solid fa-bowl-food"></i></div>
    <h3><?= $cari ? 'Menu tidak ditemukan' : 'Belum ada menu' ?></h3>
    <p><?= $cari ? 'Tidak ada menu dengan nama "' . htmlspecialchars($cari) . '"' : 'Tambahkan menu pertamamu untuk mulai menerima pesanan' ?></p>
    <?php if ($cari): ?>
    <a href="manajemenmenu.php?filter=<?= urlencode($filter) ?>" class="tombolringan">Hapus Pencarian</a>
    <?php else: ?>
    <a href="#modal-tambah" class="tombolutama">
      <i class="fa-solid fa-plus"></i> Tambah Menu Sekarang
    </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</main>

<!-- modal tambah menu baru — muncul saat URL berisi fragment #modal-tambah -->
<div class="modaloverlay" id="modal-tambah">
  <!-- klik area di luar modal → kembali ke urldasar (tutup modal) -->
  <a href="<?= $urldasar ?>" class="penutup-modal"></a>
  <div class="isimodal" style="max-width:520px;position:relative;z-index:1;">
    <div class="modal-judul">
      <h2><i class="fa-solid fa-plus"></i> Tambah Menu Baru</h2>
      <a href="<?= $urldasar ?>" class="tombol-tutup-modal">
        <i class="fa-solid fa-xmark"></i>
      </a>
    </div>
    <!-- form tambah menu dikirim ke prosesmanajemenmenu.php, enctype multipart untuk upload foto -->
    <form method="POST" action="prosesmanajemenmenu.php" enctype="multipart/form-data">
      <input type="hidden" name="aksi" value="tambah">
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
      <div class="barisform">
        <div class="kelompokform">
          <label>Nama Menu <span style="color:var(--gagal);">*</span></label>
          <input type="text" name="nama_menu" required maxlength="50" placeholder="Nama menu...">
        </div>
        <div class="kelompokform">
          <label>Kategori <span style="color:var(--gagal);">*</span></label>
          <select name="kategori" required>
            <?php foreach ($kategorilist as $k): ?>
            <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="barisform">
        <div class="kelompokform">
          <label>Harga (Rp) <span style="color:var(--gagal);">*</span></label>
          <input type="number" name="harga" required min="0" max="999999" placeholder="0">
          <small>Minimum Rp 0</small>
        </div>
        <div class="kelompokform">
          <label>Stok Awal <span style="color:var(--gagal);">*</span></label>
          <input type="number" name="stok" required min="0" max="9999" placeholder="0">
          <small>Minimum 0</small>
        </div>
      </div>
      <div class="kelompokform">
        <label>Deskripsi</label>
        <textarea name="deskripsi" rows="2" placeholder="Deskripsi singkat menu..."></textarea>
      </div>
      <div class="kelompokform">
        <label>Foto Menu <span style="color:var(--gagal);">*</span></label>
        <!-- accept membatasi tipe file yang bisa dipilih di dialog file browser -->
        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" required>
        <small>Format: JPG, PNG, WEBP. Maks. 2MB</small>
      </div>
      <div style="display:flex;gap:10px;margin-top:4px;">
        <a href="<?= $urldasar ?>" class="tombolringan">Batal</a>
        <button type="submit" class="tombolutama" style="flex:1;">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Menu
        </button>
      </div>
    </form>
  </div>
</div>

<!-- modal edit menu — muncul saat URL berisi fragment #modal-edit -->
<div class="modaloverlay" id="modal-edit">
  <a href="<?= $urldasar ?>" class="penutup-modal"></a>
  <div class="isimodal" style="max-width:520px;position:relative;z-index:1;">
    <div class="modal-judul">
      <h2><i class="fa-solid fa-pen"></i> Edit Menu</h2>
      <a href="<?= $urldasar ?>" class="tombol-tutup-modal">
        <i class="fa-solid fa-xmark"></i>
      </a>
    </div>
    <?php if ($dataedit): ?>
    <!-- form edit menu — data diisi awal dari $dataedit yang diambil dari database -->
    <form method="POST" action="prosesmanajemenmenu.php" enctype="multipart/form-data">
      <input type="hidden" name="aksi" value="edit">
      <!-- kirim id_menu dan nama file foto lama agar bisa dipakai jika foto tidak diganti -->
      <input type="hidden" name="id_menu" value="<?= $dataedit['id_menu'] ?>">
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
      <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($dataedit['foto']) ?>">
      <div class="barisform">
        <div class="kelompokform">
          <label>Nama Menu <span style="color:var(--gagal);">*</span></label>
          <input type="text" name="nama_menu" required maxlength="50"
                 value="<?= htmlspecialchars($dataedit['nama_menu']) ?>">
        </div>
        <div class="kelompokform">
          <label>Kategori <span style="color:var(--gagal);">*</span></label>
          <select name="kategori" required>
            <?php foreach ($kategorilist as $k): ?>
            <!-- tandai "selected" pada kategori yang sesuai data saat ini -->
            <option value="<?= htmlspecialchars($k) ?>" <?= $dataedit['kategori'] === $k ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="barisform">
        <div class="kelompokform">
          <label>Harga (Rp) <span style="color:var(--gagal);">*</span></label>
          <input type="number" name="harga" required min="0" max="999999"
                 value="<?= $dataedit['harga'] ?>">
        </div>
        <div class="kelompokform">
          <label>Stok <span style="color:var(--gagal);">*</span></label>
          <input type="number" name="stok" required min="0" max="9999"
                 value="<?= $dataedit['stok'] ?>">
        </div>
      </div>
      <div class="kelompokform">
        <label>Deskripsi</label>
        <textarea name="deskripsi" rows="2"><?= htmlspecialchars($dataedit['deskripsi']) ?></textarea>
      </div>
      <div class="kelompokform">
        <!-- input foto opsional saat edit — jika dikosongkan, foto lama tetap dipakai -->
        <label>Ganti Foto (kosongkan jika tidak ingin ganti)</label>
        <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
        <small>Foto saat ini: <?= htmlspecialchars($dataedit['foto']) ?></small>
      </div>
      <div style="display:flex;gap:10px;margin-top:4px;">
        <a href="<?= $urldasar ?>" class="tombolringan">Batal</a>
        <button type="submit" class="tombolutama" style="flex:1;">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
        </button>
      </div>
    </form>
    <?php else: ?>
    <!-- pesan ini muncul jika seseorang membuka #modal-edit tanpa parameter ?edit= -->
    <p style="text-align:center;color:var(--tekssamar);padding:20px;">Pilih menu yang ingin diedit.</p>
    <?php endif; ?>
  </div>
</div>

<!-- modal konfirmasi aktif/nonaktif menu — hanya dirender jika ada ?toggle= valid -->
<?php if ($datatoggle):
  $akanNonaktif = $datatoggle['status'] === 'aktif';
?>
<div class="modaloverlay" id="konfirm-toggle">
  <a href="<?= $urldasar ?>" class="penutup-modal"></a>
  <div class="isimodal" style="max-width:380px;text-align:center;position:relative;z-index:1;">
    <div style="font-size:42px;color:var(--<?= $akanNonaktif ? 'tunggu' : 'sukses' ?>);margin-bottom:10px;">
      <i class="fa-solid fa-<?= $akanNonaktif ? 'ban' : 'circle-check' ?>"></i>
    </div>
    <div style="font-size:17px;font-weight:800;color:var(--utama);margin-bottom:8px;">
      <?= $akanNonaktif ? 'Nonaktifkan Menu?' : 'Aktifkan Menu?' ?>
    </div>
    <div style="font-size:13px;color:var(--tekssamar);margin-bottom:20px;">
      <?php if ($akanNonaktif): ?>
      Menu <strong><?= htmlspecialchars($datatoggle['nama_menu']) ?></strong> akan disembunyikan dari pembeli sampai diaktifkan lagi.
      <?php else: ?>
      Menu <strong><?= htmlspecialchars($datatoggle['nama_menu']) ?></strong> akan kembali tampil dan bisa dipesan pembeli.
      <?php endif; ?>
    </div>
    <!-- konfirmasi: klik akan mengirim ke prosesmanajemenmenu.php dengan aksi=toggle -->
    <a href="prosesmanajemenmenu.php?aksi=toggle&id=<?= (int)$datatoggle['id_menu'] ?>&filter=<?= urlencode($filter) ?>"
       class="tombolutama blok" style="margin-bottom:10px;background:var(--<?= $akanNonaktif ? 'tunggu' : 'sukses' ?>);">
      <i class="fa-solid fa-<?= $akanNonaktif ? 'ban' : 'check' ?>"></i>
      <?= $akanNonaktif ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' ?>
    </a>
    <a href="<?= $urldasar ?>" class="tombolringan blok">Batal</a>
  </div>
</div>
<?php endif; ?>

<!-- modal konfirmasi hapus — hanya dirender jika ada parameter ?hapus= di URL -->
<?php if ($hapusid > 0): ?>
<div class="modaloverlay" id="konfirm-hapus">
  <a href="<?= $urldasar ?>" class="penutup-modal"></a>
  <div class="isimodal" style="max-width:380px;text-align:center;position:relative;z-index:1;">
    <div style="font-size:44px;color:var(--gagal);margin-bottom:10px;">
      <i class="fa-solid fa-trash"></i>
    </div>
    <div style="font-size:17px;font-weight:800;color:var(--utama);margin-bottom:8px;">Hapus Menu?</div>
    <div style="font-size:13px;color:var(--tekssamar);margin-bottom:20px;">
      Tindakan ini tidak bisa dibatalkan. Menu akan dihapus secara permanen.
    </div>
    <!-- konfirmasi hapus: klik tombol ini akan mengirim ke prosesmanajemenmenu.php dengan aksi=hapus -->
    <a href="prosesmanajemenmenu.php?aksi=hapus&id=<?= $hapusid ?>&filter=<?= urlencode($filter) ?>"
       class="tombolutama blok" style="margin-bottom:10px;background:var(--gagal);">
      <i class="fa-solid fa-trash"></i> Ya, Hapus
    </a>
    <a href="<?= $urldasar ?>" class="tombolringan blok">
      Batal
    </a>
  </div>
</div>
<?php endif; ?>

</body>
</html>
