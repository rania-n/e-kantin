<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard e-Kantin</title>
    <link rel="stylesheet" href="../../3. komponen/admin.css">
</head>
<body>
    <?php include '../../3. komponen/sidebaradmin.html'; ?>

    <div class="content">
            <div class="header">
                <div>
                    <h2>Dashboard</h2>
                    <p>e-Kantin</p>
                </div>
            </div>

            <div class="period-selector">
                <input type="radio" id="today" name="period" checked>
                <label for="today">Hari Ini</label>
                <input type="radio" id="week" name="period">
                <label for="week">Minggu</label>
                <input type="radio" id="month" name="period">
                <label for="month">Bulan</label>
                <input type="radio" id="year" name="period">
                <label for="year">Tahun</label>
            </div>

            <div class="stats">
                <div class="card">
                    <h4>Total Pembeli</h4>
                    <h2>1284</h2>
                    <p>892 aktif</p>
                </div>
                <div class="card">
                    <h4>Total Penjual</h4>
                    <h2>5</h2>
                    <p>4 aktif</p>
                </div>
                <div class="card">
                    <h4>Total Produk</h4>
                    <h2>156</h2>
                    <p>142 tersedia</p>
                </div>
                <div class="card">
                    <h4>Total Toko</h4>
                    <h2>3</h2>
                    <p>3 aktif</p>
                </div>
            </div>

            <div class="grid">
                <div class="card">
                    <h3>Produk Terlaris</h3>
                    <div class="item"><span>#1</span><p>Nasi Goreng</p><b>Rp 3.6 Jt</b></div>
                    <div class="item"><span>#2</span><p>Mie Ayam</p><b>Rp 2.9 Jt</b></div>
                    <div class="item"><span>#3</span><p>Es Teh</p><b>Rp 1.9 Jt</b></div>
                    <div class="item"><span>#4</span><p>Bakso</p><b>Rp 3.1 Jt</b></div>
                    <div class="item"><span>#5</span><p>Soto Ayam</p><b>Rp 2.6 Jt</b></div>
                </div>

                <div class="card">
                    <h3>Penjualan Kategori</h3>
                    <div class="bar"><p>Makanan</p><div class="progress"><div style="width:66%"></div></div></div>
                    <div class="bar"><p>Minuman</p><div class="progress"><div style="width:17%"></div></div></div>
                    <div class="bar"><p>Snack</p><div class="progress"><div style="width:11%"></div></div></div>
                    <div class="bar"><p>Buah</p><div class="progress"><div style="width:4%"></div></div></div>
                </div>
            </div>
    </div>
</body>
</html>
