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
$qt = $conn->prepare("SELECT id_toko, nomor_kantin, nama_toko FROM tb_toko WHERE id_user=? AND deleted=0");
$qt->bind_param("i", $id); $qt->execute();
$tokopenjual = $qt->get_result()->fetch_assoc(); $qt->close();

if ($tokopenjual) {
    // simpan snapshot ke tb_riwayat_toko jika tabel sudah ada (migrasi sudah dijalankan)
    $cektbr = $conn->query("SHOW TABLES LIKE 'tb_riwayat_toko'");
    if ($cektbr && $cektbr->num_rows > 0) {
        $ins = $conn->prepare(
            "INSERT INTO tb_riwayat_toko (id_user, id_toko, nomor_kantin, nama_toko, tgl_keluar)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $ins->bind_param("iiis", $id, $tokopenjual['id_toko'], $tokopenjual['nomor_kantin'], $tokopenjual['nama_toko']);
        $ins->execute(); $ins->close();
    }

    // soft-delete toko lama agar data menu dan order lama tetap terikat di id_toko tersebut (tidak ngikut ke toko baru)
    $upt = $conn->prepare(
        "UPDATE tb_toko SET deleted=1, deleted_at=NOW(), nomor_kantin=NULL, status_toko='tutup' WHERE id_toko=?"
    );
    $upt->bind_param("i", $tokopenjual['id_toko']);
    $upt->execute(); $upt->close();

    // buat slot kantin kosong baru dengan nomor kantin yang sama (id_toko baru)
    if ($tokopenjual['nomor_kantin'] !== null) {
        $ins_baru = $conn->prepare("INSERT INTO tb_toko (nomor_kantin, status_toko) VALUES (?, 'tutup')");
        $ins_baru->bind_param("i", $tokopenjual['nomor_kantin']);
        $ins_baru->execute(); $ins_baru->close();
    } else {
        $conn->query("INSERT INTO tb_toko (status_toko) VALUES ('tutup')");
    }
}

flash('sukses','Pengguna berhasil dihapus. Kantin yang ditempatinya kini tersedia untuk penjual baru.');
redirect('user.php');

function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }
function redirect(string $url): void { header("Location: $url"); exit; }
?>
