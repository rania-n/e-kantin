<?php
/*
   auto-batalkan pesanan kedaluwarsa.

   aturan: pesanan yang masih berstatus "Menunggu" (belum diproses penjual)
   dan tanggal ordernya sudah lewat dari hari ini, otomatis dibatalkan.
   alasannya: pesanan dari hari kemarin yang tidak ditanggapi penjual tidak
   lagi relevan untuk diproses, jadi sistem membatalkannya sendiri.

   stok item ikut dikembalikan (sama seperti pembatalan manual oleh penjual)
   agar stok tidak berkurang sia-sia.

   file ini di-include di halaman daftar pesanan (penjual & pembeli). karena
   query-nya ringan dan hanya menyentuh pesanan yang benar-benar kedaluwarsa,
   aman dipanggil setiap halaman tersebut dibuka — berfungsi seperti "cron"
   sederhana tanpa perlu penjadwal terpisah.
*/

// hanya jalan jika koneksi database tersedia (mencegah error saat di-include sembarangan)
if (isset($conn) && $conn instanceof mysqli) {

    // ambil semua pesanan "Menunggu" yang tanggalnya SEBELUM hari ini (CURDATE()).
    // DATE() membuang jam, jadi perbandingan murni per tanggal.
    $resexp = $conn->query(
        "SELECT id_order FROM tb_order
         WHERE status_order='Menunggu' AND deleted=0
           AND DATE(tanggal_order) < CURDATE()"
    );

    if ($resexp && $resexp->num_rows > 0) {
        // tampung id dulu supaya result set bisa ditutup sebelum menjalankan UPDATE
        // (menghindari error "commands out of sync" pada mysqli)
        $idkadaluarsa = [];
        while ($row = $resexp->fetch_assoc()) $idkadaluarsa[] = (int)$row['id_order'];

        foreach ($idkadaluarsa as $oid) {
            // kembalikan stok tiap item — tepat sebanyak yang dulu terpotong (stok_dipotong),
            // fallback ke jumlah untuk order lama sebelum kolom stok_dipotong ada.
            $qd = $conn->prepare("SELECT id_menu, jumlah, stok_dipotong FROM tb_detail_order WHERE id_order=? AND deleted=0");
            $qd->bind_param("i", $oid); $qd->execute();
            $items = $qd->get_result()->fetch_all(MYSQLI_ASSOC); $qd->close();
            foreach ($items as $it) {
                $kembali = ((int)$it['stok_dipotong'] > 0) ? (int)$it['stok_dipotong'] : (int)$it['jumlah'];
                $us = $conn->prepare("UPDATE tb_menu SET stok = stok + ? WHERE id_menu=?");
                $us->bind_param("ii", $kembali, $it['id_menu']);
                $us->execute(); $us->close();
            }
            // ubah status pesanan menjadi Dibatalkan dan catat waktu perubahan
            $up = $conn->prepare("UPDATE tb_order SET status_order='Dibatalkan', updated=NOW() WHERE id_order=?");
            $up->bind_param("i", $oid); $up->execute(); $up->close();
        }
    }
}
?>
