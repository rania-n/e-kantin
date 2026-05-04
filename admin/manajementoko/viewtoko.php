<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Toko - Kantin Bu Siti</title>
    <link rel="stylesheet" href="../../3. komponen/admin.css">
</head>
<body>
    <?php include '../../3. komponen/sidebaradmin.html'; ?>

    <div class="content">
        <div class="container">
            <h2>Detail Toko - Kantin Bu Siti</h2>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-box">
                    <h4>Total Pesanan</h4>
                    <p class="green">120</p>
                </div>
                <div class="stat-box">
                    <h4>Pendapatan Minggu Ini</h4>
                    <p class="green">Rp 2,5jt</p>
                </div>
            </div>

            <!-- INFO TOKO -->
            <div class="card">
                <div class="row">
                    <label>Nama Toko</label>
                    <p>Kantin Bu Siti</p>
                </div>
                <div class="row">
                    <label>Username Pemilik</label>
                    <p>seller1</p>
                </div>
                <div class="row">
                    <label>Status Operasional</label>
                    <span class="badge status">Aktif / Buka</span>
                </div>
            </div>

            <!-- PRODUK TERLARIS -->
            <div class="card">
                <h3>Produk Terlaris Minggu Ini</h3>
                <table class="tabel-kantin">
                    <thead>
                        <tr>
                            <th>Nama Menu</th>
                            <th>Terjual</th>
                            <th>Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Nasi Goreng Spesial</td><td>45 porsi</td><td>Rp 450.000</td></tr>
                        <tr><td>Es Teh Manis</td><td>60 gelas</td><td>Rp 180.000</td></tr>
                        <tr><td>Ayam Geprek</td><td>30 porsi</td><td>Rp 450.000</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- ACTIONS -->
            <div class="actions">
                <a href="edittoko.php" class="btn edit">✏ Edit Toko</a>
                <a href="toko.php" class="btn back">← Kembali ke Daftar Toko</a>
            </div>
        </div>
    </div>
</body>
</html>