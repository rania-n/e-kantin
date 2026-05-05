<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail User</title>
    <link rel="stylesheet" href="../../3. komponen/admin.css">
</head>
<body>
    <?php include '../../3. komponen/sidebaradmin.html'; ?>

    <div class="content">
            <h2 style="margin-bottom: 24px;">Detail Pengguna</h2>

            <div class="card">
                <div class="row">
                    <label>Nama</label>
                    <p>Ahmad Rizki</p>
                </div>
                <div class="row">
                    <label>Username</label>
                    <p>ahmad</p>
                </div>
                <div class="row">
                    <label>Email</label>
                    <p>ahmad@sekolah.sch.id</p>
                </div>
                <div class="row">
                    <label>Password</label>
                    <p>••••••••</p>
                </div>
                <div class="row">
                    <label>Role</label>
                    <p><span class="badge role">Pembeli</span></p>
                </div>
                <div class="row">
                    <label>Status</label>
                    <p><span class="badge status">Aktif</span></p>
                </div>
                <div class="row">
                    <label>Total Pembelian</label>
                    <p>12 transaksi</p>
                </div>
            </div>

            <div class="actions">
                <a href="edituser.php" class="btn edit">✏ Edit</a>
                <a href="user.php" class="btn back">← Kembali</a>
            </div>
    </div>
</body>
</html>