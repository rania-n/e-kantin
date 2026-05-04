<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="../../3. komponen/admin.css">
</head>
<body>
    <?php include '../../3. komponen/sidebaradmin.html'; ?>

    <div class="content">
        <div class="container">
            <h2>Edit Pengguna</h2>

            <form class="card">
                <div class="row">
                    <label>Nama</label>
                    <input type="text" value="Ahmad Rizki">
                </div>
                <div class="row">
                    <label>Username</label>
                    <input type="text" value="ahmad">
                </div>
                <div class="row">
                    <label>Email</label>
                    <input type="email" value="ahmad@sekolah.sch.id">
                </div>
                <div class="row">
                    <label>Password</label>
                    <input type="text" value="123456">
                </div>
                <div class="row">
                    <label>Role</label>
                    <select>
                        <option>Pembeli</option>
                        <option>Penjual</option>
                    </select>
                </div>
                <div class="row">
                    <label>Status</label>
                    <select>
                        <option>Aktif</option>
                        <option>Nonaktif</option>
                    </select>
                </div>

                <div class="buttons">
                    <button type="submit" class="btn save">Simpan</button>
                    <a href="user.php" class="btn cancel">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>