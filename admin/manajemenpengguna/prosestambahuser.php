<?php
/* proses tambah pengguna baru.
   mendukung semua role: penjual (dipilihkan kantin kosong), pembeli, admin.
   untuk penjual: tidak membuat toko baru, melainkan MENGISI kantin yang dipilih
   (UPDATE id_user dan nama_toko pada baris tb_toko yang kosong).
   file ini hanya menerima request POST dari form tambahuser.php. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tolak akses langsung (bukan POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: tambahuser.php"); exit; }

// ambil dan bersihkan data dari form
$username  = trim($_POST['username']  ?? '');
$email     = trim($_POST['email']     ?? '');
$namatoko  = trim($_POST['nama_toko'] ?? '');
$password  = $_POST['password'] ?? '';
$role      = $_POST['role'] ?? 'penjual';
$idkantin  = (int)($_POST['id_kantin'] ?? 0); // id_toko kantin yang dipilih untuk penjual

// siapkan data lama untuk dikembalikan ke form jika validasi gagal
$oldinput = ['username'=>$username,'email'=>$email,'role'=>$role,'id_kantin'=>$idkantin,'nama_toko'=>$namatoko];

// validasi role agar hanya nilai yang diizinkan diterima
if (!in_array($role, ['penjual','pembeli','admin'])) {
    flash('gagal','Peran tidak valid.');
    redirect('tambahuser.php');
}

// validasi panjang username: minimal 6, maksimal 50 karakter
if (strlen($username) < 6 || strlen($username) > 50) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Username harus 6–50 karakter.');
    redirect('tambahuser.php');
}

// validasi format username: hanya huruf, angka, titik, dan garis bawah — tanpa spasi
if (!preg_match('/^[a-zA-Z0-9_.]+$/', $username)) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Username hanya boleh berisi huruf, angka, titik (.), dan garis bawah (_). Tanpa spasi.');
    redirect('tambahuser.php');
}

// validasi format email menggunakan filter bawaan PHP
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Format email tidak valid.');
    redirect('tambahuser.php');
}

// validasi panjang password: minimal 8 karakter
if (strlen($password) < 8) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Password minimal 8 karakter.');
    redirect('tambahuser.php');
}

// validasi khusus penjual: wajib pilih kantin dan isi nama toko
if ($role === 'penjual') {
    if (!$idkantin) {
        $_SESSION['oldinput'] = $oldinput;
        flash('gagal','Penjual wajib memilih kantin yang tersedia.');
        redirect('tambahuser.php');
    }
    if (empty($namatoko)) {
        $_SESSION['oldinput'] = $oldinput;
        flash('gagal','Nama toko wajib diisi untuk Penjual.');
        redirect('tambahuser.php');
    }
}

// cek apakah username sudah dipakai (hanya pada akun yang belum dihapus)
$ck = $conn->prepare("SELECT id_user FROM tb_user WHERE username=? AND deleted=0");
$ck->bind_param("s", $username); $ck->execute();
if ($ck->get_result()->num_rows > 0) {
    $ck->close();
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Username sudah terdaftar.');
    redirect('tambahuser.php');
}
$ck->close();

// cek apakah email sudah dipakai
$ce = $conn->prepare("SELECT id_user FROM tb_user WHERE email=? AND deleted=0");
$ce->bind_param("s", $email); $ce->execute();
if ($ce->get_result()->num_rows > 0) {
    $ce->close();
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Email sudah digunakan.');
    redirect('tambahuser.php');
}
$ce->close();

// jika penjual: validasi bahwa kantin yang dipilih memang kosong (belum punya pemilik)
// ini mencegah kondisi race-condition jika dua admin tambah penjual bersamaan
if ($role === 'penjual') {
    // cek apakah kolom nomor_kantin sudah ada (migrasi sudah dijalankan)
    $cekkolom = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'nomor_kantin'");
    $migrasiSudah = ($cekkolom && $cekkolom->num_rows > 0);
    $kolomNomor = $migrasiSudah ? "nomor_kantin" : "NULL AS nomor_kantin";

    $cek = $conn->prepare(
        "SELECT id_toko, $kolomNomor FROM tb_toko
         WHERE id_toko=? AND id_user IS NULL AND deleted=0"
    );
    $cek->bind_param("i", $idkantin); $cek->execute();
    $kantindata = $cek->get_result()->fetch_assoc(); $cek->close();
    if (!$kantindata) {
        $_SESSION['oldinput'] = $oldinput;
        flash('gagal','Kantin yang dipilih sudah terisi atau tidak ditemukan. Pilih kantin lain.');
        redirect('tambahuser.php');
    }
    // label kantin untuk pesan sukses: gunakan nomor jika tersedia
    $nomorkantin = $kantindata['nomor_kantin'] !== null
        ? (int)$kantindata['nomor_kantin']
        : 0; // fallback sebelum migrasi
}

// cek apakah kolom tanggal_mulai sudah ada (untuk mencatat kapan penjual mulai di slot)
$cekmulai   = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'tanggal_mulai'");
$adamulai   = ($cekmulai && $cekmulai->num_rows > 0);
$setmulai   = $adamulai ? ", tanggal_mulai=NOW()" : "";

// hash password menggunakan bcrypt — tidak pernah simpan password polos
$hash = password_hash($password, PASSWORD_DEFAULT);

// masukkan pengguna baru ke database
$ins = $conn->prepare("INSERT INTO tb_user (username, email, password, role, deleted) VALUES (?,?,?,?,0)");
$ins->bind_param("ssss", $username, $email, $hash, $role);
if (!$ins->execute()) {
    $ins->close();
    flash('gagal','Gagal menyimpan pengguna. Coba lagi.');
    redirect('tambahuser.php');
}

// ambil id pengguna yang baru saja dibuat (diperlukan untuk mengisi kantin)
$iduser = $conn->insert_id;
$ins->close();

// jika role penjual: isi kantin yang dipilih dengan data penjual ini
// ini adalah UPDATE bukan INSERT — kantin sudah ada, hanya diisi pemiliknya
// tanggal_mulai dicatat agar statistik bisa difilter per masa jabatan penjual
if ($role === 'penjual') {
    $upk = $conn->prepare(
        "UPDATE tb_toko
         SET id_user=?, nama_toko=?, status_toko='tutup'{$setmulai}
         WHERE id_toko=? AND id_user IS NULL AND deleted=0"
    );
    // "isi" = int (id_user), string (nama_toko), int (id_toko)
    $upk->bind_param("isi", $iduser, $namatoko, $idkantin);
    $upk->execute(); $upk->close();

    $labelKantin = $nomorkantin > 0 ? "Kantin ke-{$nomorkantin}" : "kantin #$idkantin";
    flash('sukses', "Penjual \"$username\" berhasil ditambahkan di {$labelKantin} dengan toko \"$namatoko\".");
    redirect('user.php?role=penjual');
} else {
    flash('sukses', ucfirst($role) . " \"$username\" berhasil ditambahkan.");
    redirect('user.php?role=' . $role);
}

// fungsi pembantu: simpan flash message ke session
function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }

// fungsi pembantu: redirect ke url tertentu dan hentikan eksekusi
function redirect(string $url): void { header("Location: $url"); exit; }
?>
