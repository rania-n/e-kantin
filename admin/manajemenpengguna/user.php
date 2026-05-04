<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna</title>
    <link rel="stylesheet" href="../../3. komponen/admin.css">
</head>
<body>
    <?php include '../../3. komponen/sidebaradmin.html'; ?>

    <div class="content">
        <div class="header">
            <div>
                <h2>Manajemen Pengguna</h2>
                <p>Manage semua pengguna sistem e-Kantin</p>
            </div>
            <a href="#" class="btn">+ Tambah Penjual</a>
        </div>

        <div class="tabs">
            <input type="radio" name="tab" id="all" checked>
            <label for="all" class="tab">Semua (4)</label>

            <input type="radio" name="tab" id="admin">
            <label for="admin" class="tab">Admin (1)</label>

            <input type="radio" name="tab" id="seller">
            <label for="seller" class="tab">Penjual (2)</label>

            <input type="radio" name="tab" id="student">
            <label for="student" class="tab">Pembeli (1)</label>
        </div>

        <div class="search">
            <input type="text" placeholder="Cari nama, email, atau username...">
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data contoh -->
                    <tr>
                        <td>
                            <div class="user">
                                <div class="avatar">👤</div>
                                <div>
                                    <div>Admin Kantin</div>
                                    <small>admin@sekolah.sch.id</small>
                                </div>
                            </div>
                        </td>
                        <td>admin</td>
                        <td><span class="badge role">Admin</span></td>
                        <td class="center"><span class="badge status">Aktif</span></td>
                        <td>
                            <div class="aksi-box">
                                <a href="viewuser.php" class="aksi-icon">👁</a>
                                <a href="edituser.php" class="aksi-icon">✏️</a>
                                <a href="#" class="aksi-icon delete">🗑</a>
                            </div>
                        </td>
                    </tr>
                    <!-- Sisanya sama seperti sebelumnya (bisa copy-paste) -->
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>