<?php
/* ============================================================
   PROSES UPDATE STATUS PESANAN
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idtoko = (int)$_SESSION['id_toko'];
$aksi   = $_GET['aksi']   ?? '';
$idpesanan = (int)($_GET['id'] ?? 0);
$filter = $_GET['filter'] ?? 'Semua';

function setFlash(string $pesan, string $jenis): void {
    $_SESSION['flash'] = ['pesan' => $pesan, 'jenis' => $jenis];
}

if (!$idpesanan) {
    setFlash("ID pesanan tidak valid.", 'gagal');
    header("Location: manajemenpesanan.php?filter=" . urlencode($filter)); exit;
}

// Pastikan pesanan milik toko ini
$cek = $conn->prepare("SELECT id_order, status_order FROM tb_order WHERE id_order=? AND id_toko=? AND deleted=0");
$cek->bind_param("ii", $idpesanan, $idtoko); $cek->execute();
$pesanan = $cek->get_result()->fetch_assoc(); $cek->close();

if (!$pesanan) {
    setFlash("Pesanan tidak ditemukan atau bukan milik toko kamu.", 'gagal');
    header("Location: manajemenpesanan.php?filter=" . urlencode($filter)); exit;
}

// Peta transisi status yang diizinkan
// key = aksi, value = [status_sekarang_yang_diizinkan, status_baru, pesan]
$transisi = [
    'proses'  => [['Menunggu'],              'Diproses',     'Pesanan mulai diproses.'],
    'siap'    => [['Diproses'],              'Siap Diambil', 'Pesanan ditandai siap diambil.'],
    'selesai' => [['Siap Diambil'],          'Selesai',      'Pesanan selesai!'],
    'batal'   => [['Menunggu','Diproses'],   'Dibatalkan',   'Pesanan dibatalkan.'],
];

if (!array_key_exists($aksi, $transisi)) {
    setFlash("Aksi tidak dikenali.", 'gagal');
    header("Location: manajemenpesanan.php?filter=" . urlencode($filter)); exit;
}

[$statusdiizinkan, $statusbaru, $pesanberhasil] = $transisi[$aksi];

if (!in_array($pesanan['status_order'], $statusdiizinkan)) {
    setFlash("Status pesanan saat ini (" . $pesanan['status_order'] . ") tidak bisa di-" . $aksi . ".", 'gagal');
    header("Location: manajemenpesanan.php?filter=" . urlencode($filter)); exit;
}

// Update status
$upd = $conn->prepare("UPDATE tb_order SET status_order=?, updated=NOW() WHERE id_order=?");
$upd->bind_param("si", $statusbaru, $idpesanan);
$upd->execute(); $upd->close();

setFlash($pesanberhasil, 'sukses');
header("Location: manajemenpesanan.php?filter=" . urlencode($filter));
exit;
?>
