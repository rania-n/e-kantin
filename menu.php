<?php include 'sidebar.php'; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Menu e-Kantin</title>
    <link rel="stylesheet" href="manajmenu.css">
    
</head>
<body>

<div class="container">

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="header">
            <div>
                <h1>Manajemen Menu</h1>
                <p>Kelola menu makanan dan minuman kantin</p>
            </div>

            <!-- tombol tambah menu -->
            <a href="#menuModal" class="btn-add">+ Tambah Menu</a>
        </div>

        <!-- FILTER -->
        <div class="filter">
            <button class="active">Semua</button>
            <button>Makanan Berat</button>
            <button>Makanan Ringan</button>
            <button>Makanan Sehat</button>
            <button>Minuman</button>
        </div>

        <!-- MENU GRID -->
        <div class="menu-grid">

            <div class="card">
                <img src="nasi-goreng.jpg" alt="Nasi Goreng">
                <h3>Nasi Goreng</h3>
                <small>Makanan Berat</small>
                <p>Nasi goreng spesial dengan telur dan ayam</p>
                <span>Rp 15.000</span>

                <div class="actions">
                    <button class="disable">Nonaktifkan</button>
                    <a href="#menuModal" class="edit">✏</a>
                    <button class="delete">🗑</button>
                </div>
            </div>

            <div class="card">
                <img src="salad.jpg" alt="Salad Bowl">
                <h3>Salad Bowl</h3>
                <small>Makanan Sehat</small>
                <p>Salad sayuran segar dengan dressing pilihan</p>
                <span>Rp 25.000</span>

                <div class="actions">
                    <button class="disable">Nonaktifkan</button>
                    <a href="#menuModal" class="edit">✏</a>
                    <button class="delete">🗑</button>
                </div>
            </div>

            <div class="card">
                <img src="sate-ayam.jpg" alt="Sate Ayam">
                <h3>Sate Ayam</h3>
                <small>Makanan Berat</small>
                <p>Sate ayam dengan bumbu kacang khas</p>
                <span>Rp 18.000</span>

                <div class="actions">
                    <button class="disable">Nonaktifkan</button>
                    <a href="#menuModal" class="edit">✏</a>
                    <button class="delete">🗑</button>
                </div>
            </div>

            <div class="card">
                <img src="smoothie.jpg" alt="Smoothie">
                <h3>Smoothie Bowl</h3>
                <small>Minuman</small>
                <p>Minuman buah segar yang menyehatkan</p>
                <span>Rp 18.000</span>

                <div class="actions">
                    <button class="disable">Nonaktifkan</button>
                    <a href="#menuModal" class="edit">✏</a>
                    <button class="delete">🗑</button>
                </div>
            </div>

            <div class="card">
                <img src="sandwich.jpg" alt="Sandwich">
                <h3>Sandwich</h3>
                <small>Makanan Ringan</small>
                <p>Sandwich isi sayuran dan protein sehat</p>
                <span>Rp 18.000</span>

                <div class="actions">
                    <button class="disable">Nonaktifkan</button>
                    <a href="#menuModal" class="edit">✏</a>
                    <button class="delete">🗑</button>
                </div>
            </div>

            <div class="card">
                <img src="bakso.jpg" alt="Bakso">
                <h3>Bakso</h3>
                <small>Makanan Berat</small>
                <p>Bakso kuah hangat dengan mie dan sayuran</p>
                <span>Rp 15.000</span>

                <div class="actions">
                    <button class="disable">Nonaktifkan</button>
                    <a href="#menuModal" class="edit">✏</a>
                    <button class="delete">🗑</button>
                </div>
            </div>

        </div>

    </main>
</div>

<!-- MODAL TAMBAH / EDIT MENU -->
<div class="modal" id="menuModal">

    <div class="modal-content">

        <div class="modal-header">
            <h2>Edit Menu</h2>
            <a href="#" class="close">&times;</a>
        </div>

        <form>

            <label>Nama Menu</label>
            <input type="text" placeholder="Contoh: Nasi Goreng Spesial">

            <label>Kategori</label>
            <select>
                <option>Makanan Berat</option>
                <option>Makanan Ringan</option>
                <option>Makanan Sehat</option>
                <option>Minuman</option>
            </select>

            <label>Harga (Rp)</label>
            <input type="number" placeholder="15000">

            <label>Stok</label>
            <input type="number" placeholder="25">

            <label>Deskripsi</label>
            <textarea placeholder="Deskripsi singkat menu..."></textarea>

            <label>Upload Gambar</label>
            <input type="file" accept="image/*">

            <div class="checkbox">
                <input type="checkbox" id="menuSehat">
                <label for="menuSehat">Menu Sehat</label>
            </div>

            <div class="modal-buttons">
                <a href="#" class="btn-cancel">Batal</a>
                <button type="submit" class="btn-save">Simpan Perubahan</button>
            </div>

        </form>

    </div>

</div>

</body>
</html>