<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard e-Kantin</title>
  <link rel="stylesheet" href="dashboard.CSS">
</head>
<body>
<?php include '../3. komponen/sidebar.html'; ?>
<div class="content">
<div class="container">

  <!-- HEADER -->
  <div class="header">
    <div>
      <h2>Dashboard</h2>
      <p>e-Kantin</p>
    </div>
  </div>

  <!-- PERIOD -->
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

  <!-- GLOBAL STATS -->
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

  <!-- GRID -->
  <div class="grid">

    <!-- PRODUK -->
    <div class="card">
      <h3>Produk Terlaris</h3>

      <div class="item"><span>#1</span><p>Nasi Goreng</p><b>Rp 3.6 Jt</b></div>
      <div class="item"><span>#2</span><p>Mie Ayam</p><b>Rp 2.9 Jt</b></div>
      <div class="item"><span>#3</span><p>Es Teh</p><b>Rp 1.9 Jt</b></div>
      <div class="item"><span>#4</span><p>Bakso</p><b>Rp 3.1 Jt</b></div>
      <div class="item"><span>#5</span><p>Soto Ayam</p><b>Rp 2.6 Jt</b></div>
    </div>

    <!-- KATEGORI -->
    <div class="card">
      <h3>Penjualan Kategori</h3>

      <div class="bar">
        <p>Makanan</p>
        <div class="progress"><div style="width:66%"></div></div>
      </div>

      <div class="bar">
        <p>Minuman</p>
        <div class="progress"><div style="width:17%"></div></div>
      </div>

      <div class="bar">
        <p>Snack</p>
        <div class="progress"><div style="width:11%"></div></div>
      </div>

      <div class="bar">
        <p>Buah</p>
        <div class="progress"><div style="width:4%"></div></div>
      </div>
    </div>

  </div>

  <!-- TOP PEMBELI -->
  <div class="card">
    <h3>Top Pembeli</h3>
    <div class="table-wrapper">
      <table>
        <tr>
          <th>Rank</th>
          <th>Nama</th>
          <th>Kelas</th>
          <th>Pesanan</th>
          <th>Total</th>
        </tr>
          <tr><td>#1</td><td>Ahmad</td><td>10A</td><td>48</td><td>Rp 2.4 Jt</td></tr>
          <tr><td>#2</td><td>Sinta</td><td>11B</td><td>42</td><td>Rp 2.1 Jt</td></tr>
          <tr><td>#3</td><td>Budi</td><td>Guru</td><td>38</td><td>Rp 1.9 Jt</td></tr>
          <tr><td>#4</td><td>Rina</td><td>12C</td><td>35</td><td>Rp 1.7 Jt</td></tr>
          <tr><td>#5</td><td>Andi</td><td>10B</td><td>32</td><td>Rp 1.6 Jt</td></tr>
      </table>
    </div>
  </div>

  <!-- RATING PENJUAL -->
  <div class="card">
    <h3>Rating Penjual Tertinggi</h3>

    <div class="seller-card">
      <div class="seller-header">
        <div class="icon gold">🥇</div>
        <div>
          <b>Ibu Siti</b>
          <small>Kantin Sehat</small>
        </div>
      </div>
      <div class="rating">
        ⭐ <b>4.9</b> <span>(328 reviews)</span>
      </div>
      <hr>
      <div class="seller-info">
        <p>Revenue <b>Rp 45.2 Jt</b></p>
        <p>Pesanan <b>892</b></p>
      </div>
    </div>
  
    <div class="seller-card">
      <div class="seller-header">
        <div class="icon silver">🥈</div>
        <div>
          <b>Pak Joko</b>
          <small>Warung Nasi</small>
        </div>
      </div>
      <div class="rating">
        ⭐ <b>4.8</b> <span>(256 reviews)</span>
      </div>
      <hr>
      <div class="seller-info">
        <p>Revenue <b>Rp 38.5 Jt</b></p>
        <p>Pesanan <b>751</b></p>
      </div>
    </div>
    
    <div class="seller-card">
      <div class="seller-header">
        <div class="icon bronze">🥉</div>
        <div>
          <b>Bu Ani</b>
          <small>Kedai Minuman</small>
        </div>
      </div>
      <div class="rating">
        ⭐ <b>4.7</b> <span>(189 reviews)</span>
      </div>
      <hr>
      <div class="seller-info">
        <p>Revenue <b>Rp 28.3 Jt</b></p>
        <p>Pesanan <b>598</b></p>
      </div>
    </div>
  </div>

  <!-- LAPORAN TOKO -->
  <div class="card">
    <h3>Laporan Toko</h3>

    <table>
      <tr>
        <th>Toko</th>
        <th>Penjual</th>
        <th>Revenue</th>
        <th>Pesanan</th>
        <th>Produk</th>
        <th>Rating</th>
        <th>Status</th>
      </tr>

      <tr>
        <td>
          <div class="shop-icon">🏪</div>
          Kantin Sehat
        </td>
        <td>Ibu Siti</td>
        <td>Rp 45.2 Jt</td>
        <td>892</td>
        <td>48</td>
        <td>⭐ 4.9</td>
        <td><span class="badge">Aktif</span></td>
      </tr>

      <tr>
        <td>
          <div class="shop-icon">🏪</div>
          Warung Nasi
        </td>
        <td>Pak Joko</td>
        <td>Rp 38.5 Jt</td>
        <td>742</td>
        <td>35</td>
        <td>⭐ 4.8</td>
        <td><span class="badge">Aktif</span></td>
      </tr>

      <tr>
        <td>
          <div class="shop-icon">🏪</div>
          Kedai Minuman
        </td>
        <td>Bu Ani</td>
        <td>Rp 28.3 Jt</td>
        <td>586</td>
        <td>28</td>
        <td>⭐ 4.7</td>
        <td><span class="badge">Aktif</span></td>
      </tr>
    </table>
  </div>

</div>
</div>
</body>
</html>