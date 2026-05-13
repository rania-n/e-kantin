<?php
include '../../1. koneksi/koneksi.php';

/* ===================== QUERY ===================== */

// Total pembeli
$q_pembeli = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN deleted = 0 THEN 1 ELSE 0 END) as aktif
    FROM tb_user 
    WHERE role = 'pembeli'
");
$pembeli = mysqli_fetch_assoc($q_pembeli);

// Total penjual
$q_penjual = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN deleted = 0 THEN 1 ELSE 0 END) as aktif
    FROM tb_user 
    WHERE role = 'penjual'
");
$penjual = mysqli_fetch_assoc($q_penjual);

// Total produk
$q_produk = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status='aktif' THEN 1 ELSE 0 END) as tersedia
    FROM tb_menu
");
$produk = mysqli_fetch_assoc($q_produk);

// Total toko
$q_toko = mysqli_query($conn, "
    SELECT COUNT(*) as total FROM tb_toko WHERE deleted = 0
");
$toko = mysqli_fetch_assoc($q_toko);

// Produk terlaris
$q_terlaris = mysqli_query($conn, "
    SELECT m.nama_menu, SUM(d.jumlah) as total_terjual
    FROM tb_detail_order d
    JOIN tb_menu m ON d.id_menu = m.id_menu
    GROUP BY d.id_menu
    ORDER BY total_terjual DESC
    LIMIT 5
");

// Penjualan kategori
$q_kategori = mysqli_query($conn, "
    SELECT m.kategori, SUM(d.subtotal) as total
    FROM tb_detail_order d
    JOIN tb_menu m ON d.id_menu = m.id_menu
    GROUP BY m.kategori
");

$kategori_data = [];
$total_all = 0;

while ($row = mysqli_fetch_assoc($q_kategori)) {
    $kategori_data[] = $row;
    $total_all += $row['total'];
}
?>
