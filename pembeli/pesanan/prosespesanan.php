<?php
/* file proses pembuatan pesanan
   dipanggil dari form checkout via POST
   alur: validasi → cek toko buka → cek stok → simpan ke database → redirect ke struk
   menggunakan transaksi database agar jika ada error, semua perubahan dibatalkan */

// guard memastikan hanya pembeli yang login yang bisa mengakses
include '../../3. komponen/guardpembeli.php';
// koneksi database untuk menyimpan order dan update stok
include '../../1. koneksi/koneksi.php';

// tolak akses selain POST — file ini tidak boleh dibuka langsung dari browser
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../keranjang/keranjang.php"); exit;
}

// ambil data dari form checkout
// (int) = cast ke integer, ?? 0 = kalau tidak ada key id_toko di POST pakai 0
// kombinasi keduanya = pertahanan dari input kosong/aneh yang dikirim user
$idtoko     = (int)($_POST['id_toko'] ?? 0);
// metode pembayaran selalu Tunai — tidak ada opsi lain
// meskipun ada nilai lain dari POST, paksa ke 'Tunai' untuk keamanan
// (jangan pernah percaya input dari client, set sendiri di server)
$metode     = 'Tunai';
$catatan    = trim($_POST['catatan']  ?? ''); // trim() hapus spasi di awal/akhir
$idpengguna = (int)$_SESSION['id_user']; // id pembeli yang sedang login
// ambil keranjang dari session — data item ada di sini
// struktur: $keranjang[id_toko][id_menu] = [nama_menu, harga, qty, ...]
$keranjang  = $_SESSION['keranjang'] ?? [];

// validasi: toko harus valid dan ada di keranjang session
if (!$idtoko || !isset($keranjang[$idtoko])) {
    $_SESSION['flash'] = ['pesan' => 'Keranjang tidak ditemukan', 'jenis' => 'gagal'];
    header("Location: ../keranjang/keranjang.php"); exit;
}

// ambil item dari toko yang dipilih, pisahkan _info
// $itemtoko berisi semua entry keranjang untuk 1 toko, termasuk metadata _info
$itemtoko   = $keranjang[$idtoko];
$daftaritem = []; // akan diisi hanya item menu (tanpa _info)
$subtotal   = 0;  // akumulator total harga sebelum biaya tambahan

foreach ($itemtoko as $k => $v) {
    if ($k === '_info') continue; // lewati data info toko (bukan item menu)
    $daftaritem[] = $v;
    $subtotal    += $v['harga'] * $v['qty']; // jumlahkan harga * qty tiap item
}

// jika keranjang toko kosong, tolak
if (empty($daftaritem)) {
    $_SESSION['flash'] = ['pesan' => 'Tidak ada item di keranjang', 'jenis' => 'gagal'];
    header("Location: ../keranjang/keranjang.php"); exit;
}

// total yang disimpan ke database sama dengan subtotal — tidak ada biaya tambahan
$totalbayar = $subtotal;

/* validasi 1: cek apakah toko masih buka saat proses checkout
   ini penting karena status toko bisa berubah antara saat pembeli melihat menu
   dan saat menekan tombol "Pesan".
   ambil juga nama_toko, nomor_kantin, foto_toko untuk disnapshot ke tb_order.
   prepared statement (prepare + bind_param) = pola standar query aman dari sql injection */
$cektoko = $conn->prepare("SELECT status_toko, nama_toko, id_user, nomor_kantin, foto_toko FROM tb_toko WHERE id_toko=? AND deleted=0");
$cektoko->bind_param("i", $idtoko); // "i" = integer, mengganti tanda ? di query
$cektoko->execute();
$datatoko = $cektoko->get_result()->fetch_assoc(); // ambil 1 baris hasil sebagai array asosiatif
$cektoko->close(); // tutup statement agar resource db dilepas

if (!$datatoko || $datatoko['status_toko'] !== 'buka') {
    $namatokox = $datatoko['nama_toko'] ?? 'Kantin';
    $_SESSION['flash'] = ['pesan' => "Maaf, kantin {$namatokox} sedang tutup. Pesanan tidak bisa diproses.", 'jenis' => 'gagal'];
    // kembali ke checkout agar pembeli bisa melihat pesan error
    header("Location: checkout.php?toko=$idtoko"); exit;
}

/* validasi 2: cek stok semua item sebelum memproses
   dilakukan sebelum transaksi dimulai agar tidak perlu rollback jika gagal.
   prinsip: validasi dulu → baru mulai transaksi → lebih efisien */
foreach ($daftaritem as $item) {
    // cek stok terbaru langsung dari db (bukan dari session) karena bisa berubah real-time
    $cek = $conn->prepare("SELECT stok FROM tb_menu WHERE id_menu=? AND deleted=0 AND status='aktif'");
    $cek->bind_param("i", $item['id_menu']);
    $cek->execute();
    // fetch_row() mengembalikan array index numerik, [0] = kolom pertama (stok)
    // ?? 0 = kalau menu tidak ditemukan/null, anggap stok 0
    $stokdb = (int)($cek->get_result()->fetch_row()[0] ?? 0);
    $cek->close();

    // jika stok di database tidak cukup untuk qty yang dipesan
    if ($stokdb < $item['qty']) {
        $_SESSION['flash'] = ['pesan' => $item['nama_menu'] . ' stok tidak mencukupi', 'jenis' => 'gagal'];
        header("Location: checkout.php?toko=$idtoko"); exit;
    }
}

/* transaksi database: semua operasi di bawah harus berhasil semua
   jika ada yang gagal, rollback akan membatalkan semua perubahan
   ini mencegah kondisi seperti: order tersimpan tapi stok tidak berkurang.
   konsep ACID: transaksi menjamin atomicity = "all or nothing".
   begin_transaction() menonaktifkan autocommit sampai commit/rollback dipanggil */
$conn->begin_transaction();
try {
    // simpan data order utama ke tabel tb_order
    // id_penjual mencatat siapa penjualnya saat order ini dibuat — kunci isolasi data antar penjual.
    // snapshot nama_toko/nomor_kantin/foto_toko = potret kondisi toko saat order dibuat.
    // berkat snapshot ini, histori order tetap tampil benar meski penjual ganti atau toko diedit.
    $idpenjual = (int)($datatoko['id_user'] ?? 0) ?: null;
    $snapnama  = $datatoko['nama_toko'] ?? null;
    $snapnomor = isset($datatoko['nomor_kantin']) ? (int)$datatoko['nomor_kantin'] : null;
    $snapfoto  = $datatoko['foto_toko'] ?? null;

    // status awal selalu 'Menunggu' — penjual yang mengubahnya jadi Diproses/Selesai
    // NOW() = fungsi mysql untuk waktu sekarang (timestamp dari server db)
    $s = $conn->prepare("INSERT INTO tb_order
        (id_user, id_toko, id_penjual,
         nama_toko_snapshot, nomor_kantin_snapshot, foto_toko_snapshot,
         total_harga, status_order, metode_pembayaran, catatan, tanggal_order)
        VALUES (?,?,?, ?,?,?, ?,'Menunggu',?,?, NOW())");
    // tipe bind: i i i  s i s  d  s s
    // i=integer, s=string, d=double (untuk angka desimal seperti harga)
    // urutan tipe HARUS sesuai urutan variabel yang dibind di bawah
    $s->bind_param("iiisisdss",
        $idpengguna, $idtoko, $idpenjual,
        $snapnama, $snapnomor, $snapfoto,
        $totalbayar, $metode, $catatan
    );
    $s->execute();
    // insert_id mengambil id yang baru saja di-insert (auto increment)
    // dipakai sebagai foreign key untuk tabel tb_detail_order di bawah
    $idpesananbaru = $conn->insert_id;
    $s->close();

    // simpan detail setiap item ke tabel tb_detail_order dan kurangi stok
    // pola: 1 order header (tb_order) ↔ banyak baris detail (tb_detail_order) → relasi 1-ke-N
    foreach ($daftaritem as $item) {
        $sub      = $item['harga'] * $item['qty']; // hitung subtotal item
        $snapmenu = $item['nama_menu'] ?? null;    // snapshot nama menu saat order

        // insert ke detail order — nama_menu_snapshot membekukan nama menu di saat order dibuat
        // kenapa snapshot? agar histori order tetap menampilkan nama lama meski menu sudah diedit/dihapus penjual
        $d = $conn->prepare("INSERT INTO tb_detail_order
            (id_order, id_menu, nama_menu_snapshot, jumlah, harga_satuan, subtotal)
            VALUES (?,?,?,?,?,?)");
        $d->bind_param("iisidd",
            $idpesananbaru, $item['id_menu'], $snapmenu, $item['qty'], $item['harga'], $sub
        );
        $d->execute(); $d->close();

        // kurangi stok menu — GREATEST(0, stok-qty) mencegah stok jadi negatif
        // GREATEST mysql function: ambil nilai terbesar dari argumen yang diberikan
        // jadi kalau stok-qty hasilnya negatif, dipaksa 0
        $u = $conn->prepare("UPDATE tb_menu SET stok=GREATEST(0,stok-?) WHERE id_menu=?");
        $u->bind_param("ii", $item['qty'], $item['id_menu']);
        $u->execute(); $u->close();
    }

    // semua berhasil — commit untuk menyimpan semua perubahan ke database
    // sebelum commit, perubahan masih "sementara" dan bisa dibatalkan dengan rollback
    $conn->commit();
    // hapus toko ini dari keranjang session karena sudah dipesan
    // unset() menghapus key/variabel tertentu dari array
    unset($_SESSION['keranjang'][$idtoko]);
    // hapus juga item-item yang sudah dipesan dari tabel tb_keranjang di database
    // agar keranjang di database sinkron dengan session dan tidak muncul kembali saat login ulang
    foreach ($daftaritem as $item) {
        $delk = $conn->prepare("DELETE FROM tb_keranjang WHERE id_user=? AND id_menu=?");
        $delk->bind_param("ii", $idpengguna, $item['id_menu']);
        $delk->execute(); $delk->close();
    }
    // redirect ke struk dengan parameter ?baru=1 untuk menampilkan banner sukses
    // pattern PRG (Post-Redirect-Get): setelah POST sukses selalu redirect agar refresh browser tidak mengulang submit
    header("Location: struk.php?id_order=$idpesananbaru&baru=1"); exit;

} catch (Exception $e) {
    // jika ada error di blok try, batalkan semua perubahan database
    // rollback() mengembalikan db ke kondisi sebelum begin_transaction() dipanggil
    $conn->rollback();
    // flash message disimpan di session, akan ditampilkan di halaman tujuan lalu dihapus
    $_SESSION['flash'] = ['pesan' => 'Terjadi kesalahan, coba lagi', 'jenis' => 'gagal'];
    // kembali ke checkout agar pembeli bisa mencoba lagi
    header("Location: checkout.php?toko=$idtoko"); exit;
}
?>
