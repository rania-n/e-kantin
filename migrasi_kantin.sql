-- ================================================================
-- MIGRASI DATABASE: Sistem Kantin Tetap (10 Slot Fisik)
-- Jalankan SELURUH file ini di phpMyAdmin sebelum menggunakan kode PHP baru.
-- ================================================================

-- Langkah 1: Buat kolom id_user nullable
-- Artinya: kantin bisa "kosong" (tidak punya pemilik) ketika penjual dihapus.
ALTER TABLE tb_toko MODIFY COLUMN id_user INT NULL;

-- Langkah 2: Buat nama_toko nullable
-- Ketika kantin kosong, nama_toko akan diisi NULL.
ALTER TABLE tb_toko MODIFY COLUMN nama_toko VARCHAR(100) NULL;

-- Langkah 3: Tambah kolom nomor_kantin (nomor fisik kantin di sekolah, 1-10)
-- Kolom ini dipakai sebagai identitas tetap kantin yang bisa dilihat penjual dan pembeli.
ALTER TABLE tb_toko ADD COLUMN nomor_kantin TINYINT UNSIGNED NULL AFTER id_toko;

-- Langkah 4: Konversi toko yang sudah di-soft-delete (deleted=1) menjadi kantin kosong
-- Kantin tidak pernah benar-benar dihapus dari database, hanya dikosongkan.
UPDATE tb_toko
SET id_user     = NULL,
    nama_toko   = NULL,
    status_toko = 'tutup',
    deleted     = 0
WHERE deleted = 1;

-- Langkah 5: Beri nomor urut ke semua baris yang ada di tb_toko
-- Baris pertama (id_toko terkecil) mendapat nomor_kantin = 1, dst.
SET @cnt = 0;
UPDATE tb_toko
SET nomor_kantin = (@cnt := @cnt + 1)
ORDER BY id_toko ASC;

-- Langkah 6: Insert kantin kosong untuk slot yang belum ada (sampai total 10 kantin)
-- Query ini hanya menambah baris untuk nomor yang belum ada di tabel.
INSERT INTO tb_toko (nomor_kantin, id_user, nama_toko, status_toko, deleted)
SELECT n, NULL, NULL, 'tutup', 0
FROM (
    SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
) AS angka
WHERE angka.n NOT IN (
    SELECT nomor_kantin FROM tb_toko WHERE nomor_kantin IS NOT NULL
);

-- Langkah 7: Pastikan nomor_kantin unik (tidak ada dua kantin dengan nomor sama)
ALTER TABLE tb_toko ADD UNIQUE KEY uk_nomor_kantin (nomor_kantin);

-- Langkah 8: Verifikasi hasil — jalankan ini setelah migrasi untuk memeriksa
-- SELECT id_toko, nomor_kantin, id_user, nama_toko, status_toko, deleted FROM tb_toko ORDER BY nomor_kantin;
