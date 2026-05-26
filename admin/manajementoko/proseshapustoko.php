<?php
/* proses kosongkan kantin — melepas penjual dari kantin tanpa menghapus data kantin.
   PENTING: toko di-soft-delete, dan slot baru dibuat. Ini memastikan data menu
   dan pesanan penjual lama tidak terbawa ke penjual baru di slot yang sama.
   Akun penjual juga tidak dihapus — gunakan Hapus Pengguna untuk itu. */

// sambungkan ke database dan pastikan yang mengakses adalah admin
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardadmin.php';

// tolak akses langsung (bukan POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: kantin.php"); exit; }

// ambil id toko dari form, konversi ke integer
$id = (int)($_POST['id_toko'] ?? 0);

// validasi: id harus ada
if (!$id) { flash('gagal','Data tidak valid.'); redirect('kantin.php'); }

// cek apakah migrasi nomor_kantin sudah dijalankan (untuk pesan sukses)
$cekkolom = $conn->query("SHOW COLUMNS FROM tb_toko LIKE 'nomor_kantin'");
$migrasiSudah = ($cekkolom && $cekkolom->num_rows > 0);
$kolomNomor = $migrasiSudah ? "nomor_kantin" : "NULL AS nomor_kantin";

// cek apakah toko ini memang terisi (ada penjualnya)
$cek = $conn->prepare("SELECT id_user, id_toko, nama_toko, $kolomNomor FROM tb_toko WHERE id_toko=? AND deleted=0");
$cek->bind_param("i", $id); $cek->execute();
$toko = $cek->get_result()->fetch_assoc(); $cek->close();

if (!$toko) {
    flash('gagal','Kantin tidak ditemukan.');
    redirect('kantin.php');
}

if (!$toko['id_user']) {
    flash('gagal','Kantin sudah kosong.');
    redirect('kantin.php');
}

// simpan snapshot ke tb_riwayat_toko jika tabel sudah ada (migrasi sudah dijalankan)
$cektbr = $conn->query("SHOW TABLES LIKE 'tb_riwayat_toko'");
if ($cektbr && $cektbr->num_rows > 0) {
    $ins = $conn->prepare(
        "INSERT INTO tb_riwayat_toko (id_user, id_toko, nomor_kantin, nama_toko, tgl_keluar)
         VALUES (?, ?, ?, ?, NOW())"
    );
    $namatoko = $toko['nama_toko'] ?? '';
    $ins->bind_param("iiis", $toko['id_user'], $id, $toko['nomor_kantin'], $namatoko);
    $ins->execute(); $ins->close();
}

// soft-delete toko lama agar data menu dan order lama tetap terikat di id_toko tersebut (tidak ngikut ke toko baru)
$upd = $conn->prepare(
    "UPDATE tb_toko
     SET deleted=1, deleted_at=NOW(), nomor_kantin=NULL, status_toko='tutup'
     WHERE id_toko=? AND deleted=0"
);
$upd->bind_param("i", $id);
$upd->execute();
$upd->close();

// buat slot kantin kosong baru dengan nomor kantin yang sama (id_toko baru)
if ($toko['nomor_kantin'] !== null) {
    $ins_baru = $conn->prepare("INSERT INTO tb_toko (nomor_kantin, status_toko) VALUES (?, 'tutup')");
    $ins_baru->bind_param("i", $toko['nomor_kantin']);
    $ins_baru->execute(); $ins_baru->close();
} else {
    $conn->query("INSERT INTO tb_toko (status_toko) VALUES ('tutup')");
}

// label kantin untuk pesan: gunakan nomor jika tersedia, fallback ke id_toko
$labelKantin = ($toko['nomor_kantin'] !== null)
    ? 'Kantin ke-' . (int)$toko['nomor_kantin']
    : 'Kantin #' . (int)$toko['id_toko'];

// simpan flash message dan redirect ke daftar kantin
flash('sukses', "{$labelKantin} berhasil dikosongkan. Slot tersedia untuk penjual baru.");
redirect('kantin.php');

// fungsi pembantu: simpan flash message ke session
function flash(string $j, string $p): void { $_SESSION['flash'] = ['jenis'=>$j,'pesan'=>$p]; }

// fungsi pembantu: redirect ke url tertentu dan hentikan eksekusi
function redirect(string $url): void { header("Location: $url"); exit; }
?>
