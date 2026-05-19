<?php
/* ============================================================
   KELOLA MENU PENJUAL
   Modal menggunakan CSS :target — tanpa JavaScript.
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idtoko = (int)$_SESSION['id_toko'];
$halamansaatini = 'manajemenmenu';

// Filter, pencarian, dan ID aksi dari GET
$filter   = $_GET['filter'] ?? 'Semua';
$cari     = trim($_GET['cari'] ?? '');
$editid   = (int)($_GET['edit']  ?? 0);
$hapusid  = (int)($_GET['hapus'] ?? 0);

// Flash message
$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']);
}

// Kategori yang valid
$kategorilist = ['Makanan Berat','Makanan Ringan','Makanan Sehat','Minuman Ringan','Minuman Sehat'];

// Validasi filter
if (!in_array($filter, array_merge(['Semua'], $kategorilist))) $filter = 'Semua';

// Query menu milik toko ini
$kondisi = "id_toko=$idtoko AND deleted=0";
if ($filter !== 'Semua') {
    $filteraman = $conn->real_escape_string($filter);
    $kondisi .= " AND kategori='$filteraman'";
}
if ($cari !== '') {
    $cariaman = $conn->real_escape_string($cari);
    $kondisi .= " AND nama_menu LIKE '%$cariaman%'";
}
$hasilmenu = $conn->query("SELECT * FROM tb_menu WHERE $kondisi ORDER BY created DESC");

// Data edit (jika ada ?edit=)
$dataedit = null;
if ($editid > 0) {
    $qe = $conn->prepare("SELECT * FROM tb_menu WHERE id_menu=? AND id_toko=? AND deleted=0");
    $qe->bind_param("ii", $editid, $idtoko);
    $qe->execute();
    $dataedit = $qe->get_result()->fetch_assoc();
    $qe->close();
}

// URL dasar (tanpa hash dan tanpa edit/hapus params) untuk tutup modal
$urldasar = 'manajemenmenu.php?filter=' . urlencode($filter) . ($cari ? '&cari=' . urlencode($cari) : '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Menu - eKantin</title>
<link rel="stylesheet" href="../../3. komponen/penjual.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include '../../3. komponen/navbarpenjual.php'; ?>

<main class="konten">

  <div class="header-halaman">
    <div class="kiri">
      <h1><i class="fa-solid fa-bowl-food"></i> Kelola Menu</h1>
      <p>Daftar menu yang dijual di <?= htmlspecialchars($_SESSION['nama_toko']??'') ?></p>
    </div>
    <div class="grup-aksi">
      <form method="GET" action="manajemenmenu.php">
        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
        <div class="kotakcari">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                 placeholder="Cari nama menu...">
          <button type="submit" class="tombolcari"><i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </form>
      <a href="#modal-tambah" class="tombolutama">
        <i class="fa-solid fa-plus"></i> Tambah Menu
      </a>
    </div>
  </div>

  <?php if ($flashpesan): ?>
  <div class="flashpesan flash<?= $flashjenis ?>">
    <i class="fa-solid fa-<?= $flashjenis === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
  <?php endif; ?>

  <!-- Filter kategori -->
  <div class="filter-bar">
    <?php $paramcari = $cari ? '&cari=' . urlencode($cari) : ''; ?>
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

  <!-- Grid menu -->
  <?php if ($hasilmenu && $hasilmenu->num_rows > 0): ?>
  <div class="grid-menu">
    <?php while ($menu = $hasilmenu->fetch_assoc()): ?>
    <div class="kartu-menu">
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
          <span style="color:var(--tunggu);font-size:10px;"> (hampir habis)</span>
          <?php elseif ($menu['stok'] == 0): ?>
          <span style="color:var(--gagal);font-size:10px;"> (habis)</span>
          <?php endif; ?>
        </div>
        <div style="margin-top:6px;">
          <span class="badge <?= $menu['status'] === 'aktif' ? 'siap' : 'selesai' ?>">
            <?= $menu['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?>
          </span>
        </div>
      </div>
      <div class="aksi-menu">
        <!-- Edit: reload halaman dengan ?edit=ID#modal-edit agar data edit dimuat -->
        <a href="manajemenmenu.php?edit=<?= $menu['id_menu'] ?>&filter=<?= urlencode($filter) ?><?= $cari ? '&cari='.urlencode($cari) : '' ?>#modal-edit"
           class="tombolkecil">
          <i class="fa-solid fa-pen"></i> Edit
        </a>
        <!-- Toggle status: langsung tanpa konfirmasi (bisa dibalik) -->
        <a href="prosesmanajemenmenu.php?aksi=toggle&id=<?= $menu['id_menu'] ?>&filter=<?= urlencode($filter) ?>"
           class="tombolkecil <?= $menu['status'] === 'aktif' ? 'kuning' : 'hijau' ?>">
          <i class="fa-solid fa-<?= $menu['status'] === 'aktif' ? 'ban' : 'check' ?>"></i>
          <?= $menu['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
        </a>
        <!-- Hapus: buka modal konfirmasi via CSS :target -->
        <a href="manajemenmenu.php?hapus=<?= $menu['id_menu'] ?>&filter=<?= urlencode($filter) ?><?= $cari ? '&cari='.urlencode($cari) : '' ?>#konfirm-hapus"
           class="tombolkecil merah">
          <i class="fa-solid fa-trash"></i>
        </a>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php else: ?>
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

<!-- ===== MODAL TAMBAH MENU (CSS :target) ===== -->
<div class="modaloverlay" id="modal-tambah">
  <a href="<?= $urldasar ?>" class="penutup-modal"></a>
  <div class="isimodal" style="max-width:520px;position:relative;z-index:1;">
    <div class="modal-judul">
      <h2><i class="fa-solid fa-plus"></i> Tambah Menu Baru</h2>
      <a href="<?= $urldasar ?>" class="tombol-tutup-modal">
        <i class="fa-solid fa-xmark"></i>
      </a>
    </div>
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
            <option value="<?= $k ?>"><?= $k ?></option>
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

<!-- ===== MODAL EDIT MENU (CSS :target) ===== -->
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
    <form method="POST" action="prosesmanajemenmenu.php" enctype="multipart/form-data">
      <input type="hidden" name="aksi" value="edit">
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
            <option value="<?= $k ?>" <?= $dataedit['kategori'] === $k ? 'selected' : '' ?>><?= $k ?></option>
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
    <p style="text-align:center;color:var(--tekssamar);padding:20px;">Pilih menu yang ingin diedit.</p>
    <?php endif; ?>
  </div>
</div>

<!-- ===== MODAL KONFIRMASI HAPUS (CSS :target) ===== -->
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
