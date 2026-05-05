<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Pengguna</title>

<style>

:root {
    --primary: #643843;
    --secondary: #99627A;
    --bg-light: #EFD9D4;
    --white: #F8EBF1;
    --text-main: #643843;
    --text-muted: #99627A;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg-light);
    color: var(--text-main);
}

.container {
    max-width: 900px;
    margin: auto;
    padding: 20px;
}

.header h2 {
    font-size: 24px;
    margin-bottom: 5px;
}

.header p {
    color: var(--text-muted);
    font-size: 14px;
    margin-bottom: 20px;
}

.card {
    background: white;
    padding: 20px;
    border-radius: 20px;
    box-shadow: 0 4px 12px rgba(100, 56, 67, 0.08);
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    font-size: 14px;
    color: var(--secondary);
    margin-bottom: 6px;
    font-weight: 600;
}

.form-group input {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    border: 1px solid #E7CBCB;
    background: #f9f9f9;
    font-size: 14px;
}

.form-group input:focus {
    outline: none;
    border-color: var(--secondary);
    background: white;
}

.btn {
    width: 100%;
    padding: 14px;
    border-radius: 12px;
    border: none;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 10px;
}

.btn-save {
    background: var(--primary);
    color: white;
}

.btn-cancel {
    background: #E7CBCB;
    color: var(--primary);
}

/* MENU LIST */
.menu-item {
    padding: 14px;
    border-radius: 12px;
    border: 1px solid #E7CBCB;
    margin-bottom: 10px;
    cursor: pointer;
    transition: 0.3s;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.menu-item:hover {
    background: #fdf2f5;
}

/* 🔥 HAPUS AKUN */
.menu-item.delete {
    color: #b91c1c;
    border-color: #f5c2c2;
}

.menu-item.delete:hover {
    background: #ffe5e5;
}

.menu-item.logout {
    color: #643843; 
    background: white; 
}


@media (max-width: 600px) {
    .container {
        padding: 15px;
    }
}

</style>
    <!-- <link rel="stylesheet" href="../../3. komponen/pembeli.css"> -->
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