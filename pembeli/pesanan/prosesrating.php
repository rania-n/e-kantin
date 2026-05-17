<?php
/* ============================================================
   PROSES RATING
   ============================================================ */
include '../../3. komponen/guard.php';
include '../../1. koneksi/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: pesanan.php"); exit; }

$idpesanan  = (int)($_POST['id_order']    ?? 0);
$nilaitoko  = (int)($_POST['rating_toko'] ?? 0);
$ulasan     = trim($_POST['ulasan']        ?? '');
$idpengguna = (int)$_SESSION['id_user'];

if (!$idpesanan || $nilaitoko < 1 || $nilaitoko > 5) {
    $_SESSION['flash'] = ['pesan' => 'Data rating tidak valid', 'jenis' => 'gagal'];
    header("Location: pesanan.php?tab=riwayat"); exit;
}

// Verifikasi pesanan milik user
$q = $conn->prepare("SELECT id_toko,status_order FROM tb_order WHERE id_order=? AND id_user=? AND deleted=0");
$q->bind_param("ii", $idpesanan, $idpengguna);
$q->execute();
$pesanan = $q->get_result()->fetch_assoc();
$q->close();

if (!$pesanan || !in_array($pesanan['status_order'], ['Selesai','Siap Diambil'])) {
    $_SESSION['flash'] = ['pesan' => 'Pesanan tidak ditemukan atau belum selesai', 'jenis' => 'gagal'];
    header("Location: pesanan.php?tab=riwayat"); exit;
}

// Cek duplikat
$cd = $conn->prepare("SELECT id_rating FROM tb_rating WHERE id_order=? AND id_user=?");
$cd->bind_param("ii", $idpesanan, $idpengguna);
$cd->execute();
if ($cd->get_result()->num_rows > 0) {
    $_SESSION['flash'] = ['pesan' => 'Kamu sudah memberi rating untuk pesanan ini', 'jenis' => 'info'];
    header("Location: pesanan.php?tab=riwayat"); exit;
}
$cd->close();

// Simpan rating utama
$idtoko = $pesanan['id_toko'] ?? null;
$ins    = $conn->prepare("INSERT INTO tb_rating (id_order,id_user,id_toko,rating_toko,ulasan) VALUES (?,?,?,?,?)");
$ins->bind_param("iiiss", $idpesanan, $idpengguna, $idtoko, $nilaitoko, $ulasan);
if (!$ins->execute()) {
    $_SESSION['flash'] = ['pesan' => 'Gagal menyimpan rating', 'jenis' => 'gagal'];
    header("Location: pesanan.php?tab=riwayat"); exit;
}
$idratingbaru = $conn->insert_id;
$ins->close();

// Simpan rating per menu (jika ada tabel tb_rating_menu)
$nilaimenu = $_POST['nilai_menu'] ?? [];
if (!empty($nilaimenu)) {
    $cektabel = $conn->query("SHOW TABLES LIKE 'tb_rating_menu'");
    if ($cektabel && $cektabel->num_rows > 0) {
        foreach ($nilaimenu as $idmenu => $nilai) {
            $idmenu = (int)$idmenu;
            $nilai  = max(1, min(5, (int)$nilai));
            if ($idmenu > 0 && $nilai > 0) {
                $rm = $conn->prepare("INSERT INTO tb_rating_menu (id_rating,id_menu,rating) VALUES (?,?,?)");
                $rm->bind_param("iii", $idratingbaru, $idmenu, $nilai);
                $rm->execute(); $rm->close();
            }
        }
    }
}

$_SESSION['flash'] = ['pesan' => 'Rating berhasil dikirim, terima kasih!', 'jenis' => 'sukses'];
header("Location: pesanan.php?tab=riwayat"); exit;
?>
