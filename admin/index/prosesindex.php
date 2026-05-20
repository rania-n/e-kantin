<?php
// file ini berisi query-query untuk statistik dashboard admin (versi lama, tidak digunakan aktif)
include '../../1. koneksi/koneksi.php';

/* ===================== QUERY ===================== */

// hitung total pembeli: semua yang ada di database, termasuk yang dihapus,
// dan hitung berapa yang masih aktif (deleted = 0)
$q_pembeli = mysqli_query($conn, "
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN deleted = 0 THEN 1 ELSE 0 END) as aktif
    FROM tb_user
    WHERE role = 'pembeli'
");
$pembeli = mysqli_fetch_assoc($q_pembeli);

// hitung total penjual dengan logika yang sama seperti pembeli
$q_penjual = mysqli_query($conn, "
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN deleted = 0 THEN 1 ELSE 0 END) as aktif
    FROM tb_user
    WHERE role = 'penjual'
");
$penjual = mysqli_fetch_assoc($q_penjual);

// hitung total produk (menu): semua produk, dan berapa yang berstatus aktif (tersedia dipesan)
$q_produk = mysqli_query($conn, "
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status='aktif' THEN 1 ELSE 0 END) as tersedia
    FROM tb_menu
");
$produk = mysqli_fetch_assoc($q_produk);

// hitung total toko yang belum dihapus
$q_toko = mysqli_query($conn, "
    SELECT COUNT(*) as total FROM tb_toko WHERE deleted = 0
");
$toko = mysqli_fetch_assoc($q_toko);

// ambil 5 produk terlaris berdasarkan total jumlah yang terjual dari tabel detail pesanan
$q_terlaris = mysqli_query($conn, "
    SELECT m.nama_menu, SUM(d.jumlah) as total_terjual
    FROM tb_detail_order d
    JOIN tb_menu m ON d.id_menu = m.id_menu
    GROUP BY d.id_menu
    ORDER BY total_terjual DESC
    LIMIT 5
");

// ambil total penjualan per kategori menu untuk ditampilkan sebagai diagram
$q_kategori = mysqli_query($conn, "
    SELECT m.kategori, SUM(d.subtotal) as total
    FROM tb_detail_order d
    JOIN tb_menu m ON d.id_menu = m.id_menu
    GROUP BY m.kategori
");

// tampung data kategori ke array dan hitung total keseluruhan untuk perhitungan persentase
$kategori_data = [];
$total_all = 0;

while ($row = mysqli_fetch_assoc($q_kategori)) {
    $kategori_data[] = $row;
    $total_all += $row['total'];
}
?>
