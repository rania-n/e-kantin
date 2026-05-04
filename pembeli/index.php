<?php 
include '../1. koneksi/koneksi.php'; 

$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';
$id_kantin = isset($_GET['kantin']) ? $_GET['kantin'] : ''; // Menangkap pilihan kantin

$query = "SELECT * FROM tb_menu WHERE deleted_at IS NULL";

if($kategori != '') {
    $query .= " AND kategori = '$kategori'";
}

if($cari != '') {
    $query .= " AND nama_menu LIKE '%$cari%'";
}

// Jika kantin dipilih, filter berdasarkan kantin (opsional jika nanti database sudah siap)
if($id_kantin != '') {
    // $query .= " AND id_kantin = '$id_kantin'"; 
}

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Kantin - Beranda</title>
    <link rel="stylesheet" href="../3. komponen/pembeli.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <h5>pembeli</h5>
        <h1>Halo, Budi Santoso! 👋</h1>
        
        <!-- Jika sedang memilih kantin, tampilkan namanya -->
        <?php if($id_kantin != ''): ?>
            <div class="visiting-badge">
                <i class="fas fa-store"></i> Sedang di: <b>Kantin <?= $id_kantin ?></b>
            </div>
        <?php endif; ?>
    </header>

    <!-- BAGIAN PILIHAN KANTIN (Kecil & Responsive) -->
    <div class="kantin-section">
        <h3 class="section-title" style="margin-left: 5px;">Pilih Kantin</h3>
        <div class="kantin-container">
            <?php for($i=1; $i<=10; $i++): ?>
            <a href="index.php?kantin=<?= $i ?>" class="kantin-item">
                <!-- Gambar Toko Dummy -->
                <img src="https://ui-avatars.com/api/?name=Kantin+<?= $i ?>&background=99627A&color=fff" class="kantin-img">
                <span>Kantin <?= $i ?></span>
            </a>
            <?php endfor; ?>
        </div>
    </div>

    <div class="search-container">
        <form action="index.php" method="GET">
            <!-- Simpan id_kantin di form agar saat cari menu tidak keluar dari toko -->
            <input type="hidden" name="kantin" value="<?= $id_kantin ?>">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" name="cari" value="<?= $cari ?>" placeholder="Cari menu di Kantin <?= $id_kantin ? $id_kantin : 'favoritmu' ?>..." aria-label="Cari menu">
            </div>
        </form>
    </div>

    <div class="kategori-container">
        <a href="index.php?kantin=<?= $id_kantin ?>" class="tab-kategori <?= $kategori == '' ? 'active' : '' ?>">Semua</a>
        <a href="index.php?kantin=<?= $id_kantin ?>&kategori=Makanan" class="tab-kategori <?= $kategori == 'Makanan' ? 'active' : '' ?>">Makanan</a>
        <a href="index.php?kantin=<?= $id_kantin ?>&kategori=Makanan_berat" class="tab-kategori <?= $kategori == 'Makanan_berat' ? 'active' : '' ?>">Makanan berat</a>
        <a href="index.php?kantin=<?= $id_kantin ?>&kategori=Makanan_ringan" class="tab-kategori <?= $kategori == 'Makanan_ringan' ? 'active' : '' ?>">Makanan ringan</a>
        <a href="index.php?kantin=<?= $id_kantin ?>&kategori=Minuman" class="tab-kategori <?= $kategori == 'Minuman' ? 'active' : '' ?>">Minuman</a>
    </div>

    <div class="menu-grid">
    <?php if(mysqli_num_rows($result) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <a href="detail.php?id=<?= $row['id_menu'] ?>" class="card">
            <img src="../2. aset/katalog/katalog<?= $row['foto'] ?>" alt="<?= $row['nama_menu'] ?>">
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

    <?php include 'navbarpembeli.html'; ?>
</body>
</html>