<?php
require_once '../../../auth/check.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// ─── GET OPTIONS ──────────────────────────────────
if ($action === 'get_tahun_akademik') {
    $result = $conn->query("SELECT id, tahun, semester, status FROM tahun_akademik ORDER BY status DESC, tahun DESC");
    $rows = []; while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'data' => $rows]); exit;
}
if ($action === 'get_kelas') {
    $result = $conn->query("SELECT id, nama_kelas, jurusan FROM kelas ORDER BY nama_kelas");
    $rows = []; while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'data' => $rows]); exit;
}
if ($action === 'get_dosen') {
    $result = $conn->query("SELECT id, nidn, nama FROM dosen WHERE status='aktif' ORDER BY nama");
    $rows = []; while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'data' => $rows]); exit;
}
if ($action === 'get_ruangan') {
    $result = $conn->query("SELECT id, kode_ruangan, nama_ruangan FROM ruangan WHERE status='aktif' ORDER BY kode_ruangan");
    $rows = []; while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'data' => $rows]); exit;
}
if ($action === 'get_jam_ke') {
    $result = $conn->query("SELECT id, jam_ke, jam_mulai, jam_selesai FROM jam_ke ORDER BY jam_ke");
    $rows = []; while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'data' => $rows]); exit;
}
if ($action === 'get_mata_kuliah') {
    $result = $conn->query("SELECT id, kode_mk, nama_mk, sks FROM mata_kuliah ORDER BY kode_mk");
    $rows = []; while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'data' => $rows]); exit;
}

// ─── LIST JADWAL (pakai view) ─────────────────────
if ($action === 'list') {
    $ta = (int)($_POST['tahun_akademik_id'] ?? 0);
    if (!$ta) { echo json_encode(['success' => false, 'msg' => 'Pilih TA']); exit; }
    
    $where = ["tahun_akademik_id = ?"]; $params = [$ta]; $types = 'i';
    if (!empty($_POST['hari'])) { $where[] = "hari = ?"; $params[] = $_POST['hari']; $types .= 's'; }
    if (!empty($_POST['kelas_id'])) { $where[] = "kelas_id = ?"; $params[] = (int)$_POST['kelas_id']; $types .= 'i'; }
    if (!empty($_POST['dosen_id'])) { $where[] = "dosen_id = ?"; $params[] = (int)$_POST['dosen_id']; $types .= 'i'; }
    
    $sql = "SELECT * FROM v_jadwal_lengkap WHERE " . implode(' AND ', $where) . " ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jam_mulai";
    $stmt = $conn->prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute();
    $rows = []; $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'data' => $rows]); exit;
}

// ─── GET SINGLE ───────────────────────────────────
if ($action === 'get') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare("SELECT j.*, mk.sks FROM jadwal j JOIN mata_kuliah mk ON j.mata_kuliah_id=mk.id WHERE j.id=?");
    $stmt->bind_param('i', $id); $stmt->execute(); $row = $stmt->get_result()->fetch_assoc();
    echo json_encode(['success' => (bool)$row, 'data' => $row]); exit;
}

// ─── CEK BENTROKAN (pakai stored procedure) ──────
if ($action === 'cek_bentrokan_rentang') {
    $ta = (int)($_POST['tahun_akademik_id'] ?? 0);
    $hari = $_POST['hari'] ?? '';
    $jamMulai = (int)($_POST['jam_mulai_ke'] ?? 0);
    $sks = (int)($_POST['sks'] ?? 0);
    $ruangan = (int)($_POST['ruangan_id'] ?? 0);
    $dosen = (int)($_POST['dosen_id'] ?? 0);
    $kelas = (int)($_POST['kelas_id'] ?? 0);
    $exclude = (int)($_POST['exclude_id'] ?? 0);
    
    $stmt = $conn->prepare("CALL sp_cek_bentrokan_rentang(?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isiiiiii', $ta, $hari, $jamMulai, $sks, $ruangan, $dosen, $kelas, $exclude);
    $stmt->execute();
    $result = $stmt->get_result();
    $bentrokan = [];
    while ($row = $result->fetch_assoc()) {
        if (isset($row['jam_bentrokan'])) {
            $bentrokan[] = ['jenis' => $row['jenis_error'] ?? 'bentrokan', 'pesan' => "Jam ke-{$row['jam_bentrokan']}: Sudah digunakan"];
        }
    }
    $stmt->close();
    echo json_encode(['success' => true, 'bentrokan' => $bentrokan]); exit;
}

// ─── CREATE ───────────────────────────────────────
if ($action === 'create') {
    $ta = (int)($_POST['tahun_akademik_id']); $hari = $_POST['hari']; $kelas = (int)$_POST['kelas_id'];
    $mk = (int)$_POST['mata_kuliah_id']; $dosen = (int)$_POST['dosen_id']; $ruangan = (int)$_POST['ruangan_id'];
    $jam = (int)$_POST['jam_ke_id'];
    
    if (!$ta || !$hari || !$kelas || !$mk || !$dosen || !$ruangan || !$jam) {
        echo json_encode(['success' => false, 'msg' => 'Semua field wajib diisi']); exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO jadwal (tahun_akademik_id, hari, kelas_id, mata_kuliah_id, dosen_id, ruangan_id, jam_ke_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isiiiii', $ta, $hari, $kelas, $mk, $dosen, $ruangan, $jam);
    $ok = $stmt->execute(); $id = $stmt->insert_id;
    echo json_encode(['success' => $ok, 'msg' => $ok ? 'Berhasil' : 'Gagal', 'id' => $id]); exit;
}

// ─── UPDATE ───────────────────────────────────────
if ($action === 'update') {
    $id = (int)$_POST['id']; $ta = (int)$_POST['tahun_akademik_id']; $hari = $_POST['hari'];
    $kelas = (int)$_POST['kelas_id']; $mk = (int)$_POST['mata_kuliah_id']; $dosen = (int)$_POST['dosen_id'];
    $ruangan = (int)$_POST['ruangan_id']; $jam = (int)$_POST['jam_ke_id'];
    
    $stmt = $conn->prepare("UPDATE jadwal SET tahun_akademik_id=?, hari=?, kelas_id=?, mata_kuliah_id=?, dosen_id=?, ruangan_id=?, jam_ke_id=? WHERE id=?");
    $stmt->bind_param('isiiiiii', $ta, $hari, $kelas, $mk, $dosen, $ruangan, $jam, $id);
    $ok = $stmt->execute();
    echo json_encode(['success' => $ok, 'msg' => $ok ? 'Berhasil' : 'Gagal']); exit;
}

// ─── DELETE ───────────────────────────────────────
if ($action === 'delete') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM jadwal WHERE id=?"); $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    echo json_encode(['success' => $ok, 'msg' => $ok ? 'Berhasil' : 'Gagal']); exit;
}
// ─── GET PRINT DATA ───────────────────────────────
if ($action === 'get_print_data') {
    $ta = (int)($_POST['tahun_akademik_id'] ?? 0);
    if (!$ta) { echo json_encode(['success' => false, 'msg' => 'Pilih TA']); exit; }
    
    $where = ["tahun_akademik_id = ?"]; $params = [$ta]; $types = 'i';
    if (!empty($_POST['hari'])) { $where[] = "hari = ?"; $params[] = $_POST['hari']; $types .= 's'; }
    if (!empty($_POST['kelas_id'])) { $where[] = "kelas_id = ?"; $params[] = (int)$_POST['kelas_id']; $types .= 'i'; }
    if (!empty($_POST['dosen_id'])) { $where[] = "dosen_id = ?"; $params[] = (int)$_POST['dosen_id']; $types .= 'i'; }
    
    $sql = "SELECT * FROM v_jadwal_lengkap WHERE " . implode(' AND ', $where) . " ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jam_mulai";
    $stmt = $conn->prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute();
    $rows = []; $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'data' => $rows]); exit;
}

// ─── EXPORT CSV ───────────────────────────────────
if ($action === 'export_csv') {
    $ta = (int)($_POST['tahun_akademik_id'] ?? 0);
    if (!$ta) { exit('Pilih TA'); }
    
    $where = ["tahun_akademik_id = ?"]; $params = [$ta]; $types = 'i';
    if (!empty($_POST['hari'])) { $where[] = "hari = ?"; $params[] = $_POST['hari']; $types .= 's'; }
    if (!empty($_POST['kelas_id'])) { $where[] = "kelas_id = ?"; $params[] = (int)$_POST['kelas_id']; $types .= 'i'; }
    if (!empty($_POST['dosen_id'])) { $where[] = "dosen_id = ?"; $params[] = (int)$_POST['dosen_id']; $types .= 'i'; }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="jadwal_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Hari', 'Jam Mulai', 'Jam Selesai', 'Jam Ke-', 'Mata Kuliah', 'SKS', 'Dosen', 'Kelas', 'Ruangan']);
    
    $sql = "SELECT * FROM v_jadwal_lengkap WHERE " . implode(' AND ', $where) . " ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jam_mulai";
    $stmt = $conn->prepare($sql); $stmt->bind_param($types, ...$params); $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($out, [
            $row['hari'],
            substr($row['jam_mulai'], 0, 5),
            substr($row['jam_selesai'], 0, 5),
            $row['jam_ke_list'] ?? $row['jam_mulai_ke'],
            $row['nama_mk'],
            $row['sks'],
            $row['nama_dosen'],
            $row['nama_kelas'],
            $row['nama_ruangan']
        ]);
    }
    fclose($out);
    exit;
}
echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal']);