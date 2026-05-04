<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penjual - e-Kantin</title>
    <link rel="stylesheet" href="../3. komponen/penjual.css">
</head>
<body> 

    <?php include 'sidebarpenjual.html'; ?>

    <div class="main">
        <div class="header">
            <h1>Dashboard Penjual</h1>
            <p>Ringkasan hari ini</p>
        </div>

        <div class="cards">
            <div class="card">
                <h3>Pesanan Hari Ini</h3>
                <h2>47</h2>
                <p>↑ 5 dari kemarin</p>
            </div>
            <div class="card">
                <h3>Pendapatan Hari Ini</h3>
                <h2>Rp 2.4jt</h2>
                <p>↑ Rp 200rb dari kemarin</p>
            </div>
            <div class="card">
                <h3>7 Hari Terakhir</h3>
                <h2>Rp 14jt</h2>
                <p>+10% dari minggu lalu</p>
            </div>
        </div>

        <div class="grid">
            <div class="box">
                <h3>Pesanan Terbaru</h3>
                <div class="order">
                    <div>
                        <b>ORD-001</b><br>
                        Ahmad Rizki<br>
                        <small>Nasi Goreng, Jus Jeruk</small>
                    </div>
                    <div class="right">
                        <strong>Rp 25.000</strong><br>
                        <span class="status pending">Menunggu</span><br>
                        <small>10:15</small>
                    </div>
                </div>
                <div class="order">
                    <div>
                        <b>ORD-002</b><br>
                        Sinta Dewi<br>
                        <small>Mie Ayam</small>
                    </div>
                    <div class="right">
                        <strong>Rp 18.000</strong><br>
                        <span class="status proses">Diproses</span><br>
                        <small>10:30</small>
                    </div>
                </div>
            </div>

            <div class="box">
                <h3>Produk Terlaris</h3>
                <div class="top-product">
                    <p><strong>#1</strong> Nasi Goreng <span>45 terjual</span></p>
                    <p><strong>#2</strong> Mie Ayam <span>38 terjual</span></p>
                    <p><strong>#3</strong> Es Teh Manis <span>30 terjual</span></p>
                </div>
            </div>

            <div class="box">
                <h3>Review Terbaru</h3>
                <div class="review">
                    <p>⭐⭐⭐⭐⭐ Nasi gorengnya enak banget!</p>
                    <small>— Ahmad Rizki</small>
                </div>
                <div class="review">
                    <p>⭐⭐⭐⭐ Pelayanan cepat & ramah</p>
                    <small>— Sinta Dewi</small>
                </div>
            </div>

            <div class="box">
                <h3>Pelanggan Setia</h3>
                <div class="top-customer">
                    <p><strong>#1</strong> Ahmad Rizki <span>28 pesanan</span></p>
                    <p><strong>#2</strong> Sinta Dewi <span>24 pesanan</span></p>
                    <p><strong>#3</strong> Budi Santoso <span>21 pesanan</span></p>
                </div>
            </div>

            <div class="box">
                <h3>Penghasilan Bulanan</h3>
                <h2>Rp 68.4jt</h2>
                <p>Target: Rp 100jt (68%)</p>
                <div class="progress">
                    <div class="bar" style="width: 68%;"></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>