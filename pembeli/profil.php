<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../3. komponen/pembeli.css">
<title>Edit Pengguna</title>
</head>
</head>

<body>

<div class="container">

    <div class="header">
        <h2>Edit Pengguna</h2>
        <p>Ubah data akun kamu</p>
    </div>

    <!-- CARD EDIT -->
    <div class="card">
        <form>

            <div class="form-group">
                <label>Username</label>
                <input type="text" value="ahmad">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" value="ahmad@sekolah.sch.id">
            </div>

            <button class="btn btn-save">Simpan</button>
            <button type="button" class="btn btn-cancel">Batal</button>

        </form>
    </div>

    <!-- CARD PENGATURAN -->
    <div class="card">
        <h3 style="margin-bottom:15px;">Pengaturan Lainnya</h3>

        <div class="menu-item">
            <span>📄 Riwayat Notifikasi</span>
            <span>›</span>
        </div>

        <div class="menu-item">
            <span>❓ Pusat Bantuan</span>
            <span>›</span>
        </div>

        <div class="menu-item delete">
            <span>🗑️ Hapus Akun</span>
            <span>›</span>
        </div>

    </div>

    <div class="menu-item logout">
    <span class="logout-content">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Keluar
    </span>
</div>

</div>

</body>
</html>