<?php 
include '../1. koneksi/koneksi.php'; 

$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';

$query = "SELECT * FROM tb_menu WHERE deleted_at IS NULL";

if($kategori != '') {
    $query .= " AND kategori = '$kategori'";
}

if($cari != '') {
    $query .= " AND nama_menu LIKE '%$cari%'";
}

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Kantin - Beranda</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <h5>pembeli</h5>
        <h1>Halo, Budi Santoso! 👋</h1>
    </header>

    <div class="search-container">
        <form action="index.php" method="GET">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" name="cari" value="<?= $cari ?>" placeholder="Cari menu favoritmu..." aria-label="Cari menu">
            </div>
        </form>
    </div>

    <div class="kategori-container">
        <a href="index.php" class="tab-kategori <?= $kategori == '' ? 'active' : '' ?>">Semua</a>
        <a href="index.php?kategori=Makanan" class="tab-kategori <?= $kategori == 'Makanan' ? 'active' : '' ?>">Makanan</a>
        <a href="index.php?kategori=Makanan_berat" class="tab-kategori <?= $kategori == 'Makanan_berat' ? 'active' : '' ?>">Makanan berat</a>
        <a href="index.php?kategori=Makanan_ringan" class="tab-kategori <?= $kategori == 'Makanan_ringan' ? 'active' : '' ?>">Makanan ringan</a>
        <a href="index.php?kategori=Minuman" class="tab-kategori <?= $kategori == 'Minuman' ? 'active' : '' ?>">Minuman</a>
    </div>

    <div class="menu-grid">
    <?php if(mysqli_num_rows($result) > 0): ?>
        
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <a href="detail.php?id=<?= $row['id_menu'] ?>" class="card">
            <img src="katalog/<?= $row['foto'] ?>" alt="<?= $row['nama_menu'] ?>">
            <div class="card-info">
                <h3><?= $row['nama_menu'] ?></h3>
                <p class="harga">Rp <?= number_format($row['harga'], 0, ',', '.') ?></p>
                
                <div class="btn-add-cart">
                    <span>+</span>
                </div>
            </div>
        </a>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="padding: 20px;">Menu tidak ditemukan.</p>
    <?php endif; ?>
</div>
            </a>
            <div class="menu-grid">
        </div>

    <?php include 'navbarpembeli.html'; ?>
</body>
</html>