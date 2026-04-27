<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - e-Kantin</title>
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

        .header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 24px;
          flex-wrap: wrap;
          gap: 15px;
        }

        .header h1 {
            font-size: 26px;
            color: #064e3b;
        }

        .header p {
            color: #64748b;
            font-size: 14px;
            margin-top: 4px;
        }

        /* TOP BAR + FILTER TABS (PERSIS GAMBAR) */
        .top-bar {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 24px 0;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 280px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid #e2e8f0;
            border-radius: 9999px;
            font-size: 14px;
            background: white;
        }

        .search-box::before {
            content: "🔍";
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            color: #64748b;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-tabs button {
            padding: 10px 24px;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 9999px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: #475569;
            transition: all 0.25s ease;
            white-space: nowrap;
        }

        .filter-tabs button:hover {
            border-color: #10b981;
            color: #10b981;
        }

        .filter-tabs button.active {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }

        /* ORDERS GRID */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            padding: 20px;
            transition: 0.2s;
        }

        .order-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        /* Isi card (sama style dengan menu) */
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .order-id { font-weight: 700; font-size: 15px; }

        .status {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .status.waiting { background: #fef3c7; color: #92400e; }
        .status.process { background: #dbeafe; color: #1e40af; }
        .status.done { background: #dcfce7; color: #15803d; }
        .status.cancelled { background: #fee2e2; color: #b91c1c; }

        .customer {
            font-weight: 600;
            font-size: 17px;
            margin-bottom: 4px;
        }

        .customer small {
            color: #64748b;
            font-weight: 400;
        }

        .time {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 14px;
        }

        .items {
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
            margin-bottom: 12px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .total {
            font-weight: 700;
            font-size: 18px;
            color: #10b981;
            margin: 12px 0;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .actions button {
            flex: 1;
            padding: 11px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-process { background: #10b981; color: white; }
        .btn-reject { background: #fee2e2; color: #b91c1c; }
        .btn-detail { background: #e2e8f0; color: #475569; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { position: relative; width: 100%; height: auto; }
            .main { margin-left: 0; padding: 20px; }
            .orders-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'sidebarpenjual.html'; ?>

<div class="main">

    <div class="header">
        <h1>Manajemen Pesanan</h1>
        <p>Kelola dan proses pesanan dari siswa dan guru</p>
    </div>

    <!-- TOP BAR -->
    <div class="top-bar">
        <div class="search-box">
            <input type="text" placeholder="Cari pesanan atau nama pelanggan...">
        </div>

        <div class="filter-tabs">
            <button class="active">Semua (5)</button>
            <button>Menunggu (2)</button>
            <button>Diproses (1)</button>
            <button>Selesai (2)</button>
            <button>Dibatalkan (0)</button>
        </div>
    </div>

    <!-- ORDERS GRID -->
    <div class="orders-grid">

        <!-- Order 1 -->
        <div class="order-card">
            <div class="order-header">
                <span class="order-id">ORD-001</span>
                <span class="status waiting">Menunggu</span>
            </div>
            <div class="customer">Ahmad Rizki <small>Kelas 10A</small></div>
            <div class="time">Pesan: 10:15 | Ambil: 12:00</div>

            <div class="items">
                <div class="item-row"><span>1x Nasi Goreng</span><span>Rp 15.000</span></div>
                <div class="item-row"><span>1x Jus Jeruk</span><span>Rp 10.000</span></div>
            </div>

            <div class="total">Total: Rp 25.000</div>

            <div class="actions">
                <button class="btn-process">Proses</button>
                <button class="btn-reject">Tolak</button>
            </div>
        </div>

        <!-- Order 2 -->
        <div class="order-card">
            <div class="order-header">
                <span class="order-id">ORD-002</span>
                <span class="status process">Diproses</span>
            </div>
            <div class="customer">Siti Nurhaliza <small>Kelas 11B</small></div>
            <div class="time">Pesan: 10:12 | Ambil: 12:00</div>

            <div class="items">
                <div class="item-row"><span>2x Sate Ayam</span><span>Rp 30.000</span></div>
                <div class="item-row"><span>1x Es Teh</span><span>Rp 5.000</span></div>
            </div>

            <div class="total">Total: Rp 35.000</div>

            <div class="actions">
                <button class="btn-process">Tandai Selesai</button>
            </div>
        </div>

        <!-- Order 3 -->
        <div class="order-card">
            <div class="order-header">
                <span class="order-id">ORD-003</span>
                <span class="status done">Selesai</span>
            </div>
            <div class="customer">Budi Santoso <small>Guru</small></div>
            <div class="time">Pesan: 10:08 | Ambil: 12:00</div>

            <div class="items">
                <div class="item-row"><span>1x Salad Bowl</span><span>Rp 25.000</span></div>
                <div class="item-row"><span>1x Smoothie</span><span>Rp 15.000</span></div>
            </div>

            <div class="total">Total: Rp 40.000</div>

            <div class="actions">
                <button class="btn-detail" style="background:#dcfce7; color:#15803d;">✓ Pesanan Selesai</button>
            </div>
        </div>

    </div>

</div>

</body>
</html>