<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Menu e-Kantin</title>
    <link rel="stylesheet" href="../3. komponen/penjual.css">
</head>
<body>

    <?php include 'sidebarpenjual.html'; ?>

    <div class="main">
        <div class="header">
            <div>
                <h1>Manajemen Menu</h1>
                <p>Kelola menu makanan dan minuman kantin</p>
            </div>
            <a href="#menuModal" class="btn-add">+ Tambah Menu</a>
        </div>

        <div class="filter">
            <button class="active">Semua</button>
            <button>Makanan Berat</button>
            <button>Makanan Ringan</button>
            <button>Makanan Sehat</button>
            <button>Minuman</button>
        </div>

        <div class="menu-grid">
            <!-- Contoh menu -->
            <div class="card">
                <img src="nasi-goreng.jpg" alt="Nasi Goreng">
                <div class="card-body">
                    <h3>Nasi Goreng</h3>
                    <small>Makanan Berat</small>
                    <p>Nasi goreng spesial dengan telur dan ayam</p>
                    <div class="price">Rp 15.000</div>
                </div>
                <div class="actions">
                    <button class="disable">Nonaktifkan</button>
                    <a href="#menuModal" class="edit">✏</a>
                    <button class="delete">🗑</button>
                </div>
            </div>

            <div class="card">
                <img src="salad.jpg" alt="Salad Bowl">
                <div class="card-body">
                    <h3>Salad Bowl</h3>
                    <small>Makanan Sehat</small>
                    <p>Salad sayuran segar dengan dressing pilihan</p>
                    <div class="price">Rp 25.000</div>
                </div>
                <div class="actions">
                    <button class="disable">Nonaktifkan</button>
                    <a href="#menuModal" class="edit">✏</a>
                    <button class="delete">🗑</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL -->
    <div class="modal" id="menuModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Menu</h2>
                <a href="#" class="close">&times;</a>
            </div>
            <form>
                <label>Kategori</label>
                <select>
                    <option>Makanan Berat</option>
                    <option>Makanan Sehat</option>
                    <option>Minuman</option>
                </select>
                <label>Harga (Rp)</label>
                <input type="number" value="15000">
                <label>Stok</label>
                <input type="number" value="25">
                <label>Deskripsi</label>
                <textarea placeholder="Deskripsi singkat menu..."></textarea>
                <label>Upload Gambar</label>
                <input type="file" accept="image/*">
                <div class="modal-buttons">
                    <a href="#" class="btn-cancel">Batal</a>
                    <button type="submit" class="btn-save">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>