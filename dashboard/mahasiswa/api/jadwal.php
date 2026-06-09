<?php
require_once __DIR__ . '/../../../auth/check.php';
header('Content-Type: application/json');

// Get mahasiswa_id from user
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

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── GET JADWAL MAHASISWA ─────────────────────────
if ($action === 'get_jadwal') {
    // Get TA aktif
    $ta = $conn->query("SELECT id FROM tahun_akademik WHERE status = 'aktif' LIMIT 1");
    $ta_id = 0;
    if ($ta && $row = $ta->fetch_assoc()) {
        $ta_id = $row['id'];
    }
    
    $sql = "SELECT 
                j.*,
                mk.kode_mk,
                mk.nama_mk,
                mk.sks,
                d.nidn,
                d.nama as nama_dosen,
                r.kode_ruangan,
                r.nama_ruangan,
                jk.jam_mulai,
                jk.jam_selesai,
                jk.jam_ke as jam_ke_list,
                (SELECT MAX(pertemuan_ke) FROM sesi_absensi WHERE jadwal_id = j.id) as sesi_terakhir
            FROM jadwal j
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            JOIN dosen d ON j.dosen_id = d.id
            LEFT JOIN ruangan r ON j.ruangan_id = r.id
            LEFT JOIN jam_ke jk ON j.jam_ke_id = jk.id
            WHERE j.kelas_id = ?";
    
    $params = [$kelas_id];
    $types = 'i';
    
    if ($ta_id) {
        $sql .= " AND j.tahun_akademik_id = ?";
        $params[] = $ta_id;
        $types .= 'i';
    }
    
    $sql .= " ORDER BY FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jk.jam_mulai";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ─── GET SESI AKTIF ───────────────────────────────
if ($action === 'get_sesi_aktif') {
    $sql = "SELECT s.id as sesi_id, s.jadwal_id, s.pertemuan_ke, s.status,
                   mk.nama_mk, jk.jam_mulai, jk.jam_selesai
            FROM sesi_absensi s
            JOIN jadwal j ON s.jadwal_id = j.id
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            LEFT JOIN jam_ke jk ON j.jam_ke_id = jk.id
            WHERE j.kelas_id = ? AND s.status = 'aktif'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $kelas_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ─── EXPORT CSV ───────────────────────────────────
if ($action === 'export_csv') {
    // Get TA aktif
    $ta = $conn->query("SELECT tahun, semester FROM tahun_akademik WHERE status = 'aktif' LIMIT 1");
    $ta_text = 'Jadwal';
    if ($ta && $row = $ta->fetch_assoc()) {
        $ta_text = $row['tahun'] . ' ' . $row['semester'];
    }
    
    // Get kelas name
    $kelas_name = '';
    $q = $conn->query("SELECT nama_kelas FROM kelas WHERE id = $kelas_id");
    if ($q && $row = $q->fetch_assoc()) {
        $kelas_name = $row['nama_kelas'];
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="jadwal_' . str_replace(' ', '_', $kelas_name) . '_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF";
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Hari', 'Jam Mulai', 'Jam Selesai', 'Mata Kuliah', 'SKS', 'Dosen', 'Ruangan']);
    
    $sql = "SELECT 
                j.hari,
                jk.jam_mulai,
                jk.jam_selesai,
                mk.nama_mk,
                mk.sks,
                d.nama as nama_dosen,
                r.nama_ruangan
            FROM jadwal j
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            JOIN dosen d ON j.dosen_id = d.id
            LEFT JOIN ruangan r ON j.ruangan_id = r.id
            LEFT JOIN jam_ke jk ON j.jam_ke_id = jk.id
            WHERE j.kelas_id = $kelas_id
            ORDER BY FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jk.jam_mulai";
    
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($out, [
            $row['hari'],
            substr($row['jam_mulai'], 0, 5),
            substr($row['jam_selesai'], 0, 5),
            $row['nama_mk'],
            $row['sks'],
            $row['nama_dosen'],
            $row['nama_ruangan'] ?: '-'
        ]);
    }
    fclose($out);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal']);
exit;
?>