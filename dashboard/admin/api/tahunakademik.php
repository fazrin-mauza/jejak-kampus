<?php
require_once '../../../auth/check.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// ─── GET ALL KELAS (untuk checkbox) ───────────────
if ($action === 'get_all_kelas') {
    $result = $conn->query("SELECT id, nama_kelas, jurusan, angkatan FROM kelas ORDER BY nama_kelas");
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ─── LIST ─────────────────────────────────────────
if ($action === 'list') {
    $sql = "
        SELECT 
            ta.*,
            (SELECT COUNT(*) FROM kelas WHERE tahun_akademik_id = ta.id) as total_kelas,
            (SELECT COUNT(*) FROM jadwal WHERE tahun_akademik_id = ta.id) as total_jadwal
        FROM tahun_akademik ta
        ORDER BY ta.tahun DESC, ta.semester DESC
    ";
    $result = $conn->query($sql);
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ─── GET SINGLE ───────────────────────────────────
if ($action === 'get') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM tahun_akademik WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    
    if ($row) {
        // Get kelas yang terkait dengan TA ini
        $stmt2 = $conn->prepare("
            SELECT k.id 
            FROM kelas k 
            WHERE k.tahun_akademik_id = ?
            ORDER BY k.nama_kelas
        ");
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $kelas_ids = [];
        while ($k = $result2->fetch_assoc()) {
            $kelas_ids[] = $k['id'];
        }
        $row['kelas_ids'] = !empty($kelas_ids) ? implode(',', $kelas_ids) : '';
        $stmt2->close();
    }
    $stmt->close();
    
    echo json_encode(['success' => (bool)$row, 'data' => $row]);
    exit;
}

// ─── CREATE ───────────────────────────────────────
if ($action === 'create') {
    $tahun = trim($_POST['tahun'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $status = trim($_POST['status'] ?? 'nonaktif');
    $kelas_ids = $_POST['kelas_ids'] ?? [];
    
    if (!$tahun || !$semester) {
        echo json_encode(['success' => false, 'msg' => 'Tahun dan Semester wajib diisi']); exit;
    }
    
    if (!preg_match('/^\d{4}\/\d{4}$/', $tahun)) {
        echo json_encode(['success' => false, 'msg' => 'Format tahun harus YYYY/YYYY (contoh: 2025/2026)']); exit;
    }
    
    // Cek duplikat
    $chk = $conn->prepare("SELECT id FROM tahun_akademik WHERE tahun = ? AND semester = ?");
    $chk->bind_param('ss', $tahun, $semester);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        echo json_encode(['success' => false, 'msg' => 'Tahun Akademik sudah ada']); exit;
    }
    $chk->close();
    
    // Jika status aktif, nonaktifkan yang lain
    if ($status === 'aktif') {
        $conn->query("UPDATE tahun_akademik SET status = 'nonaktif' WHERE status = 'aktif'");
    }
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("INSERT INTO tahun_akademik (tahun, semester, status) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $tahun, $semester, $status);
        $stmt->execute();
        $ta_id = $stmt->insert_id;
        $stmt->close();
        
        // Update kelas yang dipilih
        if (!empty($kelas_ids)) {
            $stmt = $conn->prepare("UPDATE kelas SET tahun_akademik_id = ? WHERE id = ?");
            foreach ($kelas_ids as $kelas_id) {
                $stmt->bind_param('ii', $ta_id, $kelas_id);
                $stmt->execute();
            }
            $stmt->close();
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'msg' => 'Tahun Akademik berhasil ditambahkan', 'id' => $ta_id]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'msg' => 'Gagal: ' . $e->getMessage()]);
    }
    exit;
}

// ─── UPDATE ───────────────────────────────────────
if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $tahun = trim($_POST['tahun'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $status = trim($_POST['status'] ?? 'nonaktif');
    $kelas_ids = $_POST['kelas_ids'] ?? [];
    
    if (!$id || !$tahun || !$semester) {
        echo json_encode(['success' => false, 'msg' => 'Semua field wajib diisi']); exit;
    }
    
    // Cek duplikat
    $chk = $conn->prepare("SELECT id FROM tahun_akademik WHERE tahun = ? AND semester = ? AND id != ?");
    $chk->bind_param('ssi', $tahun, $semester, $id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        echo json_encode(['success' => false, 'msg' => 'Tahun Akademik sudah ada']); exit;
    }
    $chk->close();
    
    // Jika status aktif, nonaktifkan yang lain
    if ($status === 'aktif') {
        $conn->query("UPDATE tahun_akademik SET status = 'nonaktif' WHERE status = 'aktif' AND id != $id");
    }
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("UPDATE tahun_akademik SET tahun = ?, semester = ?, status = ? WHERE id = ?");
        $stmt->bind_param('sssi', $tahun, $semester, $status, $id);
        $stmt->execute();
        $stmt->close();
        
        // Reset kelas yang sebelumnya terkait
        $conn->query("UPDATE kelas SET tahun_akademik_id = NULL WHERE tahun_akademik_id = $id");
        
        // Update kelas yang dipilih
        if (!empty($kelas_ids)) {
            $stmt = $conn->prepare("UPDATE kelas SET tahun_akademik_id = ? WHERE id = ?");
            foreach ($kelas_ids as $kelas_id) {
                $stmt->bind_param('ii', $id, $kelas_id);
                $stmt->execute();
            }
            $stmt->close();
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'msg' => 'Tahun Akademik berhasil diperbarui']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'msg' => 'Gagal: ' . $e->getMessage()]);
    }
    exit;
}

// ─── SET AKTIF ────────────────────────────────────
if ($action === 'set_aktif') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'msg' => 'ID tidak valid']); exit; }
    
    $conn->query("UPDATE tahun_akademik SET status = 'nonaktif' WHERE status = 'aktif'");
    $stmt = $conn->prepare("UPDATE tahun_akademik SET status = 'aktif' WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    
    echo json_encode(['success' => $ok, 'msg' => $ok ? 'Tahun Akademik diaktifkan' : 'Gagal mengaktifkan']);
    exit;
}

// ─── DELETE ───────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'msg' => 'ID tidak valid']); exit; }
    
    // Cek apakah ada kelas terkait
    $chk = $conn->prepare("SELECT COUNT(*) FROM kelas WHERE tahun_akademik_id = ?");
    $chk->bind_param('i', $id);
    $chk->execute();
    $chk->bind_result($countKelas);
    $chk->fetch();
    $chk->close();
    
    // Cek apakah ada jadwal terkait
    $chk = $conn->prepare("SELECT COUNT(*) FROM jadwal WHERE tahun_akademik_id = ?");
    $chk->bind_param('i', $id);
    $chk->execute();
    $chk->bind_result($countJadwal);
    $chk->fetch();
    $chk->close();
    
    if ($countKelas > 0 || $countJadwal > 0) {
        echo json_encode(['success' => false, 'msg' => 'Tidak dapat menghapus. Ada kelas/jadwal terkait.']); exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM tahun_akademik WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    
    echo json_encode(['success' => $ok, 'msg' => $ok ? 'Berhasil dihapus' : 'Gagal menghapus']);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal']);
exit;
?>