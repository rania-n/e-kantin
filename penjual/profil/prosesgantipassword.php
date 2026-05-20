<?php
/* file ini memproses permintaan ganti password penjual.
   verifikasi password lama, validasi password baru, lalu simpan hash yang baru */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

// pastikan request berasal dari form (POST), bukan akses langsung lewat browser
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profil.php"); exit;
}

// ambil id pengguna dari session (sudah divalidasi oleh guardpenjual.php)
$idpengguna = (int)$_SESSION['id_user'];

// ambil input dari form
$passlama   = $_POST['password_lama']  ?? '';
$passbaru   = $_POST['password_baru']  ?? '';
$konfirmasi = $_POST['konfirmasi']     ?? '';

$err = '';

// validasi: semua kolom harus diisi
if (empty($passlama) || empty($passbaru) || empty($konfirmasi)) {
    $err = "Semua kolom wajib diisi.";
// validasi: password baru minimal 8 karakter
} elseif (strlen($passbaru) < 8) {
    $err = "Password baru minimal 8 karakter.";
// validasi: password baru maksimal 100 karakter
} elseif (strlen($passbaru) > 100) {
    $err = "Password baru terlalu panjang (maksimal 100 karakter).";
// validasi: password baru dan konfirmasi harus sama persis
} elseif ($passbaru !== $konfirmasi) {
    $err = "Konfirmasi password tidak cocok.";
} else {
    // ambil hash password yang tersimpan di database
    $qu = $conn->prepare("SELECT password FROM tb_user WHERE id_user=? AND deleted=0");
    $qu->bind_param("i", $idpengguna); $qu->execute();
    $passdb = $qu->get_result()->fetch_row()[0] ?? ''; $qu->close();

    // verifikasi password lama menggunakan password_verify (cocokkan dengan hash bcrypt)
    if (!password_verify($passlama, $passdb)) {
        $err = "Password lama salah.";
    } else {
        // buat hash bcrypt dari password baru — PASSWORD_BCRYPT adalah algoritma yang aman
        $hash = password_hash($passbaru, PASSWORD_BCRYPT);

        // simpan hash baru ke database
        $upd  = $conn->prepare("UPDATE tb_user SET password=? WHERE id_user=?");
        $upd->bind_param("si", $hash, $idpengguna); $upd->execute(); $upd->close();

        // simpan flash message sukses dan redirect ke halaman profil
        $_SESSION['flash'] = ['pesan' => 'Password berhasil diubah!', 'jenis' => 'sukses'];
        header("Location: profil.php"); exit;
    }
}

// jika ada error, simpan pesan ke flash dan kembali ke tab password
$_SESSION['flash'] = ['pesan' => $err, 'jenis' => 'gagal'];
header("Location: profil.php?tab=password"); exit;
?>
