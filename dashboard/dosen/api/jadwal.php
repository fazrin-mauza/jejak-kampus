<?php
require_once __DIR__ . '/../../../auth/check.php';
header('Content-Type: application/json');

// $user sudah tersedia dari auth/check.php
$user_id = $user['id'] ?? 0;
$user_role = $user['role'] ?? '';

// Get dosen_id berdasarkan user_id
$dosen_id = 0;
if ($user_id && $user_role === 'dosen') {
    $stmt = $conn->prepare("SELECT id FROM dosen WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($dosen_id);
    $stmt->fetch();
    $stmt->close();
}

// Validasi akses
if (!$dosen_id) {
    echo json_encode(['success' => false, 'msg' => 'Data dosen tidak ditemukan atau akses ditolak']);
    exit;
}

$action = $_POST['action'] ?? '';

// ─── GET TA OPTIONS ───────────────────────────────
if ($action === 'get_ta_options') {
    $sql = "SELECT DISTINCT ta.id, ta.tahun, ta.semester, ta.status 
            FROM tahun_akademik ta
            JOIN jadwal j ON ta.id = j.tahun_akademik_id
            WHERE j.dosen_id = ?
            ORDER BY ta.tahun DESC, ta.semester DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $dosen_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ─── GET MK OPTIONS ───────────────────────────────
if ($action === 'get_mk_options') {
    $sql = "SELECT DISTINCT mk.id, mk.nama_mk 
            FROM mata_kuliah mk
            JOIN jadwal j ON mk.id = j.mata_kuliah_id
            WHERE j.dosen_id = ?
            ORDER BY mk.nama_mk";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $dosen_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ─── LIST JADWAL ──────────────────────────────────
if ($action === 'list') {
    $ta_id = (int)($_POST['tahun_akademik_id'] ?? 0);
    $mk_id = (int)($_POST['mata_kuliah_id'] ?? 0);
    $page = max(1, (int)($_POST['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    if (!$ta_id) {
        echo json_encode(['success' => false, 'msg' => 'Pilih Tahun Akademik']); exit;
    }
    
    $where = ["j.dosen_id = ?", "j.tahun_akademik_id = ?"];
    $params = [$dosen_id, $ta_id];
    $types = 'ii';
    
    if ($mk_id) {
        $where[] = "j.mata_kuliah_id = ?";
        $params[] = $mk_id;
        $types .= 'i';
    }
    
    $whereSQL = implode(' AND ', $where);
    
    // Count total
    $countSQL = "SELECT COUNT(*) FROM jadwal j WHERE $whereSQL";
    $stmt = $conn->prepare($countSQL);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    
    // Get data
    $sql = "SELECT 
            j.*,
            k.nama_kelas,
            mk.nama_mk,
            mk.sks,
            r.kode_ruangan,
            r.nama_ruangan,
            jk.jam_mulai,
            (SELECT jam_selesai FROM jam_ke WHERE jam_ke = jk.jam_ke + mk.sks - 1) as jam_selesai,
            jk.jam_ke as jam_ke_mulai,
                (SELECT id FROM sesi_absensi WHERE jadwal_id = j.id AND status = 'aktif' LIMIT 1) as sesi_id,
                (SELECT pertemuan_ke FROM sesi_absensi WHERE jadwal_id = j.id AND status = 'aktif' LIMIT 1) as pertemuan_ke,
                (SELECT MAX(pertemuan_ke) FROM sesi_absensi WHERE jadwal_id = j.id) as sesi_terakhir,
                CASE WHEN EXISTS (SELECT 1 FROM sesi_absensi WHERE jadwal_id = j.id AND status = 'aktif') THEN 1 ELSE 0 END as sesi_aktif
            FROM jadwal j
            LEFT JOIN kelas k ON j.kelas_id = k.id
            LEFT JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            LEFT JOIN ruangan r ON j.ruangan_id = r.id
            LEFT JOIN jam_ke jk ON j.jam_ke_id = jk.id
            WHERE $whereSQL
            ORDER BY FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jk.jam_mulai
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    
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

// ─── CREATE SESI ──────────────────────────────────
if ($action === 'create_sesi') {
    $jadwal_id = (int)($_POST['jadwal_id'] ?? 0);
    $pertemuan_ke = (int)($_POST['pertemuan_ke'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? '';
    
    if (!$jadwal_id || !$pertemuan_ke || !$tanggal) {
        echo json_encode(['success' => false, 'msg' => 'Semua field wajib diisi']); exit;
    }
    
    // Verifikasi jadwal milik dosen ini
    $chk = $conn->prepare("SELECT id FROM jadwal WHERE id = ? AND dosen_id = ?");
    $chk->bind_param('ii', $jadwal_id, $dosen_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows == 0) {
        echo json_encode(['success' => false, 'msg' => 'Jadwal tidak ditemukan']); exit;
    }
    $chk->close();
    
    // Cek apakah sudah ada sesi aktif untuk jadwal ini
    $chk = $conn->prepare("SELECT id FROM sesi_absensi WHERE jadwal_id = ? AND status = 'aktif'");
    $chk->bind_param('i', $jadwal_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        echo json_encode(['success' => false, 'msg' => 'Masih ada sesi aktif untuk jadwal ini']); exit;
    }
    $chk->close();
    
    // Get tahun_akademik_id dari jadwal
    $stmt = $conn->prepare("SELECT tahun_akademik_id FROM jadwal WHERE id = ?");
    $stmt->bind_param('i', $jadwal_id);
    $stmt->execute();
    $stmt->bind_result($ta_id);
    $stmt->fetch();
    $stmt->close();
    
    // Get radius dari settings
    $radius = 50;
    $latitude = null;
    $longitude = null;
    $set = $conn->query("SELECT radius_absensi, latitude, longitude FROM settings LIMIT 1");
    if ($set && $row = $set->fetch_assoc()) {
        $radius = $row['radius_absensi'] ?? 50;
        $latitude = $row['latitude'] ?? null;
        $longitude = $row['longitude'] ?? null;
    }
    
    // Create sesi
    $stmt = $conn->prepare("INSERT INTO sesi_absensi (jadwal_id, tanggal, pertemuan_ke, latitude, longitude, radius, status, tahun_akademik_id) VALUES (?, ?, ?, ?, ?, ?, 'aktif', ?)");
    $stmt->bind_param('isiddii', $jadwal_id, $tanggal, $pertemuan_ke, $latitude, $longitude, $radius, $ta_id);
    $stmt->execute();
    $sesi_id = $stmt->insert_id;
    $stmt->close();
    
    // Get info for response
    $stmt = $conn->prepare("SELECT mk.nama_mk, k.nama_kelas FROM jadwal j JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id JOIN kelas k ON j.kelas_id = k.id WHERE j.id = ?");
    $stmt->bind_param('i', $jadwal_id);
    $stmt->execute();
    $stmt->bind_result($nama_mk, $nama_kelas);
    $stmt->fetch();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'msg' => 'Sesi berhasil dibuat',
        'sesi_id' => $sesi_id,
        'nama_mk' => $nama_mk,
        'nama_kelas' => $nama_kelas,
        'pertemuan_ke' => $pertemuan_ke
    ]);
    exit;
}

// ─── UPDATE QR ────────────────────────────────────
if ($action === 'update_qr') {
    $sesi_id = (int)($_POST['sesi_id'] ?? 0);
    $qr_code = $_POST['qr_code'] ?? '';
    
    // Verifikasi sesi milik dosen ini
    $chk = $conn->prepare("SELECT s.id FROM sesi_absensi s JOIN jadwal j ON s.jadwal_id = j.id WHERE s.id = ? AND j.dosen_id = ?");
    $chk->bind_param('ii', $sesi_id, $dosen_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows == 0) {
        echo json_encode(['success' => false, 'msg' => 'Sesi tidak ditemukan']); exit;
    }
    $chk->close();
    
    $stmt = $conn->prepare("UPDATE sesi_absensi SET qr_code = ? WHERE id = ?");
    $stmt->bind_param('si', $qr_code, $sesi_id);
    $ok = $stmt->execute();
    
    echo json_encode(['success' => $ok]);
    exit;
}

// ─── TUTUP SESI ───────────────────────────────────
if ($action === 'tutup_sesi') {
    $sesi_id = (int)($_POST['sesi_id'] ?? 0);
    
    // Verifikasi sesi milik dosen ini
    $chk = $conn->prepare("SELECT s.id FROM sesi_absensi s JOIN jadwal j ON s.jadwal_id = j.id WHERE s.id = ? AND j.dosen_id = ?");
    $chk->bind_param('ii', $sesi_id, $dosen_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows == 0) {
        echo json_encode(['success' => false, 'msg' => 'Sesi tidak ditemukan']); exit;
    }
    $chk->close();
    
    $stmt = $conn->prepare("UPDATE sesi_absensi SET status = 'selesai' WHERE id = ?");
    $stmt->bind_param('i', $sesi_id);
    $ok = $stmt->execute();
    
    echo json_encode(['success' => $ok, 'msg' => $ok ? 'Sesi ditutup' : 'Gagal']);
    exit;
}

// ─── EXPORT CSV ───────────────────────────────────
if ($action === 'export_csv') {
    $ta_id = (int)($_POST['tahun_akademik_id'] ?? 0);
    $mk_id = (int)($_POST['mata_kuliah_id'] ?? 0);
    
    $where = ["j.dosen_id = ?", "j.tahun_akademik_id = ?"];
    $params = [$dosen_id, $ta_id];
    $types = 'ii';
    
    if ($mk_id) {
        $where[] = "j.mata_kuliah_id = ?";
        $params[] = $mk_id;
        $types .= 'i';
    }
    
    $whereSQL = implode(' AND ', $where);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="jadwal_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF";
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Hari', 'Jam', 'Mata Kuliah', 'SKS', 'Kelas', 'Ruangan', 'Status']);
    
    $sql = "SELECT 
                j.hari, jk.jam_mulai, jk.jam_selesai, mk.nama_mk, mk.sks, 
                k.nama_kelas, r.nama_ruangan,
                CASE WHEN EXISTS (SELECT 1 FROM sesi_absensi WHERE jadwal_id = j.id AND status = 'aktif') THEN 'Berlangsung' ELSE '-' END as status
            FROM jadwal j
            LEFT JOIN kelas k ON j.kelas_id = k.id
            LEFT JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            LEFT JOIN ruangan r ON j.ruangan_id = r.id
            LEFT JOIN jam_ke jk ON j.jam_ke_id = jk.id
            WHERE $whereSQL
            ORDER BY FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jk.jam_mulai";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($out, [
            $row['hari'],
            substr($row['jam_mulai'], 0, 5) . '-' . substr($row['jam_selesai'], 0, 5),
            $row['nama_mk'],
            $row['sks'],
            $row['nama_kelas'],
            $row['nama_ruangan'] ?: '-',
            $row['status']
        ]);
    }
    fclose($out);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal']);
exit;
?>