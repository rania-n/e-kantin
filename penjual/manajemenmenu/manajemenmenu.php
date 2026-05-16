<?php
session_start();
include '../../1. koneksi/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'penjual') {
    header("Location: ../../4. autentifikasi/login.php");
    exit();
}

// Filter
$filter = $_GET['filter'] ?? 'Semua';

// Notifikasi
$pesan = $_GET['pesan'] ?? '';
$tipe  = $_GET['tipe'] ?? '';

// Query dengan filter yang benar
$query = "SELECT * FROM tb_menu WHERE deleted = 0";
if ($filter === 'Minuman Ringan' || $filter === 'Minuman Sehat') {
    $query .= " AND kategori = '$filter'";
} elseif (in_array($filter, ['Makanan Berat', 'Makanan Ringan', 'Makanan Sehat'])) {
    $query .= " AND kategori = '$filter'";
}
// Semua = tidak ditambah kondisi
$query .= " ORDER BY created DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Menu e-Kantin</title>
    <link rel="stylesheet" href="../../3. komponen/penjual.css">
    <style>
        .modal { display: none; }
        .modal:target { display: flex; }
        .alert { padding: 12px; margin: 15px 0; border-radius: 6px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<?php include '../../3. komponen/sidebarpenjual.html'; ?>

<div class="main">
    <div class="header">
        <div>
            <h1>Manajemen Menu</h1>
            <p>Kelola menu makanan dan minuman kantin</p>
        </div>
        <a href="#tambahModal" class="btn-add">+ Tambah Menu</a>
    </div>

    <!-- Notifikasi -->
    <?php if($pesan): ?>
        <div class="alert alert-<?= $tipe === 'success' ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>


    <div class="filter-tabs">
        <?php
        $kategori_list = [
            'Semua'           => 'Semua',
            'Makanan Berat'   => 'Makanan Berat',
            'Makanan Ringan'  => 'Makanan Ringan',
            'Makanan Sehat'   => 'Makanan Sehat',
            'Minuman Ringan'  => 'Minuman Ringan',
            'Minuman Sehat'   => 'Minuman Sehat'
        ];
        foreach ($kategori_list as $val => $label):
        ?>
            <form method="get" style="display:inline-block;margin:0;">
                <input type="hidden" name="filter" value="<?= $val ?>">
                <button type="submit" class="<?= $filter === $val ? 'active' : '' ?>"><?= $label ?></button>
            </form>
        <?php endforeach; ?>
    </div>

    <div class="menu-grid">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <div class="card">
                <img src="../../2. aset/katalog/<?= htmlspecialchars($row['foto']) ?>" 
                     alt="<?= htmlspecialchars($row['nama_menu']) ?>"
                     onerror="this.src='../../2. aset/katalog/default.jpg'">
                <div class="card-body">
                    <h3><?= htmlspecialchars($row['nama_menu']) ?></h3>
                    <small><?= htmlspecialchars($row['kategori']) ?></small>
                    <p><?= htmlspecialchars($row['deskripsi']) ?></p>
                    <div class="price">Rp <?= number_format($row['harga'], 0, ',', '.') ?></div>
                </div>
                <div class="actions">
                    <a href="prosesmanajemenmenu.php?action=toggle&id=<?= $row['id_menu'] ?>&filter=<?= urlencode($filter) ?>" 
                       class="disable" onclick="return confirm('Ubah status menu ini?')"> 
                        <?= $row['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                    </a>
                    <a href="?edit=<?= $row['id_menu'] ?>&filter=<?= urlencode($filter) ?>#menuModal" class="edit">✏ Edit</a>
                    <a href="prosesmanajemenmenu.php?action=delete&id=<?= $row['id_menu'] ?>&filter=<?= urlencode($filter) ?>" 
                       class="delete" onclick="return confirm('Yakin hapus menu ini?')">🗑 Hapus</a>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #888; font-size: 1.1em;">
                Menu tidak ditemukan.
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal" id="tambahModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Tambah Menu Baru</h2>
            <a href="#" class="close">&times;</a>
        </div>
        <form action="prosesmanajemenmenu.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">

            <label>Nama Menu</label>
            <input type="text" name="nama_menu" required>

            <label>Kategori</label>
            <select name="kategori" required>
                <option value="Makanan Berat">Makanan Berat</option>
                <option value="Makanan Ringan">Makanan Ringan</option>
                <option value="Makanan Sehat">Makanan Sehat</option>
                <option value="Minuman Ringan">Minuman Ringan</option>
                <option value="Minuman Sehat">Minuman Sehat</option>
            </select>

            <label>Harga (Rp)</label>
            <input type="number" name="harga" required>

            <label>Stok</label>
            <input type="number" name="stok" required>

            <label>Deskripsi</label>
            <textarea name="deskripsi" rows="3"></textarea>

            <label>Upload Gambar</label>
            <input type="file" name="foto" accept="image/*" required>

            <div class="modal-buttons">
                <a href="#" class="btn-cancel">Batal</a>
                <button type="submit" class="btn-save">Tambah Menu</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal" id="menuModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Menu</h2>
            <a href="#" class="close">&times;</a>
        </div>
        <?php 
        $edit_id = $_GET['edit'] ?? '';
        $edit_data = null;
        if($edit_id) {
            $q = mysqli_query($conn, "SELECT * FROM tb_menu WHERE id_menu = " . (int)$edit_id);
            $edit_data = mysqli_fetch_assoc($q);
        }
        ?>
        <form action="prosesmanajemenmenu.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_menu" value="<?= $edit_data['id_menu'] ?? '' ?>">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <input type="hidden" name="foto_lama" value="<?= $edit_data['foto'] ?? '' ?>">

            <label>Nama Menu</label>
            <input type="text" name="nama_menu" value="<?= htmlspecialchars($edit_data['nama_menu'] ?? '') ?>" required>

            <label>Kategori</label>
            <select name="kategori" required>
                <option value="Makanan Berat" <?= ($edit_data['kategori'] ?? '') === 'Makanan Berat' ? 'selected' : '' ?>>Makanan Berat</option>
                <option value="Makanan Ringan" <?= ($edit_data['kategori'] ?? '') === 'Makanan Ringan' ? 'selected' : '' ?>>Makanan Ringan</option>
                <option value="Makanan Sehat" <?= ($edit_data['kategori'] ?? '') === 'Makanan Sehat' ? 'selected' : '' ?>>Makanan Sehat</option>
                <option value="Minuman Ringan" <?= ($edit_data['kategori'] ?? '') === 'Minuman Ringan' ? 'selected' : '' ?>>Minuman Ringan</option>
                <option value="Minuman Sehat" <?= ($edit_data['kategori'] ?? '') === 'Minuman Sehat' ? 'selected' : '' ?>>Minuman Sehat</option>
            </select>

            <label>Harga (Rp)</label>
            <input type="number" name="harga" value="<?= $edit_data['harga'] ?? '' ?>" required>

            <label>Stok</label>
            <input type="number" name="stok" value="<?= $edit_data['stok'] ?? '' ?>" required>

            <label>Deskripsi</label>
            <textarea name="deskripsi" rows="3"><?= htmlspecialchars($edit_data['deskripsi'] ?? '') ?></textarea>

            <label>Upload Gambar Baru (kosongkan jika tidak ingin ganti)</label>
            <input type="file" name="foto" accept="image/*">

            <div class="modal-buttons">
                <a href="#" class="btn-cancel">Batal</a>
                <button type="submit" class="btn-save">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>