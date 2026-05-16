<?php
include '../../1. koneksi/koneksi.php';

// 1. Ambil ID User dari URL
$id_user = isset($_GET['id_user']) ? mysqli_real_escape_string($conn, $_GET['id_user']) : '';

if (empty($id_user)) {
    header("Location: user.php");
    exit();
}

// 2. Ambil data Toko & User
$query_toko = mysqli_query($conn, "SELECT t.*, u.username, u.email 
                                   FROM tb_toko t 
                                   JOIN tb_user u ON t.id_user = u.id_user 
                                   WHERE t.id_user = '$id_user'");
$data = mysqli_fetch_assoc($query_toko);

// 3. Logika Grafik Penjualan (Anti-Error)
$grafik_label = ['Data Kosong'];
$grafik_data = [0];

// Cek apakah tabel tb_transaksi sudah dibuat di database
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tb_transaksi'");
if (mysqli_num_rows($check_table) > 0) {
    // Jika tabel ada, ambil data 7 hari terakhir
    $query_grafik = mysqli_query($conn, "SELECT DATE(tanggal) as tgl, SUM(total_harga) as total 
                                         FROM tb_transaksi 
                                         WHERE id_user = '$id_user' 
                                         GROUP BY DATE(tanggal) 
                                         ORDER BY tgl DESC LIMIT 7");

    if (mysqli_num_rows($query_grafik) > 0) {
        $grafik_label = [];
        $grafik_data = [];
        while ($row = mysqli_fetch_assoc($query_grafik)) {
            $grafik_label[] = date('d M', strtotime($row['tgl']));
            $grafik_data[] = $row['total'];
        }
        // Balik urutan agar hari terlama di kiri
        $grafik_label = array_reverse($grafik_label);
        $grafik_data = array_reverse($grafik_data);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Toko - <?php echo isset($data['nama_toko']) ? htmlspecialchars($data['nama_toko']) : 'Tidak Ditemukan'; ?></title>
    <link rel="stylesheet" href="../../3. komponen/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-container { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-top: 20px; }
        .info-card, .chart-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .info-item { margin-bottom: 15px; }
        .info-item label { display: block; color: #888; font-size: 12px; margin-bottom: 5px; }
        .info-item p { font-weight: bold; font-size: 16px; margin: 0; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-back { background: #eee; color: #333; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 14px; }
        .badge-active { background: #e8f5e9; color: #2e7d32; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
    </style>
</head>
<body>
    <?php include '../../3. komponen/sidebaradmin.html'; ?>

    <div class="content">
        <?php if ($data) : ?>
            <div class="header">
                <div>
                    <h2>Statistik <?php echo htmlspecialchars($data['nama_toko']); ?></h2>
                    <p>Pemilik: <?php echo htmlspecialchars($data['username']); ?> (<?php echo $data['email']; ?>)</p>
                </div>
                <a href="user.php" class="btn-back">← Kembali</a>
            </div>

            <div class="stats-container">
                <!-- Bagian Kiri: Info -->
                <div class="info-card">
                    <h3>Informasi Toko</h3>
                    <hr style="border: 0.5px solid #f0f0f0; margin: 20px 0;">
                    <div class="info-item">
                        <label>Nama Toko</label>
                        <p><?php echo htmlspecialchars($data['nama_toko']); ?></p>
                    </div>
                    <div class="info-item">
                        <label>ID Penjual</label>
                        <p>#<?php echo $data['id_user']; ?></p>
                    </div>
                    <div class="info-item">
                        <label>Status</label>
                        <p><span class="badge-active">Aktif (Live)</span></p>
                    </div>
                </div>

                <!-- Bagian Kanan: Grafik -->
                <div class="chart-card">
                    <h3>Pendapatan 7 Hari Terakhir</h3>
                    <div style="margin-top: 20px;">
                        <canvas id="salesChart" height="150"></canvas>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div style="text-align: center; padding: 50px;">
                <h2>Data Toko Tidak Ditemukan</h2>
                <p>User ini mungkin tidak memiliki role penjual atau data toko terhapus.</p>
                <a href="user.php" class="btn-back">Kembali ke Daftar User</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($grafik_label); ?>,
                datasets: [{
                    label: 'Total Pendapatan',
                    data: <?php echo json_encode($grafik_data); ?>,
                    borderColor: '#845b5b',
                    backgroundColor: 'rgba(132, 91, 91, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return 'Rp ' + value.toLocaleString(); }
                        }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>
</html>