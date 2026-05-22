<?php
/* proses kosongkan kantin — melepas penjual dari kantin tanpa menghapus data kantin.
   PENTING: toko TIDAK di-soft-delete. Hanya id_user dan nama_toko yang dikosongkan.
   Ini memastikan slot kantin tetap tersedia untuk penjual berikutnya.
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
$cek = $conn->prepare("SELECT id_user, id_toko, $kolomNomor FROM tb_toko WHERE id_toko=? AND deleted=0");
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

// kosongkan kantin: set id_user=NULL dan nama_toko=NULL
// status_toko diset 'tutup' agar kantin kosong tidak muncul sebagai buka di sisi pembeli
// deleted TETAP 0 — kantin tidak pernah dihapus dari database
$upd = $conn->prepare(
    "UPDATE tb_toko
     SET id_user=NULL, nama_toko=NULL, status_toko='tutup'
     WHERE id_toko=? AND deleted=0"
);
$upd->bind_param("i", $id);
$upd->execute();
$upd->close();

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
