<?php
/* file proses simpan rating dan ulasan
   dipanggil dari form rating.php via POST
   alur: validasi input → verifikasi pesanan → cek duplikat → simpan rating → redirect
   tag yang dipilih digabungkan ke dalam teks ulasan sebelum disimpan */

// guard memastikan hanya pembeli yang login yang bisa mengakses
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

// tolak akses selain POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: pesanan.php"); exit; }

// ambil data dari form rating
$idpesanan  = (int)($_POST['id_order']    ?? 0);
// nilai bintang antara 1-5
$nilaitoko  = (int)($_POST['rating_toko'] ?? 0);
$ulasan     = trim($_POST['ulasan']        ?? '');
$idpengguna = (int)$_SESSION['id_user'];

/* proses tag yang dipilih via checkbox (name="tag[]")
   array_filter menghapus nilai kosong
   array_map trim membersihkan spasi di setiap tag
   (array) memastikan nilai tetap array meski hanya satu atau tidak ada */
$tags = array_filter(array_map('trim', (array)($_POST['tag'] ?? [])));
if (!empty($tags)) {
    // gabungkan tag-tag menjadi satu string dipisah koma
    $tagstr = implode(', ', $tags);
    // jika ada teks ulasan, tambahkan tag di baris baru
    // jika tidak ada teks ulasan, tag menjadi isi ulasan
    $ulasan = $ulasan ? $ulasan . "\n" . $tagstr : $tagstr;
}

// validasi dasar: id pesanan harus valid dan nilai bintang harus antara 1-5
if (!$idpesanan || $nilaitoko < 1 || $nilaitoko > 5) {
    $_SESSION['flash'] = ['pesan' => 'Data rating tidak valid', 'jenis' => 'gagal'];
    header("Location: pesanan.php?tab=riwayat"); exit;
}

// verifikasi bahwa pesanan memang milik pembeli yang sedang login
// dan statusnya sudah Selesai atau Siap Diambil
$q = $conn->prepare("SELECT id_toko,status_order FROM tb_order WHERE id_order=? AND id_user=? AND deleted=0");
$q->bind_param("ii", $idpesanan, $idpengguna);
$q->execute();
$pesanan = $q->get_result()->fetch_assoc();
$q->close();

if (!$pesanan || !in_array($pesanan['status_order'], ['Selesai','Siap Diambil'])) {
    $_SESSION['flash'] = ['pesan' => 'Pesanan tidak ditemukan atau belum selesai', 'jenis' => 'gagal'];
    header("Location: pesanan.php?tab=riwayat"); exit;
}

/* cek duplikat rating — satu pesanan hanya boleh diberi rating satu kali
   ini mencegah pembeli memberi rating berkali-kali */
$cd = $conn->prepare("SELECT id_rating FROM tb_rating WHERE id_order=? AND id_user=?");
$cd->bind_param("ii", $idpesanan, $idpengguna);
$cd->execute();
if ($cd->get_result()->num_rows > 0) {
    $_SESSION['flash'] = ['pesan' => 'Kamu sudah memberi rating untuk pesanan ini', 'jenis' => 'info'];
    header("Location: pesanan.php?tab=riwayat"); exit;
}
$cd->close();

// simpan rating utama ke tabel tb_rating
$idtoko = $pesanan['id_toko'] ?? null;
$ins    = $conn->prepare("INSERT INTO tb_rating (id_order,id_user,id_toko,rating_toko,ulasan) VALUES (?,?,?,?,?)");
// "iiiss": integer, integer, integer, string, string
$ins->bind_param("iiiss", $idpesanan, $idpengguna, $idtoko, $nilaitoko, $ulasan);
if (!$ins->execute()) {
    // jika gagal menyimpan, beri tahu pembeli
    $_SESSION['flash'] = ['pesan' => 'Gagal menyimpan rating', 'jenis' => 'gagal'];
    header("Location: pesanan.php?tab=riwayat"); exit;
}
// simpan id rating baru untuk relasi ke rating_menu
$idratingbaru = $conn->insert_id;
$ins->close();

/* simpan rating per item menu (opsional)
   hanya dijalankan jika tabel tb_rating_menu ada di database
   dan ada nilai rating per menu yang dikirim via POST */
$nilaimenu = $_POST['nilai_menu'] ?? [];
if (!empty($nilaimenu)) {
    // cek dulu apakah tabelnya ada — agar tidak error jika belum dibuat
    $cektabel = $conn->query("SHOW TABLES LIKE 'tb_rating_menu'");
    if ($cektabel && $cektabel->num_rows > 0) {
        foreach ($nilaimenu as $idmenu => $nilai) {
            $idmenu = (int)$idmenu;
            // pastikan nilai antara 1-5
            $nilai  = max(1, min(5, (int)$nilai));
            if ($idmenu > 0 && $nilai > 0) {
                $rm = $conn->prepare("INSERT INTO tb_rating_menu (id_rating,id_menu,rating) VALUES (?,?,?)");
                $rm->bind_param("iii", $idratingbaru, $idmenu, $nilai);
                $rm->execute(); $rm->close();
            }
        }
    }
}

// rating berhasil disimpan — tampilkan pesan sukses dan redirect ke riwayat
$_SESSION['flash'] = ['pesan' => 'Rating berhasil dikirim, terima kasih!', 'jenis' => 'sukses'];
header("Location: pesanan.php?tab=riwayat"); exit;
?>
