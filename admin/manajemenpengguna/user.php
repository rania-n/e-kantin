<?php
include '../../1. koneksi/koneksi.php';

// Menangkap input pencarian dan filter tab
$keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($conn, $_GET['keyword']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';

// Query dasar - HANYA mengambil yang deleted = 0 (Belum dihapus)
$query_text = "SELECT * FROM tb_user WHERE deleted = 0";

// Logika Pencarian
if (!empty($keyword)) {
    $query_text .= " AND (username LIKE '%$keyword%' OR role LIKE '%$keyword%')";
}

// Logika Filter Tab
if ($role_filter != 'all') {
    $query_text .= " AND role = '$role_filter'";
}

$query_text .= " ORDER BY id_user DESC";
$result = mysqli_query($conn, $query_text);

// Menghitung jumlah untuk label Tab secara dinamis (Hanya yang belum dihapus)
$count_all = mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM tb_user WHERE deleted = 0"));
$count_admin = mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM tb_user WHERE role='admin' AND deleted = 0"));
$count_seller = mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM tb_user WHERE role='penjual' AND deleted = 0"));
$count_student = mysqli_num_rows(mysqli_query($conn, "SELECT id_user FROM tb_user WHERE role='pembeli' AND deleted = 0"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna</title>
    <link rel="stylesheet" href="../../3. komponen/admin.css">
</head>
<body>
    <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses_hapus') : ?>
        <script>alert('User dan Toko berhasil disembunyikan (Soft Delete)!');</script>
    <?php endif; ?>

    <?php include '../../3. komponen/sidebaradmin.html'; ?>

    <div class="content">
        <div class="header">
            <div>
                <h2>Manajemen Pengguna</h2>
                <p>Manage semua pengguna sistem e-Kantin</p>
            </div>
            <a href="tambah_user.php" class="btn">+ Tambah Penjual</a>
        </div>

        <div class="tabs">
            <input type="radio" name="tab" id="all" onclick="window.location.href='user.php?role=all'" <?php echo $role_filter == 'all' ? 'checked' : ''; ?>>
            <label for="all" class="tab">Semua (<?php echo $count_all; ?>)</label>

            <input type="radio" name="tab" id="admin" onclick="window.location.href='user.php?role=admin'" <?php echo $role_filter == 'admin' ? 'checked' : ''; ?>>
            <label for="admin" class="tab">Admin (<?php echo $count_admin; ?>)</label>

            <input type="radio" name="tab" id="seller" onclick="window.location.href='user.php?role=penjual'" <?php echo $role_filter == 'seller' ? 'checked' : ''; ?>>
            <label for="seller" class="tab">Penjual (<?php echo $count_seller; ?>)</label>

            <input type="radio" name="tab" id="student" onclick="window.location.href='user.php?role=pembeli'" <?php echo $role_filter == 'pembeli' ? 'checked' : ''; ?>>
            <label for="student" class="tab">Pembeli (<?php echo $count_student; ?>)</label>
        </div>

        <div class="search">
            <form action="user.php" method="GET">
                <input type="hidden" name="role" value="<?php echo $role_filter; ?>">
                <input type="text" name="keyword" placeholder="Cari nama atau username..." value="<?php echo htmlspecialchars($keyword); ?>">
            </form>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th class="center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0) : ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                        <tr>
                            <td>
                                <div class="user">
                                    <div class="avatar">👤</div>
                                    <div>
                                        <div><?php echo htmlspecialchars($row['username']); ?></div>
                                        <small>ID: #<?php echo $row['id_user']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><span class="badge role"><?php echo ucfirst($row['role']); ?></span></td>
                            <td>
                                <div class="aksi-box">
                                    <a href="viewuser.php?id=<?php echo $row['id_user']; ?>" class="aksi-icon">👁</a>
                                    <a href="edituser.php?id=<?php echo $row['id_user']; ?>" class="aksi-icon">✏️</a>
                                    
                                    <?php if ($row['role'] == 'penjual') : ?>
                                        <a href="detail_toko.php?id_user=<?php echo $row['id_user']; ?>" class="aksi-icon">🏪</a>
                                    <?php endif; ?>

                                    <a href="hapususer.php?id=<?php echo $row['id_user']; ?>" 
                                       class="aksi-icon delete" 
                                       onclick="return confirm('Yakin ingin menghapus user dan tokonya secara aman?')">🗑</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="center">Data tidak ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>