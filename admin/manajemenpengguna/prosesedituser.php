<?php
/* proses edit pengguna — menerima data dari form edituser.php via POST.
   bisa mengubah username, email, password (opsional), dan nama toko (jika penjual).
   role tidak bisa diubah. setelah berhasil, redirect ke halaman detail pengguna. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tolak akses langsung (bukan POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: user.php"); exit; }

// ambil dan bersihkan data dari form
// (int) = cast ke integer, mencegah nilai aneh masuk ke query
// trim() = buang spasi di awal/akhir string
// operator ?? (null coalescing) = kalau $_POST['x'] tidak ada, pakai default
$id       = (int)($_POST['id_user'] ?? 0);
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$namatoko = trim($_POST['nama_toko'] ?? '');

// siapkan data lama untuk dikembalikan ke form jika validasi gagal
$oldinput = ['username'=>$username,'email'=>$email,'nama_toko'=>$namatoko];

// validasi: id harus ada
if (!$id) { flash('gagal','Data tidak valid.'); redirect('user.php'); }

// validasi panjang username
if (strlen($username) < 6) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Username minimal 6 karakter.');
    redirect("edituser.php?id=$id");
}

// validasi format username: hanya huruf, angka, titik, dan garis bawah — tanpa spasi
// preg_match() = cek apakah string cocok dengan pola regex
// pola ^[a-zA-Z0-9_.]+$ artinya: dari awal (^) sampai akhir ($), hanya karakter dalam set tsb
if (!preg_match('/^[a-zA-Z0-9_.]+$/', $username)) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Username hanya boleh berisi huruf, angka, titik (.), dan garis bawah (_). Tanpa spasi.');
    redirect("edituser.php?id=$id");
}

// validasi format email
// filter_var() dengan FILTER_VALIDATE_EMAIL = cara bawaan PHP untuk cek struktur email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Format email tidak valid.');
    redirect("edituser.php?id=$id");
}

// ambil role pengguna saat ini dari database — role tidak boleh berubah lewat form ini
$qr = $conn->prepare("SELECT role FROM tb_user WHERE id_user=? AND deleted=0");
$qr->bind_param("i", $id); $qr->execute();
$rowrole = $qr->get_result()->fetch_row(); $qr->close();

// jika pengguna tidak ditemukan (mungkin sudah dihapus)
if (!$rowrole) { flash('gagal','Pengguna tidak ditemukan.'); redirect('user.php'); }
$role = $rowrole[0];

// cek apakah username baru sudah dipakai pengguna lain (kecuali diri sendiri)
// kondisi "id_user!=?" penting: agar pengguna boleh menyimpan username miliknya sendiri
$ck = $conn->prepare("SELECT id_user FROM tb_user WHERE username=? AND id_user!=? AND deleted=0");
$ck->bind_param("si", $username, $id); $ck->execute();
if ($ck->get_result()->num_rows > 0) { $ck->close(); $_SESSION['oldinput']=$oldinput; flash('gagal','Username sudah digunakan oleh pengguna lain.'); redirect("edituser.php?id=$id"); }
$ck->close();

// cek apakah email baru sudah dipakai pengguna lain
$ce = $conn->prepare("SELECT id_user FROM tb_user WHERE email=? AND id_user!=? AND deleted=0");
$ce->bind_param("si", $email, $id); $ce->execute();
if ($ce->get_result()->num_rows > 0) { $ce->close(); $_SESSION['oldinput']=$oldinput; flash('gagal','Email sudah digunakan oleh pengguna lain.'); redirect("edituser.php?id=$id"); }
$ce->close();

// jika field password diisi, ikut-sertakan password baru dalam update
if ($password !== '') {
    // validasi panjang password baru
    if (strlen($password) < 8) { $_SESSION['oldinput']=$oldinput; flash('gagal','Password minimal 8 karakter.'); redirect("edituser.php?id=$id"); }

    // hash password baru sebelum disimpan
    // password_hash() pakai algoritma bcrypt secara default (PASSWORD_DEFAULT)
    // hasil hash berbeda tiap kali walau passwordnya sama (karena ada salt unik di dalamnya)
    // password ASLI tidak pernah disimpan ke database — hanya hash-nya
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // update username, email, dan password sekaligus
    $upd = $conn->prepare("UPDATE tb_user SET username=?, email=?, password=? WHERE id_user=? AND deleted=0");
    $upd->bind_param("sssi", $username, $email, $hash, $id);
} else {
    // jika password dikosongkan, hanya update username dan email saja
    $upd = $conn->prepare("UPDATE tb_user SET username=?, email=? WHERE id_user=? AND deleted=0");
    $upd->bind_param("ssi", $username, $email, $id);
}

// jalankan query update pengguna
if (!$upd->execute()) { $upd->close(); flash('gagal','Gagal menyimpan perubahan.'); redirect("edituser.php?id=$id"); }
$upd->close();

// ===== FOTO PROFIL USER =====
// folder upload + validasi format/ukuran (sama dengan flow penjual edit profil)
// __DIR__ = path folder dari file ini, dipakai supaya path absolut, tidak tergantung cwd
$folderUpload = __DIR__ . '/../../2. aset/profil/';
// buat folder jika belum ada (chmod 0755 = owner full, lainnya read+execute)
if (!is_dir($folderUpload)) mkdir($folderUpload, 0755, true);
// whitelist ekstensi yang diperbolehkan — lebih aman daripada blacklist
$tipeOk    = ['jpg','jpeg','png','webp'];
$maksByte  = 2 * 1024 * 1024; // 2MB (1024 * 1024 byte = 1MB)

// ambil foto lama user supaya bisa dihapus jika diganti/dihapus
$qfu = $conn->prepare("SELECT foto FROM tb_user WHERE id_user=?");
$qfu->bind_param("i", $id); $qfu->execute();
$fotoLamaUser = ($qfu->get_result()->fetch_row()[0] ?? null);
$qfu->close();

// cek checkbox "hapus foto" dicentang
$hapusFoto = !empty($_POST['hapus_foto']) && $_POST['hapus_foto'] == '1';
// $_FILES = superglobal untuk file upload (butuh enctype="multipart/form-data" di form)
// UPLOAD_ERR_OK (=0) artinya upload sukses tanpa error
$adaUploadFoto = isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK;

if ($adaUploadFoto) {
    // pathinfo() dengan PATHINFO_EXTENSION ambil ekstensi file (misal "png" dari "foto.PNG")
    // strtolower() = ubah jadi huruf kecil supaya cocok dengan whitelist $tipeOk
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $tipeOk)) {
        flash('gagal','Format foto profil tidak didukung. Gunakan JPG/PNG/WEBP.');
        redirect("edituser.php?id=$id");
    }
    if ($_FILES['foto']['size'] > $maksByte) {
        flash('gagal','Ukuran foto profil maksimal 2MB.');
        redirect("edituser.php?id=$id");
    }
    // nama file dibuat unik: gabung id_user + timestamp (time()) supaya tidak bentrok
    // dan mencegah pengguna nakal menebak/menimpa file orang lain
    $namaFotoBaru = 'user_' . $id . '_' . time() . '.' . $ext;
    // move_uploaded_file() = pindahkan file dari folder tmp PHP ke folder tujuan
    // wajib pakai fungsi ini (bukan rename) karena ada validasi keamanan bawaan PHP
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $folderUpload . $namaFotoBaru)) {
        // hapus foto lama dari disk supaya tidak menumpuk jadi file sampah
        // tanda @ di depan unlink() = supir warning kalau file tidak ada / gagal hapus
        if ($fotoLamaUser && file_exists($folderUpload . $fotoLamaUser)) {
            @unlink($folderUpload . $fotoLamaUser);
        }
        $uf = $conn->prepare("UPDATE tb_user SET foto=? WHERE id_user=?");
        $uf->bind_param("si", $namaFotoBaru, $id); $uf->execute(); $uf->close();
    }
} elseif ($hapusFoto) {
    // hapus foto lama dari disk + set kolom foto jadi NULL
    if ($fotoLamaUser && file_exists($folderUpload . $fotoLamaUser)) {
        @unlink($folderUpload . $fotoLamaUser);
    }
    $uf = $conn->prepare("UPDATE tb_user SET foto=NULL WHERE id_user=?");
    $uf->bind_param("i", $id); $uf->execute(); $uf->close();
}

// ===== UPDATE TOKO (untuk penjual) =====
if ($role === 'penjual') {
    // update nama toko jika diisi
    if ($namatoko !== '') {
        $upt = $conn->prepare("UPDATE tb_toko SET nama_toko=? WHERE id_user=? AND deleted=0");
        $upt->bind_param("si", $namatoko, $id); $upt->execute(); $upt->close();
    }

    // ambil id_toko & foto_toko lama
    $qft = $conn->prepare("SELECT id_toko, foto_toko FROM tb_toko WHERE id_user=? AND deleted=0");
    $qft->bind_param("i", $id); $qft->execute();
    $rowtoko = $qft->get_result()->fetch_assoc();
    $qft->close();

    if ($rowtoko) {
        $idToko        = (int)$rowtoko['id_toko'];
        $fotoLamaToko  = $rowtoko['foto_toko'];
        $hapusFotoToko = !empty($_POST['hapus_foto_toko']) && $_POST['hapus_foto_toko'] == '1';
        $adaUploadToko = isset($_FILES['foto_toko']) && $_FILES['foto_toko']['error'] === UPLOAD_ERR_OK;

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

// simpan flash message sukses dan arahkan ke halaman detail pengguna
flash('sukses','Pengguna berhasil diperbarui.');
redirect("viewuser.php?id=$id");

// fungsi pembantu: simpan flash message ke session
function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }

// fungsi pembantu: redirect ke url tertentu dan hentikan eksekusi
function redirect(string $url): void { header("Location: $url"); exit; }
?>
