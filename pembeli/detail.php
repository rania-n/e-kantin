<?php 
include '../1. koneksi/koneksi.php'; 

if(!isset($_GET['id'])) {
    header("Location: index.php");
}

$id = $_GET['id'];
$query = "SELECT * FROM tb_menu WHERE id_menu = '$id'";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail - <?= $data['nama_menu'] ?></title>
    <link rel="stylesheet" href="../3. komponen/pembeli.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="detail-container">
        <a href="index.php" class="btn-back">⬅ Kembali~</a>
        
        <div class="img-wrapper">
            <img src="katalog/<?= $data['foto'] ?>" class="detail-img" alt="<?= $data['nama_menu'] ?>">
        </div>
        
        <div class="content-wrapper">
            <h2><?= $data['nama_menu'] ?></h2>
            <h3 class="detail-harga">Rp <?= number_format($data['harga'], 0, ',', '.') ?></h3>

            <div class="description-box">
                <h4>Deskripsi Menu</h4>
                <p><?= $data['deskripsi'] ?></p>
            </div>
            
            <p class="stok-info">Sisa Stok: <strong><?= $data['stok'] ?></strong></p>
        </div>

        <div class="action-footer">
            <a href="#" class="btn-beli"> 🛒 Tambah ke Keranjang</a>
        </div>
    </div>

</body>
</html>