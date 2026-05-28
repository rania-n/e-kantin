<?php
/* file ini menangani semua aksi kelola menu: tambah, edit, toggle status, dan hapus.
   setelah setiap aksi, selalu redirect kembali ke halaman manajemenmenu.php */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

// ambil id toko dan id penjual dari session
$idtoko    = (int)$_SESSION['id_toko'];
$idpenjual = (int)$_SESSION['id_user'];
$aksi      = $_POST['aksi'] ?? $_GET['aksi'] ?? '';
$filter = $_POST['filter'] ?? $_GET['filter'] ?? 'Semua';

/* fungsi setFlash menyimpan pesan ke session.
   pesan ini akan dibaca dan ditampilkan di halaman manajemenmenu setelah redirect */
function setFlash(string $pesan, string $jenis): void {
    $_SESSION['flash'] = ['pesan' => $pesan, 'jenis' => $jenis];
}

/* fungsi kembali melakukan redirect ke halaman manajemenmenu dengan filter yang aktif.
   jika ada $editid, tambahkan parameter ?edit= agar modal edit terbuka kembali */
function kembali(string $filter, int $editid = 0): void {
    $url = "manajemenmenu.php?filter=" . urlencode($filter);
    if ($editid) $url .= "&edit=$editid";
    header("Location: $url"); exit;
}

try {

    /* aksi tambah dan edit memiliki alur serupa:
       validasi input → proses foto → simpan ke database */
    if ($aksi === 'tambah' || $aksi === 'edit') {
        // pastikan request berasal dari POST (form) bukan GET langsung dari browser
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            kembali($filter);
        }

        // ambil dan bersihkan semua input dari form
        $namamenu  = trim($_POST['nama_menu']  ?? '');
        $harga     = (int)($_POST['harga']     ?? 0);
        $stok      = (int)($_POST['stok']      ?? 0);
        $kategori  = trim($_POST['kategori']   ?? '');
        $deskripsi = trim($_POST['deskripsi']  ?? '');

        // daftar kategori yang diizinkan (harus sama dengan yang ada di halaman form)
        $kategorilist = ['Makanan Berat','Makanan Ringan','Makanan Sehat','Minuman Ringan','Minuman Sehat'];

        // validasi satu per satu — lempar exception jika ada yang tidak valid
        if (empty($namamenu))                          throw new Exception("Nama menu wajib diisi.");
        if (strlen($namamenu) > 50)                    throw new Exception("Nama menu maksimal 50 karakter.");
        if (!in_array($kategori, $kategorilist))       throw new Exception("Kategori tidak valid.");
        if ($harga < 0)                                throw new Exception("Harga tidak boleh negatif.");
        if ($harga > 999999)                           throw new Exception("Harga terlalu besar (maks. Rp 999.999).");
        if ($stok < 0)                                 throw new Exception("Stok tidak boleh negatif.");
        if ($stok > 9999)                              throw new Exception("Stok terlalu besar (maks. 9.999).");

        // proses upload foto jika ada file yang dikirim dan tidak ada error upload
        $fotofile = '';
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            // validasi tipe mime file — hanya jpg, png, webp yang diizinkan
            $allowedmime = ['image/jpeg','image/png','image/webp'];
            if (!in_array($_FILES['foto']['type'], $allowedmime)) {
                throw new Exception("Format gambar hanya JPG, PNG, atau WEBP.");
            }
            // validasi ukuran file — maksimal 2MB (2 * 1024 * 1024 byte)
            if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Ukuran gambar maksimal 2MB.");
            }
            // buat nama file unik menggunakan uniqid() agar tidak tabrakan dengan file lain
            $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotofile = uniqid() . '.' . strtolower($ext);
            $target   = '../../2. aset/katalog/' . $fotofile;
            // pindahkan file dari folder sementara ke folder katalog
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
                throw new Exception("Gagal mengupload gambar. Pastikan folder katalog bisa ditulis.");
            }
        } elseif ($aksi === 'edit') {
            // saat edit tanpa mengganti foto, gunakan nama file foto yang lama
            $fotofile = $_POST['foto_lama'] ?? '';
        } else {
            // saat tambah menu baru, foto wajib ada
            throw new Exception("Foto menu wajib diunggah.");
        }

        if ($aksi === 'tambah') {
            // masukkan menu baru — id_penjual mencatat siapa yang membuat menu ini
            $s = $conn->prepare("INSERT INTO tb_menu (nama_menu, harga, stok, kategori, deskripsi, foto, status, id_toko, id_penjual)
                                  VALUES (?,?,?,?,?,?,'aktif',?,?)");
            $s->bind_param("siisssii", $namamenu, $harga, $stok, $kategori, $deskripsi, $fotofile, $idtoko, $idpenjual);
            $s->execute(); $s->close();
            setFlash("Menu '$namamenu' berhasil ditambahkan!", 'sukses');
        } else {
            // ambil id_menu dari form dan pastikan angka
            $idmenu = (int)($_POST['id_menu'] ?? 0);
            // verifikasi bahwa menu ini memang milik toko yang sedang login (keamanan)
            $cek = $conn->prepare("SELECT id_menu FROM tb_menu WHERE id_menu=? AND id_toko=? AND deleted=0");
            $cek->bind_param("ii", $idmenu, $idtoko); $cek->execute();
            if (!$cek->get_result()->num_rows) throw new Exception("Menu tidak ditemukan.");
            $cek->close();

            // update data menu — updated=NOW() mencatat waktu perubahan terakhir
            $s = $conn->prepare("UPDATE tb_menu SET nama_menu=?, harga=?, stok=?, kategori=?, deskripsi=?, foto=?, updated=NOW() WHERE id_menu=? AND id_toko=?");
            $s->bind_param("siisssii", $namamenu, $harga, $stok, $kategori, $deskripsi, $fotofile, $idmenu, $idtoko);
            $s->execute(); $s->close();
            setFlash("Menu '$namamenu' berhasil diperbarui!", 'sukses');
        }
    }

    // aksi toggle: ubah status menu antara "aktif" dan "nonaktif"
    elseif ($aksi === 'toggle') {
        $idmenu = (int)($_GET['id'] ?? 0);
        // ambil status saat ini dan pastikan menu milik toko ini
        $cek = $conn->prepare("SELECT status FROM tb_menu WHERE id_menu=? AND id_toko=? AND deleted=0");
        $cek->bind_param("ii", $idmenu, $idtoko); $cek->execute();
        $row = $cek->get_result()->fetch_assoc(); $cek->close();
        if (!$row) throw new Exception("Menu tidak ditemukan.");

        // balik status: jika aktif → nonaktif, jika nonaktif → aktif
        $statusbaru = ($row['status'] === 'aktif') ? 'nonaktif' : 'aktif';
        $s = $conn->prepare("UPDATE tb_menu SET status=?, updated=NOW() WHERE id_menu=? AND id_toko=?");
        $s->bind_param("sii", $statusbaru, $idmenu, $idtoko);
        $s->execute(); $s->close();
        setFlash("Status menu diubah menjadi '$statusbaru'.", 'sukses');
    }

    // aksi hapus: gunakan soft delete (set deleted=1) agar data tidak benar-benar hilang dari database
    elseif ($aksi === 'hapus') {
        $idmenu = (int)($_GET['id'] ?? 0);
        // verifikasi kepemilikan menu sebelum menghapus
        $cek = $conn->prepare("SELECT id_menu FROM tb_menu WHERE id_menu=? AND id_toko=? AND deleted=0");
        $cek->bind_param("ii", $idmenu, $idtoko); $cek->execute();
        if (!$cek->get_result()->num_rows) throw new Exception("Menu tidak ditemukan.");
        $cek->close();

        // tandai deleted=1 dan catat waktu penghapusan, serta nonaktifkan menu
        $s = $conn->prepare("UPDATE tb_menu SET deleted=1, deleted_at=NOW(), status='nonaktif' WHERE id_menu=? AND id_toko=?");
        $s->bind_param("ii", $idmenu, $idtoko);
        $s->execute(); $s->close();
        setFlash("Menu berhasil dihapus.", 'sukses');
    }

    else {
        throw new Exception("Aksi tidak dikenali.");
    }

} catch (Exception $e) {
    // tangkap semua error dan simpan sebagai flash pesan gagal
    setFlash($e->getMessage(), 'gagal');
}

// selalu redirect kembali ke halaman manajemen menu setelah proses selesai
kembali($filter);
?>
