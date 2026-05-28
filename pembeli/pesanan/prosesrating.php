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
// pakai prepared statement — kalau hanya cek id_order saja, pembeli iseng bisa rating
// pesanan orang lain dengan mengganti id_order di form (tidak aman)
$q = $conn->prepare("SELECT id_toko,id_penjual,status_order FROM tb_order WHERE id_order=? AND id_user=? AND deleted=0");
// "ii": dua parameter integer
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
// id_penjual diambil dari order — mencatat siapa penjualnya saat pembeli memesan
$idtoko    = $pesanan['id_toko']    ?? null;
$idpenjual = $pesanan['id_penjual'] ?? null;
$ins    = $conn->prepare("INSERT INTO tb_rating (id_order,id_user,id_toko,id_penjual,rating_toko,ulasan) VALUES (?,?,?,?,?,?)");
// "iiiiss": integer, integer, integer, integer, string, string
$ins->bind_param("iiiiss", $idpesanan, $idpengguna, $idtoko, $idpenjual, $nilaitoko, $ulasan);
if (!$ins->execute()) {
    // jika gagal menyimpan, beri tahu pembeli
    $_SESSION['flash'] = ['pesan' => 'Gagal menyimpan rating', 'jenis' => 'gagal'];
    header("Location: pesanan.php?tab=riwayat"); exit;
}
$ins->close();

// rating berhasil disimpan — tampilkan pesan sukses dan redirect ke riwayat
$_SESSION['flash'] = ['pesan' => 'Rating berhasil dikirim, terima kasih!', 'jenis' => 'sukses'];
header("Location: pesanan.php?tab=riwayat"); exit;
?>
