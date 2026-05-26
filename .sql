-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 26, 2026 at 07:20 AM
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
(1, 1, 1, 1, 8000.00, 8000.00, '2026-05-21 06:56:44', NULL, 0, NULL),
(2, 2, 11, 1, 5000.00, 5000.00, '2026-05-21 06:59:10', NULL, 0, NULL),
(3, 3, 8, 5, 1000.00, 5000.00, '2026-05-22 00:57:51', NULL, 0, NULL),
(4, 3, 3, 1, 5000.00, 5000.00, '2026-05-22 00:57:51', NULL, 0, NULL),
(5, 4, 4, 1, 5000.00, 5000.00, '2026-05-22 01:00:14', NULL, 0, NULL),
(6, 5, 43, 1, 3000.00, 3000.00, '2026-05-22 01:00:23', NULL, 0, NULL),
(7, 6, 8, 1, 1000.00, 1000.00, '2026-05-22 05:08:31', NULL, 0, NULL),
(8, 7, 68, 1, 3000.00, 3000.00, '2026-05-25 05:20:59', NULL, 0, NULL),
(9, 7, 69, 1, 3000.00, 3000.00, '2026-05-25 05:20:59', NULL, 0, NULL),
(10, 8, 67, 1, 5000.00, 5000.00, '2026-05-26 00:31:35', NULL, 0, NULL),
(11, 9, 52, 1, 6000.00, 6000.00, '2026-05-26 00:31:58', NULL, 0, NULL),
(12, 10, 1, 1, 8000.00, 8000.00, '2026-05-26 00:32:17', NULL, 0, NULL),
(13, 11, 61, 1, 3000.00, 3000.00, '2026-05-26 00:32:44', NULL, 0, NULL),
(14, 12, 3, 3, 5000.00, 15000.00, '2026-05-26 00:32:57', NULL, 0, NULL),
(15, 12, 1, 7, 8000.00, 56000.00, '2026-05-26 00:32:57', NULL, 0, NULL),
(16, 13, 8, 1, 1000.00, 1000.00, '2026-05-26 00:39:52', NULL, 0, NULL),
(17, 13, 3, 1, 5000.00, 5000.00, '2026-05-26 00:39:52', NULL, 0, NULL),
(18, 13, 1, 1, 8000.00, 8000.00, '2026-05-26 00:39:52', NULL, 0, NULL),
(19, 14, 1, 2, 8000.00, 16000.00, '2026-05-26 00:42:29', NULL, 0, NULL),
(20, 15, 80, 1, 2000.00, 2000.00, '2026-05-26 00:45:15', NULL, 0, NULL),
(21, 16, 53, 1, 4000.00, 4000.00, '2026-05-26 01:12:08', NULL, 0, NULL),
(22, 17, 24, 1, 8000.00, 8000.00, '2026-05-26 01:12:24', NULL, 0, NULL),
(23, 18, 1, 1, 8000.00, 8000.00, '2026-05-26 01:37:11', NULL, 0, NULL),
(24, 19, 1, 1, 8000.00, 8000.00, '2026-05-26 01:37:21', NULL, 0, NULL),
(25, 20, 1, 1, 8000.00, 8000.00, '2026-05-26 01:37:27', NULL, 0, NULL),
(26, 21, 8, 1, 1000.00, 1000.00, '2026-05-26 01:37:33', NULL, 0, NULL),
(27, 22, 1, 1, 8000.00, 8000.00, '2026-05-26 02:23:38', NULL, 0, NULL),
(28, 23, 48, 4, 7000.00, 28000.00, '2026-05-26 04:43:14', NULL, 0, NULL),
(29, 24, 69, 5, 3000.00, 15000.00, '2026-05-26 05:03:38', NULL, 0, NULL);

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
(2, 7, 63, 1),
(3, 7, 56, 1),
(4, 7, 50, 1),
(6, 13, 5, 1),
(31, 17, 67, 1),
(33, 17, 68, 1),
(91, 18, 80, 2);

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
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_toko` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_menu`
--

INSERT INTO `tb_menu` (`id_menu`, `nama_menu`, `harga`, `stok`, `kategori`, `deskripsi`, `foto`, `status`, `deleted`, `deleted_at`, `created`, `updated`, `id_toko`) VALUES
(9, 'Dimsum Mentai Mini', 5000, 50, 'Makanan Ringan', 'Dimsum mentai mini dengan isian ayam dan wortel. 1 porsi berisi 4 dimsum.', '6a0e7f0bf10f5.jpg', 'aktif', 0, NULL, '2026-05-21 10:42:03', '2026-05-21 03:42:26', 3),
(10, 'Nasi Ayam Goreng', 8000, 50, 'Makanan Berat', 'Nasi hangat dengan ayam goreng gurih dan renyah, cocok untuk menu makan yang mengenyangkan dan lezat.', '6a0e7fd8cb85c.jpeg', 'aktif', 0, NULL, '2026-05-21 10:45:28', '2026-05-21 03:47:08', 2),
(11, 'Tahu Bakso', 5000, 54, 'Makanan Ringan', 'Tahu bakso dengan perpaduan tahu lembut dan isian bakso gurih yang kenyal, cocok sebagai camilan atau lauk yang enak dan mengenyangkan. Satu porsi berisi 3 tahu bakso.', '6a0e8066969fd.jpg', 'aktif', 0, NULL, '2026-05-21 10:47:50', '2026-05-21 06:59:10', 3),
(12, 'Mie Instan', 5000, 65, 'Makanan Ringan', 'Mie instan hangat dengan rasa gurih dan lezat, cocok untuk makan praktis dan mengenyangkan.', '6a0e80b5ad5ad.jpeg', 'aktif', 0, NULL, '2026-05-21 10:49:09', '2026-05-21 03:49:09', 2),
(13, 'Tahu Kress', 5000, 60, 'Makanan Ringan', 'Tahu kress yang renyah bisa custom rasa bumbu. Bumbu balado, bbq, keju, balado cabe hijau, jagung manis. Rasa bumbu tulis pada notes.', '6a0e80e67078d.jpg', 'aktif', 0, NULL, '2026-05-21 10:49:31', '2026-05-21 03:49:58', 3),
(14, 'Jamur crispy', 5000, 60, 'Makanan Ringan', 'Jamur goreng tepung crispy yang renyah bisa custom rasa bumbu. Bumbu balado, bbq, keju, balado cabe hijau, jagung manis. Rasa bumbu tulis pada notes.', '6a0e812df3b6e.jpg', 'aktif', 0, NULL, '2026-05-21 10:51:09', '2026-05-21 03:51:09', 3),
(15, 'Mie Telur', 8000, 50, 'Makanan Ringan', 'ie telur hangat dengan topping pilihan yang menggugah selera dan mengenyangkan.', '6a0e812f276ef.jpeg', 'aktif', 0, NULL, '2026-05-21 10:51:11', '2026-05-21 03:51:35', 2),
(16, 'Usus Crunchy', 5000, 60, 'Makanan Ringan', 'Usus goreng yang crunchy bisa custom rasa bumbu. Bumbu balado, bbq, keju, balado cabe hijau, jagung manis. Rasa bumbu tulis pada notes.', '6a0e8194c6beb.jpg', 'aktif', 0, NULL, '2026-05-21 10:52:52', '2026-05-21 03:52:52', 3),
(17, 'Pop Mie', 7000, 23, 'Makanan Ringan', 'Pop Mie adalah mie instan cup yang praktis, disajikan hangat dengan kuah gurih dan rasa yang nikmat.', '6a0e81b3b17e6.jpeg', 'aktif', 0, NULL, '2026-05-21 10:53:23', '2026-05-21 03:53:37', 2),
(18, 'Pentol cilok pedes', 5000, 55, 'Makanan Ringan', 'Pentol cilok pedas dengan tekstur kenyal yang disiram bumbu pedas gurih, cocok untuk camilan santai yang bikin nagih dan menggoyang lidah.', '6a0e82b21b9de.jpg', 'aktif', 0, NULL, '2026-05-21 10:57:38', '2026-05-21 06:41:24', 3),
(19, 'Nasi 4T', 7000, 44, 'Makanan Sehat', 'Perpaduan nasi putih dengan lauk telur, timun, tempe, dan tahu yang praktis, enak, dan bikin kenyang.', '6a0e82c36560e.png', 'aktif', 0, NULL, '2026-05-21 10:57:55', '2026-05-21 03:57:55', 2),
(20, 'Es Teh', 3000, 55, 'Minuman Ringan', 'Teh dingin dengan tambahan gula, menyegarkan dan cocok untuk melepas dahaga.', '6a0e832ca3e72.jpeg', 'aktif', 0, NULL, '2026-05-21 10:59:40', '2026-05-21 03:59:40', 2),
(21, 'Pop Ice', 3000, 68, 'Minuman Ringan', 'Pop Ice minuman dingin yang praktis, memiliki banyak varian rasa dan cocok untuk melepas dahaga.', '6a0e8399f21bd.jpeg', 'aktif', 0, NULL, '2026-05-21 11:01:29', '2026-05-21 04:01:29', 2),
(22, 'Nasi Pecel', 6000, 20, 'Makanan Berat', 'Nasi dengan lauk, sayur, dan sambal kacang', '6a0e839a4d21f.jpg', 'aktif', 0, NULL, '2026-05-21 11:01:30', '2026-05-21 04:02:17', 5),
(23, 'Nasi Ayam Goreng', 8000, 20, 'Makanan Berat', 'Nasi dengan ayam goreng gurih dan renyah', '6a0e8421075b9.jpg', 'aktif', 0, NULL, '2026-05-21 11:03:45', '2026-05-21 04:03:45', 5),
(24, 'Nasi Ayam Geprek', 8000, 19, 'Makanan Berat', 'Nasi dengan ayam crispy dan sambal pedas', '6a0e846f1ce8f.jpg', 'aktif', 0, NULL, '2026-05-21 11:05:03', '2026-05-26 01:12:24', 5),
(25, 'Soto', 7000, 20, 'Makanan Berat', 'Sup khas Indonesia dengan kuah gurih dan rempah', '6a0e84e81ec91.jpg', 'aktif', 0, NULL, '2026-05-21 11:07:04', '2026-05-21 04:07:04', 5),
(26, 'Nasi Ayam Geprek', 8000, 60, 'Makanan Berat', 'Nasi ayam geprek sambal ijo dengan ayam crispy yang digeprek dan dilumuri sambal ijo pedas gurih, disajikan dengan nasi hangat yang bikin nagih di setiap suapan.', '6a0e85553a775.jpg', 'aktif', 0, NULL, '2026-05-21 11:08:53', '2026-05-21 06:37:19', 4),
(27, 'Es Teh', 3000, 20, 'Minuman Ringan', 'Teh dingin yang segar dan manis', '6a0e85588ecf5.jpg', 'aktif', 0, NULL, '2026-05-21 11:08:56', '2026-05-21 04:10:14', 5),
(28, 'Nasi Ayam Bakar', 8000, 60, 'Makanan Berat', 'Nasi ayam bakar dengan ayam berbumbu khas yang dibakar hingga harum dan meresap, disajikan bersama nasi hangat dan sambal yang menambah kenikmatan setiap suapan.', '6a0e858c66eed.jpg', 'aktif', 0, NULL, '2026-05-21 11:09:48', '2026-05-21 06:37:05', 4),
(29, 'Es Jeruk', 3000, 20, 'Minuman Ringan', 'Minuman jeruk dingin yang manis dan menyegarkan', '6a0e859bc6851.jpg', 'aktif', 0, NULL, '2026-05-21 11:10:03', '2026-05-21 04:10:03', 5),
(30, 'Nasi Campur Telur', 8000, 50, 'Makanan Berat', 'Nasi campur telur dengan nasi hangat, telur lezat, dan pelengkap sederhana yang gurih, praktis, dan mengenyangkan untuk menu sehari-hari.', '6a0e85c16e534.jpg', 'aktif', 0, NULL, '2026-05-21 11:10:41', '2026-05-21 06:37:48', 4),
(31, 'Kopi', 3000, 20, 'Minuman Ringan', 'Minuman dengan rasa khas dan aroma kuat', '6a0e85efd6ebc.jpg', 'aktif', 0, NULL, '2026-05-21 11:11:27', '2026-05-26 00:49:26', 5),
(32, 'Nasi Pecel', 6000, 65, 'Makanan Berat', 'Nasi pecel dengan aneka sayuran segar yang disiram bumbu kacang khas, gurih, pedas, dan nikmat, cocok untuk menu sederhana yang mengenyangkan.', '6a0e85f939503.jpg', 'aktif', 0, NULL, '2026-05-21 11:11:37', '2026-05-21 06:38:25', 4),
(33, 'Es Jeruk /Jeruk Hangat', 3000, 70, 'Minuman Ringan', 'Tulis keterangan di notes saat checkout ingin order es jeruk/jeruk hangat. Es jeruk / jeruk hangat dengan rasa manis dan asam yang segar, bisa dinikmati dingin untuk menyegarkan atau hangat untuk menghangatkan tubuh.', '6a0e866636e1c.jpg', 'aktif', 0, NULL, '2026-05-21 11:13:26', '2026-05-21 06:36:23', 4),
(34, 'Es Teh / Teh Hangat', 3000, 75, 'Minuman Ringan', 'Tulis keterangan di notes saat checkout ingin order es teh/teh hangat. Es Teh / Teh Hangat dengan rasa teh yang ringan, segar, dan pas di lidah. Bisa dinikmati dingin untuk melepas dahaga atau hangat untuk menemani suasana santai.', '6a0e86ae31fdf.jpg', 'aktif', 0, NULL, '2026-05-21 11:14:38', '2026-05-21 06:35:32', 4),
(35, 'Kopi Hitam', 3000, 75, 'Minuman Ringan', 'Kopi hitam hangat dengan rasa kuat dan aroma khas kopi yang pekat, cocok untuk menemani waktu santai atau menambah semangat di hari yang sibuk.', '6a0e86d02e018.jpg', 'aktif', 0, NULL, '2026-05-21 11:15:12', '2026-05-21 06:34:28', 4),
(36, 'Nasi Ayam Geprek', 8000, 50, 'Makanan Berat', 'Nasi ayam geprek dengan ayam crispy yang digeprek bersama sambal pedas khas, disajikan hangat dengan nasi putih pulen yang bikin kenyang dan nagih.', '6a0e8d0a82dad.jpg', 'aktif', 0, NULL, '2026-05-21 11:41:46', '2026-05-21 06:32:02', 8),
(37, 'Nasi Ayam Geprek', 8000, 50, 'Makanan Berat', '', '6a0e8d2bd7aab.jpg', 'nonaktif', 1, '2026-05-21 11:42:25', '2026-05-21 11:42:19', '2026-05-21 04:42:25', 8),
(38, 'Nasi Soto Ayam', 7000, 60, 'Makanan Berat', 'Soto ayam hangat dengan kuah gurih beraroma rempah, berisi suwiran ayam lembut dan pelengkap yang membuat rasanya semakin nikmat dan menghangatkan.', '6a0e8d53f024e.jpg', 'aktif', 0, NULL, '2026-05-21 11:42:59', '2026-05-21 06:32:14', 8),
(39, 'Es Jeruk /Jeruk Hangat', 3000, 70, 'Minuman Ringan', 'Tulis keterangan di notes saat chechkout ingin order es jeruk/jeruk hangat. Es jeruk / jeruk hangat dengan perpaduan rasa manis dan asam yang segar, bisa dinikmati dingin untuk menyegarkan atau hangat untuk menghangatkan tubuh.', '6a0e8d7ddd91b.jpg', 'aktif', 0, NULL, '2026-05-21 11:43:41', '2026-05-21 06:30:44', 8),
(40, 'Es Teh / Teh Hangat', 3000, 75, 'Minuman Ringan', 'Tulis keterangan di notes saat checkout ingin order es teh/teh hangat. Es teh / teh hangat dengan rasa teh yang segar dan pas, bisa dinikmati dingin untuk melepas dahaga atau hangat untuk menemani suasana santai.', '6a0e8dc02e883.jpg', 'aktif', 0, NULL, '2026-05-21 11:44:48', '2026-05-21 06:29:59', 8),
(41, 'Jahe Hangat', 3000, 55, 'Minuman Sehat', 'Jahe hangat dengan aroma rempah yang khas dan rasa hangat menyegarkan, cocok dinikmati untuk menemani waktu santai dan menghangatkan badan.', '6a0e8e7473700.jpg', 'aktif', 0, NULL, '2026-05-21 11:47:48', '2026-05-21 06:29:40', 8),
(42, 'Es Jeruk/Panas', 3000, 41, 'Minuman Ringan', 'Silakan tulis di catatan apakah ingin disajikan es atau panas.', '6a0e9013434ea.jpeg', 'aktif', 0, NULL, '2026-05-21 11:54:43', '2026-05-21 04:54:43', 7),
(43, 'Es Teh/Panas', 3000, 99, 'Minuman Ringan', 'Mohon cantumkan di catatan apakah ingin es teh atau teh panas.', '6a0e90e263b9c.jpeg', 'aktif', 0, NULL, '2026-05-21 11:56:57', '2026-05-22 01:00:23', 7),
(44, 'Kopi Hitam', 3000, 66, 'Minuman Ringan', '“Minuman kopi hitam dengan rasa kuat dan pahit yang khas, cocok untuk penikmat kopi sederhana.', '6a0e91379fa34.jpeg', 'aktif', 0, NULL, '2026-05-21 11:59:35', '2026-05-21 04:59:35', 7),
(45, 'Kopi Susu', 4000, 20, 'Minuman Ringan', 'Minuman kopi dengan campuran susu yang lembut, menghasilkan rasa manis dan creamy yang seimbang.', '6a0e9171d1b27.jpeg', 'aktif', 0, NULL, '2026-05-21 12:00:33', '2026-05-21 05:00:33', 7),
(46, 'Soto', 7000, 17, 'Makanan Berat', 'Soto berkuah hangat dengan rasa gurih dan rempah yang khas, disajikan dengan isian daging atau ayam serta pelengkap yang lezat.', '6a0e91f48f731.jpeg', 'aktif', 0, NULL, '2026-05-21 12:02:44', '2026-05-21 05:02:44', 7),
(47, 'Pentol', 500, 100, 'Makanan Ringan', 'Pentol dengan tekstur kenyal dan rasa gurih, tersedia dengan pilihan harga yang fleksibel sesuai porsi.', '6a0e92ca13aa9.jpeg', 'aktif', 0, NULL, '2026-05-21 12:06:18', '2026-05-21 05:10:49', 7),
(48, 'Nasi Soto Ayam', 7000, 71, 'Makanan Berat', 'Soto ayam hangat dengan kuah gurih yang kaya rempah, disajikan dengan suwiran ayam dan pelengkap yang bikin makan jadi lebih nikmat.', '6a0e930a46b0b.jpg', 'aktif', 0, NULL, '2026-05-21 12:07:22', '2026-05-26 04:43:14', 6),
(49, 'Nasi Ayam Geprek', 8000, 65, 'Makanan Berat', 'Ayam geprek crispy dengan sambal pedas yang nampol dan bumbu meresap, disajikan hangat cocok untuk pecinta pedas sejati.', '6a0e935b895cc.jpg', 'aktif', 0, NULL, '2026-05-21 12:08:43', '2026-05-21 06:28:17', 6),
(50, 'Tahu Walik', 1000, 55, 'Makanan Ringan', 'Camilan tahu walik dengan tekstur crispy dan isian gurih yang lezat.', '6a0e93943f02d.jpeg', 'aktif', 0, NULL, '2026-05-21 12:09:40', '2026-05-21 05:09:40', 7),
(51, 'Tahu Walik', 1000, 55, 'Makanan Ringan', 'Camilan tahu walik dengan tekstur crispy dan isian gurih yang lezat.', '6a0e939ec5e92.jpeg', 'nonaktif', 1, '2026-05-21 12:12:56', '2026-05-21 12:09:50', '2026-05-21 05:12:56', 7),
(52, 'Nasi Pecel', 6000, 70, 'Makanan Berat', 'Nasi pecel dengan sayuran segar dan siraman bumbu kacang khas yang gurih pedas, disajikan lengkap untuk menu sederhana yang bikin nagih.', '6a0e93ab9ccd9.jpg', 'aktif', 0, NULL, '2026-05-21 12:10:03', '2026-05-26 00:55:24', 6),
(53, 'Chocolatos', 4000, 54, 'Makanan Berat', 'Chocolatos dengan berbagai varian rasa yang manis, creamy, dan nikmat, cocok dinikmati hangat maupun dingin untuk menemani harimu.', '6a0e93dcb39b0.jpg', 'aktif', 0, NULL, '2026-05-21 12:10:52', '2026-05-26 01:12:08', 6),
(54, 'Es Jeruk', 3000, 100, 'Makanan Ringan', 'Es jeruk segar dengan perpaduan rasa manis dan asam yang menyegarkan, cocok dinikmati kapan saja untuk melepas dahaga.', '6a0e940cece1e.jpg', 'aktif', 0, NULL, '2026-05-21 12:11:40', '2026-05-21 06:26:28', 6),
(55, 'Es Teh', 3000, 100, 'Minuman Ringan', 'Es teh manis segar dengan rasa teh yang pas dan menyegarkan, cocok menjadi teman menikmati berbagai menu favoritmu.', '6a0e944d0da3e.jpg', 'aktif', 0, NULL, '2026-05-21 12:12:45', '2026-05-21 06:26:00', 6),
(56, 'Mie Goreng/Kuah', 5000, 28, 'Makanan Berat', 'Mie goreng lezat dengan perpaduan rasa gurih yang nikmat dan aroma menggugah selera.', '6a0e945431570.jpeg', 'aktif', 0, NULL, '2026-05-21 12:12:52', '2026-05-21 05:12:52', 7),
(59, 'Kopi Hitam', 3000, 20, 'Minuman Ringan', 'Minuman dengan rasa khas dan aroma kuat', '6a0ea45934813.jpg', 'aktif', 0, NULL, '2026-05-21 13:21:13', '2026-05-21 06:21:13', 10),
(61, 'Es Jeruk', 3000, 19, 'Minuman Ringan', 'Minuman dingin yang manis dan menyegarkan', '6a0ea4a0f145a.jpg', 'aktif', 0, NULL, '2026-05-21 13:22:24', '2026-05-26 00:32:44', 10),
(62, 'Es Teh', 3000, 20, 'Minuman Ringan', 'Teh dingin yang segar dan manis', '6a0ea4d34939a.jpg', 'aktif', 0, NULL, '2026-05-21 13:23:15', '2026-05-21 06:23:15', 10),
(64, 'Nasi Pecel', 6000, 20, 'Makanan Berat', 'Nasi dengan lauk, sayur, dan saus kacang', '6a0ea510460f1.jpg', 'aktif', 0, NULL, '2026-05-21 13:24:16', '2026-05-21 06:24:16', 10),
(65, 'Nasi Soto Ayam', 7000, 20, 'Makanan Berat', 'Nasi dengan soto ayam yang gurih', '6a0ea57aa1061.jpg', 'aktif', 0, NULL, '2026-05-21 13:26:02', '2026-05-21 06:26:02', 10),
(66, 'Mie Goreng', 5000, 20, 'Makanan Berat', 'Mie dengan bumbu yang gurih dan lezat', '6a0ea5d20b5a8.jpg', 'aktif', 0, NULL, '2026-05-21 13:27:30', '2026-05-21 06:27:30', 10),
(67, 'Mie Rebus', 5000, 19, 'Makanan Berat', 'Mie berkuah hangat dengan rasa gurih dan nikmat', '6a0ea64f2ea08.jpg', 'aktif', 0, NULL, '2026-05-21 13:29:35', '2026-05-26 00:31:35', 10),
(68, 'Le Minerale', 3000, 29, 'Minuman Sehat', 'Dingin/Biasa tulis di catatan', '6a0eab45a0148.jpeg', 'aktif', 0, NULL, '2026-05-21 13:50:45', '2026-05-25 05:20:59', 6),
(69, 'Minuman RHS', 3000, 0, 'Minuman Sehat', 'rahasia guys, kasih cttan dingin/biasa', '6a14f01b011bd.jpeg', 'aktif', 0, NULL, '2026-05-21 13:52:38', '2026-05-26 05:03:38', 6),
(70, 'Floridina', 3500, 23, 'Minuman Ringan', '', '6a0eac1153932.jpeg', 'aktif', 0, NULL, '2026-05-21 13:54:09', '2026-05-21 06:55:23', 6),
(71, 'Nestle Pure Life', 3000, 23, 'Minuman Sehat', 'Dingin/Biasa tulis di catatan', '6a0ead28e20d0.jpeg', 'nonaktif', 1, '2026-05-21 13:59:25', '2026-05-21 13:58:48', '2026-05-21 06:59:25', 6),
(72, 'Nestle Pure Life', 3000, 23, 'Minuman Sehat', '', '6a0eada6ec8a2.jpeg', 'aktif', 0, NULL, '2026-05-21 14:00:54', '2026-05-21 07:00:54', 7),
(73, 'Susu Hilo', 4000, 37, 'Minuman Sehat', 'Hangat/Dingin tulis di catatan', '6a0eae359f6c0.jpeg', 'aktif', 0, NULL, '2026-05-21 14:03:17', '2026-05-21 07:03:17', 7),
(74, 'Milku', 3500, 10, 'Minuman Sehat', '', '6a0eaf9b246e3.jpeg', 'aktif', 0, NULL, '2026-05-21 14:09:15', '2026-05-21 07:09:15', 2),
(75, 'Nipis Madu', 5000, 9, 'Minuman Ringan', '', '6a0eafe1b25d0.jpeg', 'aktif', 0, NULL, '2026-05-21 14:10:25', '2026-05-21 07:10:25', 2),
(76, 'Pure Life', 3000, 27, 'Minuman Sehat', '', '6a0eb00ad7b99.jpeg', 'aktif', 0, NULL, '2026-05-21 14:11:06', '2026-05-21 07:11:06', 2),
(77, 'Es Jeruk', 3000, 20, 'Minuman Ringan', 'Minuman dingin yang manis  dan menyegarkan', '6a0eb0b20bccb.jpg', 'aktif', 0, NULL, '2026-05-21 14:13:54', '2026-05-21 07:13:54', 9),
(78, 'Es Teh', 3000, 20, 'Minuman Ringan', 'Teh dingin yang segar dan manis', '6a0eb0db04401.jpg', 'aktif', 0, NULL, '2026-05-21 14:14:35', '2026-05-21 07:14:35', 9),
(79, 'Pop Mie', 7000, 20, 'Makanan Berat', 'Mie instan dalam cup yang praktis dan cepat disajikan', '6a0eb16eb703b.jpg', 'aktif', 0, NULL, '2026-05-21 14:17:02', '2026-05-21 07:17:21', 9),
(80, 'Gabin', 2000, 14, 'Makanan Ringan', 'enaq euyyy, tapenya yummy', '6a0eb1b91c030.jpeg', 'aktif', 0, NULL, '2026-05-21 14:18:17', '2026-05-26 00:45:15', 5),
(81, 'Nasi Ayam Penyet', 8000, 20, 'Makanan Berat', 'Nasi dengan ayam penyet dan sambal pedas gurih', '6a0eb1e0989ff.jpg', 'aktif', 0, NULL, '2026-05-21 14:18:56', '2026-05-21 07:18:56', 9),
(82, 'Mie Ayam', 8000, 20, 'Makanan Berat', 'Mie dengan potongan ayam berbumbu gurih dan lezat', '6a0eb2273a852.jpg', 'aktif', 0, NULL, '2026-05-21 14:20:07', '2026-05-21 07:20:07', 9),
(83, 'Ultra Milk strobery', 6500, 20, 'Minuman Sehat', '', '6a0eb2672f6a1.jpeg', 'aktif', 0, NULL, '2026-05-21 14:21:11', '2026-05-21 07:21:11', 4);

-- --------------------------------------------------------

--
-- Table structure for table `tb_order`
--

CREATE TABLE `tb_order` (
  `id_order` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_toko` int(11) DEFAULT NULL,
  `id_penjual` int(11) DEFAULT NULL,
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

INSERT INTO `tb_order` (`id_order`, `id_user`, `id_toko`, `id_penjual`, `tanggal_order`, `total_harga`, `status_order`, `metode_pembayaran`, `catatan`, `created`, `updated`, `deleted`, `deleted_at`, `rating`, `ulasan`) VALUES
(1, 13, 1, 26, '2026-05-21 13:56:44', 8000.00, 'CLOSED', 'Tunai', '', '2026-05-21 06:56:44', '2026-05-26 04:38:42', 0, NULL, NULL, NULL),
(2, 13, 3, NULL, '2026-05-21 13:59:10', 5000.00, 'Menunggu', 'Tunai', '', '2026-05-21 06:59:10', NULL, 0, NULL, NULL, NULL),
(3, 15, 1, 26, '2026-05-22 07:57:51', 10000.00, 'CLOSED', 'Tunai', 'saus dipisah', '2026-05-22 00:57:51', '2026-05-26 04:38:42', 0, NULL, NULL, NULL),
(4, 15, 1, 26, '2026-05-22 08:00:14', 5000.00, 'CLOSED', 'Tunai', '', '2026-05-22 01:00:14', '2026-05-26 04:38:42', 0, NULL, NULL, NULL),
(5, 15, 7, NULL, '2026-05-22 08:00:23', 3000.00, 'Menunggu', 'Tunai', '', '2026-05-22 01:00:23', NULL, 0, NULL, NULL, NULL),
(6, 15, 1, 26, '2026-05-22 12:08:31', 1000.00, 'CLOSED', 'Tunai', '', '2026-05-22 05:08:31', '2026-05-26 04:38:42', 0, NULL, NULL, NULL),
(7, 15, 6, 8, '2026-05-25 12:20:59', 6000.00, 'Selesai', 'Tunai', 'xs', '2026-05-25 05:20:59', '2026-05-26 04:21:40', 0, NULL, NULL, NULL),
(8, 19, 10, NULL, '2026-05-26 07:31:35', 5000.00, 'Menunggu', 'Tunai', 'kuahnya dikit aja', '2026-05-26 00:31:35', NULL, 0, NULL, NULL, NULL),
(9, 17, 6, 8, '2026-05-26 07:31:58', 6000.00, 'Dibatalkan', 'Tunai', 'pakai daging kucing ya', '2026-05-26 00:31:58', '2026-05-26 04:21:40', 0, NULL, NULL, NULL),
(10, 19, 1, 26, '2026-05-26 07:32:17', 8000.00, 'CLOSED', 'Tunai', 'kasih tulang', '2026-05-26 00:32:17', '2026-05-26 04:38:42', 0, NULL, NULL, NULL),
(11, 15, 10, NULL, '2026-05-26 07:32:44', 3000.00, 'Menunggu', 'Tunai', 'hangat', '2026-05-26 00:32:44', NULL, 0, NULL, NULL, NULL),
(12, 15, 1, 26, '2026-05-26 07:32:57', 71000.00, 'CLOSED', 'Tunai', '', '2026-05-26 00:32:57', '2026-05-26 04:38:42', 0, NULL, NULL, NULL),
(13, 17, 1, 26, '2026-05-26 07:39:52', 14000.00, 'CLOSED', 'Tunai', 'gak usah pakai ceker', '2026-05-26 00:39:52', '2026-05-26 04:38:42', 0, NULL, NULL, NULL),
(14, 18, 1, 26, '2026-05-26 07:42:29', 16000.00, 'CLOSED', 'Tunai', 'cekernya jangan pedes, jari cekernya harus 5', '2026-05-26 00:42:29', '2026-05-26 04:38:42', 0, NULL, NULL, NULL),
(15, 18, 5, NULL, '2026-05-26 07:45:15', 2000.00, 'Selesai', 'Tunai', '', '2026-05-26 00:45:15', '2026-05-26 00:46:01', 0, NULL, NULL, NULL),
(16, 20, 6, 8, '2026-05-26 08:12:08', 4000.00, 'Menunggu', 'Tunai', 'rasa matcha', '2026-05-26 01:12:08', '2026-05-26 04:21:40', 0, NULL, NULL, NULL),
(17, 20, 5, NULL, '2026-05-26 08:12:24', 8000.00, 'Selesai', 'Tunai', 'sambelnya dipisah', '2026-05-26 01:12:24', '2026-05-26 01:14:59', 0, NULL, NULL, NULL),
(18, 15, 1, 26, '2026-05-26 08:37:11', 8000.00, 'CLOSED', 'Tunai', '', '2026-05-26 01:37:11', '2026-05-26 04:38:42', 0, NULL, NULL, NULL),
(19, 15, 1, 26, '2026-05-26 08:37:21', 8000.00, 'CLOSED', 'Tunai', '', '2026-05-26 01:37:21', '2026-05-26 04:38:42', 0, NULL, NULL, NULL),
(20, 15, 1, 26, '2026-05-26 08:37:27', 8000.00, 'CLOSED', 'Tunai', '', '2026-05-26 01:37:27', '2026-05-26 04:38:42', 0, NULL, NULL, NULL),
(21, 15, 1, 26, '2026-05-26 08:37:33', 1000.00, 'CLOSED', 'Tunai', '', '2026-05-26 01:37:33', '2026-05-26 04:38:42', 0, NULL, NULL, NULL),
(22, 18, 1, 26, '2026-05-26 09:23:38', 8000.00, 'CLOSED', 'Tunai', '', '2026-05-26 02:23:38', '2026-05-26 04:38:42', 0, NULL, NULL, NULL),
(23, 15, 6, NULL, '2026-05-26 11:43:14', 28000.00, 'Menunggu', 'Tunai', '', '2026-05-26 04:43:14', NULL, 0, NULL, NULL, NULL),
(24, 7, 6, NULL, '2026-05-26 12:03:38', 15000.00, 'Menunggu', 'Tunai', 'DINGIN', '2026-05-26 05:03:38', NULL, 0, NULL, NULL, NULL);

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
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_rating`
--

INSERT INTO `tb_rating` (`id_rating`, `id_order`, `id_user`, `id_toko`, `rating_toko`, `ulasan`, `created`, `deleted`) VALUES
(1, 4, 15, 1, 1, 'ada lalatnya', '2026-05-22 08:03:28', 0),
(2, 7, 15, 6, 5, 'Pedas Pas', '2026-05-25 12:21:28', 0),
(3, 12, 15, 1, 3, 'gak enak kayak m*ntah kucing 🤮🤢', '2026-05-26 07:43:14', 0),
(4, 17, 20, 5, 5, 'gercep', '2026-05-26 08:16:11', 0);

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

-- --------------------------------------------------------

--
-- Table structure for table `tb_riwayat_menu`
--

CREATE TABLE `tb_riwayat_menu` (
  `id_riwayat_menu` int(11) NOT NULL,
  `id_menu` int(11) DEFAULT NULL,
  `id_toko` int(11) DEFAULT NULL,
  `nama_menu` varchar(50) DEFAULT NULL,
  `harga` int(11) DEFAULT NULL,
  `stok` int(11) DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tgl_arsip` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tb_riwayat_menu`
--

INSERT INTO `tb_riwayat_menu` (`id_riwayat_menu`, `id_menu`, `id_toko`, `nama_menu`, `harga`, `stok`, `kategori`, `deskripsi`, `foto`, `status`, `deleted`, `deleted_at`, `created`, `updated`, `tgl_arsip`) VALUES
(1, 1, 1, 'Ceker Lava Tanpa Tulang', 8000, 16, 'Makanan Ringan', 'Ceker lava tanpa tulang dengan bumbu pedas gurih yang meresap hingga ke dalam, teksturnya empuk dan praktis dinikmati tanpa ribet. Cocok untuk pecinta pedas yang ingin sensasi “lava” di setiap gigitan.', '6a0e79df33e87.jpg', '', 0, NULL, '2026-05-21 10:19:59', '2026-05-26 02:23:38', '2026-05-26 10:46:22'),
(2, 2, 1, 'Soto Ayam Spesial', 8000, 55, 'Makanan Berat', 'Bukan soto biasa, isiannya komplit rasanya nagih.', '6a0e7a389d88a.jpg', '', 0, NULL, '2026-05-21 10:21:28', '2026-05-21 03:21:28', '2026-05-26 10:46:22'),
(3, 3, 1, 'Es Gamon', 5000, 46, 'Minuman Ringan', 'Perpaduan Matcha Strawberry Yang Bikin Jantung Dag Dig Dug tapi nagih.', '6a0e7b159d9ff.jpg', '', 0, NULL, '2026-05-21 10:25:09', '2026-05-26 00:42:56', '2026-05-26 10:46:22'),
(4, 4, 1, 'Es CLBK', 5000, 49, 'Minuman Ringan', 'Matcha susu bercampur boba boba yang sangat cocok nemenin kamu biar tambah happy.', '6a0e7b9aaf540.jpg', '', 0, NULL, '2026-05-21 10:27:22', '2026-05-22 01:00:14', '2026-05-26 10:46:22'),
(5, 5, 1, 'Es Friendzone', 5000, 55, 'Minuman Ringan', 'Perpaduan kopi yang nikmat dicampur es membuat harimu lebih bersemangat.', '6a0e7bfd2b403.jpg', '', 0, NULL, '2026-05-21 10:29:01', '2026-05-21 03:29:01', '2026-05-26 10:46:22'),
(6, 6, 1, 'Es Soria', 5000, 50, 'Minuman Ringan', 'Soda manis segar dengan sentuhan susu, ala soda gembira yagn bikin hari jadi warna warni.', '6a0e7c4b2059d.jpg', '', 1, '2026-05-22 07:55:23', '2026-05-21 10:30:19', '2026-05-22 00:55:23', '2026-05-26 10:46:22'),
(7, 7, 1, 'Sempol Ayam', 1000, 100, 'Makanan Ringan', 'Sempol ayam yang enak dan gurih. Harga di atas untuk sempol perbiji.', '6a0e7d193cee2.jpg', '', 1, '2026-05-21 10:34:13', '2026-05-21 10:33:45', '2026-05-21 03:34:13', '2026-05-26 10:46:22'),
(8, 8, 1, 'Sempol Ayam', 1000, 94, 'Makanan Ringan', 'Sempol ayam yang enak dan gurih. Harga di atas untuk sempol perbiji.', '6a0e7d1c1209d.jpg', '', 0, NULL, '2026-05-21 10:33:48', '2026-05-26 01:37:33', '2026-05-26 10:46:22'),
(9, 57, 1, 'Es Jomblo', 5000, 55, 'Minuman Ringan', 'Soda warna galaxy yang cantik & seger, cocok buat nemenin kamu yang happy walau single', '6a0ea36d4711e.jpg', '', 1, '2026-05-21 13:17:23', '2026-05-21 13:17:17', '2026-05-21 06:17:23', '2026-05-26 10:46:22'),
(10, 58, 1, 'Es HTS', 5000, 52, 'Minuman Ringan', 'Perpaduan matcha dan espresso yang bikin jantung dag dig dug tapi nagih.', '6a0ea40196eab.jpg', '', 0, NULL, '2026-05-21 13:19:45', '2026-05-21 06:19:57', '2026-05-26 10:46:22'),
(11, 60, 1, 'Es PHP', 5000, 64, 'Minuman Ringan', 'Perpaduan Americano dicampur dengan susu yang bikin kamu tambah mood menjalani keseharianmu.', '6a0ea48ae8e1c.jpg', '', 0, NULL, '2026-05-21 13:21:41', '2026-05-21 06:22:02', '2026-05-26 10:46:22'),
(12, 63, 1, 'Es Toxic', 5000, 66, 'Minuman Ringan', 'Perpaduan Nescafe yang pahit dengan Milo yang manis menciptakan rasa unik pahit tapi tetap bikin nagih cocok untuk yang lagi menikamti \"hubungan rasa campur aduk\".', '6a0ea506a8054.jpg', '', 0, NULL, '2026-05-21 13:24:06', '2026-05-21 06:24:06', '2026-05-26 10:46:22');

-- --------------------------------------------------------

--
-- Table structure for table `tb_riwayat_toko`
--

CREATE TABLE `tb_riwayat_toko` (
  `id_riwayat` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_toko` int(11) NOT NULL,
  `nomor_kantin` tinyint(3) UNSIGNED DEFAULT NULL,
  `nama_toko` varchar(100) DEFAULT NULL,
  `foto_toko` varchar(255) DEFAULT NULL,
  `tgl_keluar` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_riwayat_toko`
--

INSERT INTO `tb_riwayat_toko` (`id_riwayat`, `id_user`, `id_toko`, `nomor_kantin`, `nama_toko`, `foto_toko`, `tgl_keluar`) VALUES
(1, 3, 2, 2, 'Kantin Pak Sahudi', NULL, '2026-05-24 10:19:32'),
(2, 6, 5, 5, 'Kantin Mar Dika', NULL, '2026-05-26 09:58:48'),
(3, 12, 10, 10, 'Kantin P. Basuni', NULL, '2026-05-26 10:08:27'),
(4, 2, 1, 1, 'Kantin Bu Tika', 'toko_1_1779763787.png', '2026-05-26 10:13:26'),
(5, 11, 9, 9, 'Kantin P. Angga Widhy Wirawan / B. Farah', 'toko_9_1779765391.jpeg', '2026-05-26 10:16:49'),
(6, 21, 1, 1, 'raniaii', 'toko_1_1779765677.jpeg', '2026-05-26 10:21:24'),
(7, 22, 1, 1, 'kantin', NULL, '2026-05-26 10:42:53'),
(8, 23, 1, 1, 'p', NULL, '2026-05-26 10:46:22'),
(9, 24, 1, 1, 'p', NULL, '2026-05-26 10:47:47'),
(10, 25, 1, 1, 'p', NULL, '2026-05-26 10:51:54'),
(11, 26, 1, 1, 'p', NULL, '2026-05-26 11:38:42');

-- --------------------------------------------------------

--
-- Table structure for table `tb_toko`
--

CREATE TABLE `tb_toko` (
  `id_toko` int(11) NOT NULL,
  `nomor_kantin` tinyint(3) UNSIGNED DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `nama_toko` varchar(100) DEFAULT NULL,
  `foto_toko` varchar(255) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `status_toko` enum('buka','tutup') NOT NULL DEFAULT 'buka'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_toko`
--

INSERT INTO `tb_toko` (`id_toko`, `nomor_kantin`, `id_user`, `nama_toko`, `foto_toko`, `created`, `updated`, `deleted`, `deleted_at`, `status_toko`) VALUES
(1, 1, 27, 'Toko kantin', NULL, '2026-05-21 02:52:54', '2026-05-26 04:51:46', 0, NULL, 'buka'),
(2, 2, NULL, NULL, NULL, '2026-05-21 02:54:56', '2026-05-24 03:19:32', 0, NULL, 'tutup'),
(3, 3, NULL, NULL, NULL, '2026-05-21 02:58:20', '2026-05-22 05:03:53', 0, NULL, 'tutup'),
(4, 4, NULL, NULL, NULL, '2026-05-21 03:00:18', '2026-05-26 02:55:52', 0, NULL, 'tutup'),
(5, 5, NULL, NULL, NULL, '2026-05-21 03:01:46', '2026-05-26 03:33:17', 0, NULL, 'tutup'),
(6, 6, 8, 'Kantin Pak Agus / Bu Erna', NULL, '2026-05-21 04:18:44', '2026-05-22 05:03:53', 0, NULL, 'buka'),
(7, 7, NULL, NULL, NULL, '2026-05-21 04:19:46', '2026-05-26 02:55:40', 0, NULL, 'tutup'),
(8, 8, 10, 'Kantin Darma Wanita / Bu Kom', NULL, '2026-05-21 04:21:36', '2026-05-22 05:03:53', 0, NULL, 'buka'),
(9, 9, NULL, NULL, NULL, '2026-05-21 04:23:29', '2026-05-26 03:16:49', 0, NULL, 'tutup'),
(10, 10, NULL, NULL, NULL, '2026-05-21 04:25:04', '2026-05-26 03:33:17', 0, NULL, 'tutup');

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
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` tinyint(4) NOT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_user`
--

INSERT INTO `tb_user` (`id_user`, `username`, `nama_lengkap`, `email`, `foto`, `password`, `role`, `created`, `updated`, `deleted`, `deleted_at`) VALUES
(1, 'jajankita.admin', NULL, 'jajankita@gmail.com', NULL, '$2y$10$grwkJY6wTXFc8lpJ4kFbTeV3KhLzxuqo5oslAvj0ivFoCVPR4kTDu', 'admin', '2026-05-21 09:38:55', '2026-05-21 02:38:55', 0, NULL),
(2, 'bu tikaa', NULL, 'butikaa@gmail.com', NULL, '$2y$10$NCrBFDZPjLxHaEALXIU.ZO5Tv9.pWVNSrN8ruKEvbgGyLe9OH4dsq', 'penjual', '2026-05-21 09:52:54', '2026-05-26 03:12:17', 1, '2026-05-26 10:12:17'),
(3, 'sahudi', NULL, 'sahudi@gmail.com', NULL, '$2y$10$fdYC6asbshOvGwtNPaj60.VGZVH9aLwosK3chLPaAr4xQaKU6gmtC', 'penjual', '2026-05-21 09:56:32', '2026-05-24 03:19:32', 1, '2026-05-24 10:19:32'),
(4, 'bu dian', NULL, 'dian@gmail.com', NULL, '$2y$10$9lTnxAVf8Ii2u7aUn8wK3.oAddnOzmQpdTBnpzTRiyucLBWTcB7Ai', 'penjual', '2026-05-22 07:46:02', '2026-05-22 00:46:02', 1, NULL),
(5, 'sukamto', NULL, 'sukamto@gmail.com', NULL, '$2y$10$0/PvuV5cM4EF5KGo1r.Fu.WCSWmu9hoQ2I7k/tAtS5HbysJhSzGKW', 'penjual', '2026-05-24 08:31:46', '2026-05-24 01:31:46', 1, '2026-05-24 08:31:46'),
(6, 'mar dika', NULL, 'mardika@gmail.com', NULL, '$2y$10$ry910HAd7fD4TRqtboHyX.Eb8J2vx9E7xJMu7pTvoj8DpXzFK3ab2', 'penjual', '2026-05-21 10:01:46', '2026-05-26 02:58:48', 1, '2026-05-26 09:58:48'),
(7, 'nanuna', NULL, 'apacoba@gmail.com', NULL, '$2y$10$3jMe3Ev3pypE2CYzjaY9ueXVwEBgHHhyWDgWMs3J1AZb5jRy8GAaG', 'pembeli', '2026-05-21 10:22:58', '2026-05-21 03:22:58', 0, NULL),
(8, 'pak agus', NULL, 'agus@gmail.com', NULL, '$2y$10$J61NY.ba16resIm9cM5CwOT/pk0qIVx3fRUVebkdZtLYYvmhfU1H6', 'penjual', '2026-05-21 11:18:44', '2026-05-26 00:54:30', 0, NULL),
(9, 'pak fajar', NULL, 'pakfajar@gmail.com', NULL, '$2y$10$K5QfoPBFCs4gGVc5FXuoYuLiicj80teq0tPYEirsFkjDyHy2zY.vG', 'penjual', '2026-05-21 11:19:46', '2026-05-24 01:49:50', 1, '2026-05-24 08:49:50'),
(10, 'bu kom', NULL, 'darmawanita@gmail.com', NULL, '$2y$10$Cx.p/d1YrPvQ9XpkfEaPB.rEZwC0QSrtyt.08nClvWvf4hQj6Syd2', 'penjual', '2026-05-21 11:21:36', '2026-05-21 04:21:36', 0, NULL),
(11, 'pakangga', NULL, 'anggafarah@gmail.com', NULL, '$2y$10$VX6DqJcrksIhs9Xdb232ouGudEdEuyPeU8I81FKPIcJ1.k8DTAhge', 'penjual', '2026-05-21 11:23:29', '2026-05-26 03:16:49', 1, '2026-05-26 10:16:49'),
(12, 'pakbasuni', NULL, 'basuni@gmail.com', NULL, '$2y$10$MS5g9MIJ72w7ufvU6y9mL.zhL18c.3LNk5uLPxD4dOm1TQBs0QRu2', 'penjual', '2026-05-21 11:25:04', '2026-05-26 03:08:27', 1, '2026-05-26 10:08:27'),
(13, 'ariana grande', NULL, 'grande@gmail.com', NULL, '$2y$10$VvBGYCsZxiK5OG0TwqjryeGp3op7siw8wqORM2AjCJtJpBcT6XER.', 'pembeli', '2026-05-21 13:43:05', '2026-05-21 06:43:05', 0, NULL),
(14, 'tokokita', NULL, 'kita@gmail.com', NULL, '$2y$10$9jTa1EIdOfjEELYvLULg.esHUv0IHeOf00HSRpz/Ca2wyxWEWpJWm', 'penjual', '2026-05-23 22:22:07', '2026-05-23 15:22:07', 1, '2026-05-23 22:22:07'),
(15, 'raniaa', NULL, 'r@gmail.com', NULL, '$2y$10$TUFDKJkwdfDXI8qcE8FfV.i/i5CpqzL5HPlswQhxsBMpuO2C0i1DS', 'pembeli', '2026-05-22 07:54:48', '2026-05-22 00:54:48', 0, NULL),
(16, 'buwina', NULL, 'buwina@gmail.com', NULL, '$2y$10$cQNUHrL4nONbgTosPj7CJu.cc897Q6CK83dKMbIEk5mbvV24lq.gS', 'penjual', '2026-05-24 08:53:12', '2026-05-24 03:19:04', 1, '2026-05-24 10:19:04'),
(17, 'kerensekali', NULL, 'keren@gmail.com', NULL, '$2y$10$yfPE7X0As7w23a1hjmHe8OMJ5duz8So6D2oJGfcS8SeWrcNsMJr4u', 'pembeli', '2026-05-26 07:29:20', '2026-05-26 00:29:20', 0, NULL),
(18, 'caca mei', NULL, 'caca@gmail.com', NULL, '$2y$10$fQWpUzbGv8vUoXuaD3DuAuA1v0eYry4tpUEc/dYnib1mQEaxlxFEu', 'pembeli', '2026-05-26 07:29:29', '2026-05-26 00:29:29', 0, NULL),
(19, 'pitaaa', NULL, 'pita@gmail.com', NULL, '$2y$10$4f5No7ixy7uJ0oHJ4fJMUO8zDSOSUogipGMt8m6sOwShdXGweMp6m', 'pembeli', '2026-05-26 07:29:50', '2026-05-26 00:29:50', 0, NULL),
(20, 'Chelsea putri', NULL, 'chelsea@gmail.com', NULL, '$2y$10$xJr1mYUxY1W5AfWLbSJNk./l67PgdMT5eyQOUKXVMiG3yW7NJUn.K', 'pembeli', '2026-05-26 08:10:58', '2026-05-26 01:10:58', 0, NULL),
(21, 'raniaii', NULL, 'rania@gmail.com', NULL, '$2y$10$832rteyZE2QpO7pBoW/ZmuthP942vHlTUBnashYf0gd7PTDCkoO.G', 'penjual', '2026-05-26 10:19:39', '2026-05-26 03:21:24', 1, '2026-05-26 10:21:24'),
(22, 'kantin', NULL, 'kantin@gmail.com', NULL, '$2y$10$u1jAM3zecRUAbR41vcB0heBcuMQRWsjWtC/HkaaZJeiMBXyLVA/Hu', 'penjual', '2026-05-26 10:32:19', '2026-05-26 03:42:53', 1, '2026-05-26 10:42:53'),
(23, 'raniaii', NULL, 'rania@gmail.com', NULL, '$2y$10$zzsRonlDIbrsg.XoXP12Tuw3Oqa49fiZ4mWYwokLO00POQMquGP9m', 'penjual', '2026-05-26 10:45:05', '2026-05-26 03:46:22', 1, '2026-05-26 10:46:22'),
(24, 'raniaii', NULL, 'rania@gmail.com', NULL, '$2y$10$iVCWG0Vg8x1ICLLrRUhPq.qZfSmehfhDjovF6kx0JTT04rxhz4p6O', 'penjual', '2026-05-26 10:47:41', '2026-05-26 03:47:47', 1, '2026-05-26 10:47:47'),
(25, 'raniaii', NULL, 'rania@gmail.com', NULL, '$2y$10$aqgEh5lpP.2F.3levdw6ZuELqFFu0MsLOYksHgaJnsQeGIKbdJpuC', 'penjual', '2026-05-26 10:51:50', '2026-05-26 03:51:54', 1, '2026-05-26 10:51:54'),
(26, 'kantina', NULL, 'akua@gmail.com', NULL, '$2y$10$qdfmDDTTezVdlwBtXoc4J.tYS52hZ9gv8x1Xl2b/XXjvz5MhL4Lwa', 'penjual', '2026-05-26 10:52:29', '2026-05-26 04:38:42', 1, '2026-05-26 11:38:42'),
(27, 'kantinn', NULL, 'kantinn@gmail.com', NULL, '$2y$10$mKXkEbLumqGRc.TG4a9FIeKM6NQWjinYhTFpbqfuUqaj17WCUUnUi', 'penjual', '2026-05-26 11:50:17', '2026-05-26 04:50:17', 0, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_order_aktif`
-- (See below for the actual view)
--
CREATE TABLE `view_order_aktif` (
`id_order` int(11)
,`id_user` int(11)
,`id_toko` int(11)
,`id_penjual` int(11)
,`tanggal_order` datetime
,`total_harga` decimal(10,2)
,`status_order` varchar(50)
,`metode_pembayaran` varchar(50)
,`catatan` text
,`created` timestamp
,`updated` timestamp
,`deleted` tinyint(1)
,`deleted_at` datetime
,`rating` int(1)
,`ulasan` text
);

-- --------------------------------------------------------

--
-- Structure for view `view_order_aktif`
--
DROP TABLE IF EXISTS `view_order_aktif`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_order_aktif`  AS SELECT `tb_order`.`id_order` AS `id_order`, `tb_order`.`id_user` AS `id_user`, `tb_order`.`id_toko` AS `id_toko`, `tb_order`.`id_penjual` AS `id_penjual`, `tb_order`.`tanggal_order` AS `tanggal_order`, `tb_order`.`total_harga` AS `total_harga`, `tb_order`.`status_order` AS `status_order`, `tb_order`.`metode_pembayaran` AS `metode_pembayaran`, `tb_order`.`catatan` AS `catatan`, `tb_order`.`created` AS `created`, `tb_order`.`updated` AS `updated`, `tb_order`.`deleted` AS `deleted`, `tb_order`.`deleted_at` AS `deleted_at`, `tb_order`.`rating` AS `rating`, `tb_order`.`ulasan` AS `ulasan` FROM `tb_order` WHERE `tb_order`.`status_order` <> 'CLOSED' ;

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
-- Indexes for table `tb_riwayat_menu`
--
ALTER TABLE `tb_riwayat_menu`
  ADD PRIMARY KEY (`id_riwayat_menu`);

--
-- Indexes for table `tb_riwayat_toko`
--
ALTER TABLE `tb_riwayat_toko`
  ADD PRIMARY KEY (`id_riwayat`),
  ADD KEY `idx_id_user` (`id_user`);

--
-- Indexes for table `tb_toko`
--
ALTER TABLE `tb_toko`
  ADD PRIMARY KEY (`id_toko`),
  ADD UNIQUE KEY `uk_nomor_kantin` (`nomor_kantin`),
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
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `tb_keranjang`
--
ALTER TABLE `tb_keranjang`
  MODIFY `id_keranjang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `tb_menu`
--
ALTER TABLE `tb_menu`
  MODIFY `id_menu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `tb_order`
--
ALTER TABLE `tb_order`
  MODIFY `id_order` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `tb_rating`
--
ALTER TABLE `tb_rating`
  MODIFY `id_rating` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_rating_menu`
--
ALTER TABLE `tb_rating_menu`
  MODIFY `id_rating_menu` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_riwayat_menu`
--
ALTER TABLE `tb_riwayat_menu`
  MODIFY `id_riwayat_menu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tb_riwayat_toko`
--
ALTER TABLE `tb_riwayat_toko`
  MODIFY `id_riwayat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tb_toko`
--
ALTER TABLE `tb_toko`
  MODIFY `id_toko` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

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
