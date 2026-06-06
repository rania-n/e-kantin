<?php
/* file ini memproses perubahan status pesanan oleh penjual.
   alur normal: menunggu → diproses → siap diambil → selesai.
   pembatalan bisa dilakukan dari status menunggu atau diproses, dan stok dikembalikan. */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

// ambil id penjual, aksi yang diminta, id pesanan, dan filter aktif dari URL
$idpenjual = (int)$_SESSION['id_user'];
$aksi      = $_GET['aksi']   ?? '';
$idpesanan = (int)($_GET['id'] ?? 0);
$filter    = $_GET['filter'] ?? 'Semua';

// simpan pesan ke session agar bisa ditampilkan setelah redirect
function setFlash(string $pesan, string $jenis): void {
    $_SESSION['flash'] = ['pesan' => $pesan, 'jenis' => $jenis];
}

// jika id pesanan tidak valid (0), langsung kembalikan dengan pesan error
if (!$idpesanan) {
    setFlash("ID pesanan tidak valid.", 'gagal');
    header("Location: manajemenpesanan.php?filter=" . urlencode($filter)); exit;
}

// ambil data pesanan dan pastikan pesanan ini memang milik penjual yang sedang login
$cek = $conn->prepare("SELECT id_order, status_order FROM tb_order WHERE id_order=? AND id_penjual=? AND deleted=0");
$cek->bind_param("ii", $idpesanan, $idpenjual); $cek->execute();
$pesanan = $cek->get_result()->fetch_assoc(); $cek->close();

// jika pesanan tidak ditemukan atau bukan milik penjual ini, tolak aksi
if (!$pesanan) {
    setFlash("Pesanan tidak ditemukan atau bukan milik tokomu.", 'gagal');
    header("Location: manajemenpesanan.php?filter=" . urlencode($filter)); exit;
}

/* peta transisi status yang diizinkan.
   format: 'aksi' => [status_asal_yang_diizinkan, status_tujuan, pesan_sukses]
   contoh: aksi 'proses' hanya bisa dilakukan jika status saat ini adalah 'Menunggu' */
$transisi = [
    'proses'  => [['Menunggu'],            'Diproses',     'Pesanan mulai diproses.'],
    'siap'    => [['Diproses'],            'Siap Diambil', 'Pesanan ditandai siap diambil.'],
    'selesai' => [['Siap Diambil'],        'Selesai',      'Pesanan selesai!'],
    'batal'   => [['Menunggu','Diproses'], 'Dibatalkan',   'Pesanan dibatalkan.'],
];

// tolak jika aksi tidak ada dalam daftar yang dikenali
if (!array_key_exists($aksi, $transisi)) {
    setFlash("Aksi tidak dikenali.", 'gagal');
    header("Location: manajemenpesanan.php?filter=" . urlencode($filter)); exit;
}

// pecah array transisi menjadi tiga variabel
[$statusdiizinkan, $statusbaru, $pesanberhasil] = $transisi[$aksi];

// periksa apakah status saat ini ada dalam daftar status asal yang diizinkan
if (!in_array($pesanan['status_order'], $statusdiizinkan)) {
    setFlash("Status pesanan saat ini (" . $pesanan['status_order'] . ") tidak bisa di-" . $aksi . ".", 'gagal');
    header("Location: manajemenpesanan.php?filter=" . urlencode($filter)); exit;
}

/* jika pesanan dibatalkan, kembalikan stok semua item yang ada di pesanan ini.
   ini penting agar stok tidak berkurang sia-sia akibat pesanan yang tidak jadi */
if ($aksi === 'batal') {
    // ambil semua item di pesanan yang akan dibatalkan, beserta stok_dipotong
    // (jumlah yang benar-benar terpotong saat order dibuat).
    $qd = $conn->prepare("SELECT id_menu, jumlah, stok_dipotong FROM tb_detail_order WHERE id_order=? AND deleted=0");
    $qd->bind_param("i", $idpesanan);
    $qd->execute();
    $daftaritem = $qd->get_result()->fetch_all(MYSQLI_ASSOC);
    $qd->close();

    // kembalikan stok TEPAT sebanyak yang dulu terpotong — tidak lebih.
    // pakai stok_dipotong; kalau 0 (order lama sebelum kolom ada) fallback ke jumlah.
    foreach ($daftaritem as $item) {
        $kembali = ((int)$item['stok_dipotong'] > 0) ? (int)$item['stok_dipotong'] : (int)$item['jumlah'];
        $us = $conn->prepare("UPDATE tb_menu SET stok = stok + ? WHERE id_menu=?");
        $us->bind_param("ii", $kembali, $item['id_menu']);
        $us->execute();
        $us->close();
    }
}

// update status pesanan ke status yang baru dan catat waktu perubahan
$upd = $conn->prepare("UPDATE tb_order SET status_order=?, updated=NOW() WHERE id_order=?");
$upd->bind_param("si", $statusbaru, $idpesanan);
$upd->execute(); $upd->close();

// simpan pesan sukses dan redirect kembali ke halaman pesanan
setFlash($pesanberhasil, 'sukses');
header("Location: manajemenpesanan.php?filter=" . urlencode($filter));
exit;
?>
