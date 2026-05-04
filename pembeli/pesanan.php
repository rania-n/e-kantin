<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya</title>
    <link rel="stylesheet" href="../3. komponen/pembeli.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

<div class="container">

    <div class="header">
        <h2>Pesanan Saya</h2>
        <p>Riwayat pesanan kantin</p>
    </div>

    <h3 class="section-title">Pesanan Aktif</h3>

    <div class="card active">
        <div class="top">
            <span class="status">✔ Siap Diambil</span>
            <span class="ambil">Ambil di Kantin!</span>
        </div>

        <div class="content">
            <div class="left">
                <b>ORD-001234</b>
                <p class="date">20 Feb 2026</p>
                <p>1x Nasi Goreng</p>
                <p>1x Jus Jeruk</p>
            </div>

            <div class="right">
                <p>Waktu Ambil</p>
                <b>12:00</b>
                <p class="price">Rp 15.000</p>
                <p class="price">Rp 10.000</p>
            </div>
        </div>

        <hr>

        <div class="bottom">
            <div>
                <p class="total-label">Total Pembayaran</p>
                <b>Rp 25.000</b>
            </div>
            <span class="payment">Bayar di Kantin</span>
        </div>
    </div>

    <h3 class="section-title">Riwayat Pesanan</h3>

    <div class="card">
        <div class="top">
            <b>ORD-001233</b>
            <span class="done">Selesai</span>
        </div>
        <p class="date">19 Feb 2026</p>
        <div class="content">
            <div class="left">
                <p>1x Salad Bowl</p>
                <p>1x Smoothie</p>
            </div>
            <div class="right">
                <p class="price">Rp 25.000</p>
                <p class="price">Rp 15.000</p>
            </div>
        </div>
        <hr>
        <div class="bottom">
            <span>Total: <b>Rp 40.000</b></span>
            <a href="index.php" class="order-again">Pesan Lagi</a>
        </div>
    </div>

    <div class="card">
        <div class="top">
            <b>ORD-001232</b>
            <span class="done">Selesai</span>
        </div>
        <p class="date">18 Feb 2026</p>
        <div class="content">
            <div class="left">
                <p>2x Sate Ayam</p>
            </div>
            <div class="right">
                <p class="price">Rp 30.000</p>
            </div>
        </div>
        <hr>
        <div class="bottom">
            <span>Total: <b>Rp 30.000</b></span>
            <a href="index.php" class="order-again">Pesan Lagi</a>
        </div>
    </div>

</div>

<?php include 'navbarpembeli.html'; ?>

</body>
</html>