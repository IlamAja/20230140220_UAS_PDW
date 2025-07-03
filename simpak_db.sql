--
-- Table structure for table `users`
--
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('mahasiswa','asisten') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `praktikums`
--
-- Menyimpan data mata praktikum (misal: Pemrograman Web, Jaringan Komputer)
CREATE TABLE `praktikums` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_praktikum` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `modules`
--
-- Menyimpan data modul/pertemuan untuk setiap praktikum
CREATE TABLE `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `praktikum_id` int(11) NOT NULL,
  `nama_modul` varchar(255) NOT NULL,
  `deskripsi_modul` text DEFAULT NULL,
  `file_materi` varchar(255) DEFAULT NULL, -- Path ke file materi
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`praktikum_id`) REFERENCES `praktikums`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `enrollments`
--
-- Menyimpan data pendaftaran mahasiswa ke mata praktikum
CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `praktikum_id` int(11) NOT NULL,
  `tanggal_daftar` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`user_id`, `praktikum_id`), -- Mahasiswa hanya bisa daftar 1x ke 1 praktikum
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`praktikum_id`) REFERENCES `praktikums`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `reports`
--
-- Menyimpan data laporan/tugas yang dikumpulkan mahasiswa
CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `file_laporan` varchar(255) NOT NULL, -- Path ke file laporan
  `tanggal_pengumpulan` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('submitted','graded') NOT NULL DEFAULT 'submitted', -- Status laporan
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_report_per_module` (`user_id`, `module_id`), -- Mahasiswa hanya bisa submit 1 laporan per modul
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `grades`
--
-- Menyimpan nilai dan feedback untuk laporan
CREATE TABLE `grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `asisten_id` int(11) NOT NULL, -- Asisten yang memberi nilai
  `nilai` decimal(5,2) DEFAULT NULL, -- Nilai (misal: 0.00 - 100.00)
  `feedback` text DEFAULT NULL,
  `tanggal_penilaian` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_grade_per_report` (`report_id`), -- Satu laporan hanya bisa dinilai 1x
  FOREIGN KEY (`report_id`) REFERENCES `reports`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`asisten_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO praktikums (nama_praktikum, deskripsi) VALUES
('Pemrograman Web', 'Mempelajari dasar-dasar pengembangan web menggunakan HTML, CSS, dan PHP.'),
('Jaringan Komputer', 'Pengenalan konsep dasar jaringan komputer dan implementasinya.'),
('Basis Data', 'Mempelajari desain dan implementasi sistem basis data relasional;');