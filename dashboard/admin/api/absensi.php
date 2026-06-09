<?php
require_once '../../../auth/check.php';

header('Content-Type: application/json');

if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'msg' => 'Action parameter required']);
    exit;
}

$action = $_POST['action'];

// ── GET KELAS LIST ──
if ($action === 'get_kelas_list') {
    $query = "SELECT id, nama_kelas, jurusan FROM kelas ORDER BY nama_kelas";
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => $row['id'],
            'nama_kelas' => $row['nama_kelas'] . ' - ' . $row['jurusan']
        ];
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// ── GET MATA KULIAH LIST ──
if ($action === 'get_mk_list') {
    $query = "SELECT id, kode_mk, nama_mk, sks FROM mata_kuliah ORDER BY nama_mk";
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => $row['id'],
            'nama_mk' => $row['kode_mk'] . ' - ' . $row['nama_mk'] . ' (' . $row['sks'] . ' SKS)'
        ];
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// ── GET ABSENSI ──
if ($action === 'get_absensi') {
    $page = max(1, (int)($_POST['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $tanggal = isset($_POST['tanggal']) ? $_POST['tanggal'] : '';
    $kelas_id = isset($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : 0;
    $mk_id = isset($_POST['mk_id']) ? (int)$_POST['mk_id'] : 0;
    $status_filter = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Bangun WHERE clause
    $where = [];
    $whereParams = [];
    $types = "";
    
    if ($tanggal) {
        $where[] = "sa.tanggal = ?";
        $whereParams[] = $tanggal;
        $types .= "s";
    }
    if ($kelas_id > 0) {
        $where[] = "m.kelas_id = ?";
        $whereParams[] = $kelas_id;
        $types .= "i";
    }
    if ($mk_id > 0) {
        $where[] = "j.mata_kuliah_id = ?";
        $whereParams[] = $mk_id;
        $types .= "i";
    }
    if ($status_filter) {
        if ($status_filter == 'izin' || $status_filter == 'sakit') {
            $where[] = "i.jenis = ? AND i.status = 'disetujui'";
            $whereParams[] = $status_filter;
            $types .= "s";
        } elseif ($status_filter == 'hadir') {
            $where[] = "a.status IN ('hadir', 'telat')";
        } elseif ($status_filter == 'alpha') {
            $where[] = "a.status = 'alpha'";
            $where[] = "i.id IS NULL";
        }
    }
    
    $whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // Base query
    $fromSQL = "
        FROM absensi a
        LEFT JOIN sesi_absensi sa ON a.sesi_id = sa.id
        LEFT JOIN jadwal j ON sa.jadwal_id = j.id
        LEFT JOIN mahasiswa m ON a.mahasiswa_id = m.id
        LEFT JOIN kelas k ON m.kelas_id = k.id
        LEFT JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
        LEFT JOIN izin i ON a.sesi_id = i.sesi_id AND a.mahasiswa_id = i.mahasiswa_id AND i.status = 'disetujui'
    ";
    
    // Get total count
    $countSQL = "SELECT COUNT(*) as total " . $fromSQL . " " . $whereSQL;
    
    if (!empty($whereParams)) {
        $stmt = $conn->prepare($countSQL);
        $stmt->bind_param($types, ...$whereParams);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    } else {
        $result = $conn->query($countSQL);
        $total = $result->fetch_assoc()['total'];
    }
    
    $pages = ($total > 0) ? ceil($total / $limit) : 1;
    
    // Get data
    $query = "
        SELECT 
            a.id,
            a.status as absensi_status,
            a.waktu_absen,
            a.latitude,
            a.longitude,
            sa.tanggal,
            sa.pertemuan_ke,
            m.id as mahasiswa_id,
            m.nama as nama_mahasiswa,
            m.nim,
            k.nama_kelas,
            k.jurusan,
            mk.id as mk_id,
            mk.kode_mk,
            mk.nama_mk,
            i.jenis as izin_jenis
        " . $fromSQL . "
        " . $whereSQL . "
        ORDER BY a.waktu_absen DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    if (!empty($whereParams)) {
        $params = array_merge($whereParams, [$limit, $offset]);
        $typesWithLimit = $types . "ii";
        $stmt->bind_param($typesWithLimit, ...$params);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Tentukan status akhir
        $finalStatus = $row['absensi_status'];
        if ($row['izin_jenis'] && in_array($row['izin_jenis'], ['izin', 'sakit'])) {
            $finalStatus = $row['izin_jenis'];
        }
        
        // Tentukan lokasi
        $lokasiText = '-';
        if ($row['latitude'] && $row['longitude']) {
            $lokasiText = '📍 Dalam area';
        } elseif ($finalStatus == 'hadir' || $finalStatus == 'telat') {
            $lokasiText = '✅ Terdeteksi';
        }
        
        // Tentukan status text untuk badge
        $statusText = ucfirst($finalStatus);
        if ($finalStatus == 'telat') $statusText = 'Terlambat';
        if ($finalStatus == 'alpha') $statusText = 'Tidak Hadir';
        
        $data[] = [
            'id' => $row['id'],
            'status' => $finalStatus,
            'status_text' => $statusText,
            'waktu_absen' => $row['waktu_absen'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'lokasi' => $lokasiText,
            'tanggal' => $row['tanggal'],
            'pertemuan_ke' => $row['pertemuan_ke'],
            'mahasiswa_id' => $row['mahasiswa_id'],
            'nama_mahasiswa' => $row['nama_mahasiswa'] ?? '-',
            'nim' => $row['nim'] ?? '-',
            'nama_kelas' => $row['nama_kelas'] ?? '-',
            'jurusan' => $row['jurusan'] ?? '-',
            'mk_id' => $row['mk_id'],
            'kode_mk' => $row['kode_mk'],
            'nama_mk' => $row['nama_mk'] ?? '-'
        ];
    }
    $stmt->close();
    
    // Get stats
    $statsSQL = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE 
                WHEN a.status IN ('hadir', 'telat') THEN 1 
                ELSE 0 
            END) as hadir,
            SUM(CASE 
                WHEN a.status = 'alpha' AND i.id IS NULL THEN 1 
                ELSE 0 
            END) as alpha,
            SUM(CASE 
                WHEN i.jenis IS NOT NULL AND i.status = 'disetujui' THEN 1 
                ELSE 0 
            END) as izin
        " . $fromSQL . "
        " . $whereSQL . "
    ";
    
    if (!empty($whereParams)) {
        $stmt = $conn->prepare($statsSQL);
        $stmt->bind_param($types, ...$whereParams);
        $stmt->execute();
        $statsResult = $stmt->get_result();
        $statsRow = $statsResult->fetch_assoc();
        $stmt->close();
    } else {
        $statsResult = $conn->query($statsSQL);
        $statsRow = $statsResult->fetch_assoc();
    }
    
    $totalStats = $statsRow['total'] ?: 0;
    $stats = [
        'total_absensi' => $totalStats,
        'persen_hadir' => $totalStats > 0 ? round(($statsRow['hadir'] / $totalStats) * 100) : 0,
        'persen_alpha' => $totalStats > 0 ? round(($statsRow['alpha'] / $totalStats) * 100) : 0,
        'persen_izin' => $totalStats > 0 ? round(($statsRow['izin'] / $totalStats) * 100) : 0
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'stats' => $stats,
        'page' => $page,
        'pages' => $pages,
        'total' => $total,
        'limit' => $limit
    ]);
    exit;
}

// ── EXPORT EXCEL (CSV) ──
if ($action === 'export_excel') {
    $tanggal = isset($_POST['tanggal']) ? $_POST['tanggal'] : '';
    $kelas_id = isset($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : 0;
    $mk_id = isset($_POST['mk_id']) ? (int)$_POST['mk_id'] : 0;
    $status_filter = isset($_POST['status']) ? $_POST['status'] : '';
    
    $where = [];
    $whereParams = [];
    $types = "";
    
    if ($tanggal) {
        $where[] = "sa.tanggal = ?";
        $whereParams[] = $tanggal;
        $types .= "s";
    }
    if ($kelas_id > 0) {
        $where[] = "m.kelas_id = ?";
        $whereParams[] = $kelas_id;
        $types .= "i";
    }
    if ($mk_id > 0) {
        $where[] = "j.mata_kuliah_id = ?";
        $whereParams[] = $mk_id;
        $types .= "i";
    }
    if ($status_filter) {
        if ($status_filter == 'izin' || $status_filter == 'sakit') {
            $where[] = "i.jenis = ? AND i.status = 'disetujui'";
            $whereParams[] = $status_filter;
            $types .= "s";
        } elseif ($status_filter == 'hadir') {
            $where[] = "a.status IN ('hadir', 'telat')";
        } elseif ($status_filter == 'alpha') {
            $where[] = "a.status = 'alpha'";
            $where[] = "i.id IS NULL";
        }
    }
    
    $whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $fromSQL = "
        FROM absensi a
        LEFT JOIN sesi_absensi sa ON a.sesi_id = sa.id
        LEFT JOIN jadwal j ON sa.jadwal_id = j.id
        LEFT JOIN mahasiswa m ON a.mahasiswa_id = m.id
        LEFT JOIN kelas k ON m.kelas_id = k.id
        LEFT JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
        LEFT JOIN izin i ON a.sesi_id = i.sesi_id AND a.mahasiswa_id = i.mahasiswa_id AND i.status = 'disetujui'
    ";
    
    $query = "
        SELECT 
            m.nama as nama_mahasiswa,
            m.nim,
            k.nama_kelas,
            mk.nama_mk,
            sa.pertemuan_ke,
            a.waktu_absen,
            CASE 
                WHEN a.status IN ('hadir', 'telat') THEN 'Hadir'
                WHEN i.jenis IS NOT NULL AND i.status = 'disetujui' THEN CONCAT(UCASE(LEFT(i.jenis,1)), SUBSTRING(i.jenis,2))
                WHEN a.status = 'alpha' THEN 'Tidak Hadir'
                ELSE a.status
            END as status
        " . $fromSQL . "
        " . $whereSQL . "
        ORDER BY m.nama ASC
    ";
    
    if (!empty($whereParams)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$whereParams);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="absensi_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF";
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Nama Mahasiswa', 'NIM', 'Kelas', 'Mata Kuliah', 'Pertemuan Ke-', 'Waktu Absen', 'Status']);
    
    while ($row = $result->fetch_assoc()) {
        $waktu = $row['waktu_absen'] ? date('H:i', strtotime($row['waktu_absen'])) : '-';
        fputcsv($out, [
            $row['nama_mahasiswa'],
            $row['nim'],
            $row['nama_kelas'],
            $row['nama_mk'],
            $row['pertemuan_ke'],
            $waktu,
            $row['status']
        ]);
    }
    
    fclose($out);
    if (isset($stmt)) $stmt->close();
    exit;
}

// ── EXPORT PDF ──
if ($action === 'export_pdf') {
    $tanggal = isset($_POST['tanggal']) ? $_POST['tanggal'] : '';
    $kelas_id = isset($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : 0;
    $mk_id = isset($_POST['mk_id']) ? (int)$_POST['mk_id'] : 0;
    $status_filter = isset($_POST['status']) ? $_POST['status'] : '';
    
    $where = [];
    $whereParams = [];
    $types = "";
    
    if ($tanggal) {
        $where[] = "sa.tanggal = ?";
        $whereParams[] = $tanggal;
        $types .= "s";
    }
    if ($kelas_id > 0) {
        $where[] = "m.kelas_id = ?";
        $whereParams[] = $kelas_id;
        $types .= "i";
    }
    if ($mk_id > 0) {
        $where[] = "j.mata_kuliah_id = ?";
        $whereParams[] = $mk_id;
        $types .= "i";
    }
    if ($status_filter) {
        if ($status_filter == 'izin' || $status_filter == 'sakit') {
            $where[] = "i.jenis = ? AND i.status = 'disetujui'";
            $whereParams[] = $status_filter;
            $types .= "s";
        } elseif ($status_filter == 'hadir') {
            $where[] = "a.status IN ('hadir', 'telat')";
        } elseif ($status_filter == 'alpha') {
            $where[] = "a.status = 'alpha'";
            $where[] = "i.id IS NULL";
        }
    }
    
    $whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $fromSQL = "
        FROM absensi a
        LEFT JOIN sesi_absensi sa ON a.sesi_id = sa.id
        LEFT JOIN jadwal j ON sa.jadwal_id = j.id
        LEFT JOIN mahasiswa m ON a.mahasiswa_id = m.id
        LEFT JOIN kelas k ON m.kelas_id = k.id
        LEFT JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
        LEFT JOIN izin i ON a.sesi_id = i.sesi_id AND a.mahasiswa_id = i.mahasiswa_id AND i.status = 'disetujui'
    ";
    
    $query = "
        SELECT 
            m.nama as nama_mahasiswa,
            m.nim,
            k.nama_kelas,
            mk.nama_mk,
            sa.pertemuan_ke,
            a.waktu_absen,
            CASE 
                WHEN a.status IN ('hadir', 'telat') THEN 'Hadir'
                WHEN i.jenis IS NOT NULL AND i.status = 'disetujui' THEN CONCAT(UCASE(LEFT(i.jenis,1)), SUBSTRING(i.jenis,2))
                WHEN a.status = 'alpha' THEN 'Tidak Hadir'
                ELSE a.status
            END as status
        " . $fromSQL . "
        " . $whereSQL . "
        ORDER BY m.nama ASC
    ";
    
    if (!empty($whereParams)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$whereParams);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <title>Laporan Absensi</title>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h2 { text-align: center; margin-bottom: 5px; color: #1e293b; }
            .subtitle { text-align: center; color: #64748b; margin-top: 0; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 11px; }
            th { background-color: #f1f5f9; font-weight: bold; }
            .footer { margin-top: 20px; font-size: 10px; text-align: center; color: #94a3b8; }
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
            .badge-hadir { color: #10b981; font-weight: bold; }
            .badge-alpha { color: #ef4444; font-weight: bold; }
            .badge-izin { color: #3b82f6; font-weight: bold; }
        </style>
    </head>
    <body>
        <h2>LAPORAN ABSENSI MAHASISWA</h2>
        <div class="subtitle">Periode: ' . ($tanggal ? date('d/m/Y', strtotime($tanggal)) : 'Semua Tanggal') . '</div>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Mahasiswa</th>
                    <th>NIM</th>
                    <th>Kelas</th>
                    <th>Mata Kuliah</th>
                    <th>Pertemuan</th>
                    <th>Waktu</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';
    
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        $waktu = $row['waktu_absen'] ? date('H:i', strtotime($row['waktu_absen'])) : '-';
        $statusClass = '';
        if ($row['status'] == 'Hadir') $statusClass = 'badge-hadir';
        elseif ($row['status'] == 'Tidak Hadir') $statusClass = 'badge-alpha';
        elseif ($row['status'] == 'Izin' || $row['status'] == 'Sakit') $statusClass = 'badge-izin';
        
        $html .= '<tr>
            <td>' . $no++ . '</td>
            <td>' . htmlspecialchars($row['nama_mahasiswa']) . '</td>
            <td>' . htmlspecialchars($row['nim']) . '</td>
            <td>' . htmlspecialchars($row['nama_kelas']) . '</td>
            <td>' . htmlspecialchars($row['nama_mk']) . '</td>
            <td style="text-align:center">' . ($row['pertemuan_ke'] ?? '-') . '</td>
            <td style="text-align:center">' . $waktu . '</td>
            <td class="' . $statusClass . '">' . htmlspecialchars($row['status']) . '</td>
        </tr>';
    }
    
    $html .= '</tbody>
        </table>
        <div class="footer">Dicetak: ' . date('d/m/Y H:i:s') . '</div>
        <div class="no-print" style="text-align:center; margin-top:20px;">
            <button onclick="window.print()" style="padding:8px 16px; cursor:pointer; background:#3b82f6; color:white; border:none; border-radius:6px;">
                🖨️ Cetak PDF
            </button>
            <button onclick="window.close()" style="padding:8px 16px; cursor:pointer; background:#64748b; color:white; border:none; border-radius:6px; margin-left:10px;">Tutup</button>
        </div>
    </body>
    </html>';
    
    echo $html;
    if (isset($stmt)) $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal']);
exit;
?>