-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 18, 2026 at 07:17 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e_kantin`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_detail_order`
--

CREATE TABLE `tb_detail_order` (
  `id_detail` int(11) NOT NULL,
  `id_order` int(11) NOT NULL,
  `id_menu` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created` timestamp NULL DEFAULT current_timestamp(),
  `updated` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_detail_order`
--

INSERT INTO `tb_detail_order` (`id_detail`, `id_order`, `id_menu`, `jumlah`, `harga_satuan`, `subtotal`, `created`, `updated`, `deleted`, `deleted_at`) VALUES
(1, 1, 8, 1, 13000.00, 13000.00, '2026-05-17 01:40:47', NULL, 0, NULL),
(2, 1, 9, 1, 3000.00, 3000.00, '2026-05-17 01:40:47', NULL, 0, NULL),
(3, 1, 7, 1, 18000.00, 18000.00, '2026-05-17 01:40:47', NULL, 0, NULL),
(4, 2, 8, 2, 13000.00, 26000.00, '2026-05-17 01:42:00', NULL, 0, NULL),
(5, 3, 2, 6, 5000.00, 30000.00, '2026-05-17 02:04:44', NULL, 0, NULL),
(6, 4, 1, 1, 15000.00, 15000.00, '2026-05-17 03:31:15', NULL, 0, NULL),
(7, 4, 2, 1, 5000.00, 5000.00, '2026-05-17 03:31:15', NULL, 0, NULL),
(8, 5, 8, 1, 13000.00, 13000.00, '2026-05-17 03:32:05', NULL, 0, NULL),
(9, 6, 7, 1, 18000.00, 18000.00, '2026-05-17 03:34:29', NULL, 0, NULL),
(10, 7, 8, 1, 13000.00, 13000.00, '2026-05-17 06:19:36', NULL, 0, NULL),
(11, 8, 2, 11, 5000.00, 55000.00, '2026-05-17 06:24:19', NULL, 0, NULL),
(12, 8, 4, 1, 12000.00, 12000.00, '2026-05-17 06:24:19', NULL, 0, NULL),
(13, 8, 1, 1, 15000.00, 15000.00, '2026-05-17 06:24:19', NULL, 0, NULL),
(14, 9, 9, 1, 3000.00, 3000.00, '2026-05-17 08:44:57', NULL, 0, NULL),
(15, 10, 1, 7, 15000.00, 105000.00, '2026-05-17 08:46:11', NULL, 0, NULL),
(16, 10, 2, 1, 5000.00, 5000.00, '2026-05-17 08:46:11', NULL, 0, NULL),
(17, 11, 1, 1, 15000.00, 15000.00, '2026-05-17 08:56:23', NULL, 0, NULL),
(18, 12, 2, 3, 5000.00, 15000.00, '2026-05-17 10:23:21', NULL, 0, NULL),
(19, 13, 7, 1, 18000.00, 18000.00, '2026-05-17 13:06:51', NULL, 0, NULL),
(20, 13, 10, 60, 8000.00, 480000.00, '2026-05-17 13:06:51', NULL, 0, NULL),
(21, 14, 9, 1, 3000.00, 3000.00, '2026-05-17 13:09:22', NULL, 0, NULL),
(22, 15, 2, 1, 5000.00, 5000.00, '2026-05-18 01:22:00', NULL, 0, NULL),
(23, 16, 8, 1, 13000.00, 13000.00, '2026-05-18 04:28:43', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_keranjang`
--

CREATE TABLE `tb_keranjang` (
  `id_keranjang` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_menu` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_keranjang`
--

INSERT INTO `tb_keranjang` (`id_keranjang`, `id_user`, `id_menu`, `jumlah`) VALUES
(1, 13, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tb_menu`
--

CREATE TABLE `tb_menu` (
  `id_menu` int(11) NOT NULL,
  `nama_menu` varchar(50) NOT NULL,
  `harga` int(11) NOT NULL,
  `stok` int(11) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `deskripsi` text NOT NULL,
  `foto` varchar(255) NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `deleted` tinyint(4) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_toko` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_menu`
--

INSERT INTO `tb_menu` (`id_menu`, `nama_menu`, `harga`, `stok`, `kategori`, `deskripsi`, `foto`, `status`, `deleted`, `deleted_at`, `created`, `updated`, `id_toko`) VALUES
(1, 'Bakso Daging', 15000, 40, 'Makanan Berat', 'Bakso sapi asli dengan urat yang kenyal dan kuah gurih.', '69f989784ff3f.jpeg', 'aktif', 0, NULL, '2026-04-25 06:30:43', '2026-05-17 08:56:23', 1),
(2, 'Teh Manis', 5000, 77, 'Minuman Sehat', 'Teh seduh segar dengan gula asli (Es/Hangat).', '69f98982dc00c.jpeg', 'aktif', 0, NULL, '2026-04-25 06:30:43', '2026-05-18 01:22:00', 1),
(3, 'Kopi Hitam', 7000, 80, 'Minuman Sehat', 'Kopi robusta mantap untuk penambah semangat.', '69f9898b92cf9.jpeg', 'aktif', 0, NULL, '2026-04-25 06:30:43', '2026-05-17 01:27:13', 1),
(4, 'Mie Ayam', 12000, 39, 'Makanan Berat', 'Mie kuning dengan potongan ayam kecap dan sawi segar.', '69f989a245a3f.jpeg', 'aktif', 0, NULL, '2026-04-25 06:30:43', '2026-05-17 06:24:19', 1),
(5, 'Dimsum Ayam', 15000, 30, 'Makanan Ringan', 'Dimsum lembut isi daging ayam isi 4 per porsi.', '69f989b37462f.jpeg', 'aktif', 0, NULL, '2026-04-25 06:30:43', '2026-05-17 01:28:48', 1),
(6, 'Nasi Pecel', 10000, 45, 'Makanan Berat', 'Nasi dengan sayuran segar dan bumbu kacang khas.', '69f989c5ec7fb.jpeg', 'aktif', 0, NULL, '2026-04-25 06:30:43', '2026-05-17 01:28:48', 1),
(7, 'Ayam Geprek', 18000, 22, 'Makanan Berat', 'Ayam goreng tepung renyah dengan sambal korek pedas.', '69f989d48c5cd.jpeg', 'aktif', 0, NULL, '2026-04-25 06:30:43', '2026-05-18 09:48:30', 2),
(8, 'Soto Ayam', 13000, 29, 'Makanan Berat', 'Soto ayam kuah kuning bening dengan koya gurih.', '69f989f373682.jpeg', 'aktif', 0, NULL, '2026-04-25 06:30:43', '2026-05-18 04:28:43', 2),
(9, 'Air Putih', 3000, 198, 'Minuman Sehat', 'Air mineral kemasan botol 600ml.', '69f989ea8c265.jpeg', 'nonaktif', 0, NULL, '2026-04-25 06:30:43', '2026-05-18 09:49:31', 2),
(10, 'Es Jeruk', 8000, 0, 'Minuman Sehat', 'Perasan jeruk asli yang menyegarkan.', '69f989deae9c9.jpeg', 'aktif', 0, NULL, '2026-04-25 06:30:43', '2026-05-18 04:37:22', 2),
(11, 'coba', 0, 10, 'Makanan Berat', 'kkk', '69f98961aaf4f.png', 'aktif', 1, '2026-05-05 14:03:41', '2026-05-05 04:20:50', '2026-05-17 01:32:51', 2),
(12, 'Air Putih', 3000000, -10, 'Minuman Sehat', ';,,lm', '69f9937a6974c.jpeg', 'nonaktif', 1, '2026-05-18 11:23:03', '2026-05-05 06:51:38', '2026-05-18 04:23:03', 2),
(13, 'kucin', 0, 1000, 'Minuman Ringan', 'fmf', '6a0a960d6189f.png', 'aktif', 0, NULL, '2026-05-18 04:31:09', '2026-05-18 09:48:24', 2);

-- --------------------------------------------------------

--
-- Table structure for table `tb_order`
--

CREATE TABLE `tb_order` (
  `id_order` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_toko` int(11) DEFAULT NULL,
  `tanggal_order` datetime NOT NULL DEFAULT current_timestamp(),
  `total_harga` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status_order` varchar(50) NOT NULL,
  `metode_pembayaran` varchar(50) NOT NULL,
  `catatan` text DEFAULT NULL,
  `created` timestamp NULL DEFAULT current_timestamp(),
  `updated` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `rating` int(1) DEFAULT NULL,
  `ulasan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_order`
--

INSERT INTO `tb_order` (`id_order`, `id_user`, `id_toko`, `tanggal_order`, `total_harga`, `status_order`, `metode_pembayaran`, `catatan`, `created`, `updated`, `deleted`, `deleted_at`, `rating`, `ulasan`) VALUES
(1, 12, NULL, '2026-05-17 08:40:47', 35000.00, 'Diproses', 'Tunai', NULL, '2026-05-17 01:40:47', '2026-05-17 09:28:53', 0, NULL, NULL, NULL),
(2, 12, NULL, '2026-05-17 08:42:00', 27000.00, 'Siap Diambil', 'Transfer', NULL, '2026-05-17 01:42:00', '2026-05-17 09:29:10', 0, NULL, NULL, NULL),
(3, 11, NULL, '2026-05-17 09:04:44', 31000.00, 'Menunggu', 'Tunai', NULL, '2026-05-17 02:04:44', NULL, 0, NULL, NULL, NULL),
(4, 12, NULL, '2026-05-17 10:31:15', 20000.00, 'Menunggu', 'Transfer', NULL, '2026-05-17 03:31:15', NULL, 0, NULL, NULL, NULL),
(5, 12, NULL, '2026-05-17 10:32:05', 13000.00, 'Menunggu', 'Tunai', NULL, '2026-05-17 03:32:05', '2026-05-17 03:32:25', 0, NULL, 5, 'bgs'),
(6, 13, NULL, '2026-05-17 10:34:29', 19000.00, 'Menunggu', 'Tunai', NULL, '2026-05-17 03:34:29', NULL, 0, NULL, NULL, NULL),
(7, 11, 2, '2026-05-17 13:19:36', 14000.00, 'Menunggu', 'Tunai', '', '2026-05-17 06:19:36', NULL, 0, NULL, NULL, NULL),
(8, 11, 1, '2026-05-17 13:24:19', 83000.00, 'Selesai', 'Transfer', '', '2026-05-17 06:24:19', NULL, 0, NULL, NULL, NULL),
(9, 11, 2, '2026-05-17 15:44:57', 4000.00, 'Dibatalkan', 'Transfer', '', '2026-05-17 08:44:57', '2026-05-18 09:49:31', 0, NULL, NULL, NULL),
(10, 11, 1, '2026-05-17 15:46:11', 111000.00, 'Menunggu', 'Tunai', '', '2026-05-17 08:46:11', NULL, 0, NULL, NULL, NULL),
(11, 11, 1, '2026-05-17 15:56:23', 16000.00, 'Menunggu', 'Tunai', '', '2026-05-17 08:56:23', NULL, 0, NULL, NULL, NULL),
(12, 11, 1, '2026-05-17 17:23:21', 16000.00, 'Menunggu', 'Tunai', '', '2026-05-17 10:23:21', NULL, 0, NULL, NULL, NULL),
(13, 11, 2, '2026-05-17 20:06:51', 499000.00, 'Dibatalkan', 'Transfer', '', '2026-05-17 13:06:51', '2026-05-18 05:22:36', 0, NULL, NULL, NULL),
(14, 12, 2, '2026-05-17 20:09:22', 4000.00, 'Selesai', 'QRIS', '', '2026-05-17 13:09:22', '2026-05-17 13:19:26', 0, NULL, NULL, NULL),
(15, 12, 1, '2026-05-18 08:22:00', 6000.00, 'Menunggu', 'Tunai', 'dingin yaa..', '2026-05-18 01:22:00', NULL, 0, NULL, NULL, NULL),
(16, 11, 2, '2026-05-18 11:28:43', 14000.00, 'Selesai', 'Tunai', '', '2026-05-18 04:28:43', '2026-05-18 05:22:01', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_rating`
--

CREATE TABLE `tb_rating` (
  `id_rating` int(11) NOT NULL,
  `id_order` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_toko` int(11) DEFAULT NULL,
  `rating_toko` tinyint(1) NOT NULL DEFAULT 5,
  `ulasan` text DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_rating`
--

INSERT INTO `tb_rating` (`id_rating`, `id_order`, `id_user`, `id_toko`, `rating_toko`, `ulasan`, `created`, `deleted`) VALUES
(1, 8, 11, 1, 5, 'ok\n💰 Worth It', '2026-05-17 08:50:17', 0),
(2, 14, 12, 2, 3, 'baik', '2026-05-17 13:20:02', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tb_rating_menu`
--

CREATE TABLE `tb_rating_menu` (
  `id_rating_menu` int(11) NOT NULL,
  `id_rating` int(11) NOT NULL,
  `id_menu` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_rating_menu`
--

INSERT INTO `tb_rating_menu` (`id_rating_menu`, `id_rating`, `id_menu`, `rating`) VALUES
(1, 1, 1, 2),
(2, 1, 2, 3),
(3, 1, 4, 4),
(4, 2, 9, 3);

-- --------------------------------------------------------

--
-- Table structure for table `tb_toko`
--

CREATE TABLE `tb_toko` (
  `id_toko` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_toko` varchar(100) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `status_toko` enum('buka','tutup') NOT NULL DEFAULT 'buka'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_toko`
--

INSERT INTO `tb_toko` (`id_toko`, `id_user`, `nama_toko`, `created`, `updated`, `deleted`, `deleted_at`, `status_toko`) VALUES
(1, 13, 'Toko kantin', '2026-05-17 01:17:55', '2026-05-17 01:17:55', 0, NULL, 'buka'),
(2, 6, 'p', '2026-05-17 01:25:55', '2026-05-18 13:55:53', 0, NULL, 'buka');

-- --------------------------------------------------------

--
-- Table structure for table `tb_user`
--

CREATE TABLE `tb_user` (
  `id_user` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','penjual','pembeli') NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` tinyint(4) NOT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_user`
--

INSERT INTO `tb_user` (`id_user`, `username`, `nama_lengkap`, `email`, `foto`, `password`, `role`, `created`, `updated`, `deleted`, `deleted_at`) VALUES
(4, 'akupintar', NULL, 'aku@gmail.com', NULL, '$2y$10$Holy1Vs/Cq8TbK6gZtaT4OT7QSliyJunsmKGoq.3KMoaCC7lCowTO', 'pembeli', '2026-05-04 05:51:55', '2026-05-04 05:51:55', 0, NULL),
(5, 'raniaran', NULL, 'rania@gmail.com', NULL, '$2y$10$AnJx56HImmNjBTBC4uBmueh9tMFl9UvfXeo3gqnLZvnjfC.bm52jG', 'pembeli', '2026-05-05 00:56:41', '2026-05-05 00:56:41', 0, NULL),
(6, 'pep', NULL, 'penjual@gmail.com', NULL, '$2y$10$5LEtwpxlCxqm4/xhwL3mMOtfXd3EGkWVMAbbW65rF8ihhvXiJDPHS', 'penjual', '2026-05-18 10:13:28', '2026-05-18 10:13:28', 0, NULL),
(7, 'admina', NULL, 'admin@gmail.com', NULL, '$2y$10$An8Yxc5.COESH61WRXfVvuUdd3wWgfqbUKlsLjODuJDJ1jKBy7YSa', 'admin', '2026-05-05 02:12:52', '2026-05-05 02:12:52', 0, NULL),
(10, 'penjualx', NULL, 'p@gmail.com', NULL, '$2y$10$fl/u0lCYCMBAtN4u.mxOWupWBgt/MMMMt2pKXUjb7cSqPANKojNTK', 'pembeli', '2026-05-05 06:40:34', '2026-05-05 06:40:34', 0, NULL),
(11, 'raniaii', NULL, 'ranianuril@ail.com', NULL, '$2y$10$/1qCcjI/.oFdcO0aXqhBm.OaZUcLNXdo0go.CU6ueJnd/lO2Vmdo6', 'pembeli', '2026-05-17 06:25:06', '2026-05-17 06:25:06', 0, NULL),
(12, 'rania', NULL, 'r@gmail.com', NULL, '$2y$10$gZfV6YS4p4gSYIio3XAY7.eebh/V4lKQH52UZPl44h1UHaSxbtU/.', 'pembeli', '2026-05-18 03:54:44', '2026-05-18 03:54:44', 0, NULL),
(13, 'kantin', NULL, 'kantin@gmail.com', NULL, '$2y$10$zcvbrPyqTY2p7FS05QcVjO2WR/.fIO.D7dBJwkcM4MnOxE5z1X1Ea', 'penjual', '2026-05-17 01:17:55', '2026-05-17 01:17:55', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_detail_order`
--
ALTER TABLE `tb_detail_order`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_order` (`id_order`),
  ADD KEY `id_menu` (`id_menu`);

--
-- Indexes for table `tb_keranjang`
--
ALTER TABLE `tb_keranjang`
  ADD PRIMARY KEY (`id_keranjang`);

--
-- Indexes for table `tb_menu`
--
ALTER TABLE `tb_menu`
  ADD PRIMARY KEY (`id_menu`),
  ADD KEY `fk_menu_toko` (`id_toko`);

--
-- Indexes for table `tb_order`
--
ALTER TABLE `tb_order`
  ADD PRIMARY KEY (`id_order`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tb_rating`
--
ALTER TABLE `tb_rating`
  ADD PRIMARY KEY (`id_rating`),
  ADD UNIQUE KEY `uq_order_rating` (`id_order`,`id_user`);

--
-- Indexes for table `tb_rating_menu`
--
ALTER TABLE `tb_rating_menu`
  ADD PRIMARY KEY (`id_rating_menu`);

--
-- Indexes for table `tb_toko`
--
ALTER TABLE `tb_toko`
  ADD PRIMARY KEY (`id_toko`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_detail_order`
--
ALTER TABLE `tb_detail_order`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `tb_keranjang`
--
ALTER TABLE `tb_keranjang`
  MODIFY `id_keranjang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_menu`
--
ALTER TABLE `tb_menu`
  MODIFY `id_menu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tb_order`
--
ALTER TABLE `tb_order`
  MODIFY `id_order` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tb_rating`
--
ALTER TABLE `tb_rating`
  MODIFY `id_rating` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_rating_menu`
--
ALTER TABLE `tb_rating_menu`
  MODIFY `id_rating_menu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_toko`
--
ALTER TABLE `tb_toko`
  MODIFY `id_toko` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_menu`
--
ALTER TABLE `tb_menu`
  ADD CONSTRAINT `fk_menu_toko` FOREIGN KEY (`id_toko`) REFERENCES `tb_toko` (`id_toko`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
