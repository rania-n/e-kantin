<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penjual - e-Kantin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
            min-height: 100vh;
            display: flex;
        }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #064e3b;
            color: white;
            padding: 24px 20px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .logo img {
            width: 46px;
            height: 46px;
            background: white;
            border-radius: 50%;
            padding: 6px;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li {
            padding: 13px 16px;
            margin-bottom: 6px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14.5px;
            transition: 0.2s;
        }

        .sidebar ul li:hover,
        .sidebar ul li.active {
            background: #065f46;
        }

        /* MAIN CONTENT */
        .main {
            flex: 1;
            margin-left: 250px;
            padding: 32px 40px;
            min-height: 100vh;
        }

        /* HEADER */
        .header {
            margin-bottom: 32px;
        }

        .header h1 {
            font-size: 28px;
            color: #064e3b;
            margin-bottom: 8px;
        }

        .header p {
            color: #64748b;
            font-size: 15px;
        }

        /* SUMMARY CARDS */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            transition: 0.2s;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .card h3 {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
        }

        .card h2 {
            font-size: 26px;
            color: #111827;
            margin-bottom: 6px;
        }

        .card p {
            font-size: 14px;
            color: #10b981;
        }

        /* GRID BOXES */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .box h3 {
            font-size: 15px;
            color: #064e3b;
            margin-bottom: 16px;
            font-weight: 600;
        }

        /* ORDER */
        .order {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .order:last-child { border-bottom: none; }

        .right { text-align: right; }

        .status {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }

        .pending { background: #fef3c7; color: #92400e; }
        .proses { background: #dbeafe; color: #1e40af; }

        /* Top Product & Customer */
        .top-product p,
        .top-customer p {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        /* Review */
        .review {
            margin-bottom: 14px;
        }

        .review p {
            font-size: 13.5px;
            color: #374151;
        }

        .review small {
            color: #6b7280;
            font-size: 12px;
        }

        /* Progress */
        .progress {
            background: #e5e7eb;
            height: 8px;
            border-radius: 999px;
            margin-top: 12px;
            overflow: hidden;
        }

        .bar {
            height: 100%;
            background: #10b981;
            border-radius: 999px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { position: relative; width: 100%; height: auto; }
            .main { margin-left: 0; padding: 20px; }
            .cards, .grid { grid-template-columns: 1fr; }
        }

        @media (min-width: 1024px) {
            .cards { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>

<?php include 'sidebarpenjual.html'; ?>

<div class="main">

    <div class="header">
        <h1>Dashboard Penjual</h1>
        <p>Ringkasan hari ini</p>
    </div>

    <!-- RINGKASAN CARDS -->
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

    <!-- GRID UTAMA -->
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