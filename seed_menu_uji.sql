-- ================================================================
-- SEED LENGKAP — 7 penjual + isi slot 4-10 + menu masing-masing
-- ================================================================
-- Cara pakai: phpMyAdmin → database ekantin → tab SQL → paste semua → Go
-- Password semua penjual uji: 00000000
-- Aman dijalankan ulang (akan hapus dulu user uji dan menu yang ada,
-- lalu insert ulang).
-- ================================================================

-- ------------------------------------------------------------
-- 0. Bersih dulu kalau pernah dijalankan
-- ------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

-- hapus menu lama milik user uji ini supaya tidak duplikat
DELETE FROM tb_menu
WHERE id_penjual IN (
    SELECT id_user FROM tb_user
    WHERE username IN ('butika','paksahudi','paksukamto','mardika','budian','pakbasuni','pakfajar')
);

-- kosongkan slot 4-10 dulu sebelum di-assign ulang
UPDATE tb_toko
SET id_user=NULL, nama_toko=NULL, foto_toko=NULL, status_toko='tutup', tanggal_mulai=NULL
WHERE id_user IN (
    SELECT id_user FROM tb_user
    WHERE username IN ('butika','paksahudi','paksukamto','mardika','budian','pakbasuni','pakfajar')
);

-- hapus user uji sebelumnya
DELETE FROM tb_user
WHERE username IN ('butika','paksahudi','paksukamto','mardika','budian','pakbasuni','pakfajar');

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- 1. INSERT 7 PENJUAL — password '00000000' (hash bcrypt)
-- ================================================================
INSERT INTO tb_user (username, email, password, role, deleted) VALUES
('butika',     'butika@test.com',     '$2y$10$9RcrgpsbxLCsYhGlwprRme/B9xSzYOmhYRZLDWseh3g4dClwxZaKO', 'penjual', 0);
SET @id_butika = LAST_INSERT_ID();

INSERT INTO tb_user (username, email, password, role, deleted) VALUES
('paksahudi',  'paksahudi@test.com',  '$2y$10$9RcrgpsbxLCsYhGlwprRme/B9xSzYOmhYRZLDWseh3g4dClwxZaKO', 'penjual', 0);
SET @id_paksahudi = LAST_INSERT_ID();

INSERT INTO tb_user (username, email, password, role, deleted) VALUES
('paksukamto', 'paksukamto@test.com', '$2y$10$9RcrgpsbxLCsYhGlwprRme/B9xSzYOmhYRZLDWseh3g4dClwxZaKO', 'penjual', 0);
SET @id_paksukamto = LAST_INSERT_ID();

INSERT INTO tb_user (username, email, password, role, deleted) VALUES
('mardika',    'mardika@test.com',    '$2y$10$9RcrgpsbxLCsYhGlwprRme/B9xSzYOmhYRZLDWseh3g4dClwxZaKO', 'penjual', 0);
SET @id_mardika = LAST_INSERT_ID();

INSERT INTO tb_user (username, email, password, role, deleted) VALUES
('budian',     'budian@test.com',     '$2y$10$9RcrgpsbxLCsYhGlwprRme/B9xSzYOmhYRZLDWseh3g4dClwxZaKO', 'penjual', 0);
SET @id_budian = LAST_INSERT_ID();

INSERT INTO tb_user (username, email, password, role, deleted) VALUES
('pakbasuni',  'pakbasuni@test.com',  '$2y$10$9RcrgpsbxLCsYhGlwprRme/B9xSzYOmhYRZLDWseh3g4dClwxZaKO', 'penjual', 0);
SET @id_pakbasuni = LAST_INSERT_ID();

INSERT INTO tb_user (username, email, password, role, deleted) VALUES
('pakfajar',   'pakfajar@test.com',   '$2y$10$9RcrgpsbxLCsYhGlwprRme/B9xSzYOmhYRZLDWseh3g4dClwxZaKO', 'penjual', 0);
SET @id_pakfajar = LAST_INSERT_ID();

-- ================================================================
-- 2. ASSIGN PENJUAL KE SLOT KANTIN (4-10)
-- ================================================================
UPDATE tb_toko SET id_user=@id_butika,     nama_toko='Warung Bu Tika',     status_toko='buka', tanggal_mulai=NOW(), deleted=0 WHERE nomor_kantin=4;
UPDATE tb_toko SET id_user=@id_paksahudi,  nama_toko='Warung Pak Sahudi',  status_toko='buka', tanggal_mulai=NOW(), deleted=0 WHERE nomor_kantin=5;
UPDATE tb_toko SET id_user=@id_paksukamto, nama_toko='Warung Pak Sukamto', status_toko='buka', tanggal_mulai=NOW(), deleted=0 WHERE nomor_kantin=6;
UPDATE tb_toko SET id_user=@id_mardika,    nama_toko='Warung Mar Dika',    status_toko='buka', tanggal_mulai=NOW(), deleted=0 WHERE nomor_kantin=7;
UPDATE tb_toko SET id_user=@id_budian,     nama_toko='Warung Bu Dian',     status_toko='buka', tanggal_mulai=NOW(), deleted=0 WHERE nomor_kantin=8;
UPDATE tb_toko SET id_user=@id_pakbasuni,  nama_toko='Warung Pak Basuni',  status_toko='buka', tanggal_mulai=NOW(), deleted=0 WHERE nomor_kantin=9;
UPDATE tb_toko SET id_user=@id_pakfajar,   nama_toko='Warung Pak Fajar',   status_toko='buka', tanggal_mulai=NOW(), deleted=0 WHERE nomor_kantin=10;

-- ================================================================
-- 3. INSERT MENU (id_toko diambil dari slot, id_penjual diisi otomatis di langkah 4)
-- ================================================================
INSERT INTO `tb_menu`
(`nama_menu`, `harga`, `stok`, `kategori`, `deskripsi`, `foto`, `status`, `deleted`, `created`, `id_toko`)
VALUES

-- BU TIKA (id_toko = 4)
('Es HTS', 5000, 50, 'Minuman Ringan', 'Minuman Es HTS', '', 'aktif', 0, NOW(), 4),
('Es Gamon', 5000, 50, 'Minuman Ringan', 'Minuman Es Gamon', '', 'aktif', 0, NOW(), 4),
('Es PHP', 5000, 50, 'Minuman Ringan', 'Minuman Es PHP', '', 'aktif', 0, NOW(), 4),
('Es Toxic', 5000, 50, 'Minuman Ringan', 'Minuman Es Toxic', '', 'aktif', 0, NOW(), 4),
('Es CLBK', 5000, 50, 'Minuman Ringan', 'Minuman Es CLBK', '', 'aktif', 0, NOW(), 4),
('Es Friendzone', 5000, 50, 'Minuman Ringan', 'Minuman Es Friendzone', '', 'aktif', 0, NOW(), 4),
('Es Soria', 5000, 50, 'Minuman Ringan', 'Minuman Es Soria', '', 'aktif', 0, NOW(), 4),
('Sempol', 1000, 50, 'Makanan Ringan', 'Sempol ayam', '', 'aktif', 0, NOW(), 4),
('Ceker Lava Tanpa Tulang', 8000, 50, 'Makanan Ringan', 'Ceker lava pedas tanpa tulang', '', 'aktif', 0, NOW(), 4),
('Soto Ayam Special', 8000, 50, 'Makanan Berat', 'Soto ayam special', '', 'aktif', 0, NOW(), 4),

-- PAK SAHUDI (id_toko = 5)
('Nasi Ayam Goreng', 8000, 50, 'Makanan Berat', 'Nasi ayam goreng', '', 'aktif', 0, NOW(), 5),
('Nasi Lele Goreng', 8000, 50, 'Makanan Berat', 'Nasi lele goreng', '', 'aktif', 0, NOW(), 5),
('Mie Instan', 5000, 50, 'Makanan Ringan', 'Mie instan', '', 'aktif', 0, NOW(), 5),
('Es Teh', 3000, 50, 'Minuman Ringan', 'Es teh segar', '', 'aktif', 0, NOW(), 5),
('Pop Ice', 3000, 50, 'Minuman Ringan', 'Pop ice aneka rasa', '', 'aktif', 0, NOW(), 5),
('Nutrisari', 3000, 50, 'Minuman Ringan', 'Nutrisari dingin', '', 'aktif', 0, NOW(), 5),

-- PAK SUKAMTO (id_toko = 6)
('Nasi Ayam Geprek', 8000, 50, 'Makanan Berat', 'Nasi ayam geprek', '', 'aktif', 0, NOW(), 6),
('Nasi Ayam Bakar', 8000, 50, 'Makanan Berat', 'Nasi ayam bakar', '', 'aktif', 0, NOW(), 6),
('Nasi Campur Telur', 8000, 50, 'Makanan Berat', 'Nasi campur telur', '', 'aktif', 0, NOW(), 6),
('Nasi Pecel', 6000, 50, 'Makanan Berat', 'Nasi pecel', '', 'aktif', 0, NOW(), 6),
('Es Jeruk/Panas', 3000, 50, 'Minuman Ringan', 'Es jeruk atau jeruk panas', '', 'aktif', 0, NOW(), 6),
('Es Teh/Panas', 3000, 50, 'Minuman Ringan', 'Es teh atau teh panas', '', 'aktif', 0, NOW(), 6),
('Kopi Hitam', 3000, 50, 'Minuman Ringan', 'Kopi hitam', '', 'aktif', 0, NOW(), 6),

-- MAR DIKA (id_toko = 7)
('Nasi Ayam Geprek', 8000, 50, 'Makanan Berat', 'Nasi ayam geprek', '', 'aktif', 0, NOW(), 7),
('Nasi Ayam Goreng', 8000, 50, 'Makanan Berat', 'Nasi ayam goreng', '', 'aktif', 0, NOW(), 7),
('Nasi Telur + Tahu/Tempe', 6000, 50, 'Makanan Berat', 'Nasi telur dengan tahu atau tempe', '', 'aktif', 0, NOW(), 7),
('Es Teh/Teh Hangat', 3000, 50, 'Minuman Ringan', 'Es teh atau teh hangat', '', 'aktif', 0, NOW(), 7),
('Nutrisari', 3000, 50, 'Minuman Ringan', 'Nutrisari dingin', '', 'aktif', 0, NOW(), 7),
('Good Day', 3000, 50, 'Minuman Ringan', 'Minuman kopi Good Day', '', 'aktif', 0, NOW(), 7),
('Pop Ice', 3000, 50, 'Minuman Ringan', 'Pop ice aneka rasa', '', 'aktif', 0, NOW(), 7),

-- BU DIAN (id_toko = 8)
('Tahu Kres', 5000, 50, 'Makanan Ringan', 'Tahu kres renyah', '', 'aktif', 0, NOW(), 8),
('Jamur Krispy', 5000, 50, 'Makanan Ringan', 'Jamur crispy', '', 'aktif', 0, NOW(), 8),
('Usus Crunchy', 5000, 50, 'Makanan Ringan', 'Usus crunchy', '', 'aktif', 0, NOW(), 8),
('Pentol Cilok Pedes', 5000, 50, 'Makanan Ringan', 'Pentol cilok pedas', '', 'aktif', 0, NOW(), 8),
('Dimsum Mini Mentai', 5000, 50, 'Makanan Ringan', 'Dimsum mini mentai', '', 'aktif', 0, NOW(), 8),
('Tahu Bakso', 5000, 50, 'Makanan Ringan', 'Tahu bakso', '', 'aktif', 0, NOW(), 8),

-- PAK BASUNI (id_toko = 9)
('Nasi Pecel', 6000, 50, 'Makanan Berat', 'Nasi pecel', '', 'aktif', 0, NOW(), 9),
('Nasi Soto Ayam', 7000, 50, 'Makanan Berat', 'Nasi soto ayam', '', 'aktif', 0, NOW(), 9),
('Es Jeruk/Teh', 3000, 50, 'Minuman Ringan', 'Es jeruk atau es teh', '', 'aktif', 0, NOW(), 9),
('Jeruk/Teh Hangat', 3000, 50, 'Minuman Ringan', 'Jeruk hangat atau teh hangat', '', 'aktif', 0, NOW(), 9),
('Kopi Hitam', 3000, 50, 'Minuman Ringan', 'Kopi hitam', '', 'aktif', 0, NOW(), 9),
('Mie Cup', 7000, 50, 'Makanan Ringan', 'Mie cup', '', 'aktif', 0, NOW(), 9),
('Mie Goreng/Rebus', 5000, 50, 'Makanan Ringan', 'Mie goreng atau rebus', '', 'aktif', 0, NOW(), 9),

-- PAK FAJAR (id_toko = 10)
('Soto', 7000, 50, 'Makanan Berat', 'Soto hangat', '', 'aktif', 0, NOW(), 10),
('Pentol', 500, 50, 'Makanan Ringan', 'Pentol', '', 'aktif', 0, NOW(), 10),
('Tahu Walik', 500, 50, 'Makanan Ringan', 'Tahu walik', '', 'aktif', 0, NOW(), 10),
('Mie Goreng', 5000, 50, 'Makanan Ringan', 'Mie goreng', '', 'aktif', 0, NOW(), 10),
('Mie Rebus', 5000, 50, 'Makanan Ringan', 'Mie rebus', '', 'aktif', 0, NOW(), 10),
('Es Jeruk/Panas', 3000, 50, 'Minuman Ringan', 'Es jeruk atau jeruk panas', '', 'aktif', 0, NOW(), 10),
('Es Teh/Panas', 3000, 50, 'Minuman Ringan', 'Es teh atau teh panas', '', 'aktif', 0, NOW(), 10),
('Kopi Susu', 4000, 50, 'Minuman Ringan', 'Kopi susu', '', 'aktif', 0, NOW(), 10);

-- ================================================================
-- 4. BACKFILL id_penjual semua menu dari slot tokonya
-- ================================================================
UPDATE tb_menu m
JOIN tb_toko t ON m.id_toko = t.id_toko AND t.deleted = 0
SET m.id_penjual = t.id_user
WHERE m.id_penjual IS NULL AND t.id_user IS NOT NULL;
