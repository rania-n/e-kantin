<?php
session_start();
include '../../1. koneksi/koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'penjual') {
    header("Location: ../../4. autentifikasi/login.php");
    exit();
}

// Filter & Search
$filter = $_GET['filter'] ?? 'Semua';
$search = trim($_GET['search'] ?? '');

// Notifikasi
$pesan = $_GET['pesan'] ?? '';
$tipe  = $_GET['tipe'] ?? '';

// Query utama pesanan
$query = "SELECT o.*, u.username, u.email 
          FROM tb_order o 
          JOIN tb_user u ON o.id_user = u.id_user 
          WHERE o.deleted = 0";

if ($filter !== 'Semua') {
    $status_map = [
        'Menunggu'    => 'menunggu',
        'Diproses'    => 'diproses',
        'Selesai'     => 'selesai',
        'Dibatalkan'  => 'dibatalkan'
    ];
    if (isset($status_map[$filter])) {
        $query .= " AND o.status_order = '" . $status_map[$filter] . "'";
    }
}

if ($search !== '') {
    $query .= " AND (o.id_order LIKE '%$search%' OR u.username LIKE '%$search%')";
}

$query .= " ORDER BY o.tanggal_order DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - e-Kantin</title>
    <link rel="stylesheet" href="../../3. komponen/penjual.css">
    <style>
        .modal { display: none; }
        .modal:target { display: flex; }
        .alert { padding: 12px; margin: 15px 0; border-radius: 6px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger  { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<?php include '../../3. komponen/sidebarpenjual.html'; ?>

<div class="main">
    <div class="header">
        <div>
            <h1>Manajemen Pesanan</h1>
            <p>Kelola dan proses pesanan dari siswa dan guru</p>
        </div>
    </div>

    <?php if($pesan): ?>
        <div class="alert alert-<?= $tipe === 'success' ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <div class="top-bar">
        <div class="search-box">
            <form method="get" style="display:flex;">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Cari pesanan atau nama pelanggan...">
                <button type="submit" style="margin-left:8px;">Cari</button>
            </form>
        </div>

        <!-- FILTER TABS -->
        <div class="filter-tabs">
            <?php
            $tabs = ['Semua','Menunggu','Diproses','Selesai','Dibatalkan'];
            foreach ($tabs as $tab):
                $active = ($filter === $tab) ? 'active' : '';
                // Hitung jumlah (untuk tampilan)
                $count_query = "SELECT COUNT(*) as total FROM tb_order WHERE deleted = 0";
                if ($tab !== 'Semua') {
                    $st = strtolower($tab);
                    $count_query .= " AND status_order = '$st'";
                }
                $count_res = mysqli_query($conn, $count_query);
                $count = mysqli_fetch_assoc($count_res)['total'];
            ?>
                <form method="get" style="display:inline-block;margin:0;">
                    <input type="hidden" name="filter" value="<?= $tab ?>">
                    <button type="submit" class="<?= $active ?>"><?= $tab ?> (<?= $count ?>)</button>
                </form>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="orders-grid">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($order = mysqli_fetch_assoc($result)): ?>
                <?php
                // Ambil detail item
                $detail_query = "SELECT d.jumlah, m.nama_menu, d.harga_satuan 
                                 FROM tb_detail_order d 
                                 JOIN tb_menu m ON d.id_menu = m.id_menu 
                                 WHERE d.id_order = " . (int)$order['id_order'];
                $detail_res = mysqli_query($conn, $detail_query);
                ?>
                <div class="order-card">
                    <div class="order-header">
                        <span class="order-id">ORD-<?= str_pad($order['id_order'], 3, '0', STR_PAD_LEFT) ?></span>
                        <span class="status <?= strtolower($order['status_order']) ?>">
                            <?= ucfirst($order['status_order']) ?>
                        </span>
                    </div>
                    <div class="customer"><?= htmlspecialchars($order['username']) ?> <small><?= htmlspecialchars($order['email']) ?></small></div>
                    <div class="time">Pesan: <?= date('H:i', strtotime($order['tanggal_order'])) ?> | Ambil: 12:00</div>
                    
                    <div class="items">
                        <?php while($item = mysqli_fetch_assoc($detail_res)): ?>
                            <div class="item-row">
                                <span><?= $item['jumlah'] ?>x <?= htmlspecialchars($item['nama_menu']) ?></span>
                                <span>Rp <?= number_format($item['harga_satuan'] * $item['jumlah'], 0, ',', '.') ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="total">Total: Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></div>
                    
                    <div class="actions">
                        <?php if($order['status_order'] === 'menunggu'): ?>
                            <a href="prosesmanajemenpesanan.php?action=proses&id=<?= $order['id_order'] ?>&filter=<?= urlencode($filter) ?>" 
                               class="btn-process" onclick="return confirm('Proses pesanan ini?')">Proses</a>
                            <a href="prosesmanajemenpesanan.php?action=tolak&id=<?= $order['id_order'] ?>&filter=<?= urlencode($filter) ?>" 
                               class="btn-reject" onclick="return confirm('Tolak pesanan ini?')">Tolak</a>
                        <?php elseif($order['status_order'] === 'diproses'): ?>
                            <a href="prosesmanajemenpesanan.php?action=selesai&id=<?= $order['id_order'] ?>&filter=<?= urlencode($filter) ?>" 
                               class="btn-process">Tandai Selesai</a>
                        <?php elseif($order['status_order'] === 'selesai'): ?>
                            <button class="btn-detail">✓ Pesanan Selesai</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="grid-column:1/-1; text-align:center; padding:40px; color:#888;">
                Tidak ada pesanan ditemukan.
            </p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>