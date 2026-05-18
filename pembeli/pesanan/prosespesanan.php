<?php
/* ============================================================
   PROSES PESANAN
   Dipanggil dari form checkout, lalu redirect ke struk.
   ============================================================ */
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../keranjang/keranjang.php"); exit;
}

$idtoko     = (int)($_POST['id_toko'] ?? 0);
$metode     = trim($_POST['metode']   ?? 'Tunai');
$catatan    = trim($_POST['catatan']  ?? '');
$idpengguna = (int)$_SESSION['id_user'];
$keranjang  = $_SESSION['keranjang'] ?? [];

if (!$idtoko || !isset($keranjang[$idtoko])) {
    $_SESSION['flash'] = ['pesan' => 'Keranjang tidak ditemukan', 'jenis' => 'gagal'];
    header("Location: ../keranjang/keranjang.php"); exit;
}

$itemtoko   = $keranjang[$idtoko];
$daftaritem = [];
$subtotal   = 0;

foreach ($itemtoko as $k => $v) {
    if ($k === '_info') continue;
    $daftaritem[] = $v;
    $subtotal    += $v['harga'] * $v['qty'];
}

if (empty($daftaritem)) {
    $_SESSION['flash'] = ['pesan' => 'Tidak ada item di keranjang', 'jenis' => 'gagal'];
    header("Location: ../keranjang/keranjang.php"); exit;
}

$biayalayanan = 1000;
$totalbayar   = $subtotal + $biayalayanan;

// Cek apakah toko masih buka sebelum proses order
$cektoko = $conn->prepare("SELECT status_toko, nama_toko FROM tb_toko WHERE id_toko=? AND deleted=0");
$cektoko->bind_param("i", $idtoko);
$cektoko->execute();
$datatoko = $cektoko->get_result()->fetch_assoc();
$cektoko->close();

if (!$datatoko || $datatoko['status_toko'] !== 'buka') {
    $namatokox = $datatoko['nama_toko'] ?? 'Kantin';
    $_SESSION['flash'] = ['pesan' => "Maaf, kantin {$namatokox} sedang tutup. Pesanan tidak bisa diproses.", 'jenis' => 'gagal'];
    header("Location: checkout.php?toko=$idtoko"); exit;
}

// Cek stok semua item sebelum proses
foreach ($daftaritem as $item) {
    $cek = $conn->prepare("SELECT stok FROM tb_menu WHERE id_menu=? AND deleted=0 AND status='aktif'");
    $cek->bind_param("i", $item['id_menu']);
    $cek->execute();
    $stokdb = (int)($cek->get_result()->fetch_row()[0] ?? 0);
    $cek->close();

    if ($stokdb < $item['qty']) {
        $_SESSION['flash'] = ['pesan' => $item['nama_menu'] . ' stok tidak mencukupi', 'jenis' => 'gagal'];
        header("Location: checkout.php?toko=$idtoko"); exit;
    }
}

// Transaksi DB
$conn->begin_transaction();
try {
    // Simpan order
    $s = $conn->prepare("INSERT INTO tb_order (id_user,id_toko,total_harga,status_order,metode_pembayaran,catatan,tanggal_order) VALUES (?,?,?,'Menunggu',?,?,NOW())");
    $s->bind_param("iidss", $idpengguna, $idtoko, $totalbayar, $metode, $catatan);
    $s->execute();
    $idpesananbaru = $conn->insert_id;
    $s->close();

    foreach ($daftaritem as $item) {
        $sub = $item['harga'] * $item['qty'];
        $d   = $conn->prepare("INSERT INTO tb_detail_order (id_order,id_menu,jumlah,harga_satuan,subtotal) VALUES (?,?,?,?,?)");
        $d->bind_param("iiidd", $idpesananbaru, $item['id_menu'], $item['qty'], $item['harga'], $sub);
        $d->execute(); $d->close();

        $u = $conn->prepare("UPDATE tb_menu SET stok=GREATEST(0,stok-?) WHERE id_menu=?");
        $u->bind_param("ii", $item['qty'], $item['id_menu']);
        $u->execute(); $u->close();
    }

    $conn->commit();
    unset($_SESSION['keranjang'][$idtoko]);
    header("Location: struk.php?id_order=$idpesananbaru&baru=1"); exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash'] = ['pesan' => 'Terjadi kesalahan, coba lagi', 'jenis' => 'gagal'];
    header("Location: checkout.php?toko=$idtoko"); exit;
}
?>
