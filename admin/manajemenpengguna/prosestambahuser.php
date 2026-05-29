<?php
/* proses tambah pengguna baru oleh admin.
   menerima POST dari salah satu form di tambahuser.php (penjual/pembeli/admin).
   untuk penjual: mengisi slot kantin yang dipilih + opsional upload foto toko.
   untuk pembeli: status_verifikasi otomatis 'verified' karena dibuat admin.
*/

include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';
include '../../3. komponen/kelas_jurusan.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: tambahuser.php"); exit; }

// ambil & bersihkan data dari form
$username    = trim($_POST['username']     ?? '');
$email       = trim($_POST['email']        ?? '');
$namatoko    = trim($_POST['nama_toko']    ?? '');
$password    = $_POST['password']          ?? '';
$role        = $_POST['role']              ?? '';
$idkantin    = (int)($_POST['id_kantin']   ?? 0);
$namalengkap = trim($_POST['nama_lengkap'] ?? '');
$kelas       = trim($_POST['kelas']        ?? '');

// siapkan oldinput untuk dikembalikan ke form jika validasi gagal
$oldinput = [
    'username'     => $username,
    'email'        => $email,
    'id_kantin'    => $idkantin,
    'nama_toko'    => $namatoko,
    'nama_lengkap' => $namalengkap,
    'kelas'        => $kelas,
];

// validasi role
if (!in_array($role, ['penjual','pembeli','admin'], true)) {
    flash('gagal','Peran tidak valid.');
    redirect('tambahuser.php');
}

// validasi username panjang
if (strlen($username) < 6 || strlen($username) > 50) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Username harus 6–50 karakter.');
    redirect("tambahuser.php?role=$role");
}
// format username: hanya huruf/angka/titik/garis bawah
if (!preg_match('/^[a-zA-Z0-9_.]+$/', $username)) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Username hanya boleh berisi huruf, angka, titik (.), dan garis bawah (_). Tanpa spasi.');
    redirect("tambahuser.php?role=$role");
}
// format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Format email tidak valid.');
    redirect("tambahuser.php?role=$role");
}
// password
if (strlen($password) < 8) {
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Password minimal 8 karakter.');
    redirect("tambahuser.php?role=$role");
}

// validasi khusus penjual: kantin + nama toko
if ($role === 'penjual') {
    if (!$idkantin) {
        $_SESSION['oldinput'] = $oldinput;
        flash('gagal','Penjual wajib memilih kantin yang tersedia.');
        redirect("tambahuser.php?role=$role");
    }
    if (empty($namatoko)) {
        $_SESSION['oldinput'] = $oldinput;
        flash('gagal','Nama toko wajib diisi untuk Penjual.');
        redirect("tambahuser.php?role=$role");
    }
}

// validasi khusus pembeli: nama lengkap + kelas (harus dari daftar resmi)
if ($role === 'pembeli') {
    if (empty($namalengkap) || strlen($namalengkap) < 3 || strlen($namalengkap) > 100) {
        $_SESSION['oldinput'] = $oldinput;
        flash('gagal','Nama lengkap pembeli wajib diisi (3–100 karakter).');
        redirect("tambahuser.php?role=$role");
    }
    if (!kelasValid($kelas)) {
        $_SESSION['oldinput'] = $oldinput;
        flash('gagal','Pilihan kelas / status tidak valid.');
        redirect("tambahuser.php?role=$role");
    }
}

// bebaskan slot dari akun ditolak sebelumnya yang username/email-nya sama.
// (sama logic-nya dengan prosesregister.php)
$bersihkan = $conn->prepare(
    "UPDATE tb_user SET deleted=1, deleted_at=NOW()
     WHERE (username=? OR email=?) AND status_verifikasi='ditolak' AND deleted=0"
);
$bersihkan->bind_param("ss", $username, $email);
$bersihkan->execute();
$bersihkan->close();

// cek duplikat username
$ck = $conn->prepare("SELECT id_user FROM tb_user WHERE username=? AND deleted=0");
$ck->bind_param("s", $username); $ck->execute();
if ($ck->get_result()->num_rows > 0) {
    $ck->close();
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Username sudah terdaftar.');
    redirect("tambahuser.php?role=$role");
}
$ck->close();

// cek duplikat email
$ce = $conn->prepare("SELECT id_user FROM tb_user WHERE email=? AND deleted=0");
$ce->bind_param("s", $email); $ce->execute();
if ($ce->get_result()->num_rows > 0) {
    $ce->close();
    $_SESSION['oldinput'] = $oldinput;
    flash('gagal','Email sudah digunakan.');
    redirect("tambahuser.php?role=$role");
}
$ce->close();

// untuk penjual: pastikan kantin masih kosong (cegah race-condition 2 admin paralel)
$nomorkantin = 0;
if ($role === 'penjual') {
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
        redirect("tambahuser.php?role=$role");
    }
    $nomorkantin = $kantindata['nomor_kantin'] !== null ? (int)$kantindata['nomor_kantin'] : 0;
}

// cek apakah kolom tanggal_mulai sudah ada (untuk mencatat kapan penjual mulai)
$cekmulai = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'tanggal_mulai'");
$adamulai = ($cekmulai && $cekmulai->num_rows > 0);
$setmulai = $adamulai ? ", tanggal_mulai=NOW()" : "";

// nama_lengkap & kelas hanya disimpan untuk pembeli. status_verifikasi='verified'
// otomatis untuk semua akun yang dibuat admin (admin sudah verifikasi sendiri).
$namaLengkapSimpan = ($role === 'pembeli') ? $namalengkap : null;
$kelasSimpan       = ($role === 'pembeli') ? $kelas       : null;
$statusVerifSimpan = 'verified';

// hash password
$hash = password_hash($password, PASSWORD_DEFAULT);

// insert pengguna baru
$ins = $conn->prepare("INSERT INTO tb_user
    (username, nama_lengkap, kelas, email, password, role, status_verifikasi, deleted)
    VALUES (?,?,?,?,?,?,?,0)");
$ins->bind_param("sssssss", $username, $namaLengkapSimpan, $kelasSimpan, $email, $hash, $role, $statusVerifSimpan);
if (!$ins->execute()) {
    $ins->close();
    flash('gagal','Gagal menyimpan pengguna. Coba lagi.');
    redirect("tambahuser.php?role=$role");
}
$iduser = $conn->insert_id;
$ins->close();

// jika penjual: isi kantin yang dipilih + handle upload foto toko (opsional)
if ($role === 'penjual') {

    // siapkan upload foto toko kalau ada
    $folderUpload   = __DIR__ . '/../../2. aset/profil/';
    if (!is_dir($folderUpload)) mkdir($folderUpload, 0755, true);
    $tipeOk         = ['jpg','jpeg','png','webp'];
    $maksByte       = 2 * 1024 * 1024;
    $namaFotoBaru   = null;
    $adaUploadFoto  = isset($_FILES['foto_toko']) && $_FILES['foto_toko']['error'] === UPLOAD_ERR_OK;

    if ($adaUploadFoto) {
        $ext = strtolower(pathinfo($_FILES['foto_toko']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $tipeOk)) {
            flash('gagal','Format foto toko tidak didukung. Gunakan JPG/PNG/WEBP.');
            redirect("tambahuser.php?role=$role");
        }
        if ($_FILES['foto_toko']['size'] > $maksByte) {
            flash('gagal','Ukuran foto toko maksimal 2MB.');
            redirect("tambahuser.php?role=$role");
        }
        // nama file unik: gabung id_toko + timestamp
        $namaFotoBaru = 'toko_' . (int)$idkantin . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($_FILES['foto_toko']['tmp_name'], $folderUpload . $namaFotoBaru)) {
            // upload gagal — biarkan saja tanpa foto, jangan rollback insert user
            $namaFotoBaru = null;
        }
    }

    // isi slot kantin: id_user, nama_toko, foto_toko (jika upload sukses), tanggal_mulai
    if ($namaFotoBaru) {
        $upk = $conn->prepare(
            "UPDATE tb_toko
             SET id_user=?, nama_toko=?, foto_toko=?, status_toko='tutup'{$setmulai}
             WHERE id_toko=? AND id_user IS NULL AND deleted=0"
        );
        $upk->bind_param("issi", $iduser, $namatoko, $namaFotoBaru, $idkantin);
    } else {
        $upk = $conn->prepare(
            "UPDATE tb_toko
             SET id_user=?, nama_toko=?, status_toko='tutup'{$setmulai}
             WHERE id_toko=? AND id_user IS NULL AND deleted=0"
        );
        $upk->bind_param("isi", $iduser, $namatoko, $idkantin);
    }
    $upk->execute(); $upk->close();

    $labelKantin = $nomorkantin > 0 ? "Kantin ke-{$nomorkantin}" : "kantin #$idkantin";
    flash('sukses', "Penjual \"$username\" berhasil ditambahkan di {$labelKantin} dengan toko \"$namatoko\".");
    redirect('user.php?role=penjual');
}

// pembeli / admin: tidak ada upload foto saat tambah. selesai.
flash('sukses', ucfirst($role) . " \"$username\" berhasil ditambahkan.");
redirect('user.php?role=' . $role);

// fungsi pembantu: simpan flash message ke session
function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }

// fungsi pembantu: redirect ke url tertentu dan hentikan eksekusi
function redirect(string $url): void { header("Location: $url"); exit; }
?>
