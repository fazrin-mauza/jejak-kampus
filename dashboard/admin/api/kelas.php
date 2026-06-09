<?php
require_once '../../../auth/check.php';

// ════════════════════════════════════════════════
// AJAX HANDLER — CRUD Kelas
// ════════════════════════════════════════════════
header('Content-Type: application/json');

if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'msg' => 'Action parameter required']);
    exit;
}

$action = $_POST['action'];

// ── LIST (dengan paginasi, search, filter) ──
if ($action === 'list') {
    $page     = max(1, (int)($_POST['page'] ?? 1));
    $limit    = 10;
    $offset   = ($page - 1) * $limit;
    $search   = trim($_POST['search'] ?? '');
    $jurusan  = trim($_POST['jurusan'] ?? '');
    $angkatan = trim($_POST['angkatan'] ?? '');

    $where   = [];
    $params  = [];
    $types   = '';

    if ($search !== '') {
        $where[] = '(k.nama_kelas LIKE ? OR k.jurusan LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types   .= 'ss';
    }
    if ($jurusan !== '') {
        $where[] = 'k.jurusan = ?';
        $params[] = $jurusan;
        $types   .= 's';
    }
    if ($angkatan !== '') {
        $where[] = 'k.angkatan = ?';
        $params[] = $angkatan;
        $types   .= 's';
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total
    $countSQL = "SELECT COUNT(*) FROM kelas k $whereSQL";
    $stmtCount = $conn->prepare($countSQL);
    if ($types) $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $stmtCount->bind_result($total);
    $stmtCount->fetch();
    $stmtCount->close();

    // Data
    $dataSQL = "SELECT 
                    k.*,
                    ta.tahun, ta.semester, ta.status as ta_status,
                    (SELECT COUNT(*) FROM mahasiswa m WHERE m.kelas_id = k.id) as jumlah_mahasiswa
                FROM kelas k 
                LEFT JOIN tahun_akademik ta ON k.tahun_akademik_id = ta.id
                $whereSQL 
                ORDER BY k.angkatan DESC, k.nama_kelas ASC 
                LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($dataSQL);
    $params[] = $limit;
    $params[] = $offset;
    $types   .= 'ii';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'data'    => $rows,
        'total'   => $total,
        'page'    => $page,
        'pages'   => ceil($total / $limit),
        'limit'   => $limit,
    ]);
    exit;
}

// ── GET TAHUN AKADEMIK LIST ──
if ($action === 'get_tahun_akademik') {
    $result = $conn->query("
        SELECT id, tahun, semester, status 
        FROM tahun_akademik 
        ORDER BY status DESC, tahun DESC
    ");
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ── GET JURUSAN LIST (untuk filter) ──
if ($action === 'get_jurusan_list') {
    $result = $conn->query("SELECT DISTINCT jurusan FROM kelas WHERE jurusan IS NOT NULL ORDER BY jurusan");
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row['jurusan'];
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ── GET ANGKATAN LIST (untuk filter) ──
if ($action === 'get_angkatan_list') {
    $result = $conn->query("SELECT DISTINCT angkatan FROM kelas WHERE angkatan IS NOT NULL ORDER BY angkatan DESC");
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row['angkatan'];
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ── GET SINGLE KELAS ──
if ($action === 'get') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare("
        SELECT k.*, ta.tahun, ta.semester 
        FROM kelas k 
        LEFT JOIN tahun_akademik ta ON k.tahun_akademik_id = ta.id 
        WHERE k.id = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo json_encode(['success' => (bool)$row, 'data' => $row]);
    exit;
}

// ── GET ANGGOTA KELAS ──
if ($action === 'get_anggota') {
    $kelas_id = (int)($_POST['kelas_id'] ?? 0);
    
    $sql = "
        SELECT 
            m.id, m.nim, m.nama, m.status,
            COALESCE(stat.persentase, 0) as persentase
        FROM mahasiswa m
        LEFT JOIN (
            SELECT 
                a.mahasiswa_id,
                ROUND(COUNT(CASE WHEN a.status IN ('hadir','telat') THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as persentase
            FROM absensi a
            GROUP BY a.mahasiswa_id
        ) stat ON m.id = stat.mahasiswa_id
        WHERE m.kelas_id = ?
        ORDER BY m.nim ASC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $kelas_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    $stmt->close();
    
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

// ── CREATE ──
if ($action === 'create') {
    $nama_kelas        = trim($_POST['nama_kelas'] ?? '');
    $jurusan           = trim($_POST['jurusan'] ?? '');
    $angkatan          = trim($_POST['angkatan'] ?? '');
    $tahun_akademik_id = (int)($_POST['tahun_akademik_id'] ?? 0);

    if (!$nama_kelas || !$jurusan || !$angkatan || !$tahun_akademik_id) {
        echo json_encode(['success' => false, 'msg' => 'Semua field wajib diisi.']); exit;
    }

    // Cek duplikat nama kelas
    $chk = $conn->prepare("SELECT id FROM kelas WHERE nama_kelas = ?");
    $chk->bind_param('s', $nama_kelas);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        echo json_encode(['success' => false, 'msg' => 'Nama kelas sudah ada.']); exit;
    }
    $chk->close();

    $stmt = $conn->prepare("INSERT INTO kelas (nama_kelas, jurusan, angkatan, tahun_akademik_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('sssi', $nama_kelas, $jurusan, $angkatan, $tahun_akademik_id);
    $ok = $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    echo json_encode(['success' => $ok, 'msg' => $ok ? 'Kelas berhasil ditambahkan.' : 'Gagal menyimpan.', 'id' => $newId]);
    exit;
}

// ── UPDATE ──
if ($action === 'update') {
    $id                = (int)($_POST['id'] ?? 0);
    $nama_kelas        = trim($_POST['nama_kelas'] ?? '');
    $jurusan           = trim($_POST['jurusan'] ?? '');
    $angkatan          = trim($_POST['angkatan'] ?? '');
    $tahun_akademik_id = (int)($_POST['tahun_akademik_id'] ?? 0);

    if (!$id || !$nama_kelas || !$jurusan || !$angkatan || !$tahun_akademik_id) {
        echo json_encode(['success' => false, 'msg' => 'Semua field wajib diisi.']); exit;
    }

    // Cek duplikat nama kelas
    $chk = $conn->prepare("SELECT id FROM kelas WHERE nama_kelas = ? AND id != ?");
    $chk->bind_param('si', $nama_kelas, $id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        echo json_encode(['success' => false, 'msg' => 'Nama kelas sudah ada.']); exit;
    }
    $chk->close();

    $stmt = $conn->prepare("UPDATE kelas SET nama_kelas=?, jurusan=?, angkatan=?, tahun_akademik_id=? WHERE id=?");
    $stmt->bind_param('sssii', $nama_kelas, $jurusan, $angkatan, $tahun_akademik_id, $id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $ok, 'msg' => $ok ? 'Kelas berhasil diperbarui.' : 'Gagal memperbarui.']);
    exit;
}

// ── DELETE ──
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'msg' => 'ID tidak valid.']); exit; }

    // Cek apakah ada mahasiswa di kelas ini
    $chk = $conn->prepare("SELECT COUNT(*) FROM mahasiswa WHERE kelas_id = ?");
    $chk->bind_param('i', $id);
    $chk->execute();
    $chk->bind_result($count);
    $chk->fetch();
    $chk->close();

    if ($count > 0) {
        echo json_encode(['success' => false, 'msg' => "Tidak dapat menghapus kelas yang memiliki $count mahasiswa. Pindahkan mahasiswa terlebih dahulu."]); 
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM kelas WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $ok, 'msg' => $ok ? 'Kelas berhasil dihapus.' : 'Gagal menghapus.']);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal.']);
exit;
?>