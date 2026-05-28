<?php
/*
  file ini adalah otak dari proses login.
  tugasnya: menerima data dari form di login.php, memverifikasi ke database,
  lalu memulai sesi dan mengarahkan pengguna ke halaman yang sesuai dengan perannya.

  alur kerja:
  1. pastikan request datang dari form (POST), bukan akses langsung lewat url
  2. ambil dan bersihkan data input dari form
  3. validasi bahwa input tidak kosong
  4. cari data user di database berdasarkan username atau email
  5. periksa apakah password cocok dengan hash yang tersimpan
  6. tentukan nama sesi berdasarkan peran (role) user
  7. mulai sesi, simpan data user ke dalam sesi
  8. jika penjual, ambil juga data toko ke sesi
  9. arahkan (redirect) ke halaman utama sesuai peran
*/
include "../1. koneksi/koneksi.php";
// penting: session_start() TIDAK dipanggil di sini dulu.
// kita harus tahu role user terlebih dahulu sebelum menentukan nama sesi yang tepat.
// ini penting agar penjual dan pembeli bisa login di tab browser yang berbeda
// tanpa sesi mereka saling menimpa satu sama lain.

// langkah 1: pastikan request ini datang dari pengiriman form (metode POST)
// jika bukan POST (misalnya pengguna mengetik url ini langsung di browser),
// arahkan kembali ke halaman login dan hentikan script
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

// langkah 2: ambil data dari form dan bersihkan spasi di awal/akhir dengan trim()
// operator ?? dipakai untuk menghindari error jika kunci tidak ada di $_POST
$usernameemail = trim($_POST['usernameemail'] ?? '');
$password      = trim($_POST['password']      ?? '');

// langkah 3: validasi bahwa kedua kolom tidak kosong
// jika kosong, kirim pesan error dan kembali ke halaman login
if (empty($usernameemail) || empty($password)) {
    header("Location: login.php?error=" . urlencode("Username/Email dan Password wajib diisi!"));
    exit;
}

// langkah 4: cari user di database
// query ini menerima username ATAU email agar pengguna bisa login dengan salah satunya
// AND deleted=0 memastikan akun yang sudah dihapus tidak bisa login
$stmt = $conn->prepare("SELECT id_user, username, email, password, role
                         FROM tb_user
                         WHERE (username=? OR email=?) AND deleted=0");
// bind_param("ss", ...) berarti dua parameter bertipe string (s = string)
// penggunaan prepared statement ini penting untuk mencegah serangan sql injection
$stmt->bind_param("ss", $usernameemail, $usernameemail);
$stmt->execute();
// fetch_assoc() mengambil satu baris hasil query sebagai array asosiatif
// contoh: $user['username'], $user['role'], $user['password']
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// jika $user bernilai null/false, artinya tidak ada akun dengan username/email tersebut
if (!$user) {
    // kembalikan nilai input usernameemail ke url agar pengguna tidak perlu ketik ulang
    header("Location: login.php?error=" . urlencode("Akun tidak ditemukan!") . "&usernameemail=" . urlencode($usernameemail));
    exit;
}

// langkah 5: verifikasi password
// password di database disimpan dalam bentuk hash (bukan teks asli), jadi tidak bisa dibandingkan langsung.
// password_verify() membandingkan teks password yang diketik pengguna dengan hash yang tersimpan.
// fungsi ini aman karena hash bersifat satu arah — tidak bisa dikembalikan ke teks asli.
if (!password_verify($password, $user['password'])) {
    header("Location: login.php?error=" . urlencode("Password salah!") . "&usernameemail=" . urlencode($usernameemail));
    exit;
}

// langkah 6a: halaman ini KHUSUS untuk pembeli & penjual. Admin harus pakai
// halaman login admin (admin/login/loginadmin.php) — tolak sebelum sesi dibuat.
if ($user['role'] === 'admin') {
    header("Location: login.php?error=" . urlencode("Akun admin harus login lewat halaman admin.") .
           "&usernameemail=" . urlencode($usernameemail));
    exit;
}

// langkah 6b: tentukan nama sesi berdasarkan peran (role) user
// setiap peran memakai nama sesi yang berbeda agar tidak saling bentrok.
// contoh: pembeli di tab 1 dan penjual di tab 2 bisa login bersamaan.
$namaSesi = match($user['role']) {
    'penjual' => 'sesi_penjual',
    default   => 'sesi_pembeli', // role pembeli (atau yang tidak dikenali, fallback ke pembeli)
};

// terapkan nama sesi sebelum session_start() dipanggil
session_name($namaSesi);
session_start();

// langkah 7: simpan data user ke dalam variabel sesi
// data sesi ini akan tersedia di semua halaman selama pengguna belum logout
// dan akan dipakai oleh guard (penjaga halaman) untuk memverifikasi status login
$_SESSION['id_user']  = $user['id_user'];
$_SESSION['username'] = $user['username'];
$_SESSION['email']    = $user['email'];
$_SESSION['role']     = $user['role'];

// langkah 8: jika user adalah penjual, ambil juga data toko miliknya
// data toko disimpan di sesi agar tidak perlu query ulang setiap kali halaman penjual dibuka
if ($user['role'] === 'penjual') {
    // cast ke integer untuk keamanan — memastikan id_user adalah angka, bukan string berbahaya
    $iduser = (int)$user['id_user'];
    $qt = $conn->prepare("SELECT id_toko, nama_toko, status_toko FROM tb_toko WHERE id_user=? AND deleted=0 LIMIT 1");
    $qt->bind_param("i", $iduser); // "i" berarti tipe integer
    $qt->execute();
    $datatoko = $qt->get_result()->fetch_assoc();
    $qt->close();
    // simpan data toko ke sesi hanya jika toko ditemukan di database
    if ($datatoko) {
        $_SESSION['id_toko']     = $datatoko['id_toko'];
        $_SESSION['nama_toko']   = $datatoko['nama_toko'];
        // jika kolom status_toko kosong/null, pakai nilai default 'buka'
        $_SESSION['status_toko'] = $datatoko['status_toko'] ?? 'buka';
    }
}

// langkah 8b: jika user adalah pembeli, muat ulang keranjang dari database ke session
// ini mencegah keranjang hilang saat pembeli logout lalu login kembali
if ($user['role'] === 'pembeli') {
    $iduserkeranjang = (int)$user['id_user'];
    // ambil semua item keranjang di database milik pembeli ini
    // join ke tb_menu dan tb_toko untuk mendapatkan semua data yang dibutuhkan tampilan
    $qkeranjang = $conn->prepare(
        "SELECT k.id_menu, k.jumlah, m.nama_menu, m.harga, m.foto, m.id_toko, t.nama_toko
         FROM tb_keranjang k
         JOIN tb_menu m ON k.id_menu=m.id_menu
         JOIN tb_toko t ON m.id_toko=t.id_toko
         WHERE k.id_user=? AND m.deleted=0 AND m.status='aktif' AND t.deleted=0"
    );
    $qkeranjang->bind_param("i", $iduserkeranjang);
    $qkeranjang->execute();
    $bariskeranjang = $qkeranjang->get_result()->fetch_all(MYSQLI_ASSOC);
    $qkeranjang->close();
    // bangun ulang struktur keranjang di session sama persis seperti saat item ditambahkan
    if (!empty($bariskeranjang)) {
        $_SESSION['keranjang'] = []; // reset session keranjang dulu
        foreach ($bariskeranjang as $baris) {
            $idtokoitem = (int)$baris['id_toko'];
            $idmenuitem = (int)$baris['id_menu'];
            // buat slot toko di session jika belum ada
            if (!isset($_SESSION['keranjang'][$idtokoitem])) {
                $_SESSION['keranjang'][$idtokoitem] = [
                    '_info' => ['nama_toko' => $baris['nama_toko'], 'id_toko' => $idtokoitem]
                ];
            }
            // masukkan item dengan seluruh data yang dibutuhkan halaman keranjang
            $_SESSION['keranjang'][$idtokoitem][$idmenuitem] = [
                'id_menu'   => $idmenuitem,
                'nama_menu' => $baris['nama_menu'],
                'harga'     => (int)$baris['harga'],
                'foto'      => $baris['foto'],
                'qty'       => (int)$baris['jumlah'],
                'id_toko'   => $idtokoitem,
                'nama_toko' => $baris['nama_toko'],
            ];
        }
    }
}

// langkah 9: arahkan pengguna ke halaman utama sesuai dengan perannya
// (admin sudah ditolak di langkah 6a — tidak sampai sini)
switch ($user['role']) {
    case 'penjual':
        header("Location: ../penjual/index/index.php");
        break;
    case 'pembeli':
        header("Location: ../pembeli/index/index.php");
        break;
    default:
        // role tidak dikenali (kasus yang seharusnya tidak terjadi)
        header("Location: login.php?error=" . urlencode("Role tidak dikenali."));
}
exit;
?>
