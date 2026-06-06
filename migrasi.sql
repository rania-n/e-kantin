-- ============================================================
-- MIGRASI DATABASE E-KANTIN
-- ============================================================
-- File ini berisi penambahan kolom yang DULU dijalankan otomatis
-- oleh koneksi.php. Sekarang dijalankan MANUAL agar koneksi.php bersih.
--
-- CARA PAKAI (phpMyAdmin):
--   1. Buka phpMyAdmin, pilih database "e_kantin" di panel kiri.
--   2. Klik tab "SQL".
--   3. Salin-tempel seluruh isi file ini, lalu klik "Go".
--   (atau di terminal: mysql -u root e_kantin < migrasi.sql)
--
-- AMAN dijalankan berkali-kali: pakai "ADD COLUMN IF NOT EXISTS",
-- jadi kalau kolomnya sudah ada, perintah dilewati tanpa error.
-- (Sintaks IF NOT EXISTS didukung MariaDB bawaan XAMPP.)
--
-- WAJIB dijalankan setiap kali setup database baru / import ulang,
-- karena koneksi.php tidak lagi menambah kolom ini otomatis.
-- ============================================================

-- pastikan mentarget database yang benar (ganti kalau nama db kamu beda)
USE `e_kantin`;

-- 1) status_akun: untuk admin menonaktifkan/suspend pengguna
ALTER TABLE `tb_user`
  ADD COLUMN IF NOT EXISTS `status_akun` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif' AFTER `status_verifikasi`;

-- 2) no_telepon: nomor pengguna yang diisi saat daftar / admin tambah user
ALTER TABLE `tb_user`
  ADD COLUMN IF NOT EXISTS `no_telepon` VARCHAR(20) DEFAULT NULL AFTER `email`;

-- 3) stok_dipotong: jumlah stok yang benar-benar dipotong saat order dibuat,
--    dipakai saat pembatalan agar pengembalian stok tidak melebihi yang terpotong
ALTER TABLE `tb_detail_order`
  ADD COLUMN IF NOT EXISTS `stok_dipotong` INT(11) NOT NULL DEFAULT 0 AFTER `subtotal`;
