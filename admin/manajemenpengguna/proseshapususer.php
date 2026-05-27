<?php
/* proses hapus pengguna (soft delete pada tb_user).
   alur untuk penjual:
   1. soft delete tb_user (deleted=1)
   2. simpan snapshot toko ke tb_riwayat_toko (jika tabel sudah ada)
   3. kosongkan slot: id_user=NULL, nama_toko=NULL, status_toko='tutup'
   → 10 slot tb_toko tetap bersih, data historis tersimpan di riwayat.
   admin tidak bisa menghapus akunnya sendiri. */

include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: user.php"); exit; }

$id = (int)($_POST['id_user'] ?? 0);
if (!$id) { flash('gagal','Data tidak valid.'); redirect('user.php'); }

if ($id === (int)$_SESSION['id_user']) {
    flash('gagal','Tidak dapat menghapus akun sendiri.');
    redirect('user.php');
}

// soft delete tb_user
$cekdel   = $conn->query("SHOW COLUMNS FROM tb_user LIKE 'deleted_at'");
$adadelat = ($cekdel && $cekdel->num_rows > 0);
$delatkol = $adadelat ? ", deleted_at=NOW()" : "";

$upd = $conn->prepare("UPDATE tb_user SET deleted=1{$delatkol} WHERE id_user=? AND deleted=0");
$upd->bind_param("i", $id);
$upd->execute();
$upd->close();

// ambil data toko penjual ini (sebelum dikosongkan)
$qt = $conn->prepare("SELECT id_toko, nomor_kantin, nama_toko, foto_toko FROM tb_toko WHERE id_user=? AND deleted=0");
$qt->bind_param("i", $id); $qt->execute();
$tokopenjual = $qt->get_result()->fetch_assoc(); $qt->close();

if ($tokopenjual) {
    // simpan snapshot ke tb_riwayat_toko jika tabel sudah ada (migrasi sudah dijalankan)
    $cektbr = $conn->query("SHOW TABLES LIKE 'tb_riwayat_toko'");
    if ($cektbr && $cektbr->num_rows > 0) {
        $ins = $conn->prepare(
            "INSERT INTO tb_riwayat_toko (id_user, id_toko, nomor_kantin, nama_toko, foto_toko, tgl_keluar)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $ins->bind_param("iiiss", $id, $tokopenjual['id_toko'], $tokopenjual['nomor_kantin'], $tokopenjual['nama_toko'], $tokopenjual['foto_toko']);
        $ins->execute(); $ins->close();
    }

    // KOSONGKAN slot toko tanpa menghapus id_toko (karena id_toko paten 10 slot)
    $upt = $conn->prepare(
        "UPDATE tb_toko SET id_user=NULL, nama_toko=NULL, foto_toko=NULL, status_toko='tutup' WHERE id_toko=?"
    );
    $upt->bind_param("i", $tokopenjual['id_toko']);
    $upt->execute(); $upt->close();
    
    // Hapus (soft-delete) menu lama agar penjual baru yang menempati slot ini tidak mewarisi menu lama
    $upm = $conn->prepare("UPDATE tb_menu SET deleted=1 WHERE id_toko=?");
    $upm->bind_param("i", $tokopenjual['id_toko']);
    $upm->execute(); $upm->close();
}

flash('sukses','Pengguna berhasil dihapus. Kantin yang ditempatinya kini tersedia untuk penjual baru.');
redirect('user.php');

function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }
function redirect(string $url): void { header("Location: $url"); exit; }
?>
