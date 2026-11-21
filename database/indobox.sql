-- ============================================================
-- INDO BOX - Sistem Otomasi Perpustakaan
-- Import file ini lewat phpMyAdmin (tab Import). Semua ID dimulai dari 0.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `indobox` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `indobox`;

DROP TABLE IF EXISTS `reviews`;

DROP TABLE IF EXISTS `reservations`;

DROP TABLE IF EXISTS `loans`;

DROP TABLE IF EXISTS `visits`;

DROP TABLE IF EXISTS `activity_log`;

DROP TABLE IF EXISTS `announcements`;

DROP TABLE IF EXISTS `settings`;

DROP TABLE IF EXISTS `biblios`;

DROP TABLE IF EXISTS `categories`;

DROP TABLE IF EXISTS `members`;

DROP TABLE IF EXISTS `member_types`;

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `role` ENUM('admin','petugas') NOT NULL DEFAULT 'petugas',
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `member_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(50) NOT NULL,
  `maks_pinjam` INT UNSIGNED NOT NULL DEFAULT 3,
  `lama_pinjam` INT UNSIGNED NOT NULL DEFAULT 7,
  `denda_per_hari` INT UNSIGNED NOT NULL DEFAULT 500,
  `maks_perpanjang` INT UNSIGNED NOT NULL DEFAULT 1,
  `masa_berlaku_bulan` INT UNSIGNED NOT NULL DEFAULT 12,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `members` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `no_anggota` VARCHAR(30) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `jk` ENUM('L','P') NOT NULL DEFAULT 'L',
  `tgl_lahir` DATE DEFAULT NULL,
  `alamat` VARCHAR(255) DEFAULT NULL,
  `telp` VARCHAR(25) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `type_id` INT UNSIGNED NOT NULL,
  `tgl_daftar` DATE NOT NULL,
  `tgl_expire` DATE NOT NULL,
  `status` ENUM('aktif','nonaktif','blokir') NOT NULL DEFAULT 'aktif',
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_no_anggota` (`no_anggota`),
  CONSTRAINT `fk_member_type` FOREIGN KEY (`type_id`) REFERENCES `member_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode` VARCHAR(10) NOT NULL,
  `nama` VARCHAR(80) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `biblios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_barcode` VARCHAR(30) NOT NULL,
  `isbn` VARCHAR(20) DEFAULT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `pengarang` VARCHAR(150) NOT NULL,
  `penerbit` VARCHAR(150) DEFAULT NULL,
  `tahun` SMALLINT UNSIGNED DEFAULT NULL,
  `edisi` VARCHAR(30) DEFAULT NULL,
  `kategori_id` INT UNSIGNED NOT NULL,
  `bahasa` VARCHAR(30) DEFAULT 'Indonesia',
  `halaman` INT UNSIGNED DEFAULT NULL,
  `lokasi_rak` VARCHAR(30) DEFAULT NULL,
  `sinopsis` TEXT,
  `stok_total` INT UNSIGNED NOT NULL DEFAULT 1,
  `stok_tersedia` INT UNSIGNED NOT NULL DEFAULT 1,
  `cover` VARCHAR(150) DEFAULT NULL,
  `file_pdf` VARCHAR(150) DEFAULT NULL,
  `dilihat` INT UNSIGNED NOT NULL DEFAULT 0,
  `tgl_masuk` DATE NOT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_barcode` (`kode_barcode`),
  KEY `idx_judul` (`judul`), KEY `idx_pengarang` (`pengarang`),
  CONSTRAINT `fk_biblio_cat` FOREIGN KEY (`kategori_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `loans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `biblio_id` INT UNSIGNED NOT NULL,
  `member_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `tgl_pinjam` DATE NOT NULL,
  `tgl_jatuh_tempo` DATE NOT NULL,
  `tgl_kembali` DATE DEFAULT NULL,
  `perpanjang_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `denda` INT UNSIGNED NOT NULL DEFAULT 0,
  `denda_dibayar` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('dipinjam','kembali') NOT NULL DEFAULT 'dipinjam',
  PRIMARY KEY (`id`), KEY `idx_loan_status` (`status`), KEY `idx_loan_date` (`tgl_pinjam`),
  CONSTRAINT `fk_loan_biblio` FOREIGN KEY (`biblio_id`) REFERENCES `biblios` (`id`),
  CONSTRAINT `fk_loan_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reservations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `biblio_id` INT UNSIGNED NOT NULL,
  `member_id` INT UNSIGNED NOT NULL,
  `tgl_reservasi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `batas_ambil` DATE DEFAULT NULL,
  `status` ENUM('menunggu','siap','diambil','batal','kadaluarsa') NOT NULL DEFAULT 'menunggu',
  PRIMARY KEY (`id`), KEY `idx_res_status` (`status`),
  CONSTRAINT `fk_res_biblio` FOREIGN KEY (`biblio_id`) REFERENCES `biblios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_res_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `biblio_id` INT UNSIGNED NOT NULL,
  `member_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `komentar` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_review` (`biblio_id`,`member_id`),
  CONSTRAINT `fk_rev_biblio` FOREIGN KEY (`biblio_id`) REFERENCES `biblios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `announcements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `judul` VARCHAR(150) NOT NULL,
  `isi` TEXT NOT NULL,
  `tgl` DATE NOT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `visits` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` INT UNSIGNED DEFAULT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `keperluan` VARCHAR(50) NOT NULL,
  `waktu` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_visit_time` (`waktu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `k` VARCHAR(60) NOT NULL,
  `v` TEXT,
  PRIMARY KEY (`id`), UNIQUE KEY `uq_k` (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pengguna` VARCHAR(150) NOT NULL,
  `aksi` VARCHAR(255) NOT NULL,
  `waktu` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`,`username`,`password`,`nama`,`role`) VALUES
(0,'admin','plain:admin123','Administrator INDO BOX','admin'),
(1,'petugas','plain:petugas123','Petugas Layanan','petugas');

INSERT INTO `member_types` (`id`,`nama`,`maks_pinjam`,`lama_pinjam`,`denda_per_hari`,`maks_perpanjang`,`masa_berlaku_bulan`) VALUES
(0,'Mahasiswa',3,7,500,1,12),
(1,'Dosen/Staf',5,14,1000,2,24),
(2,'Umum',2,5,1000,1,6),
(3,'Pelajar',3,7,500,1,12);

INSERT INTO `members` (`id`,`no_anggota`,`nama`,`jk`,`tgl_lahir`,`alamat`,`telp`,`email`,`type_id`,`tgl_daftar`,`tgl_expire`,`status`,`password`) VALUES
(0,'AGT-0001','Aulia Rahmawati','P','2003-04-12','Jl. Melati 12, Semarang','081234560001','aulia@indobox.test',0,DATE_SUB(CURDATE(), INTERVAL 200 DAY),DATE_ADD(CURDATE(), INTERVAL 8 MONTH),'aktif','plain:anggota123'),
(1,'AGT-0002','Bima Prasetyo','L','2002-09-30','Jl. Kenanga 5, Semarang','081234560002','bima@indobox.test',0,DATE_SUB(CURDATE(), INTERVAL 185 DAY),DATE_ADD(CURDATE(), INTERVAL 8 MONTH),'aktif','plain:anggota123'),
(2,'AGT-0003','Citra Maharani','P','2004-01-21','Jl. Anggrek 8, Ungaran','081234560003','citra@indobox.test',0,DATE_SUB(CURDATE(), INTERVAL 170 DAY),DATE_ADD(CURDATE(), INTERVAL 8 MONTH),'aktif','plain:anggota123'),
(3,'AGT-0004','Dimas Anggara','L','1985-06-15','Jl. Pandanaran 77, Semarang','081234560004','dimas@indobox.test',1,DATE_SUB(CURDATE(), INTERVAL 155 DAY),DATE_ADD(CURDATE(), INTERVAL 20 MONTH),'aktif','plain:anggota123'),
(4,'AGT-0005','Eka Putri Lestari','P','2003-11-02','Jl. Tlogosari 3, Semarang','081234560005','eka@indobox.test',0,DATE_SUB(CURDATE(), INTERVAL 140 DAY),DATE_ADD(CURDATE(), INTERVAL 8 MONTH),'aktif','plain:anggota123'),
(5,'AGT-0006','Farhan Maulana','L','2008-03-18','Jl. Sompok 21, Semarang','081234560006','farhan@indobox.test',3,DATE_SUB(CURDATE(), INTERVAL 125 DAY),DATE_ADD(CURDATE(), INTERVAL 8 MONTH),'aktif','plain:anggota123'),
(6,'AGT-0007','Gita Safitri','P','1995-07-07','Jl. Veteran 40, Semarang','081234560007','gita@indobox.test',2,DATE_SUB(CURDATE(), INTERVAL 110 DAY),DATE_ADD(CURDATE(), INTERVAL 2 MONTH),'aktif','plain:anggota123'),
(7,'AGT-0008','Hendra Wijaya','L','1980-12-25','Jl. Gajah Raya 9, Semarang','081234560008','hendra@indobox.test',1,DATE_SUB(CURDATE(), INTERVAL 95 DAY),DATE_ADD(CURDATE(), INTERVAL 20 MONTH),'aktif','plain:anggota123');

INSERT INTO `categories` (`id`,`kode`,`nama`) VALUES
(0,'UMM','Karya Umum'),
(1,'FIK','Fiksi & Sastra'),
(2,'TIK','Teknologi Informasi'),
(3,'SNS','Sains & Matematika'),
(4,'SOS','Sosial & Humaniora'),
(5,'BIS','Bisnis & Ekonomi'),
(6,'DIR','Pengembangan Diri'),
(7,'SJR','Sejarah & Biografi'),
(8,'AGM','Agama & Filsafat'),
(9,'ANK','Anak & Remaja');

INSERT INTO `biblios` (`id`,`kode_barcode`,`isbn`,`judul`,`pengarang`,`penerbit`,`tahun`,`edisi`,`kategori_id`,`bahasa`,`halaman`,`lokasi_rak`,`sinopsis`,`stok_total`,`stok_tersedia`,`cover`,`file_pdf`,`dilihat`,`tgl_masuk`) VALUES
(0,'IB-0001',NULL,'Laskar Pelangi','Andrea Hirata','Bentang Pustaka',2005,NULL,1,'Indonesia',529,'RAK A-1','Kisah sekelompok anak di Belitung yang bersekolah di sebuah sekolah dasar sederhana. Dengan segala keterbatasan, mereka saling menguatkan, bermimpi besar, dan belajar arti persahabatan serta semangat menuntut ilmu.',4,4,NULL,'contoh-01.pdf',42,DATE_SUB(CURDATE(), INTERVAL 150 DAY)),
(1,'IB-0002',NULL,'Bumi Manusia','Pramoedya Ananta Toer','Lentera Dipantara',1980,NULL,1,'Indonesia',535,'RAK A-1','Minke, seorang pribumi terpelajar di masa Hindia Belanda, bertemu Annelies dan Nyai Ontosoroh. Novel sejarah ini menyorot cinta, martabat, dan perlawanan terhadap ketidakadilan kolonial.',3,3,NULL,'contoh-02.pdf',13,DATE_SUB(CURDATE(), INTERVAL 141 DAY)),
(2,'IB-0003',NULL,'Negeri 5 Menara','Ahmad Fuadi','Gramedia Pustaka Utama',2009,NULL,1,'Indonesia',423,'RAK A-2','Alif, remaja dari Minangkabau, belajar di sebuah pesantren di Jawa Timur. Bersama sahabat-sahabatnya ia belajar bahwa kesungguhan akan membawa seseorang pada tujuannya.',3,3,NULL,'contoh-03.pdf',31,DATE_SUB(CURDATE(), INTERVAL 132 DAY)),
(3,'IB-0004',NULL,'Filosofi Teras','Henry Manampiring','Penerbit Buku Kompas',2018,NULL,6,'Indonesia',346,'RAK B-1','Pengantar filsafat Stoisisme yang dibawakan dengan santai untuk kehidupan modern: bagaimana membedakan hal yang bisa dan tidak bisa kita kendalikan, serta mengelola emosi negatif.',2,2,NULL,'contoh-04.pdf',42,DATE_SUB(CURDATE(), INTERVAL 123 DAY)),
(4,'IB-0005',NULL,'Atomic Habits (Edisi Terjemahan)','James Clear','Gramedia Pustaka Utama',2019,NULL,6,'Indonesia',352,'RAK B-1','Panduan praktis membangun kebiasaan baik dan meninggalkan kebiasaan buruk melalui perubahan-perubahan kecil yang dilakukan secara konsisten.',3,3,NULL,'contoh-05.pdf',24,DATE_SUB(CURDATE(), INTERVAL 114 DAY)),
(5,'IB-0006',NULL,'Sapiens: Sejarah Singkat Umat Manusia','Yuval Noah Harari','Kepustakaan Populer Gramedia',2017,NULL,7,'Indonesia',544,'RAK C-1','Perjalanan panjang umat manusia sejak masa pemburu-pengumpul, revolusi pertanian, hingga era sains modern, dilihat dari sudut pandang yang menantang.',3,3,NULL,'contoh-06.pdf',62,DATE_SUB(CURDATE(), INTERVAL 105 DAY)),
(6,'IB-0007',NULL,'Pemrograman Web dengan PHP dan MySQL','Tim Penulis INDO BOX','Penerbit Informatika Nusantara',2022,NULL,2,'Indonesia',280,'RAK D-1','Buku ajar pengembangan aplikasi web dinamis menggunakan PHP dan MySQL di lingkungan XAMPP, mulai dari dasar sintaks hingga membuat sistem informasi sederhana.',4,4,NULL,'contoh-07.pdf',41,DATE_SUB(CURDATE(), INTERVAL 96 DAY)),
(7,'IB-0008',NULL,'Algoritma dan Struktur Data','Tim Penulis INDO BOX','Penerbit Informatika Nusantara',2021,NULL,2,'Indonesia',310,'RAK D-1','Konsep dasar algoritma, kompleksitas, serta struktur data seperti array, linked list, stack, queue, dan tree dengan contoh penerapan.',2,2,NULL,'contoh-08.pdf',59,DATE_SUB(CURDATE(), INTERVAL 87 DAY)),
(8,'IB-0009',NULL,'Dasar-Dasar Basis Data','Tim Penulis INDO BOX','Penerbit Informatika Nusantara',2020,NULL,2,'Indonesia',264,'RAK D-2','Pengantar perancangan basis data relasional: ERD, normalisasi, dan SQL, dilengkapi studi kasus sistem perpustakaan.',3,3,NULL,'contoh-09.pdf',27,DATE_SUB(CURDATE(), INTERVAL 78 DAY)),
(9,'IB-0010',NULL,'Laut Bercerita','Leila S. Chudori','Kepustakaan Populer Gramedia',2017,NULL,1,'Indonesia',379,'RAK A-2','Novel tentang aktivis mahasiswa yang hilang pada akhir masa Orde Baru, dan keluarga serta sahabat yang terus mencari kebenaran.',3,3,NULL,'contoh-10.pdf',76,DATE_SUB(CURDATE(), INTERVAL 69 DAY)),
(10,'IB-0011',NULL,'Dilan 1990','Pidi Baiq','Pastel Books',2014,NULL,1,'Indonesia',332,'RAK A-3','Kisah cinta remaja di Bandung tahun 1990 antara Dilan dan Milea, ditulis dengan gaya ringan dan jenaka.',3,3,NULL,'contoh-11.pdf',73,DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(11,'IB-0012',NULL,'Pengantar Ekonomi Mikro','Tim Penulis INDO BOX','Penerbit Informatika Nusantara',2019,NULL,5,'Indonesia',240,'RAK E-1','Pengantar teori permintaan dan penawaran, perilaku konsumen dan produsen, serta struktur pasar.',2,2,NULL,'contoh-12.pdf',81,DATE_SUB(CURDATE(), INTERVAL 51 DAY)),
(12,'IB-0013',NULL,'Kumpulan Dongeng Nusantara','Tim Penulis INDO BOX','Penerbit Informatika Nusantara',2023,NULL,9,'Indonesia',120,'RAK F-1','Kumpulan cerita rakyat dari berbagai daerah di Indonesia yang sarat pesan moral untuk anak-anak.',3,3,NULL,'contoh-13.pdf',71,DATE_SUB(CURDATE(), INTERVAL 42 DAY)),
(13,'IB-0014',NULL,'Matematika Dasar untuk Sains','Tim Penulis INDO BOX','Penerbit Informatika Nusantara',2020,NULL,3,'Indonesia',298,'RAK C-2','Ringkasan aljabar, trigonometri, dan kalkulus dasar sebagai bekal mempelajari ilmu alam dan teknik.',2,2,NULL,'contoh-14.pdf',49,DATE_SUB(CURDATE(), INTERVAL 33 DAY));

INSERT INTO `loans` (`id`,`biblio_id`,`member_id`,`user_id`,`tgl_pinjam`,`tgl_jatuh_tempo`,`tgl_kembali`,`perpanjang_count`,`denda`,`denda_dibayar`,`status`) VALUES
(0,0,4,1,DATE_SUB(CURDATE(), INTERVAL 112 DAY),DATE_SUB(CURDATE(), INTERVAL 105 DAY),DATE_SUB(CURDATE(), INTERVAL 96 DAY),0,4500,4500,'kembali'),
(1,1,0,1,DATE_SUB(CURDATE(), INTERVAL 111 DAY),DATE_SUB(CURDATE(), INTERVAL 104 DAY),DATE_SUB(CURDATE(), INTERVAL 104 DAY),0,0,0,'kembali'),
(2,7,4,0,DATE_SUB(CURDATE(), INTERVAL 104 DAY),DATE_SUB(CURDATE(), INTERVAL 97 DAY),DATE_SUB(CURDATE(), INTERVAL 97 DAY),0,0,0,'kembali'),
(3,0,4,1,DATE_SUB(CURDATE(), INTERVAL 101 DAY),DATE_SUB(CURDATE(), INTERVAL 94 DAY),DATE_SUB(CURDATE(), INTERVAL 94 DAY),0,0,0,'kembali'),
(4,4,2,1,DATE_SUB(CURDATE(), INTERVAL 101 DAY),DATE_SUB(CURDATE(), INTERVAL 94 DAY),DATE_SUB(CURDATE(), INTERVAL 94 DAY),1,0,0,'kembali'),
(5,4,4,0,DATE_SUB(CURDATE(), INTERVAL 96 DAY),DATE_SUB(CURDATE(), INTERVAL 89 DAY),DATE_SUB(CURDATE(), INTERVAL 90 DAY),0,0,0,'kembali'),
(6,13,2,0,DATE_SUB(CURDATE(), INTERVAL 93 DAY),DATE_SUB(CURDATE(), INTERVAL 86 DAY),DATE_SUB(CURDATE(), INTERVAL 81 DAY),0,2500,2500,'kembali'),
(7,6,2,1,DATE_SUB(CURDATE(), INTERVAL 89 DAY),DATE_SUB(CURDATE(), INTERVAL 82 DAY),DATE_SUB(CURDATE(), INTERVAL 85 DAY),0,0,0,'kembali'),
(8,6,1,0,DATE_SUB(CURDATE(), INTERVAL 88 DAY),DATE_SUB(CURDATE(), INTERVAL 81 DAY),DATE_SUB(CURDATE(), INTERVAL 72 DAY),1,4500,4500,'kembali'),
(9,7,7,0,DATE_SUB(CURDATE(), INTERVAL 79 DAY),DATE_SUB(CURDATE(), INTERVAL 65 DAY),DATE_SUB(CURDATE(), INTERVAL 68 DAY),0,0,0,'kembali'),
(10,12,0,1,DATE_SUB(CURDATE(), INTERVAL 68 DAY),DATE_SUB(CURDATE(), INTERVAL 61 DAY),DATE_SUB(CURDATE(), INTERVAL 61 DAY),0,0,0,'kembali'),
(11,10,4,0,DATE_SUB(CURDATE(), INTERVAL 67 DAY),DATE_SUB(CURDATE(), INTERVAL 60 DAY),DATE_SUB(CURDATE(), INTERVAL 60 DAY),0,0,0,'kembali'),
(12,4,2,1,DATE_SUB(CURDATE(), INTERVAL 64 DAY),DATE_SUB(CURDATE(), INTERVAL 57 DAY),DATE_SUB(CURDATE(), INTERVAL 55 DAY),0,1000,1000,'kembali'),
(13,5,2,1,DATE_SUB(CURDATE(), INTERVAL 59 DAY),DATE_SUB(CURDATE(), INTERVAL 52 DAY),DATE_SUB(CURDATE(), INTERVAL 43 DAY),0,4500,4500,'kembali'),
(14,0,2,0,DATE_SUB(CURDATE(), INTERVAL 57 DAY),DATE_SUB(CURDATE(), INTERVAL 50 DAY),DATE_SUB(CURDATE(), INTERVAL 51 DAY),0,0,0,'kembali'),
(15,5,0,1,DATE_SUB(CURDATE(), INTERVAL 54 DAY),DATE_SUB(CURDATE(), INTERVAL 47 DAY),DATE_SUB(CURDATE(), INTERVAL 48 DAY),0,0,0,'kembali'),
(16,6,6,0,DATE_SUB(CURDATE(), INTERVAL 54 DAY),DATE_SUB(CURDATE(), INTERVAL 49 DAY),DATE_SUB(CURDATE(), INTERVAL 51 DAY),0,0,0,'kembali'),
(17,0,4,0,DATE_SUB(CURDATE(), INTERVAL 51 DAY),DATE_SUB(CURDATE(), INTERVAL 44 DAY),DATE_SUB(CURDATE(), INTERVAL 46 DAY),0,0,0,'kembali'),
(18,7,2,1,DATE_SUB(CURDATE(), INTERVAL 49 DAY),DATE_SUB(CURDATE(), INTERVAL 42 DAY),DATE_SUB(CURDATE(), INTERVAL 43 DAY),0,0,0,'kembali'),
(19,9,3,1,DATE_SUB(CURDATE(), INTERVAL 46 DAY),DATE_SUB(CURDATE(), INTERVAL 32 DAY),DATE_SUB(CURDATE(), INTERVAL 32 DAY),0,0,0,'kembali'),
(20,9,7,0,DATE_SUB(CURDATE(), INTERVAL 45 DAY),DATE_SUB(CURDATE(), INTERVAL 31 DAY),DATE_SUB(CURDATE(), INTERVAL 32 DAY),0,0,0,'kembali'),
(21,2,2,1,DATE_SUB(CURDATE(), INTERVAL 42 DAY),DATE_SUB(CURDATE(), INTERVAL 35 DAY),DATE_SUB(CURDATE(), INTERVAL 35 DAY),0,0,0,'kembali'),
(22,0,4,0,DATE_SUB(CURDATE(), INTERVAL 36 DAY),DATE_SUB(CURDATE(), INTERVAL 29 DAY),DATE_SUB(CURDATE(), INTERVAL 32 DAY),0,0,0,'kembali'),
(23,6,3,0,DATE_SUB(CURDATE(), INTERVAL 33 DAY),DATE_SUB(CURDATE(), INTERVAL 19 DAY),DATE_SUB(CURDATE(), INTERVAL 20 DAY),0,0,0,'kembali'),
(24,3,3,0,DATE_SUB(CURDATE(), INTERVAL 29 DAY),DATE_SUB(CURDATE(), INTERVAL 15 DAY),DATE_SUB(CURDATE(), INTERVAL 17 DAY),0,0,0,'kembali'),
(25,3,0,1,DATE_SUB(CURDATE(), INTERVAL 28 DAY),DATE_SUB(CURDATE(), INTERVAL 21 DAY),DATE_SUB(CURDATE(), INTERVAL 21 DAY),0,0,0,'kembali'),
(26,3,5,0,DATE_SUB(CURDATE(), INTERVAL 28 DAY),DATE_SUB(CURDATE(), INTERVAL 21 DAY),DATE_SUB(CURDATE(), INTERVAL 21 DAY),0,0,0,'kembali'),
(27,1,2,0,DATE_SUB(CURDATE(), INTERVAL 26 DAY),DATE_SUB(CURDATE(), INTERVAL 19 DAY),DATE_SUB(CURDATE(), INTERVAL 17 DAY),0,1000,1000,'kembali'),
(28,3,7,1,DATE_SUB(CURDATE(), INTERVAL 25 DAY),DATE_SUB(CURDATE(), INTERVAL 11 DAY),DATE_SUB(CURDATE(), INTERVAL 9 DAY),0,2000,2000,'kembali'),
(29,6,7,1,DATE_SUB(CURDATE(), INTERVAL 24 DAY),DATE_SUB(CURDATE(), INTERVAL 10 DAY),DATE_SUB(CURDATE(), INTERVAL 10 DAY),0,0,0,'kembali'),
(30,3,4,1,DATE_SUB(CURDATE(), INTERVAL 21 DAY),DATE_SUB(CURDATE(), INTERVAL 14 DAY),DATE_SUB(CURDATE(), INTERVAL 14 DAY),0,0,0,'kembali'),
(31,7,7,0,DATE_SUB(CURDATE(), INTERVAL 20 DAY),DATE_SUB(CURDATE(), INTERVAL 6 DAY),NULL,0,0,0,'dipinjam'),
(32,6,0,0,DATE_SUB(CURDATE(), INTERVAL 20 DAY),DATE_SUB(CURDATE(), INTERVAL 13 DAY),DATE_SUB(CURDATE(), INTERVAL 15 DAY),0,0,0,'kembali'),
(33,3,1,1,DATE_SUB(CURDATE(), INTERVAL 19 DAY),DATE_SUB(CURDATE(), INTERVAL 12 DAY),DATE_SUB(CURDATE(), INTERVAL 14 DAY),0,0,0,'kembali'),
(34,9,1,0,DATE_SUB(CURDATE(), INTERVAL 17 DAY),DATE_SUB(CURDATE(), INTERVAL 10 DAY),DATE_SUB(CURDATE(), INTERVAL 13 DAY),0,0,0,'kembali'),
(35,4,5,1,DATE_SUB(CURDATE(), INTERVAL 17 DAY),DATE_SUB(CURDATE(), INTERVAL 10 DAY),DATE_SUB(CURDATE(), INTERVAL 11 DAY),1,0,0,'kembali'),
(36,9,3,0,DATE_SUB(CURDATE(), INTERVAL 16 DAY),DATE_SUB(CURDATE(), INTERVAL 2 DAY),NULL,0,0,0,'dipinjam'),
(37,4,7,1,DATE_SUB(CURDATE(), INTERVAL 15 DAY),DATE_SUB(CURDATE(), INTERVAL 1 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,1000,0,'kembali'),
(38,4,5,1,DATE_SUB(CURDATE(), INTERVAL 12 DAY),DATE_SUB(CURDATE(), INTERVAL 5 DAY),NULL,0,0,0,'dipinjam'),
(39,6,4,0,DATE_SUB(CURDATE(), INTERVAL 11 DAY),DATE_SUB(CURDATE(), INTERVAL 4 DAY),DATE_SUB(CURDATE(), INTERVAL 5 DAY),0,0,0,'kembali'),
(40,3,1,1,DATE_SUB(CURDATE(), INTERVAL 9 DAY),DATE_SUB(CURDATE(), INTERVAL 2 DAY),NULL,0,0,0,'dipinjam'),
(41,10,6,0,DATE_SUB(CURDATE(), INTERVAL 8 DAY),DATE_SUB(CURDATE(), INTERVAL 3 DAY),NULL,0,0,0,'dipinjam'),
(42,11,2,1,DATE_SUB(CURDATE(), INTERVAL 8 DAY),DATE_SUB(CURDATE(), INTERVAL 1 DAY),DATE_SUB(CURDATE(), INTERVAL 1 DAY),1,0,0,'kembali'),
(43,3,4,0,DATE_SUB(CURDATE(), INTERVAL 8 DAY),DATE_SUB(CURDATE(), INTERVAL 1 DAY),DATE_SUB(CURDATE(), INTERVAL 1 DAY),0,0,0,'kembali'),
(44,10,1,0,DATE_SUB(CURDATE(), INTERVAL 7 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,0,0,'kembali'),
(45,12,5,1,DATE_SUB(CURDATE(), INTERVAL 6 DAY),DATE_ADD(CURDATE(), INTERVAL 1 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,0,0,'kembali'),
(46,12,1,0,DATE_SUB(CURDATE(), INTERVAL 6 DAY),DATE_ADD(CURDATE(), INTERVAL 1 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,0,0,'kembali'),
(47,6,2,0,DATE_SUB(CURDATE(), INTERVAL 6 DAY),DATE_ADD(CURDATE(), INTERVAL 1 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,0,0,'kembali'),
(48,1,4,1,DATE_SUB(CURDATE(), INTERVAL 4 DAY),DATE_ADD(CURDATE(), INTERVAL 3 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,0,0,'kembali'),
(49,0,0,1,DATE_SUB(CURDATE(), INTERVAL 3 DAY),DATE_ADD(CURDATE(), INTERVAL 4 DAY),NULL,0,0,0,'dipinjam'),
(50,13,4,1,DATE_SUB(CURDATE(), INTERVAL 3 DAY),DATE_ADD(CURDATE(), INTERVAL 4 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,0,0,'kembali'),
(51,4,1,0,DATE_SUB(CURDATE(), INTERVAL 3 DAY),DATE_ADD(CURDATE(), INTERVAL 4 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,0,0,'kembali'),
(52,3,4,0,DATE_SUB(CURDATE(), INTERVAL 2 DAY),DATE_ADD(CURDATE(), INTERVAL 5 DAY),NULL,0,0,0,'dipinjam'),
(53,5,2,0,DATE_SUB(CURDATE(), INTERVAL 2 DAY),DATE_ADD(CURDATE(), INTERVAL 5 DAY),NULL,0,0,0,'dipinjam'),
(54,1,2,1,DATE_SUB(CURDATE(), INTERVAL 2 DAY),DATE_ADD(CURDATE(), INTERVAL 5 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,0,0,'kembali'),
(55,6,1,0,DATE_SUB(CURDATE(), INTERVAL 2 DAY),DATE_ADD(CURDATE(), INTERVAL 5 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,0,0,'kembali'),
(56,5,0,0,DATE_SUB(CURDATE(), INTERVAL 2 DAY),DATE_ADD(CURDATE(), INTERVAL 5 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,0,0,'kembali'),
(57,9,2,1,DATE_SUB(CURDATE(), INTERVAL 2 DAY),DATE_ADD(CURDATE(), INTERVAL 5 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,0,0,'kembali'),
(58,3,0,1,DATE_SUB(CURDATE(), INTERVAL 2 DAY),DATE_ADD(CURDATE(), INTERVAL 5 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,0,0,'kembali'),
(59,7,1,0,DATE_SUB(CURDATE(), INTERVAL 1 DAY),DATE_ADD(CURDATE(), INTERVAL 6 DAY),DATE_SUB(CURDATE(), INTERVAL 0 DAY),0,0,0,'kembali');

UPDATE `biblios` b SET b.stok_tersedia = b.stok_total - (SELECT COUNT(*) FROM `loans` l WHERE l.biblio_id=b.id AND l.status='dipinjam');

INSERT INTO `reservations` (`id`,`biblio_id`,`member_id`,`tgl_reservasi`,`batas_ambil`,`status`) VALUES
(0,3,5,DATE_SUB(NOW(), INTERVAL 20 HOUR),NULL,'menunggu'),
(1,1,6,DATE_SUB(NOW(), INTERVAL 5 HOUR),DATE_ADD(CURDATE(), INTERVAL 2 DAY),'siap'),
(2,2,0,DATE_SUB(NOW(), INTERVAL 9 DAY),DATE_SUB(CURDATE(), INTERVAL 5 DAY),'diambil'),
(3,5,7,DATE_SUB(NOW(), INTERVAL 6 DAY),NULL,'batal');

INSERT INTO `reviews` (`id`,`biblio_id`,`member_id`,`rating`,`komentar`) VALUES
(0,0,0,5,'Ceritanya menyentuh dan penuh semangat. Wajib baca!'),
(1,0,1,4,'Bahasanya indah, cocok untuk semua umur.'),
(2,3,4,5,'Bantu banget buat belajar mengelola emosi.'),
(3,6,2,4,'Materinya runtut, contoh kodenya mudah dipraktikkan.'),
(4,9,0,5,'Berat tapi sangat layak dibaca.'),
(5,4,3,4,'Praktis dan mudah diterapkan.');

INSERT INTO `announcements` (`id`,`judul`,`isi`,`tgl`,`aktif`) VALUES
(0,'Selamat datang di INDO BOX','Sistem otomasi perpustakaan INDO BOX kini hadir. Cari koleksi, baca PDF-nya langsung, dan reservasi buku favoritmu secara online.',CURDATE(),1),
(1,'Reservasi online sudah dibuka','Anggota dapat mereservasi buku yang sedang dipinjam. Buku yang siap diambil ditahan selama 2 hari kerja.',DATE_SUB(CURDATE(), INTERVAL 3 DAY),1),
(2,'Jam layanan hari Sabtu','Mulai bulan ini layanan hari Sabtu buka pukul 09.00 - 13.00 WIB.',DATE_SUB(CURDATE(), INTERVAL 10 DAY),1);

INSERT INTO `visits` (`id`,`member_id`,`nama`,`keperluan`,`waktu`) VALUES
(0,1,'Bima Prasetyo','Mengembalikan',DATE_SUB(NOW(), INTERVAL 8 HOUR)),
(1,2,'Citra Maharani','Belajar',DATE_SUB(NOW(), INTERVAL 10 HOUR)),
(2,4,'Eka Putri Lestari','Membaca',DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(3,4,'Eka Putri Lestari','Membaca',DATE_SUB(NOW(), INTERVAL 29 HOUR)),
(4,1,'Bima Prasetyo','Mengembalikan',DATE_SUB(NOW(), INTERVAL 16 HOUR)),
(5,7,'Hendra Wijaya','Membaca',DATE_SUB(NOW(), INTERVAL 44 HOUR)),
(6,6,'Gita Safitri','Mengembalikan',DATE_SUB(NOW(), INTERVAL 17 HOUR)),
(7,0,'Aulia Rahmawati','Meminjam',DATE_SUB(NOW(), INTERVAL 31 HOUR)),
(8,1,'Bima Prasetyo','Meminjam',DATE_SUB(NOW(), INTERVAL 34 HOUR)),
(9,0,'Aulia Rahmawati','Meminjam',DATE_SUB(NOW(), INTERVAL 26 HOUR)),
(10,4,'Eka Putri Lestari','Membaca',DATE_SUB(NOW(), INTERVAL 40 HOUR)),
(11,3,'Dimas Anggara','Mengembalikan',DATE_SUB(NOW(), INTERVAL 58 HOUR)),
(12,2,'Citra Maharani','Mengembalikan',DATE_SUB(NOW(), INTERVAL 45 HOUR)),
(13,0,'Aulia Rahmawati','Mengembalikan',DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(14,0,'Aulia Rahmawati','Membaca',DATE_SUB(NOW(), INTERVAL 65 HOUR)),
(15,3,'Dimas Anggara','Meminjam',DATE_SUB(NOW(), INTERVAL 61 HOUR));

INSERT INTO `settings` (`id`,`k`,`v`) VALUES
(0,'nama_perpustakaan','INDO BOX'),
(1,'tagline','Satu kotak, sejuta cerita.'),
(2,'alamat','Jl. Pustaka No. 1, Semarang, Jawa Tengah'),
(3,'telepon','(024) 555-0123'),
(4,'email','halo@indobox.test'),
(5,'jam_buka','Senin - Jumat: 08.00 - 16.00 WIB
Sabtu: 09.00 - 13.00 WIB'),
(6,'hari_siap_ambil','2'),
(7,'maks_reservasi','3');

INSERT INTO `activity_log` (`id`,`pengguna`,`aksi`) VALUES (0,'sistem','Database INDO BOX berhasil diimpor');

SET FOREIGN_KEY_CHECKS = 1;
