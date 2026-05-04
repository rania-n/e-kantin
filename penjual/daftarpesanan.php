<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - e-Kantin</title>
    <link rel="stylesheet" href="../3. komponen/penjual.css">
</head>
<body>

    <?php include 'sidebarpenjual.html'; ?>

    <div class="main">
        <div class="header">
            <h1>Manajemen Pesanan</h1>
            <p>Kelola dan proses pesanan dari siswa dan guru</p>
        </div>

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
                    <button class="btn-detail">✓ Pesanan Selesai</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>