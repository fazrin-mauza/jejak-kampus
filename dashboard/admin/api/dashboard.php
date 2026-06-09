<?php
require_once '../../../auth/check.php';

header('Content-Type: application/json');

if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'msg' => 'Action parameter required']);
    exit;
}

$action = $_POST['action'];

// ── GET MAIN STATS ──
if ($action === 'get_stats') {
    $total_mahasiswa = 0;
    $result = $conn->query("SELECT COUNT(*) as total FROM mahasiswa");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_mahasiswa = (int)$row['total'];
    }
    
    $total_dosen = 0;
    $result = $conn->query("SELECT COUNT(*) as total FROM dosen");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_dosen = (int)$row['total'];
    }
    
    $dosen_aktif = 0;
    $result = $conn->query("SELECT COUNT(*) as total FROM dosen WHERE status = 'aktif'");
    if ($result) {
        $row = $result->fetch_assoc();
        $dosen_aktif = (int)$row['total'];
    }
    
    $total_kelas = 0;
    $result = $conn->query("SELECT COUNT(*) as total FROM kelas");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_kelas = (int)$row['total'];
    }
    
    $total_jurusan = 0;
    $result = $conn->query("SELECT COUNT(DISTINCT jurusan) as total FROM kelas WHERE jurusan IS NOT NULL AND jurusan != ''");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_jurusan = (int)$row['total'];
    }
    
    $total_mk = 0;
    $result = $conn->query("SELECT COUNT(*) as total FROM mata_kuliah");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_mk = (int)$row['total'];
    }
    
    $total_sks = 0;
    $result = $conn->query("SELECT SUM(sks) as total FROM mata_kuliah");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_sks = (int)$row['total'];
    }
    
    // Mahasiswa baru (30 hari terakhir)
    $mahasiswa_baru = 0;
    $result = $conn->query("SELECT COUNT(*) as total FROM mahasiswa WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    if ($result) {
        $row = $result->fetch_assoc();
        $mahasiswa_baru = (int)$row['total'];
    }
    
    $response = [
        'success' => true,
        'data' => [
            'total_mahasiswa' => $total_mahasiswa,
            'total_mahasiswa_baru' => $mahasiswa_baru,
            'total_dosen' => $total_dosen,
            'total_dosen_aktif' => $dosen_aktif,
            'total_kelas' => $total_kelas,
            'total_jurusan' => $total_jurusan,
            'total_mk' => $total_mk,
            'total_sks' => $total_sks
        ]
    ];
    
    echo json_encode($response);
    exit;
}

// ── GET STATUS MAHASISWA ──
if ($action === 'get_status_stats') {
    $aktif = 0;
    $cuti = 0;
    $lulus = 0;
    $dropout = 0;
    $total = 0;
    
    $result = $conn->query("SELECT status, COUNT(*) as total FROM mahasiswa GROUP BY status");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = $row['status'];
            $count = (int)$row['total'];
            if ($status == 'aktif') $aktif = $count;
            elseif ($status == 'cuti') $cuti = $count;
            elseif ($status == 'lulus') $lulus = $count;
            elseif ($status == 'dropout') $dropout = $count;
            $total += $count;
        }
    }
    
    $response = [
        'success' => true,
        'data' => [
            'aktif' => $aktif,
            'cuti' => $cuti,
            'lulus' => $lulus,
            'dropout' => $dropout,
            'total' => $total
        ]
    ];
    
    echo json_encode($response);
    exit;
}

// ── GET STATUS DOSEN ──
if ($action === 'get_dosen_stats') {
    $aktif = 0;
    $nonaktif = 0;
    $cuti = 0;
    $pensiun = 0;
    
    $result = $conn->query("SELECT status, COUNT(*) as total FROM dosen GROUP BY status");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = $row['status'];
            $count = (int)$row['total'];
            if ($status == 'aktif') $aktif = $count;
            elseif ($status == 'nonaktif') $nonaktif = $count;
            elseif ($status == 'cuti') $cuti = $count;
            elseif ($status == 'pensiun') $pensiun = $count;
        }
    }
    
    $response = [
        'success' => true,
        'data' => [
            'aktif' => $aktif,
            'nonaktif' => $nonaktif,
            'cuti' => $cuti,
            'pensiun' => $pensiun
        ]
    ];
    
    echo json_encode($response);
    exit;
}

// ── GET KEHADIRAN MINGGU INI ──
if ($action === 'get_attendance_weekly') {
    $weeklyData = [
        'Senin' => 0,
        'Selasa' => 0,
        'Rabu' => 0,
        'Kamis' => 0,
        'Jumat' => 0
    ];
    
    $query = "SELECT 
                DAYNAME(sa.tanggal) as hari,
                COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
                COUNT(*) as total
              FROM absensi a
              JOIN sesi_absensi sa ON a.sesi_id = sa.id
              WHERE sa.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              GROUP BY DAYNAME(sa.tanggal)";
    
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $hari = $row['hari'];
            $hadir = (int)$row['hadir'];
            $total = (int)$row['total'];
            $persen = $total > 0 ? round(($hadir / $total) * 100) : 0;
            if (isset($weeklyData[$hari])) {
                $weeklyData[$hari] = $persen;
            }
        }
    }
    
    $response = [
        'success' => true,
        'data' => $weeklyData
    ];
    
    echo json_encode($response);
    exit;
}

// ── GET AKTIVITAS TERBARU (HANYA 10 DATA TERBARU, TANPA PAGINATION) ──
if ($action === 'get_recent_activities') {
    // Langsung ambil 10 data terbaru, tanpa pagination
    $query = "SELECT 
                aksi, 
                tabel, 
                deskripsi, 
                waktu,
                pelaku,
                CASE 
                    WHEN aksi = 'Tambah' THEN 'pill-green'
                    WHEN aksi = 'Edit' THEN 'pill-blue'
                    WHEN aksi = 'Hapus' THEN 'pill-red'
                    ELSE 'pill-gray'
                END as status_class,
                'Sukses' as status
              FROM log_aktivitas 
              ORDER BY waktu DESC 
              LIMIT 10";
    
    $result = $conn->query($query);
    $activities = [];
    
    if ($result && $result->num_rows > 0) {
        $no = 1;
        while ($row = $result->fetch_assoc()) {
            $waktu = date('d/m/Y H:i', strtotime($row['waktu']));
            $pelaku = !empty($row['pelaku']) ? $row['pelaku'] : 'Admin';
            
            $activities[] = [
                'no' => str_pad($no++, 3, '0', STR_PAD_LEFT),
                'waktu' => $waktu,
                'aksi' => $row['aksi'],
                'oleh' => $pelaku,
                'detail' => $row['deskripsi'],
                'status' => $row['status'],
                'status_class' => $row['status_class']
            ];
        }
    }
    
    // Jika tidak ada data, tampilkan pesan
    if (empty($activities)) {
        $activities[] = [
            'no' => '001',
            'waktu' => date('d/m/Y H:i'),
            'aksi' => 'Info',
            'oleh' => 'Sistem',
            'detail' => 'Belum ada aktivitas. Silakan tambahkan data mahasiswa atau dosen.',
            'status' => 'Info',
            'status_class' => 'pill-blue'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $activities
    ]);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal.']);
exit;
?>