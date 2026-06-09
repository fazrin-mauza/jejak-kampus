<?php
require_once __DIR__ . '/../../../auth/check.php';
header('Content-Type: application/json');

// Get mahasiswa_id
$user_id = $user['id'] ?? 0;
$mahasiswa_id = 0;
$kelas_id = 0;

if ($user_id && $user['role'] === 'mahasiswa') {
    $stmt = $conn->prepare("SELECT id, kelas_id FROM mahasiswa WHERE user_id = ? AND status = 'aktif'");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($mahasiswa_id, $kelas_id);
    $stmt->fetch();
    $stmt->close();
}

if (!$mahasiswa_id) {
    echo json_encode(['success' => false, 'msg' => 'Data mahasiswa tidak ditemukan atau tidak aktif']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ════════════════════════════════════════════════
// GET MK OPTIONS (dari jadwal kelas, TA aktif)
// ════════════════════════════════════════════════
if ($action === 'get_mk_options') {
    $sql = "SELECT DISTINCT mk.id, mk.nama_mk, mk.sks, d.nama as nama_dosen
            FROM jadwal j
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            JOIN dosen d ON j.dosen_id = d.id
            JOIN tahun_akademik ta ON j.tahun_akademik_id = ta.id
            WHERE j.kelas_id = ? AND ta.status = 'aktif'
            ORDER BY mk.nama_mk";
    
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

// ════════════════════════════════════════════════
// GET PERTEMUAN OPTIONS (sesi yg sudah ada + sisa)
// ════════════════════════════════════════════════
if ($action === 'get_pertemuan_options') {
    $mk_id = (int)($_POST['mk_id'] ?? 0);
    if (!$mk_id) {
        echo json_encode(['success' => false, 'msg' => 'Pilih mata kuliah']); exit;
    }
    
    // Get jadwal_id untuk MK ini di kelas mahasiswa
    $stmt = $conn->prepare("SELECT j.id FROM jadwal j 
                            JOIN tahun_akademik ta ON j.tahun_akademik_id = ta.id
                            WHERE j.kelas_id = ? AND j.mata_kuliah_id = ? AND ta.status = 'aktif'");
    $stmt->bind_param('ii', $kelas_id, $mk_id);
    $stmt->execute();
    $stmt->bind_result($jadwal_id);
    $stmt->fetch();
    $stmt->close();
    
    if (!$jadwal_id) {
        echo json_encode(['success' => false, 'msg' => 'Jadwal tidak ditemukan']); exit;
    }
    
    // List sesi yang sudah dibuat
    $sql = "SELECT id as sesi_id, pertemuan_ke, tanggal, status
            FROM sesi_absensi 
            WHERE jadwal_id = ?
            ORDER BY pertemuan_ke";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $jadwal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sesi_rows = [];
    while ($row = $result->fetch_assoc()) {
        $sesi_rows[$row['pertemuan_ke']] = $row;
    }
    $stmt->close();
    
    // Get total SKS & jadwal untuk menentukan ada berapa pertemuan
    $stmt = $conn->prepare("SELECT mk.sks FROM jadwal j JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id WHERE j.id = ?");
    $stmt->bind_param('i', $jadwal_id);
    $stmt->execute();
    $stmt->bind_result($sks);
    $stmt->fetch();
    $stmt->close();
    
    $total_pertemuan = 16; // Default 16 pertemuan per semester
    
    // Build response: pertemuan 1-16 dengan info sesi jika ada
    $pertemuan_list = [];
    $today = date('Y-m-d');
    
    for ($p = 1; $p <= $total_pertemuan; $p++) {
        $item = [
            'pertemuan_ke' => $p,
            'sesi_id' => null,
            'tanggal' => null,
            'status' => 'belum_ada_sesi'
        ];
        
        if (isset($sesi_rows[$p])) {
            $item['sesi_id'] = $sesi_rows[$p]['sesi_id'];
            $item['tanggal'] = $sesi_rows[$p]['tanggal'];
            $item['status'] = $sesi_rows[$p]['status'];
        }
        
        // Cek apakah mahasiswa sudah mengajukan izin untuk pertemuan ini
        $chk = $conn->prepare("SELECT id, status FROM izin 
                               WHERE mahasiswa_id = ? AND jadwal_id = ? AND pertemuan_ke = ?");
        $chk->bind_param('iii', $mahasiswa_id, $jadwal_id, $p);
        $chk->execute();
        $chk->bind_result($izin_id, $izin_status);
        if ($chk->fetch()) {
            $item['izin_ada'] = true;
            $item['izin_id'] = $izin_id;
            $item['izin_status'] = $izin_status;
        } else {
            $item['izin_ada'] = false;
        }
        $chk->close();
        
        $pertemuan_list[] = $item;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $pertemuan_list,
        'jadwal_id' => $jadwal_id
    ]);
    exit;
}

// ════════════════════════════════════════════════
// SUBMIT IZIN
// ════════════════════════════════════════════════
if ($action === 'submit') {
    $jadwal_id = (int)($_POST['jadwal_id'] ?? 0);
    $pertemuan_ke = (int)($_POST['pertemuan_ke'] ?? 0);
    $sesi_id = $_POST['sesi_id'] ? (int)$_POST['sesi_id'] : null;
    $tanggal_izin = $_POST['tanggal_izin'] ?? '';
    $jenis = $_POST['jenis'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    
    // Validasi
    if (!$jadwal_id || !$pertemuan_ke || !$jenis) {
        echo json_encode(['success' => false, 'msg' => 'Field wajib belum lengkap']); exit;
    }
    if (!in_array($jenis, ['sakit', 'izin'])) {
        echo json_encode(['success' => false, 'msg' => 'Jenis tidak valid']); exit;
    }
    
    // Verifikasi jadwal milik kelas mahasiswa
    $chk = $conn->prepare("SELECT j.id, mk.nama_mk FROM jadwal j 
                           JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
                           JOIN tahun_akademik ta ON j.tahun_akademik_id = ta.id
                           WHERE j.id = ? AND j.kelas_id = ? AND ta.status = 'aktif'");
    $chk->bind_param('ii', $jadwal_id, $kelas_id);
    $chk->execute();
    $chk->bind_result($valid_jadwal, $nama_mk);
    if (!$chk->fetch()) {
        echo json_encode(['success' => false, 'msg' => 'Jadwal tidak ditemukan untuk kelas Anda']); exit;
    }
    $chk->close();
    
    // Cek duplikasi
    $chk = $conn->prepare("SELECT id FROM izin WHERE mahasiswa_id = ? AND jadwal_id = ? AND pertemuan_ke = ?");
    $chk->bind_param('iii', $mahasiswa_id, $jadwal_id, $pertemuan_ke);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        echo json_encode(['success' => false, 'msg' => 'Anda sudah mengajukan izin untuk pertemuan ini']); exit;
    }
    $chk->close();
    
    // Cek apakah sudah absen
    if ($sesi_id) {
        $chk = $conn->prepare("SELECT id FROM absensi WHERE sesi_id = ? AND mahasiswa_id = ?");
        $chk->bind_param('ii', $sesi_id, $mahasiswa_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            echo json_encode(['success' => false, 'msg' => 'Anda sudah absen untuk pertemuan ini']); exit;
        }
        $chk->close();
    }
    
    // Upload file jika ada
    $file_surat = null;
    $upload_dir = __DIR__ . '/../../../uploads/izin/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['file_surat']['name'], PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'msg' => 'Format file tidak diizinkan: ' . $ext]); exit;
        }
        if ($_FILES['file_surat']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'msg' => 'Ukuran file maksimal 5MB']); exit;
        }
        
        $filename = 'izin_' . $user_id . '_' . time() . '.' . $ext;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['file_surat']['tmp_name'], $filepath)) {
            $file_surat = $filename;
        }
    }
    
    // Insert ke tabel izin
    $stmt = $conn->prepare("INSERT INTO izin (mahasiswa_id, jadwal_id, pertemuan_ke, sesi_id, tanggal_izin, jenis, file_surat, keterangan, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param('iiiissss', $mahasiswa_id, $jadwal_id, $pertemuan_ke, $sesi_id, $tanggal_izin, $jenis, $file_surat, $keterangan);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'msg' => 'Pengajuan izin berhasil dikirim',
            'izin_id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Gagal menyimpan: ' . $conn->error]);
    }
    exit;
}

// ════════════════════════════════════════════════
// GET RIWAYAT
// ════════════════════════════════════════════════
if ($action === 'get_riwayat') {
    $sql = "SELECT i.id, i.tanggal_izin, i.pertemuan_ke, i.jenis, i.status, i.keterangan, i.file_surat,
                   mk.nama_mk, d.nama as nama_dosen,
                   i.created_at
            FROM izin i
            JOIN jadwal j ON i.jadwal_id = j.id
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            JOIN dosen d ON j.dosen_id = d.id
            WHERE i.mahasiswa_id = ?
            ORDER BY i.created_at DESC
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $mahasiswa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    // Hitung pending
    $pending_count = 0;
    foreach ($rows as $r) {
        if ($r['status'] === 'pending') $pending_count++;
    }
    
    echo json_encode(['success' => true, 'data' => $rows, 'pending_count' => $pending_count]);
    exit;
}

// ════════════════════════════════════════════════
// GET PERINGATAN KEHADIRAN
// ════════════════════════════════════════════════
if ($action === 'get_peringatan') {
    // Cek MK yang kehadirannya di bawah batas minimum
    $min_kehadiran = 80;
    $set = $conn->query("SELECT min_kehadiran_persen FROM settings LIMIT 1");
    if ($set && $row = $set->fetch_assoc()) {
        $min_kehadiran = $row['min_kehadiran_persen'];
    }
    
    $sql = "SELECT 
                j.id as jadwal_id,
                mk.nama_mk,
                COUNT(DISTINCT s.id) as total_sesi,
                SUM(CASE WHEN a.status IN ('hadir','telat') OR iz.status = 'disetujui' THEN 1 ELSE 0 END) as total_hadir,
                ROUND(
                    SUM(CASE WHEN a.status IN ('hadir','telat') OR iz.status = 'disetujui' THEN 1 ELSE 0 END) * 100.0 / 
                    GREATEST(COUNT(DISTINCT s.id), 1), 1
                ) as persen_kehadiran
            FROM jadwal j
            JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
            JOIN sesi_absensi s ON s.jadwal_id = j.id AND s.status = 'selesai'
            LEFT JOIN absensi a ON a.sesi_id = s.id AND a.mahasiswa_id = ?
            LEFT JOIN izin iz ON iz.sesi_id = s.id AND iz.mahasiswa_id = ? AND iz.status = 'disetujui'
            WHERE j.kelas_id = ?
            GROUP BY j.id, mk.nama_mk
            HAVING persen_kehadiran < ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiid', $mahasiswa_id, $mahasiswa_id, $kelas_id, $min_kehadiran);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $rows, 'min_kehadiran' => $min_kehadiran]);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal']);