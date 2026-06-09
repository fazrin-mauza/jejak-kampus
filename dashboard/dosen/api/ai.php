<?php
require_once __DIR__ . '/../../../auth/check.php';
header('Content-Type: application/json');

$user_id = $user['id'] ?? 0;
$dosen_id = 0;
$nama_dosen = '';

if ($user_id) {
    $stmt = $conn->prepare("SELECT id, nama FROM dosen WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($dosen_id, $nama_dosen);
    $stmt->fetch();
    $stmt->close();
}

if (!$dosen_id) {
    echo json_encode(['success' => false, 'error' => 'Dosen tidak ditemukan']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// ========== 1. GET KELAS BY MATA KULIAH ==========
if ($action === 'get_kelas_by_matkul') {
    $mk_id = (int)($input['mk_id'] ?? 0);
    
    $sql = "SELECT DISTINCT k.id, k.nama_kelas 
            FROM jadwal j 
            INNER JOIN kelas k ON j.kelas_id = k.id 
            WHERE j.dosen_id = ? AND j.mata_kuliah_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $dosen_id, $mk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $kelas = [];
    while ($row = $result->fetch_assoc()) {
        $kelas[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['success' => true, 'kelas' => $kelas]);
    exit;
}

// ========== 2. BUAT SESI DAN HADIR SEMUA ==========
if ($action === 'buat_sesi_dan_hadir_semua') {
    $mk_id = (int)($input['mk_id'] ?? 0);
    $kelas_id = (int)($input['kelas_id'] ?? 0);
    
    $jadwal = $conn->prepare("SELECT id FROM jadwal WHERE dosen_id = ? AND mata_kuliah_id = ? AND kelas_id = ? LIMIT 1");
    $jadwal->bind_param('iii', $dosen_id, $mk_id, $kelas_id);
    $jadwal->execute();
    $jadwal->bind_result($jadwal_id);
    if (!$jadwal->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Jadwal tidak ditemukan']);
        exit;
    }
    $jadwal->close();
    
    $tanggal = date('Y-m-d');
    $cek = $conn->prepare("SELECT id FROM sesi_absensi WHERE jadwal_id = ? AND tanggal = ?");
    $cek->bind_param('is', $jadwal_id, $tanggal);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Sesi untuk hari ini sudah ada']);
        exit;
    }
    $cek->close();
    
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM sesi_absensi WHERE jadwal_id = ?");
    $countStmt->bind_param('i', $jadwal_id);
    $countStmt->execute();
    $countStmt->bind_result($total);
    $countStmt->fetch();
    $countStmt->close();
    $pertemuan_ke = $total + 1;
    
    $thn = $conn->query("SELECT id FROM tahun_akademik WHERE status='aktif' LIMIT 1")->fetch_assoc();
    $tahun_akademik_id = $thn ? $thn['id'] : null;
    
    $insert = $conn->prepare("INSERT INTO sesi_absensi (jadwal_id, tanggal, pertemuan_ke, status, tahun_akademik_id) VALUES (?, ?, ?, 'aktif', ?)");
    $insert->bind_param('isii', $jadwal_id, $tanggal, $pertemuan_ke, $tahun_akademik_id);
    if (!$insert->execute()) {
        echo json_encode(['success' => false, 'error' => 'Gagal membuat sesi']);
        exit;
    }
    $sesi_id = $conn->insert_id;
    
    $mhs = $conn->prepare("SELECT id FROM mahasiswa WHERE kelas_id = ? AND status = 'aktif'");
    $mhs->bind_param('i', $kelas_id);
    $mhs->execute();
    $resMhs = $mhs->get_result();
    $countHadir = 0;
    while ($row = $resMhs->fetch_assoc()) {
        $insAbs = $conn->prepare("INSERT INTO absensi (sesi_id, mahasiswa_id, status, waktu_absen) VALUES (?, ?, 'hadir', NOW())");
        $insAbs->bind_param('ii', $sesi_id, $row['id']);
        $insAbs->execute();
        $countHadir++;
    }
    
    echo json_encode(['success' => true, 'message' => "$countHadir mahasiswa telah ditandai HADIR"]);
    exit;
}

// ========== 3. BUAT SESI KOSONG ==========
if ($action === 'buat_sesi_kosong') {
    $mk_id = (int)($input['mk_id'] ?? 0);
    $kelas_id = (int)($input['kelas_id'] ?? 0);
    
    $jadwal = $conn->prepare("SELECT id FROM jadwal WHERE dosen_id = ? AND mata_kuliah_id = ? AND kelas_id = ? LIMIT 1");
    $jadwal->bind_param('iii', $dosen_id, $mk_id, $kelas_id);
    $jadwal->execute();
    $jadwal->bind_result($jadwal_id);
    if (!$jadwal->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Jadwal tidak ditemukan']);
        exit;
    }
    $jadwal->close();
    
    $tanggal = date('Y-m-d');
    $cek = $conn->prepare("SELECT id FROM sesi_absensi WHERE jadwal_id = ? AND tanggal = ?");
    $cek->bind_param('is', $jadwal_id, $tanggal);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Sesi untuk hari ini sudah ada']);
        exit;
    }
    $cek->close();
    
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM sesi_absensi WHERE jadwal_id = ?");
    $countStmt->bind_param('i', $jadwal_id);
    $countStmt->execute();
    $countStmt->bind_result($total);
    $countStmt->fetch();
    $countStmt->close();
    $pertemuan_ke = $total + 1;
    
    $thn = $conn->query("SELECT id FROM tahun_akademik WHERE status='aktif' LIMIT 1")->fetch_assoc();
    $tahun_akademik_id = $thn ? $thn['id'] : null;
    
    $insert = $conn->prepare("INSERT INTO sesi_absensi (jadwal_id, tanggal, pertemuan_ke, status, tahun_akademik_id) VALUES (?, ?, ?, 'aktif', ?)");
    $insert->bind_param('isii', $jadwal_id, $tanggal, $pertemuan_ke, $tahun_akademik_id);
    if ($insert->execute()) {
        echo json_encode(['success' => true, 'sesi_id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}

// ========== 4. EKSTRAK TIDAK HADIR DENGAN AI ==========
if ($action === 'ekstrak_tidak_hadir') {
    $mk_id = (int)($input['mk_id'] ?? 0);
    $kelas_id = (int)($input['kelas_id'] ?? 0);
    $pesan = trim($input['pesan'] ?? '');
    
    if (!$pesan) {
        echo json_encode(['success' => false, 'error' => 'Pesan kosong']);
        exit;
    }
    
    $mhs = $conn->prepare("SELECT nama FROM mahasiswa WHERE kelas_id = ? AND status = 'aktif'");
    $mhs->bind_param('i', $kelas_id);
    $mhs->execute();
    $res = $mhs->get_result();
    $namaMahasiswa = [];
    while ($row = $res->fetch_assoc()) {
        $namaMahasiswa[] = $row['nama'];
    }
    
    if (empty($namaMahasiswa)) {
        echo json_encode(['success' => false, 'error' => 'Tidak ada mahasiswa di kelas ini']);
        exit;
    }
    
    $systemPrompt = "Anda adalah asisten dosen. Dosen memberikan pesan tentang mahasiswa yang TIDAK HADIR beserta keterangan (sakit/izin). Jika tidak ada keterangan, anggap 'izin'. Output HARUS JSON: {\"tidak_hadir\": [{\"nama\": \"Nama Persis\", \"status\": \"sakit\" atau \"izin\"}], \"reply\": \"...\"}. Daftar mahasiswa: " . implode(', ', $namaMahasiswa);
    
    $apiKey = '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';
    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'meta-llama/llama-3.3-70b-instruct',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Pesan dosen: $pesan"]
        ],
        'temperature' => 0.2,
        'max_tokens' => 500
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo json_encode(['success' => false, 'error' => "API error"]);
        exit;
    }
    
    $data = json_decode($response, true);
    $aiMessage = $data['choices'][0]['message']['content'] ?? '';
    preg_match('/\{.*\}/s', $aiMessage, $matches);
    if (!isset($matches[0])) {
        echo json_encode(['success' => false, 'error' => 'AI response invalid']);
        exit;
    }
    $aiJson = json_decode($matches[0], true);
    if (isset($aiJson['error'])) {
        echo json_encode(['success' => false, 'error' => $aiJson['error']]);
        exit;
    }
    echo json_encode(['success' => true, 'tidak_hadir' => $aiJson['tidak_hadir'] ?? []]);
    exit;
}

// ========== 5. PROSES ABSENSI MANUAL ==========
if ($action === 'proses_absensi_manual') {
    $mk_id = (int)($input['mk_id'] ?? 0);
    $kelas_id = (int)($input['kelas_id'] ?? 0);
    $tidak_hadir = $input['tidak_hadir'] ?? [];
    
    if (empty($tidak_hadir)) {
        echo json_encode(['success' => false, 'error' => 'Tidak ada data ketidakhadiran']);
        exit;
    }
    
    $jadwal = $conn->prepare("SELECT id FROM jadwal WHERE dosen_id = ? AND mata_kuliah_id = ? AND kelas_id = ? LIMIT 1");
    $jadwal->bind_param('iii', $dosen_id, $mk_id, $kelas_id);
    $jadwal->execute();
    $jadwal->bind_result($jadwal_id);
    if (!$jadwal->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Jadwal tidak ditemukan']);
        exit;
    }
    $jadwal->close();
    
    $tanggal = date('Y-m-d');
    $cek = $conn->prepare("SELECT id FROM sesi_absensi WHERE jadwal_id = ? AND tanggal = ?");
    $cek->bind_param('is', $jadwal_id, $tanggal);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows > 0) {
        $cek->bind_result($sesi_id);
        $cek->fetch();
        $cek->close();
    } else {
        $countStmt = $conn->prepare("SELECT COUNT(*) FROM sesi_absensi WHERE jadwal_id = ?");
        $countStmt->bind_param('i', $jadwal_id);
        $countStmt->execute();
        $countStmt->bind_result($total);
        $countStmt->fetch();
        $countStmt->close();
        $pertemuan_ke = $total + 1;
        $thn = $conn->query("SELECT id FROM tahun_akademik WHERE status='aktif' LIMIT 1")->fetch_assoc();
        $tahun_akademik_id = $thn ? $thn['id'] : null;
        $insert = $conn->prepare("INSERT INTO sesi_absensi (jadwal_id, tanggal, pertemuan_ke, status, tahun_akademik_id) VALUES (?, ?, ?, 'aktif', ?)");
        $insert->bind_param('isii', $jadwal_id, $tanggal, $pertemuan_ke, $tahun_akademik_id);
        $insert->execute();
        $sesi_id = $conn->insert_id;
    }
    
    $mhsList = $conn->prepare("SELECT id, nama FROM mahasiswa WHERE kelas_id = ? AND status = 'aktif'");
    $mhsList->bind_param('i', $kelas_id);
    $mhsList->execute();
    $resultMhs = $mhsList->get_result();
    $mahasiswa = [];
    while ($row = $resultMhs->fetch_assoc()) {
        $mahasiswa[$row['nama']] = $row['id'];
    }
    
    $statusMap = [];
    foreach ($mahasiswa as $nama => $id) {
        $statusMap[$id] = 'hadir';
    }
    $tidakHadirCount = 0;
    foreach ($tidak_hadir as $th) {
        $namaInput = trim($th['nama']);
        $status = ($th['status'] == 'sakit') ? 'sakit' : 'izin';
        $foundId = null;
        foreach ($mahasiswa as $namaDb => $idDb) {
            if (strtolower($namaDb) === strtolower($namaInput)) {
                $foundId = $idDb;
                break;
            }
        }
        if ($foundId) {
            $statusMap[$foundId] = $status;
            $tidakHadirCount++;
        }
    }
    
    $conn->begin_transaction();
    try {
        foreach ($statusMap as $mhs_id => $sts) {
            if ($sts === 'hadir') {
                $conn->query("DELETE FROM izin WHERE sesi_id = $sesi_id AND mahasiswa_id = $mhs_id");
                $conn->query("INSERT INTO absensi (sesi_id, mahasiswa_id, status, waktu_absen) VALUES ($sesi_id, $mhs_id, 'hadir', NOW()) ON DUPLICATE KEY UPDATE status = 'hadir'");
            } else {
                $conn->query("DELETE FROM absensi WHERE sesi_id = $sesi_id AND mahasiswa_id = $mhs_id");
                $jenis = $sts === 'sakit' ? 'sakit' : 'izin';
                $conn->query("INSERT INTO izin (sesi_id, mahasiswa_id, jenis, status, keterangan, created_at) VALUES ($sesi_id, $mhs_id, '$jenis', 'disetujui', 'Ditambahkan oleh AI', NOW()) ON DUPLICATE KEY UPDATE jenis = '$jenis', status = 'disetujui'");
            }
        }
        $conn->commit();
        $hadirCount = count($statusMap) - $tidakHadirCount;
        echo json_encode(['success' => true, 'message' => "Absensi berhasil disimpan", 'hadir_count' => $hadirCount, 'tidak_hadir_count' => $tidakHadirCount]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ========== 6. HADIR SEMUA UNTUK SESI YANG SUDAH ADA ==========
if ($action === 'hadir_semua_sesi') {
    $sesi_id = (int)($input['sesi_id'] ?? 0);
    $kelas = $conn->prepare("SELECT j.kelas_id FROM sesi_absensi s JOIN jadwal j ON s.jadwal_id = j.id WHERE s.id = ?");
    $kelas->bind_param('i', $sesi_id);
    $kelas->execute();
    $kelas->bind_result($kelas_id);
    $kelas->fetch();
    $kelas->close();
    
    $mhs = $conn->prepare("SELECT id FROM mahasiswa WHERE kelas_id = ? AND status = 'aktif'");
    $mhs->bind_param('i', $kelas_id);
    $mhs->execute();
    $res = $mhs->get_result();
    $count = 0;
    while ($row = $res->fetch_assoc()) {
        $conn->query("INSERT IGNORE INTO absensi (sesi_id, mahasiswa_id, status, waktu_absen) VALUES ($sesi_id, {$row['id']}, 'hadir', NOW())");
        $conn->query("DELETE FROM izin WHERE sesi_id = $sesi_id AND mahasiswa_id = {$row['id']}");
        $count++;
    }
    echo json_encode(['success' => true, 'message' => "$count mahasiswa ditandai HADIR"]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Aksi tidak dikenal']);
?>