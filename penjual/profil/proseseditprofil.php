<?php
/* file ini memproses permintaan edit profil akun dan info toko penjual.
   menangani upload foto toko baru jika ada, lalu update ke database */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

// pastikan request berasal dari form (POST), bukan akses langsung lewat browser
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profil.php"); exit;
}

// ambil id pengguna dan id toko dari session
$idpengguna   = (int)$_SESSION['id_user'];
$idtoko       = (int)$_SESSION['id_toko'];

// ambil dan bersihkan input dari form
$usernamebaru = trim($_POST['username']   ?? '');
$emailbaru    = trim($_POST['email']      ?? '');
$noteleponbaru= trim($_POST['no_telepon'] ?? '');
$namatokobaru = trim($_POST['nama_toko']  ?? '');
$err = []; // array untuk menampung semua pesan error validasi

// validasi panjang username
if (strlen($usernamebaru) < 6)                        $err[] = "Username minimal 6 karakter.";
if (strlen($usernamebaru) > 50)                       $err[] = "Username maksimal 50 karakter.";
// validasi format email menggunakan filter bawaan PHP
if (!filter_var($emailbaru, FILTER_VALIDATE_EMAIL))   $err[] = "Format email tidak valid.";
// validasi nomor telepon: ambil angkanya saja, harus 8–15 digit
$telpdigit = preg_replace('/\D/', '', $noteleponbaru);
if (strlen($telpdigit) < 8 || strlen($telpdigit) > 15) $err[] = "Nomor telepon harus 8–15 digit angka.";
// validasi nama toko tidak boleh kosong
if (empty($namatokobaru))                             $err[] = "Nama toko wajib diisi.";
if (strlen($namatokobaru) > 100)                      $err[] = "Nama toko maksimal 100 karakter.";

// proses foto toko baru jika ada file yang diunggah dan tidak ada error upload
$namafotobaru = null; // null berarti tidak ada foto baru
$adafoto = isset($_FILES['foto_toko']) && $_FILES['foto_toko']['error'] === UPLOAD_ERR_OK;
if ($adafoto) {
    $fotofile = $_FILES['foto_toko'];
    // ambil ekstensi file dan ubah ke huruf kecil
    $ekstensi = strtolower(pathinfo($fotofile['name'], PATHINFO_EXTENSION));
    $tipediizinkan = ['jpg','jpeg','png','webp'];
    $maksukuran = 2 * 1024 * 1024; // 2mb dalam satuan byte

    if (!in_array($ekstensi, $tipediizinkan)) {
        $err[] = "Format foto tidak didukung. Gunakan jpg, png, atau webp.";
    } elseif ($fotofile['size'] > $maksukuran) {
        $err[] = "Ukuran foto maksimal 2MB.";
    } else {
        // buat nama file unik menggunakan id toko dan waktu saat ini
        $namafotobaru = 'toko_' . $idtoko . '_' . time() . '.' . $ekstensi;
    }
}

// jika belum ada error, cek apakah username/email sudah digunakan akun lain
if (empty($err)) {
    $cd = $conn->prepare("SELECT id_user FROM tb_user WHERE (username=? OR email=?) AND id_user!=? AND deleted=0");
    $cd->bind_param("ssi", $usernamebaru, $emailbaru, $idpengguna); $cd->execute();
    if ($cd->get_result()->num_rows) $err[] = "Username atau email sudah digunakan akun lain.";
    $cd->close();
}

if (empty($err)) {
    // jika pengguna memilih untuk menghapus foto lama, hapus file dan set kolom foto_toko ke NULL
    if (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] == '1') {
        $qfoto = $conn->prepare("SELECT foto_toko FROM tb_toko WHERE id_toko = ?");
        $qfoto->bind_param("i", $idtoko);
        $qfoto->execute();
        $datatoko = $qfoto->get_result()->fetch_assoc();
        $fotolama = $datatoko['foto_toko'];
        $qfoto->close();

        if (!empty($fotolama) && file_exists("../../2. aset/profil/" . $fotolama)) {
            unlink("../../2. aset/profil/" . $fotolama);
        }

        $qupdatefoto = $conn->prepare("UPDATE tb_toko SET foto_toko = NULL WHERE id_toko = ?");
        $qupdatefoto->bind_param("i", $idtoko);
        $qupdatefoto->execute();
        $qupdatefoto->close();
    }

    // jika ada foto baru, pindahkan dari folder sementara ke folder aset profil
    if ($namafotobaru) {
        $targetdir = __DIR__ . '/../../2. aset/profil/';
        // buat folder jika belum ada (recursive = true, izin 0755)
        if (!is_dir($targetdir)) mkdir($targetdir, 0755, true);
        move_uploaded_file($_FILES['foto_toko']['tmp_name'], $targetdir . $namafotobaru);
    }

    // update data akun pengguna: username, email, dan nomor telepon
    $upd = $conn->prepare("UPDATE tb_user SET username=?, email=?, no_telepon=? WHERE id_user=?");
    $upd->bind_param("sssi", $usernamebaru, $emailbaru, $noteleponbaru, $idpengguna);
    $upd->execute(); $upd->close();

    // update data toko — query berbeda tergantung ada/tidak foto baru
    if ($namafotobaru) {
        // ada foto baru: update nama toko dan foto toko sekaligus
        $updt = $conn->prepare("UPDATE tb_toko SET nama_toko=?, foto_toko=? WHERE id_toko=?");
        $updt->bind_param("ssi", $namatokobaru, $namafotobaru, $idtoko);
    } else {
        // tidak ada foto baru: hanya update nama toko
        $updt = $conn->prepare("UPDATE tb_toko SET nama_toko=? WHERE id_toko=?");
        $updt->bind_param("si", $namatokobaru, $idtoko);
    }
    $updt->execute(); $updt->close();

    // perbarui session agar navbar dan halaman lain langsung mencerminkan perubahan
    $_SESSION['username']  = $usernamebaru;
    $_SESSION['email']     = $emailbaru;
    $_SESSION['nama_toko'] = $namatokobaru;

    // simpan flash sukses dan redirect ke halaman profil
    $_SESSION['flash']     = ['pesan' => 'Profil dan info toko berhasil diperbarui!', 'jenis' => 'sukses'];
    header("Location: profil.php"); exit;
} else {
    // gabungkan semua pesan error menjadi satu kalimat dan simpan ke flash
    $_SESSION['flash'] = ['pesan' => implode(' ', $err), 'jenis' => 'gagal'];
    // kembalikan ke tab edit agar pengguna bisa memperbaiki input
    header("Location: profil.php?tab=edit"); exit;
}
?>
