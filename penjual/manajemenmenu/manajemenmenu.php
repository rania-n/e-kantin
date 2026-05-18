<?php
/* ============================================================
   KELOLA MENU PENJUAL
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idtoko = (int)$_SESSION['id_toko'];
$halamansaatini = 'manajemenmenu';

// Filter
$filter  = $_GET['filter'] ?? 'Semua';
$editid  = (int)($_GET['edit'] ?? 0);

// Flash message
$flashpesan = ''; $flashjenis = '';
if (!empty($_SESSION['flash'])) {
    $flashpesan = $_SESSION['flash']['pesan'];
    $flashjenis = $_SESSION['flash']['jenis'];
    unset($_SESSION['flash']);
}

// Kategori yang valid
$kategorilist = ['Makanan Berat','Makanan Ringan','Makanan Sehat','Minuman Ringan','Minuman Sehat'];

// Query menu milik toko ini
$kondisi = "id_toko=$idtoko AND deleted=0";
if ($filter !== 'Semua' && in_array($filter, $kategorilist)) {
    $filteraman = $conn->real_escape_string($filter);
    $kondisi .= " AND kategori='$filteraman'";
}
$hasilmenu = $conn->query("SELECT * FROM tb_menu WHERE $kondisi ORDER BY created DESC");

// Data edit (jika diminta)
$dataedit = null;
if ($editid > 0) {
    $qe = $conn->prepare("SELECT * FROM tb_menu WHERE id_menu=? AND id_toko=? AND deleted=0");
    $qe->bind_param("ii", $editid, $idtoko);
    $qe->execute();
    $dataedit = $qe->get_result()->fetch_assoc();
    $qe->close();
}
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
    <button onclick="bukaModal('modalTambah')" class="tombolutama">
      <i class="fa-solid fa-plus"></i> Tambah Menu
    </button>
  </div>

  <?php if ($flashpesan): ?>
  <div class="flashpesan flash<?= $flashjenis ?>">
    <i class="fa-solid fa-<?= $flashjenis === 'sukses' ? 'circle-check' : 'circle-xmark' ?>"></i>
    <?= htmlspecialchars($flashpesan) ?>
  </div>
  <?php endif; ?>

  <!-- Filter kategori -->
  <div class="filter-bar">
    <a href="manajemenmenu.php" class="chip-filter <?= $filter === 'Semua' ? 'aktif' : '' ?>">
      Semua
    </a>
    <?php foreach ($kategorilist as $k): ?>
    <a href="manajemenmenu.php?filter=<?= urlencode($k) ?>"
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
        <a href="manajemenmenu.php?edit=<?= $menu['id_menu'] ?>&filter=<?= urlencode($filter) ?>"
           onclick="bukaModal('modalEdit')"
           class="tombolkecil">
          <i class="fa-solid fa-pen"></i> Edit
        </a>
        <a href="prosesmanajemenmenu.php?aksi=toggle&id=<?= $menu['id_menu'] ?>&filter=<?= urlencode($filter) ?>"
           class="tombolkecil <?= $menu['status'] === 'aktif' ? 'kuning' : 'hijau' ?>"
           onclick="return confirm('Ubah status menu ini?')">
          <i class="fa-solid fa-<?= $menu['status'] === 'aktif' ? 'ban' : 'check' ?>"></i>
          <?= $menu['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
        </a>
        <a href="prosesmanajemenmenu.php?aksi=hapus&id=<?= $menu['id_menu'] ?>&filter=<?= urlencode($filter) ?>"
           class="tombolkecil merah"
           onclick="return confirm('Yakin hapus menu ini? Tindakan ini tidak bisa dibatalkan.')">
          <i class="fa-solid fa-trash"></i>
        </a>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php else: ?>
  <div class="kosong">
    <div class="ikon-kosong"><i class="fa-solid fa-bowl-food"></i></div>
    <h3>Belum ada menu</h3>
    <p>Tambahkan menu pertamamu untuk mulai menerima pesanan</p>
    <button onclick="bukaModal('modalTambah')" class="tombolutama">
      <i class="fa-solid fa-plus"></i> Tambah Menu Sekarang
    </button>
  </div>
  <?php endif; ?>

</main>

<!-- ===== MODAL TAMBAH MENU ===== -->
<div class="modaloverlay" id="modalTambah" onclick="tutupModal('modalTambah')">
  <div class="isimodal" onclick="event.stopPropagation()" style="max-width:520px;">
    <div class="modal-judul">
      <h2><i class="fa-solid fa-plus"></i> Tambah Menu Baru</h2>
      <button class="tombol-tutup-modal" onclick="tutupModal('modalTambah')">
        <i class="fa-solid fa-xmark"></i>
      </button>
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
        <button type="button" class="tombolringan" onclick="tutupModal('modalTambah')">Batal</button>
        <button type="submit" class="tombolutama" style="flex:1;">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Menu
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ===== MODAL EDIT MENU ===== -->
<div class="modaloverlay" id="modalEdit" onclick="tutupModal('modalEdit')"
     style="<?= $dataedit ? 'opacity:1;pointer-events:all;' : '' ?>">
  <div class="isimodal" onclick="event.stopPropagation()" style="max-width:520px;">
    <div class="modal-judul">
      <h2><i class="fa-solid fa-pen"></i> Edit Menu</h2>
      <a href="manajemenmenu.php?filter=<?= urlencode($filter) ?>" class="tombol-tutup-modal">
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
        <a href="manajemenmenu.php?filter=<?= urlencode($filter) ?>" class="tombolringan">Batal</a>
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

<script>
function bukaModal(id) {
  document.getElementById(id).classList.add('tampil');
}
function tutupModal(id) {
  document.getElementById(id).classList.remove('tampil');
  // Kalau tutup modal edit, hapus parameter edit dari URL
  if (id === 'modalEdit') {
    var url = new URL(window.location.href);
    url.searchParams.delete('edit');
    window.history.replaceState({}, '', url);
  }
}
// Buka modal edit otomatis jika ada parameter ?edit=
<?php if ($dataedit): ?>
document.addEventListener('DOMContentLoaded', function() {
  bukaModal('modalEdit');
});
<?php endif; ?>
</script>

</body>
</html>
