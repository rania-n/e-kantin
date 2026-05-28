<?php
/* proses hapus pengguna (soft delete pada tb_user).
   alur untuk penjual:
   1. soft delete tb_user (deleted=1)
   2. simpan snapshot toko ke tb_riwayat_toko (tgl_masuk dan tgl_keluar)
   3. kosongkan slot tb_toko (id_user=NULL) — id_toko paten 10 slot permanen
   4. soft delete semua menu toko lama
   → isolasi data 100% lewat kolom id_penjual di tb_order dan tb_rating.
   admin tidak bisa menghapus akunnya sendiri. */

include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: user.php"); exit; }

$id = (int)($_POST['id_user'] ?? 0);
if (!$id) { flash('gagal','Data tidak valid.'); redirect('user.php'); }

// proteksi penting: admin yang sedang login tidak boleh menghapus akunnya sendiri,
// karena bisa mengunci diri dari sistem (tidak ada admin tersisa)
if ($id === (int)$_SESSION['id_user']) {
    flash('gagal','Tidak dapat menghapus akun sendiri.');
    redirect('user.php');
}

// === SOFT DELETE tb_user ===
// soft delete = data tidak benar-benar dihapus dari database, hanya ditandai deleted=1.
// keuntungan: history transaksi/order tetap utuh, bisa dipulihkan, audit trail terjaga.
// kebalikannya adalah hard delete (DELETE FROM) yang menghapus baris permanen.

// cek dulu apakah kolom 'deleted_at' ada di tb_user (kompatibilitas dengan db lama yang belum migrasi)
$cekdel   = $conn->query("SHOW COLUMNS FROM tb_user LIKE 'deleted_at'");
$adadelat = ($cekdel && $cekdel->num_rows > 0);
// kalau ada, ikut update timestamp kapan akun dihapus
$delatkol = $adadelat ? ", deleted_at=NOW()" : "";

// catatan: {$delatkol} disisipkan langsung ke string SQL — ini AMAN karena nilainya
// dikontrol oleh kode (bukan input user), bukan dari $_POST/$_GET
$upd = $conn->prepare("UPDATE tb_user SET deleted=1{$delatkol} WHERE id_user=? AND deleted=0");
$upd->bind_param("i", $id);
$upd->execute();
$upd->close();

// ambil data toko penjual ini (sebelum dikosongkan)
$qt = $conn->prepare("SELECT id_toko, nomor_kantin, nama_toko, foto_toko FROM tb_toko WHERE id_user=? AND deleted=0");
$qt->bind_param("i", $id); $qt->execute();
$tokopenjual = $qt->get_result()->fetch_assoc(); $qt->close();

if ($tokopenjual) {
    // === SNAPSHOT TOKO ===
    // snapshot = "foto" kondisi toko saat penjual ini keluar.
    // disimpan ke tb_riwayat_toko supaya order/laporan lama tetap menampilkan
    // nama+foto toko yang BENAR walau slot toko sudah ditempati penjual baru.
    // tanpa snapshot, data historis akan tercampur dengan toko baru di slot yang sama.

    // cek apakah tabel riwayat sudah dibuat (migrasi sudah dijalankan)
    $cektbr = $conn->query("SHOW TABLES LIKE 'tb_riwayat_toko'");
    if ($cektbr && $cektbr->num_rows > 0) {
        // ambil tanggal_mulai dari slot (kapan penjual ini mulai bertugas) — untuk isolasi data 100%
        $tglmasukval = null;
        $cekmulai = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'tanggal_mulai'");
        if ($cekmulai && $cekmulai->num_rows > 0) {
            $qtm = $conn->prepare("SELECT tanggal_mulai FROM tb_toko WHERE id_toko=?");
            $qtm->bind_param("i", $tokopenjual['id_toko']); $qtm->execute();
            $rowmulai = $qtm->get_result()->fetch_row(); $qtm->close();
            $tglmasukval = ($rowmulai && $rowmulai[0]) ? $rowmulai[0] : null;
        }
        // gunakan kolom tgl_masuk jika sudah ada di tabel riwayat
        $cektgm = $conn->query("SHOW COLUMNS FROM tb_riwayat_toko LIKE 'tgl_masuk'");
        $adatgm = ($cektgm && $cektgm->num_rows > 0);
        if ($adatgm) {
            $ins = $conn->prepare(
                "INSERT INTO tb_riwayat_toko (id_user, id_toko, nomor_kantin, nama_toko, foto_toko, tgl_masuk, tgl_keluar)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            $ins->bind_param("iiisss", $id, $tokopenjual['id_toko'], $tokopenjual['nomor_kantin'], $tokopenjual['nama_toko'], $tokopenjual['foto_toko'], $tglmasukval);
        } else {
            $ins = $conn->prepare(
                "INSERT INTO tb_riwayat_toko (id_user, id_toko, nomor_kantin, nama_toko, foto_toko, tgl_keluar)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $ins->bind_param("iiiss", $id, $tokopenjual['id_toko'], $tokopenjual['nomor_kantin'], $tokopenjual['nama_toko'], $tokopenjual['foto_toko']);
        }
        $ins->execute(); $ins->close();
    }

    // === KOSONGKAN SLOT TOKO ===
    // sistem ini punya 10 slot toko tetap (id_toko 1-10). slot tidak dihapus,
    // hanya dikosongkan (id_user=NULL) supaya bisa diisi penjual baru nanti.
    // status_toko='tutup' = otomatis tutup karena belum ada penjual yang mengisi.
    $upt = $conn->prepare("UPDATE tb_toko SET id_user=NULL, nama_toko=NULL, foto_toko=NULL, status_toko='tutup' WHERE id_toko=?");
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
