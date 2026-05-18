<?php
/* ============================================================
   PROSES KERANJANG
   Handler aksi keranjang: tambah, hapus, update qty.
   Dipanggil via form POST, lalu redirect kembali ke halaman asal.
   Tidak mengembalikan JSON karena sudah pakai redirect.
   ============================================================ */
include '../../3. komponen/guardpembeli.php';
include '../../1. koneksi/koneksi.php';

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: keranjang.php"); exit;
}

$aksi    = $_POST['aksi']    ?? '';
$idmenu  = (int)($_POST['id_menu']  ?? 0);
$jumlah  = max(1, (int)($_POST['qty']     ?? 1));
$catatan = trim($_POST['catatan'] ?? '');
$idtoko  = (int)($_POST['id_toko']  ?? 0);
$kembali = $_POST['kembali'] ?? 'keranjang'; // 'index' atau 'keranjang' atau 'detail'

// Inisialisasi keranjang
if (!isset($_SESSION['keranjang'])) $_SESSION['keranjang'] = [];

// ============================================================
// FUNGSI BANTU
// ============================================================
function hitungTotalQty(): int {
    $total = 0;
    foreach ($_SESSION['keranjang'] as $idt => $items) {
        foreach ($items as $k => $v) {
            if ($k === '_info') continue;
            $total += $v['qty'];
        }
    }
    return $total;
}

function bersihkanTokoKosong(int $idtoko): void {
    if (!isset($_SESSION['keranjang'][$idtoko])) return;
    $ada = false;
    foreach ($_SESSION['keranjang'][$idtoko] as $k => $v) {
        if ($k !== '_info') { $ada = true; break; }
    }
    if (!$ada) unset($_SESSION['keranjang'][$idtoko]);
}

function setFlash(string $pesan, string $jenis = 'sukses'): void {
    $_SESSION['flash'] = ['pesan' => $pesan, 'jenis' => $jenis];
}

function kembalikan(string $kembali): void {
    switch ($kembali) {
        case 'index':   header("Location: ../index/index.php");  break;
        case 'detail':  header("Location: " . ($_SESSION['detail_url'] ?? '../index/index.php')); break;
        default:        header("Location: keranjang.php");        break;
    }
    exit;
}

// ============================================================
// AKSI
// ============================================================

switch ($aksi) {

    // TAMBAH ITEM
    case 'tambah':
        if (!$idmenu) { setFlash('ID menu tidak valid', 'gagal'); kembalikan($kembali); }

        // Ambil data menu dari DB
        $q = $conn->prepare("SELECT m.*,t.nama_toko FROM tb_menu m LEFT JOIN tb_toko t ON m.id_toko=t.id_toko WHERE m.id_menu=? AND m.deleted=0 AND m.status='aktif'");
        $q->bind_param("i", $idmenu);
        $q->execute();
        $menu = $q->get_result()->fetch_assoc();
        $q->close();

        if (!$menu) { setFlash('Menu tidak ditemukan', 'gagal'); kembalikan($kembali); }
        if ($menu['stok'] <= 0) { setFlash('Stok menu ini sudah habis', 'gagal'); kembalikan($kembali); }

        $idtokomenu = (int)$menu['id_toko'];
        $namatoko   = $menu['nama_toko'] ?: 'Kantin';

        // Buat slot toko jika belum ada
        if (!isset($_SESSION['keranjang'][$idtokomenu])) {
            $_SESSION['keranjang'][$idtokomenu] = ['_info' => ['nama_toko' => $namatoko, 'id_toko' => $idtokomenu]];
        }

        // Hitung qty yang sudah ada di keranjang
        $qtysudahada = $_SESSION['keranjang'][$idtokomenu][$idmenu]['qty'] ?? 0;
        $qtybaru     = $qtysudahada + $jumlah;

        // Batasi tidak melebihi stok
        if ($qtybaru > $menu['stok']) {
            $qtybaru = $menu['stok'];
            setFlash($menu['nama_menu'] . ' ditambahkan (maksimum stok: ' . $menu['stok'] . ')', 'tunggu');
        } else {
            setFlash($menu['nama_menu'] . ' ditambahkan ke keranjang', 'sukses');
        }

        if (isset($_SESSION['keranjang'][$idtokomenu][$idmenu])) {
            $_SESSION['keranjang'][$idtokomenu][$idmenu]['qty'] = $qtybaru;
            if ($catatan) $_SESSION['keranjang'][$idtokomenu][$idmenu]['catatan'] = $catatan;
        } else {
            $_SESSION['keranjang'][$idtokomenu][$idmenu] = [
                'id_menu'   => $idmenu,
                'nama_menu' => $menu['nama_menu'],
                'harga'     => (int)$menu['harga'],
                'foto'      => $menu['foto'],
                'qty'       => $qtybaru,
                'catatan'   => $catatan,
                'id_toko'   => $idtokomenu,
                'nama_toko' => $namatoko,
            ];
        }
        kembalikan($kembali);
        break;

    // HAPUS ITEM
    case 'hapus':
        if ($idtoko && $idmenu && isset($_SESSION['keranjang'][$idtoko][$idmenu])) {
            $nama = $_SESSION['keranjang'][$idtoko][$idmenu]['nama_menu'];
            unset($_SESSION['keranjang'][$idtoko][$idmenu]);
            bersihkanTokoKosong($idtoko);
            setFlash($nama . ' dihapus dari keranjang', 'info');
        }
        header("Location: keranjang.php"); exit;

    // UPDATE QTY (+ atau -)
    case 'kurang':
    case 'tambah_qty':
        if (!$idtoko || !$idmenu || !isset($_SESSION['keranjang'][$idtoko][$idmenu])) {
            header("Location: keranjang.php"); exit;
        }

        $qtyskrg = (int)$_SESSION['keranjang'][$idtoko][$idmenu]['qty'];
        $qtybaru = ($aksi === 'tambah_qty') ? $qtyskrg + 1 : $qtyskrg - 1;

        if ($qtybaru <= 0) {
            // Hapus item
            $nama = $_SESSION['keranjang'][$idtoko][$idmenu]['nama_menu'];
            unset($_SESSION['keranjang'][$idtoko][$idmenu]);
            bersihkanTokoKosong($idtoko);
            setFlash($nama . ' dihapus dari keranjang', 'info');
        } else {
            // Validasi stok jika nambah
            if ($aksi === 'tambah_qty') {
                $cekstok = $conn->prepare("SELECT stok FROM tb_menu WHERE id_menu=? AND deleted=0");
                $cekstok->bind_param("i", $idmenu);
                $cekstok->execute();
                $stokdb = (int)($cekstok->get_result()->fetch_row()[0] ?? 0);
                $cekstok->close();

                if ($qtybaru > $stokdb) {
                    setFlash('Jumlah tidak bisa melebihi stok yang tersedia (' . $stokdb . ')', 'gagal');
                    header("Location: keranjang.php"); exit;
                }
            }
            $_SESSION['keranjang'][$idtoko][$idmenu]['qty'] = $qtybaru;
        }
        header("Location: keranjang.php"); exit;

    default:
        header("Location: keranjang.php"); exit;
}
?>
