<?php
/* ============================================================
   PROSES KELOLA MENU
   Handler: tambah, edit, hapus, toggle status menu.
   ============================================================ */
include '../../1. koneksi/koneksi.php';
include '../../3. komponen/guardpenjual.php';

$idtoko = (int)$_SESSION['id_toko'];
$aksi   = $_POST['aksi'] ?? $_GET['aksi'] ?? '';
$filter = $_POST['filter'] ?? $_GET['filter'] ?? 'Semua';

function setFlash(string $pesan, string $jenis): void {
    $_SESSION['flash'] = ['pesan' => $pesan, 'jenis' => $jenis];
}
function kembali(string $filter, int $editid = 0): void {
    $url = "manajemenmenu.php?filter=" . urlencode($filter);
    if ($editid) $url .= "&edit=$editid";
    header("Location: $url"); exit;
}

try {

    // ===== TAMBAH / EDIT =====
    if ($aksi === 'tambah' || $aksi === 'edit') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            kembali($filter);
        }

        // Ambil & validasi input
        $namamenu  = trim($_POST['nama_menu']  ?? '');
        $harga     = (int)($_POST['harga']     ?? 0);
        $stok      = (int)($_POST['stok']      ?? 0);
        $kategori  = trim($_POST['kategori']   ?? '');
        $deskripsi = trim($_POST['deskripsi']  ?? '');

        $kategorilist = ['Makanan Berat','Makanan Ringan','Makanan Sehat','Minuman Ringan','Minuman Sehat'];

        // Validasi
        if (empty($namamenu))                          throw new Exception("Nama menu wajib diisi.");
        if (strlen($namamenu) > 50)                    throw new Exception("Nama menu maksimal 50 karakter.");
        if (!in_array($kategori, $kategorilist))       throw new Exception("Kategori tidak valid.");
        if ($harga < 0)                                throw new Exception("Harga tidak boleh negatif.");
        if ($harga > 999999)                           throw new Exception("Harga terlalu besar (maks. Rp 999.999).");
        if ($stok < 0)                                 throw new Exception("Stok tidak boleh negatif.");
        if ($stok > 9999)                              throw new Exception("Stok terlalu besar (maks. 9.999).");

        // Proses foto
        $fotofile = '';
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            // Validasi tipe & ukuran
            $allowedmime = ['image/jpeg','image/png','image/webp'];
            if (!in_array($_FILES['foto']['type'], $allowedmime)) {
                throw new Exception("Format gambar hanya JPG, PNG, atau WEBP.");
            }
            if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Ukuran gambar maksimal 2MB.");
            }
            $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $fotofile = uniqid() . '.' . strtolower($ext);
            $target   = '../../2. aset/katalog/' . $fotofile;
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
                throw new Exception("Gagal mengupload gambar. Pastikan folder katalog bisa ditulis.");
            }
        } elseif ($aksi === 'edit') {
            // Edit tanpa foto baru → pakai foto lama
            $fotofile = $_POST['foto_lama'] ?? '';
        } else {
            // Tambah wajib ada foto
            throw new Exception("Foto menu wajib diunggah.");
        }

        if ($aksi === 'tambah') {
            $s = $conn->prepare("INSERT INTO tb_menu (nama_menu, harga, stok, kategori, deskripsi, foto, status, id_toko)
                                  VALUES (?,?,?,?,?,?,'aktif',?)");
            $s->bind_param("siisssi", $namamenu, $harga, $stok, $kategori, $deskripsi, $fotofile, $idtoko);
            $s->execute(); $s->close();
            setFlash("Menu '$namamenu' berhasil ditambahkan!", 'sukses');
        } else {
            $idmenu = (int)($_POST['id_menu'] ?? 0);
            // Pastikan menu milik toko ini
            $cek = $conn->prepare("SELECT id_menu FROM tb_menu WHERE id_menu=? AND id_toko=? AND deleted=0");
            $cek->bind_param("ii", $idmenu, $idtoko); $cek->execute();
            if (!$cek->get_result()->num_rows) throw new Exception("Menu tidak ditemukan.");
            $cek->close();

            $s = $conn->prepare("UPDATE tb_menu SET nama_menu=?, harga=?, stok=?, kategori=?, deskripsi=?, foto=?, updated=NOW() WHERE id_menu=? AND id_toko=?");
            $s->bind_param("siisssii", $namamenu, $harga, $stok, $kategori, $deskripsi, $fotofile, $idmenu, $idtoko);
            $s->execute(); $s->close();
            setFlash("Menu '$namamenu' berhasil diperbarui!", 'sukses');
        }
    }

    // ===== TOGGLE STATUS =====
    elseif ($aksi === 'toggle') {
        $idmenu = (int)($_GET['id'] ?? 0);
        // Pastikan menu milik toko ini
        $cek = $conn->prepare("SELECT status FROM tb_menu WHERE id_menu=? AND id_toko=? AND deleted=0");
        $cek->bind_param("ii", $idmenu, $idtoko); $cek->execute();
        $row = $cek->get_result()->fetch_assoc(); $cek->close();
        if (!$row) throw new Exception("Menu tidak ditemukan.");

        $statusbaru = ($row['status'] === 'aktif') ? 'nonaktif' : 'aktif';
        $s = $conn->prepare("UPDATE tb_menu SET status=?, updated=NOW() WHERE id_menu=? AND id_toko=?");
        $s->bind_param("sii", $statusbaru, $idmenu, $idtoko);
        $s->execute(); $s->close();
        setFlash("Status menu diubah menjadi '$statusbaru'.", 'sukses');
    }

    // ===== HAPUS =====
    elseif ($aksi === 'hapus') {
        $idmenu = (int)($_GET['id'] ?? 0);
        $cek = $conn->prepare("SELECT id_menu FROM tb_menu WHERE id_menu=? AND id_toko=? AND deleted=0");
        $cek->bind_param("ii", $idmenu, $idtoko); $cek->execute();
        if (!$cek->get_result()->num_rows) throw new Exception("Menu tidak ditemukan.");
        $cek->close();

        $s = $conn->prepare("UPDATE tb_menu SET deleted=1, deleted_at=NOW(), status='nonaktif' WHERE id_menu=? AND id_toko=?");
        $s->bind_param("ii", $idmenu, $idtoko);
        $s->execute(); $s->close();
        setFlash("Menu berhasil dihapus.", 'sukses');
    }

    else {
        throw new Exception("Aksi tidak dikenali.");
    }

} catch (Exception $e) {
    setFlash($e->getMessage(), 'gagal');
}

kembali($filter);
?>
