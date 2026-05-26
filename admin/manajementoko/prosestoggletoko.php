<?php
/* proses toggle status toko — mengubah status toko dari 'buka' ke 'tutup' atau sebaliknya.
   dipanggil dari tombol di tabel pengguna (user.php) tanpa halaman konfirmasi.
   setelah selesai, selalu redirect kembali ke daftar pengguna penjual. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tolak akses langsung (bukan POST) — harus dari klik tombol toggle
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../admin/manajemenpengguna/user.php?role=penjual");
    exit;
}

// ambil id toko dari form, konversi ke integer
$id_toko = isset($_POST['id_toko']) ? (int)$_POST['id_toko'] : 0;

// validasi: id harus ada
if (!$id_toko) {
    $_SESSION['flash'] = ['pesan'=>'ID toko tidak valid.','jenis'=>'gagal'];
    header("Location: ../../admin/manajemenpengguna/user.php?role=penjual");
    exit;
}

// ambil status toko saat ini untuk menentukan nilai kebalikannya
$qt = $conn->prepare("SELECT status_toko FROM tb_toko WHERE id_toko=? AND deleted=0");
$qt->bind_param("i", $id_toko); $qt->execute();
$toko = $qt->get_result()->fetch_assoc(); $qt->close();

// jika toko tidak ditemukan (mungkin sudah dihapus)
if (!$toko) {
    $_SESSION['flash'] = ['pesan'=>'Toko tidak ditemukan.','jenis'=>'gagal'];
    header("Location: ../../admin/manajemenpengguna/user.php?role=penjual");
    exit;
}

// tentukan status baru: kebalikan dari status saat ini
// jika sekarang 'buka' maka jadi 'tutup', dan sebaliknya
$statusbaru = $toko['status_toko'] === 'buka' ? 'tutup' : 'buka';

// update status toko di database
$qu = $conn->prepare("UPDATE tb_toko SET status_toko=? WHERE id_toko=?");
$qu->bind_param("si", $statusbaru, $id_toko);
$qu->execute(); $qu->close();

// simpan flash message yang menyebutkan status baru toko
$_SESSION['flash'] = ['pesan'=>"Status toko berhasil diubah menjadi \"$statusbaru\".", 'jenis'=>'sukses'];

// kembali ke viewuser.php jika dipanggil dari halaman detail pengguna
if (!empty($_POST['id_user_ref']) && (int)$_POST['id_user_ref'] > 0) {
    header("Location: ../../admin/manajemenpengguna/viewuser.php?id=" . (int)$_POST['id_user_ref']);
} else {
    header("Location: ../../admin/manajemenpengguna/user.php?role=penjual");
}
exit;
