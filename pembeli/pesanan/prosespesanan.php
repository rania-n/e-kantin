<?php
/* file proses pembuatan pesanan
   dipanggil dari form checkout via POST
   alur: validasi → cek toko buka → cek stok → simpan ke database → redirect ke struk
   menggunakan transaksi database agar jika ada error, semua perubahan dibatalkan */

// guard memastikan hanya pembeli yang login yang bisa mengakses
include '../../3. komponen/guardpembeli.php';
// koneksi database untuk menyimpan order dan update stok
include '../../1. koneksi/koneksi.php';

// tolak akses selain POST — file ini tidak boleh dibuka langsung dari browser
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../keranjang/keranjang.php"); exit;
}

// ambil data dari form checkout
$idtoko     = (int)($_POST['id_toko'] ?? 0);
// metode pembayaran selalu Tunai — tidak ada opsi lain
// meskipun ada nilai lain dari POST, paksa ke 'Tunai' untuk keamanan
$metode     = 'Tunai';
$catatan    = trim($_POST['catatan']  ?? '');
$idpengguna = (int)$_SESSION['id_user'];
// ambil keranjang dari session — data item ada di sini
$keranjang  = $_SESSION['keranjang'] ?? [];

// validasi: toko harus valid dan ada di keranjang session
if (!$idtoko || !isset($keranjang[$idtoko])) {
    $_SESSION['flash'] = ['pesan' => 'Keranjang tidak ditemukan', 'jenis' => 'gagal'];
    header("Location: ../keranjang/keranjang.php"); exit;
}

// ambil item dari toko yang dipilih, pisahkan _info
$itemtoko   = $keranjang[$idtoko];
$daftaritem = [];
$subtotal   = 0;

foreach ($itemtoko as $k => $v) {
    if ($k === '_info') continue; // lewati data info toko
    $daftaritem[] = $v;
    $subtotal    += $v['harga'] * $v['qty'];
}

// jika keranjang toko kosong, tolak
if (empty($daftaritem)) {
    $_SESSION['flash'] = ['pesan' => 'Tidak ada item di keranjang', 'jenis' => 'gagal'];
    header("Location: ../keranjang/keranjang.php"); exit;
}

// total yang disimpan ke database sama dengan subtotal — tidak ada biaya tambahan
$totalbayar = $subtotal;

/* validasi 1: cek apakah toko masih buka saat proses checkout
   ini penting karena status toko bisa berubah antara saat pembeli melihat menu
   dan saat menekan tombol "Pesan" */
$cektoko = $conn->prepare("SELECT status_toko, nama_toko FROM tb_toko WHERE id_toko=? AND deleted=0");
$cektoko->bind_param("i", $idtoko);
$cektoko->execute();
$datatoko = $cektoko->get_result()->fetch_assoc();
$cektoko->close();

if (!$datatoko || $datatoko['status_toko'] !== 'buka') {
    $namatokox = $datatoko['nama_toko'] ?? 'Kantin';
    $_SESSION['flash'] = ['pesan' => "Maaf, kantin {$namatokox} sedang tutup. Pesanan tidak bisa diproses.", 'jenis' => 'gagal'];
    // kembali ke checkout agar pembeli bisa melihat pesan error
    header("Location: checkout.php?toko=$idtoko"); exit;
}

/* validasi 2: cek stok semua item sebelum memproses
   dilakukan sebelum transaksi dimulai agar tidak perlu rollback jika gagal */
foreach ($daftaritem as $item) {
    $cek = $conn->prepare("SELECT stok FROM tb_menu WHERE id_menu=? AND deleted=0 AND status='aktif'");
    $cek->bind_param("i", $item['id_menu']);
    $cek->execute();
    $stokdb = (int)($cek->get_result()->fetch_row()[0] ?? 0);
    $cek->close();

    // jika stok di database tidak cukup untuk qty yang dipesan
    if ($stokdb < $item['qty']) {
        $_SESSION['flash'] = ['pesan' => $item['nama_menu'] . ' stok tidak mencukupi', 'jenis' => 'gagal'];
        header("Location: checkout.php?toko=$idtoko"); exit;
    }
}

/* transaksi database: semua operasi di bawah harus berhasil semua
   jika ada yang gagal, rollback akan membatalkan semua perubahan
   ini mencegah kondisi seperti: order tersimpan tapi stok tidak berkurang */
$conn->begin_transaction();
try {
    // simpan data order utama ke tabel tb_order
    // status awal selalu 'Menunggu' — penjual yang mengubahnya
    $s = $conn->prepare("INSERT INTO tb_order (id_user,id_toko,total_harga,status_order,metode_pembayaran,catatan,tanggal_order) VALUES (?,?,?,'Menunggu',?,?,NOW())");
    // "iidss": integer, integer, double, string, string
    $s->bind_param("iidss", $idpengguna, $idtoko, $totalbayar, $metode, $catatan);
    $s->execute();
    // insert_id mengambil id yang baru saja di-insert (auto increment)
    $idpesananbaru = $conn->insert_id;
    $s->close();

    // simpan detail setiap item ke tabel tb_detail_order dan kurangi stok
    foreach ($daftaritem as $item) {
        $sub = $item['harga'] * $item['qty']; // hitung subtotal item

        // insert ke detail order
        $d   = $conn->prepare("INSERT INTO tb_detail_order (id_order,id_menu,jumlah,harga_satuan,subtotal) VALUES (?,?,?,?,?)");
        // "iiidd": integer, integer, integer, double, double
        $d->bind_param("iiidd", $idpesananbaru, $item['id_menu'], $item['qty'], $item['harga'], $sub);
        $d->execute(); $d->close();

        // kurangi stok menu — GREATEST(0, stok-qty) mencegah stok jadi negatif
        $u = $conn->prepare("UPDATE tb_menu SET stok=GREATEST(0,stok-?) WHERE id_menu=?");
        $u->bind_param("ii", $item['qty'], $item['id_menu']);
        $u->execute(); $u->close();
    }

    // semua berhasil — commit untuk menyimpan semua perubahan ke database
    $conn->commit();
    // hapus toko ini dari keranjang session karena sudah dipesan
    unset($_SESSION['keranjang'][$idtoko]);
    // hapus juga item-item yang sudah dipesan dari tabel tb_keranjang di database
    // agar keranjang di database sinkron dengan session dan tidak muncul kembali saat login ulang
    foreach ($daftaritem as $item) {
        $delk = $conn->prepare("DELETE FROM tb_keranjang WHERE id_user=? AND id_menu=?");
        $delk->bind_param("ii", $idpengguna, $item['id_menu']);
        $delk->execute(); $delk->close();
    }
    // redirect ke struk dengan parameter ?baru=1 untuk menampilkan banner sukses
    header("Location: struk.php?id_order=$idpesananbaru&baru=1"); exit;

} catch (Exception $e) {
    // jika ada error, batalkan semua perubahan database
    $conn->rollback();
    $_SESSION['flash'] = ['pesan' => 'Terjadi kesalahan, coba lagi', 'jenis' => 'gagal'];
    // kembali ke checkout agar pembeli bisa mencoba lagi
    header("Location: checkout.php?toko=$idtoko"); exit;
}
?>
