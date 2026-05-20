<?php
/* file proses keranjang belanja
   menangani semua aksi keranjang: tambah item, hapus item, tambah qty, kurangi qty
   dipanggil via form POST dari berbagai halaman, lalu redirect ke halaman asal
   tidak ada tampilan HTML — hanya logika proses dan redirect */

// guard memastikan hanya pembeli yang login yang bisa mengakses
include '../../3. komponen/guardpembeli.php';
// koneksi database untuk cek data menu dan stok
include '../../1. koneksi/koneksi.php';

// tolak akses selain POST — misalnya jika seseorang buka URL ini langsung di browser
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: keranjang.php"); exit;
}

// ambil parameter dari form POST
// ?? '' artinya jika tidak ada, gunakan string kosong sebagai nilai default
$aksi    = $_POST['aksi']    ?? '';
// (int) memastikan id hanya berupa angka, mencegah SQL injection
$idmenu  = (int)($_POST['id_menu']  ?? 0);
// max(1,...) memastikan qty tidak bisa kurang dari 1
$jumlah  = max(1, (int)($_POST['qty']     ?? 1));
$idtoko  = (int)($_POST['id_toko']  ?? 0);
// kembali menentukan ke mana redirect setelah proses selesai
$kembali = $_POST['kembali'] ?? 'keranjang'; // nilai: 'index', 'keranjang', atau 'detail'

// buat array keranjang di session jika belum ada
if (!isset($_SESSION['keranjang'])) $_SESSION['keranjang'] = [];

/* fungsi-fungsi bantu untuk menghindari pengulangan kode */

// hitung total qty semua item di keranjang (digunakan untuk badge navbar)
function hitungTotalQty(): int {
    $total = 0;
    foreach ($_SESSION['keranjang'] as $idt => $items) {
        foreach ($items as $k => $v) {
            if ($k === '_info') continue; // lewati data info toko
            $total += $v['qty'];
        }
    }
    return $total;
}

// hapus toko dari keranjang jika sudah tidak ada item di dalamnya
// dipanggil setelah menghapus item atau mengurangi qty sampai 0
function bersihkanTokoKosong(int $idtoko): void {
    if (!isset($_SESSION['keranjang'][$idtoko])) return;
    $ada = false;
    foreach ($_SESSION['keranjang'][$idtoko] as $k => $v) {
        if ($k !== '_info') { $ada = true; break; }
    }
    // jika tidak ada item selain _info, hapus seluruh slot toko
    if (!$ada) unset($_SESSION['keranjang'][$idtoko]);
}

// simpan pesan flash ke session agar bisa ditampilkan setelah redirect
// pesan flash otomatis terhapus setelah dibaca (diatur di navbar)
function setFlash(string $pesan, string $jenis = 'sukses'): void {
    $_SESSION['flash'] = ['pesan' => $pesan, 'jenis' => $jenis];
}

// redirect ke halaman asal berdasarkan nilai $kembali
// exit wajib ada setelah header redirect agar kode di bawahnya tidak dieksekusi
function kembalikan(string $kembali): void {
    switch ($kembali) {
        case 'index':   header("Location: ../index/index.php");  break;
        // jika kembali=detail, ambil URL detail dari session yang disimpan sebelumnya
        case 'detail':  header("Location: " . ($_SESSION['detail_url'] ?? '../index/index.php')); break;
        default:        header("Location: keranjang.php");        break;
    }
    exit;
}

/* blok switch utama — pilih aksi berdasarkan nilai POST['aksi'] */

switch ($aksi) {

    // aksi tambah: menambahkan item menu baru ke keranjang
    case 'tambah':
        // validasi id_menu tidak boleh 0
        if (!$idmenu) { setFlash('ID menu tidak valid', 'gagal'); kembalikan($kembali); }

        // ambil data menu dari database termasuk nama toko
        // prepared statement mencegah SQL injection
        $q = $conn->prepare("SELECT m.*,t.nama_toko FROM tb_menu m LEFT JOIN tb_toko t ON m.id_toko=t.id_toko WHERE m.id_menu=? AND m.deleted=0 AND m.status='aktif'");
        $q->bind_param("i", $idmenu);
        $q->execute();
        $menu = $q->get_result()->fetch_assoc();
        $q->close();

        // menu tidak ditemukan atau sudah dihapus/nonaktif
        if (!$menu) { setFlash('Menu tidak ditemukan', 'gagal'); kembalikan($kembali); }
        // stok habis, tidak bisa ditambah
        if ($menu['stok'] <= 0) { setFlash('Stok menu ini sudah habis', 'gagal'); kembalikan($kembali); }

        // cek status toko — tidak boleh pesan dari toko yang sedang tutup
        $idtokocek = (int)$menu['id_toko'];
        $cektoko   = $conn->prepare("SELECT status_toko FROM tb_toko WHERE id_toko=? AND deleted=0");
        $cektoko->bind_param("i", $idtokocek);
        $cektoko->execute();
        $statustoko = $cektoko->get_result()->fetch_row()[0] ?? 'tutup';
        $cektoko->close();
        if ($statustoko !== 'buka') {
            setFlash('Kantin ' . $menu['nama_toko'] . ' sedang tutup, tidak bisa memesan', 'gagal');
            kembalikan($kembali);
        }

        $idtokomenu = (int)$menu['id_toko'];
        $namatoko   = $menu['nama_toko'] ?: 'Kantin';

        // buat slot toko di keranjang jika belum ada
        // _info menyimpan metadata toko (nama dan id) agar tidak perlu query ulang saat tampil
        if (!isset($_SESSION['keranjang'][$idtokomenu])) {
            $_SESSION['keranjang'][$idtokomenu] = ['_info' => ['nama_toko' => $namatoko, 'id_toko' => $idtokomenu]];
        }

        // cek berapa qty item ini yang sudah ada di keranjang (0 jika belum ada)
        $qtysudahada = $_SESSION['keranjang'][$idtokomenu][$idmenu]['qty'] ?? 0;
        $qtybaru     = $qtysudahada + $jumlah;

        // batasi qty agar tidak melebihi stok yang tersedia
        if ($qtybaru > $menu['stok']) {
            $qtybaru = $menu['stok'];
            setFlash($menu['nama_menu'] . ' ditambahkan (maksimum stok: ' . $menu['stok'] . ')', 'tunggu');
        } else {
            setFlash($menu['nama_menu'] . ' ditambahkan ke keranjang', 'sukses');
        }

        // update qty jika item sudah ada, atau tambah entri baru jika belum
        if (isset($_SESSION['keranjang'][$idtokomenu][$idmenu])) {
            $_SESSION['keranjang'][$idtokomenu][$idmenu]['qty'] = $qtybaru;
        } else {
            // simpan semua data item yang perlu ditampilkan di keranjang
            $_SESSION['keranjang'][$idtokomenu][$idmenu] = [
                'id_menu'   => $idmenu,
                'nama_menu' => $menu['nama_menu'],
                'harga'     => (int)$menu['harga'],
                'foto'      => $menu['foto'],
                'qty'       => $qtybaru,
                'id_toko'   => $idtokomenu,
                'nama_toko' => $namatoko,
            ];
        }
        kembalikan($kembali);
        break;

    // aksi hapus: menghapus item tertentu dari keranjang
    case 'hapus':
        if ($idtoko && $idmenu && isset($_SESSION['keranjang'][$idtoko][$idmenu])) {
            $nama = $_SESSION['keranjang'][$idtoko][$idmenu]['nama_menu'];
            // hapus item dari array session
            unset($_SESSION['keranjang'][$idtoko][$idmenu]);
            // hapus slot toko jika sudah kosong setelah penghapusan
            bersihkanTokoKosong($idtoko);
            setFlash($nama . ' dihapus dari keranjang', 'info');
        }
        // setelah hapus selalu kembali ke halaman keranjang
        header("Location: keranjang.php"); exit;

    // aksi kurangi qty (tombol -) atau tambah qty (tombol +)
    case 'kurang':
    case 'tambah_qty':
        // pastikan item ada di session sebelum diubah
        if (!$idtoko || !$idmenu || !isset($_SESSION['keranjang'][$idtoko][$idmenu])) {
            header("Location: keranjang.php"); exit;
        }

        $qtyskrg = (int)$_SESSION['keranjang'][$idtoko][$idmenu]['qty'];
        // jika tambah: +1, jika kurang: -1
        $qtybaru = ($aksi === 'tambah_qty') ? $qtyskrg + 1 : $qtyskrg - 1;

        if ($qtybaru <= 0) {
            // jika qty jadi 0 atau kurang, otomatis hapus item dari keranjang
            $nama = $_SESSION['keranjang'][$idtoko][$idmenu]['nama_menu'];
            unset($_SESSION['keranjang'][$idtoko][$idmenu]);
            bersihkanTokoKosong($idtoko);
            setFlash($nama . ' dihapus dari keranjang', 'info');
        } else {
            // jika menambah qty, cek dulu apakah stok di database mencukupi
            if ($aksi === 'tambah_qty') {
                $cekstok = $conn->prepare("SELECT stok FROM tb_menu WHERE id_menu=? AND deleted=0");
                $cekstok->bind_param("i", $idmenu);
                $cekstok->execute();
                $stokdb = (int)($cekstok->get_result()->fetch_row()[0] ?? 0);
                $cekstok->close();

                // tolak jika qty baru akan melebihi stok
                if ($qtybaru > $stokdb) {
                    setFlash('Jumlah tidak bisa melebihi stok yang tersedia (' . $stokdb . ')', 'gagal');
                    header("Location: keranjang.php"); exit;
                }
            }
            // simpan qty baru ke session
            $_SESSION['keranjang'][$idtoko][$idmenu]['qty'] = $qtybaru;
        }
        header("Location: keranjang.php"); exit;

    // aksi tidak dikenali — arahkan ke keranjang
    default:
        header("Location: keranjang.php"); exit;
}
?>
