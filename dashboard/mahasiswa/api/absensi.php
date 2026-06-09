<?php
require_once __DIR__ . '/../../../auth/check.php';
header('Content-Type: application/json');

// Get mahasiswa data
$user_id = $user['id'] ?? 0;
$mahasiswa_id = 0;
$kelas_id = 0;

if ($user_id) {
    $stmt = $conn->prepare("SELECT id, kelas_id FROM mahasiswa WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($mahasiswa_id, $kelas_id);
    $stmt->fetch();
    $stmt->close();
}

if (!$mahasiswa_id) {
    echo json_encode(['success' => false, 'msg' => 'Data mahasiswa tidak ditemukan']);
    exit;
}

$action = $_POST['action'] ?? '';

// ─── GET SESI AKTIF ───────────────────────────────
if ($action === 'get_sesi_aktif') {
    $sql = "SELECT s.id as sesi_id, s.jadwal_id, s.pertemuan_ke, s.status,
                   mk.nama_mk, d.nama as nama_dosen, r.nama_ruangan, r.kode_ruangan,
                   jk.jam_mulai, jk.jam_selesai
            FROM sesi_absensi s
            JOIN jadwal j ON s.jadwal_id = j.id
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            JOIN dosen d ON j.dosen_id = d.id
            LEFT JOIN ruangan r ON j.ruangan_id = r.id
            LEFT JOIN jam_ke jk ON j.jam_ke_id = jk.id
            WHERE j.kelas_id = ? AND s.status = 'aktif'
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $kelas_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// ─── GET STATUS ABSENSI SESI ──────────────────────
if ($action === 'get_status_sesi') {
    $sesi_id = (int)($_POST['sesi_id'] ?? 0);
    
    // Check absensi
    $stmt = $conn->prepare("SELECT status FROM absensi WHERE sesi_id = ? AND mahasiswa_id = ?");
    $stmt->bind_param('ii', $sesi_id, $mahasiswa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $absensi = $result->fetch_assoc();
    
    if ($absensi) {
        echo json_encode(['success' => true, 'data' => ['status' => $absensi['status']]]);
        exit;
    }
    
    // Check izin
    $stmt = $conn->prepare("SELECT jenis, status FROM izin WHERE sesi_id = ? AND mahasiswa_id = ?");
    $stmt->bind_param('ii', $sesi_id, $mahasiswa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $izin = $result->fetch_assoc();
    
    if ($izin && $izin['status'] === 'disetujui') {
        echo json_encode(['success' => true, 'data' => ['status' => $izin['jenis']]]);
        exit;
    }
    
    echo json_encode(['success' => true, 'data' => ['status' => 'belum']]);
    exit;
}

// ─── GET STATUS HARI INI ──────────────────────────
if ($action === 'get_status_hari_ini') {
    $today = date('Y-m-d');
    $hari = date('l');
    $hariMap = [
        'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    $hariIndo = $hariMap[$hari] ?? $hari;
    
    $sql = "SELECT 
                j.id as jadwal_id, mk.nama_mk, jk.jam_mulai,
                s.id as sesi_id, s.status as sesi_status
            FROM jadwal j
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            LEFT JOIN jam_ke jk ON j.jam_ke_id = jk.id
            LEFT JOIN sesi_absensi s ON j.id = s.jadwal_id AND s.tanggal = ? AND s.status = 'aktif'
            WHERE j.kelas_id = ? AND j.hari = ?
            ORDER BY jk.jam_mulai";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sis', $today, $kelas_id, $hariIndo);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $status = 'belum';
        
        if ($row['sesi_id']) {
            // Check absensi
            $q = $conn->query("SELECT status FROM absensi WHERE sesi_id = {$row['sesi_id']} AND mahasiswa_id = $mahasiswa_id");
            if ($q && $a = $q->fetch_assoc()) {
                $status = $a['status'];
            } else {
                // Check izin
                $q = $conn->query("SELECT jenis FROM izin WHERE sesi_id = {$row['sesi_id']} AND mahasiswa_id = $mahasiswa_id AND status = 'disetujui'");
                if ($q && $i = $q->fetch_assoc()) {
                    $status = $i['jenis'];
                }
            }
        }
        
        $row['status'] = $status;
        $rows[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ─── GET RIWAYAT ABSENSI ──────────────────────────
if ($action === 'get_riwayat') {
    $page = max(1, (int)($_POST['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Count total
    $countSQL = "SELECT COUNT(*) FROM (
        SELECT a.id FROM absensi a WHERE a.mahasiswa_id = ?
        UNION ALL
        SELECT i.id FROM izin i WHERE i.mahasiswa_id = ? AND i.status = 'disetujui'
    ) t";
    $stmt = $conn->prepare($countSQL);
    $stmt->bind_param('ii', $mahasiswa_id, $mahasiswa_id);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    
    // Get data
    $sql = "SELECT 
                a.waktu_absen, a.status, a.latitude, a.longitude,
                s.pertemuan_ke, s.tanggal,
                mk.nama_mk,
                CASE WHEN a.latitude IS NOT NULL THEN 'qr' ELSE 'manual' END as metode
            FROM absensi a
            JOIN sesi_absensi s ON a.sesi_id = s.id
            JOIN jadwal j ON s.jadwal_id = j.id
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            WHERE a.mahasiswa_id = ?
            
            UNION ALL
            
            SELECT 
                i.created_at as waktu_absen, i.jenis as status, NULL as latitude, NULL as longitude,
                s.pertemuan_ke, s.tanggal,
                mk.nama_mk,
                'izin' as metode
            FROM izin i
            JOIN sesi_absensi s ON i.sesi_id = s.id
            JOIN jadwal j ON s.jadwal_id = j.id
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            WHERE i.mahasiswa_id = ? AND i.status = 'disetujui'
            
            ORDER BY tanggal DESC, pertemuan_ke DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiii', $mahasiswa_id, $mahasiswa_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
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

// ─── VERIFY TOKEN ─────────────────────────────────
if ($action === 'verify_token') {
    $sesi_id = (int)($_POST['sesi_id'] ?? 0);
    $token = $_POST['token'] ?? '';
    
    // Get QR code data from sesi
    $stmt = $conn->prepare("SELECT qr_code FROM sesi_absensi WHERE id = ? AND status = 'aktif'");
    $stmt->bind_param('i', $sesi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sesi = $result->fetch_assoc();
    
    if (!$sesi) {
        echo json_encode(['success' => false, 'msg' => 'Sesi tidak ditemukan atau sudah berakhir']);
        exit;
    }
    
    // For token method, check if token matches any part of QR data
    $qrData = json_decode($sesi['qr_code'], true);
    if ($qrData && isset($qrData['token'])) {
        // Extract last 6 chars or validate
        if (strtoupper(substr($qrData['token'], -6)) === $token) {
            echo json_encode(['success' => true, 'msg' => 'Token valid']);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'msg' => 'Token tidak valid']);
    exit;
}

// ─── SUBMIT ABSENSI ───────────────────────────────
if ($action === 'submit_absensi') {
    $sesi_id = (int)($_POST['sesi_id'] ?? 0);
    $token = $_POST['token'] ?? '';
    $metode = $_POST['metode'] ?? 'qr';
    $lat = (float)($_POST['lat'] ?? 0);
    $lng = (float)($_POST['lng'] ?? 0);
    
    // Validate sesi
    $stmt = $conn->prepare("SELECT id, status FROM sesi_absensi WHERE id = ? AND status = 'aktif'");
    $stmt->bind_param('i', $sesi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result->fetch_assoc()) {
        echo json_encode(['success' => false, 'msg' => 'Sesi tidak aktif']);
        exit;
    }
    
    // Check if already absent
    $stmt = $conn->prepare("SELECT id FROM absensi WHERE sesi_id = ? AND mahasiswa_id = ?");
    $stmt->bind_param('ii', $sesi_id, $mahasiswa_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'msg' => 'Anda sudah absen']);
        exit;
    }
    $stmt->close();
    
    // Check if already has approved izin
    $stmt = $conn->prepare("SELECT id FROM izin WHERE sesi_id = ? AND mahasiswa_id = ? AND status = 'disetujui'");
    $stmt->bind_param('ii', $sesi_id, $mahasiswa_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'msg' => 'Anda sudah mengajukan izin yang disetujui']);
        exit;
    }
    $stmt->close();
    
    // Determine status (hadir/telat) based on time
    // SELALU HADIR - tidak ada case telat
    $status = 'hadir';
    
    // Insert absensi
    $stmt = $conn->prepare("INSERT INTO absensi (sesi_id, mahasiswa_id, status, latitude, longitude, waktu_absen) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('iisdd', $sesi_id, $mahasiswa_id, $status, $lat, $lng);
    $ok = $stmt->execute();
    
    echo json_encode([
        'success' => $ok,
        'msg' => $ok ? 'Absensi berhasil' : 'Gagal menyimpan absensi',
        'status' => $status
    ]);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal']);
exit;
?>