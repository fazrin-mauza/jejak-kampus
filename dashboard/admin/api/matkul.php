<?php
require_once '../../../auth/check.php';

// ════════════════════════════════════════════════
// AJAX HANDLER — CRUD Mata Kuliah
// ════════════════════════════════════════════════
header('Content-Type: application/json');

if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'msg' => 'Action parameter required']);
    exit;
}

$action = $_POST['action'];

// ── LIST (dengan paginasi, search) ──
if ($action === 'list') {
    $page   = max(1, (int)($_POST['page'] ?? 1));
    $limit  = 10;
    $offset = ($page - 1) * $limit;
    $search = trim($_POST['search'] ?? '');

    $where  = [];
    $params = [];
    $types  = '';

    if ($search !== '') {
        $where[] = '(kode_mk LIKE ? OR nama_mk LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types   .= 'ss';
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total
    $countSQL = "SELECT COUNT(*) FROM mata_kuliah $whereSQL";
    $stmtCount = $conn->prepare($countSQL);
    if ($types) $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $stmtCount->bind_result($total);
    $stmtCount->fetch();
    $stmtCount->close();

    // Data
    $dataSQL = "SELECT 
                    mk.*,
                    (SELECT COUNT(DISTINCT j.kelas_id) FROM jadwal j WHERE j.mata_kuliah_id = mk.id) as total_kelas
                FROM mata_kuliah mk
                $whereSQL 
                ORDER BY mk.kode_mk ASC 
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

// ── GET SINGLE MATA KULIAH ──
if ($action === 'get') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM mata_kuliah WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo json_encode(['success' => (bool)$row, 'data' => $row]);
    exit;
}

// ── CREATE ──
if ($action === 'create') {
    $kode_mk = trim($_POST['kode_mk'] ?? '');
    $nama_mk = trim($_POST['nama_mk'] ?? '');
    $sks     = (int)($_POST['sks'] ?? 0);

    // Validasi
    if (!$kode_mk || !$nama_mk || $sks < 1 || $sks > 6) {
        echo json_encode(['success' => false, 'msg' => 'Semua field wajib diisi. SKS harus antara 1-6.']); exit;
    }

    // Cek duplikat kode MK
    $chk = $conn->prepare("SELECT id FROM mata_kuliah WHERE kode_mk = ?");
    $chk->bind_param('s', $kode_mk);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        echo json_encode(['success' => false, 'msg' => 'Kode mata kuliah sudah ada.']); exit;
    }
    $chk->close();

    $stmt = $conn->prepare("INSERT INTO mata_kuliah (kode_mk, nama_mk, sks) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $kode_mk, $nama_mk, $sks);
    $ok = $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    echo json_encode(['success' => $ok, 'msg' => $ok ? 'Mata kuliah berhasil ditambahkan.' : 'Gagal menyimpan.', 'id' => $newId]);
    exit;
}

// ── UPDATE ──
if ($action === 'update') {
    $id      = (int)($_POST['id'] ?? 0);
    $kode_mk = trim($_POST['kode_mk'] ?? '');
    $nama_mk = trim($_POST['nama_mk'] ?? '');
    $sks     = (int)($_POST['sks'] ?? 0);

    if (!$id || !$kode_mk || !$nama_mk || $sks < 1 || $sks > 6) {
        echo json_encode(['success' => false, 'msg' => 'Semua field wajib diisi. SKS harus antara 1-6.']); exit;
    }

    // Cek duplikat kode MK
    $chk = $conn->prepare("SELECT id FROM mata_kuliah WHERE kode_mk = ? AND id != ?");
    $chk->bind_param('si', $kode_mk, $id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        echo json_encode(['success' => false, 'msg' => 'Kode mata kuliah sudah ada.']); exit;
    }
    $chk->close();

    $stmt = $conn->prepare("UPDATE mata_kuliah SET kode_mk=?, nama_mk=?, sks=? WHERE id=?");
    $stmt->bind_param('ssii', $kode_mk, $nama_mk, $sks, $id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $ok, 'msg' => $ok ? 'Mata kuliah berhasil diperbarui.' : 'Gagal memperbarui.']);
    exit;
}

// ── DELETE ──
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'msg' => 'ID tidak valid.']); exit; }

    // Cek apakah mata kuliah sudah digunakan di jadwal
    $chk = $conn->prepare("SELECT COUNT(*) FROM jadwal WHERE mata_kuliah_id = ?");
    $chk->bind_param('i', $id);
    $chk->execute();
    $chk->bind_result($count);
    $chk->fetch();
    $chk->close();

    if ($count > 0) {
        echo json_encode(['success' => false, 'msg' => "Mata kuliah ini digunakan di $count jadwal. Hapus jadwal terkait terlebih dahulu."]); 
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM mata_kuliah WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $ok, 'msg' => $ok ? 'Mata kuliah berhasil dihapus.' : 'Gagal menghapus.']);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal.']);
exit;
?>