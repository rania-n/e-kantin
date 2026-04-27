<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan Saya</title>
<link rel="stylesheet" href="pesanan.css">
</head>

<body>
<? php include 'navbarpembeli.html'; ?>
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
        <p>Rp 15.000</p>
        <p>Rp 10.000</p>
      </div>
    </div>

    <hr>

    <div class="bottom">
      <div>
        <p>Total</p>
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
        <p>Rp 25.000</p>
        <p>Rp 15.000</p>
      </div>
    </div>

    <hr>

    <div class="bottom">
      <span>Total: Rp 40.000</span>
      <a href="#" class="order-again">Pesan Lagi</a>
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
        <p>Rp 15.000</p>
      </div>
    </div>

    <hr>

    <div class="bottom">
      <span>Total: Rp 30.000</span>
      <a href="#" class="order-again">Pesan Lagi</a>
    </div>
  </div>

</div>


</body>
</html>