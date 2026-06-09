-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 20, 2026 at 06:39 PM
-- Server version: 10.11.16-MariaDB-cll-lve
-- PHP Version: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `xfag9686_jejak_kampus`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`xfag9686`@`localhost` PROCEDURE `sp_cek_bentrokan_jadwal` (IN `p_kelas_id` INT, IN `p_hari` ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), IN `p_jam_ke_id` INT, IN `p_ruangan_id` INT, IN `p_dosen_id` INT, IN `p_tahun_akademik_id` INT, IN `p_exclude_jadwal_id` INT)   BEGIN
    -- Cek bentrokan ruangan
    SELECT 
        'ruangan' as jenis_bentrokan,
        j.id as jadwal_id,
        k.nama_kelas,
        mk.nama_mk,
        r.nama_ruangan
    FROM jadwal j
    JOIN kelas k ON j.kelas_id = k.id
    JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
    JOIN ruangan r ON j.ruangan_id = r.id
    WHERE j.ruangan_id = p_ruangan_id
      AND j.hari = p_hari
      AND j.jam_ke_id = p_jam_ke_id
      AND j.tahun_akademik_id = p_tahun_akademik_id
      AND (p_exclude_jadwal_id IS NULL OR j.id != p_exclude_jadwal_id)
    
    UNION ALL
    
    -- Cek bentrokan dosen
    SELECT 
        'dosen' as jenis_bentrokan,
        j.id as jadwal_id,
        k.nama_kelas,
        mk.nama_mk,
        r.nama_ruangan
    FROM jadwal j
    JOIN kelas k ON j.kelas_id = k.id
    JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
    JOIN ruangan r ON j.ruangan_id = r.id
    WHERE j.dosen_id = p_dosen_id
      AND j.hari = p_hari
      AND j.jam_ke_id = p_jam_ke_id
      AND j.tahun_akademik_id = p_tahun_akademik_id
      AND (p_exclude_jadwal_id IS NULL OR j.id != p_exclude_jadwal_id)
    
    UNION ALL
    
    -- Cek bentrokan kelas
    SELECT 
        'kelas' as jenis_bentrokan,
        j.id as jadwal_id,
        k.nama_kelas,
        mk.nama_mk,
        r.nama_ruangan
    FROM jadwal j
    JOIN kelas k ON j.kelas_id = k.id
    JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
    JOIN ruangan r ON j.ruangan_id = r.id
    WHERE j.kelas_id = p_kelas_id
      AND j.hari = p_hari
      AND j.jam_ke_id = p_jam_ke_id
      AND j.tahun_akademik_id = p_tahun_akademik_id
      AND (p_exclude_jadwal_id IS NULL OR j.id != p_exclude_jadwal_id);
END$$

CREATE DEFINER=`xfag9686`@`localhost` PROCEDURE `sp_cek_bentrokan_rentang` (IN `p_tahun_akademik_id` INT, IN `p_hari` VARCHAR(10), IN `p_jam_mulai_ke` INT, IN `p_sks` INT, IN `p_ruangan_id` INT, IN `p_dosen_id` INT, IN `p_kelas_id` INT, IN `p_exclude_jadwal_id` INT)   BEGIN
    DECLARE v_jam_ke INT;
    DECLARE v_jam_selesai_ke INT;
    DECLARE v_bentrokan_ditemukan INT DEFAULT 0;
    
    SET v_jam_selesai_ke = p_jam_mulai_ke + p_sks - 1;
    SET v_jam_ke = p_jam_mulai_ke;
    
    -- Cek apakah melewati jam istirahat (jam 6 ke 7)
    IF p_jam_mulai_ke <= 6 AND v_jam_selesai_ke >= 7 THEN
        SELECT 'melewati_istirahat' as jenis_error, 
               'Jadwal tidak boleh melewati jam istirahat (12:00-13:00)' as pesan,
               NULL as jam_bentrokan;
    ELSEIF v_jam_selesai_ke > 12 THEN
        SELECT 'melebihi_batas' as jenis_error, 
               'Jadwal melebihi jam ke-12' as pesan,
               NULL as jam_bentrokan;
    ELSE
        loop_cek: LOOP
            IF v_jam_ke > v_jam_selesai_ke THEN
                LEAVE loop_cek;
            END IF;
            
            -- Cek bentrokan ruangan
            IF p_ruangan_id IS NOT NULL AND p_ruangan_id > 0 THEN
                IF EXISTS (
                    SELECT 1
                    FROM jadwal j
                    JOIN kelas k ON j.kelas_id = k.id
                    JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
                    JOIN ruangan r ON j.ruangan_id = r.id
                    JOIN jam_ke jk ON j.jam_ke_id = jk.id
                    WHERE j.tahun_akademik_id = p_tahun_akademik_id
                      AND j.hari = p_hari
                      AND j.ruangan_id = p_ruangan_id
                      AND (jk.jam_ke <= v_jam_ke AND (jk.jam_ke + mk.sks - 1) >= v_jam_ke)
                      AND (p_exclude_jadwal_id IS NULL OR j.id != p_exclude_jadwal_id)
                ) THEN
                    SELECT 'ruangan' as jenis_error, 
                           CONCAT('Jam ke-', v_jam_ke, ': Ruangan sudah digunakan') as pesan,
                           v_jam_ke as jam_bentrokan;
                    SET v_bentrokan_ditemukan = 1;
                    LEAVE loop_cek;
                END IF;
            END IF;
            
            -- Cek bentrokan dosen
            IF p_dosen_id IS NOT NULL AND p_dosen_id > 0 THEN
                IF EXISTS (
                    SELECT 1
                    FROM jadwal j
                    JOIN kelas k ON j.kelas_id = k.id
                    JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
                    JOIN dosen d ON j.dosen_id = d.id
                    JOIN jam_ke jk ON j.jam_ke_id = jk.id
                    WHERE j.tahun_akademik_id = p_tahun_akademik_id
                      AND j.hari = p_hari
                      AND j.dosen_id = p_dosen_id
                      AND (jk.jam_ke <= v_jam_ke AND (jk.jam_ke + mk.sks - 1) >= v_jam_ke)
                      AND (p_exclude_jadwal_id IS NULL OR j.id != p_exclude_jadwal_id)
                ) THEN
                    SELECT 'dosen' as jenis_error, 
                           CONCAT('Jam ke-', v_jam_ke, ': Dosen sudah mengajar') as pesan,
                           v_jam_ke as jam_bentrokan;
                    SET v_bentrokan_ditemukan = 1;
                    LEAVE loop_cek;
                END IF;
            END IF;
            
            -- Cek bentrokan kelas
            IF p_kelas_id IS NOT NULL AND p_kelas_id > 0 THEN
                IF EXISTS (
                    SELECT 1
                    FROM jadwal j
                    JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
                    LEFT JOIN ruangan r ON j.ruangan_id = r.id
                    JOIN jam_ke jk ON j.jam_ke_id = jk.id
                    WHERE j.tahun_akademik_id = p_tahun_akademik_id
                      AND j.hari = p_hari
                      AND j.kelas_id = p_kelas_id
                      AND (jk.jam_ke <= v_jam_ke AND (jk.jam_ke + mk.sks - 1) >= v_jam_ke)
                      AND (p_exclude_jadwal_id IS NULL OR j.id != p_exclude_jadwal_id)
                ) THEN
                    SELECT 'kelas' as jenis_error, 
                           CONCAT('Jam ke-', v_jam_ke, ': Kelas sudah ada jadwal') as pesan,
                           v_jam_ke as jam_bentrokan;
                    SET v_bentrokan_ditemukan = 1;
                    LEAVE loop_cek;
                END IF;
            END IF;
            
            SET v_jam_ke = v_jam_ke + 1;
        END LOOP loop_cek;
        
        IF v_bentrokan_ditemukan = 0 THEN
            SELECT 'sukses' as jenis_error, 
                   'Tidak ada bentrokan' as pesan,
                   NULL as jam_bentrokan;
        END IF;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `sesi_id` int(11) DEFAULT NULL,
  `mahasiswa_id` int(11) DEFAULT NULL,
  `waktu_absen` timestamp NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('hadir','telat','alpha') DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id`, `sesi_id`, `mahasiswa_id`, `waktu_absen`, `latitude`, `longitude`, `status`, `keterangan`) VALUES
(32, 22, 40, '2026-05-19 04:57:51', -7.31638500, 112.72545900, 'hadir', NULL),
(33, 22, 5, '2026-05-19 04:58:50', -7.31637380, 112.72545420, 'hadir', NULL),
(34, 22, 13, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(35, 22, 16, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(36, 22, 21, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(37, 22, 36, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(38, 22, 17, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(39, 22, 42, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(40, 22, 24, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(41, 22, 25, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(42, 22, 35, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(43, 22, 34, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(44, 22, 33, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(45, 22, 23, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(46, 22, 29, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(47, 22, 11, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(48, 22, 19, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(49, 22, 15, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(50, 22, 5, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(51, 22, 18, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(52, 22, 38, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(53, 22, 6, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(54, 22, 26, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(55, 22, 22, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(56, 22, 30, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(57, 22, 9, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(58, 22, 28, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(59, 22, 40, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(60, 22, 31, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(61, 22, 37, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(62, 22, 27, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(63, 22, 41, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(64, 22, 32, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(65, 22, 39, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL),
(66, 22, 20, '2026-05-19 04:59:32', NULL, NULL, 'hadir', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `approval`
--

CREATE TABLE `approval` (
  `id` int(11) NOT NULL,
  `izin_id` int(11) DEFAULT NULL,
  `dosen_id` int(11) DEFAULT NULL,
  `status` enum('disetujui','ditolak') DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `approval`
--

INSERT INTO `approval` (`id`, `izin_id`, `dosen_id`, `status`, `catatan`, `approved_at`) VALUES
(1, 11, 5, 'disetujui', 'Auto-approved saat sesi ditutup oleh sistem', '2026-04-30 07:34:31'),
(2, 13, 5, 'disetujui', '', '2026-05-07 05:30:19'),
(3, 12, 5, 'disetujui', 'Auto-approved saat sesi ditutup oleh sistem', '2026-05-07 08:01:43'),
(4, 15, 5, 'disetujui', 'Auto-approved saat sesi ditutup oleh sistem', '2026-05-12 16:42:24'),
(5, 14, 5, 'disetujui', 'Auto-approved saat sesi ditutup oleh sistem', '2026-05-12 16:42:26');

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nidn` varchar(20) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `status` enum('aktif','nonaktif','cuti','pensiun') DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id`, `user_id`, `nidn`, `nama`, `tanggal_lahir`, `jenis_kelamin`, `status`, `created_at`) VALUES
(3, 12, '1960040419870110012', 'Prof. Dr. Ekohariadi, M.Pd.', '2026-04-07', 'L', 'aktif', '2026-04-22 12:16:55'),
(4, 13, '198410272015042001', 'Dr. Yeni Anistyasari, S.Pd., M.Kom.', '2026-04-02', 'P', 'aktif', '2026-04-22 12:16:55'),
(5, 14, '199207122024061001', 'Fazrin Mauza Dwi Zuhudi, S.Pd., M.MT.', '2005-09-05', 'L', 'aktif', '2026-04-22 12:16:55');

--
-- Triggers `dosen`
--
DELIMITER $$
CREATE TRIGGER `tr_dosen_delete` AFTER DELETE ON `dosen` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
    VALUES ('Hapus', 'dosen', OLD.id, CONCAT('Hapus dosen: ', OLD.nama, ' (NIDN: ', OLD.nidn, ')'));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_dosen_insert` AFTER INSERT ON `dosen` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
    VALUES ('Tambah', 'dosen', NEW.id, CONCAT('Tambah dosen: ', NEW.nama, ' (NIDN: ', NEW.nidn, ')'));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_dosen_update` AFTER UPDATE ON `dosen` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
    VALUES ('Edit', 'dosen', NEW.id, CONCAT('Edit dosen: ', NEW.nama, ' (NIDN: ', NEW.nidn, ')'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `izin`
--

CREATE TABLE `izin` (
  `id` int(11) NOT NULL,
  `mahasiswa_id` int(11) DEFAULT NULL,
  `jadwal_id` int(11) DEFAULT NULL,
  `pertemuan_ke` int(11) DEFAULT NULL,
  `tanggal_izin` date DEFAULT NULL,
  `sesi_id` int(11) DEFAULT NULL,
  `jenis` enum('sakit','izin') DEFAULT NULL,
  `file_surat` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `izin`
--

INSERT INTO `izin` (`id`, `mahasiswa_id`, `jadwal_id`, `pertemuan_ke`, `tanggal_izin`, `sesi_id`, `jenis`, `file_surat`, `keterangan`, `status`, `created_at`) VALUES
(11, 5, NULL, 3, '2026-04-30', 16, 'sakit', 'izin_10_1777534308.pdf', 'hehe sakit pak', 'disetujui', '2026-04-30 07:31:48'),
(12, 5, NULL, 4, '2026-04-30', 17, 'izin', 'izin_10_1777539157.png', 'bbb', 'disetujui', '2026-04-30 08:52:37'),
(13, 5, 3, 5, '2026-05-02', NULL, 'izin', 'izin_10_1777541505.jpg', 'mohon izin mengurus surat administrasi domisili', 'disetujui', '2026-04-30 09:31:45'),
(14, 15, NULL, 6, '2026-05-13', 20, 'sakit', NULL, 'Izin Sakit\r\nYth. Bapak/Ibu Dosen [Nama Dosen],\r\nDengan hormat,\r\nMelalui surat ini, saya [Nama Mahasiswa], dengan NIM [Nomor Induk Mahasiswa], dari program studi [Nama Program Studi], ingin memohon izin tidak dapat mengikuti perkuliahan pada hari ini, [Tanggal], dikarenakan kondisi kesehatan yang kurang baik (sakit).\r\nSaya akan berusaha untuk segera pulih dan mengikuti perkuliahan kembali setelah kondisi saya membaik. Apabila ada tugas atau materi yang terlewat, saya akan segera mengejarnya.\r\nTerima kasih atas perhatian dan pengertian Bapak/Ibu.\r\nHormat saya,\r\n[Nama Mahasiswa]\r\n[Nomor Telepon Mahasiswa]\r\n[Alamat Email Mahasiswa]', 'disetujui', '2026-05-12 15:10:04'),
(15, 15, NULL, NULL, NULL, 19, 'izin', NULL, NULL, 'disetujui', '2026-05-12 16:34:48'),
(16, 15, 3, 7, '2026-05-13', NULL, 'sakit', 'izin_28_1778661806.jpg', 'Izin sakit deman', 'pending', '2026-05-13 08:43:26'),
(17, 20, 5, 3, '2026-05-19', NULL, 'sakit', 'izin_33_1779163229.jpg', 'sange berat', 'pending', '2026-05-19 04:00:29'),
(18, 19, 5, 16, '2026-05-19', NULL, 'sakit', NULL, '-', 'pending', '2026-05-19 04:38:26');

--
-- Triggers `izin`
--
DELIMITER $$
CREATE TRIGGER `tr_izin_insert` AFTER INSERT ON `izin` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
    VALUES ('Tambah', 'izin', NEW.id, CONCAT('Pengajuan ', NEW.jenis, ' oleh mahasiswa_id:', NEW.mahasiswa_id, ' untuk jadwal_id:', NEW.jadwal_id, ' pertemuan-', NEW.pertemuan_ke));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_izin_update` AFTER UPDATE ON `izin` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
        VALUES ('Update Status', 'izin', NEW.id, CONCAT('Status izin berubah: ', OLD.status, ' -> ', NEW.status));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int(11) NOT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `mata_kuliah_id` int(11) DEFAULT NULL,
  `dosen_id` int(11) DEFAULT NULL,
  `jam_ke_id` int(11) DEFAULT NULL,
  `ruangan_id` int(11) DEFAULT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') DEFAULT NULL,
  `tahun_akademik_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id`, `kelas_id`, `mata_kuliah_id`, `dosen_id`, `jam_ke_id`, `ruangan_id`, `hari`, `tahun_akademik_id`) VALUES
(3, 2, 6, 5, 4, 6, 'Selasa', 2),
(4, 6, 3, 3, 1, 1, 'Kamis', 2),
(5, 2, 3, 3, 2, 6, 'Kamis', 2),
(7, 2, 7, 5, 10, 6, 'Rabu', 2);

-- --------------------------------------------------------

--
-- Table structure for table `jam_ke`
--

CREATE TABLE `jam_ke` (
  `id` int(11) NOT NULL,
  `jam_ke` int(11) NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `keterangan` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jam_ke`
--

INSERT INTO `jam_ke` (`id`, `jam_ke`, `jam_mulai`, `jam_selesai`, `keterangan`) VALUES
(1, 1, '07:00:00', '07:50:00', 'Jam ke-1'),
(2, 2, '07:50:00', '08:40:00', 'Jam ke-2'),
(3, 3, '08:40:00', '09:30:00', 'Jam ke-3'),
(4, 4, '09:30:00', '10:20:00', 'Jam ke-4'),
(5, 5, '10:20:00', '11:10:00', 'Jam ke-5'),
(6, 6, '11:10:00', '12:00:00', 'Jam ke-6'),
(7, 7, '13:00:00', '13:50:00', 'Jam ke-7 (Setelah Istirahat)'),
(8, 8, '13:50:00', '14:40:00', 'Jam ke-8'),
(9, 9, '14:40:00', '15:30:00', 'Jam ke-9'),
(10, 10, '15:30:00', '16:20:00', 'Jam ke-10'),
(11, 11, '16:20:00', '17:10:00', 'Jam ke-11'),
(12, 12, '17:10:00', '18:00:00', 'Jam ke-12');

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id` int(11) NOT NULL,
  `nama_kelas` varchar(50) DEFAULT NULL,
  `jurusan` varchar(100) DEFAULT NULL,
  `angkatan` year(4) DEFAULT NULL,
  `tahun_akademik_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `jurusan`, `angkatan`, `tahun_akademik_id`, `created_at`) VALUES
(2, 'PTI 2024C', 'Pendidikan Teknologi Informasi', '2024', 2, '2026-04-22 12:58:08'),
(4, 'PTI 2024A', 'Pendidikan Teknologi Informasi', '2024', 2, '2026-04-22 12:58:08'),
(5, 'PTI 2025A', 'Pendidikan Teknologi Informasi', '2025', 2, '2026-04-22 12:58:08'),
(6, 'PTI 2023A', 'Pendidikan Teknologi Informasi', '2023', 2, '2026-04-22 12:58:08'),
(9, 'PTI 2026Cj', 'Teknik Informatika', '2076', 2, '2026-04-22 12:58:08'),
(10, 'PTI 2026Cytyt', 'Teknik Informatikay', '2076', NULL, '2026-04-22 12:58:52'),
(11, 'PTI 2027C', 'Pendidikan Teknologi Informasi', '2025', 2, '2026-04-23 01:43:53');

--
-- Triggers `kelas`
--
DELIMITER $$
CREATE TRIGGER `tr_kelas_delete` AFTER DELETE ON `kelas` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
    VALUES ('Hapus', 'kelas', OLD.id, CONCAT('Hapus kelas: ', OLD.nama_kelas, ' - ', OLD.jurusan));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_kelas_insert` AFTER INSERT ON `kelas` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
    VALUES ('Tambah', 'kelas', NEW.id, CONCAT('Tambah kelas: ', NEW.nama_kelas, ' - ', NEW.jurusan));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_kelas_update` AFTER UPDATE ON `kelas` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
    VALUES ('Edit', 'kelas', NEW.id, CONCAT('Edit kelas: ', NEW.nama_kelas, ' - ', NEW.jurusan));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL,
  `aksi` varchar(50) NOT NULL,
  `tabel` varchar(50) NOT NULL,
  `data_id` int(11) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `pelaku` varchar(100) DEFAULT 'Admin',
  `waktu` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id`, `aksi`, `tabel`, `data_id`, `deskripsi`, `pelaku`, `waktu`) VALUES
(1, 'Tambah', 'kelas', 11, 'Tambah kelas: PTI 2027C - Pendidikan Teknologi Informasi', 'Admin', '2026-04-23 01:43:53'),
(2, 'Edit', 'mahasiswa', 6, 'Edit mahasiswa: IQBAL AMRI SYABANA1 (24050974068)', 'Admin', '2026-04-23 01:51:10'),
(3, 'Edit', 'mahasiswa', 13, 'Edit mahasiswa: Ahmad Hadji Suparjo2 (24050974099)', 'Admin', '2026-04-23 02:08:09'),
(4, 'Edit', 'mahasiswa', 14, 'Edit mahasiswa: Fata Favian Cannavaro4 (240509740904)', 'Admin', '2026-04-23 02:08:21'),
(5, 'Edit', 'mahasiswa', 11, 'Edit mahasiswa: Fata Favian Cannavaro3 (24050974069)', 'Admin', '2026-04-23 02:08:28'),
(6, 'Edit', 'mahasiswa', 9, 'Edit mahasiswa: MIKHAEL YULI ANANDA ELVAN PERMANA5 (24050974086)', 'Admin', '2026-04-23 02:08:43'),
(7, 'Edit', 'mahasiswa', 12, 'Edit mahasiswa: Farrell Ahmed Dimitrie Dhiaul Aulia9 (24050974084)', 'Admin', '2026-04-23 02:08:51'),
(8, 'Edit', 'dosen', 3, 'Edit dosen: Prof. Dr. Ekohariadi, M.Pd. (NIDN: 1960040419870110012)', 'Admin', '2026-04-23 02:09:41'),
(9, 'Edit', 'dosen', 7, 'Edit dosen: Ahmed Dimitrie Dhiaul Aulia S. T (NIDN: 12345678901)', 'Admin', '2026-04-23 02:09:57'),
(10, 'Edit', 'mata_kuliah', 7, 'Edit MK: Pemrograman Visual 1 (PEMVIS)', 'Admin', '2026-04-23 02:10:33'),
(11, 'Hapus', 'dosen', 7, 'Hapus dosen: Ahmed Dimitrie Dhiaul Aulia S. T (NIDN: 12345678901)', 'Admin', '2026-04-23 02:27:25'),
(12, 'Hapus', 'mahasiswa', 14, 'Hapus mahasiswa: Fata Favian Cannavaro4 (240509740904)', 'Admin', '2026-04-23 02:28:28'),
(13, 'Tambah', 'tahun_akademik', 4, 'Tambah tahun akademik: 2026/2027 Ganjil', 'Admin', '2026-04-23 08:17:05'),
(14, 'Edit', 'tahun_akademik', 1, 'Edit tahun akademik: 2025/2026 Ganjil (Status: nonaktif)', 'Admin', '2026-04-23 08:17:16'),
(15, 'Edit', 'kelas', 10, 'Edit kelas: PTI 2026Cytyt - Teknik Informatikay', 'Admin', '2026-04-23 08:17:16'),
(16, 'Edit', 'mahasiswa', 12, 'Edit mahasiswa: Farrell Ahmed Dimitrie Dhiaul Aulia9 (24050974084)', 'Admin', '2026-04-23 08:23:38'),
(17, 'Hapus', 'dosen', 8, 'Hapus dosen: Naruto Uzumaki (NIDN: 1.23E+12)', 'Admin', '2026-04-30 06:38:59'),
(18, 'Tambah', 'izin', 11, 'Pengajuan sakit oleh mahasiswa_id:5 untuk jadwal_id:3 pertemuan-3', 'Admin', '2026-04-30 07:31:48'),
(19, 'Update Status', 'izin', 11, 'Status izin berubah: pending -> disetujui', 'Admin', '2026-04-30 07:34:31'),
(20, 'Tambah', 'izin', 12, 'Pengajuan izin oleh mahasiswa_id:5 untuk jadwal_id:3 pertemuan-4', 'Admin', '2026-04-30 08:52:37'),
(21, 'Tambah', 'izin', 13, 'Pengajuan izin oleh mahasiswa_id:5 untuk jadwal_id:3 pertemuan-5', 'Admin', '2026-04-30 09:31:45'),
(22, 'Update Status', 'izin', 13, 'Status izin berubah: pending -> disetujui', 'Admin', '2026-05-07 05:30:19'),
(23, 'Update Status', 'izin', 12, 'Status izin berubah: pending -> disetujui', 'Admin', '2026-05-07 08:01:43'),
(24, 'Tambah', 'mahasiswa', 15, 'Tambah mahasiswa: Fazrin Mauza (24050974000)', 'Admin', '2026-05-11 09:10:15'),
(25, 'Tambah', 'izin', 14, 'Pengajuan sakit oleh mahasiswa_id:15 untuk jadwal_id:3 pertemuan-6', 'Admin', '2026-05-12 15:10:04'),
(26, 'Tambah', 'izin', 15, NULL, 'Admin', '2026-05-12 16:34:48'),
(27, 'Update Status', 'izin', 14, 'Status izin berubah: pending -> disetujui', 'Admin', '2026-05-12 16:42:26'),
(28, 'Tambah', 'izin', 16, 'Pengajuan sakit oleh mahasiswa_id:15 untuk jadwal_id:3 pertemuan-7', 'Admin', '2026-05-13 08:43:26'),
(29, 'Edit', 'dosen', 5, 'Edit dosen: Rizky Basatha, S.Pd., M.MT. (NIDN: 199207122024061001)', 'Admin', '2026-05-13 11:39:00'),
(30, 'Edit', 'dosen', 5, 'Edit dosen: Rizky Basatha, S.Pd., M.MT. (NIDN: 199207122024061001)', 'Admin', '2026-05-13 11:45:04'),
(31, 'Tambah', 'mahasiswa', 16, 'Tambah mahasiswa: AINI INTAN SAYLENDRA (24050974063)', 'Admin', '2026-05-19 00:43:43'),
(32, 'Tambah', 'mahasiswa', 17, 'Tambah mahasiswa: AZIZA DAMAYANTI (24050974064)', 'Admin', '2026-05-19 00:43:43'),
(33, 'Tambah', 'mahasiswa', 18, 'Tambah mahasiswa: INDRA BACHTIAR ZAKARIA (24050974065)', 'Admin', '2026-05-19 00:43:43'),
(34, 'Tambah', 'mahasiswa', 19, 'Tambah mahasiswa: FATHERA ARDILA (24050974066)', 'Admin', '2026-05-19 00:43:43'),
(35, 'Tambah', 'mahasiswa', 20, 'Tambah mahasiswa: YUSUF RAMADHAN MORTI SONDANG SIMANJUNTAK (24050974067)', 'Admin', '2026-05-19 00:43:43'),
(36, 'Tambah', 'mahasiswa', 21, 'Tambah mahasiswa: AJI NUGROHO PARANG (24050974070)', 'Admin', '2026-05-19 00:43:43'),
(37, 'Tambah', 'mahasiswa', 22, 'Tambah mahasiswa: KAROLINA HESTI UTAMI (24050974071)', 'Admin', '2026-05-19 00:43:43'),
(38, 'Tambah', 'mahasiswa', 23, 'Tambah mahasiswa: ERICHA OKTI VIRLYA MEYJIE (24050974072)', 'Admin', '2026-05-19 00:43:43'),
(39, 'Tambah', 'mahasiswa', 24, 'Tambah mahasiswa: DEA AIS RENATA (24050974073)', 'Admin', '2026-05-19 00:43:43'),
(40, 'Tambah', 'mahasiswa', 25, 'Tambah mahasiswa: DEVINA CHRISTINE PUTRI LOKO (24050974074)', 'Admin', '2026-05-19 00:43:43'),
(41, 'Tambah', 'mahasiswa', 26, 'Tambah mahasiswa: KARINA GATI RAHAYU (24050974075)', 'Admin', '2026-05-19 00:43:43'),
(42, 'Tambah', 'mahasiswa', 27, 'Tambah mahasiswa: RINA DWI APRI LESTARI (24050974076)', 'Admin', '2026-05-19 00:43:43'),
(43, 'Tambah', 'mahasiswa', 28, 'Tambah mahasiswa: MUHAMAD SYAMIL ATILLAH (24050974077)', 'Admin', '2026-05-19 00:43:43'),
(44, 'Tambah', 'mahasiswa', 29, 'Tambah mahasiswa: FANDI PRASETYA (24050974078)', 'Admin', '2026-05-19 00:43:43'),
(45, 'Tambah', 'mahasiswa', 30, 'Tambah mahasiswa: MAEFA NURDIANA PUTRI (24050974079)', 'Admin', '2026-05-19 00:43:43'),
(46, 'Tambah', 'mahasiswa', 31, 'Tambah mahasiswa: NABELLA PUTRI ADELLYA (24050974080)', 'Admin', '2026-05-19 00:43:43'),
(47, 'Tambah', 'mahasiswa', 32, 'Tambah mahasiswa: SANDI ANDIKA PUTRA (24050974081)', 'Admin', '2026-05-19 00:43:43'),
(48, 'Tambah', 'mahasiswa', 33, 'Tambah mahasiswa: ELSA CHINTYA AYU AGUSTYANNINGSIH (24050974082)', 'Admin', '2026-05-19 00:43:43'),
(49, 'Tambah', 'mahasiswa', 34, 'Tambah mahasiswa: EKA SABRINA (24050974083)', 'Admin', '2026-05-19 00:43:43'),
(50, 'Tambah', 'mahasiswa', 35, 'Tambah mahasiswa: DHIFA BARATA PUTRA (24050974085)', 'Admin', '2026-05-19 00:43:43'),
(51, 'Tambah', 'mahasiswa', 36, 'Tambah mahasiswa: ALFIAN WIDHI HARJO (24050974087)', 'Admin', '2026-05-19 00:43:43'),
(52, 'Tambah', 'mahasiswa', 37, 'Tambah mahasiswa: PUTRI NABILAH (24050974088)', 'Admin', '2026-05-19 00:43:43'),
(53, 'Tambah', 'mahasiswa', 38, 'Tambah mahasiswa: INTAN AMELIA MIRU (24050974089)', 'Admin', '2026-05-19 00:43:43'),
(54, 'Tambah', 'mahasiswa', 39, 'Tambah mahasiswa: VELISA ALYA QURAINI (24050974091)', 'Admin', '2026-05-19 00:43:43'),
(55, 'Tambah', 'mahasiswa', 40, 'Tambah mahasiswa: MUHAMMAD AQBIL BARKAH PUTRA MURTADHO (24050974092)', 'Admin', '2026-05-19 00:43:43'),
(56, 'Tambah', 'mahasiswa', 41, 'Tambah mahasiswa: SAFINATUL LATIFAH (24050974093)', 'Admin', '2026-05-19 00:43:43'),
(57, 'Tambah', 'mahasiswa', 42, 'Tambah mahasiswa: BINTANG ALIEF ARTHA MEI VIRA (24050974094)', 'Admin', '2026-05-19 00:43:43'),
(58, 'Edit', 'dosen', 5, 'Edit dosen: Fazrin Mauza Dwi Zuhudi, S.Pd., M.MT. (NIDN: 199207122024061001)', 'Admin', '2026-05-19 00:48:02'),
(59, 'Tambah', 'izin', 17, 'Pengajuan sakit oleh mahasiswa_id:20 untuk jadwal_id:5 pertemuan-3', 'Admin', '2026-05-19 04:00:29'),
(60, 'Tambah', 'izin', 18, 'Pengajuan sakit oleh mahasiswa_id:19 untuk jadwal_id:5 pertemuan-16', 'Admin', '2026-05-19 04:38:26');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nim` varchar(20) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `status` enum('aktif','cuti','lulus','dropout') DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `tanggal_lahir`, `jenis_kelamin`, `kelas_id`, `status`, `created_at`) VALUES
(5, 10, '24050974090', 'Fazrin Mauza Dwi Zuhudi', '2005-09-05', 'L', 2, 'aktif', '2026-04-22 12:16:20'),
(6, 11, '24050974068', 'IQBAL AMRI SYABANA1', '2026-04-10', 'L', 2, 'aktif', '2026-04-22 12:16:20'),
(9, 20, '24050974086', 'MIKHAEL YULI ANANDA ELVAN PERMANA5', '2026-04-08', 'L', 2, 'aktif', '2026-04-22 12:16:20'),
(11, 22, '24050974069', 'Fata Favian Cannavaro3', '2006-07-20', 'L', 2, 'aktif', '2026-04-22 12:16:20'),
(12, 23, '24050974084', 'Farrell Ahmed Dimitrie Dhiaul Aulia9', '2005-07-03', 'L', 2, 'cuti', '2026-04-22 12:16:20'),
(13, 25, '24050974099', 'Ahmad Hadji Suparjo2', '2005-07-03', 'L', 2, 'aktif', '2026-04-22 12:16:20'),
(15, 28, '24050974000', 'Fazrin Mauza', '2005-09-05', 'L', 2, 'aktif', '2026-05-11 09:10:15'),
(16, 29, '24050974063', 'AINI INTAN SAYLENDRA', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(17, 30, '24050974064', 'AZIZA DAMAYANTI', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(18, 31, '24050974065', 'INDRA BACHTIAR ZAKARIA', NULL, 'L', 2, 'aktif', '2026-05-19 00:43:43'),
(19, 32, '24050974066', 'FATHERA ARDILA', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(20, 33, '24050974067', 'YUSUF RAMADHAN MORTI SONDANG SIMANJUNTAK', NULL, 'L', 2, 'aktif', '2026-05-19 00:43:43'),
(21, 34, '24050974070', 'AJI NUGROHO PARANG', NULL, 'L', 2, 'aktif', '2026-05-19 00:43:43'),
(22, 35, '24050974071', 'KAROLINA HESTI UTAMI', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(23, 36, '24050974072', 'ERICHA OKTI VIRLYA MEYJIE', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(24, 37, '24050974073', 'DEA AIS RENATA', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(25, 38, '24050974074', 'DEVINA CHRISTINE PUTRI LOKO', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(26, 39, '24050974075', 'KARINA GATI RAHAYU', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(27, 40, '24050974076', 'RINA DWI APRI LESTARI', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(28, 41, '24050974077', 'MUHAMAD SYAMIL ATILLAH', NULL, 'L', 2, 'aktif', '2026-05-19 00:43:43'),
(29, 42, '24050974078', 'FANDI PRASETYA', NULL, 'L', 2, 'aktif', '2026-05-19 00:43:43'),
(30, 43, '24050974079', 'MAEFA NURDIANA PUTRI', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(31, 44, '24050974080', 'NABELLA PUTRI ADELLYA', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(32, 45, '24050974081', 'SANDI ANDIKA PUTRA', NULL, 'L', 2, 'aktif', '2026-05-19 00:43:43'),
(33, 46, '24050974082', 'ELSA CHINTYA AYU AGUSTYANNINGSIH', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(34, 47, '24050974083', 'EKA SABRINA', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(35, 48, '24050974085', 'DHIFA BARATA PUTRA', NULL, 'L', 2, 'aktif', '2026-05-19 00:43:43'),
(36, 49, '24050974087', 'ALFIAN WIDHI HARJO', NULL, 'L', 2, 'aktif', '2026-05-19 00:43:43'),
(37, 50, '24050974088', 'PUTRI NABILAH', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(38, 51, '24050974089', 'INTAN AMELIA MIRU', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(39, 52, '24050974091', 'VELISA ALYA QURAINI', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(40, 53, '24050974092', 'MUHAMMAD AQBIL BARKAH PUTRA MURTADHO', NULL, 'L', 2, 'aktif', '2026-05-19 00:43:43'),
(41, 54, '24050974093', 'SAFINATUL LATIFAH', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43'),
(42, 55, '24050974094', 'BINTANG ALIEF ARTHA MEI VIRA', NULL, 'P', 2, 'aktif', '2026-05-19 00:43:43');

--
-- Triggers `mahasiswa`
--
DELIMITER $$
CREATE TRIGGER `tr_mahasiswa_delete` AFTER DELETE ON `mahasiswa` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
    VALUES ('Hapus', 'mahasiswa', OLD.id, CONCAT('Hapus mahasiswa: ', OLD.nama, ' (', OLD.nim, ')'));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_mahasiswa_insert` AFTER INSERT ON `mahasiswa` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
    VALUES ('Tambah', 'mahasiswa', NEW.id, CONCAT('Tambah mahasiswa: ', NEW.nama, ' (', NEW.nim, ')'));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_mahasiswa_update` AFTER UPDATE ON `mahasiswa` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
    VALUES ('Edit', 'mahasiswa', NEW.id, CONCAT('Edit mahasiswa: ', NEW.nama, ' (', NEW.nim, ')'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `mata_kuliah`
--

CREATE TABLE `mata_kuliah` (
  `id` int(11) NOT NULL,
  `kode_mk` varchar(20) DEFAULT NULL,
  `nama_mk` varchar(100) DEFAULT NULL,
  `sks` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `mata_kuliah`
--

INSERT INTO `mata_kuliah` (`id`, `kode_mk`, `nama_mk`, `sks`) VALUES
(2, 'DDK', 'Dasar-Dasar Kependidikan', 2),
(3, 'MP', 'Metodologi Penelitian', 3),
(4, 'TB', 'Teori Belajar', 2),
(5, 'PB', 'Perencanaan Pembelajaran', 2),
(6, 'PW', 'Pemrograman Web', 3),
(7, 'PEMVIS', 'Pemrograman Visual 1', 3);

--
-- Triggers `mata_kuliah`
--
DELIMITER $$
CREATE TRIGGER `tr_matakuliah_delete` AFTER DELETE ON `mata_kuliah` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
    VALUES ('Hapus', 'mata_kuliah', OLD.id, CONCAT('Hapus MK: ', OLD.nama_mk, ' (', OLD.kode_mk, ')'));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_matakuliah_insert` AFTER INSERT ON `mata_kuliah` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
    VALUES ('Tambah', 'mata_kuliah', NEW.id, CONCAT('Tambah MK: ', NEW.nama_mk, ' (', NEW.kode_mk, ') - ', NEW.sks, ' SKS'));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_matakuliah_update` AFTER UPDATE ON `mata_kuliah` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi)
    VALUES ('Edit', 'mata_kuliah', NEW.id, CONCAT('Edit MK: ', NEW.nama_mk, ' (', NEW.kode_mk, ')'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `ruangan`
--

CREATE TABLE `ruangan` (
  `id` int(11) NOT NULL,
  `kode_ruangan` varchar(20) NOT NULL,
  `nama_ruangan` varchar(100) NOT NULL,
  `kapasitas` int(11) DEFAULT NULL,
  `jenis` enum('kelas','lab','aula','studio') DEFAULT 'kelas',
  `lokasi` varchar(100) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ruangan`
--

INSERT INTO `ruangan` (`id`, `kode_ruangan`, `nama_ruangan`, `kapasitas`, `jenis`, `lokasi`, `status`) VALUES
(1, 'A10.01.18', 'Ruang 18 Lt.1', 40, 'kelas', 'Gedung A10 Lantai 1', 'aktif'),
(5, 'A10.04.20 AUDITORIUM', 'A10.04.20 AUDITORIUM', 200, 'aula', 'Gedung A10 Lantai 4', 'aktif'),
(6, 'A10.01.19', 'Ruang 19 Lt.1', 40, 'kelas', 'Gedung A10 Lantai 1', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `sesi_absensi`
--

CREATE TABLE `sesi_absensi` (
  `id` int(11) NOT NULL,
  `jadwal_id` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `pertemuan_ke` int(11) DEFAULT NULL,
  `qr_code` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `radius` int(11) DEFAULT NULL,
  `status` enum('aktif','selesai') DEFAULT 'aktif',
  `tahun_akademik_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sesi_absensi`
--

INSERT INTO `sesi_absensi` (`id`, `jadwal_id`, `tanggal`, `pertemuan_ke`, `qr_code`, `latitude`, `longitude`, `radius`, `status`, `tahun_akademik_id`) VALUES
(22, 3, '2026-05-19', 1, '{\"sesi_id\":22,\"token\":\"c2VzaToyMjoxNzc5Mjc1MzY2NzMy\",\"timestamp\":1779275366732}', -7.31628330, 112.72277730, 500, 'aktif', 2);

--
-- Triggers `sesi_absensi`
--
DELIMITER $$
CREATE TRIGGER `tr_sesi_after_insert` AFTER INSERT ON `sesi_absensi` FOR EACH ROW BEGIN
    UPDATE izin 
    SET sesi_id = NEW.id, jadwal_id = NULL
    WHERE jadwal_id = NEW.jadwal_id 
        AND pertemuan_ke = NEW.pertemuan_ke
        AND status = 'pending';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_sesi_before_update` BEFORE UPDATE ON `sesi_absensi` FOR EACH ROW BEGIN
    IF OLD.status = 'aktif' AND NEW.status = 'selesai' THEN
        
        -- ========================================
        -- 1. Auto-approve izin pending
        -- ========================================
        UPDATE izin SET status = 'disetujui' 
        WHERE sesi_id = NEW.id AND status = 'pending';
        
        -- ========================================
        -- 2. Catat approval ke tabel approval
        -- ========================================
        INSERT INTO approval (izin_id, dosen_id, status, catatan, approved_at)
        SELECT i.id, j.dosen_id, 'disetujui', 'Auto-approved saat sesi ditutup oleh sistem', NOW()
        FROM izin i
        JOIN sesi_absensi sa ON i.sesi_id = sa.id
        JOIN jadwal j ON sa.jadwal_id = j.id
        WHERE i.sesi_id = NEW.id 
            AND i.status = 'disetujui'
            AND NOT EXISTS (SELECT 1 FROM approval a WHERE a.izin_id = i.id);
        
        -- ========================================
        -- 3. Catat ALPHA untuk yang tidak hadir & tidak izin
        -- ========================================
        INSERT INTO absensi (sesi_id, mahasiswa_id, status, keterangan, waktu_absen)
        SELECT 
            NEW.id, 
            m.id, 
            'alpha', 
            'Tidak hadir dan tidak mengajukan izin',
            NOW()
        FROM mahasiswa m
        JOIN kelas k ON m.kelas_id = k.id
        JOIN jadwal j ON j.kelas_id = k.id
        WHERE j.id = NEW.jadwal_id
          AND m.status = 'aktif'
          AND NOT EXISTS (
              SELECT 1 FROM absensi a 
              WHERE a.sesi_id = NEW.id AND a.mahasiswa_id = m.id
          )
          AND NOT EXISTS (
              SELECT 1 FROM izin i 
              WHERE i.sesi_id = NEW.id 
              AND i.mahasiswa_id = m.id 
              AND i.status = 'disetujui'
          );
          
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

CREATE TABLE `session` (
  `id` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `token` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `session`
--

INSERT INTO `session` (`id`, `email`, `token`) VALUES
(11, '24050974084@mhs.unesa.ac.id', '6621eacef01c8d4bac503b9f4f3f02b68e2a475984e92908108289afe9322e35'),
(18, '24050974090@mhs.unesa.ac.id', '0d3d47df20295dfdbe52bac90c696c9109c0e995944bfeab13e970ff02e40de1'),
(20, '24050974090@mhs.unesa.ac.id', 'ef297b3ffb79ed67a7499a55eaee80a8ac23bb9a0a454ec04bf8d0f950be517d'),
(21, '24050974068@mhs.unesa.ac.id', '830d87139bef0d1f0b5c6d004d7bf5f2b77d14d6bbf95849a40efad303d98fef'),
(25, '24050974090@mhs.unesa.ac.id', '2b92b57233504e2cc7911200efd1696d9fc947375a70953e8e654cb84026ce45'),
(28, '24050974084@mhs.unesa.ac.id', 'f861e65946b56d6a1cc36ef3ebb496620e57884044f392e172bfb890f201ae9e'),
(29, '24050974084@mhs.unesa.ac.id', 'bbc58feeaecf7ca2325c4d26de8a60489adb7b7df0324ba39fec66da52b4ddd9'),
(36, '24050974084@mhs.unesa.ac.id', '1b6edd438013433776e8b3aa847384262147ac15ec783fbbe049c0c4f924f72b'),
(39, '24050974084@mhs.unesa.ac.id', 'bd7458ea751a56bf689b53da98e6662f91263ded6cc4a9fbdf28f9ab916b26cb'),
(40, 'ngodingseseruitu@gmail.com', '02d97a13351e07a49b0b4fbfec6526635ff1014ff9ebb259566b055f86bc2b76'),
(41, 'med.dmt2018@gmail.com', 'cbe38a3421088f0f65fc5837b0820a1570a42a4f74664f6a5ce7560d56e5ba7b'),
(44, '24050974090@mhs.unesa.ac.id', 'abf0ec7fdcfb0b87cec3ceee22b898db4086ee3a9c6aa9bdc50030b4f9830c7b'),
(51, '24050974068@mhs.unesa.ac.id', 'd117a924c152615b02599f9dbc4e19bda4e9608494c53e4262251cc422766298'),
(52, 'ngodingseseruitu@gmail.com', '16c61e5c2bee4cdb210c1b79bc92eb803e7fe0092d5e18d8fa8218431aaa928d'),
(53, '24050974068@mhs.unesa.ac.id', '24d16c4258e6cd85f4c07e2bc71fe1785ebc63f53c36933575ac041bd4aa74ff'),
(54, '24050974068@mhs.unesa.ac.id', '7d7c4fcc261de0ec82d32db278ebea9eb9d2f3c2ac8c23a7d5844cb1fa80087a'),
(55, '24050974068@mhs.unesa.ac.id', 'f5e3dd9fd11863485da60433b38b3e0e193c465116974bb5265a8f68060e73fa'),
(61, '24050974068@mhs.unesa.ac.id', '2145c4928b9975f50c4d6f38026b1f9430c7bb340e363831d9f87ba201b2d2a0'),
(67, '24050974069@mhs.unesa.ac.id', '4589147dbef42edaebc7f5c96778285f9babd3ca05f4465a3004f8aa1776c34f'),
(69, 'ngodingseseruitu@gmail.com', 'e27199777000e6cf5da6a589a5a04dc6bd0eb59404323d9d4f0f4edec08c0cc8'),
(70, 'ngodingseseruitu@gmail.com', 'ce9ff705dde9f2e3fcd9a26d3deb6ab616ceef522c4c1beb96b2ed4fd83773b2'),
(77, '24050974090@mhs.unesa.ac.id', 'c8c09409b3b7f73c4a3fb1534832c7d1913993231ca0182f93b84814d590a3a1'),
(78, 'ngodingseseruitu@gmail.com', 'bf3a76f731002a0a2576bd61fbd8b9e1d82b4ef2b826e25ee842babb54d8bdf9'),
(79, 'ngodingseseruitu@gmail.com', 'e4c63742f887a2bc70b9ed86a41712129c09afaba309c4f55771c9c098013bd5'),
(80, 'ngodingseseruitu@gmail.com', 'ef0468d4fe3199334df77f3a07ee5a5ac2708ea6693088740f27b5e48db85998'),
(81, 'ngodingseseruitu@gmail.com', 'f3cc884b3831ad28697beaa920105decdd794e992844925b6183802ac579aa8c'),
(85, '24050974090@mhs.unesa.ac.id', '6cf73f1e65e38212a693db767d45118e9614ed6e76f9a73685990663f1073164'),
(86, '24050974090@mhs.unesa.ac.id', 'af8ae6741a748ddcbc54f5eb7624dd3a3c716751f83195b8eb8010f18463f686'),
(89, '24050974090@mhs.unesa.ac.id', '4c399e3ce990da79507f666f37914df4fb0ffcf354ead51faf2eb3768389deef'),
(90, '24050974090@mhs.unesa.ac.id', '1535e04a46b64a812b55eefa404c88b73ca6a6ff389259a926e1936800c3a944'),
(96, 'mauzafazrin@gmail.com', '0686f6870e70c4a63b55ce95ea5d75ab1821bfcd05d21da7fe36fc44a9119af8'),
(99, '24050974069@mhs.unesa.ac.id', '625ed85e3c61a30614490a8e0bd4c3319aabc49a90db46e818ba541d24ff6245'),
(100, 'mauzafazrin@gmail.com', 'ea38774cc43e44648697c524ca3f516a8dca3d258ecfe82a1d3f43e02341875d'),
(112, 'ngodingseseruitu@gmail.com', 'af62805e1b09d331610b0eb21ae0ab294a670215affb435ef64b11c864ae707b'),
(114, '24050974084@mhs.unesa.ac.id', 'f93fc588cc0b99ac02a79af6b4d2008ea568c83a034a320ba3aba54cef2bdcb5'),
(116, '24050974069@mhs.unesa.ac.id', '3d1915187359b9b1a91693d056116522c9765946640df3ffd796307bbeac473a'),
(117, '24050974068@mhs.unesa.ac.id', 'f006962011ab1561e08101479613fd85b0e5ea4d2415beff2b4d09ac1efdf4ff'),
(126, 'mauzafazrin@gmail.com', 'eb5c6cc4bc07ad4092cdcf118aed4491bed3b8720d6b53942829675be7fbdb52'),
(127, '24050974068@mhs.unesa.ac.id', '7055ea60e77406d313f9e07745633cddeb78c47298c73e00d0e45fdb72e0cb09'),
(130, 'mauzafazrin@gmail.com', '6cf9e99440e1b9a68330674ef39ba4f076309a7c6516cb78ca2ea578b14b60f5'),
(131, 'mauzafazrin@gmail.com', '440b598b1a0506318da9ad6931837e08681f77318c7bc3cee6bc6465749f68f3'),
(132, 'ngodingseseruitu@gmail.com', '7234c4e963414d14fe2ab395b2a5fd557a4490077160e70869211645a6a1df24'),
(133, 'ngodingseseruitu@gmail.com', '819e7a8d9ef6e463d267b7a02c6b7310fe54d23c699d11c1d8d4b82a2acf8d59'),
(134, '24050974069@mhs.unesa.ac.id', 'e9a0c7d4e993aab25d8b479ca846f1c3c5695d534c5afb710459f79f830ac72f'),
(135, '24050974084@mhs.unesa.ac.id', 'd2ca51e0fa4b97efd31c96d5cc5f4fdc3db89a08a06d7b8fd45bb96fd25646cc'),
(136, '24050974077@mhs.unesa.ac.id', '98b9a4b29aee32222af8813126237595cbd1efa2c0e8312e987b14bcb2628c11'),
(137, '24050974081@mhs.unesa.ac.id', 'be7363ae18939dade5887dcb2f13010cf63ba859f60517cdacc7d0bbe51ed232'),
(138, '24050974067@mhs.unesa.ac.id', 'df09a630c1ad9d113d4e461e08ce8f06e19fa8d1652350a31d3f597b7edceb8e'),
(139, 'mauzafazrin@gmail.com', '5da22c348c4952aca8c9a320c31a93605d0c05470d41454d0aee3dbc4ed90144'),
(142, '24050974066@mhs.unesa.ac.id', '088910e1f054bb53bb65113e7d0be861290ebfdb3cecd1961beaf562e0e8b9ca'),
(143, '24050974078@mhs.unesa.ac.id', 'a104d58a0c0962412a271e04b226885bf27355bdf7d1a37f42cfc1e9fe5f2361'),
(144, '24050974071@mhs.unesa.ac.id', '23623b40d144793d01d1f1ef5b1f5e5c6f6f58c0c70c135ede84affa31479b5f'),
(145, '24050974063@mhs.unesa.ac.id', '12d7e4af5d03f7b7a8915d1a6a176b6f6e0bd17caa71d10110164119be0f242b'),
(146, '24050974089@mhs.unesa.ac.id', '630fb67571b25ae331d611619dad5d06bd567c09f79db812bd7dbd7bd172145f'),
(147, '24050974092@mhs.unesa.ac.id', 'd5422859c7f967020b75c36a3c590afd5232c88ea14568e332a8664dd671975e'),
(148, '24050974072@mhs.unesa.ac.id', 'b346ddb3be5a82700c936a994cd7f0f7cf6ca5bc8e1492350886af647b2f8d6d'),
(149, '24050974089@mhs.unesa.ac.id', 'ed59781622e17ed74dfad98d4d5201b70808bd55538a37a6f60a6912d9075eaf'),
(150, '24050974071@mhs.unesa.ac.id', 'ef4d214e793ab9d130f1b85bd623e3b44d18031be3e39429831021b44cba957c'),
(151, '24050974077@mhs.unesa.ac.id', '8e41e137eb89878bf1aa334165deeefcf0731fff134c305605679563bf5213cc'),
(152, '24050974070@mhs.unesa.ac.id', 'fc6143f3cf0c9acd10467ccc1cebe6ba8f6e94b56babe419a2327b2c1cdc7945'),
(153, '24050974068@mhs.unesa.ac.id', '0224af98f7d3edf40d9477b472f1c5329193c382d02b2f0185c1be1033fc9e71'),
(154, '24050974090@mhs.unesa.ac.id', '6fb58c9218ff16f07e91d53e6c558684d15d2b733e0a546b051880c08f8dd605'),
(155, '24050974074@mhs.unesa.ac.id', '8852f7a9699f1e7139ce61fda0871d8f4fe01f3c6b2122cbcc8d709432e64cd1'),
(156, '24050974078@mhs.unesa.ac.id', 'd19ae1dda4354cd6748c076e8a8fc9c0638a8e524b6e094ac234ae622da182fb'),
(157, '24050974075@mhs.unesa.ac.id', 'e142551b2b1b184cfbf98de46acc14c723db03e0a104950f2be4c258a770dd15'),
(158, '24050974064@mhs.unesa.ac.id', 'b6bb912967a16ab3f4c6747500de8f9526aab6367e058534f96687493d91ac69'),
(159, '24050974083@mhs.unesa.ac.id', '3429fce23d8672ed6c0b5d7af1ce792230bbaadd89299a91b849268a8829577a'),
(160, '24050974063@mhs.unesa.ac.id', '8e8a6ef51e46602e37f7f34f6a419bdafd66f858cc6dd68a59159a412ee183b2'),
(164, 'mauzafazrin@gmail.com', '0afbfb464e01750c85e2fefc0b3d5803d35a04993e1d881b76811fc47abd2552'),
(165, 'mauzafazrin@gmail.com', '06d2d15f8f3673edea56431564b5f62f29b5cf70a22e72c2d3de542d60650684'),
(166, 'mauzafazrin@gmail.com', '8cbdbd62e4f2002e3ab906b4776d43f032fa67561c0679c4b4596c1599016388');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `app_name` varchar(100) DEFAULT 'Jejak Kampus',
  `app_description` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `radius_absensi` int(11) DEFAULT 50 COMMENT 'Radius validasi absensi dalam meter',
  `izin_max_hours_before` int(11) DEFAULT 24,
  `izin_max_days_after` int(11) DEFAULT 1,
  `izin_auto_approve_on_sesi_close` tinyint(1) DEFAULT 1,
  `min_kehadiran_persen` float NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `app_version` varchar(20) DEFAULT 'v1.0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `app_name`, `app_description`, `latitude`, `longitude`, `radius_absensi`, `izin_max_hours_before`, `izin_max_days_after`, `izin_auto_approve_on_sesi_close`, `min_kehadiran_persen`, `created_at`, `updated_at`, `app_version`) VALUES
(1, 'Jejak Kampus', 'Sistem Informasi Absensi Mahasiswa', -7.31628330, 112.72277730, 500, 24, 1, 1, 87.5, '2026-04-17 17:22:18', '2026-04-18 10:51:59', 'v1.0');

-- --------------------------------------------------------

--
-- Table structure for table `tahun_akademik`
--

CREATE TABLE `tahun_akademik` (
  `id` int(11) NOT NULL,
  `tahun` varchar(9) DEFAULT NULL,
  `semester` enum('Ganjil','Genap') DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'nonaktif',
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tahun_akademik`
--

INSERT INTO `tahun_akademik` (`id`, `tahun`, `semester`, `status`, `tanggal_mulai`, `tanggal_selesai`) VALUES
(1, '2025/2026', 'Ganjil', 'nonaktif', NULL, NULL),
(2, '2025/2026', 'Genap', 'aktif', '2025-02-03', '2025-06-20'),
(4, '2026/2027', 'Ganjil', 'nonaktif', NULL, NULL);

--
-- Triggers `tahun_akademik`
--
DELIMITER $$
CREATE TRIGGER `tr_tahunakademik_delete` AFTER DELETE ON `tahun_akademik` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi, pelaku)
    VALUES ('Hapus', 'tahun_akademik', OLD.id, CONCAT('Hapus tahun akademik: ', OLD.tahun, ' ', OLD.semester), 'Admin');
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_tahunakademik_insert` AFTER INSERT ON `tahun_akademik` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi, pelaku)
    VALUES ('Tambah', 'tahun_akademik', NEW.id, CONCAT('Tambah tahun akademik: ', NEW.tahun, ' ', NEW.semester), 'Admin');
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_tahunakademik_update` AFTER UPDATE ON `tahun_akademik` FOR EACH ROW BEGIN
    INSERT INTO log_aktivitas (aksi, tabel, data_id, deskripsi, pelaku)
    VALUES ('Edit', 'tahun_akademik', NEW.id, CONCAT('Edit tahun akademik: ', NEW.tahun, ' ', NEW.semester, ' (Status: ', NEW.status, ')'), 'Admin');
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','dosen','mahasiswa') DEFAULT NULL,
  `profile` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `profile`, `created_at`) VALUES
(1, 'Admin', 'karnoctv@gmail.com', '$2y$10$gbtvoI/aJ2bdCy7SPD9siuhPuHl58TpdrcAwtRjRs43SdpXaKguAm', 'admin', NULL, '2026-04-14 11:16:44'),
(10, 'Fazrin Mauza Dwi Zuhudi', '24050974090@mhs.unesa.ac.id', '$2y$10$W3JR7gcvBAfc8tSHzLL/2eY4rX15XzMcIoH7U7lP8sHArDFtc9c9q', 'mahasiswa', '/uploads/profile/mahasiswa_5_1778834502.jpg', '2026-04-17 18:24:32'),
(11, 'IQBAL AMRI SYABANA1', '24050974068@mhs.unesa.ac.id', '$2y$10$MYDncah/0834kvL3JS1iJ.xYCawB7Cw2rrUPrYatO5QQd6LDGYQ52', 'mahasiswa', NULL, '2026-04-17 18:28:20'),
(12, 'Prof. Dr. Ekohariadi, M.Pd.', 'eko@unesa.ac.id', '$2y$10$6AHyx4o7SG4WySkA.iVgPePSTSNnne0K7ijwMHVdq7NoylwUO81uq', 'dosen', NULL, '2026-04-17 18:34:37'),
(13, 'Dr. Yeni Anistyasari, S.Pd., M.Kom.', 'yeni@unesa.ac.id', '$2y$10$x87Dkor0P6q3/d.Wn3PXU.9mXhrSGb8HcA5cYbOj2PrBfwNl4yJba', 'dosen', NULL, '2026-04-17 18:35:37'),
(14, 'Fazrin Mauza Dwi Zuhudi, S.Pd., M.MT.', 'pastifazrin@gmail.com', '$2y$10$cc2hhcldvIiHCC.XVA57wOno1/dLuYh7pJCJJxTEjNf7X1KfgNPNO', 'dosen', '/uploads/profile/dosen_5_1779152065.jpg', '2026-04-17 18:36:21'),
(20, 'MIKHAEL YULI ANANDA ELVAN PERMANA5', '24050974086@mhs.unesa.ac.id', '$2y$10$mEqdW/4bkBlk90zTcyJzROq25t3loz7.Rwrqlxl48NXJGR/I9kIfG', 'mahasiswa', NULL, '2026-04-21 19:09:40'),
(22, 'Fata Favian Cannavaro3', '24050974069@mhs.unesa.ac.id', '$2y$10$m5mWDWLK7I7TYxzMVnsP/OnVyUcb2jEpu76BRmd1ar/OJHsInM7AG', 'mahasiswa', NULL, '2026-04-22 03:22:39'),
(23, 'Farrell Ahmed Dimitrie Dhiaul Aulia9', '24050974084@mhs.unesa.ac.id', '$2y$10$xEe46xM077j9X8BANWBZYuUGjOGaiJRbgLVmGLMAN13F7LzAgYvWG', 'mahasiswa', NULL, '2026-04-22 06:44:37'),
(24, 'Naruto Uzumaki', 'santui@gmail.com', '$2y$10$VVbPiVAi4Xj/69Nc5WMx8Oq4O1nP8vOjbYd/2JsmYef9ODSaGoX.6', 'dosen', NULL, '2026-04-22 10:57:24'),
(25, 'Ahmad Hadji Suparjo2', '24050974099@mhs.unesa.ac.id', '$2y$10$bamaKLSi70ocF0Vb8KMPuOYQqLoIdydHm0l5d69s524geq9Jwkz0a', 'mahasiswa', NULL, '2026-04-22 11:35:49'),
(27, 'Admin Farrell', 'ngodingseseruitu@gmail.com', '123456', 'admin', NULL, '2026-04-23 02:26:10'),
(28, 'Fazrin Mauza', 'mauzafazrin@gmail.com', '$2y$10$uC1rhb99tVbo9D/2ojnJ.e3VfV1aTOMzYOa69QKrs6Vja1nIeVlVO', 'mahasiswa', '/uploads/profile/mahasiswa_15_1778917733.jpg', '2026-05-11 09:10:15'),
(29, 'AINI INTAN SAYLENDRA', '24050974063@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(30, 'AZIZA DAMAYANTI', '24050974064@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(31, 'INDRA BACHTIAR ZAKARIA', '24050974065@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(32, 'FATHERA ARDILA', '24050974066@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(33, 'YUSUF RAMADHAN MORTI SONDANG SIMANJUNTAK', '24050974067@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(34, 'AJI NUGROHO PARANG', '24050974070@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(35, 'KAROLINA HESTI UTAMI', '24050974071@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(36, 'ERICHA OKTI VIRLYA MEYJIE', '24050974072@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(37, 'DEA AIS RENATA', '24050974073@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(38, 'DEVINA CHRISTINE PUTRI LOKO', '24050974074@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(39, 'KARINA GATI RAHAYU', '24050974075@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(40, 'RINA DWI APRI LESTARI', '24050974076@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(41, 'MUHAMAD SYAMIL ATILLAH', '24050974077@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(42, 'FANDI PRASETYA', '24050974078@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(43, 'MAEFA NURDIANA PUTRI', '24050974079@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(44, 'NABELLA PUTRI ADELLYA', '24050974080@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(45, 'SANDI ANDIKA PUTRA', '24050974081@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(46, 'ELSA CHINTYA AYU AGUSTYANNINGSIH', '24050974082@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(47, 'EKA SABRINA', '24050974083@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(48, 'DHIFA BARATA PUTRA', '24050974085@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(49, 'ALFIAN WIDHI HARJO', '24050974087@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(50, 'PUTRI NABILAH', '24050974088@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(51, 'INTAN AMELIA MIRU', '24050974089@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(52, 'VELISA ALYA QURAINI', '24050974091@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(53, 'MUHAMMAD AQBIL BARKAH PUTRA MURTADHO', '24050974092@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(54, 'SAFINATUL LATIFAH', '24050974093@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43'),
(55, 'BINTANG ALIEF ARTHA MEI VIRA', '24050974094@mhs.unesa.ac.id', NULL, 'mahasiswa', NULL, '2026-05-19 00:43:43');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_jadwal_lengkap`
-- (See below for the actual view)
--
CREATE TABLE `v_jadwal_lengkap` (
`id` int(11)
,`tahun_akademik_id` int(11)
,`tahun` varchar(9)
,`semester` enum('Ganjil','Genap')
,`hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu')
,`kelas_id` int(11)
,`nama_kelas` varchar(50)
,`jurusan` varchar(100)
,`mata_kuliah_id` int(11)
,`kode_mk` varchar(20)
,`nama_mk` varchar(100)
,`sks` int(11)
,`dosen_id` int(11)
,`nidn` varchar(20)
,`nama_dosen` varchar(100)
,`ruangan_id` int(11)
,`kode_ruangan` varchar(20)
,`nama_ruangan` varchar(100)
,`jam_mulai_id` int(11)
,`jam_mulai_ke` int(11)
,`jam_mulai` time
,`jam_selesai_ke` bigint(13)
,`jam_selesai` time /* mariadb-5.3 */
,`jam_ke_list` mediumtext
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sesi_id` (`sesi_id`),
  ADD KEY `mahasiswa_id` (`mahasiswa_id`);

--
-- Indexes for table `approval`
--
ALTER TABLE `approval`
  ADD PRIMARY KEY (`id`),
  ADD KEY `izin_id` (`izin_id`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nidn` (`nidn`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `izin`
--
ALTER TABLE `izin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mahasiswa_id` (`mahasiswa_id`),
  ADD KEY `sesi_id` (`sesi_id`),
  ADD KEY `jadwal_id` (`jadwal_id`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kelas_id` (`kelas_id`),
  ADD KEY `mata_kuliah_id` (`mata_kuliah_id`),
  ADD KEY `dosen_id` (`dosen_id`),
  ADD KEY `tahun_akademik_id` (`tahun_akademik_id`),
  ADD KEY `jadwal_ibfk_5` (`jam_ke_id`),
  ADD KEY `jadwal_ibfk_6` (`ruangan_id`);

--
-- Indexes for table `jam_ke`
--
ALTER TABLE `jam_ke`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jam_ke` (`jam_ke`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tahun_akademik_id` (`tahun_akademik_id`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `kelas_id` (`kelas_id`);

--
-- Indexes for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ruangan`
--
ALTER TABLE `ruangan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_ruangan` (`kode_ruangan`);

--
-- Indexes for table `sesi_absensi`
--
ALTER TABLE `sesi_absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tahun_akademik_id` (`tahun_akademik_id`),
  ADD KEY `sesi_absensi_ibfk_1` (`jadwal_id`);

--
-- Indexes for table `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tahun_akademik`
--
ALTER TABLE `tahun_akademik`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `approval`
--
ALTER TABLE `approval`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `izin`
--
ALTER TABLE `izin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `jam_ke`
--
ALTER TABLE `jam_ke`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ruangan`
--
ALTER TABLE `ruangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sesi_absensi`
--
ALTER TABLE `sesi_absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `session`
--
ALTER TABLE `session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tahun_akademik`
--
ALTER TABLE `tahun_akademik`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

-- --------------------------------------------------------

--
-- Structure for view `v_jadwal_lengkap`
--
DROP TABLE IF EXISTS `v_jadwal_lengkap`;

CREATE ALGORITHM=UNDEFINED DEFINER=`xfag9686`@`localhost` SQL SECURITY DEFINER VIEW `v_jadwal_lengkap`  AS SELECT `j`.`id` AS `id`, `j`.`tahun_akademik_id` AS `tahun_akademik_id`, `ta`.`tahun` AS `tahun`, `ta`.`semester` AS `semester`, `j`.`hari` AS `hari`, `j`.`kelas_id` AS `kelas_id`, `k`.`nama_kelas` AS `nama_kelas`, `k`.`jurusan` AS `jurusan`, `j`.`mata_kuliah_id` AS `mata_kuliah_id`, `mk`.`kode_mk` AS `kode_mk`, `mk`.`nama_mk` AS `nama_mk`, `mk`.`sks` AS `sks`, `j`.`dosen_id` AS `dosen_id`, `d`.`nidn` AS `nidn`, `d`.`nama` AS `nama_dosen`, `j`.`ruangan_id` AS `ruangan_id`, `r`.`kode_ruangan` AS `kode_ruangan`, `r`.`nama_ruangan` AS `nama_ruangan`, `j`.`jam_ke_id` AS `jam_mulai_id`, `jk_mulai`.`jam_ke` AS `jam_mulai_ke`, `jk_mulai`.`jam_mulai` AS `jam_mulai`, `jk_mulai`.`jam_ke`+ `mk`.`sks` - 1 AS `jam_selesai_ke`, (select `jam_ke`.`jam_selesai` from `jam_ke` where `jam_ke`.`jam_ke` = `jk_mulai`.`jam_ke` + `mk`.`sks` - 1) AS `jam_selesai`, (select group_concat(`jam_ke`.`jam_ke` order by `jam_ke`.`jam_ke` ASC separator ',') from `jam_ke` where `jam_ke`.`jam_ke` between `jk_mulai`.`jam_ke` and `jk_mulai`.`jam_ke` + `mk`.`sks` - 1) AS `jam_ke_list` FROM ((((((`jadwal` `j` left join `tahun_akademik` `ta` on(`j`.`tahun_akademik_id` = `ta`.`id`)) left join `kelas` `k` on(`j`.`kelas_id` = `k`.`id`)) left join `mata_kuliah` `mk` on(`j`.`mata_kuliah_id` = `mk`.`id`)) left join `dosen` `d` on(`j`.`dosen_id` = `d`.`id`)) left join `ruangan` `r` on(`j`.`ruangan_id` = `r`.`id`)) left join `jam_ke` `jk_mulai` on(`j`.`jam_ke_id` = `jk_mulai`.`id`)) ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`sesi_id`) REFERENCES `sesi_absensi` (`id`),
  ADD CONSTRAINT `absensi_ibfk_2` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`);

--
-- Constraints for table `approval`
--
ALTER TABLE `approval`
  ADD CONSTRAINT `approval_ibfk_1` FOREIGN KEY (`izin_id`) REFERENCES `izin` (`id`),
  ADD CONSTRAINT `approval_ibfk_2` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`);

--
-- Constraints for table `dosen`
--
ALTER TABLE `dosen`
  ADD CONSTRAINT `dosen_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `izin`
--
ALTER TABLE `izin`
  ADD CONSTRAINT `izin_ibfk_1` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`),
  ADD CONSTRAINT `izin_ibfk_2` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`),
  ADD CONSTRAINT `jadwal_ibfk_2` FOREIGN KEY (`mata_kuliah_id`) REFERENCES `mata_kuliah` (`id`),
  ADD CONSTRAINT `jadwal_ibfk_3` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`),
  ADD CONSTRAINT `jadwal_ibfk_4` FOREIGN KEY (`tahun_akademik_id`) REFERENCES `tahun_akademik` (`id`),
  ADD CONSTRAINT `jadwal_ibfk_5` FOREIGN KEY (`jam_ke_id`) REFERENCES `jam_ke` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `jadwal_ibfk_6` FOREIGN KEY (`ruangan_id`) REFERENCES `ruangan` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`tahun_akademik_id`) REFERENCES `tahun_akademik` (`id`);

--
-- Constraints for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `mahasiswa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `mahasiswa_ibfk_2` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`);

--
-- Constraints for table `sesi_absensi`
--
ALTER TABLE `sesi_absensi`
  ADD CONSTRAINT `sesi_absensi_ibfk_1` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sesi_absensi_ibfk_2` FOREIGN KEY (`tahun_akademik_id`) REFERENCES `tahun_akademik` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
