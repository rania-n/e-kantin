<?php
/*
  proses verifikasi akun pembeli.
  dipanggil dari verifikasi.php lewat form POST.

  aksi yang didukung:
  - terima : status pending → verified (boleh login & pesan)
  - tolak  : status pending → ditolak  (tidak bisa login)
  - reset  : kembalikan ke pending (untuk tombol "Kembalikan ke Menunggu" dari tab verified/ditolak)
*/
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// hanya boleh POST — mencegah perubahan status lewat URL biasa
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: verifikasi.php"); exit;
}

// ambil dan bersihkan input
$idUser = (int)($_POST['id_user'] ?? 0);
$aksi   = $_POST['aksi'] ?? '';

// aksi yang didukung:
// - terima : pending → verified (boleh login)
// - tolak  : pending → ditolak + langsung dihapus (deleted=1) — akun tidak bisa
//            login dan tidak bisa dikembalikan; slot username/email-nya bebas
// - reset  : verified → pending (kalau admin salah klik Terima, bisa undo)
if (!in_array($aksi, ['terima','tolak','reset'], true) || $idUser <= 0) {
    $_SESSION['flash'] = ['pesan' => 'Aksi tidak dikenali.', 'jenis' => 'gagal'];
    header("Location: verifikasi.php"); exit;
}

// pastikan user target memang pembeli yang masih aktif — jangan biarkan admin
// (tanpa sengaja) mengubah status verifikasi penjual/admin lain dari sini.
$cek = $conn->prepare("SELECT username, status_verifikasi FROM tb_user
                        WHERE id_user=? AND role='pembeli' AND deleted=0");
$cek->bind_param("i", $idUser);
$cek->execute();
$user = $cek->get_result()->fetch_assoc();
$cek->close();

if (!$user) {
    $_SESSION['flash'] = ['pesan' => 'Akun pembeli tidak ditemukan.', 'jenis' => 'gagal'];
    header("Location: verifikasi.php"); exit;
}

// jalankan perubahan sesuai aksi
if ($aksi === 'tolak') {
    // tolak = langsung soft-delete. status_verifikasi=ditolak hanya untuk catatan
    // historis kalau admin mau lihat di tab Terhapus → Manajemen Pengguna.
    $up = $conn->prepare("UPDATE tb_user
                           SET status_verifikasi='ditolak', deleted=1, deleted_at=NOW()
                           WHERE id_user=? AND role='pembeli'");
    $up->bind_param("i", $idUser);
    $tabBalik = 'pending';
} else {
    $statusBaru = ($aksi === 'terima') ? 'verified' : 'pending';
    // hindari update sia-sia kalau statusnya sudah sama
    if ($user['status_verifikasi'] === $statusBaru) {
        $_SESSION['flash'] = [
            'pesan' => 'Status akun ' . $user['username'] . ' sudah ' . $statusBaru . '.',
            'jenis' => 'gagal'
        ];
        header("Location: verifikasi.php"); exit;
    }
    $up = $conn->prepare("UPDATE tb_user SET status_verifikasi=?
                           WHERE id_user=? AND role='pembeli'");
    $up->bind_param("si", $statusBaru, $idUser);
    $tabBalik = $statusBaru;
}
$berhasil = $up->execute();
$up->close();

if (!$berhasil) {
    $_SESSION['flash'] = ['pesan' => 'Gagal memperbarui status akun.', 'jenis' => 'gagal'];
    header("Location: verifikasi.php"); exit;
}

// susun pesan sukses sesuai aksi
$pesanSukses = match($aksi) {
    'terima' => 'Akun ' . $user['username'] . ' berhasil disetujui — pembeli bisa login sekarang.',
    'tolak'  => 'Akun ' . $user['username'] . ' ditolak dan dihapus. Username & email-nya sekarang bebas dipakai pendaftar lain.',
    'reset'  => 'Akun ' . $user['username'] . ' dikembalikan ke status Menunggu.',
};
$_SESSION['flash'] = ['pesan' => $pesanSukses, 'jenis' => 'sukses'];

header("Location: verifikasi.php?filter=" . urlencode($tabBalik));
exit;
?>
