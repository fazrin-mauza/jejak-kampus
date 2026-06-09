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
    
    echo json_encode([
        'success' => true,
        'data' => [
            'mk' => $mk->fetch_all(MYSQLI_ASSOC),
            'kelas' => $kelas->fetch_all(MYSQLI_ASSOC)
        ]
    ]);
    exit;
}

// ─── GET SESI AKTIF ───────────────────────────────
if ($action === 'get_sesi_aktif') {
    $sql = "SELECT s.id as sesi_id, s.pertemuan_ke, s.tanggal, s.qr_code,
                   mk.nama_mk, k.nama_kelas, jk.jam_mulai, jk.jam_selesai,
                   -- Total mahasiswa AKTIF di kelas
                   (SELECT COUNT(*) FROM mahasiswa m WHERE m.kelas_id = j.kelas_id AND m.status = 'aktif') as total_mhs,
                   -- COUNT DISTINCT untuk menghindari duplikat
                   (SELECT COUNT(DISTINCT mahasiswa_id) FROM absensi a 
                    WHERE a.sesi_id = s.id AND a.status = 'hadir'
                    AND EXISTS (SELECT 1 FROM mahasiswa m WHERE m.id = a.mahasiswa_id AND m.status = 'aktif')) as stat_hadir,
                   (SELECT COUNT(DISTINCT mahasiswa_id) FROM absensi a 
                    WHERE a.sesi_id = s.id AND a.status = 'telat'
                    AND EXISTS (SELECT 1 FROM mahasiswa m WHERE m.id = a.mahasiswa_id AND m.status = 'aktif')) as stat_telat,
                   (SELECT COUNT(*) FROM izin i 
                    WHERE i.sesi_id = s.id AND i.status = 'disetujui' AND i.jenis = 'izin'
                    AND EXISTS (SELECT 1 FROM mahasiswa m WHERE m.id = i.mahasiswa_id AND m.status = 'aktif')) as stat_izin,
                   (SELECT COUNT(*) FROM izin i 
                    WHERE i.sesi_id = s.id AND i.status = 'disetujui' AND i.jenis = 'sakit'
                    AND EXISTS (SELECT 1 FROM mahasiswa m WHERE m.id = i.mahasiswa_id AND m.status = 'aktif')) as stat_sakit,
                   (SELECT COUNT(DISTINCT mahasiswa_id) FROM absensi a 
                    WHERE a.sesi_id = s.id AND a.status = 'alpha'
                    AND EXISTS (SELECT 1 FROM mahasiswa m WHERE m.id = a.mahasiswa_id AND m.status = 'aktif')) as stat_alpha
            FROM sesi_absensi s
            JOIN jadwal j ON s.jadwal_id = j.id
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            JOIN kelas k ON j.kelas_id = k.id
            JOIN jam_ke jk ON j.jam_ke_id = jk.id
            WHERE j.dosen_id = ? AND s.status = 'aktif'
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $dosen_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if ($data) {
        $data['jam'] = substr($data['jam_mulai'], 0, 5) . '–' . substr($data['jam_selesai'], 0, 5);
        $hadirTelat = ($data['stat_hadir'] ?? 0) + ($data['stat_telat'] ?? 0);
        $izinSakit = ($data['stat_izin'] ?? 0) + ($data['stat_sakit'] ?? 0);
        $data['stat_belum'] = ($data['total_mhs'] ?? 0) - ($hadirTelat + $izinSakit + ($data['stat_alpha'] ?? 0));
        // Pastikan tidak negatif
        if ($data['stat_belum'] < 0) $data['stat_belum'] = 0;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// ─── GET SESI STATS (untuk realtime) ──────────────
if ($action === 'get_sesi_stats') {
    $sesi_id = (int)($_POST['sesi_id'] ?? 0);
    
    $sql = "SELECT 
                s.status as sesi_status,
                (SELECT COUNT(*) FROM mahasiswa m 
                 WHERE m.kelas_id = j.kelas_id AND m.status = 'aktif') as total_mhs,
                (SELECT COUNT(DISTINCT mahasiswa_id) FROM absensi a 
                 WHERE a.sesi_id = s.id AND a.status = 'hadir'
                 AND EXISTS (SELECT 1 FROM mahasiswa m WHERE m.id = a.mahasiswa_id AND m.status = 'aktif')) as stat_hadir,
                (SELECT COUNT(DISTINCT mahasiswa_id) FROM absensi a 
                 WHERE a.sesi_id = s.id AND a.status = 'telat'
                 AND EXISTS (SELECT 1 FROM mahasiswa m WHERE m.id = a.mahasiswa_id AND m.status = 'aktif')) as stat_telat,
                (SELECT COUNT(*) FROM izin i 
                 WHERE i.sesi_id = s.id AND i.status = 'disetujui' AND i.jenis = 'izin'
                 AND EXISTS (SELECT 1 FROM mahasiswa m WHERE m.id = i.mahasiswa_id AND m.status = 'aktif')) as stat_izin,
                (SELECT COUNT(*) FROM izin i 
                 WHERE i.sesi_id = s.id AND i.status = 'disetujui' AND i.jenis = 'sakit'
                 AND EXISTS (SELECT 1 FROM mahasiswa m WHERE m.id = i.mahasiswa_id AND m.status = 'aktif')) as stat_sakit,
                (SELECT COUNT(DISTINCT mahasiswa_id) FROM absensi a 
                 WHERE a.sesi_id = s.id AND a.status = 'alpha'
                 AND EXISTS (SELECT 1 FROM mahasiswa m WHERE m.id = a.mahasiswa_id AND m.status = 'aktif')) as stat_alpha
            FROM sesi_absensi s
            JOIN jadwal j ON s.jadwal_id = j.id
            WHERE s.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $sesi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if ($data) {
        $hadirTelat = ($data['stat_hadir'] ?? 0) + ($data['stat_telat'] ?? 0);
        $izinSakit = ($data['stat_izin'] ?? 0) + ($data['stat_sakit'] ?? 0);
        $data['stat_belum'] = ($data['total_mhs'] ?? 0) - ($hadirTelat + $izinSakit + ($data['stat_alpha'] ?? 0));
        if ($data['stat_belum'] < 0) $data['stat_belum'] = 0;
        $data['is_active'] = ($data['sesi_status'] === 'aktif');
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// ─── UPDATE QR ────────────────────────────────────
if ($action === 'update_qr') {
    $sesi_id = (int)($_POST['sesi_id'] ?? 0);
    $qr_code = $_POST['qr_code'] ?? '';
    
    $stmt = $conn->prepare("UPDATE sesi_absensi SET qr_code = ? WHERE id = ?");
    $stmt->bind_param('si', $qr_code, $sesi_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    exit;
}

// ─── TUTUP SESI ───────────────────────────────────
if ($action === 'tutup_sesi') {
    $sesi_id = (int)($_POST['sesi_id'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE sesi_absensi SET status = 'selesai' WHERE id = ?");
    $stmt->bind_param('i', $sesi_id);
    $ok = $stmt->execute();
    
    echo json_encode(['success' => $ok, 'msg' => $ok ? 'Sesi ditutup' : 'Gagal']);
    exit;
}

// ─── GET SESI LIST ────────────────────────────────
if ($action === 'get_sesi_list') {
    $mk_id = (int)($_POST['mk_id'] ?? 0);
    $kelas_id = (int)($_POST['kelas_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    $where = ["j.dosen_id = ?"];
    $params = [$dosen_id];
    $types = 'i';
    
    if ($mk_id) { $where[] = "j.mata_kuliah_id = ?"; $params[] = $mk_id; $types .= 'i'; }
    if ($kelas_id) { $where[] = "j.kelas_id = ?"; $params[] = $kelas_id; $types .= 'i'; }
    if ($status) { $where[] = "s.status = ?"; $params[] = $status; $types .= 's'; }
    
    $whereSQL = implode(' AND ', $where);
    
    $sql = "SELECT s.id, s.tanggal, s.pertemuan_ke, s.status,
                   mk.nama_mk, k.nama_kelas,
                   (SELECT COUNT(*) FROM mahasiswa m WHERE m.kelas_id = j.kelas_id AND m.status = 'aktif') as total_mhs,
                   (SELECT COUNT(DISTINCT mahasiswa_id) FROM absensi a WHERE a.sesi_id = s.id AND a.status IN ('hadir','telat')) as total_hadir
            FROM sesi_absensi s
            JOIN jadwal j ON s.jadwal_id = j.id
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            JOIN kelas k ON j.kelas_id = k.id
            WHERE $whereSQL
            ORDER BY s.tanggal DESC, s.id DESC
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ─── GET MONITORING ───────────────────────────────
if ($action === 'get_monitoring') {
    $sesi_id = (int)($_POST['sesi_id'] ?? 0);
    
    // Get kelas_id from sesi
    $stmt = $conn->prepare("SELECT j.kelas_id FROM sesi_absensi s JOIN jadwal j ON s.jadwal_id = j.id WHERE s.id = ?");
    $stmt->bind_param('i', $sesi_id);
    $stmt->execute();
    $stmt->bind_result($kelas_id);
    $stmt->fetch();
    $stmt->close();
    
    $sql = "SELECT 
                m.id as mahasiswa_id, m.nim, m.nama,
                a.status, a.waktu_absen,
                i.status as izin_status, i.jenis as izin_jenis
            FROM mahasiswa m
            LEFT JOIN absensi a ON m.id = a.mahasiswa_id AND a.sesi_id = ?
            LEFT JOIN izin i ON m.id = i.mahasiswa_id AND i.sesi_id = ? AND i.status = 'disetujui'
            WHERE m.kelas_id = ? AND m.status = 'aktif'
            ORDER BY m.nama";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $sesi_id, $sesi_id, $kelas_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        // Priority: absensi > izin disetujui > belum
        if ($row['status']) {
            // sudah ada di absensi
        } elseif ($row['izin_status'] === 'disetujui') {
            $row['status'] = $row['izin_jenis']; // 'izin' atau 'sakit'
        } else {
            $row['status'] = 'belum';
        }
        $rows[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ─── UPDATE ABSENSI ───────────────────────────────
if ($action === 'update_absensi') {
    $sesi_id = (int)($_POST['sesi_id'] ?? 0);
    $mahasiswa_id = (int)($_POST['mahasiswa_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if (!in_array($status, ['hadir', 'izin', 'sakit', 'alpha'])) {
        echo json_encode(['success' => false, 'msg' => 'Status tidak valid']); 
        exit;
    }
    
    // Gunakan INSERT IGNORE atau ON DUPLICATE KEY untuk cegah duplikat
    if ($status === 'izin' || $status === 'sakit') {
        // Hapus dari absensi dulu jika ada
        $conn->query("DELETE FROM absensi WHERE sesi_id = $sesi_id AND mahasiswa_id = $mahasiswa_id");
        
        // Insert atau update ke izin
        $result = $conn->query("INSERT INTO izin (sesi_id, mahasiswa_id, jenis, status) 
                                VALUES ($sesi_id, $mahasiswa_id, '$status', 'disetujui')
                                ON DUPLICATE KEY UPDATE jenis = '$status', status = 'disetujui'");
    } else {
        // Hapus dari izin dulu jika ada
        $conn->query("DELETE FROM izin WHERE sesi_id = $sesi_id AND mahasiswa_id = $mahasiswa_id");
        
        // Insert atau update ke absensi
        $result = $conn->query("INSERT INTO absensi (sesi_id, mahasiswa_id, status, waktu_absen) 
                                VALUES ($sesi_id, $mahasiswa_id, '$status', NOW())
                                ON DUPLICATE KEY UPDATE status = '$status', waktu_absen = NOW()");
    }
    
    echo json_encode(['success' => true, 'msg' => 'Status diperbarui']);
    exit;
}

// ─── HADIR SEMUA ──────────────────────────────────
if ($action === 'hadir_semua') {
    $sesi_id = (int)($_POST['sesi_id'] ?? 0);
    
    // Get mahasiswa yang BELUM absen, BELUM punya izin, dan STATUS AKTIF
    $sql = "SELECT m.id 
            FROM mahasiswa m
            WHERE m.kelas_id = (
                SELECT j.kelas_id FROM sesi_absensi s 
                JOIN jadwal j ON s.jadwal_id = j.id 
                WHERE s.id = ?
            )
            AND m.status = 'aktif'
            AND m.id NOT IN (
                SELECT DISTINCT mahasiswa_id FROM absensi 
                WHERE sesi_id = ? 
            )
            AND m.id NOT IN (
                SELECT mahasiswa_id FROM izin 
                WHERE sesi_id = ? AND status = 'disetujui'
            )";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $sesi_id, $sesi_id, $sesi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        // Gunakan INSERT IGNORE untuk cegah duplikat
        $insert = $conn->query("INSERT IGNORE INTO absensi (sesi_id, mahasiswa_id, status, waktu_absen) 
                                VALUES ($sesi_id, {$row['id']}, 'hadir', NOW())");
        if ($insert) $count++;
    }
    
    echo json_encode(['success' => true, 'msg' => "$count mahasiswa ditandai hadir"]);
    exit;
}

// ─── BULK UPDATE ──────────────────────────────────
if ($action === 'bulk_update') {
    $sesi_id = (int)($_POST['sesi_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $ids = explode(',', $_POST['ids'] ?? '');
    
    if (empty($ids)) {
        echo json_encode(['success' => false, 'msg' => 'Tidak ada mahasiswa dipilih']); 
        exit;
    }
    
    $count = 0;
    foreach ($ids as $mahasiswa_id) {
        $mahasiswa_id = (int)$mahasiswa_id;
        
        if ($status === 'izin' || $status === 'sakit') {
            // Hapus dari absensi
            $conn->query("DELETE FROM absensi WHERE sesi_id = $sesi_id AND mahasiswa_id = $mahasiswa_id");
            // Insert ke izin dengan IGNORE
            $conn->query("INSERT IGNORE INTO izin (sesi_id, mahasiswa_id, jenis, status) 
                          VALUES ($sesi_id, $mahasiswa_id, '$status', 'disetujui')");
        } else {
            // Hapus dari izin
            $conn->query("DELETE FROM izin WHERE sesi_id = $sesi_id AND mahasiswa_id = $mahasiswa_id");
            // Insert ke absensi dengan IGNORE
            $conn->query("INSERT IGNORE INTO absensi (sesi_id, mahasiswa_id, status, waktu_absen) 
                          VALUES ($sesi_id, $mahasiswa_id, '$status', NOW())");
        }
        $count++;
    }
    
    echo json_encode(['success' => true, 'msg' => "$count mahasiswa diperbarui"]);
    exit;
}

// ─── EXPORT MONITORING ────────────────────────────
if ($action === 'export_monitoring') {
    $sesi_id = (int)($_GET['sesi_id'] ?? 0);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="monitoring_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF";
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIM', 'Nama', 'Status', 'Waktu Absen']);
    
    // Query sama dengan get_monitoring
    $stmt = $conn->prepare("SELECT j.kelas_id FROM sesi_absensi s JOIN jadwal j ON s.jadwal_id = j.id WHERE s.id = ?");
    $stmt->bind_param('i', $sesi_id);
    $stmt->execute();
    $stmt->bind_result($kelas_id);
    $stmt->fetch();
    $stmt->close();
    
    $sql = "SELECT m.nim, m.nama, a.status, a.waktu_absen, i.status as izin_status, i.jenis
            FROM mahasiswa m
            LEFT JOIN absensi a ON m.id = a.mahasiswa_id AND a.sesi_id = ?
            LEFT JOIN izin i ON m.id = i.mahasiswa_id AND i.sesi_id = ? AND i.status = 'disetujui'
            WHERE m.kelas_id = ? AND m.status = 'aktif'
            ORDER BY m.nama";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $sesi_id, $sesi_id, $kelas_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['status'] ?? ($row['izin_status'] === 'disetujui' ? $row['jenis'] : 'belum');
        fputcsv($out, [$row['nim'], $row['nama'], $status, $row['waktu_absen'] ?? '-']);
    }
    fclose($out);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal']);
exit;
?>