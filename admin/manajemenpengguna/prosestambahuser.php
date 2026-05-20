<?php
/* proses tambah pengguna baru — mendukung semua role: penjual (+ toko), pembeli, admin.
   file ini hanya menerima request POST dari form tambahuser.php.
   melakukan validasi, cek duplikasi, simpan ke database, lalu redirect. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tolak akses langsung (bukan POST) — hanya terima dari pengiriman form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: tambahuser.php"); exit; }

// ambil dan bersihkan data dari form
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email']    ?? '');
$namatoko = trim($_POST['nama_toko'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? 'penjual';

// validasi role agar hanya nilai yang diizinkan diterima
if (!in_array($role, ['penjual','pembeli','admin'])) { flash('gagal','Peran tidak valid.'); redirect('tambahuser.php'); }

// validasi panjang username: minimal 6, maksimal 50 karakter
if (strlen($username) < 6 || strlen($username) > 50) { flash('gagal','Username harus 6–50 karakter.'); redirect('tambahuser.php'); }

// validasi format email menggunakan filter bawaan php
if (!filter_var($email, FILTER_VALIDATE_EMAIL))        { flash('gagal','Format email tidak valid.'); redirect('tambahuser.php'); }

// validasi panjang password: minimal 8 karakter
if (strlen($password) < 8)                             { flash('gagal','Password minimal 8 karakter.'); redirect('tambahuser.php'); }

// penjual wajib menyertakan nama toko
if ($role === 'penjual' && empty($namatoko))           { flash('gagal','Nama toko wajib diisi untuk Penjual.'); redirect('tambahuser.php'); }

// cek apakah username sudah dipakai oleh pengguna lain yang belum dihapus
$ck = $conn->prepare("SELECT id_user FROM tb_user WHERE username=? AND deleted=0");
$ck->bind_param("s", $username); $ck->execute();
if ($ck->get_result()->num_rows > 0) { $ck->close(); flash('gagal','Username sudah terdaftar.'); redirect('tambahuser.php'); }
$ck->close();

// cek apakah email sudah dipakai oleh pengguna lain
$ce = $conn->prepare("SELECT id_user FROM tb_user WHERE email=? AND deleted=0");
$ce->bind_param("s", $email); $ce->execute();
if ($ce->get_result()->num_rows > 0) { $ce->close(); flash('gagal','Email sudah digunakan.'); redirect('tambahuser.php'); }
$ce->close();

// hash password menggunakan algoritma yang kuat (bcrypt) — tidak pernah simpan password polos
$hash = password_hash($password, PASSWORD_DEFAULT);

// masukkan pengguna baru ke database
$ins = $conn->prepare("INSERT INTO tb_user (username, email, password, role, deleted) VALUES (?,?,?,?,0)");
$ins->bind_param("ssss", $username, $email, $hash, $role);
if (!$ins->execute()) { $ins->close(); flash('gagal','Gagal menyimpan pengguna. Coba lagi.'); redirect('tambahuser.php'); }

// ambil id pengguna yang baru saja dibuat (diperlukan untuk membuat toko)
$iduser = $conn->insert_id;
$ins->close();

// jika role penjual dan nama toko diisi, buat toko baru yang dikaitkan ke akun ini
if ($role === 'penjual' && !empty($namatoko)) {
    // status_toko diset 'tutup' by default, penjual bisa mengubahnya sendiri nanti
    $insto = $conn->prepare("INSERT INTO tb_toko (id_user, nama_toko, status_toko, deleted) VALUES (?,?,'tutup',0)");
    $insto->bind_param("is", $iduser, $namatoko);
    $insto->execute(); $insto->close();
    flash('sukses', "Penjual \"$username\" berhasil ditambahkan beserta toko \"$namatoko\".");
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
