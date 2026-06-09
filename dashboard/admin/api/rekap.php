<?php
require_once '../../../auth/check.php';

// Handle export PDF (HTML) dan export Excel (CSV) tanpa header JSON
$isExport = false;
if (isset($_GET['action']) && strpos($_GET['action'], 'export_') === 0) {
    $isExport = true;
}
if (!$isExport && !isset($_POST['action']) && !isset($_GET['action'])) {
    echo json_encode(['success' => false, 'msg' => 'Action parameter required']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// -------------------------------------------------------------------
// 1. GET KELAS LIST
// -------------------------------------------------------------------
if ($action === 'get_kelas_list') {
    $result = $conn->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// -------------------------------------------------------------------
// 2. GET TAHUN AKADEMIK LIST
// -------------------------------------------------------------------
if ($action === 'get_tahun_akademik_list') {
    $result = $conn->query("SELECT id, tahun, semester, status FROM tahun_akademik ORDER BY tahun DESC, semester DESC");
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// -------------------------------------------------------------------
// 3. REKAP PER MAHASISWA
// -------------------------------------------------------------------
if ($action === 'rekap_mahasiswa') {
    $page = max(1, (int)($_POST['page'] ?? 1));
    $limit = 15;
    $offset = ($page - 1) * $limit;
    $kelas_id = isset($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    $where = [];
    if ($kelas_id > 0) $where[] = "m.kelas_id = $kelas_id";
    if ($status) $where[] = "m.status = '$status'";
    $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";
    
    // Total count
    $total = $conn->query("SELECT COUNT(*) as cnt FROM mahasiswa m $whereSQL")->fetch_assoc()['cnt'];
    $pages = ceil($total / $limit);
    
    $query = "
        SELECT m.id, m.nim, m.nama, m.status, k.nama_kelas, k.id as kelas_id
        FROM mahasiswa m
        LEFT JOIN kelas k ON m.kelas_id = k.id
        $whereSQL
        ORDER BY m.nama ASC
        LIMIT $offset, $limit
    ";
    $result = $conn->query($query);
    $data = [];
    $totalHadir = 0; $totalSesiAll = 0;
    
    while ($row = $result->fetch_assoc()) {
        // Total sesi untuk kelas
        $sesi = $conn->query("SELECT COUNT(*) as total FROM sesi_absensi sa JOIN jadwal j ON sa.jadwal_id = j.id WHERE j.kelas_id = {$row['kelas_id']}")->fetch_assoc()['total'];
        $totalSesi = $sesi ?: 1;
        
        // Statistik mahasiswa
        $hadir = $conn->query("SELECT COUNT(*) as total FROM absensi a WHERE a.mahasiswa_id = {$row['id']} AND a.status IN ('hadir','telat')")->fetch_assoc()['total'];
        $alpha = $conn->query("SELECT COUNT(*) as total FROM absensi a WHERE a.mahasiswa_id = {$row['id']} AND a.status = 'alpha'")->fetch_assoc()['total'];
        $izin  = $conn->query("SELECT COUNT(*) as total FROM izin i WHERE i.mahasiswa_id = {$row['id']} AND i.status = 'disetujui'")->fetch_assoc()['total'];
        $persen = round(($hadir / $totalSesi) * 100);
        
        $data[] = [
            'nim' => $row['nim'],
            'nama' => $row['nama'],
            'nama_kelas' => $row['nama_kelas'] ?: '-',
            'hadir' => $hadir,
            'alpha' => $alpha,
            'izin' => $izin,
            'persen' => $persen
        ];
        $totalHadir += $hadir;
        $totalSesiAll += $totalSesi;
    }
    $rataKehadiran = $totalSesiAll ? round(($totalHadir / $totalSesiAll) * 100) : 0;
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'page' => $page,
        'pages' => $pages,
        'total' => $total,
        'limit' => $limit,
        'total_mahasiswa' => $total,
        'total_pertemuan' => $totalSesiAll,
        'rata_kehadiran' => $rataKehadiran
    ]);
    exit;
}

// -------------------------------------------------------------------
// 4. REKAP PER KELAS
// -------------------------------------------------------------------
if ($action === 'rekap_kelas') {
    $ta_id = isset($_POST['tahun_akademik_id']) ? (int)$_POST['tahun_akademik_id'] : 0;
    $where = $ta_id ? "WHERE k.tahun_akademik_id = $ta_id" : "";
    
    $query = "
        SELECT k.id, k.nama_kelas, k.jurusan, k.angkatan, COUNT(DISTINCT m.id) as jml_mhs
        FROM kelas k
        LEFT JOIN mahasiswa m ON m.kelas_id = k.id
        $where
        GROUP BY k.id
        ORDER BY k.nama_kelas
    ";
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Rata-rata kehadiran per kelas
        $avg = $conn->query("
            SELECT ROUND(AVG(persen),0) as rata FROM (
                SELECT m2.id, ROUND(SUM(CASE WHEN a.status IN ('hadir','telat') THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT sa.id),0) as persen
                FROM mahasiswa m2
                LEFT JOIN absensi a ON a.mahasiswa_id = m2.id
                LEFT JOIN sesi_absensi sa ON a.sesi_id = sa.id
                LEFT JOIN jadwal j ON sa.jadwal_id = j.id
                WHERE m2.kelas_id = {$row['id']}
                GROUP BY m2.id
            ) sub
        ")->fetch_assoc()['rata'];
        $row['rata_persen'] = $avg ?: 0;
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// -------------------------------------------------------------------
// 5. REKAP PER MATA KULIAH
// -------------------------------------------------------------------
if ($action === 'rekap_matakuliah') {
    $ta_id = isset($_POST['tahun_akademik_id']) ? (int)$_POST['tahun_akademik_id'] : 0;
    $kelas_id = isset($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : 0;
    
    $where = [];
    if ($ta_id) $where[] = "j.tahun_akademik_id = $ta_id";
    if ($kelas_id) $where[] = "j.kelas_id = $kelas_id";
    $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";
    
    $query = "
        SELECT DISTINCT mk.id, mk.kode_mk, mk.nama_mk, mk.sks
        FROM mata_kuliah mk
        JOIN jadwal j ON j.mata_kuliah_id = mk.id
        $whereSQL
        ORDER BY mk.nama_mk
    ";
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Total pertemuan
        $pertemuan = $conn->query("
            SELECT COUNT(DISTINCT sa.id) as total
            FROM sesi_absensi sa
            JOIN jadwal j ON sa.jadwal_id = j.id
            WHERE j.mata_kuliah_id = {$row['id']}
            " . ($ta_id ? " AND j.tahun_akademik_id = $ta_id" : "") . "
            " . ($kelas_id ? " AND j.kelas_id = $kelas_id" : "") . "
        ")->fetch_assoc()['total'];
        
        // Rata-rata kehadiran
        $avg = $conn->query("
            SELECT ROUND(AVG(persen),0) as rata FROM (
                SELECT m.id, ROUND(SUM(CASE WHEN a.status IN ('hadir','telat') THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT sa.id),0) as persen
                FROM mahasiswa m
                LEFT JOIN absensi a ON a.mahasiswa_id = m.id
                LEFT JOIN sesi_absensi sa ON a.sesi_id = sa.id
                LEFT JOIN jadwal j ON sa.jadwal_id = j.id
                WHERE j.mata_kuliah_id = {$row['id']}
                " . ($ta_id ? " AND j.tahun_akademik_id = $ta_id" : "") . "
                " . ($kelas_id ? " AND j.kelas_id = $kelas_id" : "") . "
                GROUP BY m.id
            ) sub
        ")->fetch_assoc()['rata'];
        $row['total_pertemuan'] = $pertemuan ?: 0;
        $row['rata_persen'] = $avg ?: 0;
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// -------------------------------------------------------------------
// 6. REKAP PER SEMESTER (TAHUN AKADEMIK)
// -------------------------------------------------------------------
if ($action === 'rekap_semester') {
    $ta_id = isset($_POST['tahun_akademik_id']) ? (int)$_POST['tahun_akademik_id'] : 0;
    $where = $ta_id ? "WHERE ta.id = $ta_id" : "";
    $query = "SELECT id, tahun, semester, status FROM tahun_akademik ta $where ORDER BY tahun DESC, semester DESC";
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $totalKelas = $conn->query("SELECT COUNT(*) as total FROM kelas WHERE tahun_akademik_id = {$row['id']}")->fetch_assoc()['total'];
        $totalMhs = $conn->query("
            SELECT COUNT(DISTINCT m.id) as total
            FROM mahasiswa m
            JOIN kelas k ON m.kelas_id = k.id
            WHERE k.tahun_akademik_id = {$row['id']}
        ")->fetch_assoc()['total'];
        $avg = $conn->query("
            SELECT ROUND(AVG(persen),0) as rata FROM (
                SELECT m.id, ROUND(SUM(CASE WHEN a.status IN ('hadir','telat') THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT sa.id),0) as persen
                FROM mahasiswa m
                LEFT JOIN absensi a ON a.mahasiswa_id = m.id
                LEFT JOIN sesi_absensi sa ON a.sesi_id = sa.id
                LEFT JOIN jadwal j ON sa.jadwal_id = j.id
                LEFT JOIN kelas k ON j.kelas_id = k.id
                WHERE k.tahun_akademik_id = {$row['id']}
                GROUP BY m.id
            ) sub
        ")->fetch_assoc()['rata'];
        $row['total_kelas'] = $totalKelas;
        $row['total_mahasiswa'] = $totalMhs;
        $row['rata_persen'] = $avg ?: 0;
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// -------------------------------------------------------------------
// 7. EXPORT EXCEL (CSV) untuk semua jenis
// -------------------------------------------------------------------
if (preg_match('/^export_excel_(.+)$/', $action, $match)) {
    $type = $match[1]; // mahasiswa, kelas, matakuliah, semester
    $kelas_id = isset($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : (isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0);
    $ta_id = isset($_POST['tahun_akademik_id']) ? (int)$_POST['tahun_akademik_id'] : (isset($_GET['tahun_akademik_id']) ? (int)$_GET['tahun_akademik_id'] : 0);
    $status = isset($_POST['status']) ? $_POST['status'] : (isset($_GET['status']) ? $_GET['status'] : '');
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rekap_' . $type . '_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    
    if ($type === 'mahasiswa') {
        fputcsv($out, ['NIM', 'Nama Mahasiswa', 'Kelas', 'Hadir', 'Alpha', 'Izin', 'Persentase']);
        $where = [];
        if ($kelas_id) $where[] = "m.kelas_id = $kelas_id";
        if ($status) $where[] = "m.status = '$status'";
        $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";
        $query = "SELECT m.id, m.nim, m.nama, k.nama_kelas, k.id as kelas_id FROM mahasiswa m LEFT JOIN kelas k ON m.kelas_id = k.id $whereSQL ORDER BY m.nama";
        $res = $conn->query($query);
        while ($row = $res->fetch_assoc()) {
            $sesi = $conn->query("SELECT COUNT(*) as total FROM sesi_absensi sa JOIN jadwal j ON sa.jadwal_id = j.id WHERE j.kelas_id = {$row['kelas_id']}")->fetch_assoc()['total'];
            $totalSesi = $sesi ?: 1;
            $hadir = $conn->query("SELECT COUNT(*) as total FROM absensi a WHERE a.mahasiswa_id = {$row['id']} AND a.status IN ('hadir','telat')")->fetch_assoc()['total'];
            $alpha = $conn->query("SELECT COUNT(*) as total FROM absensi a WHERE a.mahasiswa_id = {$row['id']} AND a.status = 'alpha'")->fetch_assoc()['total'];
            $izin = $conn->query("SELECT COUNT(*) as total FROM izin i WHERE i.mahasiswa_id = {$row['id']} AND i.status = 'disetujui'")->fetch_assoc()['total'];
            $persen = round(($hadir / $totalSesi) * 100);
            fputcsv($out, [$row['nim'], $row['nama'], $row['nama_kelas'] ?: '-', $hadir, $alpha, $izin, $persen . '%']);
        }
    }
    elseif ($type === 'kelas') {
        fputcsv($out, ['Nama Kelas', 'Jurusan', 'Angkatan', 'Jumlah Mahasiswa', 'Rata-rata Kehadiran (%)']);
        $where = $ta_id ? "WHERE k.tahun_akademik_id = $ta_id" : "";
        $query = "SELECT k.id, k.nama_kelas, k.jurusan, k.angkatan, COUNT(DISTINCT m.id) as jml_mhs FROM kelas k LEFT JOIN mahasiswa m ON m.kelas_id = k.id $where GROUP BY k.id ORDER BY k.nama_kelas";
        $res = $conn->query($query);
        while ($row = $res->fetch_assoc()) {
            $avg = $conn->query("SELECT ROUND(AVG(persen),0) as rata FROM (SELECT m2.id, ROUND(SUM(CASE WHEN a.status IN ('hadir','telat') THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT sa.id),0) as persen FROM mahasiswa m2 LEFT JOIN absensi a ON a.mahasiswa_id = m2.id LEFT JOIN sesi_absensi sa ON a.sesi_id = sa.id LEFT JOIN jadwal j ON sa.jadwal_id = j.id WHERE m2.kelas_id = {$row['id']} GROUP BY m2.id) sub")->fetch_assoc()['rata'];
            fputcsv($out, [$row['nama_kelas'], $row['jurusan'], $row['angkatan'], $row['jml_mhs'], ($avg ?: 0) . '%']);
        }
    }
    elseif ($type === 'matakuliah') {
        fputcsv($out, ['Kode MK', 'Nama Mata Kuliah', 'SKS', 'Total Pertemuan', 'Rata-rata Kehadiran (%)']);
        $where = [];
        if ($ta_id) $where[] = "j.tahun_akademik_id = $ta_id";
        if ($kelas_id) $where[] = "j.kelas_id = $kelas_id";
        $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";
        $query = "SELECT DISTINCT mk.id, mk.kode_mk, mk.nama_mk, mk.sks FROM mata_kuliah mk JOIN jadwal j ON j.mata_kuliah_id = mk.id $whereSQL ORDER BY mk.nama_mk";
        $res = $conn->query($query);
        while ($row = $res->fetch_assoc()) {
            $pertemuan = $conn->query("SELECT COUNT(DISTINCT sa.id) as total FROM sesi_absensi sa JOIN jadwal j ON sa.jadwal_id = j.id WHERE j.mata_kuliah_id = {$row['id']}" . ($ta_id ? " AND j.tahun_akademik_id = $ta_id" : "") . ($kelas_id ? " AND j.kelas_id = $kelas_id" : ""))->fetch_assoc()['total'];
            $avg = $conn->query("SELECT ROUND(AVG(persen),0) as rata FROM (SELECT m.id, ROUND(SUM(CASE WHEN a.status IN ('hadir','telat') THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT sa.id),0) as persen FROM mahasiswa m LEFT JOIN absensi a ON a.mahasiswa_id = m.id LEFT JOIN sesi_absensi sa ON a.sesi_id = sa.id LEFT JOIN jadwal j ON sa.jadwal_id = j.id WHERE j.mata_kuliah_id = {$row['id']} " . ($ta_id ? " AND j.tahun_akademik_id = $ta_id" : "") . ($kelas_id ? " AND j.kelas_id = $kelas_id" : "") . " GROUP BY m.id) sub")->fetch_assoc()['rata'];
            fputcsv($out, [$row['kode_mk'], $row['nama_mk'], $row['sks'], $pertemuan ?: 0, ($avg ?: 0) . '%']);
        }
    }
    elseif ($type === 'semester') {
        fputcsv($out, ['Tahun', 'Semester', 'Status', 'Total Kelas', 'Total Mahasiswa', 'Rata-rata Kehadiran (%)']);
        $where = $ta_id ? "WHERE ta.id = $ta_id" : "";
        $query = "SELECT id, tahun, semester, status FROM tahun_akademik ta $where ORDER BY tahun DESC, semester DESC";
        $res = $conn->query($query);
        while ($row = $res->fetch_assoc()) {
            $totalKelas = $conn->query("SELECT COUNT(*) as total FROM kelas WHERE tahun_akademik_id = {$row['id']}")->fetch_assoc()['total'];
            $totalMhs = $conn->query("SELECT COUNT(DISTINCT m.id) as total FROM mahasiswa m JOIN kelas k ON m.kelas_id = k.id WHERE k.tahun_akademik_id = {$row['id']}")->fetch_assoc()['total'];
            $avg = $conn->query("SELECT ROUND(AVG(persen),0) as rata FROM (SELECT m.id, ROUND(SUM(CASE WHEN a.status IN ('hadir','telat') THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT sa.id),0) as persen FROM mahasiswa m LEFT JOIN absensi a ON a.mahasiswa_id = m.id LEFT JOIN sesi_absensi sa ON a.sesi_id = sa.id LEFT JOIN jadwal j ON sa.jadwal_id = j.id LEFT JOIN kelas k ON j.kelas_id = k.id WHERE k.tahun_akademik_id = {$row['id']} GROUP BY m.id) sub")->fetch_assoc()['rata'];
            fputcsv($out, [$row['tahun'], $row['semester'], $row['status'] == 'aktif' ? 'Aktif' : 'Nonaktif', $totalKelas, $totalMhs, ($avg ?: 0) . '%']);
        }
    }
    fclose($out);
    exit;
}

// -------------------------------------------------------------------
// 8. EXPORT PDF (print view) untuk semua jenis
// -------------------------------------------------------------------
if (preg_match('/^export_pdf_(.+)$/', $action, $match)) {
    $type = $match[1];
    $kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : (isset($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : 0);
    $ta_id = isset($_GET['tahun_akademik_id']) ? (int)$_GET['tahun_akademik_id'] : (isset($_POST['tahun_akademik_id']) ? (int)$_POST['tahun_akademik_id'] : 0);
    $status = isset($_GET['status']) ? $_GET['status'] : (isset($_POST['status']) ? $_POST['status'] : '');
    
    header('Content-Type: text/html; charset=utf-8');
    
    // Bangun data sesuai type
    $data = [];
    if ($type === 'mahasiswa') {
        $where = [];
        if ($kelas_id) $where[] = "m.kelas_id = $kelas_id";
        if ($status) $where[] = "m.status = '$status'";
        $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";
        $query = "SELECT m.id, m.nim, m.nama, k.nama_kelas, k.id as kelas_id FROM mahasiswa m LEFT JOIN kelas k ON m.kelas_id = k.id $whereSQL ORDER BY m.nama";
        $res = $conn->query($query);
        while ($row = $res->fetch_assoc()) {
            $sesi = $conn->query("SELECT COUNT(*) as total FROM sesi_absensi sa JOIN jadwal j ON sa.jadwal_id = j.id WHERE j.kelas_id = {$row['kelas_id']}")->fetch_assoc()['total'];
            $totalSesi = $sesi ?: 1;
            $hadir = $conn->query("SELECT COUNT(*) as total FROM absensi a WHERE a.mahasiswa_id = {$row['id']} AND a.status IN ('hadir','telat')")->fetch_assoc()['total'];
            $alpha = $conn->query("SELECT COUNT(*) as total FROM absensi a WHERE a.mahasiswa_id = {$row['id']} AND a.status = 'alpha'")->fetch_assoc()['total'];
            $izin = $conn->query("SELECT COUNT(*) as total FROM izin i WHERE i.mahasiswa_id = {$row['id']} AND i.status = 'disetujui'")->fetch_assoc()['total'];
            $persen = round(($hadir / $totalSesi) * 100);
            $data[] = [
                'nim' => $row['nim'],
                'nama' => $row['nama'],
                'kelas' => $row['nama_kelas'] ?: '-',
                'hadir' => $hadir,
                'alpha' => $alpha,
                'izin' => $izin,
                'persen' => $persen
            ];
        }
        $title = "REKAP ABSENSI PER MAHASISWA";
        $headers = ['No.', 'NIM', 'Nama Mahasiswa', 'Kelas', 'Hadir', 'Alpha', 'Izin', 'Persentase'];
    }
    elseif ($type === 'kelas') {
        $where = $ta_id ? "WHERE k.tahun_akademik_id = $ta_id" : "";
        $query = "SELECT k.id, k.nama_kelas, k.jurusan, k.angkatan, COUNT(DISTINCT m.id) as jml_mhs FROM kelas k LEFT JOIN mahasiswa m ON m.kelas_id = k.id $where GROUP BY k.id ORDER BY k.nama_kelas";
        $res = $conn->query($query);
        while ($row = $res->fetch_assoc()) {
            $avg = $conn->query("SELECT ROUND(AVG(persen),0) as rata FROM (SELECT m2.id, ROUND(SUM(CASE WHEN a.status IN ('hadir','telat') THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT sa.id),0) as persen FROM mahasiswa m2 LEFT JOIN absensi a ON a.mahasiswa_id = m2.id LEFT JOIN sesi_absensi sa ON a.sesi_id = sa.id LEFT JOIN jadwal j ON sa.jadwal_id = j.id WHERE m2.kelas_id = {$row['id']} GROUP BY m2.id) sub")->fetch_assoc()['rata'];
            $data[] = [
                'nama_kelas' => $row['nama_kelas'],
                'jurusan' => $row['jurusan'],
                'angkatan' => $row['angkatan'],
                'jml_mhs' => $row['jml_mhs'],
                'rata' => $avg ?: 0
            ];
        }
        $title = "REKAP ABSENSI PER KELAS";
        $headers = ['No.', 'Nama Kelas', 'Jurusan', 'Angkatan', 'Jumlah Mahasiswa', 'Rata-rata Kehadiran (%)'];
    }
    elseif ($type === 'matakuliah') {
        $where = [];
        if ($ta_id) $where[] = "j.tahun_akademik_id = $ta_id";
        if ($kelas_id) $where[] = "j.kelas_id = $kelas_id";
        $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";
        $query = "SELECT DISTINCT mk.id, mk.kode_mk, mk.nama_mk, mk.sks FROM mata_kuliah mk JOIN jadwal j ON j.mata_kuliah_id = mk.id $whereSQL ORDER BY mk.nama_mk";
        $res = $conn->query($query);
        while ($row = $res->fetch_assoc()) {
            $pertemuan = $conn->query("SELECT COUNT(DISTINCT sa.id) as total FROM sesi_absensi sa JOIN jadwal j ON sa.jadwal_id = j.id WHERE j.mata_kuliah_id = {$row['id']}" . ($ta_id ? " AND j.tahun_akademik_id = $ta_id" : "") . ($kelas_id ? " AND j.kelas_id = $kelas_id" : ""))->fetch_assoc()['total'];
            $avg = $conn->query("SELECT ROUND(AVG(persen),0) as rata FROM (SELECT m.id, ROUND(SUM(CASE WHEN a.status IN ('hadir','telat') THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT sa.id),0) as persen FROM mahasiswa m LEFT JOIN absensi a ON a.mahasiswa_id = m.id LEFT JOIN sesi_absensi sa ON a.sesi_id = sa.id LEFT JOIN jadwal j ON sa.jadwal_id = j.id WHERE j.mata_kuliah_id = {$row['id']} " . ($ta_id ? " AND j.tahun_akademik_id = $ta_id" : "") . ($kelas_id ? " AND j.kelas_id = $kelas_id" : "") . " GROUP BY m.id) sub")->fetch_assoc()['rata'];
            $data[] = [
                'kode' => $row['kode_mk'],
                'nama' => $row['nama_mk'],
                'sks' => $row['sks'],
                'pertemuan' => $pertemuan ?: 0,
                'rata' => $avg ?: 0
            ];
        }
        $title = "REKAP ABSENSI PER MATA KULIAH";
        $headers = ['No.', 'Kode MK', 'Nama Mata Kuliah', 'SKS', 'Total Pertemuan', 'Rata-rata Kehadiran (%)'];
    }
    elseif ($type === 'semester') {
        $where = $ta_id ? "WHERE ta.id = $ta_id" : "";
        $query = "SELECT id, tahun, semester, status FROM tahun_akademik ta $where ORDER BY tahun DESC, semester DESC";
        $res = $conn->query($query);
        while ($row = $res->fetch_assoc()) {
            $totalKelas = $conn->query("SELECT COUNT(*) as total FROM kelas WHERE tahun_akademik_id = {$row['id']}")->fetch_assoc()['total'];
            $totalMhs = $conn->query("SELECT COUNT(DISTINCT m.id) as total FROM mahasiswa m JOIN kelas k ON m.kelas_id = k.id WHERE k.tahun_akademik_id = {$row['id']}")->fetch_assoc()['total'];
            $avg = $conn->query("SELECT ROUND(AVG(persen),0) as rata FROM (SELECT m.id, ROUND(SUM(CASE WHEN a.status IN ('hadir','telat') THEN 1 ELSE 0 END) * 100.0 / COUNT(DISTINCT sa.id),0) as persen FROM mahasiswa m LEFT JOIN absensi a ON a.mahasiswa_id = m.id LEFT JOIN sesi_absensi sa ON a.sesi_id = sa.id LEFT JOIN jadwal j ON sa.jadwal_id = j.id LEFT JOIN kelas k ON j.kelas_id = k.id WHERE k.tahun_akademik_id = {$row['id']} GROUP BY m.id) sub")->fetch_assoc()['rata'];
            $data[] = [
                'tahun' => $row['tahun'],
                'semester' => $row['semester'],
                'status' => $row['status'],
                'total_kelas' => $totalKelas,
                'total_mhs' => $totalMhs,
                'rata' => $avg ?: 0
            ];
        }
        $title = "REKAP ABSENSI PER SEMESTER";
        $headers = ['No.', 'Tahun', 'Semester', 'Status', 'Total Kelas', 'Total Mahasiswa', 'Rata-rata Kehadiran (%)'];
    }
    
    // Generate HTML untuk print
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . $title . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h2 { text-align: center; margin-bottom: 5px; }
            .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .footer { margin-top: 20px; text-align: center; font-size: 11px; color: #888; }
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <h2>' . $title . '</h2>
        <div class="subtitle">Dicetak: ' . date('d/m/Y H:i:s') . '</div>
        <table>
            <thead><tr>';
    foreach ($headers as $h) $html .= '<th>' . $h . '</th>';
    $html .= '</tr></thead><tbody>';
    $no = 1;
    foreach ($data as $row) {
        $html .= '<tr>';
        $html .= '<td>' . $no++ . '</td>';
        if ($type === 'mahasiswa') {
            $html .= '<td>' . htmlspecialchars($row['nim']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['nama']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['kelas']) . '</td>';
            $html .= '<td class="text-center">' . $row['hadir'] . '</td>';
            $html .= '<td class="text-center">' . $row['alpha'] . '</td>';
            $html .= '<td class="text-center">' . $row['izin'] . '</td>';
            $html .= '<td class="text-center">' . $row['persen'] . '%</td>';
        }
        elseif ($type === 'kelas') {
            $html .= '<td>' . htmlspecialchars($row['nama_kelas']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['jurusan']) . '</td>';
            $html .= '<td>' . $row['angkatan'] . '</td>';
            $html .= '<td class="text-center">' . $row['jml_mhs'] . '</td>';
            $html .= '<td class="text-center">' . $row['rata'] . '%</td>';
        }
        elseif ($type === 'matakuliah') {
            $html .= '<td>' . htmlspecialchars($row['kode']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['nama']) . '</td>';
            $html .= '<td class="text-center">' . $row['sks'] . '</td>';
            $html .= '<td class="text-center">' . $row['pertemuan'] . '</td>';
            $html .= '<td class="text-center">' . $row['rata'] . '%</td>';
        }
        elseif ($type === 'semester') {
            $html .= '<td>' . $row['tahun'] . '</td>';
            $html .= '<td>' . $row['semester'] . '</td>';
            $html .= '<td>' . ($row['status'] == 'aktif' ? 'Aktif' : 'Nonaktif') . '</td>';
            $html .= '<td class="text-center">' . $row['total_kelas'] . '</td>';
            $html .= '<td class="text-center">' . $row['total_mhs'] . '</td>';
            $html .= '<td class="text-center">' . $row['rata'] . '%</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody>
        </table>
        <div class="footer">Sistem Informasi Absensi - Jejak Kampus</div>
        <div class="no-print" style="text-align:center; margin-top:20px;">
            <button onclick="window.print()" style="padding:8px 16px; background:#3b82f6; color:white; border:none; border-radius:6px; cursor:pointer;">🖨️ Cetak PDF</button>
            <button onclick="window.close()" style="padding:8px 16px; background:#64748b; color:white; border:none; border-radius:6px; cursor:pointer; margin-left:10px;">Tutup</button>
        </div>
    </body>
    </html>';
    echo $html;
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenali']);
exit;
?>