<?php
/* proses edit pengguna oleh admin.
   field yang diproses berbeda per peran:
     - penjual : username, email, nama_toko, foto_toko, password
     - pembeli : username, email, nama_lengkap, kelas, password
     - admin   : username, email, password

   peran tidak bisa diubah. foto profil user (tb_user.foto) TIDAK pernah disentuh
   di sini — fitur foto hanya ada di toko penjual. */

include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';
include '../../3. komponen/kelas_jurusan.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: user.php"); exit; }

// ambil & bersihkan input dasar
$id          = (int)($_POST['id_user']     ?? 0);
$username    = trim($_POST['username']     ?? '');
$email       = trim($_POST['email']        ?? '');
$password    = $_POST['password']          ?? '';
$namatoko    = trim($_POST['nama_toko']    ?? '');
$namalengkap = trim($_POST['nama_lengkap'] ?? '');
$kelas       = trim($_POST['kelas']        ?? '');
$notelepon   = trim($_POST['no_telepon']   ?? '');

// oldinput untuk dikembalikan ke form jika validasi gagal
$oldinput = [
    'username'     => $username,
    'email'        => $email,
    'nama_toko'    => $namatoko,
    'nama_lengkap' => $namalengkap,
    'kelas'        => $kelas,
    'no_telepon'   => $notelepon,
];

if (!$id) { flash('gagal','Data tidak valid.'); redirect('user.php'); }

// validasi username
if (strlen($username) < 6) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Username minimal 6 karakter.');
    redirect("edituser.php?id=$id");
}
if (!preg_match('/^[a-zA-Z0-9_.]+$/', $username)) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Username hanya boleh berisi huruf, angka, titik (.), dan garis bawah (_). Tanpa spasi.');
    redirect("edituser.php?id=$id");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Format email tidak valid.');
    redirect("edituser.php?id=$id");
}
// nomor telepon wajib — cek jumlah digitnya 8–15
$telpdigit = preg_replace('/\D/', '', $notelepon);
if (strlen($telpdigit) < 8 || strlen($telpdigit) > 15) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Nomor telepon tidak valid (harus 8–15 digit angka).');
    redirect("edituser.php?id=$id");
}

// ambil role pengguna saat ini — role tidak bisa diubah lewat form ini
$qr = $conn->prepare("SELECT role FROM tb_user WHERE id_user=? AND deleted=0");
$qr->bind_param("i", $id); $qr->execute();
$rowrole = $qr->get_result()->fetch_row(); $qr->close();
if (!$rowrole) { flash('gagal','Pengguna tidak ditemukan.'); redirect('user.php'); }
$role = $rowrole[0];

// validasi khusus pembeli: nama lengkap + kelas wajib dan harus valid
if ($role === 'pembeli') {
    if (empty($namalengkap) || strlen($namalengkap) < 3 || strlen($namalengkap) > 100) {
        $_SESSION['oldinput'] = $oldinput;
        flash('gagal','Nama lengkap pembeli wajib 3–100 karakter.');
        redirect("edituser.php?id=$id");
    }
    if (!kelasValid($kelas)) {
        $_SESSION['oldinput'] = $oldinput;
        flash('gagal','Pilihan kelas / status tidak valid.');
        redirect("edituser.php?id=$id");
    }
}

// cek duplikat username (selain dirinya sendiri)
$ck = $conn->prepare("SELECT id_user FROM tb_user WHERE username=? AND id_user!=? AND deleted=0");
$ck->bind_param("si", $username, $id); $ck->execute();
if ($ck->get_result()->num_rows > 0) {
    $ck->close();
    $_SESSION['oldinput']=$oldinput;
    flash('gagal','Username sudah digunakan oleh pengguna lain.');
    redirect("edituser.php?id=$id");
}
$ck->close();

// cek duplikat email
$ce = $conn->prepare("SELECT id_user FROM tb_user WHERE email=? AND id_user!=? AND deleted=0");
$ce->bind_param("si", $email, $id); $ce->execute();
if ($ce->get_result()->num_rows > 0) {
    $ce->close();
    $_SESSION['oldinput']=$oldinput;
    flash('gagal','Email sudah digunakan oleh pengguna lain.');
    redirect("edituser.php?id=$id");
}
$ce->close();

// ===== UPDATE tb_user =====
// SET-clause dibangun dinamis sesuai peran, supaya nama_lengkap & kelas hanya
// disentuh untuk pembeli, dan password hanya disentuh kalau diisi.
$setFields = ['username=?', 'email=?', 'no_telepon=?'];
$bindTypes = 'sss';
$bindVals  = [$username, $email, $notelepon];

if ($role === 'pembeli') {
    $setFields[] = 'nama_lengkap=?';
    $setFields[] = 'kelas=?';
    $bindTypes  .= 'ss';
    $bindVals[]  = $namalengkap;
    $bindVals[]  = $kelas;
}

// password baru (opsional) — validasi & hash kalau diisi
if ($password !== '') {
    if (strlen($password) < 8) {
        $_SESSION['oldinput']=$oldinput;
        flash('gagal','Password minimal 8 karakter.');
        redirect("edituser.php?id=$id");
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $setFields[] = 'password=?';
    $bindTypes  .= 's';
    $bindVals[]  = $hash;
}

// terakhir, tambah parameter id untuk WHERE
$bindTypes .= 'i';
$bindVals[]  = $id;

$sql = "UPDATE tb_user SET " . implode(', ', $setFields) . " WHERE id_user=? AND deleted=0";
$upd = $conn->prepare($sql);
$upd->bind_param($bindTypes, ...$bindVals);
if (!$upd->execute()) {
    $upd->close();
    flash('gagal','Gagal menyimpan perubahan.');
    redirect("edituser.php?id=$id");
}
$upd->close();

// ===== UPDATE TOKO (khusus penjual) =====
// nama_toko & foto_toko hanya diproses untuk role=penjual. pembeli & admin
// TIDAK punya foto profil sama sekali — kolom tb_user.foto tidak disentuh.
if ($role === 'penjual') {
    // update nama_toko jika diisi
    if ($namatoko !== '') {
        $upt = $conn->prepare("UPDATE tb_toko SET nama_toko=? WHERE id_user=? AND deleted=0");
        $upt->bind_param("si", $namatoko, $id); $upt->execute(); $upt->close();
    }

    // ambil id_toko & foto_toko lama untuk handling upload/hapus
    $qft = $conn->prepare("SELECT id_toko, foto_toko FROM tb_toko WHERE id_user=? AND deleted=0");
    $qft->bind_param("i", $id); $qft->execute();
    $rowtoko = $qft->get_result()->fetch_assoc();
    $qft->close();

    if ($rowtoko) {
        $idToko        = (int)$rowtoko['id_toko'];
        $fotoLamaToko  = $rowtoko['foto_toko'];
        $hapusFotoToko = !empty($_POST['hapus_foto_toko']) && $_POST['hapus_foto_toko'] == '1';
        $adaUploadToko = isset($_FILES['foto_toko']) && $_FILES['foto_toko']['error'] === UPLOAD_ERR_OK;

        $folderUpload = __DIR__ . '/../../2. aset/profil/';
        if (!is_dir($folderUpload)) mkdir($folderUpload, 0755, true);
        $tipeOk   = ['jpg','jpeg','png','webp'];
        $maksByte = 2 * 1024 * 1024;

        if ($adaUploadToko) {
            $extT = strtolower(pathinfo($_FILES['foto_toko']['name'], PATHINFO_EXTENSION));
            if (!in_array($extT, $tipeOk)) {
                flash('gagal','Format foto toko tidak didukung. Gunakan JPG/PNG/WEBP.');
                redirect("edituser.php?id=$id");
            }
            if ($_FILES['foto_toko']['size'] > $maksByte) {
                flash('gagal','Ukuran foto toko maksimal 2MB.');
                redirect("edituser.php?id=$id");
            }
            $namaFotoTokoBaru = 'toko_' . $idToko . '_' . time() . '.' . $extT;
            if (move_uploaded_file($_FILES['foto_toko']['tmp_name'], $folderUpload . $namaFotoTokoBaru)) {
                if ($fotoLamaToko && file_exists($folderUpload . $fotoLamaToko)) {
                    @unlink($folderUpload . $fotoLamaToko);
                }
                $ut = $conn->prepare("UPDATE tb_toko SET foto_toko=? WHERE id_toko=?");
                $ut->bind_param("si", $namaFotoTokoBaru, $idToko); $ut->execute(); $ut->close();
            }
        } elseif ($hapusFotoToko) {
            if ($fotoLamaToko && file_exists($folderUpload . $fotoLamaToko)) {
                @unlink($folderUpload . $fotoLamaToko);
            }
            $ut = $conn->prepare("UPDATE tb_toko SET foto_toko=NULL WHERE id_toko=?");
            $ut->bind_param("i", $idToko); $ut->execute(); $ut->close();
        }
    }
}

flash('sukses','Pengguna berhasil diperbarui.');
redirect("viewuser.php?id=$id");

// fungsi pembantu: simpan flash message ke session
function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }
// fungsi pembantu: redirect ke url tertentu dan hentikan eksekusi
function redirect(string $url): void { header("Location: $url"); exit; }
?>
