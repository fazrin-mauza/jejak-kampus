<?php
require_once __DIR__ . '/../../../auth/check.php';
header('Content-Type: application/json');

// Get dosen_id
$user_id = $user['id'] ?? 0;
$dosen_id = 0;

if ($user_id && $user['role'] === 'dosen') {
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

// ════════════════════════════════════════════════
// GET FILTER OPTIONS
// ════════════════════════════════════════════════
if ($action === 'get_filter_options') {
    $mk = $conn->query("SELECT DISTINCT mk.id, mk.nama_mk FROM mata_kuliah mk 
                        JOIN jadwal j ON mk.id = j.mata_kuliah_id 
                        WHERE j.dosen_id = $dosen_id ORDER BY mk.nama_mk");
    $kelas = $conn->query("SELECT DISTINCT k.id, k.nama_kelas FROM kelas k 
                           JOIN jadwal j ON k.id = j.kelas_id 
                           WHERE j.dosen_id = $dosen_id ORDER BY k.nama_kelas");
    
    echo json_encode([
        'success' => true,
        'data' => [
            'mk' => $mk->fetch_all(MYSQLI_ASSOC),
            'kelas' => $kelas->fetch_all(MYSQLI_ASSOC)
        ]
    ]);
    exit;
}

// ════════════════════════════════════════════════
// GET PENDING
// ════════════════════════════════════════════════
if ($action === 'get_pending') {
    $mk_id = (int)($_POST['mk_id'] ?? 0);
    $kelas_id = (int)($_POST['kelas_id'] ?? 0);
    
    $where = ["i.status = 'pending'", "j.dosen_id = ?"];
    $params = [$dosen_id];
    $types = 'i';
    
    if ($mk_id) { $where[] = "j.mata_kuliah_id = ?"; $params[] = $mk_id; $types .= 'i'; }
    if ($kelas_id) { $where[] = "j.kelas_id = ?"; $params[] = $kelas_id; $types .= 'i'; }
    
    $whereSQL = implode(' AND ', $where);
    
    $sql = "SELECT i.id, i.tanggal_izin, i.pertemuan_ke, i.jenis, i.keterangan, i.file_surat, i.created_at,
                   m.nim, m.nama as nama_mhs,
                   k.nama_kelas,
                   mk.nama_mk,
                   i.sesi_id,
                   CASE WHEN i.sesi_id IS NOT NULL THEN s.tanggal ELSE NULL END as sesi_tanggal
            FROM izin i
            JOIN mahasiswa m ON i.mahasiswa_id = m.id
            JOIN jadwal j ON i.jadwal_id = j.id
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            JOIN kelas k ON j.kelas_id = k.id
            LEFT JOIN sesi_absensi s ON i.sesi_id = s.id
            WHERE $whereSQL
            ORDER BY i.created_at DESC
            LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    
    echo json_encode(['success' => true, 'data' => $rows, 'count' => count($rows)]);
    exit;
}

// ════════════════════════════════════════════════
// GET RIWAYAT APPROVAL
// ════════════════════════════════════════════════
if ($action === 'get_riwayat') {
    $mk_id = (int)($_POST['mk_id'] ?? 0);
    $kelas_id = (int)($_POST['kelas_id'] ?? 0);
    
    $where = ["i.status IN ('disetujui','ditolak')", "j.dosen_id = ?"];
    $params = [$dosen_id];
    $types = 'i';
    
    if ($mk_id) { $where[] = "j.mata_kuliah_id = ?"; $params[] = $mk_id; $types .= 'i'; }
    if ($kelas_id) { $where[] = "j.kelas_id = ?"; $params[] = $kelas_id; $types .= 'i'; }
    
    $whereSQL = implode(' AND ', $where);
    
    $sql = "SELECT i.id, i.tanggal_izin, i.pertemuan_ke, i.jenis, i.status, i.keterangan, i.file_surat,
                   m.nim, m.nama as nama_mhs,
                   k.nama_kelas,
                   mk.nama_mk,
                   a.catatan, a.approved_at
            FROM izin i
            JOIN mahasiswa m ON i.mahasiswa_id = m.id
            JOIN jadwal j ON i.jadwal_id = j.id
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            JOIN kelas k ON j.kelas_id = k.id
            LEFT JOIN approval a ON a.izin_id = i.id
            WHERE $whereSQL
            ORDER BY a.approved_at DESC
            LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ════════════════════════════════════════════════
// APPROVE
// ════════════════════════════════════════════════
if ($action === 'approve') {
    $izin_id = (int)($_POST['izin_id'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? '');
    
    if (!$izin_id) {
        echo json_encode(['success' => false, 'msg' => 'ID izin tidak valid']); exit;
    }
    
    // Verifikasi izin milik dosen ini
    $chk = $conn->prepare("SELECT i.id, i.sesi_id, i.mahasiswa_id FROM izin i 
                           JOIN jadwal j ON i.jadwal_id = j.id 
                           WHERE i.id = ? AND j.dosen_id = ? AND i.status = 'pending'");
    $chk->bind_param('ii', $izin_id, $dosen_id);
    $chk->execute();
    $chk->bind_result($valid_id, $sesi_id, $mhs_id);
    if (!$chk->fetch()) {
        echo json_encode(['success' => false, 'msg' => 'Izin tidak ditemukan atau sudah diproses']); exit;
    }
    $chk->close();
    
    // Update izin
    $stmt = $conn->prepare("UPDATE izin SET status = 'disetujui' WHERE id = ?");
    $stmt->bind_param('i', $izin_id);
    $stmt->execute();
    
    // Insert approval
    $stmt = $conn->prepare("INSERT INTO approval (izin_id, dosen_id, status, catatan) VALUES (?, ?, 'disetujui', ?)");
    $stmt->bind_param('iis', $izin_id, $dosen_id, $catatan);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'msg' => 'Izin disetujui']);
    exit;
}

// ════════════════════════════════════════════════
// REJECT
// ════════════════════════════════════════════════
if ($action === 'reject') {
    $izin_id = (int)($_POST['izin_id'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? '');
    
    if (!$izin_id) {
        echo json_encode(['success' => false, 'msg' => 'ID izin tidak valid']); exit;
    }
    if (empty($catatan)) {
        echo json_encode(['success' => false, 'msg' => 'Berikan alasan penolakan']); exit;
    }
    
    // Verifikasi izin milik dosen ini
    $chk = $conn->prepare("SELECT i.id FROM izin i 
                           JOIN jadwal j ON i.jadwal_id = j.id 
                           WHERE i.id = ? AND j.dosen_id = ? AND i.status = 'pending'");
    $chk->bind_param('ii', $izin_id, $dosen_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows == 0) {
        echo json_encode(['success' => false, 'msg' => 'Izin tidak ditemukan atau sudah diproses']); exit;
    }
    $chk->close();
    
    // Update izin
    $stmt = $conn->prepare("UPDATE izin SET status = 'ditolak' WHERE id = ?");
    $stmt->bind_param('i', $izin_id);
    $stmt->execute();
    
    // Insert approval
    $stmt = $conn->prepare("INSERT INTO approval (izin_id, dosen_id, status, catatan) VALUES (?, ?, 'ditolak', ?)");
    $stmt->bind_param('iis', $izin_id, $dosen_id, $catatan);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'msg' => 'Izin ditolak']);
    exit;
}

// ════════════════════════════════════════════════
// COUNTS (untuk badge notifikasi)
// ════════════════════════════════════════════════
if ($action === 'get_counts') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM izin i 
                            JOIN jadwal j ON i.jadwal_id = j.id 
                            WHERE j.dosen_id = ? AND i.status = 'pending'");
    $stmt->bind_param('i', $dosen_id);
    $stmt->execute();
    $stmt->bind_result($pending);
    $stmt->fetch();
    
    echo json_encode(['success' => true, 'pending' => $pending]);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal']);