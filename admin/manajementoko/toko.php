<?php
include '../../1. koneksi/koneksi.php'; 
$query = "SELECT tb_toko.*, tb_user.username 
          FROM tb_toko 
          INNER JOIN tb_user ON tb_toko.id_user = tb_user.id_user 
          WHERE tb_toko.deleted = 0 
          ORDER BY tb_toko.id_toko DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Toko</title>
    <link rel="stylesheet" href="../../3. komponen/admin.css">
</head>
<body>
    <?php include '../../3. komponen/sidebaradmin.html'; ?>

    <div class="content">
        <div class="header">
            <div>
                <h2>Manajemen Toko</h2>
                <p>Manage semua toko dalam sistem e-Kantin</p>
            </div>
            <!-- Pastikan tombol ini mengarah ke file tambah yang kita buat tadi -->
        </div>

        <div class="search">
            <input type="text" placeholder="Cari nama toko atau username pemilik...">
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Nama Toko</th>
                        <th>Username Pemilik</th>
                        <th class="center">Status</th>
                        <th class="center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) { 
                    ?>
                    <tr>
                        <td>
                            <div class="user">
                                <div class="avatar">🏪</div>
                                <div>
                                    <div><?php echo htmlspecialchars($row['nama_toko']); ?></div>
                                    <small>ID: #<?php echo $row['id_toko']; ?></small>
                                </div>
                            </div>
                        </td>
                        <!-- Menampilkan Username Pemilik hasil JOIN -->
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td class="center"><span class="badge status">Aktif</span></td>
                        <td class="center">
                            <div class="aksi-box">
                                <!-- Mengirim ID Toko ke halaman view, edit, dan hapus -->
                                <a href="viewtoko.php?id=<?php echo $row['id_toko']; ?>" class="aksi-icon" title="Lihat">👁</a>
                                <a href="edittoko.php?id=<?php echo $row['id_toko']; ?>" class="aksi-icon" title="Edit">✏️</a>
                                <a href="hapustoko.php?id=<?php echo $row['id_toko']; ?>" 
                                   class="aksi-icon delete" 
                                   title="Hapus"
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus toko <?php echo $row['nama_toko']; ?>?')">🗑</a>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        } 
                    } else {
                        // Tampilan jika database masih kosong
                        echo "<tr><td colspan='4' class='center'>Belum ada data toko terdaftar.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>