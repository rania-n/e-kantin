<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Toko</title>
    <link rel="stylesheet" href="toko.css">
</head>

<body>
<?php include '../../3. komponen/sidebaradmin.html'; ?>

<div class="content">

    <div class="header">
        <div>
            <h2>Manajemen Toko</h2>
            <p>Manage semua toko dalam sistem e-Kantin</p>
        </div>
        <a href="#" class="btn">+ Tambah Toko</a>
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

                <tr>
                    <td>
                        <div class="user">
                            <div class="avatar">🏪</div>
                            <div>
                                <div>Kantin Bu Siti</div>
                                <small>Makanan & Minuman</small>
                            </div>
                        </div>
                    </td>
                    <td>seller1</td>
                    <td class="center">
                        <span class="badge status">Aktif</span>
                    </td>
                    <td class="center">
                        <div class="aksi-box">
                            <a href="viewtoko.php" class="aksi-icon">👁</a>
                            <a href="edittoko.php" class="aksi-icon">✏️</a>
                            <a href="#" class="aksi-icon delete">🗑</a>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td>
                        <div class="user">
                            <div class="avatar">🏪</div>
                            <div>
                                <div>Kantin Pak Dian</div>
                                <small>Makanan & Minuman</small>
                            </div>
                        </div>
                    </td>
                    <td>seller2</td>
                    <td class="center">
                        <span class="badge status">Aktif</span>
                    </td>
                    <td class="center">
                        <div class="aksi-box">
                            <a href="viewtoko.php" class="aksi-icon">👁</a>
                            <a href="edittoko.php" class="aksi-icon">✏️</a>
                            <a href="#" class="aksi-icon delete">🗑</a>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td>
                        <div class="user">
                            <div class="avatar">🏪</div>
                            <div>
                                <div>Kantin Kak Ros</div>
                                <small>Makanan & Minuman</small>
                            </div>
                        </div>
                    </td>
                    <td>seller3</td>
                    <td class="center">
                        <span class="badge status">Aktif</span>
                    </td>
                    <td class="center">
                        <div class="aksi-box">
                            <a href="viewtoko.php" class="aksi-icon">👁</a>
                            <a href="edittoko.php" class="aksi-icon">✏️</a>
                            <a href="#" class="aksi-icon delete">🗑</a>
                        </div>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>

</div>
</body>
</html>