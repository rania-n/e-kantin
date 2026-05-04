<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Toko</title>
    <link rel="stylesheet" href="../../3. komponen/admin.css">
</head>
<body>
    <?php include '../../3. komponen/sidebaradmin.html'; ?>

    <div class="content">
        <div class="container">
            <h2>Edit Toko</h2>

            <form class="card">
                <div class="row">
                    <label for="nama_toko">Nama Toko</label>
                    <input type="text" id="nama_toko" value="Kantin Bu Siti" required>
                </div>

                <div class="row">
                    <label for="username">Username Pemilik</label>
                    <input type="text" id="username" value="seller1" required>
                </div>

                <div class="row">
                    <label for="status">Status</label>
                    <select id="status">
                        <option value="aktif" selected>Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="buttons">
                    <button type="submit" class="btn save">Simpan Perubahan</button>
                    <a href="toko.php" class="btn cancel">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>