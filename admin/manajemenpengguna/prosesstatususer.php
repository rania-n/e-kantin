<?php
/* proses aktif/nonaktif (suspend) akun pengguna — admin only.
   dipanggil dari tombol di daftar pengguna (user.php) SETELAH konfirmasi modal.
   akun nonaktif tidak bisa login dan langsung di-logout jika sedang aktif. */

include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// helper: simpan flash + redirect kembali ke daftar pengguna, lalu hentikan script
function kembaliUser(string $pesan, string $jenis, string $role): void {
    $_SESSION['flash'] = ['pesan' => $pesan, 'jenis' => $jenis];
    // pertahankan tab role yang sedang dibuka admin
    $r = in_array($role, ['semua','admin','penjual','pembeli','terhapus'], true) ? $role : 'semua';
    header("Location: user.php?role=" . urlencode($r));
    exit;
}

// role tab asal (untuk kembali ke tab yang sama)
$role = $_POST['role'] ?? $_GET['role'] ?? 'semua';

// tolak akses langsung (bukan POST) — harus lewat tombol konfirmasi
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kembaliUser('Akses tidak valid.', 'gagal', $role);
}

// ambil id user target, konversi ke integer untuk keamanan
$iduser = (int)($_POST['id_user'] ?? 0);
if (!$iduser) {
    kembaliUser('ID pengguna tidak valid.', 'gagal', $role);
}

// ambil data user target: role + status akun saat ini
$cek = $conn->prepare("SELECT username, role, status_akun FROM tb_user WHERE id_user=? AND deleted=0");
$cek->bind_param("i", $iduser); $cek->execute();
$target = $cek->get_result()->fetch_assoc(); $cek->close();

if (!$target) {
    kembaliUser('Pengguna tidak ditemukan.', 'gagal', $role);
}

// admin tidak boleh menonaktifkan sesama admin (mencegah saling kunci akses)
if ($target['role'] === 'admin') {
    kembaliUser('Akun admin tidak bisa dinonaktifkan.', 'gagal', $role);
}

// tentukan status baru = kebalikan dari status sekarang
$statussekarang = $target['status_akun'] ?? 'aktif';
$statusbaru     = $statussekarang === 'aktif' ? 'nonaktif' : 'aktif';

// update status akun
$upd = $conn->prepare("UPDATE tb_user SET status_akun=? WHERE id_user=?");
$upd->bind_param("si", $statusbaru, $iduser);
$upd->execute(); $upd->close();

// susun pesan sesuai aksi yang dilakukan
$pesan = $statusbaru === 'nonaktif'
    ? "Akun \"{$target['username']}\" berhasil dinonaktifkan. Pengguna tidak bisa login sampai diaktifkan kembali."
    : "Akun \"{$target['username']}\" berhasil diaktifkan kembali.";

kembaliUser($pesan, 'sukses', $role);
?>
