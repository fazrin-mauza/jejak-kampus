<?php
require_once '../../../auth/check.php';

// ════════════════════════════════════════════════
// AJAX HANDLER — Monitoring Izin Mahasiswa
// ════════════════════════════════════════════════
header('Content-Type: application/json');

if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'msg' => 'Action parameter required']);
    exit;
}

$action = $_POST['action'];

// ── GET LIST DOSEN (untuk filter) ──
if ($action === 'get_dosen_list') {
    $query = "SELECT id, nama FROM dosen WHERE status = 'aktif' ORDER BY nama";
    $result = $conn->query($query);
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => $row['id'],
            'nama' => $row['nama']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// ── GET ALL IZIN (dengan paginasi, search, filter) ──
if ($action === 'get_all_izin') {
    $page    = max(1, (int)($_POST['page'] ?? 1));
    $limit   = 10;
    $offset  = ($page - 1) * $limit;
    $status  = trim($_POST['status'] ?? '');
    $jenis   = trim($_POST['jenis'] ?? '');
    $dosen_id = isset($_POST['dosen_id']) ? (int)$_POST['dosen_id'] : 0;

    $where = [];
    $joinApproval = "LEFT JOIN approval a ON i.id = a.izin_id";
    
    if ($status && $status != 'pending') {
        $joinApproval = "INNER JOIN approval a ON i.id = a.izin_id";
        $where[] = "a.status = '$status'";
    } elseif ($status == 'pending') {
        $where[] = "i.status = 'pending'";
    }
    
    if ($jenis) {
        $where[] = "i.jenis = '$jenis'";
    }
    
    if ($dosen_id > 0) {
        $where[] = "j.dosen_id = $dosen_id";
    }
    
    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total data
    $countSQL = "
        SELECT COUNT(*) as total 
        FROM izin i
        LEFT JOIN jadwal j ON i.jadwal_id = j.id
        $joinApproval
        $whereSQL
    ";
    $countResult = $conn->query($countSQL);
    $total = $countResult->fetch_assoc()['total'];
    $pages = ($total > 0) ? ceil($total / $limit) : 1;

    // Query data - PERBAIKAN: pertemuan_ke dari tabel sesi_absensi
    $query = "
        SELECT 
            i.id,
            i.jenis,
            i.keterangan,
            i.file_surat,
            i.status as izin_status,
            i.created_at,
            a.status as approval_status,
            a.catatan,
            a.approved_at,
            m.id as mahasiswa_id,
            m.nama as nama_mahasiswa,
            m.nim,
            k.nama_kelas,
            mk.nama_mk,
            d.id as dosen_id,
            d.nama as nama_dosen,
            sa.pertemuan_ke
        FROM izin i
        LEFT JOIN mahasiswa m ON i.mahasiswa_id = m.id
        LEFT JOIN kelas k ON m.kelas_id = k.id
        LEFT JOIN jadwal j ON i.jadwal_id = j.id
        LEFT JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
        LEFT JOIN dosen d ON j.dosen_id = d.id
        LEFT JOIN sesi_absensi sa ON i.sesi_id = sa.id
        $joinApproval
        $whereSQL
        ORDER BY i.created_at DESC
        LIMIT $offset, $limit
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'msg' => 'Query error: ' . $conn->error]);
        exit;
    }
    
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        // Tentukan status akhir
        $finalStatus = $row['izin_status'];
        $finalCatatan = null;
        $finalApprovedAt = null;
        
        if ($row['approval_status']) {
            $finalStatus = $row['approval_status'];
            $finalCatatan = $row['catatan'];
            $finalApprovedAt = $row['approved_at'];
        }
        
        $data[] = [
            'id' => $row['id'],
            'jenis' => $row['jenis'],
            'keterangan' => $row['keterangan'] ?? '',
            'file_surat' => $row['file_surat'],
            'status' => $finalStatus,
            'created_at' => $row['created_at'],
            'catatan' => $finalCatatan,
            'approved_at' => $finalApprovedAt,
            'mahasiswa_id' => $row['mahasiswa_id'],
            'nama_mahasiswa' => $row['nama_mahasiswa'] ?? '-',
            'nim' => $row['nim'] ?? '-',
            'nama_kelas' => $row['nama_kelas'] ?? '-',
            'nama_mk' => $row['nama_mk'] ?? '-',
            'dosen_id' => $row['dosen_id'],
            'nama_dosen' => $row['nama_dosen'] ?? '-',
            'pertemuan_ke' => $row['pertemuan_ke']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'page' => $page,
        'pages' => $pages,
        'total' => $total,
        'limit' => $limit
    ]);
    exit;
}

// ── GET DETAIL IZIN ──
if ($action === 'get_detail_izin') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'msg' => 'ID tidak valid']);
        exit;
    }
    
    // PERBAIKAN: pertemuan_ke dari tabel sesi_absensi
    $query = "
        SELECT 
            i.id,
            i.jenis,
            i.keterangan,
            i.file_surat,
            i.status as izin_status,
            i.created_at,
            a.status as approval_status,
            a.catatan,
            a.approved_at,
            m.id as mahasiswa_id,
            m.nama as nama_mahasiswa,
            m.nim,
            k.nama_kelas,
            mk.nama_mk,
            mk.sks,
            d.id as dosen_id,
            d.nama as nama_dosen,
            sa.pertemuan_ke
        FROM izin i
        LEFT JOIN mahasiswa m ON i.mahasiswa_id = m.id
        LEFT JOIN kelas k ON m.kelas_id = k.id
        LEFT JOIN jadwal j ON i.jadwal_id = j.id
        LEFT JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
        LEFT JOIN dosen d ON j.dosen_id = d.id
        LEFT JOIN sesi_absensi sa ON i.sesi_id = sa.id
        LEFT JOIN approval a ON i.id = a.izin_id
        WHERE i.id = $id
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'msg' => 'Query error: ' . $conn->error]);
        exit;
    }
    
    if ($row = $result->fetch_assoc()) {
        $finalStatus = $row['izin_status'];
        if ($row['approval_status']) {
            $finalStatus = $row['approval_status'];
        }
        
        $data = [
            'id' => $row['id'],
            'jenis' => $row['jenis'],
            'keterangan' => $row['keterangan'] ?? '',
            'file_surat' => $row['file_surat'],
            'status' => $finalStatus,
            'created_at' => $row['created_at'],
            'catatan' => $row['catatan'] ?? '',
            'approved_at' => $row['approved_at'],
            'mahasiswa_id' => $row['mahasiswa_id'],
            'nama_mahasiswa' => $row['nama_mahasiswa'] ?? '-',
            'nim' => $row['nim'] ?? '-',
            'nama_kelas' => $row['nama_kelas'] ?? '-',
            'nama_mk' => $row['nama_mk'] ?? '-',
            'sks' => $row['sks'] ?? 0,
            'dosen_id' => $row['dosen_id'],
            'nama_dosen' => $row['nama_dosen'] ?? '-',
            'pertemuan_ke' => $row['pertemuan_ke']
        ];
        
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Data tidak ditemukan']);
    }
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal.']);
exit;
?>