-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2025 at 02:40 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistem_proker`
--

-- --------------------------------------------------------

--
-- Table structure for table `dokumentasi`
--

CREATE TABLE `dokumentasi` (
  `id` int(11) NOT NULL,
  `proker_id` varchar(10) DEFAULT NULL,
  `jenis` enum('proposal','surat','foto') NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dokumentasi`
--

INSERT INTO `dokumentasi` (`id`, `proker_id`, `jenis`, `nama_file`, `file_path`, `uploaded_by`, `uploaded_at`) VALUES
(55, '25250001', 'proposal', 'Proposal Heckton II.pdf', 'uploads/proposal/proposal_25250001_1751101522.pdf', 4, '2025-06-28 09:05:22'),
(56, '25250001', 'surat', 'surat-surat.docx', 'uploads/surat/surat_25250001_1751101617.docx', 4, '2025-06-28 09:06:57'),
(79, '25250001', 'foto', 'Paper art of red umbrella and rain with black sky _ Premium Vector.jpg', 'uploads/foto/foto_25250001_1753798020.jpg', 4, '2025-07-29 14:07:00'),
(80, '25250001', 'foto', 'Reels Ankidz.mp4', 'uploads/foto/foto_25250001_1753798587.mp4', 4, '2025-07-29 14:16:27'),
(84, '25260001', 'proposal', 'PROPOSAL PODCAST MAHA SUARA.docx', 'uploads/proposal/proposal_25260001_1753801246.docx', 5, '2025-07-29 15:00:46'),
(85, '25260001', 'surat', 'KALENDER PODCAST MAHA SUARA 2024.pdf', 'uploads/surat/surat_25260001_1753801265.pdf', 5, '2025-07-29 15:01:05'),
(89, '25260001', 'foto', 'PODCAST MASA EPISODE 4.mp4', 'uploads/foto/foto_25260001_1753803164.mp4', 1, '2025-07-29 15:32:44'),
(90, '25260001', 'foto', 'Screen Recording 2024-08-21 115801.mp4', 'uploads/foto/foto_25260001_1753803697.mp4', 5, '2025-07-29 15:41:37'),
(91, '25260001', 'foto', 'wp10493944-blue-whale-art-wallpapers.jpg', 'uploads/foto/foto_25260001_1753803795.jpg', 5, '2025-07-29 15:43:15'),
(93, '25260001', 'foto', 'Screen Recording 2024-08-21 122530.mp4', 'uploads/foto/foto_25260001_1753864974.mp4', 5, '2025-07-30 08:42:54'),
(95, '25250002', 'proposal', 'PPT SIDANG.pdf', 'uploads/proposal/proposal_25250002_1754986486.pdf', NULL, '2025-08-12 08:14:46'),
(96, '25250002', 'surat', 'STATUS_PENGUJIAN_PLAGIARISME_211011401152_1507692843.pdf', 'uploads/surat/surat_25250002_1754986508.pdf', NULL, '2025-08-12 08:15:08');

-- --------------------------------------------------------

--
-- Table structure for table `evaluasi`
--

CREATE TABLE `evaluasi` (
  `id` int(11) NOT NULL,
  `proker_id` varchar(10) DEFAULT NULL,
  `evaluasi` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluasi`
--

INSERT INTO `evaluasi` (`id`, `proker_id`, `evaluasi`, `created_by`, `created_at`, `updated_at`) VALUES
(10, '25250001', 'Sudah cukup baik, kalau bisa dilakukan setiap satu bulan sekali', 4, '2025-06-28 09:22:43', '2025-06-28 09:31:11'),
(12, '25250002', 'ok', 1, '2025-08-12 08:18:05', '2025-08-12 08:18:05');

-- --------------------------------------------------------

--
-- Table structure for table `program_kerja`
--

CREATE TABLE `program_kerja` (
  `id` varchar(10) NOT NULL,
  `nama_proker` varchar(200) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `status` enum('belum_mulai','sedang_berjalan','selesai','dibatalkan') DEFAULT NULL,
  `angkatan` varchar(10) DEFAULT NULL,
  `ketua_pelaksana_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_kerja`
--

INSERT INTO `program_kerja` (`id`, `nama_proker`, `deskripsi`, `tanggal_mulai`, `tanggal_selesai`, `status`, `angkatan`, `ketua_pelaksana_id`, `created_by`, `created_at`, `updated_at`) VALUES
('25250001', 'Hackaton II', 'Pergoram Kerja HIMTIF untuk...', '2025-06-26', NULL, 'sedang_berjalan', '2025', 4, 5, '2025-06-22 15:25:28', '2025-06-28 08:29:58'),
('25250002', 'Diesnat', 'sxjhsbacs', '2025-08-23', NULL, 'selesai', '2025', NULL, NULL, '2025-08-12 08:14:07', '2025-08-12 08:20:15'),
('25260001', 'Podcast Mahasuara', 'Konten edukatif dan menghibur untuk mahasiswa umum khususnya mahasiswa teknik informatika', '2025-07-16', NULL, 'sedang_berjalan', '2026', 4, 1, '2025-07-06 02:47:02', '2025-07-29 14:54:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('sekretaris','ketua_pelaksana','ketua_umum') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `nik`, `username`, `password`, `role`, `created_at`, `status`) VALUES
(1, 'Admin Sekretaris', '12345', 'sekretaris', '482c811da5d5b4bc6d497ffa98491e38', 'sekretaris', '2025-05-29 13:21:42', 'aktif'),
(2, 'Muhammad Sehun', '54321', 'ketua1', '482c811da5d5b4bc6d497ffa98491e38', 'ketua_pelaksana', '2025-05-29 13:21:42', 'nonaktif'),
(3, 'Ketua Umum', '11111', 'ketua_umum', '482c811da5d5b4bc6d497ffa98491e38', 'ketua_umum', '2025-05-29 13:21:42', 'aktif'),
(4, 'Nadira Sabita', '211011401152', 'nadira', 'e10adc3949ba59abbe56e057f20f883e', 'ketua_pelaksana', '2025-05-31 03:56:57', 'aktif'),
(5, 'Azmi Yaumi', '211011401160', 'amiyaumi', '03236052fe6f810f705c4fd933ac5bb8', 'sekretaris', '2025-06-01 15:35:28', 'aktif'),
(9, 'Muhammad Rafi', '12432342', 'rafi', 'e10adc3949ba59abbe56e057f20f883e', 'ketua_pelaksana', '2025-06-28 07:26:16', 'nonaktif');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dokumentasi`
--
ALTER TABLE `dokumentasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `fk_dokumentasi_proker` (`proker_id`);

--
-- Indexes for table `evaluasi`
--
ALTER TABLE `evaluasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_evaluasi_proker` (`proker_id`);

--
-- Indexes for table `program_kerja`
--
ALTER TABLE `program_kerja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ketua_pelaksana_id` (`ketua_pelaksana_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dokumentasi`
--
ALTER TABLE `dokumentasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `evaluasi`
--
ALTER TABLE `evaluasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dokumentasi`
--
ALTER TABLE `dokumentasi`
  ADD CONSTRAINT `dokumentasi_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_dokumentasi_proker` FOREIGN KEY (`proker_id`) REFERENCES `program_kerja` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluasi`
--
ALTER TABLE `evaluasi`
  ADD CONSTRAINT `evaluasi_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_evaluasi_proker` FOREIGN KEY (`proker_id`) REFERENCES `program_kerja` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `program_kerja`
--
ALTER TABLE `program_kerja`
  ADD CONSTRAINT `program_kerja_ibfk_1` FOREIGN KEY (`ketua_pelaksana_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `program_kerja_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
