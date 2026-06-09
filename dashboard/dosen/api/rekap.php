<?php
require_once __DIR__ . '/../../../auth/check.php';
header('Content-Type: application/json');

// Get dosen_id
$user_id = $user['id'] ?? 0;
$dosen_id = 0;
if ($user_id) {
    $stmt = $conn->prepare("SELECT id FROM dosen WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($dosen_id);
    $stmt->fetch();
    $stmt->close();
}

if (!$dosen_id) {
    echo json_encode(['success' => false, 'msg' => 'Data dosen tidak ditemukan']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── GET FILTER OPTIONS ───────────────────────────
if ($action === 'get_filter_options') {
    $mk = $conn->query("SELECT DISTINCT mk.id, mk.nama_mk FROM mata_kuliah mk JOIN jadwal j ON mk.id = j.mata_kuliah_id WHERE j.dosen_id = $dosen_id ORDER BY mk.nama_mk");
    $kelas = $conn->query("SELECT DISTINCT k.id, k.nama_kelas FROM kelas k JOIN jadwal j ON k.id = j.kelas_id WHERE j.dosen_id = $dosen_id ORDER BY k.nama_kelas");
    $ta = $conn->query("SELECT DISTINCT ta.id, ta.tahun, ta.semester, ta.status FROM tahun_akademik ta JOIN jadwal j ON ta.id = j.tahun_akademik_id WHERE j.dosen_id = $dosen_id ORDER BY ta.tahun DESC");
    
    echo json_encode([
        'success' => true,
        'data' => [
            'mk' => $mk->fetch_all(MYSQLI_ASSOC),
            'kelas' => $kelas->fetch_all(MYSQLI_ASSOC),
            'ta' => $ta->fetch_all(MYSQLI_ASSOC)
        ]
    ]);
    exit;
}

// ─── GET MAHASISWA OPTIONS ────────────────────────
if ($action === 'get_mahasiswa_options') {
    $sql = "SELECT DISTINCT m.id, m.nim, m.nama 
            FROM mahasiswa m 
            JOIN kelas k ON m.kelas_id = k.id 
            JOIN jadwal j ON k.id = j.kelas_id 
            WHERE j.dosen_id = ? AND m.status = 'aktif'
            ORDER BY m.nama";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $dosen_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ─── GET REKAP KELAS (DIPERBAIKI) ─────────────────
if ($action === 'get_rekap_kelas') {
    $ta_id = (int)($_POST['ta_id'] ?? 0);
    $mk_id = (int)($_POST['mk_id'] ?? 0);
    $kelas_id = (int)($_POST['kelas_id'] ?? 0);
    $page = max(1, (int)($_POST['page'] ?? 1));
    $limit = (int)($_POST['limit'] ?? 10);
    $offset = ($page - 1) * $limit;
    
    if (!$ta_id) {
        echo json_encode(['success' => false, 'msg' => 'Pilih Semester']); exit;
    }
    
    // Ambil min_kehadiran dari pengaturan
    $minHadir = 75;
    $checkSettings = $conn->query("SHOW TABLES LIKE 'settings'");
    if ($checkSettings && $checkSettings->num_rows > 0) {
        $set = $conn->query("SELECT min_kehadiran_persen FROM settings LIMIT 1");
        if ($set && $row = $set->fetch_assoc()) {
            $minHadir = (int)($row['min_kehadiran_persen'] ?? 75);
        }
    }
    
    // Dapatkan daftar kelas_id yang terkait
    $kelas_ids = [];
    
    if ($kelas_id) {
        $kelas_ids[] = $kelas_id;
    } else {
        $sqlKelas = "SELECT DISTINCT k.id FROM kelas k JOIN jadwal j ON k.id = j.kelas_id WHERE j.dosen_id = $dosen_id AND j.tahun_akademik_id = $ta_id";
        if ($mk_id) $sqlKelas .= " AND j.mata_kuliah_id = $mk_id";
        
        $resultKelas = $conn->query($sqlKelas);
        if ($resultKelas) {
            while ($row = $resultKelas->fetch_assoc()) {
                $kelas_ids[] = (int)$row['id'];
            }
        }
    }
    
    if (empty($kelas_ids)) {
        echo json_encode(['success' => true, 'data' => [], 'total' => 0, 'page' => $page, 'pages' => 0, 'limit' => $limit]);
        exit;
    }
    
    // Convert ke string integer (AMAN)
    $ids_string = implode(',', array_map('intval', $kelas_ids));
    
    // Count total mahasiswa
    $countSQL = "SELECT COUNT(DISTINCT m.id) 
                 FROM mahasiswa m 
                 WHERE m.kelas_id IN ($ids_string) 
                 AND m.status = 'aktif'";
    $resultCount = $conn->query($countSQL);
    $total = $resultCount ? ($resultCount->fetch_row()[0] ?? 0) : 0;
    
    // Query utama untuk mendapatkan data mahasiswa
    $dataSQL = "SELECT 
                    m.id as mahasiswa_id, 
                    m.nim, 
                    m.nama, 
                    k.nama_kelas,
                    k.id as kelas_id
                FROM mahasiswa m
                JOIN kelas k ON m.kelas_id = k.id
                WHERE m.kelas_id IN ($ids_string) 
                AND m.status = 'aktif'
                GROUP BY m.id
                ORDER BY m.nama
                LIMIT $limit OFFSET $offset";
    
    $result = $conn->query($dataSQL);
    $rows = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $mhs_id = (int)$row['mahasiswa_id'];
            $kelas_id_mhs = (int)$row['kelas_id'];
            
            // Hitung hadir + telat
            $sqlHadir = "SELECT COUNT(*) FROM absensi a 
                         JOIN sesi_absensi s ON a.sesi_id = s.id 
                         JOIN jadwal j2 ON s.jadwal_id = j2.id 
                         WHERE a.mahasiswa_id = $mhs_id 
                         AND j2.tahun_akademik_id = $ta_id 
                         AND a.status IN ('hadir','telat')";
            if ($mk_id) $sqlHadir .= " AND j2.mata_kuliah_id = $mk_id";
            $hadir = (int)($conn->query($sqlHadir)->fetch_row()[0] ?? 0);
            
            // Hitung izin
            $sqlIzin = "SELECT COUNT(*) FROM izin i 
                        JOIN sesi_absensi s ON i.sesi_id = s.id 
                        JOIN jadwal j2 ON s.jadwal_id = j2.id 
                        WHERE i.mahasiswa_id = $mhs_id 
                        AND j2.tahun_akademik_id = $ta_id 
                        AND i.status = 'disetujui' 
                        AND i.jenis = 'izin'";
            if ($mk_id) $sqlIzin .= " AND j2.mata_kuliah_id = $mk_id";
            $izin = (int)($conn->query($sqlIzin)->fetch_row()[0] ?? 0);
            
            // Hitung sakit
            $sqlSakit = "SELECT COUNT(*) FROM izin i 
                         JOIN sesi_absensi s ON i.sesi_id = s.id 
                         JOIN jadwal j2 ON s.jadwal_id = j2.id 
                         WHERE i.mahasiswa_id = $mhs_id 
                         AND j2.tahun_akademik_id = $ta_id 
                         AND i.status = 'disetujui' 
                         AND i.jenis = 'sakit'";
            if ($mk_id) $sqlSakit .= " AND j2.mata_kuliah_id = $mk_id";
            $sakit = (int)($conn->query($sqlSakit)->fetch_row()[0] ?? 0);
            
            // Hitung alpha
            $sqlAlpha = "SELECT COUNT(*) FROM absensi a 
                         JOIN sesi_absensi s ON a.sesi_id = s.id 
                         JOIN jadwal j2 ON s.jadwal_id = j2.id 
                         WHERE a.mahasiswa_id = $mhs_id 
                         AND j2.tahun_akademik_id = $ta_id 
                         AND a.status = 'alpha'";
            if ($mk_id) $sqlAlpha .= " AND j2.mata_kuliah_id = $mk_id";
            $alpha = (int)($conn->query($sqlAlpha)->fetch_row()[0] ?? 0);
            
            // Total pertemuan
            $sqlTotal = "SELECT COUNT(*) FROM sesi_absensi s 
                         JOIN jadwal j2 ON s.jadwal_id = j2.id 
                         WHERE j2.tahun_akademik_id = $ta_id 
                         AND j2.kelas_id = $kelas_id_mhs";
            if ($mk_id) $sqlTotal .= " AND j2.mata_kuliah_id = $mk_id";
            $totalPertemuan = (int)($conn->query($sqlTotal)->fetch_row()[0] ?? 1);
            if ($totalPertemuan < 1) $totalPertemuan = 1;
            
            $row['hadir'] = $hadir;
            $row['izin'] = $izin;
            $row['sakit'] = $sakit;
            $row['alpha'] = $alpha;
            $row['persen'] = round(($hadir / $totalPertemuan) * 100, 1);
            $row['min_kehadiran'] = $minHadir;
            
            $rows[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $rows,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit),
        'limit' => $limit
    ]);
    exit;
}

// ─── GET CHART DATA ───────────────────────────────
if ($action === 'get_chart_data') {
    $ta_id = (int)($_POST['ta_id'] ?? 0);
    $mk_id = (int)($_POST['mk_id'] ?? 0);
    $kelas_id = (int)($_POST['kelas_id'] ?? 0);
    
    if (!$ta_id || !$mk_id || !$kelas_id) {
        echo json_encode(['success' => false, 'data' => []]); exit;
    }
    
    $totalMhs = (int)($conn->query("SELECT COUNT(*) FROM mahasiswa WHERE kelas_id = $kelas_id AND status = 'aktif'")->fetch_row()[0] ?? 1);
    if ($totalMhs < 1) $totalMhs = 1;
    
    $sql = "SELECT 
                s.pertemuan_ke, s.status, s.tanggal,
                (SELECT COUNT(*) FROM absensi a WHERE a.sesi_id = s.id AND a.status IN ('hadir','telat')) as hadir
            FROM sesi_absensi s
            JOIN jadwal j ON s.jadwal_id = j.id
            WHERE j.dosen_id = $dosen_id AND j.tahun_akademik_id = $ta_id AND j.mata_kuliah_id = $mk_id AND j.kelas_id = $kelas_id
            ORDER BY s.pertemuan_ke";
    
    $result = $conn->query($sql);
    $rows = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['total_mhs'] = $totalMhs;
            $row['persen'] = round(($row['hadir'] / $totalMhs) * 100, 1);
            $rows[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ─── GET REKAP PERTEMUAN ──────────────────────────
if ($action === 'get_rekap_pertemuan') {
    $ta_id = (int)($_POST['ta_id'] ?? 0);
    $mk_id = (int)($_POST['mk_id'] ?? 0);
    $kelas_id = (int)($_POST['kelas_id'] ?? 0);
    
    if (!$ta_id || !$mk_id || !$kelas_id) {
        echo json_encode(['success' => false, 'msg' => 'Parameter tidak lengkap']); exit;
    }
    
    $sql = "SELECT 
                s.id, s.pertemuan_ke, s.tanggal, s.status,
                (SELECT COUNT(*) FROM absensi a WHERE a.sesi_id = s.id AND a.status = 'hadir') as hadir,
                (SELECT COUNT(*) FROM absensi a WHERE a.sesi_id = s.id AND a.status = 'telat') as telat,
                (SELECT COUNT(*) FROM izin i WHERE i.sesi_id = s.id AND i.status = 'disetujui' AND i.jenis = 'izin') as izin,
                (SELECT COUNT(*) FROM izin i WHERE i.sesi_id = s.id AND i.status = 'disetujui' AND i.jenis = 'sakit') as sakit,
                (SELECT COUNT(*) FROM absensi a WHERE a.sesi_id = s.id AND a.status = 'alpha') as alpha
            FROM sesi_absensi s
            JOIN jadwal j ON s.jadwal_id = j.id
            WHERE j.dosen_id = $dosen_id AND j.tahun_akademik_id = $ta_id AND j.mata_kuliah_id = $mk_id AND j.kelas_id = $kelas_id
            ORDER BY s.pertemuan_ke";
    
    $result = $conn->query($sql);
    $rows = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['hadir'] = ($row['hadir'] ?? 0) + ($row['telat'] ?? 0);
            $rows[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ─── GET DETAIL MAHASISWA ─────────────────────────
if ($action === 'get_detail_mahasiswa') {
    $mhs_id = (int)($_POST['mhs_id'] ?? 0);
    $ta_id = (int)($_POST['ta_id'] ?? 0);
    
    // Get mahasiswa info
    $stmt = $conn->prepare("SELECT m.nim, m.nama, m.kelas_id, k.nama_kelas FROM mahasiswa m LEFT JOIN kelas k ON m.kelas_id = k.id WHERE m.id = ?");
    $stmt->bind_param('i', $mhs_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $mhs = $result->fetch_assoc();
    
    if (!$mhs) {
        echo json_encode(['success' => false, 'msg' => 'Mahasiswa tidak ditemukan']); exit;
    }
    
    $kelas_id_mhs = (int)($mhs['kelas_id'] ?? 0);
    
    // Get rekap per mata kuliah
    $sql = "SELECT 
                mk.id, mk.kode_mk, mk.nama_mk, mk.sks
            FROM mata_kuliah mk
            JOIN jadwal j ON mk.id = j.mata_kuliah_id
            WHERE j.dosen_id = $dosen_id AND j.tahun_akademik_id = $ta_id AND j.kelas_id = $kelas_id_mhs
            GROUP BY mk.id
            ORDER BY mk.kode_mk";
    
    $result = $conn->query($sql);
    $matkul = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $mk_id = (int)$row['id'];
            
            // Hitung hadir + telat
            $hadir = (int)($conn->query("SELECT COUNT(*) FROM absensi a 
                          JOIN sesi_absensi s ON a.sesi_id = s.id 
                          JOIN jadwal j2 ON s.jadwal_id = j2.id 
                          WHERE a.mahasiswa_id = $mhs_id 
                          AND j2.mata_kuliah_id = $mk_id 
                          AND j2.tahun_akademik_id = $ta_id 
                          AND a.status IN ('hadir','telat')")->fetch_row()[0] ?? 0);
            
            // Hitung izin
            $izin = (int)($conn->query("SELECT COUNT(*) FROM izin i 
                         JOIN sesi_absensi s ON i.sesi_id = s.id 
                         JOIN jadwal j2 ON s.jadwal_id = j2.id 
                         WHERE i.mahasiswa_id = $mhs_id 
                         AND j2.mata_kuliah_id = $mk_id 
                         AND j2.tahun_akademik_id = $ta_id 
                         AND i.status = 'disetujui' AND i.jenis = 'izin'")->fetch_row()[0] ?? 0);
            
            // Hitung sakit
            $sakit = (int)($conn->query("SELECT COUNT(*) FROM izin i 
                          JOIN sesi_absensi s ON i.sesi_id = s.id 
                          JOIN jadwal j2 ON s.jadwal_id = j2.id 
                          WHERE i.mahasiswa_id = $mhs_id 
                          AND j2.mata_kuliah_id = $mk_id 
                          AND j2.tahun_akademik_id = $ta_id 
                          AND i.status = 'disetujui' AND i.jenis = 'sakit'")->fetch_row()[0] ?? 0);
            
            // Hitung alpha
            $alpha = (int)($conn->query("SELECT COUNT(*) FROM absensi a 
                          JOIN sesi_absensi s ON a.sesi_id = s.id 
                          JOIN jadwal j2 ON s.jadwal_id = j2.id 
                          WHERE a.mahasiswa_id = $mhs_id 
                          AND j2.mata_kuliah_id = $mk_id 
                          AND j2.tahun_akademik_id = $ta_id 
                          AND a.status = 'alpha'")->fetch_row()[0] ?? 0);
            
            // Total pertemuan
            $totalPertemuan = (int)($conn->query("SELECT COUNT(*) FROM sesi_absensi s 
                                    JOIN jadwal j2 ON s.jadwal_id = j2.id 
                                    WHERE j2.mata_kuliah_id = $mk_id 
                                    AND j2.tahun_akademik_id = $ta_id 
                                    AND j2.kelas_id = $kelas_id_mhs")->fetch_row()[0] ?? 1);
            if ($totalPertemuan < 1) $totalPertemuan = 1;
            
            $row['hadir'] = $hadir;
            $row['izin'] = $izin;
            $row['sakit'] = $sakit;
            $row['alpha'] = $alpha;
            $row['persen'] = round(($hadir / $totalPertemuan) * 100, 1);
            
            $matkul[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'nim' => $mhs['nim'],
            'nama' => $mhs['nama'],
            'nama_kelas' => $mhs['nama_kelas'],
            'matkul' => $matkul
        ]
    ]);
    exit;
}

// ─── EXPORT EXCEL ─────────────────────────────────
if ($action === 'export_excel') {
    $ta_id = (int)($_POST['ta_id'] ?? 0);
    $mk_id = (int)($_POST['mk_id'] ?? 0);
    $kelas_id = (int)($_POST['kelas_id'] ?? 0);
    
    if (!$ta_id) {
        echo json_encode(['success' => false, 'msg' => 'Pilih Semester']); exit;
    }
    
    // Dapatkan daftar kelas
    $kelas_ids = [];
    if ($kelas_id) {
        $kelas_ids[] = $kelas_id;
    } else {
        $sqlKelas = "SELECT DISTINCT k.id FROM kelas k JOIN jadwal j ON k.id = j.kelas_id WHERE j.dosen_id = $dosen_id AND j.tahun_akademik_id = $ta_id";
        if ($mk_id) $sqlKelas .= " AND j.mata_kuliah_id = $mk_id";
        
        $resultKelas = $conn->query($sqlKelas);
        if ($resultKelas) {
            while ($row = $resultKelas->fetch_assoc()) {
                $kelas_ids[] = (int)$row['id'];
            }
        }
    }
    
    if (empty($kelas_ids)) {
        echo json_encode(['success' => false, 'msg' => 'Tidak ada data']); exit;
    }
    
    $ids_string = implode(',', array_map('intval', $kelas_ids));
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rekap_presensi_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIM', 'Nama', 'Kelas', 'Hadir', 'Izin', 'Sakit', 'Tanpa Keterangan', 'Persentase', 'Status']);
    
    // Ambil min_kehadiran
    $minHadir = 75;
    $checkSettings = $conn->query("SHOW TABLES LIKE 'settings'");
    if ($checkSettings && $checkSettings->num_rows > 0) {
        $set = $conn->query("SELECT min_kehadiran_persen FROM settings LIMIT 1");
        if ($set && $row = $set->fetch_assoc()) {
            $minHadir = (int)($row['min_kehadiran_persen'] ?? 75);
        }
    }
    
    // Ambil data mahasiswa
    $dataSQL = "SELECT m.id, m.nim, m.nama, k.nama_kelas, k.id as kelas_id
                FROM mahasiswa m 
                JOIN kelas k ON m.kelas_id = k.id 
                WHERE m.kelas_id IN ($ids_string) 
                AND m.status = 'aktif' 
                ORDER BY m.nama";
    
    $result = $conn->query($dataSQL);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $mhs_id = (int)$row['id'];
            $kelas_id_mhs = (int)$row['kelas_id'];
            
            // Hitung hadir
            $sqlHadir = "SELECT COUNT(*) FROM absensi a 
                          JOIN sesi_absensi s ON a.sesi_id = s.id 
                          JOIN jadwal j2 ON s.jadwal_id = j2.id 
                          WHERE a.mahasiswa_id = $mhs_id 
                          AND j2.tahun_akademik_id = $ta_id 
                          AND a.status IN ('hadir','telat')";
            if ($mk_id) $sqlHadir .= " AND j2.mata_kuliah_id = $mk_id";
            $hadir = (int)($conn->query($sqlHadir)->fetch_row()[0] ?? 0);
            
            // Hitung izin
            $sqlIzin = "SELECT COUNT(*) FROM izin i 
                         JOIN sesi_absensi s ON i.sesi_id = s.id 
                         JOIN jadwal j2 ON s.jadwal_id = j2.id 
                         WHERE i.mahasiswa_id = $mhs_id 
                         AND j2.tahun_akademik_id = $ta_id 
                         AND i.status = 'disetujui' AND i.jenis = 'izin'";
            if ($mk_id) $sqlIzin .= " AND j2.mata_kuliah_id = $mk_id";
            $izin = (int)($conn->query($sqlIzin)->fetch_row()[0] ?? 0);
            
            // Hitung sakit
            $sqlSakit = "SELECT COUNT(*) FROM izin i 
                          JOIN sesi_absensi s ON i.sesi_id = s.id 
                          JOIN jadwal j2 ON s.jadwal_id = j2.id 
                          WHERE i.mahasiswa_id = $mhs_id 
                          AND j2.tahun_akademik_id = $ta_id 
                          AND i.status = 'disetujui' AND i.jenis = 'sakit'";
            if ($mk_id) $sqlSakit .= " AND j2.mata_kuliah_id = $mk_id";
            $sakit = (int)($conn->query($sqlSakit)->fetch_row()[0] ?? 0);
            
            // Hitung alpha
            $sqlAlpha = "SELECT COUNT(*) FROM absensi a 
                          JOIN sesi_absensi s ON a.sesi_id = s.id 
                          JOIN jadwal j2 ON s.jadwal_id = j2.id 
                          WHERE a.mahasiswa_id = $mhs_id 
                          AND j2.tahun_akademik_id = $ta_id 
                          AND a.status = 'alpha'";
            if ($mk_id) $sqlAlpha .= " AND j2.mata_kuliah_id = $mk_id";
            $alpha = (int)($conn->query($sqlAlpha)->fetch_row()[0] ?? 0);
            
            // Total pertemuan
            $sqlTotal = "SELECT COUNT(*) FROM sesi_absensi s 
                                    JOIN jadwal j2 ON s.jadwal_id = j2.id 
                                    WHERE j2.tahun_akademik_id = $ta_id 
                                    AND j2.kelas_id = $kelas_id_mhs";
            if ($mk_id) $sqlTotal .= " AND j2.mata_kuliah_id = $mk_id";
            $totalPertemuan = (int)($conn->query($sqlTotal)->fetch_row()[0] ?? 1);
            if ($totalPertemuan < 1) $totalPertemuan = 1;
            
            $persen = round(($hadir / $totalPertemuan) * 100, 1);
            $status = $persen >= $minHadir ? 'Lulus' : 'Tidak Lulus';
            
            fputcsv($out, [
                $row['nim'], 
                $row['nama'], 
                $row['nama_kelas'],
                $hadir, 
                $izin, 
                $sakit, 
                $alpha,
                $persen . '%', 
                $status
            ]);
        }
    }
    
    fclose($out);
    exit;
}

// ─── EXPORT PDF ───────────────────────────────────
if ($action === 'export_pdf') {
    // Tidak digunakan, frontend menggunakan print-friendly page
    echo json_encode(['success' => false, 'msg' => 'Gunakan fitur cetak dari browser']);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Aksi tidak dikenal']);
exit;