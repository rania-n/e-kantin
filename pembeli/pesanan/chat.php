<?php
/* halaman chat pembeli ↔ penjual untuk satu pesanan.
   chat aktif selama status pesanan: Menunggu / Diproses / Siap Diambil.
   saat Selesai atau Dibatalkan, riwayat tetap bisa dibaca tapi input dikunci.

   tanpa JS sama sekali:
   - auto-scroll pakai anchor <a id="latest"> + URL berakhir #latest
   - kirim pesan pakai PRG (POST → redirect ke GET) supaya refresh tidak duplikat
   - auto-refresh pakai <meta http-equiv="refresh">

   layout responsif & simetris dengan chat penjual:
   - header  : title kiri + tombol kembali kanan (desktop), kolom (mobile)
   - input   : textarea & tombol kirim sama tinggi 44px
   - offset  : input bar di atas bottom-nav mobile (var --headertinggi) atau
               kanan sidebar desktop (var --sidebarlebar) */

// guard memastikan hanya pembeli yang login bisa membuka halaman ini
include '../../3. komponen/guardpembeli.php';
// koneksi.php menyediakan variabel $conn untuk query ke database
include '../../1. koneksi/koneksi.php';

// id_order bisa datang lewat GET (saat masuk halaman) atau POST (saat kirim pesan).
// (int) cast ke integer — kalau bukan angka jadi 0, aman dari sql injection.
$idpesanan  = (int)($_GET['id_order'] ?? $_POST['id_order'] ?? 0);
// id pengguna pembeli yang sedang login, diambil dari session
$idpengguna = (int)$_SESSION['id_user'];

// jika id_order tidak valid, balikkan ke daftar pesanan
if (!$idpesanan) { header("Location: pesanan.php"); exit; }

// query data pesanan + filter id_user supaya pembeli A tidak bisa mengintip chat pembeli B
// kalau menebak id_order milik orang lain. nama_toko_snapshot dipakai supaya tetap tampil
// walaupun toko sudah dihapus penjual.
$q = $conn->prepare("SELECT o.id_order, o.id_user, o.id_penjual, o.status_order,
                            o.nama_toko_snapshot AS nama_toko, o.tanggal_order, o.total_harga,
                            o.catatan, o.metode_pembayaran
                       FROM tb_order o
                       WHERE o.id_order=? AND o.id_user=? AND o.deleted=0");
// "ii" = dua parameter integer (id_order, id_user)
$q->bind_param("ii", $idpesanan, $idpengguna);
$q->execute();
// fetch_assoc mengambil 1 baris hasil sebagai array asosiatif (key = nama kolom)
$pesanan = $q->get_result()->fetch_assoc();
$q->close();

// kalau pesanan tidak ditemukan (atau bukan milik pembeli ini), balikkan ke daftar
if (!$pesanan) { header("Location: pesanan.php"); exit; }

// status aktif = pesanan masih berjalan, chat boleh kirim pesan baru.
// kalau Selesai/Dibatalkan, input dikunci dan riwayat tetap bisa dibaca.
$statusAktif = in_array($pesanan['status_order'], ['Menunggu','Diproses','Siap Diambil'], true);

// ambil daftar item pesanan untuk kartu detail.
// COALESCE = pakai nama_menu_snapshot kalau ada, fallback ke nama_menu dari tb_menu.
// LEFT JOIN supaya item tetap muncul walaupun menu di tb_menu sudah dihapus penjual.
$qd = $conn->prepare("SELECT d.jumlah, d.harga_satuan, d.subtotal,
                             COALESCE(d.nama_menu_snapshot, m.nama_menu) AS nama_menu
                      FROM tb_detail_order d
                      LEFT JOIN tb_menu m ON d.id_menu=m.id_menu
                      WHERE d.id_order=? AND d.deleted=0");
$qd->bind_param("i", $idpesanan);
$qd->execute();
// fetch_all mengambil semua baris sekaligus ke array php
$itemPesanan = $qd->get_result()->fetch_all(MYSQLI_ASSOC);
$qd->close();

// peta status → kelas css untuk badge berwarna di kartu detail.
// dibuat sama dengan kelas di pesanan.php supaya tampilan konsisten.
// match() = versi modern dari switch yang langsung mengembalikan nilai
$kelasStatus = match($pesanan['status_order']) {
    'Menunggu'     => 'menunggu',
    'Diproses'     => 'diproses',
    'Siap Diambil' => 'siap',
    'Selesai'      => 'selesai',
    default        => 'dibatalkan', // fallback untuk status yang tidak dikenal
};

// langkah POST: pembeli klik kirim → simpan pesan baru → redirect ke GET (pattern PRG)
// alasan PRG: kalau pengguna refresh browser setelah kirim, tidak akan kirim ulang pesan
// karena URL terakhir sudah jadi GET, bukan POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // trim hapus spasi awal/akhir; mb_strlen untuk hitung karakter unicode dengan benar
    $pesan = trim($_POST['pesan'] ?? '');
    // batas 500 karakter — cukup untuk satu pesan chat dan mencegah penyalahgunaan
    if ($statusAktif && $pesan !== '' && mb_strlen($pesan) <= 500) {
        // simpan pesan baru ke tabel tb_chat dengan prepared statement (aman dari sql injection)
        $st = $conn->prepare("INSERT INTO tb_chat (id_order, id_pengirim, pesan) VALUES (?, ?, ?)");
        // "iis" = integer, integer, string
        $st->bind_param("iis", $idpesanan, $idpengguna, $pesan);
        $st->execute();
        $st->close();
    }
    // redirect dengan fragment #latest supaya browser auto-scroll ke pesan terbaru
    header("Location: chat.php?id_order=" . $idpesanan . "#latest");
    exit;
}

// ambil semua pesan dari tabel chat, urut paling lama dulu supaya pesan terbaru di bawah
$qm = $conn->prepare("SELECT id_chat, id_pengirim, pesan, created
                       FROM tb_chat
                       WHERE id_order=?
                       ORDER BY created ASC, id_chat ASC");
$qm->bind_param("i", $idpesanan);
$qm->execute();
$pesanList = $qm->get_result()->fetch_all(MYSQLI_ASSOC);
$qm->close();

// tandai pesan dari penjual sebagai "sudah dibaca" supaya badge notifikasi
// di daftar pesanan tidak terus menumpuk setelah pembeli membuka halaman chat.
$idpenjual = (int)($pesanan['id_penjual'] ?? 0);
if ($idpenjual > 0) {
    $up = $conn->prepare("UPDATE tb_chat SET dibaca=1
                           WHERE id_order=? AND id_pengirim=? AND dibaca=0");
    $up->bind_param("ii", $idpesanan, $idpenjual);
    $up->execute();
    $up->close();
}

// format nomor pesanan tampilan: EK-000123 (6 digit padding nol di depan)
$nomerpesanan = 'EK-' . str_pad($idpesanan, 6, '0', STR_PAD_LEFT);
// nama toko untuk ditampilkan di header (fallback "Kantin" kalau snapshot kosong)
$namatoko     = $pesanan['nama_toko'] ?? 'Kantin';
// $pathbase dipakai oleh navbar untuk menentukan path relatif antar folder
$pathbase     = '..';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat <?= $nomerpesanan ?> - jajankita</title>
<link rel="stylesheet" href="../../3. komponen/pembeli.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<?php if ($statusAktif): ?>
<meta http-equiv="refresh" content="15;url=chat.php?id_order=<?= $idpesanan ?>#latest">
<?php endif; ?>

<style>
/* ========= CHAT PEMBELI — selaras dengan chat penjual ========= */

/* kontainer halaman: full-width nempel kanan-kiri (di mobile fill viewport,
   di desktop fill area setelah sidebar). padding bawah cukup untuk input
   fixed + bottom nav. */
.chat-halaman {
  width: 100%;
  padding: 16px 14px 0;
  padding-bottom: calc(60px + var(--headertinggi, 56px) + 24px);
  box-sizing: border-box;
}

/* ===== HEADER: title kiri + tombol kembali kanan ===== */
.chat-kepala {
  display: flex; flex-direction: row;
  align-items: center; justify-content: space-between;
  gap: 12px; margin-bottom: 14px;
}
.chat-kepala-info {
  display: flex; align-items: center; gap: 10px;
  min-width: 0; /* supaya teks bisa truncate, tidak overflow */
  flex: 1;
}
.chat-kepala-info .ikon {
  flex-shrink: 0;
  width: 40px; height: 40px; border-radius: 50%;
  background: var(--utama); color: white;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
}
.chat-kepala-info .teks { min-width: 0; }
.chat-kepala-info h1 {
  margin: 0; font-size: 17px; font-weight: 800;
  color: var(--utama); line-height: 1.2;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.chat-kepala-info p {
  margin: 2px 0 0; font-size: 12px;
  color: var(--tekssamar); line-height: 1.3;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* tombol kembali — tinggi 40px (touch-friendly), sama spec dengan .tombolringan */
.chat-balik {
  flex-shrink: 0;
  display: inline-flex; align-items: center; justify-content: center;
  gap: 6px;
  height: 40px; padding: 0 14px;
  background: white; color: var(--utama);
  border: 1px solid var(--garis); border-radius: 10px;
  font-size: 13px; font-weight: 600;
  text-decoration: none;
}
.chat-balik:hover { border-color: var(--utama); background: var(--putihbg); }

/* ===== BANNER INFO STATUS ===== */
.chat-info {
  background: var(--putihbg); border: 1px solid var(--garis);
  border-radius: 10px; padding: 10px 12px;
  font-size: 12px; color: var(--utama); line-height: 1.5;
  margin-bottom: 14px;
}

/* ===== KARTU DETAIL PESANAN ===== */
.chat-kartu {
  background: white;
  border: 1px solid var(--garis);
  border-radius: 12px;
  padding: 12px 14px;
  margin-bottom: 14px;
}
.chat-kartu-atas {
  display: flex; align-items: center; justify-content: space-between;
  gap: 8px; flex-wrap: wrap;
  padding-bottom: 10px;
  border-bottom: 1px dashed var(--garis);
}
.chat-kartu-atas .nomor {
  font-family: monospace; font-weight: 800; color: var(--utama);
  font-size: 14px;
}
.chat-kartu-atas .tanggal {
  font-size: 11px; color: var(--tekssamar);
}
.chat-kartu-badge {
  display: inline-block;
  padding: 3px 10px; border-radius: 99px;
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .3px;
}
.chat-kartu-badge.menunggu    { background: var(--tunggubg); color: #B45309; }
.chat-kartu-badge.diproses    { background: var(--infobg);   color: var(--info); }
.chat-kartu-badge.siap        { background: var(--suksesbg); color: var(--sukses); }
.chat-kartu-badge.selesai     { background: var(--suksesbg); color: var(--sukses); }
.chat-kartu-badge.dibatalkan  { background: var(--gagalbg);  color: var(--gagal); }

.chat-kartu-item {
  display: flex; justify-content: space-between;
  padding: 6px 0;
  font-size: 13px; color: var(--utama);
  border-bottom: 1px dotted var(--garis);
}
.chat-kartu-item:last-of-type { border-bottom: none; }
.chat-kartu-item .qty {
  color: var(--tekssamar); font-weight: 700; margin-right: 4px;
}
.chat-kartu-item .sub {
  color: var(--utama); font-weight: 700;
}

.chat-kartu-catatan {
  background: var(--putihbg);
  border-radius: 8px;
  padding: 8px 10px;
  margin-top: 10px;
  font-size: 12px; color: var(--utama); line-height: 1.4;
}
.chat-kartu-catatan strong { display: block; margin-bottom: 2px; font-size: 11px; }

.chat-kartu-total {
  display: flex; justify-content: space-between; align-items: center;
  margin-top: 10px; padding-top: 10px;
  border-top: 1px dashed var(--garis);
  font-weight: 800; color: var(--utama);
}
.chat-kartu-total .nilai { font-size: 16px; }
.chat-kartu-total .label { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; }

/* ===== DAFTAR PESAN ===== */
.chat-daftar {
  display: flex; flex-direction: column;
  gap: 8px; padding-bottom: 4px;
}
.chat-baris { display: flex; }
.chat-baris.mine   { justify-content: flex-end; }
.chat-baris.theirs { justify-content: flex-start; }
.chat-blok {
  max-width: 82%;
  display: flex; flex-direction: column;
}
.chat-baris.mine   .chat-blok { align-items: flex-end; }
.chat-baris.theirs .chat-blok { align-items: flex-start; }

.chat-bubble {
  padding: 9px 13px; border-radius: 16px;
  font-size: 14px; line-height: 1.45;
  word-wrap: break-word; overflow-wrap: anywhere;
}
.chat-bubble.mine {
  background: var(--utama); color: white;
  border-bottom-right-radius: 4px;
}
.chat-bubble.theirs {
  background: var(--putihbg); color: var(--utama);
  border-bottom-left-radius: 4px;
}
.chat-waktu {
  font-size: 10px; color: var(--tekssamar);
  margin-top: 3px; padding: 0 6px;
}

.chat-kosong {
  text-align: center; padding: 50px 12px;
  color: var(--tekssamar); font-size: 13px;
}
.chat-kosong i { font-size: 40px; opacity: .35; display: block; margin-bottom: 10px; }

/* ===== INPUT BAR — textarea & tombol kirim sama tinggi 44px ===== */
.chat-form {
  position: fixed;
  bottom: var(--headertinggi, 56px); /* default mobile: di atas bottom nav */
  left: 0; right: 0;
  background: white;
  border-top: 1px solid var(--garis);
  padding: 10px 12px;
  padding-bottom: calc(10px + env(safe-area-inset-bottom, 0px));
  z-index: 50;
}
.chat-form .isi {
  display: flex; gap: 8px;
  align-items: flex-end; /* tombol tetap di bawah saat textarea memanjang */
  width: 100%;
}
.chat-form textarea {
  flex: 1;
  height: 44px; min-height: 44px; max-height: 110px;
  padding: 11px 16px;
  border: 1px solid var(--garis); border-radius: 22px;
  font-family: inherit; font-size: 14px; line-height: 1.4;
  resize: none; outline: none;
  background: var(--putihbg); color: var(--utama);
}
.chat-form textarea::placeholder { color: var(--tekssamar); }
.chat-form textarea:focus {
  border-color: var(--utama);
  background: white;
}
.chat-form button {
  flex-shrink: 0;
  width: 44px; height: 44px;
  background: var(--utama); color: white;
  border: none; border-radius: 50%;
  cursor: pointer; font-size: 15px;
  display: inline-flex; align-items: center; justify-content: center;
}
.chat-form button:hover { background: var(--kedua); }

/* ===== BANNER CHAT DITUTUP (status final) — posisi sama dengan input bar ===== */
.chat-tutup {
  position: fixed;
  bottom: var(--headertinggi, 56px);
  left: 0; right: 0;
  background: var(--tunggubg);
  border-top: 1px solid var(--tunggu);
  padding: 14px 16px;
  padding-bottom: calc(14px + env(safe-area-inset-bottom, 0px));
  text-align: center; font-size: 12px; color: #8b5d1f;
  z-index: 50; line-height: 1.45;
}
.chat-tutup i { margin-right: 4px; }

/* ===== MOBILE — header jadi stack (title atas, tombol balik bawah full-width) ===== */
@media (max-width: 768px) {
  .chat-kepala {
    flex-direction: column;
    align-items: stretch;
  }
  .chat-balik {
    width: 100%; /* tombol balik full-width di mobile */
  }
}

/* ===== DESKTOP — sidebar di kiri, tidak ada bottom nav.
   body sudah ber-padding-left sidebar dari pembeli.css, jadi container ini
   otomatis fill area konten (kanan-kiri nempel). ===== */
@media (min-width: 769px) {
  .chat-halaman {
    padding: 22px 22px 0;
    padding-bottom: 110px;
  }
  .chat-kepala-info .ikon { width: 44px; height: 44px; font-size: 18px; }
  .chat-kepala-info h1 { font-size: 20px; }
  /* bubble tidak full-width supaya tetap nyaman dibaca di layar lebar */
  .chat-blok { max-width: 520px; }

  .chat-form,
  .chat-tutup {
    bottom: 0;
    left: var(--sidebarlebar, 240px);
  }
}
</style>
</head>
<body>

<?php include '../../3. komponen/navbarpembeli.php'; ?>

<div class="chat-halaman">

  <!-- HEADER: title (icon + nama toko) kiri, tombol kembali kanan -->
  <div class="chat-kepala">
    <div class="chat-kepala-info">
      <div class="ikon"><i class="fa-solid fa-comments"></i></div>
      <div class="teks">
        <h1>Chat Penjual</h1>
        <p>
          <strong><?= $nomerpesanan ?></strong> &middot;
          <i class="fa-solid fa-store"></i> <?= htmlspecialchars($namatoko) ?>
        </p>
      </div>
    </div>
    <a href="pesanan.php?tab=<?= $statusAktif ? 'aktif' : 'riwayat' ?>" class="chat-balik">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
  </div>

  <!-- KARTU DETAIL PESANAN — ringkasan supaya pembeli ga perlu pindah halaman -->
  <div class="chat-kartu">
    <div class="chat-kartu-atas">
      <div>
        <div class="nomor"><?= $nomerpesanan ?></div>
        <div class="tanggal"><?= date('d M Y, H:i', strtotime($pesanan['tanggal_order'])) ?></div>
      </div>
      <span class="chat-kartu-badge <?= $kelasStatus ?>">
        <?= htmlspecialchars($pesanan['status_order']) ?>
      </span>
    </div>

    <div style="margin-top:6px;">
      <?php foreach ($itemPesanan as $it): ?>
      <div class="chat-kartu-item">
        <span>
          <span class="qty"><?= (int)$it['jumlah'] ?>×</span>
          <?= htmlspecialchars($it['nama_menu']) ?>
        </span>
        <span class="sub">Rp <?= number_format($it['subtotal'],0,',','.') ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if (!empty($pesanan['catatan'])): ?>
    <div class="chat-kartu-catatan">
      <strong><i class="fa-solid fa-note-sticky"></i> Catatan untuk penjual:</strong>
      <?= htmlspecialchars($pesanan['catatan']) ?>
    </div>
    <?php endif; ?>

    <div class="chat-kartu-total">
      <span class="label">Total</span>
      <span class="nilai">Rp <?= number_format($pesanan['total_harga'],0,',','.') ?></span>
    </div>
  </div>

  <!-- BANNER INFO STATUS -->
  <div class="chat-info">
    <i class="fa-solid fa-circle-info"></i>
    <?php if ($statusAktif): ?>
      Chat akan ditutup otomatis saat pesanan selesai atau dibatalkan.
    <?php else: ?>
      Pesanan sudah <?= htmlspecialchars(strtolower($pesanan['status_order'])) ?> — chat ditutup, hanya bisa membaca riwayat.
    <?php endif; ?>
  </div>

  <!-- DAFTAR PESAN -->
  <div class="chat-daftar">
    <?php if (empty($pesanList)): ?>
      <div class="chat-kosong">
        <i class="fa-solid fa-comment-dots"></i>
        Belum ada pesan. Sapa penjual untuk memulai!
      </div>
    <?php else:
      foreach ($pesanList as $m):
        $mine = ((int)$m['id_pengirim'] === $idpengguna);
        $waktu = date('H:i', strtotime($m['created']));
    ?>
      <div class="chat-baris <?= $mine ? 'mine' : 'theirs' ?>">
        <div class="chat-blok">
          <div class="chat-bubble <?= $mine ? 'mine' : 'theirs' ?>">
            <?= nl2br(htmlspecialchars($m['pesan'])) ?>
          </div>
          <div class="chat-waktu"><?= $waktu ?></div>
        </div>
      </div>
    <?php endforeach; endif; ?>

    <!-- anchor untuk auto-scroll ke pesan terbaru tanpa JS -->
    <a id="latest"></a>
  </div>

</div>

<?php if ($statusAktif): ?>
<form class="chat-form" method="POST" action="chat.php">
  <div class="isi">
    <input type="hidden" name="id_order" value="<?= $idpesanan ?>">
    <textarea name="pesan" placeholder="Tulis pesan untuk penjual…" maxlength="500" required></textarea>
    <button type="submit" title="Kirim pesan"><i class="fa-solid fa-paper-plane"></i></button>
  </div>
</form>
<?php else: ?>
<div class="chat-tutup">
  <i class="fa-solid fa-lock"></i>
  Chat ditutup karena pesanan sudah <?= htmlspecialchars(strtolower($pesanan['status_order'])) ?>.
</div>
<?php endif; ?>

</body>
</html>
